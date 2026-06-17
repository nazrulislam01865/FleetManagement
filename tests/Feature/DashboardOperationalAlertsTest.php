<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetRole;
use App\Models\Fleet\FleetVehicle;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperationalAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_operational_alerts_show_driver_and_vehicle_document_reviews_with_filtered_links(): void
    {
        $user = $this->userForRole('super_admin');

        FleetDriver::query()->create([
            'code' => 'DRV-ALERT-001',
            'name' => 'Alert Driver',
            'status' => 'Active',
            'payload' => [
                'driverId' => 'DRV-ALERT-001',
                'fullName' => 'Alert Driver',
                'licenseValidity' => now('Asia/Dhaka')->addDays(30)->toDateString(),
                'status' => 'Active',
            ],
        ]);

        FleetVehicle::query()->create([
            'code' => 'VEH-ALERT-001',
            'name' => 'Alert Vehicle',
            'status' => 'Active',
            'payload' => [
                'id' => 'VEH-ALERT-001',
                'name' => 'Alert Vehicle',
                'status' => 'Active',
                'docs' => [
                    ['name' => 'Fitness Certificate', 'expiry' => now('Asia/Dhaka')->addDays(45)->toDateString()],
                ],
            ],
        ]);

        FleetVehicle::query()->create([
            'code' => 'VEH-NO-ALERT-001',
            'name' => 'Future Vehicle',
            'status' => 'Active',
            'payload' => [
                'id' => 'VEH-NO-ALERT-001',
                'name' => 'Future Vehicle',
                'status' => 'Active',
                'docs' => [
                    ['name' => 'Fitness Certificate', 'expiry' => now('Asia/Dhaka')->addDays(365)->toDateString()],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('fleet.dashboard'));

        $response->assertOk()
            ->assertSee('Driver related document')
            ->assertSee('Vehicle related document review')
            ->assertSee(route('fleet.drivers', ['license_filter' => 'within-180-days']), false)
            ->assertSee(route('fleet.vehicles', ['document_filter' => 'within-180-days']), false)
            ->assertDontSee('Trip payment balance');
    }

    private function userForRole(string $slug): User
    {
        $role = FleetRole::query()->where('slug', $slug)->firstOrFail();

        return User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
    }
}
