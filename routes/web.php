<?php

use App\Http\Controllers\FleetManagementController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/fleet/vehicles');

Route::prefix('fleet')->name('fleet.')->group(function () {
    Route::get('/vehicles', [FleetManagementController::class, 'vehicles'])->name('vehicles');
    Route::get('/fuel-prices', [FleetManagementController::class, 'fuelPrices'])->name('fuel-prices');
    Route::get('/fuel-recharge', [FleetManagementController::class, 'fuelRecharge'])->name('fuel-recharge');
    Route::get('/vendors', [FleetManagementController::class, 'vendors'])->name('vendors');
    Route::get('/trips', [FleetManagementController::class, 'trips'])->name('trips');
});
