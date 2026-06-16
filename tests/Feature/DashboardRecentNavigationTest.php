<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetRole;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVendorParty;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRecentNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_recent_cards_have_view_all_actions_and_each_activity_opens_its_detail_page(): void
    {
        $user = $this->userForRole('super_admin');

        $records = [
            [FleetEmployee::class, 'EMP-DASH-001', 'Dashboard Employee', ['employeeId' => 'EMP-DASH-001', 'fullName' => 'Dashboard Employee', 'designation' => 'Officer', 'status' => 'Active']],
            [FleetVehicle::class, 'VEH-DASH-001', 'Dashboard Vehicle', ['id' => 'VEH-DASH-001', 'name' => 'Dashboard Vehicle', 'regNo' => 'DHAKA-001', 'category' => 'Car', 'status' => 'Active']],
            [FleetFuelRecharge::class, 'FUEL-DASH-001', 'Dashboard Vehicle', ['rechargeId' => 'FUEL-DASH-001', 'vehicle' => 'Dashboard Vehicle', 'fuelType' => 'Octane', 'totalAmount' => 1000, 'status' => 'Submitted']],
            [FleetDriver::class, 'DRV-DASH-001', 'Dashboard Driver', ['driverId' => 'DRV-DASH-001', 'fullName' => 'Dashboard Driver', 'contact' => '01700000000', 'status' => 'Active']],
            [FleetTrip::class, 'TRIP-DASH-001', 'Client Visit', ['tripId' => 'TRIP-DASH-001', 'purpose' => 'Client Visit', 'vehicle' => 'Dashboard Vehicle', 'driver' => 'Dashboard Driver', 'totalCost' => 1000, 'paidAmount' => 1000, 'status' => 'Completed']],
            [FleetClient::class, 'CLI-DASH-001', 'Dashboard Client', ['clientId' => 'CLI-DASH-001', 'clientName' => 'Dashboard Client', 'phone' => '01800000000', 'status' => 'Active']],
            [FleetVendorParty::class, 'VEN-DASH-001', 'Dashboard Vendor', ['partyId' => 'VEN-DASH-001', 'partyName' => 'Dashboard Vendor', 'phone' => '01900000000', 'status' => 'Active']],
        ];

        foreach ($records as [$modelClass, $code, $name, $payload]) {
            $modelClass::query()->create([
                'code' => $code,
                'name' => $name,
                'status' => $payload['status'] ?? null,
                'payload' => $payload,
            ]);
        }

        $response = $this->actingAs($user)->get(route('fleet.dashboard'));

        $response->assertOk();

        foreach ([
            route('fleet.employees.show', ['code' => 'EMP-DASH-001']),
            route('fleet.vehicles.show', ['code' => 'VEH-DASH-001']),
            route('fleet.fuel-recharge.show', ['code' => 'FUEL-DASH-001']),
            route('fleet.drivers.show', ['code' => 'DRV-DASH-001']),
            route('fleet.trips.show', ['code' => 'TRIP-DASH-001']),
            route('fleet.clients.show', ['code' => 'CLI-DASH-001']),
            route('fleet.vendors.show', ['code' => 'VEN-DASH-001']),
        ] as $detailUrl) {
            $response->assertSee($detailUrl, false);
        }

        foreach ([
            route('fleet.notifications.index'),
            route('fleet.employees'),
            route('fleet.vehicles'),
            route('fleet.fuel-recharge'),
            route('fleet.drivers'),
            route('fleet.trips'),
            route('fleet.clients'),
            route('fleet.vendors'),
        ] as $listUrl) {
            $response->assertSee($listUrl, false);
        }

        $this->assertSame(8, substr_count($response->getContent(), 'class="dashboard-view-all"'));
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
