<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetDue;
use App\Support\FleetDuration;
use App\Services\FleetRecordOwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DriverAttendanceController extends FleetBaseController
{
    protected string $activeMenu = 'drive-log';
    protected string $view = 'fleetman.driver-attendance';
    protected string $page = 'driver-attendance';
    protected string $resource = 'driver_attendance';
    protected string $idKey = 'logId';
    protected string $nameKey = 'driver';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetDriverAttendance::class;

    /**
     * Save one log without submitting and revalidating the complete table.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'row' => ['required', 'array'],
        ]);

        $row = $this->cleanPersistenceMetadata(
            $this->withCalculatedDuration($validated['row'])
        );
        $this->validateAttendanceRows([$row]);

        $code = trim((string) ($row[$this->idKey] ?? ''));
        if ($code === '') {
            throw ValidationException::withMessages([
                'row.logId' => 'Attendance ID is required.',
            ]);
        }

        $isNewRecord = ! FleetDriverAttendance::query()->where('code', $code)->exists();

        $record = DB::transaction(function () use ($code, $row): FleetDriverAttendance {
            $record = FleetDriverAttendance::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => trim((string) ($row[$this->nameKey] ?? '')) ?: $code,
                    'status' => trim((string) ($row[$this->statusKey] ?? '')) ?: null,
                    'payload' => $row,
                ]
            );

            $this->syncAttendanceDue($row);

            return $record->refresh();
        });

        if ($isNewRecord) {
            app(FleetRecordOwnershipService::class)->claimRecord(
                'driver_attendance',
                $code,
                (int) $request->user()->id
            );
        }

        return response()->json([
            'ok' => true,
            'message' => $isNewRecord ? 'Attendance saved successfully.' : 'Attendance updated successfully.',
            'record' => $this->attendancePayload($record),
            'rows' => $this->syncResponseRows(FleetDriverAttendance::class, [$row], $this->idKey),
            'can_view_list' => $this->currentUserCanViewPage(),
        ]);
    }

    /**
     * Delete only the selected log. Remaining logs are not revalidated.
     */
    public function destroy(string $code): JsonResponse
    {
        $record = FleetDriverAttendance::query()->where('code', $code)->firstOrFail();

        DB::transaction(function () use ($record, $code): void {
            if (Schema::hasTable('fleet_dues')) {
                FleetDue::query()->where('code', 'PAY-LOG-'.$code)->delete();
            }
            $record->delete();
        });

        $ownership = app(FleetRecordOwnershipService::class);
        $ownership->forgetRecord('driver_attendance', $code);
        $ownership->forgetRecord('dues', 'PAY-LOG-'.$code);

        return response()->json([
            'ok' => true,
            'message' => 'Attendance deleted successfully.',
            'rows' => $this->currentUserCanViewPage()
                ? $this->recordsFor(FleetDriverAttendance::class)
                : [],
            'can_view_list' => $this->currentUserCanViewPage(),
        ]);
    }

    /**
     * Compatibility endpoint for older cached JavaScript.
     * Only a new or changed row is validated, so old demo/history rows cannot
     * block saving or deleting another log.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*' => ['array'],
        ]);

        $rows = collect($validated['rows'])
            ->map(fn (array $row): array => $this->cleanPersistenceMetadata(
                $this->withCalculatedDuration($row)
            ))
            ->values()
            ->all();

        $existingPayloads = FleetDriverAttendance::query()
            ->get()
            ->mapWithKeys(fn (FleetDriverAttendance $record): array => [
                $record->code => is_array($record->payload) ? $record->payload : [],
            ]);

        $changedRows = collect($rows)
            ->filter(function (array $row) use ($existingPayloads): bool {
                $code = trim((string) ($row[$this->idKey] ?? ''));
                if ($code === '' || ! $existingPayloads->has($code)) {
                    return true;
                }

                return $this->comparableAttendancePayload($row)
                    !== $this->comparableAttendancePayload((array) $existingPayloads->get($code));
            })
            ->values()
            ->all();

        $this->validateAttendanceRows($changedRows);

        $existingCodes = $existingPayloads->keys()->values();
        $incomingCodes = collect($rows)
            ->pluck($this->idKey)
            ->map(fn ($code): string => trim((string) $code))
            ->filter()
            ->values();
        $deletedCodes = $existingCodes->diff($incomingCodes)->values();

        $request->merge(['rows' => $rows]);
        $response = parent::sync($request);

        DB::transaction(function () use ($rows, $deletedCodes): void {
            if (Schema::hasTable('fleet_dues')) {
                foreach ($deletedCodes as $code) {
                    FleetDue::query()->where('code', 'PAY-LOG-'.$code)->delete();
                }
            }

            foreach ($rows as $row) {
                $this->syncAttendanceDue($row);
            }
        });

        return $response;
    }

    /**
     * Validate every completed/non-draft log before persistence.
     * Drafts stay permissive except that they must keep their generated Log ID.
     */
    protected function validateAttendanceRows(array $rows): void
    {
        $errors = [];
        $masters = $this->attendanceMastersFromDatabase();
        $contracts = collect($masters['contracts'] ?? []);
        $drivers = collect($masters['drivers'] ?? [])->filter(fn ($driver) => is_array($driver));
        $yards = collect($masters['yards'] ?? [])->filter(fn ($yard) => is_array($yard));
        $allowedStatuses = $this->values('attendance_status');
        if ($allowedStatuses === []) {
            $allowedStatuses = ['Initiated', 'Running', 'Completed'];
        }

        foreach ($rows as $index => $row) {
            if (trim((string) ($row['logId'] ?? '')) === '') {
                $errors["rows.$index.logId"] = 'Attendance ID is required.';
            }

            $status = trim((string) ($row['status'] ?? ''));
            if (strcasecmp($status, 'Draft') === 0) {
                continue;
            }

            $required = [
                'date' => 'Date',
                'contract' => 'Contract',
                'vehicle' => 'Vehicle',
                'driver' => 'Driver',
                'status' => 'Status',
            ];

            foreach ($required as $key => $label) {
                if (trim((string) ($row[$key] ?? '')) === '') {
                    $errors["rows.$index.$key"] = "$label is required.";
                }
            }

            if (in_array($status, ['Running', 'Completed'], true)
                && trim((string) ($row['startTime'] ?? '')) === '') {
                $errors["rows.$index.startTime"] = 'Start time is required for a running or completed trip.';
            }

            $date = trim((string) ($row['date'] ?? ''));
            if ($date !== '' && ! $this->isValidDate($date)) {
                $errors["rows.$index.date"] = 'Enter a valid date.';
            }

            $startTime = trim((string) ($row['startTime'] ?? ''));
            if ($startTime !== '' && ! $this->isValidTime($startTime)) {
                $errors["rows.$index.startTime"] = 'Enter a valid start time.';
            }

            $endTime = trim((string) ($row['endTime'] ?? ''));
            if ($endTime !== '' && ! $this->isValidTime($endTime)) {
                $errors["rows.$index.endTime"] = 'Enter a valid end time.';
            }

            if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
                $errors["rows.$index.status"] = 'Select a valid attendance status.';
            }

            $yardText = trim((string) ($row['yard'] ?? ''));
            $yardId = trim((string) ($row['yardId'] ?? ''));
            if ($yardText !== '' || $yardId !== '') {
                $selectedYard = $yards->first(function (array $yard) use ($yardText, $yardId): bool {
                    $aliases = [
                        $yard['label'] ?? null,
                        $yard['id'] ?? null,
                        $yard['code'] ?? null,
                        $yard['name'] ?? null,
                    ];

                    return ($yardText !== '' && $this->matchesAttendanceValue($yardText, $aliases))
                        || ($yardId !== '' && $this->matchesAttendanceValue($yardId, $aliases));
                });

                if (! $selectedYard) {
                    $errors["rows.$index.yard"] = 'Select a yard from the saved yard list.';
                }
            }

            $contractText = trim((string) ($row['contract'] ?? ''));
            if ($contractText === '') {
                continue;
            }

            $contract = $contracts->first(function (array $contract) use ($contractText): bool {
                return $this->matchesAttendanceValue($contractText, [
                    $contract['label'] ?? null,
                    $contract['id'] ?? null,
                    $contract['contractId'] ?? null,
                ]);
            });

            if (! $contract) {
                $errors["rows.$index.contract"] = 'Select a contract from the saved contract list.';
                continue;
            }

            $assignments = collect($contract['assignments'] ?? [])->filter(fn ($assignment) => is_array($assignment));
            $vehicleText = trim((string) ($row['vehicle'] ?? ''));
            $vehicleAssignments = $assignments->filter(function (array $assignment) use ($vehicleText): bool {
                return $vehicleText !== '' && $this->matchesAttendanceValue($vehicleText, [
                    $assignment['vehicleLabel'] ?? null,
                    $assignment['vehicle'] ?? null,
                    $assignment['vehicleName'] ?? null,
                    $assignment['vehicleId'] ?? null,
                ]);
            });

            if ($vehicleText !== '' && $vehicleAssignments->isEmpty()) {
                $errors["rows.$index.vehicle"] = 'Select a vehicle assigned to the selected contract.';
                continue;
            }

            $driverText = trim((string) ($row['driver'] ?? ''));
            $driverId = trim((string) ($row['driverId'] ?? ''));
            $assignmentType = strtolower(trim((string) ($row['driverAssignmentType'] ?? '')));

            if ($assignmentType !== '' && ! in_array($assignmentType, ['main', 'spare'], true)) {
                $errors["rows.$index.driverAssignmentType"] = 'Select Assign Main Driver or Assign Spare Driver.';
                continue;
            }

            if ($assignmentType === 'main') {
                $vehicleAssignment = $vehicleAssignments->first();
                $mainDriverValues = $this->attendanceContractDriverValues($vehicleAssignment);
                $hasMainDriver = collect($mainDriverValues)->contains(fn ($value) => filled($value));
                $matchesMainDriver = ($driverText !== '' && $this->matchesAttendanceValue($driverText, $mainDriverValues))
                    || ($driverId !== '' && $this->matchesAttendanceValue($driverId, $mainDriverValues));

                if (! $hasMainDriver) {
                    $errors["rows.$index.driver"] = 'This contract has no driver assigned to the selected vehicle. Choose Assign Spare Driver.';
                } elseif (! $matchesMainDriver) {
                    $errors["rows.$index.driver"] = 'The selected driver does not match the driver assigned to this vehicle in the selected contract.';
                }
            } elseif ($assignmentType === 'spare') {
                $selectedDriver = $drivers->first(function (array $driver) use ($driverText, $driverId): bool {
                    $aliases = [
                        $driver['label'] ?? null,
                        $driver['id'] ?? null,
                        $driver['code'] ?? null,
                        $driver['name'] ?? null,
                    ];

                    return ($driverText !== '' && $this->matchesAttendanceValue($driverText, $aliases))
                        || ($driverId !== '' && $this->matchesAttendanceValue($driverId, $aliases));
                });

                if (! $selectedDriver) {
                    $errors["rows.$index.driver"] = 'Select a valid active spare or other driver from the driver list.';
                    continue;
                }

                $vehicleAssignment = $vehicleAssignments->first();
                $mainDriverValues = $this->attendanceContractDriverValues($vehicleAssignment);
                $selectedDriverValues = [
                    $selectedDriver['label'] ?? null,
                    $selectedDriver['id'] ?? null,
                    $selectedDriver['code'] ?? null,
                    $selectedDriver['name'] ?? null,
                ];
                $isMainDriver = collect($selectedDriverValues)
                    ->filter(fn ($value) => filled($value))
                    ->contains(fn ($value) => $this->matchesAttendanceValue((string) $value, $mainDriverValues));

                if ($isMainDriver) {
                    $errors["rows.$index.driver"] = 'Use Assign Main Driver for the driver assigned in this contract, or choose a different spare driver.';
                }
            } elseif ($driverText !== '' && ! $vehicleAssignments->contains(function (array $assignment) use ($driverText, $driverId): bool {
                $aliases = [
                    $assignment['driverLabel'] ?? null,
                    $assignment['driver'] ?? null,
                    $assignment['driverName'] ?? null,
                    $assignment['driverId'] ?? null,
                ];

                return $this->matchesAttendanceValue($driverText, $aliases)
                    || ($driverId !== '' && $this->matchesAttendanceValue($driverId, $aliases));
            })) {
                // Backward compatibility for logs created before the main/spare
                // assignment option was introduced.
                $errors["rows.$index.driver"] = 'Select a driver assigned to the selected contract and vehicle.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function withCalculatedDuration(array $row): array
    {
        $startTime = trim((string) ($row['startTime'] ?? ''));
        $endTime = trim((string) ($row['endTime'] ?? ''));

        if (! $this->isValidTime($startTime) || ! $this->isValidTime($endTime)) {
            return $row;
        }

        $minutes = FleetDuration::minutesBetween($startTime, $endTime);
        $row['totalMinutes'] = $minutes;
        $row['totalTime'] = FleetDuration::decimalHours($minutes);
        $row['hours'] = FleetDuration::format($minutes);

        return $row;
    }

    /**
     * Return the authoritative driver aliases for one contract/vehicle assignment.
     *
     * The driver selected in the Contract module is the main driver for Add Log.
     * Legacy mainDriver fields are used only as a fallback for older stored data
     * that may not contain the direct contract driver fields.
     */
    protected function attendanceContractDriverValues(array $assignment): array
    {
        $contractDriverValues = [
            $assignment['driverLabel'] ?? null,
            $assignment['driver'] ?? null,
            $assignment['driverId'] ?? null,
            $assignment['driverName'] ?? null,
        ];

        if (collect($contractDriverValues)->contains(fn ($value) => filled($value))) {
            return $contractDriverValues;
        }

        $legacyMainDriver = is_array($assignment['mainDriver'] ?? null)
            ? $assignment['mainDriver']
            : [];

        return [
            $legacyMainDriver['label'] ?? null,
            $legacyMainDriver['id'] ?? null,
            $legacyMainDriver['code'] ?? null,
            $legacyMainDriver['name'] ?? null,
            $assignment['mainDriverLabel'] ?? null,
            $assignment['mainDriverId'] ?? null,
            $assignment['mainDriverName'] ?? null,
        ];
    }

    protected function matchesAttendanceValue(string $needle, array $values): bool
    {
        $normalizedNeedle = strtolower((string) preg_replace('/\s+/', ' ', trim($needle)));

        return $normalizedNeedle !== '' && collect($values)
            ->filter(fn ($value) => filled($value))
            ->contains(function ($value) use ($normalizedNeedle): bool {
                $normalizedValue = strtolower((string) preg_replace('/\s+/', ' ', trim((string) $value)));

                return $normalizedValue === $normalizedNeedle;
            });
    }

    protected function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    protected function isValidTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    private function syncAttendanceDue(array $row): void
    {
        if (! Schema::hasTable('fleet_dues')) {
            return;
        }

        $logId = trim((string) ($row[$this->idKey] ?? ''));
        if ($logId === '') {
            return;
        }

        $dueCode = 'PAY-LOG-'.$logId;
        if (trim((string) ($row[$this->statusKey] ?? '')) !== 'Completed') {
            FleetDue::query()->where('code', $dueCode)->delete();
            app(FleetRecordOwnershipService::class)->forgetRecord('dues', $dueCode);
            return;
        }

        $driverText = trim((string) ($row['driver'] ?? ''));
        $driverCode = trim((string) ($row['driverId'] ?? ''));
        if ($driverCode === '' && preg_match('/^(DVR\d+)/', $driverText, $matches) === 1) {
            $driverCode = $matches[1];
        }

        $driver = $driverCode !== ''
            ? FleetDriver::query()->where('code', $driverCode)->first()
            : null;

        if (! $driver || strcasecmp((string) ($driver->payload['salaryTenure'] ?? ''), 'Hourly') !== 0) {
            FleetDue::query()->where('code', $dueCode)->delete();
            app(FleetRecordOwnershipService::class)->forgetRecord('dues', $dueCode);
            return;
        }

        $salary = (float) ($driver->payload['salary'] ?? 0);
        $hoursText = trim((string) ($row['hours'] ?? ''));
        $driverHours = 0.0;

        if (preg_match('/(\d+)h\s*(\d+)m/', $hoursText, $matches) === 1) {
            $driverHours = (int) $matches[1] + ((int) $matches[2] / 60);
        }

        $amount = round($salary * $driverHours, 2);
        if ($amount <= 0) {
            FleetDue::query()->where('code', $dueCode)->delete();
            app(FleetRecordOwnershipService::class)->forgetRecord('dues', $dueCode);
            return;
        }

        $due = FleetDue::query()->updateOrCreate(
            ['code' => $dueCode],
            [
                'type' => 'Driver Salary',
                'party_type' => 'Driver',
                'party_id' => $driverCode,
                'source_type' => 'Attendance',
                'source_id' => $logId,
                'amount' => $amount,
                'status' => 'Pending',
                'due_date' => $row['date'] ?? null,
                'payload' => [
                    'logId' => $logId,
                    'driverName' => $driverText,
                    'hours' => $hoursText,
                ],
            ]
        );

        $userId = (int) (auth()->id() ?? 0);
        if ($due->wasRecentlyCreated && $userId > 0) {
            app(FleetRecordOwnershipService::class)->claimRecord('dues', (string) $due->code, $userId);
        }
    }

    private function cleanPersistenceMetadata(array $row): array
    {
        unset(
            $row['createdAt'], $row['created_at'], $row['updatedAt'], $row['updated_at'],
            $row['creatorName'], $row['createdBy'], $row['created_by']
        );

        return $row;
    }

    private function comparableAttendancePayload(array $row): array
    {
        $row = $this->cleanPersistenceMetadata($row);
        ksort($row);

        return $row;
    }

    private function attendancePayload(FleetDriverAttendance $record): array
    {
        $payload = is_array($record->payload) ? $record->payload : [];
        $payload['createdAt'] = optional($record->created_at)->toIso8601String();
        $payload['updatedAt'] = optional($record->updated_at)->toIso8601String();
        $payload['creatorName'] = $this->creatorNameForRecord('driver_attendance', (string) $record->code);

        return $payload;
    }
}
