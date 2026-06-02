@extends('layouts.fleetman')

@section('title', 'Fuel Recharge | FleetMan')
@section('mobile-title', 'Fuel Entry')

@section('content')
<div class="recharge-page">
    <x-fleetman.topbar :items="[['label' => 'Fuel Recharge']]">
        <x-slot:actions>
            <span class="badge soft">Simple mobile form for field users</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        class="desktop-title"
        title="Create Fuel Recharge"
        subtitle="Select contract, confirm vehicle, take photos, enter fuel quantity, and submit. The system will save time and place name with each photo."
    />

    <div class="help-card">
        <div>
            <h1>Fuel Recharge Entry</h1>
            <p>Select contract, confirm vehicle, take photos, enter fuel quantity, and submit. The system will save time and place name with each photo.</p>
        </div>
        <div class="capture-counter"><small>Photo Status</small><div id="photoCount">0 / 3 required</div></div>
    </div>

    <section class="step-card">
        <div class="step-head"><div class="step-no">1</div><div><h2>Select contract and vehicle</h2><p>Vehicle list changes based on selected contract.</p></div></div>
        <div class="grid">
            <div class="field">
                <label for="contractSelect">Contract <span class="req">*</span></label>
                <select id="contractSelect">
                    <option value="">- Select contract -</option>
                    @foreach ($fleetman['contracts'] as $contract)
                        <option value="{{ $contract['id'] }}" @selected($loop->first)>{{ $contract['label'] }}</option>
                    @endforeach
                </select>
                <div class="hint">First select the contract. Then vehicle list will update.</div>
            </div>
            <div class="field">
                <label for="vehicleSelect">Vehicle <span class="req">*</span></label>
                <select id="vehicleSelect"></select>
                <div class="hint">Vehicle fuel setup will load automatically.</div>
            </div>
        </div>
        <div class="vehicle-note" id="vehicleSetupNote">Vehicle setup loaded.</div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">2</div><div><h2>Take photos</h2><p>Tap the camera button and take photo. Upload from gallery is hidden from the screen.</p></div></div>
        <div class="photo-list" id="photoList"></div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">3</div><div><h2>Enter fuel amount</h2><p>Fuel name and rate come from vehicle setup. User only enters quantity.</p></div></div>
        <div class="fuel-panel">
            <div class="fuel-panel-head"><div><b>Main Fuel <span class="req">*</span></b><span>This is the vehicle’s regular fuel from setup.</span></div><span class="fuel-chip required">Required</span></div>
            <div class="grid">
                <div class="field"><label for="primaryFuelName">Main Fuel Name</label><input id="primaryFuelName" readonly value="Diesel"><div class="hint">Loaded from vehicle setup.</div></div>
                <div class="field"><label for="primaryQty">Quantity <span class="req">*</span></label><input id="primaryQty" type="number" min="0" step="0.01" value="42.50" inputmode="decimal"><div class="hint">Enter quantity in liter.</div></div>
                <div class="field"><label for="primaryRate">Fuel Rate</label><input id="primaryRate" readonly value="109.00"><div class="hint">Loaded from setup data.</div></div>
                <div class="field"><label for="primaryAmount">Amount</label><input id="primaryAmount" readonly value="৳ 4,632.50"></div>
            </div>
        </div>
        <div class="secondary-toggle">
            <label class="switch-line"><input id="hasSecondaryFuel" type="checkbox"><span class="switch-ui"></span><strong>Add second fuel, like CNG or LPG</strong></label>
            <p>Turn this on only when the vehicle also takes another fuel in the same recharge.</p>
        </div>
        <div class="fuel-panel secondary" id="secondaryFuelBlock" style="display:none">
            <div class="fuel-panel-head"><div><b>Second Fuel</b><span>Only enter this if the vehicle took CNG/LPG or another second fuel.</span></div><span class="fuel-chip optional">Optional</span></div>
            <div class="grid">
                <div class="field"><label for="secondaryFuelName">Second Fuel Name</label><input id="secondaryFuelName" readonly value="CNG"><div class="hint">Loaded from vehicle setup.</div></div>
                <div class="field"><label for="secondaryQty">Quantity</label><input id="secondaryQty" type="number" min="0" step="0.01" value="0" inputmode="decimal"><div class="hint">Enter quantity in KG / m³ / equivalent unit.</div></div>
                <div class="field"><label for="secondaryRate">Fuel Rate</label><input id="secondaryRate" readonly value="45.00"><div class="hint">Loaded from setup data.</div></div>
                <div class="field"><label for="secondaryAmount">Amount</label><input id="secondaryAmount" readonly value="৳ 0.00"></div>
            </div>
        </div>
        <div class="amount-strip"><div><small>Total Fuel Cost</small><b id="totalAmount">৳ 4,632.50</b></div><button class="btn light" type="button" id="recalculateFuelRechargeBtn">Recalculate</button></div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">4</div><div><h2>ODO reading and submit</h2><p>Enter the meter reading shown in the ODO photo.</p></div></div>
        <div class="grid">
            <div class="field"><label for="odoReading">ODO Meter Reading <span class="req">*</span></label><input id="odoReading" type="number" min="0" value="58420" inputmode="numeric"></div>
            <div class="field"><label for="rechargeRemarks">Remarks</label><input id="rechargeRemarks" value="Fuel taken during morning duty route." placeholder="Any note"></div>
        </div>
        <div class="log-grid" style="margin-top:12px">
            <div class="log-box"><small>Submit Time</small><b id="submitTime">Not submitted</b><span>Saved when user taps Submit.</span></div>
            <div class="log-box"><small>Submit Place</small><b id="submitPlace">Not submitted</b><span id="submitPlaceDetail">Place name will be saved if location is allowed.</span></div>
        </div>
        <div class="bottom-submit"><button class="btn light" id="draftRechargeBtn" type="button">Save Draft</button><button class="btn green" id="submitRechargeBtn" type="button">Submit</button></div>
    </section>
</div>
@endsection
