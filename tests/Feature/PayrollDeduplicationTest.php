<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDue;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetVehicle;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }


    public function test_double_shift_vehicle_generates_one_independent_due_for_each_driver(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-28 12:00:00', 'Asia/Dhaka'));

        FleetVehicle::query()->create([
            'code' => 'VEH-DOUBLE-001',
            'name' => 'Double Shift Vehicle',
            'status' => 'Active',
            'payload' => [
                'id' => 'VEH-DOUBLE-001',
                'regNo' => 'DHAKA-DOUBLE-001',
                'status' => 'Active',
                'rentalType' => 'With Driver',
                'usage' => 'Double shift',
                'driver' => 'Driver One',
                'driverPaymentAmount' => 700,
                'driverPaymentCycle' => 'Weekly',
                'secondDriver' => 'Driver Two',
                'secondDriverPaymentAmount' => 100,
                'secondDriverPaymentCycle' => 'Daily',
                'vehicleRentalAmount' => 0,
                'vehiclePaymentCycle' => 'Monthly',
            ],
        ]);

        $this->withoutMiddleware()
            ->postJson(route('fleet.dues.generate-payroll'), ['month' => '2026-06'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->withoutMiddleware()
            ->postJson(route('fleet.dues.generate-payroll'), ['month' => '2026-06'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, FleetDue::query()->where('code', 'RENT-DRV-VEH-DOUBLE-001-2026-06')->count());
        $this->assertSame(1, FleetDue::query()->where('code', 'RENT-DRV2-VEH-DOUBLE-001-2026-06')->count());

        $this->assertDatabaseHas('fleet_dues', [
            'code' => 'RENT-DRV-VEH-DOUBLE-001-2026-06',
            'party_id' => 'Driver One',
            'amount' => 3000.00,
        ]);
        $this->assertDatabaseHas('fleet_dues', [
            'code' => 'RENT-DRV2-VEH-DOUBLE-001-2026-06',
            'party_id' => 'Driver Two',
            'amount' => 3000.00,
        ]);
    }

    public function test_each_entity_receives_only_one_due_for_the_selected_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-28 12:00:00', 'Asia/Dhaka'));

        FleetDriver::query()->create([
            'code' => 'DVR-WEEKLY-001',
            'name' => 'Weekly Driver',
            'status' => 'Active',
            'payload' => [
                'driverId' => 'DVR-WEEKLY-001',
                'fullName' => 'Weekly Driver',
                'salary' => 700,
                'salaryTenure' => 'Weekly',
            ],
        ]);

        FleetDriver::query()->create([
            'code' => 'DVR-HOURLY-001',
            'name' => 'Hourly Driver',
            'status' => 'Active',
            'payload' => [
                'driverId' => 'DVR-HOURLY-001',
                'fullName' => 'Hourly Driver',
                'salary' => 100,
                'salaryTenure' => 'Hourly',
            ],
        ]);

        FleetEmployee::query()->create([
            'code' => 'EMP-DAILY-001',
            'name' => 'Daily Employee',
            'status' => 'Active',
            'payload' => [
                'employeeId' => 'EMP-DAILY-001',
                'fullName' => 'Daily Employee',
                'salary' => 100,
                'salaryTenure' => 'Daily',
            ],
        ]);

        $this
            ->withoutMiddleware()
            ->postJson(route('fleet.dues.generate-payroll'), ['month' => '2026-06'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        FleetDue::query()
            ->where('code', 'PAY-DRV-DVR-WEEKLY-001-2026-06')
            ->update(['status' => 'Paid']);

        $this
            ->withoutMiddleware()
            ->postJson(route('fleet.dues.generate-payroll'), ['month' => '2026-06'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, FleetDue::query()->where('code', 'PAY-DRV-DVR-WEEKLY-001-2026-06')->count());
        $this->assertSame(1, FleetDue::query()->where('code', 'PAY-EMP-EMP-DAILY-001-2026-06')->count());
        $this->assertSame(0, FleetDue::query()->where('code', 'PAY-DRV-DVR-HOURLY-001-2026-06')->count());
        $this->assertSame(2, FleetDue::query()->where('source_type', 'Payroll')->count());
        $this->assertSame('Paid', FleetDue::query()->where('code', 'PAY-DRV-DVR-WEEKLY-001-2026-06')->value('status'));

        $this->assertDatabaseHas('fleet_dues', [
            'code' => 'PAY-DRV-DVR-WEEKLY-001-2026-06',
            'amount' => 3000.00,
        ]);
        $this->assertDatabaseHas('fleet_dues', [
            'code' => 'PAY-EMP-EMP-DAILY-001-2026-06',
            'amount' => 3000.00,
        ]);
    }
}
