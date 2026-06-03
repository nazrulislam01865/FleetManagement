<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetClient;

class ClientController extends FleetBaseController
{
    protected string $activeMenu = 'clients';
    protected string $view = 'fleetman.clients';
    protected string $page = 'clients';
    protected string $resource = 'clients';
    protected string $idKey = 'clientId';
    protected string $nameKey = 'clientName';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetClient::class;
}
