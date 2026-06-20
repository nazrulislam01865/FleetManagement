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

    public function test_every_authenticated_role_can_open_read_only_release_list(): void
    {
        foreach (['super_admin', 'admin_user', 'supervisor', 'field_officer', 'fuel_operator'] as $slug) {
            $user = $this->userForRole($slug);

            $this->actingAs($user)
                ->get(route('fleet.release-tracker'))
                ->assertOk()
                ->assertSee('Release List')
                ->assertSee('Read Only');
        }
    }

    public function test_super_admin_and_admin_user_can_open_release_form(): void
    {
        foreach (['super_admin', 'admin_user'] as $slug) {
            $this->actingAs($this->userForRole($slug))
                ->get(route('fleet.release-tracker.form'))
                ->assertOk()
                ->assertSee('Issue Type')
                ->assertSee('Initiated By');
        }
    }

    public function test_non_admin_user_cannot_open_or_submit_release_form(): void
    {
        $initiator = $this->userForRole('supervisor');

        $this->actingAs($initiator)
            ->get(route('fleet.release-tracker.form'))
            ->assertForbidden();

        $this->actingAs($initiator)
            ->post(route('fleet.release-tracker.store'), $this->releasePayload($initiator))
            ->assertForbidden();
    }

    public function test_admin_user_can_create_release_with_issue_type_and_initiator(): void
    {
        $admin = $this->userForRole('admin_user');
        $initiator = $this->userForRole('supervisor');

        $this->actingAs($admin)
            ->post(route('fleet.release-tracker.store'), $this->releasePayload($initiator))
            ->assertRedirect(route('fleet.release-tracker.form'));

        $this->assertDatabaseHas('fleet_releases', [
            'version' => 'v1.5.0',
            'issue_type' => 'Feature',
            'initiated_by_user_id' => $initiator->id,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_inactive_user_cannot_be_selected_as_initiator(): void
    {
        $admin = $this->userForRole('admin_user');
        $inactive = $this->userForRole('supervisor');
        $inactive->update(['account_status' => User::ACCOUNT_STATUS_INACTIVE]);

        $this->actingAs($admin)
            ->post(route('fleet.release-tracker.store'), $this->releasePayload($inactive))
            ->assertSessionHasErrors('initiated_by_user_id');
    }

    private function releasePayload(User $initiator): array
    {
        return [
            'version' => 'v1.5.0',
            'title' => 'Release tracker split pages',
            'issue_type' => 'Feature',
            'initiated_by_user_id' => $initiator->id,
            'release_date' => '2026-06-17',
            'environment' => 'production',
            'status' => 'released',
            'summary' => 'Separated the form and read-only release list.',
            'changes' => 'Added issue type and initiated by fields.',
            'known_issues' => 'None',
        ];
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
