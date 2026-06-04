/* Shared record details modal for FleetMan entities. */
window.FleetmanDetailViewer = window.FleetmanDetailViewer || (() => {
    'use strict';

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ch]));
    const humanize = (key) => String(key || '')
        .replace(/[_-]+/g, ' ')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

    function currentRoleSlug() {
        return String(window.FLEETMAN?.auth?.role?.slug || '').toLowerCase();
    }

    function canViewDetails() {
        const auth = window.FLEETMAN?.auth || {};
        const roleName = String(auth.role?.name || '').toLowerCase();
        return Boolean(auth.isSuperAdmin) || ['super_admin', 'admin_user'].includes(currentRoleSlug()) || roleName === 'admin user';
    }

    function toast(message) {
        const element = document.getElementById('toast');
        if (!element) {
            alert(message);
            return;
        }
        element.textContent = message;
        element.classList.add('show');
        setTimeout(() => element.classList.remove('show'), 2800);
    }

    function ensureModal() {
        let overlay = document.getElementById('fleetDetailOverlay');
        if (overlay) return overlay;

        overlay = document.createElement('div');
        overlay.id = 'fleetDetailOverlay';
        overlay.className = 'fleet-detail-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.innerHTML = `
            <section class="fleet-detail-panel" role="dialog" aria-modal="true" aria-labelledby="fleetDetailTitle">
                <div class="fleet-detail-head">
                    <div>
                        <span class="fleet-detail-kicker" id="fleetDetailKicker">Record Details</span>
                        <h2 id="fleetDetailTitle">Details</h2>
                        <p id="fleetDetailSubtitle"></p>
                    </div>
                    <button type="button" class="fleet-detail-close" data-fleet-detail-close aria-label="Close details">×</button>
                </div>
                <div class="fleet-detail-body" id="fleetDetailBody"></div>
            </section>`;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay || event.target.closest('[data-fleet-detail-close]')) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay.classList.contains('show')) close();
        });

        return overlay;
    }

    function close() {
        const overlay = document.getElementById('fleetDetailOverlay');
        if (!overlay) return;
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('fleet-detail-open');
    }

    function fileUrl(file = {}) {
        if (file.fileUrl || file.url) return String(file.fileUrl || file.url);
        const path = String(file.filePath || file.path || '');
        if (!path) return '';
        if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
        return '/storage/' + path.replace(/^public\//, '').replace(/^storage\//, '');
    }

    function isFileLike(value) {
        return value && typeof value === 'object' && !Array.isArray(value) && Boolean(value.fileUrl || value.filePath || value.url || value.path || value.originalName || value.fileName || value.mimeType);
    }

    function isImageFile(file = {}) {
        const mime = String(file.mimeType || file.type || '').toLowerCase();
        const name = String(file.originalName || file.fileName || file.filePath || file.url || '').toLowerCase();
        return mime.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|bmp|svg)$/i.test(name);
    }

    function collectFiles(record) {
        const files = [];
        const seen = new Set();

        function push(file, label = '') {
            if (!isFileLike(file)) return;
            const url = fileUrl(file);
            const name = file.originalName || file.fileName || label || 'Uploaded file';
            const key = [url, name, file.mimeType || ''].join('|');
            if (seen.has(key)) return;
            seen.add(key);
            files.push({
                url,
                name,
                label: label || name,
                mimeType: file.mimeType || '',
                sizeBytes: file.sizeBytes || '',
                uploadedAt: file.uploadedAt || '',
                isImage: isImageFile(file),
            });
        }

        function walk(node, context = '') {
            if (!node || typeof node !== 'object') return;
            if (isFileLike(node)) {
                push(node, context);
                return;
            }
            if (Array.isArray(node)) {
                node.forEach((item, index) => walk(item, context ? `${context} ${index + 1}` : `Item ${index + 1}`));
                return;
            }
            if (node.file && isFileLike(node.file)) {
                push(node.file, node.name || node.title || node.type || node.reference || context || 'Document');
            }
            Object.entries(node).forEach(([key, value]) => {
                if (key === 'file') return;
                walk(value, node.name || node.title || node.type || humanize(key));
            });
        }

        walk(record, 'Record');
        return files;
    }

    function valueText(value) {
        if (value === null || value === undefined || value === '') return '—';
        if (typeof value === 'boolean') return value ? 'Yes' : 'No';
        if (typeof value === 'number') return Number(value).toLocaleString();
        return String(value);
    }

    function renderPrimitiveFields(record = {}) {
        const skip = new Set(['photo', 'image', 'file', 'documents', 'docs', 'contacts', 'fuels', 'payments', 'photos', 'assignments']);
        const fields = Object.entries(record)
            .filter(([key, value]) => !skip.has(key) && (typeof value !== 'object' || value === null) && value !== '')
            .map(([key, value]) => `
                <div class="fleet-detail-field">
                    <small>${escapeHtml(humanize(key))}</small>
                    <strong>${escapeHtml(valueText(value))}</strong>
                </div>`)
            .join('');

        return fields ? `<div class="fleet-detail-grid">${fields}</div>` : '<p class="fleet-detail-muted">No primary fields found for this record.</p>';
    }

    function renderArrayCards(title, rows) {
        if (!Array.isArray(rows) || !rows.length) return '';
        const cards = rows.map((item, index) => {
            if (!item || typeof item !== 'object') return `<div class="fleet-detail-mini-card"><b>${index + 1}.</b> ${escapeHtml(valueText(item))}</div>`;
            const parts = Object.entries(item)
                .filter(([key]) => key !== 'file')
                .map(([key, value]) => {
                    if (value && typeof value === 'object') return '';
                    return `<div><small>${escapeHtml(humanize(key))}</small><b>${escapeHtml(valueText(value))}</b></div>`;
                })
                .filter(Boolean)
                .join('');
            return `<div class="fleet-detail-mini-card"><div class="fleet-detail-mini-title">${escapeHtml(title)} ${index + 1}</div>${parts || '<span class="fleet-detail-muted">No extra data</span>'}</div>`;
        }).join('');
        return `<section class="fleet-detail-section"><h3>${escapeHtml(title)}</h3><div class="fleet-detail-mini-grid">${cards}</div></section>`;
    }

    function renderFiles(files) {
        if (!files.length) return '';
        const images = files.filter((file) => file.isImage && file.url);
        const documents = files.filter((file) => !file.isImage || !file.url);
        return `
            ${images.length ? `<section class="fleet-detail-section"><h3>Uploaded Images</h3><div class="fleet-detail-image-grid">${images.map((file) => `
                <a href="${escapeHtml(file.url)}" target="_blank" rel="noopener" class="fleet-detail-image-card">
                    <img src="${escapeHtml(file.url)}" alt="${escapeHtml(file.label || file.name)}">
                    <span>${escapeHtml(file.label || file.name)}</span>
                </a>`).join('')}</div></section>` : ''}
            ${documents.length ? `<section class="fleet-detail-section"><h3>Uploaded Documents / Files</h3><div class="fleet-detail-file-list">${documents.map((file) => `
                <div class="fleet-detail-file-row">
                    <div><b>${escapeHtml(file.label || file.name)}</b><small>${escapeHtml([file.name, file.mimeType, file.uploadedAt].filter(Boolean).join(' • ') || 'Saved file')}</small></div>
                    ${file.url ? `<a href="${escapeHtml(file.url)}" target="_blank" rel="noopener" class="mini-btn">Open</a>` : '<span class="badge soft">No link</span>'}
                </div>`).join('')}</div></section>` : ''}`;
    }

    function renderNestedSections(record = {}) {
        const sections = [];
        if (record.contacts) sections.push(renderArrayCards('Contact', record.contacts));
        if (record.documents) sections.push(renderArrayCards('Document', record.documents));
        if (record.docs) sections.push(renderArrayCards('Document', record.docs));
        if (record.fuels) sections.push(renderArrayCards('Fuel', record.fuels));
        if (record.payments) sections.push(renderArrayCards('Payment', record.payments));
        if (record.assignments) sections.push(renderArrayCards('Assignment', record.assignments));
        if (record.photos && typeof record.photos === 'object') sections.push(renderArrayCards('Photo', Object.entries(record.photos).map(([key, value]) => ({ type: key, ...(value || {}) }))));
        return sections.filter(Boolean).join('');
    }

    function titleFor(type, record = {}) {
        return record.fullName || record.clientName || record.partyName || record.name || record.tripId || record.id || record.employeeId || record.driverId || 'Record Details';
    }

    function subtitleFor(type, record = {}) {
        const values = [];
        if (record.id) values.push(record.id);
        if (record.driverId) values.push(record.driverId);
        if (record.employeeId) values.push(record.employeeId);
        if (record.clientId) values.push(record.clientId);
        if (record.partyId) values.push(record.partyId);
        if (record.tripId) values.push(record.tripId);
        if (record.status) values.push(record.status);
        return values.join(' • ');
    }

    function show(type, record = {}) {
        if (!canViewDetails()) {
            toast('Only Super Admin and Admin User can view full details.');
            return;
        }
        if (!record) return;

        const overlay = ensureModal();
        const files = collectFiles(record);
        document.getElementById('fleetDetailKicker').textContent = type || 'Record Details';
        document.getElementById('fleetDetailTitle').textContent = titleFor(type, record);
        document.getElementById('fleetDetailSubtitle').textContent = subtitleFor(type, record);
        document.getElementById('fleetDetailBody').innerHTML = `
            <section class="fleet-detail-section">
                <h3>Full Details</h3>
                ${renderPrimitiveFields(record)}
            </section>
            ${renderNestedSections(record)}
            ${renderFiles(files)}
        `;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fleet-detail-open');
    }

    return { show, close, canViewDetails };
})();


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

    function bindMobileDrawer() {
        const body = document.body;
        const sidebar = $('.sidebar');
        const menuButton = $('#menuBtn');
        const backdrop = $('#backdrop');
        const scrollKey = 'fleetman.sidebar.scrollTop';
        const openKey = (key) => `fleetman.sidebar.open.${key}`;

        function setDrawer(open) {
            body.classList.toggle('drawer-open', open);
            menuButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function setMenuOpen(block, open, persist = true) {
            const key = block?.dataset?.menuKey || '';
            const toggle = $('[data-submenu-toggle]', block);
            if (!block || !toggle || !key) return;

            block.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (persist) localStorage.setItem(openKey(key), open ? '1' : '0');
        }

        if (sidebar) {
            requestAnimationFrame(() => {
                const savedScroll = Number(localStorage.getItem(scrollKey) || 0);
                if (savedScroll > 0) sidebar.scrollTop = savedScroll;
            });

            sidebar.addEventListener('scroll', () => {
                localStorage.setItem(scrollKey, String(sidebar.scrollTop || 0));
            }, { passive: true });
        }

        $$('[data-menu-block]').forEach((block) => {
            const key = block.dataset.menuKey || '';
            if (!key || !$('[data-submenu-toggle]', block)) return;

            const saved = localStorage.getItem(openKey(key));
            if (saved === '1') setMenuOpen(block, true, false);
            if (saved === '0') setMenuOpen(block, false, false);
        });

        menuButton?.setAttribute('aria-controls', 'fleetSidebar');
        menuButton?.setAttribute('aria-expanded', 'false');
        menuButton?.addEventListener('click', () => setDrawer(!body.classList.contains('drawer-open')));
        backdrop?.addEventListener('click', () => setDrawer(false));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setDrawer(false);
        });

        document.addEventListener('click', (event) => {
            const submenuToggle = event.target.closest('[data-submenu-toggle]');
            if (submenuToggle) {
                if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
                    event.preventDefault();
                    const block = submenuToggle.closest('[data-menu-block]');
                    if (block) setMenuOpen(block, !block.classList.contains('open'));
                }
                return;
            }

            const menuLink = event.target.closest('.menu-item,.submenu-item');
            if (menuLink && sidebar?.contains(menuLink)) {
                localStorage.setItem(scrollKey, String(sidebar.scrollTop || 0));
                if (window.matchMedia('(max-width: 1050px)').matches) setDrawer(false);
            }
        });
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
        let vehicles = Array.isArray(records.vehicles) ? records.vehicles : (samples.vehicles || []);
        const vehicleCategories = options.vehicle_categories || {};
        const fuelTypes = options.fuel_types || [];
        const latestFuelRates = data.latestFuelRates || {};
        const fuelStations = Array.isArray(data.fuelStations) ? data.fuelStations : [];
        const docTemplates = options.document_templates || [];
        const docReminders = options.document_reminders || [];

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

        function renderVehicleImageInfo(fileData = {}, selectedFile = null) {
            const info = $('#vehicleImageUploadInfo');
            if (!info) return;
            if (selectedFile) {
                info.innerHTML = `<span class="pending-upload">Selected: <b>${escapeHtml(selectedFile.name)}</b>. It will upload only after Save Vehicle.</span>`;
                return;
            }
            if (fileData.fileUrl || fileData.filePath) {
                const label = fileData.originalName || fileData.fileName || 'Vehicle image';
                const link = fileData.fileUrl ? `<a href="${escapeHtml(fileData.fileUrl)}" target="_blank" rel="noopener">View image</a>` : '';
                info.innerHTML = `Uploaded: <b>${escapeHtml(label)}</b>${link ? ` · ${link}` : ''}`;
            } else {
                info.textContent = 'Choose image. It will be stored after Save Vehicle.';
            }
        }

        function renderDocFileInfo(row, fileData = {}, selectedFile = null) {
            const info = $('.docUploadInfo', row);
            if (!info) return;
            if (selectedFile) {
                info.innerHTML = `<span class="pending-upload">Selected: <b>${escapeHtml(selectedFile.name)}</b>. It will upload only after Save Vehicle.</span>`;
                return;
            }
            if (fileData.fileUrl || fileData.filePath) {
                const label = fileData.originalName || fileData.fileName || 'Uploaded document';
                const link = fileData.fileUrl ? `<a href="${escapeHtml(fileData.fileUrl)}" target="_blank" rel="noopener">View file</a>` : '';
                info.innerHTML = `Uploaded: <b>${escapeHtml(label)}</b>${link ? ` · ${link}` : ''}`;
            } else {
                info.textContent = 'Choose image/PDF. It will be stored after Save Vehicle.';
            }
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
                if (hint) hint.textContent = 'Select fuel type to load latest active rate.';
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

        function addFuelRow(row = {}) {
            const wrapper = $('#vehicleFuelRows');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row fuel-row';
            const fuelOptions = [''].concat(fuelTypes);
            div.innerHTML = `
                <div class="field">
                    <label>Fuel Type</label>
                    <select class="fuelType">${fuelOptions.map((type) => `<option value="${escapeHtml(type)}" ${row.type === type ? 'selected' : ''}>${escapeHtml(type || 'Select fuel type')}</option>`).join('')}</select>
                </div>
                <div class="field">
                    <label>Fuel Priority</label>
                    <select class="fuelPriority">
                        ${['Primary', 'Secondary', 'Tertiary'].map((priority) => `<option value="${priority}" ${row.priority === priority ? 'selected' : ''}>${priority}</option>`).join('')}
                    </select>
                </div>
                <div class="field">
                    <label>Default Rate</label>
                    <input class="fuelRate" type="number" placeholder="Auto from latest active fuel price" value="${escapeHtml(row.rate || '')}" readonly>
                    <small class="upload-meta fuelRateHint">Select fuel type to load latest active rate.</small>
                </div>
                <button type="button" class="mini-btn remove-row">Remove</button>`;
            wrapper.appendChild(div);
            if (wrapper.children.length === 1 && !row.priority) div.querySelector('.fuelPriority').value = 'Primary';
            if (row.type && !row.rate) updateFuelRate(div, true);
            if (row.type && row.rate) updateFuelRate(div, false);
        }

        function addDocRow(row = {}) {
            const wrapper = $('#vehicleDocRows');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row doc-row';
            const defaultDocOptions = [''].concat(docTemplates);
            const fileData = normalizeFileData(row.file || row);
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
                <div class="field">
                    <label>Upload Picture / File</label>
                    <input class="docFile" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf">
                    <input class="docFileData" type="hidden" value="${escapeHtml(JSON.stringify(fileData || {}))}">
                    <small class="upload-meta docUploadInfo"></small>
                </div>
                <button type="button" class="mini-btn remove-row">Remove</button>`;
            wrapper.appendChild(div);
            renderDocFileInfo(div, fileData);
        }

        function validatePendingFiles(vehicleImageFile = null, documentFiles = {}) {
            if (vehicleImageFile) {
                const imageExt = String(vehicleImageFile.name || '').split('.').pop().toLowerCase();
                if (!['jpg', 'jpeg', 'png', 'webp'].includes(imageExt)) {
                    toast('Vehicle image must be JPG, JPEG, PNG or WEBP.');
                    return false;
                }
                if (vehicleImageFile.size > 5 * 1024 * 1024) {
                    toast('Vehicle image must be 5 MB or smaller.');
                    return false;
                }
            }

            for (const file of Object.values(documentFiles || {})) {
                if (!file) continue;
                const ext = String(file.name || '').split('.').pop().toLowerCase();
                if (!['jpg', 'jpeg', 'png', 'webp', 'pdf'].includes(ext)) {
                    toast('Vehicle documents must be JPG, JPEG, PNG, WEBP or PDF.');
                    return false;
                }
                if (file.size > 5 * 1024 * 1024) {
                    toast('Each vehicle document must be 5 MB or smaller.');
                    return false;
                }
            }
            return true;
        }

        function hasPendingFiles(bundle = {}) {
            return Boolean(bundle.imageFile) || Object.values(bundle.documentFiles || {}).some(Boolean);
        }

        async function syncVehicles(rows, filesByVehicle = {}) {
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

                if (!response.ok) {
                    let message = 'Vehicle could not be saved.';
                    try {
                        const error = await response.json();
                        message = error.message || Object.values(error.errors || {}).flat().join(' ') || message;
                    } catch (_) {}
                    throw new Error(message);
                }

                return await response.json().catch(() => ({ ok: true }));
            } catch (error) {
                toast(error.message || 'Vehicle could not be saved.');
                return { ok: false, syncFailed: true, message: error.message };
            }
        }

        function resetForm(withId = true) {
            $$('#vehicleAddPage input:not([type=radio]):not([type=file]):not([type=hidden]), #vehicleAddPage textarea').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage input[type=file]').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage input[type=hidden]').forEach((input) => { input.value = ''; });
            $$('#vehicleAddPage select').forEach((select) => { select.selectedIndex = 0; });
            setUsage('');
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            if (withId) setValue('#vehicleId', uid());
            setValue('#rent', 0);
            updateSubCategory('');
            addFuelRow({ priority: 'Primary' });
            docTemplates.forEach((doc) => addDocRow({ name: doc, reminder: docReminders[0] || '' }));
            renderVehicleImageInfo({});
        }

        function collectVehicle() {
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
                    driver: value('#driver'),
                    rent: value('#rent'),
                    notes: value('#notes'),
                    image: parseFileDataInput('#vehicleImageData'),
                    fuels: $$('.fuel-row').map((row) => ({
                        type: $('.fuelType', row)?.value || '',
                        priority: $('.fuelPriority', row)?.value || '',
                        rate: $('.fuelRate', row)?.value || '',
                    })).filter((fuel) => fuel.type || fuel.rate),
                    docs,
                    status: 'Active',
                },
                imageFile: selectedVehicleImageFile(),
                documentFiles,
            };
        }

        function validateVehicle(vehicle) {
            if (!vehicle.name || !vehicle.regNo || !vehicle.vendor || !vehicle.model || !vehicle.engineNo || !vehicle.category || !vehicle.usage) {
                toast('Please fill all required vehicle information.');
                return false;
            }
            const validFuels = (vehicle.fuels || []).filter((fuel) => fuel.type);
            if (!validFuels.length) {
                toast('Please add at least one fuel type.');
                return false;
            }
            if (!validFuels.some((fuel) => fuel.priority === 'Primary')) {
                toast('Please mark one fuel as Primary.');
                return false;
            }
            if (validFuels.some((fuel) => !fuel.rate)) {
                toast('Default rate is missing. Add an active fuel price first, then select the fuel type.');
                return false;
            }
            const existingRegistration = vehicles.find((item) => String(item.regNo || '').toLowerCase() === vehicle.regNo.toLowerCase() && item.id !== vehicle.id);
            if (existingRegistration) {
                toast('Registration number must be unique. This registration already exists.');
                return false;
            }
            return true;
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

        async function saveVehicle() {
            const form = collectVehicle();
            const vehicle = form.vehicle;
            if (!validateVehicle(vehicle)) return;
            if (!validatePendingFiles(form.imageFile, form.documentFiles)) return;

            const saveBtn = $('#saveVehicleBtn');
            const originalText = saveBtn?.textContent || '';
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = hasPendingFiles(form) ? 'Saving & uploading...' : 'Saving...';
            }

            const previousVehicles = cloneVehicles();
            const vehicleIndex = upsertVehicle(vehicle);
            const filesForSync = hasPendingFiles(form) ? { [vehicleIndex]: { imageFile: form.imageFile, documentFiles: form.documentFiles } } : {};
            const result = await syncVehicles(vehicles, filesForSync);

            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }

            if (result?.syncFailed || result?.ok === false) {
                vehicles = previousVehicles;
                renderTable();
                return;
            }

            if (Array.isArray(result?.rows)) vehicles = result.rows;
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
            if (sample.image) {
                setValue('#vehicleImageData', JSON.stringify(sample.image));
                renderVehicleImageInfo(sample.image);
            }
            setUsage(sample.usage);
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            (sample.fuels || []).forEach(addFuelRow);
            (sample.docs || []).forEach(addDocRow);
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
            if (vehicle.image) {
                setValue('#vehicleImageData', JSON.stringify(vehicle.image));
                renderVehicleImageInfo(vehicle.image);
            } else {
                setValue('#vehicleImageData', '');
                renderVehicleImageInfo({});
            }
            setUsage(vehicle.usage);
            $('#vehicleFuelRows').innerHTML = '';
            $('#vehicleDocRows').innerHTML = '';
            (vehicle.fuels || []).forEach(addFuelRow);
            if (!(vehicle.fuels || []).length) addFuelRow({ priority: 'Primary' });
            (vehicle.docs || []).forEach(addDocRow);
            setVisible('vehicleAddPage');
        }

        async function deleteVehicle(id) {
            if (!confirm('Delete this vehicle from the list?')) return;
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
        }

        function documentLinks(vehicle) {
            return (vehicle.docs || [])
                .filter((doc) => doc.file?.fileUrl || doc.file?.filePath)
                .map((doc) => {
                    const label = doc.name || doc.file?.originalName || 'Document';
                    return doc.file?.fileUrl ? `<a href="${escapeHtml(doc.file.fileUrl)}" target="_blank" rel="noopener">${escapeHtml(label)}</a>` : escapeHtml(label);
                })
                .join(', ');
        }

        function viewVehicle(id) {
            const vehicle = vehicles.find((item) => item.id === id);
            if (!vehicle) return;
            window.FleetmanDetailViewer?.show('Vehicle Details', vehicle);
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
            body.innerHTML = rows.length ? rows.map((vehicle) => {
                const docs = vehicle.docs || [];
                const docsWithFiles = docs.filter((doc) => doc.file?.filePath || doc.file?.fileUrl).length;
                const imageLink = vehicle.image?.fileUrl ? `<br><small><a href="${escapeHtml(vehicle.image.fileUrl)}" target="_blank" rel="noopener">View image</a></small>` : '';
                return `
                <tr>
                    <td><div class="vehicle-cell"><div class="vehicle-icon">🚗</div><div><b>${escapeHtml(vehicle.name)}</b><br><small>${escapeHtml(vehicle.id)} · ${escapeHtml(vehicle.model)}</small>${imageLink}</div></div></td>
                    <td>${escapeHtml(vehicle.regNo)}</td>
                    <td>${escapeHtml(vehicle.category)}<br><small>${escapeHtml(vehicle.subCategory || '')}</small></td>
                    <td>${(vehicle.fuels || []).map((item) => `<span class="badge soft">${escapeHtml(item.priority)}: ${escapeHtml(item.type)} · ${escapeHtml(item.rate || '0')}</span>`).join('')}</td>
                    <td>${escapeHtml(vehicle.driver || 'Not assigned')}</td>
                    <td>${docs.length} document(s)<br><small>${docsWithFiles} uploaded file(s)${docsWithFiles ? ` · ${documentLinks(vehicle)}` : ''}</small></td>
                    <td>${Number(vehicle.rent || 0).toLocaleString()} BDT</td>
                    <td><span class="badge ${vehicle.status === 'Active' ? 'ok' : 'warn'}">${escapeHtml(vehicle.status || '-')}</span></td>
                    <td><button type="button" class="mini-btn view-vehicle" data-id="${escapeHtml(vehicle.id)}">View</button><button type="button" class="mini-btn edit-vehicle" data-id="${escapeHtml(vehicle.id)}">Edit</button><button type="button" class="mini-btn danger delete-vehicle" data-id="${escapeHtml(vehicle.id)}">Delete</button></td>
                </tr>`;
            }).join('') : '<tr><td colspan="9" class="empty">No vehicles found.</td></tr>';

            $('#vehicleKpiTotal').textContent = vehicles.length;
            $('#vehicleKpiActive').textContent = vehicles.filter((vehicle) => vehicle.status === 'Active').length;
            $('#vehicleKpiDocs').textContent = vehicles.filter((vehicle) => (vehicle.docs || []).some((doc) => doc.expiry)).length;
            $('#vehicleKpiFuel').textContent = vehicles.filter((vehicle) => (vehicle.fuels || []).length > 1).length;
        }

        function exportCsv() {
            downloadCsv('fleetman-vehicle-list.csv', [
                ['Vehicle ID', 'Vehicle Name', 'Registration', 'Category', 'Fuels', 'Driver', 'Documents', 'Image', 'Status'],
                ...vehicles.map((vehicle) => [
                    vehicle.id,
                    vehicle.name,
                    vehicle.regNo,
                    vehicle.category,
                    (vehicle.fuels || []).map((fuel) => fuel.priority + ' ' + fuel.type + ' @ ' + fuel.rate).join(' | '),
                    vehicle.driver,
                    (vehicle.docs || []).map((doc) => [doc.name, doc.expiry, doc.file?.originalName || doc.file?.fileName || doc.file?.filePath || ''].filter(Boolean).join(' ')).join(' | '),
                    vehicle.image?.filePath || '',
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

        document.addEventListener('change', (event) => {
            const fuelSelect = event.target.closest('.fuelType');
            if (fuelSelect) updateFuelRate(fuelSelect.closest('.fuel-row'), true);

            const docFile = event.target.closest('.docFile');
            if (docFile) {
                const row = docFile.closest('.doc-row');
                if (row) renderDocFileInfo(row, parseFileDataInput('.docFileData', row), selectedDocFile(row));
            }

            if (event.target.matches('#image')) {
                renderVehicleImageInfo(parseFileDataInput('#vehicleImageData'), selectedVehicleImageFile());
            }
        });

        document.addEventListener('click', (event) => {
            const pageTarget = event.target.closest('[data-page-target]');
            if (pageTarget) { renderTable(); setVisible(pageTarget.dataset.pageTarget); }
            const remove = event.target.closest('.remove-row');
            if (remove) remove.parentElement.remove();
            const view = event.target.closest('.view-vehicle');
            if (view) viewVehicle(view.dataset.id);
            const edit = event.target.closest('.edit-vehicle');
            if (edit) editVehicle(edit.dataset.id);
            const del = event.target.closest('.delete-vehicle');
            if (del) deleteVehicle(del.dataset.id);
        });

        resetForm();
        renderTable();
        setVisible('vehicleListPage');
    }

    function initFuelPrices() {
        const STORAGE = 'fleetman_fuel_prices_v2';
        let prices = Array.isArray(records.fuel_prices) ? records.fuel_prices : (samples.fuel_prices || []);

        function saveStore() {
            syncResource('fuel_prices', prices);
        }

        function genId() {
            return 'FPR' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function resetForm() {
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
                if (!row.name) row.name = 'Draft Fuel Price';
            }
            if (!row.fuelType || !row.name || !row.price || !row.unit || !row.status || !row.effectiveDate) {
                toast('Please fill the required fuel price information, including fuel type and unit type.');
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

        resetForm();
        renderList();
        setVisible('fuelPriceListPage');
    }

    function initFuelRecharge() {
        const contracts = Array.isArray(data.contracts) ? data.contracts : [];
        const latestFuelRates = data.latestFuelRates || {};
        const fuelStations = Array.isArray(data.fuelStations) ? data.fuelStations : [];
        const photoRequirements = data.photoRequirements || [];
        const photoState = {};
        photoRequirements.forEach((photo) => { photoState[photo.key] = { captured: false, file: null, preview: '', capturedAt: '', displayTime: '', place: '' }; });
        let recharges = Array.isArray(records.fuel_recharges) ? records.fuel_recharges.slice() : [];
        let activeCameraKey = null;
        let activeStream = null;

        function endpoint() {
            return resources?.fuel_recharges?.sync || null;
        }

        async function syncFuelRecharges(rows, filesByRow = {}) {
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
            return String(name || '').toLowerCase().replace(/[^a-z0-9]+/g, '').replace('petroloctane', 'octane').replace('octanepetrol', 'octane');
        }

        function latestRateForFuel(fuelName) {
            const fuel = String(fuelName || '').trim();
            if (!fuel) return null;
            if (latestFuelRates[fuel]) return latestFuelRates[fuel];
            const needle = normalizeFuelName(fuel);
            const foundKey = Object.keys(latestFuelRates).find((key) => {
                const candidate = normalizeFuelName(key);
                return candidate === needle || candidate.includes(needle) || needle.includes(candidate);
            });
            return foundKey ? latestFuelRates[foundKey] : null;
        }

        function selectedContract() {
            return contracts.find((contract) => String(contract.id) === value('#contractSelect')) || null;
        }

        function selectedVehicle() {
            const contract = selectedContract();
            if (!contract) return null;
            return (contract.vehicles || []).find((vehicle) => String(vehicle.id) === value('#vehicleSelect')) || null;
        }

        function setFuelRateFields(fuelName, rateSelector, unitSelector) {
            const rateInfo = latestRateForFuel(fuelName);
            setValue(rateSelector, rateInfo?.price ? Number(rateInfo.price || 0).toFixed(2) : '');
            const unitElement = unitSelector ? $(unitSelector) : null;
            if (unitElement) unitElement.textContent = rateInfo?.unit || '';
            return rateInfo;
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
                clearVehicleSetup('Select a contract first. Vehicle list will load from that contract.');
                return;
            }

            const vehicles = contract.vehicles || [];
            if (!vehicles.length) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No vehicle assigned in this contract';
                select.appendChild(option);
                clearVehicleSetup('This contract has no vehicle assignment. Add a vehicle assignment in the Contract page first.');
                return;
            }

            if (vehicles.length > 1) {
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '- Select vehicle from contract -';
                select.appendChild(placeholder);
            }

            vehicles.forEach((vehicle) => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = vehicle.label || vehicle.name || vehicle.id;
                select.appendChild(option);
            });

            if (vehicles.length === 1) select.value = vehicles[0].id;
            updateVehicleSetup();
        }

        function clearVehicleSetup(note) {
            setValue('#primaryFuelName', '');
            setValue('#primaryStation', '');
            setValue('#primaryRate', '');
            setValue('#primaryQty', '');
            setValue('#primaryAmount', money(0));
            setValue('#secondaryFuelName', '');
            setValue('#secondaryStation', '');
            setValue('#secondaryRate', '');
            setValue('#secondaryQty', 0);
            setValue('#secondaryAmount', money(0));
            setValue('#startKm', '');
            setValue('#endKm', '');
            setValue('#totalKm', '');
            setValue('#mileage', '');
            $('#totalAmount').textContent = money(0);
            const toggle = $('#hasSecondaryFuel');
            if (toggle) {
                toggle.checked = false;
                toggle.disabled = true;
            }
            const secondaryBlock = $('#secondaryFuelBlock');
            if (secondaryBlock) secondaryBlock.style.display = 'none';
            $('#vehicleSetupNote').textContent = note || 'Vehicle setup is not loaded.';
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
            setValue('#primaryRate', primaryRate?.price ? Number(primaryRate.price || 0).toFixed(2) : '');
            setValue('#secondaryFuelName', secondaryFuel);
            setValue('#secondaryRate', secondaryRate?.price ? Number(secondaryRate.price || 0).toFixed(2) : '');
            setValue('#primaryQty', '');
            setValue('#secondaryQty', 0);
            setValue('#primaryAmount', money(0));
            setValue('#secondaryAmount', money(0));
            setValue('#primaryStation', '');
            setValue('#secondaryStation', '');
            setValue('#startKm', vehicle.startKm ?? vehicle.odo ?? vehicle.lastOdo ?? '');
            setValue('#endKm', '');
            setValue('#totalKm', '');
            setValue('#mileage', '');
            const primaryUnit = primaryRate?.unit || vehicle.primaryUnit || '';
            const secondaryUnit = secondaryRate?.unit || vehicle.secondaryUnit || '';
            const primaryQtyHint = $('#primaryQtyHint');
            const secondaryQtyHint = $('#secondaryQtyHint');
            const primaryRateHint = $('#primaryRateHint');
            const secondaryRateHint = $('#secondaryRateHint');
            if (primaryQtyHint) primaryQtyHint.textContent = primaryUnit ? `Enter quantity in ${primaryUnit}.` : 'Enter quantity.';
            if (secondaryQtyHint) secondaryQtyHint.textContent = secondaryUnit ? `Enter quantity in ${secondaryUnit}.` : 'Enter quantity.';
            if (primaryRateHint) primaryRateHint.textContent = primaryRate?.effectiveDate ? `Latest active price from ${primaryRate.effectiveDate}.` : 'No latest active price found.';
            if (secondaryRateHint) secondaryRateHint.textContent = secondaryFuel ? (secondaryRate?.effectiveDate ? `Latest active price from ${secondaryRate.effectiveDate}.` : 'No latest active price found.') : 'No secondary fuel configured.';

            const toggle = $('#hasSecondaryFuel');
            const secondaryAvailable = Boolean(secondaryFuel);
            if (toggle) {
                toggle.disabled = !secondaryAvailable;
                toggle.checked = false;
            }
            const secondaryBlock = $('#secondaryFuelBlock');
            if (secondaryBlock) secondaryBlock.style.display = 'none';

            const primaryRateText = primaryRate?.price ? `৳ ${Number(primaryRate.price).toLocaleString('en-BD')} ${primaryRate.unit || ''}` : 'No active fuel price found';
            const secondaryRateText = secondaryAvailable
                ? (secondaryRate?.price ? `৳ ${Number(secondaryRate.price).toLocaleString('en-BD')} ${secondaryRate.unit || ''}` : 'No active fuel price found')
                : 'No secondary fuel configured';
            const driverText = vehicle.driver ? ` Assigned driver: ${vehicle.driver}.` : '';
            const odoText = (vehicle.startKm || vehicle.odo || vehicle.lastOdo) ? ` Start KM: ${vehicle.startKm || vehicle.odo || vehicle.lastOdo}.` : '';

            $('#vehicleSetupNote').textContent = `Vehicle setup loaded from vehicle table. Primary: ${primaryFuel || 'Not set'} (${primaryRateText}). Secondary: ${secondaryFuel || 'Not set'} (${secondaryRateText}).${driverText}${odoText}`;
            recalculate();
        }

        function rechargeMileageQuantity(primaryName, primaryQty, secondaryName, secondaryQty) {
            const primaryIsGas = /cng|lpg|gas/i.test(primaryName || '');
            const secondaryIsGas = /cng|lpg|gas/i.test(secondaryName || '');
            const liquidQty = (primaryIsGas ? 0 : primaryQty) + (secondaryIsGas ? 0 : secondaryQty);
            return liquidQty > 0 ? liquidQty : primaryQty + secondaryQty;
        }

        function recalculate() {
            const primaryQty = Number(value('#primaryQty') || 0);
            const secondaryQty = $('#hasSecondaryFuel')?.checked ? Number(value('#secondaryQty') || 0) : 0;
            const primaryAmount = primaryQty * Number(value('#primaryRate') || 0);
            const hasSecondary = $('#hasSecondaryFuel')?.checked;
            const secondaryAmount = hasSecondary ? secondaryQty * Number(value('#secondaryRate') || 0) : 0;
            setValue('#primaryAmount', money(primaryAmount));
            setValue('#secondaryAmount', money(secondaryAmount));
            $('#totalAmount').textContent = money(primaryAmount + secondaryAmount);

            const startKm = Number(value('#startKm') || 0);
            const endKm = Number(value('#endKm') || 0);
            const totalKm = startKm > 0 && endKm >= startKm ? endKm - startKm : 0;
            setValue('#totalKm', totalKm > 0 ? totalKm : '');

            const mileageQty = rechargeMileageQuantity(value('#primaryFuelName'), primaryQty, value('#secondaryFuelName'), secondaryQty);
            const mileageValue = totalKm > 0 && mileageQty > 0 ? totalKm / mileageQty : 0;
            setValue('#mileage', mileageValue > 0 ? mileageValue.toFixed(2) : '');
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
                    <div class="photo-meta"><div class="meta-row"><small>Date & Time</small><b class="cap-time">Not captured yet</b></div><div class="meta-row"><small>Place</small><b class="cap-place">Not captured yet</b></div></div>
                </div>`).join('');
            bindPhotoEvents();
        }

        function ensureCameraModal() {
            let modal = $('#fuelCameraModal');
            if (modal) return modal;
            modal = document.createElement('div');
            modal.id = 'fuelCameraModal';
            modal.className = 'fuel-camera-modal hidden';
            modal.innerHTML = `
                <div class="fuel-camera-panel">
                    <div class="fuel-camera-head"><strong>Take Live Photo</strong><button class="mini-btn" type="button" id="fuelCameraCloseBtn">Close</button></div>
                    <video id="fuelCameraVideo" autoplay playsinline></video>
                    <canvas id="fuelCameraCanvas" class="hidden"></canvas>
                    <div class="fuel-camera-actions">
                        <button class="btn green" type="button" id="fuelCameraCaptureBtn">Capture Photo</button>
                    </div>
                    <p class="fuel-camera-note">Camera and location permission may be requested by the browser/device. Gallery upload is not used here.</p>
                </div>`;
            document.body.appendChild(modal);
            $('#fuelCameraCloseBtn', modal)?.addEventListener('click', closeCamera);
            $('#fuelCameraCaptureBtn', modal)?.addEventListener('click', captureCameraPhoto);
            return modal;
        }

        async function openCamera(key) {
            if (!navigator.mediaDevices?.getUserMedia) {
                toast('Live camera capture is not supported in this browser/device.');
                return;
            }
            activeCameraKey = key;
            const modal = ensureCameraModal();
            const video = $('#fuelCameraVideo', modal);
            try {
                activeStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
                video.srcObject = activeStream;
                modal.classList.remove('hidden');
                toast('Camera opened. Capture the required photo.');
            } catch (error) {
                activeCameraKey = null;
                toast('Camera permission denied or camera unavailable: ' + (error.message || 'Unable to open camera.'));
            }
        }

        function closeCamera() {
            if (activeStream) {
                activeStream.getTracks().forEach((track) => track.stop());
                activeStream = null;
            }
            const modal = $('#fuelCameraModal');
            const video = $('#fuelCameraVideo');
            if (video) video.srcObject = null;
            if (modal) modal.classList.add('hidden');
            activeCameraKey = null;
        }

        function captureCameraPhoto() {
            if (!activeCameraKey) return;
            const modal = $('#fuelCameraModal');
            const video = $('#fuelCameraVideo', modal);
            const canvas = $('#fuelCameraCanvas', modal);
            if (!video || !canvas || !video.videoWidth) {
                toast('Camera is still loading. Please try again in a moment.');
                return;
            }
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(async (blob) => {
                if (!blob) {
                    toast('Could not capture photo. Please try again.');
                    return;
                }
                const key = activeCameraKey;
                const capturedAt = new Date();
                const file = new File([blob], `${key}-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const preview = URL.createObjectURL(blob);
                photoState[key] = {
                    ...(photoState[key] || {}),
                    captured: true,
                    file,
                    preview,
                    capturedAt: capturedAt.toISOString(),
                    displayTime: capturedAt.toLocaleString(),
                    place: 'Getting place name...',
                };
                updatePhotoCard(key);
                updateCounter();
                closeCamera();
                const location = await requestCurrentPlace();
                photoState[key] = { ...(photoState[key] || {}), ...location, place: location.place || 'Location unavailable' };
                updatePhotoCard(key);
                toast('Photo captured with time and place.');
            }, 'image/jpeg', 0.9);
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
                    photoState[key] = { captured: false, file: null, preview: '', capturedAt: '', displayTime: '', place: '' };
                    updatePhotoCard(key);
                    updateCounter();
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

        function resetFuelRechargeForm() {
            closeCamera();
            setValue('#contractSelect', '');
            updateVehicles();
            Object.keys(photoState).forEach((key) => {
                if (photoState[key]?.preview) URL.revokeObjectURL(photoState[key].preview);
                photoState[key] = { captured: false, file: null, preview: '', capturedAt: '', displayTime: '', place: '' };
            });
            $$('.photo-card').forEach((card) => updatePhotoCard(card.dataset.key));
            updateCounter();
            setValue('#primaryStation', '');
            setValue('#secondaryStation', '');
            setValue('#endKm', '');
            setValue('#totalKm', '');
            setValue('#mileage', '');
            setValue('#rechargeRemarks', '');
            const submitTime = $('#submitTime');
            const submitPlace = $('#submitPlace');
            const submitPlaceDetail = $('#submitPlaceDetail');
            if (submitTime) submitTime.textContent = 'Not submitted';
            if (submitPlace) submitPlace.textContent = 'Not submitted';
            if (submitPlaceDetail) submitPlaceDetail.textContent = 'Place name will be saved if location is allowed.';
            recalculate();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function collectPhotoFiles() {
            return Object.fromEntries(Object.entries(photoState)
                .filter(([, state]) => state.captured && state.file)
                .map(([key, state]) => [key, state.file]));
        }

        function collectRecharge(statusOverride, submitLocation = null) {
            const contract = selectedContract();
            const vehicle = selectedVehicle();
            const rechargeId = nextRechargeId();
            const primaryName = value('#primaryFuelName') || vehicle?.primary || '';
            const secondaryEnabled = $('#hasSecondaryFuel')?.checked;
            const secondaryName = secondaryEnabled ? (value('#secondaryFuelName') || vehicle?.secondary || '') : '';
            const primaryQty = Number(value('#primaryQty') || 0);
            const secondaryQty = secondaryEnabled ? Number(value('#secondaryQty') || 0) : 0;
            const primaryRate = Number(value('#primaryRate') || 0);
            const secondaryRate = secondaryEnabled ? Number(value('#secondaryRate') || 0) : 0;
            const primaryAmount = primaryQty * primaryRate;
            const secondaryAmount = secondaryQty * secondaryRate;
            const endKm = Number(value('#endKm') || 0);
            const startKm = Number(value('#startKm') || vehicle?.startKm || vehicle?.odo || vehicle?.lastOdo || 0);
            const totalKm = startKm > 0 && endKm >= startKm ? endKm - startKm : 0;
            const primaryLower = primaryName.toLowerCase();
            const secondaryLower = secondaryName.toLowerCase();
            const primaryIsGas = /cng|lpg|gas/.test(primaryLower);
            const secondaryIsGas = /cng|lpg|gas/.test(secondaryLower);
            const mileageQty = rechargeMileageQuantity(primaryName, primaryQty, secondaryName, secondaryQty);
            const diesel = (primaryLower.includes('diesel') ? primaryQty : 0) + (secondaryLower.includes('diesel') ? secondaryQty : 0);
            const octane = ((primaryLower.includes('octane') || primaryLower.includes('petrol')) ? primaryQty : 0) + ((secondaryLower.includes('octane') || secondaryLower.includes('petrol')) ? secondaryQty : 0);
            const gas = (primaryIsGas ? primaryAmount : 0) + (secondaryIsGas ? secondaryAmount : 0);

            return {
                rechargeId,
                date: new Date().toISOString().slice(0, 10),
                contractId: contract?.contractId || contract?.id || '',
                contract: contract?.label || $('#contractSelect option:checked')?.textContent || '',
                contractLabel: contract?.label || '',
                vehicleId: vehicle?.id || value('#vehicleSelect'),
                vehicle: vehicle?.label || vehicle?.name || $('#vehicleSelect option:checked')?.textContent || '',
                vehicleLabel: vehicle?.label || '',
                car: vehicle?.label || vehicle?.name || '',
                driverId: vehicle?.driverId || '',
                driver: vehicle?.driver || 'Assigned Driver',
                driverName: vehicle?.driver || '',
                driverStart: '',
                driverEnd: '',
                totalTime: 0,
                primaryFuelName: primaryName,
                primaryStation: value('#primaryStation'),
                primaryFuelStation: value('#primaryStation'),
                fuelStation: value('#primaryStation'),
                primaryQty,
                primaryRate,
                primaryAmount,
                primaryFuelUnit: latestRateForFuel(primaryName)?.unit || vehicle?.primaryUnit || '',
                secondaryFuelName: secondaryName,
                secondaryStation: secondaryEnabled ? value('#secondaryStation') : '',
                secondaryFuelStation: secondaryEnabled ? value('#secondaryStation') : '',
                secondaryQty,
                secondaryRate,
                secondaryAmount,
                secondaryFuelUnit: secondaryName ? (latestRateForFuel(secondaryName)?.unit || vehicle?.secondaryUnit || '') : '',
                diesel,
                gas,
                octane,
                startKm,
                endKm,
                odoReading: endKm,
                totalKm,
                mileage: totalKm > 0 && mileageQty > 0 ? +(totalKm / mileageQty).toFixed(2) : 0,
                totalAmount: primaryAmount + secondaryAmount,
                status: statusOverride || 'Submitted',
                submittedBy: value('#submittedBy') || data.account?.name || 'Logged-in User',
                fuelType: secondaryEnabled && secondaryName ? `${primaryName} + ${secondaryName}` : primaryName,
                remarks: value('#rechargeRemarks'),
                photos: collectPhotoPayload(),
                submittedAt: new Date().toISOString(),
                submittedLocation: submitLocation || null,
            };
        }

        async function saveRecharge(statusOverride, submitLocation = null) {
            const row = collectRecharge(statusOverride, submitLocation);
            const previousRows = JSON.parse(JSON.stringify(recharges || []));
            recharges.unshift(row);
            const files = collectPhotoFiles();
            const result = await syncFuelRecharges(recharges, Object.keys(files).length ? { 0: files } : {});
            if (result?.syncFailed || result?.ok === false) {
                recharges = previousRows;
                return null;
            }
            if (Array.isArray(result?.rows)) recharges = result.rows;
            return row;
        }

        function validateBeforeSubmit(requirePhotos = true) {
            if (!value('#contractSelect') || !value('#vehicleSelect')) {
                toast('Please select contract and vehicle.');
                return false;
            }
            if (!value('#primaryFuelName')) {
                toast('Primary fuel is missing in the selected vehicle setup.');
                return false;
            }
            if (Number(value('#primaryRate') || 0) <= 0) {
                toast('Latest active fuel price is missing for the selected primary fuel. Add it in Fuel Prices first.');
                return false;
            }
            if (requirePhotos) {
                const missingRequiredPhoto = photoRequirements.some((photo) => photo.required && !photoState[photo.key]?.captured);
                if (missingRequiredPhoto) {
                    toast('Please take all required live photos: Vehicle, Fuel/Dispenser, and ODO Meter.');
                    return false;
                }
            }
            if (!value('#primaryStation')) {
                toast('Please select or type the primary fuel station.');
                return false;
            }
            if (Number(value('#primaryQty') || 0) <= 0) {
                toast('Please enter main fuel quantity.');
                return false;
            }
            if ($('#hasSecondaryFuel')?.checked) {
                if (!value('#secondaryStation')) {
                    toast('Please select or type the secondary fuel station.');
                    return false;
                }
                if (Number(value('#secondaryRate') || 0) <= 0) {
                    toast('Latest active fuel price is missing for the selected secondary fuel.');
                    return false;
                }
                if (Number(value('#secondaryQty') || 0) <= 0) {
                    toast('Second fuel is selected. Please enter second fuel quantity.');
                    return false;
                }
            }
            if (Number(value('#endKm') || 0) <= 0) {
                toast('Please enter end KM / latest ODO reading.');
                return false;
            }
            return true;
        }

        async function submitRecharge() {
            if (!validateBeforeSubmit(true)) return;
            const submitBtn = $('#submitRechargeBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }
            $('#submitTime').textContent = new Date().toLocaleString();
            const submitPlace = $('#submitPlace');
            const submitDetail = $('#submitPlaceDetail');
            submitPlace.textContent = 'Getting place name...';
            submitDetail.textContent = 'Browser/device may ask for location permission.';
            const location = await requestCurrentPlace();
            submitPlace.textContent = location.place || 'Location unavailable';
            submitDetail.textContent = location.error || 'Place name and time saved with the fuel recharge entry.';
            const saved = await saveRecharge('Submitted', location);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            }
            if (saved) {
                resetFuelRechargeForm();
                toast('Fuel recharge submitted successfully. Form reset for next entry.');
            }
        }

        async function saveDraft() {
            if (!value('#contractSelect') || !value('#vehicleSelect')) {
                toast('Please select contract and vehicle before saving draft.');
                return;
            }
            const draftBtn = $('#draftRechargeBtn');
            if (draftBtn) {
                draftBtn.disabled = true;
                draftBtn.textContent = 'Saving...';
            }
            const saved = await saveRecharge('Draft', null);
            if (draftBtn) {
                draftBtn.disabled = false;
                draftBtn.textContent = 'Save Draft';
            }
            if (saved) {
                resetFuelRechargeForm();
                toast('Draft saved to database. Form reset for next entry.');
            }
        }

        $('#contractSelect')?.addEventListener('change', updateVehicles);
        $('#vehicleSelect')?.addEventListener('change', updateVehicleSetup);
        $('#primaryQty')?.addEventListener('input', recalculate);
        $('#secondaryQty')?.addEventListener('input', recalculate);
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
            if (!this.checked) { setValue('#secondaryQty', 0); setValue('#secondaryStation', ''); }
            recalculate();
        });
        $('#resetRechargeBtn')?.addEventListener('click', resetFuelRechargeForm);
        $('#draftRechargeBtn')?.addEventListener('click', saveDraft);
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
    const records = data.records || samples || {};
    const resources = data.resources || {};

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function syncResource(resource, rows) {
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

            if (!response.ok) {
                let message = 'Database sync failed. Please check required fields and server logs.';
                try {
                    const error = await response.json();
                    message = error.message || Object.values(error.errors || {}).flat().join(' ') || message;
                } catch (_) {}
                throw new Error(message);
            }

            return await response.json().catch(() => ({ ok: true }));
        } catch (error) {
            toast(error.message || 'Saved locally in screen state, but database sync failed. Check server connection.');
            return { ok: false, syncFailed: true, message: error.message };
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

        function genId() {
            return 'VND' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function hasPendingFiles(documentFilesByParty = {}) {
            return Object.values(documentFilesByParty).some((documentMap) => Object.values(documentMap || {}).some(Boolean));
        }

        function validatePendingFiles(documentFiles = {}) {
            const allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            for (const file of Object.values(documentFiles || {})) {
                if (!file) continue;
                const ext = String(file.name || '').split('.').pop().toLowerCase();
                if (!allowed.includes(ext)) {
                    toast('Only JPG, JPEG, PNG, WEBP or PDF documents are allowed.');
                    return false;
                }
                if (file.size > 5 * 1024 * 1024) {
                    toast('Each document file must be 5 MB or smaller.');
                    return false;
                }
            }
            return true;
        }

        async function syncParties(rows, documentFilesByParty = {}) {
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
                <div class="field"><label>Name <span class="req">*</span></label><input class="partyContactName" placeholder="Example: Md. Karim" value="${escapeHtml(row.name || '')}"></div>
                <div class="field"><label>Role</label><input class="partyContactRole" placeholder="Example: Manager" value="${escapeHtml(row.role || '')}"></div>
                <div class="field"><label>Phone Number <span class="req">*</span></label><input class="partyContactPhone" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone || '')}"></div>
                <div class="field"><label>Email / WhatsApp</label><input class="partyContactMeta" placeholder="Email or WhatsApp" value="${escapeHtml(meta)}"></div>
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

        function renderDocumentFileInfo(row, fileData = {}, selectedFile = null) {
            const info = $('.partyDocUploadInfo', row);
            if (!info) return;

            if (selectedFile) {
                info.innerHTML = `<span class="pending-upload">Selected: <b>${escapeHtml(selectedFile.name)}</b>. It will upload only after Save Vendor / Party.</span>`;
                return;
            }

            if (fileData.fileUrl || fileData.filePath) {
                const label = fileData.originalName || fileData.fileName || 'Uploaded document';
                const link = fileData.fileUrl ? `<a href="${escapeHtml(fileData.fileUrl)}" target="_blank" rel="noopener">View file</a>` : '';
                info.innerHTML = `Uploaded: <b>${escapeHtml(label)}</b>${link ? ` · ${link}` : ''}`;
            } else {
                info.textContent = 'Choose image/PDF. It will be stored only when this vendor/party is saved.';
            }
        }

        function addDocument(row = {}) {
            const wrapper = $('#partyDocuments');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row document-row';
            const docOptions = [''].concat(partyDocumentTemplates);
            const fileData = normalizeDocumentFile(row);
            div.innerHTML = `
                <div class="field"><label>Document Name</label><select class="partyDocName">${docOptions.map((doc) => `<option value="${escapeHtml(doc)}" ${row.name === doc ? 'selected' : ''}>${escapeHtml(doc || 'Select document')}</option>`).join('')}</select></div>
                <div class="field"><label>Reference No.</label><input class="partyDocNumber" placeholder="Optional" value="${escapeHtml(row.number || '')}"></div>
                <div class="field"><label>Expiry Date</label><input class="partyDocExpiry" type="date" value="${escapeHtml(row.expiry || '')}"></div>
                <div class="field"><label>Upload Picture / File</label><input class="partyDocFile" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf"><input class="partyDocFileData" type="hidden" value="${escapeHtml(JSON.stringify(fileData || {}))}"><small class="upload-meta partyDocUploadInfo"></small></div>
                <button type="button" class="mini-btn danger remove-row">Remove</button>`;
            wrapper.appendChild(div);
            renderDocumentFileInfo(div, fileData);
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

            const documentFiles = {};
            const documents = [];
            $$('.document-row').forEach((row) => {
                const existingFile = fileDataFromRow(row);
                const pendingFile = selectedFileFromRow(row);
                const doc = {
                    name: $('.partyDocName', row)?.value.trim() || '',
                    number: $('.partyDocNumber', row)?.value.trim() || '',
                    expiry: $('.partyDocExpiry', row)?.value || '',
                    file: existingFile,
                };

                if (doc.name || doc.number || doc.expiry || doc.file?.filePath || doc.file?.fileUrl || pendingFile) {
                    const documentIndex = documents.length;
                    documents.push(doc);
                    if (pendingFile) documentFiles[documentIndex] = pendingFile;
                }
            });

            return {
                party: {
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
                },
                documentFiles,
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
            const form = collect(statusOverride);
            const party = form.party;

            if (statusOverride === 'Draft') {
                if (!party.partyName) party.partyName = 'Draft Vendor / Party';
                if (!party.partyType) party.partyType = 'Other';
            } else if (!validate(party)) {
                return;
            }

            if (!validatePendingFiles(form.documentFiles)) return;

            const saveBtn = statusOverride === 'Draft' ? $('#savePartyDraftBtn') : $('#savePartyBtn');
            const originalText = saveBtn?.textContent || '';
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = hasPendingFiles({ 0: form.documentFiles }) ? 'Saving & uploading...' : 'Saving...';
            }

            const previousParties = cloneParties();
            const partyIndex = upsert(party);
            const filesForSync = hasPendingFiles({ [partyIndex]: form.documentFiles }) ? { [partyIndex]: form.documentFiles } : {};
            const result = await saveStore(filesForSync);

            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }

            if (result?.syncFailed || result?.ok === false) {
                parties = previousParties;
                renderList();
                return;
            }

            if (Array.isArray(result?.rows)) parties = result.rows;

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
            if (!confirm('Delete this vendor / party from list?')) return;
            const previousParties = cloneParties();
            parties = parties.filter((party) => party.partyId !== id);
            saveStore().then((result) => {
                if (result?.syncFailed || result?.ok === false) {
                    parties = previousParties;
                    renderList();
                    return;
                }
                renderList();
                toast('Vendor / Party deleted.');
            });
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
                <td><div class="party-cell"><div class="party-icon">🤝</div><div><b>${escapeHtml(party.partyName)}</b><br><small>${escapeHtml(party.partyId)}</small></div></div></td>
                <td><span class="badge soft">${escapeHtml(party.partyType || '-')}</span></td>
                <td>${escapeHtml(party.phone || '-')}<br><small>${escapeHtml(party.email || '')}</small></td>
                <td><b>${escapeHtml(main.name || '-')}</b><br><small>${escapeHtml(main.phone || '')}${(party.contacts || []).length > 1 ? ` · +${(party.contacts || []).length - 1} more` : ''}</small></td>
                <td>${escapeHtml(party.paymentTerms || '-')}</td>
                <td>${(party.documents || []).length} document(s)<br><small>${uploadedCount} uploaded file(s)</small></td>
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
        $('#newPartyBtn')?.addEventListener('click', () => { resetForm(); setVisible('vendorAddPage'); });
        $('#exportPartiesBtn')?.addEventListener('click', exportParties);
        $('#applyPartyFiltersBtn')?.addEventListener('click', renderList);
        $('#clearPartyFiltersBtn')?.addEventListener('click', clearFilters);
        ['#partySearch', '#partyFilterType', '#partyFilterStatus', '#partyFilterTerms'].forEach((selector) => $(selector)?.addEventListener('input', renderList));
        document.addEventListener('change', (event) => {
            const input = event.target.closest('.partyDocFile');
            if (!input) return;
            const row = input.closest('.document-row');
            if (row) renderDocumentFileInfo(row, fileDataFromRow(row), selectedFileFromRow(row));
        });
        document.addEventListener('click', (event) => {
            if (event.target.closest('.remove-row')) event.target.closest('.repeat-row')?.remove();
            const view = event.target.closest('.view-party');
            if (view) viewParty(view.dataset.id);
            const edit = event.target.closest('.edit-party');
            if (edit) editParty(edit.dataset.id);
            const del = event.target.closest('.delete-party');
            if (del) deleteParty(del.dataset.id);
        });

        resetForm();
        renderList();
        setVisible('vendorListPage');
    }

    function initTrips() {
        const RECENT_KEY = 'fleetman_trip_selector_recent_v2';
        let trips = Array.isArray(records.trips) ? records.trips : (samples.trips || []);
        const vehicles = (tripMasters.vehicles || []).map((item) => ({
            id: String(item.id || ''),
            name: String(item.name || ''),
            label: String(item.label || [item.id, item.name].filter(Boolean).join(' - ')),
            type: String(item.type || item.category || item.subCategory || 'Vehicle'),
            note: String(item.note || [item.regNo, item.model, item.status].filter(Boolean).join(' • ') || 'From vehicle table'),
            status: String(item.status || ''),
            regNo: String(item.regNo || ''),
            model: String(item.model || ''),
        })).filter((item) => item.label || item.id || item.name);
        const drivers = (tripMasters.drivers || []).map((item) => ({
            id: String(item.id || ''),
            name: String(item.name || ''),
            label: String(item.label || [item.id, item.name].filter(Boolean).join(' - ')),
            phone: String(item.phone || item.contact || ''),
            area: String(item.area || item.presentAddress || item.duty || 'Driver'),
            duty: String(item.duty || ''),
            status: String(item.status || ''),
            note: String(item.note || [item.phone || item.contact, item.duty, item.status].filter(Boolean).join(' • ') || 'From driver table'),
        })).filter((item) => item.label || item.id || item.name);
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

        function saveStore() { syncResource('trips', trips); }
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

        function vehicleValue(item) { return item?.label || [item?.id, item?.name].filter(Boolean).join(' - '); }
        function driverValue(item) { return item?.label || [item?.id, item?.name].filter(Boolean).join(' - '); }
        function findVehicle(selected) { return vehicles.find((item) => vehicleValue(item) === selected || item.id === selected); }
        function findDriver(selected) { return drivers.find((item) => driverValue(item) === selected || item.id === selected); }

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
                ? `<div><b>${escapeHtml(vehicleValue(vehicle))}</b><small>${escapeHtml([vehicle.type, vehicle.note].filter(Boolean).join(' • '))}</small></div><span>✓</span>`
                : selectedVehicle
                    ? `<div><b>${escapeHtml(selectedVehicle)}</b><small>This saved vehicle is not currently available in the vehicle table.</small></div><span>!</span>`
                    : '<div><b>No vehicle selected</b><small>Tap the button to search and choose from saved vehicles</small></div>';
            $('#tripDriverSummary').innerHTML = driver
                ? `<div><b>${escapeHtml(driverValue(driver))}</b><small>${escapeHtml([driver.phone, driver.area, driver.note].filter(Boolean).join(' • '))}</small></div><span>✓</span>`
                : selectedDriver
                    ? `<div><b>${escapeHtml(selectedDriver)}</b><small>This saved driver is not currently available in the driver table.</small></div><span>!</span>`
                    : '<div><b>No driver selected</b><small>Tap the button to search and choose from saved drivers</small></div>';
        }
        function renderRecentChips() {
            const recent = getRecent();
            $('#recentTripVehicleChips').innerHTML = (recent.vehicle || []).map((item) => `<button type="button" class="quick-chip ${selectedVehicle === item ? 'active' : ''}" data-recent-type="vehicle" data-value="${escapeHtml(item)}">${escapeHtml(item)}</button>`).join('');
            $('#recentTripDriverChips').innerHTML = (recent.driver || []).map((item) => `<button type="button" class="quick-chip ${selectedDriver === item ? 'active' : ''}" data-recent-type="driver" data-value="${escapeHtml(item)}">${escapeHtml(item)}</button>`).join('');
        }

        function openSelector(type) {
            selectorType = type;
            selectorTab = 'all';
            setValue('#tripSelectorSearch', '');
            $('#tripSelectorTitle').textContent = type === 'vehicle' ? 'Select Vehicle' : 'Select Driver';
            $('#tripSelectorSubtitle').textContent = type === 'vehicle' ? 'Search, filter, and choose from a large vehicle list' : 'Search, filter, and choose from a large driver list';
            const filter = $('#tripSelectorFilter');
            if (type === 'vehicle') {
                const types = [...new Set(vehicles.map((vehicle) => vehicle.type).filter(Boolean))];
                filter.innerHTML = '<option value="">All Types</option>' + types.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join('');
            } else {
                const areas = [...new Set(drivers.map((driver) => driver.area).filter(Boolean))];
                filter.innerHTML = '<option value="">All Areas</option>' + areas.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join('');
            }
            $('#tripSelectorOverlay').classList.add('show');
            setSelectorTab('all');
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
                    ? `${item.id} ${item.name} ${item.label} ${item.type} ${item.note} ${item.regNo} ${item.model} ${item.status}`
                    : `${item.id} ${item.name} ${item.label} ${item.phone} ${item.area} ${item.duty} ${item.status} ${item.note}`;
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
                const meta = selectorType === 'vehicle' ? [item.type, item.note].filter(Boolean).join(' • ') : [item.phone, item.area, item.note].filter(Boolean).join(' • ');
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
            const vehicle = findVehicle(selectedVehicle);
            const driver = findDriver(selectedDriver);
            return {
                tripId: value('#tripId'),
                startDate: value('#tripStartDate'),
                endDate: value('#tripEndDate'),
                vehicle: selectedVehicle,
                vehicleId: vehicle?.id || '',
                driver: selectedDriver,
                driverId: driver?.id || '',
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
            if (!findVehicle(trip.vehicle)) {
                toast('Please select a vehicle from the saved vehicle table.');
                return false;
            }
            if (!findDriver(trip.driver)) {
                toast('Please select a driver from the saved driver table.');
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
            toast('Existing trip data loaded.');
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
            if (!confirm('Delete this trip from the trip list?')) return;
            trips = trips.filter((trip) => trip.tripId !== id);
            saveStore();
            renderList();
            toast('Trip deleted.');
        }
        function viewTrip(id) {
            const trip = trips.find((item) => item.tripId === id);
            if (!trip) return;
            window.FleetmanDetailViewer?.show('Trip Details', trip);
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
            exportCsv(rows, 'fleetman-trip-list.csv');
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

        resetForm();
        renderList();
        setVisible('tripListPage');
    }


    function initDrivers() {
        const STORAGE = 'fleetman_drivers_v2';
        let drivers = Array.isArray(records.drivers) ? records.drivers : (samples.drivers || []);
        const docTemplates = options.driver_document_templates || [];
        const docReminders = options.document_reminders || [];
        const licenseWarnDays = 180;
        let docRowCounter = 0;

        async function syncDrivers(rows, driverIndex) {
            const endpoint = resources?.drivers?.sync;
            if (!endpoint) return { ok: true, skipped: true };

            const photoFile = $('#driverPhoto')?.files?.[0] || null;
            const docFileInputs = $$('#driverDocuments .driver-document-row .driverDocFile');
            const docFiles = docFileInputs.map((input) => input.files?.[0] || null).filter(Boolean);

            if (!photoFile && docFiles.length === 0) {
                return syncResource('drivers', rows);
            }

            const formData = new FormData();
            formData.append('rows', JSON.stringify(rows || []));

            if (photoFile) {
                formData.append(`driver_photo_files[${driverIndex}]`, photoFile);
            }

            docFileInputs.forEach((input, seqIdx) => {
                const file = input.files?.[0];
                if (file) {
                    formData.append(`driver_document_files[${driverIndex}][${seqIdx}]`, file);
                }
            });

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: formData,
            }).then(async (res) => {
                if (!res.ok) throw new Error((await res.json().catch(()=>({}))).message || 'Driver save failed.');
                return res.json();
            });
        }

        function saveStore(driverIndex){ syncDrivers(drivers, driverIndex); }
        function genId(){ return 'DVR' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }
        
        function renderDocFileInfo(infoContainer, fileData) {
            if (!infoContainer) return;
            const info = infoContainer.querySelector('.upload-meta');
            if (!info) return;
            if (fileData && (fileData.fileUrl || fileData.filePath)) {
                const label = fileData.originalName || fileData.fileName || 'Uploaded file';
                const link = fileData.fileUrl ? ` · <a href="${escapeHtml(fileData.fileUrl)}" target="_blank" rel="noopener">View file</a>` : '';
                info.innerHTML = `✅ Uploaded: <b>${escapeHtml(label)}</b>${link}`;
            } else {
                info.innerHTML = '';
            }
        }

        function addContact(row = {}) {
            const wrapper = $('#driverContacts');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row driver-contact-row';
            const typeValue = row.type || 'Personal';
            
            div.innerHTML = `
                <div class="field">
                    <label>Type <span class="req">*</span></label>
                    <select class="driverContactType">
                        <option value="Personal" ${typeValue === 'Personal' ? 'selected' : ''}>Personal</option>
                        <option value="Home" ${typeValue === 'Home' ? 'selected' : ''}>Home</option>
                        <option value="Relative" ${typeValue === 'Relative' ? 'selected' : ''}>Relative</option>
                    </select>
                </div>
                <div class="field">
                    <label>Phone Number <span class="req">*</span></label>
                    <input class="driverContactPhone" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone || '')}">
                </div>
                <div class="field rel-field" style="display: ${typeValue === 'Relative' ? 'block' : 'none'};">
                    <label>Relationship</label>
                    <input class="driverContactRel" placeholder="e.g. Brother, Wife" value="${escapeHtml(row.relationship || '')}">
                </div>
                <button type="button" class="mini-btn danger remove-row" style="align-self:flex-end">Remove</button>
            `;
            wrapper.appendChild(div);
            
            const typeSelect = $('.driverContactType', div);
            const relField = $('.rel-field', div);
            typeSelect.addEventListener('change', () => {
                relField.style.display = typeSelect.value === 'Relative' ? 'block' : 'none';
            });
        }

        function addDocument(row = {}) {
            const wrapper = $('#driverDocuments');
            if (!wrapper) return;
            const rowIdx = docRowCounter++;
            const existingFile = (row.file && typeof row.file === 'object' && (row.file.filePath || row.file.fileUrl)) ? row.file : null;
            const div = document.createElement('div');
            div.className = 'repeat-row driver-document-row';
            div.dataset.docIdx = rowIdx;
            if (existingFile) div.dataset.fileJson = JSON.stringify(existingFile);
            
            const docOptions = [''].concat(docTemplates);
            
            div.innerHTML = `
                <div class="field">
                    <label>Document Name</label>
                    <select class="driverDocName">${docOptions.map((doc) => `<option value="${escapeHtml(doc)}" ${row.name === doc ? 'selected' : ''}>${escapeHtml(doc || 'Select document')}</option>`).join('')}</select>
                </div>
                <div class="field">
                    <label>Upload File</label>
                    <input type="file" class="driverDocFile" accept="image/*,application/pdf" data-doc-idx="${rowIdx}">
                    <div class="upload-meta"></div>
                </div>
                <div class="field">
                    <label>Document No./Reference</label>
                    <input class="driverDocNumber" placeholder="Optional" value="${escapeHtml(row.number || '')}">
                </div>
                <div class="field">
                    <label>Expiry Date</label>
                    <input class="driverDocExpiry" type="date" value="${escapeHtml(row.expiry || '')}">
                </div>
                <div class="field">
                    <label>Reminder</label>
                    <select class="driverDocReminder">${docReminders.map((reminder) => `<option value="${escapeHtml(reminder)}" ${row.reminder === reminder ? 'selected' : ''}>${escapeHtml(reminder)}</option>`).join('')}</select>
                </div>
                <button type="button" class="mini-btn danger remove-row" style="align-self:flex-end">Remove</button>
            `;
            wrapper.appendChild(div);
            if (existingFile) renderDocFileInfo(div, existingFile);
        }

        function resetForm(){
            $$('#driverAddPage input, #driverAddPage select, #driverAddPage textarea').forEach((el)=>{ if(el.type==='radio') el.checked=false; else if(el.type==='file') el.value=''; else el.value=''; });
            docRowCounter = 0;
            setValue('#driverId', genId()); setValue('#driverOtRate', '50'); setValue('#driverWorkingHour', '270'); setValue('#driverSalaryTenure','Monthly'); setValue('#driverStatus','Active');
            $('#driverContacts').innerHTML=''; addContact(); addContact({type: 'Relative'});
            $('#driverDocuments').innerHTML=''; addDocument({name:'NID Scan Copy'}); addDocument({name:'Driving License Copy'});
        }

        function collect(statusOverride){
            const contacts = $$('#driverContacts .driver-contact-row').map((row) => ({
                type: $('.driverContactType', row).value,
                relationship: $('.driverContactRel', row).value.trim(),
                phone: $('.driverContactPhone', row).value.trim()
            })).filter(c => c.phone);

            const documents = $$('#driverDocuments .driver-document-row').map((domRow) => {
                let existingFile = {};
                try { existingFile = domRow.dataset.fileJson ? JSON.parse(domRow.dataset.fileJson) : {}; } catch (_) {}
                const hasNewFile = !!$('.driverDocFile', domRow)?.files?.[0];
                return {
                    name: $('.driverDocName', domRow)?.value.trim() || '',
                    number: $('.driverDocNumber', domRow)?.value.trim() || '',
                    expiry: $('.driverDocExpiry', domRow)?.value || '',
                    reminder: $('.driverDocReminder', domRow)?.value || '',
                    file: hasNewFile ? {} : existingFile,
                };
            }).filter(d => d.name || d.number || d.expiry);

            const primaryContact = contacts[0]?.phone || '';
            const secondaryContact = contacts[1]?.phone || '';

            return {
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
                contacts: contacts,
                documents: documents,
                photoName: $('#driverPhoto')?.files?.[0]?.name || ''
            };
        }

        function validate(row){
            const required=['fullName','fatherName','motherName','contact','nid','licenseNo','licenseType','licenseValidity','salary','workingHour','presentAddress','permanentAddress'];
            if(required.some((key)=>!row[key])){ toast('Please fill required fields before saving.'); return false; }
            return true;
        }

        function upsert(row){ 
            const idx = drivers.findIndex((item) => item.driverId === row.driverId); 
            if (idx >= 0) drivers[idx] = row; 
            else { drivers.unshift(row); } 
            const newIdx = drivers.findIndex((item) => item.driverId === row.driverId);
            saveStore(newIdx); 
        }

        function saveDriver(statusOverride){ 
            const row=collect(statusOverride); 
            if(statusOverride==='Draft' && !row.fullName) row.fullName='Draft Driver'; 
            if(statusOverride!=='Draft' && !validate(row)) return; 
            
            // Re-merge old file paths for photo if not overridden
            const oldRow = drivers.find(r => r.driverId === row.driverId);
            if (oldRow && !$('#driverPhoto')?.files?.[0]) {
                row.photo = oldRow.photo;
            }

            upsert(row); 
            toast(statusOverride==='Draft'?'Draft saved.':'Driver saved. Redirecting to driver list.'); 
            setTimeout(()=>{ renderList(); setVisible('driverListPage'); },450); 
        }

        function loadSample(){ 
            resetForm(); 
            const row=(samples.drivers||[])[0]; 
            if(!row) return; 
            const map={driverId:'#driverId',fullName:'#driverFullName',fatherName:'#driverFatherName',motherName:'#driverMotherName',whatsapp:'#driverWhatsapp',email:'#driverEmail',dob:'#driverDob',age:'#driverAge',nid:'#driverNid',reference:'#driverReference',licenseNo:'#driverLicenseNo',licenseType:'#driverLicenseType',licenseValidity:'#driverLicenseValidity',salary:'#driverSalary',salaryTenure:'#driverSalaryTenure',otRate:'#driverOtRate',workingHour:'#driverWorkingHour',vendor:'#driverVendor',status:'#driverStatus',presentAddress:'#driverPresentAddress',permanentAddress:'#driverPermanentAddress',about:'#driverAbout'}; 
            Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); 
            const duty=document.querySelector(`input[name="driverDuty"][value="${CSS.escape(row.duty||'')}"]`); 
            if(duty) duty.checked=true; 
            $('#driverContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); 
            if (!row.contacts) { addContact({type: 'Personal', phone: row.contact}); addContact({type: 'Relative', phone: row.secondaryContact}); }
            $('#driverDocuments').innerHTML=''; (row.documents||[]).forEach(addDocument); 
            toast('Sample driver data added.'); 
        }

        function isExpiringSoon(row){ if(!row.licenseValidity) return false; return (new Date(row.licenseValidity)-new Date())/86400000 < licenseWarnDays; }

        function rowHtml(row){ 
            const exp=isExpiringSoon(row); 
            const statusClass=row.status==='Active'?'ok':row.status==='Draft'?'warn':row.status==='Blacklisted'?'danger':'soft'; 
            return `<tr><td><div class="driver-cell"><div class="driver-icon">🧑‍✈️</div><div><b>${escapeHtml(row.fullName)}</b><br><small>${escapeHtml(row.driverId)} · NID: ${escapeHtml(row.nid||'-')}</small></div></div></td><td>${escapeHtml(row.contact||'-')}<br><small>${row.whatsapp?'WA: '+escapeHtml(row.whatsapp):''}</small></td><td><span class="badge soft">${escapeHtml(row.licenseType||'-')}</span><br><small>${escapeHtml(row.licenseNo||'-')}</small></td><td><span class="badge ${exp?'warn':'ok'}">${escapeHtml(row.licenseValidity||'-')}</span></td><td>${escapeHtml(row.salary||0)} / ${escapeHtml(row.salaryTenure||'-')}<br><small>OT: ${escapeHtml(row.otRate||0)}</small></td><td>${escapeHtml(row.workingHour||0)} hrs<br><small>${escapeHtml(row.duty||'-')}</small></td><td>${escapeHtml(row.vendor||'None')}</td><td>${(row.documents||[]).length} document(s)</td><td><span class="badge ${statusClass}">${escapeHtml(row.status||'-')}</span></td><td><button type="button" class="mini-btn view-driver" data-id="${escapeHtml(row.driverId)}">View</button><button type="button" class="mini-btn edit-driver" data-id="${escapeHtml(row.driverId)}">Edit</button><button type="button" class="mini-btn danger delete-driver" data-id="${escapeHtml(row.driverId)}">Delete</button></td></tr>`; 
        }

        function renderList(){ 
            const q=value('#driverSearch').toLowerCase(), status=value('#driverFilterStatus'), license=value('#driverFilterLicense'), tenure=value('#driverFilterTenure'); 
            const rows=drivers.filter((row)=>(!q||[row.fullName,row.contact,row.nid,row.licenseNo,row.driverId].join(' ').toLowerCase().includes(q))&&(!status||row.status===status)&&(!license||row.licenseType===license)&&(!tenure||row.salaryTenure===tenure)); 
            $('#driverTbody').innerHTML=rows.length?rows.map(rowHtml).join(''):'<tr><td colspan="10" class="empty">No driver found. Click “Add Driver” to create one.</td></tr>'; 
            $('#driverKpiTotal').textContent=drivers.length; $('#driverKpiActive').textContent=drivers.filter((r)=>r.status==='Active').length; $('#driverKpiExpired').textContent=drivers.filter(isExpiringSoon).length; $('#driverKpiDocs').textContent=drivers.reduce((sum,r)=>sum+(r.documents||[]).length,0); 
        }

        function editDriver(id){ 
            const row=drivers.find((r)=>r.driverId===id); 
            if(!row) return; 
            resetForm(); 
            const map={driverId:'#driverId',fullName:'#driverFullName',fatherName:'#driverFatherName',motherName:'#driverMotherName',whatsapp:'#driverWhatsapp',email:'#driverEmail',dob:'#driverDob',age:'#driverAge',nid:'#driverNid',reference:'#driverReference',licenseNo:'#driverLicenseNo',licenseType:'#driverLicenseType',licenseValidity:'#driverLicenseValidity',salary:'#driverSalary',salaryTenure:'#driverSalaryTenure',otRate:'#driverOtRate',workingHour:'#driverWorkingHour',vendor:'#driverVendor',status:'#driverStatus',presentAddress:'#driverPresentAddress',permanentAddress:'#driverPermanentAddress',about:'#driverAbout'}; 
            Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); 
            const duty=document.querySelector(`input[name="driverDuty"][value="${CSS.escape(row.duty||'')}"]`); 
            if(duty) duty.checked=true; 
            
            $('#driverContacts').innerHTML=''; 
            if (row.contacts && row.contacts.length) {
                row.contacts.forEach(addContact);
            } else {
                if (row.contact) addContact({type: 'Personal', phone: row.contact});
                if (row.secondaryContact) addContact({type: 'Relative', phone: row.secondaryContact});
            }
            
            $('#driverDocuments').innerHTML=''; 
            (row.documents||[]).forEach(addDocument); 
            setVisible('driverAddPage'); 
        }

        function viewDriver(id){ const row=drivers.find((r)=>r.driverId===id); if(row) window.FleetmanDetailViewer?.show('Driver Details', row); }
        function deleteDriver(id){ if(!confirm('Delete this driver from prototype list?')) return; drivers=drivers.filter((row)=>row.driverId!==id); saveStore(); renderList(); toast('Driver deleted.'); }
        function exportDrivers(){ const rows=[['Driver ID','Full Name','Contact','NID','License No','License Type','License Validity','Salary','Salary Tenure','Working Hour','Vendor','Status','Documents']]; drivers.forEach((row)=>rows.push([row.driverId,row.fullName,row.contact,row.nid,row.licenseNo,row.licenseType,row.licenseValidity,row.salary,row.salaryTenure,row.workingHour,row.vendor,row.status,(row.documents||[]).map((doc)=>doc.name).join('; ')])); exportCsv(rows,'fleetman-driver-list.csv'); }

        $('#addDriverDocumentBtn')?.addEventListener('click',()=>addDocument()); 
        $('#addDriverContactBtn')?.addEventListener('click',()=>addContact());
        $('#resetDriverBtn')?.addEventListener('click',resetForm); 
        $('#saveDriverBtn')?.addEventListener('click',()=>saveDriver()); 
        $('#saveDriverDraftBtn')?.addEventListener('click',()=>saveDriver('Draft')); 
        $('#loadDriverSampleBtn')?.addEventListener('click',loadSample); 
        $('#newDriverBtn')?.addEventListener('click',()=>{resetForm();setVisible('driverAddPage');}); 
        $('#exportDriversBtn')?.addEventListener('click',exportDrivers); 
        $('#clearDriverFiltersBtn')?.addEventListener('click',()=>{['#driverSearch','#driverFilterStatus','#driverFilterLicense','#driverFilterTenure'].forEach((sel)=>setValue(sel,'')); renderList();}); 
        ['#driverSearch','#driverFilterStatus','#driverFilterLicense','#driverFilterTenure'].forEach((sel)=>$(sel)?.addEventListener('input',renderList)); 
        
        document.addEventListener('click',(e)=>{ 
            if(e.target.closest('.remove-row')) e.target.closest('.remove-row').parentElement.remove(); 
            const view=e.target.closest('.view-driver'); if(view) viewDriver(view.dataset.id); 
            const edit=e.target.closest('.edit-driver'); if(edit) editDriver(edit.dataset.id); 
            const del=e.target.closest('.delete-driver'); if(del) deleteDriver(del.dataset.id); 
        });

        resetForm(); renderList(); setVisible('driverListPage');
    }


    function initClients() {
        const STORAGE='fleetman_clients_v2'; let clients=Array.isArray(records.clients) ? records.clients : (samples.clients||[]);
        function saveStore(){ syncResource('clients', clients); }
        function genId(){ return 'CLI' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }
        function addContact(row={}){ const wrapper=$('#clientContacts'); if(!wrapper) return; const meta=row.whatsapp || row.email || ''; const div=document.createElement('div'); div.className='repeat-row contact-row'; div.innerHTML=`<div class="field"><label>Contact Person Name <span class="req">*</span></label><input class="clientContactName" placeholder="Example: Md. Karim" value="${escapeHtml(row.name||'')}"></div><div class="field"><label>Contact Type</label><select class="clientContactType">${((window.FLEETMAN.options||{}).client_contact_methods||[]).map(t => '<option value="'+escapeHtml(t)+'" '+(row.type===t?'selected':'')+'>'+escapeHtml(t)+'</option>').join('')}</select></div><div class="field"><label>Role / Designation</label><input class="clientContactRole" placeholder="Example: Operations Manager" value="${escapeHtml(row.role||'')}"></div><div class="field"><label>Phone Number <span class="req">*</span></label><input class="clientContactPhone" placeholder="01XXXXXXXXX" value="${escapeHtml(row.phone||'')}"></div><div class="field"><label>WhatsApp / Email</label><input class="clientContactMeta" placeholder="WhatsApp or email" value="${escapeHtml(meta)}"></div><button type="button" class="mini-btn danger remove-row">Remove</button>`; wrapper.appendChild(div); }
        function resetForm(){ $$('#clientAddPage input,#clientAddPage select,#clientAddPage textarea').forEach((el)=>{el.value='';}); setValue('#clientId',genId()); setValue('#clientType','Corporate'); setValue('#clientStatus','Active'); setValue('#clientContactMethod','Phone'); $('#clientContacts').innerHTML=''; addContact(); }
        function collect(statusOverride){ const contacts=$$('#clientContacts .contact-row').map((row)=>{ const meta=$('.clientContactMeta',row)?.value.trim()||''; return {name:$('.clientContactName',row)?.value.trim()||'',type:$('.clientContactType',row)?.value||'',role:$('.clientContactRole',row)?.value.trim()||'',phone:$('.clientContactPhone',row)?.value.trim()||'',whatsapp:meta.includes('@')?'':meta,email:meta.includes('@')?meta:''}; }).filter((c)=>c.name||c.phone||c.role||c.whatsapp||c.email||c.type); return {clientId:value('#clientId'),clientName:value('#clientName').trim(),email:value('#clientEmail').trim(),phone:value('#clientPhone').trim(),whatsapp:value('#clientWhatsapp').trim(),reference:value('#clientReference').trim(),clientType:value('#clientType'),status:statusOverride||value('#clientStatus'),contactMethod:value('#clientContactMethod'),address:value('#clientAddress').trim(),about:value('#clientAbout').trim(),contacts}; }
        function validate(row){ if(!row.clientName || !row.phone || !row.address){ toast('Please fill client name, phone number, and address.'); return false; } if(!row.contacts.length || !row.contacts[0].name || !row.contacts[0].phone){ toast('Please add at least one contact person with name and phone number.'); return false; } return true; }
        function upsert(row){ const idx=clients.findIndex((item)=>item.clientId===row.clientId); if(idx>=0) clients[idx]=row; else clients.unshift(row); saveStore(); }
        function saveClient(statusOverride){ const row=collect(statusOverride); if(statusOverride==='Draft' && !row.clientName) row.clientName='Draft Client'; if(statusOverride!=='Draft' && !validate(row)) return; upsert(row); toast(statusOverride==='Draft'?'Draft saved.':'Client saved. Redirecting to client list.'); setTimeout(()=>{renderList();setVisible('clientListPage');},450); }
        function loadSample(){ resetForm(); const row=(samples.clients||[])[0]; if(!row) return; const map={clientId:'#clientId',clientName:'#clientName',email:'#clientEmail',phone:'#clientPhone',whatsapp:'#clientWhatsapp',reference:'#clientReference',clientType:'#clientType',status:'#clientStatus',contactMethod:'#clientContactMethod',address:'#clientAddress',about:'#clientAbout'}; Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); $('#clientContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); toast('Sample client data added.'); }
        function rowHtml(row){ const main=(row.contacts||[])[0]||{}; const statusClass=row.status==='Active'?'ok':row.status==='Prospect'?'warn':row.status==='Draft'?'soft':'danger'; return `<tr><td><div class="client-cell"><div class="client-icon">🏢</div><div><b>${escapeHtml(row.clientName)}</b><br><small>${escapeHtml(row.clientId)}${row.reference?' · Ref: '+escapeHtml(row.reference):''}</small></div></div></td><td>${escapeHtml(row.phone||'-')}<br><small>${escapeHtml(row.email||'')}</small></td><td><b>${escapeHtml(main.name||'-')}</b> <span class="badge soft" style="font-size:10px">${escapeHtml(main.type||'')}</span><br><small>${escapeHtml(main.phone||'')}${(row.contacts||[]).length>1?' · +'+((row.contacts||[]).length-1)+' more':''}</small></td><td><span class="badge soft">${escapeHtml(row.clientType||'-')}</span></td><td><span class="badge ${statusClass}">${escapeHtml(row.status||'-')}</span></td><td>${escapeHtml(row.contactMethod||'-')}</td><td>${escapeHtml(row.address||'-')}</td><td><button type="button" class="mini-btn view-client" data-id="${escapeHtml(row.clientId)}">View</button><button type="button" class="mini-btn edit-client" data-id="${escapeHtml(row.clientId)}">Edit</button><button type="button" class="mini-btn danger delete-client" data-id="${escapeHtml(row.clientId)}">Delete</button></td></tr>`; }
        function renderList(){ const q=value('#clientSearch').toLowerCase(), status=value('#clientFilterStatus'), type=value('#clientFilterType'), method=value('#clientFilterMethod'); const rows=clients.filter((row)=>{ const people=(row.contacts||[]).map((person)=>[person.name,person.phone,person.role,person.whatsapp,person.email].join(' ')).join(' '); return (!q||[row.clientName,row.phone,row.email,row.clientId,row.reference,people].join(' ').toLowerCase().includes(q))&&(!status||row.status===status)&&(!type||row.clientType===type)&&(!method||row.contactMethod===method); }); $('#clientTbody').innerHTML=rows.length?rows.map(rowHtml).join(''):'<tr><td colspan="8" class="empty">No client found. Click “Add Client” to create one.</td></tr>'; $('#clientKpiTotal').textContent=clients.length; $('#clientKpiActive').textContent=clients.filter((c)=>c.status==='Active').length; $('#clientKpiContacts').textContent=clients.reduce((sum,c)=>sum+(c.contacts||[]).length,0); $('#clientKpiEmail').textContent=clients.filter((c)=>c.email).length; }
        function editClient(id){ const row=clients.find((r)=>r.clientId===id); if(!row) return; resetForm(); const map={clientId:'#clientId',clientName:'#clientName',email:'#clientEmail',phone:'#clientPhone',whatsapp:'#clientWhatsapp',reference:'#clientReference',clientType:'#clientType',status:'#clientStatus',contactMethod:'#clientContactMethod',address:'#clientAddress',about:'#clientAbout'}; Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); $('#clientContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); setVisible('clientAddPage'); }
        function viewClient(id){ const row=clients.find((r)=>r.clientId===id); if(row) window.FleetmanDetailViewer?.show('Client Details', row); }
        function deleteClient(id){ if(!confirm('Delete this client from prototype list?')) return; clients=clients.filter((row)=>row.clientId!==id); saveStore(); renderList(); toast('Client deleted.'); }
        function exportClients(){ const rows=[['Client ID','Client Name','Phone','WhatsApp','Email','Reference','Client Type','Status','Preferred Contact','Address','About','Contact Persons']]; clients.forEach((row)=>rows.push([row.clientId,row.clientName,row.phone,row.whatsapp,row.email,row.reference,row.clientType,row.status,row.contactMethod,row.address,row.about,(row.contacts||[]).map((p)=>`${p.name} / ${p.role||''} / ${p.phone||''}`).join('; ')])); exportCsv(rows,'fleetman-client-list.csv'); }
        $('#addClientContactBtn')?.addEventListener('click',()=>addContact()); $('#resetClientBtn')?.addEventListener('click',resetForm); $('#saveClientBtn')?.addEventListener('click',()=>saveClient()); $('#saveClientDraftBtn')?.addEventListener('click',()=>saveClient('Draft')); $('#loadClientSampleBtn')?.addEventListener('click',loadSample); $('#newClientBtn')?.addEventListener('click',()=>{resetForm();setVisible('clientAddPage');}); $('#exportClientsBtn')?.addEventListener('click',exportClients); $('#applyClientFiltersBtn')?.addEventListener('click',renderList); $('#clearClientFiltersBtn')?.addEventListener('click',()=>{['#clientSearch','#clientFilterStatus','#clientFilterType','#clientFilterMethod'].forEach((sel)=>setValue(sel,'')); renderList();}); ['#clientSearch','#clientFilterStatus','#clientFilterType','#clientFilterMethod'].forEach((sel)=>$(sel)?.addEventListener('input',renderList)); document.addEventListener('click',(e)=>{ if(e.target.closest('.remove-row')) e.target.closest('.remove-row').parentElement.remove(); const view=e.target.closest('.view-client'); if(view) viewClient(view.dataset.id); const edit=e.target.closest('.edit-client'); if(edit) editClient(edit.dataset.id); const del=e.target.closest('.delete-client'); if(del) deleteClient(del.dataset.id); });
        saveStore(); resetForm(); renderList(); setVisible('clientListPage');
    }


    function initEmployees() {
        let employees = Array.isArray(records.employees) ? records.employees : (samples.employees || []);
        const docTemplates = (window.FLEETMAN_EMPLOYEE_DOC_TEMPLATES || options.employee_document_templates || []);
        // Track pending document files: { docRowIndex: File }
        let docRowCounter = 0;

        /* ── async sync: supports multipart FormData when files are present ── */
        async function syncEmployees(rows, empIndex) {
            const endpoint = resources?.employees?.sync;
            if (!endpoint) return { ok: true, skipped: true };

            // Collect file inputs directly from the live DOM at save-time
            const photoFile = $('#employeePhoto')?.files?.[0] || null;
            const docFileInputs = $$('#employeeDocuments .emp-document-row .empDocFile');
            const docFiles = docFileInputs.map((input) => input.files?.[0] || null).filter(Boolean);

            if (!photoFile && docFiles.length === 0) {
                // No files → plain JSON sync
                return syncResource('employees', rows);
            }

            const formData = new FormData();
            formData.append('rows', JSON.stringify(rows || []));

            // empIndex is the position of this employee in the rows array
            // Backend maps employee_photo_files[empIndex] → rows[empIndex]
            if (photoFile) {
                formData.append(`employee_photo_files[${empIndex}]`, photoFile);
            }

            // For documents: use sequential index matching collect()'s documents array
            // collect() builds documents[0], documents[1], ... in DOM order
            // We match that same sequential order here
            docFileInputs.forEach((input, seqIdx) => {
                const file = input.files?.[0];
                if (file) formData.append(`employee_document_files[${empIndex}][${seqIdx}]`, file);
            });

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: formData,
                });
                if (!response.ok) {
                    let message = 'Employee could not be saved.';
                    try { const err = await response.json(); message = err.message || Object.values(err.errors || {}).flat().join(' ') || message; } catch (_) {}
                    throw new Error(message);
                }
                return await response.json().catch(() => ({ ok: true }));
            } catch (error) {
                toast(error.message || 'Employee could not be saved.');
                return { ok: false, syncFailed: true, message: error.message };
            }
        }

        function saveStore(empIndex) { return syncEmployees(employees, empIndex); }
        function genId() { return 'EMP' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }

        /* ── Contact rows ── */
        const CONTACT_TYPES = ['Office', 'Home', 'Relative', 'Other'];

        function addContact(row = {}) {
            const wrapper = $('#employeeContacts');
            if (!wrapper) return;
            const div = document.createElement('div');
            div.className = 'repeat-row emp-contact-row';
            const typeOptions = CONTACT_TYPES.map((t) =>
                `<option value="${t}" ${(row.type || 'Office') === t ? 'selected' : ''}>${t}</option>`
            ).join('');
            const showRel = (row.type === 'Relative') ? '' : ' style="display:none"';
            div.innerHTML = `
                <div class="field">
                    <label>Type <span class="req">*</span></label>
                    <select class="empContactType">${typeOptions}</select>
                </div>
                <div class="field">
                    <label>Phone Number <span class="req">*</span></label>
                    <input class="empContactNumber" type="tel" placeholder="01XXXXXXXXX" value="${escapeHtml(row.number || '')}">
                </div>
                <div class="field emp-relationship-field"${showRel}>
                    <label>Relationship</label>
                    <input class="empContactRelationship" placeholder="e.g. Brother, Wife, Father" value="${escapeHtml(row.relationship || '')}">
                </div>
                <button type="button" class="mini-btn danger remove-row" style="align-self:flex-end">Remove</button>`;
            wrapper.appendChild(div);
        }

        /* ── Document rows ── */
        function renderDocFileInfo(wrapper, fileData, selectedFile) {
            const info = $('.emp-upload-info', wrapper);
            if (!info) return;
            if (selectedFile) {
                info.innerHTML = `<span class="pending-upload">📎 Selected: <b>${escapeHtml(selectedFile.name)}</b> — will upload on Save.</span>`;
                return;
            }
            if (fileData && (fileData.fileUrl || fileData.filePath)) {
                const label = fileData.originalName || fileData.fileName || 'Uploaded file';
                const link = fileData.fileUrl ? ` · <a href="${escapeHtml(fileData.fileUrl)}" target="_blank" rel="noopener">View file</a>` : '';
                info.innerHTML = `✅ Uploaded: <b>${escapeHtml(label)}</b>${link}`;
            } else {
                info.innerHTML = '';
            }
        }

        function addDocument(row = {}) {
            const wrapper = $('#employeeDocuments');
            if (!wrapper) return;
            const rowIdx = docRowCounter++;
            // Must declare existingFile BEFORE using it below
            const existingFile = (row.file && typeof row.file === 'object' && (row.file.filePath || row.file.fileUrl)) ? row.file : null;
            const div = document.createElement('div');
            div.className = 'repeat-row emp-document-row';
            div.dataset.docIdx = rowIdx;
            // Store existing (already-uploaded) file data so collect() can preserve it
            if (existingFile) div.dataset.fileJson = JSON.stringify(existingFile);
            const docOptions = ['', ...docTemplates].map((d) =>
                `<option value="${escapeHtml(d)}" ${(row.name === d && d) ? 'selected' : ''}>${escapeHtml(d || 'Select document type')}</option>`
            ).join('');
            div.innerHTML = `
                <div class="field">
                    <label>Document Type</label>
                    <select class="empDocName">${docOptions}</select>
                </div>
                <div class="field">
                    <label>Reference / Number</label>
                    <input class="empDocRef" placeholder="Optional reference" value="${escapeHtml(row.reference || row.number || '')}">
                </div>
                <div class="field">
                    <label>Upload File</label>
                    <input class="empDocFile" type="file" accept="image/jpg,image/jpeg,image/png,image/webp,application/pdf" data-doc-idx="${rowIdx}">
                    <div class="emp-upload-info upload-meta"></div>
                </div>
                <button type="button" class="mini-btn danger remove-row" style="align-self:flex-end">Remove</button>`;
            wrapper.appendChild(div);
            if (existingFile) renderDocFileInfo(div, existingFile, null);
        }

        /* ── reset ── */
        function resetForm() {
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
            const preview = $('#employeePhotoPreview');
            if (preview) preview.textContent = '👤';
        }

        /* ── collect ── */
        function collect(statusOverride) {
            // contacts
            const contacts = $$('#employeeContacts .emp-contact-row').map((row) => ({
                type: $('.empContactType', row)?.value || 'Office',
                number: $('.empContactNumber', row)?.value.trim() || '',
                relationship: $('.empContactRelationship', row)?.value.trim() || '',
            })).filter((c) => c.number);

            // documents: sequential array in DOM order, matching FormData doc file indices
            const documents = $$('#employeeDocuments .emp-document-row').map((domRow) => {
                // Recover previously-uploaded file data if no new file was chosen
                let existingFile = {};
                try { existingFile = domRow.dataset.fileJson ? JSON.parse(domRow.dataset.fileJson) : {}; } catch (_) {}
                const hasNewFile = !!$('.empDocFile', domRow)?.files?.[0];
                return {
                    name: $('.empDocName', domRow)?.value.trim() || '',
                    reference: $('.empDocRef', domRow)?.value.trim() || '',
                    // If a new file is chosen, server will overwrite; otherwise keep existing
                    file: hasNewFile ? {} : existingFile,
                };
            });

            // primary contact fallback for backward compat
            const primaryContact = contacts[0]?.number || '';

            return {
                employeeId: value('#employeeId'),
                fullName: value('#employeeFullName').trim(),
                fatherName: value('#employeeFatherName').trim(),
                motherName: value('#employeeMotherName').trim(),
                nid: value('#employeeNid').trim(),
                contactNumber: primaryContact,
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
                photoName: $('#employeePhoto')?.files?.[0]?.name || '',
                documents,
            };
        }

        /* ── validate ── */
        function validate(row) {
            const required = ['fullName','fatherName','motherName','nid','designation','joiningDate','salary','salaryTenure','presentAddress','permanentAddress'];
            if (required.some((key) => !row[key])) {
                toast('Please fill all required employee fields.');
                return false;
            }
            if (!row.contacts || !row.contacts.length || !row.contacts[0].number) {
                toast('Please add at least one contact number.');
                return false;
            }
            return true;
        }

        function upsert(row) {
            const index = employees.findIndex((item) => item.employeeId === row.employeeId);
            if (index >= 0) employees[index] = row;
            else employees.unshift(row);
            // Return the actual array index of this employee after upsert
            return employees.findIndex((item) => item.employeeId === row.employeeId);
        }

        /* ── save ── */
        async function saveEmployee(statusOverride) {
            const row = collect(statusOverride);
            if (statusOverride === 'Draft') {
                if (!row.fullName) row.fullName = 'Draft Employee';
                if (!row.designation) row.designation = 'Other';
                if (!row.contactNumber) row.contactNumber = 'Pending';
            } else if (!validate(row)) {
                return;
            }
            // upsert returns the index of this employee in the array
            const empIndex = upsert(row);
            const result = await saveStore(empIndex);
            // If server returned updated rows (with file paths), refresh employees + restore docs
            if (result && result.rows) {
                employees = result.rows;
                // Update document rows with server-returned file paths so "View file" links appear
                const savedEmp = employees.find((e) => e.employeeId === row.employeeId);
                if (savedEmp && savedEmp.documents) {
                    $$('#employeeDocuments .emp-document-row').forEach((domRow, seqIdx) => {
                        const savedDoc = savedEmp.documents[seqIdx];
                        if (savedDoc && savedDoc.file && (savedDoc.file.fileUrl || savedDoc.file.filePath)) {
                            // Update the data attribute so future collects preserve this file
                            domRow.dataset.fileJson = JSON.stringify(savedDoc.file);
                            renderDocFileInfo(domRow, savedDoc.file, null);
                        }
                    });
                }
            }
            toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Employee saved. Redirecting to employee list.');
            setTimeout(() => { renderList(); setVisible('employeeListPage'); }, 450);
        }

        /* ── load sample ── */
        function loadSample() {
            resetForm();
            const row = (samples.employees || [])[0];
            if (!row) { toast('No sample data available.'); return; }
            const map = {
                employeeId: '#employeeId', fullName: '#employeeFullName', fatherName: '#employeeFatherName', motherName: '#employeeMotherName',
                nid: '#employeeNid', email: '#employeeEmail', reference: '#employeeReference',
                designation: '#employeeDesignation', joiningDate: '#employeeJoiningDate', status: '#employeeStatus', socialMedia: '#employeeSocialMedia',
                age: '#employeeAge', salary: '#employeeSalary', salaryTenure: '#employeeSalaryTenure', overtimeRate: '#employeeOvertimeRate',
                presentAddress: '#employeePresentAddress', permanentAddress: '#employeePermanentAddress', about: '#employeeAbout'
            };
            Object.entries(map).forEach(([key, selector]) => setValue(selector, row[key] || ''));
            // populate contacts
            const contactsWrap = $('#employeeContacts');
            if (contactsWrap) {
                contactsWrap.innerHTML = '';
                if (row.contacts && row.contacts.length) {
                    row.contacts.forEach((c) => addContact(c));
                } else if (row.contactNumber) {
                    addContact({ type: 'Office', number: row.contactNumber });
                } else {
                    addContact({ type: 'Office' });
                }
            }
            // populate documents
            const docsWrap = $('#employeeDocuments');
            if (docsWrap) {
                docsWrap.innerHTML = '';
                if (row.documents && row.documents.length) {
                    row.documents.forEach((d) => addDocument(d));
                } else {
                    addDocument();
                }
            }
            toast('Sample employee data loaded.');
        }

        /* ── list ── */
        function statusClass(status) {
            if (status === 'Active') return 'ok';
            if (status === 'On Leave') return 'warn';
            if (status === 'Draft') return 'soft';
            return 'danger';
        }

        function formatContacts(row) {
            if (row.contacts && row.contacts.length) {
                const first = row.contacts[0];
                const more = row.contacts.length > 1 ? `<br><small>+${row.contacts.length - 1} more</small>` : '';
                const rel = first.type === 'Relative' && first.relationship ? ` (${escapeHtml(first.relationship)})` : '';
                return `<span class="badge soft" style="font-size:11px">${escapeHtml(first.type)}</span> ${escapeHtml(first.number)}${rel}${more}`;
            }
            return escapeHtml(row.contactNumber || '-');
        }

        function rowHtml(row) {
            const docCount = (row.documents || []).length;
            return `<tr>
                <td><div class="employee-cell"><div class="employee-icon">👤</div><div><b>${escapeHtml(row.fullName)}</b><br><small>${escapeHtml(row.employeeId)}</small></div></div></td>
                <td>${formatContacts(row)}<br><small style="color:#667085">${escapeHtml(row.email || '')}</small></td>
                <td>${escapeHtml(row.designation || '-')}</td>
                <td>${escapeHtml(row.joiningDate || '-')}</td>
                <td>${Number(row.salary || 0).toLocaleString()}<br><small>${escapeHtml(row.salaryTenure || '')}</small></td>
                <td><span class="badge ${statusClass(row.status)}">${escapeHtml(row.status || '-')}</span></td>
                <td>${escapeHtml(row.presentAddress || '-')}</td>
                <td>${docCount > 0 ? `<span class="badge soft">${docCount} file${docCount > 1 ? 's' : ''}</span>` : '<span style="color:#aaa">—</span>'}</td>
                <td><button type="button" class="mini-btn view-employee" data-id="${escapeHtml(row.employeeId)}">View</button><button type="button" class="mini-btn edit-employee" data-id="${escapeHtml(row.employeeId)}">Edit</button><button type="button" class="mini-btn danger delete-employee" data-id="${escapeHtml(row.employeeId)}">Delete</button></td>
            </tr>`;
        }

        function renderList() {
            const query = value('#employeeSearch').toLowerCase();
            const status = value('#employeeFilterStatus');
            const tenure = value('#employeeFilterTenure');
            const designation = value('#employeeFilterDesignation');
            const rows = employees.filter((row) => {
                const contactStr = (row.contacts || []).map((c) => c.number + ' ' + c.type).join(' ');
                return (!query || [row.employeeId, row.fullName, row.designation, row.contactNumber, row.nid, contactStr].join(' ').toLowerCase().includes(query)) &&
                    (!status || row.status === status) &&
                    (!tenure || row.salaryTenure === tenure) &&
                    (!designation || row.designation === designation);
            });
            $('#employeeTbody').innerHTML = rows.length ? rows.map(rowHtml).join('') : '<tr><td colspan="9" class="empty">No employee found. Click \u201cAdd Employee\u201d to create one.</td></tr>';
            $('#employeeKpiTotal').textContent = employees.length;
            $('#employeeKpiActive').textContent = employees.filter((row) => row.status === 'Active').length;
            $('#employeeKpiMonthly').textContent = employees.filter((row) => row.salaryTenure === 'Monthly').length;
            $('#employeeKpiPayroll').textContent = employees.reduce((sum, row) => sum + Number(row.salary || 0), 0).toLocaleString();
        }

        /* ── edit ── */
        function editEmployee(id) {
            const row = employees.find((item) => item.employeeId === id);
            if (!row) return;
            resetForm();
            const map = {
                employeeId: '#employeeId', fullName: '#employeeFullName', fatherName: '#employeeFatherName', motherName: '#employeeMotherName',
                nid: '#employeeNid', email: '#employeeEmail', reference: '#employeeReference',
                designation: '#employeeDesignation', joiningDate: '#employeeJoiningDate', status: '#employeeStatus', socialMedia: '#employeeSocialMedia',
                age: '#employeeAge', salary: '#employeeSalary', salaryTenure: '#employeeSalaryTenure', overtimeRate: '#employeeOvertimeRate',
                presentAddress: '#employeePresentAddress', permanentAddress: '#employeePermanentAddress', about: '#employeeAbout'
            };
            Object.entries(map).forEach(([key, selector]) => setValue(selector, row[key] || ''));
            // restore contacts
            const contactsWrap = $('#employeeContacts');
            if (contactsWrap) {
                contactsWrap.innerHTML = '';
                if (row.contacts && row.contacts.length) {
                    row.contacts.forEach((c) => addContact(c));
                } else if (row.contactNumber) {
                    addContact({ type: 'Office', number: row.contactNumber });
                } else {
                    addContact({ type: 'Office' });
                }
            }
            // restore documents
            const docsWrap = $('#employeeDocuments');
            if (docsWrap) {
                docsWrap.innerHTML = '';
                if (row.documents && row.documents.length) {
                    row.documents.forEach((d) => addDocument(d));
                } else {
                    addDocument();
                }
            }
            setVisible('employeeAddPage');
        }

        function viewEmployee(id) {
            const row = employees.find((item) => item.employeeId === id);
            if (!row) return;
            window.FleetmanDetailViewer?.show('Employee Details', row);
        }

        function deleteEmployee(id) {
            if (!confirm('Delete this employee?')) return;
            employees = employees.filter((row) => row.employeeId !== id);
            saveStore();
            renderList();
            toast('Employee deleted.');
        }

        function exportEmployees() {
            const rows = [['Employee ID','Full Name','Father Name','Mother Name','NID','Primary Contact','All Contacts','Email','Reference','Designation','Joining Date','Status','Social Media','Age','Salary','Salary Tenure','Overtime Rate','Present Address','Permanent Address','About','Documents']];
            employees.forEach((row) => {
                const contactStr = (row.contacts || []).map((c) => `${c.type}: ${c.number}${c.relationship ? ' (' + c.relationship + ')' : ''}`).join('; ') || row.contactNumber || '';
                const docStr = (row.documents || []).map((d) => d.name || 'Document').join('; ');
                rows.push([row.employeeId, row.fullName, row.fatherName, row.motherName, row.nid, row.contactNumber, contactStr, row.email, row.reference, row.designation, row.joiningDate, row.status, row.socialMedia, row.age, row.salary, row.salaryTenure, row.overtimeRate, row.presentAddress, row.permanentAddress, row.about, docStr]);
            });
            exportCsv(rows, 'fleetman-employee-list.csv');
        }

        /* ── event listeners ── */
        $('#addEmployeeContactBtn')?.addEventListener('click', () => addContact({ type: 'Office' }));
        $('#addEmployeeDocumentBtn')?.addEventListener('click', () => addDocument());
        $('#resetEmployeeBtn')?.addEventListener('click', resetForm);
        $('#saveEmployeeBtn')?.addEventListener('click', () => saveEmployee());
        $('#saveEmployeeDraftBtn')?.addEventListener('click', () => saveEmployee('Draft'));
        $('#loadEmployeeSampleBtn')?.addEventListener('click', loadSample);
        $('#newEmployeeBtn')?.addEventListener('click', () => { resetForm(); setVisible('employeeAddPage'); });
        $('#exportEmployeesBtn')?.addEventListener('click', exportEmployees);
        $('#applyEmployeeFiltersBtn')?.addEventListener('click', renderList);
        $('#clearEmployeeFiltersBtn')?.addEventListener('click', () => { ['#employeeSearch','#employeeFilterStatus','#employeeFilterTenure','#employeeFilterDesignation'].forEach((selector) => setValue(selector, '')); renderList(); });
        ['#employeeSearch','#employeeFilterStatus','#employeeFilterTenure','#employeeFilterDesignation'].forEach((selector) => $(selector)?.addEventListener('input', renderList));

        // Photo preview
        $('#employeePhoto')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            const preview = $('#employeePhotoPreview');
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = (ev) => { preview.innerHTML = `<img src="${ev.target.result}" style="width:66px;height:66px;border-radius:16px;object-fit:cover">`; };
                reader.readAsDataURL(file);
            }
        });

        // Delegated events for dynamic rows
        document.addEventListener('click', (event) => {
            // remove-row inside employee sections
            const removeBtn = event.target.closest('#employeeContacts .remove-row, #employeeDocuments .remove-row');
            if (removeBtn) {
                const row = removeBtn.closest('.emp-contact-row, .emp-document-row');
                if (row) row.remove();
            }
            const view = event.target.closest('.view-employee');
            if (view) viewEmployee(view.dataset.id);
            const edit = event.target.closest('.edit-employee');
            if (edit) editEmployee(edit.dataset.id);
            const del = event.target.closest('.delete-employee');
            if (del) deleteEmployee(del.dataset.id);
        });

        // Contact type change → show/hide relationship field
        document.addEventListener('change', (event) => {
            const typeSelect = event.target.closest('.empContactType');
            if (typeSelect) {
                const row = typeSelect.closest('.emp-contact-row');
                const relField = row?.querySelector('.emp-relationship-field');
                if (relField) relField.style.display = typeSelect.value === 'Relative' ? '' : 'none';
            }
            // Document file chosen → show preview info
            const docFile = event.target.closest('.empDocFile');
            if (docFile) {
                const file = docFile.files?.[0] || null;
                const row = docFile.closest('.emp-document-row');
                if (row) renderDocFileInfo(row, {}, file);
            }
        });

        // Initial sync without files (plain JSON)
        syncResource('employees', employees);
        resetForm();
        renderList();
        setVisible('employeeListPage');
    }

    function initDriverAttendance() {
        let logs = Array.isArray(records.driver_attendance) ? records.driver_attendance : (samples.driver_attendance || []);
        const masters = data.attendanceMasters || { contracts: [], vehicle_driver_map: {}, drivers: [] };
        let selectedStatus = 'Completed';

        const normalize = (text) => String(text || '').trim();
        const unique = (items) => Array.from(new Set((items || []).map(normalize).filter(Boolean)));
        const labelOf = (item) => typeof item === 'string' ? item : (item?.label || item?.name || item?.id || '');
        const contractLabel = (contract = {}) => contract.label || [contract.id || contract.contractId, contract.name || contract.partyName].filter(Boolean).join(' - ');
        const contractLabels = unique((masters.contracts || []).map(contractLabel));

        function saveStore() {
            syncResource('driver_attendance', logs);
        }

        function genId() {
            return 'DL' + new Date().toISOString().slice(2, 10).replaceAll('-', '') + Math.floor(100 + Math.random() * 900);
        }

        function fillDatalist(id, items) {
            const node = document.getElementById(id);
            if (!node) return;
            node.innerHTML = unique(items || []).map((item) => `<option value="${escapeHtml(item)}"></option>`).join('');
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

        function vehiclesFor(contract) {
            if (!contract) return [];
            const fromAssignments = assignmentsFor(contract).map(vehicleLabel);
            const fromContract = (contract.vehicles || []).map(labelOf);
            return unique([...fromAssignments, ...fromContract]);
        }

        function driversFor(contract, vehicle = '') {
            if (!contract) return [];
            const currentVehicle = normalize(vehicle);
            const assignments = assignmentsFor(contract).filter((assignment) => !currentVehicle || vehicleLabel(assignment) === currentVehicle);
            const fromAssignments = assignments.map(driverLabel);
            const fromContract = currentVehicle ? [] : (contract.drivers || []).map(labelOf);
            return unique([...fromAssignments, ...fromContract]);
        }

        function selectedAssignment(contract = selectedContract()) {
            if (!contract) return null;
            const currentVehicle = normalize(value('#attendanceVehicle'));
            const currentDriver = normalize(value('#attendanceDriver'));
            return assignmentsFor(contract).find((assignment) => {
                const vehicleMatches = !currentVehicle || vehicleLabel(assignment) === currentVehicle;
                const driverMatches = !currentDriver || driverLabel(assignment) === currentDriver;
                return vehicleMatches && driverMatches;
            }) || assignmentsFor(contract).find((assignment) => vehicleLabel(assignment) === currentVehicle) || null;
        }

        function populateBase() {
            fillDatalist('attendanceContractList', contractLabels);
            fillDatalist('attendanceStatusFilterList', options.attendance_statuses || ['Initiated', 'Running', 'Completed']);
            fillDatalist('attendanceFilterContractList', contractLabels);
        }

        function populateDriversBySelection() {
            const found = selectedContract();
            const drivers = driversFor(found, value('#attendanceVehicle'));
            fillDatalist('attendanceDriverList', drivers);

            if (value('#attendanceDriver') && !drivers.includes(value('#attendanceDriver'))) {
                setValue('#attendanceDriver', '');
            }

            const assigned = selectedAssignment(found);
            const assignedDriver = assigned ? driverLabel(assigned) : '';
            if (assignedDriver) {
                setValue('#attendanceDriver', assignedDriver);
            } else if (!value('#attendanceDriver') && drivers.length === 1) {
                setValue('#attendanceDriver', drivers[0]);
            }
        }

        function populateByContract() {
            const found = selectedContract();
            const vehicles = vehiclesFor(found);
            fillDatalist('attendanceVehicleList', vehicles);

            if (value('#attendanceVehicle') && !vehicles.includes(value('#attendanceVehicle'))) {
                setValue('#attendanceVehicle', '');
            }

            if (!value('#attendanceVehicle') && vehicles.length === 1) {
                setValue('#attendanceVehicle', vehicles[0]);
            }

            populateDriversBySelection();
        }

        function onVehicleChange() {
            populateDriversBySelection();
        }

        function renderStatusChoices() {
            const statuses = options.attendance_statuses || ['Initiated', 'Running', 'Completed'];
            const wrap = $('#attendanceStatusChoices');
            if (!wrap) return;
            wrap.innerHTML = statuses.map((status) => `<button type="button" class="choice-btn ${selectedStatus === status ? 'active' : ''}" data-attendance-status="${escapeHtml(status)}">${escapeHtml(status)}</button>`).join('');
        }

        function setNow(fieldId) {
            const d = new Date();
            setValue('#' + fieldId, String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0'));
            updateSummary();
        }

        function calcHours(start, end) {
            if (!start || !end) return '0h 0m';
            const [sh, sm] = start.split(':').map(Number);
            const [eh, em] = end.split(':').map(Number);
            let minutes = (eh * 60 + em) - (sh * 60 + sm);
            if (minutes < 0) minutes = 0;
            return `${Math.floor(minutes / 60)}h ${minutes % 60}m`;
        }

        function updateSummary() {
            const hours = $('#attendanceSummaryHours');
            if (hours) hours.textContent = calcHours(value('#attendanceStartTime'), value('#attendanceEndTime'));
        }

        function resetForm() {
            $$('#attendanceAddPage input,#attendanceAddPage textarea').forEach((el) => { el.value = ''; });
            setValue('#attendanceId', genId());
            setValue('#attendanceDate', new Date().toISOString().slice(0, 10));
            selectedStatus = 'Completed';
            populateBase();
            populateByContract();
            renderStatusChoices();
            updateSummary();
        }

        function collect(statusOverride) {
            updateSummary();
            const contract = selectedContract();
            const assignment = selectedAssignment(contract);
            return {
                logId: value('#attendanceId') || genId(),
                date: value('#attendanceDate'),
                contract: value('#attendanceContract'),
                contractId: contract?.id || contract?.contractId || '',
                contractParty: contract?.partyName || contract?.name || '',
                vehicle: value('#attendanceVehicle'),
                vehicleId: assignment?.vehicleId || '',
                driver: value('#attendanceDriver'),
                driverId: assignment?.driverId || '',
                startTime: value('#attendanceStartTime'),
                endTime: value('#attendanceEndTime'),
                status: statusOverride || selectedStatus,
                hours: $('#attendanceSummaryHours')?.textContent || '0h 0m',
                notes: value('#attendanceNotes').trim(),
                savedAt: new Date().toISOString(),
            };
        }

        function validate(row) {
            const found = selectedContract();
            if (!row.date || !row.contract || !row.vehicle || !row.driver || !row.startTime || !row.status) {
                toast('Please fill Date, Contract, Vehicle, Driver, Start Time, and Status.');
                return false;
            }

            if (!found) {
                toast('Please choose a valid contract from the real contract list.');
                return false;
            }

            const allowedVehicles = vehiclesFor(found);
            if (!allowedVehicles.includes(row.vehicle)) {
                toast('Please choose a vehicle assigned to the selected contract.');
                return false;
            }

            const allowedDrivers = driversFor(found, row.vehicle);
            if (!allowedDrivers.includes(row.driver)) {
                toast('Please choose a driver assigned to the selected contract/vehicle.');
                return false;
            }

            return true;
        }

        function upsert(row) {
            const idx = logs.findIndex((item) => item.logId === row.logId);
            if (idx >= 0) logs[idx] = row;
            else logs.unshift(row);
            saveStore();
        }

        function saveLog(statusOverride) {
            const row = collect(statusOverride);
            if (statusOverride !== 'Draft' && !validate(row)) return;
            upsert(row);
            toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Attendance saved. Redirecting to list.');
            setTimeout(() => {
                renderList();
                setVisible('attendanceListPage');
            }, 450);
        }

        function loadSample() {
            resetForm();
            const firstContract = (masters.contracts || [])[0];
            if (!firstContract) {
                toast('No saved contract assignment found. Please create a contract first.');
                return;
            }
            setValue('#attendanceContract', contractLabel(firstContract));
            populateByContract();
            const firstVehicle = vehiclesFor(firstContract)[0] || '';
            if (firstVehicle) setValue('#attendanceVehicle', firstVehicle);
            populateDriversBySelection();
            setValue('#attendanceStartTime', '09:00');
            setValue('#attendanceEndTime', '17:00');
            selectedStatus = 'Completed';
            renderStatusChoices();
            updateSummary();
            toast('First saved contract assignment loaded.');
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
                <td><div class="list-cell"><div class="list-icon">📝</div><div><b>${escapeHtml(row.logId)}</b><br><small>${escapeHtml(row.date)}</small></div></div></td>
                <td>${escapeHtml(row.date || '-')}<br><small>${escapeHtml(row.startTime || '-')} to ${escapeHtml(row.endTime || '-')}</small></td>
                <td><b>${escapeHtml(row.contract || '-')}</b><br><small>${escapeHtml(row.vehicle || '-')}</small></td>
                <td><b>${escapeHtml(row.driver || '-')}</b></td>
                <td>${escapeHtml(row.hours || '0h 0m')}</td>
                <td><span class="badge ${cls}">${escapeHtml(row.status || '-')}</span></td>
                <td><button type="button" class="mini-btn view-attendance" data-id="${escapeHtml(row.logId)}">View</button><button type="button" class="mini-btn edit-attendance" data-id="${escapeHtml(row.logId)}">Edit</button><button type="button" class="mini-btn danger delete-attendance" data-id="${escapeHtml(row.logId)}">Delete</button></td>
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
                const haystack = [row.logId, row.contract, row.contractId, row.contractParty, row.vehicle, row.driver].join(' ').toLowerCase();
                return (!q || haystack.includes(q)) && (!status || row.status === status) && (!contract || row.contract === contract);
            });
            const tbody = $('#attendanceTbody');
            if (tbody) tbody.innerHTML = rows.length ? rows.map(rowHtml).join('') : '<tr><td colspan="7" class="empty">No attendance found. Click “Add Attendance” to create one.</td></tr>';
            if ($('#attendanceKpiTotal')) $('#attendanceKpiTotal').textContent = logs.length;
            if ($('#attendanceKpiCompleted')) $('#attendanceKpiCompleted').textContent = logs.filter((row) => row.status === 'Completed').length;
            if ($('#attendanceKpiRunning')) $('#attendanceKpiRunning').textContent = logs.filter((row) => row.status === 'Running').length;
            if ($('#attendanceKpiHours')) $('#attendanceKpiHours').textContent = (logs.reduce((sum, row) => sum + hoursToMinutes(row.hours), 0) / 60).toFixed(1);
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
            onVehicleChange();
            setValue('#attendanceDriver', row.driver);
            setValue('#attendanceStartTime', row.startTime);
            setValue('#attendanceEndTime', row.endTime);
            selectedStatus = row.status === 'Draft' ? 'Initiated' : row.status;
            renderStatusChoices();
            setValue('#attendanceNotes', row.notes);
            updateSummary();
            setVisible('attendanceAddPage');
        }

        function viewLog(id) {
            const row = logs.find((item) => item.logId === id);
            if (row) {
                alert(`${row.logId}\nDate: ${row.date}\nContract: ${row.contract}\nVehicle: ${row.vehicle}\nDriver: ${row.driver}\nTime: ${row.startTime} to ${row.endTime || '-'}\nHours: ${row.hours}\nStatus: ${row.status}\nNotes: ${row.notes || '-'}`);
            }
        }

        function deleteLog(id) {
            if (!confirm('Delete this attendance record?')) return;
            logs = logs.filter((row) => row.logId !== id);
            saveStore();
            renderList();
            toast('Attendance deleted.');
        }

        function exportLogs() {
            const rows = [['Attendance ID', 'Date', 'Contract', 'Contract ID', 'Vehicle', 'Vehicle ID', 'Driver', 'Driver ID', 'Start Time', 'End Time', 'Status', 'Hours', 'Notes']];
            logs.forEach((row) => rows.push([row.logId, row.date, row.contract, row.contractId, row.vehicle, row.vehicleId, row.driver, row.driverId, row.startTime, row.endTime, row.status, row.hours, row.notes]));
            exportCsv(rows, 'fleetman-driver-attendance-list.csv');
        }

        $('#attendanceContract')?.addEventListener('change', populateByContract);
        $('#attendanceContract')?.addEventListener('input', populateByContract);
        $('#attendanceVehicle')?.addEventListener('change', onVehicleChange);
        $('#attendanceVehicle')?.addEventListener('input', onVehicleChange);
        ['#attendanceStartTime', '#attendanceEndTime'].forEach((selector) => $(selector)?.addEventListener('input', updateSummary));
        $('#resetAttendanceBtn')?.addEventListener('click', resetForm);
        $('#saveAttendanceBtn')?.addEventListener('click', () => saveLog());
        $('#saveAttendanceDraftBtn')?.addEventListener('click', () => saveLog('Draft'));
        $('#loadAttendanceSampleBtn')?.addEventListener('click', loadSample);
        $('#newAttendanceBtn')?.addEventListener('click', () => { resetForm(); setVisible('attendanceAddPage'); });
        $('#exportAttendanceBtn')?.addEventListener('click', exportLogs);
        $('#applyAttendanceFiltersBtn')?.addEventListener('click', renderList);
        $('#clearAttendanceFiltersBtn')?.addEventListener('click', () => {
            ['#attendanceSearch', '#attendanceFilterStatus', '#attendanceFilterContract'].forEach((selector) => setValue(selector, ''));
            renderList();
        });
        ['#attendanceSearch', '#attendanceFilterStatus', '#attendanceFilterContract'].forEach((selector) => $(selector)?.addEventListener('input', renderList));

        document.addEventListener('click', (event) => {
            const status = event.target.closest('[data-attendance-status]');
            if (status) {
                selectedStatus = status.dataset.attendanceStatus;
                renderStatusChoices();
                updateSummary();
            }
            const now = event.target.closest('[data-time-now]');
            if (now) setNow(now.dataset.timeNow);
            const clear = event.target.closest('[data-clear-field]');
            if (clear) {
                setValue('#' + clear.dataset.clearField, '');
                updateSummary();
            }
            const view = event.target.closest('.view-attendance');
            if (view) viewLog(view.dataset.id);
            const edit = event.target.closest('.edit-attendance');
            if (edit) editLog(edit.dataset.id);
            const del = event.target.closest('.delete-attendance');
            if (del) deleteLog(del.dataset.id);
        });

        populateBase();
        resetForm();
        renderList();
        setVisible('attendanceListPage');
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

/* Master Data page logic: dynamic database-backed lookup rows for app-wide dropdowns. */
(() => {
    'use strict';

    const data = window.FLEETMAN || {};
    const resources = data.resources || {};
    const masterData = data.masterData || { vehicle_categories: [], vehicle_sub_categories: [], party_types: [], document_names: [], licence_types: [], client_types: [], contact_methods: [], fuel_types: [], fuel_units: [] };
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
        let clientTypes = Array.isArray(masterData.client_types) ? masterData.client_types.slice() : [];
        let contactMethods = Array.isArray(masterData.contact_methods) ? masterData.contact_methods.slice() : [];
        let fuelTypes = Array.isArray(masterData.fuel_types) ? masterData.fuel_types.slice() : [];
        let fuelUnits = Array.isArray(masterData.fuel_units) ? masterData.fuel_units.slice() : [];

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
                return;
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ vehicle_categories: vehicleCategories, vehicle_sub_categories: vehicleSubCategories, party_types: partyTypes, document_names: documentNames, licence_types: licenceTypes, client_types: clientTypes, contact_methods: contactMethods, fuel_types: fuelTypes, fuel_units: fuelUnits }),
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
                        clientTypes = Array.isArray(payload.masterData.client_types) ? payload.masterData.client_types : clientTypes;
                        contactMethods = Array.isArray(payload.masterData.contact_methods) ? payload.masterData.contact_methods : contactMethods;
                        fuelTypes = Array.isArray(payload.masterData.fuel_types) ? payload.masterData.fuel_types : fuelTypes;
                        fuelUnits = Array.isArray(payload.masterData.fuel_units) ? payload.masterData.fuel_units : fuelUnits;
                        renderAll();
                    }
                })
                .catch((error) => toast(error.message || 'Could not save master data to database.'));
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
            setValue('#documentNameMasterSort', '0');
            setValue('#documentNameMasterStatus', 'Active');
            setValue('#documentNameMasterDescription', '');
            setText('#saveDocumentNameMasterBtn', 'Save Document Name');
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
            if (!name) {
                toast('Document Name is required.');
                return null;
            }

            return {
                code: codeFrom(value('#documentNameMasterCode') || name),
                name,
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
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-vehicle-category="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-vehicle-category="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="6" class="empty">No vehicle category added yet.</td></tr>';
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
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td>${escapeHtml(vehicleCategoryName(row.vehicleCategoryCode))}</td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-vehicle-sub-category="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-vehicle-sub-category="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="7" class="empty">No vehicle sub category added yet.</td></tr>';
        }

        function renderPartyTypes() {
            setText('#masterPartyTypeCount', partyTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#partyTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(partyTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-party="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-party="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="6" class="empty">No party type added yet.</td></tr>';
        }

        function renderDocumentNames() {
            setText('#masterDocumentNameCount', documentNames.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#documentNameMasterTbody');
            if (!tbody) return;

            const rows = sortRows(documentNames);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-document="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-document="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="6" class="empty">No document name added yet.</td></tr>';
        }

        function renderLicenceTypes() {
            setText('#masterLicenceTypeCount', licenceTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#licenceTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(licenceTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-licence="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-licence="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="6" class="empty">No licence type added yet.</td></tr>';
        }

        function renderFuelTypes() {
            setText('#masterFuelTypeCount', fuelTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelTypeMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-type="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-type="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="6" class="empty">No fuel type added yet.</td></tr>';
        }

        function renderFuelUnits() {
            setText('#masterFuelUnitCount', fuelUnits.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelUnitMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelUnits);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-unit="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-unit="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="6" class="empty">No fuel unit added yet.</td></tr>';
        }

        function renderClientTypes() {
            setText('#masterClientTypeCount', clientTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#clientTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(clientTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-client="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-client="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="6" class="empty">No client type added yet.</td></tr>';
        }

        function renderContactMethods() {
            setText('#masterContactMethodCount', contactMethods.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#contactMethodMasterTbody');
            if (!tbody) return;

            const rows = sortRows(contactMethods);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td><b>${escapeHtml(row.name)}</b></td>
                    <td><span class="master-code">${escapeHtml(row.code)}</span></td>
                    <td>${Number(row.sortOrder || 0)}</td>
                    <td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td>
                    <td class="master-description">${escapeHtml(row.description || '—')}</td>
                    <td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-contact-method="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-contact-method="${escapeHtml(row.code)}">Delete</button></div></td>
                </tr>`).join('') : '<tr><td colspan="6" class="empty">No contact method added yet.</td></tr>';
        }

        function renderAll() {
            renderVehicleCategories();
            renderVehicleSubCategories();
            renderPartyTypes();
            renderDocumentNames();
            renderLicenceTypes();
            renderClientTypes();
            renderContactMethods();
            renderFuelTypes();
            renderFuelUnits();
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
            setValue('#documentNameMasterSort', row.sortOrder || 0);
            setValue('#documentNameMasterStatus', row.status || 'Active');
            setValue('#documentNameMasterDescription', row.description || '');
            setText('#saveDocumentNameMasterBtn', 'Update Document Name');
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

        $('#vehicleCategoryMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectVehicleCategory();
            if (!row) return;
            const editingCode = value('#vehicleCategoryEditingCode');
            vehicleCategories = upsertRow(vehicleCategories, row, editingCode);
            if (editingCode && editingCode !== row.code) {
                vehicleSubCategories = vehicleSubCategories.map((subRow) => subRow.vehicleCategoryCode === editingCode ? { ...subRow, vehicleCategoryCode: row.code, vehicleCategoryName: row.name } : subRow);
            }
            resetVehicleCategoryForm();
            renderAll();
            saveStore();
            toast('Vehicle category saved to database.');
        });

        $('#vehicleSubCategoryMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectVehicleSubCategory();
            if (!row) return;
            vehicleSubCategories = upsertRow(vehicleSubCategories, row, value('#vehicleSubCategoryEditingCode'));
            resetVehicleSubCategoryForm();
            renderAll();
            saveStore();
            toast('Vehicle sub category saved to database.');
        });

        $('#partyTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectPartyType();
            if (!row) return;
            partyTypes = upsertRow(partyTypes, row, value('#partyTypeEditingCode'));
            resetPartyTypeForm();
            renderAll();
            saveStore();
            toast('Party type saved to database.');
        });

        $('#documentNameMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectDocumentName();
            if (!row) return;
            documentNames = upsertRow(documentNames, row, value('#documentNameEditingCode'));
            resetDocumentNameForm();
            renderAll();
            saveStore();
            toast('Document name saved to database.');
        });

        $('#licenceTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectLicenceType();
            if (!row) return;
            licenceTypes = upsertRow(licenceTypes, row, value('#licenceTypeEditingCode'));
            resetLicenceTypeForm();
            renderAll();
            saveStore();
            toast('Licence type saved to database.');
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

        $('#clientTypeMasterForm')?.addEventListener('submit', (event) => {
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

        $('#resetClientTypeMasterBtn')?.addEventListener('click', resetClientTypeForm);
        $('#cancelClientTypeEditBtn')?.addEventListener('click', resetClientTypeForm);
        $('#resetContactMethodMasterBtn')?.addEventListener('click', resetContactMethodForm);
        $('#cancelContactMethodEditBtn')?.addEventListener('click', resetContactMethodForm);

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
        $('#licenceTypeMasterName')?.addEventListener('input', () => {
            if (!value('#licenceTypeMasterCode') || !value('#licenceTypeEditingCode')) setValue('#licenceTypeMasterCode', codeFrom(value('#licenceTypeMasterName')));
        });

        document.addEventListener('click', (event) => {
            const editVehicleCategoryBtn = event.target.closest('[data-master-edit-vehicle-category]');
            if (editVehicleCategoryBtn) editVehicleCategory(editVehicleCategoryBtn.dataset.masterEditVehicleCategory);

            const deleteVehicleCategoryBtn = event.target.closest('[data-master-delete-vehicle-category]');
            if (deleteVehicleCategoryBtn && confirm('Delete this vehicle category from master data? Related sub categories will also be removed.')) {
                const deletingCode = deleteVehicleCategoryBtn.dataset.masterDeleteVehicleCategory;
                vehicleCategories = vehicleCategories.filter((row) => row.code !== deletingCode);
                vehicleSubCategories = vehicleSubCategories.filter((row) => row.vehicleCategoryCode !== deletingCode);
                renderAll();
                saveStore();
                toast('Vehicle category deleted from database.');
            }

            const editVehicleSubCategoryBtn = event.target.closest('[data-master-edit-vehicle-sub-category]');
            if (editVehicleSubCategoryBtn) editVehicleSubCategory(editVehicleSubCategoryBtn.dataset.masterEditVehicleSubCategory);

            const deleteVehicleSubCategoryBtn = event.target.closest('[data-master-delete-vehicle-sub-category]');
            if (deleteVehicleSubCategoryBtn && confirm('Delete this vehicle sub category from master data?')) {
                vehicleSubCategories = vehicleSubCategories.filter((row) => row.code !== deleteVehicleSubCategoryBtn.dataset.masterDeleteVehicleSubCategory);
                renderAll();
                saveStore();
                toast('Vehicle sub category deleted from database.');
            }

            const editPartyBtn = event.target.closest('[data-master-edit-party]');
            if (editPartyBtn) editParty(editPartyBtn.dataset.masterEditParty);

            const deletePartyBtn = event.target.closest('[data-master-delete-party]');
            if (deletePartyBtn && confirm('Delete this party type from master data?')) {
                partyTypes = partyTypes.filter((row) => row.code !== deletePartyBtn.dataset.masterDeleteParty);
                renderAll();
                saveStore();
                toast('Party type deleted from database.');
            }

            const editDocumentBtn = event.target.closest('[data-master-edit-document]');
            if (editDocumentBtn) editDocument(editDocumentBtn.dataset.masterEditDocument);

            const deleteDocumentBtn = event.target.closest('[data-master-delete-document]');
            if (deleteDocumentBtn && confirm('Delete this document name from master data?')) {
                documentNames = documentNames.filter((row) => row.code !== deleteDocumentBtn.dataset.masterDeleteDocument);
                renderAll();
                saveStore();
                toast('Document name deleted from database.');
            }

            const editLicenceBtn = event.target.closest('[data-master-edit-licence]');
            if (editLicenceBtn) editLicenceType(editLicenceBtn.dataset.masterEditLicence);

            const deleteLicenceBtn = event.target.closest('[data-master-delete-licence]');
            if (deleteLicenceBtn && confirm('Delete this licence type from master data?')) {
                licenceTypes = licenceTypes.filter((row) => row.code !== deleteLicenceBtn.dataset.masterDeleteLicence);
                renderAll();
                saveStore();
                toast('Licence type deleted from database.');
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

            const editFuelUnitBtn = event.target.closest('[data-master-edit-fuel-unit]');
            if (editFuelUnitBtn) editFuelUnit(editFuelUnitBtn.dataset.masterEditFuelUnit);

            const deleteFuelUnitBtn = event.target.closest('[data-master-delete-fuel-unit]');
            if (deleteFuelUnitBtn && confirm('Delete this fuel unit from master data?')) {
                fuelUnits = fuelUnits.filter((row) => row.code !== deleteFuelUnitBtn.dataset.masterDeleteFuelUnit);
                renderAll();
                saveStore();
                toast('Fuel unit deleted from database.');
            }

            const editClientBtn = event.target.closest('[data-master-edit-client]');
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

        resetVehicleCategoryForm();
        resetVehicleSubCategoryForm();
        resetFuelTypeForm();
        resetFuelUnitForm();
        resetClientTypeForm();
        resetContactMethodForm();
        resetPartyTypeForm();
        resetDocumentNameForm();
        resetLicenceTypeForm();
        renderAll();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.body.dataset.page === 'master-data') initMasterData();
    });
})();

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
        const documentTypes = ['PDF', 'DOCX', 'XLSX', 'JPG', 'PNG'];

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

        function fileInfoHtml(file = {}, selectedName = '') {
            if (selectedName) return `Selected: <b>${escapeHtml(selectedName)}</b>. It will be stored after save.`;
            if (file.fileUrl || file.filePath) {
                const label = file.originalName || file.fileName || 'Uploaded document';
                const link = file.fileUrl ? ` · <a href="${escapeHtml(file.fileUrl)}" target="_blank" rel="noopener">View file</a>` : '';
                return `Uploaded: <b>${escapeHtml(label)}</b>${link}`;
            }
            return 'Choose PDF/image/office file. It will be stored after Save Contract.';
        }

        function renderFileInfo(row, selectedFile = null) {
            const info = $('.contract-upload-info', row);
            if (!info) return;
            const fileData = parseHiddenFile($('.contractDocExistingFile', row));
            info.innerHTML = fileInfoHtml(fileData, selectedFile?.name || '');
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
                        <div class="contract-card-hint">One vehicle and one driver pair with rate and duty hour.</div>
                    </div>
                    <button class="btn light small remove-contract-card" type="button">Remove</button>
                </div>
                <div class="contract-grid">
                    <div class="field contract-col-3">
                        <label>Driver <span class="req">*</span></label>
                        <select class="contractAsgDriver">${optionHtml(masters.drivers || [], driverId, 'Select driver')}</select>
                    </div>
                    <div class="field contract-col-4">
                        <label>Vehicle <span class="req">*</span></label>
                        <select class="contractAsgVehicle">${optionHtml(masters.vehicles || [], vehicleId, 'Select vehicle')}</select>
                    </div>
                    <div class="field contract-col-2">
                        <label>Vehicle Hourly Rate <span class="req">*</span></label>
                        <input class="contractAsgRate" type="number" step="0.01" value="${escapeHtml(row.rate ?? '')}" placeholder="0">
                    </div>
                    <div class="field contract-col-2">
                        <label>Vehicle Duty Hour <span class="req">*</span></label>
                        <input class="contractAsgDuty" type="number" step="0.01" value="${escapeHtml(row.duty ?? '')}" placeholder="0">
                    </div>
                    <div class="field contract-col-1">
                        <label>Weight</label>
                        <input class="contractAsgWeight" type="number" step="0.01" value="${escapeHtml(row.weight ?? 1)}">
                    </div>
                </div>`;
            wrapper.appendChild(card);
        }

        function addDocument(row = {}) {
            documentCounter += 1;
            const wrapper = $('#contractDocuments');
            if (!wrapper) return;
            const card = document.createElement('div');
            card.className = 'contract-doc-card';
            const documentNameOptions = (documentNames || []).map((name) => ({ id: name, label: name }));
            card.innerHTML = `
                <div class="contract-card-head">
                    <div>
                        <div class="contract-card-title">Document ${documentCounter}</div>
                        <div class="contract-card-hint">Name the document before attaching a file.</div>
                    </div>
                    <button class="btn light small remove-contract-card" type="button">Remove</button>
                </div>
                <div class="contract-grid">
                    <div class="field contract-col-4">
                        <label>Document Name</label>
                        <select class="contractDocName">${optionHtml(documentNameOptions, row.name || '', 'Select document name')}</select>
                    </div>
                    <div class="field contract-col-3">
                        <label>Document Type</label>
                        <select class="contractDocType">${optionHtml(documentTypes, row.type || '', 'Select document type')}</select>
                    </div>
                    <div class="field contract-col-5">
                        <label>Attach File</label>
                        <input class="contractDocFile" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                        <input class="contractDocExistingFile" type="hidden" value="${escapeHtml(hiddenFileValue(row.file || {}))}">
                        <div class="contract-upload-info">${fileInfoHtml(row.file || {})}</div>
                    </div>
                </div>`;
            wrapper.appendChild(card);
        }

        function clearRepeating() {
            if ($('#contractAssignments')) $('#contractAssignments').innerHTML = '';
            if ($('#contractDocuments')) $('#contractDocuments').innerHTML = '';
            assignmentCounter = 0;
            documentCounter = 0;
        }

        function resetForm() {
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
                    weight: Number($('.contractAsgWeight', card)?.value || 1),
                };
            });
        }

        function collectDocuments() {
            return $$('.contract-doc-card').map((card) => ({
                name: $('.contractDocName', card)?.value || 'Unnamed Document',
                type: $('.contractDocType', card)?.value || '',
                file: parseHiddenFile($('.contractDocExistingFile', card)),
            }));
        }

        function collectDocumentFiles() {
            const files = [];
            $$('.contract-doc-card').forEach((card, index) => {
                const file = $('.contractDocFile', card)?.files?.[0] || null;
                if (file) files.push({ index, file });
            });
            return files;
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

        function validateContract(row, savedAs) {
            if (savedAs === 'Draft') return true;
            if (!row.contractId || !row.contractWith || !row.partyId || !row.partyName || !row.amount || !row.status || !row.contractStart || !row.contractEnd || !row.details) {
                toast('Please fill all required contract information before submitting.');
                return false;
            }
            if (!row.assignments.length) {
                toast('Please add at least one vehicle and driver assignment.');
                return false;
            }
            const invalidAssignment = row.assignments.find((item) => !item.vehicleId || !item.driverId || !item.rate || !item.duty);
            if (invalidAssignment) {
                toast('Please complete vehicle, driver, rate, and duty hour for every assignment.');
                return false;
            }
            return true;
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

        function syncContracts(documentFiles, rowIndex) {
            const saveUrl = endpoint();
            if (!saveUrl) return Promise.resolve();
            const formData = new FormData();
            formData.append('rows', JSON.stringify(contracts));
            documentFiles.forEach((item) => {
                formData.append(`contract_document_files[${rowIndex}][${item.index}]`, item.file);
            });
            return fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: formData,
            }).then((response) => {
                if (!response.ok) throw new Error('Contract database save failed.');
                return response.json();
            }).then((payload) => {
                if (Array.isArray(payload.rows)) contracts = payload.rows;
            });
        }

        function saveContract(savedAs) {
            const row = collectContract(savedAs);
            if (!validateContract(row, savedAs)) return;
            const documentFiles = collectDocumentFiles();
            const rowIndex = upsertLocal(row);
            syncContracts(documentFiles, rowIndex)
                .then(() => {
                    currentPage = 1;
                    renderList();
                    setPage('contractListPage');
                    toast(savedAs === 'Draft' ? 'Contract draft saved.' : 'Contract submitted successfully.');
                })
                .catch((error) => {
                    toast(error.message || 'Contract save failed. Please check server connection.');
                });
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
                        <td class="contract-sticky-1"><b>${escapeHtml(row.contractId)}</b></td>
                        <td class="contract-sticky-2">${escapeHtml(row.partyName || '-')}</td>
                        <td class="contract-sticky-3">${escapeHtml(row.contractWith || '-')}</td>
                        <td><span class="badge ${badgeClass(row.status)}">${escapeHtml(row.status || '-')}</span></td>
                        <td>${money(row.amount)}</td>
                        <td>${formatDate(row.contractStart)}</td>
                        <td>${formatDate(row.contractEnd)}</td>
                        <td>${(row.assignments || []).length}</td>
                        <td>${new Set((row.assignments || []).map((item) => item.driverId || item.driver)).size}</td>
                        <td>${(row.documents || []).length}</td>
                        <td><span class="badge ${badgeClass(row.savedAs)}">${escapeHtml(row.savedAs || '-')}</span></td>
                        <td><button class="mini-btn view-contract" type="button" data-id="${escapeHtml(row.contractId)}">View</button><button class="mini-btn edit-contract" type="button" data-id="${escapeHtml(row.contractId)}">Edit</button><button class="mini-btn danger delete-contract" type="button" data-id="${escapeHtml(row.contractId)}">Delete</button></td>
                    </tr>`).join('') : '<tr><td colspan="12"><div class="contract-empty">No contract found for the selected filters.</div></td></tr>';
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
            if (!row) return;
            const docs = (row.documents || []).map((doc) => doc.name).filter(Boolean).join(', ') || '-';
            alert(`${row.contractId}\nParty: ${row.partyName}\nContract With: ${row.contractWith}\nStatus: ${row.status}\nSaved As: ${row.savedAs}\nAmount: ${money(row.amount)}\nAssignments: ${(row.assignments || []).length}\nDocuments: ${docs}`);
        }

        function editContract(id) {
            const row = contracts.find((item) => item.contractId === id);
            if (!row) return;
            loadContract(row);
            setPage('contractCreatePage');
        }

        function deleteContract(id) {
            if (!confirm('Delete this contract?')) return;
            contracts = contracts.filter((row) => row.contractId !== id);
            syncContracts([], 0).then(() => {
                renderList();
                toast('Contract deleted.');
            }).catch(() => {
                renderList();
                toast('Deleted locally, but database sync failed.');
            });
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
            if (remove) remove.closest('.contract-assignment-card,.contract-doc-card')?.remove();

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
            if (del) deleteContract(del.dataset.id);
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
        $('#newContractBtn')?.addEventListener('click', () => { resetForm(); setPage('contractCreatePage'); });
        $('#exportContractsBtn')?.addEventListener('click', exportContracts);
        $('#contractPrevPageBtn')?.addEventListener('click', () => { if (currentPage > 1) { currentPage -= 1; renderList(); } });
        $('#contractNextPageBtn')?.addEventListener('click', () => {
            const pages = Math.max(1, Math.ceil(filteredContracts().length / rowsPerPage));
            if (currentPage < pages) { currentPage += 1; renderList(); }
        });
        ['#contractFilterStatus', '#contractFilterWith', '#contractFilterParty'].forEach((selector) => $(selector)?.addEventListener('input', () => { currentPage = 1; renderList(); }));
        $('#contractRowsPerPage')?.addEventListener('change', () => { rowsPerPage = Number(value('#contractRowsPerPage') || 10); currentPage = 1; renderList(); });
        document.addEventListener('change', (event) => {
            const file = event.target.closest('.contractDocFile');
            if (file) renderFileInfo(file.closest('.contract-doc-card'), file.files?.[0] || null);
        });

        resetForm();
        renderList();
        setPage('contractCreatePage');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.body.dataset.page === 'contracts') initContracts();
    });
})();
