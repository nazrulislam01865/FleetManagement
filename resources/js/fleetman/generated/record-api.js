(() => {
    'use strict';

    const fleet = window.FLEETMAN || {};
    const resources = fleet.resources || {};
    const idKeys = {
        yards: 'yardId',
        vehicles: 'id',
        fuel_prices: 'fuelPriceId',
        fuel_recharges: 'rechargeId',
        parties: 'partyId',
        trips: 'tripId',
        drivers: 'driverId',
        clients: 'clientId',
        driver_attendance: 'logId',
        employees: 'employeeId',
        contracts: 'contractId',
    };
    const snapshots = new Map();
    const pagination = new Map();
    const registrations = new Map();
    const loadPromises = new Map();
    let loadingResource = '';
    let scrollQueued = false;
    let filterLoadTimer = 0;

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const codeFor = (resource, row = {}) => String(row?.[idKeys[resource]] || row?._recordCode || '').trim();
    const clean = (value) => {
        if (Array.isArray(value)) return value.map(clean);
        if (!value || typeof value !== 'object') return value;
        const output = {};
        Object.keys(value).sort().forEach((key) => {
            if (['createdAt', 'updatedAt', 'created_at', 'updated_at', 'creatorName', 'createdBy', 'created_by', '_recordDbId', '_recordCode'].includes(key)) return;
            output[key] = clean(value[key]);
        });
        return output;
    };
    const comparable = (row) => JSON.stringify(clean(row || {}));
    const clone = (value) => JSON.parse(JSON.stringify(value ?? null));

    function snapshotRows(resource, rows) {
        const map = new Map();
        (Array.isArray(rows) ? rows : []).forEach((row) => {
            const code = codeFor(resource, row);
            if (code) map.set(code, clone(row));
        });
        snapshots.set(resource, map);
    }

    Object.keys(idKeys).forEach((resource) => {
        snapshotRows(resource, fleet.records?.[resource] || fleet.samples?.[resource] || []);
    });

    if (fleet.recordPagination?.resource) {
        pagination.set(String(fleet.recordPagination.resource), {
            ...fleet.recordPagination,
            loading: false,
        });
    }

    function endpointFor(resource, mode, code = '') {
        const config = resources?.[resource] || {};
        if (mode === 'create') return config.store || '';
        if (mode === 'update') {
            return config.update_template
                ? String(config.update_template).replace('__CODE__', encodeURIComponent(code))
                : (config.store || '');
        }
        if (mode === 'delete') return String(config.destroy_template || '').replace('__CODE__', encodeURIComponent(code));
        if (mode === 'records') return config.records || '';
        return '';
    }

    async function responsePayload(response, fallbackMessage) {
        const contentType = String(response.headers.get('content-type') || '').toLowerCase();
        const payload = contentType.includes('application/json')
            ? await response.json().catch(() => ({}))
            : {};
        if (!response.ok) {
            const validationMessage = payload?.errors
                ? Object.values(payload.errors).flat().join(' ')
                : '';
            throw new Error(payload?.message || validationMessage || fallbackMessage);
        }
        return payload;
    }

    async function saveRow(resource, row, options = {}) {
        const code = codeFor(resource, row);
        if (!code) throw new Error('The record ID is missing.');
        const exists = snapshots.get(resource)?.has(code) === true;
        const mode = exists ? 'update' : 'create';
        const endpoint = endpointFor(resource, mode, code);
        if (!endpoint) throw new Error('The optimized save endpoint is not available for this module.');
        const updateViaStore = mode === 'update' && !resources?.[resource]?.update_template;

        let body;
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() };
        if (typeof options.formDataForRow === 'function') {
            body = options.formDataForRow(row, options.rowIndex ?? 0, mode) || new FormData();
            if (!(body instanceof FormData)) throw new Error('The upload request could not be prepared.');
            if (!body.has('row')) body.append('row', JSON.stringify(row));
            if (!body.has('rows')) body.append('rows', JSON.stringify([row]));
            if (!body.has('_partial_sync')) body.append('_partial_sync', '1');
            Object.entries(options.extra || {}).forEach(([key, value]) => {
                if (value !== undefined && value !== null && !body.has(key)) body.append(key, String(value));
            });
            if (mode === 'update' && !updateViaStore && !body.has('_method')) body.append('_method', 'PUT');
        } else {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify({ row, rows: [row], _partial_sync: true, ...(options.extra || {}) });
        }

        const response = await fetch(endpoint, {
            method: mode === 'update' && !updateViaStore && !(body instanceof FormData) ? 'PUT' : 'POST',
            headers,
            body,
        });
        return responsePayload(response, 'The record could not be saved.');
    }

    async function deleteRow(resource, code) {
        const endpoint = endpointFor(resource, 'delete', code);
        if (!endpoint) throw new Error('The optimized delete endpoint is not available for this module.');
        const response = await fetch(endpoint, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });
        return responsePayload(response, 'The record could not be deleted.');
    }

    async function persistCollection(resource, rows, options = {}) {
        const currentRows = Array.isArray(rows) ? rows : [];
        const previous = snapshots.get(resource) || new Map();
        const current = new Map();
        currentRows.forEach((row, index) => {
            const code = codeFor(resource, row);
            if (code) current.set(code, { row, index });
        });

        const changed = [];
        current.forEach((entry, code) => {
            const before = previous.get(code);
            if (!before || comparable(before) !== comparable(entry.row)) changed.push(entry);
        });
        const deleted = [...previous.keys()].filter((code) => !current.has(code));
        const workingRows = currentRows.slice();

        for (const entry of changed) {
            const payload = await saveRow(resource, entry.row, {
                ...options,
                rowIndex: entry.index,
            });
            const savedRow = Array.isArray(payload?.rows) ? payload.rows[0] : null;
            if (savedRow && typeof savedRow === 'object') {
                workingRows[entry.index] = { ...entry.row, ...savedRow };
            }
        }
        for (const code of deleted) await deleteRow(resource, code);

        snapshotRows(resource, workingRows);
        return {
            ok: true,
            rows: workingRows,
            changed: changed.length,
            deleted: deleted.length,
            can_view_list: fleet.auth?.pageAccess?.canView === true,
        };
    }

    async function loadMore(resource, force = false) {
        if (loadPromises.has(resource)) return loadPromises.get(resource);

        const state = pagination.get(resource);
        const registration = registrations.get(resource);
        const endpoint = endpointFor(resource, 'records');
        if (!state || !registration || !endpoint || (!state.has_more && !force)) return null;

        const promise = (async () => {
            state.loading = true;
            loadingResource = resource;
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('per_page', String(state.per_page || 50));
            if (state.next_cursor) url.searchParams.set('cursor', String(state.next_cursor));
            if (state.total) url.searchParams.set('known_total', String(state.total));
            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const payload = await responsePayload(response, 'More records could not be loaded.');
            const incoming = Array.isArray(payload.rows) ? payload.rows : [];
            const existingRows = registration.getRows();
            const merged = Array.isArray(existingRows) ? existingRows.slice() : [];
            const known = new Set(merged.map((row) => codeFor(resource, row)).filter(Boolean));
            incoming.forEach((row) => {
                const code = codeFor(resource, row);
                if (code && !known.has(code)) {
                    known.add(code);
                    merged.push(row);
                }
            });
            registration.setRows(merged);
            const next = payload.pagination || {};
            Object.assign(state, next, { loaded: merged.length });
            snapshotRows(resource, merged);
            registration.render();
            document.dispatchEvent(new CustomEvent('fleetman:records-loaded', {
                detail: { resource, added: incoming.length, total: state.total || merged.length },
            }));
            return payload;
        })();

        loadPromises.set(resource, promise);
        try {
            return await promise;
        } finally {
            loadPromises.delete(resource);
            state.loading = false;
            if (loadingResource === resource) loadingResource = '';
        }
    }

    async function loadAll(resource) {
        const state = pagination.get(resource);
        if (!state || !registrations.has(resource)) return;

        let previousCursor = null;
        while (state.has_more) {
            const cursor = state.next_cursor ?? null;
            await loadMore(resource);
            if (state.has_more && state.next_cursor === cursor && previousCursor === cursor) {
                throw new Error('Additional records could not be loaded safely.');
            }
            previousCursor = cursor;
        }
    }

    function activePaginatedResource() {
        for (const resource of registrations.keys()) {
            if (pagination.has(resource)) return resource;
        }
        return '';
    }

    function scheduleCompleteLoad(target) {
        const id = String(target?.id || '');
        if (!/(search|filter)/i.test(id)) return;
        const resource = activePaginatedResource();
        const state = pagination.get(resource);
        if (!resource || !state?.has_more) return;

        window.clearTimeout(filterLoadTimer);
        filterLoadTimer = window.setTimeout(() => {
            loadAll(resource).catch((error) => console.error(error));
        }, 180);
    }

    document.addEventListener('input', (event) => scheduleCompleteLoad(event.target), true);
    document.addEventListener('change', (event) => scheduleCompleteLoad(event.target), true);
    document.addEventListener('click', (event) => {
        const target = event.target?.closest?.('[id]');
        if (target && /^(apply|clear).*(filters?|search)/i.test(String(target.id || ''))) {
            scheduleCompleteLoad(target);
        }
    }, true);

    document.addEventListener('click', async (event) => {
        const exportButton = event.target?.closest?.('[id^="export"]');
        if (!exportButton) return;

        const resource = activePaginatedResource();
        const state = pagination.get(resource);
        if (!resource || !state?.has_more || exportButton.dataset.fleetLoadingAll === '1') return;

        event.preventDefault();
        event.stopImmediatePropagation();
        exportButton.dataset.fleetLoadingAll = '1';
        const wasDisabled = exportButton.disabled === true;
        exportButton.disabled = true;
        try {
            await loadAll(resource);
            delete exportButton.dataset.fleetLoadingAll;
            exportButton.disabled = wasDisabled;
            exportButton.click();
        } catch (error) {
            delete exportButton.dataset.fleetLoadingAll;
            exportButton.disabled = wasDisabled;
            window.alert(error?.message || 'All records could not be loaded for export.');
        }
    }, true);

    function registerInfinite(resource, getRows, setRows, render) {
        if (!resource || typeof getRows !== 'function' || typeof setRows !== 'function' || typeof render !== 'function') return;
        registrations.set(resource, { getRows, setRows, render });
        const state = pagination.get(resource);
        if (state && state.has_more && document.documentElement.scrollHeight <= window.innerHeight + 250) {
            loadMore(resource).catch(() => {});
        }
    }

    function onScroll() {
        if (scrollQueued) return;
        scrollQueued = true;
        window.requestAnimationFrame(() => {
            scrollQueued = false;
            if (loadingResource || window.innerHeight + window.scrollY < document.documentElement.scrollHeight - 650) return;
            for (const [resource, state] of pagination.entries()) {
                if (state.has_more && registrations.has(resource)) {
                    loadMore(resource).catch(() => {});
                    break;
                }
            }
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.FleetmanRecordApi = {
        persistCollection,
        saveRow,
        deleteRow,
        loadMore,
        loadAll,
        registerInfinite,
        snapshotRows,
        codeFor,
        state: (resource) => pagination.get(resource) || null,
    };
})();
