const fs = require('fs');

let code = fs.readFileSync('public/js/fleetman.js', 'utf8');

// 1. masterData defaults
code = code.replace(
    /contact_methods: \[\] \};/,
    'contact_methods: [], units: [], fuel_types: [] };'
);

// 2. Local variables
code = code.replace(
    /let contactMethods = Array\.isArray\(masterData\.contact_methods\) \? masterData\.contact_methods\.slice\(\) : \[\];/,
    `let contactMethods = Array.isArray(masterData.contact_methods) ? masterData.contact_methods.slice() : [];
        let units = Array.isArray(masterData.units) ? masterData.units.slice() : [];
        let fuelTypes = Array.isArray(masterData.fuel_types) ? masterData.fuel_types.slice() : [];`
);

// 3. body JSON stringify
code = code.replace(
    /client_types: clientTypes, contact_methods: contactMethods \}\),/,
    `client_types: clientTypes, contact_methods: contactMethods, units: units, fuel_types: fuelTypes }),`
);

// 4. Update from payload
code = code.replace(
    /contactMethods = Array\.isArray\(payload\.masterData\.contact_methods\) \? payload\.masterData\.contact_methods : contactMethods;/,
    `contactMethods = Array.isArray(payload.masterData.contact_methods) ? payload.masterData.contact_methods : contactMethods;
                        units = Array.isArray(payload.masterData.units) ? payload.masterData.units : units;
                        fuelTypes = Array.isArray(payload.masterData.fuel_types) ? payload.masterData.fuel_types : fuelTypes;`
);

