(() => {
    'use strict';

    if (document.body?.dataset?.page !== 'yards') return;

    const data = window.FLEETMAN || {};
    const resources = data.resources?.yards || {};
    const uploadManager = window.FleetmanTemporaryUploads;
    const canManage = data.auth?.pageAccess?.canManage === true;
    const supervisors = Array.isArray(data.supervisors) ? data.supervisors : [];
    const vehicleCategories = (Array.isArray(data.yardVehicleCategories) ? data.yardVehicleCategories : [])
        .map((category) => String(category || '').trim())
        .filter(Boolean);
    const documentReminders = (Array.isArray(data.yardDocumentReminders) ? data.yardDocumentReminders : [])
        .map((reminder) => typeof reminder === 'string' ? reminder : (reminder?.value || reminder?.name || reminder?.label || ''))
        .map((reminder) => String(reminder || '').trim())
        .filter(Boolean);
    let records = Array.isArray(data.records?.yards) ? [...data.records.yards] : [];
    let nextYardId = String(data.nextYardId || 'YRD00001');
    let editingCode = null;
    let currentPage = 1;
    let rowsPerPage = 10;
    let preserveEditOnNextView = false;

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const money = (amount) => `৳ ${Number(amount || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const integer = (value) => Number.parseInt(value || 0, 10) || 0;
    const formatCreatedAt = (value) => window.FleetmanFormatCreatedAt ? window.FleetmanFormatCreatedAt(value) : (value || '—');

    function toast(message) {
        const node = $('#toast');
        if (!node) {
            window.alert(message);
            return;
        }
        node.textContent = message;
        node.classList.add('show');
        window.setTimeout(() => node.classList.remove('show'), 3000);
    }

    function fieldContainer(element) {
        return element?.closest('.field') || element?.closest('.yard-repeat-card') || null;
    }

    function clearFieldError(element) {
        const container = fieldContainer(element);
        if (!container) return;
        container.classList.remove('field-invalid');
        container.querySelectorAll(':scope > .field-error').forEach((error) => error.remove());
        element?.removeAttribute?.('aria-invalid');
    }

    function markInvalid(element, message) {
        if (!element) return;
        const container = fieldContainer(element);
        if (!container) return;
        clearFieldError(element);
        container.classList.add('field-invalid');
        element.setAttribute('aria-invalid', 'true');
        const error = document.createElement('small');
        error.className = 'field-error';
        error.textContent = message;
        container.appendChild(error);
    }

    function clearValidation() {
        $$('.field-invalid', $('#yardAddPage')).forEach((field) => field.classList.remove('field-invalid'));
        $$('.field-error', $('#yardAddPage')).forEach((error) => error.remove());
        $$('[aria-invalid="true"]', $('#yardAddPage')).forEach((element) => element.removeAttribute('aria-invalid'));
    }

    function digitsOnly(input) {
        if (!input) return;
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 11);
    }

    function populateSupervisors() {
        const list = $('#yardSupervisorList');
        if (!list) return;
        list.innerHTML = supervisors.map((name) => `<option value="${escapeHtml(name)}"></option>`).join('');
    }

    function updateEmptyStates() {
        $('#yardZonesEmpty')?.classList.toggle('hidden', $$('.yard-zone-row').length > 0);
    }

    function zoneRow(dataRow = {}) {
        const card = document.createElement('div');
        const selectedVehicleType = String(dataRow.vehicleType || dataRow.type || '');
        const categoryOptions = vehicleCategories
            .map((category) => `<option value="${escapeHtml(category)}" ${selectedVehicleType === category ? 'selected' : ''}>${escapeHtml(category)}</option>`)
            .join('');
        const categoryPlaceholder = vehicleCategories.length
            ? 'Select vehicle category'
            : 'No active vehicle category found';

        card.className = 'yard-repeat-card yard-zone-row';
        card.innerHTML = `
            <div class="yard-repeat-head">
                <div><b>Parking Zone</b></div>
            </div>
            <div class="yard-zone-grid">
                <div class="field"><label>Zone Name</label><input class="yard-zone-name" value="${escapeHtml(dataRow.name || '')}" placeholder="Example: Zone A"></div>
                <div class="field"><label>Vehicle Type</label><select class="yard-zone-type">
                    <option value="">${escapeHtml(categoryPlaceholder)}</option>
                    ${categoryOptions}
                </select></div>
                <div class="field"><label>Capacity</label><input class="yard-zone-capacity" type="number" min="0" step="1" value="${escapeHtml(dataRow.capacity ?? '')}" placeholder="0"></div>
                <button type="button" class="mini-btn danger remove-yard-row">Remove</button>
            </div>`;
        $('#yardZones')?.appendChild(card);
        updateEmptyStates();
        return card;
    }

    function fileJson(file = {}) {
        try { return JSON.stringify(file || {}); } catch (_) { return '{}'; }
    }

    function documentRow(dataRow = {}) {
        const renderer = window.FleetmanDocumentRows;
        if (!renderer?.create) {
            toast('The document row component is unavailable.');
            return null;
        }

        const fileData = dataRow.file && typeof dataRow.file === 'object' ? dataRow.file : {};
        const rendered = renderer.create({
            row: {
                name: dataRow.name || '',
                expiry: dataRow.expiry || dataRow.expiryDate || '',
                reminder: dataRow.reminder || '',
            },
            fileData,
            rowClass: 'yard-document-row',
            nameInput: true,
            namePlaceholder: 'Enter document name',
            reminders: documentReminders,
            classes: {
                name: 'yard-document-name',
                expiry: 'yard-document-expiry',
                reminder: 'yard-document-reminder',
                file: 'yard-document-input',
                hidden: 'yard-document-file-data',
                progress: 'yard-document-upload-progress',
                info: 'yard-document-file-info',
            },
            extraHidden: [{ className: 'yard-document-type', value: dataRow.type || '' }],
            removeClass: 'remove-yard-row',
        });

        const hidden = $('.yard-document-file-data', rendered.element);
        const info = $('.yard-document-file-info', rendered.element);
        const progress = $('.temp-upload-progress', rendered.element);
        if (hidden) hidden.value = fileJson(fileData);
        uploadManager?.render?.({ info, progress, file: fileData, showPreview: false });

        $('#yardDocuments')?.appendChild(rendered.element);
        updateEmptyStates();
        return rendered.element;
    }

    async function removeRepeatRow(button) {
        const card = button.closest('.yard-repeat-card, .yard-document-row');
        if (!card) return;
        if (card.classList.contains('yard-document-row')) {
            const file = uploadManager?.readHidden?.($('.yard-document-file-data', card)) || {};
            if (file.tempToken) {
                try { await uploadManager.destroy(file.tempToken); } catch (_) {}
            }
        }
        card.remove();
        updateEmptyStates();
    }

    function resetForm() {
        editingCode = null;
        clearValidation();
        $('#yardId').value = nextYardId;
        $('#yardName').value = '';
        $('#yardSupervisor').value = '';
        $('#yardPhone').value = '';
        $('#yardSecondaryPhone').value = '';
        $('#yardWhatsapp').value = '';
        $('#yardParkingSlots').value = '';
        $('#yardMonthlyCharge').value = '';
        $('#yardStatus').value = 'Active';
        $('#yardAddress').value = '';
        $('#yardCity').value = '';
        $('#yardArea').value = '';
        $('#yardRemarks').value = '';
        $('#yardZones').innerHTML = '';
        $('#yardDocuments').innerHTML = '';
        $('#submitYardBtn').textContent = 'Submit Yard';
        $('#saveYardDraftBtn').textContent = 'Save Draft';
        updateEmptyStates();
    }

    function fillForm(record) {
        if (!record) return;
        editingCode = String(record.yardId || '');
        clearValidation();
        $('#yardId').value = record.yardId || '';
        $('#yardName').value = record.yardName || '';
        $('#yardSupervisor').value = record.supervisor || '';
        $('#yardPhone').value = record.phone || '';
        $('#yardSecondaryPhone').value = record.secondaryPhone || '';
        $('#yardWhatsapp').value = record.whatsapp || '';
        $('#yardParkingSlots').value = record.parkingSlots ?? '';
        $('#yardMonthlyCharge').value = record.monthlyCharge ?? '';
        $('#yardStatus').value = ['Active', 'Inactive'].includes(record.status) ? record.status : 'Active';
        $('#yardAddress').value = record.address || '';
        $('#yardCity').value = record.city || '';
        $('#yardArea').value = record.area || '';
        $('#yardRemarks').value = record.remarks || '';
        $('#yardZones').innerHTML = '';
        $('#yardDocuments').innerHTML = '';
        (Array.isArray(record.zones) ? record.zones : []).forEach(zoneRow);
        (Array.isArray(record.documents) ? record.documents : []).forEach(documentRow);
        $('#submitYardBtn').textContent = 'Update Yard';
        $('#saveYardDraftBtn').textContent = 'Update Draft';
        updateEmptyStates();
    }

    function collectZones() {
        return $$('.yard-zone-row').map((row) => ({
            name: $('.yard-zone-name', row)?.value.trim() || '',
            vehicleType: $('.yard-zone-type', row)?.value || '',
            capacity: integer($('.yard-zone-capacity', row)?.value),
        })).filter((zone) => zone.name || zone.capacity > 0);
    }

    function collectDocuments() {
        return $$('.yard-document-row').map((row) => ({
            name: $('.yard-document-name', row)?.value.trim() || '',
            expiry: $('.yard-document-expiry', row)?.value || '',
            reminder: $('.yard-document-reminder', row)?.value || '',
            type: $('.yard-document-type', row)?.value || '',
            file: uploadManager?.readHidden?.($('.yard-document-file-data', row)) || {},
        })).filter((document) => document.name || Object.keys(document.file || {}).length > 0);
    }

    function validateForm(savedAs) {
        clearValidation();
        let valid = true;
        const required = (selector, message) => {
            const element = $(selector);
            if (!element || String(element.value || '').trim() !== '') return;
            markInvalid(element, message);
            valid = false;
        };
        const validatePhone = (selector, label, requiredField = false) => {
            const element = $(selector);
            const value = String(element?.value || '').trim();
            if (!value && !requiredField) return;
            if (!/^01\d{9}$/.test(value)) {
                markInvalid(element, `${label} must be a valid 11-digit Bangladesh mobile number.`);
                valid = false;
            }
        };

        if (savedAs !== 'Draft') {
            required('#yardName', 'Yard Name is required.');
            required('#yardSupervisor', 'Supervisor is required.');
            required('#yardPhone', 'Phone Number is required.');
            required('#yardMonthlyCharge', 'Monthly Charge is required.');
            required('#yardAddress', 'Address is required.');
            validatePhone('#yardPhone', 'Phone Number', true);
        } else if ($('#yardPhone').value.trim()) {
            validatePhone('#yardPhone', 'Phone Number');
        }
        validatePhone('#yardSecondaryPhone', 'Secondary Contact');
        validatePhone('#yardWhatsapp', 'WhatsApp Number');

        $$('.yard-zone-row').forEach((row) => {
            const nameInput = $('.yard-zone-name', row);
            const typeSelect = $('.yard-zone-type', row);
            const capacityInput = $('.yard-zone-capacity', row);
            const hasZoneData = String(nameInput?.value || '').trim() !== ''
                || String(typeSelect?.value || '').trim() !== ''
                || integer(capacityInput?.value) > 0;
            if (hasZoneData && !String(typeSelect?.value || '').trim()) {
                markInvalid(typeSelect, vehicleCategories.length
                    ? 'Select a vehicle category from Master Data.'
                    : 'Add an active vehicle category in Master Data before creating this zone.');
                valid = false;
            }
        });

        const slots = integer($('#yardParkingSlots').value);
        const totalZoneCapacity = collectZones().reduce((total, zone) => total + integer(zone.capacity), 0);
        if (slots > 0 && totalZoneCapacity > slots) {
            markInvalid($('#yardParkingSlots'), 'Parking Slots cannot be less than the total zone capacity.');
            valid = false;
        }

        const seenDocumentNames = new Set();
        $$('.yard-document-row').forEach((row) => {
            const nameInput = $('.yard-document-name', row);
            const hidden = $('.yard-document-file-data', row);
            const name = String(nameInput?.value || '').trim();
            const file = uploadManager?.readHidden?.(hidden) || {};
            if (!name) {
                markInvalid(nameInput, 'Document Name is required for an added document.');
                valid = false;
            }
            if (!(file.tempToken || file.filePath || file.fileUrl)) {
                markInvalid($('.yard-document-input', row), 'Upload a file or remove this document row.');
                valid = false;
            }
            const normalized = name.toLowerCase();
            if (normalized && seenDocumentNames.has(normalized)) {
                markInvalid(nameInput, 'Each document name can be used only once.');
                valid = false;
            }
            if (normalized) seenDocumentNames.add(normalized);
        });

        if (!valid) {
            $('#yardAddPage .field-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return valid;
    }

    function payload(savedAs) {
        return {
            yardName: $('#yardName').value.trim(),
            supervisor: $('#yardSupervisor').value.trim(),
            phone: $('#yardPhone').value.trim(),
            secondaryPhone: $('#yardSecondaryPhone').value.trim(),
            whatsapp: $('#yardWhatsapp').value.trim(),
            parkingSlots: integer($('#yardParkingSlots').value),
            monthlyCharge: Number($('#yardMonthlyCharge').value || 0),
            status: $('#yardStatus').value,
            address: $('#yardAddress').value.trim(),
            city: $('#yardCity').value.trim(),
            area: $('#yardArea').value.trim(),
            remarks: $('#yardRemarks').value.trim(),
            zones: collectZones(),
            documents: collectDocuments(),
            savedAs,
        };
    }

    function responseMessage(response, fallback) {
        if (!response || typeof response !== 'object') return fallback;
        const validation = Object.values(response.errors || {}).flat().filter(Boolean);
        return response.message || validation.join(' ') || fallback;
    }

    async function saveYard(savedAs) {
        if (!canManage) {
            toast('Your role has read-only access to this module.');
            return;
        }

        await uploadManager?.waitForInputs?.($$('.yard-document-input'));
        if (!validateForm(savedAs)) return;

        const endpoint = editingCode
            ? String(resources.update_template || '').replace('__CODE__', encodeURIComponent(editingCode))
            : resources.store;
        if (!endpoint) {
            toast('Yard database endpoint is unavailable.');
            return;
        }

        const submitButtons = [$('#saveYardDraftBtn'), $('#submitYardBtn')].filter(Boolean);
        submitButtons.forEach((button) => { button.disabled = true; });

        try {
            const response = await fetch(endpoint, {
                method: editingCode ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload(savedAs)),
            });
            let result = {};
            try { result = await response.json(); } catch (_) {}
            if (!response.ok || !result.record) {
                throw new Error(responseMessage(result, 'The yard could not be saved.'));
            }

            const savedRecord = result.record;
            const index = records.findIndex((record) => String(record.yardId) === String(savedRecord.yardId));
            if (index >= 0) records[index] = savedRecord;
            else records.unshift(savedRecord);
            nextYardId = String(result.nextYardId || nextYardId);
            toast(result.message || 'Yard saved successfully.');
            editingCode = null;
            renderList();
            navigateTo('list');
            resetForm();
        } catch (error) {
            toast(error?.message || 'The yard could not be saved.');
        } finally {
            submitButtons.forEach((button) => { button.disabled = false; });
        }
    }

    function filteredRecords() {
        const query = String($('#yardSearch')?.value || '').trim().toLowerCase();
        const status = String($('#yardStatusFilter')?.value || '');
        return records.filter((record) => {
            const searchable = [
                record.yardId, record.yardName, record.supervisor, record.phone,
                record.secondaryPhone, record.whatsapp, record.address, record.area, record.city
            ].join(' ').toLowerCase();
            const statusMatch = !status || String(record.status || record.savedAs || '') === status;
            return (!query || searchable.includes(query)) && statusMatch;
        });
    }

    function statusBadge(status) {
        const normalized = String(status || 'Draft');
        const className = normalized === 'Active' ? 'ok' : (normalized === 'Inactive' ? 'danger' : 'warn');
        return `<span class="badge ${className}">${escapeHtml(normalized)}</span>`;
    }

    function showUrl(record) {
        return String(resources.show_template || '').replace('__CODE__', encodeURIComponent(record.yardId || ''));
    }

    function actionMarkup(record, mobile = false) {
        const view = `<a class="mini-btn view-yard" href="${escapeHtml(showUrl(record))}">View</a>`;
        if (!canManage) return view;
        return `${view}
            <button type="button" class="mini-btn edit-yard" data-yard-id="${escapeHtml(record.yardId)}">Edit</button>
            <button type="button" class="mini-btn danger delete-yard" data-yard-id="${escapeHtml(record.yardId)}">Delete</button>`;
    }

    function renderList() {
        const filtered = filteredRecords();
        const totalPages = Math.max(1, Math.ceil(filtered.length / rowsPerPage));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);
        const startIndex = (currentPage - 1) * rowsPerPage;
        const pageRows = filtered.slice(startIndex, startIndex + rowsPerPage);

        $('#yardKpiTotal').textContent = String(filtered.length);
        $('#yardKpiActive').textContent = String(filtered.filter((record) => record.status === 'Active').length);
        $('#yardKpiSlots').textContent = filtered.reduce((sum, record) => sum + integer(record.parkingSlots), 0).toLocaleString();
        $('#yardKpiCharge').textContent = money(filtered.reduce((sum, record) => sum + Number(record.monthlyCharge || 0), 0));

        const body = $('#yardTableBody');
        body.innerHTML = pageRows.length ? pageRows.map((record) => `
            <tr>
                <td>${window.FleetmanCreatedAtCell(record.createdAt || record.created_at, record.creatorName || record.createdBy)}</td>
                <td><b>${escapeHtml(record.yardId || '—')}</b></td>
                <td class="yard-name-cell"><b>${escapeHtml(record.yardName || 'Unnamed Yard')}</b><br><small>${escapeHtml(record.address || 'No address')}</small></td>
                <td>${escapeHtml(record.supervisor || '—')}</td>
                <td>${statusBadge(record.status)}</td>
                <td>${escapeHtml(record.phone || '—')}</td>
                <td>${escapeHtml([record.area, record.city].filter(Boolean).join(', ') || '—')}</td>
                <td>${integer(record.parkingSlots).toLocaleString()}</td>
                <td>${money(record.monthlyCharge)}</td>
                <td>${Array.isArray(record.zones) ? record.zones.length : 0}</td>
                <td>${Array.isArray(record.documents) ? record.documents.length : 0}</td>
                <td class="yard-action-cell">${actionMarkup(record)}</td>
            </tr>`).join('') : '<tr><td colspan="12" class="empty">No yard record matches the selected filters.</td></tr>';

        $('#yardMobileList').innerHTML = pageRows.map((record) => `
            <article class="yard-mobile-card">
                <div class="yard-mobile-card-head">
                    <div><h3>${escapeHtml(record.yardName || 'Unnamed Yard')}</h3><small>${escapeHtml(record.yardId || '')} • ${escapeHtml(record.supervisor || 'No supervisor')}</small></div>
                    ${statusBadge(record.status)}
                </div>
                <div class="yard-mobile-grid">
                    <div>Area / City<b>${escapeHtml([record.area, record.city].filter(Boolean).join(', ') || '—')}</b></div>
                    <div>Phone<b>${escapeHtml(record.phone || '—')}</b></div>
                    <div>Parking Slots<b>${integer(record.parkingSlots).toLocaleString()}</b></div>
                    <div>Monthly Charge<b>${money(record.monthlyCharge)}</b></div>
                    <div>Zones<b>${Array.isArray(record.zones) ? record.zones.length : 0}</b></div>
                    <div>Documents<b>${Array.isArray(record.documents) ? record.documents.length : 0}</b></div>
                </div>
                <div class="yard-mobile-actions">${actionMarkup(record, true)}</div>
            </article>`).join('');

        const from = filtered.length ? startIndex + 1 : 0;
        const to = Math.min(startIndex + rowsPerPage, filtered.length);
        $('#yardPageInfo').textContent = `Showing ${from} - ${to} of ${filtered.length} yards`;
        $('#yardListSummary').textContent = `${filtered.length} yard record${filtered.length === 1 ? '' : 's'} found.`;
        $('#yardPageNumbers').innerHTML = Array.from({ length: totalPages }, (_, index) => index + 1)
            .map((page) => `<button type="button" class="mini-btn ${page === currentPage ? 'active' : ''}" data-yard-page="${page}">${page}</button>`)
            .join('');
        $('#yardPreviousPageBtn').disabled = currentPage <= 1;
        $('#yardNextPageBtn').disabled = currentPage >= totalPages;
    }

    function navigateTo(action) {
        const url = new URL(window.location.href);
        url.searchParams.set('action', action);
        url.searchParams.delete('edit');
        window.history.pushState({ fleetmanAction: action }, '', url);
        window.FleetmanNavigation?.showCurrentModuleView?.(url);
    }

    function editYard(code) {
        const record = records.find((item) => String(item.yardId) === String(code));
        if (!record) return;
        preserveEditOnNextView = true;
        fillForm(record);
        navigateTo('add');
    }

    async function deleteYard(code) {
        if (!canManage) return;
        const record = records.find((item) => String(item.yardId) === String(code));
        if (!record || !window.confirm(`Delete ${record.yardName || code}? This action cannot be undone.`)) return;
        const endpoint = String(resources.destroy_template || '').replace('__CODE__', encodeURIComponent(code));
        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            let result = {};
            try { result = await response.json(); } catch (_) {}
            if (!response.ok) throw new Error(responseMessage(result, 'The yard could not be deleted.'));
            records = records.filter((item) => String(item.yardId) !== String(code));
            renderList();
            toast(result.message || 'Yard deleted successfully.');
        } catch (error) {
            toast(error?.message || 'The yard could not be deleted.');
        }
    }

    function exportCsv() {
        const rows = filteredRecords();
        if (!rows.length) {
            toast('There are no yard records to export.');
            return;
        }
        const csvRows = [
            ['Yard ID', 'Yard Name', 'Supervisor', 'Status', 'Phone', 'Secondary Contact', 'WhatsApp', 'Address', 'Area', 'City', 'Parking Slots', 'Monthly Charge', 'Zones', 'Documents'],
            ...rows.map((record) => [
                record.yardId, record.yardName, record.supervisor, record.status, record.phone,
                record.secondaryPhone, record.whatsapp, record.address, record.area, record.city,
                record.parkingSlots, record.monthlyCharge,
                Array.isArray(record.zones) ? record.zones.length : 0,
                Array.isArray(record.documents) ? record.documents.length : 0,
            ]),
        ];
        const csv = csvRows.map((row) => row.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' }));
        link.download = `yard-list-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(link.href);
    }

    function bindEvents() {
        $('#addYardZoneBtn')?.addEventListener('click', () => zoneRow());
        $('#addYardDocumentBtn')?.addEventListener('click', () => documentRow());
        $('#resetYardBtn')?.addEventListener('click', resetForm);
        $('#saveYardDraftBtn')?.addEventListener('click', () => saveYard('Draft'));
        $('#submitYardBtn')?.addEventListener('click', () => saveYard('Submitted'));
        $('#exportYardsBtn')?.addEventListener('click', exportCsv);
        $('#addYardFromListBtn')?.addEventListener('click', () => { preserveEditOnNextView = false; resetForm(); });
        $('#applyYardFiltersBtn')?.addEventListener('click', () => { currentPage = 1; renderList(); });
        $('#clearYardFiltersBtn')?.addEventListener('click', () => {
            $('#yardSearch').value = '';
            $('#yardStatusFilter').value = '';
            currentPage = 1;
            renderList();
        });
        $('#yardSearch')?.addEventListener('input', () => { currentPage = 1; renderList(); });
        $('#yardStatusFilter')?.addEventListener('change', () => { currentPage = 1; renderList(); });
        $('#yardRowsPerPage')?.addEventListener('change', (event) => {
            rowsPerPage = integer(event.target.value) || 10;
            currentPage = 1;
            renderList();
        });
        $('#yardPreviousPageBtn')?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderList(); } });
        $('#yardNextPageBtn')?.addEventListener('click', () => {
            const pages = Math.max(1, Math.ceil(filteredRecords().length / rowsPerPage));
            if (currentPage < pages) { currentPage++; renderList(); }
        });

        ['#yardPhone', '#yardSecondaryPhone', '#yardWhatsapp'].forEach((selector) => {
            $(selector)?.addEventListener('input', (event) => { digitsOnly(event.target); clearFieldError(event.target); });
        });
        $('#yardAddPage')?.addEventListener('input', (event) => clearFieldError(event.target));
        $('#yardAddPage')?.addEventListener('change', (event) => clearFieldError(event.target));

        document.addEventListener('change', (event) => {
            const input = event.target.closest('.yard-document-input');
            if (!input) return;
            const row = input.closest('.yard-document-row');
            const hidden = $('.yard-document-file-data', row);
            const info = $('.yard-document-file-info', row);
            const progress = $('.temp-upload-progress', row);
            const type = $('.yard-document-type', row);
            uploadManager?.upload?.(input, uploadManager.documentOptions({
                hidden,
                info,
                progress,
                showPreview: false,
                onSuccess: (file) => {
                    const extension = String(file?.originalName || file?.fileName || '').split('.').pop().toUpperCase();
                    if (type && ['PDF', 'DOC', 'DOCX', 'XLS', 'XLSX'].includes(extension)) type.value = extension;
                    clearFieldError(input);
                },
            }));
        });

        document.addEventListener('click', (event) => {
            const remove = event.target.closest('.remove-yard-row');
            if (remove) { removeRepeatRow(remove); return; }
            const edit = event.target.closest('.edit-yard');
            if (edit) { editYard(edit.dataset.yardId); return; }
            const removeYard = event.target.closest('.delete-yard');
            if (removeYard) { deleteYard(removeYard.dataset.yardId); return; }
            const page = event.target.closest('[data-yard-page]');
            if (page) { currentPage = integer(page.dataset.yardPage) || 1; renderList(); }
        });

        document.addEventListener('fleetman:view-changed', (event) => {
            if (event.detail?.page !== 'yards') return;
            if (event.detail.action === 'list') {
                renderList();
                return;
            }
            if (preserveEditOnNextView) {
                preserveEditOnNextView = false;
                return;
            }
            if (!editingCode) resetForm();
        });
    }

    populateSupervisors();
    resetForm();
    renderList();
    bindEvents();
})();
