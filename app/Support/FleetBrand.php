<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class FleetBrand
{
    /**
     * Return the newest supported logo file stored on the public disk.
     */
    public static function logoPath(): ?string
    {
        $disk = Storage::disk('public');

        try {
            $files = array_values(array_filter(
                $disk->files('logo'),
                static fn (string $path): bool => in_array(
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                    ['png', 'jpg', 'jpeg', 'svg', 'webp'],
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

    /**
     * Generate a request-aware URL instead of relying on public/storage.
     */
    public static function logoUrl(): ?string
    {
        $path = self::logoPath();

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

        return route('brand.logo', ['v' => $version], false);
    }
}
