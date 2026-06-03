<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriverAttendance;

class DriverAttendanceController extends FleetBaseController
{
    protected string $activeMenu = 'drive-log';
    protected string $view = 'fleetman.driver-attendance';
    protected string $page = 'driver-attendance';
    protected string $resource = 'driver_attendance';
    protected string $idKey = 'logId';
    protected string $nameKey = 'driver';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetDriverAttendance::class;
}
