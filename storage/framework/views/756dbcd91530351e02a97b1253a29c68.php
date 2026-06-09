<div class="fleet-notification-widget" id="fleetNotificationWidget">
    <button
        type="button"
        class="fleet-notification-bell"
        id="fleetNotificationBell"
        aria-label="Open notifications"
        aria-expanded="false"
        aria-controls="fleetNotificationPanel"
    >
        <span aria-hidden="true">🔔</span>
        <span class="fleet-notification-count hidden" id="fleetNotificationCount">0</span>
    </button>

    <section class="fleet-notification-panel hidden" id="fleetNotificationPanel" aria-label="Notifications">
        <header class="fleet-notification-panel-head">
            <div>
                <strong>Notifications</strong>
                <small id="fleetNotificationStatus">Loading…</small>
            </div>
            <button type="button" class="fleet-notification-text-btn" id="fleetMarkAllRead">Mark all read</button>
        </header>

        <div class="fleet-notification-list" id="fleetNotificationList">
            <div class="fleet-notification-empty">Loading notifications…</div>
        </div>

        <footer class="fleet-notification-panel-foot">
            <a href="<?php echo e(route('fleet.notifications.index')); ?>">View all notifications</a>
        </footer>
    </section>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/notification-bell.blade.php ENDPATH**/ ?>