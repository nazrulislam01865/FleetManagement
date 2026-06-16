(function () {
    'use strict';

    const ENHANCED_ATTRIBUTE = 'data-fleet-searchable-enhanced';
    const ORIGINAL_LIST_ATTRIBUTE = 'data-fleet-searchable-list';
    const MOBILE_BREAKPOINT = 760;
    let activeInstance = null;
    let instanceCounter = 0;
    let globalBackdrop = null;

    const normalize = (value) => String(value == null ? '' : value).trim();
    const searchableText = (value) => normalize(value).toLocaleLowerCase();

    function ensureBackdrop() {
        if (globalBackdrop && document.body.contains(globalBackdrop)) return globalBackdrop;

        globalBackdrop = document.createElement('button');
        globalBackdrop.type = 'button';
        globalBackdrop.className = 'fleet-searchable-backdrop';
        globalBackdrop.setAttribute('aria-label', 'Close searchable dropdown');
        globalBackdrop.addEventListener('click', () => activeInstance?.close({ returnFocus: true }));
        document.body.appendChild(globalBackdrop);
        return globalBackdrop;
    }

    function fieldLabel(input) {
        const explicitLabel = input.id
            ? document.querySelector(`label[for="${window.CSS?.escape ? CSS.escape(input.id) : input.id}"]`)
            : null;
        const nearbyLabel = input.closest('.field')?.querySelector('label');
        const raw = explicitLabel?.textContent || nearbyLabel?.textContent || input.getAttribute('aria-label') || 'Select from list';
        return normalize(raw.replace(/\*/g, '')) || 'Select from list';
    }

    function inputAllowsCustomValue(input) {
        return input.dataset.searchableAllowCustom === 'true'
            || input.dataset.fleetSearchableAllowCustom === 'true';
    }

    function optionData(option) {
        const value = normalize(option.value || option.getAttribute('value'));
        const text = normalize(option.textContent);
        const explicitTitle = normalize(option.dataset.title);
        const explicitMeta = normalize(option.dataset.meta);
        const optionLabel = normalize(option.label || option.getAttribute('label'));
        const explicitStatus = normalize(option.dataset.status);

        return {
            value,
            title: explicitTitle || value || text,
            meta: explicitMeta || optionLabel || (text && text !== value ? text : ''),
            status: explicitStatus,
            disabled: option.disabled,
        };
    }

    class FleetSearchableDropdown {
        constructor(input) {
            this.input = input;
            this.listId = normalize(input.getAttribute('list') || input.getAttribute(ORIGINAL_LIST_ATTRIBUTE));
            this.datalist = this.listId ? document.getElementById(this.listId) : null;
            this.allowCustomValue = inputAllowsCustomValue(input);
            this.isOpen = false;
            this.activeOptionIndex = -1;
            this.renderQueued = false;
            this.suppressNextFocusOpen = false;
            this.uid = `fleet-searchable-${++instanceCounter}`;

            if (!this.listId || !this.datalist) return;

            this.build();
            this.bind();
            this.observe();
            this.refresh();
        }

        build() {
            const parent = this.input.parentNode;
            this.wrapper = document.createElement('div');
            this.wrapper.className = 'fleet-searchable-dropdown';
            this.wrapper.dataset.fleetSearchableFor = this.input.id || this.uid;

            parent.insertBefore(this.wrapper, this.input);
            this.wrapper.appendChild(this.input);

            this.input.setAttribute(ENHANCED_ATTRIBUTE, 'true');
            this.input.setAttribute(ORIGINAL_LIST_ATTRIBUTE, this.listId);
            this.input.removeAttribute('list');
            this.input.classList.add('fleet-searchable-trigger');
            this.input.setAttribute('role', 'combobox');
            this.input.setAttribute('aria-autocomplete', 'none');
            this.input.setAttribute('aria-haspopup', 'listbox');
            this.input.setAttribute('aria-expanded', 'false');
            this.input.setAttribute('aria-controls', `${this.uid}-listbox`);
            this.input.setAttribute('autocomplete', 'off');
            this.input.inputMode = 'none';

            this.clearButton = document.createElement('button');
            this.clearButton.type = 'button';
            this.clearButton.className = 'fleet-searchable-clear';
            this.clearButton.setAttribute('aria-label', `Clear ${fieldLabel(this.input)}`);
            this.clearButton.innerHTML = '<span aria-hidden="true">×</span>';

            this.arrow = document.createElement('span');
            this.arrow.className = 'fleet-searchable-arrow';
            this.arrow.setAttribute('aria-hidden', 'true');
            this.arrow.textContent = '⌄';

            this.panel = document.createElement('div');
            this.panel.className = 'fleet-searchable-panel';
            this.panel.setAttribute('aria-hidden', 'true');
            this.panel.innerHTML = `
                <div class="fleet-searchable-mobile-handle" aria-hidden="true"></div>
                <div class="fleet-searchable-panel-head">
                    <strong class="fleet-searchable-panel-title"></strong>
                    <button type="button" class="fleet-searchable-close" aria-label="Close dropdown">×</button>
                </div>
                <div class="fleet-searchable-search-box">
                    <span class="fleet-searchable-search-icon" aria-hidden="true">⌕</span>
                    <input type="search" class="fleet-searchable-search-input" autocomplete="off" spellcheck="false">
                </div>
                <div class="fleet-searchable-option-list" id="${this.uid}-listbox" role="listbox"></div>
                <div class="fleet-searchable-empty" role="status">No matching result found</div>
            `;

            this.wrapper.appendChild(this.clearButton);
            this.wrapper.appendChild(this.arrow);
            this.wrapper.appendChild(this.panel);

            this.panelTitle = this.panel.querySelector('.fleet-searchable-panel-title');
            this.closeButton = this.panel.querySelector('.fleet-searchable-close');
            this.searchInput = this.panel.querySelector('.fleet-searchable-search-input');
            this.optionList = this.panel.querySelector('.fleet-searchable-option-list');
            this.emptyState = this.panel.querySelector('.fleet-searchable-empty');

            const label = fieldLabel(this.input);
            this.panelTitle.textContent = label;
            this.searchInput.placeholder = `Search ${label.toLocaleLowerCase()}...`;
        }

        bind() {
            this.input.addEventListener('pointerdown', (event) => {
                if (this.input.disabled) return;
                event.preventDefault();
                this.toggle();
            });

            this.input.addEventListener('focus', () => {
                if (this.suppressNextFocusOpen) {
                    this.suppressNextFocusOpen = false;
                    return;
                }
                if (!this.input.disabled && !this.isOpen) this.open();
            });

            this.input.addEventListener('keydown', (event) => {
                if (this.input.disabled) return;

                if (event.key === 'Escape') {
                    event.preventDefault();
                    this.close({ returnFocus: false });
                    return;
                }

                if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
                    event.preventDefault();
                    this.open();
                    return;
                }

                if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                    event.preventDefault();
                    this.open({ initialQuery: event.key });
                }
            });

            this.searchInput.addEventListener('input', () => this.renderOptions());
            this.searchInput.addEventListener('keydown', (event) => this.handleSearchKeydown(event));

            this.clearButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                if (this.input.disabled) return;
                this.selectValue('');
            });

            this.closeButton.addEventListener('click', () => this.close({ returnFocus: true }));

            this.optionList.addEventListener('click', (event) => {
                const option = event.target.closest('[data-fleet-searchable-value]');
                if (!option || option.disabled) return;
                this.selectValue(option.dataset.fleetSearchableValue || '');
            });

            this.input.addEventListener('input', () => this.refreshSelectionState());
            this.input.addEventListener('change', () => this.refreshSelectionState());

            document.addEventListener('pointerdown', (event) => {
                if (!this.isOpen || this.wrapper.contains(event.target)) return;
                if (globalBackdrop?.contains(event.target)) return;
                this.close({ returnFocus: false });
            });
        }

        observe() {
            this.listObserver = new MutationObserver(() => this.queueRefresh());
            this.listObserver.observe(this.datalist, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['value', 'label', 'disabled', 'data-title', 'data-meta', 'data-status'],
            });

            this.inputObserver = new MutationObserver(() => {
                this.wrapper.classList.toggle('is-disabled', this.input.disabled);
                if (this.input.disabled && this.isOpen) this.close({ returnFocus: false });
                this.refreshSelectionState();
            });
            this.inputObserver.observe(this.input, {
                attributes: true,
                attributeFilter: ['disabled', 'placeholder', 'required', 'aria-invalid', 'value'],
            });

            const form = this.input.form;
            if (form) {
                form.addEventListener('reset', () => window.setTimeout(() => this.refresh(), 0));
            }
        }

        queueRefresh() {
            if (this.renderQueued) return;
            this.renderQueued = true;
            window.requestAnimationFrame(() => {
                this.renderQueued = false;
                this.refresh();
            });
        }

        options() {
            return Array.from(this.datalist.options || [])
                .map(optionData)
                .filter((item) => item.value);
        }

        refresh() {
            this.wrapper.classList.toggle('is-disabled', this.input.disabled);
            this.refreshSelectionState();
            if (this.isOpen) this.renderOptions();
        }

        refreshSelectionState() {
            const hasValue = normalize(this.input.value) !== '';
            this.wrapper.classList.toggle('has-value', hasValue);
            this.clearButton.hidden = !hasValue || this.input.required || this.input.disabled;
            this.input.setAttribute('aria-expanded', this.isOpen ? 'true' : 'false');
        }

        toggle() {
            this.isOpen ? this.close({ returnFocus: false }) : this.open();
        }

        open({ initialQuery = '' } = {}) {
            if (this.input.disabled || !this.datalist) return;
            if (activeInstance && activeInstance !== this) activeInstance.close({ returnFocus: false });

            activeInstance = this;
            this.isOpen = true;
            this.wrapper.classList.add('open');
            this.input.classList.add('active');
            this.input.setAttribute('aria-expanded', 'true');
            this.panel.setAttribute('aria-hidden', 'false');
            this.searchInput.value = initialQuery;
            this.activeOptionIndex = -1;
            this.renderOptions();
            this.setDesktopDirection();

            const backdrop = ensureBackdrop();
            backdrop.classList.add('show');
            document.body.classList.add('fleet-searchable-open');

            window.setTimeout(() => {
                this.searchInput.focus({ preventScroll: true });
                if (initialQuery) this.searchInput.setSelectionRange(initialQuery.length, initialQuery.length);
            }, 40);
        }

        close({ returnFocus = false } = {}) {
            if (!this.isOpen) return;

            this.isOpen = false;
            this.wrapper.classList.remove('open', 'drop-up');
            this.input.classList.remove('active');
            this.input.setAttribute('aria-expanded', 'false');
            this.panel.setAttribute('aria-hidden', 'true');
            this.searchInput.value = '';
            this.activeOptionIndex = -1;

            if (activeInstance === this) activeInstance = null;
            globalBackdrop?.classList.remove('show');
            document.body.classList.remove('fleet-searchable-open');
            this.refreshSelectionState();

            if (returnFocus && !this.input.disabled) {
                this.suppressNextFocusOpen = true;
                window.setTimeout(() => {
                    this.input.focus({ preventScroll: true });
                    window.setTimeout(() => {
                        this.suppressNextFocusOpen = false;
                    }, 0);
                }, 0);
            }
        }

        setDesktopDirection() {
            this.wrapper.classList.remove('drop-up');
            if (window.innerWidth <= MOBILE_BREAKPOINT) return;

            const rect = this.input.getBoundingClientRect();
            const roomBelow = window.innerHeight - rect.bottom;
            const roomAbove = rect.top;
            if (roomBelow < 330 && roomAbove > roomBelow) this.wrapper.classList.add('drop-up');
        }

        renderOptions() {
            const query = searchableText(this.searchInput.value);
            const selectedValue = normalize(this.input.value);
            const allOptions = this.options();
            const matching = allOptions.filter((item) => {
                if (!query) return true;
                return [item.title, item.meta, item.status, item.value]
                    .some((part) => searchableText(part).includes(query));
            });

            const rows = [];
            if (!this.input.required && !query) {
                const clearTitle = normalize(this.input.placeholder) || 'Clear selection';
                rows.push(this.optionMarkup({
                    value: '',
                    title: clearTitle,
                    meta: 'Show all / no selection',
                    status: '',
                    disabled: false,
                }, selectedValue === ''));
            }

            matching.forEach((item) => rows.push(this.optionMarkup(item, item.value === selectedValue)));

            const exactMatch = allOptions.some((item) => searchableText(item.value) === query);
            if (this.allowCustomValue && query && !exactMatch) {
                rows.push(this.optionMarkup({
                    value: normalize(this.searchInput.value),
                    title: `Use “${normalize(this.searchInput.value)}”`,
                    meta: 'Use this custom value',
                    status: '',
                    disabled: false,
                    custom: true,
                }, false));
            }

            this.optionList.innerHTML = rows.join('');
            const visibleOptions = this.optionButtons();
            this.emptyState.style.display = visibleOptions.length ? 'none' : 'block';
            this.activeOptionIndex = visibleOptions.length ? 0 : -1;
            this.updateActiveOption();
        }

        optionMarkup(item, selected) {
            const escape = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const badge = item.status
                ? `<span class="fleet-searchable-status">${escape(item.status)}</span>`
                : (selected ? '<span class="fleet-searchable-selected-mark" aria-label="Selected">✓</span>' : '');

            return `
                <button type="button"
                    class="fleet-searchable-option${selected ? ' selected' : ''}${item.custom ? ' custom' : ''}"
                    role="option"
                    aria-selected="${selected ? 'true' : 'false'}"
                    data-fleet-searchable-value="${escape(item.value)}"
                    ${item.disabled ? 'disabled' : ''}>
                    <span class="fleet-searchable-option-copy">
                        <span class="fleet-searchable-option-title">${escape(item.title)}</span>
                        ${item.meta ? `<span class="fleet-searchable-option-meta">${escape(item.meta)}</span>` : ''}
                    </span>
                    ${badge}
                </button>
            `;
        }

        optionButtons() {
            return Array.from(this.optionList.querySelectorAll('.fleet-searchable-option:not(:disabled)'));
        }

        handleSearchKeydown(event) {
            const buttons = this.optionButtons();

            if (event.key === 'Escape') {
                event.preventDefault();
                this.close({ returnFocus: true });
                return;
            }

            if (!buttons.length) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.activeOptionIndex = (this.activeOptionIndex + 1) % buttons.length;
                this.updateActiveOption();
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.activeOptionIndex = (this.activeOptionIndex - 1 + buttons.length) % buttons.length;
                this.updateActiveOption();
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                const selected = buttons[Math.max(0, this.activeOptionIndex)];
                selected?.click();
            }
        }

        updateActiveOption() {
            const buttons = this.optionButtons();
            buttons.forEach((button, index) => {
                const active = index === this.activeOptionIndex;
                button.classList.toggle('active', active);
                button.tabIndex = active ? 0 : -1;
                if (active) button.scrollIntoView({ block: 'nearest' });
            });
        }

        selectValue(value) {
            const nextValue = normalize(value);
            this.input.value = nextValue;
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.refreshSelectionState();
            this.close({ returnFocus: true });
        }
    }

    function enhance(input) {
        if (!(input instanceof HTMLInputElement)) return null;
        if (input.hasAttribute(ENHANCED_ATTRIBUTE)) return input.__fleetSearchableDropdown || null;
        if (!input.getAttribute('list')) return null;

        const list = document.getElementById(input.getAttribute('list'));
        if (!list) return null;

        const instance = new FleetSearchableDropdown(input);
        input.__fleetSearchableDropdown = instance;
        return instance;
    }

    function enhanceWithin(root = document) {
        const inputs = [];
        if (root instanceof HTMLInputElement && root.matches('input[list]')) inputs.push(root);
        root.querySelectorAll?.('input[list]').forEach((input) => inputs.push(input));
        inputs.forEach(enhance);
    }

    function initialize() {
        ensureBackdrop();
        enhanceWithin(document);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) enhanceWithin(node);
                });

                if (mutation.type === 'attributes' && mutation.target instanceof HTMLInputElement) {
                    enhance(mutation.target);
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['list'],
        });

        window.addEventListener('resize', () => activeInstance?.setDesktopDirection(), { passive: true });
        window.addEventListener('orientationchange', () => activeInstance?.setDesktopDirection(), { passive: true });
    }

    window.FleetmanSearchableDropdown = {
        enhance,
        refresh(target) {
            const input = typeof target === 'string' ? document.querySelector(target) : target;
            input?.__fleetSearchableDropdown?.refresh();
        },
        refreshAll() {
            document.querySelectorAll(`[${ENHANCED_ATTRIBUTE}]`).forEach((input) => input.__fleetSearchableDropdown?.refresh());
        },
        closeAll() {
            activeInstance?.close({ returnFocus: false });
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
