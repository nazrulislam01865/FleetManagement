<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriverAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*' => ['array'],
        ]);

        $rows = $validated['rows'];
        $this->validateAttendanceRows($rows);

        $response = parent::sync($request);

        foreach ($rows as $row) {
            if (($row[$this->statusKey] ?? '') !== 'Completed') {
                continue;
            }

            $driverStr = $row['driver'] ?? '';
            // Extract driver code (e.g., DVR12345 from "DVR12345 - Kamal")
            if (! preg_match('/^(DVR\d+)/', $driverStr, $matches)) {
                continue;
            }

            $driverCode = $matches[1];
            $driver = \App\Models\Fleet\FleetDriver::where('code', $driverCode)->first();

            if (! $driver || ($driver->payload['salaryTenure'] ?? '') !== 'Hourly') {
                continue;
            }

            $salary = (float) ($driver->payload['salary'] ?? 0);
            $driverHours = 0;
            $hoursStr = $row['hours'] ?? '';

            if (preg_match('/(\d+)h\s*(\d+)m/', $hoursStr, $hourMatches)) {
                $driverHours = (int) $hourMatches[1] + ((int) $hourMatches[2] / 60);
            } else {
                $start = strtotime($row['startTime'] ?? '');
                $end = strtotime($row['endTime'] ?? '');
                if ($start && $end) {
                    if ($end < $start) {
                        $end += 86400; // cross midnight
                    }
                    $driverHours = ($end - $start) / 3600;
                }
            }

            $amount = $salary * $driverHours;
            $logId = $row[$this->idKey] ?? '';

            if ($amount <= 0 || ! $logId) {
                continue;
            }

            \App\Models\Fleet\FleetDue::updateOrCreate(
                ['code' => 'PAY-LOG-'.$logId],
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
                        'driverName' => $driverStr,
                        'hours' => $hoursStr,
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * Validate every completed/non-draft log before it reaches persistence.
     * Drafts remain intentionally permissive.
     */
    protected function validateAttendanceRows(array $rows): void
    {
        $errors = [];
        $masters = $this->attendanceMastersFromDatabase();
        $contracts = collect($masters['contracts'] ?? []);
        $allowedStatuses = $this->values('attendance_status');
        if ($allowedStatuses === []) {
            $allowedStatuses = ['Initiated', 'Running', 'Completed'];
        }

        foreach ($rows as $index => $row) {
            $status = trim((string) ($row['status'] ?? ''));
            if (strcasecmp($status, 'Draft') === 0) {
                continue;
            }

            $required = [
                'logId' => 'Attendance ID',
                'date' => 'Date',
                'contract' => 'Contract',
                'vehicle' => 'Vehicle',
                'driver' => 'Driver',
                'startTime' => 'Start time',
                'status' => 'Status',
            ];

            foreach ($required as $key => $label) {
                if (trim((string) ($row[$key] ?? '')) === '') {
                    $errors["rows.$index.$key"] = "$label is required.";
                }
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
            if ($driverText !== '' && ! $vehicleAssignments->contains(function (array $assignment) use ($driverText): bool {
                return $this->matchesAttendanceValue($driverText, [
                    $assignment['driverLabel'] ?? null,
                    $assignment['driver'] ?? null,
                    $assignment['driverName'] ?? null,
                    $assignment['driverId'] ?? null,
                ]);
            })) {
                $errors["rows.$index.driver"] = 'Select a driver assigned to the selected contract and vehicle.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function matchesAttendanceValue(string $needle, array $values): bool
    {
        return collect($values)
            ->filter(fn ($value) => filled($value))
            ->contains(fn ($value) => trim((string) $value) === $needle);
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
}
