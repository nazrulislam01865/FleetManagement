/* Vendor / Party and Large List Trip page logic. Kept separate so existing FleetMan pages stay untouched. */
(() => {
    const data = window.FLEETMAN || {};
    const options = data.options || {};
    const samples = data.samples || {};
    const records = data.records || samples || {};
    const resources = data.resources || {};

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function syncResource(resource, rows) {
        if (window.FleetmanRecordApi && resources?.[resource]?.store) {
            try {
                return await window.FleetmanRecordApi.persistCollection(resource, rows || []);
            } catch (error) {
                return { ok: false, syncFailed: true, message: error?.message || 'Database save failed.' };
            }
        }
        const endpoint = resources?.[resource]?.sync;
        if (!endpoint) return { ok: true, skipped: true };

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ rows: rows || [] }),
            });

            const contentType = String(response.headers.get('content-type') || '').toLowerCase();
            const payload = contentType.includes('application/json')
                ? await response.json().catch(() => null)
                : null;

            if (!response.ok) {
                const validationMessage = payload?.errors
                    ? Object.values(payload.errors).flat().filter(Boolean).join(' ')
                    : '';
                const sessionMessage = [401, 419].includes(response.status)
                    ? 'Your session has expired. Please log in again before saving.'
                    : '';
                const serverMessage = validationMessage
                    || sessionMessage
                    || payload?.message
                    || `The server could not save this record (HTTP ${response.status}). Please try again.`;
                const reference = payload?.error_reference
                    ? ` Reference: ${payload.error_reference}`
                    : '';
                throw new Error(`${serverMessage}${reference}`);
            }

            if (!payload || payload.ok !== true || !Array.isArray(payload.rows)) {
                throw new Error('The server did not confirm that the record was saved. Please refresh and try again.');
            }

            if (Array.isArray(rows)) {
                rows.splice(0, rows.length, ...payload.rows);
            }
            return payload;
        } catch (error) {
            const message = error instanceof TypeError
                ? 'The server could not be reached. Check the internet connection and try again.'
                : (error?.message || 'The record could not be saved because of an unexpected server error.');
            toast(message);
            return { ok: false, syncFailed: true, message };
        }
    }
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
        if (page === 'drivers') ids = ['driverAddPage', 'driverListPage'];
        if (page === 'clients') ids = ['clientAddPage', 'clientListPage'];
        if (page === 'driver-attendance') ids = ['attendanceAddPage', 'attendanceListPage'];
        if (page === 'employees') ids = ['employeeAddPage', 'employeeListPage'];
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
        let parties = Array.isArray(records.parties) ? records.parties : (samples.parties || []);
        const partyDocumentTemplates = options.party_document_templates || [];
        const documentReminders = options.document_reminders || [];
        const documentSelects = window.FleetmanUniqueDocumentSelects;
        const uploadManager = window.FleetmanTemporaryUploads;
        const partyPhonePattern = /^\d{11}$/;
        const partyEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const partyDigitsPattern = /^\d+$/;

        function partyField(element) {
            return element?.closest('.field') || element;
        }

        function clearPartyFieldError(element, customContainer = null) {
            const field = customContainer || partyField(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll(':scope > .field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function clearPartyValidation() {
            const page = $('#vendorAddPage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markPartyInvalid(element, message, customContainer = null) {
            if (!element && !customContainer) return;
            const field = customContainer || partyField(element);
            if (!field) return;
            clearPartyFieldError(element, field);
            field.classList.add('field-invalid');
            element?.setAttribute?.('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'field-error';
            error.textContent = message;
            field.appendChild(error);
        }

        function focusFirstPartyError() {
            const first = $('#vendorAddPage .field-invalid');
            if (!first) return;
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => first.querySelector('input,select,textarea')?.focus?.({ preventScroll: true }), 250);
        }

        function hasPartyUploadedFile(file = {}) {
            return Boolean(file.tempToken || file.filePath || file.fileUrl || file.previewUrl);
        }

        function parsePartyPhoto(hidden) {
            if (!hidden?.value) return {};
            try { return JSON.parse(hidden.value) || {}; } catch (_) { return {}; }
        }

        function renderPartyPhoto(fileData = {}) {
            uploadManager.render({
                info: $('#partyPhotoInfo'),
                progress: $('#partyPhotoProgress'),
                file: fileData,
                showPreview: true,
            });
        }

        function genId() {
            return 'VND' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function isFuelStationParty(type = value('#partyType'), name = value('#partyName')) {
            return /fuel|station|petrol|octane|octen|diesel|cng|lpg|gas/i.test(`${type || ''} ${name || ''}`);
        }

        function selectedPartyFuelTypes() {
            return $$('input[name="partyFuelTypes"]:checked').map((input) => input.value).filter(Boolean);
        }

        function setPartyFuelTypes(types = []) {
            const selected = new Set((Array.isArray(types) ? types : []).map(String));
            $$('input[name="partyFuelTypes"]').forEach((input) => {
                input.checked = selected.has(input.value);
            });
        }

        function toggleFuelStationFields() {
            const field = $('#partyFuelTypesField');
            if (!field) return;
            const show = isFuelStationParty();
            field.classList.toggle('hidden', !show);
            if (!show) setPartyFuelTypes([]);
        }

        function hasPendingFiles(documentFilesByParty = {}) {
            return Object.values(documentFilesByParty).some((documentMap) => Object.values(documentMap || {}).some(Boolean));
        }

        function validatePendingFiles(documentFiles = {}) {
            const allowed = uploadManager.documentPolicy().extensions;
            for (const file of Object.values(documentFiles || {})) {
                if (!file) continue;
                const ext = String(file.name || '').split('.').pop().toLowerCase();
                if (!allowed.includes(ext)) {
                    toast('Only PDF, DOC, DOCX, XLS or XLSX documents are allowed. Images are not allowed.');
                    return false;
                }
                if (file.size > 4 * 1024 * 1024) {
                    toast('Each document file must be 4 MB or smaller.');
                    return false;
                }
            }
            return true;
        }

        async function syncParties(rows, documentFilesByParty = {}) {
            if (window.FleetmanRecordApi && resources?.parties?.store) {
                try {
                    return await window.FleetmanRecordApi.persistCollection('parties', rows || [], {
                        formDataForRow: (row, rowIndex) => {
                            const formData = new FormData();
                            Object.entries(documentFilesByParty?.[rowIndex] || {}).forEach(([documentIndex, file]) => {
                                if (file) formData.append(`document_files[0][${documentIndex}]`, file);
                            });
                            return formData;
                        },
                    });
                } catch (error) {
                    toast(error.message || 'Vendor / Party could not be saved.');
                    return { ok: false, syncFailed: true, message: error.message };
                }
            }
            const endpoint = resources?.parties?.sync;
            if (!endpoint) return { ok: true, skipped: true };

            const containsFiles = hasPendingFiles(documentFilesByParty);
            if (!containsFiles) {
                return syncResource('parties', rows);
            }

            const formData = new FormData();
            formData.append('rows', JSON.stringify(rows || []));
            Object.entries(documentFilesByParty).forEach(([partyIndex, documentMap]) => {
                Object.entries(documentMap || {}).forEach(([documentIndex, file]) => {
                    if (file) formData.append(`document_files[${partyIndex}][${documentIndex}]`, file);
                });
            });

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: formData,
                });

                if (!response.ok) {
                    let message = 'Vendor / Party could not be saved.';
                    try {
                        const error = await response.json();
                        message = error.message || Object.values(error.errors || {}).flat().join(' ') || message;
                    } catch (_) {}
                    throw new Error(message);
                }

                return await response.json().catch(() => ({ ok: true }));
            } catch (error) {
                toast(error.message || 'Vendor / Party could not be saved.');
                return { ok: false, syncFailed: true, message: error.message };
            }
        }

        function saveStore(documentFilesByParty = {}) {
            return syncParties(parties, documentFilesByParty);
        }

        function addContact(row = {}) {
            const wrapper = $('#partyContacts');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row contact-row';
            const meta = row.email || row.whatsapp || '';
            div.innerHTML = `
                <div class="field"><label>Name <span class="req">*</span></label><input class="partyContactName" required aria-required="true" placeholder="Example: Md. Karim" value="${escapeHtml(row.name || '')}"></div>
                <div class="field"><label>Role</label><input class="partyContactRole" placeholder="Example: Manager" value="${escapeHtml(row.role || '')}"></div>
                <div class="field"><label>Phone Number <span class="req">*</span></label><input class="partyContactPhone" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" required aria-required="true" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone || '')}"></div>
                <div class="field"><label>Email / WhatsApp</label><input class="partyContactMeta" placeholder="Valid email or 11-digit WhatsApp number" value="${escapeHtml(meta)}"></div>
                <button type="button" class="mini-btn danger remove-row">Remove</button>`;
            wrapper.appendChild(div);
        }

        function normalizeDocumentFile(row = {}) {
            if (row.file && typeof row.file === 'object') return row.file;
            if (row.filePath || row.fileUrl || row.originalName) {
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

        function fileDataFromRow(row) {
            const hidden = $('.partyDocFileData', row);
            if (!hidden?.value) return {};
            try {
                return JSON.parse(hidden.value) || {};
            } catch (_) {
                return {};
            }
        }

        function selectedFileFromRow(row) {
            const input = $('.partyDocFile', row);
            return input?.files?.[0] || null;
        }

        function renderDocumentFileInfo(row, fileData = {}) {
            uploadManager.render({
                info: $('.partyDocUploadInfo', row),
                progress: $('.partyDocUploadProgress', row),
                file: fileData,
                showPreview: false,
            });
        }

        function refreshPartyDocumentOptions() {
            documentSelects.refresh('#partyDocuments', '.partyDocName', partyDocumentTemplates, 'Select document');
        }

        function addDocument(row = {}) {
            const wrapper = $('#partyDocuments');
            if (!wrapper) return;
            const fileData = normalizeDocumentFile(row);
            const rendered = window.FleetmanDocumentRows.create({
                row,
                fileData,
                rowClass: 'document-row party-document-row',
                names: partyDocumentTemplates,
                reminders: documentReminders,
                namePlaceholder: 'Select document',
                classes: {
                    name: 'partyDocName', expiry: 'partyDocExpiry', reminder: 'partyDocReminder',
                    file: 'partyDocFile', hidden: 'partyDocFileData', progress: 'partyDocUploadProgress', info: 'partyDocUploadInfo'
                },
                extraHidden: [{ className: 'partyDocNumber', value: row.number || row.reference || '' }]
            });
            wrapper.appendChild(rendered.element);
            renderDocumentFileInfo(rendered.element, fileData);
            refreshPartyDocumentOptions();
        }

        function resetForm() {
            clearPartyValidation();
            $$('#vendorAddPage input, #vendorAddPage select, #vendorAddPage textarea').forEach((element) => {
                if (element.type === 'checkbox' || element.type === 'radio') element.checked = false;
                else element.value = '';
            });
            setValue('#partyId', genId());
            setValue('#partyStatus', 'Active');
            setValue('#vendorContractorType', $('#vendorContractorType')?.dataset.defaultValue || '');
            setValue('#paymentTerms', 'Cash');
            setValue('#partyPhotoData', '');
            renderPartyPhoto({});
            setPartyFuelTypes([]);
            toggleFuelStationFields();
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

            const documentFiles = {};
            const documents = [];
            $$('.document-row').forEach((row) => {
                const existingFile = fileDataFromRow(row);
                const pendingFile = selectedFileFromRow(row);
                const doc = {
                    name: $('.partyDocName', row)?.value.trim() || '',
                    number: $('.partyDocNumber', row)?.value.trim() || '',
                    expiry: $('.partyDocExpiry', row)?.value || '',
                    reminder: $('.partyDocReminder', row)?.value || '',
                    file: existingFile,
                };

                if (doc.name || doc.number || doc.expiry || doc.reminder || doc.file?.filePath || doc.file?.fileUrl || pendingFile) {
                    const documentIndex = documents.length;
                    documents.push(doc);
                    if (pendingFile) documentFiles[documentIndex] = pendingFile;
                }
            });

            const photo = parsePartyPhoto($('#partyPhotoData'));

            return {
                party: {
                    partyId: value('#partyId'),
                    partyName: value('#partyName').trim(),
                    partyType: value('#partyType'),
                    vendorContractorType: value('#vendorContractorType'),
                    vendorValidationVersion: 2,
                    fuelStationCapabilityVersion: 1,
                    fuelTypes: isFuelStationParty() ? selectedPartyFuelTypes() : [],
                    status: statusOverride || value('#partyStatus'),
                    phone: value('#partyPhone').trim(),
                    email: value('#partyEmail').trim(),
                    whatsapp: value('#partyWhatsapp').trim(),
                    tradeLicense: value('#tradeLicense').trim(),
                    tinBin: value('#tinBin').trim(),
                    paymentTerms: value('#paymentTerms'),
                    address: value('#partyAddress').trim(),
                    about: value('#partyAbout').trim(),
                    photo,
                    photoName: photo.originalName || '',
                    contacts,
                    documents,
                },
                documentFiles,
            };
        }

        function validate(party) {
            clearPartyValidation();
            let valid = true;
            const invalidate = (element, message, container = null) => {
                markPartyInvalid(element, message, container);
                valid = false;
            };

            [
                ['#partyId', 'Vendor / Party ID is required.'],
                ['#partyName', 'Party Name is required.'],
                ['#partyType', 'Party Type is required.'],
                ['#vendorContractorType', 'Vendor / Contractor Type is required.'],
                ['#partyStatus', 'Status is required.'],
                ['#partyPhone', 'Phone Number is required.'],
                ['#partyAddress', 'Address is required.'],
            ].forEach(([selector, message]) => {
                const element = $(selector);
                if (!String(element?.value || '').trim()) invalidate(element, message);
            });

            const phone = $('#partyPhone');
            if (phone?.value && !partyPhonePattern.test(phone.value.trim())) {
                invalidate(phone, 'Phone Number must be exactly 11 digits.');
            }

            const email = $('#partyEmail');
            if (email?.value.trim() && !partyEmailPattern.test(email.value.trim())) {
                invalidate(email, 'Enter a valid email address.');
            }

            const whatsapp = $('#partyWhatsapp');
            if (whatsapp?.value.trim() && !partyPhonePattern.test(whatsapp.value.trim())) {
                invalidate(whatsapp, 'WhatsApp Number must be exactly 11 digits.');
            }

            const tradeLicense = $('#tradeLicense');
            if (tradeLicense?.value.trim() && !partyDigitsPattern.test(tradeLicense.value.trim())) {
                invalidate(tradeLicense, 'Trade License No. must contain digits only.');
            }

            if (isFuelStationParty(party.partyType, party.partyName) && !(party.fuelTypes || []).length) {
                invalidate(null, 'Select at least one fuel type sold by this fuel station.', $('#partyFuelTypesField'));
            }

            if (hasPartyUploadedFile(party.photo) && Number(party.photo?.sizeBytes || 0) > 100 * 1024) {
                invalidate($('#partyPhoto'), 'Vendor Photo must be 100 KB or smaller.', $('.party-photo-box'));
            }

            const contactRows = $$('#partyContacts .contact-row');
            if (!contactRows.length) {
                invalidate(null, 'Add at least one contact person.', $('#partyContacts'));
            }
            contactRows.forEach((row) => {
                const name = $('.partyContactName', row);
                const phoneInput = $('.partyContactPhone', row);
                const meta = $('.partyContactMeta', row);
                if (!name?.value.trim()) invalidate(name, 'Contact Person Name is required.');
                if (!partyPhonePattern.test(phoneInput?.value.trim() || '')) {
                    invalidate(phoneInput, 'Phone Number must be exactly 11 digits.');
                }
                const metaValue = meta?.value.trim() || '';
                if (metaValue) {
                    if (metaValue.includes('@') && !partyEmailPattern.test(metaValue)) {
                        invalidate(meta, 'Enter a valid contact-person email address.');
                    } else if (!metaValue.includes('@') && !partyPhonePattern.test(metaValue)) {
                        invalidate(meta, 'WhatsApp Number must be exactly 11 digits.');
                    }
                }
            });

            const documentRows = $$('#partyDocuments .document-row');
            if (!documentRows.length) {
                invalidate(null, 'Add at least one vendor document.', $('#partyDocuments'));
            }
            const selectedNames = new Map();
            documentRows.forEach((row) => {
                const name = $('.partyDocName', row);
                const fileInput = $('.partyDocFile', row);
                const file = fileDataFromRow(row);
                const pending = selectedFileFromRow(row);
                const normalizedName = String(name?.value || '').trim().toLowerCase();

                if (!normalizedName) {
                    invalidate(name, 'Document Name is required.');
                } else if (selectedNames.has(normalizedName)) {
                    invalidate(name, 'This document name has already been selected.');
                    invalidate(selectedNames.get(normalizedName), 'This document name has already been selected.');
                } else {
                    selectedNames.set(normalizedName, name);
                }

                if (!hasPartyUploadedFile(file) && !pending) {
                    invalidate(fileInput, 'Upload Document is required.');
                }

                const fileSize = Number(pending?.size || file?.sizeBytes || 0);
                if (fileSize > 4 * 1024 * 1024) {
                    invalidate(fileInput, 'The document must be 4 MB or smaller.');
                }
            });

            if (!valid) {
                toast('Please correct the highlighted vendor fields.');
                focusFirstPartyError();
            }
            return valid;
        }

        function upsert(party) {
            const index = parties.findIndex((item) => item.partyId === party.partyId);
            if (index >= 0) {
                parties[index] = party;
                return index;
            }
            parties.unshift(party);
            return 0;
        }

        function cloneParties() {
            return JSON.parse(JSON.stringify(parties || []));
        }

        async function saveParty(statusOverride) {
            const saveBtn = statusOverride === 'Draft' ? $('#savePartyDraftBtn') : $('#savePartyBtn');
            return window.FleetmanRunTransaction(saveBtn, async () => {
                await uploadManager.waitForInputs([$('#partyPhoto'), ...$$('#partyDocuments .partyDocFile')]);
                const form = collect(statusOverride);
                const party = form.party;

                if (statusOverride === 'Draft') {
                    if (!party.partyName) party.partyName = 'Draft Vendor / Party';
                    if (!party.partyType) party.partyType = 'Other';
                } else if (!validate(party)) {
                    return;
                }

                if (!validatePendingFiles(form.documentFiles)) return;

                const previousParties = cloneParties();
                const partyIndex = upsert(party);
                const filesForSync = hasPendingFiles({ [partyIndex]: form.documentFiles }) ? { [partyIndex]: form.documentFiles } : {};
                const result = await saveStore(filesForSync);

                if (result?.syncFailed || result?.ok === false) {
                    parties = previousParties;
                    renderList();
                    return;
                }

                if (Array.isArray(result?.rows)) parties = result.rows;

                if (window.FleetmanListAccess.canView()) {
                    renderList();
                    toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Vendor / Party saved.');
                    setVisible('vendorListPage');
                } else {
                    parties = [];
                    resetForm();
                    setVisible('vendorAddPage');
                    toast(window.FleetmanListAccess.savedMessage('Vendor / Party', statusOverride === 'Draft'));
                }
            }, { loadingText: statusOverride === 'Draft' ? 'Saving Draft...' : 'Saving...' });
        }

        function loadSample() {
            const sample = (samples.parties || [])[0];
            if (!sample) return;
            resetForm();
            setValue('#partyId', sample.partyId);
            setValue('#partyName', sample.partyName);
            setValue('#partyType', sample.partyType);
            setValue('#vendorContractorType', sample.vendorContractorType || $('#vendorContractorType')?.dataset.defaultValue || '');
            toggleFuelStationFields();
            setPartyFuelTypes(sample.fuelTypes || sample.supportedFuelTypes || []);
            setValue('#partyStatus', sample.status);
            setValue('#partyPhone', sample.phone);
            setValue('#partyEmail', sample.email);
            setValue('#partyWhatsapp', sample.whatsapp);
            setValue('#tradeLicense', sample.tradeLicense);
            setValue('#tinBin', sample.tinBin);
            setValue('#paymentTerms', sample.paymentTerms);
            setValue('#partyAddress', sample.address);
            setValue('#partyAbout', sample.about);
            setValue('#partyPhotoData', sample.photo ? JSON.stringify(sample.photo) : '');
            renderPartyPhoto(sample.photo || {});
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
            setValue('#vendorContractorType', party.vendorContractorType || $('#vendorContractorType')?.dataset.defaultValue || '');
            toggleFuelStationFields();
            setPartyFuelTypes(party.fuelTypes || party.supportedFuelTypes || []);
            setValue('#partyStatus', party.status);
            setValue('#partyPhone', party.phone);
            setValue('#partyEmail', party.email);
            setValue('#partyWhatsapp', party.whatsapp);
            setValue('#tradeLicense', party.tradeLicense);
            setValue('#tinBin', party.tinBin);
            setValue('#paymentTerms', party.paymentTerms);
            setValue('#partyAddress', party.address);
            setValue('#partyAbout', party.about);
            setValue('#partyPhotoData', party.photo ? JSON.stringify(party.photo) : '');
            renderPartyPhoto(party.photo || {});
            $('#partyContacts').innerHTML = '';
            (party.contacts || []).forEach(addContact);
            $('#partyDocuments').innerHTML = '';
            (party.documents || []).forEach(addDocument);
            setVisible('vendorAddPage');
        }

        async function deleteParty(id, triggerButton = null) {
            if (!confirm('Delete this vendor / party from list?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previousParties = cloneParties();
                parties = parties.filter((party) => party.partyId !== id);
                const result = await saveStore();
                if (result?.syncFailed || result?.ok === false) {
                    parties = previousParties;
                    renderList();
                    return;
                }
                renderList();
                toast('Vendor / Party deleted.');
            }, { loadingText: 'Deleting...' });
        }

        function viewParty(id) {
            const party = parties.find((item) => item.partyId === id);
            if (!party) return;
            window.FleetmanDetailViewer?.show('Vendor / Party Details', party);
        }

        function rowHtml(party) {
            const main = (party.contacts || [])[0] || {};
            const uploadedCount = (party.documents || []).filter((doc) => doc.file?.filePath || doc.file?.fileUrl).length;
            const cls = party.status === 'Active' ? 'ok' : party.status === 'Blacklisted' ? 'danger' : party.status === 'Draft' ? 'soft' : 'warn';
            return `<tr>
                <td>${window.FleetmanCreatedAtCell(party.createdAt || party.created_at, party.creatorName || party.createdBy)}</td>
                <td><div class="party-cell">${window.FleetmanEntityAvatar.html(party.photo || {}, { fallback: '🤝', alt: `${party.partyName || 'Vendor'} photo`, size: 'table' })}<div><b>${escapeHtml(party.partyName)}</b><br><small>${escapeHtml(party.partyId)}</small></div></div></td>
                <td><span class="badge soft">${escapeHtml(party.partyType || '-')}</span><br><small>${escapeHtml(party.vendorContractorType || '-')}</small>${(party.fuelTypes || []).length ? `<br><small>${escapeHtml((party.fuelTypes || []).join(', '))}</small>` : ''}</td>
                <td>${escapeHtml(party.phone || '-')}<br><small>${escapeHtml(party.email || '')}</small></td>
                <td><b>${escapeHtml(main.name || '-')}</b><br><small>${escapeHtml(main.phone || '')}${(party.contacts || []).length > 1 ? ` · +${(party.contacts || []).length - 1} more` : ''}</small></td>
                <td>${escapeHtml(party.paymentTerms || '-')}</td>
                <td>${(party.documents || []).length} document(s)<br><small>${uploadedCount} uploaded file(s)</small></td>
                <td>${window.FleetmanExpiringDocuments.html(party.documents || [])}</td>
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
                return (!query || [party.partyId, party.partyName, party.partyType, party.vendorContractorType, party.phone, party.email, party.tradeLicense, (party.fuelTypes || []).join(' '), contactText].join(' ').toLowerCase().includes(query))
                    && (!type || party.partyType === type)
                    && (!status || party.status === status)
                    && (!terms || party.paymentTerms === terms);
            });
            $('#partyTbody').innerHTML = list.length ? list.map(rowHtml).join('') : '<tr><td colspan="11" class="empty">No vendor / party found. Click “Add Vendor / Party” to create one.</td></tr>';
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
            const rows = [['Party ID', 'Party Name', 'Party Type', 'Vendor / Contractor Type', 'Fuel Types Sold', 'Status', 'Phone', 'Email', 'WhatsApp', 'Trade License', 'TIN/BIN', 'Payment Terms', 'Address', 'About', 'Contacts', 'Documents']];
            parties.forEach((party) => rows.push([
                party.partyId, party.partyName, party.partyType, party.vendorContractorType, (party.fuelTypes || []).join('; '), party.status, party.phone, party.email, party.whatsapp, party.tradeLicense, party.tinBin, party.paymentTerms, party.address, party.about,
                (party.contacts || []).map((contact) => `${contact.name} / ${contact.role || ''} / ${contact.phone || ''}`).join('; '),
                (party.documents || []).map((doc) => `${doc.name} / ${doc.number || ''} / ${doc.expiry || ''} / ${(doc.file?.originalName || doc.file?.fileName || '')}`).join('; '),
            ]));
            exportCsv(rows, 'fleetman-vendor-party-list.csv');
        }

        $('#addPartyContactBtn')?.addEventListener('click', () => addContact());
        $('#addPartyDocumentBtn')?.addEventListener('click', () => addDocument());
        $('#resetPartyBtn')?.addEventListener('click', resetForm);
        $('#savePartyBtn')?.addEventListener('click', () => saveParty());
        $('#savePartyDraftBtn')?.addEventListener('click', () => saveParty('Draft'));
        $('#loadPartySampleBtn')?.addEventListener('click', loadSample);
        $('#exportPartiesBtn')?.addEventListener('click', exportParties);
        $('#applyPartyFiltersBtn')?.addEventListener('click', renderList);
        $('#clearPartyFiltersBtn')?.addEventListener('click', clearFilters);
        ['#partySearch', '#partyFilterType', '#partyFilterStatus', '#partyFilterTerms'].forEach((selector) => $(selector)?.addEventListener('input', renderList));
        $('#partyType')?.addEventListener('change', toggleFuelStationFields);
        $('#partyName')?.addEventListener('input', toggleFuelStationFields);
        document.addEventListener('input', (event) => {
            const digitInput = event.target.closest('#partyPhone, #partyWhatsapp, #tradeLicense, #partyContacts .partyContactPhone');
            if (digitInput) {
                const maxLength = digitInput.matches('#partyPhone, #partyWhatsapp, #partyContacts .partyContactPhone') ? 11 : null;
                const digits = String(digitInput.value || '').replace(/\D/g, '');
                digitInput.value = maxLength ? digits.slice(0, maxLength) : digits;
            }
            if (event.target.closest('#vendorAddPage input, #vendorAddPage select, #vendorAddPage textarea')) {
                clearPartyFieldError(event.target);
            }
        });
        document.addEventListener('change', (event) => {
            if (event.target.closest('#vendorAddPage input, #vendorAddPage select, #vendorAddPage textarea')) {
                clearPartyFieldError(event.target);
            }
            const partyPhoto = event.target.closest('#partyPhoto');
            if (partyPhoto) {
                uploadManager.upload(partyPhoto, {
                    hidden: $('#partyPhotoData'),
                    info: $('#partyPhotoInfo'),
                    progress: $('#partyPhotoProgress'),
                    extensions: ['jpg', 'jpeg', 'png', 'webp'],
                    maxBytes: 100 * 1024,
                    imageOnly: true,
                    showPreview: true,
                    onSuccess: () => clearPartyFieldError(partyPhoto, $('.party-photo-box')),
                });
                return;
            }

            const documentName = event.target.closest('#partyDocuments .partyDocName');
            if (documentName) refreshPartyDocumentOptions();

            const input = event.target.closest('.partyDocFile');
            if (!input) return;
            const row = input.closest('.document-row');
            if (row) {
                uploadManager.upload(input, uploadManager.documentOptions({
                    hidden: $('.partyDocFileData', row),
                    info: $('.partyDocUploadInfo', row),
                    progress: $('.partyDocUploadProgress', row),
                }));
            }
        });
        document.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-row');
            if (removeButton) {
                const row = removeButton.closest('.repeat-row');
                const documentRow = row?.classList.contains('document-row');
                row?.remove();
                if (documentRow) refreshPartyDocumentOptions();
            }
            const view = event.target.closest('.view-party');
            if (view) viewParty(view.dataset.id);
            const edit = event.target.closest('.edit-party');
            if (edit) editParty(edit.dataset.id);
            const del = event.target.closest('.delete-party');
            if (del) deleteParty(del.dataset.id, del);
        });

        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('parties', () => parties, (rows) => { parties = rows; }, renderList);
        if (window.location.search.includes('action=add')) {
            setVisible('vendorAddPage');
        } else {
            setVisible('vendorListPage');
        }
    }

    function initTrips() {
        let trips = Array.isArray(records.trips) ? records.trips : (samples.trips || []);
        const purposeOptions = options.trip_purposes || [];
        const paymentMethods = Array.isArray(options.payment_types)
            ? options.payment_types.map((method) => String(method || '').trim()).filter(Boolean)
            : [];
        const vehicles = (tripMasters.vehicles || []).map((item) => ({
            id: String(item.id || ''),
            name: String(item.name || ''),
            label: String(item.label || [item.id, item.name].filter(Boolean).join(' - ')),
            regNo: String(item.regNo || ''),
            model: String(item.model || ''),
            type: String(item.type || item.category || item.subCategory || 'Vehicle'),
        })).filter((item) => item.label || item.id || item.name);
        const drivers = (tripMasters.drivers || []).map((item) => ({
            id: String(item.id || ''),
            name: String(item.name || ''),
            label: String(item.label || [item.id, item.name].filter(Boolean).join(' - ')),
            phone: String(item.phone || item.contact || ''),
        })).filter((item) => item.label || item.id || item.name);
        const clients = (tripMasters.clients || []).map((item) => ({
            id: String(item.id || ''),
            name: String(item.name || ''),
            label: String(item.label || [item.id, item.name].filter(Boolean).join(' - ')),
            phone: String(item.phone || ''),
            email: String(item.email || ''),
        })).filter((item) => item.label || item.id || item.name);

        function genId() {
            return 'TRP' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function toNum(input) {
            const number = Number(input || 0);
            return Number.isFinite(number) ? number : 0;
        }

        function roundMoney(input) {
            return Math.round((toNum(input) + Number.EPSILON) * 100) / 100;
        }

        function vehicleValue(item) {
            return item?.label || [item?.id, item?.name].filter(Boolean).join(' - ');
        }

        function driverValue(item) {
            return item?.label || [item?.id, item?.name].filter(Boolean).join(' - ');
        }

        function clientValue(item) {
            return item?.label || [item?.id, item?.name].filter(Boolean).join(' - ');
        }

        function findVehicle(input) {
            const needle = String(input || '').trim().toLowerCase();
            if (!needle) return null;
            return vehicles.find((item) => [vehicleValue(item), item.id, item.name, item.regNo]
                .filter(Boolean)
                .some((candidate) => String(candidate).trim().toLowerCase() === needle)) || null;
        }

        function findDriver(input) {
            const needle = String(input || '').trim().toLowerCase();
            if (!needle) return null;
            return drivers.find((item) => [driverValue(item), item.id, item.name, item.phone]
                .filter(Boolean)
                .some((candidate) => String(candidate).trim().toLowerCase() === needle)) || null;
        }

        function findClient(input) {
            const needle = String(input || '').trim().toLowerCase();
            if (!needle) return null;
            return clients.find((item) => [clientValue(item), item.id, item.name, item.phone, item.email]
                .filter(Boolean)
                .some((candidate) => String(candidate).trim().toLowerCase() === needle)) || null;
        }

        function isClientVisit(purpose = value('#tripPurpose')) {
            return String(purpose || '').trim().toLowerCase() === 'client visit';
        }

        function toggleClientVisitField(purpose = value('#tripPurpose')) {
            const field = $('#tripClientVisitField');
            const input = $('#tripClient');
            const show = isClientVisit(purpose);
            field?.classList.toggle('hidden', !show);
            if (input) {
                input.required = false;
                input.setAttribute('aria-required', 'false');
                if (!show) {
                    input.value = '';
                    clearFieldError(input);
                }
            }
        }

        function legacyTotal(trip) {
            const saved = toNum(trip?.totalCost ?? trip?.tripTotalCost);
            if (saved > 0) return saved;
            return roundMoney(
                toNum(trip?.fuelCost) +
                toNum(trip?.foodCost) +
                toNum(trip?.tolls) +
                toNum(trip?.otherCost) +
                toNum(trip?.accommodationCost)
            );
        }

        function tripPayments(trip) {
            return Array.isArray(trip?.payments)
                ? trip.payments.filter((payment) => payment && (payment.method || toNum(payment.amount) > 0))
                : [];
        }

        function paidAmount(trip) {
            const payments = tripPayments(trip);
            if (payments.length) return roundMoney(payments.reduce((sum, payment) => sum + toNum(payment.amount), 0));
            return roundMoney(trip?.paidAmount || 0);
        }

        function balanceDue(trip) {
            return roundMoney(Math.max(0, legacyTotal(trip) - paidAmount(trip)));
        }

        function paymentState(trip) {
            const total = legacyTotal(trip);
            const paid = paidAmount(trip);
            const balance = balanceDue(trip);
            if (total > 0 && balance <= 0.009) return 'Paid';
            if (paid > 0) return 'Partially Paid';
            return 'Unpaid';
        }

        function fillSuggestions() {
            const clientList = $('#tripClientList');
            if (clientList) {
                clientList.innerHTML = clients.map((item) => `<option value="${escapeHtml(clientValue(item))}">${escapeHtml([item.phone, item.email].filter(Boolean).join(' • '))}</option>`).join('');
            }
        }

        function renderPurposeChoices(active) {
            const box = $('#tripPurposeChoices');
            if (!box) return;
            box.innerHTML = purposeOptions.map((option) => `<button type="button" class="choice-btn ${active === option ? 'active' : ''}" data-trip-purpose="${escapeHtml(option)}">${escapeHtml(option)}</button>`).join('');
            toggleClientVisitField(active);
        }

        function paymentOptions(selected = '') {
            const selectedMethod = String(selected || '').trim();
            const methods = paymentMethods.slice();
            if (selectedMethod && !methods.some((method) => method.toLowerCase() === selectedMethod.toLowerCase())) {
                methods.push(selectedMethod);
            }

            return '<option value="">Select payment method</option>' + methods.map((method) => `<option value="${escapeHtml(method)}" ${method === selectedMethod ? 'selected' : ''}>${escapeHtml(method)}</option>`).join('');
        }

        function paymentRowHtml(payment = {}) {
            return `<div class="trip-payment-row">
                <div class="field">
                    <label>Payment Method <span class="req">*</span></label>
                    <select class="trip-payment-method" aria-label="Payment Method">${paymentOptions(String(payment.method || ''))}</select>
                </div>
                <div class="field">
                    <label>Amount (Taka) <span class="req">*</span></label>
                    <input class="trip-payment-amount" type="number" min="0.01" step="0.01" value="${escapeHtml(payment.amount ?? '')}" placeholder="0.00" inputmode="decimal">
                </div>
                <div class="field">
                    <label>Reference / Transaction ID</label>
                    <input class="trip-payment-reference" value="${escapeHtml(payment.reference || '')}" placeholder="Optional reference">
                </div>
                <button type="button" class="btn light remove-trip-payment">Remove</button>
            </div>`;
        }

        function renderPayments(payments = []) {
            const container = $('#tripPayments');
            if (!container) return;
            container.innerHTML = payments.length
                ? payments.map(paymentRowHtml).join('')
                : '<div class="trip-payment-empty">No payment added. The full total will remain as required payment.</div>';
            recalculatePayment();
        }

        function addPayment(payment = {}) {
            const container = $('#tripPayments');
            if (!container) return;
            container.querySelector('.trip-payment-empty')?.remove();
            container.insertAdjacentHTML('beforeend', paymentRowHtml(payment));
            container.querySelector('.trip-payment-row:last-child .trip-payment-method')?.focus();
            recalculatePayment();
        }

        function collectPayments() {
            return $$('.trip-payment-row', $('#tripPayments')).map((row) => ({
                method: row.querySelector('.trip-payment-method')?.value.trim() || '',
                amount: roundMoney(row.querySelector('.trip-payment-amount')?.value),
                reference: row.querySelector('.trip-payment-reference')?.value.trim() || '',
            })).filter((payment) => payment.method || payment.amount > 0 || payment.reference);
        }

        function clearPaymentLimitValidation() {
            const page = $('#tripAddPage');
            if (!page) return;

            $$('.trip-payment-limit-error', page).forEach((error) => {
                const field = error.closest('.field');
                error.remove();
                if (field && !field.querySelector('.field-error')) field.classList.remove('field-invalid');
            });

            [$('#tripTotalCost'), $('#tripPaidAmount'), ...$$('.trip-payment-amount', $('#tripPayments'))].filter(Boolean).forEach((element) => {
                const field = fieldContainer(element);
                if (!field?.querySelector('.field-error')) {
                    field?.classList.remove('field-invalid');
                    element.removeAttribute('aria-invalid');
                }
                element.setCustomValidity?.('');
            });
        }

        function markPaymentLimitInvalid(element, message) {
            if (!element) return;
            const field = fieldContainer(element);
            field?.classList.add('field-invalid');
            element.setAttribute('aria-invalid', 'true');
            element.setCustomValidity?.(message);
            if (field && !field.querySelector('.trip-payment-limit-error')) {
                const error = document.createElement('div');
                error.className = 'field-error trip-payment-limit-error';
                error.textContent = message;
                field.appendChild(error);
            }
        }

        function updatePaymentMaximums(total) {
            const amountInputs = $$('.trip-payment-amount', $('#tripPayments'));
            const enteredAmounts = amountInputs.map((input) => roundMoney(input.value));
            const totalPaid = roundMoney(enteredAmounts.reduce((sum, amount) => sum + amount, 0));

            amountInputs.forEach((input, index) => {
                if (total > 0) {
                    const otherPayments = roundMoney(totalPaid - enteredAmounts[index]);
                    input.max = Math.max(0, roundMoney(total - otherPayments)).toFixed(2);
                } else {
                    input.removeAttribute('max');
                }
            });
        }

        function recalculatePayment() {
            const total = roundMoney(value('#tripTotalCost'));
            const paid = roundMoney(collectPayments().reduce((sum, payment) => sum + payment.amount, 0));
            const balance = roundMoney(Math.max(0, total - paid));
            const isOverpaid = paid > total + 0.009;

            setValue('#tripPaidAmount', paid.toFixed(2));
            setValue('#tripBalanceDue', balance.toFixed(2));
            updatePaymentMaximums(total);
            clearPaymentLimitValidation();

            if (isOverpaid) {
                const message = 'Total paid cannot exceed the total bill (trip cost).';
                markPaymentLimitInvalid($('#tripTotalCost'), message);
                markPaymentLimitInvalid($('#tripPaidAmount'), message);
                $$('.trip-payment-amount', $('#tripPayments')).forEach((element) => {
                    if (toNum(element.value) > 0) markPaymentLimitInvalid(element, 'Reduce the payment amount so total paid does not exceed the total bill.');
                });
            }

            return { total, paid, balance, isOverpaid };
        }

        function fieldContainer(element) {
            return element?.closest('.field') || element;
        }

        function clearFieldError(element) {
            const field = fieldContainer(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll('.field-error').forEach((error) => error.remove());
            element?.removeAttribute('aria-invalid');
        }

        function clearValidation() {
            $$('.field-invalid', $('#tripAddPage')).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', $('#tripAddPage')).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', $('#tripAddPage')).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markInvalid(element, message) {
            if (!element) return;
            const field = fieldContainer(element);
            field?.classList.add('field-invalid');
            element.setAttribute('aria-invalid', 'true');
            if (field && !field.querySelector('.field-error')) {
                const error = document.createElement('div');
                error.className = 'field-error';
                error.textContent = message;
                field.appendChild(error);
            }
        }

        function validateTrip() {
            clearValidation();
            const errors = [];
            const required = [
                ['#tripId', 'Trip ID is required.'],
                ['#tripStartDate', 'Start date is required.'],
                ['#tripVehicle', 'Vehicle is required.'],
                ['#tripDriver', 'Driver is required.'],
                ['#tripTotalCost', 'Total cost is required.'],
                ['#tripDetails', 'Trip details are required.'],
            ];

            required.forEach(([selector, message]) => {
                const element = $(selector);
                if (!String(element?.value || '').trim()) {
                    markInvalid(element, message);
                    errors.push(element);
                }
            });

            const clientInput = $('#tripClient');
            if (isClientVisit() && clientInput?.value.trim() && !findClient(clientInput.value)) {
                markInvalid(clientInput, 'Select a client from the suggestion list or leave the field blank.');
                errors.push(clientInput);
            }

            const totalInput = $('#tripTotalCost');
            const total = toNum(totalInput?.value);
            if (totalInput?.value.trim() && total <= 0) {
                markInvalid(totalInput, 'Total cost must be greater than zero.');
                errors.push(totalInput);
            }

            const odoStart = $('#tripOdoStart');
            const odoEnd = $('#tripOdoEnd');
            if (odoStart?.value !== '' && toNum(odoStart.value) < 0) {
                markInvalid(odoStart, 'Odo start cannot be negative.');
                errors.push(odoStart);
            }
            if (odoEnd?.value !== '' && toNum(odoEnd.value) < 0) {
                markInvalid(odoEnd, 'Odo end cannot be negative.');
                errors.push(odoEnd);
            }
            if (odoStart?.value !== '' && odoEnd?.value !== '' && toNum(odoEnd.value) < toNum(odoStart.value)) {
                markInvalid(odoEnd, 'Odo end cannot be lower than Odo start.');
                errors.push(odoEnd);
            }

            let enteredPayment = 0;
            $$('.trip-payment-row', $('#tripPayments')).forEach((row) => {
                const method = row.querySelector('.trip-payment-method');
                const amount = row.querySelector('.trip-payment-amount');
                const reference = row.querySelector('.trip-payment-reference');
                const hasAnyValue = Boolean(method?.value || amount?.value || reference?.value);
                if (!hasAnyValue) return;
                if (!method?.value) {
                    markInvalid(method, 'Payment method is required.');
                    errors.push(method);
                }
                if (!amount?.value || toNum(amount.value) <= 0) {
                    markInvalid(amount, 'Payment amount must be greater than zero.');
                    errors.push(amount);
                } else {
                    enteredPayment += toNum(amount.value);
                }
            });

            if (enteredPayment > total + 0.009) {
                const message = 'Total paid cannot exceed the total bill (trip cost).';
                markInvalid(totalInput, message);
                markInvalid($('#tripPaidAmount'), message);
                errors.push(totalInput);
                $$('.trip-payment-amount', $('#tripPayments')).forEach((element) => {
                    if (toNum(element.value) > 0) markInvalid(element, 'Reduce the payment amount so total paid does not exceed the total bill.');
                });
            }

            if (errors.length) {
                const first = errors.find(Boolean);
                first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => first?.focus(), 250);
                toast('Please correct the highlighted required fields.');
                return false;
            }
            return true;
        }

        function collect(savedAs = 'Submitted') {
            const vehicleText = value('#tripVehicle').trim();
            const driverText = value('#tripDriver').trim();
            const vehicle = findVehicle(vehicleText);
            const driver = findDriver(driverText);
            const client = isClientVisit() ? findClient(value('#tripClient')) : null;
            const payments = collectPayments();
            const summary = recalculatePayment();
            return {
                tripValidationVersion: 2,
                savedAs: savedAs === 'Draft' ? 'Draft' : 'Submitted',
                tripId: value('#tripId').trim(),
                startDate: value('#tripStartDate'),
                vehicle: vehicleText,
                vehicleId: vehicle?.id || '',
                driver: driverText,
                driverId: driver?.id || '',
                purpose: value('#tripPurpose').trim(),
                client: client ? clientValue(client) : '',
                clientId: client?.id || '',
                fromLocation: value('#tripFromLocation').trim(),
                toLocation: value('#tripToLocation').trim(),
                odoStart: value('#tripOdoStart'),
                odoEnd: value('#tripOdoEnd'),
                totalCost: summary.total.toFixed(2),
                payments,
                paidAmount: summary.paid.toFixed(2),
                balanceDue: summary.balance.toFixed(2),
                paymentState: summary.balance <= 0.009 ? 'Paid' : (summary.paid > 0 ? 'Partially Paid' : 'Unpaid'),
                details: value('#tripDetails').trim(),
            };
        }

        function resetForm() {
            clearValidation();
            $$('#tripAddPage input:not([readonly]), #tripAddPage textarea').forEach((element) => { element.value = ''; });
            setValue('#tripId', genId());
            setValue('#tripStartDate', new Date().toISOString().slice(0, 10));
            setValue('#tripPaidAmount', '0.00');
            setValue('#tripBalanceDue', '0.00');
            renderPurposeChoices('');
            toggleClientVisitField('');
            renderPayments([]);
        }

        async function saveTrip(savedAs = 'Submitted') {
            const isDraft = savedAs === 'Draft';
            const button = isDraft ? $('#saveTripDraftBtn') : $('#saveTripBtn');
            return window.FleetmanRunTransaction(button, async () => {
                if (!isDraft && !validateTrip()) return;
                const trip = collect(isDraft ? 'Draft' : 'Submitted');
                if (!trip.tripId) {
                    trip.tripId = genId();
                    setValue('#tripId', trip.tripId);
                }
                const nextTrips = [...trips];
                const index = nextTrips.findIndex((item) => item.tripId === trip.tripId);
                if (index >= 0) nextTrips[index] = trip;
                else nextTrips.unshift(trip);

                const result = await syncResource('trips', nextTrips);
                if (!result?.ok) return;

                trips = Array.isArray(result.rows) ? result.rows : nextTrips;
                if (window.FleetmanListAccess.canView()) {
                    renderList();
                    toast(isDraft ? 'Trip draft saved.' : 'Trip saved successfully.');
                    setVisible('tripListPage');
                } else {
                    trips = [];
                    resetForm();
                    setVisible('tripAddPage');
                    toast(window.FleetmanListAccess.savedMessage('Trip', isDraft));
                }
            }, { loadingText: isDraft ? 'Saving Draft...' : 'Saving...' });
        }

        function loadTripIntoForm(trip) {
            resetForm();
            setValue('#tripId', trip.tripId || genId());
            setValue('#tripStartDate', trip.startDate || new Date().toISOString().slice(0, 10));
            setValue('#tripVehicle', trip.vehicle || '');
            setValue('#tripDriver', trip.driver || '');
            setValue('#tripPurpose', trip.purpose || '');
            setValue('#tripClient', trip.client || '');
            setValue('#tripFromLocation', trip.fromLocation || '');
            setValue('#tripToLocation', trip.toLocation || '');
            setValue('#tripOdoStart', trip.odoStart ?? '');
            setValue('#tripOdoEnd', trip.odoEnd ?? '');
            setValue('#tripTotalCost', legacyTotal(trip).toFixed(2));
            setValue('#tripDetails', trip.details || '');
            renderPurposeChoices(trip.purpose || '');
            toggleClientVisitField(trip.purpose || '');
            renderPayments(tripPayments(trip));
            recalculatePayment();
        }

        function editTrip(id) {
            const trip = trips.find((item) => item.tripId === id);
            if (!trip) return;
            loadTripIntoForm(trip);
            setVisible('tripAddPage');
        }

        async function deleteTrip(id, triggerButton = null) {
            if (!confirm('Delete this trip from the trip list?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const nextTrips = trips.filter((trip) => trip.tripId !== id);
                const result = await syncResource('trips', nextTrips);
                if (!result?.ok) return;
                trips = Array.isArray(result.rows) ? result.rows : nextTrips;
                renderList();
                toast('Trip deleted.');
            }, { loadingText: 'Deleting...' });
        }

        function viewTrip(id) {
            const trip = trips.find((item) => item.tripId === id);
            if (!trip) return;
            const details = {
                ...trip,
                totalCost: legacyTotal(trip).toFixed(2),
                paidAmount: paidAmount(trip).toFixed(2),
                balanceDue: balanceDue(trip).toFixed(2),
                paymentState: paymentState(trip),
            };
            delete details.endDate;
            delete details.tripAround;
            delete details.tripPeriod;
            delete details.status;
            delete details.fuelCost;
            delete details.foodCost;
            delete details.tolls;
            delete details.otherCost;
            delete details.accommodationCost;
            window.FleetmanDetailViewer?.show('Trip Details', details);
        }

        function rowHtml(trip) {
            const total = legacyTotal(trip);
            const paid = paidAmount(trip);
            const balance = balanceDue(trip);
            const state = paymentState(trip);
            const stateClass = state === 'Paid' ? 'paid' : state === 'Partially Paid' ? 'partial' : 'unpaid';
            const isDraft = String(trip.savedAs || trip.status || '').trim().toLowerCase() === 'draft';
            const draftBadge = isDraft ? ' <span class="badge warn">Draft</span>' : '';
            return `<tr>
                <td>${window.FleetmanCreatedAtCell(trip.createdAt || trip.created_at, trip.creatorName || trip.createdBy)}</td>
                <td><div class="trip-cell"><div class="trip-icon">🧭</div><div><b>${escapeHtml(trip.tripId)}</b>${draftBadge}<br><small>${escapeHtml([trip.purpose || 'Trip', trip.client || ''].filter(Boolean).join(' · '))}</small></div></div></td>
                <td>${escapeHtml(trip.startDate || '-')}</td>
                <td><b>${escapeHtml(trip.vehicle || '-')}</b><br><small>${escapeHtml(trip.driver || '-')}</small></td>
                <td>${escapeHtml(trip.fromLocation || '-')} → ${escapeHtml(trip.toLocation || '-')}</td>
                <td>Start: ${escapeHtml(trip.odoStart || '-')}<br><small>End: ${escapeHtml(trip.odoEnd || '-')}</small></td>
                <td><b>Total: ${money(total)}</b><br><small>Paid: ${money(paid)} · Balance: ${money(balance)}</small><br><span class="trip-payment-state ${stateClass}">${escapeHtml(state)}</span></td>
                <td><button type="button" class="mini-btn view-trip" data-id="${escapeHtml(trip.tripId)}">View</button><button type="button" class="mini-btn edit-trip" data-id="${escapeHtml(trip.tripId)}">Edit</button><button type="button" class="mini-btn danger delete-trip" data-id="${escapeHtml(trip.tripId)}">Delete</button></td>
            </tr>`;
        }

        function renderList() {
            const query = value('#tripSearch').toLowerCase();
            const vehicleQuery = value('#tripVehicleSearch').toLowerCase();
            const list = trips.filter((trip) => (!query || [trip.tripId, trip.vehicle, trip.driver, trip.client, trip.fromLocation, trip.toLocation, trip.purpose]
                .join(' ').toLowerCase().includes(query))
                && (!vehicleQuery || String(trip.vehicle || '').toLowerCase().includes(vehicleQuery)));
            $('#tripTbody').innerHTML = list.length
                ? list.map(rowHtml).join('')
                : '<tr><td colspan="8" class="empty">No trip found.</td></tr>';
            $('#tripKpiTotal').textContent = trips.length;
            $('#tripKpiCost').textContent = money(trips.reduce((sum, trip) => sum + legacyTotal(trip), 0));
            $('#tripKpiPaid').textContent = money(trips.reduce((sum, trip) => sum + paidAmount(trip), 0));
            $('#tripKpiBalance').textContent = money(trips.reduce((sum, trip) => sum + balanceDue(trip), 0));
        }

        function clearFilters() {
            setValue('#tripSearch', '');
            setValue('#tripVehicleSearch', '');
            renderList();
        }

        function exportTrips() {
            const rows = [['Trip ID', 'Saved As', 'Start Date', 'Vehicle', 'Driver', 'Purpose', 'Client', 'From Location', 'To Location', 'Odo Start', 'Odo End', 'Total Cost', 'Paid Amount', 'Remaining Payment', 'Payments', 'Details']];
            trips.forEach((trip) => {
                const payments = tripPayments(trip).map((payment) => `${payment.method}: ${roundMoney(payment.amount).toFixed(2)}${payment.reference ? ` (${payment.reference})` : ''}`).join(' | ');
                rows.push([
                    trip.tripId,
                    trip.savedAs || 'Submitted',
                    trip.startDate,
                    trip.vehicle,
                    trip.driver,
                    trip.purpose,
                    trip.client,
                    trip.fromLocation,
                    trip.toLocation,
                    trip.odoStart,
                    trip.odoEnd,
                    legacyTotal(trip).toFixed(2),
                    paidAmount(trip).toFixed(2),
                    balanceDue(trip).toFixed(2),
                    payments,
                    trip.details,
                ]);
            });
            exportCsv(rows, 'fleetman-trip-list.csv');
        }

        fillSuggestions();
        $('#tripTotalCost')?.addEventListener('input', recalculatePayment);
        $('#addTripPaymentBtn')?.addEventListener('click', () => addPayment());
        $('#resetTripBtn')?.addEventListener('click', resetForm);
        $('#saveTripBtn')?.addEventListener('click', () => saveTrip('Submitted'));
        $('#saveTripDraftBtn')?.addEventListener('click', () => saveTrip('Draft'));
        $('#exportTripsBtn')?.addEventListener('click', exportTrips);
        $('#applyTripFiltersBtn')?.addEventListener('click', renderList);
        $('#clearTripFiltersBtn')?.addEventListener('click', clearFilters);
        ['#tripSearch', '#tripVehicleSearch'].forEach((selector) => $(selector)?.addEventListener('input', renderList));

        $('#tripClient')?.addEventListener('change', (event) => {
            const match = findClient(event.target.value);
            if (match) event.target.value = clientValue(match);
        });

        $('#tripPurpose')?.addEventListener('input', (event) => {
            renderPurposeChoices(event.target.value.trim());
            toggleClientVisitField(event.target.value);
        });

        $('#tripAddPage')?.addEventListener('input', (event) => {
            clearFieldError(event.target);
            if (event.target.matches('.trip-payment-amount')) recalculatePayment();
        });
        $('#tripAddPage')?.addEventListener('change', (event) => {
            clearFieldError(event.target);
            if (event.target.matches('.trip-payment-method, .trip-payment-amount')) recalculatePayment();
        });

        document.addEventListener('click', (event) => {
            const purpose = event.target.closest('[data-trip-purpose]');
            if (purpose) {
                setValue('#tripPurpose', purpose.dataset.tripPurpose);
                renderPurposeChoices(purpose.dataset.tripPurpose);
                toggleClientVisitField(purpose.dataset.tripPurpose);
            }
            const removePayment = event.target.closest('.remove-trip-payment');
            if (removePayment) {
                removePayment.closest('.trip-payment-row')?.remove();
                if (!$('#tripPayments')?.querySelector('.trip-payment-row')) renderPayments([]);
                else recalculatePayment();
            }
            const view = event.target.closest('.view-trip');
            if (view) viewTrip(view.dataset.id);
            const edit = event.target.closest('.edit-trip');
            if (edit) editTrip(edit.dataset.id);
            const del = event.target.closest('.delete-trip');
            if (del) deleteTrip(del.dataset.id, del);
        });

        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('trips', () => trips, (rows) => { trips = rows; }, renderList);
        if (window.location.search.includes('action=add')) setVisible('tripAddPage');
        else setVisible('tripListPage');
    }

    function initDrivers() {
        let drivers = Array.isArray(records.drivers) ? records.drivers : (samples.drivers || []);
        const docTemplates = options.driver_document_templates || [];
        const docReminders = options.document_reminders || [];
        const contactTypes = options.driver_contact_types || ['Personal', 'Home', 'Relative'];
        const documentSelects = window.FleetmanUniqueDocumentSelects;
        const uploadManager = window.FleetmanTemporaryUploads;
        const phonePattern = /^\d{11}$/;
        const nidPattern = /^\d{1,17}$/;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const licenseWarnDays = 180;
        let docRowCounter = 0;

        async function syncDrivers(rows) {
            return syncResource('drivers', rows);
        }

        function saveStore(){ return syncDrivers(drivers); }
        function genId(){ return 'DVR' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }

        function driverField(element) {
            return element?.closest('.field') || element;
        }

        function clearDriverFieldError(element, customContainer = null) {
            const field = customContainer || driverField(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll(':scope > .field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function clearDriverValidation() {
            const page = $('#driverAddPage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markDriverInvalid(element, message, customContainer = null) {
            if (!element && !customContainer) return;
            const field = customContainer || driverField(element);
            if (!field) return;
            clearDriverFieldError(element, field);
            field.classList.add('field-invalid');
            element?.setAttribute?.('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'field-error';
            error.textContent = message;
            field.appendChild(error);
        }

        function focusFirstDriverError() {
            const first = $('#driverAddPage .field-invalid');
            if (!first) return;
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => first.querySelector('input,select,textarea')?.focus?.({ preventScroll: true }), 250);
        }

        function parseUploadData(hidden) {
            if (!hidden?.value) return {};
            try { return JSON.parse(hidden.value) || {}; } catch (_) { return {}; }
        }

        function hasUploadedFile(file = {}) {
            return Boolean(file.tempToken || file.filePath || file.fileUrl || file.previewUrl);
        }

        function renderDocFileInfo(container, fileData = {}) {
            uploadManager.render({
                info: container?.querySelector('.upload-meta'),
                progress: container?.querySelector('.driverDocProgress'),
                file: fileData,
                showPreview: false,
            });
        }

        function renderDriverPhoto(fileData = {}) {
            uploadManager.render({
                info: $('#driverPhotoInfo'),
                progress: $('#driverPhotoProgress'),
                file: fileData,
                showPreview: true,
            });
        }

        function calculateAge() {
            const dobInput = $('#driverDob');
            const ageInput = $('#driverAge');
            if (!dobInput || !ageInput) return '';
            const dobValue = dobInput.value;
            if (!dobValue) {
                ageInput.value = '';
                return '';
            }
            const dob = new Date(`${dobValue}T00:00:00`);
            const today = new Date();
            if (Number.isNaN(dob.getTime()) || dob > today) {
                ageInput.value = '';
                return '';
            }
            let age = today.getFullYear() - dob.getFullYear();
            const monthDifference = today.getMonth() - dob.getMonth();
            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < dob.getDate())) age -= 1;
            ageInput.value = age >= 0 ? String(age) : '';
            return ageInput.value;
        }

        function refreshDriverContactOptions() {
            // Contact rows are independent: every row keeps the complete contact-type list.
        }

        function toggleContactRelationship(row) {
            const typeSelect = $('.driverContactType', row);
            const relField = $('.rel-field', row);
            const relationship = $('.driverContactRel', row);
            const isRelative = String(typeSelect?.value || '').toLowerCase() === 'relative';
            if (relField) relField.style.display = isRelative ? 'block' : 'none';
            if (relationship) {
                relationship.required = isRelative;
                relationship.setAttribute('aria-required', isRelative ? 'true' : 'false');
                if (!isRelative) clearDriverFieldError(relationship);
            }
        }

        function addContact(row = {}) {
            const wrapper = $('#driverContacts');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row driver-contact-row';
            const typeValue = row.type || '';
            const typeOptions = [''].concat(contactTypes);
            if (typeValue && !typeOptions.some((type) => String(type).toLowerCase() === String(typeValue).toLowerCase())) typeOptions.push(typeValue);

            div.innerHTML = `
                <div class="field">
                    <label>Type <span class="req">*</span></label>
                    <select class="driverContactType" required aria-required="true">
                        ${typeOptions.map((type) => `<option value="${escapeHtml(type)}" ${typeValue === type ? 'selected' : ''}>${escapeHtml(type || 'Select contact type')}</option>`).join('')}
                    </select>
                </div>
                <div class="field">
                    <label>Phone Number <span class="req">*</span></label>
                    <input class="driverContactPhone" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" required aria-required="true" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone || '')}">
                </div>
                <div class="field rel-field">
                    <label>Relationship <span class="req">*</span></label>
                    <input class="driverContactRel" placeholder="Example: Brother or Wife" value="${escapeHtml(row.relationship || '')}">
                </div>
                <button type="button" class="mini-btn danger remove-row" style="align-self:flex-end">Remove</button>
            `;
            wrapper.appendChild(div);
            toggleContactRelationship(div);
            refreshDriverContactOptions();
        }

        function refreshDriverDocumentOptions() {
            documentSelects.refresh('#driverDocuments', '.driverDocName', docTemplates, 'Select driver document');
        }

        function addDocument(row = {}) {
            const wrapper = $('#driverDocuments');
            if (!wrapper) return;
            const rowIdx = docRowCounter++;
            const existingFile = (row.file && typeof row.file === 'object') ? row.file : {};
            const rendered = window.FleetmanDocumentRows.create({
                row,
                fileData: existingFile,
                rowClass: 'driver-document-row',
                names: docTemplates,
                reminders: docReminders,
                namePlaceholder: 'Select document',
                dataset: { docIdx: rowIdx },
                fileAttributes: `data-doc-idx="${rowIdx}"`,
                classes: {
                    name: 'driverDocName', expiry: 'driverDocExpiry', reminder: 'driverDocReminder',
                    file: 'driverDocFile', hidden: 'driverDocFileData', progress: 'driverDocProgress', info: 'driverDocUploadInfo'
                },
                extraHidden: [{ className: 'driverDocNumber', value: row.number || row.reference || '' }]
            });
            wrapper.appendChild(rendered.element);
            renderDocFileInfo(rendered.element, existingFile);
            refreshDriverDocumentOptions();
        }

        function resetForm(){
            clearDriverValidation();
            $$('#driverAddPage input, #driverAddPage select, #driverAddPage textarea').forEach((el)=>{
                if(el.type==='radio' || el.type === 'checkbox') el.checked=false;
                else if(el.type==='file') el.value='';
                else el.value='';
            });
            docRowCounter = 0;
            const today = new Date().toISOString().slice(0, 10);
            $('#driverDob')?.setAttribute('max', today);
            setValue('#driverId', genId());
            setValue('#driverOtRate', '50');
            setValue('#driverWorkingHour', '270');
            setValue('#driverSalaryTenure','Monthly');
            setValue('#driverStatus','Active');
            $('#driverContacts').innerHTML='';
            const firstType = contactTypes.includes('Personal') ? 'Personal' : (contactTypes[0] || '');
            if (firstType) addContact({ type: firstType });
            if (contactTypes.includes('Relative') && firstType !== 'Relative') addContact({ type: 'Relative' });
            $('#driverDocuments').innerHTML='';
            if (docTemplates.includes('NID Scan Copy')) addDocument({name:'NID Scan Copy'});
            if (docTemplates.includes('Driving License Copy')) addDocument({name:'Driving License Copy'});
            if (!$('#driverDocuments .driver-document-row')) addDocument();
            setValue('#driverPhotoData', '');
            renderDriverPhoto({});
        }

        function collect(statusOverride){
            const contacts = $$('#driverContacts .driver-contact-row').map((row) => ({
                type: $('.driverContactType', row)?.value || '',
                relationship: $('.driverContactRel', row)?.value.trim() || '',
                phone: $('.driverContactPhone', row)?.value.trim() || ''
            })).filter((contact) => contact.type || contact.relationship || contact.phone);

            const documents = $$('#driverDocuments .driver-document-row').map((domRow) => ({
                name: $('.driverDocName', domRow)?.value.trim() || '',
                number: $('.driverDocNumber', domRow)?.value.trim() || '',
                expiry: $('.driverDocExpiry', domRow)?.value || '',
                reminder: $('.driverDocReminder', domRow)?.value || '',
                file: parseUploadData($('.driverDocFileData', domRow)),
            })).filter((documentRow) => documentRow.name || documentRow.number || documentRow.expiry || documentRow.reminder || hasUploadedFile(documentRow.file));

            const primaryContact = contacts[0]?.phone || '';
            const secondaryContact = contacts[1]?.phone || '';
            const photo = parseUploadData($('#driverPhotoData'));

            return {
                driverValidationVersion: 1,
                driverId: value('#driverId'),
                fullName: value('#driverFullName').trim(),
                fatherName: value('#driverFatherName').trim(),
                motherName: value('#driverMotherName').trim(),
                contact: primaryContact,
                secondaryContact: secondaryContact,
                whatsapp: value('#driverWhatsapp').trim(),
                email: value('#driverEmail').trim(),
                dob: value('#driverDob'),
                age: value('#driverAge'),
                nid: value('#driverNid').trim(),
                reference: value('#driverReference').trim(),
                licenseNo: value('#driverLicenseNo').trim(),
                licenseType: value('#driverLicenseType'),
                licenseValidity: value('#driverLicenseValidity'),
                salary: value('#driverSalary'),
                salaryTenure: value('#driverSalaryTenure'),
                otRate: value('#driverOtRate'),
                workingHour: value('#driverWorkingHour'),
                vendor: value('#driverVendor'),
                status: statusOverride || value('#driverStatus'),
                duty: document.querySelector('input[name="driverDuty"]:checked')?.value || '',
                presentAddress: value('#driverPresentAddress').trim(),
                permanentAddress: value('#driverPermanentAddress').trim(),
                about: value('#driverAbout').trim(),
                contacts,
                documents,
                photo,
                photoName: photo.originalName || ''
            };
        }

        function validateDriverForm(){
            clearDriverValidation();
            let valid = true;
            const requiredFields = [
                ['#driverId', 'Driver ID is required.'],
                ['#driverFullName', 'Full Name is required.'],
                ['#driverFatherName', "Father's Name is required."],
                ['#driverMotherName', "Mother's Name is required."],
                ['#driverDob', 'Date of Birth is required.'],
                ['#driverAge', 'Age could not be calculated.'],
                ['#driverNid', 'NID is required.'],
                ['#driverReference', 'Reference is required.'],
                ['#driverLicenseNo', 'Driving License No. is required.'],
                ['#driverLicenseType', 'License Type is required.'],
                ['#driverLicenseValidity', 'License Validity Date is required.'],
                ['#driverSalary', 'Salary is required.'],
                ['#driverSalaryTenure', 'Salary Tenure is required.'],
                ['#driverOtRate', 'Overtime Rate/Hourly is required.'],
                ['#driverWorkingHour', 'Regular Working Hour is required.'],
                ['#driverVendor', 'Vendor is required.'],
                ['#driverStatus', 'Driver Status is required.'],
                ['#driverPresentAddress', 'Present Address is required.'],
                ['#driverPermanentAddress', 'Permanent Address is required.'],
                ['#driverAbout', 'About / Remarks is required.'],
            ];
            requiredFields.forEach(([selector, message]) => {
                const element = $(selector);
                if (!String(element?.value || '').trim()) {
                    markDriverInvalid(element, message);
                    valid = false;
                }
            });

            const whatsapp = $('#driverWhatsapp');
            if (whatsapp?.value && !phonePattern.test(whatsapp.value)) {
                markDriverInvalid(whatsapp, 'WhatsApp Number must be exactly 11 digits.');
                valid = false;
            }
            const email = $('#driverEmail');
            if (email?.value && !emailPattern.test(email.value.trim())) {
                markDriverInvalid(email, 'Enter a valid email address.');
                valid = false;
            }
            const nid = $('#driverNid');
            if (nid?.value && !nidPattern.test(nid.value)) {
                markDriverInvalid(nid, 'NID must contain digits only and cannot exceed 17 digits.');
                valid = false;
            }

            const age = Number(calculateAge());
            if (!Number.isFinite(age) || age < 18 || age > 80) {
                markDriverInvalid($('#driverDob'), 'Date of Birth must calculate to an age between 18 and 80 years.');
                valid = false;
            }
            const today = new Date().toISOString().slice(0, 10);
            const validity = $('#driverLicenseValidity');
            if (validity?.value && validity.value < today) {
                markDriverInvalid(validity, 'License Validity Date cannot be in the past.');
                valid = false;
            }
            [['#driverSalary', 0], ['#driverOtRate', 0], ['#driverWorkingHour', 0.000001]].forEach(([selector, minimum]) => {
                const element = $(selector);
                const number = Number(element?.value);
                if (element?.value && (!Number.isFinite(number) || number < minimum)) {
                    markDriverInvalid(element, minimum > 0 ? 'Value must be greater than zero.' : 'Value cannot be negative.');
                    valid = false;
                }
            });

            const duty = document.querySelector('input[name="driverDuty"]:checked');
            if (!duty) {
                markDriverInvalid(null, 'Preferred Duty Type is required.', $('#driverDutyField'));
                valid = false;
            }

            const contactRows = $$('#driverContacts .driver-contact-row');
            if (!contactRows.length) {
                markDriverInvalid(null, 'Add at least one contact number.', $('#driverContactsSection'));
                valid = false;
            }
            contactRows.forEach((row) => {
                const type = $('.driverContactType', row);
                const phone = $('.driverContactPhone', row);
                const relationship = $('.driverContactRel', row);
                const normalizedType = String(type?.value || '').trim().toLowerCase();
                if (!type?.value) {
                    markDriverInvalid(type, 'Contact Type is required.');
                    valid = false;
                }
                if (!phonePattern.test(String(phone?.value || ''))) {
                    markDriverInvalid(phone, 'Phone Number must be exactly 11 digits.');
                    valid = false;
                }
                if (normalizedType === 'relative' && !String(relationship?.value || '').trim()) {
                    markDriverInvalid(relationship, 'Relationship is required for a Relative contact.');
                    valid = false;
                }
            });

            const documentRows = $$('#driverDocuments .driver-document-row');
            if (!documentRows.length) {
                markDriverInvalid(null, 'Add at least one driver document.', $('#driverDocumentsSection'));
                valid = false;
            }
            documentRows.forEach((row) => {
                const name = $('.driverDocName', row);
                const fileInput = $('.driverDocFile', row);
                const expiry = $('.driverDocExpiry', row);
                const reminder = $('.driverDocReminder', row);
                const fileData = parseUploadData($('.driverDocFileData', row));
                if (!name?.value) {
                    markDriverInvalid(name, 'Document Name is required.');
                    valid = false;
                }
                if (!hasUploadedFile(fileData)) {
                    markDriverInvalid(fileInput, 'Upload File is required.');
                    valid = false;
                } else if (Number(fileData.sizeBytes || 0) > 4 * 1024 * 1024) {
                    markDriverInvalid(fileInput, 'The document must be 4 MB or smaller.');
                    valid = false;
                }
            });
            if (documentSelects.hasDuplicates('#driverDocuments', '.driverDocName')) {
                $$('#driverDocuments .driverDocName').forEach((select) => {
                    const duplicates = $$('#driverDocuments .driverDocName').filter((other) => other !== select && other.value && other.value.toLowerCase() === select.value.toLowerCase());
                    if (duplicates.length) markDriverInvalid(select, 'Each document name can be selected only once.');
                });
                valid = false;
            }

            const photoInput = $('#driverPhoto');
            const photo = parseUploadData($('#driverPhotoData'));
            const photoBox = $('.driver-photo-box');
            if (hasUploadedFile(photo) && Number(photo.sizeBytes || 0) > 100 * 1024) {
                markDriverInvalid(photoInput, 'Driver Photo must be 100 KB or smaller.', photoBox);
                valid = false;
            }

            if (!valid) {
                toast('Please correct the highlighted driver fields.');
                focusFirstDriverError();
            }
            return valid;
        }

        function upsert(row){
            const idx = drivers.findIndex((item) => item.driverId === row.driverId);
            if (idx >= 0) { drivers[idx] = row; return idx; }
            drivers.unshift(row);
            return 0;
        }

        async function saveDriver(statusOverride){
            const saveButton = statusOverride === 'Draft' ? $('#saveDriverDraftBtn') : $('#saveDriverBtn');
            return window.FleetmanRunTransaction(saveButton, async () => {
                await uploadManager.waitForInputs([$('#driverPhoto'), ...$$('#driverDocuments .driverDocFile')]);
                if (statusOverride !== 'Draft' && !validateDriverForm()) return;
                if (documentSelects.hasDuplicates('#driverDocuments', '.driverDocName')) {
                    toast('Each driver document type can be selected only once.');
                    return;
                }
                const row = collect(statusOverride);
                if(statusOverride==='Draft' && !row.fullName) row.fullName='Draft Driver';
                const previous = JSON.parse(JSON.stringify(drivers || []));
                upsert(row);
                const result = await syncDrivers(drivers);
                if (result?.syncFailed || result?.ok === false) { drivers = previous; renderList(); return; }
                const savedDriverRows = Array.isArray(result?.rows) ? result.rows : [];
                if (!savedDriverRows.some((savedRow) => String(savedRow?.driverId || '') === String(row.driverId || ''))) {
                    drivers = previous;
                    renderList();
                    toast('The driver was not found in the database response, so it was not added to the list.');
                    return;
                }
                drivers = savedDriverRows;
                if (window.FleetmanListAccess.canView()) {
                    renderList();
                    toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Driver saved. Redirecting to driver list.');
                    setVisible('driverListPage');
                } else {
                    drivers = [];
                    resetForm();
                    setVisible('driverAddPage');
                    toast(window.FleetmanListAccess.savedMessage('Driver', statusOverride === 'Draft'));
                }
            }, { loadingText: statusOverride === 'Draft' ? 'Saving Draft...' : 'Saving...' });
        }

        function populateDriverForm(row) {
            resetForm();
            const map={driverId:'#driverId',fullName:'#driverFullName',fatherName:'#driverFatherName',motherName:'#driverMotherName',whatsapp:'#driverWhatsapp',email:'#driverEmail',dob:'#driverDob',nid:'#driverNid',reference:'#driverReference',licenseNo:'#driverLicenseNo',licenseType:'#driverLicenseType',licenseValidity:'#driverLicenseValidity',salary:'#driverSalary',salaryTenure:'#driverSalaryTenure',otRate:'#driverOtRate',workingHour:'#driverWorkingHour',vendor:'#driverVendor',status:'#driverStatus',presentAddress:'#driverPresentAddress',permanentAddress:'#driverPermanentAddress',about:'#driverAbout'};
            Object.entries(map).forEach(([key,selector])=>setValue(selector,row[key]||''));
            calculateAge();
            const duty=document.querySelector(`input[name="driverDuty"][value="${CSS.escape(row.duty||'')}"]`);
            if(duty) duty.checked=true;
            $('#driverContacts').innerHTML='';
            if (row.contacts && row.contacts.length) row.contacts.forEach(addContact);
            else {
                if (row.contact) addContact({type: contactTypes.includes('Personal') ? 'Personal' : (contactTypes[0] || ''), phone: row.contact});
                if (row.secondaryContact) addContact({type: contactTypes.includes('Relative') ? 'Relative' : (contactTypes[1] || ''), phone: row.secondaryContact});
            }
            if (!$('#driverContacts .driver-contact-row')) addContact();
            $('#driverDocuments').innerHTML='';
            (row.documents||[]).filter((documentRow) => docTemplates.includes(documentRow.name)).forEach(addDocument);
            if (!$('#driverDocuments .driver-document-row')) addDocument();
            setValue('#driverPhotoData', row.photo ? JSON.stringify(row.photo) : '');
            renderDriverPhoto(row.photo || {});
        }

        function loadSample(){
            const row=(samples.drivers||[])[0];
            if(!row) return;
            populateDriverForm(row);
            toast('Sample driver data added.');
        }

        function licenseDaysRemaining(row) {
            const rawDate = String(row?.licenseValidity || '').trim();
            if (!rawDate) return null;

            const expiryDate = new Date(`${rawDate}T00:00:00`);
            if (Number.isNaN(expiryDate.getTime())) return null;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            return Math.round((expiryDate.getTime() - today.getTime()) / 86400000);
        }

        function isExpiringSoon(row) {
            const daysRemaining = licenseDaysRemaining(row);
            return daysRemaining !== null
                && daysRemaining >= 0
                && daysRemaining <= licenseWarnDays;
        }

        function matchesDriverValidityFilter(row, filterValue) {
            if (!filterValue) return true;

            const daysRemaining = licenseDaysRemaining(row);
            if (daysRemaining === null) return false;

            if (filterValue === 'within-180-days') {
                return daysRemaining >= 0 && daysRemaining <= licenseWarnDays;
            }
            if (filterValue === 'expired') return daysRemaining < 0;
            if (filterValue === 'beyond-180-days') return daysRemaining > licenseWarnDays;

            return true;
        }

        function rowHtml(row){
            const exp=isExpiringSoon(row);
            const statusClass=row.status==='Active'?'ok':row.status==='Draft'?'warn':row.status==='Blacklisted'?'danger':'soft';
            const avatar = window.FleetmanEntityAvatar.html(row.photo || {}, {
                fallback: '🧑‍✈️',
                alt: `${row.fullName || 'Driver'} photo`,
                size: 'table',
            });
            return `<tr><td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td><td><div class="driver-cell">${avatar}<div><b>${escapeHtml(row.fullName)}</b><br><small>${escapeHtml(row.driverId)} · NID: ${escapeHtml(row.nid||'-')}</small></div></div></td><td>${escapeHtml(row.contact||'-')}<br><small>${row.whatsapp?'WA: '+escapeHtml(row.whatsapp):''}</small></td><td><span class="badge soft">${escapeHtml(row.licenseType||'-')}</span><br><small>${escapeHtml(row.licenseNo||'-')}</small></td><td><span class="badge ${exp?'warn':'ok'}">${escapeHtml(row.licenseValidity||'-')}</span></td><td>${escapeHtml(row.salary||0)} / ${escapeHtml(row.salaryTenure||'-')}<br><small>OT/Hour: ${escapeHtml(row.otRate||0)}</small></td><td>${escapeHtml(row.workingHour||0)} hrs<br><small>${escapeHtml(row.duty||'-')}</small></td><td>${escapeHtml(row.vendor||'None')}</td><td>${(row.documents||[]).length} document(s)</td><td>${window.FleetmanExpiringDocuments.html(row.documents || [])}</td><td><span class="badge ${statusClass}">${escapeHtml(row.status||'-')}</span></td><td><button type="button" class="mini-btn view-driver" data-id="${escapeHtml(row.driverId)}">View</button><button type="button" class="mini-btn edit-driver" data-id="${escapeHtml(row.driverId)}">Edit</button><button type="button" class="mini-btn danger delete-driver" data-id="${escapeHtml(row.driverId)}">Delete</button></td></tr>`;
        }

        function renderList(){
            const q=value('#driverSearch').toLowerCase();
            const status=value('#driverFilterStatus');
            const license=value('#driverFilterLicense');
            const validity=value('#driverFilterValidity');
            const tenure=value('#driverFilterTenure');

            // The dashboard link preselects the appropriate filter. It behaves
            // like a normal Driver List filter and can be changed or cleared.
            const rows=drivers.filter((row)=>(!q||[row.fullName,row.contact,row.nid,row.licenseNo,row.driverId].join(' ').toLowerCase().includes(q))&&(!status||row.status===status)&&(!license||row.licenseType===license)&&matchesDriverValidityFilter(row, validity)&&(!tenure||row.salaryTenure===tenure));
            const emptyMessage = validity === 'within-180-days'
                ? 'No drivers have a license expiring within the next 180 days.'
                : validity === 'expired'
                    ? 'No drivers have an expired license.'
                    : validity === 'beyond-180-days'
                        ? 'No drivers have a license valid beyond 180 days.'
                        : 'No driver found. Click “Add Driver” to create one.';
            $('#driverTbody').innerHTML=rows.length?rows.map(rowHtml).join(''):`<tr><td colspan="12" class="empty">${emptyMessage}</td></tr>`;

            // KPI values must represent only the currently filtered list.
            $('#driverKpiTotal').textContent=rows.length;
            $('#driverKpiActive').textContent=rows.filter((row)=>row.status==='Active').length;
            $('#driverKpiExpired').textContent=rows.filter(isExpiringSoon).length;
            $('#driverKpiDocs').textContent=rows.reduce((sum,row)=>sum+(row.documents||[]).length,0);
        }

        function editDriver(id){
            const row=drivers.find((item)=>item.driverId===id);
            if(!row) return;
            populateDriverForm(row);
            setVisible('driverAddPage');
        }

        function viewDriver(id){ const row=drivers.find((item)=>item.driverId===id); if(row) window.FleetmanDetailViewer?.show('Driver Details', row); }
        async function deleteDriver(id, triggerButton = null){
            if(!confirm('Delete this driver from prototype list?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previous = drivers.slice();
                drivers = drivers.filter((row) => row.driverId !== id);
                const result = await saveStore();
                if (result?.syncFailed || result?.ok === false) {
                    drivers = previous;
                    renderList();
                    return;
                }
                renderList();
                toast('Driver deleted.');
            }, { loadingText: 'Deleting...' });
        }
        function exportDrivers(){ const rows=[['Driver ID','Full Name','Contact','NID','License No','License Type','License Validity','Salary','Salary Tenure','Overtime Rate/Hourly','Working Hour','Vendor','Status','Documents']]; drivers.forEach((row)=>rows.push([row.driverId,row.fullName,row.contact,row.nid,row.licenseNo,row.licenseType,row.licenseValidity,row.salary,row.salaryTenure,row.otRate,row.workingHour,row.vendor,row.status,(row.documents||[]).map((doc)=>doc.name).join('; ')])); exportCsv(rows,'fleetman-driver-list.csv'); }

        $('#addDriverDocumentBtn')?.addEventListener('click',()=>addDocument());
        $('#addDriverContactBtn')?.addEventListener('click',()=>addContact());
        $('#resetDriverBtn')?.addEventListener('click',resetForm);
        $('#saveDriverBtn')?.addEventListener('click',()=>saveDriver());
        $('#saveDriverDraftBtn')?.addEventListener('click',()=>saveDriver('Draft'));
        $('#loadDriverSampleBtn')?.addEventListener('click',loadSample);
        $('#exportDriversBtn')?.addEventListener('click',exportDrivers);
        $('#clearDriverFiltersBtn')?.addEventListener('click',()=>{
            ['#driverSearch','#driverFilterStatus','#driverFilterLicense','#driverFilterValidity','#driverFilterTenure']
                .forEach((selector)=>setValue(selector,''));

            const url = new URL(window.location.href);
            url.searchParams.delete('license_filter');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);

            renderList();
        });
        ['#driverSearch','#driverFilterStatus','#driverFilterLicense','#driverFilterValidity','#driverFilterTenure'].forEach((selector)=>$(selector)?.addEventListener('input',renderList));

        document.addEventListener('input', (event) => {
            if (!event.target.closest('#driverAddPage')) return;
            if (event.target.matches('#driverNid')) event.target.value = event.target.value.replace(/\D/g, '').slice(0, 17);
            if (event.target.matches('#driverWhatsapp, .driverContactPhone')) event.target.value = event.target.value.replace(/\D/g, '').slice(0, 11);
            if (event.target.matches('#driverDob')) calculateAge();
            clearDriverFieldError(event.target);
        });

        document.addEventListener('change', (event) => {
            const contactType = event.target.closest('#driverContacts .driverContactType');
            if (contactType) {
                toggleContactRelationship(contactType.closest('.driver-contact-row'));
                refreshDriverContactOptions();
            }

            const documentName = event.target.closest('#driverDocuments .driverDocName');
            if (documentName) refreshDriverDocumentOptions();

            const fileInput = event.target.closest('#driverDocuments .driverDocFile');
            if (fileInput) {
                const row = fileInput.closest('.driver-document-row');
                if (row) uploadManager.upload(fileInput, uploadManager.documentOptions({
                    hidden: $('.driverDocFileData', row),
                    info: $('.upload-meta', row),
                    progress: $('.driverDocProgress', row),
                    onSuccess: () => clearDriverFieldError(fileInput),
                }));
            }
            if (event.target.matches('#driverPhoto')) {
                uploadManager.upload(event.target, {
                    hidden: $('#driverPhotoData'),
                    info: $('#driverPhotoInfo'),
                    progress: $('#driverPhotoProgress'),
                    extensions: ['jpg','jpeg','png','webp'],
                    maxBytes: 100 * 1024,
                    imageOnly: true,
                    showPreview: true,
                    onSuccess: () => clearDriverFieldError(event.target, $('.driver-photo-box')),
                });
            }
        });

        document.addEventListener('click',(event)=>{
            const removeButton = event.target.closest('#driverAddPage .remove-row');
            if (removeButton) {
                const row = removeButton.closest('.repeat-row');
                const documentRow = row?.classList.contains('driver-document-row');
                const contactRow = row?.classList.contains('driver-contact-row');
                row?.remove();
                if (documentRow) refreshDriverDocumentOptions();
                if (contactRow) refreshDriverContactOptions();
            }
            const view=event.target.closest('.view-driver'); if(view) viewDriver(view.dataset.id);
            const edit=event.target.closest('.edit-driver'); if(edit) editDriver(edit.dataset.id);
            const del=event.target.closest('.delete-driver'); if(del) deleteDriver(del.dataset.id, del);
        });

        resetForm();

        const driverUrlParams = new URLSearchParams(window.location.search);
        const requestedLicenseFilter = driverUrlParams.get('license_filter');
        if (['within-180-days', 'expired', 'beyond-180-days'].includes(requestedLicenseFilter)) {
            setValue('#driverFilterValidity', requestedLicenseFilter);
        }

        renderList();
        window.FleetmanRecordApi?.registerInfinite('drivers', () => drivers, (rows) => { drivers = rows; }, renderList);
        if (window.location.search.includes('action=add')) setVisible('driverAddPage');
        else setVisible('driverListPage');
    }

    function initClients() {
        const STORAGE='fleetman_clients_v2';
        let clients=Array.isArray(records.clients) ? records.clients : (samples.clients||[]);
        const phonePattern = /^\d{11}$/;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const uploadManager = window.FleetmanTemporaryUploads;

        function saveStore(){ return syncResource('clients', clients); }
        function parseClientUpload(hidden){
            if(!hidden?.value) return {};
            try { return JSON.parse(hidden.value) || {}; } catch (_) { return {}; }
        }
        function hasClientUploadedFile(file={}){
            return Boolean(file.tempToken || file.filePath || file.fileUrl || file.previewUrl);
        }
        function renderClientPhoto(fileData={}){
            uploadManager.render({
                info: $('#clientPhotoInfo'),
                progress: $('#clientPhotoProgress'),
                file: fileData,
                showPreview: true,
            });
        }
        function genId(){ return 'CLI' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }
        function clientField(element){ return element?.closest('.field') || element; }
        function clearClientFieldError(element,customContainer=null){
            const field=customContainer||clientField(element);
            if(!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll('.field-error').forEach((error)=>error.remove());
            element?.removeAttribute?.('aria-invalid');
        }
        function clearClientValidation(){
            const page=$('#clientAddPage');
            if(!page) return;
            $$('.field-invalid',page).forEach((field)=>field.classList.remove('field-invalid'));
            $$('.field-error',page).forEach((error)=>error.remove());
            $$('[aria-invalid="true"]',page).forEach((element)=>element.removeAttribute('aria-invalid'));
        }
        function markClientInvalid(element,message,customContainer=null){
            if(!element && !customContainer) return;
            const field=customContainer||clientField(element);
            if(!field) return;
            clearClientFieldError(element,field);
            field.classList.add('field-invalid');
            element?.setAttribute?.('aria-invalid','true');
            const error=document.createElement('small');
            error.className='field-error';
            error.textContent=message;
            field.appendChild(error);
        }
        function focusFirstClientError(){
            const first=$('#clientAddPage .field-invalid');
            if(!first) return;
            first.scrollIntoView({behavior:'smooth',block:'center'});
            setTimeout(()=>first.querySelector('input,select,textarea')?.focus?.({preventScroll:true}),250);
        }
        function addContact(row={}){
            const wrapper=$('#clientContacts');
            if(!wrapper) return;
            const meta=row.whatsapp || row.email || '';
            const div=document.createElement('div');
            div.className='repeat-row contact-row';
            div.innerHTML=`<div class="field"><label>Contact Person Name <span class="req">*</span></label><input class="clientContactName" required aria-required="true" placeholder="Example: Md. Karim" value="${escapeHtml(row.name||'')}"></div><div class="field"><label>Role / Designation <span class="req">*</span></label><input class="clientContactRole" required aria-required="true" placeholder="Example: Operations Manager" value="${escapeHtml(row.role||'')}"></div><div class="field"><label>Phone Number <span class="req">*</span></label><input class="clientContactPhone" type="tel" required aria-required="true" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone||'')}"></div><div class="field"><label>WhatsApp / Email <span class="req">*</span></label><input class="clientContactMeta" required aria-required="true" placeholder="11-digit WhatsApp or valid email" value="${escapeHtml(meta)}"></div><button type="button" class="mini-btn danger remove-row">Remove</button>`;
            wrapper.appendChild(div);
        }
        function resetForm(){
            clearClientValidation();
            $$('#clientAddPage input,#clientAddPage select,#clientAddPage textarea').forEach((el)=>{el.value='';});
            setValue('#clientId',genId());
            setValue('#clientType','Corporate');
            setValue('#clientStatus','Active');
            setValue('#clientContactMethod','');
            setValue('#clientPhotoData','');
            renderClientPhoto({});
            $('#clientContacts').innerHTML='';
            addContact();
        }
        function collect(statusOverride){
            const contacts=$$('#clientContacts .contact-row').map((row)=>{
                const meta=$('.clientContactMeta',row)?.value.trim()||'';
                return {name:$('.clientContactName',row)?.value.trim()||'',role:$('.clientContactRole',row)?.value.trim()||'',phone:$('.clientContactPhone',row)?.value.trim()||'',whatsapp:meta.includes('@')?'':meta,email:meta.includes('@')?meta:''};
            }).filter((c)=>c.name||c.phone||c.role||c.whatsapp||c.email);
            const photo=parseClientUpload($('#clientPhotoData'));
            return {clientValidationVersion:1,clientId:value('#clientId').trim(),clientName:value('#clientName').trim(),email:value('#clientEmail').trim(),phone:value('#clientPhone').trim(),whatsapp:value('#clientWhatsapp').trim(),reference:value('#clientReference').trim(),clientType:value('#clientType'),status:statusOverride||value('#clientStatus'),contactMethod:value('#clientContactMethod'),address:value('#clientAddress').trim(),about:value('#clientAbout').trim(),contacts,photo,photoName:photo.originalName||''};
        }
        function validate(row){
            clearClientValidation();
            const errors=[];
            const invalidate=(element,message,container=null)=>{ markClientInvalid(element,message,container); if(element||container) errors.push(element||container); };
            const required=[
                ['#clientId',row.clientId,'Client ID is required.'],
                ['#clientName',row.clientName,'Client Name is required.'],
                ['#clientEmail',row.email,'Email is required.'],
                ['#clientPhone',row.phone,'Phone Number is required.'],
                ['#clientWhatsapp',row.whatsapp,'WhatsApp Number is required.'],
                ['#clientReference',row.reference,'Reference is required.'],
                ['#clientType',row.clientType,'Client Type is required.'],
                ['#clientStatus',row.status,'Status is required.'],
                ['#clientContactMethod',row.contactMethod,'Preferred Contact Method is required.'],
                ['#clientAddress',row.address,'Permanent Address is required.'],
                ['#clientAbout',row.about,'About / Notes is required.'],
            ];
            required.forEach(([selector,valueToCheck,message])=>{ if(!String(valueToCheck||'').trim()) invalidate($(selector),message); });
            if(row.email && !emailPattern.test(row.email)) invalidate($('#clientEmail'),'Please enter a valid email address.');
            if(row.phone && !phonePattern.test(row.phone)) invalidate($('#clientPhone'),'Phone Number must be exactly 11 digits.');
            if(row.whatsapp && !phonePattern.test(row.whatsapp)) invalidate($('#clientWhatsapp'),'WhatsApp Number must be exactly 11 digits.');
            if(hasClientUploadedFile(row.photo) && Number(row.photo?.sizeBytes||0)>100*1024){
                invalidate($('#clientPhoto'),'Client Logo must be 100 KB or smaller.',$('.client-photo-box'));
            }

            const contactRows=$$('#clientContacts .contact-row');
            if(!contactRows.length){
                toast('Please add at least one contact person.');
                return false;
            }
            contactRows.forEach((contactRow,index)=>{
                const name=$('.clientContactName',contactRow);
                const role=$('.clientContactRole',contactRow);
                const phone=$('.clientContactPhone',contactRow);
                const meta=$('.clientContactMeta',contactRow);
                const label=`Contact person ${index+1}`;
                if(!name?.value.trim()) invalidate(name,`${label} name is required.`);
                if(!role?.value.trim()) invalidate(role,`${label} role / designation is required.`);
                if(!phone?.value.trim()) invalidate(phone,`${label} phone number is required.`);
                else if(!phonePattern.test(phone.value.trim())) invalidate(phone,`${label} phone number must be exactly 11 digits.`);
                const metaValue=meta?.value.trim()||'';
                if(!metaValue) invalidate(meta,`${label} WhatsApp number or email is required.`);
                else if(metaValue.includes('@')){
                    if(!emailPattern.test(metaValue)) invalidate(meta,`${label} email address is invalid.`);
                } else if(!phonePattern.test(metaValue)) {
                    invalidate(meta,`${label} WhatsApp number must be exactly 11 digits.`);
                }
            });

            if(errors.length){
                focusFirstClientError();
                toast('Please correct the highlighted fields.');
                return false;
            }
            return true;
        }
        function upsert(row){ const idx=clients.findIndex((item)=>item.clientId===row.clientId); if(idx>=0) clients[idx]=row; else clients.unshift(row); return idx; }
        async function saveClient(statusOverride){
            const saveButton = statusOverride === 'Draft' ? $('#saveClientDraftBtn') : $('#saveClientBtn');
            return window.FleetmanRunTransaction(saveButton, async () => {
                await uploadManager.waitForInputs([$('#clientPhoto')]);
                const row=collect(statusOverride);
                if(statusOverride==='Draft' && !row.clientName) row.clientName='Draft Client';
                if(statusOverride!=='Draft' && !validate(row)) return;
                const previous=clients.find((item)=>item.clientId===row.clientId);
                const previousIndex=clients.findIndex((item)=>item.clientId===row.clientId);
                upsert(row);
                const result=await saveStore();
                if(result?.syncFailed){
                    if(previousIndex>=0) clients[previousIndex]=previous;
                    else clients=clients.filter((item)=>item.clientId!==row.clientId);
                    return;
                }
                if (window.FleetmanListAccess.canView()) {
                    toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Client saved. Redirecting to client list.');
                    await new Promise((resolve) => setTimeout(resolve, 450));
                    renderList();
                    setVisible('clientListPage');
                } else {
                    clients = [];
                    resetForm();
                    setVisible('clientAddPage');
                    toast(window.FleetmanListAccess.savedMessage('Client', statusOverride === 'Draft'));
                }
            }, { loadingText: statusOverride === 'Draft' ? 'Saving Draft...' : 'Saving...' });
        }
        function loadSample(){ resetForm(); const row=(samples.clients||[])[0]; if(!row) return; const map={clientId:'#clientId',clientName:'#clientName',email:'#clientEmail',phone:'#clientPhone',whatsapp:'#clientWhatsapp',reference:'#clientReference',clientType:'#clientType',status:'#clientStatus',contactMethod:'#clientContactMethod',address:'#clientAddress',about:'#clientAbout'}; Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); setValue('#clientPhotoData',row.photo?JSON.stringify(row.photo):''); renderClientPhoto(row.photo||{}); $('#clientContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); toast('Sample client data added.'); }
        function rowHtml(row){ const main=(row.contacts||[])[0]||{}; const statusClass=row.status==='Active'?'ok':row.status==='Prospect'?'warn':row.status==='Draft'?'soft':'danger'; return `<tr><td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td><td><div class="client-cell">${window.FleetmanEntityAvatar.html(row.photo||{},{fallback:'🏢',alt:`${row.clientName||'Client'} logo`,size:'table'})}<div><b>${escapeHtml(row.clientName)}</b><br><small>${escapeHtml(row.clientId)}${row.reference?' · Ref: '+escapeHtml(row.reference):''}</small></div></div></td><td>${escapeHtml(row.phone||'-')}<br><small>${escapeHtml(row.email||'')}</small></td><td><b>${escapeHtml(main.name||'-')}</b><br><small>${escapeHtml(main.phone||'')}${(row.contacts||[]).length>1?' · +'+((row.contacts||[]).length-1)+' more':''}</small></td><td><span class="badge soft">${escapeHtml(row.clientType||'-')}</span></td><td><span class="badge ${statusClass}">${escapeHtml(row.status||'-')}</span></td><td>${escapeHtml(row.contactMethod||'-')}</td><td>${escapeHtml(row.address||'-')}</td><td><button type="button" class="mini-btn view-client" data-id="${escapeHtml(row.clientId)}">View</button><button type="button" class="mini-btn edit-client" data-id="${escapeHtml(row.clientId)}">Edit</button><button type="button" class="mini-btn danger delete-client" data-id="${escapeHtml(row.clientId)}">Delete</button></td></tr>`; }
        function renderList(){ const q=value('#clientSearch').toLowerCase(), status=value('#clientFilterStatus'), type=value('#clientFilterType'), method=value('#clientFilterMethod'); const rows=clients.filter((row)=>{ const people=(row.contacts||[]).map((person)=>[person.name,person.phone,person.role,person.whatsapp,person.email].join(' ')).join(' '); return (!q||[row.clientName,row.phone,row.email,row.clientId,row.reference,people].join(' ').toLowerCase().includes(q))&&(!status||row.status===status)&&(!type||row.clientType===type)&&(!method||row.contactMethod===method); }); $('#clientTbody').innerHTML=rows.length?rows.map(rowHtml).join(''):'<tr><td colspan="9" class="empty">No client found. Click “Add Client” to create one.</td></tr>'; $('#clientKpiTotal').textContent=clients.length; $('#clientKpiActive').textContent=clients.filter((c)=>c.status==='Active').length; $('#clientKpiEmail').textContent=clients.filter((c)=>c.email).length; }
        function editClient(id){ const row=clients.find((r)=>r.clientId===id); if(!row) return; resetForm(); const map={clientId:'#clientId',clientName:'#clientName',email:'#clientEmail',phone:'#clientPhone',whatsapp:'#clientWhatsapp',reference:'#clientReference',clientType:'#clientType',status:'#clientStatus',contactMethod:'#clientContactMethod',address:'#clientAddress',about:'#clientAbout'}; Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); setValue('#clientPhotoData',row.photo?JSON.stringify(row.photo):''); renderClientPhoto(row.photo||{}); $('#clientContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); setVisible('clientAddPage'); }
        function viewClient(id){ const row=clients.find((r)=>r.clientId===id); if(row) window.FleetmanDetailViewer?.show('Client Details', row); }
        async function deleteClient(id, triggerButton = null){
            if(!confirm('Delete this client from prototype list?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previous=clients.slice();
                clients=clients.filter((row)=>row.clientId!==id);
                const result=await saveStore();
                if(result?.syncFailed || result?.ok === false){clients=previous;renderList();return;}
                renderList();
                toast('Client deleted.');
            }, { loadingText: 'Deleting...' });
        }
        function exportClients(){ const rows=[['Client ID','Client Name','Phone','WhatsApp','Email','Reference','Client Type','Status','Preferred Contact','Address','About','Contact Persons']]; clients.forEach((row)=>rows.push([row.clientId,row.clientName,row.phone,row.whatsapp,row.email,row.reference,row.clientType,row.status,row.contactMethod,row.address,row.about,(row.contacts||[]).map((p)=>`${p.name} / ${p.role||''} / ${p.phone||''}`).join('; ')])); exportCsv(rows,'fleetman-client-list.csv'); }

        $('#addClientContactBtn')?.addEventListener('click',()=>addContact());
        $('#resetClientBtn')?.addEventListener('click',resetForm);
        $('#saveClientBtn')?.addEventListener('click',()=>saveClient());
        $('#saveClientDraftBtn')?.addEventListener('click',()=>saveClient('Draft'));
        $('#loadClientSampleBtn')?.addEventListener('click',loadSample);
        $('#exportClientsBtn')?.addEventListener('click',exportClients);
        $('#applyClientFiltersBtn')?.addEventListener('click',renderList);
        $('#clearClientFiltersBtn')?.addEventListener('click',()=>{['#clientSearch','#clientFilterStatus','#clientFilterType','#clientFilterMethod'].forEach((sel)=>setValue(sel,'')); renderList();});
        ['#clientSearch','#clientFilterStatus','#clientFilterType','#clientFilterMethod'].forEach((sel)=>$(sel)?.addEventListener('input',renderList));
        $('#clientPhoto')?.addEventListener('change',(event)=>{
            uploadManager.upload(event.target,{
                hidden:$('#clientPhotoData'),
                info:$('#clientPhotoInfo'),
                progress:$('#clientPhotoProgress'),
                extensions:['jpg','jpeg','png','webp'],
                maxBytes:100*1024,
                imageOnly:true,
                showPreview:true,
                onSuccess:()=>clearClientFieldError(event.target,$('.client-photo-box')),
            });
        });
        $('#clientAddPage')?.addEventListener('input',(event)=>{
            const target=event.target;
            if(target.matches('#clientPhone,#clientWhatsapp,.clientContactPhone')) target.value=target.value.replace(/\D/g,'').slice(0,11);
            if(target.matches('.clientContactMeta') && !target.value.includes('@') && /^\d*$/.test(target.value)) target.value=target.value.slice(0,11);
            clearClientFieldError(target);
        });
        $('#clientAddPage')?.addEventListener('change',(event)=>clearClientFieldError(event.target));
        document.addEventListener('click',(e)=>{
            const remove=e.target.closest('#clientContacts .remove-row');
            if(remove){
                const rows=$$('#clientContacts .contact-row');
                if(rows.length<=1){toast('At least one contact person is required.');return;}
                remove.parentElement.remove();
            }
            const view=e.target.closest('.view-client'); if(view) viewClient(view.dataset.id);
            const edit=e.target.closest('.edit-client'); if(edit) editClient(edit.dataset.id);
            const del=e.target.closest('.delete-client'); if(del) deleteClient(del.dataset.id, del);
        });
        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('clients', () => clients, (rows) => { clients = rows; }, renderList);
        if(window.location.search.includes('action=add')) setVisible('clientAddPage'); else setVisible('clientListPage');
    }


    function initEmployees() {
        let employees = Array.isArray(records.employees) ? records.employees : (samples.employees || []);
        const docTemplates = (window.FLEETMAN_EMPLOYEE_DOC_TEMPLATES || options.employee_document_templates || []);
        const docReminders = options.document_reminders || [];
        const documentSelects = window.FleetmanUniqueDocumentSelects;
        const uploadManager = window.FleetmanTemporaryUploads;
        const CONTACT_TYPES = ['Office', 'Home', 'Relative', 'Other'];
        const phonePattern = /^\d{11}$/;
        const nidPattern = /^\d{1,17}$/;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        let docRowCounter = 0;

        async function syncEmployees(rows) { return syncResource('employees', rows); }
        function saveStore() { return syncEmployees(employees); }
        function genId() { return 'EMP' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }

        function employeeField(element) {
            return element?.closest('.field') || element;
        }

        function clearEmployeeFieldError(element, customContainer = null) {
            const field = customContainer || employeeField(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll(':scope > .field-error').forEach((error) => error.remove());
            element?.removeAttribute?.('aria-invalid');
        }

        function clearEmployeeValidation() {
            const page = $('#employeeAddPage');
            if (!page) return;
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markEmployeeInvalid(element, message, customContainer = null) {
            if (!element && !customContainer) return;
            const field = customContainer || employeeField(element);
            if (!field) return;
            clearEmployeeFieldError(element, field);
            field.classList.add('field-invalid');
            element?.setAttribute?.('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'field-error';
            error.textContent = message;
            field.appendChild(error);
        }

        function focusFirstEmployeeError() {
            const first = $('#employeeAddPage .field-invalid');
            if (!first) return;
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => first.querySelector('input,select,textarea')?.focus?.({ preventScroll: true }), 250);
        }

        function parseEmployeeUpload(hidden) {
            if (!hidden?.value) return {};
            try { return JSON.parse(hidden.value) || {}; } catch (_) { return {}; }
        }

        function hasUploadedFile(file = {}) {
            return Boolean(file.tempToken || file.filePath || file.fileUrl || file.previewUrl);
        }

        function renderDocFileInfo(wrapper, fileData = {}) {
            uploadManager.render({
                info: $('.emp-upload-info', wrapper),
                progress: $('.empDocProgress', wrapper),
                file: fileData,
                showPreview: false,
            });
        }

        function renderEmployeePhoto(fileData = {}) {
            uploadManager.render({
                info: $('#employeePhotoInfo'),
                progress: $('#employeePhotoProgress'),
                file: fileData,
                showPreview: true,
            });
        }

        function refreshEmployeeContactOptions() {
            // Contact rows are independent: every row keeps the complete contact-type list.
        }

        function toggleEmployeeRelationship(row) {
            const type = $('.empContactType', row);
            const relationshipField = $('.emp-relationship-field', row);
            const relationship = $('.empContactRelationship', row);
            const isRelative = String(type?.value || '').toLowerCase() === 'relative';
            if (relationshipField) relationshipField.style.display = isRelative ? '' : 'none';
            if (relationship) {
                relationship.required = isRelative;
                relationship.setAttribute('aria-required', isRelative ? 'true' : 'false');
                if (!isRelative) clearEmployeeFieldError(relationship);
            }
        }

        function addContact(row = {}) {
            const wrapper = $('#employeeContacts');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row emp-contact-row';
            const currentType = row.type || '';
            const employeeContactTypes = [''].concat(CONTACT_TYPES);
            if (currentType && !employeeContactTypes.some((type) => String(type).toLowerCase() === String(currentType).toLowerCase())) employeeContactTypes.push(currentType);
            const typeOptions = employeeContactTypes.map((type) =>
                `<option value="${escapeHtml(type)}" ${currentType === type ? 'selected' : ''}>${escapeHtml(type || 'Select contact type')}</option>`
            ).join('');
            div.innerHTML = `
                <div class="field">
                    <label>Type <span class="req">*</span></label>
                    <select class="empContactType" required aria-required="true">${typeOptions}</select>
                </div>
                <div class="field">
                    <label>Phone Number <span class="req">*</span></label>
                    <input class="empContactNumber" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" required aria-required="true" placeholder="01XXXXXXXXX" value="${escapeHtml(row.number || '')}">
                </div>
                <div class="field emp-relationship-field">
                    <label>Relationship <span class="req">*</span></label>
                    <input class="empContactRelationship" placeholder="Example: Brother, Wife or Father" value="${escapeHtml(row.relationship || '')}">
                </div>
                <button type="button" class="mini-btn danger remove-row" style="align-self:flex-end">Remove</button>`;
            wrapper.appendChild(div);
            toggleEmployeeRelationship(div);
            refreshEmployeeContactOptions();
        }

        function refreshEmployeeDocumentOptions() {
            documentSelects.refresh('#employeeDocuments', '.empDocName', docTemplates, 'Select employee document');
        }

        function addDocument(row = {}) {
            const wrapper = $('#employeeDocuments');
            if (!wrapper) return;
            const rowIdx = docRowCounter++;
            const existingFile = (row.file && typeof row.file === 'object') ? row.file : {};
            const rendered = window.FleetmanDocumentRows.create({
                row,
                fileData: existingFile,
                rowClass: 'emp-document-row employee-document-row',
                names: docTemplates,
                reminders: docReminders,
                showReminder: false,
                namePlaceholder: 'Select document',
                dataset: { docIdx: rowIdx },
                fileAttributes: `data-doc-idx="${rowIdx}"`,
                classes: {
                    name: 'empDocName', expiry: 'empDocExpiry', reminder: 'empDocReminder',
                    file: 'empDocFile', hidden: 'empDocFileData', progress: 'empDocProgress', info: 'emp-upload-info'
                },
                extraHidden: [{ className: 'empDocRef', value: row.reference || row.number || '' }]
            });
            wrapper.appendChild(rendered.element);
            renderDocFileInfo(rendered.element, existingFile);
            refreshEmployeeDocumentOptions();
        }

        function resetForm() {
            clearEmployeeValidation();
            $$('#employeeAddPage input, #employeeAddPage select, #employeeAddPage textarea').forEach((el) => {
                if (el.type === 'file') el.value = '';
                else el.value = '';
            });
            docRowCounter = 0;
            setValue('#employeeId', genId());
            setValue('#employeeStatus', 'Active');
            setValue('#employeeSalaryTenure', 'Monthly');
            setValue('#employeeJoiningDate', new Date().toISOString().slice(0, 10));
            const contactsWrap = $('#employeeContacts');
            if (contactsWrap) { contactsWrap.innerHTML = ''; addContact({ type: 'Office' }); }
            const docsWrap = $('#employeeDocuments');
            if (docsWrap) { docsWrap.innerHTML = ''; addDocument(); }
            setValue('#employeePhotoData', '');
            renderEmployeePhoto({});
        }

        function collect(statusOverride) {
            const contacts = $$('#employeeContacts .emp-contact-row').map((row) => ({
                type: $('.empContactType', row)?.value || '',
                number: $('.empContactNumber', row)?.value.trim() || '',
                relationship: $('.empContactRelationship', row)?.value.trim() || '',
            })).filter((contact) => contact.type || contact.number || contact.relationship);

            const documents = $$('#employeeDocuments .emp-document-row').map((domRow) => ({
                name: $('.empDocName', domRow)?.value.trim() || '',
                reference: $('.empDocRef', domRow)?.value.trim() || '',
                expiry: $('.empDocExpiry', domRow)?.value || '',
                reminder: $('.empDocReminder', domRow)?.value || '',
                file: parseEmployeeUpload($('.empDocFileData', domRow)),
            })).filter((documentRow) => documentRow.name || documentRow.reference || documentRow.expiry || documentRow.reminder || hasUploadedFile(documentRow.file));

            const photo = parseEmployeeUpload($('#employeePhotoData'));
            return {
                employeeValidationVersion: 1,
                employeeId: value('#employeeId'),
                fullName: value('#employeeFullName').trim(),
                fatherName: value('#employeeFatherName').trim(),
                motherName: value('#employeeMotherName').trim(),
                nid: value('#employeeNid').trim(),
                contactNumber: contacts[0]?.number || '',
                contacts,
                email: value('#employeeEmail').trim(),
                reference: value('#employeeReference').trim(),
                designation: value('#employeeDesignation').trim(),
                joiningDate: value('#employeeJoiningDate'),
                status: statusOverride || value('#employeeStatus'),
                socialMedia: value('#employeeSocialMedia').trim(),
                age: value('#employeeAge'),
                salary: value('#employeeSalary'),
                salaryTenure: value('#employeeSalaryTenure'),
                overtimeRate: value('#employeeOvertimeRate'),
                presentAddress: value('#employeePresentAddress').trim(),
                permanentAddress: value('#employeePermanentAddress').trim(),
                about: value('#employeeAbout').trim(),
                photo,
                photoName: photo.originalName || '',
                documents,
            };
        }

        function validateEmployeeForm() {
            clearEmployeeValidation();
            let valid = true;
            const requiredFields = [
                ['#employeeId', 'Employee ID is required.'],
                ['#employeeFullName', 'Full Name is required.'],
                ['#employeeFatherName', "Father's Name is required."],
                ['#employeeMotherName', "Mother's Name is required."],
                ['#employeeNid', 'NID is required.'],
                ['#employeeDesignation', 'Designation is required.'],
                ['#employeeJoiningDate', 'Joining Date is required.'],
                ['#employeeStatus', 'Status is required.'],
                ['#employeeSalary', 'Salary is required.'],
                ['#employeeSalaryTenure', 'Salary Tenure is required.'],
                ['#employeePresentAddress', 'Present Address is required.'],
                ['#employeePermanentAddress', 'Permanent Address is required.'],
            ];
            requiredFields.forEach(([selector, message]) => {
                const element = $(selector);
                if (!String(element?.value || '').trim()) {
                    markEmployeeInvalid(element, message);
                    valid = false;
                }
            });

            const nid = $('#employeeNid');
            if (nid?.value && !nidPattern.test(nid.value)) {
                markEmployeeInvalid(nid, 'NID must contain digits only and cannot exceed 17 digits.');
                valid = false;
            }
            const email = $('#employeeEmail');
            if (email?.value && !emailPattern.test(email.value.trim())) {
                markEmployeeInvalid(email, 'Enter a valid email address.');
                valid = false;
            }
            const age = $('#employeeAge');
            if (age?.value) {
                const ageValue = Number(age.value);
                if (!Number.isInteger(ageValue) || ageValue < 0 || ageValue > 120) {
                    markEmployeeInvalid(age, 'Age must be a whole number between 0 and 120.');
                    valid = false;
                }
            }
            [['#employeeSalary', true], ['#employeeOvertimeRate', false]].forEach(([selector, required]) => {
                const element = $(selector);
                if (!element?.value && !required) return;
                const amount = Number(element?.value);
                if (!Number.isFinite(amount) || amount < 0) {
                    markEmployeeInvalid(element, 'Value must be a valid non-negative number.');
                    valid = false;
                }
            });

            const contactRows = $$('#employeeContacts .emp-contact-row');
            if (!contactRows.length) {
                toast('Please add at least one employee contact number.');
                valid = false;
            }
            contactRows.forEach((row) => {
                const type = $('.empContactType', row);
                const number = $('.empContactNumber', row);
                const relationship = $('.empContactRelationship', row);
                const normalizedType = String(type?.value || '').trim().toLowerCase();
                if (!type?.value) {
                    markEmployeeInvalid(type, 'Contact Type is required.');
                    valid = false;
                }
                if (!phonePattern.test(String(number?.value || ''))) {
                    markEmployeeInvalid(number, 'Phone Number must be exactly 11 digits.');
                    valid = false;
                }
                if (normalizedType === 'relative' && !String(relationship?.value || '').trim()) {
                    markEmployeeInvalid(relationship, 'Relationship is required for a Relative contact.');
                    valid = false;
                }
            });

            const documentRows = $$('#employeeDocuments .emp-document-row');
            if (!documentRows.length) {
                toast('Please add at least one employee document.');
                valid = false;
            }
            documentRows.forEach((row) => {
                const name = $('.empDocName', row);
                const fileInput = $('.empDocFile', row);
                const fileData = parseEmployeeUpload($('.empDocFileData', row));
                if (!name?.value) {
                    markEmployeeInvalid(name, 'Document Name is required.');
                    valid = false;
                }
                if (!hasUploadedFile(fileData)) {
                    markEmployeeInvalid(fileInput, 'Upload File is required.');
                    valid = false;
                } else if (Number(fileData.sizeBytes || 0) > 4 * 1024 * 1024) {
                    markEmployeeInvalid(fileInput, 'The document must be 4 MB or smaller.');
                    valid = false;
                }
            });
            if (documentSelects.hasDuplicates('#employeeDocuments', '.empDocName')) {
                $$('#employeeDocuments .empDocName').forEach((select) => {
                    const duplicates = $$('#employeeDocuments .empDocName').filter((other) => other !== select && other.value && other.value.toLowerCase() === select.value.toLowerCase());
                    if (duplicates.length) markEmployeeInvalid(select, 'Each document type can be selected only once.');
                });
                valid = false;
            }

            const photoInput = $('#employeePhoto');
            const photo = parseEmployeeUpload($('#employeePhotoData'));
            const photoBox = $('.employee-photo-box');
            if (!hasUploadedFile(photo)) {
                markEmployeeInvalid(photoInput, 'Employee Photo is required.', photoBox);
                valid = false;
            } else if (Number(photo.sizeBytes || 0) > 100 * 1024) {
                markEmployeeInvalid(photoInput, 'Employee Photo must be 100 KB or smaller.', photoBox);
                valid = false;
            }

            if (!valid) {
                toast('Please correct the highlighted employee fields.');
                focusFirstEmployeeError();
            }
            return valid;
        }

        function upsert(row) {
            const index = employees.findIndex((item) => item.employeeId === row.employeeId);
            if (index >= 0) employees[index] = row;
            else employees.unshift(row);
            return employees.findIndex((item) => item.employeeId === row.employeeId);
        }

        async function saveEmployee(statusOverride) {
            const saveButton = statusOverride === 'Draft' ? $('#saveEmployeeDraftBtn') : $('#saveEmployeeBtn');
            return window.FleetmanRunTransaction(saveButton, async () => {
                await uploadManager.waitForInputs([$('#employeePhoto'), ...$$('#employeeDocuments .empDocFile')]);
                if (statusOverride !== 'Draft' && !validateEmployeeForm()) return;
                if (documentSelects.hasDuplicates('#employeeDocuments', '.empDocName')) {
                    toast('Each employee document type can be selected only once.');
                    return;
                }
                const row = collect(statusOverride);
                if (statusOverride === 'Draft') {
                    if (!row.fullName) row.fullName = 'Draft Employee';
                    if (!row.designation) row.designation = 'Other';
                    if (!row.contactNumber) row.contactNumber = 'Pending';
                }
                const previous = JSON.parse(JSON.stringify(employees || []));
                upsert(row);
                const result = await saveStore();
                if (result?.syncFailed || result?.ok === false) {
                    employees = previous;
                    renderList();
                    return;
                }
                const savedEmployeeRows = Array.isArray(result?.rows) ? result.rows : [];
                if (!savedEmployeeRows.some((savedRow) => String(savedRow?.employeeId || '') === String(row.employeeId || ''))) {
                    employees = previous;
                    renderList();
                    toast('The employee was not found in the database response, so it was not added to the list.');
                    return;
                }
                employees = savedEmployeeRows;
                if (window.FleetmanListAccess.canView()) {
                    toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Employee saved. Redirecting to employee list.');
                    await new Promise((resolve) => setTimeout(resolve, 450));
                    renderList();
                    setVisible('employeeListPage');
                } else {
                    employees = [];
                    resetForm();
                    setVisible('employeeAddPage');
                    toast(window.FleetmanListAccess.savedMessage('Employee', statusOverride === 'Draft'));
                }
            }, { loadingText: statusOverride === 'Draft' ? 'Saving Draft...' : 'Saving...' });
        }

        function populateEmployeeForm(row) {
            resetForm();
            const map = {
                employeeId: '#employeeId', fullName: '#employeeFullName', fatherName: '#employeeFatherName', motherName: '#employeeMotherName',
                nid: '#employeeNid', email: '#employeeEmail', reference: '#employeeReference',
                designation: '#employeeDesignation', joiningDate: '#employeeJoiningDate', status: '#employeeStatus', socialMedia: '#employeeSocialMedia',
                age: '#employeeAge', salary: '#employeeSalary', salaryTenure: '#employeeSalaryTenure', overtimeRate: '#employeeOvertimeRate',
                presentAddress: '#employeePresentAddress', permanentAddress: '#employeePermanentAddress', about: '#employeeAbout'
            };
            Object.entries(map).forEach(([key, selector]) => setValue(selector, row[key] || ''));
            const contactsWrap = $('#employeeContacts');
            if (contactsWrap) {
                contactsWrap.innerHTML = '';
                if (row.contacts?.length) row.contacts.forEach((contact) => addContact(contact));
                else if (row.contactNumber) addContact({ type: 'Office', number: row.contactNumber });
                else addContact({ type: 'Office' });
            }
            const docsWrap = $('#employeeDocuments');
            if (docsWrap) {
                docsWrap.innerHTML = '';
                (row.documents || []).filter((documentRow) => docTemplates.includes(documentRow.name)).forEach((documentRow) => addDocument(documentRow));
                if (!$('#employeeDocuments .emp-document-row')) addDocument();
            }
            setValue('#employeePhotoData', row.photo ? JSON.stringify(row.photo) : '');
            renderEmployeePhoto(row.photo || {});
        }

        function loadSample() {
            const row = (samples.employees || [])[0];
            if (!row) { toast('No sample data available.'); return; }
            populateEmployeeForm(row);
            toast('Sample employee data loaded.');
        }

        function statusClass(status) {
            if (status === 'Active') return 'ok';
            if (status === 'On Leave') return 'warn';
            if (status === 'Draft') return 'soft';
            return 'danger';
        }

        function formatContacts(row) {
            if (row.contacts?.length) {
                const first = row.contacts[0];
                const more = row.contacts.length > 1 ? `<br><small>+${row.contacts.length - 1} more</small>` : '';
                const rel = first.type === 'Relative' && first.relationship ? ` (${escapeHtml(first.relationship)})` : '';
                return `<span class="badge soft" style="font-size:11px">${escapeHtml(first.type)}</span> ${escapeHtml(first.number)}${rel}${more}`;
            }
            return escapeHtml(row.contactNumber || '-');
        }

        function rowHtml(row) {
            const docCount = (row.documents || []).length;
            const avatar = window.FleetmanEntityAvatar.html(row.photo || {}, {
                fallback: '👤',
                alt: `${row.fullName || 'Employee'} photo`,
                size: 'table',
            });
            return `<tr>
                <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                <td><div class="employee-cell">${avatar}<div><b>${escapeHtml(row.fullName)}</b><br><small>${escapeHtml(row.employeeId)}</small></div></div></td>
                <td>${formatContacts(row)}<br><small style="color:#667085">${escapeHtml(row.email || '')}</small></td>
                <td>${escapeHtml(row.designation || '-')}</td>
                <td>${escapeHtml(row.joiningDate || '-')}</td>
                <td>${Number(row.salary || 0).toLocaleString()}<br><small>${escapeHtml(row.salaryTenure || '')}</small></td>
                <td><span class="badge ${statusClass(row.status)}">${escapeHtml(row.status || '-')}</span></td>
                <td>${escapeHtml(row.presentAddress || '-')}</td>
                <td>${docCount > 0 ? `<span class="badge soft">${docCount} file${docCount > 1 ? 's' : ''}</span>` : '<span style="color:#aaa">—</span>'}</td>
                <td>${window.FleetmanExpiringDocuments.html(row.documents || [])}</td>
                <td><button type="button" class="mini-btn view-employee" data-id="${escapeHtml(row.employeeId)}">View</button><button type="button" class="mini-btn edit-employee" data-id="${escapeHtml(row.employeeId)}">Edit</button><button type="button" class="mini-btn danger delete-employee" data-id="${escapeHtml(row.employeeId)}">Delete</button></td>
            </tr>`;
        }

        function renderList() {
            const query = value('#employeeSearch').toLowerCase();
            const status = value('#employeeFilterStatus');
            const tenure = value('#employeeFilterTenure');
            const designation = value('#employeeFilterDesignation');
            const rows = employees.filter((row) => {
                const contactStr = (row.contacts || []).map((contact) => contact.number + ' ' + contact.type).join(' ');
                return (!query || [row.employeeId, row.fullName, row.designation, row.contactNumber, row.nid, contactStr].join(' ').toLowerCase().includes(query)) &&
                    (!status || row.status === status) && (!tenure || row.salaryTenure === tenure) && (!designation || row.designation === designation);
            });
            $('#employeeTbody').innerHTML = rows.length ? rows.map(rowHtml).join('') : '<tr><td colspan="11" class="empty">No employee found. Click “Add Employee” to create one.</td></tr>';
            $('#employeeKpiTotal').textContent = employees.length;
            $('#employeeKpiActive').textContent = employees.filter((row) => row.status === 'Active').length;
            $('#employeeKpiMonthly').textContent = employees.filter((row) => row.salaryTenure === 'Monthly').length;
            $('#employeeKpiPayroll').textContent = employees.reduce((sum, row) => sum + Number(row.salary || 0), 0).toLocaleString();
        }

        function editEmployee(id) {
            const row = employees.find((item) => item.employeeId === id);
            if (!row) return;
            populateEmployeeForm(row);
            setVisible('employeeAddPage');
        }

        function viewEmployee(id) {
            const row = employees.find((item) => item.employeeId === id);
            if (row) window.FleetmanDetailViewer?.show('Employee Details', row);
        }

        async function deleteEmployee(id, triggerButton = null) {
            if (!confirm('Delete this employee?')) return;
            return window.FleetmanRunTransaction(triggerButton, async () => {
                const previous = employees.slice();
                employees = employees.filter((row) => row.employeeId !== id);
                const result = await saveStore();
                if (result?.syncFailed || result?.ok === false) { employees = previous; renderList(); return; }
                renderList();
                toast('Employee deleted.');
            }, { loadingText: 'Deleting...' });
        }

        function exportEmployees() {
            const rows = [['Employee ID','Full Name','Father Name','Mother Name','NID','Primary Contact','All Contacts','Email','Reference','Designation','Joining Date','Status','Social Media','Age','Salary','Salary Tenure','Overtime Rate/Hourly','Present Address','Permanent Address','About','Documents']];
            employees.forEach((row) => {
                const contactStr = (row.contacts || []).map((contact) => `${contact.type}: ${contact.number}${contact.relationship ? ' (' + contact.relationship + ')' : ''}`).join('; ') || row.contactNumber || '';
                const docStr = (row.documents || []).map((documentRow) => documentRow.name || 'Document').join('; ');
                rows.push([row.employeeId,row.fullName,row.fatherName,row.motherName,row.nid,row.contactNumber,contactStr,row.email,row.reference,row.designation,row.joiningDate,row.status,row.socialMedia,row.age,row.salary,row.salaryTenure,row.overtimeRate,row.presentAddress,row.permanentAddress,row.about,docStr]);
            });
            exportCsv(rows, 'fleetman-employee-list.csv');
        }

        $('#addEmployeeContactBtn')?.addEventListener('click', () => addContact());
        $('#addEmployeeDocumentBtn')?.addEventListener('click', () => addDocument());
        $('#resetEmployeeBtn')?.addEventListener('click', resetForm);
        $('#saveEmployeeBtn')?.addEventListener('click', () => saveEmployee());
        $('#saveEmployeeDraftBtn')?.addEventListener('click', () => saveEmployee('Draft'));
        $('#loadEmployeeSampleBtn')?.addEventListener('click', loadSample);
        $('#exportEmployeesBtn')?.addEventListener('click', exportEmployees);
        $('#applyEmployeeFiltersBtn')?.addEventListener('click', renderList);
        $('#clearEmployeeFiltersBtn')?.addEventListener('click', () => { ['#employeeSearch','#employeeFilterStatus','#employeeFilterTenure','#employeeFilterDesignation'].forEach((selector) => setValue(selector, '')); renderList(); });
        ['#employeeSearch','#employeeFilterStatus','#employeeFilterTenure','#employeeFilterDesignation'].forEach((selector) => $(selector)?.addEventListener('input', renderList));

        $('#employeePhoto')?.addEventListener('change', (event) => {
            uploadManager.upload(event.target, {
                hidden: $('#employeePhotoData'),
                info: $('#employeePhotoInfo'),
                progress: $('#employeePhotoProgress'),
                extensions: ['jpg','jpeg','png','webp'],
                maxBytes: 100 * 1024,
                imageOnly: true,
                showPreview: true,
                onSuccess: () => clearEmployeeFieldError(event.target, $('.employee-photo-box')),
            });
        });

        document.addEventListener('input', (event) => {
            if (!event.target.closest('#employeeAddPage')) return;
            if (event.target.matches('#employeeNid')) event.target.value = event.target.value.replace(/\D/g, '').slice(0, 17);
            if (event.target.matches('.empContactNumber')) event.target.value = event.target.value.replace(/\D/g, '').slice(0, 11);
            clearEmployeeFieldError(event.target);
        });

        document.addEventListener('change', (event) => {
            const contactType = event.target.closest('#employeeContacts .empContactType');
            if (contactType) {
                toggleEmployeeRelationship(contactType.closest('.emp-contact-row'));
                refreshEmployeeContactOptions();
            }
            const documentName = event.target.closest('#employeeDocuments .empDocName');
            if (documentName) refreshEmployeeDocumentOptions();
            const docFile = event.target.closest('#employeeDocuments .empDocFile');
            if (docFile) {
                const row = docFile.closest('.emp-document-row');
                if (row) uploadManager.upload(docFile, uploadManager.documentOptions({
                    hidden: $('.empDocFileData', row),
                    info: $('.emp-upload-info', row),
                    progress: $('.empDocProgress', row),
                    onSuccess: () => clearEmployeeFieldError(docFile),
                }));
            }
            if (event.target.closest('#employeeAddPage')) clearEmployeeFieldError(event.target);
        });

        document.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('#employeeContacts .remove-row, #employeeDocuments .remove-row');
            if (removeBtn) {
                const row = removeBtn.closest('.emp-contact-row, .emp-document-row');
                const isContact = row?.classList.contains('emp-contact-row');
                const isDocument = row?.classList.contains('emp-document-row');
                row?.remove();
                if (isContact) refreshEmployeeContactOptions();
                if (isDocument) refreshEmployeeDocumentOptions();
            }
            const view = event.target.closest('.view-employee'); if (view) viewEmployee(view.dataset.id);
            const edit = event.target.closest('.edit-employee'); if (edit) editEmployee(edit.dataset.id);
            const del = event.target.closest('.delete-employee'); if (del) deleteEmployee(del.dataset.id, del);
        });

        syncResource('employees', employees);
        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('employees', () => employees, (rows) => { employees = rows; }, renderList);
        if (window.location.search.includes('action=add')) setVisible('employeeAddPage');
        else setVisible('employeeListPage');
    }

    function initDriverAttendance() {
        let logs = Array.isArray(records.driver_attendance) ? [...records.driver_attendance] : [];
        const attendanceResources = resources?.driver_attendance || {};
        const masters = data.attendanceMasters || { contracts: [], vehicle_driver_map: {}, drivers: [], yards: [] };
        let selectedStatus = 'Completed';
        let selectedDriverMode = 'main';
        let savingLog = false;
        const transitioningLogs = new Set();

        const normalize = (text) => String(text || '').trim();

        function normalizeTimeValue(raw) {
            if (raw instanceof Date && !Number.isNaN(raw.getTime())) {
                return `${String(raw.getHours()).padStart(2, '0')}:${String(raw.getMinutes()).padStart(2, '0')}`;
            }

            const text = String(raw ?? '').trim();
            if (!text) return '';

            const match = text.match(/^(\d{1,2}):(\d{2})(?::\d{2}(?:\.\d+)?)?\s*([ap]m)?$/i);
            if (match) {
                let hours = Number(match[1]);
                const minutes = Number(match[2]);
                const meridiem = String(match[3] || '').toLowerCase();

                if (meridiem) {
                    if (hours < 1 || hours > 12) return '';
                    if (meridiem === 'am' && hours === 12) hours = 0;
                    if (meridiem === 'pm' && hours !== 12) hours += 12;
                }

                if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
                    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                }
            }

            const parsed = new Date(text);
            if (!Number.isNaN(parsed.getTime())) {
                return `${String(parsed.getHours()).padStart(2, '0')}:${String(parsed.getMinutes()).padStart(2, '0')}`;
            }

            return '';
        }

        function setTimeValue(selector, raw) {
            const input = $(selector);
            if (!input) return '';
            const normalized = normalizeTimeValue(raw);
            input.value = normalized;

            if (normalized && input.value !== normalized && 'valueAsNumber' in input) {
                const [hours, minutes] = normalized.split(':').map(Number);
                input.valueAsNumber = ((hours * 60) + minutes) * 60000;
            }

            return input.value;
        }
        const unique = (items) => Array.from(new Set((items || []).map(normalize).filter(Boolean)));
        const labelOf = (item) => typeof item === 'string' ? item : (item?.label || item?.name || item?.id || '');
        const contractLabel = (contract = {}) => contract.label || [contract.id || contract.contractId, contract.name || contract.partyName].filter(Boolean).join(' - ');
        const contractLabels = unique((masters.contracts || []).map(contractLabel));
        const attendanceDrivers = Array.from(new Map((masters.drivers || []).map((item) => {
            const driver = typeof item === 'string'
                ? { id: item, name: item, label: item, phone: '', status: '', duty: '' }
                : {
                    id: normalize(item?.id || item?.driverId || item?.code),
                    code: normalize(item?.code),
                    name: normalize(item?.name || item?.fullName),
                    label: normalize(item?.label || [item?.id || item?.driverId, item?.name || item?.fullName].filter(Boolean).join(' - ')),
                    phone: normalize(item?.phone || item?.contact || item?.mobile),
                    status: normalize(item?.status),
                    duty: normalize(item?.duty),
                };
            const key = driver.id || driver.label || driver.name;
            return [key, driver];
        }).filter(([key, driver]) => key && normalize(driver.status).toLowerCase() === 'active')).values());
        const attendanceYards = Array.from(new Map((masters.yards || []).map((item) => {
            const yard = typeof item === 'string'
                ? { id: item, code: item, name: item, label: item, status: '', location: '' }
                : {
                    id: normalize(item?.id || item?.yardId || item?.code),
                    code: normalize(item?.code),
                    name: normalize(item?.name || item?.yardName),
                    label: normalize(item?.label || [item?.id || item?.yardId || item?.code, item?.name || item?.yardName].filter(Boolean).join(' - ')),
                    status: normalize(item?.status),
                    location: normalize(item?.location || [item?.area, item?.city].filter(Boolean).join(', ')),
                };
            const key = yard.id || yard.code || yard.label || yard.name;
            return [key, yard];
        }).filter(([key, yard]) => key && normalize(yard.status).toLowerCase() !== 'draft')).values());

        logs = logs.map((row) => {
            const startTime = normalizeTimeValue(row?.startTime);
            const endTime = normalizeTimeValue(row?.endTime);
            return {
                ...row,
                startTime,
                endTime,
                hours: startTime && endTime ? calcHours(startTime, endTime) : (row?.hours || '0h 0m'),
            };
        });

        function attendanceResponseMessage(payload, fallback) {
            const errors = Object.values(payload?.errors || {}).flat().filter(Boolean);
            return payload?.message || errors.join(' ') || fallback;
        }

        async function attendanceRequest(endpoint, requestOptions, fallbackMessage) {
            if (!endpoint) {
                return { ok: false, syncFailed: true, message: fallbackMessage };
            }

            try {
                const response = await fetch(endpoint, {
                    ...requestOptions,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        ...(requestOptions?.headers || {}),
                    },
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(attendanceResponseMessage(payload, fallbackMessage));
                }
                return payload;
            } catch (error) {
                const message = error?.message || fallbackMessage;
                toast(message);
                return { ok: false, syncFailed: true, message };
            }
        }

        async function saveStore(row) {
            const endpoint = attendanceResources.store;
            if (!endpoint) {
                return syncResource('driver_attendance', rowsWithUpsertedLog(row));
            }

            return attendanceRequest(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ row }),
            }, 'The attendance record could not be saved.');
        }

        function genId() {
            return 'DL' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function fillDatalist(id, items) {
            const node = document.getElementById(id);
            if (!node) return;
            node.innerHTML = (items || []).map((item) => {
                if (typeof item === 'string') {
                    return `<option value="${escapeHtml(item)}"></option>`;
                }
                const itemValue = normalize(item?.value || item?.label || item?.name || item?.id);
                const itemLabel = normalize(item?.optionLabel || item?.meta || '');
                return itemValue
                    ? `<option value="${escapeHtml(itemValue)}"${itemLabel ? ` label="${escapeHtml(itemLabel)}"` : ''}></option>`
                    : '';
            }).join('');
        }

        function selectedContract() {
            const current = normalize(value('#attendanceContract'));
            return (masters.contracts || []).find((contract) => {
                return contractLabel(contract) === current || normalize(contract.id) === current || normalize(contract.contractId) === current;
            }) || null;
        }

        function assignmentsFor(contract) {
            return Array.isArray(contract?.assignments) ? contract.assignments : [];
        }

        function vehicleLabel(assignment = {}) {
            return normalize(assignment.vehicleLabel || assignment.vehicle || assignment.vehicleName || assignment.vehicleId || assignment.id);
        }

        function driverLabel(assignment = {}) {
            return normalize(assignment.driverLabel || assignment.driver || assignment.driverName || assignment.driverId || assignment.id);
        }

        function driverOptionLabel(driver = {}) {
            return normalize(driver.label || [driver.id, driver.name].filter(Boolean).join(' - '));
        }

        function driverMatches(driver, candidate) {
            const needle = normalize(candidate).replace(/\s+/g, ' ').toLowerCase();
            if (!driver || !needle) return false;
            return [driver.id, driver.code, driver.name, driver.label]
                .map((value) => normalize(value).replace(/\s+/g, ' ').toLowerCase())
                .filter(Boolean)
                .includes(needle);
        }

        function driverByValue(candidate) {
            return attendanceDrivers.find((driver) => driverMatches(driver, candidate)) || null;
        }

        function yardOptionLabel(yard = {}) {
            return normalize(yard.label || [yard.id || yard.code, yard.name].filter(Boolean).join(' - '));
        }

        function yardMatches(yard, candidate) {
            const needle = normalize(candidate).replace(/\s+/g, ' ').toLowerCase();
            if (!yard || !needle) return false;
            return [yard.id, yard.code, yard.name, yard.label]
                .map((item) => normalize(item).replace(/\s+/g, ' ').toLowerCase())
                .filter(Boolean)
                .includes(needle);
        }

        function yardByValue(candidate) {
            return attendanceYards.find((yard) => yardMatches(yard, candidate)) || null;
        }

        function vehiclesFor(contract) {
            if (!contract) return [];
            const fromAssignments = assignmentsFor(contract).map(vehicleLabel);
            const fromContract = (contract.vehicles || []).map(labelOf);
            return unique([...fromAssignments, ...fromContract]);
        }

        function assignmentsForVehicle(contract, vehicle = '') {
            const currentVehicle = normalize(vehicle);
            if (!contract || !currentVehicle) return [];

            return assignmentsFor(contract)
                .filter((assignment) => vehicleLabel(assignment) === currentVehicle);
        }

        function selectedVehicleAssignment(contract = selectedContract()) {
            if (!contract) return null;

            const currentVehicle = normalize(value('#attendanceVehicle'));
            if (!currentVehicle) return null;

            return assignmentsForVehicle(contract, currentVehicle)[0] || null;
        }

        function mainDriverForVehicle(contract = selectedContract(), vehicle = value('#attendanceVehicle')) {
            const assignment = assignmentsForVehicle(contract, vehicle)[0] || null;
            if (!assignment) return null;

            // The driver chosen in the Contract module for this exact vehicle is
            // authoritative. A vehicle's default driver must not override it.
            const contractDriver = {
                id: normalize(assignment.driverId),
                code: '',
                name: normalize(assignment.driverName),
                label: normalize(assignment.driverLabel || assignment.driver
                    || [assignment.driverId, assignment.driverName].filter(Boolean).join(' - ')),
                phone: '',
                status: '',
                duty: '',
            };
            const hasContractDriver = Boolean(contractDriver.id || contractDriver.name || contractDriver.label);

            if (hasContractDriver) {
                return driverByValue(contractDriver.id)
                    || driverByValue(contractDriver.label)
                    || driverByValue(contractDriver.name)
                    || contractDriver;
            }

            // Backward compatibility for older contract payloads.
            const embedded = assignment.mainDriver && typeof assignment.mainDriver === 'object'
                ? assignment.mainDriver
                : null;
            const legacyCandidate = embedded?.label
                || assignment.mainDriverLabel
                || embedded?.id
                || assignment.mainDriverId
                || embedded?.name
                || assignment.mainDriverName
                || '';

            return driverByValue(legacyCandidate) || (embedded ? {
                id: normalize(embedded.id || embedded.code),
                code: normalize(embedded.code),
                name: normalize(embedded.name),
                label: normalize(embedded.label || [embedded.id, embedded.name].filter(Boolean).join(' - ')),
                phone: normalize(embedded.phone),
                status: normalize(embedded.status),
                duty: normalize(embedded.duty),
            } : null);
        }

        function spareDriverOptions(contract = selectedContract(), vehicle = value('#attendanceVehicle')) {
            const mainDriver = mainDriverForVehicle(contract, vehicle);
            const contractDriverValues = assignmentsForVehicle(contract, vehicle)
                .map(driverLabel)
                .filter(Boolean);

            return attendanceDrivers
                .filter((driver) => !mainDriver || !driverMatches(driver, driverOptionLabel(mainDriver)))
                .map((driver) => {
                    const isContractRecommended = contractDriverValues.some((candidate) => driverMatches(driver, candidate));
                    const isSpareDuty = normalize(driver.duty).toLowerCase().includes('spare');
                    const priority = isContractRecommended ? 0 : (isSpareDuty ? 1 : 2);
                    const badges = [];
                    if (isContractRecommended) badges.push('Assigned to vehicle');
                    else if (isSpareDuty) badges.push('Spare driver');
                    if (driver.status) badges.push(driver.status);

                    return {
                        ...driver,
                        value: driverOptionLabel(driver),
                        optionLabel: badges.join(' • '),
                        priority,
                    };
                })
                .sort((a, b) => a.priority - b.priority
                    || normalize(a.name || a.label).localeCompare(normalize(b.name || b.label)));
        }

        function renderDriverModeCards({ mainAvailable = false } = {}) {
            $$('[data-driver-mode-card]').forEach((card) => {
                const mode = card.dataset.driverModeCard;
                const input = $('input[name="attendanceDriverMode"]', card);
                const disabled = mode === 'main' && !mainAvailable;
                card.classList.toggle('active', selectedDriverMode === mode);
                card.classList.toggle('disabled', disabled);
                if (input) {
                    input.checked = selectedDriverMode === mode;
                    input.disabled = disabled;
                }
            });
        }

        function setSpareDriverFieldState(enabled) {
            const field = $('#attendanceSpareDriverField');
            const input = $('#attendanceSpareDriver');
            const hint = $('#attendanceSpareDriverHint');
            field?.classList.toggle('is-disabled', !enabled);
            field?.setAttribute('aria-disabled', enabled ? 'false' : 'true');
            if (input) input.disabled = !enabled;
            if (!enabled) clearFieldError(input);
            if (hint) {
                hint.textContent = enabled
                    ? 'Active spare-duty drivers appear first, followed by all other active drivers.'
                    : 'Appears only when Assign Spare Driver is selected.';
            }
        }

        function renderDriverAssignment({ clearSpareSelection = false } = {}) {
            const contract = selectedContract();
            const vehicle = normalize(value('#attendanceVehicle'));
            const vehicleAssignment = assignmentsForVehicle(contract, vehicle)[0] || null;
            const mainDriver = vehicleAssignment ? mainDriverForVehicle(contract, vehicle) : null;
            const mainPanel = $('#attendanceMainDriverPanel');
            const mainName = $('#attendanceMainDriverName');
            const mainMeta = $('#attendanceMainDriverMeta');
            const spareInput = $('#attendanceSpareDriver');

            if (clearSpareSelection) setValue('#attendanceSpareDriver', '');

            if (!contract || !vehicle || !vehicleAssignment) {
                selectedDriverMode = 'main';
                setValue('#attendanceDriver', '');
                mainPanel?.classList.remove('unavailable');
                if (mainName) mainName.textContent = vehicle ? 'Select a saved vehicle' : 'Select a vehicle first';
                if (mainMeta) mainMeta.textContent = vehicle
                    ? 'Choose a vehicle from the filtered contract suggestions.'
                    : "The driver assigned in the selected contract will appear here.";
                setSpareDriverFieldState(false);
                renderDriverModeCards({ mainAvailable: false });
                fillDatalist('attendanceSpareDriverList', []);
                return;
            }

            if (!mainDriver && selectedDriverMode === 'main') {
                selectedDriverMode = 'spare';
            }

            renderDriverModeCards({ mainAvailable: Boolean(mainDriver) });

            if (mainDriver) {
                mainPanel?.classList.remove('unavailable');
                if (mainName) mainName.textContent = mainDriver.name || driverOptionLabel(mainDriver);
                if (mainMeta) mainMeta.textContent = "This driver is assigned to the selected vehicle in the selected contract.";
            } else {
                mainPanel?.classList.add('unavailable');
                if (mainName) mainName.textContent = 'No main driver assigned';
                if (mainMeta) mainMeta.textContent = 'Choose Assign Spare Driver to continue.';
            }

            const spareDrivers = spareDriverOptions(contract, vehicle);
            fillDatalist('attendanceSpareDriverList', spareDrivers);

            if (selectedDriverMode === 'main' && mainDriver) {
                setSpareDriverFieldState(false);
                setValue('#attendanceDriver', driverOptionLabel(mainDriver));
            } else {
                setSpareDriverFieldState(true);
                const selectedSpare = driverByValue(spareInput?.value || '');
                if (spareInput?.value && !selectedSpare) {
                    setValue('#attendanceSpareDriver', '');
                }
                setValue('#attendanceDriver', driverOptionLabel(selectedSpare || {}));
            }
        }

        function setDriverMode(mode, { clearSpareSelection = false } = {}) {
            if (!['main', 'spare'].includes(mode)) return;
            if (mode === 'main' && !mainDriverForVehicle()) return;
            selectedDriverMode = mode;
            clearFieldError($('#attendanceDriverAssignmentField'));
            renderDriverAssignment({ clearSpareSelection });
        }

        function populateBase() {
            fillDatalist('attendanceContractList', contractLabels);
            fillDatalist('attendanceYardList', attendanceYards.map((yard) => ({
                value: yardOptionLabel(yard),
                optionLabel: [yard.location, yard.status].filter(Boolean).join(' • '),
            })));
            fillDatalist('attendanceStatusFilterList', options.attendance_statuses || ['Initiated', 'Running', 'Completed']);
            fillDatalist('attendanceFilterContractList', contractLabels);
        }

        function populateByContract({ clearSelection = false } = {}) {
            if (clearSelection) {
                setValue('#attendanceVehicle', '');
                setValue('#attendanceDriver', '');
                setValue('#attendanceSpareDriver', '');
            }

            const found = selectedContract();
            const vehicles = vehiclesFor(found);
            fillDatalist('attendanceVehicleList', vehicles);

            if (value('#attendanceVehicle') && !vehicles.includes(value('#attendanceVehicle'))) {
                setValue('#attendanceVehicle', '');
            }

            renderDriverAssignment({ clearSpareSelection: clearSelection });
        }

        function onContractChange() {
            selectedDriverMode = 'main';
            populateByContract({ clearSelection: true });
        }

        function onVehicleChange() {
            selectedDriverMode = 'main';
            renderDriverAssignment({ clearSpareSelection: true });
        }

        function renderStatusChoices() {
            const statuses = options.attendance_statuses || ['Initiated', 'Running', 'Completed'];
            const wrap = $('#attendanceStatusChoices');
            if (!wrap) return;
            wrap.innerHTML = statuses.map((status) => `<button type="button" class="choice-btn ${selectedStatus === status ? 'active' : ''}" data-attendance-status="${escapeHtml(status)}">${escapeHtml(status)}</button>`).join('');
        }

        function setNow(fieldId) {
            setTimeValue('#' + fieldId, new Date());
            clearFieldError(document.getElementById(fieldId));
            if (fieldId === 'attendanceEndTime') {
                selectedStatus = 'Completed';
                clearFieldError($('#attendanceStatusChoices'));
                renderStatusChoices();
            }
            updateSummary();
        }

        function calcHours(start, end) {
            start = normalizeTimeValue(start);
            end = normalizeTimeValue(end);
            if (!start || !end) return '0h 0m';
            const [sh, sm] = start.split(':').map(Number);
            const [eh, em] = end.split(':').map(Number);
            let minutes = (eh * 60 + em) - (sh * 60 + sm);
            if (minutes < 0) minutes += 24 * 60;
            return `${Math.floor(minutes / 60)}h ${minutes % 60}m`;
        }

        function updateSummary() {
            const hours = $('#attendanceSummaryHours');
            if (hours) hours.textContent = calcHours(value('#attendanceStartTime'), value('#attendanceEndTime'));
        }

        function resetForm() {
            clearValidation();
            $$('#attendanceAddPage input:not([type="radio"]),#attendanceAddPage textarea').forEach((el) => { el.value = ''; });
            setValue('#attendanceId', genId());
            setValue('#attendanceDate', new Date().toISOString().slice(0, 10));
            selectedStatus = 'Completed';
            selectedDriverMode = 'main';
            populateBase();
            populateByContract({ clearSelection: true });
            renderStatusChoices();
            updateSummary();
        }

        function collect(statusOverride) {
            updateSummary();
            const contract = selectedContract();
            const assignment = selectedVehicleAssignment(contract);
            const selectedDriver = selectedDriverMode === 'main'
                ? mainDriverForVehicle(contract, value('#attendanceVehicle'))
                : driverByValue(value('#attendanceSpareDriver'));
            const selectedYard = yardByValue(value('#attendanceYard'));
            const startTime = normalizeTimeValue(value('#attendanceStartTime'));
            const endTime = normalizeTimeValue(value('#attendanceEndTime'));
            return {
                logId: value('#attendanceId') || genId(),
                date: value('#attendanceDate'),
                contract: value('#attendanceContract'),
                contractId: contract?.id || contract?.contractId || '',
                contractParty: contract?.partyName || contract?.name || '',
                vehicle: value('#attendanceVehicle'),
                vehicleId: assignment?.vehicleId || '',
                yard: selectedYard ? yardOptionLabel(selectedYard) : value('#attendanceYard'),
                yardId: selectedYard?.id || selectedYard?.code || '',
                driverAssignmentType: selectedDriverMode,
                driver: driverOptionLabel(selectedDriver || {}) || value('#attendanceDriver'),
                driverId: selectedDriver?.id || '',
                startTime,
                endTime,
                status: statusOverride || selectedStatus,
                hours: calcHours(startTime, endTime),
                notes: value('#attendanceNotes').trim(),
                savedAt: new Date().toISOString(),
            };
        }

        function fieldContainer(element) {
            return element?.closest('.field') || element;
        }

        function clearFieldError(element) {
            const field = fieldContainer(element);
            if (!field) return;
            field.classList.remove('field-invalid');
            field.querySelectorAll('.field-error').forEach((error) => error.remove());
            element?.removeAttribute('aria-invalid');
        }

        function clearValidation() {
            const page = $('#attendanceAddPage');
            $$('.field-invalid', page).forEach((field) => field.classList.remove('field-invalid'));
            $$('.field-error', page).forEach((error) => error.remove());
            $$('[aria-invalid="true"]', page).forEach((element) => element.removeAttribute('aria-invalid'));
        }

        function markInvalid(element, message) {
            if (!element) return;
            const field = fieldContainer(element);
            field?.classList.add('field-invalid');
            element.setAttribute('aria-invalid', 'true');
            if (field && !field.querySelector('.field-error')) {
                const error = document.createElement('div');
                error.className = 'field-error';
                error.textContent = message;
                field.appendChild(error);
            }
        }

        function validate(row) {
            clearValidation();
            const errors = [];
            const required = [
                ['#attendanceId', 'Attendance ID is required.'],
                ['#attendanceDate', 'Date is required.'],
                ['#attendanceContract', 'Contract is required.'],
                ['#attendanceVehicle', 'Vehicle is required.'],
            ];

            required.forEach(([selector, message]) => {
                const element = $(selector);
                if (!String(element?.value || '').trim()) {
                    markInvalid(element, message);
                    errors.push(element);
                }
            });

            if (['Running', 'Completed'].includes(row.status) && !row.startTime) {
                const startTime = $('#attendanceStartTime');
                markInvalid(startTime, 'Start time is required for a running or completed trip.');
                errors.push(startTime);
            }

            const statusChoices = $('#attendanceStatusChoices');
            if (!row.status) {
                markInvalid(statusChoices, 'Status is required.');
                errors.push(statusChoices);
            }

            const contractInput = $('#attendanceContract');
            const vehicleInput = $('#attendanceVehicle');
            const yardInput = $('#attendanceYard');
            const driverAssignmentField = $('#attendanceDriverAssignmentField');
            const spareDriverInput = $('#attendanceSpareDriver');
            const dateInput = $('#attendanceDate');
            const startTimeInput = $('#attendanceStartTime');
            const endTimeInput = $('#attendanceEndTime');
            const found = selectedContract();

            if (row.date && !/^\d{4}-\d{2}-\d{2}$/.test(row.date)) {
                markInvalid(dateInput, 'Enter a valid date.');
                errors.push(dateInput);
            }

            if (row.startTime && !/^([01]\d|2[0-3]):[0-5]\d$/.test(row.startTime)) {
                markInvalid(startTimeInput, 'Enter a valid start time.');
                errors.push(startTimeInput);
            }

            if (row.endTime && !/^([01]\d|2[0-3]):[0-5]\d$/.test(row.endTime)) {
                markInvalid(endTimeInput, 'Enter a valid end time.');
                errors.push(endTimeInput);
            }

            if (row.contract && !found) {
                markInvalid(contractInput, 'Select a contract from the saved contract suggestions.');
                errors.push(contractInput);
            }

            if (row.yard && !yardByValue(row.yard) && !yardByValue(row.yardId)) {
                markInvalid(yardInput, 'Select a yard from the saved yard suggestions, or leave the field blank.');
                errors.push(yardInput);
            }

            if (found && row.vehicle) {
                const allowedVehicles = vehiclesFor(found);
                if (!allowedVehicles.includes(row.vehicle)) {
                    markInvalid(vehicleInput, 'Select a vehicle assigned to the selected contract.');
                    errors.push(vehicleInput);
                }
            }

            if (!row.driverAssignmentType || !['main', 'spare'].includes(row.driverAssignmentType)) {
                markInvalid(driverAssignmentField, 'Choose Assign Main Driver or Assign Spare Driver.');
                errors.push(driverAssignmentField);
            } else if (found && row.vehicle && row.driverAssignmentType === 'main') {
                const assignedMainDriver = mainDriverForVehicle(found, row.vehicle);
                if (!assignedMainDriver) {
                    markInvalid(driverAssignmentField, 'This contract has no driver assigned to the selected vehicle. Choose Assign Spare Driver.');
                    errors.push(driverAssignmentField);
                } else if (!driverMatches(assignedMainDriver, row.driver) && !driverMatches(assignedMainDriver, row.driverId)) {
                    markInvalid(driverAssignmentField, "The selected driver does not match the driver assigned in the selected contract.");
                    errors.push(driverAssignmentField);
                }
            } else if (found && row.vehicle && row.driverAssignmentType === 'spare') {
                const selectedSpareDriver = driverByValue(row.driver) || driverByValue(row.driverId);
                const allowedSpareDrivers = spareDriverOptions(found, row.vehicle);
                const isAllowed = selectedSpareDriver && allowedSpareDrivers.some((driver) => driverMatches(driver, selectedSpareDriver.id) || driverMatches(driver, selectedSpareDriver.label));
                if (!row.driver || !selectedSpareDriver || !isAllowed) {
                    markInvalid(spareDriverInput, 'Select a valid active spare or other driver from the searchable driver list.');
                    errors.push(spareDriverInput);
                }
            }

            const statuses = options.attendance_statuses || ['Initiated', 'Running', 'Completed'];
            if (row.status && !statuses.includes(row.status)) {
                markInvalid(statusChoices, 'Select a valid attendance status.');
                errors.push(statusChoices);
            }

            if (errors.length) {
                const first = errors.find(Boolean);
                first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => first?.focus(), 250);
                toast('Please correct the highlighted required fields.');
                return false;
            }

            return true;
        }

        function rowsWithUpsertedLog(row) {
            const nextRows = logs.map((item) => ({ ...item }));
            const idx = nextRows.findIndex((item) => item.logId === row.logId);
            if (idx >= 0) nextRows[idx] = row;
            else nextRows.unshift(row);
            return nextRows;
        }

        async function saveLog(statusOverride) {
            if (savingLog) return;

            const isDraft = statusOverride === 'Draft';
            const saveButton = $('#saveAttendanceBtn');
            const draftButton = $('#saveAttendanceDraftBtn');
            const activeButton = isDraft ? draftButton : saveButton;

            return window.FleetmanRunTransaction(activeButton, async () => {
                const row = collect(isDraft ? 'Draft' : statusOverride);
                if (isDraft) row.status = 'Draft';
                if (!isDraft && !validate(row)) return;

                const nextRows = rowsWithUpsertedLog(row);
                savingLog = true;

                try {
                    const result = await saveStore(row);
                    if (result?.syncFailed || result?.ok === false) return;

                    logs = Array.isArray(result?.rows) ? result.rows : nextRows;
                    if (window.FleetmanListAccess.canView()) {
                        renderList();
                        setVisible('attendanceListPage');
                        toast(isDraft ? 'Draft saved.' : 'Attendance saved successfully.');
                    } else {
                        logs = [];
                        resetForm();
                        setVisible('attendanceAddPage');
                        toast(window.FleetmanListAccess.savedMessage('Attendance', isDraft));
                    }
                } finally {
                    savingLog = false;
                }
            }, { loadingText: isDraft ? 'Saving Draft...' : 'Saving...' });
        }

        function loadSample() {
            resetForm();
            if (!(masters.contracts || []).length) {
                toast('No saved contract assignment found. Please create a contract first.');
                return;
            }

            setTimeValue('#attendanceStartTime', '09:00');
            setTimeValue('#attendanceEndTime', '17:00');
            selectedStatus = 'Completed';
            renderStatusChoices();
            updateSummary();
            toast('Select the contract and vehicle, then use the main driver or choose a spare driver.');
        }

        function badgeClass(status) {
            if (status === 'Completed') return 'ok';
            if (status === 'Running') return 'warn';
            if (status === 'Initiated' || status === 'Draft') return 'soft';
            return 'danger';
        }

        function rowHtml(row) {
            const cls = badgeClass(row.status);
            return `<tr>
                <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                <td><div class="list-cell"><div class="list-icon">📝</div><div><b>${escapeHtml(row.logId)}</b><br><small>${escapeHtml(row.date)}</small></div></div></td>
                <td>${escapeHtml(row.date || '-')}<br><small>${escapeHtml(row.startTime || '-')} to ${escapeHtml(row.endTime || '-')}</small></td>
                <td><b>${escapeHtml(row.contract || '-')}</b><br><small>${escapeHtml(row.vehicle || '-')}</small>${row.yard ? `<br><small>Yard: ${escapeHtml(row.yard)}</small>` : ''}</td>
                <td><b>${escapeHtml(row.driver || '-')}</b></td>
                <td>${escapeHtml(row.hours || '0h 0m')}</td>
                <td><span class="badge ${cls}">${escapeHtml(row.status || '-')}</span></td>
                <td>${row.status === 'Initiated' ? `<button type="button" class="mini-btn edit-start-attendance" data-id="${escapeHtml(row.logId)}">Start</button>` : ''}${row.status === 'Running' ? `<button type="button" class="mini-btn edit-end-attendance" data-id="${escapeHtml(row.logId)}">End Trip</button>` : ''}<button type="button" class="mini-btn view-attendance" data-id="${escapeHtml(row.logId)}">View</button><button type="button" class="mini-btn edit-attendance" data-id="${escapeHtml(row.logId)}">Edit</button><button type="button" class="mini-btn danger delete-attendance" data-id="${escapeHtml(row.logId)}">Delete</button></td>
            </tr>`;
        }

        function hoursToMinutes(text) {
            const match = String(text || '0h 0m').match(/(\d+)h\s+(\d+)m/);
            return match ? Number(match[1]) * 60 + Number(match[2]) : 0;
        }

        function renderList() {
            const q = value('#attendanceSearch').toLowerCase();
            const status = value('#attendanceFilterStatus');
            const contract = value('#attendanceFilterContract');
            const rows = logs.filter((row) => {
                const haystack = [row.logId, row.contract, row.contractId, row.contractParty, row.vehicle, row.yard, row.yardId, row.driver].join(' ').toLowerCase();
                return (!q || haystack.includes(q)) && (!status || row.status === status) && (!contract || row.contract === contract);
            });
            const tbody = $('#attendanceTbody');
            if (tbody) tbody.innerHTML = rows.length ? rows.map(rowHtml).join('') : '<tr><td colspan="8" class="empty">No attendance found. Click “Add Attendance” to create one.</td></tr>';
            if ($('#attendanceKpiTotal')) $('#attendanceKpiTotal').textContent = logs.length;
            if ($('#attendanceKpiCompleted')) $('#attendanceKpiCompleted').textContent = logs.filter((row) => row.status === 'Completed').length;
            if ($('#attendanceKpiRunning')) $('#attendanceKpiRunning').textContent = logs.filter((row) => row.status === 'Running').length;
            if ($('#attendanceKpiHours')) $('#attendanceKpiHours').textContent = (logs.reduce((sum, row) => sum + hoursToMinutes(row.hours), 0) / 60).toFixed(1);
        }

        async function transitionLog(id, action, triggerButton = null) {
            if (transitioningLogs.has(id)) return;

            const index = logs.findIndex((item) => item.logId === id);
            if (index < 0) return;

            const current = logs[index];
            if (action === 'start' && current.status !== 'Initiated') {
                toast('Only an initiated log can be started.');
                renderList();
                return;
            }
            if (action === 'end' && current.status !== 'Running') {
                toast('Only a running trip can be ended.');
                renderList();
                return;
            }
            if (action === 'end' && !normalizeTimeValue(current.startTime)) {
                toast('Start time is missing. Edit the log and add a valid start time first.');
                return;
            }

            return window.FleetmanRunTransaction(triggerButton, async () => {
                transitioningLogs.add(id);
                try {
                    const previousLogs = JSON.parse(JSON.stringify(logs));
                    const now = normalizeTimeValue(new Date());
                    const updated = { ...current, savedAt: new Date().toISOString() };

                    if (action === 'start') {
                        updated.startTime = now;
                        updated.endTime = '';
                        updated.status = 'Running';
                        updated.hours = '0h 0m';
                        updated.startedAt = new Date().toISOString();
                    } else {
                        updated.endTime = now;
                        updated.status = 'Completed';
                        updated.hours = calcHours(updated.startTime, updated.endTime);
                        updated.endedAt = new Date().toISOString();
                    }

                    logs[index] = updated;

                    const result = await saveStore(updated);
                    if (result?.syncFailed || result?.ok === false) {
                        logs = previousLogs;
                        renderList();
                        return;
                    }

                    if (Array.isArray(result?.rows)) logs = result.rows;
                    renderList();
                    toast(action === 'start' ? `Trip started at ${now}.` : `Trip completed at ${now}.`);
                } finally {
                    transitioningLogs.delete(id);
                }
            }, { loadingText: action === 'start' ? 'Starting...' : 'Ending...' });
        }

        function editLog(id) {
            const row = logs.find((item) => item.logId === id);
            if (!row) return;
            resetForm();
            setValue('#attendanceId', row.logId);
            setValue('#attendanceDate', row.date);
            setValue('#attendanceContract', row.contract);
            populateByContract();
            setValue('#attendanceVehicle', row.vehicle);
            setValue('#attendanceYard', row.yard || row.yardId || '');
            renderDriverAssignment({ clearSpareSelection: true });
            const assignedMainDriver = mainDriverForVehicle();
            const inferredMode = row.driverAssignmentType
                || (assignedMainDriver && (driverMatches(assignedMainDriver, row.driver) || driverMatches(assignedMainDriver, row.driverId)) ? 'main' : 'spare');
            selectedDriverMode = inferredMode === 'main' && assignedMainDriver ? 'main' : 'spare';
            if (selectedDriverMode === 'spare') {
                setValue('#attendanceSpareDriver', row.driver || row.driverId || '');
            }
            renderDriverAssignment();
            setTimeValue('#attendanceStartTime', row.startTime);
            setTimeValue('#attendanceEndTime', row.endTime);
            selectedStatus = row.status === 'Draft' ? 'Initiated' : row.status;
            renderStatusChoices();
            setValue('#attendanceNotes', row.notes);
            updateSummary();
            setVisible('attendanceAddPage');
        }

        function viewLog(id) {
            const row = logs.find((item) => item.logId === id);
            if (row) window.FleetmanDetailViewer?.show('Driver Attendance Details', row);
        }

        async function deleteLog(id, triggerButton = null) {
            if (!confirm('Delete this attendance record?')) return;

            return window.FleetmanRunTransaction(triggerButton, async () => {
                const nextRows = logs.filter((row) => row.logId !== id);
                const endpoint = String(attendanceResources.destroy_template || '')
                    .replace('__CODE__', encodeURIComponent(id));
                const result = endpoint
                    ? await attendanceRequest(endpoint, { method: 'DELETE' }, 'The attendance record could not be deleted.')
                    : await syncResource('driver_attendance', nextRows);

                if (result?.syncFailed || result?.ok === false) return;

                logs = Array.isArray(result?.rows) ? result.rows : nextRows;
                renderList();
                toast(result?.message || 'Attendance deleted.');
            }, { loadingText: 'Deleting...' });
        }

        function exportLogs() {
            const rows = [['Attendance ID', 'Date', 'Contract', 'Contract ID', 'Vehicle', 'Vehicle ID', 'Yard', 'Yard ID', 'Driver Assignment', 'Driver', 'Driver ID', 'Start Time', 'End Time', 'Status', 'Hours', 'Notes']];
            logs.forEach((row) => rows.push([row.logId, row.date, row.contract, row.contractId, row.vehicle, row.vehicleId, row.yard || '', row.yardId || '', row.driverAssignmentType || '', row.driver, row.driverId, row.startTime, row.endTime, row.status, row.hours, row.notes]));
            exportCsv(rows, 'fleetman-driver-attendance-list.csv');
        }

        $('#attendanceContract')?.addEventListener('change', onContractChange);
        $('#attendanceContract')?.addEventListener('input', onContractChange);
        $('#attendanceVehicle')?.addEventListener('change', onVehicleChange);
        $('#attendanceVehicle')?.addEventListener('input', onVehicleChange);
        $('#attendanceYard')?.addEventListener('change', () => {
            const selectedYard = yardByValue(value('#attendanceYard'));
            if (selectedYard) setValue('#attendanceYard', yardOptionLabel(selectedYard));
            clearFieldError($('#attendanceYard'));
        });
        $$('input[name="attendanceDriverMode"]').forEach((input) => input.addEventListener('change', () => {
            if (input.checked) setDriverMode(input.value);
        }));
        $('#attendanceSpareDriver')?.addEventListener('input', () => {
            const selectedDriver = driverByValue(value('#attendanceSpareDriver'));
            setValue('#attendanceDriver', driverOptionLabel(selectedDriver || {}));
            clearFieldError($('#attendanceSpareDriver'));
            clearFieldError($('#attendanceDriverAssignmentField'));
        });
        $('#attendanceSpareDriver')?.addEventListener('change', () => {
            const selectedDriver = driverByValue(value('#attendanceSpareDriver'));
            setValue('#attendanceDriver', driverOptionLabel(selectedDriver || {}));
        });
        ['#attendanceStartTime', '#attendanceEndTime'].forEach((selector) => $(selector)?.addEventListener('input', updateSummary));
        $('#resetAttendanceBtn')?.addEventListener('click', resetForm);
        $('#saveAttendanceBtn')?.addEventListener('click', () => saveLog());
        $('#saveAttendanceDraftBtn')?.addEventListener('click', () => saveLog('Draft'));
        $('#loadAttendanceSampleBtn')?.addEventListener('click', loadSample);
        $('#exportAttendanceBtn')?.addEventListener('click', exportLogs);
        $('#applyAttendanceFiltersBtn')?.addEventListener('click', renderList);
        $('#clearAttendanceFiltersBtn')?.addEventListener('click', () => {
            ['#attendanceSearch', '#attendanceFilterStatus', '#attendanceFilterContract'].forEach((selector) => setValue(selector, ''));
            renderList();
        });
        ['#attendanceSearch', '#attendanceFilterStatus', '#attendanceFilterContract'].forEach((selector) => $(selector)?.addEventListener('input', renderList));

        $('#attendanceAddPage')?.addEventListener('input', (event) => {
            clearFieldError(event.target);
        });
        $('#attendanceAddPage')?.addEventListener('change', (event) => {
            clearFieldError(event.target);
        });

        document.addEventListener('click', (event) => {
            const status = event.target.closest('[data-attendance-status]');
            if (status) {
                selectedStatus = status.dataset.attendanceStatus;
                clearFieldError($('#attendanceStatusChoices'));
                renderStatusChoices();
                updateSummary();
            }
            const now = event.target.closest('[data-time-now]');
            if (now) setNow(now.dataset.timeNow);
            const clear = event.target.closest('[data-clear-field]');
            if (clear) {
                setValue('#' + clear.dataset.clearField, '');
                clearFieldError(document.getElementById(clear.dataset.clearField));
                updateSummary();
            }
            const startTrip = event.target.closest('.edit-start-attendance');
            if (startTrip) transitionLog(startTrip.dataset.id, 'start', startTrip);
            const endTrip = event.target.closest('.edit-end-attendance');
            if (endTrip) transitionLog(endTrip.dataset.id, 'end', endTrip);
            const view = event.target.closest('.view-attendance');
            if (view) viewLog(view.dataset.id);
            const edit = event.target.closest('.edit-attendance');
            if (edit) editLog(edit.dataset.id);
            const del = event.target.closest('.delete-attendance');
            if (del) deleteLog(del.dataset.id, del);
        });

        populateBase();
        resetForm();
        renderList();
        window.FleetmanRecordApi?.registerInfinite('driver_attendance', () => logs, (rows) => { logs = rows; }, renderList);
        if (window.location.search.includes('action=add')) {
            setVisible('attendanceAddPage');
        } else {
            setVisible('attendanceListPage');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindPageTargets();
        const page = document.body.dataset.page;
        if (page === 'vendors') initVendors();
        if (page === 'trips') initTrips();
        if (page === 'drivers') initDrivers();
        if (page === 'clients') initClients();
        if (page === 'employees') initEmployees();
        if (page === 'driver-attendance') initDriverAttendance();
    });
})();
