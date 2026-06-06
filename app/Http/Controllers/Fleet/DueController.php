<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDue;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DueController extends FleetBaseController
{
    protected string $activeMenu = 'dues';
    protected string $view = 'fleetman.dues';
    protected string $page = 'dues';
    protected string $resource = 'dues';
    protected string $idKey = 'code';
    protected string $nameKey = 'type';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetDue::class;

    /**
     * Override sync to handle specific fields for Dues if needed, 
     * or we just use FleetBaseController's sync, but we need to map payload to columns.
     */
    public function sync(Request $request): JsonResponse
    {
        $rows = is_array($request->input('rows')) 
            ? $request->input('rows') 
            : json_decode((string) $request->input('rows', '[]'), true);

        if (!is_array($rows)) {
            return response()->json(['error' => 'Invalid rows payload'], 400);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $code = (string) ($row['code'] ?? '');
                if ($code === '') {
                    continue;
                }

                FleetDue::updateOrCreate(
                    ['code' => $code],
                    [
                        'type' => $row['type'] ?? 'General',
                        'party_type' => $row['party_type'] ?? null,
                        'party_id' => $row['party_id'] ?? null,
                        'source_type' => $row['source_type'] ?? null,
                        'source_id' => $row['source_id'] ?? null,
                        'amount' => $row['amount'] ?? 0,
                        'status' => $row['status'] ?? 'Pending',
                        'due_date' => $row['due_date'] ?? null,
                        'payload' => $row,
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'rows' => FleetDue::all(),
        ]);
    }

    /**
     * Generate Payroll Dues for Drivers and Employees for a specific month.
     */
    public function generatePayroll(Request $request): JsonResponse
    {
        $month = $request->input('month', date('Y-m')); // e.g. 2026-06

        DB::transaction(function () use ($month) {
            // Pre-fetch all attendances to calculate hourly wages
            $attendances = \App\Models\Fleet\FleetDriverAttendance::all()->filter(function ($att) use ($month) {
                return str_starts_with($att->payload['date'] ?? '', $month);
            });

            // 1. Driver Salaries
            $drivers = FleetDriver::where('status', 'Active')->get();
            foreach ($drivers as $driver) {
                $payload = $driver->payload ?? [];
                $salary = (float)($payload['salary'] ?? 0);
                $tenure = $payload['salaryTenure'] ?? 'Monthly';

                if ($salary <= 0) continue;

                $dueCode = "PAY-DRV-{$driver->code}-{$month}";
                
                $amount = $salary;

                if ($tenure === 'Hourly') {
                    // Hourly drivers are generated per attendance log in DriverAttendanceController@sync
                    continue;
                }

                FleetDue::updateOrCreate(
                    ['code' => $dueCode],
                    [
                        'type' => 'Driver Salary',
                        'party_type' => 'Driver',
                        'party_id' => $driver->code,
                        'source_type' => 'Payroll',
                        'source_id' => $month,
                        'amount' => $amount,
                        'status' => 'Pending',
                        'due_date' => $month . '-05', // Assume due on 5th of next month
                        'payload' => [
                            'month' => $month,
                            'tenure' => $tenure,
                            'driverName' => $payload['fullName'] ?? $driver->name,
                        ]
                    ]
                );
            }

            // 2. Employee Salaries
            $employees = FleetEmployee::where('status', 'Active')->get();
            foreach ($employees as $employee) {
                $payload = $employee->payload ?? [];
                $salary = (float)($payload['salary'] ?? 0);
                $tenure = $payload['salaryTenure'] ?? 'Monthly';
                
                if ($salary <= 0) continue;

                // Without an Employee Attendance module, we assume a standard 200 hours/month for Hourly employees
                $amount = $tenure === 'Hourly' ? ($salary * 200) : $salary;

                $dueCode = "PAY-EMP-{$employee->code}-{$month}";

                FleetDue::updateOrCreate(
                    ['code' => $dueCode],
                    [
                        'type' => 'Employee Salary',
                        'party_type' => 'Employee',
                        'party_id' => $employee->code,
                        'source_type' => 'Payroll',
                        'source_id' => $month,
                        'amount' => $amount,
                        'status' => 'Pending',
                        'due_date' => $month . '-05',
                        'payload' => [
                            'month' => $month,
                            'tenure' => $tenure,
                            'employeeName' => $payload['fullName'] ?? $employee->name,
                        ]
                    ]
                );
            }

            // 3. Vehicle Monthly Rents
            $vehicles = \App\Models\Fleet\FleetVehicle::where('status', 'Active')->get();
            foreach ($vehicles as $vehicle) {
                $payload = $vehicle->payload ?? [];
                $rent = (float)($payload['rent'] ?? 0);
                $vendor = $payload['vendor'] ?? null;
                
                if ($rent <= 0 || !$vendor) continue;

                $dueCode = "RENT-VHL-{$vehicle->code}-{$month}";

                FleetDue::updateOrCreate(
                    ['code' => $dueCode],
                    [
                        'type' => 'Vehicle Rent',
                        'party_type' => 'Vendor',
                        'party_id' => $vendor,
                        'source_type' => 'VehicleRent',
                        'source_id' => $month,
                        'amount' => $rent,
                        'status' => 'Pending',
                        'due_date' => $month . '-01',
                        'payload' => [
                            'month' => $month,
                            'vehicleId' => $vehicle->code,
                            'regNo' => $payload['regNo'] ?? '',
                        ]
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Payroll dues generated successfully for ' . $month,
            'rows' => FleetDue::all(),
        ]);
    }

    public function records(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'rows' => FleetDue::all(),
        ]);
    }
}
