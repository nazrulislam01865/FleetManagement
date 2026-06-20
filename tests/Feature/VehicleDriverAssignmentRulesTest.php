<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetFuelType;
use App\Models\Fleet\FleetLookup;
use App\Models\Fleet\FleetRole;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVehicleCategory;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDriverAssignmentRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);

        FleetLookup::query()->create([
            'group' => 'usage_type',
            'key' => 'single_shift',
            'label' => 'Single shift',
            'value' => 'Single shift',
            'meta' => ['title' => 'Single shift'],
            'sort_order' => 1,
            'is_active' => true,
        ]);
        FleetLookup::query()->create([
            'group' => 'usage_type',
            'key' => 'double_shift',
            'label' => 'Double shift',
            'value' => 'Double shift',
            'meta' => ['title' => 'Double shift'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        FleetVehicleCategory::query()->create([
            'code' => 'CAR',
            'name' => 'Car',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        FleetFuelType::query()->create([
            'code' => 'DIESEL',
            'name' => 'Diesel',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->createDriver('DRV-001', 'Driver One');
        $this->createDriver('DRV-002', 'Driver Two');
    }

    public function test_with_driver_single_shift_requires_driver_one(): void
    {
        $row = $this->vehicleRow('Single shift', 'With Driver');
        $row['driver'] = null;

        $this->actingAs($this->superAdmin())
            ->postJson(route('fleet.vehicles.sync'), ['rows' => [$row]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rows.0.driver']);
    }

    public function test_with_driver_double_shift_requires_two_different_drivers(): void
    {
        $row = $this->vehicleRow('Double shift', 'With Driver');
        $row['driver'] = 'Driver One';
        $row['secondDriver'] = null;
        $row['secondDriverPaymentAmount'] = null;
        $row['secondDriverPaymentCycle'] = null;

        $this->actingAs($this->superAdmin())
            ->postJson(route('fleet.vehicles.sync'), ['rows' => [$row]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rows.0.secondDriver']);

        $row['secondDriver'] = 'Driver One';
        $row['secondDriverPaymentAmount'] = 900;
        $row['secondDriverPaymentCycle'] = 'Monthly';

        $this->actingAs($this->superAdmin())
            ->postJson(route('fleet.vehicles.sync'), ['rows' => [$row]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rows.0.secondDriver']);
    }

    public function test_with_driver_double_shift_saves_two_drivers(): void
    {
        $row = $this->vehicleRow('Double shift', 'With Driver');
        $row['driver'] = 'Driver One';
        $row['secondDriver'] = 'Driver Two';
        $row['secondDriverPaymentAmount'] = 900;
        $row['secondDriverPaymentCycle'] = 'Weekly';

        $this->actingAs($this->superAdmin())
            ->postJson(route('fleet.vehicles.sync'), ['rows' => [$row]])
            ->assertOk()
            ->assertJsonPath('rows.0.driver', 'Driver One')
            ->assertJsonPath('rows.0.secondDriver', 'Driver Two')
            ->assertJsonPath('rows.0.driverPaymentAmount', 1000)
            ->assertJsonPath('rows.0.driverPaymentCycle', 'Monthly')
            ->assertJsonPath('rows.0.secondDriverPaymentAmount', 900)
            ->assertJsonPath('rows.0.secondDriverPaymentCycle', 'Weekly')
            ->assertJsonPath('rows.0.totalRentalAmount', 6900);

        $saved = FleetVehicle::query()->where('code', 'VHL-DRIVER-RULE')->firstOrFail();
        $this->assertSame('Driver One', $saved->payload['driver']);
        $this->assertSame('Driver Two', $saved->payload['secondDriver']);
        $this->assertSame(1000.0, (float) $saved->payload['driverPaymentAmount']);
        $this->assertSame('Monthly', $saved->payload['driverPaymentCycle']);
        $this->assertSame(900.0, (float) $saved->payload['secondDriverPaymentAmount']);
        $this->assertSame('Weekly', $saved->payload['secondDriverPaymentCycle']);
        $this->assertSame(6900.0, (float) $saved->payload['totalRentalAmount']);
    }


    public function test_with_driver_double_shift_requires_separate_payment_details_for_driver_two(): void
    {
        $row = $this->vehicleRow('Double shift', 'With Driver');
        $row['driver'] = 'Driver One';
        $row['secondDriver'] = 'Driver Two';
        $row['secondDriverPaymentAmount'] = null;
        $row['secondDriverPaymentCycle'] = null;

        $this->actingAs($this->superAdmin())
            ->postJson(route('fleet.vehicles.sync'), ['rows' => [$row]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rows.0.secondDriverPaymentAmount',
                'rows.0.secondDriverPaymentCycle',
            ]);
    }

    public function test_without_driver_keeps_only_one_optional_driver_even_for_double_shift(): void
    {
        $row = $this->vehicleRow('Double shift', 'Without Driver');
        $row['driver'] = null;
        $row['secondDriver'] = 'Driver Two';
        $row['driverPaymentAmount'] = null;
        $row['driverPaymentCycle'] = null;
        $row['secondDriverPaymentAmount'] = 900;
        $row['secondDriverPaymentCycle'] = 'Weekly';

        $this->actingAs($this->superAdmin())
            ->postJson(route('fleet.vehicles.sync'), ['rows' => [$row]])
            ->assertOk()
            ->assertJsonPath('rows.0.driver', null)
            ->assertJsonPath('rows.0.secondDriver', null)
            ->assertJsonPath('rows.0.driverPaymentAmount', null)
            ->assertJsonPath('rows.0.driverPaymentCycle', null)
            ->assertJsonPath('rows.0.secondDriverPaymentAmount', null)
            ->assertJsonPath('rows.0.secondDriverPaymentCycle', null);
    }

    private function vehicleRow(string $usage, string $rentalType): array
    {
        return [
            'id' => 'VHL-DRIVER-RULE',
            'name' => 'Driver Rule Vehicle',
            'regNo' => 'DHAKA-TEST-1001',
            'vendor' => null,
            'model' => 'Test Model',
            'color' => 'White',
            'engineNo' => 'ENGINE-1001',
            'mileage' => 8,
            'odo' => 1000,
            'category' => 'Car',
            'subCategory' => null,
            'usage' => $usage,
            'rentalType' => $rentalType,
            'driver' => null,
            'secondDriver' => null,
            'driverPaymentAmount' => 1000,
            'driverPaymentCycle' => 'Monthly',
            'secondDriverPaymentAmount' => $usage === 'Double shift' && $rentalType === 'With Driver' ? 900 : null,
            'secondDriverPaymentCycle' => $usage === 'Double shift' && $rentalType === 'With Driver' ? 'Weekly' : null,
            'vehicleRentalAmount' => 5000,
            'vehiclePaymentCycle' => 'Monthly',
            'totalRentalAmount' => $rentalType === 'With Driver'
                ? ($usage === 'Double shift' ? 6900 : 6000)
                : 5000,
            'rent' => $rentalType === 'With Driver'
                ? ($usage === 'Double shift' ? 6900 : 6000)
                : 5000,
            'notes' => null,
            'image' => [],
            'fuels' => [[
                'type' => 'Diesel',
                'priority' => 'Primary',
                'rate' => 120,
            ]],
            'docs' => [],
            'status' => 'Active',
            'vehicleValidationVersion' => 3,
        ];
    }

    private function createDriver(string $code, string $name): void
    {
        FleetDriver::query()->create([
            'code' => $code,
            'name' => $name,
            'status' => 'Active',
            'payload' => [
                'driverId' => $code,
                'fullName' => $name,
                'status' => 'Active',
            ],
        ]);
    }

    private function superAdmin(): User
    {
        $role = FleetRole::query()->where('slug', 'super_admin')->firstOrFail();

        return User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
    }
}
