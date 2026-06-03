<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Fleet\ClientController;
use App\Http\Controllers\Fleet\ContractController;
use App\Http\Controllers\Fleet\DashboardController;
use App\Http\Controllers\Fleet\DriverAttendanceController;
use App\Http\Controllers\Fleet\DriverController;
use App\Http\Controllers\Fleet\EmployeeController;
use App\Http\Controllers\Fleet\FuelPriceController;
use App\Http\Controllers\Fleet\FuelRechargeController;
use App\Http\Controllers\Fleet\MasterDataController;
use App\Http\Controllers\Fleet\ReportController;
use App\Http\Controllers\Fleet\TripController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\Fleet\VendorPartyController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::redirect('/', '/fleet/dashboard');

Route::prefix('fleet')->name('fleet.')->middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles');
    Route::post('/vehicles/sync', [VehicleController::class, 'sync'])->name('vehicles.sync');

    Route::get('/fuel-prices', [FuelPriceController::class, 'index'])->name('fuel-prices');
    Route::post('/fuel-prices/sync', [FuelPriceController::class, 'sync'])->name('fuel-prices.sync');

    Route::get('/fuel-recharge', [FuelRechargeController::class, 'index'])->name('fuel-recharge');
    Route::post('/fuel-recharge/sync', [FuelRechargeController::class, 'sync'])->name('fuel-recharge.sync');

    Route::get('/vendors', [VendorPartyController::class, 'index'])->name('vendors');
    Route::post('/vendors/sync', [VendorPartyController::class, 'sync'])->name('vendors.sync');
    Route::post('/vendors/documents/upload', [VendorPartyController::class, 'uploadDocument'])->name('vendors.documents.upload');

    Route::get('/trips', [TripController::class, 'index'])->name('trips');
    Route::post('/trips/sync', [TripController::class, 'sync'])->name('trips.sync');

    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers');
    Route::post('/drivers/sync', [DriverController::class, 'sync'])->name('drivers.sync');

    Route::get('/driver-attendance', [DriverAttendanceController::class, 'index'])->name('driver-attendance');
    Route::post('/driver-attendance/sync', [DriverAttendanceController::class, 'sync'])->name('driver-attendance.sync');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
    Route::post('/employees/sync', [EmployeeController::class, 'sync'])->name('employees.sync');

    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
    Route::post('/contracts/sync', [ContractController::class, 'sync'])->name('contracts.sync');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients');
    Route::post('/clients/sync', [ClientController::class, 'sync'])->name('clients.sync');

    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data');
    Route::get('/master-data/vehicle-categories', [MasterDataController::class, 'vehicleCategories'])->name('master-data.vehicle-categories');
    Route::get('/master-data/vehicle-sub-categories', [MasterDataController::class, 'vehicleSubCategories'])->name('master-data.vehicle-sub-categories');
    Route::get('/master-data/party-types', [MasterDataController::class, 'partyTypes'])->name('master-data.party-types');
    Route::get('/master-data/document-names', [MasterDataController::class, 'documentNames'])->name('master-data.document-names');
    Route::get('/master-data/licence-types', [MasterDataController::class, 'licenceTypes'])->name('master-data.licence-types');
    Route::get('/master-data/client-types', [MasterDataController::class, 'clientTypes'])->name('master-data.client-types');
    Route::get('/master-data/contact-methods', [MasterDataController::class, 'contactMethods'])->name('master-data.contact-methods');
    Route::get('/master-data/fuel-types', [MasterDataController::class, 'fuelTypes'])->name('master-data.fuel-types');
    Route::get('/master-data/fuel-units', [MasterDataController::class, 'fuelUnits'])->name('master-data.fuel-units');
    Route::post('/master-data/sync', [MasterDataController::class, 'sync'])->name('master-data.sync');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/daily-driver-fuel', [ReportController::class, 'dailyDriverFuel'])->name('reports.daily-driver-fuel');
    Route::get('/reports/weekly-driver-fuel', [ReportController::class, 'weeklyDriverFuel'])->name('reports.weekly-driver-fuel');
    Route::get('/reports/monthly-driver-fuel', [ReportController::class, 'monthlyDriverFuel'])->name('reports.monthly-driver-fuel');
});
