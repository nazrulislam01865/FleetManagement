<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

class FleetPerformancePayload
{
    /**
     * Return normalized relational values extracted from the legacy JSON payload.
     * The JSON payload remains the source of truth for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public static function attributes(string $table, array $payload): array
    {
        return match ($table) {
            'fleet_vehicles' => [
                'registration_number' => self::keyText($payload['regNo'] ?? null),
            ],
            'fleet_trips' => [
                'trip_date' => self::date($payload['date'] ?? $payload['tripDate'] ?? $payload['startDate'] ?? null),
                'contract_code' => self::text($payload['contractId'] ?? $payload['contractCode'] ?? null),
                'vehicle_code' => self::text($payload['vehicleId'] ?? null),
                'driver_code' => self::text($payload['driverId'] ?? null),
                'total_cost' => self::number($payload['totalCost'] ?? 0),
                'paid_amount' => self::number($payload['paidAmount'] ?? 0),
                'balance_due' => self::number($payload['balanceDue'] ?? max(0, self::number($payload['totalCost'] ?? 0) - self::number($payload['paidAmount'] ?? 0))),
            ],
            'fleet_fuel_recharges' => [
                'recharge_date' => self::date($payload['date'] ?? $payload['rechargeDate'] ?? $payload['submitDate'] ?? $payload['submittedDate'] ?? $payload['submittedAt'] ?? null),
                'contract_code' => self::text($payload['contractId'] ?? null),
                'vehicle_code' => self::text($payload['vehicleId'] ?? null),
                'driver_code' => self::text($payload['driverId'] ?? null),
                'total_amount' => self::number($payload['totalAmount'] ?? $payload['totalCost'] ?? 0),
                'total_km' => self::fuelDistance($payload),
            ],
            'fleet_driver_attendances' => [
                'log_date' => self::date($payload['date'] ?? $payload['attendanceDate'] ?? null),
                'contract_code' => self::text($payload['contractId'] ?? null),
                'vehicle_code' => self::text($payload['vehicleId'] ?? null),
                'driver_code' => self::text($payload['driverId'] ?? null),
                'distance_km' => self::number($payload['distance'] ?? $payload['totalKm'] ?? 0),
                'duration_minutes' => self::durationMinutes($payload),
            ],
            'fleet_drivers' => [
                'license_validity' => self::date($payload['licenseValidity'] ?? null),
                'salary_amount' => self::number($payload['salary'] ?? 0),
                'nid_number' => self::keyText($payload['nid'] ?? null),
                'license_number' => self::keyText($payload['licenseNo'] ?? null),
            ],
            'fleet_employees' => [
                'salary_amount' => self::number($payload['salary'] ?? 0),
                'nid_number' => self::keyText($payload['nid'] ?? null),
            ],
            'fleet_contracts' => [
                'contract_start' => self::date($payload['contractStart'] ?? null),
                'contract_end' => self::date($payload['contractEnd'] ?? null),
                'party_code' => self::text($payload['partyId'] ?? null),
                'amount_value' => self::number($payload['amount'] ?? 0),
            ],
            default => [],
        };
    }

    private static function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 191);
    }

    private static function keyText(mixed $value): ?string
    {
        $value = self::text($value);

        return $value === null ? null : mb_strtolower($value);
    }

    private static function number(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', '৳', 'TK', 'Tk', 'tk'], '', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : 0.0;
    }

    private static function date(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function fuelDistance(array $payload): float
    {
        $stored = self::number($payload['totalKm'] ?? $payload['distance'] ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        $start = self::number($payload['startKm'] ?? $payload['odoStart'] ?? $payload['kmStart'] ?? 0);
        $end = self::number($payload['endKm'] ?? $payload['odoReading'] ?? $payload['odoEnd'] ?? $payload['kmEnd'] ?? 0);

        return $end > $start ? round($end - $start, 2) : 0.0;
    }

    private static function durationMinutes(array $payload): int
    {
        $stored = $payload['totalMinutes'] ?? $payload['durationMinutes'] ?? null;
        if (is_numeric($stored)) {
            return max(0, (int) round((float) $stored));
        }

        $start = trim((string) ($payload['startTime'] ?? $payload['driverStart'] ?? ''));
        $end = trim((string) ($payload['endTime'] ?? $payload['driverEnd'] ?? ''));
        if ($start === '' || $end === '') {
            return 0;
        }

        try {
            $startAt = Carbon::createFromFormat('H:i', mb_substr($start, 0, 5));
            $endAt = Carbon::createFromFormat('H:i', mb_substr($end, 0, 5));
            if ($endAt->lessThan($startAt)) {
                $endAt->addDay();
            }

            return max(0, (int) $startAt->diffInMinutes($endAt));
        } catch (Throwable) {
            return 0;
        }
    }
}
