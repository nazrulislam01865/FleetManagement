<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BrandAssetController;
use App\Http\Controllers\Fleet\ClientController;
use App\Http\Controllers\Fleet\ContractController;
use App\Http\Controllers\Fleet\DashboardController;
use App\Http\Controllers\Fleet\DriverAttendanceController;
use App\Http\Controllers\Fleet\DriverController;
use App\Http\Controllers\Fleet\EmployeeController;
use App\Http\Controllers\Fleet\FuelPriceController;
use App\Http\Controllers\Fleet\FuelRechargeController;
use App\Http\Controllers\Fleet\FleetFileController;
use App\Http\Controllers\Fleet\TemporaryUploadController;
use App\Http\Controllers\Fleet\MasterDataController;
use App\Http\Controllers\Fleet\ReportController;
use App\Http\Controllers\Fleet\RoleMatrixController;
use App\Http\Controllers\Fleet\TripController;
use App\Http\Controllers\Fleet\UserManagementController;
use App\Http\Controllers\Fleet\SettingsController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\Fleet\VendorPartyController;
use App\Http\Middleware\EnsureFleetPermission;
use Illuminate\Support\Facades\Route;

Route::get('/brand/logo', [BrandAssetController::class, 'logo'])->name('brand.logo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::redirect('/', '/fleet/dashboard');

Route::prefix('fleet')->name('fleet.')->middleware('auth')->group(function () {
    Route::post('/uploads/temp', [TemporaryUploadController::class, 'store'])->name('uploads.store');
    Route::get('/uploads/temp/{token}', [TemporaryUploadController::class, 'preview'])->name('uploads.preview');
    Route::delete('/uploads/temp/{token}', [TemporaryUploadController::class, 'destroy'])->name('uploads.destroy');
    Route::get('/files/{path}', [FleetFileController::class, 'show'])->where('path', '.*')->name('files.show');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':dashboard.view')
        ->name('dashboard');

    Route::get('/vehicles', [VehicleController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':vehicles.view')
        ->name('vehicles');
    Route::post('/vehicles/sync', [VehicleController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':vehicles.manage')
        ->name('vehicles.sync');

    Route::get('/fuel-prices', [FuelPriceController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':fuel_prices.view')
        ->name('fuel-prices');
    Route::post('/fuel-prices/sync', [FuelPriceController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':fuel_prices.manage')
        ->name('fuel-prices.sync');

    Route::get('/fuel-recharge', [FuelRechargeController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':fuel_recharge.view')
        ->name('fuel-recharge');
    Route::post('/fuel-recharge/sync', [FuelRechargeController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':fuel_recharge.manage')
        ->name('fuel-recharge.sync');

    Route::get('/dues', [\App\Http\Controllers\Fleet\DueController::class, 'index'])
        ->name('dues');
    Route::post('/dues/sync', [\App\Http\Controllers\Fleet\DueController::class, 'sync'])
        ->name('dues.sync');
    Route::post('/dues/generate-payroll', [\App\Http\Controllers\Fleet\DueController::class, 'generatePayroll'])
        ->name('dues.generate-payroll');

    Route::get('/vendors', [VendorPartyController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':vendors.view')
        ->name('vendors');
    Route::post('/vendors/sync', [VendorPartyController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':vendors.manage')
        ->name('vendors.sync');
    Route::post('/vendors/documents/upload', [VendorPartyController::class, 'uploadDocument'])
        ->middleware(EnsureFleetPermission::class.':vendors.manage')
        ->name('vendors.documents.upload');

    Route::get('/trips', [TripController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':trips.view')
        ->name('trips');
    Route::post('/trips/sync', [TripController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':trips.manage')
        ->name('trips.sync');

    Route::get('/drivers', [DriverController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':drivers.view')
        ->name('drivers');
    Route::post('/drivers/sync', [DriverController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':drivers.manage')
        ->name('drivers.sync');

    Route::get('/driver-attendance', [DriverAttendanceController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':driver_attendance.view')
        ->name('driver-attendance');
    Route::post('/driver-attendance/sync', [DriverAttendanceController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':driver_attendance.manage')
        ->name('driver-attendance.sync');

    Route::get('/employees', [EmployeeController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':employees.view')
        ->name('employees');
    Route::post('/employees/sync', [EmployeeController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':employees.manage')
        ->name('employees.sync');

    Route::get('/contracts', [ContractController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':contracts.view')
        ->name('contracts');
    Route::post('/contracts/sync', [ContractController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':contracts.manage')
        ->name('contracts.sync');

    Route::get('/clients', [ClientController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':clients.view')
        ->name('clients');
    Route::post('/clients/sync', [ClientController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':clients.manage')
        ->name('clients.sync');

    Route::get('/master-data', [MasterDataController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data');
    Route::get('/master-data/vehicle-categories', [MasterDataController::class, 'vehicleCategories'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.vehicle-categories');
    Route::get('/master-data/vehicle-sub-categories', [MasterDataController::class, 'vehicleSubCategories'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.vehicle-sub-categories');
    Route::get('/master-data/party-types', [MasterDataController::class, 'partyTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.party-types');
    Route::get('/master-data/document-names', [MasterDataController::class, 'documentNames'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.document-names');
    Route::get('/master-data/licence-types', [MasterDataController::class, 'licenceTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.licence-types');
    Route::get('/master-data/driver-contact-types', [MasterDataController::class, 'driverContactTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.driver-contact-types');
    Route::get('/master-data/client-types', [MasterDataController::class, 'clientTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.client-types');
    Route::get('/master-data/contact-methods', [MasterDataController::class, 'contactMethods'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.contact-methods');
    Route::get('/master-data/fuel-types', [MasterDataController::class, 'fuelTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.fuel-types');
    Route::get('/master-data/fuel-units', [MasterDataController::class, 'fuelUnits'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.fuel-units');
    Route::get('/master-data/vendor-contractor-types', [MasterDataController::class, 'vendorContractorTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.vendor-contractor-types');
    Route::post('/master-data/vendor-contractor-types', [MasterDataController::class, 'storeVendorContractorType'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.vendor-contractor-types.store');
    Route::put('/master-data/vendor-contractor-types/{vendorContractorType}', [MasterDataController::class, 'updateVendorContractorType'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.vendor-contractor-types.update');
    Route::delete('/master-data/vendor-contractor-types/{vendorContractorType}', [MasterDataController::class, 'destroyVendorContractorType'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.vendor-contractor-types.destroy');
    Route::get('/master-data/payment-types', [MasterDataController::class, 'paymentTypes'])
        ->middleware(EnsureFleetPermission::class.':master_data.view')
        ->name('master-data.payment-types');
    Route::post('/master-data/payment-types', [MasterDataController::class, 'storePaymentType'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.payment-types.store');
    Route::put('/master-data/payment-types/{paymentType}', [MasterDataController::class, 'updatePaymentType'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.payment-types.update');
    Route::delete('/master-data/payment-types/{paymentType}', [MasterDataController::class, 'destroyPaymentType'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.payment-types.destroy');
    Route::post('/master-data/sync', [MasterDataController::class, 'sync'])
        ->middleware(EnsureFleetPermission::class.':master_data.manage')
        ->name('master-data.sync');


    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':users.view')
        ->name('users');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware(EnsureFleetPermission::class.':users.manage')
        ->name('users.store');

    Route::get('/role-matrix', [RoleMatrixController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':role_matrix.view')
        ->name('role-matrix');
    Route::post('/role-matrix/users', [RoleMatrixController::class, 'storeUser'])
        ->middleware(EnsureFleetPermission::class.':users.manage')
        ->name('role-matrix.users.store');
    Route::post('/role-matrix', [RoleMatrixController::class, 'update'])
        ->middleware(EnsureFleetPermission::class.':role_matrix.manage')
        ->name('role-matrix.update');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':reports.view')
        ->name('reports');
    Route::get('/reports/daily-driver-fuel', [ReportController::class, 'dailyDriverFuel'])
        ->middleware(EnsureFleetPermission::class.':reports.view')
        ->name('reports.daily-driver-fuel');
    Route::get('/reports/weekly-driver-fuel', [ReportController::class, 'weeklyDriverFuel'])
        ->middleware(EnsureFleetPermission::class.':reports.view')
        ->name('reports.weekly-driver-fuel');
    Route::get('/reports/monthly-driver-fuel', [ReportController::class, 'monthlyDriverFuel'])
        ->middleware(EnsureFleetPermission::class.':reports.view')
        ->name('reports.monthly-driver-fuel');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware(EnsureFleetPermission::class.':settings.manage')
        ->name('settings');
    Route::post('/settings/logo', [SettingsController::class, 'updateLogo'])
        ->middleware(EnsureFleetPermission::class.':settings.manage')
        ->name('settings.update-logo');
});
