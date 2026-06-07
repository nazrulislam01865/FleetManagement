# Fuel Station Filtering and Unit Update

## What changed

- The Add Fuel station dropdown now shows only stations configured for the selected vehicle fuel type.
- Petrol/Octane (including the `Octen` spelling), Diesel, and other liquid fuels are entered in **liters** and calculated using the active per-liter rate.
- CNG, Gas/Natural Gas, and LPG are entered as the **total purchase amount in Taka**. They are not included in KM/L mileage calculations.
- `Gas` and `Natural Gas` are treated as aliases of CNG for station filtering.
- The server validates the station/fuel match, so a mismatched station cannot be submitted by bypassing the browser.

## Required station setup

Existing stations must be configured once:

1. Open **Vendor / Party**.
2. Add or edit the fuel station.
3. Make sure its party type/name identifies it as a fuel station.
4. Select every applicable option under **Fuel Types Sold**.
5. Save the vendor/party.

A station configured for multiple fuels will appear in each matching dropdown. An old generic station with no configured fuel type will remain hidden until it is updated; this prevents incorrect station suggestions.

## Deployment

Run these commands from the Laravel project directory:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Make sure the web server can write to `storage` and `bootstrap/cache`.
