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

    public function sync(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $response = parent::sync($request);

        $rows = is_array($request->input('rows')) 
            ? $request->input('rows') 
            : json_decode((string) $request->input('rows', '[]'), true);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (($row[$this->statusKey] ?? '') === 'Completed') {
                    $driverStr = $row['driver'] ?? '';
                    // Extract driver code (e.g., DVR12345 from "DVR12345 - Kamal")
                    if (preg_match('/^(DVR\d+)/', $driverStr, $m)) {
                        $driverCode = $m[1];
                        $driver = \App\Models\Fleet\FleetDriver::where('code', $driverCode)->first();
                        
                        if ($driver && ($driver->payload['salaryTenure'] ?? '') === 'Hourly') {
                            $salary = (float)($driver->payload['salary'] ?? 0);
                            $driverHours = 0;
                            $hoursStr = $row['hours'] ?? '';
                            
                            if (preg_match('/(\d+)h\s*(\d+)m/', $hoursStr, $matches)) {
                                $driverHours = (int)$matches[1] + ((int)$matches[2] / 60);
                            } else {
                                $start = strtotime($row['startTime'] ?? '');
                                $end = strtotime($row['endTime'] ?? '');
                                if ($start && $end) {
                                    if ($end < $start) $end += 86400; // cross midnight
                                    $driverHours = ($end - $start) / 3600;
                                }
                            }
                            
                            $amount = $salary * $driverHours;
                            $logId = $row[$this->idKey] ?? '';

                            if ($amount > 0 && $logId) {
                                \App\Models\Fleet\FleetDue::updateOrCreate(
                                    ['code' => 'PAY-LOG-' . $logId],
                                    [
                                        'type' => 'Driver Salary',
                                        'party_type' => 'Driver',
                                        'party_id' => $driverCode,
                                        'source_type' => 'Attendance',
                                        'source_id' => $logId,
                                        'amount' => $amount,
                                        'status' => 'Pending',
                                        'due_date' => $row['date'] ?? null,
                                        'payload' => [
                                            'logId' => $logId,
                                            'driverName' => $driverStr,
                                            'hours' => $hoursStr,
                                        ]
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }

        return $response;
    }
}
