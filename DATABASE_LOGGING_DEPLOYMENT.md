# Fleet Management Database Logging

This update adds:

- Driver-specific database error logging with a `DRV-...` reference.
- Global logging for uncaught Laravel `QueryException` errors.
- Slow-query logging without SQL bindings.
- Meaningful driver save messages instead of the generic database-sync message.

## Production `.env`

Keep `APP_DEBUG=false` and add:

```env
DB_LOG_LEVEL=error
DB_LOG_DAYS=30
DB_SLOW_QUERY_LOG=true
DB_SLOW_QUERY_MS=750
DB_SLOW_LOG_DAYS=14
```

## Redeploy commands

Use the project's normal deployment process. After the new code is deployed, run:

```bash
cd /var/www/FleetManagement

mkdir -p storage/logs
touch storage/logs/database.log storage/logs/slow-query.log storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo systemctl reload php8.4-fpm
```

A PHP-FPM reload is graceful and does not require Laravel maintenance mode.

## Read the logs

```bash
cd /var/www/FleetManagement
ls -lah storage/logs/
tail -F storage/logs/database-*.log storage/logs/slow-query-*.log storage/logs/laravel*.log
```

Find the exact driver failure shown on screen:

```bash
grep -Rni "DRV-PASTE-THE-REFERENCE-HERE" storage/logs/
```

The logs intentionally exclude SQL bindings, so driver NID, phone, email, and other submitted values are not recorded.
