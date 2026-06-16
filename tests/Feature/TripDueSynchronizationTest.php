<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripDueSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_trip_updates_do_not_delete_other_trip_dues_or_create_duplicates(): void
    {
        $first = $this->tripRow('TRIP-DUE-001', 1000, 200);
        $second = $this->tripRow('TRIP-DUE-002', 500, 100);

        $this->withoutMiddleware()->postJson(route('fleet.trips.records.store'), ['row' => $first])->assertOk();
        $this->withoutMiddleware()->postJson(route('fleet.trips.records.store'), ['row' => $second])->assertOk();

        $this->assertSame(1, FleetDue::query()->where('code', 'DUE-TRP-TRIP-DUE-001')->count());
        $this->assertSame(1, FleetDue::query()->where('code', 'DUE-TRP-TRIP-DUE-002')->count());

        $first['payments'][0]['amount'] = 1000;
        $this->withoutMiddleware()
            ->putJson(route('fleet.trips.records.update', ['code' => 'TRIP-DUE-001']), ['row' => $first])
            ->assertOk();

        $this->assertDatabaseMissing('fleet_dues', ['code' => 'DUE-TRP-TRIP-DUE-001']);
        $this->assertDatabaseHas('fleet_dues', ['code' => 'DUE-TRP-TRIP-DUE-002', 'amount' => 400.00]);
        $this->assertSame(1, FleetDue::query()->where('source_type', 'Trip')->count());
    }

    private function tripRow(string $tripId, float $totalCost, float $paidAmount): array
    {
        return [
            'tripId' => $tripId,
            'tripValidationVersion' => 2,
            'savedAs' => 'Submitted',
            'startDate' => '2026-06-15',
            'vehicle' => 'Test Vehicle',
            'vehicleId' => '',
            'driver' => 'Test Driver',
            'driverId' => '',
            'purpose' => 'Official Duty',
            'client' => '',
            'clientId' => '',
            'fromLocation' => 'Dhaka',
            'toLocation' => 'Gazipur',
            'odoStart' => 100,
            'odoEnd' => 150,
            'totalCost' => $totalCost,
            'payments' => [[
                'method' => 'Cash',
                'amount' => $paidAmount,
                'reference' => '',
            ]],
            'details' => 'Trip due test',
        ];
    }
}
