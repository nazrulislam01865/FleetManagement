/* Shared record details modal for FleetMan entities. */
document.addEventListener('DOMContentLoaded', () => document.body.classList.remove('preload'), { once: true });
window.FleetmanFormatCreatedAt = window.FleetmanFormatCreatedAt || ((value) => {
    if (!value) return '—';
    const raw = String(value).trim();
    const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(raw)
        ? raw.replace(' ', 'T') + 'Z'
        : raw;
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) return raw;
    return new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Dhaka',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).format(parsed);
});
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
        const path = String(file.filePath || file.path || '').replace(/^public\//, '').replace(/^storage\//, '');
        const template = String(window.FLEETMAN?.resources?.uploads?.file_template || '');
        if (path && template) {
            const encodedPath = path.split('/').map((part) => encodeURIComponent(part)).join('/');
            return template.replace('__PATH__', encodedPath);
        }
        if (file.previewUrl) return String(file.previewUrl);
        if (file.fileUrl || file.url) return String(file.fileUrl || file.url);
        if (!path) return '';
        if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
        return '/storage/' + path;
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
        return record.yardName || record.fullName || record.clientName || record.partyName || record.name || record.tripId || record.id || record.employeeId || record.driverId || 'Record Details';
    }

    function subtitleFor(type, record = {}) {
        const values = [];
        if (record.yardId) values.push(record.yardId);
        if (record.id) values.push(record.id);
        if (record.driverId) values.push(record.driverId);
        if (record.employeeId) values.push(record.employeeId);
        if (record.clientId) values.push(record.clientId);
        if (record.partyId) values.push(record.partyId);
        if (record.tripId) values.push(record.tripId);
        if (record.status) values.push(record.status);
        return values.join(' • ');
    }

    function recordDetailUrl(record = {}) {
        const resourceByPage = {
            yards: 'yards',
            vehicles: 'vehicles',
            'fuel-prices': 'fuel_prices',
            'fuel-recharge': 'fuel_recharges',
            vendors: 'parties',
            trips: 'trips',
            drivers: 'drivers',
            clients: 'clients',
            employees: 'employees',
            'driver-attendance': 'driver_attendance',
            contracts: 'contracts',
        };
        const idKeysByResource = {
            yards: ['yardId'],
            vehicles: ['id'],
            fuel_prices: ['fuelPriceId'],
            fuel_recharges: ['rechargeId'],
            parties: ['partyId'],
            trips: ['tripId'],
            drivers: ['driverId'],
            clients: ['clientId'],
            employees: ['employeeId'],
            driver_attendance: ['logId'],
            contracts: ['contractId'],
        };
        const resource = resourceByPage[String(document.body.dataset.page || '')];
        const template = String(window.FLEETMAN?.resources?.[resource]?.show_template || '');
        const idKeys = idKeysByResource[resource] || [];
        const code = idKeys.map((key) => record?.[key]).find((value) => String(value ?? '').trim() !== '');

        if (!resource || !template || code === undefined || code === null || String(code).trim() === '') return '';

        return template.replace('__CODE__', encodeURIComponent(String(code)));
    }

    function show(type, record = {}) {
        if (!canViewDetails()) {
            toast('Only Super Admin and Admin User can view full details.');
            return;
        }
        if (!record) return;

        const detailsUrl = recordDetailUrl(record);
        if (detailsUrl) {
            window.location.assign(detailsUrl);
            return;
        }

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

window.FleetmanEntityAvatar = window.FleetmanEntityAvatar || (() => {
    'use strict';

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ch]));

    function fileUrl(file = {}) {
        if (typeof file === 'string') {
            const value = file.trim();
            if (!value) return '';
            if (/^https?:\/\//i.test(value) || value.startsWith('/')) return value;
            file = { filePath: value };
        }

        if (!file || typeof file !== 'object' || Array.isArray(file)) return '';

        const path = String(file.filePath || file.path || '').replace(/^public\//, '').replace(/^storage\//, '').replace(/^\/+/, '');
        const template = String(window.FLEETMAN?.resources?.uploads?.file_template || '');
        if (path && template) {
            const encodedPath = path.split('/').map((part) => encodeURIComponent(part)).join('/');
            return template.replace('__PATH__', encodedPath);
        }

        return String(file.previewUrl || file.fileUrl || file.url || '');
    }

    function html(file = {}, settings = {}) {
        const fallback = settings.fallback || '👤';
        const alt = settings.alt || 'Record image';
        const allowedSizes = new Set(['table', 'compact', 'large']);
        const size = allowedSizes.has(settings.size) ? settings.size : 'table';
        const extraClass = String(settings.className || '').replace(/[^a-zA-Z0-9 _-]/g, '').trim();
        const url = fileUrl(file);
        const image = url
            ? `<img src="${escapeHtml(url)}" alt="${escapeHtml(alt)}" loading="lazy" decoding="async" onerror="this.remove()">`
            : '';

        return `<span class="entity-avatar entity-avatar-${size}${extraClass ? ` ${escapeHtml(extraClass)}` : ''}">${image}<span class="entity-avatar-fallback" aria-hidden="true">${escapeHtml(fallback)}</span></span>`;
    }

    return { fileUrl, html };
})();

