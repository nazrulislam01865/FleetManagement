@extends('layouts.fleetman')

@section('title', 'Reports | FleetMan')
@section('mobile-title', 'Reports')

@section('content')
<div class="page-section report-page">
    <x-fleetman.topbar :items="[['label' => 'Reports']]" />

    <x-fleetman.title-card
        title="Reports"
        subtitle="Open each report separately from this section. Daily, weekly, and monthly driver fuel reports are kept as independent dynamic report pages."
    />

    <div class="report-card-grid">
        @foreach ($reportCards as $reportCard)
            <x-fleetman.report-link-card :report="$reportCard" />
        @endforeach
    </div>
</div>
@endsection
