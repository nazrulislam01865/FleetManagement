/*
 * FleetMan sidebar and submenu navigation.
 *
 * This file intentionally contains navigation-only behavior. Module business
 * logic remains in fleetman.js. Keeping one navigation owner prevents the
 * sidebar drawer, submenu state, page switching and scroll restoration from
 * competing with one another.
 */
(() => {
    'use strict';

    const SIDEBAR_SCROLL_KEY = 'fleetman.sidebar.scrollTop';
    const SIDEBAR_PENDING_SCROLL_KEY = 'fleetman.sidebar.pendingScrollTop';
    const SUBMENU_OPEN_PREFIX = 'fleetman.sidebar.open.';
    const MOBILE_BREAKPOINT = '(max-width: 1050px)';

    const safeStorage = (name) => {
        try {
            return window[name];
        } catch (_) {
            return null;
        }
    };

    const localStore = safeStorage('localStorage');
    const sessionStore = safeStorage('sessionStorage');

    const PAGE_VIEWS = {
        vehicles: {
            add: 'vehicleAddPage',
            list: 'vehicleListPage',
        },
        'fuel-prices': {
            add: 'fuelPriceAddPage',
            list: 'fuelPriceListPage',
        },
        'fuel-recharge': {
            add: 'rechargeAddPage',
            list: 'rechargeListPage',
        },
        vendors: {
            add: 'vendorAddPage',
            list: 'vendorListPage',
        },
        trips: {
            add: 'tripAddPage',
            list: 'tripListPage',
        },
        drivers: {
            add: 'driverAddPage',
            list: 'driverListPage',
        },
        clients: {
            add: 'clientAddPage',
            list: 'clientListPage',
        },
        employees: {
            add: 'employeeAddPage',
            list: 'employeeListPage',
        },
        'driver-attendance': {
            add: 'attendanceAddPage',
            list: 'attendanceListPage',
        },
        contracts: {
            add: 'contractCreatePage',
            list: 'contractListPage',
        },
    };

    const normalisePath = (pathname) => {
        const path = String(pathname || '/').replace(/\/+$/, '');
        return path || '/';
    };

    const isPlainLeftClick = (event) => (
        event.button === 0
        && !event.metaKey
        && !event.ctrlKey
        && !event.shiftKey
        && !event.altKey
    );

    const readStoredNumber = (storage, key) => {
        if (!storage) {
            return null;
        }
        try {
            const rawValue = storage.getItem(key);
            if (rawValue === null || rawValue === '') {
                return null;
            }
            const value = Number(rawValue);
            return Number.isFinite(value) && value >= 0 ? value : null;
        } catch (_) {
            return null;
        }
    };

    const writeStorage = (storage, key, value) => {
        if (!storage) {
            return;
        }
        try {
            storage.setItem(key, String(value));
        } catch (_) {
            // Storage can be unavailable in strict privacy modes.
        }
    };

    const removeStorage = (storage, key) => {
        if (!storage) {
            return;
        }
        try {
            storage.removeItem(key);
        } catch (_) {
            // Storage can be unavailable in strict privacy modes.
        }
    };

    function pageDefinition() {
        return PAGE_VIEWS[document.body?.dataset?.page || ''] || null;
    }

    function currentAction(url = new URL(window.location.href)) {
        return url.searchParams.get('action') === 'add' ? 'add' : 'list';
    }

    function setDrawer(open) {
        const body = document.body;
        const menuButton = document.getElementById('menuBtn');

        body?.classList.toggle('drawer-open', Boolean(open));
        menuButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function setMenuOpen(block, open, persist = true) {
        const key = block?.dataset?.menuKey || '';
        const toggle = block?.querySelector('[data-submenu-toggle]');

        if (!block || !toggle || !key) {
            return;
        }

        block.classList.toggle('open', Boolean(open));
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (persist) {
            writeStorage(localStore, SUBMENU_OPEN_PREFIX + key, open ? '1' : '0');
        }
    }

    function saveSidebarScroll({ pendingNavigation = false } = {}) {
        const sidebar = document.getElementById('fleetSidebar');
        if (!sidebar) {
            return;
        }

        const scrollTop = Math.max(0, Math.round(sidebar.scrollTop || 0));
        writeStorage(localStore, SIDEBAR_SCROLL_KEY, scrollTop);

        if (pendingNavigation) {
            writeStorage(sessionStore, SIDEBAR_PENDING_SCROLL_KEY, scrollTop);
        }
    }

    function restoreSidebarScroll() {
        const sidebar = document.getElementById('fleetSidebar');
        if (!sidebar) {
            return;
        }

        const inlineTarget = Number(window.__fleetmanSidebarScrollTarget);
        const pendingTarget = readStoredNumber(sessionStore, SIDEBAR_PENDING_SCROLL_KEY);
        const savedTarget = readStoredNumber(localStore, SIDEBAR_SCROLL_KEY);
        const target = Number.isFinite(inlineTarget) && inlineTarget >= 0
            ? inlineTarget
            : (pendingTarget ?? savedTarget ?? 0);

        let restoring = true;
        let userTookControl = false;

        const stopAutomaticRestore = () => {
            userTookControl = true;
            restoring = false;
        };

        const apply = () => {
            if (userTookControl) {
                return;
            }

            // Reapply the original absolute position after layout changes such
            // as the company logo loading or an active submenu opening.
            sidebar.scrollTop = target;
        };

        sidebar.addEventListener('wheel', stopAutomaticRestore, { passive: true, once: true });
        sidebar.addEventListener('touchstart', stopAutomaticRestore, { passive: true, once: true });
        sidebar.addEventListener('pointerdown', stopAutomaticRestore, { passive: true, once: true });

        sidebar.addEventListener('scroll', () => {
            if (!restoring) {
                saveSidebarScroll();
            }
        }, { passive: true });

        apply();
        requestAnimationFrame(apply);
        [60, 160, 320].forEach((delay) => window.setTimeout(apply, delay));

        const logoImage = sidebar.querySelector('.logo-card img');
        if (logoImage && !logoImage.complete) {
            logoImage.addEventListener('load', apply, { once: true });
            logoImage.addEventListener('error', apply, { once: true });
        }

        window.addEventListener('load', apply, { once: true });

        window.setTimeout(() => {
            if (!userTookControl) {
                apply();
            }
            restoring = false;
            removeStorage(sessionStore, SIDEBAR_PENDING_SCROLL_KEY);
            saveSidebarScroll();
        }, 380);
    }

    function syncActiveSubmenu(url = new URL(window.location.href)) {
        const currentPath = normalisePath(url.pathname);
        const currentActionName = currentAction(url);

        document.querySelectorAll('.submenu-item').forEach((item) => {
            let itemUrl;
            try {
                itemUrl = new URL(item.href, window.location.href);
            } catch (_) {
                return;
            }

            const samePath = normalisePath(itemUrl.pathname) === currentPath;
            const itemHasAction = itemUrl.searchParams.has('action');
            const actionMatches = !itemHasAction || currentAction(itemUrl) === currentActionName;
            const active = samePath && actionMatches;

            item.classList.toggle('active', active);
            if (active) {
                item.setAttribute('aria-current', 'page');
                const block = item.closest('[data-menu-block]');
                if (block) {
                    setMenuOpen(block, true, true);
                    block.querySelector('.menu-item')?.classList.add('active');
                }
            } else {
                item.removeAttribute('aria-current');
            }
        });
    }

    function showCurrentModuleView(url = new URL(window.location.href), options = {}) {
        const definition = pageDefinition();
        if (!definition) {
            return false;
        }

        const action = currentAction(url);
        const targetId = definition[action];
        const target = document.getElementById(targetId);

        if (!target) {
            return false;
        }

        Object.values(definition).forEach((id) => {
            const element = document.getElementById(id);
            if (!element) {
                return;
            }

            // The old navigation handler wrote inline display values. Clear
            // those values so each module returns to its original class-based
            // show/hide behavior.
            element.style.removeProperty('display');
            element.classList.toggle('hidden', id !== targetId);
        });

        syncActiveSubmenu(url);

        if (options.scrollMain !== false) {
            window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        }

        document.dispatchEvent(new CustomEvent('fleetman:view-changed', {
            detail: {
                page: document.body.dataset.page || '',
                action,
                targetId,
            },
        }));

        return true;
    }

    function handleDocumentClick(event) {
        const sidebar = document.getElementById('fleetSidebar');
        if (!sidebar) {
            return;
        }

        const submenuToggle = event.target.closest('[data-submenu-toggle]');
        if (submenuToggle && sidebar.contains(submenuToggle)) {
            if (isPlainLeftClick(event)) {
                event.preventDefault();
                const block = submenuToggle.closest('[data-menu-block]');
                if (block) {
                    setMenuOpen(block, !block.classList.contains('open'));
                }
            }
            return;
        }

        const link = event.target.closest('a.menu-item, a.submenu-item');
        if (!link || !sidebar.contains(link)) {
            return;
        }

        saveSidebarScroll();

        if (window.matchMedia(MOBILE_BREAKPOINT).matches) {
            setDrawer(false);
        }

        if (!link.classList.contains('submenu-item') || !isPlainLeftClick(event)) {
            if (link.href && link.href !== '#') {
                saveSidebarScroll({ pendingNavigation: true });
            }
            return;
        }

        let destination;
        try {
            destination = new URL(link.href, window.location.href);
        } catch (_) {
            return;
        }

        const sameOrigin = destination.origin === window.location.origin;
        const samePath = normalisePath(destination.pathname) === normalisePath(window.location.pathname);
        const definition = pageDefinition();
        const action = currentAction(destination);
        const targetExists = Boolean(definition?.[action] && document.getElementById(definition[action]));

        if (!sameOrigin || !samePath || !targetExists) {
            saveSidebarScroll({ pendingNavigation: true });
            return;
        }

        event.preventDefault();
        window.history.pushState({ fleetmanAction: action }, '', destination.href);
        showCurrentModuleView(destination);
    }

    function initialise() {
        const sidebar = document.getElementById('fleetSidebar');
        const menuButton = document.getElementById('menuBtn');
        const backdrop = document.getElementById('backdrop');

        menuButton?.setAttribute('aria-controls', 'fleetSidebar');
        menuButton?.setAttribute('aria-expanded', 'false');
        menuButton?.addEventListener('click', () => {
            setDrawer(!document.body.classList.contains('drawer-open'));
        });
        backdrop?.addEventListener('click', () => setDrawer(false));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setDrawer(false);
            }
        });

        // Capture phase makes this the single navigation owner before the
        // module-level delegated click handlers run.
        document.addEventListener('click', handleDocumentClick, true);

        window.addEventListener('popstate', () => {
            showCurrentModuleView(new URL(window.location.href));
        });

        window.addEventListener('pagehide', () => saveSidebarScroll());
        window.addEventListener('beforeunload', () => saveSidebarScroll());

        if (sidebar) {
            restoreSidebarScroll();
        }

        // This script is loaded after fleetman.js. Therefore this final sync
        // runs after every module has completed its original initialisation.
        showCurrentModuleView(new URL(window.location.href), { scrollMain: false });
        syncActiveSubmenu();
    }

    window.FleetmanNavigation = Object.freeze({
        showCurrentModuleView,
        saveSidebarScroll,
        syncActiveSubmenu,
    });

    document.addEventListener('DOMContentLoaded', initialise, { once: true });
})();
