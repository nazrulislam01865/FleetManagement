<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class FleetTemporaryUploadService
{
    public const TEMP_ROOT = 'fleet-temp';

    public const CHUNK_ROOT = 'fleet-upload-chunks';

    public function store(UploadedFile $file, int $userId, string $uploadKind = 'generic'): array
    {
        $this->purgeExpiredForUser($userId);
        $this->purgeExpiredChunksForUser($userId);

        return $this->storeTemporaryStream(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream',
            (int) $file->getSize(),
            $userId,
            $uploadKind
        );
    }

    public function storeChunk(
        UploadedFile $chunk,
        int $userId,
        string $uploadId,
        int $chunkIndex,
        int $totalChunks,
        string $originalName,
        string $mimeType,
        int $fileSize,
        string $uploadKind = 'generic'
    ): array {
        $this->purgeExpiredChunksForUser($userId);
        $uploadId = $this->validatedUploadId($uploadId);
        $directory = self::CHUNK_ROOT."/{$userId}/{$uploadId}";
        $disk = Storage::disk('local');
        $sessionPath = $directory.'/session.json';
        $session = [
            'uploadId' => $uploadId,
            'userId' => $userId,
            'originalName' => $originalName,
            'mimeType' => $mimeType ?: 'application/octet-stream',
            'sizeBytes' => $fileSize,
            'totalChunks' => $totalChunks,
            'uploadKind' => $uploadKind,
            'updatedAt' => now()->toDateTimeString(),
        ];

        if ($disk->exists($sessionPath)) {
            $existing = json_decode((string) $disk->get($sessionPath), true);
            if (! is_array($existing)
                || (int) ($existing['userId'] ?? 0) !== $userId
                || (string) ($existing['originalName'] ?? '') !== $originalName
                || (int) ($existing['sizeBytes'] ?? 0) !== $fileSize
                || (int) ($existing['totalChunks'] ?? 0) !== $totalChunks
                || (string) ($existing['uploadKind'] ?? 'generic') !== $uploadKind) {
                throw ValidationException::withMessages([
                    'chunk' => 'The chunked upload session is inconsistent. Please choose the file again.',
                ]);
            }
            $session = array_merge($existing, ['updatedAt' => now()->toDateTimeString()]);
        }

        $chunkPath = $directory.'/chunks/'.str_pad((string) $chunkIndex, 6, '0', STR_PAD_LEFT).'.part';
        $stream = fopen($chunk->getRealPath(), 'rb');
        if ($stream === false || ! $disk->writeStream($chunkPath, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw ValidationException::withMessages([
                'chunk' => 'A part of the selected file could not be uploaded. Please try again.',
            ]);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        $disk->put($sessionPath, json_encode($session, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return [
            'uploadId' => $uploadId,
            'chunkIndex' => $chunkIndex,
            'receivedChunks' => count($disk->files($directory.'/chunks')),
            'totalChunks' => $totalChunks,
        ];
    }

    public function completeChunkUpload(string $uploadId, int $userId): array
    {
        $uploadId = $this->validatedUploadId($uploadId);
        $directory = self::CHUNK_ROOT."/{$userId}/{$uploadId}";
        $sessionPath = $directory.'/session.json';
        $disk = Storage::disk('local');

        if (! $disk->exists($sessionPath)) {
            throw ValidationException::withMessages([
                'upload_id' => 'The chunked upload session expired. Please choose the file again.',
            ]);
        }

        $session = json_decode((string) $disk->get($sessionPath), true);
        if (! is_array($session) || (int) ($session['userId'] ?? 0) !== $userId) {
            throw ValidationException::withMessages([
                'upload_id' => 'The chunked upload session is invalid.',
            ]);
        }

        $totalChunks = (int) ($session['totalChunks'] ?? 0);
        if ($totalChunks < 1) {
            throw ValidationException::withMessages([
                'upload_id' => 'The chunked upload session has no file parts.',
            ]);
        }

        $token = Str::lower(Str::random(40));
        $safeName = $this->safeFileName((string) ($session['originalName'] ?? 'upload'));
        $temporaryDirectory = self::TEMP_ROOT."/{$userId}/{$token}";
        $temporaryPath = $temporaryDirectory.'/'.$safeName;
        $disk->makeDirectory($temporaryDirectory);
        $target = fopen($disk->path($temporaryPath), 'wb');

        if ($target === false) {
            throw ValidationException::withMessages([
                'upload_id' => 'The uploaded file could not be assembled. Check storage permissions and try again.',
            ]);
        }

        $actualSize = 0;
        try {
            for ($index = 0; $index < $totalChunks; $index++) {
                $chunkPath = $directory.'/chunks/'.str_pad((string) $index, 6, '0', STR_PAD_LEFT).'.part';
                if (! $disk->exists($chunkPath)) {
                    throw ValidationException::withMessages([
                        'upload_id' => 'One or more file parts are missing. Please upload the document again.',
                    ]);
                }

                $source = $disk->readStream($chunkPath);
                if ($source === false) {
                    throw ValidationException::withMessages([
                        'upload_id' => 'A file part could not be read. Please upload the document again.',
                    ]);
                }

                $copied = stream_copy_to_stream($source, $target);
                fclose($source);
                if ($copied === false) {
                    throw ValidationException::withMessages([
                        'upload_id' => 'The uploaded file could not be assembled. Please try again.',
                    ]);
                }
                $actualSize += $copied;
            }
        } catch (Throwable $exception) {
            fclose($target);
            $disk->deleteDirectory($temporaryDirectory);
            throw $exception;
        }
        fclose($target);

        $declaredSize = (int) ($session['sizeBytes'] ?? 0);
        if ($actualSize <= 0 || $actualSize !== $declaredSize) {
            $disk->deleteDirectory($temporaryDirectory);
            throw ValidationException::withMessages([
                'upload_id' => 'The uploaded document was incomplete. Please choose the file and try again.',
            ]);
        }

        $detectedMimeType = $disk->mimeType($temporaryPath) ?: (string) ($session['mimeType'] ?? 'application/octet-stream');
        $metadata = [
            'tempToken' => $token,
            'temporary' => true,
            'tempPath' => $temporaryPath,
            'fileName' => basename($temporaryPath),
            'originalName' => (string) ($session['originalName'] ?? basename($temporaryPath)),
            'mimeType' => $detectedMimeType,
            'sizeBytes' => $actualSize,
            'uploadedAt' => now()->toDateTimeString(),
            'userId' => $userId,
            'uploadKind' => (string) ($session['uploadKind'] ?? 'generic'),
        ];

        $disk->put(
            $temporaryDirectory.'/manifest.json',
            json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
        $disk->deleteDirectory($directory);

        return $metadata;
    }

    public function deleteChunkUpload(string $uploadId, int $userId): void
    {
        $uploadId = $this->validatedUploadId($uploadId);
        Storage::disk('local')->deleteDirectory(self::CHUNK_ROOT."/{$userId}/{$uploadId}");
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

    private function storeTemporaryStream(
        string $sourcePath,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        int $userId,
        string $uploadKind
    ): array {
        $token = Str::lower(Str::random(40));
        $safeName = $this->safeFileName($originalName);
        $directory = self::TEMP_ROOT."/{$userId}/{$token}";
        $path = $directory.'/'.$safeName;
        $disk = Storage::disk('local');
        $stream = fopen($sourcePath, 'rb');

        if ($stream === false || ! $disk->writeStream($path, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw ValidationException::withMessages([
                'file' => 'The selected file could not be uploaded. Check storage permissions and try again.',
            ]);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        $metadata = [
            'tempToken' => $token,
            'temporary' => true,
            'tempPath' => $path,
            'fileName' => basename($path),
            'originalName' => $originalName,
            'mimeType' => $mimeType ?: 'application/octet-stream',
            'sizeBytes' => $sizeBytes,
            'uploadedAt' => now()->toDateTimeString(),
            'userId' => $userId,
            'uploadKind' => $uploadKind,
        ];

        $disk->put(
            $directory.'/manifest.json',
            json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $metadata;
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

    private function purgeExpiredChunksForUser(int $userId, int $hours = 24): void
    {
        $disk = Storage::disk('local');
        $root = self::CHUNK_ROOT."/{$userId}";
        $cutoff = now()->subHours($hours)->getTimestamp();

        foreach ($disk->directories($root) as $directory) {
            $session = $directory.'/session.json';
            if (! $disk->exists($session) || $disk->lastModified($session) < $cutoff) {
                $disk->deleteDirectory($directory);
            }
        }
    }

    private function validatedToken(string $token): string
    {
        abort_unless((bool) preg_match('/^[a-z0-9]{40}$/', $token), 404);

        return $token;
    }

    private function validatedUploadId(string $uploadId): string
    {
        if (! preg_match('/^[a-z0-9-]{16,80}$/', $uploadId)) {
            throw ValidationException::withMessages([
                'upload_id' => 'The upload session identifier is invalid.',
            ]);
        }

        return $uploadId;
    }

    private function safeFileName(string $name): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'upload';

        return $base.'-'.Str::lower(Str::random(8)).($extension ? '.'.$extension : '');
    }
}
