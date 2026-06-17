/* Contract page logic: dynamic database-backed contract form, assignments, documents, and list. */
(() => {
    'use strict';

    const data = window.FLEETMAN || {};
    const options = data.options || {};
    const records = data.records || data.samples || {};
    const resources = data.resources || {};
    const masters = data.contractMasters || { parties: { Client: [], Vendor: [] }, vehicles: [], drivers: [] };

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const value = (selector) => ($(selector) ? $(selector).value : '');
    const setValue = (selector, nextValue) => { const el = $(selector); if (el) el.value = nextValue ?? ''; };
    const escapeHtml = (nextValue) => String(nextValue ?? '').replace(/[&<>'"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ch]));

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function toast(message) {
        const element = $('#toast');
        if (!element) return;
        element.textContent = message;
        element.classList.add('show');
        setTimeout(() => element.classList.remove('show'), 2800);
    }

    function money(number) {
        return '৳ ' + Number(number || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 });
    }

    function formatDate(nextValue) {
        if (!nextValue) return '-';
        const date = new Date(nextValue + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return nextValue;
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
    }

    function downloadCsv(filename, rows) {
        const csv = rows.map((row) => row.map((cell) => `"${String(cell ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function initContracts() {
        let contracts = Array.isArray(records.contracts)
            ? records.contracts.filter((row) => !['fuel_recharge', 'attendance'].includes(String(row.status || row.savedAs || '').toLowerCase()))
            : [];
        let currentPage = 1;
        let rowsPerPage = 10;
        let assignmentCounter = 0;
        let documentCounter = 0;
        const documentNames = options.contract_document_templates || options.document_templates || [];
        const documentReminders = options.document_reminders || [];
        const documentSelects = window.FleetmanUniqueDocumentSelects;
        const uploadManager = window.FleetmanTemporaryUploads;

        function endpoint() {
            return resources?.contracts?.sync || null;
        }

        function setPage(pageId) {
            ['contractCreatePage', 'contractListPage'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('hidden', id !== pageId);
            });
            window.scrollTo(0, 0);
        }

        function genId() {
            const now = new Date();
            const yy = String(now.getFullYear()).slice(-2);
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            return 'CNT' + yy + mm + dd + String(Math.floor(100 + Math.random() * 900));
        }

        function optionHtml(items, selected = '', placeholder = 'Select') {
            const opts = [`<option value="">${escapeHtml(placeholder)}</option>`];
            (items || []).forEach((item) => {
                const val = typeof item === 'string' ? item : (item.value || item.id || item.name || item.label || '');
                const label = typeof item === 'string' ? item : (item.label || item.name || item.value || item.id || '');
                opts.push(`<option value="${escapeHtml(val)}" ${selected === val ? 'selected' : ''}>${escapeHtml(label)}</option>`);
            });
            if (selected && !opts.some((opt) => opt.includes(`value="${escapeHtml(selected)}"`))) {
                opts.push(`<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`);
            }
            return opts.join('');
        }

        function activeChip(groupId) {
            return $(`#${groupId} .chip.active`)?.dataset.value || '';
        }

        function setChip(groupId, nextValue) {
            const buttons = $$(`#${groupId} .chip`);
            buttons.forEach((btn) => btn.classList.toggle('active', btn.dataset.value === nextValue));
            if (!buttons.some((btn) => btn.classList.contains('active')) && buttons[0]) buttons[0].classList.add('active');
        }

        function partyList() {
            return masters.parties?.[activeChip('contractWithGroup') || 'Client'] || [];
        }

        function selectedParty() {
            const id = value('#contractParty');
            return partyList().find((party) => party.id === id) || null;
        }

        function updatePartySelect(selectedId = '') {
            const select = $('#contractParty');
            if (!select) return;
            const parties = partyList();
            select.innerHTML = optionHtml(parties, selectedId, parties.length ? 'Select contract party' : 'No saved party found');
            const selected = selectedParty();
            setValue('#contractPartyId', selected?.id || value('#contractParty'));
        }

        function vehicleById(id) {
            return (masters.vehicles || []).find((vehicle) => vehicle.id === id) || null;
        }

        function driverById(id) {
            return (masters.drivers || []).find((driver) => driver.id === id) || null;
        }

        function hiddenFileValue(fileData = {}) {
            try { return JSON.stringify(fileData || {}); } catch (_) { return '{}'; }
        }

        function parseHiddenFile(input) {
            if (!input?.value) return {};
            try { return JSON.parse(input.value) || {}; } catch (_) { return {}; }
        }

        function renderFileInfo(row, message = '', error = false) {
            if (!row) return;
            uploadManager.render({
                info: $('.contract-upload-info', row),
                progress: $('.contractDocProgress', row),
                file: parseHiddenFile($('.contractDocExistingFile', row)),
                message,
                error,
                showPreview: false,
            });
        }

        function refreshUniqueAssignmentSelects(selectSelector, items, placeholder) {
            const wrapper = $('#contractAssignments');
            if (!wrapper) return;

            const selects = $$(selectSelector, wrapper);
            const selectedValues = selects
                .map((select) => String(select.value || '').trim())
                .filter(Boolean);

            selects.forEach((select) => {
                const current = String(select.value || '').trim();
                const selectedElsewhere = new Set(
                    selectedValues
                        .filter((selected) => selected && selected !== current)
                        .map((selected) => selected.toLowerCase())
                );
                const availableItems = (items || []).filter((item) => {
                    const itemValue = String(item?.value || item?.id || item?.name || item?.label || item || '').trim();
                    return itemValue === current || !selectedElsewhere.has(itemValue.toLowerCase());
                });

                select.innerHTML = optionHtml(availableItems, current, placeholder);
            });
        }

        function refreshContractAssignmentOptions() {
            refreshUniqueAssignmentSelects('.contractAsgDriver', masters.drivers || [], 'Select driver');
            refreshUniqueAssignmentSelects('.contractAsgVehicle', masters.vehicles || [], 'Select vehicle');
        }

        function addAssignment(row = {}) {
            assignmentCounter += 1;
            const wrapper = $('#contractAssignments');
            if (!wrapper) return;
            const card = document.createElement('div');
            card.className = 'contract-assignment-card';
            const driverId = row.driverId || row.driver || '';
            const vehicleId = row.vehicleId || row.vehicle || '';
            card.innerHTML = `
                <div class="contract-card-head">
                    <div>
                        <div class="contract-card-title">Assignment ${assignmentCounter}</div>
                    </div>
                    <button class="btn light small remove-contract-card" type="button">Remove</button>
                </div>
                <div class="contract-grid">
                    <div class="field contract-col-3">
                        <label>Driver <span class="req">*</span></label>
                        <select class="contractAsgDriver" required aria-required="true">${optionHtml(masters.drivers || [], driverId, 'Select driver')}</select>
                    </div>
                    <div class="field contract-col-3">
                        <label>Vehicle <span class="req">*</span></label>
                        <select class="contractAsgVehicle" required aria-required="true">${optionHtml(masters.vehicles || [], vehicleId, 'Select vehicle')}</select>
                    </div>
                    <div class="field contract-col-3">
                        <label>Vehicle Hourly Rate <span class="req">*</span></label>
                        <input class="contractAsgRate" type="number" min="0.01" step="0.01" value="${escapeHtml(row.rate ?? '')}" placeholder="0" required aria-required="true">
                    </div>
                    <div class="field contract-col-3">
                        <label>Vehicle Duty Hour/Daily <span class="req">*</span></label>
                        <input class="contractAsgDuty" type="number" min="0.01" step="0.01" value="${escapeHtml(row.duty ?? '')}" placeholder="0" required aria-required="true">
                    </div>
                </div>`;
            wrapper.appendChild(card);
            refreshContractAssignmentOptions();
        }

        function normalizedContractDocumentNames() {
            return (documentNames || [])
                .map((item) => typeof item === 'string'
                    ? item
                    : (item?.value || item?.id || item?.name || item?.label || ''))
                .map((item) => String(item || '').trim())
                .filter(Boolean);
        }

        function refreshContractDocumentOptions() {
            const wrapper = $('#contractDocuments');
            if (!wrapper) return;

            const selects = $$('.contractDocName', wrapper);
            const selectedValues = selects
                .map((select) => String(select.value || '').trim())
                .filter(Boolean);
            const availableDocumentNames = normalizedContractDocumentNames();

            selects.forEach((select) => {
                const current = String(select.value || '').trim();
                const currentKey = current.toLowerCase();
                const selectedElsewhere = new Set(
                    selectedValues
                        .filter((selected) => selected.toLowerCase() !== currentKey)
                        .map((selected) => selected.toLowerCase())
                );
                const optionsForRow = availableDocumentNames.filter((name) => {
                    const nameKey = name.toLowerCase();
                    return nameKey === currentKey || !selectedElsewhere.has(nameKey);
                });

                select.innerHTML = optionHtml(optionsForRow, current, 'Select document name');
            });
        }

        function addDocument(row = {}) {
            documentCounter += 1;
            const wrapper = $('#contractDocuments');
            if (!wrapper) return;
            const fileData = (row.file && typeof row.file === 'object') ? row.file : {};
            const rendered = window.FleetmanDocumentRows.create({
                row,
                fileData,
                rowClass: 'contract-doc-card contract-document-row',
                names: normalizedContractDocumentNames(),
                reminders: documentReminders,
                namePlaceholder: 'Select document',
                classes: {
                    name: 'contractDocName', expiry: 'contractDocExpiry', reminder: 'contractDocReminder',
                    file: 'contractDocFile', hidden: 'contractDocExistingFile', progress: 'contractDocProgress', info: 'contract-upload-info'
                },
                removeClass: 'remove-contract-card'
            });
            wrapper.appendChild(rendered.element);
            renderFileInfo(rendered.element);
            refreshContractDocumentOptions();
        }

        function clearRepeating() {
            if ($('#contractAssignments')) $('#contractAssignments').innerHTML = '';
            if ($('#contractDocuments')) $('#contractDocuments').innerHTML = '';
            assignmentCounter = 0;
            documentCounter = 0;
        }

        function resetForm() {
            clearContractValidation();
            setValue('#contractId', genId());
            setChip('contractWithGroup', 'Client');
            updatePartySelect();
            setValue('#contractAmount', '');
            setChip('contractStatusGroup', 'Initiated');
            setValue('#contractStart', '');
            setValue('#contractEnd', '');
            setValue('#contractDetails', '');
            clearRepeating();
            addAssignment();
            addDocument();
        }

        function collectAssignments() {
            return $$('.contract-assignment-card').map((card) => {
                const vehicleId = $('.contractAsgVehicle', card)?.value || '';
                const driverId = $('.contractAsgDriver', card)?.value || '';
                const vehicle = vehicleById(vehicleId);
                const driver = driverById(driverId);
                return {
                    driverId,
                    driver: driver ? driver.label : driverId,
                    driverName: driver?.name || '',
                    vehicleId,
                    vehicle: vehicle ? vehicle.label : vehicleId,
                    vehicleName: vehicle?.name || '',
                    rate: Number($('.contractAsgRate', card)?.value || 0),
                    duty: Number($('.contractAsgDuty', card)?.value || 0),
                };
            });
        }

        function collectDocuments() {
            return $$('.contract-doc-card').map((card) => ({
                name: $('.contractDocName', card)?.value || '',
                expiry: $('.contractDocExpiry', card)?.value || '',
                reminder: $('.contractDocReminder', card)?.value || '',
                file: parseHiddenFile($('.contractDocExistingFile', card)),
            }));
        }

        function collectContract(savedAs) {
            const party = selectedParty();
            return {
                contractId: value('#contractId') || genId(),
                contractWith: activeChip('contractWithGroup'),
                partyId: party?.id || value('#contractPartyId') || value('#contractParty'),
                partyName: party?.name || $('#contractParty option:checked')?.textContent || '',
                amount: Number(value('#contractAmount') || 0),
                status: activeChip('contractStatusGroup'),
                contractStart: value('#contractStart'),
                contractEnd: value('#contractEnd'),
                details: value('#contractDetails').trim(),
                assignments: collectAssignments(),
                documents: collectDocuments(),
                savedAs,
                savedAt: new Date().toISOString(),
            };
        }

        function clearContractValidation() {
            const page = $('#contractCreatePage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function clearContractFieldError(element) {
            const field = element?.closest?.('.field');
            field?.classList.remove('field-invalid');
            field?.querySelectorAll('.field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function invalidateContractField(element, message) {
            if (!element) return;
            const field = element.closest('.field') || element;
            field.classList.add('field-invalid');
            element.setAttribute?.('aria-invalid', 'true');
            let error = field.querySelector('.field-error');
            if (!error) {
                error = document.createElement('small');
                error.className = 'field-error';
                field.appendChild(error);
            }
            error.textContent = message;
        }

        function focusFirstContractError() {
            const firstField = $('#contractCreatePage .field-invalid');
            if (!firstField) return;
            firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstField.querySelector('input:not([type="hidden"]), select, textarea, button')?.focus?.({ preventScroll: true });
        }

        function hasUploadedContractFile(file = {}) {
            return Boolean(file.tempToken || file.filePath || file.fileUrl || file.previewUrl || file.url);
        }

        function validateContract(row, savedAs) {
            clearContractValidation();
            if (savedAs === 'Draft') return true;

            let valid = true;
            const invalidate = (element, message) => {
                valid = false;
                invalidateContractField(element, message);
            };

            if (!String(row.contractId || '').trim()) invalidate($('#contractId'), 'Contract ID is required.');
            if (!String(row.contractWith || '').trim()) invalidate($('#contractWithGroup'), 'Contract With is required.');
            if (!String(row.partyId || '').trim()) invalidate($('#contractParty'), 'Contract Party is required.');
            if (!Number.isFinite(Number(row.amount)) || Number(row.amount) <= 0) invalidate($('#contractAmount'), 'Contract Amount must be greater than zero.');
            if (!String(row.status || '').trim()) invalidate($('#contractStatusGroup'), 'Status is required.');
            if (!row.contractStart) invalidate($('#contractStart'), 'Contract Start is required.');
            if (!row.contractEnd) invalidate($('#contractEnd'), 'Contract End is required.');
            if (row.contractStart && row.contractEnd && row.contractEnd < row.contractStart) invalidate($('#contractEnd'), 'Contract End cannot be earlier than Contract Start.');
            if (!String(row.details || '').trim()) invalidate($('#contractDetails'), 'Details are required.');
            else if (String(row.details).length > 5000) invalidate($('#contractDetails'), 'Details cannot exceed 5000 characters.');

            const assignmentCards = $$('.contract-assignment-card');
            if (!assignmentCards.length) {
                valid = false;
                toast('Please add at least one vehicle and driver assignment.');
            }
            assignmentCards.forEach((card) => {
                const driver = $('.contractAsgDriver', card);
                const vehicle = $('.contractAsgVehicle', card);
                const rate = $('.contractAsgRate', card);
                const duty = $('.contractAsgDuty', card);
                if (!driver?.value) invalidate(driver, 'Driver is required.');
                if (!vehicle?.value) invalidate(vehicle, 'Vehicle is required.');
                if (!Number.isFinite(Number(rate?.value)) || Number(rate?.value) <= 0) invalidate(rate, 'Vehicle Hourly Rate must be greater than zero.');
                if (!Number.isFinite(Number(duty?.value)) || Number(duty?.value) <= 0) invalidate(duty, 'Vehicle Duty Hour/Daily must be greater than zero.');
            });

            const documentCards = $$('.contract-doc-card');
            if (!documentCards.length) {
                valid = false;
                toast('Please add at least one contract document.');
            }
            const seenDocumentNames = new Map();
            documentCards.forEach((card) => {
                const name = $('.contractDocName', card);
                const expiry = $('.contractDocExpiry', card);
                const fileInput = $('.contractDocFile', card);
                const fileData = parseHiddenFile($('.contractDocExistingFile', card));
                const normalizedName = String(name?.value || '').trim().toLowerCase();
                if (!normalizedName) {
                    invalidate(name, 'Document Name is required.');
                } else if (seenDocumentNames.has(normalizedName)) {
                    invalidate(name, 'This document name has already been selected.');
                    invalidate(seenDocumentNames.get(normalizedName), 'This document name has already been selected.');
                } else {
                    seenDocumentNames.set(normalizedName, name);
                }
                if (!hasUploadedContractFile(fileData)) invalidate(fileInput, 'Please upload the document before submitting.');
                if (Number(fileData.sizeBytes || 0) > 4 * 1024 * 1024) invalidate(fileInput, 'The document must be 4 MB or smaller.');
            });

            if (!valid) {
                focusFirstContractError();
                toast('Please correct the highlighted fields before submitting.');
            }
            return valid;
        }

        function upsertLocal(row) {
            const index = contracts.findIndex((item) => item.contractId === row.contractId);
            if (index >= 0) {
                contracts[index] = row;
                return index;
            }
            contracts.unshift(row);
            return 0;
        }

        function syncContracts(validateContractId = '') {
            if (window.FleetmanRecordApi && resources?.contracts?.store) {
                return window.FleetmanRecordApi.persistCollection('contracts', contracts, {
                    extra: { validateContractId },
                }).then((payload) => {
                    if (Array.isArray(payload?.rows)) contracts = payload.rows;
                    return payload;
                });
            }
            const saveUrl = endpoint();
            if (!saveUrl) return Promise.resolve();
            return fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ rows: contracts, validateContractId }),
            }).then(async (response) => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || Object.values(payload.errors || {}).flat().join(' ') || 'Contract database save failed.');
                }
                return payload;
            }).then((payload) => {
                if (Array.isArray(payload.rows)) contracts = payload.rows;
            });
        }

        async function saveContract(savedAs) {
            const saveButton = savedAs === 'Draft' ? $('#saveContractDraftBtn') : $('#submitContractBtn');
            return window.FleetmanRunTransaction(saveButton, async () => {
                await uploadManager.waitForInputs($$('.contractDocFile'));
                if (documentSelects.hasDuplicates('#contractDocuments', '.contractDocName')) {
                    toast('Each contract document name can be selected only once.');
                    return;
                }
                const row = collectContract(savedAs);
                if (!validateContract(row, savedAs)) return;
                const previousContracts = JSON.parse(JSON.stringify(contracts || []));
                upsertLocal(row);

                try {
                    await syncContracts(row.contractId);
                    if (window.FleetmanListAccess.canView()) {
                        currentPage = 1;
                        renderList();
                        setPage('contractListPage');
                        toast(savedAs === 'Draft' ? 'Contract draft saved.' : 'Contract submitted successfully.');
                    } else {
                        contracts = [];
                        resetForm();
                        setPage('contractCreatePage');
                        toast(window.FleetmanListAccess.savedMessage('Contract', savedAs === 'Draft'));
                    }
                } catch (error) {
                    contracts = previousContracts;
                    renderList();
                    toast(error.message || 'Contract save failed. Please check server connection.');
                }
            }, { loadingText: savedAs === 'Draft' ? 'Saving Draft...' : 'Submitting...' });
        }

        function filteredContracts() {
            const status = value('#contractFilterStatus');
            const withType = value('#contractFilterWith');
            const party = value('#contractFilterParty').toLowerCase().trim();
            return contracts.filter((row) => {
                const haystack = `${row.contractId || ''} ${row.partyName || ''} ${row.contractWith || ''}`.toLowerCase();
                return (!status || row.savedAs === status || row.status === status)
                    && (!withType || row.contractWith === withType)
                    && (!party || haystack.includes(party));
            });
        }

        function badgeClass(valueToCheck) {
            if (valueToCheck === 'Active' || valueToCheck === 'Submitted') return 'ok';
            if (valueToCheck === 'Draft' || valueToCheck === 'Initiated') return 'warn';
            return 'soft';
        }

        function renderList() {
            const items = filteredContracts();
            const totalVehicles = items.reduce((sum, row) => sum + (row.assignments || []).length, 0);
            const totalValue = items.reduce((sum, row) => sum + Number(row.amount || 0), 0);
            if ($('#contractKpiTotal')) $('#contractKpiTotal').textContent = items.length;
            if ($('#contractKpiActive')) $('#contractKpiActive').textContent = items.filter((row) => row.status === 'Active').length;
            if ($('#contractKpiDraft')) $('#contractKpiDraft').textContent = items.filter((row) => row.savedAs === 'Draft').length;
            if ($('#contractKpiVehicles')) $('#contractKpiVehicles').textContent = totalVehicles;
            if ($('#contractKpiValue')) $('#contractKpiValue').textContent = money(totalValue);

            const pages = Math.max(1, Math.ceil(items.length / rowsPerPage));
            if (currentPage > pages) currentPage = pages;
            const start = (currentPage - 1) * rowsPerPage;
            const pageItems = items.slice(start, start + rowsPerPage);
            const tbody = $('#contractListBody');
            if (tbody) {
                tbody.innerHTML = pageItems.length ? pageItems.map((row) => `
                    <tr>
                        <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                        <td><b>${escapeHtml(row.contractId)}</b></td>
                        <td>${escapeHtml(row.partyName || '-')}</td>
                        <td>${escapeHtml(row.contractWith || '-')}</td>
                        <td><span class="badge ${badgeClass(row.status)}">${escapeHtml(row.status || '-')}</span></td>
                        <td>${money(row.amount)}</td>
                        <td>${formatDate(row.contractStart)}</td>
                        <td>${formatDate(row.contractEnd)}</td>
                        <td>${(row.assignments || []).length}</td>
                        <td>${new Set((row.assignments || []).map((item) => item.driverId || item.driver)).size}</td>
                        <td>${(row.documents || []).length}</td>
                        <td>${window.FleetmanExpiringDocuments.html(row.documents || [])}</td>
                        <td><span class="badge ${badgeClass(row.savedAs)}">${escapeHtml(row.savedAs || '-')}</span></td>
                        <td><button class="mini-btn view-contract" type="button" data-id="${escapeHtml(row.contractId)}">View</button><button class="mini-btn edit-contract" type="button" data-id="${escapeHtml(row.contractId)}">Edit</button><button class="mini-btn danger delete-contract" type="button" data-id="${escapeHtml(row.contractId)}">Delete</button></td>
                    </tr>`).join('') : '<tr><td colspan="14"><div class="contract-empty">No contract found for the selected filters.</div></td></tr>';
            }

            const cards = $('#contractMobileCards');
            if (cards) {
                cards.innerHTML = pageItems.map((row) => `
                    <div class="contract-card-mobile">
                        <h3>${escapeHtml(row.contractId)} <span class="badge ${badgeClass(row.savedAs)}">${escapeHtml(row.savedAs || '-')}</span></h3>
                        <div><b>${escapeHtml(row.partyName || '-')}</b> • ${escapeHtml(row.contractWith || '-')}</div>
                        <div class="contract-mini-grid">
                            <div><b>Status</b><br>${escapeHtml(row.status || '-')}</div>
                            <div><b>Amount</b><br>${money(row.amount)}</div>
                            <div><b>Start</b><br>${formatDate(row.contractStart)}</div>
                            <div><b>End</b><br>${formatDate(row.contractEnd)}</div>
                            <div><b>Assignments</b><br>${(row.assignments || []).length}</div>
                            <div><b>Documents</b><br>${(row.documents || []).length}</div>
                            <div class="contract-expiring-mobile"><b>Expiring Documents</b>${window.FleetmanExpiringDocuments.html(row.documents || [], { limit: 2 })}</div>
                        </div>
                    </div>`).join('');
            }

            if ($('#contractPageInfo')) $('#contractPageInfo').textContent = `Showing ${items.length ? start + 1 : 0} - ${Math.min(start + rowsPerPage, items.length)} of ${items.length} contracts`;
            if ($('#contractPageNumbers')) {
                $('#contractPageNumbers').innerHTML = Array.from({ length: pages }, (_, i) => `<button type="button" class="mini-btn ${currentPage === i + 1 ? 'active' : ''}" data-contract-page="${i + 1}">${i + 1}</button>`).join('');
            }
        }

        function loadContract(row) {
            if (!row) return;
            clearContractValidation();
            setValue('#contractId', row.contractId || genId());
            setChip('contractWithGroup', row.contractWith || 'Client');
            updatePartySelect(row.partyId || '');
            if (!value('#contractParty') && row.partyId) {
                const select = $('#contractParty');
                if (select) select.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(row.partyId)}" selected>${escapeHtml(row.partyName || row.partyId)}</option>`);
            }
            setValue('#contractPartyId', row.partyId || '');
            setValue('#contractAmount', row.amount || '');
            setChip('contractStatusGroup', row.status || 'Initiated');
            setValue('#contractStart', row.contractStart || '');
            setValue('#contractEnd', row.contractEnd || '');
            setValue('#contractDetails', row.details || '');
            clearRepeating();
            (row.assignments || []).forEach(addAssignment);
            if (!$('.contract-assignment-card')) addAssignment();
            (row.documents || []).forEach(addDocument);
            if (!$('.contract-doc-card')) addDocument();
        }

        function viewContract(id) {
            const row = contracts.find((item) => item.contractId === id);
            if (row) window.FleetmanDetailViewer?.show('Contract Details', row);
        }

        function editContract(id) {
            const row = contracts.find((item) => String(item.contractId || item._recordCode || '') === String(id));
            if (!row) return;
            loadContract(row);
            setPage('contractCreatePage');
        }

        async function deleteContract(id, triggerButton = null) {
            if (!confirm('Delete this contract?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                contracts = contracts.filter((row) => row.contractId !== id);
                try {
                    await syncContracts();
                    renderList();
                    toast('Contract deleted.');
                } catch (_) {
                    renderList();
                    toast('Deleted locally, but database sync failed.');
                }
            }, { loadingText: 'Deleting...' });
        }

        function loadExisting() {
            if (contracts.length) {
                loadContract(contracts[0]);
                toast('Latest saved contract loaded.');
            } else {
                resetForm();
                toast('No saved contract found yet. Blank contract form opened.');
            }
        }

        function exportContracts() {
            downloadCsv('fleetman-contract-list.csv', [
                ['Contract ID', 'Contract With', 'Party Name', 'Amount', 'Status', 'Start Date', 'End Date', 'Assignments', 'Documents', 'Saved As', 'Details'],
                ...contracts.map((row) => [row.contractId, row.contractWith, row.partyName, row.amount, row.status, row.contractStart, row.contractEnd, (row.assignments || []).length, (row.documents || []).length, row.savedAs, row.details]),
            ]);
        }

        document.addEventListener('click', (event) => {
            const chip = event.target.closest('[data-contract-chip]');
            if (chip) {
                const group = chip.dataset.contractChip === 'contractWith' ? 'contractWithGroup' : 'contractStatusGroup';
                setChip(group, chip.dataset.value);
                if (group === 'contractWithGroup') updatePartySelect();
            }

            const pageTarget = event.target.closest('[data-contract-page-target]');
            if (pageTarget) {
                renderList();
                setPage(pageTarget.dataset.contractPageTarget);
            }

            const remove = event.target.closest('.remove-contract-card');
            if (remove) {
                const card = remove.closest('.contract-assignment-card,.contract-doc-card');
                const assignmentCard = card?.classList.contains('contract-assignment-card');
                const documentCard = card?.classList.contains('contract-doc-card');
                card?.remove();
                if (assignmentCard) refreshContractAssignmentOptions();
                if (documentCard) refreshContractDocumentOptions();
            }

            const pageBtn = event.target.closest('[data-contract-page]');
            if (pageBtn) {
                currentPage = Number(pageBtn.dataset.contractPage || 1);
                renderList();
            }

            const view = event.target.closest('.view-contract');
            if (view) viewContract(view.dataset.id);

            const edit = event.target.closest('.edit-contract');
            if (edit) editContract(edit.dataset.id);

            const del = event.target.closest('.delete-contract');
            if (del) deleteContract(del.dataset.id, del);
        });

        $('#contractParty')?.addEventListener('change', () => {
            const party = selectedParty();
            setValue('#contractPartyId', party?.id || value('#contractParty'));
        });
        $('#addContractAssignmentBtn')?.addEventListener('click', () => addAssignment());
        $('#addContractDocumentBtn')?.addEventListener('click', () => addDocument());
        $('#resetContractBtn')?.addEventListener('click', resetForm);
        $('#loadContractExistingBtn')?.addEventListener('click', loadExisting);
        $('#saveContractDraftBtn')?.addEventListener('click', () => saveContract('Draft'));
        $('#submitContractBtn')?.addEventListener('click', () => saveContract('Submitted'));
        $('#exportContractsBtn')?.addEventListener('click', exportContracts);
        $('#contractPrevPageBtn')?.addEventListener('click', () => { if (currentPage > 1) { currentPage -= 1; renderList(); } });
        $('#contractNextPageBtn')?.addEventListener('click', () => {
            const pages = Math.max(1, Math.ceil(filteredContracts().length / rowsPerPage));
            if (currentPage < pages) { currentPage += 1; renderList(); }
        });
        ['#contractFilterStatus', '#contractFilterWith', '#contractFilterParty'].forEach((selector) => $(selector)?.addEventListener('input', () => { currentPage = 1; renderList(); }));
        $('#contractRowsPerPage')?.addEventListener('change', () => { rowsPerPage = Number(value('#contractRowsPerPage') || 10); currentPage = 1; renderList(); });
        $('#contractCreatePage')?.addEventListener('input', (event) => {
            const changedField = event.target.closest('input, select, textarea');
            if (changedField) clearContractFieldError(changedField);
        });

        document.addEventListener('change', (event) => {
            const changedField = event.target.closest('#contractCreatePage input, #contractCreatePage select, #contractCreatePage textarea');
            if (changedField) clearContractFieldError(changedField);

            const assignmentSelect = event.target.closest('#contractAssignments .contractAsgDriver, #contractAssignments .contractAsgVehicle');
            if (assignmentSelect) refreshContractAssignmentOptions();

            const documentName = event.target.closest('#contractDocuments .contractDocName');
            if (documentName) refreshContractDocumentOptions();

            const file = event.target.closest('.contractDocFile');
            if (file) {
                const card = file.closest('.contract-doc-card');
                uploadManager.upload(file, uploadManager.documentOptions({
                    hidden: $('.contractDocExistingFile', card),
                    info: $('.contract-upload-info', card),
                    progress: $('.contractDocProgress', card),
                    onSuccess: () => clearContractFieldError(file),
                    onError: (message) => invalidateContractField(file, message),
                }));
            }
        });

        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('contracts', () => contracts, (rows) => { contracts = rows; }, renderList);
        const contractUrlParams = new URLSearchParams(window.location.search);
        const requestedContractAction = contractUrlParams.get('action');
        const requestedContractCode = contractUrlParams.get('code');
        if (requestedContractAction === 'edit' && requestedContractCode && contracts.some((row) => String(row.contractId || row._recordCode || '') === requestedContractCode)) {
            editContract(requestedContractCode);
        } else if (requestedContractAction === 'add') {
            setPage('contractCreatePage');
        } else {
            setPage('contractListPage');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.body.dataset.page === 'contracts') initContracts();
    });
})();
