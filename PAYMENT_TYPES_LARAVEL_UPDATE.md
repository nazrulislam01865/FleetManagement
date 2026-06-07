# Payment Types Laravel Update

Payment Types are now managed directly by Laravel and stored in `fleet_payment_types`.

## What changed

- Added dedicated Laravel create, update, and delete routes.
- Replaced the JavaScript-rendered Payment Types form/table with a server-rendered Blade form and table.
- Removed Payment Types from the bulk Master Data JavaScript sync so another master page cannot overwrite payment methods with stale browser data.
- Removed Payment Types from `FleetDatabaseSeeder` so running `php artisan db:seed` does not restore deleted defaults or overwrite edited values.
- The existing payment-type migration still inserts the initial default methods one time when the table is first created.
- Add Trip continues loading active payment types from the database.

## Deployment

```bash
php artisan migrate --force
php artisan optimize:clear
```
