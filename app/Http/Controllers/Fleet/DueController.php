<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDue;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetEmployee;
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

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $code = (string) ($row['code'] ?? '');
                if ($code === '') {
                    continue;
                }

                FleetDue::updateOrCreate(
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
                        'payload' => $row,
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'rows' => FleetDue::all(),
        ]);
    }

    /**
     * Generate monthly payroll dues during the permitted business window.
     *
     * Payroll can be generated only from the 26th through the 30th day of
     * a month (Asia/Dhaka time), and never for a future payroll month.
     * Existing monthly records are preserved so historical/paid payroll is
     * never reset by generating the same month again.
     */
    public function generatePayroll(Request $request): JsonResponse
    {
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

        $result = DB::transaction(function () use ($month, $today): array {
            $created = 0;
            $existing = 0;

            // 1. Driver Salaries
            $drivers = FleetDriver::where('status', 'Active')->get();
            foreach ($drivers as $driver) {
                $payload = $driver->payload ?? [];
                $salary = (float) ($payload['salary'] ?? 0);
                $tenure = $payload['salaryTenure'] ?? 'Monthly';

                if ($salary <= 0 || $tenure === 'Hourly') {
                    // Hourly drivers are generated per attendance log in DriverAttendanceController@sync.
                    continue;
                }

                $due = FleetDue::firstOrCreate(
                    ['code' => "PAY-DRV-{$driver->code}-{$month}"],
                    [
                        'type' => 'Driver Salary',
                        'party_type' => 'Driver',
                        'party_id' => $driver->code,
                        'source_type' => 'Payroll',
                        'source_id' => $month,
                        'amount' => $salary,
                        'status' => 'Pending',
                        'due_date' => $month.'-05',
                        'payload' => [
                            'month' => $month,
                            'tenure' => $tenure,
                            'driverName' => $payload['fullName'] ?? $driver->name,
                            'generatedAt' => $today->toIso8601String(),
                        ],
                    ]
                );

                $due->wasRecentlyCreated ? $created++ : $existing++;
            }

            // 2. Employee Salaries
            $employees = FleetEmployee::where('status', 'Active')->get();
            foreach ($employees as $employee) {
                $payload = $employee->payload ?? [];
                $salary = (float) ($payload['salary'] ?? 0);
                $tenure = $payload['salaryTenure'] ?? 'Monthly';

                if ($salary <= 0) {
                    continue;
                }

                // Without an Employee Attendance module, use the existing 200-hour monthly calculation.
                $amount = $tenure === 'Hourly' ? ($salary * 200) : $salary;

                $due = FleetDue::firstOrCreate(
                    ['code' => "PAY-EMP-{$employee->code}-{$month}"],
                    [
                        'type' => 'Employee Salary',
                        'party_type' => 'Employee',
                        'party_id' => $employee->code,
                        'source_type' => 'Payroll',
                        'source_id' => $month,
                        'amount' => $amount,
                        'status' => 'Pending',
                        'due_date' => $month.'-05',
                        'payload' => [
                            'month' => $month,
                            'tenure' => $tenure,
                            'employeeName' => $payload['fullName'] ?? $employee->name,
                            'generatedAt' => $today->toIso8601String(),
                        ],
                    ]
                );

                $due->wasRecentlyCreated ? $created++ : $existing++;
            }

            // 3. Vehicle Monthly Rents (kept in the existing monthly generation flow)
            $vehicles = \App\Models\Fleet\FleetVehicle::where('status', 'Active')->get();
            foreach ($vehicles as $vehicle) {
                $payload = $vehicle->payload ?? [];
                $rent = (float) ($payload['rent'] ?? 0);
                $vendor = $payload['vendor'] ?? null;

                if ($rent <= 0 || ! $vendor) {
                    continue;
                }

                $due = FleetDue::firstOrCreate(
                    ['code' => "RENT-VHL-{$vehicle->code}-{$month}"],
                    [
                        'type' => 'Vehicle Rent',
                        'party_type' => 'Vendor',
                        'party_id' => $vendor,
                        'source_type' => 'VehicleRent',
                        'source_id' => $month,
                        'amount' => $rent,
                        'status' => 'Pending',
                        'due_date' => $month.'-01',
                        'payload' => [
                            'month' => $month,
                            'vehicleId' => $vehicle->code,
                            'regNo' => $payload['regNo'] ?? '',
                            'generatedAt' => $today->toIso8601String(),
                        ],
                    ]
                );

                $due->wasRecentlyCreated ? $created++ : $existing++;
            }

            return compact('created', 'existing');
        });

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

        return response()->json([
            'ok' => true,
            'message' => $message,
            'created' => $result['created'],
            'existing' => $result['existing'],
            'rows' => FleetDue::query()->orderByDesc('due_date')->orderByDesc('id')->get(),
        ]);
    }

    public function records(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'rows' => FleetDue::query()->orderByDesc('due_date')->orderByDesc('id')->get(),
        ]);
    }
}
