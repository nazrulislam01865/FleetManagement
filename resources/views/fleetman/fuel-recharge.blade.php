@extends('layouts.fleetman')

@section('title', 'Fuel Recharge | FleetMan')
@section('mobile-title', 'Fuel Entry')

@section('content')
<div class="recharge-page fuel-recharge-dynamic-page">
    <x-fleetman.topbar :items="[['label' => 'Fuel Recharge']]">
        <x-slot:actions>
            <span class="badge soft">Dynamic field fuel recharge</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        class="desktop-title"
        title="Create Fuel Recharge"
        subtitle="Select contract, load assigned vehicle, take live photos, enter fuel quantity, confirm ODO, and submit. Fuel type and latest active rate are loaded automatically."
    />

    <div class="help-card">
        <div>
            <h1>Fuel Recharge Entry</h1>
            <p>Contract and vehicle come from your saved records. Fuel setup comes from the selected vehicle and rates come from the latest active fuel price.</p>
        </div>
        <div class="capture-counter"><small>Photo Status</small><div id="photoCount">0 / 3 required</div></div>
    </div>

    <datalist id="fuelStationList">
        @foreach (($fleetman['fuelStations'] ?? []) as $station)
            <option value="{{ $station }}"></option>
        @endforeach
    </datalist>

    <section class="step-card">
        <div class="step-head"><div class="step-no">1</div><div><h2>Select contract and vehicle</h2><p>Choose the contract first. Vehicle list changes based on that contract’s assignments.</p></div></div>
        <div class="grid">
            <div class="field">
                <label for="contractSelect">Contract <span class="req">*</span></label>
                <select id="contractSelect">
                    <option value="">- Select contract -</option>
                    @foreach (($fleetman['contracts'] ?? []) as $contract)
                        <option value="{{ $contract['id'] }}">{{ $contract['label'] }}</option>
                    @endforeach
                </select>
                <div class="hint">First select the contract. Then the assigned vehicles will load automatically.</div>
            </div>
            <div class="field">
                <label for="vehicleSelect">Vehicle <span class="req">*</span></label>
                <select id="vehicleSelect">
                    <option value="">- Select contract first -</option>
                </select>
                <div class="hint">If the contract has one vehicle it will be selected automatically.</div>
            </div>
        </div>
        <div class="vehicle-note" id="vehicleSetupNote">Select a contract first. Vehicle, driver, fuel setup and latest ODO will load from saved records.</div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">2</div><div><h2>Take photos</h2><p>Vehicle, fuel/dispenser and ODO meter photos must be taken from the live camera. Gallery upload is not allowed on this page.</p></div></div>
        <div class="photo-list" id="photoList"></div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">3</div><div><h2>Enter fuel amount</h2><p>Fuel names come from the selected vehicle. Rates come from the latest active fuel price.</p></div></div>
        <div class="fuel-panel">
            <div class="fuel-panel-head"><div><b>Main Fuel <span class="req">*</span></b><span>Primary fuel is loaded from the vehicle setup.</span></div><span class="fuel-chip required">Required</span></div>
            <div class="grid">
                <div class="field"><label for="primaryFuelName">Main Fuel Name</label><input id="primaryFuelName" readonly value=""><div class="hint">Loaded from vehicle setup.</div></div>
                <div class="field"><label for="primaryStation">Primary Fuel Station <span class="req">*</span></label><input id="primaryStation" list="fuelStationList" placeholder="Type or select fuel station"><div class="hint">Fuel stations come from Vendor/Party records where available.</div></div>
                <div class="field"><label for="primaryQty">Quantity <span class="req">*</span></label><input id="primaryQty" type="number" min="0" step="0.01" value="" inputmode="decimal"><div class="hint" id="primaryQtyHint">Enter quantity.</div></div>
                <div class="field"><label for="primaryRate">Fuel Rate</label><input id="primaryRate" readonly value=""><div class="hint" id="primaryRateHint">Loaded from latest active fuel price.</div></div>
                <div class="field"><label for="primaryAmount">Amount</label><input id="primaryAmount" readonly value="৳ 0.00"></div>
            </div>
        </div>
        <div class="secondary-toggle">
            <label class="switch-line"><input id="hasSecondaryFuel" type="checkbox"><span class="switch-ui"></span><strong>Add second fuel, like CNG or LPG</strong></label>
            <p>Turn this on only when the selected vehicle has a secondary fuel setup.</p>
        </div>
        <div class="fuel-panel secondary" id="secondaryFuelBlock" style="display:none">
            <div class="fuel-panel-head"><div><b>Second Fuel</b><span>Secondary fuel is loaded from the selected vehicle setup.</span></div><span class="fuel-chip optional">Optional</span></div>
            <div class="grid">
                <div class="field"><label for="secondaryFuelName">Second Fuel Name</label><input id="secondaryFuelName" readonly value=""><div class="hint">Loaded from vehicle setup.</div></div>
                <div class="field"><label for="secondaryStation">Secondary Fuel Station</label><input id="secondaryStation" list="fuelStationList" placeholder="Type or select fuel station"><div class="hint">Can be different from the primary station.</div></div>
                <div class="field"><label for="secondaryQty">Quantity</label><input id="secondaryQty" type="number" min="0" step="0.01" value="0" inputmode="decimal"><div class="hint" id="secondaryQtyHint">Enter quantity.</div></div>
                <div class="field"><label for="secondaryRate">Fuel Rate</label><input id="secondaryRate" readonly value=""><div class="hint" id="secondaryRateHint">Loaded from latest active fuel price.</div></div>
                <div class="field"><label for="secondaryAmount">Amount</label><input id="secondaryAmount" readonly value="৳ 0.00"></div>
            </div>
        </div>
        <div class="amount-strip"><div><small>Total Fuel Cost</small><b id="totalAmount">৳ 0.00</b></div><button class="btn light" type="button" id="recalculateFuelRechargeBtn">Recalculate</button></div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">4</div><div><h2>ODO reading and submit</h2><p>Confirm start and end KM. Total KM and mileage are calculated automatically.</p></div></div>
        <div class="grid">
            <div class="field"><label for="startKm">Start KM</label><input id="startKm" readonly value=""><div class="hint">Loaded from latest saved ODO/fuel recharge or vehicle setup.</div></div>
            <div class="field"><label for="endKm">End KM <span class="req">*</span></label><input id="endKm" type="number" min="0" value="" placeholder="Latest ODO reading" inputmode="numeric"></div>
        </div>
        <div class="grid3" style="margin-top:12px">
            <div class="field"><label for="totalKm">Total KM</label><input id="totalKm" readonly value=""></div>
            <div class="field"><label for="mileage">Mileage (KM/L)</label><input id="mileage" readonly value=""></div>
            <div class="field"><label for="submittedBy">Submitted By</label><input id="submittedBy" readonly value="{{ $account['name'] ?? 'Logged-in User' }}"></div>
        </div>
        <div class="field" style="margin-top:12px"><label for="rechargeRemarks">Remarks</label><textarea id="rechargeRemarks" placeholder="Write any note about the fuel recharge."></textarea></div>
        <div class="log-grid" style="margin-top:12px">
            <div class="log-box"><small>Submit Time</small><b id="submitTime">Not submitted</b><span>Saved when user taps Submit.</span></div>
            <div class="log-box"><small>Submit Place</small><b id="submitPlace">Not submitted</b><span id="submitPlaceDetail">Place name will be saved if location is allowed.</span></div>
        </div>
        <div class="bottom-submit"><button class="btn light" id="resetRechargeBtn" type="button">Reset Form</button><button class="btn secondary" id="draftRechargeBtn" type="button">Save Draft</button><button class="btn green" id="submitRechargeBtn" type="button">Submit</button></div>
    </section>
</div>
@endsection
