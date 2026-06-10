<?php

namespace App\Support;

final class FleetDuration
{
    /**
     * Calculate a duration in minutes from two clock times.
     * An end time earlier than the start time is treated as an overnight shift.
     */
    public static function minutesBetween(mixed $start, mixed $end): int
    {
        $startMinutes = self::clockTimeToMinutes($start);
        $endMinutes = self::clockTimeToMinutes($end);

        if ($startMinutes === null || $endMinutes === null) {
            return 0;
        }

        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return max(0, $endMinutes - $startMinutes);
    }

    /**
     * Read the most reliable duration available in an attendance/fuel payload.
     */
    public static function minutesFromPayload(array $payload, mixed $start = null, mixed $end = null): int
    {
        $resolvedStart = $start ?? $payload['startTime'] ?? $payload['driverStart'] ?? null;
        $resolvedEnd = $end ?? $payload['endTime'] ?? $payload['driverEnd'] ?? null;

        /*
         * Start and end times are the source of truth whenever both are valid.
         * This also repairs old records whose saved hours/totalTime value was
         * calculated incorrectly before the duration fix was introduced.
         */
        if (self::clockTimeToMinutes($resolvedStart) !== null
            && self::clockTimeToMinutes($resolvedEnd) !== null) {
            return self::minutesBetween($resolvedStart, $resolvedEnd);
        }

        if (is_numeric($payload['totalMinutes'] ?? null)) {
            $minutes = max(0, (int) round((float) $payload['totalMinutes']));
            if ($minutes > 0) {
                return $minutes;
            }
        }

        foreach (['hours', 'totalHours', 'duration'] as $key) {
            $minutes = self::minutesFromHoursValue($payload[$key] ?? null);
            if ($minutes !== null && $minutes > 0) {
                return $minutes;
            }
        }

        if (is_numeric($payload['totalTime'] ?? null)) {
            $minutes = max(0, (int) round(((float) $payload['totalTime']) * 60));
            if ($minutes > 0) {
                return $minutes;
            }
        }

        return 0;
    }

    /**
     * Parse values such as 8.5, "8h 30m", "8 hours 30 minutes", or "08:30".
     * Numeric values are interpreted as decimal hours.
     */
    public static function minutesFromHoursValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) round(((float) $value) * 60));
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^(\d+)\s*h(?:ours?|rs?)?\s*(?:(\d+)\s*m(?:in(?:ute)?s?)?)?$/i', $text, $matches) === 1) {
            return ((int) $matches[1] * 60) + (int) ($matches[2] ?? 0);
        }

        if (preg_match('/^(\d+)\s*:\s*([0-5]\d)$/', $text, $matches) === 1) {
            return ((int) $matches[1] * 60) + (int) $matches[2];
        }

        return null;
    }

    public static function decimalHours(int $minutes, int $precision = 2): float
    {
        return round(max(0, $minutes) / 60, $precision);
    }

    public static function format(int $minutes): string
    {
        $minutes = max(0, $minutes);

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    }

    private static function clockTimeToMinutes(mixed $value): ?int
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?$/i', $text, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        $meridiem = strtoupper((string) ($matches[3] ?? ''));

        if ($minute > 59) {
            return null;
        }

        if ($meridiem !== '') {
            if ($hour < 1 || $hour > 12) {
                return null;
            }

            if ($meridiem === 'AM' && $hour === 12) {
                $hour = 0;
            } elseif ($meridiem === 'PM' && $hour !== 12) {
                $hour += 12;
            }
        } elseif ($hour > 23) {
            return null;
        }

        return ($hour * 60) + $minute;
    }
}
