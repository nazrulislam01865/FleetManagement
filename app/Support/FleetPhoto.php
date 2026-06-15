<?php

namespace App\Support;

final class FleetPhoto
{
    /**
     * Folders that contain display photos only. Uploaded documents are
     * deliberately excluded even when the document itself is an image.
     *
     * @var array<int, string>
     */
    private const PATH_PATTERNS = [
        '#^fleet/profile-pictures/\d+/[^/]+$#',
        '#^fleet/vehicles/[^/]+/images/[^/]+$#',
        '#^fleet/drivers/[^/]+/photo/[^/]+$#',
        '#^fleet/employees/[^/]+/photo/[^/]+$#',
        '#^fleet/vendor-parties/[^/]+/photo/[^/]+$#',
        '#^fleet/clients/[^/]+/photo/[^/]+$#',
        '#^fleet/yards/[^/]+/photo/[^/]+$#',
        '#^fleet/fuel-recharges/[^/]+/photos(?:/[^/]+)+$#',
    ];

    public static function normalizePath(string $path): string
    {
        $path = ltrim(rawurldecode(trim($path)), '/');

        return preg_replace('#^(public/|storage/)#i', '', $path) ?? $path;
    }

    public static function isDisplayPath(string $path): bool
    {
        $normalized = strtolower(self::normalizePath($path));

        if ($normalized === '' || str_contains($normalized, '..') || str_starts_with($normalized, '.')) {
            return false;
        }

        foreach (self::PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function url(string $path, bool $absolute = false): string
    {
        $normalized = self::normalizePath($path);
        $routeName = self::isDisplayPath($normalized)
            ? 'fleet.photos.show'
            : 'fleet.files.show';

        return route($routeName, ['path' => $normalized], $absolute);
    }

    /**
     * Convert a previously stored /fleet/files photo URL to the public photo
     * route. External and document URLs are left unchanged.
     */
    public static function rewriteStoredUrl(string $url, bool $absolute = false): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $urlPath = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        foreach (['/fleet/files/', '/fleet/photos/'] as $prefix) {
            $position = strpos($urlPath, $prefix);
            if ($position === false) {
                continue;
            }

            $storedPath = substr($urlPath, $position + strlen($prefix));
            $storedPath = self::normalizePath($storedPath);

            if (self::isDisplayPath($storedPath)) {
                return self::url($storedPath, $absolute);
            }
        }

        return $url;
    }
}
