# FleetMan Dashboard + Login Update

This update adds a database-backed dashboard and a real Laravel login flow.

## New routes

- `/login`
- `/fleet/dashboard`

The FleetMan module is now protected by Laravel `auth` middleware.

## Default seeded admin

Run the seeder, then log in with:

- Email: `admin@fleetman.local`
- Password: `password`

## Commands

```bash
composer install
php artisan optimize:clear
php artisan migrate --seed
php artisan serve
```

Open:

```text
http://127.0.0.1:8000/login
```

## Added files

- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Fleet/DashboardController.php`
- `resources/views/auth/login.blade.php`
- `resources/views/fleetman/dashboard.blade.php`

## Updated files

- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`
- `config/fleetman.php`
- `resources/views/components/fleetman/sidebar.blade.php`
- `resources/views/components/fleetman/topbar.blade.php`
- `app/Http/Controllers/Fleet/FleetBaseController.php`
- `public/css/fleetman.css`

## Notes

- Dashboard numbers are read from the MySQL-backed FleetMan tables.
- Login uses the existing Laravel `users` table.
- UI for existing pages was not changed.
