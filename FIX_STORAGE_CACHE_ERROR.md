# Fix: Please provide a valid cache path

This error happens when Laravel cannot find or write to its runtime cache folders, especially:

- `storage/framework/views`
- `storage/framework/cache/data`
- `storage/framework/sessions`
- `bootstrap/cache`

These folders are now included with `.gitignore` placeholders so they remain present after ZIP extraction.

After unzipping on macOS/XAMPP, run:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan migrate --seed
php artisan serve
```

If MySQL database does not exist yet:

```sql
CREATE DATABASE fleet_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then confirm `.env` contains:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fleet_management
DB_USERNAME=root
DB_PASSWORD=
```
