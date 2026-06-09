<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetTrip;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReportController extends FleetBaseController
{
    protected string $activeMenu = 'reports';
    protected string $view = 'fleetman.reports';
    protected string $page = 'reports';

    public function index(): View
    {
        return view('fleetman.reports', $this->reportViewData([
            'page' => 'reports',
            'reportCards' => $this->reportCards(),
        ]));
    }

    public function dailyDriverFuel(): View
    {
        return view('fleetman.reports.daily-driver-fuel', $this->reportViewData([
            'page' => 'report-daily-driver-fuel',
            'report' => $this->reportPayload('daily'),
        ]));
    }

    public function weeklyDriverFuel(): View
    {
        return view('fleetman.reports.weekly-driver-fuel', $this->reportViewData([
            'page' => 'report-weekly-driver-fuel',
            'report' => $this->reportPayload('weekly'),
        ]));
    }

    public function monthlyDriverFuel(): View
    {
        return view('fleetman.reports.monthly-driver-fuel', $this->reportViewData([
            'page' => 'report-monthly-driver-fuel',
            'report' => $this->reportPayload('monthly'),
        ]));
    }


    /**
     * FleetBaseController::shared() keeps page-specific data inside the
     * `$fleetman` payload for JavaScript pages. Report Blade pages also read
     * these values directly, so expose the same data at the top level too.
     */
    private function reportViewData(array $pageData): array
    {
        return array_merge($this->shared('reports', $pageData), $pageData);
    }

    private function reportCards(): array
    {
        return [
            [
                'title' => 'Daily Driver & Fuel Report',
                'description' => 'Driver working time, fuel use, odometer movement, mileage, and submission status by date.',
                'icon' => '📅',
                'route' => 'fleet.reports.daily-driver-fuel',
                'button' => 'Open Daily Report',
            ],
            [
                'title' => 'Weekly Driver Fuel Summary Report',
                'description' => 'Saturday-to-Friday weekly summary with day-wise Diesel, CNG/LPG, and Octane columns.',
                'icon' => '🗓️',
                'route' => 'fleet.reports.weekly-driver-fuel',
                'button' => 'Open Weekly Report',
            ],
            [
                'title' => 'Monthly Driver & Fuel Summary Report',
                'description' => 'Monthly summary on screen with date-wise monthly Excel export support.',
                'icon' => '📊',
                'route' => 'fleet.reports.monthly-driver-fuel',
                'button' => 'Open Monthly Report',
            ],
        ];
    }

    private function reportPayload(string $type): array
    {
        $records = $this->fuelRechargeRecords();
        $dates = $records->pluck('date')->filter()->sort()->values();
        $maxDate = $dates->last() ?: now()->toDateString();
        $minDate = $dates->first() ?: now()->copy()->subDays(6)->toDateString();

        $defaultEnd = Carbon::parse($maxDate);
        $defaultStart = $defaultEnd->copy()->subDays(6);

        return [
            'type' => $type,
            'records' => $records->values()->all(),
            'filters' => [
                'contracts' => $records->pluck('contract')->filter()->unique()->sort()->values()->all(),
                'vehicles' => $records->pluck('car')->filter()->unique()->sort()->values()->all(),
                'drivers' => $records->pluck('driver')->filter()->unique()->sort()->values()->all(),
                'statuses' => $records->pluck('status')->filter()->unique()->sort()->values()->all(),
                'fuelTypes' => ['Diesel', 'Diesel + CNG/LPG', 'Octane', 'Petrol/Octane', 'CNG/LPG'],
            ],
            'defaults' => [
                'fromDate' => $defaultStart->toDateString(),
                'toDate' => $defaultEnd->toDateString(),
                'week' => $this->weekKey($defaultEnd),
                'month' => $defaultEnd->format('Y-m'),
            ],
            'weeks' => $this->weekOptions($records, $defaultEnd),
            'months' => $this->monthOptions($records, $defaultEnd),
            'dateRange' => [
                'min' => $minDate,
                'max' => $maxDate,
            ],
        ];
    }

    private function fuelRechargeRecords(): Collection
    {
        $databaseRows = collect();

        if (Schema::hasTable('fleet_fuel_recharges')) {
            $databaseRows = FleetFuelRecharge::query()
                ->orderBy('id')
                ->get()
                ->map(fn (FleetFuelRecharge $row) => $this->normalizeRecharge($row->payload ?? [], $row->code, $row->status));
        }

        $records = $databaseRows->isNotEmpty()
            ? $databaseRows
            : collect(config('fleetman.samples.fuel_recharges', []))
                ->map(fn (array $row, int $index) => $this->normalizeRecharge($row, $row['rechargeId'] ?? ('FR-SAMPLE-' . ($index + 1)), $row['status'] ?? 'Submitted'));

        $reportableRecords = $records
            ->reject(fn (array $record): bool => strcasecmp(trim((string) ($record['status'] ?? '')), 'Draft') === 0)
            ->values();

        return $this->enrichReportRecords($reportableRecords);
    }

    private function enrichReportRecords(Collection $records): Collection
    {
        $attendanceRows = $this->driverAttendanceReportRows();
        $tripRows = null;

        return $records->map(function (array $record) use ($attendanceRows, &$tripRows): array {
            $attendance = $this->attendanceForRecord($record, $attendanceRows);

            if ($attendance !== null) {
                $record['driverStart'] = $attendance['startTime'] ?: $record['driverStart'];
                $record['driverEnd'] = $attendance['endTime'] ?: $record['driverEnd'];
                $record['totalTime'] = $attendance['totalTime'];
            }

            // TK(KM) on Add Fuel is defined as total fuel price divided by
            // total travelled KM. Use the stored fuel amount first so daily,
            // weekly, monthly, and exported reports all follow that same rule.
            $totalFuelPrice = (float) ($record['totalAmount'] ?? 0);

            // Preserve reports for old rows created before fuel amounts were
            // stored by using the previous trip-cost matching only as fallback.
            if ($totalFuelPrice <= 0) {
                $tripRows ??= $this->tripCostReportRows();
                $totalFuelPrice = $this->tripCostForRecord($record, $tripRows);
            }

            $totalKm = (float) ($record['totalKm'] ?? 0);
            $record['totalCost'] = round($totalFuelPrice, 2);
            $record['tkKm'] = $totalKm > 0 && $totalFuelPrice > 0
                ? round($totalFuelPrice / $totalKm, 2)
                : 0.0;

            return $record;
        });
    }

    private function driverAttendanceReportRows(): Collection
    {
        if (! Schema::hasTable('fleet_driver_attendances')) {
            return collect();
        }

        return FleetDriverAttendance::query()
            ->orderBy('id')
            ->get()
            ->map(function (FleetDriverAttendance $row): ?array {
                $payload = $row->payload ?? [];
                if (! is_array($payload)) {
                    return null;
                }

                $status = trim((string) ($payload['status'] ?? $row->status ?? ''));
                if (strcasecmp($status, 'Draft') === 0) {
                    return null;
                }

                $date = $payload['date'] ?? $payload['attendanceDate'] ?? null;
                if (! $date) {
                    return null;
                }

                $start = (string) ($payload['startTime'] ?? $payload['driverStart'] ?? '');
                $end = (string) ($payload['endTime'] ?? $payload['driverEnd'] ?? '');
                $minutes = $this->attendanceMinutes($payload, $start, $end);

                return [
                    'logId' => (string) ($payload['logId'] ?? $row->code),
                    'date' => Carbon::parse($date)->toDateString(),
                    'contractId' => (string) ($payload['contractId'] ?? ''),
                    'contract' => (string) ($payload['contract'] ?? $payload['contractLabel'] ?? $payload['contractParty'] ?? ''),
                    'vehicleId' => (string) ($payload['vehicleId'] ?? ''),
                    'vehicle' => (string) ($payload['vehicle'] ?? $payload['vehicleLabel'] ?? ''),
                    'driverId' => (string) ($payload['driverId'] ?? ''),
                    'driver' => (string) ($payload['driver'] ?? $payload['driverLabel'] ?? $payload['driverName'] ?? ''),
                    'startTime' => $start,
                    'endTime' => $end,
                    'minutes' => $minutes,
                ];
            })
            ->filter()
            ->values();
    }

    private function tripCostReportRows(): Collection
    {
        if (! Schema::hasTable('fleet_trips')) {
            return collect();
        }

        $contractAssignments = $this->contractAssignmentReportRows();

        return FleetTrip::query()
            ->orderBy('id')
            ->get()
            ->map(function (FleetTrip $row) use ($contractAssignments): ?array {
                $payload = $row->payload ?? [];
                if (! is_array($payload)) {
                    return null;
                }

                $status = trim((string) ($payload['status'] ?? $row->status ?? ''));
                if (strcasecmp($status, 'Draft') === 0) {
                    return null;
                }

                $trip = [
                    'tripId' => (string) ($payload['tripId'] ?? $row->code),
                    'startDate' => $this->safeDate($payload['startDate'] ?? $payload['date'] ?? null),
                    'endDate' => $this->safeDate($payload['endDate'] ?? $payload['startDate'] ?? $payload['date'] ?? null),
                    'contractId' => (string) ($payload['contractId'] ?? ''),
                    'contract' => (string) ($payload['contract'] ?? $payload['contractLabel'] ?? ''),
                    'vehicleId' => (string) ($payload['vehicleId'] ?? ''),
                    'vehicle' => (string) ($payload['vehicle'] ?? $payload['vehicleLabel'] ?? ''),
                    'driverId' => (string) ($payload['driverId'] ?? ''),
                    'driver' => (string) ($payload['driver'] ?? $payload['driverLabel'] ?? ''),
                    'totalCost' => (float) ($payload['totalCost'] ?? $payload['tripTotalCost'] ?? 0),
                ];

                if ($trip['totalCost'] <= 0) {
                    $trip['totalCost'] = (float) ($payload['fuelCost'] ?? 0)
                        + (float) ($payload['foodCost'] ?? 0)
                        + (float) ($payload['tolls'] ?? 0)
                        + (float) ($payload['otherCost'] ?? 0)
                        + (float) ($payload['accommodationCost'] ?? 0);
                }

                if ($trip['contractId'] === '' && $trip['contract'] === '') {
                    $inferred = $this->inferTripContract($trip, $contractAssignments);
                    if ($inferred !== null) {
                        $trip['contractId'] = $inferred['contractId'];
                        $trip['contract'] = $inferred['contract'];
                    }
                }

                return $trip;
            })
            ->filter(fn (?array $trip) => $trip !== null && (float) ($trip['totalCost'] ?? 0) > 0)
            ->values();
    }

    private function contractAssignmentReportRows(): Collection
    {
        if (! Schema::hasTable('fleet_contracts')) {
            return collect();
        }

        return FleetContract::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['fuel_recharge', 'attendance']);
            })
            ->orderBy('id')
            ->get()
            ->flatMap(function (FleetContract $contract): array {
                $payload = $contract->payload ?? [];
                if (! is_array($payload)) {
                    return [];
                }

                $contractId = (string) ($payload['contractId'] ?? $payload['id'] ?? $contract->code);
                $partyName = (string) ($payload['partyName'] ?? $payload['party'] ?? $contract->name ?? '');
                $contractLabel = trim($contractId.' | '.$partyName, ' |');

                return collect($payload['assignments'] ?? [])
                    ->filter(fn ($assignment) => is_array($assignment))
                    ->map(fn (array $assignment): array => [
                        'contractId' => $contractId,
                        'contract' => $contractLabel,
                        'vehicleId' => (string) ($assignment['vehicleId'] ?? ''),
                        'vehicle' => (string) ($assignment['vehicle'] ?? $assignment['vehicleLabel'] ?? $assignment['vehicleName'] ?? ''),
                        'driverId' => (string) ($assignment['driverId'] ?? ''),
                        'driver' => (string) ($assignment['driver'] ?? $assignment['driverLabel'] ?? $assignment['driverName'] ?? ''),
                    ])
                    ->all();
            })
            ->values();
    }

    private function inferTripContract(array $trip, Collection $contractAssignments): ?array
    {
        $tripVehicleKeys = $this->identityKeys([$trip['vehicleId'] ?? '', $trip['vehicle'] ?? '']);
        $tripDriverKeys = $this->identityKeys([$trip['driverId'] ?? '', $trip['driver'] ?? '']);

        return $contractAssignments->first(function (array $assignment) use ($tripVehicleKeys, $tripDriverKeys): bool {
            $assignmentVehicleKeys = $this->identityKeys([$assignment['vehicleId'] ?? '', $assignment['vehicle'] ?? '']);
            $assignmentDriverKeys = $this->identityKeys([$assignment['driverId'] ?? '', $assignment['driver'] ?? '']);

            $vehicleMatches = empty($tripVehicleKeys) || empty($assignmentVehicleKeys) || $this->keysOverlap($tripVehicleKeys, $assignmentVehicleKeys);
            $driverMatches = empty($tripDriverKeys) || empty($assignmentDriverKeys) || $this->keysOverlap($tripDriverKeys, $assignmentDriverKeys);

            return $vehicleMatches && $driverMatches;
        });
    }

    private function attendanceForRecord(array $record, Collection $attendanceRows): ?array
    {
        $matches = $attendanceRows->filter(function (array $attendance) use ($record): bool {
            if (($attendance['date'] ?? '') !== ($record['date'] ?? '')) {
                return false;
            }

            if (! $this->entityMatches($record, $attendance, 'contract')) {
                return false;
            }

            if (! $this->entityMatches($record, $attendance, 'driver')) {
                return false;
            }

            return $this->optionalEntityMatches($record, $attendance, 'vehicle');
        })->values();

        if ($matches->isEmpty()) {
            return null;
        }

        $start = $this->earliestTime($matches->pluck('startTime')->filter()->values()->all());
        $end = $this->latestTime($matches->pluck('endTime')->filter()->values()->all());
        $minutes = $matches->sum(fn (array $row) => (int) ($row['minutes'] ?? 0));

        return [
            'startTime' => $start,
            'endTime' => $end,
            'totalTime' => round($minutes / 60, 2),
        ];
    }

    private function tripCostForRecord(array $record, Collection $tripRows): float
    {
        $contractTrips = $tripRows->filter(fn (array $trip): bool => $this->entityMatches($record, $trip, 'contract'));

        $datedTrips = $contractTrips->filter(fn (array $trip): bool => $this->dateWithin($record['date'] ?? '', $trip['startDate'] ?? null, $trip['endDate'] ?? null));
        $matchedTrips = $datedTrips->isNotEmpty() ? $datedTrips : $contractTrips;

        return (float) $matchedTrips->sum(fn (array $trip) => (float) ($trip['totalCost'] ?? 0));
    }

    private function entityMatches(array $left, array $right, string $entity): bool
    {
        $leftKeys = $this->entityKeys($left, $entity);
        $rightKeys = $this->entityKeys($right, $entity);

        return ! empty($leftKeys) && ! empty($rightKeys) && $this->keysOverlap($leftKeys, $rightKeys);
    }

    private function optionalEntityMatches(array $left, array $right, string $entity): bool
    {
        $leftKeys = $this->entityKeys($left, $entity);
        $rightKeys = $this->entityKeys($right, $entity);

        if (empty($leftKeys) || empty($rightKeys)) {
            return true;
        }

        return $this->keysOverlap($leftKeys, $rightKeys);
    }

    private function entityKeys(array $row, string $entity): array
    {
        return match ($entity) {
            'contract' => $this->identityKeys([$row['contractId'] ?? '', $row['contract'] ?? '', $row['contractLabel'] ?? '', $row['contractParty'] ?? '']),
            'vehicle' => $this->identityKeys([$row['vehicleId'] ?? '', $row['vehicle'] ?? '', $row['vehicleLabel'] ?? '', $row['car'] ?? '']),
            'driver' => $this->identityKeys([$row['driverId'] ?? '', $row['driver'] ?? '', $row['driverName'] ?? '', $row['driverLabel'] ?? '']),
            default => [],
        };
    }

    private function identityKeys(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => filled($value))
            ->flatMap(function ($value): array {
                $text = (string) $value;
                $parts = preg_split('/\s+\-\s+|\s+\|\s+|\|/', $text) ?: [];
                return array_merge([$text], $parts);
            })
            ->map(fn ($value) => $this->identityKey((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function identityKey(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?: '');
    }

    private function keysOverlap(array $left, array $right): bool
    {
        return count(array_intersect($left, $right)) > 0;
    }

    private function safeDate(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateWithin(string $date, ?string $startDate, ?string $endDate): bool
    {
        if (! filled($date)) {
            return false;
        }

        $date = Carbon::parse($date)->toDateString();
        if (! $startDate && ! $endDate) {
            return true;
        }

        if ($startDate && $date < $startDate) {
            return false;
        }

        if ($endDate && $date > $endDate) {
            return false;
        }

        return true;
    }

    private function attendanceMinutes(array $payload, string $start, string $end): int
    {
        $minutes = $this->minutesFromHoursText($payload['hours'] ?? $payload['totalHours'] ?? null);
        if ($minutes > 0) {
            return $minutes;
        }

        if (is_numeric($payload['totalTime'] ?? null)) {
            return (int) round(((float) $payload['totalTime']) * 60);
        }

        return $this->minutesBetweenTimes($start, $end);
    }

    private function minutesFromHoursText(mixed $value): int
    {
        if (! filled($value)) {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) round(((float) $value) * 60);
        }

        $text = (string) $value;
        if (preg_match('/(\d+)\s*h(?:ours?)?\s*(\d+)?\s*m?/i', $text, $match)) {
            return ((int) $match[1]) * 60 + (int) ($match[2] ?? 0);
        }

        return 0;
    }

    private function minutesBetweenTimes(string $start, string $end): int
    {
        if ($start === '' || $end === '') {
            return 0;
        }

        [$startHour, $startMinute] = array_pad(array_map('intval', explode(':', $start)), 2, 0);
        [$endHour, $endMinute] = array_pad(array_map('intval', explode(':', $end)), 2, 0);
        $startMinutes = $startHour * 60 + $startMinute;
        $endMinutes = $endHour * 60 + $endMinute;

        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return max(0, $endMinutes - $startMinutes);
    }

    private function earliestTime(array $times): string
    {
        $times = collect($times)->filter()->sortBy(fn ($time) => $this->timeSortValue((string) $time))->values();
        return (string) ($times->first() ?? '');
    }

    private function latestTime(array $times): string
    {
        $times = collect($times)->filter()->sortByDesc(fn ($time) => $this->timeSortValue((string) $time))->values();
        return (string) ($times->first() ?? '');
    }

    private function timeSortValue(string $time): int
    {
        [$hour, $minute] = array_pad(array_map('intval', explode(':', $time)), 2, 0);
        return $hour * 60 + $minute;
    }

    private function normalizeRecharge(array $row, ?string $fallbackCode = null, ?string $fallbackStatus = null): array
    {
        $primaryName = (string) ($row['primaryFuelName'] ?? $row['primaryFuel'] ?? $row['fuelName'] ?? 'Diesel');
        $secondaryName = (string) ($row['secondaryFuelName'] ?? $row['secondaryFuel'] ?? '');
        $primaryQty = (float) ($row['primaryQty'] ?? $row['diesel'] ?? $row['octane'] ?? 0);
        $secondaryQty = (float) ($row['secondaryQty'] ?? 0);
        $primaryRate = (float) ($row['primaryRate'] ?? 0);
        $secondaryRate = (float) ($row['secondaryRate'] ?? 0);
        $primaryAmount = (float) ($row['primaryAmount'] ?? ($primaryQty * $primaryRate));
        $secondaryAmount = (float) ($row['secondaryAmount'] ?? ($secondaryQty * $secondaryRate));
        $totalAmount = (float) ($row['totalAmount'] ?? 0);
        if ($totalAmount <= 0) {
            $totalAmount = $primaryAmount + $secondaryAmount;
        }

        $diesel = (float) ($row['diesel'] ?? 0);
        $octane = (float) ($row['octane'] ?? 0);
        $gas = (float) ($row['gas'] ?? $row['cngLpgCost'] ?? 0);

        if ($diesel <= 0 && str($primaryName)->lower()->contains('diesel')) {
            $diesel = $primaryQty;
        }

        if ($octane <= 0 && (str($primaryName)->lower()->contains('octane') || str($primaryName)->lower()->contains('petrol'))) {
            $octane = $primaryQty;
        }

        if ($gas <= 0) {
            if (str($primaryName)->lower()->contains(['cng', 'lpg', 'gas'])) {
                $gas += $primaryAmount;
            }
            if (str($secondaryName)->lower()->contains(['cng', 'lpg', 'gas'])) {
                $gas += $secondaryAmount;
            }
        }

        $date = $row['date'] ?? $row['submitDate'] ?? $row['submittedDate'] ?? null;
        if (! $date && ! empty($row['submittedAt'])) {
            try {
                $date = Carbon::parse($row['submittedAt'])->toDateString();
            } catch (\Throwable) {
                $date = now()->toDateString();
            }
        }
        $date = $date ?: now()->toDateString();

        $startKm = (float) ($row['startKm'] ?? $row['odoStart'] ?? $row['kmStart'] ?? 0);
        $endKm = (float) ($row['endKm'] ?? $row['odoReading'] ?? $row['odoEnd'] ?? $row['kmEnd'] ?? 0);
        $totalKm = (float) ($row['totalKm'] ?? $row['distance'] ?? max(0, $endKm - $startKm));
        if ($endKm <= 0 && $startKm > 0 && $totalKm > 0) {
            $endKm = $startKm + $totalKm;
        }
        if ($startKm <= 0 && $endKm > 0 && $totalKm > 0) {
            $startKm = max(0, $endKm - $totalKm);
        }

        $fuelLitres = (float) ($row['liquidFuelLitres'] ?? ($diesel + $octane));
        $mileage = array_key_exists('mileage', $row)
            ? (float) $row['mileage']
            : ($fuelLitres > 0 ? round($totalKm / $fuelLitres, 2) : 0);

        $fuelType = $row['fuelType'] ?? $this->fuelTypeLabel($diesel, $gas, $octane, $primaryName, $secondaryName);

        return [
            'entryId' => (string) ($row['entryId'] ?? $row['rechargeId'] ?? $fallbackCode ?? 'FR-' . now()->format('ymdHis')),
            'date' => Carbon::parse($date)->toDateString(),
            'contractId' => (string) ($row['contractId'] ?? ''),
            'contract' => (string) ($row['contract'] ?? $row['contractLabel'] ?? 'Unassigned Contract'),
            'contractLabel' => (string) ($row['contractLabel'] ?? $row['contract'] ?? ''),
            'vehicleId' => (string) ($row['vehicleId'] ?? ''),
            'car' => (string) ($row['car'] ?? $row['vehicle'] ?? $row['vehicleLabel'] ?? 'Unassigned Vehicle'),
            'vehicle' => (string) ($row['vehicle'] ?? $row['car'] ?? $row['vehicleLabel'] ?? ''),
            'vehicleLabel' => (string) ($row['vehicleLabel'] ?? $row['vehicle'] ?? $row['car'] ?? ''),
            'driverId' => (string) ($row['driverId'] ?? ''),
            'driver' => (string) ($row['driver'] ?? $row['driverName'] ?? 'Assigned Driver'),
            'driverName' => (string) ($row['driverName'] ?? $row['driver'] ?? ''),
            'driverStart' => (string) ($row['driverStart'] ?? $row['startTime'] ?? '08:00'),
            'driverEnd' => (string) ($row['driverEnd'] ?? $row['endTime'] ?? '17:00'),
            'totalTime' => round((float) ($row['totalTime'] ?? $row['totalHours'] ?? $this->hoursBetween($row['driverStart'] ?? $row['startTime'] ?? '08:00', $row['driverEnd'] ?? $row['endTime'] ?? '17:00')), 2),
            'diesel' => round($diesel, 2),
            'gas' => round($gas, 2),
            'octane' => round($octane, 2),
            'startKm' => (int) round($startKm),
            'endKm' => (int) round($endKm),
            'totalKm' => (int) round($totalKm),
            'mileage' => round($mileage, 2),
            'totalAmount' => round($totalAmount, 2),
            'tkKm' => $totalKm > 0 && $totalAmount > 0
                ? round($totalAmount / $totalKm, 2)
                : round((float) ($row['tkKm'] ?? 0), 2),
            'status' => (string) ($row['status'] ?? $fallbackStatus ?? 'Submitted'),
            'submittedBy' => (string) ($row['submittedBy'] ?? $row['operator'] ?? 'Admin User'),
            'fuelType' => $fuelType,
        ];
    }

    private function fuelTypeLabel(float $diesel, float $gas, float $octane, string $primaryName, string $secondaryName): string
    {
        if ($diesel > 0 && $gas > 0) {
            return 'Diesel + CNG/LPG';
        }
        if ($diesel > 0) {
            return 'Diesel';
        }
        if ($octane > 0) {
            return str($primaryName)->lower()->contains('petrol') ? 'Petrol/Octane' : 'Octane';
        }
        if ($gas > 0 || str($secondaryName)->lower()->contains(['cng', 'lpg'])) {
            return 'CNG/LPG';
        }

        return $primaryName ?: 'Fuel';
    }

    private function hoursBetween(string $start, string $end): float
    {
        try {
            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);
            if ($endTime->lessThan($startTime)) {
                $endTime->addDay();
            }
            return round($startTime->diffInMinutes($endTime) / 60, 2);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function weekOptions(Collection $records, Carbon $defaultEnd): array
    {
        $dates = $records->pluck('date')->filter()->map(fn (string $date) => Carbon::parse($date));
        if ($dates->isEmpty()) {
            $dates = collect([$defaultEnd]);
        }

        return $dates
            ->map(fn (Carbon $date) => $this->weekStart($date))
            ->unique(fn (Carbon $date) => $date->toDateString())
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->values()
            ->map(function (Carbon $start) {
                $end = $start->copy()->addDays(6);
                return [
                    'value' => $start->toDateString(),
                    'label' => 'Week | Sat ' . $start->format('d-M') . ' to Fri ' . $end->format('d-M'),
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                    'days' => collect(CarbonPeriod::create($start, $end))->map(fn (Carbon $day) => [
                        'date' => $day->toDateString(),
                        'label' => $day->format('D d-M'),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    private function monthOptions(Collection $records, Carbon $defaultEnd): array
    {
        $dates = $records->pluck('date')->filter()->map(fn (string $date) => Carbon::parse($date));
        if ($dates->isEmpty()) {
            $dates = collect([$defaultEnd]);
        }

        return $dates
            ->map(fn (Carbon $date) => $date->copy()->startOfMonth())
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->values()
            ->map(fn (Carbon $month) => [
                'value' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
                'start' => $month->toDateString(),
                'end' => $month->copy()->endOfMonth()->toDateString(),
                'days' => $month->daysInMonth,
            ])
            ->all();
    }

    private function weekKey(Carbon $date): string
    {
        return $this->weekStart($date)->toDateString();
    }

    private function weekStart(Carbon $date): Carbon
    {
        $start = $date->copy()->startOfDay();
        while ($start->dayOfWeek !== Carbon::SATURDAY) {
            $start->subDay();
        }

        return $start;
    }
}
