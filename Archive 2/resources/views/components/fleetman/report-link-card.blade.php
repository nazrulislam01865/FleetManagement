@props(['report'])

<a class="report-link-card" href="{{ Route::has($report['route']) ? route($report['route']) : '#' }}">
    <div class="report-link-icon">{{ $report['icon'] ?? '📊' }}</div>
    <div>
        <h2>{{ $report['title'] }}</h2>
        <p>{{ $report['description'] }}</p>
        <span>{{ $report['button'] ?? 'Open Report' }} →</span>
    </div>
</a>
