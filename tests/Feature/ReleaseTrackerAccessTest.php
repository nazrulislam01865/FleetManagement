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

    public function test_every_authenticated_role_can_open_release_list_but_only_super_admin_sees_edit_actions(): void
    {
        $initiator = $this->userForRole('supervisor');
        $release = $this->createRelease($initiator, $initiator);

        foreach (['super_admin', 'admin_user', 'supervisor', 'field_officer', 'fuel_operator'] as $slug) {
            $response = $this->actingAs($this->userForRole($slug))
                ->get(route('fleet.release-tracker'))
                ->assertOk()
                ->assertSee('Release List');

            if ($slug === 'super_admin') {
                $response
                    ->assertSee('Super Admin Management')
                    ->assertSee(route('fleet.release-tracker.edit', $release), false);
            } else {
                $response
                    ->assertSee('Read Only')
                    ->assertDontSee(route('fleet.release-tracker.edit', $release), false);
            }
        }
    }

    public function test_only_super_admin_can_open_or_submit_release_form(): void
    {
        $initiator = $this->userForRole('supervisor');

        $this->actingAs($this->userForRole('super_admin'))
            ->get(route('fleet.release-tracker.form'))
            ->assertOk()
            ->assertSee('Issue Type')
            ->assertSee('Initiated By');

        foreach (['admin_user', 'supervisor', 'field_officer', 'fuel_operator'] as $slug) {
            $user = $this->userForRole($slug);

            $this->actingAs($user)
                ->get(route('fleet.release-tracker.form'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('fleet.release-tracker.store'), $this->releasePayload($initiator))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_create_release_with_issue_type_and_initiator(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        $initiator = $this->userForRole('supervisor');

        $this->actingAs($superAdmin)
            ->post(route('fleet.release-tracker.store'), $this->releasePayload($initiator))
            ->assertRedirect(route('fleet.release-tracker.form'));

        $this->assertDatabaseHas('fleet_releases', [
            'version' => 'v1.5.0',
            'issue_type' => 'Feature',
            'initiated_by_user_id' => $initiator->id,
            'created_by_user_id' => $superAdmin->id,
            'updated_by_user_id' => $superAdmin->id,
        ]);
    }

    public function test_only_super_admin_can_open_edit_page_and_update_release(): void
    {
        $creator = $this->userForRole('super_admin');
        $initiator = $this->userForRole('supervisor');
        $release = $this->createRelease($creator, $initiator);

        $this->actingAs($creator)
            ->get(route('fleet.release-tracker.edit', $release))
            ->assertOk()
            ->assertSee('Edit Release')
            ->assertSee($release->version);

        $payload = $this->releasePayload($initiator);
        $payload['version'] = $release->version;
        $payload['title'] = 'Updated release notes title';
        $payload['changes'] = 'Updated by Super Admin only.';

        $this->actingAs($creator)
            ->put(route('fleet.release-tracker.update', $release), $payload)
            ->assertRedirect(route('fleet.release-tracker'));

        $this->assertDatabaseHas('fleet_releases', [
            'id' => $release->id,
            'title' => 'Updated release notes title',
            'changes' => 'Updated by Super Admin only.',
            'updated_by_user_id' => $creator->id,
        ]);

        foreach (['admin_user', 'supervisor', 'field_officer', 'fuel_operator'] as $slug) {
            $user = $this->userForRole($slug);

            $this->actingAs($user)
                ->get(route('fleet.release-tracker.edit', $release))
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('fleet.release-tracker.update', $release), $payload)
                ->assertForbidden();
        }
    }

    public function test_inactive_user_cannot_be_selected_as_initiator(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        $inactive = $this->userForRole('supervisor');
        $inactive->update(['account_status' => User::ACCOUNT_STATUS_INACTIVE]);

        $this->actingAs($superAdmin)
            ->post(route('fleet.release-tracker.store'), $this->releasePayload($inactive))
            ->assertSessionHasErrors('initiated_by_user_id');
    }

    private function createRelease(User $creator, User $initiator): FleetRelease
    {
        return FleetRelease::query()->create([
            'version' => 'v1.4.0',
            'title' => 'Existing release entry',
            'issue_type' => 'Enhancement',
            'initiated_by_user_id' => $initiator->id,
            'release_date' => '2026-06-16',
            'environment' => 'production',
            'status' => 'released',
            'summary' => 'Existing summary.',
            'changes' => 'Existing release notes.',
            'known_issues' => 'None',
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $creator->id,
        ]);
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
