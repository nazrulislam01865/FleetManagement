<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleActiveLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_login_replaces_the_previous_active_session(): void
    {
        $user = User::factory()->create([
            'email' => 'single-login@example.com',
            'password' => 'password',
            'active_session_id' => 'previous-session-id',
        ]);

        DB::table('sessions')->insert([
            'id' => 'previous-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            'login_notice',
            'Login successful. The previous device was logged out because only one active login is allowed per user.'
        );

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('sessions', ['id' => 'previous-session-id']);
        $this->assertNotSame('previous-session-id', $user->fresh()->active_session_id);
        $this->assertNotEmpty($user->fresh()->active_session_id);
    }

    public function test_replaced_session_is_logged_out_on_its_next_request(): void
    {
        $user = User::factory()->create([
            'active_session_id' => 'newer-session-id',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('session.keep-alive'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas(
            'status',
            'You were logged out because this account was signed in on another device or browser. Only one active login is allowed per user. If this was not you, change your password immediately.'
        );
        $this->assertGuest();
        $this->assertSame('newer-session-id', $user->fresh()->active_session_id);
    }

    public function test_existing_session_without_marker_is_claimed_without_forced_logout(): void
    {
        $user = User::factory()->create([
            'active_session_id' => null,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('session.keep-alive'));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($user->fresh()->active_session_id);
    }
}
