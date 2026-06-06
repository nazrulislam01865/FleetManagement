@props(['title' => 'Report Result', 'subtitle' => 'Only this report box has horizontal scrolling.', 'tableMinWidth' => '1800px'])

<section {{ $attributes->merge(['class' => 'report-shell']) }}>
    <div class="report-toolbar">
        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $subtitle }}</p>
        </div>

    </div>

    <div class="fixed-report-box">
        <div class="table-scroller">
            <table style="min-width: {{ $tableMinWidth }}">
                {{ $table }}
            </table>
        </div>
        <div class="report-pagination">
            <div id="pageInfo"></div>
            <div class="page-btns">
                <button class="mini-btn report-prev-page" type="button">Previous</button>
                <span id="pageNumbers"></span>
                <button class="mini-btn report-next-page" type="button">Next</button>
            </div>
        </div>
    </div>

    <div class="mobile-cards" id="mobileCards"></div>
</section>
