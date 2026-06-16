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
window.FleetmanListAccess = window.FleetmanListAccess || Object.freeze({
    canView() {
        return window.FLEETMAN?.auth?.pageAccess?.canView === true;
    },
    isCreateOnly() {
        const access = window.FLEETMAN?.auth?.pageAccess || {};
        return access.canManage === true && access.canView !== true;
    },
    savedMessage(entityLabel, draft = false) {
        const action = draft ? `${entityLabel} draft saved successfully.` : `${entityLabel} saved successfully.`;
        return `${action} You are not allowed to view the list. The Add page has been reopened.`;
    },
});

window.FleetmanMediaUrl = window.FleetmanMediaUrl || (() => {
    'use strict';

    const photoPatterns = [
        /^fleet\/profile-pictures\/\d+\/[^/]+$/i,
        /^fleet\/vehicles\/[^/]+\/images\/[^/]+$/i,
        /^fleet\/drivers\/[^/]+\/photo\/[^/]+$/i,
        /^fleet\/employees\/[^/]+\/photo\/[^/]+$/i,
        /^fleet\/vendor-parties\/[^/]+\/photo\/[^/]+$/i,
        /^fleet\/clients\/[^/]+\/photo\/[^/]+$/i,
        /^fleet\/yards\/[^/]+\/photo\/[^/]+$/i,
        /^fleet\/fuel-recharges\/[^/]+\/photos(?:\/[^/]+)+$/i,
    ];

    function normalizePath(value) {
        return String(value || '')
            .trim()
            .replace(/^\/+/, '')
            .replace(/^(public\/|storage\/)/i, '');
    }

    function isDisplayPhotoPath(value) {
        const path = normalizePath(value);
        return Boolean(path) && !path.includes('..') && photoPatterns.some((pattern) => pattern.test(path));
    }

    function urlForPath(value) {
        const path = normalizePath(value);
        if (!path) return '';

        const uploads = window.FLEETMAN?.resources?.uploads || {};
        const template = String(
            isDisplayPhotoPath(path)
                ? (uploads.photo_template || '')
                : (uploads.file_template || '')
        );

        if (!template) return '';
        const encodedPath = path.split('/').map((part) => encodeURIComponent(part)).join('/');
        return template.replace('__PATH__', encodedPath);
    }

    function storedPathFromUrl(value) {
        const url = String(value || '').trim();
        if (!url) return '';

        let pathname = '';
        try {
            pathname = new URL(url, window.location.origin).pathname;
        } catch (_) {
            pathname = url.split('?')[0].split('#')[0];
        }

        for (const prefix of ['/fleet/files/', '/fleet/photos/']) {
            const index = pathname.indexOf(prefix);
            if (index === -1) continue;
            const encodedPath = pathname.slice(index + prefix.length);
            try {
                return normalizePath(decodeURIComponent(encodedPath));
            } catch (_) {
                return normalizePath(encodedPath);
            }
        }

        return '';
    }

    function rewriteStoredUrl(value) {
        const url = String(value || '').trim();
        if (!url) return '';

        const path = storedPathFromUrl(url);
        if (path && isDisplayPhotoPath(path)) {
            return urlForPath(path);
        }

        return url;
    }

    function fileUrl(file = {}) {
        if (typeof file === 'string') {
            const value = file.trim();
            if (!value) return '';
            if (/^https?:\/\//i.test(value) || value.startsWith('/')) {
                return rewriteStoredUrl(value);
            }
            return urlForPath(value);
        }

        if (!file || typeof file !== 'object' || Array.isArray(file)) return '';

        const path = normalizePath(file.filePath || file.path || '');
        if (path) {
            const generated = urlForPath(path);
            if (generated) return generated;
        }

        return rewriteStoredUrl(file.previewUrl || file.fileUrl || file.url || '');
    }

    return { fileUrl, isDisplayPhotoPath, normalizePath, rewriteStoredUrl, urlForPath };
})();

window.FleetmanCreatedAtCell = window.FleetmanCreatedAtCell || ((value, creator) => {
    const escapeHtml = (input) => String(input ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));
    const creatorName = creator && typeof creator === 'object'
        ? (creator.name || creator.label || '')
        : creator;
    const safeCreator = String(creatorName || 'System / Legacy').trim() || 'System / Legacy';

    return `<div class="created-at-cell"><span class="created-at-date">${escapeHtml(window.FleetmanFormatCreatedAt(value))}</span><small class="created-at-creator">Created by: ${escapeHtml(safeCreator)}</small></div>`;
});

