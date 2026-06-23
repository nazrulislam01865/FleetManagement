<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelRechargeDetailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FleetRbac::syncDefaults(true);
    }

    public function test_fuel_recharge_uses_the_supplied_full_width_square_photo_template(): void
    {
        $user = $this->superAdmin();

        FleetFuelRecharge::query()->create([
            'code' => 'FR-VIEW-001',
            'name' => 'VHL-001 - Template Vehicle',
            'status' => 'Submitted',
            'payload' => [
                'rechargeId' => 'FR-VIEW-001',
                'rechargeValidationVersion' => 2,
                'date' => '2026-06-21',
                'contractId' => 'CNT-001',
                'contract' => 'CNT-001 | Template Contract',
                'vehicleId' => 'VHL-001',
                'vehicle' => 'VHL-001 - Template Vehicle',
                'driverId' => 'DVR-001',
                'driver' => 'Template Driver',
                'driverShift' => 'Day Shift',
                'primaryFuelName' => 'CNG',
                'primaryFuelStation' => 'Template Filling Station',
                'primaryEnteredValue' => 569,
                'primaryQty' => 0,
                'primaryRate' => 0,
                'primaryAmount' => 569,
                'primaryPricingMode' => 'direct_amount',
                'primaryEntryUnit' => 'Taka',
                'hasSecondaryFuel' => false,
                'startKm' => 131382,
                'endKm' => 131438,
                'odoReading' => 131438,
                'totalKm' => 56,
                'mileage' => 0,
                'tkKm' => 10.16,
                'totalAmount' => 569,
                'status' => 'Submitted',
                'submittedBy' => 'Template User',
                'photos' => [
                    'vehicle' => [
                        'captured' => true,
                        'time' => '21/06/2026, 08:13:12',
                        'place' => 'Dhaka, Bangladesh',
                        'capturedAt' => '2026-06-21T02:13:14.506Z',
                        'file' => [
                            'filePath' => 'fleet/fuel-recharges/FR-VIEW-001/photos/vehicle/vehicle.jpg',
                            'originalName' => 'vehicle.jpg',
                            'mimeType' => 'image/jpeg',
                            'sizeBytes' => 2048,
                        ],
                    ],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('fleet.fuel-recharge.show', 'FR-VIEW-001'))
            ->assertOk()
            ->assertSee('fleet-record-detail-page-fuel-recharges', false)
            ->assertSee('Contract &amp; Vehicle Information', false)
            ->assertSee('Photo Evidence')
            ->assertSee('record-fuel-photo-grid', false)
            ->assertSee('Fuel Amount Information')
            ->assertSee('ODO &amp; Submission Information', false)
            ->assertSee('Template Filling Station')
            ->assertSee(route('fleet.fuel-recharge', ['action' => 'edit', 'code' => 'FR-VIEW-001']), false);
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
