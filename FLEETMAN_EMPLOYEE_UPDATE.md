# FleetMan Employee Module Update

Added the Employee module using the same reusable Laravel FleetMan stack.

## New route

- `GET /fleet/employees`
- `POST /fleet/employees/sync`

## New files

- `app/Http/Controllers/Fleet/EmployeeController.php`
- `app/Models/Fleet/FleetEmployee.php`
- `database/migrations/2026_06_02_000002_create_fleet_employees_table.php`
- `resources/views/fleetman/employees.blade.php`

## Updated files

- `routes/web.php`
- `app/Http/Controllers/Fleet/FleetBaseController.php`
- `database/seeders/FleetDatabaseSeeder.php`
- `config/fleetman.php`
- `public/css/fleetman.css`
- `public/js/fleetman.js`

## Database-backed dropdowns

The Employee module uses database lookups seeded into `fleet_lookups` for:

- employee statuses
- salary tenures
- designations

Employee records are stored in `fleet_employees` through the existing JSON payload pattern used by the other FleetMan modules.

## Run

```bash
php artisan migrate --seed
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000/fleet/employees
```
