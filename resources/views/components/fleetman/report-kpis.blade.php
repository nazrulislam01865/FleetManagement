@props(['items' => []])

<section class="report-kpis">
    @foreach ($items as $item)
        <div class="report-kpi">
            <strong id="{{ $item['id'] }}">{{ $item['value'] ?? '0' }}</strong>
            <span>{{ $item['label'] }}</span>
        </div>
    @endforeach
</section>