window.FleetmanUniqueDocumentSelects = window.FleetmanUniqueDocumentSelects || (() => {
    'use strict';

    function uniqueOptions(options = []) {
        const seen = new Set();
        return (options || []).map((item) => String(item || '').trim()).filter((item) => {
            const key = item.toLowerCase();
            if (!item || seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }

    function refresh(containerOrSelector, selectSelector, options = [], placeholder = 'Select document') {
        const container = typeof containerOrSelector === 'string'
            ? document.querySelector(containerOrSelector)
            : containerOrSelector;
        if (!container) return;

        const selects = Array.from(container.querySelectorAll(selectSelector));
        const allOptions = uniqueOptions(options);
        const selectedValues = selects.map((select) => String(select.value || '').trim()).filter(Boolean);

        selects.forEach((select) => {
            const current = String(select.value || '').trim();
            const unavailable = new Set(selectedValues.filter((value) => value && value !== current).map((value) => value.toLowerCase()));
            const available = allOptions.filter((option) => option === current || !unavailable.has(option.toLowerCase()));

            select.innerHTML = '';
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = placeholder;
            select.appendChild(emptyOption);

            available.forEach((option) => {
                const node = document.createElement('option');
                node.value = option;
                node.textContent = option;
                select.appendChild(node);
            });

            if (current && !available.some((option) => option.toLowerCase() === current.toLowerCase())) {
                const legacyOption = document.createElement('option');
                legacyOption.value = current;
                legacyOption.textContent = current;
                select.appendChild(legacyOption);
            }

            select.value = current;
        });
    }

    function hasDuplicates(containerOrSelector, selectSelector) {
        const container = typeof containerOrSelector === 'string'
            ? document.querySelector(containerOrSelector)
            : containerOrSelector;
        if (!container) return false;

        const seen = new Set();
        return Array.from(container.querySelectorAll(selectSelector)).some((select) => {
            const value = String(select.value || '').trim().toLowerCase();
            if (!value) return false;
            if (seen.has(value)) return true;
            seen.add(value);
            return false;
        });
    }

    return { refresh, hasDuplicates };
})();

window.FleetmanTemporaryUploads = window.FleetmanTemporaryUploads || (() => {
    'use strict';

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ch]));
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resources = () => window.FLEETMAN?.resources?.uploads || {};
    const uploadScope = () => String(window.FLEETMAN?.page || document.body?.dataset?.page || '').trim().toLowerCase();

    function formatSize(bytes) {
        const value = Number(bytes || 0);
        if (value < 1024) return `${value} B`;
        if (value < 1024 * 1024) return `${(value / 1024).toFixed(value < 10240 ? 1 : 0)} KB`;
        return `${(value / (1024 * 1024)).toFixed(2)} MB`;
    }

    function readHidden(hidden) {
        if (!hidden?.value) return {};
        try { return JSON.parse(hidden.value) || {}; } catch (_) { return {}; }
    }

    function writeHidden(hidden, file) {
        if (hidden) hidden.value = file && Object.keys(file).length ? JSON.stringify(file) : '';
    }

    function permanentUrl(file = {}) {
        const path = String(file.filePath || '').replace(/^public\//, '').replace(/^storage\//, '');
        const template = String(resources().file_template || '');
        if (path && template) return template.replace('__PATH__', path.split('/').map(encodeURIComponent).join('/'));
        return file.previewUrl || file.fileUrl || file.url || '';
    }

    function isImage(file = {}) {
        const mime = String(file.mimeType || '').toLowerCase();
        const name = String(file.originalName || file.fileName || '').toLowerCase();
        return mime.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|svg)$/i.test(name);
    }

    function render({ info, progress, file = {}, message = '', error = false, showPreview = true }) {
        if (progress) {
            const uploading = Boolean(file.uploading);
            const temporarilyUploaded = Boolean(file.tempToken) && !uploading;
            progress.classList.toggle('hidden', !uploading && !temporarilyUploaded);
            const bar = progress.querySelector('.temp-upload-progress-bar');
            const label = progress.querySelector('.temp-upload-progress-label');
            const percentage = temporarilyUploaded ? 100 : Math.max(0, Math.min(100, Number(file.progress || 0)));
            if (bar) bar.style.width = `${percentage}%`;
            if (label) label.textContent = uploading
                ? `Uploading ${Math.round(percentage)}%`
                : (temporarilyUploaded ? 'Uploaded temporarily — save the form to keep this file.' : '');
        }
        if (!info) return;
        info.classList.toggle('upload-error', Boolean(error));
        if (message) {
            info.innerHTML = `<span class="${error ? 'upload-error-text' : ''}">${escapeHtml(message)}</span>`;
            return;
        }
        if (!(file.tempToken || file.filePath || file.fileUrl || file.previewUrl)) {
            info.innerHTML = '';
            return;
        }
        const url = permanentUrl(file);
        const name = file.originalName || file.fileName || 'Uploaded file';
        const size = formatSize(file.sizeBytes);
        const link = url ? `<a class="temp-upload-file-link" href="${escapeHtml(url)}" target="_blank" rel="noopener">${escapeHtml(name)}</a>` : `<b>${escapeHtml(name)}</b>`;
        const preview = showPreview && isImage(file) && url
            ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="temp-upload-preview-link"><img src="${escapeHtml(url)}" alt="${escapeHtml(name)}" class="temp-upload-preview"></a>`
            : '';
        info.innerHTML = `<div class="temp-upload-file">${link}<span>${escapeHtml(size)}</span></div>${preview}`;
    }

    function documentPolicy() {
        const configuredExtensions = resources().document_extensions;
        return {
            extensions: Array.isArray(configuredExtensions) && configuredExtensions.length
                ? configuredExtensions.map((item) => String(item).toLowerCase())
                : ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            maxBytes: Number(resources().document_max_bytes || 4 * 1024 * 1024),
            accept: String(resources().document_accept || '.pdf,.doc,.docx,.xls,.xlsx'),
        };
    }

    function documentOptions(options = {}) {
        const policy = documentPolicy();
        return {
            kind: 'document',
            extensions: policy.extensions,
            maxBytes: policy.maxBytes,
            showPreview: false,
            ...options,
        };
    }

    function validationMessage(file, options = {}) {
        if (!file) return 'Choose a file.';
        const extension = String(file.name || '').split('.').pop().toLowerCase();
        const allowed = (options.extensions || []).map((item) => String(item).toLowerCase());
        if (allowed.length && !allowed.includes(extension)) {
            const suffix = options.kind === 'document' ? ' Images are not allowed.' : '';
            return `Allowed file types: ${allowed.map((item) => item.toUpperCase()).join(', ')}.${suffix}`;
        }
        if (options.imageOnly && !String(file.type || '').toLowerCase().startsWith('image/')) {
            return 'Please choose an image file.';
        }
        if (options.maxBytes && file.size > options.maxBytes) {
            return `${file.name} is ${formatSize(file.size)}. Maximum allowed size is ${formatSize(options.maxBytes)}.`;
        }
        return '';
    }

    function responseMessage(response, fallback) {
        if (!response || typeof response !== 'object') return fallback;
        return response.message || Object.values(response.errors || {}).flat().join(' ') || fallback;
    }

    function randomUploadId() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID().toLowerCase();
        return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}-${Math.random().toString(36).slice(2)}`.toLowerCase();
    }

    function requestChunk(formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', resources().chunk_store, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.setRequestHeader('X-Fleet-Upload-Scope', uploadScope());
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) onProgress?.(event.loaded, event.total);
            });
            xhr.addEventListener('load', () => {
                let response = {};
                try { response = JSON.parse(xhr.responseText || '{}'); } catch (_) {}
                if (xhr.status >= 200 && xhr.status < 300 && response.ok) {
                    resolve(response);
                    return;
                }
                reject(new Error(responseMessage(response, 'A part of the document could not be uploaded.')));
            });
            xhr.addEventListener('error', () => reject(new Error('The upload failed because the server could not be reached.')));
            xhr.send(formData);
        });
    }

    async function cleanupChunk(uploadId) {
        const template = String(resources().chunk_destroy_template || '');
        if (!template || !uploadId) return;
        try {
            await fetch(template.replace('__UPLOAD_ID__', encodeURIComponent(uploadId)), {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Fleet-Upload-Scope': uploadScope() },
            });
        } catch (_) {}
    }

    async function uploadInChunks(file, options, payload) {
        const uploadId = randomUploadId();
        const configuredSize = Number(resources().chunk_bytes || 256 * 1024);
        const chunkSize = Math.max(64 * 1024, Math.min(512 * 1024, configuredSize));
        const totalChunks = Math.ceil(file.size / chunkSize);

        try {
            for (let index = 0; index < totalChunks; index += 1) {
                const start = index * chunkSize;
                const end = Math.min(file.size, start + chunkSize);
                const chunk = file.slice(start, end);
                const formData = new FormData();
                formData.append('upload_id', uploadId);
                formData.append('chunk_index', String(index));
                formData.append('total_chunks', String(totalChunks));
                formData.append('original_name', file.name);
                formData.append('mime_type', file.type || 'application/octet-stream');
                formData.append('file_size', String(file.size));
                formData.append('upload_kind', 'document');
                formData.append('upload_scope', uploadScope());
                formData.append('chunk', chunk, `${file.name}.part${index}`);

                let lastError = null;
                for (let attempt = 0; attempt < 2; attempt += 1) {
                    try {
                        await requestChunk(formData, (loaded) => {
                            payload.progress = ((start + loaded) / file.size) * 100;
                            render({ info: options.info, progress: options.progress, file: payload });
                        });
                        lastError = null;
                        break;
                    } catch (error) {
                        lastError = error;
                        if (attempt === 0) await new Promise((resolve) => setTimeout(resolve, 250));
                    }
                }
                if (lastError) throw lastError;

                payload.progress = (end / file.size) * 100;
                render({ info: options.info, progress: options.progress, file: payload });
            }

            const completion = await fetch(resources().chunk_complete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Fleet-Upload-Scope': uploadScope(),
                },
                body: JSON.stringify({ upload_id: uploadId, upload_scope: uploadScope() }),
            });
            let response = {};
            try { response = await completion.json(); } catch (_) {}
            if (!completion.ok || !response.file) {
                throw new Error(responseMessage(response, 'The uploaded document could not be finalized.'));
            }
            return response.file;
        } catch (error) {
            await cleanupChunk(uploadId);
            throw error;
        }
    }

    function uploadSingle(file, options, payload) {
        const endpoint = resources().store;
        if (!endpoint) return Promise.reject(new Error('Temporary upload service is unavailable.'));

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.setRequestHeader('X-Fleet-Upload-Scope', uploadScope());
            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) return;
                payload.progress = (event.loaded / event.total) * 100;
                render({ info: options.info, progress: options.progress, file: payload });
            });
            xhr.addEventListener('load', () => {
                let response = {};
                try { response = JSON.parse(xhr.responseText || '{}'); } catch (_) {}
                if (xhr.status < 200 || xhr.status >= 300 || !response.file) {
                    reject(new Error(responseMessage(response, 'The file could not be uploaded.')));
                    return;
                }
                resolve(response.file);
            });
            xhr.addEventListener('error', () => reject(new Error('The upload failed because the server could not be reached.')));
            const formData = new FormData();
            formData.append('file', file);
            formData.append('upload_kind', options.kind || 'generic');
            formData.append('upload_scope', uploadScope());
            xhr.send(formData);
        });
    }

    function upload(input, options = {}) {
        const file = options.file || input?.files?.[0] || null;
        const hidden = options.hidden;
        const info = options.info;
        const progress = options.progress;
        const localError = validationMessage(file, options);
        if (localError) {
            writeHidden(hidden, {});
            render({ info, progress, message: localError, error: true });
            if (input) input.value = '';
            options.onError?.(localError);
            return Promise.resolve(null);
        }

        const sequence = input ? Number(input._fleetUploadSequence || 0) + 1 : 1;
        if (input) input._fleetUploadSequence = sequence;

        const previous = readHidden(hidden);
        if (previous.tempToken) destroy(previous.tempToken).catch(() => {});

        const payload = { uploading: true, progress: 0 };
        render({ info, progress, file: payload, message: `Preparing ${file.name}...` });

        const useChunkedDocumentUpload = options.kind === 'document'
            && Boolean(resources().chunk_store)
            && Boolean(resources().chunk_complete);

        const promise = (useChunkedDocumentUpload
            ? uploadInChunks(file, options, payload)
            : uploadSingle(file, options, payload))
            .then((uploaded) => {
                if (input && input._fleetUploadSequence !== sequence) {
                    if (uploaded?.tempToken) destroy(uploaded.tempToken).catch(() => {});
                    return null;
                }
                writeHidden(hidden, uploaded);
                render({ info, progress, file: uploaded, showPreview: options.showPreview !== false });
                options.onSuccess?.(uploaded);
                return uploaded;
            })
            .catch((error) => {
                if (input && input._fleetUploadSequence !== sequence) return null;
                const message = error?.message || 'The file could not be uploaded.';
                writeHidden(hidden, {});
                render({ info, progress, message, error: true });
                options.onError?.(message);
                return null;
            });

        if (input) {
            input.value = '';
            input._fleetUploadPromise = promise;
        }
        if (options.promiseTarget) options.promiseTarget._fleetUploadPromise = promise;
        return promise;
    }

    async function waitForInputs(inputs = []) {
        const promises = inputs.map((input) => input?._fleetUploadPromise).filter(Boolean);
        if (promises.length) await Promise.all(promises);
    }

    async function destroy(token) {
        if (!token) return;
        const template = String(resources().destroy_template || '');
        if (!template) return;
        await fetch(template.replace('__TOKEN__', encodeURIComponent(token)), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Fleet-Upload-Scope': uploadScope() },
        });
    }

    function progressMarkup() {
        return '<div class="temp-upload-progress hidden"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div>';
    }

    return {
        upload,
        render,
        readHidden,
        writeHidden,
        permanentUrl,
        formatSize,
        waitForInputs,
        destroy,
        progressMarkup,
        isImage,
        documentPolicy,
        documentOptions,
    };
})();

