# Company Favicon and Logo Permission Update

## Implemented

- Added a **Company Favicon** upload section to Fleet Settings.
- Favicon files are stored on the public disk under `favicon/`.
- Supported favicon formats: ICO, PNG, JPG, JPEG and WebP.
- Maximum favicon size: 1 MB.
- Added current favicon preview in Settings.
- Added cache-versioned public favicon route: `brand.favicon`.
- Added favicon links to both application layouts:
  - authenticated Fleet pages
  - login and password-reset pages
- The uploaded favicon is used in browser tabs, bookmarks and supported home-screen shortcuts.
- Existing logo upload remains available and its permission failure is fixed.

## Access control

- Settings page is now restricted by `EnsureFleetSuperAdmin`.
- Settings menu is visible only to active Super Admin accounts.
- Branding temporary uploads use the `settings` upload scope.
- The `settings` upload scope accepts only active Super Admin accounts.
- `settings.manage` is hidden from the assignable Role Matrix.
- A migration revokes existing Settings permission from non-Super-Admin roles/users while retaining it for Super Admin.

## Compatibility

- Existing company logo functionality is preserved.
- Existing routes, forms, modules and prior searchable-dropdown updates are preserved.
- No original application source file or named route was removed.
- Only generated local route/config/event cache files were excluded so deployment cannot reuse stale machine-specific caches.

## New routes

- `GET /brand/favicon` — `brand.favicon`
- `POST /fleet/settings/favicon` — `fleet.settings.update-favicon`

## New migration

- `2026_06_16_000003_restrict_brand_settings_to_super_admin.php`

## Deployment

Run after copying the updated project:

```bash
php artisan optimize:clear
php artisan migrate
php artisan optimize
```

Cloud deployment:

```bash
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize
sudo systemctl reload php8.4-fpm
```

After uploading a favicon, hard refresh the browser because browsers cache favicons aggressively.

## Validation

- 146 PHP files passed syntax validation.
- 60 Blade templates compiled successfully.
- JavaScript source and deployed core bundle passed syntax validation.
- 152 routes registered.
- All 148 original named routes remain.
- Two routes were added; none were removed.
- No original source file is missing.
- PHPUnit execution was unavailable in the verification container because PHP DOM, mbstring, XML and XMLWriter extensions are missing.
