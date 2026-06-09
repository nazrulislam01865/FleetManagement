<?php

namespace App\Http\Controllers\Fleet;

use App\Services\PusherChannelsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationController extends FleetBaseController
{
    protected string $activeMenu = '';
    protected string $view = 'fleetman.notifications.index';
    protected string $page = 'notifications';

    public function index(): View
    {
        /** @var Request $request */
        $request = request();
        $notifications = Schema::hasTable('notifications')
            ? $request->user()->notifications()->paginate(20)
            : collect();

        return view($this->view, array_merge($this->shared('', [
            'page' => 'notifications',
        ]), [
            'notifications' => $notifications,
        ]));
    }

    public function feed(Request $request): JsonResponse
    {
        if (! Schema::hasTable('notifications')) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = $request->user()->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->serialize($notification))
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return response()->json([
            'ok' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    public function pusherAuth(Request $request, PusherChannelsService $pusher): JsonResponse
    {
        $validated = $request->validate([
            'socket_id' => ['required', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string', 'max:200'],
        ]);

        $expectedChannel = 'private-fleet.user.'.(int) $request->user()->id;
        abort_unless(hash_equals($expectedChannel, $validated['channel_name']), 403);
        abort_unless($pusher->enabled(), 503, 'Pusher is not configured.');

        return response()->json($pusher->authenticate($validated['socket_id'], $validated['channel_name']));
    }

    private function serialize(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'data' => $notification->data,
            'read_at' => optional($notification->read_at)->toIso8601String(),
            'created_at' => optional($notification->created_at)->toIso8601String(),
            'created_at_human' => optional($notification->created_at)->diffForHumans(),
        ];
    }
}
