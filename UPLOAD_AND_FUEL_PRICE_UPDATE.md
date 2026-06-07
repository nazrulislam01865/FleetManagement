# Upload and Fuel Price Update

## File upload workflow

All FleetMan image and document fields now upload the selected file immediately to private temporary storage. The progress bar reports the upload state, and the displayed filename can be opened in a new tab for verification.

The temporary upload is not attached permanently to a record until the related Save, Submit, or Update button succeeds. Temporary uploads older than 24 hours are removed automatically when the same user starts another upload.

Permanent files are opened through the authenticated `/fleet/files/...` route. This avoids broken images when the public `storage` symlink or application URL is not configured correctly.

### Limits

- Vehicle image: JPG, JPEG, PNG, or WebP; maximum 100 KB.
- Vehicle document: supported image or PDF; maximum 4 MB.
- Other module limits remain those shown beside their upload fields.

## Fuel Price validation

Fuel Price ID, Fuel Type, Name, Price per Unit, Unit, Effective Date, Reference, Status, and Remarks are required. Invalid fields receive a red border and inline message; the page scrolls to and focuses the first error. The backend applies the same required, numeric, date, and length checks.

## Deployment

No database migration is required.

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure these directories are writable by the web server:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```
