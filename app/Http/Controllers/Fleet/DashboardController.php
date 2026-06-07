<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelPrice;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVendorParty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

class DashboardController extends FleetBaseController
{
    protected string $activeMenu = 'dashboard';
    protected string $view = 'fleetman.dashboard';
    protected string $page = 'dashboard';

    public function index(): View
    {
        return view($this->view, $this->shared($this->activeMenu, [
            'page' => $this->page,
            'dashboard' => $this->dashboardData(),
        ]));
    }

    private function dashboardData(): array
    {
        $vehicles = $this->rows(FleetVehicle::class);
        $drivers = $this->rows(FleetDriver::class);
        $trips = $this->rows(FleetTrip::class);
        $fuelPrices = $this->rows(FleetFuelPrice::class);
        $clients = $this->rows(FleetClient::class);
        $vendors = $this->rows(FleetVendorParty::class);
        $attendance = $this->rows(FleetDriverAttendance::class);
        $employees = $this->rows(FleetEmployee::class);

        $activeVehicleCount = collect($vehicles)->filter(function (array $row) {
            return in_array($row['status'] ?? 'Active', ['Active', 'Needs document review'], true);
        })->count();

        $totalTripCost = collect($trips)->sum(fn (array $row) => (float) ($row['totalCost'] ?? 0));
        $totalTripPaid = collect($trips)->sum(fn (array $row) => (float) ($row['paidAmount'] ?? 0));
        $totalTripBalance = collect($trips)->sum(fn (array $row) => max(0, (float) ($row['balanceDue'] ?? ((float) ($row['totalCost'] ?? 0) - (float) ($row['paidAmount'] ?? 0)))));
        $paidTrips = collect($trips)->filter(function (array $row): bool {
            $total = (float) ($row['totalCost'] ?? 0);
            $balance = max(0, (float) ($row['balanceDue'] ?? ($total - (float) ($row['paidAmount'] ?? 0))));

            return $total > 0 && $balance <= 0.009;
        })->count();
        $totalPayroll = collect($drivers)->sum(fn (array $row) => (float) ($row['salary'] ?? 0))
            + collect($employees)->sum(fn (array $row) => (float) ($row['salary'] ?? 0));
        $latestFuelPrice = collect($fuelPrices)->sortByDesc('effectiveDate')->first();
        $expiringDrivers = collect($drivers)->filter(function (array $row) {
            $date = $row['licenseValidity'] ?? null;
            if (! $date) {
                return false;
            }

            try {
                return now()->diffInDays(Carbon::parse($date), false) <= 180;
            } catch (\Throwable) {
                return false;
            }
        })->count();

        $totalAttendanceKm = collect($attendance)->sum(fn (array $row) => (float) ($row['distance'] ?? 0));

        return [
            'stats' => [
                ['label' => 'Total Vehicles', 'value' => 0, 'helper' => '0 active / usable', 'icon' => '🚗'],
                ['label' => 'Drivers', 'value' => 0, 'helper' => '0 license warning', 'icon' => '🧑‍✈️'],
                ['label' => 'Trips', 'value' => 0, 'helper' => '0 fully paid · ৳0.00 balance', 'icon' => '🧭'],
                ['label' => 'Clients', 'value' => 0, 'helper' => '0 vendors / parties', 'icon' => '🏢'],
            ],
            'finance' => [
                'trip_cost' => 0,
                'trip_paid' => 0,
                'trip_balance' => 0,
                'payroll' => 0,
                'fuel_rate' => ['fuelType' => 'Fuel', 'price' => 0],
                'attendance_km' => 0,
            ],
            'recent' => [
                'vehicles' => array_slice($vehicles, 0, 5),
                'trips' => array_slice($trips, 0, 5),
                'drivers' => array_slice($drivers, 0, 5),
                'clients' => array_slice($clients, 0, 5),
            ],
            'warnings' => [
                ['title' => 'Driver license review', 'value' => 0, 'description' => 'Drivers with license validity within 180 days.'],
                ['title' => 'Trip payment balance', 'value' => '৳0.00', 'description' => 'Remaining client payments across saved trips.'],
                ['title' => 'Total attendance distance', 'value' => '0 km', 'description' => 'Distance from driver attendance logs.'],
            ],
        ];
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function rows(string $modelClass): array
    {
        return $modelClass::query()
            ->latest('id')
            ->get()
            ->map(fn (Model $row) => $row->payload ?? [])
            ->values()
            ->all();
    }
}
