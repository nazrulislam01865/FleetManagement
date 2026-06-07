# Vehicle Page Update

## Included changes

- Vehicle IDs are generated automatically in the format `VHLyymmNNN` in the form and again on the server if the browser value is missing.
- Registration numbers must use `ABC-AB-12-1234`.
- Engine numbers must contain exactly 17 letters or digits.
- Required fields use inline red validation, scroll to the first error, and receive focus.
- Add Vehicle shows active vendors classified through **Vendor Type Master** as vehicle/car/transport/rental vendors.
- Rental Type supports **With Driver** and **Without Driver**, requires the assigned driver in both cases, shows vendor-driver payment fields only for With Driver, and calculates Total Rental Amount automatically.
- Fuel rows hide fuel types already selected in another row.
- Vehicle documents are filtered through **Document Type Master** and duplicate document names are hidden from additional rows.
- Vehicle images show a preview, file name, file size, validation status, and upload progress. Images must be 30–50 KB.
- Vehicle documents show a preview/icon, file name, file size, validation status, and upload progress. Documents must be 4 MB or smaller.

## Deployment

Run the migration and clear Laravel caches after uploading the updated project:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then configure existing vendor records in **Vendor Type Master / Add Vendor or Party**, and classify document names in **Document Type Master / Document Name Master**.
