<?php

namespace App\Support;

final class FleetDocumentUploadPolicy
{
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    public const MAX_KILOBYTES = 4096;

    public const MAX_BYTES = self::MAX_KILOBYTES * 1024;

    public const ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:'.implode(',', self::EXTENSIONS),
            'max:'.self::MAX_KILOBYTES,
        ];
    }

    public static function messages(string $label = 'document'): array
    {
        $display = $label === 'file' ? 'document' : str_replace('_', ' ', $label);

        return [
            "{$label}.mimes" => "The {$display} must be a PDF, DOC, DOCX, XLS or XLSX file. Images are not allowed.",
            "{$label}.max" => "The {$display} must not exceed 4 MB.",
            "{$label}.uploaded" => "The {$display} upload failed before it reached the application. Please try again.",
            "{$label}.file" => "The selected {$display} is not a valid file.",
            "{$label}.required" => 'The '.ucfirst($display).' file is required.',
        ];
    }

    public static function extensionAllowed(string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, self::EXTENSIONS, true);
    }
}
