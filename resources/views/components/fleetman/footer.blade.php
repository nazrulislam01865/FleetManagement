@props(['brand' => []])

<footer {{ $attributes->class(['fleet-footer']) }}>
    <span class="fleet-footer-line">
        &copy; {{ date('Y') }} {{ $brand['name'] ?? 'FleetMan' }}. All rights reserved.
    </span>
    <span class="fleet-footer-line">
        System design, development, and intellectual property are owned by
        <a href="https://itqanconsulting.com/" target="_blank" rel="noopener noreferrer">
            <b>{{ $brand['footer_owner'] ?? 'ITQAN Consulting' }}</b>
        </a>
    </span>
</footer>
