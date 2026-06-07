# FleetMan JavaScript modules

The previous `public/js/fleetman.js` application bundle has been divided into stable shared utilities and page-specific modules.

## Shared files

- `public/js/fleetman-core.js` — record detail modal, duplicate document-dropdown helper, and temporary upload/progress/preview manager.
- `public/js/fleetman-navigation.js` — sidebar, submenu, mobile menu, and navigation state.
- `public/js/fleetman-reports.js` — report pages.
- `public/js/fleetman.js` — small compatibility loader for older cached layouts. New pages do not use this file directly.

## Page modules

Page logic is stored in `public/js/fleetman-pages/`:

- `vehicles.js`
- `fuel-prices.js`
- `fuel-recharge.js`
- `vendors.js`
- `trips.js`
- `drivers.js`
- `clients.js`
- `employees.js`
- `driver-attendance.js`
- `master-data.js`
- `contracts.js`

`resources/views/layouts/fleetman.blade.php` automatically loads only `fleetman-core.js` and the JavaScript file for the current page. Each file uses its own `filemtime` cache version, so changing one page does not invalidate or modify every other page script.

## Future updates

When changing a page, edit only its matching file. Shared upload or document-dropdown behavior belongs in `fleetman-core.js`. Sidebar behavior belongs in `fleetman-navigation.js`.

## Database seeding

`FleetDatabaseSeeder` now seeds only required master/lookup values. It no longer creates demo vehicles, drivers, vendors, clients, trips, fuel records, attendance logs, employees, or contracts. Shipped SQLite databases and temporary patch files were removed from the delivery archive.