window.FleetmanExpiringDocuments = window.FleetmanExpiringDocuments || (() => {
    'use strict';

    const DAY_MS = 24 * 60 * 60 * 1000;
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));

    function documentName(document = {}) {
        return String(
            document.name
            || document.documentName
            || document.title
            || document.type
            || document.file?.originalName
            || document.file?.fileName
            || 'Document'
        ).trim() || 'Document';
    }

    function expiryValue(document = {}) {
        return String(
            document.expiry
            || document.expiryDate
            || document.expiresAt
            || document.validUntil
            || document.validityDate
            || ''
        ).trim();
    }

    function dateSerial(value) {
        const raw = String(value || '').trim();
        if (!raw) return null;

        const isoMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (isoMatch) {
            const year = Number(isoMatch[1]);
            const month = Number(isoMatch[2]);
            const day = Number(isoMatch[3]);
            const serial = Date.UTC(year, month - 1, day);
            return Number.isNaN(serial) ? null : serial;
        }

        const parsed = new Date(raw);
        if (Number.isNaN(parsed.getTime())) return null;
        return Date.UTC(parsed.getUTCFullYear(), parsed.getUTCMonth(), parsed.getUTCDate());
    }

    function todaySerial() {
        const parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Dhaka',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).formatToParts(new Date());
        const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));
        return Date.UTC(Number(values.year), Number(values.month) - 1, Number(values.day));
    }

    function formatDate(value) {
        const serial = dateSerial(value);
        if (serial === null) return String(value || '');
        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(serial));
    }

    function labelForDays(days) {
        if (days < -1) return `Expired ${Math.abs(days)} days ago`;
        if (days === -1) return 'Expired yesterday';
        if (days === 0) return 'Expires today';
        if (days === 1) return '1 day left';
        return `${days} days left`;
    }

    function classForDays(days) {
        if (days < 0) return 'expired';
        if (days <= 7) return 'urgent';
        if (days <= 30) return 'warning';
        return 'safe';
    }

    function items(documents = []) {
        const today = todaySerial();
        return (Array.isArray(documents) ? documents : [])
            .map((document) => {
                const expiry = expiryValue(document);
                const serial = dateSerial(expiry);
                if (serial === null) return null;
                const days = Math.round((serial - today) / DAY_MS);
                return {
                    name: documentName(document),
                    expiry,
                    formattedExpiry: formatDate(expiry),
                    days,
                    label: labelForDays(days),
                    statusClass: classForDays(days),
                };
            })
            .filter(Boolean)
            .sort((left, right) => left.days - right.days || left.name.localeCompare(right.name));
    }

    function html(documents = [], options = {}) {
        const datedDocuments = items(documents);
        if (!datedDocuments.length) {
            return '<span class="expiring-docs-empty">No dated documents</span>';
        }

        const limit = Math.max(1, Number(options.limit || 3));
        const visible = datedDocuments.slice(0, limit);
        const remaining = datedDocuments.slice(limit);
        const hiddenTitle = remaining.map((item) => `${item.name}: ${item.label} (${item.formattedExpiry})`).join('\n');

        return `<div class="expiring-docs-cell">
            ${visible.map((item) => `<div class="expiring-doc-item">
                <div class="expiring-doc-name">${escapeHtml(item.name)}</div>
                <div class="expiring-doc-meta">
                    <span class="expiring-doc-badge ${escapeHtml(item.statusClass)}">${escapeHtml(item.label)}</span>
                    <small>${escapeHtml(item.formattedExpiry)}</small>
                </div>
            </div>`).join('')}
            ${remaining.length ? `<button type="button" class="expiring-doc-more" title="${escapeHtml(hiddenTitle)}">+${remaining.length} more</button>` : ''}
        </div>`;
    }

    function text(documents = []) {
        return items(documents)
            .map((item) => `${item.name}: ${item.label} (${item.formattedExpiry})`)
            .join(' | ');
    }

    return { items, html, text };
})();

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
        return window.FleetmanMediaUrl.fileUrl(file);
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
        return window.FleetmanMediaUrl.fileUrl(file);
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
        return window.FleetmanMediaUrl.fileUrl(file);
    }

    function isImage(file = {}) {
        const mime = String(file.mimeType || '').toLowerCase();
        const name = String(file.originalName || file.fileName || '').toLowerCase();
        return mime.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|svg|ico)$/i.test(name);
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
