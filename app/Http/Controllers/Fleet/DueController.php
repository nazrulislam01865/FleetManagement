<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDue;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetVehicle;
use App\Services\FleetDueService;
use App\Services\FleetPayrollCalculator;
use App\Services\FleetRecordOwnershipService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DueController extends FleetBaseController
{
    protected string $activeMenu = 'dues';
    protected string $view = 'fleetman.dues';
    protected string $page = 'dues';
    protected string $resource = 'dues';
    protected string $idKey = 'code';
    protected string $nameKey = 'type';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetDue::class;

    /**
     * Override sync to handle specific fields for Dues if needed, 
     * or we just use FleetBaseController's sync, but we need to map payload to columns.
     */
    public function sync(Request $request): JsonResponse
    {
        $rows = is_array($request->input('rows')) 
            ? $request->input('rows') 
            : json_decode((string) $request->input('rows', '[]'), true);

        if (!is_array($rows)) {
            return response()->json(['error' => 'Invalid rows payload'], 400);
        }

        $createdCodes = DB::transaction(function () use ($rows): array {
            $createdCodes = [];

            foreach ($rows as $row) {
                $code = (string) ($row['code'] ?? '');
                if ($code === '') {
                    continue;
                }

                $due = FleetDue::updateOrCreate(
                    ['code' => $code],
                    [
                        'type' => $row['type'] ?? 'General',
                        'party_type' => $row['party_type'] ?? null,
                        'party_id' => $row['party_id'] ?? null,
                        'source_type' => $row['source_type'] ?? null,
                        'source_id' => $row['source_id'] ?? null,
                        'amount' => $row['amount'] ?? 0,
                        'status' => $row['status'] ?? 'Pending',
                        'due_date' => $row['due_date'] ?? null,
                        'payload' => $this->withoutRecordMetadata($row),
                    ]
                );

                if ($due->wasRecentlyCreated) {
                    $createdCodes[] = $code;
                }
            }

            return array_values(array_unique($createdCodes));
        });

        $userId = (int) ($request->user()?->id ?? 0);
        if ($userId > 0 && $createdCodes !== []) {
            $ownership = app(FleetRecordOwnershipService::class);
            foreach ($createdCodes as $code) {
                $ownership->claimRecord('dues', (string) $code, $userId);
            }
        }

        return response()->json([
            'ok' => true,
            'rows' => $this->latestDues(),
        ]);
    }

    /**
     * Generate one aggregate due per payable entity for the selected month.
     *
     * The saved salary/payment tenure controls the calculation:
     * Monthly, Contract and Other keep the saved amount; Weekly is accrued by
     * the days in the selected month; Daily is multiplied by the month length;
     * driver Hourly payroll continues to come from completed attendance logs;
     * employee Hourly payroll keeps the existing 200-hour monthly basis because
     * there is no employee-attendance module yet.
     *
     * Every generated record uses a deterministic code and the database's
     * unique code constraint, so repeated or simultaneous generation cannot
     * create a duplicate payroll due.
     */
    public function generatePayroll(
        Request $request,
        FleetDueService $dueService,
        FleetPayrollCalculator $calculator
    ): JsonResponse {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ], [
            'month.required' => 'Select a payroll month.',
            'month.date_format' => 'The payroll month must use YYYY-MM format.',
        ]);

        $today = CarbonImmutable::now('Asia/Dhaka');
        $day = $today->day;

        if ($day < 26 || $day > 30) {
            return response()->json([
                'ok' => false,
                'code' => 'PAYROLL_WINDOW_CLOSED',
                'message' => 'Monthly payroll can only be generated from the 26th through the 30th of each month. Today is '.$today->format('d M Y').'.',
            ], 422);
        }

        $month = $validated['month'];
        $payrollMonth = CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Dhaka')->startOfMonth();
        $currentMonth = $today->startOfMonth();

        if ($payrollMonth->isAfter($currentMonth)) {
            return response()->json([
                'ok' => false,
                'code' => 'FUTURE_PAYROLL_MONTH',
                'message' => 'Future-month payroll cannot be generated. Select '.$currentMonth->format('Y-m').' or an earlier month.',
            ], 422);
        }

        $earliestAllowedMonth = $currentMonth->subMonths(2);

        if ($payrollMonth->isBefore($earliestAllowedMonth)) {
            return response()->json([
                'ok' => false,
                'code' => 'PAYROLL_MONTH_OUT_OF_RANGE',
                'message' => 'Payroll can only be generated for the current month or the previous two months. Select a month from '
                    .$earliestAllowedMonth->format('Y-m').' through '.$currentMonth->format('Y-m').'.',
            ], 422);
        }

        $creatorUserId = (int) ($request->user()?->id ?? 0);

        $result = DB::transaction(function () use (
            $month,
            $payrollMonth,
            $today,
            $creatorUserId,
            $dueService,
            $calculator
        ): array {
            $created = 0;
            $existing = 0;
            $skipped = 0;

            // Driver payroll: hourly drivers are generated exactly once per
            // completed attendance log by DriverAttendanceController.
            FleetDriver::query()
                ->where('status', 'Active')
                ->orderBy('id')
                ->each(function (FleetDriver $driver) use (
                    $month,
                    $payrollMonth,
                    $today,
                    $creatorUserId,
                    $dueService,
                    $calculator,
                    &$created,
                    &$existing,
                    &$skipped
                ): void {
                    $payload = is_array($driver->payload) ? $driver->payload : [];
                    $salary = (float) ($payload['salary'] ?? 0);
                    $tenure = trim((string) ($payload['salaryTenure'] ?? 'Monthly')) ?: 'Monthly';

                    if (strcasecmp($tenure, 'Hourly') === 0) {
                        $skipped++;
                        return;
                    }

                    $calculation = $calculator->monthlyAmount($salary, $tenure, $payrollMonth);
                    if (! $calculation || $calculation['amount'] <= 0) {
                        $skipped++;
                        return;
                    }

                    $saved = $dueService->createOnce([
                        'code' => "PAY-DRV-{$driver->code}-{$month}",
                        'type' => 'Driver Salary',
                        'party_type' => 'Driver',
                        'party_id' => $driver->code,
                        'source_type' => 'Payroll',
                        'source_id' => "driver:{$driver->code}:{$month}",
                        'amount' => $calculation['amount'],
                        'status' => 'Pending',
                        'due_date' => $month.'-05',
                        'payload' => $this->payrollPayload(
                            $month,
                            $payrollMonth,
                            $today,
                            $salary,
                            $calculation,
                            [
                                'driverName' => $payload['fullName'] ?? $driver->name,
                                'driverId' => $driver->code,
                            ]
                        ),
                    ], $creatorUserId);

                    $saved['created'] ? $created++ : $existing++;
                });

            // Employee payroll: the existing 200-hour basis is retained for
            // Hourly employees until an employee-attendance module is added.
            FleetEmployee::query()
                ->where('status', 'Active')
                ->orderBy('id')
                ->each(function (FleetEmployee $employee) use (
                    $month,
                    $payrollMonth,
                    $today,
                    $creatorUserId,
                    $dueService,
                    $calculator,
                    &$created,
                    &$existing,
                    &$skipped
                ): void {
                    $payload = is_array($employee->payload) ? $employee->payload : [];
                    $salary = (float) ($payload['salary'] ?? 0);
                    $tenure = trim((string) ($payload['salaryTenure'] ?? 'Monthly')) ?: 'Monthly';
                    $hourlyUnits = strcasecmp($tenure, 'Hourly') === 0 ? 200.0 : null;
                    $calculation = $calculator->monthlyAmount($salary, $tenure, $payrollMonth, $hourlyUnits);

                    if (! $calculation || $calculation['amount'] <= 0) {
                        $skipped++;
                        return;
                    }

                    $saved = $dueService->createOnce([
                        'code' => "PAY-EMP-{$employee->code}-{$month}",
                        'type' => 'Employee Salary',
                        'party_type' => 'Employee',
                        'party_id' => $employee->code,
                        'source_type' => 'Payroll',
                        'source_id' => "employee:{$employee->code}:{$month}",
                        'amount' => $calculation['amount'],
                        'status' => 'Pending',
                        'due_date' => $month.'-05',
                        'payload' => $this->payrollPayload(
                            $month,
                            $payrollMonth,
                            $today,
                            $salary,
                            $calculation,
                            [
                                'employeeName' => $payload['fullName'] ?? $employee->name,
                                'employeeId' => $employee->code,
                                'assumedHours' => $hourlyUnits,
                            ]
                        ),
                    ], $creatorUserId);

                    $saved['created'] ? $created++ : $existing++;
                });

            // Vehicle owner rent and optional assigned-driver payment are kept
            // as separate monthly dues because they may use different cycles.
            FleetVehicle::query()
                ->where('status', 'Active')
                ->orderBy('id')
                ->each(function (FleetVehicle $vehicle) use (
                    $month,
                    $payrollMonth,
                    $today,
                    $creatorUserId,
                    $dueService,
                    $calculator,
                    &$created,
                    &$existing,
                    &$skipped
                ): void {
                    $payload = is_array($vehicle->payload) ? $vehicle->payload : [];
                    $vendor = trim((string) ($payload['vendor'] ?? ''));
                    $vehicleRate = (float) ($payload['vehicleRentalAmount'] ?? $payload['rent'] ?? 0);
                    $vehicleCycle = trim((string) ($payload['vehiclePaymentCycle'] ?? 'Monthly')) ?: 'Monthly';
                    $vehicleCalculation = $calculator->monthlyAmount($vehicleRate, $vehicleCycle, $payrollMonth);

                    if ($vendor !== '' && $vehicleCalculation && $vehicleCalculation['amount'] > 0) {
                        $saved = $dueService->createOnce([
                            'code' => "RENT-VHL-{$vehicle->code}-{$month}",
                            'type' => 'Vehicle Rent',
                            'party_type' => 'Vendor',
                            'party_id' => $vendor,
                            'source_type' => 'VehicleRent',
                            'source_id' => "vehicle:{$vehicle->code}:{$month}",
                            'amount' => $vehicleCalculation['amount'],
                            'status' => 'Pending',
                            'due_date' => $month.'-01',
                            'payload' => $this->payrollPayload(
                                $month,
                                $payrollMonth,
                                $today,
                                $vehicleRate,
                                $vehicleCalculation,
                                [
                                    'vehicleId' => $vehicle->code,
                                    'regNo' => $payload['regNo'] ?? '',
                                    'vendor' => $vendor,
                                ]
                            ),
                        ], $creatorUserId);

                        $saved['created'] ? $created++ : $existing++;
                    } elseif ($vehicleRate > 0) {
                        $skipped++;
                    }

                    $withDriver = strcasecmp((string) ($payload['rentalType'] ?? ''), 'With Driver') === 0;
                    $isDoubleShift = strcasecmp((string) ($payload['usage'] ?? ''), 'Double shift') === 0;
                    $driverPayments = [
                        [
                            'position' => 1,
                            'party' => trim((string) ($payload['driver'] ?? '')),
                            'rate' => (float) ($payload['driverPaymentAmount'] ?? 0),
                            'cycle' => trim((string) ($payload['driverPaymentCycle'] ?? 'Monthly')) ?: 'Monthly',
                            'code' => "RENT-DRV-{$vehicle->code}-{$month}",
                            'source_id' => "vehicle-driver:{$vehicle->code}:{$month}",
                        ],
                    ];

                    if ($isDoubleShift) {
                        $driverPayments[] = [
                            'position' => 2,
                            'party' => trim((string) ($payload['secondDriver'] ?? '')),
                            'rate' => (float) ($payload['secondDriverPaymentAmount'] ?? 0),
                            'cycle' => trim((string) ($payload['secondDriverPaymentCycle'] ?? 'Monthly')) ?: 'Monthly',
                            'code' => "RENT-DRV2-{$vehicle->code}-{$month}",
                            'source_id' => "vehicle-driver-2:{$vehicle->code}:{$month}",
                        ];
                    }

                    $processedDrivers = [];
                    foreach ($driverPayments as $driverPayment) {
                        $driverParty = $driverPayment['party'];
                        $driverKey = mb_strtolower($driverParty);
                        if (! $withDriver || $driverParty === '' || isset($processedDrivers[$driverKey])) {
                            continue;
                        }

                        $driverCalculation = $calculator->monthlyAmount(
                            $driverPayment['rate'],
                            $driverPayment['cycle'],
                            $payrollMonth
                        );
                        if (! $driverCalculation || $driverCalculation['amount'] <= 0) {
                            continue;
                        }

                        $processedDrivers[$driverKey] = true;
                        $saved = $dueService->createOnce([
                            'code' => $driverPayment['code'],
                            'type' => 'Vehicle Driver Payment',
                            'party_type' => 'Driver',
                            'party_id' => $driverParty,
                            'source_type' => 'VehicleDriverPayment',
                            'source_id' => $driverPayment['source_id'],
                            'amount' => $driverCalculation['amount'],
                            'status' => 'Pending',
                            'due_date' => $month.'-01',
                            'payload' => $this->payrollPayload(
                                $month,
                                $payrollMonth,
                                $today,
                                $driverPayment['rate'],
                                $driverCalculation,
                                [
                                    'vehicleId' => $vehicle->code,
                                    'regNo' => $payload['regNo'] ?? '',
                                    'driver' => $driverParty,
                                    'driverPosition' => $driverPayment['position'],
                                ]
                            ),
                        ], $creatorUserId);

                        $saved['created'] ? $created++ : $existing++;
                    }
                });

            return compact('created', 'existing', 'skipped');
        }, 3);

        if (($result['created'] + $result['existing']) === 0) {
            $message = "No eligible active payroll or vehicle-rent records were found for {$month}.";
        } elseif ($result['created'] > 0) {
            $message = "Payroll dues generated and stored for {$month}. {$result['created']} new record(s) created.";

            if ($result['existing'] > 0) {
                $message .= " {$result['existing']} existing record(s) were preserved.";
            }
        } else {
            $message = "Payroll for {$month} is already stored. No duplicate records were created.";
        }

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} ineligible or attendance-based record(s) were skipped.";
        }

        return response()->json([
            'ok' => true,
            'message' => $message,
            'created' => $result['created'],
            'existing' => $result['existing'],
            'skipped' => $result['skipped'],
            'rows' => $this->latestDues(),
        ]);
    }

    private function payrollPayload(
        string $month,
        CarbonImmutable $payrollMonth,
        CarbonImmutable $generatedAt,
        float $rate,
        array $calculation,
        array $extra = []
    ): array {
        return array_filter(array_merge([
            'month' => $month,
            'periodStart' => $payrollMonth->startOfMonth()->toDateString(),
            'periodEnd' => $payrollMonth->endOfMonth()->toDateString(),
            'tenure' => $calculation['tenure'],
            'baseRate' => round($rate, 2),
            'units' => $calculation['units'],
            'unitLabel' => $calculation['unit_label'],
            'formula' => $calculation['formula'],
            'generatedAt' => $generatedAt->toIso8601String(),
        ], $extra), fn ($value): bool => $value !== null && $value !== '');
    }

    public function records(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'rows' => $this->latestDues(),
        ]);
    }

    /**
     * Keep the newest Accounts Payable & Dues entry at the top consistently
     * after initial load, payroll generation and status updates.
     */
    private function latestDues()
    {
        $rows = FleetDue::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $creatorNames = app(FleetRecordOwnershipService::class)->creatorNames(
            'dues',
            $rows->pluck('code')->all()
        );

        return $rows->each(function (FleetDue $row) use ($creatorNames): void {
            $row->setAttribute(
                'creatorName',
                $creatorNames[(string) $row->code] ?? 'System / Legacy'
            );
        });
    }
}
