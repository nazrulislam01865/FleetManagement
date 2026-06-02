<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class FleetManagementController extends Controller
{
    private function shared(string $activeMenu, array $pageData = []): array
    {
        return [
            'brand' => config('fleetman.brand'),
            'account' => config('fleetman.account'),
            'menuGroups' => config('fleetman.menu'),
            'activeMenu' => $activeMenu,
            'fleetman' => array_merge([
                'options' => config('fleetman.options'),
                'contracts' => config('fleetman.contracts'),
                'photoRequirements' => config('fleetman.photo_requirements'),
                'samples' => config('fleetman.samples'),
                'tripMasters' => config('fleetman.trip_masters'),
            ], $pageData),
        ];
    }

    public function vehicles(): View
    {
        return view('fleetman.vehicles', $this->shared('vehicles', [
            'page' => 'vehicles',
        ]));
    }

    public function fuelPrices(): View
    {
        return view('fleetman.fuel-prices', $this->shared('fuel-prices', [
            'page' => 'fuel-prices',
        ]));
    }

    public function fuelRecharge(): View
    {
        return view('fleetman.fuel-recharge', $this->shared('fuel-recharge', [
            'page' => 'fuel-recharge',
        ]));
    }

    public function vendors(): View
    {
        return view('fleetman.vendor-parties', $this->shared('vendors', [
            'page' => 'vendors',
        ]));
    }

    public function trips(): View
    {
        return view('fleetman.trips', $this->shared('trips', [
            'page' => 'trips',
        ]));
    }
}
