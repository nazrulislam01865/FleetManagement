@extends('layouts.fleetman')

@section('title', 'Notifications - '.($brand['name'] ?? 'FleetMan'))
@section('mobile-title', 'Notifications')

@section('content')
<div class="page-section notification-center-page">
    <x-fleetman.topbar :items="[['label' => 'Notifications']]" />

    <x-fleetman.title-card
        title="Notification Center"
        subtitle="Your reminders and system activity notifications are stored here even when real-time delivery is unavailable."
    >
        <x-slot:action>
            <form method="POST" action="{{ route('fleet.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn secondary">Mark All as Read</button>
            </form>
        </x-slot:action>
    </x-fleetman.title-card>

    @if(session('status'))
        <div class="fleet-notification-page-alert">{{ session('status') }}</div>
    @endif

    <section class="card">
        <div class="section-head">
            <div>
                <h2>All Notifications</h2>
                <p>Reminders are delivered to the responsible user and administrators. Activity notifications are delivered to Admin and Super Admin accounts.</p>
            </div>
        </div>

        <div class="fleet-notification-page-list">
            @forelse($notifications as $notification)
                @php($data = is_array($notification->data) ? $notification->data : [])
                <article class="fleet-notification-page-item {{ $notification->read_at ? '' : 'unread' }}">
                    <div class="fleet-notification-page-icon">{{ $data['icon'] ?? '🔔' }}</div>
                    <div class="fleet-notification-page-copy">
                        <div class="fleet-notification-page-title-row">
                            <strong>{{ $data['title'] ?? 'FleetMan Notification' }}</strong>
                            @unless($notification->read_at)<span class="badge soft">New</span>@endunless
                        </div>
                        <p>{{ $data['message'] ?? '' }}</p>
                        <small>{{ optional($notification->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</small>
                    </div>
                    <div class="fleet-notification-page-actions">
                        @if(! empty($data['url']))
                            <a class="mini-btn" href="{{ $data['url'] }}">Open</a>
                        @endif
                        @unless($notification->read_at)
                            <button
                                type="button"
                                class="mini-btn fleet-page-mark-read"
                                data-notification-id="{{ $notification->id }}"
                            >Mark Read</button>
                        @endunless
                    </div>
                </article>
            @empty
                <div class="empty">No notifications yet.</div>
            @endforelse
        </div>

        @if(method_exists($notifications, 'links'))
            <div class="fleet-notification-pagination">{{ $notifications->links() }}</div>
        @endif
    </section>
</div>
@endsection