// 5. Reset forms
code = code.replace(
    /        function collectClientType\(\) \{/,
    `        function resetUnitForm() {
            setValue('#unitEditingCode', '');
            setValue('#unitMasterName', '');
            setValue('#unitMasterCode', '');
            setValue('#unitMasterSort', '0');
            setValue('#unitMasterStatus', 'Active');
            setValue('#unitMasterDescription', '');
            setText('#saveUnitMasterBtn', 'Save Unit');
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

        function collectClientType() {`
);

// 6. Collect functions
code = code.replace(
    /        function upsertRow\(rows, row, editingCode\) \{/,
    `        function collectUnit() {
            const name = value('#unitMasterName').trim();
            if (!name) {
                toast('Unit Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#unitMasterCode') || name),
                name,
                sortOrder: Number(value('#unitMasterSort') || 0),
                status: value('#unitMasterStatus') || 'Active',
                description: value('#unitMasterDescription').trim(),
            };
        }

        function collectFuelType() {
            const name = value('#fuelTypeMasterName').trim();
            if (!name) {
                toast('Fuel Type Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#fuelTypeMasterCode') || name),
                name,
                sortOrder: Number(value('#fuelTypeMasterSort') || 0),
                status: value('#fuelTypeMasterStatus') || 'Active',
                description: value('#fuelTypeMasterDescription').trim(),
            };
        }

        function upsertRow(rows, row, editingCode) {`
);

// 7. Render functions
code = code.replace(
    /        function renderAll\(\) \{/,
    `        function renderUnits() {
            setText('#masterUnitCount', units.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#unitMasterTbody');
            if (!tbody) return;

            const rows = sortRows(units);
            tbody.innerHTML = rows.length ? rows.map((row) => \`
                <tr>
                    <td><b>\${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">\${escapeHtml(row.code)}</span></td>
                    <td>\${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge \${row.status === 'Inactive' ? 'warn' : 'ok'}">\${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">\${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-unit="\${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-unit="\${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>\`).join('') : '<tr><td colspan="6" class="empty">No unit added yet.</td></tr>';
        }

        function renderFuelTypes() {
            setText('#masterFuelTypeCount', fuelTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(fuelTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => \`
                <tr>
                    <td><b>\${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">\${escapeHtml(row.code)}</span></td>
                    <td>\${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge \${row.status === 'Inactive' ? 'warn' : 'ok'}">\${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">\${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-type="\${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-type="\${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>\`).join('') : '<tr><td colspan="6" class="empty">No fuel type added yet.</td></tr>';
        }

        function renderAll() {`
);

// 8. renderAll calls
code = code.replace(
    /            renderContactMethods\(\);\n        \}/,
    `            renderContactMethods();
            renderUnits();
            renderFuelTypes();
        }`
);

// 9. Edit functions
code = code.replace(
    /        function editClientType\(code\) \{/,
    `        function editUnit(code) {
            const row = units.find((item) => item.code === code);
            if (!row) return;
            setValue('#unitEditingCode', row.code);
            setValue('#unitMasterName', row.name);
            setValue('#unitMasterCode', row.code);
            setValue('#unitMasterSort', row.sortOrder || 0);
            setValue('#unitMasterStatus', row.status || 'Active');
            setValue('#unitMasterDescription', row.description || '');
            setText('#saveUnitMasterBtn', 'Update Unit');
            $('#unitMasterName')?.focus();
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

        function editClientType(code) {`
);

// 10. Form Submits
code = code.replace(
    /        \$\('#clientTypeMasterForm'\)\?\.addEventListener/,
    `        $('#unitMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectUnit();
            if (!row) return;
            units = upsertRow(units, row, value('#unitEditingCode'));
            resetUnitForm();
            renderAll();
            saveStore();
            toast('Unit saved to database.');
        });

        $('#fuelTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectFuelType();
            if (!row) return;
            fuelTypes = upsertRow(fuelTypes, row, value('#fuelTypeEditingCode'));
            resetFuelTypeForm();
            renderAll();
            saveStore();
            toast('Fuel type saved to database.');
        });

        $('#clientTypeMasterForm')?.addEventListener`
);

// 11. Reset listeners
code = code.replace(
    /        \$\('#resetClientTypeMasterBtn'\)\?\.addEventListener/,
    `        $('#resetUnitMasterBtn')?.addEventListener('click', resetUnitForm);
        $('#cancelUnitEditBtn')?.addEventListener('click', resetUnitForm);
        $('#resetFuelTypeMasterBtn')?.addEventListener('click', resetFuelTypeForm);
        $('#cancelFuelTypeEditBtn')?.addEventListener('click', resetFuelTypeForm);

        $('#resetClientTypeMasterBtn')?.addEventListener`
);

// 12. Input syncs
code = code.replace(
    /        \$\('#clientTypeMasterName'\)\?\.addEventListener/,
    `        $('#unitMasterName')?.addEventListener('input', () => {
            if (!value('#unitMasterCode') || !value('#unitEditingCode')) setValue('#unitMasterCode', codeFrom(value('#unitMasterName')));
        });
        $('#fuelTypeMasterName')?.addEventListener('input', () => {
            if (!value('#fuelTypeMasterCode') || !value('#fuelTypeEditingCode')) setValue('#fuelTypeMasterCode', codeFrom(value('#fuelTypeMasterName')));
        });

        $('#clientTypeMasterName')?.addEventListener`
);

// 13. Click delegates
code = code.replace(
    /        resetClientTypeForm\(\);/,
    `            const editUnitBtn = event.target.closest('[data-master-edit-unit]');
            if (editUnitBtn) editUnit(editUnitBtn.dataset.masterEditUnit);

            const deleteUnitBtn = event.target.closest('[data-master-delete-unit]');
            if (deleteUnitBtn && confirm('Delete this unit from master data?')) {
                units = units.filter((row) => row.code !== deleteUnitBtn.dataset.masterDeleteUnit);
                renderAll();
                saveStore();
                toast('Unit deleted from database.');
            }

            const editFuelTypeBtn = event.target.closest('[data-master-edit-fuel-type]');
            if (editFuelTypeBtn) editFuelType(editFuelTypeBtn.dataset.masterEditFuelType);

            const deleteFuelTypeBtn = event.target.closest('[data-master-delete-fuel-type]');
            if (deleteFuelTypeBtn && confirm('Delete this fuel type from master data?')) {
                fuelTypes = fuelTypes.filter((row) => row.code !== deleteFuelTypeBtn.dataset.masterDeleteFuelType);
                renderAll();
                saveStore();
                toast('Fuel type deleted from database.');
            }
        });

        resetUnitForm();
        resetFuelTypeForm();
        resetClientTypeForm();`
);

// wait, replace step 13 targets: `        resetClientTypeForm();`
// we also need to close the `});` correctly. Oh wait, my target text is JUST `        resetClientTypeForm();`.
// Let me change the target text to `        });\n\n        resetClientTypeForm();` to be safe.
code = code.replace(
    /        \}\);\n\n        resetClientTypeForm\(\);/,
    `            const editUnitBtn = event.target.closest('[data-master-edit-unit]');
            if (editUnitBtn) editUnit(editUnitBtn.dataset.masterEditUnit);

            const deleteUnitBtn = event.target.closest('[data-master-delete-unit]');
            if (deleteUnitBtn && confirm('Delete this unit from master data?')) {
                units = units.filter((row) => row.code !== deleteUnitBtn.dataset.masterDeleteUnit);
                renderAll();
                saveStore();
                toast('Unit deleted from database.');
            }

            const editFuelTypeBtn = event.target.closest('[data-master-edit-fuel-type]');
            if (editFuelTypeBtn) editFuelType(editFuelTypeBtn.dataset.masterEditFuelType);

            const deleteFuelTypeBtn = event.target.closest('[data-master-delete-fuel-type]');
            if (deleteFuelTypeBtn && confirm('Delete this fuel type from master data?')) {
                fuelTypes = fuelTypes.filter((row) => row.code !== deleteFuelTypeBtn.dataset.masterDeleteFuelType);
                renderAll();
                saveStore();
                toast('Fuel type deleted from database.');
            }
        });

        resetUnitForm();
        resetFuelTypeForm();
        resetClientTypeForm();`
);

fs.writeFileSync('public/js/fleetman.js', code);
console.log('Done!');
