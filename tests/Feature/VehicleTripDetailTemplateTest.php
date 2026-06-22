<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetRole;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTripDetailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_vehicle_and_trip_use_the_supplied_full_width_detail_templates(): void
    {
        $user = $this->superAdmin();

        FleetVehicle::query()->create([
            'code' => 'VHL-VIEW-001',
            'name' => 'Template Vehicle',
            'status' => 'Active',
            'payload' => [
                'vehicleValidationVersion' => 3,
                'id' => 'VHL-VIEW-001',
                'name' => 'Template Vehicle',
                'regNo' => 'DHAKA-TEST-1001',
                'model' => '2026',
                'category' => 'Car',
                'subCategory' => 'Sedan',
                'usage' => 'Double shift',
                'rentalType' => 'With Driver',
                'driver' => 'Driver One',
                'secondDriver' => 'Driver Two',
                'driverPaymentAmount' => 1000,
                'driverPaymentCycle' => 'Monthly',
                'secondDriverPaymentAmount' => 900,
                'secondDriverPaymentCycle' => 'Weekly',
                'vehicleRentalAmount' => 5000,
                'vehiclePaymentCycle' => 'Monthly',
                'totalRentalAmount' => 6900,
                'fuels' => [],
                'docs' => [],
                'status' => 'Active',
            ],
        ]);

        FleetTrip::query()->create([
            'code' => 'TRP-VIEW-001',
            'name' => 'TRP-VIEW-001',
            'status' => 'Submitted',
            'payload' => [
                'tripValidationVersion' => 2,
                'tripId' => 'TRP-VIEW-001',
                'savedAs' => 'Submitted',
                'startDate' => '2026-06-18',
                'purpose' => 'Template Office Trip',
                'details' => 'Template trip details',
                'vehicle' => 'VHL-VIEW-001 - Template Vehicle',
                'vehicleId' => 'VHL-VIEW-001',
                'driver' => 'DRV-VIEW-001 - Template Driver',
                'driverId' => 'DRV-VIEW-001',
                'totalCost' => 3500,
                'paidAmount' => 3000,
                'balanceDue' => 500,
                'paymentState' => 'Partially Paid',
                'payments' => [[
                    'method' => 'bKash',
                    'amount' => 3000,
                    'reference' => 'TXN-001',
                ]],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('fleet.vehicles.show', 'VHL-VIEW-001'))
            ->assertOk()
            ->assertSee('fleet-record-detail-page-vehicles', false)
            ->assertSee('Vehicle Information')
            ->assertSee('Driver 2 Payment Amount')
            ->assertSee(route('fleet.vehicles', ['action' => 'edit', 'code' => 'VHL-VIEW-001']), false);

        $this->actingAs($user)
            ->get(route('fleet.trips.show', 'TRP-VIEW-001'))
            ->assertOk()
            ->assertSee('fleet-record-detail-page-trips', false)
            ->assertSee('Route &amp; Odometer Information', false)
            ->assertSee('Partially Paid')
            ->assertSee('Payment 1')
            ->assertSee(route('fleet.trips', ['action' => 'edit', 'code' => 'TRP-VIEW-001']), false);
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
