(() => {
    'use strict';

    const config = window.FLEETMAN_NOTIFICATIONS || {};
    const widget = document.getElementById('fleetNotificationWidget');
    if (!widget || !config.feedUrl) return;

    const bell = document.getElementById('fleetNotificationBell');
    const panel = document.getElementById('fleetNotificationPanel');
    const list = document.getElementById('fleetNotificationList');
    const count = document.getElementById('fleetNotificationCount');
    const status = document.getElementById('fleetNotificationStatus');
    const markAll = document.getElementById('fleetMarkAllRead');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let notifications = [];
    let unreadCount = 0;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    function updateCount(value) {
        unreadCount = Math.max(0, Number(value) || 0);
        count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        count.classList.toggle('hidden', unreadCount === 0);
        status.textContent = unreadCount ? `${unreadCount} unread` : 'All caught up';
    }

    function itemMarkup(notification) {
        const data = notification.data || {};
        const unread = !notification.read_at;
        const id = escapeHtml(notification.id || '');
        const url = data.url ? escapeHtml(data.url) : '';
        const openMarkup = url
            ? `<a href="${url}" class="fleet-notification-open" data-notification-id="${id}">Open</a>`
            : '';

        return `
            <article class="fleet-notification-item ${unread ? 'unread' : ''}" data-notification-id="${id}">
                <div class="fleet-notification-item-icon">${escapeHtml(data.icon || '🔔')}</div>
                <div class="fleet-notification-item-copy">
                    <strong>${escapeHtml(data.title || 'FleetMan Notification')}</strong>
                    <p>${escapeHtml(data.message || '')}</p>
                    <small>${escapeHtml(notification.created_at_human || formatTime(notification.created_at))}</small>
                    <div class="fleet-notification-item-actions">
                        ${openMarkup}
                        ${unread ? `<button type="button" data-mark-read="${id}">Mark read</button>` : ''}
                    </div>
                </div>
            </article>`;
    }

    function formatTime(value) {
        if (!value) return 'Just now';
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? 'Just now' : parsed.toLocaleString();
    }

    function render() {
        if (!notifications.length) {
            list.innerHTML = '<div class="fleet-notification-empty">No notifications yet.</div>';
            return;
        }
        list.innerHTML = notifications.map(itemMarkup).join('');
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.headers || {}),
            },
            ...options,
        });
        if (!response.ok) throw new Error(`Notification request failed (${response.status}).`);
        return response.json();
    }

    async function refresh() {
        try {
            const payload = await request(config.feedUrl);
            notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
            updateCount(payload.unread_count);
            render();
        } catch (error) {
            status.textContent = 'Unable to load';
            console.warn(error);
        }
    }

    async function markRead(id) {
        if (!id) return;
        const url = String(config.readUrlTemplate || '').replace('__ID__', encodeURIComponent(id));
        if (!url) return;
        const payload = await request(url, { method: 'POST' });
        notifications = notifications.map((item) => item.id === id ? { ...item, read_at: new Date().toISOString() } : item);
        updateCount(payload.unread_count);
        render();
    }

    async function markAllRead() {
        const payload = await request(config.readAllUrl, { method: 'POST' });
        notifications = notifications.map((item) => ({ ...item, read_at: item.read_at || new Date().toISOString() }));
        updateCount(payload.unread_count);
        render();
    }

    function showRealtimeToast(notification) {
        const data = notification.data || {};
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = `${data.icon || '🔔'} ${data.title || 'Notification'}: ${data.message || ''}`;
        toast.classList.add('show');
        window.setTimeout(() => toast.classList.remove('show'), 5000);
    }

    function addRealtime(notification) {
        if (!notification?.id || notifications.some((item) => item.id === notification.id)) return;
        notifications = [{ ...notification, created_at_human: 'Just now' }, ...notifications].slice(0, 15);
        updateCount(unreadCount + 1);
        render();
        showRealtimeToast(notification);
    }

    bell?.addEventListener('click', () => {
        const opening = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !opening);
        bell.setAttribute('aria-expanded', opening ? 'true' : 'false');
        if (opening) refresh();
    });

    document.addEventListener('click', (event) => {
        if (!widget.contains(event.target)) {
            panel.classList.add('hidden');
            bell?.setAttribute('aria-expanded', 'false');
        }
    });

    list?.addEventListener('click', async (event) => {
        const markButton = event.target.closest('[data-mark-read]');
        const openLink = event.target.closest('.fleet-notification-open');
        try {
            if (markButton) {
                await window.FleetmanRunTransaction(markButton, () => markRead(markButton.dataset.markRead), {
                    loadingText: 'Updating...',
                });
            } else if (openLink) {
                await markRead(openLink.dataset.notificationId);
            }
        } catch (error) {
            console.warn(error);
        }
    });

    markAll?.addEventListener('click', async () => {
        try {
            await window.FleetmanRunTransaction(markAll, markAllRead, { loadingText: 'Updating...' });
        } catch (error) {
            console.warn(error);
        }
    });

    document.querySelectorAll('.fleet-page-mark-read').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await window.FleetmanRunTransaction(button, async () => {
                    await markRead(button.dataset.notificationId);
                    button.closest('.fleet-notification-page-item')?.classList.remove('unread');
                    button.remove();
                }, { loadingText: 'Updating...' });
            } catch (error) {
                console.warn(error);
            }
        });
    });

    if (config.pusherEnabled && window.Pusher && config.pusherKey) {
        try {
            const authOptions = {
                endpoint: config.pusherAuthUrl,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            };
            const pusher = new window.Pusher(config.pusherKey, {
                cluster: config.pusherCluster,
                forceTLS: true,
                authEndpoint: config.pusherAuthUrl,
                auth: { headers: authOptions.headers },
                channelAuthorization: authOptions,
            });
            pusher.subscribe(`private-fleet.user.${config.userId}`)
                .bind('fleet-notification', addRealtime);
        } catch (error) {
            console.warn('Pusher notifications could not start. Polling remains active.', error);
        }
    }

    refresh();
    window.setInterval(refresh, Number(config.pollIntervalMs) || 60000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
})();
