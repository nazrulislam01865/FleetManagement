/* Master Data page logic: dynamic database-backed lookup rows for app-wide dropdowns. */
(() => {
    'use strict';

    const data = window.FLEETMAN || {};
    const resources = data.resources || {};
    const masterData = data.masterData || { vehicle_categories: [], vehicle_sub_categories: [], party_types: [], document_names: [], licence_types: [], driver_contact_types: [], client_types: [], contact_methods: [], fuel_types: [], fuel_units: [], payment_types: [] };
    const $ = (selector, root = document) => root.querySelector(selector);
    const value = (selector) => $(selector)?.value || '';
    const setValue = (selector, nextValue) => { const element = $(selector); if (element) element.value = nextValue ?? ''; };
    const setText = (selector, nextValue) => { const element = $(selector); if (element) element.textContent = nextValue ?? ''; };
    const escapeHtml = (input) => String(input ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function toast(message) {
        const node = $('#toast');
        if (!node) return;
        node.textContent = message;
        node.classList.add('show');
        setTimeout(() => node.classList.remove('show'), 2800);
    }

    function codeFrom(input) {
        const code = String(input || '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');

        return code || 'MASTER_' + Math.random().toString(36).slice(2, 8).toUpperCase();
    }

    function sortRows(rows) {
        return rows.slice().sort((a, b) => {
            const sortA = Number(a.sortOrder || 0);
            const sortB = Number(b.sortOrder || 0);
            if (sortA !== sortB) return sortA - sortB;
            return String(a.name || '').localeCompare(String(b.name || ''));
        });
    }

    function initMasterData() {

        let vehicleCategories = Array.isArray(masterData.vehicle_categories) ? masterData.vehicle_categories.slice() : [];
        let vehicleSubCategories = Array.isArray(masterData.vehicle_sub_categories) ? masterData.vehicle_sub_categories.slice() : [];
        let partyTypes = Array.isArray(masterData.party_types) ? masterData.party_types.slice() : [];
        let documentNames = Array.isArray(masterData.document_names) ? masterData.document_names.slice() : [];
        let licenceTypes = Array.isArray(masterData.licence_types) ? masterData.licence_types.slice() : [];
        let driverContactTypes = Array.isArray(masterData.driver_contact_types) ? masterData.driver_contact_types.slice() : [];
        let clientTypes = Array.isArray(masterData.client_types) ? masterData.client_types.slice() : [];
        let contactMethods = Array.isArray(masterData.contact_methods) ? masterData.contact_methods.slice() : [];
        let fuelTypes = Array.isArray(masterData.fuel_types) ? masterData.fuel_types.slice() : [];
        let fuelUnits = Array.isArray(masterData.fuel_units) ? masterData.fuel_units.slice() : [];
        let paymentTypes = Array.isArray(masterData.payment_types) ? masterData.payment_types.slice() : [];
        const documentTypeOptions = ['All Modules', 'Vehicles', 'Drivers', 'Vendors', 'Vendors & Parties', 'Employees', 'Clients', 'Contracts'];

        function normalizeDocumentTypes(documentTypes, legacyType = '') {
            let types = documentTypes;

            if (typeof types === 'string') {
                try {
                    const decoded = JSON.parse(types);
                    types = Array.isArray(decoded) ? decoded : [types];
                } catch (error) {
                    types = [types];
                }
            }

            if (!Array.isArray(types) || !types.length) {
                types = [legacyType || 'All Modules'];
            }

            const normalized = [...new Set(types
                .map((type) => String(type || '').trim())
                .filter((type) => documentTypeOptions.includes(type)))];

            if (normalized.includes('All Modules')) return ['All Modules'];
            return normalized.length ? normalized : ['All Modules'];
        }

        function selectedDocumentTypes() {
            return normalizeDocumentTypes(
                Array.from(document.querySelectorAll('input[name="documentNameMasterTypes[]"]:checked'))
                    .map((input) => input.value)
            );
        }

        function setDocumentTypeValidationError(show) {
            const field = $('#documentNameMasterTypesField');
            const error = $('#documentNameMasterTypesError');
            field?.classList.toggle('is-invalid', Boolean(show));
            if (error) error.hidden = !show;
        }

        function setDocumentTypeCheckboxes(documentTypes, legacyType = '') {
            const selected = normalizeDocumentTypes(documentTypes, legacyType);
            document.querySelectorAll('input[name="documentNameMasterTypes[]"]').forEach((input) => {
                input.checked = selected.includes(input.value);
            });
            setDocumentTypeValidationError(false);
        }

        documentNames = documentNames.map((row) => {
            const documentTypes = normalizeDocumentTypes(row.documentTypes, row.documentType);
            return {
                ...row,
                documentTypes,
                documentType: documentTypes.includes('All Modules') ? 'All Modules' : documentTypes[0],
            };
        });

        function populateVehicleCategorySelect(selectedValue = '') {
            const select = $('#vehicleSubCategoryParent');
            if (!select) return;

            const currentValue = selectedValue || select.value;
            select.innerHTML = '<option value="">Select vehicle category</option>';
            sortRows(vehicleCategories)
                .filter((row) => row.status !== 'Inactive')
                .forEach((row) => {
                    const option = document.createElement('option');
                    option.value = row.code;
                    option.textContent = row.name;
                    select.appendChild(option);
                });

            if (currentValue && Array.from(select.options).some((option) => option.value === currentValue)) {
                select.value = currentValue;
            }
        }

        function vehicleCategoryName(code) {
            return vehicleCategories.find((row) => row.code === code)?.name || code || '—';
        }

        function codeForVehicleSubCategory() {
            const categoryCode = value('#vehicleSubCategoryParent');
            const name = value('#vehicleSubCategoryMasterName');
            return codeFrom([categoryCode, name].filter(Boolean).join('_'));
        }

        function saveStore() {
            const endpoint = resources?.master_data?.sync;
            if (!endpoint) {
                toast('Master Data route is missing. Please check routes/web.php.');
                return Promise.resolve({ ok: false });
            }

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ vehicle_categories: vehicleCategories, vehicle_sub_categories: vehicleSubCategories, party_types: partyTypes, document_names: documentNames, licence_types: licenceTypes, driver_contact_types: driverContactTypes, client_types: clientTypes, contact_methods: contactMethods, fuel_types: fuelTypes, fuel_units: fuelUnits }),
            })
                .then(async (response) => {
                    if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || 'Master data sync failed.');
                    return response.json();
                })
                .then((payload) => {
                    if (payload.masterData) {
                        vehicleCategories = Array.isArray(payload.masterData.vehicle_categories) ? payload.masterData.vehicle_categories : vehicleCategories;
                        vehicleSubCategories = Array.isArray(payload.masterData.vehicle_sub_categories) ? payload.masterData.vehicle_sub_categories : vehicleSubCategories;
                        partyTypes = Array.isArray(payload.masterData.party_types) ? payload.masterData.party_types : partyTypes;
                        documentNames = Array.isArray(payload.masterData.document_names) ? payload.masterData.document_names : documentNames;
                        licenceTypes = Array.isArray(payload.masterData.licence_types) ? payload.masterData.licence_types : licenceTypes;
                        driverContactTypes = Array.isArray(payload.masterData.driver_contact_types) ? payload.masterData.driver_contact_types : driverContactTypes;
                        clientTypes = Array.isArray(payload.masterData.client_types) ? payload.masterData.client_types : clientTypes;
                        contactMethods = Array.isArray(payload.masterData.contact_methods) ? payload.masterData.contact_methods : contactMethods;
                        fuelTypes = Array.isArray(payload.masterData.fuel_types) ? payload.masterData.fuel_types : fuelTypes;
                        fuelUnits = Array.isArray(payload.masterData.fuel_units) ? payload.masterData.fuel_units : fuelUnits;
                        renderAll();
                    }
                    return { ok: true, payload };
                })
                .catch((error) => {
                    toast(error.message || 'Could not save master data to database.');
                    return { ok: false, error };
                });
        }

        async function saveDocumentName(row, editingCode = '') {
            const endpoint = resources?.document_names?.save;
            if (!endpoint) {
                throw new Error('Document Type save route is missing. Please check routes/web.php.');
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ document: row, editingCode }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const validationMessage = payload?.errors
                    ? Object.values(payload.errors).flat().filter(Boolean)[0]
                    : '';
                throw new Error(validationMessage || payload.message || 'Document type could not be saved.');
            }

            if (!Array.isArray(payload.documentNames)) {
                throw new Error('Document type was saved, but the updated table could not be loaded.');
            }

            documentNames = payload.documentNames.map((savedRow) => {
                const savedTypes = normalizeDocumentTypes(savedRow.documentTypes, savedRow.documentType);
                return {
                    ...savedRow,
                    documentTypes: savedTypes,
                    documentType: savedTypes.includes('All Modules') ? 'All Modules' : savedTypes[0],
                };
            });
            renderDocumentNames();
        }

        async function deleteDocumentName(documentId) {
            const endpointTemplate = resources?.document_names?.destroy;
            const normalizedId = Number(documentId);

            if (!endpointTemplate) {
                throw new Error('Document Type delete route is missing. Please check routes/web.php.');
            }

            if (!Number.isInteger(normalizedId) || normalizedId <= 0) {
                throw new Error('This Document Type does not have a valid database ID. Refresh the page and try again.');
            }

            const endpoint = endpointTemplate.replace('__DOCUMENT_ID__', encodeURIComponent(String(normalizedId)));
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Document type could not be deleted.');
            }

            if (Number(payload.deletedId) !== normalizedId) {
                throw new Error('The server did not confirm the requested Document Type deletion.');
            }

            return normalizedId;
        }

        function resetVehicleCategoryForm() {
            setValue('#vehicleCategoryEditingCode', '');
            setValue('#vehicleCategoryMasterName', '');
            setValue('#vehicleCategoryMasterCode', '');
            setValue('#vehicleCategoryMasterSort', '0');
            setValue('#vehicleCategoryMasterStatus', 'Active');
            setValue('#vehicleCategoryMasterDescription', '');
            setText('#saveVehicleCategoryMasterBtn', 'Save Vehicle Category');
        }

        function resetVehicleSubCategoryForm() {
            setValue('#vehicleSubCategoryEditingCode', '');
            setValue('#vehicleSubCategoryParent', '');
            setValue('#vehicleSubCategoryMasterName', '');
            setValue('#vehicleSubCategoryMasterCode', '');
            setValue('#vehicleSubCategoryMasterSort', '0');
            setValue('#vehicleSubCategoryMasterStatus', 'Active');
            setValue('#vehicleSubCategoryMasterDescription', '');
            setText('#saveVehicleSubCategoryMasterBtn', 'Save Vehicle Sub Category');
        }

        function resetPartyTypeForm() {
            setValue('#partyTypeEditingCode', '');
            setValue('#partyTypeMasterName', '');
            setValue('#partyTypeMasterCode', '');
            setValue('#partyTypeMasterSort', '0');
            setValue('#partyTypeMasterStatus', 'Active');
            setValue('#partyTypeMasterDescription', '');
            setText('#savePartyTypeMasterBtn', 'Save Party Type');
        }

        function resetDocumentNameForm() {
            setValue('#documentNameEditingCode', '');
            setValue('#documentNameMasterName', '');
            setValue('#documentNameMasterCode', '');
            setDocumentTypeCheckboxes(['All Modules']);
            setValue('#documentNameMasterSort', '0');
            setValue('#documentNameMasterStatus', 'Active');
            setValue('#documentNameMasterDescription', '');
            setText('#saveDocumentNameMasterBtn', 'Save Document Type');
        }

        function resetLicenceTypeForm() {
            setValue('#licenceTypeEditingCode', '');
            setValue('#licenceTypeMasterName', '');
            setValue('#licenceTypeMasterCode', '');
            setValue('#licenceTypeMasterSort', '0');
            setValue('#licenceTypeMasterStatus', 'Active');
            setValue('#licenceTypeMasterDescription', '');
            setText('#saveLicenceTypeMasterBtn', 'Save Licence Type');
        }

        function resetFuelTypeForm() {
            setValue('#fuelTypeEditingCode', '');
            setValue('#fuelTypeMasterName', '');
            setValue('#fuelTypeMasterCode', '');
            setValue('#fuelTypeMasterSort', '0');
            setValue('#fuelTypeMasterStatus', 'Active');
            setValue('#fuelTypeMasterDescription', '');
            setText('#saveFuelTypeMasterBtn', 'Save Fuel Type');
        }

        function resetFuelUnitForm() {
            setValue('#fuelUnitEditingCode', '');
            setValue('#fuelUnitMasterName', '');
            setValue('#fuelUnitMasterCode', '');
            setValue('#fuelUnitMasterSort', '0');
            setValue('#fuelUnitMasterStatus', 'Active');
            setValue('#fuelUnitMasterDescription', '');
            setText('#saveFuelUnitMasterBtn', 'Save Fuel Unit');
        }

        function resetPaymentTypeForm() {
            setValue('#paymentTypeEditingCode', '');
            setValue('#paymentTypeMasterName', '');
            setValue('#paymentTypeMasterCode', '');
            setValue('#paymentTypeMasterSort', '0');
            setValue('#paymentTypeMasterStatus', 'Active');
            setValue('#paymentTypeMasterDescription', '');
            setText('#savePaymentTypeMasterBtn', 'Save Payment Type');
        }

        function resetClientTypeForm() {
            setValue('#clientTypeEditingCode', '');
            setValue('#clientTypeMasterName', '');
            setValue('#clientTypeMasterCode', '');
            setValue('#clientTypeMasterSort', '0');
            setValue('#clientTypeMasterStatus', 'Active');
            setValue('#clientTypeMasterDescription', '');
            setText('#saveClientTypeMasterBtn', 'Save Client Type');
        }

        function resetContactMethodForm() {
            setValue('#contactMethodEditingCode', '');
            setValue('#contactMethodMasterName', '');
            setValue('#contactMethodMasterCode', '');
            setValue('#contactMethodMasterSort', '0');
            setValue('#contactMethodMasterStatus', 'Active');
            setValue('#contactMethodMasterDescription', '');
            setText('#saveContactMethodMasterBtn', 'Save Contact Method');
        }

        function resetDriverContactTypeForm() {
            setValue('#driverContactTypeEditingCode', '');
            setValue('#driverContactTypeMasterName', '');
            setValue('#driverContactTypeMasterCode', '');
            setValue('#driverContactTypeMasterSort', '0');
            setValue('#driverContactTypeMasterStatus', 'Active');
            setValue('#driverContactTypeMasterDescription', '');
            setText('#saveDriverContactTypeMasterBtn', 'Save Contact Type');
        }

        function collectVehicleCategory() {
            const name = value('#vehicleCategoryMasterName').trim();
            if (!name) {
                toast('Vehicle Category Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#vehicleCategoryMasterCode') || name),
                name,
                sortOrder: Number(value('#vehicleCategoryMasterSort') || 0),
                status: value('#vehicleCategoryMasterStatus') || 'Active',
                description: value('#vehicleCategoryMasterDescription').trim(),
            };
        }

        function collectVehicleSubCategory() {
            const name = value('#vehicleSubCategoryMasterName').trim();
            const vehicleCategoryCode = value('#vehicleSubCategoryParent').trim();

            if (!vehicleCategoryCode) {
                toast('Vehicle Category is required.');
                return null;
            }

            if (!name) {
                toast('Vehicle Sub Category Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#vehicleSubCategoryMasterCode') || `${vehicleCategoryCode}_${name}`),
                vehicleCategoryCode,
                vehicleCategoryName: vehicleCategoryName(vehicleCategoryCode),
                name,
                sortOrder: Number(value('#vehicleSubCategoryMasterSort') || 0),
                status: value('#vehicleSubCategoryMasterStatus') || 'Active',
                description: value('#vehicleSubCategoryMasterDescription').trim(),
            };
        }

        function collectPartyType() {
            const name = value('#partyTypeMasterName').trim();
            if (!name) {
                toast('Party Type Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#partyTypeMasterCode') || name),
                name,
                sortOrder: Number(value('#partyTypeMasterSort') || 0),
                status: value('#partyTypeMasterStatus') || 'Active',
                description: value('#partyTypeMasterDescription').trim(),
            };
        }

        function collectDocumentName() {
            const name = value('#documentNameMasterName').trim();
            const checkedTypes = Array.from(document.querySelectorAll('input[name="documentNameMasterTypes[]"]:checked'))
                .map((input) => input.value);
            if (!name) {
                toast('Document Name is required.');
                return null;
            }
            if (!checkedTypes.length) {
                setDocumentTypeValidationError(true);
                toast('Select at least one document type.');
                return null;
            }

            setDocumentTypeValidationError(false);
            const documentTypes = normalizeDocumentTypes(checkedTypes);

            return {
                code: codeFrom(value('#documentNameMasterCode') || name),
                name,
                documentTypes,
                documentType: documentTypes.includes('All Modules') ? 'All Modules' : documentTypes[0],
                sortOrder: Number(value('#documentNameMasterSort') || 0),
                status: value('#documentNameMasterStatus') || 'Active',
                description: value('#documentNameMasterDescription').trim(),
            };
        }

        function collectLicenceType() {
            const name = value('#licenceTypeMasterName').trim();
            if (!name) {
                toast('Licence Type Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#licenceTypeMasterCode') || name),
                name,
                sortOrder: Number(value('#licenceTypeMasterSort') || 0),
                status: value('#licenceTypeMasterStatus') || 'Active',
                description: value('#licenceTypeMasterDescription').trim(),
            };
        }

        function collectFuelType() {
            const name = value('#fuelTypeMasterName').trim();
            if (!name) { toast('Fuel Type Name is required.'); return null; }
            return { code: codeFrom(value('#fuelTypeMasterCode') || name), name, sortOrder: Number(value('#fuelTypeMasterSort') || 0), status: value('#fuelTypeMasterStatus') || 'Active', description: value('#fuelTypeMasterDescription').trim() };
        }

        function collectFuelUnit() {
            const name = value('#fuelUnitMasterName').trim();
            if (!name) { toast('Fuel Unit Name is required.'); return null; }
            return { code: codeFrom(value('#fuelUnitMasterCode') || name), name, sortOrder: Number(value('#fuelUnitMasterSort') || 0), status: value('#fuelUnitMasterStatus') || 'Active', description: value('#fuelUnitMasterDescription').trim() };
        }

        function collectPaymentType() {
            const name = value('#paymentTypeMasterName').trim();
            if (!name) { toast('Payment Type Name is required.'); return null; }
            return { code: codeFrom(value('#paymentTypeMasterCode') || name), name, sortOrder: Number(value('#paymentTypeMasterSort') || 0), status: value('#paymentTypeMasterStatus') || 'Active', description: value('#paymentTypeMasterDescription').trim() };
        }

        function collectClientType() {
            const name = value('#clientTypeMasterName').trim();
            if (!name) {
                toast('Client Type Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#clientTypeMasterCode') || name),
                name,
                sortOrder: Number(value('#clientTypeMasterSort') || 0),
                status: value('#clientTypeMasterStatus') || 'Active',
                description: value('#clientTypeMasterDescription').trim(),
            };
        }

        function collectContactMethod() {
            const name = value('#contactMethodMasterName').trim();
            if (!name) {
                toast('Contact Method Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#contactMethodMasterCode') || name),
                name,
                sortOrder: Number(value('#contactMethodMasterSort') || 0),
                status: value('#contactMethodMasterStatus') || 'Active',
                description: value('#contactMethodMasterDescription').trim(),
            };
        }

        function collectDriverContactType() {
            const name = value('#driverContactTypeMasterName').trim();
            if (!name) {
                toast('Driver Contact Type Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#driverContactTypeMasterCode') || name),
                name,
                sortOrder: Number(value('#driverContactTypeMasterSort') || 0),
                status: value('#driverContactTypeMasterStatus') || 'Active',
                description: value('#driverContactTypeMasterDescription').trim(),
            };
        }

        function upsertRow(rows, row, editingCode) {
            const duplicate = rows.find((item) => item.code === row.code && item.code !== editingCode);
            if (duplicate) {
                toast('This code already exists. Please use a unique code.');
                return rows;
            }

            const nextRows = rows.filter((item) => item.code !== (editingCode || row.code));
            nextRows.push(row);
            return sortRows(nextRows);
        }

        function renderVehicleCategories() {
            setText('#masterVehicleCategoryCount', vehicleCategories.filter((row) => row.status !== 'Inactive').length);
            populateVehicleCategorySelect();
            const tbody = $('#vehicleCategoryMasterTbody');
            if (!tbody) return;

            const rows = sortRows(vehicleCategories);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-vehicle-category="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-vehicle-category="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No vehicle category added yet.</td></tr>';
        }

        function renderVehicleSubCategories() {
            setText('#masterVehicleSubCategoryCount', vehicleSubCategories.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#vehicleSubCategoryMasterTbody');
            if (!tbody) return;

            const rows = sortRows(vehicleSubCategories).sort((a, b) => {
                const categoryCompare = vehicleCategoryName(a.vehicleCategoryCode).localeCompare(vehicleCategoryName(b.vehicleCategoryCode));
                if (categoryCompare !== 0) return categoryCompare;
                const sortA = Number(a.sortOrder || 0);
                const sortB = Number(b.sortOrder || 0);
                if (sortA !== sortB) return sortA - sortB;
                return String(a.name || '').localeCompare(String(b.name || ''));
            });

            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td>${escapeHtml(vehicleCategoryName(row.vehicleCategoryCode))}</td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-vehicle-sub-category="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-vehicle-sub-category="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="8" class="empty">No vehicle sub category added yet.</td></tr>';
        }

        function renderPartyTypes() {
            setText('#masterPartyTypeCount', partyTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#partyTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(partyTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-party="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-party="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No party type added yet.</td></tr>';
        }

        function renderDocumentNames() {
            setText('#masterDocumentNameCount', documentNames.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#documentNameMasterTbody');
            if (!tbody) return;

            const rows = sortRows(documentNames);
            tbody.innerHTML = rows.length ? rows.map((row) => {
                const types = normalizeDocumentTypes(row.documentTypes, row.documentType);
                const badges = types.map((type) => `<span class="badge soft">${escapeHtml(type)}</span>`).join('');

                return `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><div class="master-document-type-badges">${badges}</div></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-document="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-document="${Number(row.id || 0)}">Delete</button></div></td>
                </tr>`;
            }).join('') : '<tr><td colspan="8" class="empty">No document type added yet.</td></tr>';
        }

        function renderLicenceTypes() {
            setText('#masterLicenceTypeCount', licenceTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#licenceTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(licenceTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-licence="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-licence="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No licence type added yet.</td></tr>';
        }

        function renderFuelTypes() {
            setText('#masterFuelTypeCount', fuelTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelTypeMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-type="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-type="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="7" class="empty">No fuel type added yet.</td></tr>';
        }

        function renderFuelUnits() {
            setText('#masterFuelUnitCount', fuelUnits.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelUnitMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelUnits);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-unit="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-unit="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="7" class="empty">No fuel unit added yet.</td></tr>';
        }

        function renderPaymentTypes() {
            setText('#masterPaymentTypeCount', paymentTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#paymentTypeMasterTbody');
            if (!tbody) return;
            const rows = sortRows(paymentTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-payment-type="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-payment-type="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="7" class="empty">No payment type added yet.</td></tr>';
        }

        function renderClientTypes() {
            setText('#masterClientTypeCount', clientTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#clientTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(clientTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-client="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-client="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No client type added yet.</td></tr>';
        }

        function renderContactMethods() {
            setText('#masterContactMethodCount', contactMethods.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#contactMethodMasterTbody');
            if (!tbody) return;

            const rows = sortRows(contactMethods);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-contact-method="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-contact-method="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No contact method added yet.</td></tr>';
        }

        function renderDriverContactTypes() {
            setText('#masterDriverContactTypeCount', driverContactTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#driverContactTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(driverContactTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${window.FleetmanCreatedAtCell(row.createdAt || row.created_at, row.creatorName || row.createdBy)}</td>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-driver-contact-type="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-driver-contact-type="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No driver contact type added yet.</td></tr>';
        }

        function renderAll() {
            renderVehicleCategories();
            renderVehicleSubCategories();
            renderPartyTypes();
            renderDocumentNames();
            renderLicenceTypes();
            renderClientTypes();
            renderContactMethods();
            renderDriverContactTypes();
            renderFuelTypes();
            renderFuelUnits();
            renderPaymentTypes();
        }

        function editVehicleCategory(code) {
            const row = vehicleCategories.find((item) => item.code === code);
            if (!row) return;
            setValue('#vehicleCategoryEditingCode', row.code);
            setValue('#vehicleCategoryMasterName', row.name);
            setValue('#vehicleCategoryMasterCode', row.code);
            setValue('#vehicleCategoryMasterSort', row.sortOrder || 0);
            setValue('#vehicleCategoryMasterStatus', row.status || 'Active');
            setValue('#vehicleCategoryMasterDescription', row.description || '');
            setText('#saveVehicleCategoryMasterBtn', 'Update Vehicle Category');
            $('#vehicleCategoryMasterName')?.focus();
        }

        function editVehicleSubCategory(code) {
            const row = vehicleSubCategories.find((item) => item.code === code);
            if (!row) return;
            populateVehicleCategorySelect(row.vehicleCategoryCode);
            setValue('#vehicleSubCategoryEditingCode', row.code);
            setValue('#vehicleSubCategoryParent', row.vehicleCategoryCode);
            setValue('#vehicleSubCategoryMasterName', row.name);
            setValue('#vehicleSubCategoryMasterCode', row.code);
            setValue('#vehicleSubCategoryMasterSort', row.sortOrder || 0);
            setValue('#vehicleSubCategoryMasterStatus', row.status || 'Active');
            setValue('#vehicleSubCategoryMasterDescription', row.description || '');
            setText('#saveVehicleSubCategoryMasterBtn', 'Update Vehicle Sub Category');
            $('#vehicleSubCategoryMasterName')?.focus();
        }

        function editParty(code) {
            const row = partyTypes.find((item) => item.code === code);
            if (!row) return;
            setValue('#partyTypeEditingCode', row.code);
            setValue('#partyTypeMasterName', row.name);
            setValue('#partyTypeMasterCode', row.code);
            setValue('#partyTypeMasterSort', row.sortOrder || 0);
            setValue('#partyTypeMasterStatus', row.status || 'Active');
            setValue('#partyTypeMasterDescription', row.description || '');
            setText('#savePartyTypeMasterBtn', 'Update Party Type');
            $('#partyTypeMasterName')?.focus();
        }

        function editDocument(code) {
            const row = documentNames.find((item) => item.code === code);
            if (!row) return;
            setValue('#documentNameEditingCode', row.code);
            setValue('#documentNameMasterName', row.name);
            setValue('#documentNameMasterCode', row.code);
            setDocumentTypeCheckboxes(row.documentTypes, row.documentType);
            setValue('#documentNameMasterSort', row.sortOrder || 0);
            setValue('#documentNameMasterStatus', row.status || 'Active');
            setValue('#documentNameMasterDescription', row.description || '');
            setText('#saveDocumentNameMasterBtn', 'Update Document Type');
            $('#documentNameMasterName')?.focus();
        }

        function editLicenceType(code) {
            const row = licenceTypes.find((item) => item.code === code);
            if (!row) return;
            setValue('#licenceTypeEditingCode', row.code);
            setValue('#licenceTypeMasterName', row.name);
            setValue('#licenceTypeMasterCode', row.code);
            setValue('#licenceTypeMasterSort', row.sortOrder || 0);
            setValue('#licenceTypeMasterStatus', row.status || 'Active');
            setValue('#licenceTypeMasterDescription', row.description || '');
            setText('#saveLicenceTypeMasterBtn', 'Update Licence Type');
            $('#licenceTypeMasterName')?.focus();
        }

        function editFuelType(code) {
            const row = fuelTypes.find((item) => item.code === code);
            if (!row) return;
            setValue('#fuelTypeEditingCode', row.code);
            setValue('#fuelTypeMasterName', row.name);
            setValue('#fuelTypeMasterCode', row.code);
            setValue('#fuelTypeMasterSort', row.sortOrder || 0);
            setValue('#fuelTypeMasterStatus', row.status || 'Active');
            setValue('#fuelTypeMasterDescription', row.description || '');
            setText('#saveFuelTypeMasterBtn', 'Update Fuel Type');
            $('#fuelTypeMasterName')?.focus();
        }

        function editFuelUnit(code) {
            const row = fuelUnits.find((item) => item.code === code);
            if (!row) return;
            setValue('#fuelUnitEditingCode', row.code);
            setValue('#fuelUnitMasterName', row.name);
            setValue('#fuelUnitMasterCode', row.code);
            setValue('#fuelUnitMasterSort', row.sortOrder || 0);
            setValue('#fuelUnitMasterStatus', row.status || 'Active');
            setValue('#fuelUnitMasterDescription', row.description || '');
            setText('#saveFuelUnitMasterBtn', 'Update Fuel Unit');
            $('#fuelUnitMasterName')?.focus();
        }

        function editPaymentType(code) {
            const row = paymentTypes.find((item) => item.code === code);
            if (!row) return;
            setValue('#paymentTypeEditingCode', row.code);
            setValue('#paymentTypeMasterName', row.name);
            setValue('#paymentTypeMasterCode', row.code);
            setValue('#paymentTypeMasterSort', row.sortOrder || 0);
            setValue('#paymentTypeMasterStatus', row.status || 'Active');
            setValue('#paymentTypeMasterDescription', row.description || '');
            setText('#savePaymentTypeMasterBtn', 'Update Payment Type');
            $('#paymentTypeMasterName')?.focus();
        }

        function editClientType(code) {
            const row = clientTypes.find((item) => item.code === code);
            if (!row) return;
            setValue('#clientTypeEditingCode', row.code);
            setValue('#clientTypeMasterName', row.name);
            setValue('#clientTypeMasterCode', row.code);
            setValue('#clientTypeMasterSort', row.sortOrder || 0);
            setValue('#clientTypeMasterStatus', row.status || 'Active');
            setValue('#clientTypeMasterDescription', row.description || '');
            setText('#saveClientTypeMasterBtn', 'Update Client Type');
            $('#clientTypeMasterName')?.focus();
        }

        function editContactMethod(code) {
            const row = contactMethods.find((item) => item.code === code);
            if (!row) return;
            setValue('#contactMethodEditingCode', row.code);
            setValue('#contactMethodMasterName', row.name);
            setValue('#contactMethodMasterCode', row.code);
            setValue('#contactMethodMasterSort', row.sortOrder || 0);
            setValue('#contactMethodMasterStatus', row.status || 'Active');
            setValue('#contactMethodMasterDescription', row.description || '');
            setText('#saveContactMethodMasterBtn', 'Update Contact Method');
            $('#contactMethodMasterName')?.focus();
        }

        function editDriverContactType(code) {
            const row = driverContactTypes.find((item) => item.code === code);
            if (!row) return;
            setValue('#driverContactTypeEditingCode', row.code);
            setValue('#driverContactTypeMasterName', row.name);
            setValue('#driverContactTypeMasterCode', row.code);
            setValue('#driverContactTypeMasterSort', row.sortOrder || 0);
            setValue('#driverContactTypeMasterStatus', row.status || 'Active');
            setValue('#driverContactTypeMasterDescription', row.description || '');
            setText('#saveDriverContactTypeMasterBtn', 'Update Contact Type');
            $('#driverContactTypeMasterName')?.focus();
        }

        async function runMasterFormTransaction(event, task) {
            event.preventDefault();
            const form = event.currentTarget;
            const submitter = event.submitter || form?.querySelector('button[type="submit"], input[type="submit"]');
            return window.FleetmanRunTransaction(submitter, task, {
                scope: form,
                loadingText: buttonLabelForMaster(submitter).includes('update') ? 'Updating...' : 'Saving...',
            });
        }

        function buttonLabelForMaster(button) {
            return String(button?.textContent || button?.value || '').trim().toLowerCase();
        }

        $('#vehicleCategoryMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectVehicleCategory();
                if (!row) return;
                const editingCode = value('#vehicleCategoryEditingCode');
                vehicleCategories = upsertRow(vehicleCategories, row, editingCode);
                if (editingCode && editingCode !== row.code) {
                    vehicleSubCategories = vehicleSubCategories.map((subRow) => subRow.vehicleCategoryCode === editingCode ? { ...subRow, vehicleCategoryCode: row.code, vehicleCategoryName: row.name } : subRow);
                }
                resetVehicleCategoryForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Vehicle category saved to database.');
            });
        });

        $('#vehicleSubCategoryMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectVehicleSubCategory();
                if (!row) return;
                vehicleSubCategories = upsertRow(vehicleSubCategories, row, value('#vehicleSubCategoryEditingCode'));
                resetVehicleSubCategoryForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Vehicle sub category saved to database.');
            });
        });

        $('#partyTypeMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectPartyType();
                if (!row) return;
                partyTypes = upsertRow(partyTypes, row, value('#partyTypeEditingCode'));
                resetPartyTypeForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Party type saved to database.');
            });
        });

        $('#documentNameMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectDocumentName();
                if (!row) return;

                const editingCode = value('#documentNameEditingCode');
                const previousRows = documentNames.slice();
                const nextRows = upsertRow(documentNames, row, editingCode);
                if (nextRows === documentNames) return;

                documentNames = nextRows;
                renderDocumentNames();

                try {
                    await saveDocumentName(row, editingCode);
                    resetDocumentNameForm();
                    toast('Document type saved to database.');
                } catch (error) {
                    documentNames = previousRows;
                    renderDocumentNames();
                    toast(error.message || 'Document type could not be saved.');
                }
            });
        });

        $('#licenceTypeMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectLicenceType();
                if (!row) return;
                licenceTypes = upsertRow(licenceTypes, row, value('#licenceTypeEditingCode'));
                resetLicenceTypeForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Licence type saved to database.');
            });
        });

        $('#fuelTypeMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectFuelType();
                if (!row) return;
                fuelTypes = upsertRow(fuelTypes, row, value('#fuelTypeEditingCode'));
                resetFuelTypeForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Fuel type saved to database.');
            });
        });

        $('#fuelUnitMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectFuelUnit();
                if (!row) return;
                fuelUnits = upsertRow(fuelUnits, row, value('#fuelUnitEditingCode'));
                resetFuelUnitForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Fuel unit saved to database.');
            });
        });

        $('#paymentTypeMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectPaymentType();
                if (!row) return;
                paymentTypes = upsertRow(paymentTypes, row, value('#paymentTypeEditingCode'));
                resetPaymentTypeForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Payment type saved to database.');
            });
        });

        $('#clientTypeMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectClientType();
                if (!row) return;
                clientTypes = upsertRow(clientTypes, row, value('#clientTypeEditingCode'));
                resetClientTypeForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Client type saved to database.');
            });
        });

        $('#contactMethodMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectContactMethod();
                if (!row) return;
                contactMethods = upsertRow(contactMethods, row, value('#contactMethodEditingCode'));
                resetContactMethodForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Contact method saved to database.');
            });
        });

        $('#driverContactTypeMasterForm')?.addEventListener('submit', async (event) => {
            await runMasterFormTransaction(event, async () => {
                const row = collectDriverContactType();
                if (!row) return;
                driverContactTypes = upsertRow(driverContactTypes, row, value('#driverContactTypeEditingCode'));
                resetDriverContactTypeForm();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast('Driver contact type saved to database.');
            });
        });

        $('#resetVehicleCategoryMasterBtn')?.addEventListener('click', resetVehicleCategoryForm);
        $('#cancelVehicleCategoryEditBtn')?.addEventListener('click', resetVehicleCategoryForm);
        $('#resetVehicleSubCategoryMasterBtn')?.addEventListener('click', resetVehicleSubCategoryForm);
        $('#cancelVehicleSubCategoryEditBtn')?.addEventListener('click', resetVehicleSubCategoryForm);
        $('#resetPartyTypeMasterBtn')?.addEventListener('click', resetPartyTypeForm);
        $('#cancelPartyTypeEditBtn')?.addEventListener('click', resetPartyTypeForm);
        $('#resetDocumentNameMasterBtn')?.addEventListener('click', resetDocumentNameForm);
        $('#cancelDocumentNameEditBtn')?.addEventListener('click', resetDocumentNameForm);
        $('#resetLicenceTypeMasterBtn')?.addEventListener('click', resetLicenceTypeForm);
        $('#cancelLicenceTypeEditBtn')?.addEventListener('click', resetLicenceTypeForm);

        $('#resetFuelTypeMasterBtn')?.addEventListener('click', resetFuelTypeForm);
        $('#cancelFuelTypeEditBtn')?.addEventListener('click', resetFuelTypeForm);
        $('#resetFuelUnitMasterBtn')?.addEventListener('click', resetFuelUnitForm);
        $('#cancelFuelUnitEditBtn')?.addEventListener('click', resetFuelUnitForm);
        $('#resetPaymentTypeMasterBtn')?.addEventListener('click', resetPaymentTypeForm);
        $('#cancelPaymentTypeEditBtn')?.addEventListener('click', resetPaymentTypeForm);

        $('#resetClientTypeMasterBtn')?.addEventListener('click', resetClientTypeForm);
        $('#cancelClientTypeEditBtn')?.addEventListener('click', resetClientTypeForm);
        $('#resetContactMethodMasterBtn')?.addEventListener('click', resetContactMethodForm);
        $('#cancelContactMethodEditBtn')?.addEventListener('click', resetContactMethodForm);
        $('#resetDriverContactTypeMasterBtn')?.addEventListener('click', resetDriverContactTypeForm);
        $('#cancelDriverContactTypeEditBtn')?.addEventListener('click', resetDriverContactTypeForm);

        $('#vehicleCategoryMasterName')?.addEventListener('input', () => {
            if (!value('#vehicleCategoryMasterCode') || !value('#vehicleCategoryEditingCode')) setValue('#vehicleCategoryMasterCode', codeFrom(value('#vehicleCategoryMasterName')));
        });
        $('#vehicleSubCategoryMasterName')?.addEventListener('input', () => {
            if (!value('#vehicleSubCategoryMasterCode') || !value('#vehicleSubCategoryEditingCode')) setValue('#vehicleSubCategoryMasterCode', codeForVehicleSubCategory());
        });
        $('#vehicleSubCategoryParent')?.addEventListener('change', () => {
            if (!value('#vehicleSubCategoryMasterCode') || !value('#vehicleSubCategoryEditingCode')) setValue('#vehicleSubCategoryMasterCode', codeForVehicleSubCategory());
        });
        $('#partyTypeMasterName')?.addEventListener('input', () => {
            if (!value('#partyTypeMasterCode') || !value('#partyTypeEditingCode')) setValue('#partyTypeMasterCode', codeFrom(value('#partyTypeMasterName')));
        });
        $('#documentNameMasterName')?.addEventListener('input', () => {
            if (!value('#documentNameMasterCode') || !value('#documentNameEditingCode')) setValue('#documentNameMasterCode', codeFrom(value('#documentNameMasterName')));
        });
        $('#documentNameMasterTypes')?.addEventListener('change', (event) => {
            const changed = event.target.closest('input[name="documentNameMasterTypes[]"]');
            if (!changed) return;

            const allModules = document.querySelector('input[name="documentNameMasterTypes[]"][value="All Modules"]');
            const specificTypes = Array.from(document.querySelectorAll('input[name="documentNameMasterTypes[]"]:not([value="All Modules"])'));

            if (changed.value === 'All Modules' && changed.checked) {
                specificTypes.forEach((input) => { input.checked = false; });
            } else if (changed.checked && allModules) {
                allModules.checked = false;
            }

            // The validation message is shown only after Save is pressed
            // with no document type selected. Any valid selection hides it.
            if (document.querySelector('input[name="documentNameMasterTypes[]"]:checked')) {
                setDocumentTypeValidationError(false);
            }
        });
        $('#licenceTypeMasterName')?.addEventListener('input', () => {
            if (!value('#licenceTypeMasterCode') || !value('#licenceTypeEditingCode')) setValue('#licenceTypeMasterCode', codeFrom(value('#licenceTypeMasterName')));
        });
        function bindMasterCodeGenerator(nameSelector, codeSelector, editingSelector = '') {
            const nameInput = $(nameSelector);
            const codeInput = $(codeSelector);
            if (!nameInput || !codeInput) return;

            const initialCode = codeInput.value.trim();
            nameInput.addEventListener('input', () => {
                const isEditing = editingSelector ? Boolean(value(editingSelector)) : initialCode !== '';
                if (isEditing) return;

                const name = nameInput.value.trim();
                codeInput.value = name ? codeFrom(name) : '';
            });
        }

        bindMasterCodeGenerator('#clientTypeMasterName', '#clientTypeMasterCode', '#clientTypeEditingCode');
        bindMasterCodeGenerator('#driverContactTypeMasterName', '#driverContactTypeMasterCode', '#driverContactTypeEditingCode');
        bindMasterCodeGenerator('#contactMethodMasterName', '#contactMethodMasterCode', '#contactMethodEditingCode');
        bindMasterCodeGenerator('#fuelTypeMasterName', '#fuelTypeMasterCode', '#fuelTypeEditingCode');
        bindMasterCodeGenerator('#fuelUnitMasterName', '#fuelUnitMasterCode', '#fuelUnitEditingCode');
        bindMasterCodeGenerator('#paymentTypeName', '#paymentTypeCode');

        async function runMasterDelete(button, confirmationMessage, mutate, successMessage) {
            if (!button || !confirm(confirmationMessage)) return;
            await window.FleetmanRunTransaction(button, async () => {
                mutate();
                renderAll();
                const result = await saveStore();
                if (result?.ok) toast(successMessage);
            }, { loadingText: 'Deleting...' });
        }

        document.addEventListener('click', async (event) => {
            const editVehicleCategoryBtn = event.target.closest('[data-master-edit-vehicle-category]');
            if (editVehicleCategoryBtn) editVehicleCategory(editVehicleCategoryBtn.dataset.masterEditVehicleCategory);

            const deleteVehicleCategoryBtn = event.target.closest('[data-master-delete-vehicle-category]');
            if (deleteVehicleCategoryBtn) {
                await runMasterDelete(deleteVehicleCategoryBtn, 'Delete this vehicle category from master data? Related sub categories will also be removed.', () => {
                    const deletingCode = deleteVehicleCategoryBtn.dataset.masterDeleteVehicleCategory;
                    vehicleCategories = vehicleCategories.filter((row) => row.code !== deletingCode);
                    vehicleSubCategories = vehicleSubCategories.filter((row) => row.vehicleCategoryCode !== deletingCode);
                }, 'Vehicle category deleted from database.');
            }

            const editVehicleSubCategoryBtn = event.target.closest('[data-master-edit-vehicle-sub-category]');
            if (editVehicleSubCategoryBtn) editVehicleSubCategory(editVehicleSubCategoryBtn.dataset.masterEditVehicleSubCategory);

            const deleteVehicleSubCategoryBtn = event.target.closest('[data-master-delete-vehicle-sub-category]');
            if (deleteVehicleSubCategoryBtn) {
                await runMasterDelete(deleteVehicleSubCategoryBtn, 'Delete this vehicle sub category from master data?', () => {
                    vehicleSubCategories = vehicleSubCategories.filter((row) => row.code !== deleteVehicleSubCategoryBtn.dataset.masterDeleteVehicleSubCategory);
                }, 'Vehicle sub category deleted from database.');
            }

            const editPartyBtn = event.target.closest('[data-master-edit-party]');
            if (editPartyBtn) editParty(editPartyBtn.dataset.masterEditParty);

            const deletePartyBtn = event.target.closest('[data-master-delete-party]');
            if (deletePartyBtn) {
                await runMasterDelete(deletePartyBtn, 'Delete this party type from master data?', () => {
                    partyTypes = partyTypes.filter((row) => row.code !== deletePartyBtn.dataset.masterDeleteParty);
                }, 'Party type deleted from database.');
            }

            const editDocumentBtn = event.target.closest('[data-master-edit-document]');
            if (editDocumentBtn) editDocument(editDocumentBtn.dataset.masterEditDocument);

            const deleteDocumentBtn = event.target.closest('[data-master-delete-document]');
            if (deleteDocumentBtn) {
                const documentId = Number(deleteDocumentBtn.dataset.masterDeleteDocument);
                const deletingRow = documentNames.find((row) => Number(row.id) === documentId);
                const documentLabel = deletingRow?.name ? ` “${deletingRow.name}”` : '';

                if (confirm(`Delete Document Type${documentLabel}? Only this selected row will be deleted.`)) {
                    await window.FleetmanRunTransaction(deleteDocumentBtn, async () => {
                        try {
                            const deletedId = await deleteDocumentName(documentId);
                            documentNames = documentNames.filter((row) => Number(row.id) !== deletedId);

                            if (deletingRow && value('#documentNameEditingCode') === deletingRow.code) {
                                resetDocumentNameForm();
                            }

                            renderDocumentNames();
                            toast('Document type deleted from database.');
                        } catch (error) {
                            toast(error.message || 'Document type could not be deleted.');
                        }
                    }, { loadingText: 'Deleting...' });
                }
            }

            const editLicenceBtn = event.target.closest('[data-master-edit-licence]');
            if (editLicenceBtn) editLicenceType(editLicenceBtn.dataset.masterEditLicence);

            const deleteLicenceBtn = event.target.closest('[data-master-delete-licence]');
            if (deleteLicenceBtn) {
                await runMasterDelete(deleteLicenceBtn, 'Delete this licence type from master data?', () => {
                    licenceTypes = licenceTypes.filter((row) => row.code !== deleteLicenceBtn.dataset.masterDeleteLicence);
                }, 'Licence type deleted from database.');
            }

            const editFuelTypeBtn = event.target.closest('[data-master-edit-fuel-type]');
            if (editFuelTypeBtn) editFuelType(editFuelTypeBtn.dataset.masterEditFuelType);

            const deleteFuelTypeBtn = event.target.closest('[data-master-delete-fuel-type]');
            if (deleteFuelTypeBtn) {
                await runMasterDelete(deleteFuelTypeBtn, 'Delete this fuel type from master data?', () => {
                    fuelTypes = fuelTypes.filter((row) => row.code !== deleteFuelTypeBtn.dataset.masterDeleteFuelType);
                }, 'Fuel type deleted from database.');
            }

            const editFuelUnitBtn = event.target.closest('[data-master-edit-fuel-unit]');
            if (editFuelUnitBtn) editFuelUnit(editFuelUnitBtn.dataset.masterEditFuelUnit);

            const deleteFuelUnitBtn = event.target.closest('[data-master-delete-fuel-unit]');
            if (deleteFuelUnitBtn) {
                await runMasterDelete(deleteFuelUnitBtn, 'Delete this fuel unit from master data?', () => {
                    fuelUnits = fuelUnits.filter((row) => row.code !== deleteFuelUnitBtn.dataset.masterDeleteFuelUnit);
                }, 'Fuel unit deleted from database.');
            }

            const editClientBtn = event.target.closest('[data-master-edit-client]');
            if (editClientBtn) editClientType(editClientBtn.dataset.masterEditClient);

            const deleteClientBtn = event.target.closest('[data-master-delete-client]');
            if (deleteClientBtn) {
                await runMasterDelete(deleteClientBtn, 'Delete this client type from master data?', () => {
                    clientTypes = clientTypes.filter((row) => row.code !== deleteClientBtn.dataset.masterDeleteClient);
                }, 'Client type deleted from database.');
            }

            const editContactMethodBtn = event.target.closest('[data-master-edit-contact-method]');
            if (editContactMethodBtn) editContactMethod(editContactMethodBtn.dataset.masterEditContactMethod);

            const deleteContactMethodBtn = event.target.closest('[data-master-delete-contact-method]');
            if (deleteContactMethodBtn) {
                await runMasterDelete(deleteContactMethodBtn, 'Delete this contact method from master data?', () => {
                    contactMethods = contactMethods.filter((row) => row.code !== deleteContactMethodBtn.dataset.masterDeleteContactMethod);
                }, 'Contact method deleted from database.');
            }

            const editPaymentTypeBtn = event.target.closest('[data-master-edit-payment-type]');
            if (editPaymentTypeBtn) editPaymentType(editPaymentTypeBtn.dataset.masterEditPaymentType);

            const deletePaymentTypeBtn = event.target.closest('[data-master-delete-payment-type]');
            if (deletePaymentTypeBtn) {
                await runMasterDelete(deletePaymentTypeBtn, 'Delete this payment type from master data? Existing trip records will keep their saved payment method.', () => {
                    paymentTypes = paymentTypes.filter((row) => row.code !== deletePaymentTypeBtn.dataset.masterDeletePaymentType);
                }, 'Payment type deleted from database.');
            }

            const editDriverContactTypeBtn = event.target.closest('[data-master-edit-driver-contact-type]');
            if (editDriverContactTypeBtn) editDriverContactType(editDriverContactTypeBtn.dataset.masterEditDriverContactType);

            const deleteDriverContactTypeBtn = event.target.closest('[data-master-delete-driver-contact-type]');
            if (deleteDriverContactTypeBtn) {
                await runMasterDelete(deleteDriverContactTypeBtn, 'Delete this driver contact type from master data?', () => {
                    driverContactTypes = driverContactTypes.filter((row) => row.code !== deleteDriverContactTypeBtn.dataset.masterDeleteDriverContactType);
                }, 'Driver contact type deleted from database.');
            }
        });

        resetVehicleCategoryForm();
        resetVehicleSubCategoryForm();
        resetFuelTypeForm();
        resetFuelUnitForm();
        resetPaymentTypeForm();
        resetClientTypeForm();
        resetContactMethodForm();
        resetDriverContactTypeForm();
        resetPartyTypeForm();
        resetDocumentNameForm();
        resetLicenceTypeForm();
        renderAll();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.body.dataset.page === 'master-data') initMasterData();
    });
})();
