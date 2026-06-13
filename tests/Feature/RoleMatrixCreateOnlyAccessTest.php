<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetPermission;
use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleMatrixCreateOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_manage_only_user_can_open_add_page_but_not_driver_list(): void
    {
        $user = $this->createManageOnlyUser('drivers');

        FleetDriver::query()->create([
            'code' => 'DVR-EXISTING',
            'name' => 'Existing Driver',
            'status' => 'Active',
            'payload' => [
                'driverId' => 'DVR-EXISTING',
                'fullName' => 'Existing Driver',
                'status' => 'Active',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('fleet.drivers', ['action' => 'add']))
            ->assertOk()
            ->assertSee('Add Driver')
            ->assertDontSee('Existing Driver');

        $this->actingAs($user)
            ->get(route('fleet.drivers', ['action' => 'list']))
            ->assertForbidden();
    }

    public function test_manage_only_save_does_not_delete_or_return_existing_driver_rows(): void
    {
        $user = $this->createManageOnlyUser('drivers');

        FleetDriver::query()->create([
            'code' => 'DVR-EXISTING',
            'name' => 'Existing Driver',
            'status' => 'Active',
            'payload' => [
                'driverId' => 'DVR-EXISTING',
                'fullName' => 'Existing Driver',
                'status' => 'Active',
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('fleet.drivers.sync'), [
                'rows' => [[
                    'driverId' => 'DVR-CREATE-ONLY-001',
                    'fullName' => 'Create Only Driver',
                    'status' => 'Draft',
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('can_view_list', false)
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.driverId', 'DVR-CREATE-ONLY-001');

        $this->assertDatabaseHas('fleet_drivers', ['code' => 'DVR-EXISTING']);
        $this->assertDatabaseHas('fleet_drivers', ['code' => 'DVR-CREATE-ONLY-001']);
    }

    public function test_first_allowed_destination_uses_add_page_for_manage_only_role(): void
    {
        $user = $this->createManageOnlyUser('drivers');

        $this->assertSame([
            'route' => 'fleet.drivers',
            'parameters' => ['action' => 'add'],
        ], FleetRbac::firstAllowedDestination($user));
    }

    private function createManageOnlyUser(string $module): User
    {
        $role = FleetRole::query()->create([
            'name' => 'Create Only '.ucfirst($module),
            'slug' => 'create-only-'.$module,
            'description' => 'Can create records without list access.',
            'sort_order' => 900,
            'is_system' => false,
            'is_active' => true,
        ]);

        $managePermission = FleetPermission::query()
            ->where('key', $module.'.manage')
            ->firstOrFail();
        $viewPermission = FleetPermission::query()
            ->where('key', $module.'.view')
            ->firstOrFail();

        DB::table('fleet_role_permissions')->insert([
            [
                'role_id' => $role->id,
                'permission_id' => $managePermission->id,
                'allowed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => $role->id,
                'permission_id' => $viewPermission->id,
                'allowed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
    }
}
