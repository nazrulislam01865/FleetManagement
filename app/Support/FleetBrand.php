<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class FleetBrand
{
    private const LOGO_EXTENSIONS = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

    private const FAVICON_EXTENSIONS = ['ico', 'png', 'jpg', 'jpeg', 'webp'];

    /**
     * Return the newest supported company logo stored on the public disk.
     */
    public static function logoPath(): ?string
    {
        return self::newestAssetPath('logo', self::LOGO_EXTENSIONS);
    }

    /**
     * Return the newest supported company favicon stored on the public disk.
     */
    public static function faviconPath(): ?string
    {
        return self::newestAssetPath('favicon', self::FAVICON_EXTENSIONS);
    }

    /**
     * Generate a request-aware logo URL instead of relying on public/storage.
     */
    public static function logoUrl(): ?string
    {
        return self::versionedRouteUrl(self::logoPath(), 'brand.logo');
    }

    /**
     * Generate a cache-safe favicon URL for browser tabs and bookmarks.
     */
    public static function faviconUrl(): ?string
    {
        return self::versionedRouteUrl(self::faviconPath(), 'brand.favicon');
    }

    /**
     * @param array<int, string> $allowedExtensions
     */
    private static function newestAssetPath(string $directory, array $allowedExtensions): ?string
    {
        $disk = Storage::disk('public');

        try {
            $files = array_values(array_filter(
                $disk->files($directory),
                static fn (string $path): bool => in_array(
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                    $allowedExtensions,
                    true
                )
            ));

            if ($files === []) {
                return null;
            }

            usort($files, static function (string $left, string $right) use ($disk): int {
                return $disk->lastModified($right) <=> $disk->lastModified($left);
            });

            return $files[0] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function versionedRouteUrl(?string $path, string $routeName): ?string
    {
        if ($path === null) {
            return null;
        }

        try {
            $disk = Storage::disk('public');
            $version = hash_file('sha256', $disk->path($path))
                ?: (string) $disk->lastModified($path);
        } catch (Throwable) {
            $version = (string) time();
        }

        return route($routeName, ['v' => $version], false);
    }
}
