(function () {
    'use strict';

    const data = window.FLEETMAN || {};
    const options = data.options || {};
    const samples = data.samples || {};

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const value = (selector) => ($(selector) ? $(selector).value : '');
    const setValue = (selector, nextValue) => { if ($(selector)) $(selector).value = nextValue ?? ''; };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ch]));

    function toast(message) {
        const element = $('#toast');
        if (!element) return;
        element.textContent = message;
        element.classList.add('show');
        setTimeout(() => element.classList.remove('show'), 2800);
    }

    function downloadCsv(filename, rows) {
        const csv = rows.map((row) => row.map((cell) => `"${String(cell ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function money(number) {
        return '৳ ' + Number(number || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function bindMobileDrawer() {
        const body = document.body;
        $('#menuBtn')?.addEventListener('click', () => body.classList.add('drawer-open'));
        $('#backdrop')?.addEventListener('click', () => body.classList.remove('drawer-open'));
        $$('.menu-item').forEach((link) => link.addEventListener('click', () => body.classList.remove('drawer-open')));
    }

    function setVisible(pageId) {
        ['vehicleAddPage', 'vehicleListPage', 'fuelPriceAddPage', 'fuelPriceListPage'].forEach((id) => {
            const element = document.getElementById(id);
            if (element) element.classList.toggle('hidden', id !== pageId);
        });
        window.scrollTo(0, 0);
    }

    function populateSelect(select, values, placeholder) {
        if (!select) return;
        const oldValue = select.value;
        select.innerHTML = '';
        if (placeholder !== undefined) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            select.appendChild(option);
        }
        values.forEach((item) => {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            select.appendChild(option);
        });
        if (oldValue && values.includes(oldValue)) select.value = oldValue;
    }

    function initVehicles() {
        const STORAGE = 'fleetman_vehicles_v2';
        let vehicles = JSON.parse(localStorage.getItem(STORAGE) || 'null') || (samples.vehicles || []);
        const vehicleCategories = options.vehicle_categories || {};
        const fuelTypes = options.fuel_types || [];
        const docTemplates = options.document_templates || [];
        const docReminders = options.document_reminders || [];

        function saveStore() {
            localStorage.setItem(STORAGE, JSON.stringify(vehicles));
        }

        function uid() {
            return 'VHL' + String(Date.now()).slice(-8);
        }

        function updateSubCategory(selectedValue) {
            const category = $('#category')?.value || '';
            const subCategories = vehicleCategories[category] || [];
            populateSelect($('#subCategory'), subCategories, 'Select sub-category');
            if (selectedValue && subCategories.includes(selectedValue)) $('#subCategory').value = selectedValue;
        }

        function getUsage() {
            return $('input[name="usage"]:checked')?.value || '';
        }

        function setUsage(usage) {
            $$('input[name="usage"]').forEach((radio) => { radio.checked = radio.value === usage; });
        }

        function addFuelRow(row = {}) {
            const wrapper = $('#vehicleFuelRows');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row fuel-row';
            div.innerHTML = `
                <div class="field">
                    <label>Fuel Type</label>
                    <select class="fuelType">${fuelTypes.map((type) => `<option value="${escapeHtml(type)}" ${row.type === type ? 'selected' : ''}>${escapeHtml(type)}</option>`).join('')}</select>
                </div>
                <div class="field">
                    <label>Fuel Priority</label>
                    <select class="fuelPriority">
                        ${['Primary', 'Secondary', 'Tertiary'].map((priority) => `<option value="${priority}" ${row.priority === priority ? 'selected' : ''}>${priority}</option>`).join('')}
                    </select>
                </div>
                <div class="field">
                    <label>Default Rate</label>
                    <input class="fuelRate" type="number" placeholder="Example: 110" value="${escapeHtml(row.rate || '')}">
                </div>
                <button type="button" class="mini-btn remove-row">Remove</button>`;
            wrapper.appendChild(div);
            if (wrapper.children.length === 1 && !row.priority) div.querySelector('.fuelPriority').value = 'Primary';
        }

        function addDocRow(row = {}) {
            const wrapper = $('#vehicleDocRows');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row doc-row';
            const defaultDocOptions = [''].concat(docTemplates);
            div.innerHTML = `
                <div class="field">
                    <label>Document Name</label>
                    <select class="docName">
                        ${defaultDocOptions.map((name) => `<option value="${escapeHtml(name)}" ${row.name === name ? 'selected' : ''}>${escapeHtml(name || 'Select document')}</option>`).join('')}
                    </select>
                </div>
                <div class="field">
                    <label>Expiry Date</label>
                    <input class="docExpiry" type="date" value="${escapeHtml(row.expiry || '')}">
                </div>
                <div class="field">
                    <label>Reminder</label>
                    <select class="docReminder">${docReminders.map((reminder) => `<option value="${escapeHtml(reminder)}" ${row.reminder === reminder ? 'selected' : ''}>${escapeHtml(reminder)}</option>`).join('')}</select>
                </div>
                <button type="button" class="mini-btn remove-row">Remove</button>`;
            wrapper.appendChild(div);
        }

        function resetForm(withId = true) {
            $$('#vehicleAddPage input:not([type=radio]):not([type=file]), #vehicleAddPage textarea').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage select').forEach((select) => { select.selectedIndex = 0; });
            setUsage('');
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            if (withId) setValue('#vehicleId', uid());
            setValue('#rent', 0);
            updateSubCategory('');
            addFuelRow({ priority: 'Primary' });
            docTemplates.forEach((doc) => addDocRow({ name: doc, reminder: docReminders[0] || '' }));
        }

        function collectVehicle() {
            return {
                id: value('#vehicleId'),
                name: value('#vehicleName').trim(),
                regNo: value('#regNo').trim(),
                vendor: value('#vendor'),
                model: value('#model').trim(),
                color: value('#color').trim(),
                engineNo: value('#engineNo').trim(),
                mileage: value('#mileage'),
                odo: value('#odo'),
                category: value('#category'),
                subCategory: value('#subCategory'),
                usage: getUsage(),
                driver: value('#driver'),
                rent: value('#rent'),
                notes: value('#notes'),
                fuels: $$('.fuel-row').map((row) => ({
                    type: $('.fuelType', row).value,
                    priority: $('.fuelPriority', row).value,
                    rate: $('.fuelRate', row).value,
                })),
                docs: $$('.doc-row').map((row) => ({
                    name: $('.docName', row).value,
                    expiry: $('.docExpiry', row).value,
                    reminder: $('.docReminder', row).value,
                })).filter((doc) => doc.name),
                status: 'Active',
            };
        }

        function saveVehicle() {
            const vehicle = collectVehicle();
            if (!vehicle.name || !vehicle.regNo || !vehicle.vendor || !vehicle.model || !vehicle.engineNo || !vehicle.category || !vehicle.usage || !vehicle.fuels.length) {
                toast('Please fill all required fields and add at least one fuel.');
                return;
            }
            if (!vehicle.fuels.some((fuel) => fuel.priority === 'Primary')) {
                toast('Please mark one fuel as Primary.');
                return;
            }
            const existingRegistration = vehicles.find((item) => item.regNo.toLowerCase() === vehicle.regNo.toLowerCase() && item.id !== vehicle.id);
            if (existingRegistration) {
                toast('Registration number must be unique. This registration already exists.');
                return;
            }
            const index = vehicles.findIndex((item) => item.id === vehicle.id);
            if (index >= 0) vehicles[index] = vehicle;
            else vehicles.unshift(vehicle);
            saveStore();
            renderTable();
            toast('Vehicle saved successfully.');
            setVisible('vehicleListPage');
        }

        function loadSample() {
            const sample = (samples.vehicles || [])[0];
            if (!sample) return;
            resetForm(false);
            setValue('#vehicleId', sample.id);
            setValue('#vehicleName', sample.name);
            setValue('#regNo', sample.regNo);
            setValue('#vendor', sample.vendor);
            setValue('#model', sample.model);
            setValue('#color', sample.color);
            setValue('#engineNo', sample.engineNo);
            setValue('#mileage', sample.mileage);
            setValue('#odo', sample.odo);
            setValue('#category', sample.category);
            updateSubCategory(sample.subCategory);
            setValue('#driver', sample.driver);
            setValue('#rent', sample.rent);
            setValue('#notes', sample.notes || '');
            setUsage(sample.usage);
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            sample.fuels.forEach(addFuelRow);
            sample.docs.forEach(addDocRow);
            toast('Sample vehicle data loaded.');
        }

        function editVehicle(id) {
            const vehicle = vehicles.find((item) => item.id === id);
            if (!vehicle) return;
            resetForm(false);
            setValue('#vehicleId', vehicle.id);
            setValue('#vehicleName', vehicle.name);
            setValue('#regNo', vehicle.regNo);
            setValue('#vendor', vehicle.vendor);
            setValue('#model', vehicle.model);
            setValue('#color', vehicle.color);
            setValue('#engineNo', vehicle.engineNo);
            setValue('#mileage', vehicle.mileage);
            setValue('#odo', vehicle.odo);
            setValue('#category', vehicle.category);
            updateSubCategory(vehicle.subCategory);
            setValue('#driver', vehicle.driver);
            setValue('#rent', vehicle.rent);
            setValue('#notes', vehicle.notes || '');
            setUsage(vehicle.usage);
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            vehicle.fuels.forEach(addFuelRow);
            vehicle.docs.forEach(addDocRow);
            setVisible('vehicleAddPage');
        }

        function deleteVehicle(id) {
            if (!confirm('Delete this vehicle from the list?')) return;
            vehicles = vehicles.filter((vehicle) => vehicle.id !== id);
            saveStore();
            renderTable();
            toast('Vehicle deleted.');
        }

        function renderTable() {
            const query = value('#vehicleSearch').toLowerCase();
            const category = value('#vehicleFilterCategory');
            const fuel = value('#vehicleFilterFuel');
            const status = value('#vehicleFilterStatus');
            const rows = vehicles.filter((vehicle) => {
                const text = [vehicle.name, vehicle.regNo, vehicle.driver, vehicle.id, vehicle.model].join(' ').toLowerCase();
                return (!query || text.includes(query))
                    && (!category || vehicle.category === category)
                    && (!fuel || (vehicle.fuels || []).some((item) => item.type === fuel))
                    && (!status || vehicle.status === status);
            });

            const body = $('#vehicleTbody');
            if (!body) return;
            body.innerHTML = rows.length ? rows.map((vehicle) => `
                <tr>
                    <td><div class="vehicle-cell"><div class="vehicle-icon">🚗</div><div><b>${escapeHtml(vehicle.name)}</b><br><small>${escapeHtml(vehicle.id)} · ${escapeHtml(vehicle.model)}</small></div></div></td>
                    <td>${escapeHtml(vehicle.regNo)}</td>
                    <td>${escapeHtml(vehicle.category)}<br><small>${escapeHtml(vehicle.subCategory || '')}</small></td>
                    <td>${(vehicle.fuels || []).map((item) => `<span class="badge soft">${escapeHtml(item.priority)}: ${escapeHtml(item.type)}</span>`).join('')}</td>
                    <td>${escapeHtml(vehicle.driver || 'Not assigned')}</td>
                    <td>${(vehicle.docs || []).length} document(s)<br><small>${escapeHtml((vehicle.docs || []).map((doc) => doc.name).join(', '))}</small></td>
                    <td>${Number(vehicle.rent || 0).toLocaleString()} BDT</td>
                    <td><span class="badge ${vehicle.status === 'Active' ? 'ok' : 'warn'}">${escapeHtml(vehicle.status || '-')}</span></td>
                    <td><button type="button" class="mini-btn edit-vehicle" data-id="${escapeHtml(vehicle.id)}">Edit</button><button type="button" class="mini-btn danger delete-vehicle" data-id="${escapeHtml(vehicle.id)}">Delete</button></td>
                </tr>`).join('') : '<tr><td colspan="9" class="empty">No vehicles found.</td></tr>';

            $('#vehicleKpiTotal').textContent = vehicles.length;
            $('#vehicleKpiActive').textContent = vehicles.filter((vehicle) => vehicle.status === 'Active').length;
            $('#vehicleKpiDocs').textContent = vehicles.filter((vehicle) => String(vehicle.status || '').includes('document')).length;
            $('#vehicleKpiFuel').textContent = vehicles.filter((vehicle) => (vehicle.fuels || []).length > 1).length;
        }

        function exportCsv() {
            downloadCsv('fleetman-vehicle-list.csv', [
                ['Vehicle ID', 'Vehicle Name', 'Registration', 'Category', 'Fuels', 'Driver', 'Documents', 'Status'],
                ...vehicles.map((vehicle) => [
                    vehicle.id,
                    vehicle.name,
                    vehicle.regNo,
                    vehicle.category,
                    (vehicle.fuels || []).map((fuel) => fuel.priority + ' ' + fuel.type).join(' | '),
                    vehicle.driver,
                    (vehicle.docs || []).map((doc) => doc.name + ' ' + doc.expiry).join(' | '),
                    vehicle.status,
                ]),
            ]);
        }

        $('#category')?.addEventListener('change', () => updateSubCategory(''));
        $('#addFuelRowBtn')?.addEventListener('click', () => addFuelRow());
        $('#addDocRowBtn')?.addEventListener('click', () => addDocRow());
        $('#clearVehicleBtn')?.addEventListener('click', () => resetForm());
        $('#saveVehicleBtn')?.addEventListener('click', saveVehicle);
        $('#loadVehicleSampleBtn')?.addEventListener('click', loadSample);
        $('#newVehicleBtn')?.addEventListener('click', () => { resetForm(); setVisible('vehicleAddPage'); });
        $('#exportVehiclesBtn')?.addEventListener('click', exportCsv);
        $('#clearVehicleFiltersBtn')?.addEventListener('click', () => { ['#vehicleSearch', '#vehicleFilterCategory', '#vehicleFilterFuel', '#vehicleFilterStatus'].forEach((selector) => setValue(selector, '')); renderTable(); });
        ['#vehicleSearch', '#vehicleFilterCategory', '#vehicleFilterFuel', '#vehicleFilterStatus'].forEach((selector) => $(selector)?.addEventListener('input', renderTable));
        document.addEventListener('click', (event) => {
            const pageTarget = event.target.closest('[data-page-target]');
            if (pageTarget) { renderTable(); setVisible(pageTarget.dataset.pageTarget); }
            const remove = event.target.closest('.remove-row');
            if (remove) remove.parentElement.remove();
            const edit = event.target.closest('.edit-vehicle');
            if (edit) editVehicle(edit.dataset.id);
            const del = event.target.closest('.delete-vehicle');
            if (del) deleteVehicle(del.dataset.id);
        });

        saveStore();
        resetForm();
        renderTable();
        setVisible('vehicleAddPage');
    }

    function initFuelPrices() {
        const STORAGE = 'fleetman_fuel_prices_v2';
        let prices = JSON.parse(localStorage.getItem(STORAGE) || 'null') || (samples.fuel_prices || []);

        function saveStore() {
            localStorage.setItem(STORAGE, JSON.stringify(prices));
        }

        function genId() {
            return 'FPR' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function resetForm() {
            $$('#fuelPriceAddPage input, #fuelPriceAddPage select, #fuelPriceAddPage textarea').forEach((field) => { field.value = ''; });
            setValue('#fuelPriceId', genId());
            setValue('#fuelStatus', 'Active');
            setValue('#fuelUnit', 'Per Liter');
            setValue('#effectiveDate', new Date().toISOString().slice(0, 10));
        }

        function collect(statusOverride) {
            return {
                fuelPriceId: value('#fuelPriceId'),
                fuelType: value('#fuelType'),
                name: value('#fuelName').trim(),
                price: value('#fuelPrice'),
                unit: value('#fuelUnit'),
                effectiveDate: value('#effectiveDate'),
                reference: value('#fuelReference').trim(),
                status: statusOverride || value('#fuelStatus'),
                remarks: value('#fuelRemarks').trim(),
            };
        }

        function upsert(row) {
            const index = prices.findIndex((item) => item.fuelPriceId === row.fuelPriceId);
            if (index >= 0) prices[index] = row;
            else prices.unshift(row);
            saveStore();
        }

        function saveFuelPrice(statusOverride) {
            const row = collect(statusOverride);
            if (row.status === 'Draft') {
                if (!row.fuelType) row.fuelType = 'Other';
                if (!row.name) row.name = 'Draft Fuel Price';
            }
            if (!row.fuelType || !row.name || !row.price || !row.status || !row.effectiveDate) {
                toast('Please fill the required fuel price information.');
                return;
            }
            upsert(row);
            renderList();
            toast(row.status === 'Draft' ? 'Draft saved.' : 'Fuel price saved.');
            setVisible('fuelPriceListPage');
        }

        function editFuelPrice(id) {
            const row = prices.find((item) => item.fuelPriceId === id);
            if (!row) return;
            resetForm();
            setValue('#fuelPriceId', row.fuelPriceId);
            setValue('#fuelType', row.fuelType);
            setValue('#fuelName', row.name);
            setValue('#fuelPrice', row.price);
            setValue('#fuelUnit', row.unit);
            setValue('#effectiveDate', row.effectiveDate);
            setValue('#fuelReference', row.reference);
            setValue('#fuelStatus', row.status);
            setValue('#fuelRemarks', row.remarks);
            setVisible('fuelPriceAddPage');
        }

        function deleteFuelPrice(id) {
            if (!confirm('Delete this fuel price from the list?')) return;
            prices = prices.filter((row) => row.fuelPriceId !== id);
            saveStore();
            renderList();
            toast('Fuel price deleted.');
        }

        function loadSample() {
            const sample = (samples.fuel_prices || [])[0];
            if (!sample) return;
            resetForm();
            setValue('#fuelPriceId', sample.fuelPriceId);
            setValue('#fuelType', sample.fuelType);
            setValue('#fuelName', sample.name);
            setValue('#fuelPrice', sample.price);
            setValue('#fuelUnit', sample.unit);
            setValue('#effectiveDate', sample.effectiveDate);
            setValue('#fuelReference', sample.reference);
            setValue('#fuelStatus', sample.status);
            setValue('#fuelRemarks', sample.remarks);
            toast('Sample fuel price data loaded.');
        }

        function renderList() {
            const query = value('#fuelPriceSearch').toLowerCase();
            const fuelType = value('#fuelPriceFilterFuel');
            const status = value('#fuelPriceFilterStatus');
            const unit = value('#fuelPriceFilterUnit');
            const rows = prices.filter((row) => {
                const text = [row.fuelPriceId, row.fuelType, row.name, row.reference].join(' ').toLowerCase();
                return (!query || text.includes(query)) && (!fuelType || row.fuelType === fuelType) && (!status || row.status === status) && (!unit || row.unit === unit);
            });

            const body = $('#fuelPriceTbody');
            if (!body) return;
            body.innerHTML = rows.length ? rows.map((row) => {
                const statusClass = row.status === 'Active' ? 'ok' : row.status === 'Inactive' ? 'danger' : 'soft';
                return `<tr>
                    <td><div class="fuel-cell"><div class="fuel-icon">⛽</div><div><b>${escapeHtml(row.name)}</b><br><small>${escapeHtml(row.fuelPriceId)}</small></div></div></td>
                    <td>${escapeHtml(row.fuelType || '-')}</td>
                    <td>${Number(row.price || 0).toLocaleString()}<br><small>${escapeHtml(row.remarks ? row.remarks.slice(0, 42) : '')}</small></td>
                    <td>${escapeHtml(row.unit || '-')}</td>
                    <td>${escapeHtml(row.effectiveDate || '-')}</td>
                    <td>${escapeHtml(row.reference || '-')}</td>
                    <td><span class="badge ${statusClass}">${escapeHtml(row.status || '-')}</span></td>
                    <td><button type="button" class="mini-btn view-fuel-price" data-id="${escapeHtml(row.fuelPriceId)}">View</button><button type="button" class="mini-btn edit-fuel-price" data-id="${escapeHtml(row.fuelPriceId)}">Edit</button><button type="button" class="mini-btn danger delete-fuel-price" data-id="${escapeHtml(row.fuelPriceId)}">Delete</button></td>
                </tr>`;
            }).join('') : '<tr><td colspan="8" class="empty">No fuel price found. Click “Add Fuel Price” to create one.</td></tr>';

            $('#fuelPriceKpiTotal').textContent = prices.length;
            $('#fuelPriceKpiActive').textContent = prices.filter((row) => row.status === 'Active').length;
            $('#fuelPriceKpiTypes').textContent = new Set(prices.map((row) => row.fuelType).filter(Boolean)).size;
            $('#fuelPriceKpiLatest').textContent = prices.map((row) => row.effectiveDate).filter(Boolean).sort().reverse()[0] || '-';
        }

        function exportCsv() {
            downloadCsv('fleetman-fuel-price-list.csv', [
                ['Fuel Price ID', 'Fuel Type', 'Name', 'Price', 'Unit', 'Effective Date', 'Reference', 'Status', 'Remarks'],
                ...prices.map((row) => [row.fuelPriceId, row.fuelType, row.name, row.price, row.unit, row.effectiveDate, row.reference, row.status, row.remarks]),
            ]);
        }

        $('#resetFuelPriceBtn')?.addEventListener('click', resetForm);
        $('#saveFuelPriceBtn')?.addEventListener('click', () => saveFuelPrice());
        $('#saveFuelPriceDraftBtn')?.addEventListener('click', () => saveFuelPrice('Draft'));
        $('#loadFuelPriceSampleBtn')?.addEventListener('click', loadSample);
        $('#newFuelPriceBtn')?.addEventListener('click', () => { resetForm(); setVisible('fuelPriceAddPage'); });
        $('#exportFuelPricesBtn')?.addEventListener('click', exportCsv);
        $('#applyFuelPriceFiltersBtn')?.addEventListener('click', renderList);
        $('#clearFuelPriceFiltersBtn')?.addEventListener('click', () => { ['#fuelPriceSearch', '#fuelPriceFilterFuel', '#fuelPriceFilterStatus', '#fuelPriceFilterUnit'].forEach((selector) => setValue(selector, '')); renderList(); });
        ['#fuelPriceSearch', '#fuelPriceFilterFuel', '#fuelPriceFilterStatus', '#fuelPriceFilterUnit'].forEach((selector) => $(selector)?.addEventListener('input', renderList));
        document.addEventListener('click', (event) => {
            const pageTarget = event.target.closest('[data-page-target]');
            if (pageTarget) { renderList(); setVisible(pageTarget.dataset.pageTarget); }
            const view = event.target.closest('.view-fuel-price');
            if (view) {
                const row = prices.find((item) => item.fuelPriceId === view.dataset.id);
                if (row) alert(`${row.name}\nFuel Type: ${row.fuelType}\nPrice: ${row.price} ${row.unit}\nEffective Date: ${row.effectiveDate}\nStatus: ${row.status}\nReference: ${row.reference || '-'}`);
            }
            const edit = event.target.closest('.edit-fuel-price');
            if (edit) editFuelPrice(edit.dataset.id);
            const del = event.target.closest('.delete-fuel-price');
            if (del) deleteFuelPrice(del.dataset.id);
        });

        saveStore();
        resetForm();
        renderList();
        setVisible('fuelPriceListPage');
    }

    function initFuelRecharge() {
        const contracts = data.contracts || [];
        const photoRequirements = data.photoRequirements || [];
        const photoState = {};
        photoRequirements.forEach((photo) => { photoState[photo.key] = { captured: false }; });

        function selectedContract() {
            return contracts.find((contract) => contract.id === value('#contractSelect'));
        }

        function selectedVehicle() {
            const contract = selectedContract();
            if (!contract) return null;
            return (contract.vehicles || []).find((vehicle) => vehicle.id === value('#vehicleSelect'));
        }

        function updateVehicles() {
            const contract = selectedContract();
            const select = $('#vehicleSelect');
            if (!select) return;
            select.innerHTML = '';
            if (!contract) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = '- Select vehicle -';
                select.appendChild(option);
                return;
            }
            (contract.vehicles || []).forEach((vehicle) => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = vehicle.name;
                select.appendChild(option);
            });
            updateVehicleSetup();
        }

        function updateVehicleSetup() {
            const vehicle = selectedVehicle();
            if (!vehicle) return;
            setValue('#primaryFuelName', vehicle.primary);
            setValue('#primaryRate', Number(vehicle.primaryRate || 0).toFixed(2));
            setValue('#secondaryFuelName', vehicle.secondary || 'Not set');
            setValue('#secondaryRate', Number(vehicle.secondaryRate || 0).toFixed(2));
            const toggle = $('#hasSecondaryFuel');
            if (toggle) {
                toggle.disabled = !vehicle.secondaryAvailable;
                if (!vehicle.secondaryAvailable) {
                    toggle.checked = false;
                    $('#secondaryFuelBlock').style.display = 'none';
                }
            }
            $('#vehicleSetupNote').textContent = vehicle.secondaryAvailable
                ? `Vehicle setup loaded: Main Fuel ${vehicle.primary}, Second Fuel ${vehicle.secondary} available.`
                : `Vehicle setup loaded: Main Fuel ${vehicle.primary}. No second fuel is configured for this vehicle.`;
            recalculate();
        }

        function recalculate() {
            const primaryAmount = Number(value('#primaryQty') || 0) * Number(value('#primaryRate') || 0);
            const hasSecondary = $('#hasSecondaryFuel')?.checked;
            const secondaryAmount = hasSecondary ? Number(value('#secondaryQty') || 0) * Number(value('#secondaryRate') || 0) : 0;
            setValue('#primaryAmount', money(primaryAmount));
            setValue('#secondaryAmount', money(secondaryAmount));
            $('#totalAmount').textContent = money(primaryAmount + secondaryAmount);
        }

        function updateCounter() {
            const requiredKeys = photoRequirements.filter((photo) => photo.required).map((photo) => photo.key);
            const done = requiredKeys.filter((key) => photoState[key]?.captured).length;
            const hasOptional = photoRequirements.some((photo) => !photo.required && photoState[photo.key]?.captured);
            $('#photoCount').textContent = `${done} / ${requiredKeys.length} required${hasOptional ? ' + other' : ''}`;
        }

        function placeNameFromCoordinates(lat, lng) {
            if (lat >= 23.85 && lat <= 23.92 && lng >= 90.35 && lng <= 90.43) return 'Uttara, Dhaka';
            if (lat >= 23.72 && lat <= 23.82 && lng >= 90.36 && lng <= 90.43) return 'Banani / Mohakhali, Dhaka';
            if (lat >= 23.68 && lat <= 23.75 && lng >= 90.38 && lng <= 90.45) return 'Motijheel / Central Dhaka';
            if (lat >= 23.95 && lat <= 24.15 && lng >= 90.35 && lng <= 90.48) return 'Gazipur / Tongi Area';
            return 'Current GPS Location';
        }

        function setPlaceName(element) {
            element.textContent = 'Getting place name...';
            if (!navigator.geolocation) {
                element.textContent = 'Place name not supported in this browser';
                return;
            }
            navigator.geolocation.getCurrentPosition((position) => {
                element.textContent = placeNameFromCoordinates(position.coords.latitude, position.coords.longitude) + ' (GPS saved)';
            }, (error) => {
                element.textContent = 'Place unavailable: ' + error.message;
            }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
        }

        function renderPhotos() {
            const list = $('#photoList');
            if (!list) return;
            list.innerHTML = photoRequirements.map((photo) => `
                <div class="photo-card" data-key="${escapeHtml(photo.key)}" data-required="${photo.required ? 'yes' : 'no'}">
                    <div class="photo-card-header">
                        <div><b>${escapeHtml(photo.title)} ${photo.required ? '<span class="req">*</span>' : ''}</b><span>${escapeHtml(photo.description)}</span></div>
                        <div class="photo-pill ${photo.required ? '' : 'optional'}">${photo.required ? 'Pending' : 'Optional'}</div>
                    </div>
                    <div class="stage"><img alt="${escapeHtml(photo.title)} preview"><div class="stage-empty"><div class="icon">${escapeHtml(photo.icon)}</div>Tap below to take photo</div></div>
                    <div class="photo-actions">
                        <button class="btn primary take-btn" type="button">📷 Take Photo</button>
                        <button class="btn light retake-btn" type="button" disabled>Retake</button>
                        <button class="btn danger clear-btn" type="button" disabled>Clear</button>
                        <input type="file" accept="image/*" capture="environment">
                    </div>
                    <div class="photo-meta"><div class="meta-row"><small>Date & Time</small><b class="cap-time">Not captured yet</b></div><div class="meta-row"><small>Place</small><b class="cap-place">Not captured yet</b></div></div>
                </div>`).join('');
            bindPhotoEvents();
        }

        function bindPhotoEvents() {
            $$('.photo-card').forEach((card) => {
                const key = card.dataset.key;
                const input = $('input[type=file]', card);
                const take = $('.take-btn', card);
                const retake = $('.retake-btn', card);
                const clear = $('.clear-btn', card);
                const img = $('img', card);
                const empty = $('.stage-empty', card);
                const pill = $('.photo-pill', card);
                const time = $('.cap-time', card);
                const place = $('.cap-place', card);

                take.addEventListener('click', () => input.click());
                retake.addEventListener('click', () => input.click());
                input.addEventListener('change', (event) => {
                    const file = event.target.files && event.target.files[0];
                    if (!file) return;
                    if (!file.type.startsWith('image/')) {
                        toast('Only photo capture is allowed.');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (readerEvent) => {
                        img.src = readerEvent.target.result;
                        img.style.display = 'block';
                        empty.style.display = 'none';
                        photoState[key].captured = true;
                        pill.textContent = 'Captured';
                        pill.classList.remove('optional');
                        pill.classList.add('done');
                        retake.disabled = false;
                        clear.disabled = false;
                        time.textContent = new Date().toLocaleString();
                        setPlaceName(place);
                        updateCounter();
                        toast('Photo captured. Time and place name are being saved.');
                    };
                    reader.readAsDataURL(file);
                });
                clear.addEventListener('click', () => {
                    input.value = '';
                    img.removeAttribute('src');
                    img.style.display = 'none';
                    empty.style.display = 'block';
                    photoState[key].captured = false;
                    pill.textContent = card.dataset.required === 'yes' ? 'Pending' : 'Optional';
                    pill.classList.remove('done');
                    if (card.dataset.required !== 'yes') pill.classList.add('optional');
                    retake.disabled = true;
                    clear.disabled = true;
                    time.textContent = 'Not captured yet';
                    place.textContent = 'Not captured yet';
                    updateCounter();
                });
            });
        }

        function submitRecharge() {
            if (!value('#contractSelect') || !value('#vehicleSelect')) {
                toast('Please select contract and vehicle.');
                return;
            }
            const missingRequiredPhoto = photoRequirements.some((photo) => photo.required && !photoState[photo.key]?.captured);
            if (missingRequiredPhoto) {
                toast('Please take all required photos: Vehicle, Fuel/Dispenser, and ODO Meter.');
                return;
            }
            if (Number(value('#primaryQty') || 0) <= 0) {
                toast('Please enter main fuel quantity.');
                return;
            }
            if ($('#hasSecondaryFuel')?.checked && Number(value('#secondaryQty') || 0) <= 0) {
                toast('Second fuel is selected. Please enter second fuel quantity.');
                return;
            }
            if (Number(value('#odoReading') || 0) <= 0) {
                toast('Please enter ODO meter reading.');
                return;
            }
            $('#submitTime').textContent = new Date().toLocaleString();
            const submitPlace = $('#submitPlace');
            const submitDetail = $('#submitPlaceDetail');
            submitPlace.textContent = 'Getting place name...';
            if (!navigator.geolocation) {
                submitPlace.textContent = 'Place not supported';
                submitDetail.textContent = 'Browser GPS is not available.';
                toast('Submitted. Place name could not be captured in this browser.');
                return;
            }
            navigator.geolocation.getCurrentPosition((position) => {
                submitPlace.textContent = placeNameFromCoordinates(position.coords.latitude, position.coords.longitude);
                submitDetail.textContent = 'GPS saved in backend. Operator sees place name.';
                toast('Fuel recharge submitted successfully.');
            }, (error) => {
                submitPlace.textContent = 'Place unavailable';
                submitDetail.textContent = error.message;
                toast('Submitted, but place name was not available.');
            }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
        }

        $('#contractSelect')?.addEventListener('change', updateVehicles);
        $('#vehicleSelect')?.addEventListener('change', updateVehicleSetup);
        $('#primaryQty')?.addEventListener('input', recalculate);
        $('#secondaryQty')?.addEventListener('input', recalculate);
        $('#recalculateFuelRechargeBtn')?.addEventListener('click', recalculate);
        $('#hasSecondaryFuel')?.addEventListener('change', function () {
            const vehicle = selectedVehicle();
            if (vehicle && !vehicle.secondaryAvailable) {
                this.checked = false;
                toast('Second fuel is not configured for this vehicle.');
                return;
            }
            $('#secondaryFuelBlock').style.display = this.checked ? 'block' : 'none';
            if (!this.checked) setValue('#secondaryQty', 0);
            recalculate();
        });
        $('#draftRechargeBtn')?.addEventListener('click', () => toast('Draft saved for prototype demo.'));
        $('#submitRechargeBtn')?.addEventListener('click', submitRecharge);

        renderPhotos();
        updateVehicles();
        updateCounter();
        recalculate();
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindMobileDrawer();
        const page = document.body.dataset.page;
        if (page === 'vehicles') initVehicles();
        if (page === 'fuel-prices') initFuelPrices();
        if (page === 'fuel-recharge') initFuelRecharge();
    });
})();

/* Vendor / Party and Large List Trip page logic. Kept separate so existing FleetMan pages stay untouched. */
(() => {
    const data = window.FLEETMAN || {};
    const options = data.options || {};
    const samples = data.samples || {};
    const tripMasters = data.tripMasters || { vehicles: [], drivers: [] };

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const value = (selector) => $(selector)?.value || '';
    const setValue = (selector, val) => { const element = $(selector); if (element) element.value = val ?? ''; };
    const escapeHtml = (input) => String(input ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
    const money = (amount) => '৳ ' + Number(amount || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(message) {
        const node = $('#toast');
        if (!node) return;
        node.textContent = message;
        node.classList.add('show');
        setTimeout(() => node.classList.remove('show'), 2600);
    }

    function setVisible(pageId) {
        const page = document.body.dataset.page;
        let ids = [];
        if (page === 'vendors') ids = ['vendorAddPage', 'vendorListPage'];
        if (page === 'trips') ids = ['tripAddPage', 'tripListPage'];
        ids.forEach((id) => {
            const element = document.getElementById(id);
            if (element) element.classList.toggle('hidden', id !== pageId);
        });
        window.scrollTo(0, 0);
    }

    function exportCsv(rows, filename) {
        const csv = rows.map((row) => row.map((value) => `"${String(value || '').replaceAll('"', '""')}"`).join(',')).join('\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
        link.download = filename;
        link.click();
    }

    function bindPageTargets() {
        document.addEventListener('click', (event) => {
            const target = event.target.closest('[data-page-target]');
            if (!target) return;
            setVisible(target.dataset.pageTarget);
        });
    }

    function initVendors() {
        const STORAGE = 'fleetman_parties_v2';
        let parties = JSON.parse(localStorage.getItem(STORAGE) || 'null') || (samples.parties || []);
        const partyDocumentTemplates = options.party_document_templates || [];

        function saveStore() {
            localStorage.setItem(STORAGE, JSON.stringify(parties));
        }

        function genId() {
            return 'VND' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function addContact(row = {}) {
            const wrapper = $('#partyContacts');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row contact-row';
            const meta = row.email || row.whatsapp || '';
            div.innerHTML = `
                <div class="field"><label>Name <span class="req">*</span></label><input class="partyContactName" placeholder="Example: Md. Karim" value="${escapeHtml(row.name || '')}"></div>
                <div class="field"><label>Role</label><input class="partyContactRole" placeholder="Example: Manager" value="${escapeHtml(row.role || '')}"></div>
                <div class="field"><label>Phone Number <span class="req">*</span></label><input class="partyContactPhone" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone || '')}"></div>
                <div class="field"><label>Email / WhatsApp</label><input class="partyContactMeta" placeholder="Email or WhatsApp" value="${escapeHtml(meta)}"></div>
                <button type="button" class="mini-btn danger remove-row">Remove</button>`;
            wrapper.appendChild(div);
        }

        function addDocument(row = {}) {
            const wrapper = $('#partyDocuments');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row document-row';
            const docOptions = [''].concat(partyDocumentTemplates);
            div.innerHTML = `
                <div class="field"><label>Document Name</label><select class="partyDocName">${docOptions.map((doc) => `<option value="${escapeHtml(doc)}" ${row.name === doc ? 'selected' : ''}>${escapeHtml(doc || 'Select document')}</option>`).join('')}</select></div>
                <div class="field"><label>Reference No.</label><input class="partyDocNumber" placeholder="Optional" value="${escapeHtml(row.number || '')}"></div>
                <div class="field"><label>Expiry Date</label><input class="partyDocExpiry" type="date" value="${escapeHtml(row.expiry || '')}"></div>
                <button type="button" class="mini-btn danger remove-row">Remove</button>`;
            wrapper.appendChild(div);
        }

        function resetForm() {
            $$('#vendorAddPage input, #vendorAddPage select, #vendorAddPage textarea').forEach((element) => { element.value = ''; });
            setValue('#partyId', genId());
            setValue('#partyStatus', 'Active');
            setValue('#paymentTerms', 'Cash');
            $('#partyContacts').innerHTML = '';
            $('#partyDocuments').innerHTML = '';
            addContact();
            addDocument({ name: 'Trade License Copy' });
        }

        function collect(statusOverride) {
            const contacts = $$('.contact-row').map((row) => {
                const meta = $('.partyContactMeta', row)?.value.trim() || '';
                return {
                    name: $('.partyContactName', row)?.value.trim() || '',
                    role: $('.partyContactRole', row)?.value.trim() || '',
                    phone: $('.partyContactPhone', row)?.value.trim() || '',
                    email: meta.includes('@') ? meta : '',
                    whatsapp: meta.includes('@') ? '' : meta,
                };
            }).filter((contact) => contact.name || contact.role || contact.phone || contact.email || contact.whatsapp);
            const documents = $$('.document-row').map((row) => ({
                name: $('.partyDocName', row)?.value.trim() || '',
                number: $('.partyDocNumber', row)?.value.trim() || '',
                expiry: $('.partyDocExpiry', row)?.value || '',
            })).filter((doc) => doc.name || doc.number || doc.expiry);
            return {
                partyId: value('#partyId'),
                partyName: value('#partyName').trim(),
                partyType: value('#partyType'),
                status: statusOverride || value('#partyStatus'),
                phone: value('#partyPhone').trim(),
                email: value('#partyEmail').trim(),
                whatsapp: value('#partyWhatsapp').trim(),
                tradeLicense: value('#tradeLicense').trim(),
                tinBin: value('#tinBin').trim(),
                paymentTerms: value('#paymentTerms'),
                address: value('#partyAddress').trim(),
                about: value('#partyAbout').trim(),
                contacts,
                documents,
            };
        }

        function validate(party) {
            if (!party.partyName || !party.partyType || !party.phone || !party.address || !party.status) {
                toast('Please fill required vendor / party information.');
                return false;
            }
            if (!party.contacts.length || !party.contacts[0].name || !party.contacts[0].phone) {
                toast('Please add at least one contact person with name and phone.');
                return false;
            }
            return true;
        }

        function upsert(party) {
            const index = parties.findIndex((item) => item.partyId === party.partyId);
            if (index >= 0) parties[index] = party;
            else parties.unshift(party);
            saveStore();
        }

        function saveParty(statusOverride) {
            const party = collect(statusOverride);
            if (statusOverride === 'Draft') {
                if (!party.partyName) party.partyName = 'Draft Vendor / Party';
                if (!party.partyType) party.partyType = 'Other';
            } else if (!validate(party)) {
                return;
            }
            upsert(party);
            renderList();
            toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Vendor / Party saved.');
            setVisible('vendorListPage');
        }

        function loadSample() {
            const sample = (samples.parties || [])[0];
            if (!sample) return;
            resetForm();
            setValue('#partyId', sample.partyId);
            setValue('#partyName', sample.partyName);
            setValue('#partyType', sample.partyType);
            setValue('#partyStatus', sample.status);
            setValue('#partyPhone', sample.phone);
            setValue('#partyEmail', sample.email);
            setValue('#partyWhatsapp', sample.whatsapp);
            setValue('#tradeLicense', sample.tradeLicense);
            setValue('#tinBin', sample.tinBin);
            setValue('#paymentTerms', sample.paymentTerms);
            setValue('#partyAddress', sample.address);
            setValue('#partyAbout', sample.about);
            $('#partyContacts').innerHTML = '';
            (sample.contacts || []).forEach(addContact);
            $('#partyDocuments').innerHTML = '';
            (sample.documents || []).forEach(addDocument);
            toast('Sample vendor / party data loaded.');
        }

        function editParty(id) {
            const party = parties.find((item) => item.partyId === id);
            if (!party) return;
            resetForm();
            setValue('#partyId', party.partyId);
            setValue('#partyName', party.partyName);
            setValue('#partyType', party.partyType);
            setValue('#partyStatus', party.status);
            setValue('#partyPhone', party.phone);
            setValue('#partyEmail', party.email);
            setValue('#partyWhatsapp', party.whatsapp);
            setValue('#tradeLicense', party.tradeLicense);
            setValue('#tinBin', party.tinBin);
            setValue('#paymentTerms', party.paymentTerms);
            setValue('#partyAddress', party.address);
            setValue('#partyAbout', party.about);
            $('#partyContacts').innerHTML = '';
            (party.contacts || []).forEach(addContact);
            $('#partyDocuments').innerHTML = '';
            (party.documents || []).forEach(addDocument);
            setVisible('vendorAddPage');
        }

        function deleteParty(id) {
            if (!confirm('Delete this vendor / party from prototype list?')) return;
            parties = parties.filter((party) => party.partyId !== id);
            saveStore();
            renderList();
            toast('Vendor / Party deleted.');
        }

        function viewParty(id) {
            const party = parties.find((item) => item.partyId === id);
            if (!party) return;
            alert(`${party.partyName}\nType: ${party.partyType}\nPhone: ${party.phone}\nStatus: ${party.status}\nPayment Terms: ${party.paymentTerms}\nContacts: ${(party.contacts || []).map((contact) => `${contact.name} (${contact.phone || '-'})`).join(', ')}`);
        }

        function rowHtml(party) {
            const main = (party.contacts || [])[0] || {};
            const cls = party.status === 'Active' ? 'ok' : party.status === 'Blacklisted' ? 'danger' : party.status === 'Draft' ? 'soft' : 'warn';
            return `<tr>
                <td><div class="party-cell"><div class="party-icon">🤝</div><div><b>${escapeHtml(party.partyName)}</b><br><small>${escapeHtml(party.partyId)}</small></div></div></td>
                <td><span class="badge soft">${escapeHtml(party.partyType || '-')}</span></td>
                <td>${escapeHtml(party.phone || '-')}<br><small>${escapeHtml(party.email || '')}</small></td>
                <td><b>${escapeHtml(main.name || '-')}</b><br><small>${escapeHtml(main.phone || '')}${(party.contacts || []).length > 1 ? ` · +${(party.contacts || []).length - 1} more` : ''}</small></td>
                <td>${escapeHtml(party.paymentTerms || '-')}</td>
                <td>${(party.documents || []).length} document(s)</td>
                <td><span class="badge ${cls}">${escapeHtml(party.status || '-')}</span></td>
                <td>${escapeHtml(party.address || '-')}</td>
                <td><button type="button" class="mini-btn view-party" data-id="${escapeHtml(party.partyId)}">View</button><button type="button" class="mini-btn edit-party" data-id="${escapeHtml(party.partyId)}">Edit</button><button type="button" class="mini-btn danger delete-party" data-id="${escapeHtml(party.partyId)}">Delete</button></td>
            </tr>`;
        }

        function renderList() {
            const query = value('#partySearch').toLowerCase();
            const type = value('#partyFilterType');
            const status = value('#partyFilterStatus');
            const terms = value('#partyFilterTerms');
            const list = parties.filter((party) => {
                const contactText = (party.contacts || []).map((contact) => [contact.name, contact.phone, contact.role, contact.email, contact.whatsapp].join(' ')).join(' ');
                return (!query || [party.partyId, party.partyName, party.phone, party.email, party.tradeLicense, contactText].join(' ').toLowerCase().includes(query))
                    && (!type || party.partyType === type)
                    && (!status || party.status === status)
                    && (!terms || party.paymentTerms === terms);
            });
            $('#partyTbody').innerHTML = list.length ? list.map(rowHtml).join('') : '<tr><td colspan="9" class="empty">No vendor / party found. Click “Add Vendor / Party” to create one.</td></tr>';
            $('#partyKpiTotal').textContent = parties.length;
            $('#partyKpiActive').textContent = parties.filter((party) => party.status === 'Active').length;
            $('#partyKpiTypes').textContent = new Set(parties.map((party) => party.partyType).filter(Boolean)).size;
            $('#partyKpiContacts').textContent = parties.reduce((sum, party) => sum + (party.contacts || []).length, 0);
        }

        function clearFilters() {
            setValue('#partySearch', '');
            setValue('#partyFilterType', '');
            setValue('#partyFilterStatus', '');
            setValue('#partyFilterTerms', '');
            renderList();
        }

        function exportParties() {
            const rows = [['Party ID', 'Party Name', 'Party Type', 'Status', 'Phone', 'Email', 'WhatsApp', 'Trade License', 'TIN/BIN', 'Payment Terms', 'Address', 'About', 'Contacts', 'Documents']];
            parties.forEach((party) => rows.push([
                party.partyId, party.partyName, party.partyType, party.status, party.phone, party.email, party.whatsapp, party.tradeLicense, party.tinBin, party.paymentTerms, party.address, party.about,
                (party.contacts || []).map((contact) => `${contact.name} / ${contact.role || ''} / ${contact.phone || ''}`).join('; '),
                (party.documents || []).map((doc) => `${doc.name} / ${doc.number || ''} / ${doc.expiry || ''}`).join('; '),
            ]));
            exportCsv(rows, 'fleetman-vendor-party-list.csv');
        }

        $('#addPartyContactBtn')?.addEventListener('click', () => addContact());
        $('#addPartyDocumentBtn')?.addEventListener('click', () => addDocument());
        $('#resetPartyBtn')?.addEventListener('click', resetForm);
        $('#savePartyBtn')?.addEventListener('click', () => saveParty());
        $('#savePartyDraftBtn')?.addEventListener('click', () => saveParty('Draft'));
        $('#loadPartySampleBtn')?.addEventListener('click', loadSample);
        $('#newPartyBtn')?.addEventListener('click', () => { resetForm(); setVisible('vendorAddPage'); });
        $('#exportPartiesBtn')?.addEventListener('click', exportParties);
        $('#applyPartyFiltersBtn')?.addEventListener('click', renderList);
        $('#clearPartyFiltersBtn')?.addEventListener('click', clearFilters);
        ['#partySearch', '#partyFilterType', '#partyFilterStatus', '#partyFilterTerms'].forEach((selector) => $(selector)?.addEventListener('input', renderList));
        document.addEventListener('click', (event) => {
            if (event.target.closest('.remove-row')) event.target.closest('.repeat-row')?.remove();
            const view = event.target.closest('.view-party');
            if (view) viewParty(view.dataset.id);
            const edit = event.target.closest('.edit-party');
            if (edit) editParty(edit.dataset.id);
            const del = event.target.closest('.delete-party');
            if (del) deleteParty(del.dataset.id);
        });

        saveStore();
        resetForm();
        renderList();
        setVisible('vendorListPage');
    }

    function initTrips() {
        const STORAGE = 'fleetman_trips_v4';
        const RECENT_KEY = 'fleetman_trip_selector_recent_v2';
        let trips = JSON.parse(localStorage.getItem(STORAGE) || 'null') || (samples.trips || []);
        const vehicles = tripMasters.vehicles || [];
        const drivers = tripMasters.drivers || [];
        const statusOptions = options.trip_statuses || [];
        const aroundOptions = options.trip_around || [];
        const periodOptions = options.trip_periods || [];
        const purposeOptions = options.trip_purposes || [];
        let selectedVehicle = '';
        let selectedDriver = '';
        let selectedStatus = statusOptions[0] || 'Initiated';
        let selectedAround = '';
        let selectedPeriod = '';
        let selectorType = 'vehicle';
        let selectorTab = 'recent';

        function saveStore() { localStorage.setItem(STORAGE, JSON.stringify(trips)); }
        function getRecent() { return JSON.parse(localStorage.getItem(RECENT_KEY) || '{"vehicle":[],"driver":[]}'); }
        function setRecent(recent) { localStorage.setItem(RECENT_KEY, JSON.stringify(recent)); }
        function pushRecent(type, selected) {
            if (!selected) return;
            const recent = getRecent();
            recent[type] = [selected, ...(recent[type] || []).filter((item) => item !== selected)].slice(0, 6);
            setRecent(recent);
            renderRecentChips();
        }
        function genId() { return 'TRP' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900); }
        function toNum(v) { return Number(v || 0); }

        function calculateTotal() {
            const total = toNum(value('#tripFuelCost')) + toNum(value('#tripFoodCost')) + toNum(value('#tripTolls')) + toNum(value('#tripOtherCost')) + toNum(value('#tripAccommodationCost'));
            setValue('#tripTotalCost', total.toFixed(2).replace(/\.00$/, ''));
            $('#tripSideTotal').textContent = Number(total).toLocaleString();
            return total;
        }

        function vehicleValue(item) { return `${item.id} - ${item.name}`; }
        function driverValue(item) { return `${item.id} - ${item.name}`; }
        function findVehicle(selected) { return vehicles.find((item) => vehicleValue(item) === selected); }
        function findDriver(selected) { return drivers.find((item) => driverValue(item) === selected); }

        function renderChoiceGroup(containerId, opts, selectedValue, callbackName) {
            const box = document.getElementById(containerId);
            if (!box) return;
            box.innerHTML = opts.map((opt) => `<button type="button" class="choice-btn ${selectedValue === opt ? 'active' : ''}" data-choice-callback="${callbackName}" data-value="${escapeHtml(opt)}">${escapeHtml(opt)}</button>`).join('');
        }
        function renderPurposeChoices(active) {
            const box = $('#tripPurposeChoices');
            if (!box) return;
            box.innerHTML = purposeOptions.map((opt) => `<button type="button" class="choice-btn ${active === opt ? 'active' : ''}" data-trip-purpose="${escapeHtml(opt)}">${escapeHtml(opt)}</button>`).join('');
        }
        function setStatus(value) { selectedStatus = value; renderChoiceGroup('tripStatusChoices', statusOptions, selectedStatus, 'status'); }
        function setAround(value) { selectedAround = value; renderChoiceGroup('tripAroundChoices', aroundOptions, selectedAround, 'around'); }
        function setPeriod(value) { selectedPeriod = value; renderChoiceGroup('tripPeriodChoices', periodOptions, selectedPeriod, 'period'); }

        function renderSelectionSummary() {
            const vehicle = findVehicle(selectedVehicle);
            const driver = findDriver(selectedDriver);
            $('#tripVehicleSummary').innerHTML = vehicle
                ? `<div><b>${escapeHtml(vehicleValue(vehicle))}</b><small>${escapeHtml(vehicle.type)} • ${escapeHtml(vehicle.note)}</small></div><span>✓</span>`
                : '<div><b>No vehicle selected</b><small>Tap the button to search and choose from many vehicles</small></div>';
            $('#tripDriverSummary').innerHTML = driver
                ? `<div><b>${escapeHtml(driverValue(driver))}</b><small>${escapeHtml(driver.phone)} • ${escapeHtml(driver.area)}</small></div><span>✓</span>`
                : '<div><b>No driver selected</b><small>Tap the button to search and choose from many drivers</small></div>';
        }
        function renderRecentChips() {
            const recent = getRecent();
            $('#recentTripVehicleChips').innerHTML = (recent.vehicle || []).map((item) => `<button type="button" class="quick-chip ${selectedVehicle === item ? 'active' : ''}" data-recent-type="vehicle" data-value="${escapeHtml(item)}">${escapeHtml(item)}</button>`).join('');
            $('#recentTripDriverChips').innerHTML = (recent.driver || []).map((item) => `<button type="button" class="quick-chip ${selectedDriver === item ? 'active' : ''}" data-recent-type="driver" data-value="${escapeHtml(item)}">${escapeHtml(item)}</button>`).join('');
        }

        function openSelector(type) {
            selectorType = type;
            selectorTab = 'recent';
            setValue('#tripSelectorSearch', '');
            $('#tripSelectorTitle').textContent = type === 'vehicle' ? 'Select Vehicle' : 'Select Driver';
            $('#tripSelectorSubtitle').textContent = type === 'vehicle' ? 'Search, filter, and choose from a large vehicle list' : 'Search, filter, and choose from a large driver list';
            const filter = $('#tripSelectorFilter');
            if (type === 'vehicle') {
                const types = [...new Set(vehicles.map((vehicle) => vehicle.type))];
                filter.innerHTML = '<option value="">All Types</option>' + types.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join('');
            } else {
                const areas = [...new Set(drivers.map((driver) => driver.area))];
                filter.innerHTML = '<option value="">All Areas</option>' + areas.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join('');
            }
            $('#tripSelectorOverlay').classList.add('show');
            setSelectorTab('recent');
            renderSelectorList();
        }
        function closeSelector() { $('#tripSelectorOverlay').classList.remove('show'); }
        function setSelectorTab(tab) {
            selectorTab = tab;
            $('#tripSelectorRecentTab').classList.toggle('active', tab === 'recent');
            $('#tripSelectorAllTab').classList.toggle('active', tab === 'all');
            renderSelectorList();
        }
        function getDataset() { return selectorType === 'vehicle' ? vehicles : drivers; }
        function getSelectedValue() { return selectorType === 'vehicle' ? selectedVehicle : selectedDriver; }
        function setSelectedValue(val) {
            if (selectorType === 'vehicle') {
                selectedVehicle = val;
                pushRecent('vehicle', val);
            } else {
                selectedDriver = val;
                pushRecent('driver', val);
            }
            renderSelectionSummary();
            renderRecentChips();
            renderSelectorList();
        }
        function clearSelectorChoice() {
            if (selectorType === 'vehicle') selectedVehicle = '';
            else selectedDriver = '';
            renderSelectionSummary();
            renderRecentChips();
            renderSelectorList();
        }
        function renderSelectorList() {
            const dataset = getDataset();
            const query = value('#tripSelectorSearch').toLowerCase();
            const filter = value('#tripSelectorFilter');
            const recent = getRecent()[selectorType] || [];
            let filtered = dataset.filter((item) => {
                const combined = selectorType === 'vehicle'
                    ? `${item.id} ${item.name} ${item.type} ${item.note}`
                    : `${item.id} ${item.name} ${item.phone} ${item.area}`;
                const filterOk = selectorType === 'vehicle' ? (!filter || item.type === filter) : (!filter || item.area === filter);
                return (!query || combined.toLowerCase().includes(query)) && filterOk;
            });
            if (selectorTab === 'recent') {
                filtered = filtered.filter((item) => recent.includes(selectorType === 'vehicle' ? vehicleValue(item) : driverValue(item)));
            }
            $('#tripSelectorStats').innerHTML = `<span class="stat-pill">${dataset.length} total ${selectorType}s</span><span class="stat-pill">${filtered.length} shown</span><span class="stat-pill">${recent.length} recent</span>`;
            const selectedVal = getSelectedValue();
            $('#tripSelectorList').innerHTML = filtered.length ? filtered.map((item) => {
                const val = selectorType === 'vehicle' ? vehicleValue(item) : driverValue(item);
                const meta = selectorType === 'vehicle' ? `${item.type} • ${item.note}` : `${item.phone} • ${item.area}`;
                const icon = selectorType === 'vehicle' ? '🚘' : '🧑‍✈️';
                return `<div class="list-row ${selectedVal === val ? 'active' : ''}" data-selector-value="${escapeHtml(val)}"><div class="icon">${icon}</div><div><b>${escapeHtml(val)}</b><small>${escapeHtml(meta)}</small></div><div class="radio-dot"></div></div>`;
            }).join('') : `<div class="empty">${selectorTab === 'recent' ? 'No recent items found. Switch to All or search.' : 'No matching results found.'}</div>`;
        }

        function resetForm() {
            $$('#tripAddPage input, #tripAddPage textarea').forEach((element) => { element.value = ''; });
            setValue('#tripId', genId());
            const today = new Date().toISOString().slice(0, 10);
            setValue('#tripStartDate', today);
            setValue('#tripEndDate', today);
            selectedVehicle = '';
            selectedDriver = '';
            selectedStatus = statusOptions[0] || 'Initiated';
            selectedAround = '';
            selectedPeriod = '';
            setStatus(selectedStatus);
            setAround(selectedAround);
            setPeriod(selectedPeriod);
            renderPurposeChoices('');
            renderSelectionSummary();
            renderRecentChips();
            calculateTotal();
        }

        function collect(statusOverride) {
            return {
                tripId: value('#tripId'),
                startDate: value('#tripStartDate'),
                endDate: value('#tripEndDate'),
                vehicle: selectedVehicle,
                driver: selectedDriver,
                status: statusOverride || selectedStatus,
                tripAround: selectedAround,
                tripPeriod: selectedPeriod,
                purpose: value('#tripPurpose').trim(),
                fromLocation: value('#tripFromLocation').trim(),
                toLocation: value('#tripToLocation').trim(),
                odoStart: value('#tripOdoStart'),
                odoEnd: value('#tripOdoEnd'),
                fuelCost: value('#tripFuelCost'),
                foodCost: value('#tripFoodCost'),
                tolls: value('#tripTolls'),
                otherCost: value('#tripOtherCost'),
                accommodationCost: value('#tripAccommodationCost'),
                totalCost: String(calculateTotal()),
                details: value('#tripDetails').trim(),
            };
        }
        function validate(trip) {
            if (!trip.tripId || !trip.startDate || !trip.endDate || !trip.vehicle || !trip.driver || !trip.status || !trip.tripAround || !trip.tripPeriod || !trip.odoStart || !trip.details) {
                toast('Please fill the required trip information.');
                return false;
            }
            if (trip.endDate < trip.startDate) {
                toast('End date cannot be earlier than start date.');
                return false;
            }
            return true;
        }
        function upsert(trip) {
            const index = trips.findIndex((item) => item.tripId === trip.tripId);
            if (index >= 0) trips[index] = trip;
            else trips.unshift(trip);
            saveStore();
        }
        function saveTrip(statusOverride) {
            const trip = collect(statusOverride);
            if (statusOverride === 'Draft') {
                if (!trip.vehicle) trip.vehicle = 'Pending vehicle';
                if (!trip.driver) trip.driver = 'Pending driver';
            } else if (!validate(trip)) {
                return;
            }
            if (trip.vehicle !== 'Pending vehicle') pushRecent('vehicle', trip.vehicle);
            if (trip.driver !== 'Pending driver') pushRecent('driver', trip.driver);
            upsert(trip);
            renderList();
            toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Trip saved.');
            setVisible('tripListPage');
        }
        function loadSample() {
            const sample = (samples.trips || [])[0];
            if (!sample) return;
            resetForm();
            setValue('#tripId', sample.tripId);
            setValue('#tripStartDate', sample.startDate);
            setValue('#tripEndDate', sample.endDate);
            selectedVehicle = sample.vehicle;
            selectedDriver = sample.driver;
            selectedStatus = sample.status;
            selectedAround = sample.tripAround;
            selectedPeriod = sample.tripPeriod;
            setValue('#tripPurpose', sample.purpose);
            setValue('#tripFromLocation', sample.fromLocation);
            setValue('#tripToLocation', sample.toLocation);
            setValue('#tripOdoStart', sample.odoStart);
            setValue('#tripOdoEnd', sample.odoEnd);
            setValue('#tripFuelCost', sample.fuelCost);
            setValue('#tripFoodCost', sample.foodCost);
            setValue('#tripTolls', sample.tolls);
            setValue('#tripOtherCost', sample.otherCost);
            setValue('#tripAccommodationCost', sample.accommodationCost);
            setValue('#tripDetails', sample.details);
            setStatus(selectedStatus);
            setAround(selectedAround);
            setPeriod(selectedPeriod);
            renderPurposeChoices(sample.purpose);
            pushRecent('vehicle', selectedVehicle);
            pushRecent('driver', selectedDriver);
            renderSelectionSummary();
            calculateTotal();
            toast('Sample trip data loaded.');
        }
        function editTrip(id) {
            const trip = trips.find((item) => item.tripId === id);
            if (!trip) return;
            resetForm();
            setValue('#tripId', trip.tripId);
            setValue('#tripStartDate', trip.startDate);
            setValue('#tripEndDate', trip.endDate);
            selectedVehicle = trip.vehicle;
            selectedDriver = trip.driver;
            selectedStatus = trip.status;
            selectedAround = trip.tripAround;
            selectedPeriod = trip.tripPeriod;
            setValue('#tripPurpose', trip.purpose);
            setValue('#tripFromLocation', trip.fromLocation);
            setValue('#tripToLocation', trip.toLocation);
            setValue('#tripOdoStart', trip.odoStart);
            setValue('#tripOdoEnd', trip.odoEnd);
            setValue('#tripFuelCost', trip.fuelCost);
            setValue('#tripFoodCost', trip.foodCost);
            setValue('#tripTolls', trip.tolls);
            setValue('#tripOtherCost', trip.otherCost);
            setValue('#tripAccommodationCost', trip.accommodationCost);
            setValue('#tripDetails', trip.details);
            setStatus(selectedStatus);
            setAround(selectedAround);
            setPeriod(selectedPeriod);
            renderPurposeChoices(trip.purpose);
            renderSelectionSummary();
            renderRecentChips();
            calculateTotal();
            setVisible('tripAddPage');
        }
        function deleteTrip(id) {
            if (!confirm('Delete this trip from prototype list?')) return;
            trips = trips.filter((trip) => trip.tripId !== id);
            saveStore();
            renderList();
            toast('Trip deleted.');
        }
        function viewTrip(id) {
            const trip = trips.find((item) => item.tripId === id);
            if (!trip) return;
            alert(`${trip.tripId}\nVehicle: ${trip.vehicle}\nDriver: ${trip.driver}\nDates: ${trip.startDate} to ${trip.endDate}\nRoute: ${trip.fromLocation || '-'} to ${trip.toLocation || '-'}\nStatus: ${trip.status}\nTotal Cost: ${trip.totalCost}`);
        }
        function rowHtml(trip) {
            const cls = trip.status === 'Completed' ? 'ok' : trip.status === 'Running' ? 'warn' : trip.status === 'Initiated' || trip.status === 'Draft' ? 'soft' : 'danger';
            return `<tr>
                <td><div class="trip-cell"><div class="trip-icon">🧭</div><div><b>${escapeHtml(trip.tripId)}</b><br><small>${escapeHtml(trip.purpose || 'Trip')}</small></div></div></td>
                <td>${escapeHtml(trip.startDate || '-')}<br><small>to ${escapeHtml(trip.endDate || '-')}</small></td>
                <td><b>${escapeHtml(trip.vehicle || '-')}</b><br><small>${escapeHtml(trip.driver || '-')}</small></td>
                <td>${escapeHtml(trip.fromLocation || '-')} → ${escapeHtml(trip.toLocation || '-')}<div class="chip-row" style="margin-top:6px"><span class="chip">${escapeHtml(trip.tripAround || '-')}</span><span class="chip">${escapeHtml(trip.tripPeriod || '-')}</span></div></td>
                <td>Start: ${escapeHtml(trip.odoStart || '-')}<br><small>End: ${escapeHtml(trip.odoEnd || '-')}</small></td>
                <td>${money(trip.totalCost || 0)}<br><small>Fuel ${Number(trip.fuelCost || 0).toLocaleString()} | Food ${Number(trip.foodCost || 0).toLocaleString()}</small></td>
                <td><span class="badge ${cls}">${escapeHtml(trip.status || '-')}</span></td>
                <td><button type="button" class="mini-btn view-trip" data-id="${escapeHtml(trip.tripId)}">View</button><button type="button" class="mini-btn edit-trip" data-id="${escapeHtml(trip.tripId)}">Edit</button><button type="button" class="mini-btn danger delete-trip" data-id="${escapeHtml(trip.tripId)}">Delete</button></td>
            </tr>`;
        }
        function renderList() {
            const query = value('#tripSearch').toLowerCase();
            const vehicleQuery = value('#tripVehicleSearch').toLowerCase();
            const status = value('#tripFilterStatus');
            const around = value('#tripFilterAround');
            const list = trips.filter((trip) => (!query || [trip.tripId, trip.vehicle, trip.driver, trip.fromLocation, trip.toLocation, trip.status, trip.purpose].join(' ').toLowerCase().includes(query))
                && (!vehicleQuery || String(trip.vehicle || '').toLowerCase().includes(vehicleQuery))
                && (!status || trip.status === status)
                && (!around || trip.tripAround === around));
            $('#tripTbody').innerHTML = list.length ? list.map(rowHtml).join('') : '<tr><td colspan="8" class="empty">No trip found. Click “Add Trip” to create one.</td></tr>';
            $('#tripKpiTotal').textContent = trips.length;
            $('#tripKpiRunning').textContent = trips.filter((trip) => trip.status === 'Running').length;
            $('#tripKpiCompleted').textContent = trips.filter((trip) => trip.status === 'Completed').length;
            $('#tripKpiCost').textContent = '৳ ' + trips.reduce((sum, trip) => sum + Number(trip.totalCost || 0), 0).toLocaleString();
        }
        function clearFilters() {
            setValue('#tripSearch', '');
            setValue('#tripVehicleSearch', '');
            setValue('#tripFilterStatus', '');
            setValue('#tripFilterAround', '');
            renderList();
        }
        function exportTrips() {
            const rows = [['Trip ID', 'Start Date', 'End Date', 'Vehicle', 'Driver', 'Status', 'Trip Around', 'Trip Period', 'Purpose', 'From Location', 'To Location', 'Odo Start', 'Odo End', 'Fuel Cost', 'Food Cost', 'Tolls', 'Other Cost', 'Accommodation Cost', 'Total Cost', 'Details']];
            trips.forEach((trip) => rows.push([trip.tripId, trip.startDate, trip.endDate, trip.vehicle, trip.driver, trip.status, trip.tripAround, trip.tripPeriod, trip.purpose, trip.fromLocation, trip.toLocation, trip.odoStart, trip.odoEnd, trip.fuelCost, trip.foodCost, trip.tolls, trip.otherCost, trip.accommodationCost, trip.totalCost, trip.details]));
            exportCsv(rows, 'fleetman-trip-list-large-list-ux.csv');
        }

        ['#tripFuelCost', '#tripFoodCost', '#tripTolls', '#tripOtherCost', '#tripAccommodationCost'].forEach((selector) => $(selector)?.addEventListener('input', calculateTotal));
        $('#selectTripVehicleBtn')?.addEventListener('click', () => openSelector('vehicle'));
        $('#selectTripDriverBtn')?.addEventListener('click', () => openSelector('driver'));
        $('#closeTripSelectorBtn')?.addEventListener('click', closeSelector);
        $('#doneTripSelectorBtn')?.addEventListener('click', closeSelector);
        $('#tripSelectorOverlay')?.addEventListener('click', (event) => { if (event.target.id === 'tripSelectorOverlay') closeSelector(); });
        $('#tripSelectorSearch')?.addEventListener('input', renderSelectorList);
        $('#tripSelectorFilter')?.addEventListener('change', renderSelectorList);
        $('#tripSelectorRecentTab')?.addEventListener('click', () => setSelectorTab('recent'));
        $('#tripSelectorAllTab')?.addEventListener('click', () => setSelectorTab('all'));
        $('#clearTripSelectorChoiceBtn')?.addEventListener('click', clearSelectorChoice);
        $('#resetTripBtn')?.addEventListener('click', resetForm);
        $('#saveTripBtn')?.addEventListener('click', () => saveTrip());
        $('#saveTripDraftBtn')?.addEventListener('click', () => saveTrip('Draft'));
        $('#loadTripSampleBtn')?.addEventListener('click', loadSample);
        $('#newTripBtn')?.addEventListener('click', () => { resetForm(); setVisible('tripAddPage'); });
        $('#exportTripsBtn')?.addEventListener('click', exportTrips);
        $('#applyTripFiltersBtn')?.addEventListener('click', renderList);
        $('#clearTripFiltersBtn')?.addEventListener('click', clearFilters);
        ['#tripSearch', '#tripVehicleSearch', '#tripFilterStatus', '#tripFilterAround'].forEach((selector) => $(selector)?.addEventListener('input', renderList));
        document.addEventListener('click', (event) => {
            const choice = event.target.closest('[data-choice-callback]');
            if (choice) {
                const choiceValue = choice.dataset.value;
                if (choice.dataset.choiceCallback === 'status') setStatus(choiceValue);
                if (choice.dataset.choiceCallback === 'around') setAround(choiceValue);
                if (choice.dataset.choiceCallback === 'period') setPeriod(choiceValue);
            }
            const purpose = event.target.closest('[data-trip-purpose]');
            if (purpose) {
                setValue('#tripPurpose', purpose.dataset.tripPurpose);
                renderPurposeChoices(purpose.dataset.tripPurpose);
            }
            const recent = event.target.closest('[data-recent-type]');
            if (recent) {
                if (recent.dataset.recentType === 'vehicle') selectedVehicle = recent.dataset.value;
                if (recent.dataset.recentType === 'driver') selectedDriver = recent.dataset.value;
                renderSelectionSummary();
                renderRecentChips();
            }
            const selected = event.target.closest('[data-selector-value]');
            if (selected) setSelectedValue(selected.dataset.selectorValue);
            const view = event.target.closest('.view-trip');
            if (view) viewTrip(view.dataset.id);
            const edit = event.target.closest('.edit-trip');
            if (edit) editTrip(edit.dataset.id);
            const del = event.target.closest('.delete-trip');
            if (del) deleteTrip(del.dataset.id);
        });

        saveStore();
        resetForm();
        renderList();
        setVisible('tripListPage');
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindPageTargets();
        const page = document.body.dataset.page;
        if (page === 'vendors') initVendors();
        if (page === 'trips') initTrips();
    });
})();
