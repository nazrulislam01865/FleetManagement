<?php

namespace Tests\Unit;

use App\Support\FleetDuration;
use PHPUnit\Framework\TestCase;

class FleetDurationTest extends TestCase
{
    public function test_it_parses_decimal_and_human_readable_hours(): void
    {
        $this->assertSame(510, FleetDuration::minutesFromHoursValue(8.5));
        $this->assertSame(510, FleetDuration::minutesFromHoursValue('8h 30m'));
        $this->assertSame(510, FleetDuration::minutesFromHoursValue('8 hours 30 minutes'));
        $this->assertSame(510, FleetDuration::minutesFromHoursValue('08:30'));
    }

    public function test_it_calculates_overnight_shift_minutes(): void
    {
        $this->assertSame(465, FleetDuration::minutesBetween('22:30', '06:15'));
        $this->assertSame('7h 45m', FleetDuration::format(465));
        $this->assertSame(7.75, FleetDuration::decimalHours(465));
    }

    public function test_payload_uses_real_times_without_fabricating_default_hours(): void
    {
        $this->assertSame(0, FleetDuration::minutesFromPayload([]));
        $this->assertSame(525, FleetDuration::minutesFromPayload([
            'startTime' => '08:15',
            'endTime' => '17:00',
        ]));
    }


    public function test_valid_clock_times_override_stale_saved_duration_values(): void
    {
        $this->assertSame(540, FleetDuration::minutesFromPayload([
            'totalMinutes' => 420,
            'hours' => '7h 0m',
            'totalTime' => 7,
            'startTime' => '08:00',
            'endTime' => '17:00',
        ]));
    }

    public function test_stale_zero_duration_falls_back_to_valid_times(): void
    {
        $this->assertSame(540, FleetDuration::minutesFromPayload([
            'hours' => '0h 0m',
            'totalTime' => 0,
            'startTime' => '08:00',
            'endTime' => '17:00',
        ]));
    }
}
