# FleetMan Database Update

This project has been converted from prototype/config/localStorage pages to a MySQL-backed Laravel module.

## Controller structure

Fleet pages now use separate controllers:

- `app/Http/Controllers/Fleet/VehicleController.php`
- `app/Http/Controllers/Fleet/FuelPriceController.php`
- `app/Http/Controllers/Fleet/FuelRechargeController.php`
- `app/Http/Controllers/Fleet/VendorPartyController.php`
- `app/Http/Controllers/Fleet/TripController.php`
- `app/Http/Controllers/Fleet/DriverController.php`
- `app/Http/Controllers/Fleet/DriverAttendanceController.php`
- `app/Http/Controllers/Fleet/ClientController.php`

Shared page data, lookup loading, records loading, and sync helpers are in:

- `app/Http/Controllers/Fleet/FleetBaseController.php`

## Database tables

The migration `database/migrations/2026_06_02_000001_create_fleet_database_tables.php` creates:

- `fleet_lookups` for all dropdown/master data
- `fleet_contracts`
- `fleet_vehicles`
- `fleet_fuel_prices`
- `fleet_fuel_recharges`
- `fleet_vendor_parties`
- `fleet_trips`
- `fleet_drivers`
- `fleet_clients`
- `fleet_driver_attendances`

The current tables use a `payload` JSON column so the UI can remain unchanged while the prototype is converted to database persistence. Later, each JSON payload can be normalized into child tables such as vehicle fuels, vehicle documents, client contacts, driver documents, etc.

## Seeder

Run this once after configuring MySQL:

```bash
php artisan migrate --seed
```

This seeds dropdown data and starter records from `config/fleetman.php` into the database.

## MySQL setup

Create the database first:

```sql
CREATE DATABASE fleet_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then check `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fleet_management
DB_USERNAME=root
DB_PASSWORD=
```

For XAMPP, `root` with empty password is common locally. Update it if your MySQL password is different.

## Run

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open:

- `/fleet/vehicles`
- `/fleet/fuel-prices`
- `/fleet/fuel-recharge`
- `/fleet/vendors`
- `/fleet/trips`
- `/fleet/drivers`
- `/fleet/driver-attendance`
- `/fleet/clients`

## How saving works now

The UI design and frontend interaction remain the same. When users add, edit, or delete rows on these screens, JavaScript syncs the current list to Laravel API routes such as:

- `POST /fleet/vehicles/sync`
- `POST /fleet/drivers/sync`
- `POST /fleet/clients/sync`
- `POST /fleet/trips/sync`

These routes save data into MySQL instead of browser localStorage.
