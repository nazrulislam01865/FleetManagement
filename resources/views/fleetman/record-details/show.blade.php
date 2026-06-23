@extends('layouts.fleetman')

@php
    $detail = $detail ?? data_get($fleetman ?? [], 'detail', []);
    $record = $record ?? data_get($fleetman ?? [], 'record');
    $recordPayload = $recordPayload ?? data_get($fleetman ?? [], 'recordPayload', []);
    $recordTitle = $recordTitle ?? data_get($fleetman ?? [], 'recordTitle', data_get($record, 'code', 'Record Details'));
    $detailResource = $detailResource ?? data_get($fleetman ?? [], 'detailResource', '');
@endphp

@section('title', ($detail['title'] ?? 'Record Details').' | FleetMan')
@section('mobile-title', $detail['title'] ?? 'Details')

@section('content')
@php
    $display = static function ($value, string $fallback = '—'): string {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    };

    $formatDate = static function ($value, string $format = 'd M Y'): string {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $formatDateTime = static function ($value): string {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $formatMoney = static function ($value, int $decimals = 0): string {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        return '৳'.number_format((float) $value, $decimals);
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

    $fileUrl = static function ($file): string {
        if (! is_array($file)) {
            return '';
        }

        $path = trim((string) ($file['filePath'] ?? $file['path'] ?? ''));
        if ($path !== '') {
            return \App\Support\FleetPhoto::url($path, false);
        }

        return \App\Support\FleetPhoto::rewriteStoredUrl(
            trim((string) ($file['previewUrl'] ?? $file['fileUrl'] ?? $file['url'] ?? '')),
            false
        );
    };

    $fileName = static function ($file): string {
        if (! is_array($file)) {
            return '—';
        }

        return trim((string) ($file['originalName'] ?? $file['fileName'] ?? basename((string) ($file['filePath'] ?? $file['path'] ?? '')))) ?: 'Uploaded file';
    };

    $fileDescription = static function ($file) use ($fileName, $formatBytes): string {
        if (! is_array($file)) {
            return '—';
        }

        $mime = strtoupper(trim((string) ($file['mimeType'] ?? $file['type'] ?? '')));
        if (str_contains($mime, '/')) {
            $mime = strtoupper((string) last(explode('/', $mime)));
        }

        return implode(' · ', array_filter([
            $fileName($file),
            $mime,
            $formatBytes($file['sizeBytes'] ?? null),
        ])) ?: '—';
    };

    $photoUrl = static function ($file) use ($fileUrl): string {
        return $fileUrl(is_array($file) ? $file : []);
    };

    $canManageRecord = (bool) data_get($fleetman ?? [], 'auth.pageAccess.canManage', false);
    $listRoute = (string) ($detail['list_route'] ?? 'fleet.dashboard');
    $listUrl = Route::has($listRoute) ? route($listRoute) : route('fleet.dashboard');
    $editUrl = Route::has($listRoute)
        ? route($listRoute, ['action' => 'edit', 'code' => (string) $record->code])
        : $listUrl;

    $pageSubtitles = [
        'drivers' => 'Clean driver profile view with grouped information and table-style line items.',
        'employees' => 'Single table-style detail view with clearer label and value separation, especially on mobile.',
        'contracts' => 'Clean contract view with grouped line-item tables, vertical assignment details, and document records.',
        'fuel_recharges' => 'Detail view organized following the fuel recharge input form sequence.',
    ];
    $showPageSubtitle = ! in_array($detailResource, ['vehicles', 'trips'], true);
@endphp

<div class="page-section fleet-record-detail-page fleet-record-detail-page-{{ str_replace('_', '-', $detailResource) }}">
    <x-fleetman.topbar :items="[
        ['label' => $detail['list_label'] ?? 'List', 'route' => $listRoute],
        ['label' => $detail['title'] ?? 'Record Details'],
    ]" />

    <div class="record-detail-page-header">
        <div class="record-detail-page-title">
            <h1>{{ $detail['title'] ?? 'Record Details' }}</h1>
            @if ($showPageSubtitle)
                <p>{{ $pageSubtitles[$detailResource] ?? 'Read-only details for the selected record.' }}</p>
            @endif
        </div>
        <div class="record-detail-page-actions">
            <a href="{{ $listUrl }}" class="record-detail-secondary-btn">← {{ $detail['list_label'] ?? 'Back to List' }}</a>
            @if($canManageRecord)
                <a href="{{ $editUrl }}" class="record-detail-primary-btn">Edit {{ str_replace(' Details', '', $detail['title'] ?? 'Record') }}</a>
            @endif
        </div>
    </div>

    @includeIf('fleetman.record-details.'.$detailResource)
</div>
@endsection
