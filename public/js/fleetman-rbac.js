(() => {
    'use strict';

    const auth = window.FLEETMAN?.auth || {};
    const pageAccess = auth.pageAccess || {};
    const managePermission = String(pageAccess.managePermission || '');
    const isReadOnly = Boolean(managePermission) && pageAccess.canView === true && pageAccess.canManage !== true;
    const isCreateOnly = Boolean(managePermission) && pageAccess.canManage === true && pageAccess.canView !== true;
    const canDeleteRecords = auth.canDeleteRecords === true;
    const canViewFullDetails = typeof window.FleetmanDetailViewer?.canViewDetails === 'function'
        ? window.FleetmanDetailViewer.canViewDetails()
        : Boolean(auth.isSuperAdmin);

    function permissionAllowed(permission) {
        if (!permission) return true;
        return Boolean(auth.isSuperAdmin) || (Array.isArray(auth.permissions) && auth.permissions.includes(permission));
    }

    function mutationAttributeFound(element) {
        return Array.from(element.attributes || []).some((attribute) => {
            const name = String(attribute.name || '').toLowerCase();
            return name.startsWith('data-master-edit-')
                || name.startsWith('data-master-delete-')
                || name === 'data-recharge-edit'
                || name === 'data-recharge-delete';
        });
    }

    function isDeleteControl(element) {
        if (!(element instanceof HTMLElement)) return false;
        if (element.matches('[data-rbac-ignore], [data-rbac-disabled]')) return false;

        const form = element.matches('form') ? element : element.closest('form');
        if (form) {
            const declaredMethod = String(form.getAttribute('method') || 'get').toLowerCase();
            const spoofedMethod = String(form.querySelector('input[name="_method"]')?.value || '').toLowerCase();
            if (declaredMethod === 'delete' || spoofedMethod === 'delete') return true;
        }

        const id = String(element.id || '').toLowerCase();
        const classes = String(element.className || '').toLowerCase().split(/\s+/).filter(Boolean);
        const text = String(element.textContent || element.value || '').trim().toLowerCase();
        const hasDeleteData = Array.from(element.attributes || []).some((attribute) =>
            String(attribute.name || '').toLowerCase().includes('delete')
        );

        return id.startsWith('delete')
            || classes.some((className) => className.startsWith('delete-'))
            || hasDeleteData
            || text === 'delete';
    }

    function enforceDeleteControls(root = document) {
        if (canDeleteRecords) return;

        const scope = root instanceof Element ? root : document.querySelector('.main-content');
        if (!scope) return;

        if (isDeleteControl(scope)) {
            muteElement(scope, 'You do not have Delete Records permission. A Super Admin can grant it from Role Matrix.');
        }

        scope.querySelectorAll('button, a, input[type="submit"], form').forEach((element) => {
            if (!isDeleteControl(element)) return;

            if (element.matches('form')) {
                element.querySelectorAll('button, input[type="submit"]').forEach((button) =>
                    muteElement(button, 'You do not have Delete Records permission. A Super Admin can grant it from Role Matrix.')
                );
                return;
            }

            muteElement(element, 'You do not have Delete Records permission. A Super Admin can grant it from Role Matrix.');
        });
    }

    function isMutationControl(element) {
        if (!(element instanceof HTMLElement)) return false;
        if (element.matches('[data-rbac-ignore], [data-rbac-disabled]')) return false;

        const tagName = element.tagName.toLowerCase();
        if (!['button', 'a', 'input'].includes(tagName)) return false;

        if (tagName === 'button' && String(element.getAttribute('type') || 'submit').toLowerCase() === 'submit') {
            return !element.closest('.logout-form');
        }

        const id = String(element.id || '').toLowerCase();
        const classNames = String(element.className || '').toLowerCase().split(/\s+/).filter(Boolean);
        const mutationIdPrefixes = ['save', 'submit', 'draft', 'add', 'update', 'delete', 'generate'];
        const mutationClassPrefixes = ['edit-', 'delete-', 'remove-'];

        if (mutationIdPrefixes.some((prefix) => id.startsWith(prefix))) return true;
        if (classNames.some((className) => mutationClassPrefixes.some((prefix) => className.startsWith(prefix)))) return true;
        if (classNames.includes('mark-paid-btn')) return true;
        if (mutationAttributeFound(element)) return true;

        return false;
    }

    function muteElement(element, message) {
        if (!(element instanceof HTMLElement) || element.dataset.rbacMuted === 'true') return;

        element.dataset.rbacMuted = 'true';
        element.classList.add('rbac-control-muted');
        element.setAttribute('aria-disabled', 'true');
        element.setAttribute('title', message);

        if ('disabled' in element) {
            element.disabled = true;
        } else {
            element.setAttribute('tabindex', '-1');
        }
    }

    function muteControl(element) {
        if (!isMutationControl(element)) return;
        muteElement(element, 'Your role has read-only access to this module.');
    }

    function isDetailControl(element) {
        if (!(element instanceof HTMLElement)) return false;
        return String(element.className || '')
            .toLowerCase()
            .split(/\s+/)
            .some((className) => className.startsWith('view-'));
    }

    function enforceDetailControls(root = document) {
        if (canViewFullDetails) return;

        const scope = root instanceof Element ? root : document.querySelector('.main-content');
        if (!scope) return;

        if (isDetailControl(scope)) {
            muteElement(scope, 'Only Super Admin and Admin User can view full record details.');
        }
        scope.querySelectorAll('button, a').forEach((element) => {
            if (isDetailControl(element)) {
                muteElement(element, 'Only Super Admin and Admin User can view full record details.');
            }
        });
    }

    function enforceReadOnlyControls(root = document) {
        if (!isReadOnly) return;

        const scope = root instanceof Element ? root : document.querySelector('.main-content');
        if (!scope) return;

        if (isMutationControl(scope)) muteControl(scope);
        scope.querySelectorAll('button, a, input[type="submit"]').forEach(muteControl);

        // Traditional POST/PUT/DELETE forms (users, role matrix and master
        // data) are fully read-only, while GET search/filter forms remain usable.
        scope.querySelectorAll('form').forEach((form) => {
            if (form.closest('.logout-form')) return;
            const method = String(form.getAttribute('method') || 'get').toLowerCase();
            if (method === 'get') return;

            form.querySelectorAll('input:not([type="hidden"]), select, textarea, button').forEach((field) => {
                muteElement(field, 'Your role has read-only access to this module.');
            });
        });
    }

    function addReadOnlyBanner() {
        if (!isReadOnly || document.querySelector('.rbac-readonly-banner')) return;

        const body = document.querySelector('.fleet-main-body');
        const page = String(document.body?.dataset?.page || '').toLowerCase();
        if (!body || ['users', 'role-matrix'].includes(page)) return;

        const banner = document.createElement('div');
        banner.className = 'rbac-readonly-banner';
        banner.setAttribute('role', 'status');
        banner.innerHTML = '<span aria-hidden="true">🔒</span><div><b>Read-only access</b><small>Your role can view this module, but create, edit, delete, upload, and save options are disabled.</small></div>';
        body.prepend(banner);
    }


    function addCreateOnlyBanner() {
        if (!isCreateOnly || document.querySelector('.rbac-create-only-banner')) return;

        const body = document.querySelector('.fleet-main-body');
        if (!body) return;

        const banner = document.createElement('div');
        banner.className = 'rbac-readonly-banner rbac-create-only-banner';
        banner.setAttribute('role', 'status');
        banner.innerHTML = '<span aria-hidden="true">➕</span><div><b>Create-only access</b><small>You may add records in this module, but your role is not allowed to view its list.</small></div>';
        body.prepend(banner);
    }

    function isListNavigationControl(element) {
        if (!(element instanceof HTMLElement)) return false;

        const target = String(element.dataset?.pageTarget || '');
        if (target.endsWith('ListPage')) return true;

        if (element.tagName.toLowerCase() !== 'a' || !element.getAttribute('href')) return false;

        try {
            const url = new URL(element.href, window.location.href);
            if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return false;
            return url.searchParams.get('action') !== 'add';
        } catch (_) {
            return false;
        }
    }

    function enforceCreateOnlyControls(root = document) {
        if (!isCreateOnly) return;

        const page = String(document.body?.dataset?.page || '').toLowerCase();
        const pagePairs = {
            'yards': ['yardAddPage', 'yardListPage'],
            'vehicles': ['vehicleAddPage', 'vehicleListPage'],
            'fuel-prices': ['fuelPriceAddPage', 'fuelPriceListPage'],
            'fuel-recharge': ['rechargeAddPage', 'rechargeListPage'],
            'trips': ['tripAddPage', 'tripListPage'],
            'driver-attendance': ['attendanceAddPage', 'attendanceListPage'],
            'drivers': ['driverAddPage', 'driverListPage'],
            'employees': ['employeeAddPage', 'employeeListPage'],
            'clients': ['clientAddPage', 'clientListPage'],
            'vendors': ['vendorAddPage', 'vendorListPage'],
            'contracts': ['contractCreatePage', 'contractListPage'],
        };
        const pair = pagePairs[page];
        if (pair) {
            document.getElementById(pair[0])?.classList.remove('hidden');
            document.getElementById(pair[1])?.classList.add('hidden');
        }

        const scope = root instanceof Element ? root : document.querySelector('.main-content');
        if (!scope) return;

        if (isListNavigationControl(scope)) {
            muteElement(scope, 'You are not allowed to view the list.');
        }
        scope.querySelectorAll('button[data-page-target], a[href]').forEach((element) => {
            if (isListNavigationControl(element)) {
                muteElement(element, 'You are not allowed to view the list.');
            }
        });
    }

    function forceListPage() {
        if (!isReadOnly) return;

        const page = String(document.body?.dataset?.page || '').toLowerCase();
        const pagePairs = {
            'yards': ['yardAddPage', 'yardListPage'],
            'vehicles': ['vehicleAddPage', 'vehicleListPage'],
            'fuel-prices': ['fuelPriceAddPage', 'fuelPriceListPage'],
            'fuel-recharge': ['rechargeAddPage', 'rechargeListPage'],
            'trips': ['tripAddPage', 'tripListPage'],
            'driver-attendance': ['attendanceAddPage', 'attendanceListPage'],
            'drivers': ['driverAddPage', 'driverListPage'],
            'employees': ['employeeAddPage', 'employeeListPage'],
            'clients': ['clientAddPage', 'clientListPage'],
            'vendors': ['vendorAddPage', 'vendorListPage'],
            'contracts': ['contractCreatePage', 'contractListPage'],
        };
        const pair = pagePairs[page];
        if (!pair) return;

        const addPage = document.getElementById(pair[0]);
        const listPage = document.getElementById(pair[1]);
        addPage?.classList.add('hidden');
        listPage?.classList.remove('hidden');
    }

    function blockDisabledNavigation(event) {
        const disabled = event.target.closest('[data-rbac-disabled="true"], .rbac-control-muted');
        if (!disabled) return;

        event.preventDefault();
        event.stopImmediatePropagation();
    }

    document.addEventListener('click', blockDisabledNavigation, true);
    document.addEventListener('submit', (event) => {
        if (event.target.closest('.logout-form')) return;

        if (!canDeleteRecords && isDeleteControl(event.target)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        if (!isReadOnly) return;
        event.preventDefault();
        event.stopImmediatePropagation();
    }, true);

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-rbac-disabled="true"]').forEach((element) => {
            element.addEventListener('click', blockDisabledNavigation);
        });

        forceListPage();
        addReadOnlyBanner();
        addCreateOnlyBanner();
        enforceReadOnlyControls();
        enforceCreateOnlyControls();
        enforceDeleteControls();
        enforceDetailControls();

        if (isReadOnly || isCreateOnly || !canDeleteRecords || !canViewFullDetails) {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node instanceof Element) {
                                enforceReadOnlyControls(node);
                                enforceCreateOnlyControls(node);
                                enforceDeleteControls(node);
                                enforceDetailControls(node);
                            }
                        });
                    });
                }).observe(mainContent, { childList: true, subtree: true });
            }
        }
    }, { once: true });

    window.FleetmanAccess = Object.freeze({
        has: permissionAllowed,
        canManagePage: () => pageAccess.canManage === true,
        canDeleteRecords: () => canDeleteRecords,
        pageAccess: { ...pageAccess },
    });
})();
