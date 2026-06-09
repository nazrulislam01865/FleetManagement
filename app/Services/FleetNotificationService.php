<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FleetNotificationService
{
    public function __construct(private readonly PusherChannelsService $pusher)
    {
    }

    public function adminUsers(): Collection
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('fleet_roles')) {
            return collect();
        }

        return User::query()
            ->whereHas('fleetRole', fn ($query) => $query
                ->whereIn('slug', ['super_admin', 'admin_user'])
                ->where('is_active', true))
            ->when(
                Schema::hasColumn('users', 'account_status'),
                fn ($query) => $query->where(function ($statusQuery) {
                    $statusQuery->where('account_status', User::ACCOUNT_STATUS_ACTIVE)
                        ->orWhereNull('account_status');
                })
            )
            ->get();
    }

    public function notifyAdmins(array $data, ?string $dedupeKey = null): int
    {
        return $this->notifyUsers($this->adminUsers(), $data, $dedupeKey);
    }

    public function notifyUserIdsAndAdmins(array $userIds, array $data, ?string $dedupeKey = null): int
    {
        $users = User::query()
            ->whereIn('id', collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->all())
            ->get()
            ->merge($this->adminUsers())
            ->unique('id')
            ->values();

        return $this->notifyUsers($users, $data, $dedupeKey);
    }

    public function notifyUsers(iterable $users, array $data, ?string $dedupeKey = null): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $sent = 0;
        $normalized = $this->normalizeData($data);

        foreach (collect($users)->filter()->unique('id') as $user) {
            if (! $user instanceof User || ! $user->isAccountActive()) {
                continue;
            }

            $userDedupeKey = $dedupeKey !== null ? $dedupeKey : null;
            if ($userDedupeKey !== null && $this->alreadyDelivered((int) $user->id, $userDedupeKey)) {
                continue;
            }

            $notificationId = (string) Str::uuid();
            $now = now();

            DB::transaction(function () use ($user, $normalized, $notificationId, $now, $userDedupeKey): void {
                DB::table('notifications')->insert([
                    'id' => $notificationId,
                    'type' => 'fleet.system',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($userDedupeKey !== null && Schema::hasTable('fleet_notification_deliveries')) {
                    DB::table('fleet_notification_deliveries')->insert([
                        'user_id' => $user->id,
                        'dedupe_key' => $userDedupeKey,
                        'notification_id' => $notificationId,
                        'delivered_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

            $this->pusher->triggerUser((int) $user->id, 'fleet-notification', [
                'id' => $notificationId,
                'data' => $normalized,
                'read_at' => null,
                'created_at' => $now->toIso8601String(),
            ]);

            $sent++;
        }

        return $sent;
    }

    private function alreadyDelivered(int $userId, string $dedupeKey): bool
    {
        return Schema::hasTable('fleet_notification_deliveries')
            && DB::table('fleet_notification_deliveries')
                ->where('user_id', $userId)
                ->where('dedupe_key', $dedupeKey)
                ->exists();
    }

    private function normalizeData(array $data): array
    {
        return [
            'title' => trim((string) ($data['title'] ?? 'FleetMan Notification')),
            'message' => trim((string) ($data['message'] ?? '')),
            'category' => trim((string) ($data['category'] ?? 'system')),
            'icon' => trim((string) ($data['icon'] ?? '🔔')),
            'url' => trim((string) ($data['url'] ?? '')),
            'actor_name' => trim((string) ($data['actor_name'] ?? '')),
            'resource' => trim((string) ($data['resource'] ?? '')),
            'resource_code' => trim((string) ($data['resource_code'] ?? '')),
            'occurred_at' => (string) ($data['occurred_at'] ?? now()->toIso8601String()),
        ];
    }
}
