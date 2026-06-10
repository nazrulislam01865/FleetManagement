<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDocumentName;
use App\Models\Fleet\FleetPermission;
use App\Models\Fleet\FleetRole;
use App\Models\Fleet\FleetVehicle;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeletePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_supervisor_cannot_delete_a_record_through_a_delete_route(): void
    {
        $user = $this->userForRole('supervisor');
        $document = FleetDocumentName::query()->create($this->documentAttributes());

        $this->actingAs($user)
            ->deleteJson(route('fleet.master-data.document-names.destroy', $document))
            ->assertForbidden()
            ->assertJson([
                'ok' => false,
                'permission' => FleetRbac::DELETE_PERMISSION_KEY,
            ]);

        $this->assertDatabaseHas('fleet_document_names', ['id' => $document->id]);
    }

    public function test_admin_user_cannot_delete_by_default(): void
    {
        $user = $this->userForRole('admin_user');
        $document = FleetDocumentName::query()->create($this->documentAttributes());

        $this->actingAs($user)
            ->deleteJson(route('fleet.master-data.document-names.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('fleet_document_names', ['id' => $document->id]);
        $this->assertFalse($user->fresh()->canDeleteFleetRecords());
    }

    public function test_super_admin_can_delete_by_default(): void
    {
        $user = $this->userForRole('super_admin');
        $document = FleetDocumentName::query()->create($this->documentAttributes());

        $this->actingAs($user)
            ->deleteJson(route('fleet.master-data.document-names.destroy', $document))
            ->assertOk();

        $this->assertDatabaseMissing('fleet_document_names', ['id' => $document->id]);
    }

    public function test_supervisor_cannot_delete_by_omitting_a_record_from_a_sync_payload(): void
    {
        $user = $this->userForRole('supervisor');
        FleetVehicle::query()->create([
            'code' => 'VEH-DELETE-GUARD',
            'name' => 'Protected Vehicle',
            'status' => 'Active',
            'payload' => [
                'id' => 'VEH-DELETE-GUARD',
                'name' => 'Protected Vehicle',
                'status' => 'Active',
            ],
        ]);

        $this->actingAs($user)
            ->postJson(route('fleet.vehicles.sync'), ['rows' => []])
            ->assertForbidden();

        $this->assertDatabaseHas('fleet_vehicles', ['code' => 'VEH-DELETE-GUARD']);
    }

    public function test_super_admin_can_grant_delete_permission_to_another_role(): void
    {
        $actor = $this->userForRole('super_admin');
        $role = $this->createCustomRole('Custom Delete Manager', 'custom-delete-manager');

        $this->actingAs($actor)
            ->post(route('fleet.role-matrix.update'), [
                'permissions' => [
                    $role->id => [
                        'master_data.view',
                        'master_data.manage',
                        FleetRbac::DELETE_PERMISSION_KEY,
                    ],
                ],
            ])
            ->assertRedirect(route('fleet.role-matrix'));

        $this->assertRolePermission($role, FleetRbac::DELETE_PERMISSION_KEY, true);

        $user = User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
        $document = FleetDocumentName::query()->create($this->documentAttributes());

        $this->actingAs($user)
            ->deleteJson(route('fleet.master-data.document-names.destroy', $document))
            ->assertOk();

        $this->assertDatabaseMissing('fleet_document_names', ['id' => $document->id]);
    }

    public function test_non_super_admin_cannot_grant_delete_permission_from_role_matrix(): void
    {
        $delegatedRole = $this->createCustomRole('Delegated Role Manager', 'delegated-role-manager');
        $targetRole = $this->createCustomRole('Target Role', 'target-role');

        $this->setRolePermission($delegatedRole, 'role_matrix.view', true);
        $this->setRolePermission($delegatedRole, 'role_matrix.manage', true);

        $actor = User::factory()->create([
            'fleet_role_id' => $delegatedRole->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);

        $this->actingAs($actor)
            ->post(route('fleet.role-matrix.update'), [
                'permissions' => [
                    $delegatedRole->id => ['role_matrix.view', 'role_matrix.manage'],
                    $targetRole->id => [FleetRbac::DELETE_PERMISSION_KEY],
                ],
            ])
            ->assertRedirect(route('fleet.role-matrix'));

        $this->assertRolePermission($targetRole, FleetRbac::DELETE_PERMISSION_KEY, false);
    }

    private function userForRole(string $slug): User
    {
        $role = FleetRole::query()->where('slug', $slug)->firstOrFail();

        return User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
    }

    private function createCustomRole(string $name, string $slug): FleetRole
    {
        return FleetRole::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => 'Custom role used for delete-permission testing.',
            'sort_order' => 500,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    private function setRolePermission(FleetRole $role, string $permissionKey, bool $allowed): void
    {
        $permission = FleetPermission::query()->where('key', $permissionKey)->firstOrFail();

        DB::table('fleet_role_permissions')->updateOrInsert(
            ['role_id' => $role->id, 'permission_id' => $permission->id],
            ['allowed' => $allowed, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function assertRolePermission(FleetRole $role, string $permissionKey, bool $expected): void
    {
        $permission = FleetPermission::query()->where('key', $permissionKey)->firstOrFail();
        $actual = (bool) DB::table('fleet_role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->value('allowed');

        $this->assertSame($expected, $actual);
    }

    private function documentAttributes(): array
    {
        return [
            'code' => 'DELETE-GUARD-DOCUMENT',
            'name' => 'Delete Guard Document',
            'document_type' => 'Vehicles',
            'document_types' => ['Vehicles'],
            'description' => null,
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
