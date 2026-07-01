(function () {
    'use strict';

    const data = window.FLEETMAN || {};
    const options = data.options || {};
    const samples = data.samples || {};
    const records = data.records || samples || {};
    const resources = data.resources || {};

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function syncResource(resource, rows) {
        if (window.FleetmanRecordApi && resources?.[resource]?.store) {
            return window.FleetmanRecordApi.persistCollection(resource, rows || []);
        }
        const endpoint = resources?.[resource]?.sync;
        if (!endpoint) return;

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ rows: rows || [] }),
        }).catch(() => {
            const element = document.getElementById('toast');
            if (element) {
                element.textContent = 'Saved locally in screen state, but database sync failed. Check server connection.';
                element.classList.add('show');
                setTimeout(() => element.classList.remove('show'), 3200);
            }
        });
    }

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

    function setVisible(pageId) {
        ['vehicleAddPage', 'vehicleListPage', 'fuelPriceAddPage', 'fuelPriceListPage', 'rechargeAddPage', 'rechargeListPage'].forEach((id) => {
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
        let vehicles = Array.isArray(records.vehicles) ? records.vehicles : (samples.vehicles || []);
        const vehicleCategories = options.vehicle_categories || {};
        const fuelTypes = options.fuel_types || [];
        const latestFuelRates = data.latestFuelRates || {};
        const fuelStations = Array.isArray(data.fuelStations) ? data.fuelStations : [];
        const docTemplates = options.document_templates || [];
        const docReminders = options.document_reminders || [];
        const documentSelects = window.FleetmanUniqueDocumentSelects;
        const uploadManager = window.FleetmanTemporaryUploads;

        function uid() {
            return 'VHL' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(1000 + Math.random() * 9000);
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

        function vehicleField(element) {
            return element?.closest('.field') || element;
        }

        function clearVehicleFieldError(element, customContainer = null) {
            const field = customContainer || vehicleField(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll(':scope > .field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function clearVehicleValidation() {
            const page = $('#vehicleAddPage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markVehicleInvalid(element, message, customContainer = null) {
            const field = customContainer || vehicleField(element);
            if (!field) return;
            clearVehicleFieldError(element, field);
            field.classList.add('field-invalid');
            element?.setAttribute?.('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'field-error';
            error.textContent = message;
            field.appendChild(error);
        }

        function focusFirstVehicleError() {
            const first = $('#vehicleAddPage .field-invalid');
            if (!first) return;
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => first.querySelector('input,select,textarea')?.focus?.({ preventScroll: true }), 250);
        }

        function recalculateRentalTotal() {
            const rentalType = value('#rentalType');
            const withDriver = rentalType === 'With Driver';
            const vehicleAmount = Math.max(0, Number(value('#vehicleRentalAmount') || 0));
            const driverOneAmount = withDriver ? Math.max(0, Number(value('#driverPaymentAmount') || 0)) : 0;
            const driverTwoAmount = withDriver && isDoubleShiftUsage()
                ? Math.max(0, Number(value('#secondDriverPaymentAmount') || 0))
                : 0;
            setValue('#totalRentalAmount', (vehicleAmount + driverOneAmount + driverTwoAmount).toFixed(2));
        }

        const vehicleDriverOptions = $$('#vehiclePrimaryDriverList option').map((option) => ({
            value: String(option.value || '').trim(),
            label: String(option.textContent || option.value || '').trim(),
        })).filter((option) => option.value);

        function isDoubleShiftUsage() {
            return getUsage().trim().toLowerCase() === 'double shift';
        }

        function renderVehicleDriverDatalist(selector, excludedValue = '') {
            const list = $(selector);
            if (!list) return;
            const excluded = String(excludedValue || '').trim().toLowerCase();
            list.innerHTML = vehicleDriverOptions
                .filter((option) => option.value.toLowerCase() !== excluded)
                .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
                .join('');
        }

        function refreshVehicleDriverOptions() {
            renderVehicleDriverDatalist('#vehiclePrimaryDriverList', value('#secondDriver'));
            renderVehicleDriverDatalist('#vehicleSecondaryDriverList', value('#driver'));
        }

        function toggleRentalFields() {
            const rentalType = value('#rentalType');
            const withDriver = rentalType === 'With Driver';
            const doubleShift = isDoubleShiftUsage();
            const secondDriverRequired = withDriver && doubleShift;
            const driver = $('#driver');
            const secondDriver = $('#secondDriver');
            const secondDriverField = $('#vehicleSecondaryDriverField');
            const primaryPaymentFields = $('#driverPaymentFields');
            const secondaryPaymentFields = $('#secondDriverPaymentFields');
            const primaryCardState = $('#vehiclePrimaryDriverCardState');
            const panel = $('#vehicleDriverAssignmentPanel');
            const badge = $('#vehicleDriverRequirementBadge');
            const requirementText = $('#vehicleDriverRequirementText');
            const requiredMark = $('#vehiclePrimaryDriverRequired');
            const optionalMark = $('#vehiclePrimaryDriverOptional');

            if (driver) {
                driver.required = withDriver;
                driver.setAttribute('aria-required', withDriver ? 'true' : 'false');
                driver.placeholder = withDriver
                    ? 'Type to search and select Driver 1'
                    : 'Type to search and select a driver (optional)';
            }

            requiredMark?.classList.toggle('hidden', !withDriver);
            optionalMark?.classList.toggle('hidden', withDriver);
            if (primaryCardState) {
                primaryCardState.textContent = withDriver ? 'Required' : 'Optional';
                primaryCardState.className = `badge ${withDriver ? 'warn' : 'soft'}`;
            }
            secondDriverField?.classList.toggle('hidden', !secondDriverRequired);
            if (secondDriver) {
                secondDriver.required = secondDriverRequired;
                secondDriver.setAttribute('aria-required', secondDriverRequired ? 'true' : 'false');
                if (!secondDriverRequired) {
                    secondDriver.value = '';
                    clearVehicleFieldError(secondDriver);
                }
            }

            panel?.classList.toggle('is-required', withDriver);
            panel?.classList.toggle('is-double', secondDriverRequired);

            if (!rentalType) {
                if (badge) badge.textContent = 'Not selected';
                if (requirementText) requirementText.textContent = 'Select Rental Type and Usage Type to see the driver requirement.';
            } else if (!withDriver) {
                if (badge) badge.textContent = '1 optional driver';
                if (requirementText) requirementText.textContent = 'Without Driver keeps one driver selection available, but it is optional.';
            } else if (secondDriverRequired) {
                if (badge) badge.textContent = '2 drivers required';
                if (requirementText) requirementText.textContent = 'Double shift with driver requires two different drivers.';
            } else {
                if (badge) badge.textContent = '1 driver required';
                if (requirementText) requirementText.textContent = 'With Driver requires Driver 1. Select Double shift to assign Driver 2 as well.';
            }

            primaryPaymentFields?.classList.toggle('hidden', !withDriver);
            secondaryPaymentFields?.classList.toggle('hidden', !secondDriverRequired);

            ['#driverPaymentAmount', '#driverPaymentCycle'].forEach((selector) => {
                const field = $(selector);
                if (!field) return;
                field.required = withDriver;
                field.disabled = !withDriver;
                field.setAttribute('aria-required', withDriver ? 'true' : 'false');
                if (!withDriver) {
                    field.value = '';
                    clearVehicleFieldError(field);
                }
            });

            ['#secondDriverPaymentAmount', '#secondDriverPaymentCycle'].forEach((selector) => {
                const field = $(selector);
                if (!field) return;
                field.required = secondDriverRequired;
                field.disabled = !secondDriverRequired;
                field.setAttribute('aria-required', secondDriverRequired ? 'true' : 'false');
                if (!secondDriverRequired) {
                    field.value = '';
                    clearVehicleFieldError(field);
                }
            });
            refreshVehicleDriverOptions();
            recalculateRentalTotal();
        }

        function normalizeFileData(row = {}) {
            if (row.file && typeof row.file === 'object') return row.file;
            if (row.filePath || row.fileUrl || row.originalName || row.fileName) {
                return {
                    filePath: row.filePath || '',
                    fileUrl: row.fileUrl || '',
                    fileName: row.fileName || '',
                    originalName: row.originalName || row.fileName || '',
                    mimeType: row.mimeType || '',
                    sizeBytes: row.sizeBytes || '',
                    uploadedAt: row.uploadedAt || '',
                };
            }
            return {};
        }

        function parseFileDataInput(selector, root = document) {
            const hidden = $(selector, root);
            if (!hidden?.value) return {};
            try {
                return JSON.parse(hidden.value) || {};
            } catch (_) {
                return {};
            }
        }

        function selectedVehicleImageFile() {
            return $('#image')?.files?.[0] || null;
        }

        function selectedDocFile(row) {
            return $('.docFile', row)?.files?.[0] || null;
        }

        function renderVehicleImageInfo(fileData = {}) {
            uploadManager.render({
                info: $('#vehicleImageUploadInfo'),
                progress: $('#vehicleImageProgress'),
                file: fileData,
                showPreview: true,
            });
        }

        function renderDocFileInfo(row, fileData = {}) {
            uploadManager.render({
                info: $('.docUploadInfo', row),
                progress: $('.docUploadProgress', row),
                file: fileData,
                showPreview: false,
            });
        }

        function activeRateFor(type) {
            return latestFuelRates[type] || null;
        }

        function updateFuelRate(row, force = false) {
            const type = $('.fuelType', row)?.value || '';
            const input = $('.fuelRate', row);
            const hint = $('.fuelRateHint', row);
            if (!input) return;

            if (!type) {
                if (force) input.value = '';
                if (hint) hint.textContent = '';
                return;
            }

            const rate = activeRateFor(type);
            if (!rate) {
                if (force) input.value = '';
                if (hint) hint.textContent = 'No active fuel price found for this fuel type.';
                return;
            }

            input.value = rate.price ?? '';
            if (hint) {
                const unit = rate.unit ? ` / ${rate.unit}` : '';
                const date = rate.effectiveDate ? ` Effective: ${rate.effectiveDate}.` : '';
                hint.textContent = `Loaded latest active rate${unit}.${date}`;
            }
        }

        const fuelPriorities = ['Primary', 'Secondary', 'Tertiary'];

        function addFuelRow(row = {}) {
            const wrapper = $('#vehicleFuelRows');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row fuel-row';
            const fuelOptions = [''].concat(fuelTypes);
            div.innerHTML = `
                <div class="field">
                    <label>Fuel Type <span class="req">*</span></label>
                    <select class="fuelType" required>${fuelOptions.map((type) => `<option value="${escapeHtml(type)}" ${row.type === type ? 'selected' : ''}>${escapeHtml(type || 'Select fuel type')}</option>`).join('')}</select>
                </div>
                <div class="field">
                    <label>Fuel Priority <span class="req">*</span></label>
                    <select class="fuelPriority" required>
                        <option value="">Select priority</option>
                        ${fuelPriorities.map((priority) => `<option value="${priority}" ${row.priority === priority ? 'selected' : ''}>${priority}</option>`).join('')}
                    </select>
                </div>
                <div class="field">
                    <label>Default Rate <span class="req">*</span></label>
                    <input class="fuelRate" type="number" placeholder="Auto from latest active fuel price" value="${escapeHtml(row.rate || '')}" readonly>
                    <small class="upload-meta fuelRateHint"></small>
                </div>
                <button type="button" class="mini-btn remove-row">Remove</button>`;
            wrapper.appendChild(div);
            if (wrapper.children.length === 1 && !row.priority) div.querySelector('.fuelPriority').value = 'Primary';
            if (row.type && !row.rate) updateFuelRate(div, true);
            if (row.type && row.rate) updateFuelRate(div, false);
            refreshVehicleFuelOptions();
            refreshVehicleFuelPriorityOptions();
        }

        function refreshVehicleFuelOptions() {
            documentSelects.refresh('#vehicleFuelRows', '.fuelType', fuelTypes, 'Select fuel type');
        }

        function refreshVehicleFuelPriorityOptions() {
            const rows = $$('#vehicleFuelRows .fuel-row');
            const claimed = new Set();

            rows.forEach((row) => {
                const select = $('.fuelPriority', row);
                if (!select) return;
                const current = String(select.value || '');
                if (current && claimed.has(current)) select.value = '';
                else if (current) claimed.add(current);
            });

            rows.forEach((row) => {
                const select = $('.fuelPriority', row);
                if (!select) return;
                const current = String(select.value || '');
                const selectedElsewhere = new Set(rows
                    .filter((otherRow) => otherRow !== row)
                    .map((otherRow) => String($('.fuelPriority', otherRow)?.value || ''))
                    .filter(Boolean));
                const available = fuelPriorities.filter((priority) => priority === current || !selectedElsewhere.has(priority));
                select.innerHTML = '<option value="">Select priority</option>'
                    + available.map((priority) => `<option value="${priority}">${priority}</option>`).join('');
                select.value = available.includes(current) ? current : '';
            });

            const addButton = $('#addFuelRowBtn');
            if (addButton) {
                addButton.disabled = rows.length >= fuelPriorities.length;
                addButton.title = addButton.disabled ? 'All fuel priorities are already used.' : '';
            }
        }

        function refreshVehicleDocumentOptions() {
            documentSelects.refresh('#vehicleDocRows', '.docName', docTemplates, 'Select document');
        }

        function addDocRow(row = {}) {
            const wrapper = $('#vehicleDocRows');
            if (!wrapper) return;
            const fileData = normalizeFileData(row.file || row);
            const rendered = window.FleetmanDocumentRows.create({
                row,
                fileData,
                rowClass: 'vehicle-document-row',
                names: docTemplates,
                reminders: docReminders,
                namePlaceholder: 'Select document',
                classes: {
                    name: 'docName', expiry: 'docExpiry', reminder: 'docReminder',
                    file: 'docFile', hidden: 'docFileData', progress: 'docUploadProgress', info: 'docUploadInfo'
                }
            });
            wrapper.appendChild(rendered.element);
            renderDocFileInfo(rendered.element, fileData);
            refreshVehicleDocumentOptions();
        }

        function validatePendingFiles(vehicleImageFile = null, documentFiles = {}) {
            if (vehicleImageFile) {
                const imageExt = String(vehicleImageFile.name || '').split('.').pop().toLowerCase();
                if (!['jpg', 'jpeg', 'png', 'webp'].includes(imageExt)) {
                    toast('Vehicle image must be JPG, JPEG, PNG or WEBP.');
                    return false;
                }
                if (vehicleImageFile.size > 100 * 1024) {
                    toast('Vehicle image must be 100 KB or smaller.');
                    return false;
                }
            }

            for (const file of Object.values(documentFiles || {})) {
                if (!file) continue;
                const ext = String(file.name || '').split('.').pop().toLowerCase();
                if (!uploadManager.documentPolicy().extensions.includes(ext)) {
                    toast('Vehicle documents must be PDF, DOC, DOCX, XLS or XLSX. Images are not allowed.');
                    return false;
                }
                if (file.size > 4 * 1024 * 1024) {
                    toast('Each vehicle document must be 4 MB or smaller.');
                    return false;
                }
            }
            return true;
        }

        function hasPendingFiles(bundle = {}) {
            return Boolean(bundle.imageFile) || Object.values(bundle.documentFiles || {}).some(Boolean);
        }

        async function syncVehicles(rows, filesByVehicle = {}) {
            if (window.FleetmanRecordApi && resources?.vehicles?.store) {
                try {
                    return await window.FleetmanRecordApi.persistCollection('vehicles', rows || [], {
                        formDataForRow: (row, rowIndex) => {
                            const formData = new FormData();
                            const files = filesByVehicle?.[rowIndex] || {};
                            if (files.imageFile) formData.append('vehicle_image_files[0]', files.imageFile);
                            Object.entries(files.documentFiles || {}).forEach(([documentIndex, file]) => {
                                if (file) formData.append(`vehicle_document_files[0][${documentIndex}]`, file);
                            });
                            return formData;
                        },
                    });
                } catch (error) {
                    toast(error.message || 'Vehicle could not be saved.');
                    return { ok: false, syncFailed: true, message: error.message };
                }
            }
            const endpoint = resources?.vehicles?.sync;
            if (!endpoint) return { ok: true, skipped: true };

            const containsFiles = Object.values(filesByVehicle || {}).some(hasPendingFiles);

            try {
                let response;
                if (containsFiles) {
                    const formData = new FormData();
                    formData.append('rows', JSON.stringify(rows || []));
                    Object.entries(filesByVehicle).forEach(([vehicleIndex, files]) => {
                        if (files.imageFile) formData.append(`vehicle_image_files[${vehicleIndex}]`, files.imageFile);
                        Object.entries(files.documentFiles || {}).forEach(([documentIndex, file]) => {
                            if (file) formData.append(`vehicle_document_files[${vehicleIndex}][${documentIndex}]`, file);
                        });
                    });
                    response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: formData,
                    });
                } else {
                    response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify({ rows: rows || [] }),
                    });
                }

                const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                const payload = contentType.includes('application/json')
                    ? await response.json().catch(() => null)
                    : null;

                if (!response.ok) {
                    const validationMessage = payload?.errors
                        ? Object.values(payload.errors).flat().join(' ')
                        : '';
                    const sessionMessage = [401, 419].includes(response.status)
                        ? 'Your session has expired. Please log in again before saving.'
                        : '';
                    throw new Error(payload?.message || validationMessage || sessionMessage || 'Vehicle could not be saved.');
                }

                if (!payload || payload.ok !== true || !Array.isArray(payload.rows)) {
                    throw new Error('The server did not confirm that the vehicle was saved. Please refresh and try again.');
                }

                return payload;
            } catch (error) {
                toast(error.message || 'Vehicle could not be saved.');
                return { ok: false, syncFailed: true, message: error.message };
            }
        }

        function resetForm(withId = true) {
            clearVehicleValidation();
            $$('#vehicleAddPage input:not([type=radio]):not([type=file]):not([type=hidden]), #vehicleAddPage textarea').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage input[type=file]').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage input[type=hidden]').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage select').forEach((select) => { select.selectedIndex = 0; });
            setUsage('');
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            if (withId) setValue('#vehicleId', uid());
            setValue('#totalRentalAmount', '0.00');
            updateSubCategory('');
            addFuelRow({ priority: 'Primary' });
            addDocRow({ reminder: docReminders[0] || '' });
            toggleRentalFields();
            renderVehicleImageInfo({});
        }

        function collectVehicle(statusOverride = 'Active') {
            const documentFiles = {};
            const docs = [];
            $$('.doc-row').forEach((row) => {
                const existingFile = parseFileDataInput('.docFileData', row);
                const pendingFile = selectedDocFile(row);
                const doc = {
                    name: $('.docName', row)?.value || '',
                    expiry: $('.docExpiry', row)?.value || '',
                    reminder: $('.docReminder', row)?.value || '',
                    file: existingFile,
                };
                if (doc.name || doc.expiry || doc.file?.filePath || doc.file?.fileUrl || pendingFile) {
                    const documentIndex = docs.length;
                    docs.push(doc);
                    if (pendingFile) documentFiles[documentIndex] = pendingFile;
                }
            });

            return {
                vehicle: {
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
                    rentalType: value('#rentalType'),
                    driver: value('#driver'),
                    secondDriver: value('#secondDriver'),
                    driverPaymentAmount: value('#driverPaymentAmount'),
                    driverPaymentCycle: value('#driverPaymentCycle'),
                    secondDriverPaymentAmount: value('#secondDriverPaymentAmount'),
                    secondDriverPaymentCycle: value('#secondDriverPaymentCycle'),
                    vehicleRentalAmount: value('#vehicleRentalAmount'),
                    vehiclePaymentCycle: value('#vehiclePaymentCycle'),
                    totalRentalAmount: value('#totalRentalAmount'),
                    rent: value('#totalRentalAmount'),
                    notes: value('#notes'),
                    image: parseFileDataInput('#vehicleImageData'),
                    fuels: $$('.fuel-row').map((row) => ({
                        type: $('.fuelType', row)?.value || '',
                        priority: $('.fuelPriority', row)?.value || '',
                        rate: $('.fuelRate', row)?.value || '',
                    })).filter((fuel) => fuel.type || fuel.rate),
                    docs,
                    status: statusOverride === 'Draft' ? 'Draft' : 'Active',
                    vehicleValidationVersion: 3,
                },
                imageFile: selectedVehicleImageFile(),
                documentFiles,
            };
        }

        function validateVehicle(vehicle) {
            clearVehicleValidation();
            let valid = true;
            const invalidate = (element, message, container = null) => {
                markVehicleInvalid(element, message, container);
                valid = false;
            };

            [
                ['#vehicleId', 'Vehicle ID is required.'],
                ['#vehicleName', 'Vehicle Name is required.'],
                ['#regNo', 'Registration Number is required.'],
                ['#model', 'Model is required.'],
                ['#engineNo', 'Engine Number is required.'],
                ['#category', 'Vehicle Category is required.'],
                ['#rentalType', 'Rental Type is required.'],
                ['#vehicleRentalAmount', 'Vehicle Rental Amount is required.'],
                ['#vehiclePaymentCycle', 'Vehicle Payment Cycle is required.'],
            ].forEach(([selector, message]) => {
                const element = $(selector);
                if (!String(element?.value || '').trim()) invalidate(element, message);
            });

            if (!vehicle.usage) {
                invalidate(null, 'Usage Type is required.', $('#vehicleAddPage .choice-grid'));
            }

            const regInput = $('#regNo');
            if (regInput?.value.trim().length > 25) {
                invalidate(regInput, 'Registration Number cannot be more than 25 characters.');
            } else if (regInput?.value.trim() && /[@#$%^&*()!`~]/.test(regInput.value.trim())) {
                invalidate(regInput, 'Registration Number cannot contain: @ # $ % ^ & * ( ) ! ` ~.');
            }

            const engineInput = $('#engineNo');
            if (engineInput?.value.trim().length > 22) {
                invalidate(engineInput, 'Engine Number cannot be more than 22 characters.');
            }

            const driver = $('#driver');
            const secondDriver = $('#secondDriver');
            const validDriverValues = new Set(vehicleDriverOptions.map((option) => option.value));
            const withDriver = vehicle.rentalType === 'With Driver';
            const doubleShiftWithDriver = withDriver && String(vehicle.usage || '').trim().toLowerCase() === 'double shift';

            if (driver?.value && !validDriverValues.has(String(driver.value).trim())) {
                invalidate(driver, 'Select a valid Driver 1 from the searchable list.');
            }
            if (secondDriver?.value && !validDriverValues.has(String(secondDriver.value).trim())) {
                invalidate(secondDriver, 'Select a valid Driver 2 from the searchable list.');
            }
            if (withDriver && !String(driver?.value || '').trim()) {
                invalidate(driver, 'Driver 1 is required when Rental Type is With Driver.');
            }
            if (doubleShiftWithDriver && !String(secondDriver?.value || '').trim()) {
                invalidate(secondDriver, 'Driver 2 is required for a double-shift vehicle rented With Driver.');
            }
            if (String(driver?.value || '').trim() && String(secondDriver?.value || '').trim()
                && String(driver.value).trim().toLowerCase() === String(secondDriver.value).trim().toLowerCase()) {
                invalidate(driver, 'Driver 1 and Driver 2 must be different drivers.');
                invalidate(secondDriver, 'Driver 1 and Driver 2 must be different drivers.');
            }

            if (withDriver) {
                const amount = $('#driverPaymentAmount');
                const cycle = $('#driverPaymentCycle');
                if (!String(amount?.value || '').trim()) invalidate(amount, 'Driver 1 Payment Amount is required.');
                else if (Number(amount.value) < 0) invalidate(amount, 'Driver 1 Payment Amount cannot be negative.');
                if (!cycle?.value) invalidate(cycle, 'Driver 1 Payment Cycle is required.');
            }

            if (doubleShiftWithDriver) {
                const amount = $('#secondDriverPaymentAmount');
                const cycle = $('#secondDriverPaymentCycle');
                if (!String(amount?.value || '').trim()) invalidate(amount, 'Driver 2 Payment Amount is required.');
                else if (Number(amount.value) < 0) invalidate(amount, 'Driver 2 Payment Amount cannot be negative.');
                if (!cycle?.value) invalidate(cycle, 'Driver 2 Payment Cycle is required.');
            }

            const vehicleAmount = $('#vehicleRentalAmount');
            if (vehicleAmount?.value !== '' && Number(vehicleAmount.value) < 0) {
                invalidate(vehicleAmount, 'Vehicle Rental Amount cannot be negative.');
            }

            const validFuels = (vehicle.fuels || []).filter((fuel) => fuel.type);
            if (!validFuels.length) {
                invalidate(null, 'Add at least one fuel type.', $('#vehicleFuelRows'));
            }
            if (!validFuels.some((fuel) => fuel.priority === 'Primary')) {
                invalidate(null, 'Mark one fuel as Primary.', $('#vehicleFuelRows'));
            }
            const selectedFuelNames = new Map();
            const selectedFuelPriorities = new Map();
            $$('#vehicleFuelRows .fuel-row').forEach((row) => {
                const type = $('.fuelType', row);
                const priority = $('.fuelPriority', row);
                const rate = $('.fuelRate', row);
                const normalized = String(type?.value || '').trim().toLowerCase();
                if (!normalized) invalidate(type, 'Fuel Type is required.');
                else if (selectedFuelNames.has(normalized)) {
                    invalidate(type, 'This fuel type has already been selected.');
                    invalidate(selectedFuelNames.get(normalized), 'This fuel type has already been selected.');
                } else selectedFuelNames.set(normalized, type);
                if (!priority?.value) invalidate(priority, 'Fuel Priority is required.');
                else if (selectedFuelPriorities.has(priority.value)) {
                    invalidate(priority, 'This fuel priority has already been selected.');
                    invalidate(selectedFuelPriorities.get(priority.value), 'This fuel priority has already been selected.');
                } else selectedFuelPriorities.set(priority.value, priority);
                if (!rate?.value || Number(rate.value) <= 0) invalidate(rate, 'Add an active fuel price, then select this fuel type.');
            });

            const selectedDocs = new Map();
            $$('#vehicleDocRows .doc-row').forEach((row) => {
                const name = $('.docName', row);
                const expiry = $('.docExpiry', row);
                const fileInput = $('.docFile', row);
                const fileData = parseFileDataInput('.docFileData', row);
                const pending = selectedDocFile(row);
                const hasAny = Boolean(name?.value || expiry?.value || pending || fileData.tempToken || fileData.filePath || fileData.fileUrl || fileData.previewUrl);
                if (!hasAny) return;
                const normalized = String(name?.value || '').trim().toLowerCase();
                if (!normalized) invalidate(name, 'Document Name is required.');
                else if (selectedDocs.has(normalized)) {
                    invalidate(name, 'This document has already been selected.');
                    invalidate(selectedDocs.get(normalized), 'This document has already been selected.');
                } else selectedDocs.set(normalized, name);
                if (!pending && !fileData.tempToken && !fileData.filePath && !fileData.fileUrl && !fileData.previewUrl) {
                    invalidate(fileInput, 'Upload a file for this document.');
                }
                const size = Number(pending?.size || fileData.sizeBytes || 0);
                if (size > 4 * 1024 * 1024) invalidate(fileInput, 'The document must be 4 MB or smaller.');
            });

            const imageInput = $('#image');
            const imageData = parseFileDataInput('#vehicleImageData');
            const imageFile = selectedVehicleImageFile();
            if (Number(imageFile?.size || imageData.sizeBytes || 0) > 100 * 1024) {
                invalidate(imageInput, 'Vehicle image must be 100 KB or smaller.');
            }

            const existingRegistration = vehicles.find((item) => String(item.regNo || '').toLowerCase() === vehicle.regNo.toLowerCase() && item.id !== vehicle.id);
            if (existingRegistration) {
                invalidate(regInput, 'Registration Number must be unique.');
            }

            if (!valid) {
                toast('Please correct the highlighted vehicle fields.');
                focusFirstVehicleError();
            }
            return valid;
        }

        function upsertVehicle(vehicle) {
            const index = vehicles.findIndex((item) => item.id === vehicle.id);
            if (index >= 0) {
                vehicles[index] = vehicle;
                return index;
            }
            vehicles.unshift(vehicle);
            return 0;
        }

        function cloneVehicles() {
            return JSON.parse(JSON.stringify(vehicles || []));
        }

        async function saveVehicle(statusOverride = 'Active') {
            const isDraft = statusOverride === 'Draft';
            const saveBtn = isDraft ? $('#saveVehicleDraftBtn') : $('#saveVehicleBtn');
            return window.FleetmanRunTransaction(saveBtn, async () => {
                await uploadManager.waitForInputs([$('#image'), ...$$('#vehicleDocRows .docFile')]);
                if (documentSelects.hasDuplicates('#vehicleDocRows', '.docName')) {
                    toast('Each vehicle document type can be selected only once.');
                    return;
                }
                const form = collectVehicle(isDraft ? 'Draft' : 'Active');
                const vehicle = form.vehicle;
                if (!isDraft && !validateVehicle(vehicle)) return;
                if (!validatePendingFiles(form.imageFile, form.documentFiles)) return;

                const previousVehicles = cloneVehicles();
                const vehicleIndex = upsertVehicle(vehicle);
                const filesForSync = hasPendingFiles(form) ? { [vehicleIndex]: { imageFile: form.imageFile, documentFiles: form.documentFiles } } : {};
                const result = await syncVehicles(vehicles, filesForSync);

                if (result?.syncFailed || result?.ok === false) {
                    vehicles = previousVehicles;
                    renderTable();
                    return;
                }

                const savedVehicleRows = Array.isArray(result?.rows) ? result.rows : [];
                if (!savedVehicleRows.some((savedRow) => String(savedRow?.id || '') === String(vehicle.id || ''))) {
                    vehicles = previousVehicles;
                    renderTable();
                    toast('The vehicle was not found in the database response, so it was not added to the list.');
                    return;
                }

                vehicles = savedVehicleRows;
                if (window.FleetmanListAccess.canView()) {
                    renderTable();
                    toast(isDraft ? 'Vehicle draft saved.' : 'Vehicle saved successfully.');
                    setVisible('vehicleListPage');
                } else {
                    vehicles = [];
                    resetForm();
                    setVisible('vehicleAddPage');
                    toast(window.FleetmanListAccess.savedMessage('Vehicle', isDraft));
                }
            }, { loadingText: isDraft ? 'Saving Draft...' : 'Saving...' });
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
            setValue('#rentalType', sample.rentalType || (sample.driverPaymentAmount ? 'With Driver' : 'Without Driver'));
            setUsage(sample.usage);
            setValue('#driver', sample.driver);
            setValue('#secondDriver', sample.secondDriver || sample.driver2 || sample.secondaryDriver || '');
            setValue('#driverPaymentAmount', sample.driverPaymentAmount ?? '');
            setValue('#driverPaymentCycle', sample.driverPaymentCycle || 'Monthly');
            setValue('#secondDriverPaymentAmount', sample.secondDriverPaymentAmount ?? '');
            setValue('#secondDriverPaymentCycle', sample.secondDriverPaymentCycle || 'Monthly');
            setValue('#vehicleRentalAmount', sample.vehicleRentalAmount ?? sample.rent ?? 0);
            setValue('#vehiclePaymentCycle', sample.vehiclePaymentCycle || 'Monthly');
            toggleRentalFields();
            recalculateRentalTotal();
            setValue('#notes', sample.notes || '');
            if (sample.image) {
                setValue('#vehicleImageData', JSON.stringify(sample.image));
                renderVehicleImageInfo(sample.image);
            }
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            (sample.fuels || []).forEach(addFuelRow);
            (sample.docs || []).forEach(addDocRow);
            toast('Sample vehicle data loaded.');
        }

        function editVehicle(id) {
            const vehicle = vehicles.find((item) => item.id === id || item._recordCode === id);
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
            setValue('#rentalType', vehicle.rentalType || (vehicle.driverPaymentAmount ? 'With Driver' : 'Without Driver'));
            setUsage(vehicle.usage);
            setValue('#driver', vehicle.driver);
            setValue('#secondDriver', vehicle.secondDriver || vehicle.driver2 || vehicle.secondaryDriver || '');
            setValue('#driverPaymentAmount', vehicle.driverPaymentAmount ?? '');
            setValue('#driverPaymentCycle', vehicle.driverPaymentCycle || 'Monthly');
            setValue('#secondDriverPaymentAmount', vehicle.secondDriverPaymentAmount ?? '');
            setValue('#secondDriverPaymentCycle', vehicle.secondDriverPaymentCycle || 'Monthly');
            setValue('#vehicleRentalAmount', vehicle.vehicleRentalAmount ?? vehicle.rent ?? 0);
            setValue('#vehiclePaymentCycle', vehicle.vehiclePaymentCycle || 'Monthly');
            toggleRentalFields();
            recalculateRentalTotal();
            setValue('#notes', vehicle.notes || '');
            if (vehicle.image) {
                setValue('#vehicleImageData', JSON.stringify(vehicle.image));
                renderVehicleImageInfo(vehicle.image);
            } else {
                setValue('#vehicleImageData', '');
                renderVehicleImageInfo({});
            }
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            (vehicle.fuels || []).forEach(addFuelRow);
            if (!(vehicle.fuels || []).length) addFuelRow({ priority: 'Primary' });
            (vehicle.docs || []).forEach(addDocRow);
            setVisible('vehicleAddPage');
        }

        async function deleteVehicle(id, triggerButton = null) {
            if (!confirm('Delete this vehicle from the list?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previousVehicles = cloneVehicles();
                vehicles = vehicles.filter((vehicle) => vehicle.id !== id);
                const result = await syncVehicles(vehicles);
                if (result?.syncFailed || result?.ok === false) {
                    vehicles = previousVehicles;
                    renderTable();
                    return;
                }
                if (Array.isArray(result?.rows)) vehicles = result.rows;
                renderTable();
                toast('Vehicle deleted.');
            }, { loadingText: 'Deleting...' });
        }

        function documentLinks(vehicle) {
            return (vehicle.docs || [])
                .filter((doc) => doc.file?.fileUrl || doc.file?.filePath)
                .map((doc) => {
                    const label = doc.name || doc.file?.originalName || 'Document';
                    const url = uploadManager.permanentUrl(doc.file || {});
                    return url ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener">${escapeHtml(label)}</a>` : escapeHtml(label);
                })
                .join(', ');
        }

        function viewVehicle(id) {
            const vehicle = vehicles.find((item) => item.id === id);
            if (!vehicle) return;
            window.FleetmanDetailViewer?.show('Vehicle Details', vehicle);
        }

        function hasVehicleDocumentReview(vehicle) {
            return window.FleetmanExpiringDocuments
                .items(vehicle?.docs || [])
                .some((document) => document.days >= 0 && document.days <= 180);
        }

        function matchesVehicleStatusFilter(vehicle, filterValue) {
            if (!filterValue) return true;
            if (filterValue === 'Needs document review') return hasVehicleDocumentReview(vehicle);
            return vehicle.status === filterValue;
        }

        function renderTable() {
            const query = value('#vehicleSearch').toLowerCase();
            const category = value('#vehicleFilterCategory');
            const fuel = value('#vehicleFilterFuel');
            const status = value('#vehicleFilterStatus');
            const rows = vehicles.filter((vehicle) => {
                const text = [vehicle.name, vehicle.regNo, vehicle.driver, vehicle.secondDriver, vehicle.id, vehicle.model].join(' ').toLowerCase();
                return (!query || text.includes(query))
                    && (!category || vehicle.category === category)
                    && (!fuel || (vehicle.fuels || []).some((item) => item.type === fuel))
                    && matchesVehicleStatusFilter(vehicle, status);
            });

            const body = $('#vehicleTbody');
            if (!body) return;
            const emptyMessage = status === 'Needs document review'
                ? 'No vehicles have documents expiring within the next 180 days.'
                : 'No vehicles found.';
            body.innerHTML = rows.length ? rows.map((vehicle) => {
                const docs = vehicle.docs || [];
                const docsWithFiles = docs.filter((doc) => doc.file?.filePath || doc.file?.fileUrl).length;
                const imageUrl = uploadManager.permanentUrl(vehicle.image || {});
                const imageLink = imageUrl ? `<br><small><a href="${escapeHtml(imageUrl)}" target="_blank" rel="noopener">View image</a></small>` : '';
                const avatar = window.FleetmanEntityAvatar.html(vehicle.image || {}, {
                    fallback: '🚗',
                    alt: `${vehicle.name || 'Vehicle'} image`,
                    size: 'table',
                });
                return `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(vehicle.createdAt || vehicle.created_at, vehicle.creatorName || vehicle.createdBy)}</td>
                    <td><div class="vehicle-cell">${avatar}<div><b>${escapeHtml(vehicle.name || 'Draft Vehicle')}</b><br><small>${escapeHtml(vehicle.id)} · ${escapeHtml(vehicle.model || 'Not completed')}</small>${imageLink}</div></div></td>
                    <td>${escapeHtml(vehicle.regNo)}</td>
                    <td>${escapeHtml(vehicle.category)}<br><small>${escapeHtml(vehicle.subCategory || '')}</small></td>
                    <td>${(vehicle.fuels || []).map((item) => `<span class="badge soft">${escapeHtml(item.priority)}: ${escapeHtml(item.type)} · ${escapeHtml(item.rate || '0')}</span>`).join('')}</td>
                    <td>
                        ${escapeHtml(vehicle.driver || 'Not assigned')}
                        ${vehicle.driver && vehicle.rentalType === 'With Driver' ? `<br><small>${Number(vehicle.driverPaymentAmount || 0).toLocaleString()} BDT · ${escapeHtml(vehicle.driverPaymentCycle || '-')}</small>` : ''}
                        ${vehicle.secondDriver ? `<br><b>Driver 2:</b> ${escapeHtml(vehicle.secondDriver)}<br><small>${Number(vehicle.secondDriverPaymentAmount || 0).toLocaleString()} BDT · ${escapeHtml(vehicle.secondDriverPaymentCycle || '-')}</small>` : ''}
                    </td>
                    <td>${docs.length} document(s)<br><small>${docsWithFiles} uploaded file(s)${docsWithFiles ? ` · ${documentLinks(vehicle)}` : ''}</small></td>
                    <td>${window.FleetmanExpiringDocuments.html(docs)}</td>
                    <td>${Number(vehicle.totalRentalAmount ?? vehicle.rent ?? 0).toLocaleString()} BDT<br><small>${escapeHtml(vehicle.rentalType || '-')}</small></td>
                    <td><span class="badge ${vehicle.status === 'Active' ? 'ok' : 'warn'}">${escapeHtml(vehicle.status || '-')}</span></td>
                    <td><button type="button" class="mini-btn view-vehicle" data-id="${escapeHtml(vehicle.id)}">View</button><button type="button" class="mini-btn edit-vehicle" data-id="${escapeHtml(vehicle.id)}">Edit</button><button type="button" class="mini-btn danger delete-vehicle" data-id="${escapeHtml(vehicle.id)}">Delete</button></td>
                </tr>`;
            }).join('') : `<tr><td colspan="11" class="empty">${emptyMessage}</td></tr>`;

            // Dashboard deep links behave like normal list filters, so the KPI
            // values reflect only the rows currently visible in the table.
            $('#vehicleKpiTotal').textContent = rows.length;
            $('#vehicleKpiActive').textContent = rows.filter((vehicle) => vehicle.status === 'Active').length;
            $('#vehicleKpiDocs').textContent = rows.filter((vehicle) => (vehicle.docs || []).some((doc) => doc.expiry)).length;
            $('#vehicleKpiFuel').textContent = rows.filter((vehicle) => (vehicle.fuels || []).length > 1).length;
        }

        function exportCsv() {
            downloadCsv('fleetman-vehicle-list.csv', [
                ['Vehicle ID', 'Vehicle Name', 'Registration', 'Category', 'Fuels', 'Rental Type', 'Driver 1', 'Driver 1 Payment', 'Driver 1 Cycle', 'Driver 2', 'Driver 2 Payment', 'Driver 2 Cycle', 'Vehicle Rental', 'Vehicle Cycle', 'Total Rental', 'Documents', 'Image', 'Status'],
                ...vehicles.map((vehicle) => [
                    vehicle.id,
                    vehicle.name,
                    vehicle.regNo,
                    vehicle.category,
                    (vehicle.fuels || []).map((fuel) => fuel.priority + ' ' + fuel.type + ' @ ' + fuel.rate).join(' | '),
                    vehicle.rentalType,
                    vehicle.driver,
                    vehicle.driverPaymentAmount,
                    vehicle.driverPaymentCycle,
                    vehicle.secondDriver,
                    vehicle.secondDriverPaymentAmount,
                    vehicle.secondDriverPaymentCycle,
                    vehicle.vehicleRentalAmount,
                    vehicle.vehiclePaymentCycle,
                    vehicle.totalRentalAmount ?? vehicle.rent,
                    (vehicle.docs || []).map((doc) => [doc.name, doc.expiry, doc.file?.originalName || doc.file?.fileName || doc.file?.filePath || ''].filter(Boolean).join(' ')).join(' | '),
                    vehicle.image?.filePath || '',
                    vehicle.status,
                ]),
            ]);
        }

        $('#category')?.addEventListener('change', () => updateSubCategory(''));
        $('#rentalType')?.addEventListener('change', toggleRentalFields);
        $$('input[name="usage"]').forEach((radio) => radio.addEventListener('change', toggleRentalFields));
        ['#driver', '#secondDriver'].forEach((selector) => {
            $(selector)?.addEventListener('input', refreshVehicleDriverOptions);
            $(selector)?.addEventListener('change', refreshVehicleDriverOptions);
        });
        ['#driverPaymentAmount', '#secondDriverPaymentAmount', '#vehicleRentalAmount'].forEach((selector) => $(selector)?.addEventListener('input', recalculateRentalTotal));
        $('#addFuelRowBtn')?.addEventListener('click', () => {
            if ($$('#vehicleFuelRows .fuel-row').length >= fuelPriorities.length) {
                toast('Primary, Secondary, and Tertiary priorities are already used.');
                return;
            }
            addFuelRow();
        });
        $('#addDocRowBtn')?.addEventListener('click', () => addDocRow());
        $('#clearVehicleBtn')?.addEventListener('click', () => resetForm());
        $('#saveVehicleBtn')?.addEventListener('click', () => saveVehicle('Active'));
        $('#saveVehicleDraftBtn')?.addEventListener('click', () => saveVehicle('Draft'));
        $('#loadVehicleSampleBtn')?.addEventListener('click', loadSample);
        $('#exportVehiclesBtn')?.addEventListener('click', exportCsv);
        $('#clearVehicleFiltersBtn')?.addEventListener('click', () => {
            ['#vehicleSearch', '#vehicleFilterCategory', '#vehicleFilterFuel', '#vehicleFilterStatus']
                .forEach((selector) => setValue(selector, ''));

            const url = new URL(window.location.href);
            url.searchParams.delete('document_filter');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);

            renderTable();
        });
        ['#vehicleSearch', '#vehicleFilterCategory', '#vehicleFilterFuel', '#vehicleFilterStatus'].forEach((selector) => $(selector)?.addEventListener('input', renderTable));

        document.addEventListener('change', (event) => {
            const fuelSelect = event.target.closest('.fuelType');
            if (fuelSelect) {
                updateFuelRate(fuelSelect.closest('.fuel-row'), true);
                refreshVehicleFuelOptions();
            }

            if (event.target.closest('#vehicleFuelRows .fuelPriority')) {
                refreshVehicleFuelPriorityOptions();
            }

            const docName = event.target.closest('#vehicleDocRows .docName');
            if (docName) refreshVehicleDocumentOptions();

            const docFile = event.target.closest('.docFile');
            if (docFile) {
                const row = docFile.closest('.doc-row');
                if (row) {
                    uploadManager.upload(docFile, uploadManager.documentOptions({
                        hidden: $('.docFileData', row),
                        info: $('.docUploadInfo', row),
                        progress: $('.docUploadProgress', row),
                    }));
                }
            }

            if (event.target.matches('#image')) {
                uploadManager.upload(event.target, {
                    hidden: $('#vehicleImageData'),
                    info: $('#vehicleImageUploadInfo'),
                    progress: $('#vehicleImageProgress'),
                    extensions: ['jpg', 'jpeg', 'png', 'webp'],
                    maxBytes: 100 * 1024,
                    imageOnly: true,
                    showPreview: true,
                });
            }
        });

        document.addEventListener('click', (event) => {
            const pageTarget = event.target.closest('[data-page-target]');
            if (pageTarget) { renderTable(); setVisible(pageTarget.dataset.pageTarget); }
            const remove = event.target.closest('.remove-row');
            if (remove) {
                const row = remove.parentElement;
                const documentRow = row?.classList.contains('doc-row');
                row?.remove();
                if (documentRow) refreshVehicleDocumentOptions();
                if (row?.classList.contains('fuel-row')) {
                    refreshVehicleFuelOptions();
                    refreshVehicleFuelPriorityOptions();
                }
            }
            const view = event.target.closest('.view-vehicle');
            if (view) viewVehicle(view.dataset.id);
            const edit = event.target.closest('.edit-vehicle');
            if (edit) editVehicle(edit.dataset.id);
            const del = event.target.closest('.delete-vehicle');
            if (del) deleteVehicle(del.dataset.id, del);
        });

        $('#vehicleAddPage')?.addEventListener('input', (event) => {
            if (event.target.matches('input,select,textarea')) clearVehicleFieldError(event.target);
        });
        $('#vehicleAddPage')?.addEventListener('change', (event) => {
            if (event.target.matches('input,select,textarea')) clearVehicleFieldError(event.target);
        });

        resetForm();

        const vehicleUrlParams = new URLSearchParams(window.location.search);
        if (vehicleUrlParams.get('document_filter') === 'within-180-days') {
            setValue('#vehicleFilterStatus', 'Needs document review');
        }

        renderTable();
        window.FleetmanRecordApi?.registerInfinite('vehicles', () => vehicles, (rows) => { vehicles = rows; }, renderTable);
        const requestedVehicleAction = vehicleUrlParams.get('action');
        const requestedVehicleCode = vehicleUrlParams.get('code');
        if (requestedVehicleAction === 'edit' && requestedVehicleCode) {
            const requestedVehicle = vehicles.find((vehicle) => String(vehicle._recordCode || vehicle.id || '') === requestedVehicleCode);
            if (requestedVehicle) editVehicle(requestedVehicleCode);
            else setVisible('vehicleListPage');
        } else if (requestedVehicleAction === 'add') {
            setVisible('vehicleAddPage');
        } else {
            setVisible('vehicleListPage');
        }
    }

    function initFuelPrices() {
        const STORAGE = 'fleetman_fuel_prices_v2';
        let prices = Array.isArray(records.fuel_prices) ? records.fuel_prices : (samples.fuel_prices || []);

        async function saveStore() {
            if (window.FleetmanRecordApi && resources?.fuel_prices?.store) {
                const payload = await window.FleetmanRecordApi.persistCollection('fuel_prices', prices);
                if (Array.isArray(payload?.rows)) prices = payload.rows;
                return payload;
            }
            const url = resources?.fuel_prices?.sync;
            if (!url) return { ok: true, rows: prices };
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ rows: prices }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || Object.values(payload.errors || {}).flat().join(' ') || 'Fuel price save failed.');
            }
            if (Array.isArray(payload.rows)) prices = payload.rows;
            return payload;
        }

        function genId() {
            return 'FPR' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function fuelPriceField(element) {
            return element?.closest('.field') || element?.closest('.select-field') || element;
        }

        function clearFuelPriceError(element) {
            const field = fuelPriceField(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll('.field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function clearFuelPriceValidation() {
            const page = $('#fuelPriceAddPage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markFuelPriceInvalid(element, message) {
            if (!element) return;
            const field = fuelPriceField(element);
            if (!field) return;
            clearFuelPriceError(element);
            field.classList.add('field-invalid');
            element.setAttribute('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'field-error';
            error.textContent = message;
            field.appendChild(error);
        }

        function validateFuelPrice(row) {
            clearFuelPriceValidation();
            const errors = [];
            const invalidate = (selector, message) => {
                const element = $(selector);
                markFuelPriceInvalid(element, message);
                if (element) errors.push(element);
            };

            if (!String(row.fuelPriceId || '').trim()) invalidate('#fuelPriceId', 'Fuel Price ID is required.');
            if (!String(row.fuelType || '').trim()) invalidate('#fuelType', 'Fuel Type is required.');
            if (!String(row.name || '').trim()) invalidate('#fuelName', 'Name is required.');
            else if (String(row.name).length > 160) invalidate('#fuelName', 'Name cannot exceed 160 characters.');

            const numericPrice = Number(row.price);
            if (String(row.price ?? '').trim() === '' || !Number.isFinite(numericPrice) || numericPrice <= 0) {
                invalidate('#fuelPrice', 'Price per Unit is required and must be greater than zero.');
            }
            if (!String(row.unit || '').trim()) invalidate('#fuelUnit', 'Unit is required.');
            if (!String(row.status || '').trim()) invalidate('#fuelStatus', 'Status is required.');
            if (!String(row.effectiveDate || '').trim() || Number.isNaN(new Date(`${row.effectiveDate}T00:00:00`).getTime())) {
                invalidate('#effectiveDate', 'A valid Effective Date is required.');
            }
            if (!String(row.reference || '').trim()) invalidate('#fuelReference', 'Reference is required.');
            else if (String(row.reference).length > 160) invalidate('#fuelReference', 'Reference cannot exceed 160 characters.');
            if (!String(row.remarks || '').trim()) invalidate('#fuelRemarks', 'Remarks are required.');
            else if (String(row.remarks).length > 1000) invalidate('#fuelRemarks', 'Remarks cannot exceed 1000 characters.');

            if (errors.length) {
                const first = errors[0];
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => first.focus(), 250);
                toast('Please correct the highlighted fields.');
                return false;
            }
            return true;
        }

        function resetForm() {
            clearFuelPriceValidation();
            $$('#fuelPriceAddPage input, #fuelPriceAddPage select, #fuelPriceAddPage textarea').forEach((field) => { field.value = ''; });
            setValue('#fuelPriceId', genId());
            setValue('#fuelStatus', 'Active');
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
                fuelPriceValidationVersion: 1,
            };
        }

        function upsert(row) {
            const index = prices.findIndex((item) => item.fuelPriceId === row.fuelPriceId);
            if (index >= 0) prices[index] = row;
            else prices.unshift(row);
        }

        async function saveFuelPrice(statusOverride) {
            const saveButton = statusOverride === 'Draft' ? $('#saveFuelPriceDraftBtn') : $('#saveFuelPriceBtn');
            return window.FleetmanRunTransaction(saveButton, async () => {
                const row = collect(statusOverride);
                if (!validateFuelPrice(row)) return;

                const previous = JSON.parse(JSON.stringify(prices || []));
                upsert(row);

                try {
                    await saveStore();
                    if (window.FleetmanListAccess.canView()) {
                        renderList();
                        toast(row.status === 'Draft' ? 'Draft saved.' : 'Fuel price saved.');
                        setVisible('fuelPriceListPage');
                    } else {
                        prices = [];
                        resetForm();
                        setVisible('fuelPriceAddPage');
                        toast(window.FleetmanListAccess.savedMessage('Fuel price', row.status === 'Draft'));
                    }
                } catch (error) {
                    prices = previous;
                    toast(error.message || 'Fuel price save failed.');
                }
            }, { loadingText: statusOverride === 'Draft' ? 'Saving Draft...' : 'Saving...' });
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

        async function deleteFuelPrice(id, triggerButton = null) {
            if (!confirm('Delete this fuel price from the list?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previous = JSON.parse(JSON.stringify(prices || []));
                prices = prices.filter((row) => row.fuelPriceId !== id);
                try {
                    await saveStore();
                    renderList();
                    toast('Fuel price deleted.');
                } catch (error) {
                    prices = previous;
                    renderList();
                    toast(error.message || 'Fuel price deletion failed.');
                }
            }, { loadingText: 'Deleting...' });
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
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><div class="fuel-cell"><div class="fuel-icon">⛽</div><div><b>${escapeHtml(row.name)}</b><br><small>${escapeHtml(row.fuelPriceId)}</small></div></div></td>
                    <td>${escapeHtml(row.fuelType || '-')}</td>
                    <td>${Number(row.price || 0).toLocaleString()}<br><small>${escapeHtml(row.remarks ? row.remarks.slice(0, 42) : '')}</small></td>
                    <td>${escapeHtml(row.unit || '-')}</td>
                    <td>${escapeHtml(row.effectiveDate || '-')}</td>
                    <td>${escapeHtml(row.reference || '-')}</td>
                    <td><span class="badge ${statusClass}">${escapeHtml(row.status || '-')}</span></td>
                    <td><button type="button" class="mini-btn view-fuel-price" data-id="${escapeHtml(row.fuelPriceId)}">View</button><button type="button" class="mini-btn edit-fuel-price" data-id="${escapeHtml(row.fuelPriceId)}">Edit</button><button type="button" class="mini-btn danger delete-fuel-price" data-id="${escapeHtml(row.fuelPriceId)}">Delete</button></td>
                </tr>`;
            }).join('') : '<tr><td colspan="9" class="empty">No fuel price found. Click “Add Fuel Price” to create one.</td></tr>';

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
                if (row) window.FleetmanDetailViewer?.show('Fuel Price Details', row);
            }
            const edit = event.target.closest('.edit-fuel-price');
            if (edit) editFuelPrice(edit.dataset.id);
            const del = event.target.closest('.delete-fuel-price');
            if (del) deleteFuelPrice(del.dataset.id, del);
        });

        $('#fuelPriceAddPage')?.addEventListener('input', (event) => {
            const control = event.target.closest('input, select, textarea');
            if (control) clearFuelPriceError(control);
        });
        $('#fuelPriceAddPage')?.addEventListener('change', (event) => {
            const control = event.target.closest('input, select, textarea');
            if (control) clearFuelPriceError(control);
        });

        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('fuel_prices', () => prices, (rows) => { prices = rows; }, renderList);
        if (window.location.search.includes('action=add')) {
            setVisible('fuelPriceAddPage');
        } else {
            setVisible('fuelPriceListPage');
        }
    }

    function initFuelRecharge() {
        const contracts = Array.isArray(data.contracts) ? data.contracts : [];
        const latestFuelRates = data.latestFuelRates || {};
        const rawFuelStations = Array.isArray(data.fuelStations) ? data.fuelStations : [];
        const photoRequirements = data.photoRequirements || [];
        const uploadManager = window.FleetmanTemporaryUploads;
        const photoState = {};
        photoRequirements.forEach((photo) => { photoState[photo.key] = { captured: false, file: null, fileData: {}, preview: '', capturedAt: '', displayTime: '', place: '' }; });
        let recharges = Array.isArray(records.fuel_recharges) ? records.fuel_recharges.slice() : [];
        let activeCameraKey = null;
        let activeStream = null;
        let editingRechargeId = '';
        let editingRechargeDate = '';
        let activeFuelDriver = null;

        function endpoint() {
            return resources?.fuel_recharges?.sync || null;
        }

        async function syncFuelRecharges(rows, filesByRow = {}) {
            if (window.FleetmanRecordApi && resources?.fuel_recharges?.store) {
                try {
                    return await window.FleetmanRecordApi.persistCollection('fuel_recharges', rows || [], {
                        formDataForRow: (row, rowIndex) => {
                            const formData = new FormData();
                            Object.entries(filesByRow?.[rowIndex] || {}).forEach(([photoKey, file]) => {
                                if (file) formData.append(`fuel_recharge_photos[0][${photoKey}]`, file);
                            });
                            return formData;
                        },
                    });
                } catch (error) {
                    toast(error.message || 'Fuel recharge could not be saved.');
                    return { ok: false, syncFailed: true, message: error.message };
                }
            }
            const url = endpoint();
            if (!url) return { ok: true, skipped: true };
            const hasFiles = Object.values(filesByRow || {}).some((photos) => Object.values(photos || {}).some(Boolean));
            try {
                let response;
                if (hasFiles) {
                    const formData = new FormData();
                    formData.append('rows', JSON.stringify(rows || []));
                    Object.entries(filesByRow || {}).forEach(([rowIndex, photos]) => {
                        Object.entries(photos || {}).forEach(([photoKey, file]) => {
                            if (file) formData.append(`fuel_recharge_photos[${rowIndex}][${photoKey}]`, file);
                        });
                    });
                    response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                        body: formData,
                    });
                } else {
                    response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                        body: JSON.stringify({ rows: rows || [] }),
                    });
                }

                if (!response.ok) {
                    let message = 'Fuel recharge could not be saved.';
                    try {
                        const error = await response.json();
                        message = error.message || Object.values(error.errors || {}).flat().join(' ') || message;
                    } catch (_) {}
                    throw new Error(message);
                }
                return await response.json().catch(() => ({ ok: true }));
            } catch (error) {
                toast(error.message || 'Fuel recharge could not be saved.');
                return { ok: false, syncFailed: true, message: error.message };
            }
        }

        function nextRechargeId() {
            return 'FR-' + new Date().toISOString().slice(0, 10).replaceAll('-', '') + '-' + String(Date.now()).slice(-5);
        }

        function normalizeFuelName(name) {
            const normalized = String(name || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
            if (!normalized) return '';
            if (normalized.includes('cng') || normalized.includes('compressednaturalgas') || normalized === 'gas' || normalized.includes('naturalgas')) return 'cng';
            if (normalized.includes('lpg') || normalized.includes('liquefiedpetroleumgas')) return 'lpg';
            if (normalized.includes('diesel')) return 'diesel';
            if (normalized.includes('octane') || normalized.includes('octen') || normalized.includes('petrol') || normalized.includes('gasoline')) return 'octane';
            return normalized.replace('petroloctane', 'octane').replace('octanepetrol', 'octane');
        }

        function isDirectAmountFuel(fuelName) {
            return ['cng', 'lpg', 'gas'].includes(normalizeFuelName(fuelName));
        }

        function inferStationFuelTypes(text) {
            const value = String(text || '').toLowerCase();
            const inferred = [];
            if (/\bdiesel\b/i.test(value)) inferred.push('Diesel');
            if (/octane|octen|petrol|gasoline/i.test(value)) inferred.push('Petrol/Octane');
            if (/\bcng\b|compressed natural gas/i.test(value)) inferred.push('CNG');
            if (/\blpg\b|liquefied petroleum gas/i.test(value)) inferred.push('LPG');
            return inferred;
        }

        const fuelStations = rawFuelStations.map((station) => {
            if (typeof station === 'string') {
                return { id: station, name: station, fuelTypes: inferStationFuelTypes(station) };
            }
            const name = String(station?.name || station?.label || station?.partyName || '');
            const configuredTypes = Array.isArray(station?.fuelTypes)
                ? station.fuelTypes
                : (Array.isArray(station?.supportedFuelTypes) ? station.supportedFuelTypes : []);
            return {
                id: String(station?.id || station?.partyId || name),
                name,
                fuelTypes: configuredTypes.length ? configuredTypes : inferStationFuelTypes(name),
            };
        }).filter((station) => station.name);

        function stationsForFuel(fuelName) {
            const fuelKey = normalizeFuelName(fuelName);
            if (!fuelKey) return [];
            return fuelStations.filter((station) => (station.fuelTypes || []).some((type) => normalizeFuelName(type) === fuelKey));
        }

        function stationSupportsFuel(stationName, fuelName) {
            const selectedName = String(stationName || '').trim().toLowerCase();
            return stationsForFuel(fuelName).some((station) => station.name.toLowerCase() === selectedName);
        }

        function populateStationSelect(selector, fuelName, hintSelector, selectedValue = '') {
            const select = $(selector);
            const hint = $(hintSelector);
            if (!select) return;

            const stations = stationsForFuel(fuelName);
            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';

            if (!fuelName) {
                placeholder.textContent = '- Select vehicle first -';
                select.disabled = true;
                if (hint) hint.textContent = '';
            } else if (!stations.length) {
                placeholder.textContent = `No ${fuelName} station configured`;
                select.disabled = true;
                if (hint) hint.textContent = '';
            } else {
                placeholder.textContent = `- Select ${fuelName} station -`;
                select.disabled = false;
                if (hint) hint.textContent = '';
            }
            select.appendChild(placeholder);

            stations.forEach((station) => {
                const option = document.createElement('option');
                option.value = station.name;
                option.textContent = station.name;
                select.appendChild(option);
            });

            if (selectedValue && stations.some((station) => station.name === selectedValue)) {
                select.value = selectedValue;
            }
        }

        function latestRateForFuel(fuelName) {
            const fuel = String(fuelName || '').trim();
            if (!fuel) return null;
            if (latestFuelRates[fuel]) return latestFuelRates[fuel];
            const needle = normalizeFuelName(fuel);
            const foundKey = Object.keys(latestFuelRates).find((key) => normalizeFuelName(key) === needle);
            return foundKey ? latestFuelRates[foundKey] : null;
        }

        function contractSearchLabel(contract = {}) {
            return String(contract.label || [contract.id || contract.contractId, contract.partyName || contract.name].filter(Boolean).join(' | ')).trim();
        }

        function selectedContract() {
            const current = String(value('#contractSelect') || '').trim();
            if (!current) return null;
            const normalizedCurrent = current.toLowerCase();

            return contracts.find((contract) => {
                const id = String(contract.id || contract.contractId || '').trim();
                const label = contractSearchLabel(contract);
                return id === current || label.toLowerCase() === normalizedCurrent;
            }) || null;
        }

        function vehicleSearchLabel(vehicle = {}) {
            return String(vehicle.label || vehicle.name || vehicle.id || '').trim();
        }

        function selectedVehicle() {
            const contract = selectedContract();
            if (!contract) return null;

            const current = String(value('#vehicleSelect') || '').trim();
            if (!current) return null;
            const normalizedCurrent = current.toLowerCase();

            return (contract.vehicles || []).find((vehicle) => {
                const id = String(vehicle.id || '').trim();
                const label = vehicleSearchLabel(vehicle);
                return id === current || label.toLowerCase() === normalizedCurrent;
            }) || null;
        }

        function timeToMinutes(time) {
            const match = String(time || '').trim().match(/^(\d{1,2}):(\d{2})/);
            if (!match) return null;
            const hour = Number(match[1]);
            const minute = Number(match[2]);
            if (!Number.isInteger(hour) || !Number.isInteger(minute) || hour < 0 || hour > 23 || minute < 0 || minute > 59) return null;
            return (hour * 60) + minute;
        }

        function currentMinutesInFleetTimeZone() {
            const timeZone = String(data.timeZone || 'Asia/Dhaka');
            try {
                const parts = new Intl.DateTimeFormat('en-GB', {
                    timeZone,
                    hour: '2-digit',
                    minute: '2-digit',
                    hourCycle: 'h23',
                }).formatToParts(new Date());
                const hour = Number(parts.find((part) => part.type === 'hour')?.value || 0) % 24;
                const minute = Number(parts.find((part) => part.type === 'minute')?.value || 0);
                return (hour * 60) + minute;
            } catch (_) {
                const now = new Date();
                return (now.getHours() * 60) + now.getMinutes();
            }
        }

        function currentFleetTimeLabel() {
            const timeZone = String(data.timeZone || 'Asia/Dhaka');
            try {
                return new Intl.DateTimeFormat('en-BD', {
                    timeZone,
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                }).format(new Date());
            } catch (_) {
                return new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            }
        }

        function shiftContainsMinutes(startTime, endTime, currentMinutes) {
            const start = timeToMinutes(startTime);
            const end = timeToMinutes(endTime);
            if (start === null || end === null) return false;
            if (start === end) return true;
            if (start < end) return currentMinutes >= start && currentMinutes < end;
            return currentMinutes >= start || currentMinutes < end;
        }

        function normalizedVehicleDrivers(vehicle = {}) {
            const drivers = Array.isArray(vehicle.drivers) ? vehicle.drivers.filter(Boolean) : [];
            if (drivers.length) return drivers;

            const fallback = [];
            if (vehicle.driverId || vehicle.driver || vehicle.driverName) {
                fallback.push({
                    driverId: vehicle.driverId || '',
                    driver: vehicle.driver || vehicle.driverName || '',
                    driverName: vehicle.driverName || vehicle.driver || '',
                    shiftId: vehicle.shiftId || '',
                    shift: vehicle.shift || '',
                    shiftName: vehicle.shiftName || vehicle.shift || '',
                    shiftStartTime: vehicle.shiftStartTime || '',
                    shiftEndTime: vehicle.shiftEndTime || '',
                });
            }
            if (vehicle.secondDriverId || vehicle.secondDriver || vehicle.secondDriverName) {
                fallback.push({
                    driverId: vehicle.secondDriverId || '',
                    driver: vehicle.secondDriver || vehicle.secondDriverName || '',
                    driverName: vehicle.secondDriverName || vehicle.secondDriver || '',
                    shiftId: vehicle.secondShiftId || '',
                    shift: vehicle.secondShift || '',
                    shiftName: vehicle.secondShiftName || vehicle.secondShift || '',
                    shiftStartTime: vehicle.secondShiftStartTime || '',
                    shiftEndTime: vehicle.secondShiftEndTime || '',
                });
            }
            return fallback;
        }

        function resolveActiveFuelDriver(vehicle = {}) {
            const drivers = normalizedVehicleDrivers(vehicle);
            if (!drivers.length) return null;

            const doubleShift = String(vehicle.shiftType || '').toLowerCase() === 'double' || drivers.length > 1;
            if (!doubleShift) return drivers[0];

            const currentMinutes = currentMinutesInFleetTimeZone();
            return drivers.find((driver) => shiftContainsMinutes(driver.shiftStartTime, driver.shiftEndTime, currentMinutes)) || null;
        }

        function refreshAssignedFuelDriver() {
            const vehicle = selectedVehicle();
            const input = $('#assignedFuelDriver');
            const hint = $('#assignedFuelDriverShift');
            activeFuelDriver = vehicle ? resolveActiveFuelDriver(vehicle) : null;

            if (!input || !hint) return activeFuelDriver;
            if (!vehicle) {
                input.value = '';
                input.placeholder = 'Select contract and vehicle';
                hint.textContent = 'The assigned driver will appear automatically.';
                return activeFuelDriver;
            }

            const drivers = normalizedVehicleDrivers(vehicle);
            if (!drivers.length) {
                input.value = 'No driver assigned';
                hint.textContent = 'Assign a driver from the Contract page before adding fuel.';
                return activeFuelDriver;
            }

            if (!activeFuelDriver) {
                input.value = 'No active shift driver';
                hint.textContent = `No assigned driver shift matches the current system time (${currentFleetTimeLabel()}).`;
                return activeFuelDriver;
            }

            input.value = String(activeFuelDriver.driverName || activeFuelDriver.driver || '').trim();
            const shiftName = String(activeFuelDriver.shiftName || activeFuelDriver.shift || '').trim();
            const start = String(activeFuelDriver.shiftStartTime || '').trim();
            const end = String(activeFuelDriver.shiftEndTime || '').trim();
            const shiftTime = start || end ? `${start || '--:--'} - ${end || '--:--'}` : '';
            hint.textContent = shiftName || shiftTime
                ? `Current shift: ${[shiftName, shiftTime].filter(Boolean).join(' • ')} • System time: ${currentFleetTimeLabel()}`
                : 'Assigned driver loaded from the selected contract and vehicle.';
            return activeFuelDriver;
        }

        function setFuelEntryMode(prefix, fuelName, rateInfo = null) {
            const directAmount = isDirectAmountFuel(fuelName);
            const qtyLabel = $(`#${prefix}QtyLabel`);
            const qtyInput = $(`#${prefix}Qty`);
            const qtyHint = $(`#${prefix}QtyHint`);
            const rateLabel = $(`#${prefix}RateLabel`);
            const rateHint = $(`#${prefix}RateHint`);
            const amountLabel = $(`#${prefix}AmountLabel`);

            if (directAmount) {
                if (qtyLabel) qtyLabel.innerHTML = `Fuel Cost (Taka)${prefix === 'primary' ? ' <span class="req">*</span>' : ''}`;
                if (qtyInput) qtyInput.placeholder = 'Enter total amount in Taka';
                if (qtyHint) qtyHint.textContent = '';
                if (rateLabel) rateLabel.textContent = 'Fuel Rate';
                if (rateHint) rateHint.textContent = '';
                if (amountLabel) amountLabel.textContent = 'Fuel Cost';
                setValue(`#${prefix}Rate`, '');
            } else {
                if (qtyLabel) qtyLabel.innerHTML = `Quantity (Liter)${prefix === 'primary' ? ' <span class="req">*</span>' : ''}`;
                if (qtyInput) qtyInput.placeholder = 'Enter liters';
                if (qtyHint) qtyHint.textContent = '';
                if (rateLabel) rateLabel.textContent = 'Rate per Liter';
                if (rateHint) rateHint.textContent = '';
                if (amountLabel) amountLabel.textContent = 'Calculated Amount';
                setValue(`#${prefix}Rate`, rateInfo?.price ? Number(rateInfo.price || 0).toFixed(2) : '');
            }
        }

        function setSecondaryRequiredState(enabled) {
            const fuelName = value('#secondaryFuelName');
            ['#secondaryFuelName', '#secondaryStation', '#secondaryQty'].forEach((selector) => {
                const element = $(selector);
                if (!element) return;
                element.required = Boolean(enabled);
                element.setAttribute('aria-required', enabled ? 'true' : 'false');
            });
            const rate = $('#secondaryRate');
            const rateRequired = Boolean(enabled && fuelName && !isDirectAmountFuel(fuelName));
            if (rate) {
                rate.required = rateRequired;
                rate.setAttribute('aria-required', rateRequired ? 'true' : 'false');
            }
        }

        function updateVehicles() {
            const contract = selectedContract();
            const input = $('#vehicleSelect');
            const list = $('#vehicleSelectList');
            if (!input || !list) return;

            input.value = '';
            input.disabled = true;
            input.placeholder = 'Select contract first';
            list.innerHTML = '';

            if (!contract) {
                clearVehicleSetup('Select a contract first. Vehicle list will load from that contract.');
                return;
            }

            const vehicles = contract.vehicles || [];
            if (!vehicles.length) {
                input.placeholder = 'No vehicle assigned in this contract';
                clearVehicleSetup('This contract has no vehicle assignment. Add a vehicle assignment in the Contract page first.');
                return;
            }

            vehicles.forEach((vehicle) => {
                const option = document.createElement('option');
                option.value = vehicleSearchLabel(vehicle);
                option.label = String(vehicle.id || '');
                list.appendChild(option);
            });

            input.disabled = false;
            input.placeholder = 'Search and select vehicle from contract';
            clearVehicleSetup(`${vehicles.length} vehicle${vehicles.length === 1 ? '' : 's'} available for this contract. Search and select the required vehicle.`);
        }

        function clearVehicleSetup(note) {
            setValue('#primaryFuelName', '');
            setValue('#primaryRate', '');
            setValue('#primaryQty', '');
            setValue('#primaryAmount', money(0));
            setValue('#secondaryFuelName', '');
            setValue('#secondaryRate', '');
            setValue('#secondaryQty', 0);
            setValue('#secondaryAmount', money(0));
            populateStationSelect('#primaryStation', '', '#primaryStationHint');
            populateStationSelect('#secondaryStation', '', '#secondaryStationHint');
            setFuelEntryMode('primary', '', null);
            setFuelEntryMode('secondary', '', null);
            setValue('#startKm', '');
            setValue('#endKm', '');
            setValue('#totalKm', '');
            setValue('#mileage', '');
            setValue('#tkKm', '');
            $('#totalAmount').textContent = money(0);
            const toggle = $('#hasSecondaryFuel');
            if (toggle) {
                toggle.checked = false;
                toggle.disabled = true;
            }
            const secondaryBlock = $('#secondaryFuelBlock');
            if (secondaryBlock) secondaryBlock.style.display = 'none';
            setSecondaryRequiredState(false);
            activeFuelDriver = null;
            setValue('#assignedFuelDriver', '');
            const assignedDriverHint = $('#assignedFuelDriverShift');
            if (assignedDriverHint) assignedDriverHint.textContent = 'The assigned driver will appear automatically.';
            const vehicleSetupNote = $('#vehicleSetupNote');
            if (vehicleSetupNote) {
                vehicleSetupNote.textContent = note || '';
                vehicleSetupNote.hidden = !vehicleSetupNote.textContent;
            }
        }

        function updateVehicleSetup() {
            const vehicle = selectedVehicle();
            if (!vehicle) {
                clearVehicleSetup('Select a vehicle to load its primary / secondary fuel and latest active fuel price.');
                return;
            }

            const primaryFuel = vehicle.primary || '';
            const secondaryFuel = vehicle.secondary || '';
            const primaryRate = latestRateForFuel(primaryFuel) || vehicle.primaryRateInfo || null;
            const secondaryRate = latestRateForFuel(secondaryFuel) || vehicle.secondaryRateInfo || null;

            setValue('#primaryFuelName', primaryFuel);
            setValue('#secondaryFuelName', secondaryFuel);
            setValue('#primaryQty', '');
            setValue('#secondaryQty', 0);
            setValue('#primaryAmount', money(0));
            setValue('#secondaryAmount', money(0));
            setFuelEntryMode('primary', primaryFuel, primaryRate);
            setFuelEntryMode('secondary', secondaryFuel, secondaryRate);
            populateStationSelect('#primaryStation', primaryFuel, '#primaryStationHint');
            populateStationSelect('#secondaryStation', secondaryFuel, '#secondaryStationHint');
            setValue('#startKm', vehicle.startKm ?? vehicle.odo ?? vehicle.lastOdo ?? '');
            setValue('#endKm', '');
            setValue('#totalKm', '');
            setValue('#mileage', '');
            setValue('#tkKm', '');

            const toggle = $('#hasSecondaryFuel');
            const secondaryAvailable = Boolean(secondaryFuel);
            if (toggle) {
                toggle.disabled = !secondaryAvailable;
                toggle.checked = false;
            }
            const secondaryBlock = $('#secondaryFuelBlock');
            if (secondaryBlock) secondaryBlock.style.display = 'none';
            setSecondaryRequiredState(false);
            refreshAssignedFuelDriver();

            ['#vehicleSelect', '#primaryFuelName', '#primaryRate', '#startKm'].forEach((selector) => {
                const element = $(selector);
                if (String(element?.value || '').trim()) clearRechargeFieldError(element);
            });

            const vehicleSetupNote = $('#vehicleSetupNote');
            if (vehicleSetupNote) {
                vehicleSetupNote.textContent = '';
                vehicleSetupNote.hidden = true;
            }
            recalculate();
        }

        function rechargeMileageQuantity(primaryName, primaryEntered, secondaryName, secondaryEntered) {
            const primaryLitres = isDirectAmountFuel(primaryName) ? 0 : primaryEntered;
            const secondaryLitres = isDirectAmountFuel(secondaryName) ? 0 : secondaryEntered;
            return primaryLitres + secondaryLitres;
        }

        function fuelEntryValues(prefix, fuelName, enabled = true) {
            const enteredValue = enabled ? Number(value(`#${prefix}Qty`) || 0) : 0;
            const directAmount = isDirectAmountFuel(fuelName);
            const rate = enabled && !directAmount ? Number(value(`#${prefix}Rate`) || 0) : 0;
            const qty = directAmount ? 0 : enteredValue;
            const amount = directAmount ? enteredValue : enteredValue * rate;
            return {
                enteredValue,
                directAmount,
                qty,
                rate,
                amount,
                pricingMode: directAmount ? 'direct_amount' : 'per_liter',
                entryUnit: directAmount ? 'Taka' : 'Liter',
            };
        }

        function recalculate() {
            const primaryName = value('#primaryFuelName');
            const secondaryName = value('#secondaryFuelName');
            const hasSecondary = Boolean($('#hasSecondaryFuel')?.checked);
            const primary = fuelEntryValues('primary', primaryName, true);
            const secondary = fuelEntryValues('secondary', secondaryName, hasSecondary);

            const totalFuelPrice = primary.amount + secondary.amount;
            setValue('#primaryAmount', money(primary.amount));
            setValue('#secondaryAmount', money(secondary.amount));
            $('#totalAmount').textContent = money(totalFuelPrice);

            const startKm = Number(value('#startKm') || 0);
            const endKm = Number(value('#endKm') || 0);
            const totalKm = startKm >= 0 && endKm > startKm ? endKm - startKm : 0;
            setValue('#totalKm', totalKm > 0 ? totalKm : '');

            const mileageQty = rechargeMileageQuantity(primaryName, primary.enteredValue, secondaryName, secondary.enteredValue);
            const mileageValue = totalKm > 0 && mileageQty > 0 ? totalKm / mileageQty : 0;
            const tkKmValue = totalKm > 0 && totalFuelPrice > 0 ? totalFuelPrice / totalKm : 0;
            setValue('#mileage', mileageValue > 0 ? mileageValue.toFixed(2) : '');
            setValue('#tkKm', tkKmValue > 0 ? tkKmValue.toFixed(2) : '');
        }

        function updateCounter() {
            const requiredKeys = photoRequirements.filter((photo) => photo.required).map((photo) => photo.key);
            const done = requiredKeys.filter((key) => photoState[key]?.captured).length;
            const hasOptional = photoRequirements.some((photo) => !photo.required && photoState[photo.key]?.captured);
            $('#photoCount').textContent = `${done} / ${requiredKeys.length} required${hasOptional ? ' + other' : ''}`;
        }

        function fallbackPlaceNameFromCoordinates(lat, lng) {
            if (lat >= 23.85 && lat <= 23.92 && lng >= 90.35 && lng <= 90.43) return 'Uttara, Dhaka';
            if (lat >= 23.72 && lat <= 23.82 && lng >= 90.36 && lng <= 90.43) return 'Banani / Mohakhali, Dhaka';
            if (lat >= 23.68 && lat <= 23.75 && lng >= 90.38 && lng <= 90.45) return 'Motijheel / Central Dhaka';
            if (lat >= 23.95 && lat <= 24.15 && lng >= 90.35 && lng <= 90.48) return 'Gazipur / Tongi Area';
            if (lat >= 22.30 && lat <= 22.42 && lng >= 91.75 && lng <= 91.90) return 'Chattogram City Area';
            return 'Place name unavailable';
        }

        function formatReverseGeocodeAddress(address = {}) {
            const parts = [
                address.road || address.neighbourhood || address.suburb || address.quarter,
                address.city || address.town || address.village || address.municipality || address.county,
                address.state || address.division,
                address.country,
            ].filter(Boolean);
            return [...new Set(parts)].join(', ');
        }

        async function reverseGeocodePlaceName(latitude, longitude) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`;
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return '';
                const result = await response.json();
                return formatReverseGeocodeAddress(result.address || {}) || result.display_name || '';
            } catch (_) {
                return '';
            }
        }

        const fuelPhotoUploadSettings = {
            maxDimension: 1600,
            jpegQuality: 0.72,
            maxBytes: 8 * 1024 * 1024,
        };

        function fuelPhotoDimensions(width, height, maxDimension = fuelPhotoUploadSettings.maxDimension) {
            const safeWidth = Math.max(1, Number(width || 0));
            const safeHeight = Math.max(1, Number(height || 0));
            const longest = Math.max(safeWidth, safeHeight);
            const scale = longest > maxDimension ? maxDimension / longest : 1;

            return {
                width: Math.max(1, Math.round(safeWidth * scale)),
                height: Math.max(1, Math.round(safeHeight * scale)),
            };
        }

        function canvasToJpegBlob(canvas, quality = fuelPhotoUploadSettings.jpegQuality) {
            return new Promise((resolve) => {
                if (!canvas?.toBlob) {
                    resolve(null);
                    return;
                }
                canvas.toBlob((blob) => resolve(blob), 'image/jpeg', quality);
            });
        }

        function imageElementFromFile(file) {
            return new Promise((resolve, reject) => {
                const url = URL.createObjectURL(file);
                const image = new Image();
                image.onload = () => {
                    URL.revokeObjectURL(url);
                    resolve(image);
                };
                image.onerror = () => {
                    URL.revokeObjectURL(url);
                    reject(new Error('The captured photo could not be prepared. Please retake it.'));
                };
                image.src = url;
            });
        }

        function optimizedFuelPhotoName(file, key = 'fuel-photo') {
            const baseName = String(file?.name || key || 'fuel-photo')
                .replace(/\.[^.]+$/, '')
                .replace(/[^a-zA-Z0-9_-]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'fuel-photo';

            return `${baseName}-${Date.now()}.jpg`;
        }

        async function optimizeFuelRechargePhoto(file, key = 'fuel-photo') {
            if (!file || !String(file.type || '').toLowerCase().startsWith('image/')) return file;
            if (String(file.type || '').toLowerCase() === 'image/svg+xml') return file;

            try {
                const image = await imageElementFromFile(file);
                const sourceWidth = image.naturalWidth || image.width;
                const sourceHeight = image.naturalHeight || image.height;
                if (!sourceWidth || !sourceHeight) return file;

                const dimensions = fuelPhotoDimensions(sourceWidth, sourceHeight);
                const canvas = document.createElement('canvas');
                canvas.width = dimensions.width;
                canvas.height = dimensions.height;
                const context = canvas.getContext('2d', { alpha: false });
                if (!context) return file;

                context.drawImage(image, 0, 0, dimensions.width, dimensions.height);
                const blob = await canvasToJpegBlob(canvas);
                if (!blob) return file;

                const optimizedFile = new File([blob], optimizedFuelPhotoName(file, key), {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });

                return optimizedFile.size > 0 ? optimizedFile : file;
            } catch (error) {
                console.warn('Fuel recharge photo optimization failed. Uploading original file.', error);
                return file;
            }
        }

        function requestCurrentPlace() {
            return new Promise((resolve) => {
                if (!navigator.geolocation) {
                    resolve({ place: 'Location not supported', placeName: 'Location not supported', error: 'Browser location service is not available.' });
                    return;
                }
                navigator.geolocation.getCurrentPosition(async (position) => {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    const resolvedPlace = await reverseGeocodePlaceName(latitude, longitude);
                    const place = resolvedPlace || fallbackPlaceNameFromCoordinates(latitude, longitude);
                    resolve({
                        place,
                        placeName: place,
                        capturedAt: new Date().toISOString(),
                        source: resolvedPlace ? 'reverse_geocode' : 'device_location_fallback',
                    });
                }, (error) => {
                    resolve({ place: 'Location unavailable', placeName: 'Location unavailable', error: error.message });
                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
            });
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
                    <div class="stage"><img alt="${escapeHtml(photo.title)} preview"><div class="stage-empty"><div class="icon">${escapeHtml(photo.icon)}</div>Tap below to open camera</div></div>
                    <div class="photo-actions">
                        <button class="btn primary take-btn" type="button">📷 Open Camera</button>
                        <button class="btn light retake-btn" type="button" disabled>Retake</button>
                        <button class="btn danger clear-btn" type="button" disabled>Clear</button>
                    </div>
                    <input class="photoTempFile" type="hidden" value="">
                    <div class="temp-upload-progress hidden photoUploadProgress"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div>
                    <div class="photo-upload-info"></div>
                    <div class="photo-meta"><div class="meta-row"><small>Date & Time</small><b class="cap-time">Not captured yet</b></div><div class="meta-row"><small>Place</small><b class="cap-place">Not captured yet</b></div></div>
                </div>`).join('');
            bindPhotoEvents();
        }

        function renderCameraSupportNotice() {
            const list = $('#photoList');
            if (!list) return;

            let notice = $('#fuelCameraSupportNotice');
            if (!notice) {
                notice = document.createElement('div');
                notice.id = 'fuelCameraSupportNotice';
                notice.className = 'camera-support-notice';
                list.parentNode.insertBefore(notice, list);
            }

            if (!window.isSecureContext) {
                notice.className = 'camera-support-notice warning';
                notice.innerHTML = '<b>HTTPS is required for live camera preview.</b><span>This page is open over HTTP. The button will try the phone camera fallback, but the deployed site should use a valid HTTPS certificate.</span>';
                notice.hidden = false;
                return;
            }

            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                notice.className = 'camera-support-notice warning';
                notice.innerHTML = '<b>Live preview is unavailable in this browser.</b><span>The button will use the device camera capture screen instead.</span>';
                notice.hidden = false;
                return;
            }

            notice.hidden = true;
        }

        function ensureNativeCameraInput() {
            let input = $('#fuelNativeCameraInput');
            if (input) return input;

            input = document.createElement('input');
            input.id = 'fuelNativeCameraInput';
            input.type = 'file';
            input.accept = 'image/*';
            input.setAttribute('capture', 'environment');
            input.className = 'hidden-camera-input';
            input.setAttribute('aria-hidden', 'true');
            input.tabIndex = -1;
            document.body.appendChild(input);

            input.addEventListener('change', async () => {
                const key = activeCameraKey;
                const file = input.files?.[0] || null;
                input.value = '';

                if (!key || !file) {
                    activeCameraKey = null;
                    return;
                }
                if (!file.type || !file.type.startsWith('image/')) {
                    activeCameraKey = null;
                    toast('Please capture a valid image file.');
                    return;
                }

                activeCameraKey = null;
                await saveCapturedPhoto(key, file);
            });

            return input;
        }

        function openNativeCamera(key, message = 'Opening the device camera...') {
            closeCamera();
            activeCameraKey = key;
            const input = ensureNativeCameraInput();
            toast(message);
            input.click();
        }

        function cameraErrorMessage(error) {
            const name = error?.name || '';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                return 'Camera permission is blocked. Allow Camera permission for this site in the browser settings, then try again.';
            }
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                return 'No usable camera was found on this device.';
            }
            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'The camera is already being used by another app or browser tab. Close it there and try again.';
            }
            if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
                return 'The requested rear camera mode is unavailable on this device.';
            }
            if (name === 'SecurityError') {
                return 'The browser security policy blocked camera access. Confirm HTTPS and the server camera permission policy.';
            }
            if (name === 'AbortError') {
                return 'Camera startup was interrupted. Please try again.';
            }
            return 'Unable to open the camera' + (error?.message ? ': ' + error.message : '.');
        }

        function ensureCameraModal() {
            let modal = $('#fuelCameraModal');
            if (modal) return modal;
            modal = document.createElement('div');
            modal.id = 'fuelCameraModal';
            modal.className = 'fuel-camera-modal hidden';
            modal.innerHTML = `
                <div class="fuel-camera-panel" role="dialog" aria-modal="true" aria-labelledby="fuelCameraTitle">
                    <div class="fuel-camera-head"><strong id="fuelCameraTitle">Take Live Photo</strong><button class="mini-btn" type="button" id="fuelCameraCloseBtn">Close</button></div>
                    <div class="fuel-camera-video-wrap">
                        <video id="fuelCameraVideo" autoplay muted playsinline></video>
                        <div class="fuel-camera-loading" id="fuelCameraStatus">Requesting camera permission...</div>
                    </div>
                    <canvas id="fuelCameraCanvas" class="hidden"></canvas>
                    <div class="fuel-camera-actions">
                        <button class="btn green" type="button" id="fuelCameraCaptureBtn" disabled>Capture Photo</button>
                        <button class="btn light" type="button" id="fuelNativeCameraBtn">Use Device Camera</button>
                    </div>
                    <p class="fuel-camera-note">Allow Camera permission when prompted. Live preview requires HTTPS. The device-camera option is available as a mobile fallback; some browsers may also show existing photos.</p>
                </div>`;
            document.body.appendChild(modal);
            $('#fuelCameraCloseBtn', modal)?.addEventListener('click', closeCamera);
            $('#fuelCameraCaptureBtn', modal)?.addEventListener('click', captureCameraPhoto);
            $('#fuelNativeCameraBtn', modal)?.addEventListener('click', () => {
                const key = activeCameraKey;
                if (key) openNativeCamera(key, 'Opening the device camera fallback...');
            });
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeCamera();
            });
            return modal;
        }

        async function waitForCameraVideo(video) {
            if (video.readyState >= 2 && video.videoWidth > 0) return;
            await new Promise((resolve, reject) => {
                const timeout = window.setTimeout(() => {
                    cleanup();
                    reject(new Error('Camera preview timed out.'));
                }, 12000);
                const onReady = () => {
                    cleanup();
                    resolve();
                };
                const onError = () => {
                    cleanup();
                    reject(new Error('Camera preview could not start.'));
                };
                const cleanup = () => {
                    window.clearTimeout(timeout);
                    video.removeEventListener('loadedmetadata', onReady);
                    video.removeEventListener('canplay', onReady);
                    video.removeEventListener('error', onError);
                };
                video.addEventListener('loadedmetadata', onReady, { once: true });
                video.addEventListener('canplay', onReady, { once: true });
                video.addEventListener('error', onError, { once: true });
            });
        }

        async function requestCameraStream() {
            const preferredConstraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            };

            try {
                return await navigator.mediaDevices.getUserMedia(preferredConstraints);
            } catch (error) {
                const canRetryWithBasicVideo = ['OverconstrainedError', 'ConstraintNotSatisfiedError', 'NotFoundError', 'DevicesNotFoundError'].includes(error?.name);
                if (!canRetryWithBasicVideo) throw error;
                return navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            }
        }

        async function openCamera(key) {
            renderCameraSupportNotice();

            if (!window.isSecureContext) {
                openNativeCamera(key, 'Live preview needs HTTPS. Opening the phone camera fallback...');
                return;
            }
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                openNativeCamera(key, 'Live preview is unavailable. Opening the device camera fallback...');
                return;
            }

            closeCamera();
            activeCameraKey = key;
            const modal = ensureCameraModal();
            const video = $('#fuelCameraVideo', modal);
            const status = $('#fuelCameraStatus', modal);
            const captureButton = $('#fuelCameraCaptureBtn', modal);
            if (status) {
                status.textContent = 'Requesting camera permission...';
                status.classList.remove('hidden');
            }
            if (captureButton) captureButton.disabled = true;
            modal.classList.remove('hidden');

            try {
                activeStream = await requestCameraStream();
                video.srcObject = activeStream;
                await video.play().catch(() => {});
                await waitForCameraVideo(video);
                if (status) status.classList.add('hidden');
                if (captureButton) captureButton.disabled = false;
                toast('Camera opened. Capture the required photo.');
            } catch (error) {
                const message = cameraErrorMessage(error);
                if (activeStream) {
                    activeStream.getTracks().forEach((track) => track.stop());
                    activeStream = null;
                }
                if (video) {
                    video.pause?.();
                    video.srcObject = null;
                }
                if (captureButton) captureButton.disabled = true;
                if (status) {
                    status.textContent = `${message} Tap “Use Device Camera” below to continue.`;
                    status.classList.remove('hidden');
                }
                toast(message);
                console.warn('FleetMan camera access failed:', error);
            }
        }

        function closeCamera() {
            if (activeStream) {
                activeStream.getTracks().forEach((track) => track.stop());
                activeStream = null;
            }
            const modal = $('#fuelCameraModal');
            const video = $('#fuelCameraVideo');
            const captureButton = $('#fuelCameraCaptureBtn');
            if (video) {
                video.pause?.();
                video.srcObject = null;
            }
            if (captureButton) captureButton.disabled = true;
            if (modal) modal.classList.add('hidden');
            activeCameraKey = null;
        }

        async function saveCapturedPhoto(key, file) {
            if (!key || !file) return;
            const card = $(`.photo-card[data-key="${String(key).replace(/[^a-zA-Z0-9_-]/g, '')}"]`);
            if (!card) return;
            if (photoState[key]?.preview) URL.revokeObjectURL(photoState[key].preview);

            const hidden = $('.photoTempFile', card);
            const info = $('.photo-upload-info', card);
            const progress = $('.photoUploadProgress', card);
            uploadManager.render({
                info,
                progress,
                file: { uploading: true, progress: 0 },
                message: 'Optimizing photo for faster mobile upload...',
            });

            const uploadFile = await optimizeFuelRechargePhoto(file, key);
            const capturedAt = new Date();
            const preview = URL.createObjectURL(uploadFile);
            photoState[key] = {
                ...(photoState[key] || {}),
                captured: true,
                file: uploadFile,
                fileData: {},
                preview,
                capturedAt: capturedAt.toISOString(),
                displayTime: capturedAt.toLocaleString(),
                place: 'Getting place name...',
            };
            updatePhotoCard(key);
            updateCounter();
            clearRechargePhotoError(key);

            const uploadPromise = uploadManager.upload(null, {
                file: uploadFile,
                kind: 'image',
                queue: true,
                queueKey: 'fuel-recharge-photos',
                queueConcurrency: 2,
                timeoutMs: 120000,
                promiseTarget: card,
                hidden,
                info,
                progress,
                extensions: ['jpg', 'jpeg', 'png', 'webp'],
                imageOnly: true,
                maxBytes: fuelPhotoUploadSettings.maxBytes,
                showPreview: false,
                onSuccess: (uploaded) => {
                    photoState[key] = { ...(photoState[key] || {}), fileData: uploaded };
                    clearRechargePhotoError(key);
                },
                onError: (message) => {
                    photoState[key] = { ...(photoState[key] || {}), fileData: {} };
                    markRechargePhotoInvalid(key, message);
                },
            });

            const [uploaded, location] = await Promise.all([uploadPromise, requestCurrentPlace()]);
            photoState[key] = {
                ...(photoState[key] || {}),
                ...location,
                fileData: uploaded || photoState[key]?.fileData || {},
                place: location.place || 'Location unavailable',
            };
            updatePhotoCard(key);
            updateCounter();
            if (uploaded) toast('Photo uploaded temporarily with time and place. Save the form to keep it.');
        }

        function captureCameraPhoto() {
            if (!activeCameraKey) return;
            const modal = $('#fuelCameraModal');
            const video = $('#fuelCameraVideo', modal);
            const canvas = $('#fuelCameraCanvas', modal);
            if (!video || !canvas || !video.videoWidth || video.readyState < 2) {
                toast('Camera is still loading. Please try again in a moment.');
                return;
            }

            const key = activeCameraKey;
            const dimensions = fuelPhotoDimensions(video.videoWidth, video.videoHeight);
            canvas.width = dimensions.width;
            canvas.height = dimensions.height;
            const context = canvas.getContext('2d', { alpha: false });
            if (!context) {
                toast('Could not prepare the camera image. Please try the device camera option.');
                return;
            }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(async (blob) => {
                if (!blob) {
                    toast('Could not capture photo. Please try again.');
                    return;
                }
                const file = new File([blob], `${key}-${Date.now()}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
                closeCamera();
                await saveCapturedPhoto(key, file);
            }, 'image/jpeg', fuelPhotoUploadSettings.jpegQuality);
        }

        function updatePhotoCard(key) {
            const card = $(`.photo-card[data-key="${String(key).replace(/[^a-zA-Z0-9_-]/g, '')}"]`);
            if (!card) return;
            const state = photoState[key] || {};
            const img = $('img', card);
            const empty = $('.stage-empty', card);
            const pill = $('.photo-pill', card);
            const retake = $('.retake-btn', card);
            const clear = $('.clear-btn', card);
            const time = $('.cap-time', card);
            const place = $('.cap-place', card);

            if (state.captured) {
                if (img && state.preview) {
                    img.src = state.preview;
                    img.style.display = 'block';
                }
                if (empty) empty.style.display = 'none';
                if (pill) {
                    pill.textContent = 'Captured';
                    pill.classList.remove('optional');
                    pill.classList.add('done');
                }
                if (retake) retake.disabled = false;
                if (clear) clear.disabled = false;
                if (time) time.textContent = state.displayTime || (state.capturedAt ? new Date(state.capturedAt).toLocaleString() : 'Captured');
                if (place) place.textContent = state.place || 'Place unavailable';
            } else {
                if (img) {
                    img.removeAttribute('src');
                    img.style.display = 'none';
                }
                if (empty) empty.style.display = 'block';
                if (pill) {
                    pill.textContent = card.dataset.required === 'yes' ? 'Pending' : 'Optional';
                    pill.classList.remove('done');
                    if (card.dataset.required !== 'yes') pill.classList.add('optional');
                }
                if (retake) retake.disabled = true;
                if (clear) clear.disabled = true;
                if (time) time.textContent = 'Not captured yet';
                if (place) place.textContent = 'Not captured yet';
            }
        }

        function bindPhotoEvents() {
            $$('.photo-card').forEach((card) => {
                const key = card.dataset.key;
                $('.take-btn', card)?.addEventListener('click', () => openCamera(key));
                $('.retake-btn', card)?.addEventListener('click', () => openCamera(key));
                $('.clear-btn', card)?.addEventListener('click', () => {
                    if (photoState[key]?.preview) URL.revokeObjectURL(photoState[key].preview);
                    card._fleetUploadSequence = Number(card._fleetUploadSequence || 0) + 1;
                    const hidden = $('.photoTempFile', card);
                    const previous = uploadManager.readHidden(hidden);
                    if (previous.tempToken) uploadManager.destroy(previous.tempToken).catch(() => {});
                    uploadManager.writeHidden(hidden, {});
                    uploadManager.render({ info: $('.photo-upload-info', card), progress: $('.photoUploadProgress', card), file: {} });
                    photoState[key] = { captured: false, file: null, fileData: {}, preview: '', capturedAt: '', displayTime: '', place: '' };
                    updatePhotoCard(key);
                    updateCounter();
                    clearRechargePhotoError(key);
                });
            });
        }

        function collectPhotoPayload() {
            return Object.fromEntries(Object.entries(photoState).map(([key, state]) => [key, {
                captured: Boolean(state.captured),
                capturedAt: state.capturedAt || '',
                time: state.displayTime || '',
                place: state.place || '',
                placeName: state.place || '',
                file: state.fileData || {},
            }]));
        }

        function restoreRechargePhotos(row = {}) {
            const savedPhotos = row.photos && typeof row.photos === 'object' ? row.photos : {};

            Object.keys(photoState).forEach((key) => {
                const saved = savedPhotos[key] && typeof savedPhotos[key] === 'object' ? savedPhotos[key] : {};
                const nestedFile = saved.file && typeof saved.file === 'object' ? saved.file : {};
                const fileData = Object.keys(nestedFile).length ? nestedFile : saved;
                const hasFile = Boolean(fileData.tempToken || fileData.filePath || fileData.fileUrl || fileData.previewUrl || fileData.url);
                const capturedAt = saved.capturedAt || saved.uploadedAt || '';
                const preview = hasFile ? uploadManager.permanentUrl(fileData) : '';
                const card = $(`.photo-card[data-key="${String(key).replace(/[^a-zA-Z0-9_-]/g, '')}"]`);
                const hidden = $('.photoTempFile', card);

                photoState[key] = {
                    captured: hasFile,
                    file: null,
                    fileData: hasFile ? fileData : {},
                    preview,
                    capturedAt,
                    displayTime: saved.time || (capturedAt ? new Date(capturedAt).toLocaleString() : ''),
                    place: saved.place || saved.placeName || '',
                };

                uploadManager.writeHidden(hidden, hasFile ? fileData : {});
                uploadManager.render({
                    info: $('.photo-upload-info', card),
                    progress: $('.photoUploadProgress', card),
                    file: hasFile ? fileData : {},
                    showPreview: false,
                });
                updatePhotoCard(key);
            });

            updateCounter();
        }

        function resetFuelRechargeForm() {
            closeCamera();
            clearRechargeValidation();
            editingRechargeId = '';
            editingRechargeDate = '';
            setValue('#contractSelect', '');
            updateVehicles();
            Object.keys(photoState).forEach((key) => {
                if (photoState[key]?.preview) URL.revokeObjectURL(photoState[key].preview);
                const card = $(`.photo-card[data-key="${String(key).replace(/[^a-zA-Z0-9_-]/g, '')}"]`);
                if (card) card._fleetUploadSequence = Number(card._fleetUploadSequence || 0) + 1;
                const hidden = $('.photoTempFile', card);
                const previous = uploadManager.readHidden(hidden);
                if (previous.tempToken) uploadManager.destroy(previous.tempToken).catch(() => {});
                uploadManager.writeHidden(hidden, {});
                uploadManager.render({ info: $('.photo-upload-info', card), progress: $('.photoUploadProgress', card), file: {} });
                photoState[key] = { captured: false, file: null, fileData: {}, preview: '', capturedAt: '', displayTime: '', place: '' };
            });
            $$('.photo-card').forEach((card) => updatePhotoCard(card.dataset.key));
            updateCounter();
            setValue('#primaryStation', '');
            setValue('#secondaryStation', '');
            setValue('#endKm', '');
            setValue('#totalKm', '');
            setValue('#mileage', '');
            setValue('#tkKm', '');
            setValue('#rechargeRemarks', '');
            recalculate();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function collectRecharge(statusOverride, submitLocation = null) {
            const contract = selectedContract();
            const vehicle = selectedVehicle();
            const rechargeId = editingRechargeId || nextRechargeId();
            const primaryName = value('#primaryFuelName') || vehicle?.primary || '';
            const secondaryEnabled = Boolean($('#hasSecondaryFuel')?.checked);
            const secondaryName = secondaryEnabled ? (value('#secondaryFuelName') || vehicle?.secondary || '') : '';
            const primary = fuelEntryValues('primary', primaryName, true);
            const secondary = fuelEntryValues('secondary', secondaryName, secondaryEnabled);
            const endKm = Number(value('#endKm') || 0);
            const startKm = Number(value('#startKm') || vehicle?.startKm || vehicle?.odo || vehicle?.lastOdo || 0);
            const totalKm = startKm >= 0 && endKm > startKm ? endKm - startKm : 0;
            const mileageQty = rechargeMileageQuantity(primaryName, primary.enteredValue, secondaryName, secondary.enteredValue);
            const diesel = (normalizeFuelName(primaryName) === 'diesel' ? primary.qty : 0)
                + (normalizeFuelName(secondaryName) === 'diesel' ? secondary.qty : 0);
            const octane = (normalizeFuelName(primaryName) === 'octane' ? primary.qty : 0)
                + (normalizeFuelName(secondaryName) === 'octane' ? secondary.qty : 0);
            const gas = (primary.directAmount ? primary.amount : 0) + (secondary.directAmount ? secondary.amount : 0);
            const currentDriver = resolveActiveFuelDriver(vehicle || {}) || activeFuelDriver;

            return {
                rechargeId,
                stationFuelFilterVersion: 1,
                rechargeValidationVersion: 2,
                date: editingRechargeDate || new Date().toISOString().slice(0, 10),
                contractId: contract?.contractId || contract?.id || '',
                contract: contractSearchLabel(contract || {}),
                contractLabel: contractSearchLabel(contract || {}),
                vehicleId: vehicle?.id || '',
                vehicle: vehicle?.label || vehicle?.name || value('#vehicleSelect') || '',
                vehicleLabel: vehicle?.label || vehicle?.name || value('#vehicleSelect') || '',
                car: vehicle?.label || vehicle?.name || value('#vehicleSelect') || '',
                driverId: currentDriver?.driverId || '',
                driver: currentDriver?.driverName || currentDriver?.driver || '',
                driverName: currentDriver?.driverName || currentDriver?.driver || '',
                driverShiftId: currentDriver?.shiftId || '',
                driverShift: currentDriver?.shiftName || currentDriver?.shift || '',
                driverShiftStartTime: currentDriver?.shiftStartTime || '',
                driverShiftEndTime: currentDriver?.shiftEndTime || '',
                driverStart: '',
                driverEnd: '',
                totalTime: 0,
                primaryFuelName: primaryName,
                primaryStation: value('#primaryStation'),
                primaryFuelStation: value('#primaryStation'),
                fuelStation: value('#primaryStation'),
                primaryEnteredValue: primary.enteredValue,
                primaryQty: primary.qty,
                primaryRate: primary.rate,
                primaryAmount: primary.amount,
                primaryPricingMode: primary.pricingMode,
                primaryEntryUnit: primary.entryUnit,
                primaryFuelUnit: primary.entryUnit,
                hasSecondaryFuel: secondaryEnabled,
                secondaryFuelName: secondaryName,
                secondaryStation: secondaryEnabled ? value('#secondaryStation') : '',
                secondaryFuelStation: secondaryEnabled ? value('#secondaryStation') : '',
                secondaryEnteredValue: secondary.enteredValue,
                secondaryQty: secondary.qty,
                secondaryRate: secondary.rate,
                secondaryAmount: secondary.amount,
                secondaryPricingMode: secondaryEnabled ? secondary.pricingMode : '',
                secondaryEntryUnit: secondaryEnabled ? secondary.entryUnit : '',
                secondaryFuelUnit: secondaryEnabled ? secondary.entryUnit : '',
                liquidFuelLitres: mileageQty,
                diesel,
                gas,
                octane,
                startKm,
                endKm,
                odoReading: endKm,
                totalKm,
                mileage: totalKm > 0 && mileageQty > 0 ? +(totalKm / mileageQty).toFixed(2) : 0,
                totalAmount: primary.amount + secondary.amount,
                tkKm: totalKm > 0 && (primary.amount + secondary.amount) > 0
                    ? +((primary.amount + secondary.amount) / totalKm).toFixed(2)
                    : 0,
                status: statusOverride || 'Submitted',
                submittedBy: value('#submittedBy') || data.account?.name || 'Logged-in User',
                fuelType: secondaryEnabled && secondaryName ? `${primaryName} + ${secondaryName}` : primaryName,
                remarks: value('#rechargeRemarks'),
                photos: collectPhotoPayload(),
            };
        }

        async function saveRecharge(statusOverride, submitLocation = null) {
            await uploadManager.waitForInputs($$('.photo-card'));
            const row = collectRecharge(statusOverride, submitLocation);
            const previousRows = JSON.parse(JSON.stringify(recharges || []));
            const existingIndex = editingRechargeId
                ? recharges.findIndex((item) => item.rechargeId === editingRechargeId)
                : -1;

            if (existingIndex >= 0) recharges[existingIndex] = row;
            else recharges.unshift(row);

            const result = await syncFuelRecharges(recharges);
            if (result?.syncFailed || result?.ok === false) {
                recharges = previousRows;
                return null;
            }
            if (Array.isArray(result?.rows)) recharges = result.rows;
            return recharges.find((item) => item.rechargeId === row.rechargeId) || row;
        }

        function rechargeFieldContainer(element) {
            return element?.closest('.field') || element;
        }

        function clearRechargeFieldError(element) {
            const field = rechargeFieldContainer(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll('.field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function clearRechargePhotoError(key) {
            const safeKey = String(key || '').replace(/[^a-zA-Z0-9_-]/g, '');
            const card = $(`.photo-card[data-key="${safeKey}"]`);
            if (!card) return;
            card.classList.remove('field-invalid');
            card.removeAttribute('aria-invalid');
            card.querySelectorAll('.field-error').forEach((error) => error.remove());
        }

        function clearRechargeValidation() {
            const page = $('#rechargeAddPage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markRechargeInvalid(element, message) {
            if (!element) return;
            const field = rechargeFieldContainer(element);
            field?.classList.add('field-invalid');
            element.setAttribute?.('aria-invalid', 'true');
            let error = field?.querySelector('.field-error');
            if (!error && field) {
                error = document.createElement('div');
                error.className = 'field-error';
                field.appendChild(error);
            }
            if (error) error.textContent = message;
        }

        function markRechargePhotoInvalid(key, message) {
            const safeKey = String(key || '').replace(/[^a-zA-Z0-9_-]/g, '');
            const card = $(`.photo-card[data-key="${safeKey}"]`);
            if (!card) return;
            card.classList.add('field-invalid');
            card.setAttribute('aria-invalid', 'true');
            let error = card.querySelector('.field-error');
            if (!error) {
                error = document.createElement('div');
                error.className = 'field-error';
                card.appendChild(error);
            }
            error.textContent = message;
        }

        function focusFirstRechargeInvalid() {
            const first = $('#rechargeAddPage .field-invalid');
            if (!first) return;
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const control = first.matches('input, select, textarea, button')
                ? first
                : $('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])', first);
            setTimeout(() => control?.focus(), 250);
        }

        function validateBeforeSubmit(requirePhotos = true) {
            clearRechargeValidation();
            const errors = [];
            const invalidate = (element, message) => {
                markRechargeInvalid(element, message);
                errors.push(element);
            };

            const contractSelect = $('#contractSelect');
            const vehicleSelect = $('#vehicleSelect');
            const contract = selectedContract();
            const vehicle = selectedVehicle();

            if (!value('#contractSelect')) {
                invalidate(contractSelect, 'Contract is required.');
            } else if (!contract) {
                invalidate(contractSelect, 'Please select a valid saved contract.');
            }

            if (!value('#vehicleSelect')) {
                invalidate(vehicleSelect, 'Vehicle is required.');
            } else if (!vehicle) {
                invalidate(vehicleSelect, 'Please select a vehicle assigned to the selected contract.');
            } else {
                const assignedDrivers = normalizedVehicleDrivers(vehicle);
                if (!assignedDrivers.length) {
                    invalidate($('#assignedFuelDriver'), 'No driver is assigned to this contract vehicle.');
                } else if (!resolveActiveFuelDriver(vehicle)) {
                    invalidate($('#assignedFuelDriver'), 'No assigned driver shift matches the current system time.');
                }
            }

            const primaryFuel = value('#primaryFuelName');
            const secondaryFuel = value('#secondaryFuelName');
            const secondaryEnabled = Boolean($('#hasSecondaryFuel')?.checked);

            if (!primaryFuel) {
                invalidate($('#primaryFuelName'), 'Main fuel is required in the selected vehicle setup.');
            }

            const primaryRate = Number(value('#primaryRate'));
            if (primaryFuel && !isDirectAmountFuel(primaryFuel) && (!Number.isFinite(primaryRate) || primaryRate <= 0)) {
                invalidate($('#primaryRate'), `An active per-liter rate is required for ${primaryFuel}.`);
            }

            if (requirePhotos) {
                photoRequirements.filter((photo) => photo.required).forEach((photo) => {
                    const fileData = photoState[photo.key]?.fileData || {};
                    if (!photoState[photo.key]?.captured || !(fileData.tempToken || fileData.filePath || fileData.fileUrl)) {
                        markRechargePhotoInvalid(photo.key, `${photo.title.replace(/^\d+\.\s*/, '')} is required.`);
                        errors.push($(`.photo-card[data-key="${String(photo.key).replace(/[^a-zA-Z0-9_-]/g, '')}"]`));
                    }
                });
            }

            const primaryStation = $('#primaryStation');
            if (!value('#primaryStation')) {
                invalidate(primaryStation, primaryFuel ? `Select a station that sells ${primaryFuel}.` : 'Primary fuel station is required.');
            } else if (primaryFuel && !stationSupportsFuel(value('#primaryStation'), primaryFuel)) {
                invalidate(primaryStation, `The selected station is not configured to sell ${primaryFuel}.`);
            }

            const primaryEntered = Number(value('#primaryQty'));
            if (!Number.isFinite(primaryEntered) || primaryEntered <= 0) {
                invalidate($('#primaryQty'), isDirectAmountFuel(primaryFuel)
                    ? `${primaryFuel || 'Fuel'} cost in Taka must be greater than zero.`
                    : `${primaryFuel || 'Fuel'} quantity in liters must be greater than zero.`);
            }

            if (secondaryEnabled) {
                if (!secondaryFuel) {
                    invalidate($('#secondaryFuelName'), 'Second fuel is required when the second-fuel option is enabled.');
                }

                const secondaryStation = $('#secondaryStation');
                if (!value('#secondaryStation')) {
                    invalidate(secondaryStation, secondaryFuel ? `Select a station that sells ${secondaryFuel}.` : 'Secondary fuel station is required.');
                } else if (secondaryFuel && !stationSupportsFuel(value('#secondaryStation'), secondaryFuel)) {
                    invalidate(secondaryStation, `The selected station is not configured to sell ${secondaryFuel}.`);
                }

                const secondaryRate = Number(value('#secondaryRate'));
                if (secondaryFuel && !isDirectAmountFuel(secondaryFuel) && (!Number.isFinite(secondaryRate) || secondaryRate <= 0)) {
                    invalidate($('#secondaryRate'), `An active per-liter rate is required for ${secondaryFuel}.`);
                }

                const secondaryEntered = Number(value('#secondaryQty'));
                if (!Number.isFinite(secondaryEntered) || secondaryEntered <= 0) {
                    invalidate($('#secondaryQty'), isDirectAmountFuel(secondaryFuel)
                        ? `${secondaryFuel || 'Second fuel'} cost in Taka must be greater than zero.`
                        : `${secondaryFuel || 'Second fuel'} quantity in liters must be greater than zero.`);
                }
            }

            const startRaw = String(value('#startKm') ?? '').trim();
            const startKm = Number(startRaw);
            if (startRaw === '' || !Number.isFinite(startKm) || startKm < 0) {
                invalidate($('#startKm'), 'A valid start KM is required from the vehicle record.');
            }

            const endRaw = String(value('#endKm') ?? '').trim();
            const endKm = Number(endRaw);
            if (endRaw === '' || !Number.isFinite(endKm) || endKm <= 0) {
                invalidate($('#endKm'), 'End KM is required and must be greater than zero.');
            } else if (Number.isFinite(startKm) && startKm >= 0 && endKm <= startKm) {
                invalidate($('#endKm'), 'Ending KM must be greater than Starting KM.');
            }

            if (!String(value('#submittedBy') || '').trim()) {
                invalidate($('#submittedBy'), 'Submitted By is required.');
            }

            const remarks = String(value('#rechargeRemarks') || '');
            if (remarks.length > 2000) {
                invalidate($('#rechargeRemarks'), 'Remarks cannot exceed 2000 characters.');
            }

            if (errors.length) {
                focusFirstRechargeInvalid();
                toast('Please correct the highlighted fields.');
                return false;
            }
            return true;
        }

        async function submitRecharge() {
            const submitBtn = $('#submitRechargeBtn');
            return window.FleetmanRunTransaction(submitBtn, async () => {
                await uploadManager.waitForInputs($$('.photo-card'));
                if (!validateBeforeSubmit(true)) return;
                const saved = await saveRecharge('Submitted', null);
                if (saved) {
                    resetFuelRechargeForm();
                    toast(window.FleetmanListAccess.canView()
                        ? 'Fuel recharge submitted successfully. Form reset for next entry.'
                        : window.FleetmanListAccess.savedMessage('Fuel recharge'));
                }
            }, { loadingText: 'Submitting...' });
        }

        async function saveDraft() {
            const draftBtn = $('#draftRechargeBtn');
            return window.FleetmanRunTransaction(draftBtn, async () => {
                await uploadManager.waitForInputs($$('.photo-card'));
                clearRechargeValidation();
                const draftErrors = [];
                if (!value('#contractSelect') || !selectedContract()) {
                    markRechargeInvalid($('#contractSelect'), 'A valid contract is required before saving a draft.');
                    draftErrors.push($('#contractSelect'));
                }
                if (!value('#vehicleSelect') || !selectedVehicle()) {
                    markRechargeInvalid($('#vehicleSelect'), 'A vehicle assigned to the selected contract is required before saving a draft.');
                    draftErrors.push($('#vehicleSelect'));
                }
                if (draftErrors.length) {
                    focusFirstRechargeInvalid();
                    toast('Please correct the highlighted fields.');
                    return;
                }
                const saved = await saveRecharge('Draft', null);
                if (saved) {
                    resetFuelRechargeForm();
                    toast(window.FleetmanListAccess.canView()
                        ? 'Draft saved to database. Form reset for next entry.'
                        : window.FleetmanListAccess.savedMessage('Fuel recharge', true));
                }
            }, { loadingText: 'Saving Draft...' });
        }

        function rechargePhotoFlag(row) {
            const photos = row.photos && typeof row.photos === 'object' ? Object.values(row.photos) : [];
            const captured = photos.filter((photo) => photo?.captured || photo?.file?.filePath || photo?.file?.fileUrl).length;
            if (captured >= 3) return 'All 3 Images Taken';
            if (captured > 0) return `${captured} Image${captured === 1 ? '' : 's'} Taken`;
            return row.imageFlag || 'Not Taken';
        }

        function rechargeFuelDisplay(row, prefix) {
            const fuelName = row[`${prefix}FuelName`] || '';
            if (!fuelName) return '-';
            const direct = (row[`${prefix}PricingMode`] || '') === 'direct_amount' || isDirectAmountFuel(fuelName);
            const entered = Number(row[`${prefix}EnteredValue`] ?? (direct ? row[`${prefix}Amount`] : row[`${prefix}Qty`]) ?? 0);
            const valueText = direct ? money(entered) : `${entered.toFixed(2)} L`;
            return `<div class="chipRow"><span class="chip">${escapeHtml(fuelName)}</span><span>${escapeHtml(valueText)}</span></div>`;
        }

        function renderRechargeList() {
            const q = value('#rechargeSearch').toLowerCase();
            const status = value('#rechargeFilterStatus');
            const rows = recharges.filter((row) => {
                const combined = [row.rechargeId, row.contract, row.vehicle, row.driver, row.primaryStation, row.secondaryStation, row.primaryFuelName, row.secondaryFuelName].join(' ').toLowerCase();
                return (!q || combined.includes(q)) && (!status || row.status === status);
            });

            const tbody = $('#rechargeTbody');
            if (tbody) {
                tbody.innerHTML = rows.length ? rows.map((row) => {
                    const statClass = row.status === 'Submitted' ? 'ok' : 'warn';
                    const imageFlag = rechargePhotoFlag(row);
                    const imgClass = imageFlag === 'All 3 Images Taken' ? 'ok' : (imageFlag === 'Not Taken' ? 'danger' : 'warn');
                    return `<tr>
                        <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                        <td><div class="listCell"><div class="listIcon">⛽</div><div><b>${escapeHtml(row.rechargeId || '')}</b></div></div></td>
                        <td>${escapeHtml(row.date || row.createdAt || '')}</td>
                        <td>${escapeHtml(row.contract || '')}</td>
                        <td>${escapeHtml(row.vehicle || '')}</td>
                        <td>${escapeHtml(row.driver || '')}</td>
                        <td>${rechargeFuelDisplay(row, 'primary')}</td>
                        <td>${escapeHtml(row.primaryStation || '-')}</td>
                        <td>${rechargeFuelDisplay(row, 'secondary')}</td>
                        <td>${escapeHtml(row.secondaryStation || '-')}</td>
                        <td>${escapeHtml(row.startKm ?? '')}</td>
                        <td>${escapeHtml(row.endKm ?? '')}</td>
                        <td>${escapeHtml(row.totalKm ?? '')}</td>
                        <td>${Number(row.mileage || 0) > 0 ? Number(row.mileage).toFixed(2) : '-'}</td>
                        <td><span class="badge ${imgClass}">${escapeHtml(imageFlag)}</span></td>
                        <td><span class="badge ${statClass}">${escapeHtml(row.status || 'Draft')}</span></td>
                        <td>${escapeHtml(row.submittedBy || '')}</td>
                        <td>
                            <button class="mini-btn view-recharge" type="button" data-recharge-view="${escapeHtml(row.rechargeId || '')}">View</button>
                            <button class="mini-btn edit-recharge" type="button" data-recharge-edit="${escapeHtml(row.rechargeId || '')}">Edit</button>
                            <button class="mini-btn danger delete-recharge" type="button" data-recharge-delete="${escapeHtml(row.rechargeId || '')}">Delete</button>
                        </td>
                    </tr>`;
                }).join('') : '<tr><td colspan="18" class="empty">No fuel recharge entry found. Click “Add Recharge” to create one.</td></tr>';
            }

            const submitted = recharges.filter((row) => row.status === 'Submitted').length;
            const drafts = recharges.filter((row) => row.status === 'Draft').length;
            const mileageRows = recharges.filter((row) => Number(row.mileage) > 0);
            const averageMileage = mileageRows.length
                ? mileageRows.reduce((sum, row) => sum + Number(row.mileage), 0) / mileageRows.length
                : 0;
            if ($('#rechargeKpiTotal')) $('#rechargeKpiTotal').textContent = recharges.length;
            if ($('#rechargeKpiSubmitted')) $('#rechargeKpiSubmitted').textContent = submitted;
            if ($('#rechargeKpiDraft')) $('#rechargeKpiDraft').textContent = drafts;
            if ($('#rechargeKpiMileage')) $('#rechargeKpiMileage').textContent = averageMileage > 0 ? averageMileage.toFixed(2) : '-';
        }

        function editRechargeEntry(id) {
            const row = recharges.find((item) => item.rechargeId === id);
            if (!row) return;
            resetFuelRechargeForm();
            editingRechargeId = row.rechargeId || '';
            editingRechargeDate = row.date || '';
            const editContract = contracts.find((contract) => String(contract.id || contract.contractId || '') === String(row.contractId || '')
                || contractSearchLabel(contract).toLowerCase() === String(row.contract || row.contractLabel || '').trim().toLowerCase()) || null;
            setValue('#contractSelect', editContract ? contractSearchLabel(editContract) : '');
            updateVehicles();
            const editVehicle = (selectedContract()?.vehicles || []).find((vehicle) => {
                return String(vehicle.id || '') === String(row.vehicleId || '')
                    || vehicleSearchLabel(vehicle).toLowerCase() === String(row.vehicle || row.vehicleLabel || '').trim().toLowerCase();
            }) || null;
            setValue('#vehicleSelect', editVehicle ? vehicleSearchLabel(editVehicle) : '');
            updateVehicleSetup();

            setValue('#primaryStation', row.primaryStation || '');
            setValue('#primaryQty', row.primaryEnteredValue ?? (isDirectAmountFuel(row.primaryFuelName) ? row.primaryAmount : row.primaryQty) ?? '');
            const secondaryEnabled = Boolean(row.hasSecondaryFuel || row.secondaryFuelName);
            const toggle = $('#hasSecondaryFuel');
            if (toggle && secondaryEnabled && !toggle.disabled) {
                toggle.checked = true;
                $('#secondaryFuelBlock').style.display = 'block';
                populateStationSelect('#secondaryStation', row.secondaryFuelName || value('#secondaryFuelName'), '#secondaryStationHint', row.secondaryStation || '');
                setValue('#secondaryStation', row.secondaryStation || '');
                setValue('#secondaryQty', row.secondaryEnteredValue ?? (isDirectAmountFuel(row.secondaryFuelName) ? row.secondaryAmount : row.secondaryQty) ?? 0);
            }
            setValue('#startKm', row.startKm ?? value('#startKm'));
            setValue('#endKm', row.endKm ?? '');
            setValue('#rechargeRemarks', row.remarks || '');
            restoreRechargePhotos(row);
            recalculate();
            setVisible('rechargeAddPage');
        }

        async function deleteRechargeEntry(id, triggerButton = null) {
            if (!confirm('Delete this fuel recharge entry?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previousRows = JSON.parse(JSON.stringify(recharges || []));
                recharges = recharges.filter((row) => row.rechargeId !== id);
                const result = await syncFuelRecharges(recharges);
                if (result?.syncFailed || result?.ok === false) {
                    recharges = previousRows;
                    renderRechargeList();
                    return;
                }
                if (Array.isArray(result?.rows)) recharges = result.rows;
                renderRechargeList();
                toast('Fuel recharge entry deleted.');
            }, { loadingText: 'Deleting...' });
        }

        $('#rechargeSearch')?.addEventListener('input', renderRechargeList);
        $('#rechargeFilterStatus')?.addEventListener('change', renderRechargeList);
        $('#applyRechargeFiltersBtn')?.addEventListener('click', renderRechargeList);
        $('#clearRechargeFiltersBtn')?.addEventListener('click', () => {
            setValue('#rechargeSearch', '');
            setValue('#rechargeFilterStatus', '');
            renderRechargeList();
        });
        $('#exportRechargesBtn')?.addEventListener('click', () => {
            const rows = [['Entry ID', 'Date', 'Contract', 'Vehicle', 'Driver', 'Primary Fuel', 'Primary Entry', 'Primary Unit', 'Primary Station', 'Secondary Fuel', 'Secondary Entry', 'Secondary Unit', 'Secondary Station', 'Start KM', 'End KM', 'Total KM', 'Mileage KM/L', 'Total Amount', 'Status', 'Submitted By', 'Remarks']];
            recharges.forEach((row) => rows.push([
                row.rechargeId, row.date, row.contract, row.vehicle, row.driver,
                row.primaryFuelName, row.primaryEnteredValue ?? row.primaryQty, row.primaryEntryUnit ?? row.primaryFuelUnit, row.primaryStation,
                row.secondaryFuelName, row.secondaryEnteredValue ?? row.secondaryQty, row.secondaryEntryUnit ?? row.secondaryFuelUnit, row.secondaryStation,
                row.startKm, row.endKm, row.totalKm, row.mileage, row.totalAmount, row.status, row.submittedBy, row.remarks,
            ]));
            exportCsv(rows, 'fleetman-fuel-recharges.csv');
        });
        document.addEventListener('click', (event) => {
            const pageTarget = event.target.closest('[data-page-target]');
            if (pageTarget && ['rechargeAddPage', 'rechargeListPage'].includes(pageTarget.dataset.pageTarget)) {
                if (pageTarget.dataset.pageTarget === 'rechargeListPage') renderRechargeList();
                setVisible(pageTarget.dataset.pageTarget);
            }
            const viewButton = event.target.closest('[data-recharge-view]');
            if (viewButton) {
                const row = recharges.find((item) => item.rechargeId === viewButton.dataset.rechargeView);
                if (row) window.FleetmanDetailViewer?.show('Fuel Recharge Details', row);
            }
            const editButton = event.target.closest('[data-recharge-edit]');
            if (editButton) editRechargeEntry(editButton.dataset.rechargeEdit);
            const deleteButton = event.target.closest('[data-recharge-delete]');
            if (deleteButton) deleteRechargeEntry(deleteButton.dataset.rechargeDelete, deleteButton);
        });

        $('#rechargeAddPage')?.addEventListener('input', (event) => {
            const control = event.target.closest('input, select, textarea');
            if (control) clearRechargeFieldError(control);
        });
        $('#rechargeAddPage')?.addEventListener('change', (event) => {
            const control = event.target.closest('input, select, textarea');
            if (control) clearRechargeFieldError(control);
        });

        $('#contractSelect')?.addEventListener('input', updateVehicles);
        $('#contractSelect')?.addEventListener('change', updateVehicles);
        $('#vehicleSelect')?.addEventListener('input', () => {
            if (selectedVehicle()) {
                updateVehicleSetup();
            } else {
                clearVehicleSetup('Search and select a valid vehicle assigned to the selected contract.');
            }
        });
        $('#vehicleSelect')?.addEventListener('change', updateVehicleSetup);
        window.setInterval(() => {
            if (selectedVehicle()) refreshAssignedFuelDriver();
        }, 60000);
        $('#primaryQty')?.addEventListener('input', recalculate);
        $('#secondaryQty')?.addEventListener('input', recalculate);
        $('#startKm')?.addEventListener('input', recalculate);
        $('#endKm')?.addEventListener('input', recalculate);
        $('#recalculateFuelRechargeBtn')?.addEventListener('click', recalculate);
        $('#hasSecondaryFuel')?.addEventListener('change', function () {
            const vehicle = selectedVehicle();
            if (vehicle && !vehicle.secondaryAvailable) {
                this.checked = false;
                toast('Second fuel is not configured for this vehicle.');
                return;
            }
            $('#secondaryFuelBlock').style.display = this.checked ? 'block' : 'none';
            setSecondaryRequiredState(this.checked);
            if (this.checked) {
                populateStationSelect('#secondaryStation', value('#secondaryFuelName'), '#secondaryStationHint');
            } else {
                setValue('#secondaryQty', 0);
                setValue('#secondaryStation', '');
                ['#secondaryFuelName', '#secondaryStation', '#secondaryQty', '#secondaryRate'].forEach((selector) => clearRechargeFieldError($(selector)));
            }
            recalculate();
        });
        $('#resetRechargeBtn')?.addEventListener('click', resetFuelRechargeForm);
        $('#draftRechargeBtn')?.addEventListener('click', saveDraft);
        $('#submitRechargeBtn')?.addEventListener('click', submitRecharge);

        renderPhotos();
        setSecondaryRequiredState(false);
        renderCameraSupportNotice();
        window.addEventListener('pagehide', closeCamera, { once: true });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && activeStream) closeCamera();
        });
        updateVehicles();
        updateCounter();
        recalculate();
        renderRechargeList();
        window.FleetmanRecordApi?.registerInfinite('fuel_recharges', () => recharges, (rows) => { recharges = rows; }, renderRechargeList);
        if (window.location.search.includes('action=add')) {
            setVisible('rechargeAddPage');
        } else {
            setVisible('rechargeListPage');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const page = document.body.dataset.page;
        if (page === 'vehicles') initVehicles();
        if (page === 'fuel-prices') initFuelPrices();
        if (page === 'fuel-recharge') initFuelRecharge();
    });
})();
