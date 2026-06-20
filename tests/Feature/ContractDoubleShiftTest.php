<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetRole;
use App\Models\Fleet\FleetVehicle;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractDoubleShiftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_double_shift_contract_saves_two_distinct_driver_shift_assignments(): void
    {
        $user = $this->superAdmin();
        $this->createDriver('DRV-001', 'Assigned Driver');
        $this->createDriver('DRV-002', 'Available Driver');
        $this->createVehicle('VEH-001', 'Double shift', 'Assigned Driver');

        $response = $this->actingAs($user)->postJson(route('fleet.contracts.sync'), [
            'rows' => [$this->contractRow()],
            'validateContractId' => 'CNT-DOUBLE-001',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rows.0.assignments.0.shiftType', 'Double')
            ->assertJsonPath('rows.0.assignments.0.driverId', 'DRV-001')
            ->assertJsonPath('rows.0.assignments.0.secondDriverId', 'DRV-002')
            ->assertJsonPath('rows.0.assignments.0.drivers.0.shiftId', 'DAY_SHIFT')
            ->assertJsonPath('rows.0.assignments.0.drivers.1.shiftId', 'NIGHT_SHIFT');

        $saved = FleetContract::query()->where('code', 'CNT-DOUBLE-001')->firstOrFail();
        $assignment = $saved->payload['assignments'][0];

        $this->assertSame('Double', $assignment['shiftType']);
        $this->assertCount(2, $assignment['drivers']);
        $this->assertSame('DRV-001', $assignment['drivers'][0]['driverId']);
        $this->assertSame('DRV-002', $assignment['drivers'][1]['driverId']);
    }

    public function test_double_shift_rejects_same_driver_or_same_shift_twice(): void
    {
        $user = $this->superAdmin();
        $this->createDriver('DRV-001', 'Assigned Driver');
        $this->createDriver('DRV-002', 'Available Driver');
        $this->createVehicle('VEH-001', 'Double shift', 'Assigned Driver');

        $row = $this->contractRow();
        $row['assignments'][0]['drivers'][1]['driverId'] = 'DRV-001';
        $row['assignments'][0]['drivers'][1]['shiftId'] = 'DAY_SHIFT';

        $this->actingAs($user)
            ->postJson(route('fleet.contracts.sync'), [
                'rows' => [$row],
                'validateContractId' => 'CNT-DOUBLE-001',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rows.0.assignments.0.drivers.1.driverId',
                'rows.0.assignments.0.drivers.1.shiftId',
            ]);
    }

    public function test_double_shift_vehicle_cannot_be_submitted_as_single_shift(): void
    {
        $user = $this->superAdmin();
        $this->createDriver('DRV-001', 'Assigned Driver');
        $this->createVehicle('VEH-001', 'Double shift', 'Assigned Driver');

        $row = $this->contractRow();
        $row['assignments'][0] = [
            'shiftType' => 'Single',
            'vehicleId' => 'VEH-001',
            'driverId' => 'DRV-001',
            'rate' => 100,
            'duty' => 8,
        ];

        $this->actingAs($user)
            ->postJson(route('fleet.contracts.sync'), [
                'rows' => [$row],
                'validateContractId' => 'CNT-DOUBLE-001',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rows.0.assignments.0.shiftType']);
    }

    public function test_shift_master_page_is_available_and_seeded(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('fleet.master-data.shifts'))
            ->assertOk()
            ->assertSee('Shift Master')
            ->assertSee('Day Shift')
            ->assertSee('Night Shift');
    }

    private function contractRow(): array
    {
        return [
            'contractId' => 'CNT-DOUBLE-001',
            'contractWith' => 'Client',
            'partyId' => 'CLI-001',
            'partyName' => 'Test Client',
            'amount' => 10000,
            'status' => 'Active',
            'contractStart' => '2026-06-17',
            'contractEnd' => '2026-12-31',
            'details' => 'Double shift contract test.',
            'assignments' => [[
                'shiftType' => 'Double',
                'vehicleId' => 'VEH-001',
                'rate' => 100,
                'duty' => 12,
                'drivers' => [
                    ['driverId' => 'DRV-001', 'shiftId' => 'DAY_SHIFT'],
                    ['driverId' => 'DRV-002', 'shiftId' => 'NIGHT_SHIFT'],
                ],
            ]],
            'documents' => [[
                'name' => 'Contract Agreement',
                'expiry' => '2026-12-31',
                'reminder' => null,
                'file' => [
                    'filePath' => 'fleet/contracts/CNT-DOUBLE-001/documents/agreement.pdf',
                    'originalName' => 'agreement.pdf',
                    'sizeBytes' => 1024,
                ],
            ]],
            'savedAs' => 'Submitted',
        ];
    }

    private function createDriver(string $code, string $name): void
    {
        FleetDriver::query()->create([
            'code' => $code,
            'name' => $name,
            'status' => 'Active',
            'payload' => [
                'driverId' => $code,
                'fullName' => $name,
                'status' => 'Active',
            ],
        ]);
    }

    private function createVehicle(string $code, string $usage, string $driver): void
    {
        FleetVehicle::query()->create([
            'code' => $code,
            'name' => 'Vehicle '.$code,
            'status' => 'Active',
            'payload' => [
                'id' => $code,
                'name' => 'Vehicle '.$code,
                'usage' => $usage,
                'driver' => $driver,
                'status' => 'Active',
            ],
        ]);
    }

    private function superAdmin(): User
    {
        $role = FleetRole::query()->where('slug', 'super_admin')->firstOrFail();

        return User::factory()->create([
            'fleet_role_id' => $role->id,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
        ]);
    }
}
