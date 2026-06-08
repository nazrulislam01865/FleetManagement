@extends('layouts.fleetman')

@php
    $detail = $detail ?? data_get($fleetman ?? [], 'detail', []);
    $record = $record ?? data_get($fleetman ?? [], 'record');
    $recordPayload = $recordPayload ?? data_get($fleetman ?? [], 'recordPayload', []);
    $recordTitle = $recordTitle ?? data_get($fleetman ?? [], 'recordTitle', data_get($record, 'code', 'Record Details'));
@endphp

@section('title', ($detail['title'] ?? 'Record Details').' | FleetMan')
@section('mobile-title', $detail['title'] ?? 'Details')

@section('content')
@php
    $humanize = static function (string $key): string {
        $key = preg_replace('/[_-]+/', ' ', $key) ?? $key;
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $key) ?? $key;

        return ucwords(trim($key));
    };

    $isList = static fn (array $value): bool => array_is_list($value);

    $isFileLike = static function ($value): bool {
        if (! is_array($value)) {
            return false;
        }

        foreach (['filePath', 'path', 'fileUrl', 'previewUrl', 'url', 'originalName', 'fileName', 'mimeType', 'sizeBytes'] as $key) {
            if (filled($value[$key] ?? null)) {
                return true;
            }
        }

        return false;
    };

    $fileUrl = static function (array $file) use ($fleetman): string {
        $path = trim((string) ($file['filePath'] ?? $file['path'] ?? ''));
        $path = preg_replace('#^(public/|storage/)#', '', ltrim($path, '/')) ?? $path;
        $template = (string) data_get($fleetman, 'resources.uploads.file_template', '');

        if ($path !== '' && $template !== '') {
            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

            return str_replace('__PATH__', $encodedPath, $template);
        }

        return trim((string) ($file['previewUrl'] ?? $file['fileUrl'] ?? $file['url'] ?? ''));
    };

    $isImageFile = static function (array $file): bool {
        $mime = strtolower((string) ($file['mimeType'] ?? $file['type'] ?? ''));
        $name = strtolower((string) ($file['originalName'] ?? $file['fileName'] ?? $file['filePath'] ?? $file['path'] ?? $file['url'] ?? ''));

        return str_starts_with($mime, 'image/') || preg_match('/\.(jpg|jpeg|png|webp|gif|bmp|svg)$/i', $name) === 1;
    };

    $formatBytes = static function ($bytes): string {
        if (! is_numeric($bytes) || (float) $bytes <= 0) {
            return '';
        }

        $size = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 2).' '.$units[$unit];
    };

    $displayValue = static function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_float($value)) {
            return number_format($value, 2, '.', ',');
        }
        if (is_int($value)) {
            return number_format($value);
        }

        return (string) $value;
    };

    $renderField = static function (string $label, $value) use ($displayValue): string {
        return '<div class="fleet-detail-field">'
            .'<small>'.e($label).'</small>'
            .'<strong>'.nl2br(e($displayValue($value))).'</strong>'
            .'</div>';
    };

    $renderFile = static function (string $label, array $file, bool $compact = false) use ($fileUrl, $isImageFile, $formatBytes): string {
        $url = $fileUrl($file);
        $name = trim((string) ($file['originalName'] ?? $file['fileName'] ?? $label ?? 'Uploaded file')) ?: 'Uploaded file';
        $meta = array_filter([
            trim((string) ($file['mimeType'] ?? $file['type'] ?? '')),
            $formatBytes($file['sizeBytes'] ?? null),
            trim((string) ($file['uploadedAt'] ?? '')),
        ]);
        $metaText = implode(' • ', $meta);

        if ($isImageFile($file) && $url !== '') {
            return '<a href="'.e($url).'" target="_blank" rel="noopener" class="fleet-detail-image-card">'
                .'<img src="'.e($url).'" alt="'.e($label ?: $name).'">'
                .'<span>'.e($label ?: $name).'</span>'
                .'</a>';
        }

        $open = $url !== ''
            ? '<a href="'.e($url).'" target="_blank" rel="noopener" class="mini-btn">Open</a>'
            : '<span class="badge soft">No file link</span>';

        return '<div class="fleet-detail-file-row">'
            .'<div><b>'.e($label ?: $name).'</b><small>'.e($metaText !== '' ? $name.' • '.$metaText : $name).'</small></div>'
            .$open
            .'</div>';
    };

    $renderNode = null;
    $renderNode = function (string $label, $value, int $level = 0) use (&$renderNode, $humanize, $isList, $isFileLike, $renderField, $renderFile): string {
        if (! is_array($value)) {
            return $renderField($label, $value);
        }

        if ($isFileLike($value)) {
            $fileHtml = $renderFile($label, $value);
            $class = str_contains($fileHtml, 'fleet-detail-image-card') ? 'fleet-detail-image-grid' : 'fleet-detail-file-list';

            return '<section class="fleet-detail-section"><h3>'.e($label).'</h3><div class="'.$class.'">'.$fileHtml.'</div></section>';
        }

        if ($value === []) {
            return '<section class="fleet-detail-section"><h3>'.e($label).'</h3><p class="fleet-detail-muted">No information saved.</p></section>';
        }

        if ($isList($value)) {
            $cards = '';
            foreach ($value as $index => $item) {
                if (! is_array($item)) {
                    $cards .= '<div class="fleet-detail-mini-card"><div class="fleet-detail-mini-title">'.e($label).' '.($index + 1).'</div>'
                        .$renderField('Value', $item).'</div>';
                    continue;
                }

                if ($isFileLike($item)) {
                    $cards .= '<div class="fleet-detail-mini-card"><div class="fleet-detail-mini-title">'.e($label).' '.($index + 1).'</div>'
                        .'<div style="grid-column:1/-1">'.$renderFile($label.' '.($index + 1), $item, true).'</div></div>';
                    continue;
                }

                $primitive = '';
                $nested = '';
                foreach ($item as $key => $itemValue) {
                    $itemLabel = $humanize((string) $key);
                    if (is_array($itemValue)) {
                        if ($isFileLike($itemValue)) {
                            $nested .= '<div style="grid-column:1/-1">'.$renderFile($itemLabel, $itemValue, true).'</div>';
                        } else {
                            $nested .= '<div style="grid-column:1/-1">'.$renderNode($itemLabel, $itemValue, $level + 1).'</div>';
                        }
                    } else {
                        $primitive .= '<div><small>'.e($itemLabel).'</small><b>'.nl2br(e($itemValue === null || $itemValue === '' ? '—' : (is_bool($itemValue) ? ($itemValue ? 'Yes' : 'No') : (string) $itemValue))).'</b></div>';
                    }
                }

                $cards .= '<div class="fleet-detail-mini-card">'
                    .'<div class="fleet-detail-mini-title">'.e($label).' '.($index + 1).'</div>'
                    .($primitive !== '' ? $primitive : '<span class="fleet-detail-muted">No primary details</span>')
                    .$nested
                    .'</div>';
            }

            return '<section class="fleet-detail-section"><h3>'.e($label).'</h3><div class="fleet-detail-mini-grid">'.$cards.'</div></section>';
        }

        $fields = '';
        $nested = '';
        foreach ($value as $key => $itemValue) {
            $itemLabel = $humanize((string) $key);
            if (is_array($itemValue)) {
                $nested .= $renderNode($itemLabel, $itemValue, $level + 1);
            } else {
                $fields .= $renderField($itemLabel, $itemValue);
            }
        }

        return '<section class="fleet-detail-section"><h3>'.e($label).'</h3>'
            .($fields !== '' ? '<div class="fleet-detail-grid">'.$fields.'</div>' : '')
            .$nested
            .'</section>';
    };

    $primaryFields = '';
    $nestedSections = '';
    foreach ($recordPayload as $key => $value) {
        $label = $humanize((string) $key);
        if (is_array($value)) {
            $nestedSections .= $renderNode($label, $value);
        } else {
            $primaryFields .= $renderField($label, $value);
        }
    }
