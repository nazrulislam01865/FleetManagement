<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetTrip;

class TripController extends FleetBaseController
{
    protected string $activeMenu = 'trips';
    protected string $view = 'fleetman.trips';
    protected string $page = 'trips';
    protected string $resource = 'trips';
    protected string $idKey = 'tripId';
    protected string $nameKey = 'purpose';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetTrip::class;
}
