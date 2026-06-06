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

    public function sync(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $response = parent::sync($request);

        $rows = is_array($request->input('rows')) 
            ? $request->input('rows') 
            : json_decode((string) $request->input('rows', '[]'), true);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (($row[$this->statusKey] ?? '') === 'Completed') {
                    $amount = (float) ($row['totalCost'] ?? 0);
                    $code = $row[$this->idKey] ?? '';
                    if ($amount > 0 && $code) {
                        \App\Models\Fleet\FleetDue::updateOrCreate(
                            ['code' => 'DUE-TRP-' . $code],
                            [
                                'type' => 'Trip Expense',
                                'party_type' => 'Driver',
                                'party_id' => $row['driverId'] ?? null,
                                'source_type' => 'Trip',
                                'source_id' => $code,
                                'amount' => $amount,
                                'status' => 'Pending',
                                'due_date' => $row['endDate'] ?? $row['startDate'] ?? null,
                                'payload' => [
                                    'vehicleId' => $row['vehicleId'] ?? null,
                                    'purpose' => $row['purpose'] ?? null,
                                ]
                            ]
                        );
                    }
                }
            }
        }

        return $response;
    }
}
