const fs = require('fs');

let code = fs.readFileSync('public/js/fleetman.js', 'utf8');

// 1. masterData defaults
code = code.replace(
    /const masterData = data\.masterData \|\| \{ party_types: \[\], document_names: \[\], licence_types: \[\], client_types: \[\], contact_methods: \[\] \};/,
    'const masterData = data.masterData || { party_types: [], document_names: [], licence_types: [], client_types: [], contact_methods: [], fuel_types: [], fuel_units: [] };'
);

// 2. Local variables
code = code.replace(
    /let contactMethods = Array\.isArray\(masterData\.contact_methods\) \? masterData\.contact_methods\.slice\(\) : \[\];/,
    `let contactMethods = Array.isArray(masterData.contact_methods) ? masterData.contact_methods.slice() : [];
        let fuelTypes = Array.isArray(masterData.fuel_types) ? masterData.fuel_types.slice() : [];
        let fuelUnits = Array.isArray(masterData.fuel_units) ? masterData.fuel_units.slice() : [];`
);

// 3. body JSON stringify
code = code.replace(
    /body: JSON\.stringify\(\{ party_types: partyTypes, document_names: documentNames, licence_types: licenceTypes, client_types: clientTypes, contact_methods: contactMethods \}\),/,
    `body: JSON.stringify({ party_types: partyTypes, document_names: documentNames, licence_types: licenceTypes, client_types: clientTypes, contact_methods: contactMethods, fuel_types: fuelTypes, fuel_units: fuelUnits }),`
);

// 4. Update from payload
code = code.replace(
    /contactMethods = Array\.isArray\(payload\.masterData\.contact_methods\) \? payload\.masterData\.contact_methods : contactMethods;/,
    `contactMethods = Array.isArray(payload.masterData.contact_methods) ? payload.masterData.contact_methods : contactMethods;
                        fuelTypes = Array.isArray(payload.masterData.fuel_types) ? payload.masterData.fuel_types : fuelTypes;
                        fuelUnits = Array.isArray(payload.masterData.fuel_units) ? payload.masterData.fuel_units : fuelUnits;`
);

