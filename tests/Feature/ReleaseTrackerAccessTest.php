<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetRelease;
use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseTrackerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_super_admin_can_open_release_tracker_and_see_its_menu_item(): void
    {
        $user = $this->userForRole('super_admin');

        $this->actingAs($user)
            ->get(route('fleet.release-tracker'))
            ->assertOk()
            ->assertSee('Release Tracker')
            ->assertSee('Super Admin Only');
    }

    public function test_non_super_admin_cannot_open_release_tracker_and_cannot_see_menu_item(): void
    {
        $user = $this->userForRole('admin_user');

        $this->actingAs($user)
            ->get(route('fleet.release-tracker'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('fleet.dashboard'))
            ->assertOk()
            ->assertDontSee('Release Tracker');
    }

    public function test_super_admin_can_create_update_and_delete_release_entries(): void
    {
        $user = $this->userForRole('super_admin');

        $this->actingAs($user)
            ->post(route('fleet.release-tracker.store'), [
                'version' => 'v1.5.0',
                'title' => 'Release tracker launch',
                'release_date' => '2026-06-11',
                'environment' => 'production',
                'status' => 'released',
                'summary' => 'Added the release tracker.',
                'changes' => "Added release history.\nAdded Super Admin protection.",
                'known_issues' => 'None',
            ])
            ->assertRedirect(route('fleet.release-tracker'));

        $release = FleetRelease::query()->where('version', 'v1.5.0')->firstOrFail();

        $this->assertDatabaseHas('fleet_releases', [
            'id' => $release->id,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('fleet.release-tracker.update', $release), [
                '_release_id' => $release->id,
                'version' => 'v1.5.1',
                'title' => 'Release tracker patch',
                'release_date' => '2026-06-12',
                'environment' => 'staging',
                'status' => 'scheduled',
                'summary' => 'Updated release tracker.',
                'changes' => 'Improved validation.',
                'known_issues' => null,
            ])
            ->assertRedirect(route('fleet.release-tracker'));

        $this->assertDatabaseHas('fleet_releases', [
            'id' => $release->id,
            'version' => 'v1.5.1',
            'environment' => 'staging',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->delete(route('fleet.release-tracker.destroy', $release))
            ->assertRedirect(route('fleet.release-tracker'));

        $this->assertDatabaseMissing('fleet_releases', ['id' => $release->id]);
    }

    public function test_admin_user_cannot_create_update_or_delete_release_entries(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        $admin = $this->userForRole('admin_user');
        $release = FleetRelease::query()->create([
            'version' => 'v2.0.0',
            'title' => 'Protected release',
            'release_date' => '2026-06-11',
            'environment' => 'production',
            'status' => 'draft',
            'created_by_user_id' => $superAdmin->id,
            'updated_by_user_id' => $superAdmin->id,
        ]);

        $payload = [
            '_release_id' => $release->id,
            'version' => 'v2.0.1',
            'title' => 'Unauthorized change',
            'release_date' => '2026-06-12',
            'environment' => 'production',
            'status' => 'released',
        ];

        $this->actingAs($admin)
            ->post(route('fleet.release-tracker.store'), $payload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('fleet.release-tracker.update', $release), $payload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('fleet.release-tracker.destroy', $release))
            ->assertForbidden();

        $this->assertDatabaseHas('fleet_releases', [
            'id' => $release->id,
            'version' => 'v2.0.0',
        ]);
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