@endphp

<div class="page-section">
    <x-fleetman.topbar :items="[
        ['label' => $detail['list_label'] ?? 'List', 'route' => $detail['list_route'] ?? null],
        ['label' => $detail['title'] ?? 'Record Details'],
    ]">
        <x-slot:actions>
            <a href="{{ route($detail['list_route']) }}" class="btn light">← {{ $detail['list_label'] }}</a>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        :title="$detail['title'] ?? 'Record Details'"
        subtitle="Read-only details for the selected record."
    />

    <section class="fleet-detail-section">
        <h3>{{ $recordTitle }}</h3>
        <div class="fleet-detail-grid">
            {!! $renderField('Record Code', $record->code) !!}
            {!! $renderField('Record Name', $record->name) !!}
            {!! $renderField('Record Status', $record->status) !!}
            {!! $renderField('Created At', optional($record->created_at)->format('d M Y, h:i A')) !!}
            {!! $renderField('Last Updated', optional($record->updated_at)->format('d M Y, h:i A')) !!}
        </div>
    </section>

    <section class="fleet-detail-section">
        <h3>Full Details</h3>
        @if ($primaryFields !== '')
            <div class="fleet-detail-grid">{!! $primaryFields !!}</div>
        @else
            <p class="fleet-detail-muted">No primary fields were saved for this record.</p>
        @endif
    </section>

    {!! $nestedSections !!}
</div>
@endsection