// 5. Reset forms
code = code.replace(
    /        function resetClientTypeForm\(\) \{/,
    `        function resetFuelTypeForm() {
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

        function resetClientTypeForm() {`
);

// 6. Collect functions
code = code.replace(
    /        function collectClientType\(\) \{/,
    `        function collectFuelType() {
            const name = value('#fuelTypeMasterName').trim();
            if (!name) { toast('Fuel Type Name is required.'); return null; }
            return { code: codeFrom(value('#fuelTypeMasterCode') || name), name, sortOrder: Number(value('#fuelTypeMasterSort') || 0), status: value('#fuelTypeMasterStatus') || 'Active', description: value('#fuelTypeMasterDescription').trim() };
        }

        function collectFuelUnit() {
            const name = value('#fuelUnitMasterName').trim();
            if (!name) { toast('Fuel Unit Name is required.'); return null; }
            return { code: codeFrom(value('#fuelUnitMasterCode') || name), name, sortOrder: Number(value('#fuelUnitMasterSort') || 0), status: value('#fuelUnitMasterStatus') || 'Active', description: value('#fuelUnitMasterDescription').trim() };
        }

        function collectClientType() {`
);

// 7. Render functions
code = code.replace(
    /        function renderClientTypes\(\) \{/,
    `        function renderFuelTypes() {
            setText('#masterFuelTypeCount', fuelTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelTypeMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => \`<tr><td><b>\${escapeHtml(row.name)}</b></td><td><span class="master-code">\${escapeHtml(row.code)}</span></td><td>\${Number(row.sortOrder || 0)}</td><td><span class="badge \${row.status === 'Inactive' ? 'warn' : 'ok'}">\${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">\${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-type="\${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-type="\${escapeHtml(row.code)}">Delete</button></div></td></tr>\`).join('') : '<tr><td colspan="6" class="empty">No fuel type added yet.</td></tr>';
        }

        function renderFuelUnits() {
            setText('#masterFuelUnitCount', fuelUnits.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelUnitMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelUnits);
            tbody.innerHTML = rows.length ? rows.map((row) => \`<tr><td><b>\${escapeHtml(row.name)}</b></td><td><span class="master-code">\${escapeHtml(row.code)}</span></td><td>\${Number(row.sortOrder || 0)}</td><td><span class="badge \${row.status === 'Inactive' ? 'warn' : 'ok'}">\${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">\${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-unit="\${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-unit="\${escapeHtml(row.code)}">Delete</button></div></td></tr>\`).join('') : '<tr><td colspan="6" class="empty">No fuel unit added yet.</td></tr>';
        }

        function renderClientTypes() {`
);

// 8. renderAll calls
code = code.replace(
    /            renderContactMethods\(\);\n        \}/,
    `            renderContactMethods();
            renderFuelTypes();
            renderFuelUnits();
        }`
);

// 9. Edit functions
code = code.replace(
    /        function editClientType\(code\) \{/,
    `        function editFuelType(code) {
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

        function editClientType(code) {`
);

// 10. Form Submits
code = code.replace(
    /        \$\('#clientTypeMasterForm'\)\?\.addEventListener/,
    `        $('#fuelTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectFuelType();
            if (!row) return;
            fuelTypes = upsertRow(fuelTypes, row, value('#fuelTypeEditingCode'));
            resetFuelTypeForm();
            renderAll();
            saveStore();
            toast('Fuel type saved to database.');
        });

        $('#fuelUnitMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectFuelUnit();
            if (!row) return;
            fuelUnits = upsertRow(fuelUnits, row, value('#fuelUnitEditingCode'));
            resetFuelUnitForm();
            renderAll();
            saveStore();
            toast('Fuel unit saved to database.');
        });

        $('#clientTypeMasterForm')?.addEventListener`
);

// 11. Reset listeners
code = code.replace(
    /        \$\('#resetClientTypeMasterBtn'\)\?\.addEventListener/,
    `        $('#resetFuelTypeMasterBtn')?.addEventListener('click', resetFuelTypeForm);
        $('#cancelFuelTypeEditBtn')?.addEventListener('click', resetFuelTypeForm);
        $('#resetFuelUnitMasterBtn')?.addEventListener('click', resetFuelUnitForm);
        $('#cancelFuelUnitEditBtn')?.addEventListener('click', resetFuelUnitForm);

        $('#resetClientTypeMasterBtn')?.addEventListener`
);

// 12. Input syncs
code = code.replace(
    /        \$\('#clientTypeMasterName'\)\?\.addEventListener/,
    `        $('#fuelTypeMasterName')?.addEventListener('input', () => {
            if (!value('#fuelTypeMasterCode') || !value('#fuelTypeEditingCode')) setValue('#fuelTypeMasterCode', codeFrom(value('#fuelTypeMasterName')));
        });
        $('#fuelUnitMasterName')?.addEventListener('input', () => {
            if (!value('#fuelUnitMasterCode') || !value('#fuelUnitEditingCode')) setValue('#fuelUnitMasterCode', codeFrom(value('#fuelUnitMasterName')));
        });

        $('#clientTypeMasterName')?.addEventListener`
);

// 13. Click delegates
code = code.replace(
    /            const editClientBtn = event\.target\.closest\('\[data-master-edit-client\]'\);/,
    `            const editFuelTypeBtn = event.target.closest('[data-master-edit-fuel-type]');
            if (editFuelTypeBtn) editFuelType(editFuelTypeBtn.dataset.masterEditFuelType);

            const deleteFuelTypeBtn = event.target.closest('[data-master-delete-fuel-type]');
            if (deleteFuelTypeBtn && confirm('Delete this fuel type from master data?')) {
                fuelTypes = fuelTypes.filter((row) => row.code !== deleteFuelTypeBtn.dataset.masterDeleteFuelType);
                renderAll();
                saveStore();
                toast('Fuel type deleted from database.');
            }

            const editFuelUnitBtn = event.target.closest('[data-master-edit-fuel-unit]');
            if (editFuelUnitBtn) editFuelUnit(editFuelUnitBtn.dataset.masterEditFuelUnit);

            const deleteFuelUnitBtn = event.target.closest('[data-master-delete-fuel-unit]');
            if (deleteFuelUnitBtn && confirm('Delete this fuel unit from master data?')) {
                fuelUnits = fuelUnits.filter((row) => row.code !== deleteFuelUnitBtn.dataset.masterDeleteFuelUnit);
                renderAll();
                saveStore();
                toast('Fuel unit deleted from database.');
            }

            const editClientBtn = event.target.closest('[data-master-edit-client]');`
);

// 14. Reset forms on init
code = code.replace(
    /        resetClientTypeForm\(\);/,
    `        resetFuelTypeForm();
        resetFuelUnitForm();
        resetClientTypeForm();`
);

fs.writeFileSync('public/js/fleetman.js', code);
console.log('Done!');
