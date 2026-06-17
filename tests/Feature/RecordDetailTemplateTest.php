<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordDetailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_driver_employee_and_contract_use_the_template_matched_full_width_detail_views(): void
    {
        $user = $this->userForRole('super_admin');

        FleetDriver::query()->create([
            'code' => 'DRV-VIEW-001',
            'name' => 'Template Driver',
            'status' => 'Active',
            'payload' => [
                'driverValidationVersion' => 1,
                'driverId' => 'DRV-VIEW-001',
                'fullName' => 'Template Driver',
                'contact' => '01700000000',
                'licenseType' => 'Lite',
                'licenseValidity' => '2027-01-17',
                'status' => 'Active',
                'documents' => [],
            ],
        ]);

        FleetEmployee::query()->create([
            'code' => 'EMP-VIEW-001',
            'name' => 'Template Employee',
            'status' => 'Active',
            'payload' => [
                'employeeValidationVersion' => 1,
                'employeeId' => 'EMP-VIEW-001',
                'fullName' => 'Template Employee',
                'designation' => 'Controller',
                'status' => 'Active',
                'documents' => [],
            ],
        ]);

        FleetContract::query()->create([
            'code' => 'CNT-VIEW-001',
            'name' => 'Template Party',
            'status' => 'Submitted',
            'payload' => [
                'contractId' => 'CNT-VIEW-001',
                'contractWith' => 'Client',
                'partyName' => 'Template Party',
                'amount' => 2000000,
                'status' => 'Active',
                'savedAs' => 'Submitted',
                'assignments' => [],
                'documents' => [],
            ],
        ]);

        $driver = $this->actingAs($user)->get(route('fleet.drivers.show', 'DRV-VIEW-001'));
        $driver->assertOk()
            ->assertSee('fleet-record-detail-page', false)
            ->assertSee('License & Duty Information', false)
            ->assertSee(route('fleet.drivers', ['action' => 'edit', 'code' => 'DRV-VIEW-001']), false);

        $employee = $this->actingAs($user)->get(route('fleet.employees.show', 'EMP-VIEW-001'));
        $employee->assertOk()
            ->assertSee('fleet-record-detail-page', false)
            ->assertSee('Employment Information')
            ->assertSee(route('fleet.employees', ['action' => 'edit', 'code' => 'EMP-VIEW-001']), false);

        $contract = $this->actingAs($user)->get(route('fleet.contracts.show', 'CNT-VIEW-001'));
        $contract->assertOk()
            ->assertSee('fleet-record-detail-page', false)
            ->assertSee('Assignments')
            ->assertSee(route('fleet.contracts', ['action' => 'edit', 'code' => 'CNT-VIEW-001']), false);
    }

    public function test_edit_link_preloads_a_requested_record_even_when_it_is_older_than_the_first_fifty_rows(): void
    {
        $user = $this->userForRole('super_admin');

        FleetDriver::query()->create([
            'code' => 'DRV-OLDER-TARGET',
            'name' => 'Older Target Driver',
            'status' => 'Active',
            'payload' => [
                'driverId' => 'DRV-OLDER-TARGET',
                'fullName' => 'Older Target Driver',
                'status' => 'Active',
            ],
        ]);

        foreach (range(1, 55) as $index) {
            FleetDriver::query()->create([
                'code' => sprintf('DRV-NEW-%03d', $index),
                'name' => 'New Driver '.$index,
                'status' => 'Active',
                'payload' => [
                    'driverId' => sprintf('DRV-NEW-%03d', $index),
                    'fullName' => 'New Driver '.$index,
                    'status' => 'Active',
                ],
            ]);
        }

        $this->actingAs($user)
            ->get(route('fleet.drivers', ['action' => 'edit', 'code' => 'DRV-OLDER-TARGET']))
            ->assertOk()
            ->assertSee('Older Target Driver')
            ->assertSee('DRV-OLDER-TARGET');
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
