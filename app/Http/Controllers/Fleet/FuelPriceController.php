<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetFuelPrice;

class FuelPriceController extends FleetBaseController
{
    protected string $activeMenu = 'fuel-prices';
    protected string $view = 'fleetman.fuel-prices';
    protected string $page = 'fuel-prices';
    protected string $resource = 'fuel_prices';
    protected string $idKey = 'fuelPriceId';
    protected string $nameKey = 'name';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetFuelPrice::class;
}
