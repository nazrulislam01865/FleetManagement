@props([
    'file' => null,
    'fallback' => '👤',
    'alt' => 'Record image',
    'size' => 'table',
])

@php
    $media = is_array($file) ? $file : [];
    $directValue = is_string($file) ? trim($file) : '';
    $path = trim((string) ($media['filePath'] ?? $media['path'] ?? ''));
    $path = preg_replace('#^(public/|storage/)#', '', ltrim($path, '/')) ?? $path;
    $url = '';

    if ($path !== '') {
        $url = \App\Support\FleetPhoto::url($path, false);
    } elseif ($directValue !== '') {
        if (preg_match('#^https?://#i', $directValue) || str_starts_with($directValue, '/')) {
            $url = \App\Support\FleetPhoto::rewriteStoredUrl($directValue, false);
        } else {
            $url = \App\Support\FleetPhoto::url($directValue, false);
        }
    } else {
        $url = \App\Support\FleetPhoto::rewriteStoredUrl(
            trim((string) ($media['previewUrl'] ?? $media['fileUrl'] ?? $media['url'] ?? '')),
            false
        );
    }

    $safeSize = in_array($size, ['table', 'compact', 'large'], true) ? $size : 'table';
@endphp

<span {{ $attributes->class(['entity-avatar', 'entity-avatar-'.$safeSize]) }}>
    @if($url !== '')
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            loading="lazy"
            decoding="async"
            onerror="this.remove()"
        >
    @endif
    <span class="entity-avatar-fallback" aria-hidden="true">{{ $fallback }}</span>
</span>
