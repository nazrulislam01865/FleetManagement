<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Services\FleetTemporaryUploadService;
use App\Support\FleetDocumentUploadPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemporaryUploadController extends Controller
{
    public function store(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $uploadKind = (string) $request->input('upload_kind', 'generic');
        $rules = $uploadKind === 'document'
            ? FleetDocumentUploadPolicy::rules()
            : ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg,ico,pdf,doc,docx,xls,xlsx', 'max:10240'];

        $validated = $request->validate([
            'upload_kind' => ['nullable', Rule::in(['generic', 'document', 'image'])],
            'file' => $rules,
        ], $uploadKind === 'document' ? FleetDocumentUploadPolicy::messages('file') : [
            'file.uploaded' => 'The file upload failed before it reached the application. Please try again.',
        ]);

        $file = $uploads->store($validated['file'], (int) $request->user()->id, $uploadKind);
        $file['previewUrl'] = route('fleet.uploads.preview', ['token' => $file['tempToken']]);
        $file['fileUrl'] = $file['previewUrl'];

        return response()->json([
            'ok' => true,
            'file' => $file,
        ]);
    }

    public function storeChunk(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[a-z0-9-]{16,80}$/'],
            'chunk_index' => ['required', 'integer', 'min:0', 'max:200'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:200'],
            'original_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1', 'max:'.FleetDocumentUploadPolicy::MAX_BYTES],
            'upload_kind' => ['required', Rule::in(['document'])],
            'chunk' => ['required', 'file', 'max:512'],
        ], [
            'chunk.uploaded' => 'A part of the document upload failed before it reached the application. Please try again.',
            'chunk.max' => 'A document upload part was too large. Please choose the file again.',
        ]);

        if (! FleetDocumentUploadPolicy::extensionAllowed($validated['original_name'])) {
            throw ValidationException::withMessages([
                'original_name' => 'The document must be a PDF, DOC, DOCX, XLS or XLSX file. Images are not allowed.',
            ]);
        }

        if ((int) $validated['chunk_index'] >= (int) $validated['total_chunks']) {
            throw ValidationException::withMessages([
                'chunk_index' => 'The document upload part number is invalid.',
            ]);
        }

        $result = $uploads->storeChunk(
            $validated['chunk'],
            (int) $request->user()->id,
            $validated['upload_id'],
            (int) $validated['chunk_index'],
            (int) $validated['total_chunks'],
            $validated['original_name'],
            (string) ($validated['mime_type'] ?? 'application/octet-stream'),
            (int) $validated['file_size'],
            $validated['upload_kind']
        );

        return response()->json(['ok' => true] + $result);
    }

    public function completeChunk(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[a-z0-9-]{16,80}$/'],
        ]);

        $file = $uploads->completeChunkUpload($validated['upload_id'], (int) $request->user()->id);

        if (($file['uploadKind'] ?? '') === 'document') {
            if (! FleetDocumentUploadPolicy::extensionAllowed((string) ($file['originalName'] ?? ''))
                || str_starts_with(strtolower((string) ($file['mimeType'] ?? '')), 'image/')
                || (int) ($file['sizeBytes'] ?? 0) > FleetDocumentUploadPolicy::MAX_BYTES) {
                $uploads->delete((string) $file['tempToken'], (int) $request->user()->id);
                throw ValidationException::withMessages([
                    'upload_id' => 'The document must be a PDF, DOC, DOCX, XLS or XLSX file and must not exceed 4 MB.',
                ]);
            }
        }

        $file['previewUrl'] = route('fleet.uploads.preview', ['token' => $file['tempToken']]);
        $file['fileUrl'] = $file['previewUrl'];

        return response()->json([
            'ok' => true,
            'file' => $file,
        ]);
    }

    public function preview(Request $request, string $token, FleetTemporaryUploadService $uploads): BinaryFileResponse
    {
        $metadata = $uploads->metadata($token, (int) $request->user()->id);
        $disk = Storage::disk('local');
        $path = (string) $metadata['tempPath'];

        return response()->file($disk->path($path), [
            'Content-Type' => $metadata['mimeType'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes((string) ($metadata['originalName'] ?? 'upload')).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $token, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $uploads->delete($token, (int) $request->user()->id);

        return response()->json(['ok' => true]);
    }

    public function destroyChunk(Request $request, string $uploadId, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $uploads->deleteChunkUpload($uploadId, (int) $request->user()->id);

        return response()->json(['ok' => true]);
    }
}
