<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsBrandingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_only_super_admin_can_open_company_branding_settings(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        $admin = $this->userForRole('admin_user');

        $this->actingAs($superAdmin)
            ->get(route('fleet.settings'))
            ->assertOk()
            ->assertSee('Company Favicon')
            ->assertSee('Company Logo');

        $this->actingAs($admin)
            ->get(route('fleet.settings'))
            ->assertForbidden();
    }

    public function test_super_admin_can_use_temporary_branding_upload_scope(): void
    {
        $superAdmin = $this->userForRole('super_admin');

        $response = $this->actingAs($superAdmin)
            ->post(route('fleet.uploads.store'), [
                'upload_scope' => 'settings',
                'upload_kind' => 'generic',
                'file' => UploadedFile::fake()->image('company-logo.png', 200, 200),
            ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['file' => ['tempToken', 'previewUrl']]);
    }

    public function test_non_super_admin_cannot_use_temporary_branding_upload_scope(): void
    {
        $admin = $this->userForRole('admin_user');

        $this->actingAs($admin)
            ->post(route('fleet.uploads.store'), [
                'upload_scope' => 'settings',
                'upload_kind' => 'generic',
                'file' => UploadedFile::fake()->image('company-logo.png', 200, 200),
            ], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Only Super Admin can upload company branding files.');
    }

    public function test_super_admin_can_update_logo_and_favicon_directly(): void
    {
        $superAdmin = $this->userForRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('fleet.settings.update-logo'), [
                'logo' => UploadedFile::fake()->image('company-logo.png', 400, 180),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($superAdmin)
            ->post(route('fleet.settings.update-favicon'), [
                'favicon' => UploadedFile::fake()->image('company-favicon.png', 64, 64),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertCount(1, Storage::disk('public')->files('logo'));
        $this->assertCount(1, Storage::disk('public')->files('favicon'));

        $this->get(route('brand.logo'))->assertOk();
        $this->get(route('brand.favicon'))->assertOk();
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
