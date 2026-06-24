<?php

namespace Tests\Feature;

use Tests\TestCase;

class FuelRechargePhotoRequirementsTest extends TestCase
{
    public function test_fuel_recharge_photo_capture_options_use_the_requested_order(): void
    {
        $requirements = collect(config('fleetman.photo_requirements'));

        $this->assertSame(
            ['vehicle', 'odo', 'fuel', 'other'],
            $requirements->pluck('key')->all(),
        );

        $this->assertSame(
            ['1. Vehicle Photo', '2. Odometer Photo', '3. Fuel Dispenser Photo', '4. Other Photo'],
            $requirements->pluck('title')->all(),
        );

        $this->assertSame(
            [true, true, true, false],
            $requirements->pluck('required')->all(),
        );
    }
}
