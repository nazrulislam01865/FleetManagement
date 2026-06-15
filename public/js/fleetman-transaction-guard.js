(function () {
    'use strict';

    if (window.FleetmanTransactionGuard) return;

    const activeScopes = new WeakMap();
    const activeTriggers = new WeakSet();
    const nativeFormTokens = new WeakMap();

    const mutationWords = /\b(save|submit|update|approve|reject|delete|confirm|draft|create|generate|sync|upload|send|reset password|mark all|logout|sign out)\b/i;

    function buttonLabel(button) {
        if (!button) return '';
        if (button instanceof HTMLInputElement) return String(button.value || '').trim();
        return String(button.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function loadingText(button, explicitText) {
        if (explicitText) return explicitText;
        const configured = button?.dataset?.loadingText;
        if (configured) return configured;

        const label = buttonLabel(button).toLowerCase();
        if (label.includes('draft')) return 'Saving Draft...';
        if (label.includes('approve')) return 'Approving...';
        if (label.includes('reject')) return 'Rejecting...';
        if (label.includes('delete')) return 'Deleting...';
        if (label.includes('generate')) return 'Generating...';
        if (label.includes('submit')) return 'Submitting...';
        if (label.includes('update') || label.includes('save changes') || label.includes('reset password')) return 'Updating...';
        if (label.includes('create') || label.includes('add ')) return 'Saving...';
        if (label.includes('logout') || label.includes('sign out')) return 'Signing out...';
        if (label.includes('mark all')) return 'Updating...';
        if (label.includes('save')) return 'Saving...';
        return 'Processing...';
    }

    function resolveScope(trigger, requestedScope) {
        if (requestedScope instanceof Element) return requestedScope;
        if (typeof requestedScope === 'string' && requestedScope.trim()) {
            const matched = document.querySelector(requestedScope);
            if (matched) return matched;
        }

        return trigger?.closest('form, [data-transaction-scope], .save-bar, .bottom-submit, .form-actions, .modal-actions, .release-modal, .login-card')
            || trigger?.parentElement
            || document.body;
    }

    function relatedControls(scope, trigger, requestedControls) {
        const controls = new Set();
        const add = (item) => {
            if (item instanceof HTMLButtonElement || (item instanceof HTMLInputElement && ['submit', 'button'].includes(item.type))) {
                controls.add(item);
            }
        };

        add(trigger);

        if (Array.isArray(requestedControls)) requestedControls.forEach(add);
        if (typeof requestedControls === 'string' && requestedControls.trim()) {
            document.querySelectorAll(requestedControls).forEach(add);
        }

        if (scope instanceof Element) {
            scope.querySelectorAll('button, input[type="submit"], input[type="button"]').forEach(add);
        }

        return [...controls];
    }

    function setTriggerContent(trigger, text) {
        if (!trigger) return;
        if (trigger instanceof HTMLInputElement) {
            trigger.value = text;
            return;
        }
        trigger.innerHTML = `<span class="fleet-transaction-spinner" aria-hidden="true"></span><span>${String(text).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[character]))}</span>`;
    }

    function begin(trigger, options) {
        const settings = options || {};
        if (!(trigger instanceof Element)) return null;
        if (activeTriggers.has(trigger)) return false;

        const scope = resolveScope(trigger, settings.scope);
        const currentScopeToken = activeScopes.get(scope);
        if (currentScopeToken) return false;

        const controls = relatedControls(scope, trigger, settings.relatedControls);
        const token = {
            trigger,
            scope,
            controls: controls.map((control) => ({
                control,
                disabled: Boolean(control.disabled),
                ariaDisabled: control.getAttribute('aria-disabled'),
                html: control instanceof HTMLButtonElement ? control.innerHTML : null,
                value: control instanceof HTMLInputElement ? control.value : null,
            })),
            completed: false,
        };

        activeTriggers.add(trigger);
        activeScopes.set(scope, token);
        scope.classList.add('fleet-transaction-scope-busy');
        scope.setAttribute('aria-busy', 'true');

        token.controls.forEach(({ control }) => {
            control.disabled = true;
            control.setAttribute('aria-disabled', 'true');
            control.classList.add('fleet-transaction-disabled');
        });

        trigger.classList.add('fleet-transaction-active');
        setTriggerContent(trigger, loadingText(trigger, settings.loadingText));

        return token;
    }

    function end(token) {
        if (!token || token === false || token.completed) return;
        token.completed = true;

        token.controls.forEach(({ control, disabled, ariaDisabled, html, value }) => {
            if (!control?.isConnected) return;
            control.disabled = disabled;
            if (ariaDisabled === null) control.removeAttribute('aria-disabled');
            else control.setAttribute('aria-disabled', ariaDisabled);
            control.classList.remove('fleet-transaction-disabled', 'fleet-transaction-active');
            if (control instanceof HTMLButtonElement && html !== null) control.innerHTML = html;
            if (control instanceof HTMLInputElement && value !== null) control.value = value;
        });

        if (token.scope?.isConnected) {
            token.scope.classList.remove('fleet-transaction-scope-busy');
            token.scope.removeAttribute('aria-busy');
        }

        activeTriggers.delete(token.trigger);
        if (activeScopes.get(token.scope) === token) activeScopes.delete(token.scope);
    }

    async function run(trigger, task, options) {
        const token = begin(trigger, options);
        if (token === false) return { skipped: true, reason: 'already-processing' };

        try {
            return await task();
        } finally {
            end(token);
        }
    }

    function isBusy(target) {
        if (!(target instanceof Element)) return false;
        if (activeTriggers.has(target)) return true;
        const scope = resolveScope(target);
        return activeScopes.has(scope);
    }

    function submitterFor(form, event) {
        if (event.submitter instanceof Element) return event.submitter;
        return form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.transactionGuard === 'off' || form.dataset.transactionGuard === 'manual') return;

        const submitter = submitterFor(form, event);
        if (!submitter || !mutationWords.test(buttonLabel(submitter))) return;
        if (nativeFormTokens.has(form)) {
            event.preventDefault();
            return;
        }

        queueMicrotask(() => {
            if (event.defaultPrevented || !form.isConnected) return;
            const token = begin(submitter, { scope: form });
            if (token && token !== false) nativeFormTokens.set(form, token);
        });
    }, false);

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[aria-busy="true"]').forEach((form) => {
            const token = nativeFormTokens.get(form);
            if (token) end(token);
            nativeFormTokens.delete(form);
        });
    });

    window.FleetmanTransactionGuard = Object.freeze({ begin, end, run, isBusy });
    window.FleetmanRunTransaction = (trigger, task, options) => {
        if (!(trigger instanceof Element)) return Promise.resolve().then(task);
        return window.FleetmanTransactionGuard.run(trigger, task, options);
    };
})();
