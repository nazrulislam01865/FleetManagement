# Fuel Recharge Camera Deployment

The live camera preview uses `navigator.mediaDevices.getUserMedia()`. Browsers expose this API only in a secure context, normally HTTPS (localhost is the development exception).

## Required production settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
```

After editing `.env`:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

## Nginx / proxy checks

- Redirect all HTTP requests to HTTPS.
- Do not send a `Permissions-Policy` header that contains `camera=()`.
- If a camera policy is set, use `Permissions-Policy: camera=(self), geolocation=(self)`.
- If the app is embedded in an iframe, the parent iframe must include `allow="camera; geolocation"`.

## Browser permission checks

Open the site settings in Chrome, Edge, Samsung Internet, or Safari and allow Camera and Location for the deployed domain. If permission was previously denied, the browser may not prompt again until it is changed in site settings.

## Included fallback

When HTTPS live preview is unavailable, the Add Fuel page now invokes a mobile file input with `accept="image/*"` and `capture="environment"`. This normally opens the rear device camera. Browser behavior varies, and some browsers may also allow choosing an existing image. Strict live-only capture therefore still requires HTTPS and `getUserMedia()`.