/* One shared renderer keeps every document section identical to the vehicle page. */
window.FleetmanDocumentRows = window.FleetmanDocumentRows || (() => {
    'use strict';

    const ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));

    function jsonValue(value = {}) {
        try { return JSON.stringify(value || {}); } catch (_) { return '{}'; }
    }

    function optionMarkup(values = [], selected = '', placeholder = '') {
        const normalized = Array.from(new Set((values || [])
            .map((value) => typeof value === 'string' ? value : (value?.value || value?.id || value?.name || value?.label || ''))
            .map((value) => String(value || '').trim())
            .filter(Boolean)));
        return [`<option value="">${escapeHtml(placeholder)}</option>`]
            .concat(normalized.map((value) => `<option value="${escapeHtml(value)}" ${String(selected || '') === value ? 'selected' : ''}>${escapeHtml(value)}</option>`))
            .join('');
    }

    function reminderMarkup(values = [], selected = '') {
        const normalized = Array.from(new Set((values || [])
            .map((value) => typeof value === 'string' ? value : (value?.value || value?.id || value?.name || value?.label || ''))
            .map((value) => String(value || '').trim())
            .filter(Boolean)));
        return normalized.map((value, index) => `<option value="${escapeHtml(value)}" ${(String(selected || '') === value || (!selected && index === 0)) ? 'selected' : ''}>${escapeHtml(value)}</option>`).join('');
    }

    function create(config = {}) {
        const row = config.row || {};
        const fileData = config.fileData || ((row.file && typeof row.file === 'object') ? row.file : {});
        const element = document.createElement('div');
        element.className = ['repeat-row', 'doc-row', 'fleet-document-row', config.rowClass || ''].filter(Boolean).join(' ');
        Object.entries(config.dataset || {}).forEach(([key, value]) => { element.dataset[key] = String(value); });

        const classes = config.classes || {};
        const extraHidden = (config.extraHidden || []).map((input) =>
            `<input type="hidden" class="${escapeHtml(input.className || '')}" value="${escapeHtml(input.value || '')}">`
        ).join('');
        const reminderField = config.showReminder === false
            ? `<input type="hidden" class="${escapeHtml(classes.reminder || '')}" value="${escapeHtml(row.reminder || '')}">`
            : `<div class="field">
                <label>Reminder</label>
                <select class="${escapeHtml(classes.reminder || '')}">${reminderMarkup(config.reminders, row.reminder || '')}</select>
            </div>`;
        const nameControl = config.nameInput === true
            ? `<input class="${escapeHtml(classes.name || '')}" type="text" value="${escapeHtml(row.name || '')}" placeholder="${escapeHtml(config.namePlaceholder || 'Enter document name')}" required aria-required="true">`
            : `<select class="${escapeHtml(classes.name || '')}" required aria-required="true">${optionMarkup(config.names, row.name || '', config.namePlaceholder || 'Select document')}</select>`;

        element.innerHTML = `
            <div class="field">
                <label>Document Name <span class="req">*</span></label>
                ${nameControl}
                ${extraHidden}
            </div>
            <div class="field fleet-form-temporal-field">
                <label>Expiry Date</label>
                <input class="${escapeHtml(classes.expiry || '')}" type="date" value="${escapeHtml(row.expiry || row.expiryDate || '')}">
            </div>
            ${reminderField}
            <div class="field">
                <label>Upload Document <span class="req">*</span></label>
                <input class="${escapeHtml(classes.file || '')}" type="file" accept="${ACCEPT}" ${config.fileAttributes || ''}>
                <input class="${escapeHtml(classes.hidden || '')}" type="hidden" value="${escapeHtml(jsonValue(fileData))}">
                <div class="temp-upload-progress hidden ${escapeHtml(classes.progress || '')}"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div>
                <div class="upload-meta ${escapeHtml(classes.info || '')}"></div>
                <div class="hint">Allowed: PDF, DOC, DOCX, XLS or XLSX. Maximum size: 4 MB. Images are not allowed.</div>
            </div>
            <button type="button" class="mini-btn remove-row ${escapeHtml(config.removeClass || '')}">Remove</button>`;

        return { element, fileData };
    }

    return { create };
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
            const vehicleAmount = Math.max(0, Number(value('#vehicleRentalAmount') || 0));
            const driverAmount = rentalType === 'With Driver' ? Math.max(0, Number(value('#driverPaymentAmount') || 0)) : 0;
            setValue('#totalRentalAmount', (vehicleAmount + driverAmount).toFixed(2));
        }

        function toggleRentalFields() {
            const withDriver = value('#rentalType') === 'With Driver';
            const driver = $('#driver');
            const wrapper = $('#driverPaymentFields');

            // A vehicle must always have a selected driver, even when the
            // rental agreement itself is marked as "Without Driver".
            driver?.closest('.field')?.classList.remove('hidden');
            if (driver) {
                driver.required = true;
                driver.setAttribute('aria-required', 'true');
            }

            // Only the driver's payment information depends on rental type.
            wrapper?.classList.toggle('hidden', !withDriver);
            ['#driverPaymentAmount', '#driverPaymentCycle'].forEach((selector) => {
                const field = $(selector);
                if (!field) return;
                field.required = withDriver;
                field.setAttribute('aria-required', withDriver ? 'true' : 'false');
                if (!withDriver) {
                    field.value = '';
                    clearVehicleFieldError(field);
                }
            });
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
                    rentalType: value('#rentalType'),
                    driver: value('#driver'),
                    driverPaymentAmount: value('#driverPaymentAmount'),
                    driverPaymentCycle: value('#driverPaymentCycle'),
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
                    status: 'Active',
                    vehicleValidationVersion: 1,
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
                ['#driver', 'Driver is required.'],
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
            if (regInput?.value.trim() && !/^[A-Za-z]{3}-[A-Za-z]{2}-\d{2}-\d{4}$/.test(regInput.value.trim())) {
                invalidate(regInput, 'Use the format ABC-AB-12-3456.');
            }

            const engineInput = $('#engineNo');
            if (engineInput?.value.trim() && !/^[A-Za-z0-9]{17}$/.test(engineInput.value.trim())) {
                invalidate(engineInput, 'Engine Number must contain exactly 17 letters or digits.');
            }

            const driver = $('#driver');
            const validDriverValues = new Set(
                $$('#vehicleDriverList option').map((option) => String(option.value || '').trim())
            );
            if (driver?.value && !validDriverValues.has(String(driver.value).trim())) {
                invalidate(driver, 'Select a valid driver from the searchable list.');
            }

            if (vehicle.rentalType === 'With Driver') {
                const amount = $('#driverPaymentAmount');
                const cycle = $('#driverPaymentCycle');
                if (!String(amount?.value || '').trim()) invalidate(amount, 'Driver Payment Amount is required.');
                else if (Number(amount.value) < 0) invalidate(amount, 'Driver Payment Amount cannot be negative.');
                if (!cycle?.value) invalidate(cycle, 'Driver Payment Cycle is required.');
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

        async function saveVehicle() {
            await uploadManager.waitForInputs([$('#image'), ...$$('#vehicleDocRows .docFile')]);
            if (documentSelects.hasDuplicates('#vehicleDocRows', '.docName')) {
                toast('Each vehicle document type can be selected only once.');
                return;
            }
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

            const savedVehicleRows = Array.isArray(result?.rows) ? result.rows : [];
            if (!savedVehicleRows.some((savedRow) => String(savedRow?.id || '') === String(vehicle.id || ''))) {
                vehicles = previousVehicles;
                renderTable();
                toast('The vehicle was not found in the database response, so it was not added to the list.');
                return;
            }

            vehicles = savedVehicleRows;
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
            setValue('#rentalType', sample.rentalType || (sample.driverPaymentAmount ? 'With Driver' : 'Without Driver'));
            setValue('#driver', sample.driver);
            setValue('#driverPaymentAmount', sample.driverPaymentAmount ?? '');
            setValue('#driverPaymentCycle', sample.driverPaymentCycle || 'Monthly');
            setValue('#vehicleRentalAmount', sample.vehicleRentalAmount ?? sample.rent ?? 0);
            setValue('#vehiclePaymentCycle', sample.vehiclePaymentCycle || 'Monthly');
            toggleRentalFields();
            recalculateRentalTotal();
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
            setValue('#rentalType', vehicle.rentalType || (vehicle.driverPaymentAmount ? 'With Driver' : 'Without Driver'));
            setValue('#driver', vehicle.driver);
            setValue('#driverPaymentAmount', vehicle.driverPaymentAmount ?? '');
            setValue('#driverPaymentCycle', vehicle.driverPaymentCycle || 'Monthly');
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
                const imageUrl = uploadManager.permanentUrl(vehicle.image || {});
                const imageLink = imageUrl ? `<br><small><a href="${escapeHtml(imageUrl)}" target="_blank" rel="noopener">View image</a></small>` : '';
                const avatar = window.FleetmanEntityAvatar.html(vehicle.image || {}, {
                    fallback: '🚗',
                    alt: `${vehicle.name || 'Vehicle'} image`,
                    size: 'table',
                });
                return `
                <tr>
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(vehicle.createdAt || vehicle.created_at))}</td>
                    <td><div class="vehicle-cell">${avatar}<div><b>${escapeHtml(vehicle.name)}</b><br><small>${escapeHtml(vehicle.id)} · ${escapeHtml(vehicle.model)}</small>${imageLink}</div></div></td>
                    <td>${escapeHtml(vehicle.regNo)}</td>
                    <td>${escapeHtml(vehicle.category)}<br><small>${escapeHtml(vehicle.subCategory || '')}</small></td>
                    <td>${(vehicle.fuels || []).map((item) => `<span class="badge soft">${escapeHtml(item.priority)}: ${escapeHtml(item.type)} · ${escapeHtml(item.rate || '0')}</span>`).join('')}</td>
                    <td>${escapeHtml(vehicle.driver || 'Not assigned')}</td>
                    <td>${docs.length} document(s)<br><small>${docsWithFiles} uploaded file(s)${docsWithFiles ? ` · ${documentLinks(vehicle)}` : ''}</small></td>
                    <td>${Number(vehicle.totalRentalAmount ?? vehicle.rent ?? 0).toLocaleString()} BDT<br><small>${escapeHtml(vehicle.rentalType || '-')}</small></td>
                    <td><span class="badge ${vehicle.status === 'Active' ? 'ok' : 'warn'}">${escapeHtml(vehicle.status || '-')}</span></td>
                    <td><button type="button" class="mini-btn view-vehicle" data-id="${escapeHtml(vehicle.id)}">View</button><button type="button" class="mini-btn edit-vehicle" data-id="${escapeHtml(vehicle.id)}">Edit</button><button type="button" class="mini-btn danger delete-vehicle" data-id="${escapeHtml(vehicle.id)}">Delete</button></td>
                </tr>`;
            }).join('') : '<tr><td colspan="10" class="empty">No vehicles found.</td></tr>';

            $('#vehicleKpiTotal').textContent = vehicles.length;
            $('#vehicleKpiActive').textContent = vehicles.filter((vehicle) => vehicle.status === 'Active').length;
            $('#vehicleKpiDocs').textContent = vehicles.filter((vehicle) => (vehicle.docs || []).some((doc) => doc.expiry)).length;
            $('#vehicleKpiFuel').textContent = vehicles.filter((vehicle) => (vehicle.fuels || []).length > 1).length;
        }

        function exportCsv() {
            downloadCsv('fleetman-vehicle-list.csv', [
                ['Vehicle ID', 'Vehicle Name', 'Registration', 'Category', 'Fuels', 'Rental Type', 'Driver', 'Driver Payment', 'Driver Cycle', 'Vehicle Rental', 'Vehicle Cycle', 'Total Rental', 'Documents', 'Image', 'Status'],
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
        ['#driverPaymentAmount', '#vehicleRentalAmount'].forEach((selector) => $(selector)?.addEventListener('input', recalculateRentalTotal));
        $('#addFuelRowBtn')?.addEventListener('click', () => {
            if ($$('#vehicleFuelRows .fuel-row').length >= fuelPriorities.length) {
                toast('Primary, Secondary, and Tertiary priorities are already used.');
                return;
            }
            addFuelRow();
        });
        $('#addDocRowBtn')?.addEventListener('click', () => addDocRow());
        $('#clearVehicleBtn')?.addEventListener('click', () => resetForm());
        $('#saveVehicleBtn')?.addEventListener('click', saveVehicle);
        $('#loadVehicleSampleBtn')?.addEventListener('click', loadSample);
        $('#exportVehiclesBtn')?.addEventListener('click', exportCsv);
        $('#clearVehicleFiltersBtn')?.addEventListener('click', () => { ['#vehicleSearch', '#vehicleFilterCategory', '#vehicleFilterFuel', '#vehicleFilterStatus'].forEach((selector) => setValue(selector, '')); renderTable(); });
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
            if (del) deleteVehicle(del.dataset.id);
        });

        $('#vehicleAddPage')?.addEventListener('input', (event) => {
            if (event.target.matches('input,select,textarea')) clearVehicleFieldError(event.target);
        });
        $('#vehicleAddPage')?.addEventListener('change', (event) => {
            if (event.target.matches('input,select,textarea')) clearVehicleFieldError(event.target);
        });

        resetForm();
        renderTable();
        if (window.location.search.includes('action=add')) {
            setVisible('vehicleAddPage');
        } else {
            setVisible('vehicleListPage');
        }
    }

    function initFuelPrices() {
        const STORAGE = 'fleetman_fuel_prices_v2';
        let prices = Array.isArray(records.fuel_prices) ? records.fuel_prices : (samples.fuel_prices || []);

        async function saveStore() {
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
            const row = collect(statusOverride);
            if (!validateFuelPrice(row)) return;

            const previous = JSON.parse(JSON.stringify(prices || []));
            upsert(row);
            const saveButton = statusOverride === 'Draft' ? $('#saveFuelPriceDraftBtn') : $('#saveFuelPriceBtn');
            const originalText = saveButton?.textContent || '';
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
            }

            try {
                await saveStore();
                renderList();
                toast(row.status === 'Draft' ? 'Draft saved.' : 'Fuel price saved.');
                setVisible('fuelPriceListPage');
            } catch (error) {
                prices = previous;
                toast(error.message || 'Fuel price save failed.');
            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = originalText;
                }
            }
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

        async function deleteFuelPrice(id) {
            if (!confirm('Delete this fuel price from the list?')) return;
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
            if (del) deleteFuelPrice(del.dataset.id);
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

            const capturedAt = new Date();
            const preview = URL.createObjectURL(file);
            photoState[key] = {
                ...(photoState[key] || {}),
                captured: true,
                file,
                fileData: {},
                preview,
                capturedAt: capturedAt.toISOString(),
                displayTime: capturedAt.toLocaleString(),
                place: 'Getting place name...',
            };
            updatePhotoCard(key);
            updateCounter();
            clearRechargePhotoError(key);

            const hidden = $('.photoTempFile', card);
            const uploadPromise = uploadManager.upload(null, {
                file,
                promiseTarget: card,
                hidden,
                info: $('.photo-upload-info', card),
                progress: $('.photoUploadProgress', card),
                extensions: ['jpg', 'jpeg', 'png', 'webp'],
                imageOnly: true,
                maxBytes: 8 * 1024 * 1024,
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
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
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
                const file = new File([blob], `${key}-${Date.now()}.jpg`, { type: 'image/jpeg' });
                closeCamera();
                await saveCapturedPhoto(key, file);
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
            await uploadManager.waitForInputs($$('.photo-card'));
            if (!validateBeforeSubmit(true)) return;
            const submitBtn = $('#submitRechargeBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }
            const saved = await saveRecharge('Submitted', null);
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
                        <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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

        async function deleteRechargeEntry(id) {
            if (!confirm('Delete this fuel recharge entry?')) return;
            const previous = JSON.parse(JSON.stringify(recharges || []));
            recharges = recharges.filter((row) => row.rechargeId !== id);
            const result = await syncFuelRecharges(recharges);
            if (result?.syncFailed || result?.ok === false) {
                recharges = previous;
            } else if (Array.isArray(result?.rows)) {
                recharges = result.rows;
                toast('Fuel recharge entry deleted.');
            }
            renderRechargeList();
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
            if (deleteButton) deleteRechargeEntry(deleteButton.dataset.rechargeDelete);
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
            await uploadManager.waitForInputs($$('#partyDocuments .partyDocFile'));
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
                <td>${escapeHtml(window.FleetmanFormatCreatedAt(party.createdAt || party.created_at))}</td>
                <td><div class="party-cell"><div class="party-icon">🤝</div><div><b>${escapeHtml(party.partyName)}</b><br><small>${escapeHtml(party.partyId)}</small></div></div></td>
                <td><span class="badge soft">${escapeHtml(party.partyType || '-')}</span><br><small>${escapeHtml(party.vendorContractorType || '-')}</small>${(party.fuelTypes || []).length ? `<br><small>${escapeHtml((party.fuelTypes || []).join(', '))}</small>` : ''}</td>
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
                return (!query || [party.partyId, party.partyName, party.partyType, party.vendorContractorType, party.phone, party.email, party.tradeLicense, (party.fuelTypes || []).join(' '), contactText].join(' ').toLowerCase().includes(query))
                    && (!type || party.partyType === type)
                    && (!status || party.status === status)
                    && (!terms || party.paymentTerms === terms);
            });
            $('#partyTbody').innerHTML = list.length ? list.map(rowHtml).join('') : '<tr><td colspan="10" class="empty">No vendor / party found. Click “Add Vendor / Party” to create one.</td></tr>';
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
            if (del) deleteParty(del.dataset.id);
        });

        resetForm();
        renderList();
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
                input.required = show;
                input.setAttribute('aria-required', show ? 'true' : 'false');
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
            if (isClientVisit()) {
                if (!clientInput?.value.trim()) {
                    markInvalid(clientInput, 'Client is required for a Client Visit trip.');
                    errors.push(clientInput);
                } else if (!findClient(clientInput.value)) {
                    markInvalid(clientInput, 'Select a client from the suggestion list.');
                    errors.push(clientInput);
                }
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

        function collect() {
            const vehicleText = value('#tripVehicle').trim();
            const driverText = value('#tripDriver').trim();
            const vehicle = findVehicle(vehicleText);
            const driver = findDriver(driverText);
            const client = isClientVisit() ? findClient(value('#tripClient')) : null;
            const payments = collectPayments();
            const summary = recalculatePayment();
            return {
                tripValidationVersion: 2,
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

        async function saveTrip() {
            if (!validateTrip()) return;
            const trip = collect();
            const nextTrips = [...trips];
            const index = nextTrips.findIndex((item) => item.tripId === trip.tripId);
            if (index >= 0) nextTrips[index] = trip;
            else nextTrips.unshift(trip);

            const button = $('#saveTripBtn');
            const originalText = button?.textContent || 'Save Trip';
            if (button) {
                button.disabled = true;
                button.textContent = 'Saving...';
            }
            const result = await syncResource('trips', nextTrips);
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
            if (!result?.ok) return;

            trips = Array.isArray(result.rows) ? result.rows : nextTrips;
            renderList();
            toast('Trip saved successfully.');
            setVisible('tripListPage');
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

        async function deleteTrip(id) {
            if (!confirm('Delete this trip from the trip list?')) return;
            const nextTrips = trips.filter((trip) => trip.tripId !== id);
            const result = await syncResource('trips', nextTrips);
            if (!result?.ok) return;
            trips = Array.isArray(result.rows) ? result.rows : nextTrips;
            renderList();
            toast('Trip deleted.');
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
            return `<tr>
                <td>${escapeHtml(window.FleetmanFormatCreatedAt(trip.createdAt || trip.created_at))}</td>
                <td><div class="trip-cell"><div class="trip-icon">🧭</div><div><b>${escapeHtml(trip.tripId)}</b><br><small>${escapeHtml([trip.purpose || 'Trip', trip.client || ''].filter(Boolean).join(' · '))}</small></div></div></td>
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
            const rows = [['Trip ID', 'Start Date', 'Vehicle', 'Driver', 'Purpose', 'Client', 'From Location', 'To Location', 'Odo Start', 'Odo End', 'Total Cost', 'Paid Amount', 'Remaining Payment', 'Payments', 'Details']];
            trips.forEach((trip) => {
                const payments = tripPayments(trip).map((payment) => `${payment.method}: ${roundMoney(payment.amount).toFixed(2)}${payment.reference ? ` (${payment.reference})` : ''}`).join(' | ');
                rows.push([
                    trip.tripId,
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
        $('#saveTripBtn')?.addEventListener('click', saveTrip);
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
            if (del) deleteTrip(del.dataset.id);
        });

        resetForm();
        renderList();
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
            if (!hasUploadedFile(photo)) {
                markDriverInvalid(photoInput, 'Driver Photo is required.', photoBox);
                valid = false;
            } else if (Number(photo.sizeBytes || 0) > 100 * 1024) {
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
            renderList();
            toast(statusOverride==='Draft'?'Draft saved.':'Driver saved. Redirecting to driver list.');
            setVisible('driverListPage');
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

        function isExpiringSoon(row){ if(!row.licenseValidity) return false; return (new Date(row.licenseValidity)-new Date())/86400000 < licenseWarnDays; }

        function rowHtml(row){
            const exp=isExpiringSoon(row);
            const statusClass=row.status==='Active'?'ok':row.status==='Draft'?'warn':row.status==='Blacklisted'?'danger':'soft';
            const avatar = window.FleetmanEntityAvatar.html(row.photo || {}, {
                fallback: '🧑‍✈️',
                alt: `${row.fullName || 'Driver'} photo`,
                size: 'table',
            });
            return `<tr><td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td><td><div class="driver-cell">${avatar}<div><b>${escapeHtml(row.fullName)}</b><br><small>${escapeHtml(row.driverId)} · NID: ${escapeHtml(row.nid||'-')}</small></div></div></td><td>${escapeHtml(row.contact||'-')}<br><small>${row.whatsapp?'WA: '+escapeHtml(row.whatsapp):''}</small></td><td><span class="badge soft">${escapeHtml(row.licenseType||'-')}</span><br><small>${escapeHtml(row.licenseNo||'-')}</small></td><td><span class="badge ${exp?'warn':'ok'}">${escapeHtml(row.licenseValidity||'-')}</span></td><td>${escapeHtml(row.salary||0)} / ${escapeHtml(row.salaryTenure||'-')}<br><small>OT/Hour: ${escapeHtml(row.otRate||0)}</small></td><td>${escapeHtml(row.workingHour||0)} hrs<br><small>${escapeHtml(row.duty||'-')}</small></td><td>${escapeHtml(row.vendor||'None')}</td><td>${(row.documents||[]).length} document(s)</td><td><span class="badge ${statusClass}">${escapeHtml(row.status||'-')}</span></td><td><button type="button" class="mini-btn view-driver" data-id="${escapeHtml(row.driverId)}">View</button><button type="button" class="mini-btn edit-driver" data-id="${escapeHtml(row.driverId)}">Edit</button><button type="button" class="mini-btn danger delete-driver" data-id="${escapeHtml(row.driverId)}">Delete</button></td></tr>`;
        }

        function renderList(){
            const q=value('#driverSearch').toLowerCase(), status=value('#driverFilterStatus'), license=value('#driverFilterLicense'), tenure=value('#driverFilterTenure');
            const rows=drivers.filter((row)=>(!q||[row.fullName,row.contact,row.nid,row.licenseNo,row.driverId].join(' ').toLowerCase().includes(q))&&(!status||row.status===status)&&(!license||row.licenseType===license)&&(!tenure||row.salaryTenure===tenure));
            $('#driverTbody').innerHTML=rows.length?rows.map(rowHtml).join(''):'<tr><td colspan="11" class="empty">No driver found. Click “Add Driver” to create one.</td></tr>';
            $('#driverKpiTotal').textContent=drivers.length;
            $('#driverKpiActive').textContent=drivers.filter((row)=>row.status==='Active').length;
            $('#driverKpiExpired').textContent=drivers.filter(isExpiringSoon).length;
            $('#driverKpiDocs').textContent=drivers.reduce((sum,row)=>sum+(row.documents||[]).length,0);
        }

        function editDriver(id){
            const row=drivers.find((item)=>item.driverId===id);
            if(!row) return;
            populateDriverForm(row);
            setVisible('driverAddPage');
        }

        function viewDriver(id){ const row=drivers.find((item)=>item.driverId===id); if(row) window.FleetmanDetailViewer?.show('Driver Details', row); }
        function deleteDriver(id){ if(!confirm('Delete this driver from prototype list?')) return; drivers=drivers.filter((row)=>row.driverId!==id); saveStore(); renderList(); toast('Driver deleted.'); }
        function exportDrivers(){ const rows=[['Driver ID','Full Name','Contact','NID','License No','License Type','License Validity','Salary','Salary Tenure','Overtime Rate/Hourly','Working Hour','Vendor','Status','Documents']]; drivers.forEach((row)=>rows.push([row.driverId,row.fullName,row.contact,row.nid,row.licenseNo,row.licenseType,row.licenseValidity,row.salary,row.salaryTenure,row.otRate,row.workingHour,row.vendor,row.status,(row.documents||[]).map((doc)=>doc.name).join('; ')])); exportCsv(rows,'fleetman-driver-list.csv'); }

        $('#addDriverDocumentBtn')?.addEventListener('click',()=>addDocument());
        $('#addDriverContactBtn')?.addEventListener('click',()=>addContact());
        $('#resetDriverBtn')?.addEventListener('click',resetForm);
        $('#saveDriverBtn')?.addEventListener('click',()=>saveDriver());
        $('#saveDriverDraftBtn')?.addEventListener('click',()=>saveDriver('Draft'));
        $('#loadDriverSampleBtn')?.addEventListener('click',loadSample);
        $('#exportDriversBtn')?.addEventListener('click',exportDrivers);
        $('#clearDriverFiltersBtn')?.addEventListener('click',()=>{['#driverSearch','#driverFilterStatus','#driverFilterLicense','#driverFilterTenure'].forEach((selector)=>setValue(selector,'')); renderList();});
        ['#driverSearch','#driverFilterStatus','#driverFilterLicense','#driverFilterTenure'].forEach((selector)=>$(selector)?.addEventListener('input',renderList));

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
            const del=event.target.closest('.delete-driver'); if(del) deleteDriver(del.dataset.id);
        });

        resetForm();
        renderList();
        if (window.location.search.includes('action=add')) setVisible('driverAddPage');
        else setVisible('driverListPage');
    }

    function initClients() {
        const STORAGE='fleetman_clients_v2';
        let clients=Array.isArray(records.clients) ? records.clients : (samples.clients||[]);
        const phonePattern = /^\d{11}$/;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

        function saveStore(){ return syncResource('clients', clients); }
        function genId(){ return 'CLI' + new Date().toISOString().slice(2,10).replaceAll('-','') + Math.floor(100 + Math.random()*900); }
        function clientField(element){ return element?.closest('.field') || element; }
        function clearClientFieldError(element){
            const field=clientField(element);
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
        function markClientInvalid(element,message){
            if(!element) return;
            const field=clientField(element);
            if(!field) return;
            clearClientFieldError(element);
            field.classList.add('field-invalid');
            element.setAttribute?.('aria-invalid','true');
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
            $('#clientContacts').innerHTML='';
            addContact();
        }
        function collect(statusOverride){
            const contacts=$$('#clientContacts .contact-row').map((row)=>{
                const meta=$('.clientContactMeta',row)?.value.trim()||'';
                return {name:$('.clientContactName',row)?.value.trim()||'',role:$('.clientContactRole',row)?.value.trim()||'',phone:$('.clientContactPhone',row)?.value.trim()||'',whatsapp:meta.includes('@')?'':meta,email:meta.includes('@')?meta:''};
            }).filter((c)=>c.name||c.phone||c.role||c.whatsapp||c.email);
            return {clientValidationVersion:1,clientId:value('#clientId').trim(),clientName:value('#clientName').trim(),email:value('#clientEmail').trim(),phone:value('#clientPhone').trim(),whatsapp:value('#clientWhatsapp').trim(),reference:value('#clientReference').trim(),clientType:value('#clientType'),status:statusOverride||value('#clientStatus'),contactMethod:value('#clientContactMethod'),address:value('#clientAddress').trim(),about:value('#clientAbout').trim(),contacts};
        }
        function validate(row){
            clearClientValidation();
            const errors=[];
            const invalidate=(element,message)=>{ markClientInvalid(element,message); if(element) errors.push(element); };
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
            toast(statusOverride==='Draft'?'Draft saved.':'Client saved. Redirecting to client list.');
            setTimeout(()=>{renderList();setVisible('clientListPage');},450);
        }
        function loadSample(){ resetForm(); const row=(samples.clients||[])[0]; if(!row) return; const map={clientId:'#clientId',clientName:'#clientName',email:'#clientEmail',phone:'#clientPhone',whatsapp:'#clientWhatsapp',reference:'#clientReference',clientType:'#clientType',status:'#clientStatus',contactMethod:'#clientContactMethod',address:'#clientAddress',about:'#clientAbout'}; Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); $('#clientContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); toast('Sample client data added.'); }
        function rowHtml(row){ const main=(row.contacts||[])[0]||{}; const statusClass=row.status==='Active'?'ok':row.status==='Prospect'?'warn':row.status==='Draft'?'soft':'danger'; return `<tr><td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td><td><div class="client-cell"><div class="client-icon">🏢</div><div><b>${escapeHtml(row.clientName)}</b><br><small>${escapeHtml(row.clientId)}${row.reference?' · Ref: '+escapeHtml(row.reference):''}</small></div></div></td><td>${escapeHtml(row.phone||'-')}<br><small>${escapeHtml(row.email||'')}</small></td><td><b>${escapeHtml(main.name||'-')}</b><br><small>${escapeHtml(main.phone||'')}${(row.contacts||[]).length>1?' · +'+((row.contacts||[]).length-1)+' more':''}</small></td><td><span class="badge soft">${escapeHtml(row.clientType||'-')}</span></td><td><span class="badge ${statusClass}">${escapeHtml(row.status||'-')}</span></td><td>${escapeHtml(row.contactMethod||'-')}</td><td>${escapeHtml(row.address||'-')}</td><td><button type="button" class="mini-btn view-client" data-id="${escapeHtml(row.clientId)}">View</button><button type="button" class="mini-btn edit-client" data-id="${escapeHtml(row.clientId)}">Edit</button><button type="button" class="mini-btn danger delete-client" data-id="${escapeHtml(row.clientId)}">Delete</button></td></tr>`; }
        function renderList(){ const q=value('#clientSearch').toLowerCase(), status=value('#clientFilterStatus'), type=value('#clientFilterType'), method=value('#clientFilterMethod'); const rows=clients.filter((row)=>{ const people=(row.contacts||[]).map((person)=>[person.name,person.phone,person.role,person.whatsapp,person.email].join(' ')).join(' '); return (!q||[row.clientName,row.phone,row.email,row.clientId,row.reference,people].join(' ').toLowerCase().includes(q))&&(!status||row.status===status)&&(!type||row.clientType===type)&&(!method||row.contactMethod===method); }); $('#clientTbody').innerHTML=rows.length?rows.map(rowHtml).join(''):'<tr><td colspan="9" class="empty">No client found. Click “Add Client” to create one.</td></tr>'; $('#clientKpiTotal').textContent=clients.length; $('#clientKpiActive').textContent=clients.filter((c)=>c.status==='Active').length; $('#clientKpiEmail').textContent=clients.filter((c)=>c.email).length; }
        function editClient(id){ const row=clients.find((r)=>r.clientId===id); if(!row) return; resetForm(); const map={clientId:'#clientId',clientName:'#clientName',email:'#clientEmail',phone:'#clientPhone',whatsapp:'#clientWhatsapp',reference:'#clientReference',clientType:'#clientType',status:'#clientStatus',contactMethod:'#clientContactMethod',address:'#clientAddress',about:'#clientAbout'}; Object.entries(map).forEach(([key,sel])=>setValue(sel,row[key]||'')); $('#clientContacts').innerHTML=''; (row.contacts||[]).forEach(addContact); setVisible('clientAddPage'); }
        function viewClient(id){ const row=clients.find((r)=>r.clientId===id); if(row) window.FleetmanDetailViewer?.show('Client Details', row); }
        async function deleteClient(id){ if(!confirm('Delete this client from prototype list?')) return; const previous=clients.slice(); clients=clients.filter((row)=>row.clientId!==id); const result=await saveStore(); if(result?.syncFailed){clients=previous;return;} renderList(); toast('Client deleted.'); }
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
            const del=e.target.closest('.delete-client'); if(del) deleteClient(del.dataset.id);
        });
        resetForm();
        renderList();
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
            toast(statusOverride === 'Draft' ? 'Draft saved.' : 'Employee saved. Redirecting to employee list.');
            setTimeout(() => { renderList(); setVisible('employeeListPage'); }, 450);
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
                <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
                <td><div class="employee-cell">${avatar}<div><b>${escapeHtml(row.fullName)}</b><br><small>${escapeHtml(row.employeeId)}</small></div></div></td>
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
                const contactStr = (row.contacts || []).map((contact) => contact.number + ' ' + contact.type).join(' ');
                return (!query || [row.employeeId, row.fullName, row.designation, row.contactNumber, row.nid, contactStr].join(' ').toLowerCase().includes(query)) &&
                    (!status || row.status === status) && (!tenure || row.salaryTenure === tenure) && (!designation || row.designation === designation);
            });
            $('#employeeTbody').innerHTML = rows.length ? rows.map(rowHtml).join('') : '<tr><td colspan="10" class="empty">No employee found. Click “Add Employee” to create one.</td></tr>';
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

        async function deleteEmployee(id) {
            if (!confirm('Delete this employee?')) return;
            const previous = employees.slice();
            employees = employees.filter((row) => row.employeeId !== id);
            const result = await saveStore();
            if (result?.syncFailed) { employees = previous; return; }
            renderList();
            toast('Employee deleted.');
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
            const del = event.target.closest('.delete-employee'); if (del) deleteEmployee(del.dataset.id);
        });

        syncResource('employees', employees);
        resetForm();
        renderList();
        if (window.location.search.includes('action=add')) setVisible('employeeAddPage');
        else setVisible('employeeListPage');
    }

    function initDriverAttendance() {
        let logs = Array.isArray(records.driver_attendance) ? [...records.driver_attendance] : [];
        const attendanceResources = resources?.driver_attendance || {};
        const masters = data.attendanceMasters || { contracts: [], vehicle_driver_map: {}, drivers: [] };
        let selectedStatus = 'Completed';
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
            const currentVehicle = normalize(vehicle);
            if (!contract || !currentVehicle) return [];

            return unique(
                assignmentsFor(contract)
                    .filter((assignment) => vehicleLabel(assignment) === currentVehicle)
                    .map(driverLabel)
            );
        }

        function selectedAssignment(contract = selectedContract()) {
            if (!contract) return null;

            const currentVehicle = normalize(value('#attendanceVehicle'));
            const currentDriver = normalize(value('#attendanceDriver'));
            if (!currentVehicle || !currentDriver) return null;

            return assignmentsFor(contract).find((assignment) => (
                vehicleLabel(assignment) === currentVehicle
                && driverLabel(assignment) === currentDriver
            )) || null;
        }

        function populateBase() {
            fillDatalist('attendanceContractList', contractLabels);
            fillDatalist('attendanceStatusFilterList', options.attendance_statuses || ['Initiated', 'Running', 'Completed']);
            fillDatalist('attendanceFilterContractList', contractLabels);
        }

        function populateDriversBySelection({ clearSelection = false } = {}) {
            if (clearSelection) {
                setValue('#attendanceDriver', '');
            }

            const found = selectedContract();
            const drivers = driversFor(found, value('#attendanceVehicle'));
            fillDatalist('attendanceDriverList', drivers);

            if (value('#attendanceDriver') && !drivers.includes(value('#attendanceDriver'))) {
                setValue('#attendanceDriver', '');
            }
        }

        function populateByContract({ clearSelection = false } = {}) {
            if (clearSelection) {
                setValue('#attendanceVehicle', '');
                setValue('#attendanceDriver', '');
            }

            const found = selectedContract();
            const vehicles = vehiclesFor(found);
            fillDatalist('attendanceVehicleList', vehicles);

            if (value('#attendanceVehicle') && !vehicles.includes(value('#attendanceVehicle'))) {
                setValue('#attendanceVehicle', '');
            }

            populateDriversBySelection({ clearSelection });
        }

        function onContractChange() {
            populateByContract({ clearSelection: true });
        }

        function onVehicleChange() {
            populateDriversBySelection({ clearSelection: true });
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
                driver: value('#attendanceDriver'),
                driverId: assignment?.driverId || '',
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
                ['#attendanceDriver', 'Driver is required.'],
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
            const driverInput = $('#attendanceDriver');
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

            if (found && row.vehicle) {
                const allowedVehicles = vehiclesFor(found);
                if (!allowedVehicles.includes(row.vehicle)) {
                    markInvalid(vehicleInput, 'Select a vehicle assigned to the selected contract.');
                    errors.push(vehicleInput);
                }
            }

            if (found && row.vehicle && row.driver) {
                const allowedDrivers = driversFor(found, row.vehicle);
                if (!allowedDrivers.includes(row.driver)) {
                    markInvalid(driverInput, 'Select a driver assigned to the selected contract and vehicle.');
                    errors.push(driverInput);
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
            const row = collect(isDraft ? 'Draft' : statusOverride);
            if (isDraft) row.status = 'Draft';
            if (!isDraft && !validate(row)) return;

            const nextRows = rowsWithUpsertedLog(row);
            const saveButton = $('#saveAttendanceBtn');
            const draftButton = $('#saveAttendanceDraftBtn');
            const activeButton = isDraft ? draftButton : saveButton;
            const originalText = activeButton?.textContent || '';

            savingLog = true;
            [saveButton, draftButton].filter(Boolean).forEach((button) => { button.disabled = true; });
            if (activeButton) activeButton.textContent = isDraft ? 'Saving Draft...' : 'Saving...';

            try {
                const result = await saveStore(row);
                if (result?.syncFailed || result?.ok === false) return;

                logs = Array.isArray(result?.rows) ? result.rows : nextRows;
                renderList();
                setVisible('attendanceListPage');
                toast(isDraft ? 'Draft saved.' : 'Attendance saved successfully.');
            } finally {
                savingLog = false;
                [saveButton, draftButton].filter(Boolean).forEach((button) => { button.disabled = false; });
                if (activeButton) activeButton.textContent = originalText;
            }
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
            toast('Select the contract, vehicle, and driver from the searchable lists.');
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
                <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
                <td><div class="list-cell"><div class="list-icon">📝</div><div><b>${escapeHtml(row.logId)}</b><br><small>${escapeHtml(row.date)}</small></div></div></td>
                <td>${escapeHtml(row.date || '-')}<br><small>${escapeHtml(row.startTime || '-')} to ${escapeHtml(row.endTime || '-')}</small></td>
                <td><b>${escapeHtml(row.contract || '-')}</b><br><small>${escapeHtml(row.vehicle || '-')}</small></td>
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
                const haystack = [row.logId, row.contract, row.contractId, row.contractParty, row.vehicle, row.driver].join(' ').toLowerCase();
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

            transitioningLogs.add(id);
            const originalText = triggerButton?.textContent || '';
            if (triggerButton) {
                triggerButton.disabled = true;
                triggerButton.textContent = action === 'start' ? 'Starting...' : 'Ending...';
            }

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
                transitioningLogs.delete(id);
                renderList();
                return;
            }

            if (Array.isArray(result?.rows)) logs = result.rows;
            transitioningLogs.delete(id);
            renderList();
            toast(action === 'start' ? `Trip started at ${now}.` : `Trip completed at ${now}.`);

            if (triggerButton) {
                triggerButton.disabled = false;
                triggerButton.textContent = originalText;
            }
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

        async function deleteLog(id) {
            if (!confirm('Delete this attendance record?')) return;

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
        }

        function exportLogs() {
            const rows = [['Attendance ID', 'Date', 'Contract', 'Contract ID', 'Vehicle', 'Vehicle ID', 'Driver', 'Driver ID', 'Start Time', 'End Time', 'Status', 'Hours', 'Notes']];
            logs.forEach((row) => rows.push([row.logId, row.date, row.contract, row.contractId, row.vehicle, row.vehicleId, row.driver, row.driverId, row.startTime, row.endTime, row.status, row.hours, row.notes]));
            exportCsv(rows, 'fleetman-driver-attendance-list.csv');
        }

        $('#attendanceContract')?.addEventListener('change', onContractChange);
        $('#attendanceContract')?.addEventListener('input', onContractChange);
        $('#attendanceVehicle')?.addEventListener('change', onVehicleChange);
        $('#attendanceVehicle')?.addEventListener('input', onVehicleChange);
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
            if (del) deleteLog(del.dataset.id);
        });

        populateBase();
        resetForm();
        renderList();
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
                return;
            }

            fetch(endpoint, {
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
                })
                .catch((error) => toast(error.message || 'Could not save master data to database.'));
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-type="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-type="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="7" class="empty">No fuel type added yet.</td></tr>';
        }

        function renderFuelUnits() {
            setText('#masterFuelUnitCount', fuelUnits.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#fuelUnitMasterTbody');
            if (!tbody) return;
            const rows = sortRows(fuelUnits);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-fuel-unit="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-fuel-unit="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="7" class="empty">No fuel unit added yet.</td></tr>';
        }

        function renderPaymentTypes() {
            setText('#masterPaymentTypeCount', paymentTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#paymentTypeMasterTbody');
            if (!tbody) return;
            const rows = sortRows(paymentTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr><td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td><td><b>${escapeHtml(row.name)}</b></td><td><span class="master-code">${escapeHtml(row.code)}</span></td><td>${Number(row.sortOrder || 0)}</td><td><span class="badge ${row.status === 'Inactive' ? 'warn' : 'ok'}">${escapeHtml(row.status || 'Active')}</span></td><td class="master-description">${escapeHtml(row.description || '—')}</td><td><div class="master-actions"><button type="button" class="mini-btn" data-master-edit-payment-type="${escapeHtml(row.code)}">Edit</button><button type="button" class="mini-btn danger" data-master-delete-payment-type="${escapeHtml(row.code)}">Delete</button></div></td></tr>`).join('') : '<tr><td colspan="7" class="empty">No payment type added yet.</td></tr>';
        }

        function renderClientTypes() {
            setText('#masterClientTypeCount', clientTypes.filter((row) => row.status !== 'Inactive').length);
            const tbody = $('#clientTypeMasterTbody');
            if (!tbody) return;

            const rows = sortRows(clientTypes);
            tbody.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                    <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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

        $('#documentNameMasterForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const row = collectDocumentName();
            if (!row) return;

            const editingCode = value('#documentNameEditingCode');
            const previousRows = documentNames.slice();
            const nextRows = upsertRow(documentNames, row, editingCode);
            if (nextRows === documentNames) return;

            documentNames = nextRows;
            renderDocumentNames();

            const saveButton = $('#saveDocumentNameMasterBtn');
            if (saveButton) saveButton.disabled = true;

            try {
                await saveDocumentName(row, editingCode);
                resetDocumentNameForm();
                toast('Document type saved to database.');
            } catch (error) {
                documentNames = previousRows;
                renderDocumentNames();
                toast(error.message || 'Document type could not be saved.');
            } finally {
                if (saveButton) saveButton.disabled = false;
            }
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

        $('#paymentTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectPaymentType();
            if (!row) return;
            paymentTypes = upsertRow(paymentTypes, row, value('#paymentTypeEditingCode'));
            resetPaymentTypeForm();
            renderAll();
            saveStore();
            toast('Payment type saved to database.');
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

        $('#driverContactTypeMasterForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const row = collectDriverContactType();
            if (!row) return;
            driverContactTypes = upsertRow(driverContactTypes, row, value('#driverContactTypeEditingCode'));
            resetDriverContactTypeForm();
            renderAll();
            saveStore();
            toast('Driver contact type saved to database.');
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

        document.addEventListener('click', async (event) => {
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
            if (deleteDocumentBtn) {
                const documentId = Number(deleteDocumentBtn.dataset.masterDeleteDocument);
                const deletingRow = documentNames.find((row) => Number(row.id) === documentId);
                const documentLabel = deletingRow?.name ? ` “${deletingRow.name}”` : '';

                if (confirm(`Delete Document Type${documentLabel}? Only this selected row will be deleted.`)) {
                    deleteDocumentBtn.disabled = true;

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
                    } finally {
                        if (deleteDocumentBtn.isConnected) deleteDocumentBtn.disabled = false;
                    }
                }
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


            const editPaymentTypeBtn = event.target.closest('[data-master-edit-payment-type]');
            if (editPaymentTypeBtn) editPaymentType(editPaymentTypeBtn.dataset.masterEditPaymentType);

            const deletePaymentTypeBtn = event.target.closest('[data-master-delete-payment-type]');
            if (deletePaymentTypeBtn && confirm('Delete this payment type from master data? Existing trip records will keep their saved payment method.')) {
                paymentTypes = paymentTypes.filter((row) => row.code !== deletePaymentTypeBtn.dataset.masterDeletePaymentType);
                renderAll();
                saveStore();
                toast('Payment type deleted from database.');
            }

            const editDriverContactTypeBtn = event.target.closest('[data-master-edit-driver-contact-type]');
            if (editDriverContactTypeBtn) editDriverContactType(editDriverContactTypeBtn.dataset.masterEditDriverContactType);

            const deleteDriverContactTypeBtn = event.target.closest('[data-master-delete-driver-contact-type]');
            if (deleteDriverContactTypeBtn && confirm('Delete this driver contact type from master data?')) {
                driverContactTypes = driverContactTypes.filter((row) => row.code !== deleteDriverContactTypeBtn.dataset.masterDeleteDriverContactType);
                renderAll();
                saveStore();
                toast('Driver contact type deleted from database.');
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
            await uploadManager.waitForInputs($$('.contractDocFile'));
            if (documentSelects.hasDuplicates('#contractDocuments', '.contractDocName')) {
                toast('Each contract document name can be selected only once.');
                return;
            }
            const row = collectContract(savedAs);
            if (!validateContract(row, savedAs)) return;
            const previousContracts = JSON.parse(JSON.stringify(contracts || []));
            upsertLocal(row);
            syncContracts(row.contractId)
                .then(() => {
                    currentPage = 1;
                    renderList();
                    setPage('contractListPage');
                    toast(savedAs === 'Draft' ? 'Contract draft saved.' : 'Contract submitted successfully.');
                })
                .catch((error) => {
                    contracts = previousContracts;
                    renderList();
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
                        <td>${escapeHtml(window.FleetmanFormatCreatedAt(row.createdAt || row.created_at))}</td>
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
                        <td><span class="badge ${badgeClass(row.savedAs)}">${escapeHtml(row.savedAs || '-')}</span></td>
                        <td><button class="mini-btn view-contract" type="button" data-id="${escapeHtml(row.contractId)}">View</button><button class="mini-btn edit-contract" type="button" data-id="${escapeHtml(row.contractId)}">Edit</button><button class="mini-btn danger delete-contract" type="button" data-id="${escapeHtml(row.contractId)}">Delete</button></td>
                    </tr>`).join('') : '<tr><td colspan="13"><div class="contract-empty">No contract found for the selected filters.</div></td></tr>';
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
            const row = contracts.find((item) => item.contractId === id);
            if (!row) return;
            loadContract(row);
            setPage('contractCreatePage');
        }

        function deleteContract(id) {
            if (!confirm('Delete this contract?')) return;
            contracts = contracts.filter((row) => row.contractId !== id);
            syncContracts().then(() => {
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
        if (window.location.search.includes('action=add')) {
            setPage('contractCreatePage');
        } else {
            setPage('contractListPage');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.body.dataset.page === 'contracts') initContracts();
    });
})();
