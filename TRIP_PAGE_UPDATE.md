# Trip Page Update

The Trip workflow has been rebuilt according to the requested requirements.

## Form changes

- Removed End Date.
- Removed Trip Around.
- Removed Trip Period.
- Removed Trip Status and Save as Draft.
- Odo Start is optional.
- Odo End is optional and cannot be lower than Odo Start when both are entered.
- Vehicle and Driver now use searchable input fields with datalist suggestions from saved records.
- Replaced individual fuel, food, toll, accommodation, and other cost fields with one required Total Cost field.

## Multiple payments

- A trip can contain zero, one, or many payment entries.
- Supported methods: Cash, Bank Transfer, Card, bKash, Nagad, Rocket, Cheque, and Other.
- Each payment can include an optional transaction/reference value.
- Paid Amount and Remaining Payment Required recalculate automatically.
- Total payment cannot exceed Total Cost.
- An unpaid or partially paid trip creates/updates a `Trip Payment Balance` record in `fleet_dues`.
- Paying the full amount removes the outstanding Trip due automatically.

## Validation

Required fields are validated in both JavaScript and Laravel:

- Trip ID
- Start Date
- Vehicle selected from saved suggestions
- Driver selected from saved suggestions
- Total Cost greater than zero
- Trip Details
- Payment method and positive payment amount for every added payment row

Missing or invalid fields are highlighted with a red border, an inline message is shown, and the page scrolls to the first error.

## Deployment

No database migration is required because trip payments are stored inside the existing trip JSON payload and outstanding balances use the existing `fleet_dues` table.

After uploading the project, run:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
