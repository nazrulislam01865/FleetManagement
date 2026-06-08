<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FleetTemporaryUploadService
{
    public const TEMP_ROOT = 'fleet-temp';

    public function store(UploadedFile $file, int $userId): array
    {
        $this->purgeExpiredForUser($userId);

        $token = Str::lower(Str::random(40));
        $safeName = $this->safeFileName($file->getClientOriginalName());
        $directory = self::TEMP_ROOT."/{$userId}/{$token}";
        $path = $file->storeAs($directory, $safeName, 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'file' => 'The selected file could not be uploaded. Check storage permissions and try again.',
            ]);
        }

        $metadata = [
            'tempToken' => $token,
            'temporary' => true,
            'tempPath' => $path,
            'fileName' => basename($path),
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream',
            'sizeBytes' => (int) $file->getSize(),
            'uploadedAt' => now()->toDateTimeString(),
            'userId' => $userId,
        ];

        Storage::disk('local')->put(
            $directory.'/manifest.json',
            json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $metadata;
    }

    public function metadata(string $token, int $userId): array
    {
        $token = $this->validatedToken($token);
        $manifestPath = self::TEMP_ROOT."/{$userId}/{$token}/manifest.json";
        $disk = Storage::disk('local');

        abort_unless($disk->exists($manifestPath), 404);

        $metadata = json_decode((string) $disk->get($manifestPath), true);
        abort_unless(is_array($metadata) && (int) ($metadata['userId'] ?? 0) === $userId, 404);

        $path = (string) ($metadata['tempPath'] ?? '');
        abort_unless($path !== '' && $disk->exists($path), 404);

        return $metadata;
    }

    public function claim(
        array $temporaryFile,
        int $userId,
        string $destination,
        array $allowedExtensions,
        int $maxKilobytes,
        bool $imageOnly = false
    ): array {
        $token = (string) ($temporaryFile['tempToken'] ?? '');
        if ($token === '') {
            throw ValidationException::withMessages([
                'file' => 'The temporary upload token is missing. Please choose the file again.',
            ]);
        }

        $metadata = $this->metadata($token, $userId);
        $extension = strtolower(pathinfo((string) ($metadata['originalName'] ?? $metadata['fileName'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = array_map('strtolower', $allowedExtensions);
        $sizeBytes = (int) ($metadata['sizeBytes'] ?? 0);
        $mimeType = strtolower((string) ($metadata['mimeType'] ?? ''));

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'The selected file type is not allowed.',
            ]);
        }

        if ($sizeBytes <= 0 || $sizeBytes > ($maxKilobytes * 1024)) {
            throw ValidationException::withMessages([
                'file' => "The selected file must not exceed {$maxKilobytes} KB.",
            ]);
        }

        if ($imageOnly && ! str_starts_with($mimeType, 'image/')) {
            throw ValidationException::withMessages([
                'file' => 'The selected file must be an image.',
            ]);
        }

        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $sourcePath = (string) $metadata['tempPath'];
        $storedName = Str::uuid()->toString().'.'.$extension;
        $storedPath = trim($destination, '/').'/'.$storedName;
        $stream = $localDisk->readStream($sourcePath);

        if ($stream === false || ! $publicDisk->writeStream($storedPath, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw ValidationException::withMessages([
                'file' => 'The uploaded file could not be finalized. Check storage permissions and try again.',
            ]);
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->delete($token, $userId);

        return $this->permanentPayload($storedPath, $metadata);
    }

    public function delete(string $token, int $userId): void
    {
        $token = $this->validatedToken($token);
        Storage::disk('local')->deleteDirectory(self::TEMP_ROOT."/{$userId}/{$token}");
    }

    public function permanentPayload(string $storedPath, array $metadata = []): array
    {
        return [
            'filePath' => $storedPath,
            'fileUrl' => route('fleet.files.show', ['path' => $storedPath]),
            'fileName' => basename($storedPath),
            'originalName' => $metadata['originalName'] ?? basename($storedPath),
            'mimeType' => $metadata['mimeType'] ?? (Storage::disk('public')->mimeType($storedPath) ?: 'application/octet-stream'),
            'sizeBytes' => (int) ($metadata['sizeBytes'] ?? Storage::disk('public')->size($storedPath)),
            'uploadedAt' => $metadata['uploadedAt'] ?? now()->toDateTimeString(),
        ];
    }

    private function purgeExpiredForUser(int $userId, int $hours = 24): void
    {
        $disk = Storage::disk('local');
        $root = self::TEMP_ROOT."/{$userId}";
        $cutoff = now()->subHours($hours)->getTimestamp();

        foreach ($disk->directories($root) as $directory) {
            $manifest = $directory.'/manifest.json';
            if (! $disk->exists($manifest) || $disk->lastModified($manifest) < $cutoff) {
                $disk->deleteDirectory($directory);
            }
        }
    }

    private function validatedToken(string $token): string
    {
        abort_unless((bool) preg_match('/^[a-z0-9]{40}$/', $token), 404);

        return $token;
    }

    private function safeFileName(string $name): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'upload';

        return $base.'-'.Str::lower(Str::random(8)).($extension ? '.'.$extension : '');
    }
}
