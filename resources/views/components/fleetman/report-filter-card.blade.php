@props(['title' => 'Report Filters', 'subtitle' => 'Choose filters and click Apply Report.'])

<section {{ $attributes->merge(['class' => 'card report-filter-card']) }}>
    <div class="section-head">
        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $subtitle }}</p>
        </div>
    </div>

    {{ $slot }}
</section>
