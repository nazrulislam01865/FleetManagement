const fs = require('fs');

let code = fs.readFileSync('public/js/fleetman.js', 'utf8');

// 1. masterData defaults
code = code.replace(
    /const masterData = data\.masterData \|\| \{ party_types: \[\], document_names: \[\], licence_types: \[\] \};/,
    'const masterData = data.masterData || { party_types: [], document_names: [], licence_types: [], client_types: [], contact_methods: [] };'
);

// 2. Local variables
code = code.replace(
    /let licenceTypes = Array\.isArray\(masterData\.licence_types\) \? masterData\.licence_types\.slice\(\) : \[\];/,
    `let licenceTypes = Array.isArray(masterData.licence_types) ? masterData.licence_types.slice() : [];
        let clientTypes = Array.isArray(masterData.client_types) ? masterData.client_types.slice() : [];
        let contactMethods = Array.isArray(masterData.contact_methods) ? masterData.contact_methods.slice() : [];`
);

// 3. body JSON stringify
code = code.replace(
    /body: JSON\.stringify\(\{ party_types: partyTypes, document_names: documentNames, licence_types: licenceTypes \}\),/,
    `body: JSON.stringify({ party_types: partyTypes, document_names: documentNames, licence_types: licenceTypes, client_types: clientTypes, contact_methods: contactMethods }),`
);

// 4. Update from payload
code = code.replace(
    /licenceTypes = Array\.isArray\(payload\.masterData\.licence_types\) \? payload\.masterData\.licence_types : licenceTypes;/,
    `licenceTypes = Array.isArray(payload.masterData.licence_types) ? payload.masterData.licence_types : licenceTypes;
                        clientTypes = Array.isArray(payload.masterData.client_types) ? payload.masterData.client_types : clientTypes;
                        contactMethods = Array.isArray(payload.masterData.contact_methods) ? payload.masterData.contact_methods : contactMethods;`
);

// 5. Reset forms
code = code.replace(
    /        function collectPartyType\(\) \{/,
    `        function resetClientTypeForm() {
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

        function collectPartyType() {`
);

// 6. Collect functions
code = code.replace(
    /        function upsertRow\(rows, row, editingCode\) \{/,
    `        function collectClientType() {
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

        function upsertRow(rows, row, editingCode) {`
);

// 7. Render functions
code = code.replace(
    /        function renderAll\(\) \{/,
    `        function renderClientTypes() {
            setText('#masterClientTypeCount', clientTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#clientTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(clientTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => \`
                <tr>
                    <td><b>\${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">\${escapeHtml(row.code)}</span></td>
                    <td>\${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge \${row.status === 'Inactive' ? 'warn' : 'ok'}">\${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">\${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-client="\${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-client="\${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>\`).join('') : '<tr><td colspan="6" class="empty">No client type added yet.</td></tr>';
        }

        function renderContactMethods() {
            setText('#masterContactMethodCount', contactMethods.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#contactMethodMasterTbody');
            if (!tbody) return;

            const rows = sortRows(contactMethods);
            tbody.innerHTML = rows.length ? rows.map((row) => \`
                <tr>
                    <td><b>\${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">\${escapeHtml(row.code)}</span></td>
                    <td>\${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge \${row.status === 'Inactive' ? 'warn' : 'ok'}">\${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">\${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-contact-method="\${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-contact-method="\${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>\`).join('') : '<tr><td colspan="6" class="empty">No contact method added yet.</td></tr>';
        }

        function renderAll() {`
);

// 8. renderAll calls
code = code.replace(
    /            renderLicenceTypes\(\);\n        \}/,
    `            renderLicenceTypes();
            renderClientTypes();
            renderContactMethods();
        }`
);

// 9. Edit functions
code = code.replace(
    /        \$\('#partyTypeMasterForm'\)\?\.addEventListener/,
    `        function editClientType(code) {
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

        $('#partyTypeMasterForm')?.addEventListener`
);

// 10. Form Submits
code = code.replace(
    /        \$\('#resetPartyTypeMasterBtn'\)\?\.addEventListener/,
    `        $('#clientTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectClientType();
            if (!row) return;
            clientTypes = upsertRow(clientTypes, row, value('#clientTypeEditingCode'));
            resetClientTypeForm();
            renderAll();
            saveStore();
            toast('Client type saved to database.');
        });

        $('#contactMethodMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectContactMethod();
            if (!row) return;
            contactMethods = upsertRow(contactMethods, row, value('#contactMethodEditingCode'));
            resetContactMethodForm();
            renderAll();
            saveStore();
            toast('Contact method saved to database.');
        });

        $('#resetPartyTypeMasterBtn')?.addEventListener`
);

// 11. Reset listeners
code = code.replace(
    /        \$\('#partyTypeMasterName'\)\?\.addEventListener/,
    `        $('#resetClientTypeMasterBtn')?.addEventListener('click', resetClientTypeForm);
        $('#cancelClientTypeEditBtn')?.addEventListener('click', resetClientTypeForm);
        $('#resetContactMethodMasterBtn')?.addEventListener('click', resetContactMethodForm);
        $('#cancelContactMethodEditBtn')?.addEventListener('click', resetContactMethodForm);

        $('#partyTypeMasterName')?.addEventListener`
);

// 12. Input syncs
code = code.replace(
    /        document\.addEventListener\('click', \(event\) => \{/,
    `        $('#clientTypeMasterName')?.addEventListener('input', () => {
            if (!value('#clientTypeMasterCode') || !value('#clientTypeEditingCode')) setValue('#clientTypeMasterCode', codeFrom(value('#clientTypeMasterName')));
        });
        $('#contactMethodMasterName')?.addEventListener('input', () => {
            if (!value('#contactMethodMasterCode') || !value('#contactMethodEditingCode')) setValue('#contactMethodMasterCode', codeFrom(value('#contactMethodMasterName')));
        });

        document.addEventListener('click', (event) => {`
);

// 13. Click delegates
code = code.replace(
    /        \}\);\n\n        resetPartyTypeForm\(\);/,
    `            const editClientBtn = event.target.closest('[data-master-edit-client]');
            if (editClientBtn) editClientType(editClientBtn.dataset.masterEditClient);

            const deleteClientBtn = event.target.closest('[data-master-delete-client]');
            if (deleteClientBtn && confirm('Delete this client type from master data?')) {
                clientTypes = clientTypes.filter((row) => row.code !== deleteClientBtn.dataset.masterDeleteClient);
                renderAll();
                saveStore();
                toast('Client type deleted from database.');
            }

            const editContactMethodBtn = event.target.closest('[data-master-edit-contact-method]');
            if (editContactMethodBtn) editContactMethod(editContactMethodBtn.dataset.masterEditContactMethod);

            const deleteContactMethodBtn = event.target.closest('[data-master-delete-contact-method]');
            if (deleteContactMethodBtn && confirm('Delete this contact method from master data?')) {
                contactMethods = contactMethods.filter((row) => row.code !== deleteContactMethodBtn.dataset.masterDeleteContactMethod);
                renderAll();
                saveStore();
                toast('Contact method deleted from database.');
            }
        });

        resetClientTypeForm();
        resetContactMethodForm();
        resetPartyTypeForm();`
);

fs.writeFileSync('public/js/fleetman.js', code);
console.log('Done!');
