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
        $template = route('fleet.files.show', ['path' => '__PATH__']);
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));
        $url = str_replace('__PATH__', $encodedPath, $template);
    } elseif ($directValue !== '') {
        $url = preg_match('#^https?://#i', $directValue) || str_starts_with($directValue, '/')
            ? $directValue
            : str_replace(
                '__PATH__',
                implode('/', array_map('rawurlencode', explode('/', ltrim($directValue, '/')))),
                route('fleet.files.show', ['path' => '__PATH__'])
            );
    } else {
        $url = trim((string) ($media['previewUrl'] ?? $media['fileUrl'] ?? $media['url'] ?? ''));
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
