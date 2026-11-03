<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.facebook.app_id' => '111', 'services.facebook.app_secret' => 'sec']);
    }

    public function test_facebook_login_creates_and_reuses_the_user(): void
    {
        Http::fake([
            'graph.facebook.com/debug_token*' => Http::response(['data' => ['is_valid' => true, 'app_id' => '111', 'user_id' => '999']]),
            'graph.facebook.com/me*' => Http::response(['id' => '999', 'name' => 'Fb User', 'email' => 'fb@example.com', 'picture' => ['data' => ['url' => 'https://x/y.jpg']]]),
        ]);
        $r = $this->postJson('/api/v1/auth/social-login', ['provider' => 'facebook', 'token' => 'abc'])->assertOk();
        $this->assertSame('fb@example.com', $r->json('data.user.email'));
        $this->assertNotEmpty($r->json('data.token'));
        $this->assertSame('999', User::where('email', 'fb@example.com')->value('facebook_id'));
        $this->postJson('/api/v1/auth/social-login', ['provider' => 'facebook', 'token' => 'abc'])->assertOk();
        $this->assertSame(1, User::where('facebook_id', '999')->count());
    }

    public function test_facebook_token_for_another_app_is_rejected(): void
    {
        Http::fake(['graph.facebook.com/debug_token*' => Http::response(['data' => ['is_valid' => true, 'app_id' => '222', 'user_id' => '999']])]);
        $this->postJson('/api/v1/auth/social-login', ['provider' => 'facebook', 'token' => 'abc'])->assertStatus(401);
    }

    public function test_facebook_login_not_configured_gives_501(): void
    {
        config(['services.facebook.app_id' => null, 'services.facebook.app_secret' => null]);
        $this->postJson('/api/v1/auth/social-login', ['provider' => 'facebook', 'token' => 'abc'])->assertStatus(501);
    }
}
