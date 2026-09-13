<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Production config:cache supplies these values through config, not env().
        config([
            'services.wallet.api_url' => 'https://identity.example/api/',
            'services.wallet.client_id' => 'cached-client-id',
            'services.wallet.client_secret' => 'cached-client-secret',
            'reklam.frontend_url' => 'https://reklam.biz',
        ]);
        Http::preventStrayRequests();
    }

    private function callbackPayload(): array
    {
        return ['code' => 'single-use-code', 'code_verifier' => str_repeat('v', 43), 'redirect_uri' => 'https://reklam.biz/auth/wallet/callback'];
    }

    public function test_cached_credentials_complete_login_and_issue_a_usable_session(): void
    {
        Http::fake([
            'https://identity.example/api/oauth/token' => Http::response(['access_token' => 'provider-token', 'refresh_token' => 'refresh-token']),
            'https://identity.example/api/oauth/user' => Http::response(['data' => ['id' => 'identity-123', 'name' => 'OAuth User', 'email' => 'oauth@example.test']]),
        ]);

        $response = $this->postJson('/api/auth/wallet/callback', $this->callbackPayload())->assertOk();
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('users', ['wallet_id' => 'identity-123', 'email' => 'oauth@example.test']);
        $response->assertJsonMissingPath('data.user.wallet_access_token');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://identity.example/api/oauth/token'
            && $r['client_id'] === 'cached-client-id'
            && $r['client_secret'] === 'cached-client-secret'
            && $r['code_verifier'] === str_repeat('v', 43)
            && $r->hasHeader('Accept', 'application/json'));
        Http::assertSent(fn (Request $r) => $r->url() === 'https://identity.example/api/oauth/user' && $r->hasHeader('Authorization', 'Bearer provider-token'));
        $this->withToken($token)->getJson('/api/auth/user')->assertOk()->assertJsonPath('data.wallet_id', 'identity-123');
    }

    public function test_existing_identity_logs_in_without_creating_another_account(): void
    {
        $user = User::factory()->create(['wallet_id' => 'identity-123']);
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'provider-token']),
            '*/oauth/user' => Http::response(['data' => ['id' => 'identity-123', 'name' => 'Updated name']]),
        ]);
        $this->postJson('/api/auth/wallet/callback', $this->callbackPayload())->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Updated name', $user->fresh()->name);
    }

    public function test_provider_rejection_does_not_create_a_session(): void
    {
        Http::fake(['*/oauth/token' => Http::response(['error' => 'invalid_grant'], 400)]);
        $this->postJson('/api/auth/wallet/callback', $this->callbackPayload())->assertStatus(400);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Http::assertSentCount(1);
    }

    public function test_other_account_email_is_not_automatically_linked(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'provider-token']),
            '*/oauth/user' => Http::response(['data' => ['id' => 'different-identity', 'name' => 'Other user', 'email' => 'existing@example.test']]),
        ]);
        $this->postJson('/api/auth/wallet/callback', $this->callbackPayload())->assertStatus(409);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
