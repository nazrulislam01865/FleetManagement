(function () {
    'use strict';

    if (window.FleetmanActionLoader) return;

    const ACTION_PATTERN = /^(view|edit|delete)$/i;
    const CLASS_ACTION_PATTERN = /(^|-)(view|edit|delete)(-|$)/i;
    const activeTimers = new WeakMap();

    function visibleLabel(control) {
        if (control instanceof HTMLInputElement) {
            return String(control.value || '').replace(/\s+/g, ' ').trim();
        }

        return String(control.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function actionFromText(value) {
        const firstWord = String(value || '').replace(/\s+/g, ' ').trim().split(/\s+/)[0] || '';
        return ACTION_PATTERN.test(firstWord) ? firstWord.toLowerCase() : '';
    }

    function classAction(control) {
        for (const className of control.classList || []) {
            const match = String(className).match(CLASS_ACTION_PATTERN);
            if (match) return String(match[2] || '').toLowerCase();
        }
        return '';
    }

    function attributeAction(control) {
        for (const attribute of Array.from(control.attributes || [])) {
            if (!attribute.name.startsWith('data-')) continue;
            const normalized = attribute.name.replace(/_/g, '-');
            const match = normalized.match(/(?:^|-)(view|edit|delete)(?:-|$)/i);
            if (match) return String(match[1] || '').toLowerCase();
        }
        return '';
    }

    function actionFor(control) {
        const explicit = String(control.dataset?.actionLoader || '').trim().toLowerCase();
        if (ACTION_PATTERN.test(explicit)) return explicit;

        const label = visibleLabel(control);
        const labelMatch = actionFromText(label);
        if (labelMatch) return labelMatch;

        // A visible non-action label such as Start, Close or Cancel must not be
        // treated as Edit simply because an internal handler class contains it.
        if (label && !/^[×✕✖…⋮]+$/.test(label)) return '';

        const accessibleMatch = actionFromText(control.getAttribute('aria-label') || control.getAttribute('title'));
        if (accessibleMatch) return accessibleMatch;

        return classAction(control) || attributeAction(control);
    }

    function controlFromEvent(event) {
        const target = event.target instanceof Element ? event.target : null;
        return target?.closest('button, a, input[type="button"], input[type="submit"]') || null;
    }

    function stop(control) {
        const timer = activeTimers.get(control);
        if (timer) window.clearTimeout(timer);
        activeTimers.delete(control);

        if (!control?.isConnected) return;
        control.classList.remove('fleet-action-loading');
        control.removeAttribute('aria-busy');
        control.removeAttribute('data-fleet-action-loading');
    }

    function start(control, action) {
        if (!(control instanceof Element)) return;
        if (control.matches(':disabled, [aria-disabled="true"]')) return;
        if (control.classList.contains('fleet-transaction-active')) return;

        const existingTimer = activeTimers.get(control);
        if (existingTimer) window.clearTimeout(existingTimer);

        control.classList.add('fleet-action-loading');
        control.setAttribute('aria-busy', 'true');
        control.setAttribute('data-fleet-action-loading', action);

        queueMicrotask(function () {
            if (!control.isConnected) return;

            // Real delete requests already use the transaction guard. Once it
            // starts, hand visual ownership to that existing protected flow.
            if (control.classList.contains('fleet-transaction-active')) {
                stop(control);
                return;
            }

            const timer = window.setTimeout(function () {
                stop(control);
            }, action === 'delete' ? 500 : 420);
            activeTimers.set(control, timer);
        });
    }

    document.addEventListener('click', function (event) {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const control = controlFromEvent(event);
        if (!control) return;
        if (control.closest('[data-action-loader="off"]')) return;

        const action = actionFor(control);
        if (!action) return;

        start(control, action);
    }, true);

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('.fleet-action-loading').forEach(stop);
    });

    window.FleetmanActionLoader = Object.freeze({ start, stop, actionFor });
})();
