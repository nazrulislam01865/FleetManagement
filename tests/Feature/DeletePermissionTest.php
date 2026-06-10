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

    public function test_admin_user_can_delete_a_record_through_a_delete_route(): void
    {
        $user = $this->userForRole('admin_user');
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

    public function test_delete_permission_cannot_be_granted_to_a_custom_role(): void
    {
        $actor = $this->userForRole('super_admin');
        $role = FleetRole::query()->create([
            'name' => 'Custom Manager',
            'slug' => 'custom-manager',
            'description' => 'Custom role used for delete-permission protection testing.',
            'sort_order' => 500,
            'is_system' => false,
            'is_active' => true,
        ]);
        $deletePermission = FleetPermission::query()
            ->where('key', FleetRbac::DELETE_PERMISSION_KEY)
            ->firstOrFail();

        $this->actingAs($actor)
            ->post(route('fleet.role-matrix.update'), [
                'permissions' => [
                    $role->id => [FleetRbac::DELETE_PERMISSION_KEY],
                ],
            ])
            ->assertRedirect(route('fleet.role-matrix'));

        $this->assertFalse((bool) DB::table('fleet_role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $deletePermission->id)
            ->value('allowed'));
    }

    private function userForRole(string $slug): User
    {
        $role = FleetRole::query()->where('slug', $slug)->firstOrFail();

        return User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
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
