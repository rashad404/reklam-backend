<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\Impression;
use App\Models\Publisher;
use App\Models\User;
use App\Services\SiteVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);

        return $u;
    }

    private function payload(): array
    {
        return ['request_key' => (string) Str::uuid(), 'name' => 'A real campaign', 'type' => 'text', 'budget' => 100, 'cpc_bid' => .1, 'cpm_bid' => null, 'ads' => [['title' => 'A useful title', 'description' => 'Description', 'destination_url' => 'https://example.com/product', 'ad_format' => 'text']]];
    }

    private function inventory(): array
    {
        $user = $this->owner();
        $advertiser = Advertiser::create(['user_id' => $user->id, 'company_name' => 'Test', 'balance' => 100]);
        $publisher = Publisher::create(['user_id' => $user->id, 'website_url' => 'https://publisher.example', 'website_name' => 'Publisher', 'status' => 'approved', 'verified_at' => now()]);
        $campaign = $advertiser->campaigns()->create(['name' => 'Campaign', 'type' => 'text', 'budget' => 100, 'cpc_bid' => .1, 'status' => 'active']);
        $ad = $campaign->ads()->create(['title' => 'Ad', 'destination_url' => 'https://example.com', 'ad_format' => 'text', 'status' => 'approved']);
        $unit = $publisher->adUnits()->create(['name' => 'Unit', 'website_url' => $publisher->website_url, 'ad_format' => 'text', 'status' => 'active']);
        config(['reklam.delivery_enabled' => true]);

        return compact('user', 'advertiser', 'publisher', 'campaign', 'ad', 'unit');
    }

    public function test_atomic_campaign_creation_and_retry(): void
    {
        $u = $this->owner();
        $payload = $this->payload();
        $first = $this->postJson('/api/campaigns', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/campaigns', $payload)->assertCreated()->assertJsonPath('data.id', $first);
        $this->assertDatabaseCount('campaigns', 1);
        $this->assertDatabaseCount('ads', 1);
        $this->assertDatabaseHas('ads', ['status' => 'pending']);
    }

    public function test_invalid_creative_does_not_create_profile_or_campaign(): void
    {
        $this->owner();
        $p = $this->payload();
        $p['ads'][0]['destination_url'] = 'javascript:alert(1)';
        $this->postJson('/api/campaigns', $p)->assertUnprocessable();
        $this->assertDatabaseCount('campaigns', 0);
        $this->assertDatabaseCount('advertisers', 0);
    }

    public function test_edit_is_scoped_and_removal_preserves_history(): void
    {
        $u = $this->owner();
        $p = $this->payload();
        $p['ads'][] = ['ad_format' => 'banner_300x250', 'image_url' => 'https://example.com/banner.png', 'destination_url' => 'https://example.com'];
        $id = $this->postJson('/api/campaigns', $p)->assertCreated()->json('data.id');
        for ($i = 0; $i < 25; $i++) {
            $this->postJson('/api/campaigns', $this->payload())->assertCreated();
        }
        $this->getJson('/api/campaigns/'.$id)->assertOk()->assertJsonCount(2, 'data.ads');
        array_pop($p['ads']);
        $this->putJson('/api/campaigns/'.$id, $p)->assertOk()->assertJsonCount(1, 'data.ads');
        $this->assertSame(1, Ad::onlyTrashed()->where('campaign_id', $id)->count());
        $this->owner();
        $this->getJson('/api/campaigns/'.$id)->assertForbidden();
        $this->putJson('/api/campaigns/'.$id, $p)->assertForbidden();
    }

    public function test_moderation_requires_admin_verified_site_and_reason(): void
    {
        $i = $this->inventory();
        $i['publisher']->update(['status' => 'pending', 'verified_at' => null]);
        $this->patchJson('/api/admin/publishers/'.$i['publisher']->id.'/approve', ['status' => 'approved'])->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);
        $this->patchJson('/api/admin/publishers/'.$i['publisher']->id.'/approve', ['status' => 'approved'])->assertUnprocessable();
        $this->patchJson('/api/admin/publishers/'.$i['publisher']->id.'/approve', ['status' => 'rejected'])->assertUnprocessable();
        $this->patchJson('/api/admin/publishers/'.$i['publisher']->id.'/approve', ['status' => 'rejected', 'reason' => 'Verify ownership first'])->assertOk();
        $this->assertDatabaseHas('moderation_decisions', ['actor_id' => $admin->id, 'status' => 'rejected']);
    }

    public function test_dns_ownership_verification(): void
    {
        $u = $this->owner();
        $this->postJson('/api/publisher/register', ['website_url' => 'https://publisher.example', 'website_name' => 'A site'])->assertCreated();
        $p = Publisher::first();
        $this->mock(SiteVerification::class, fn ($mock) => $mock->shouldReceive('records')->with('_reklam.publisher.example')->andReturn(['reklam-verification='.$p->verification_token]));
        $this->postJson('/api/publisher/verify')->assertOk();
        $this->assertNotNull($p->fresh()->verified_at);
    }

    public function test_delivery_checks_status_and_exact_site_host(): void
    {
        $i = $this->inventory();
        $url = '/api/serve?unit='.$i['unit']->id;
        $this->withHeader('Referer', 'https://publisher.example/story')->getJson($url)->assertOk()->assertJsonPath('ad.id', $i['ad']->id);
        $this->withHeader('Referer', 'https://publisher.example.evil.test')->getJson($url)->assertJsonPath('ad', null);
        $i['publisher']->update(['status' => 'suspended']);
        $this->withHeader('Referer', 'https://publisher.example')->getJson($url)->assertJsonPath('ad', null);
    }

    public function test_impressions_are_token_bound_and_replay_safe(): void
    {
        $i = $this->inventory();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120.0 Desktop', 'Referer' => 'https://publisher.example/story']);
        $token = $this->getJson('/api/serve?unit='.$i['unit']->id)->json('token');
        $this->postJson('/api/track/impression', ['ad_id' => $i['ad']->id, 'unit_id' => $i['unit']->id])->assertOk();
        $this->assertDatabaseCount('impressions', 0);
        $this->postJson('/api/track/impression', ['token' => $token])->assertOk();
        $this->postJson('/api/track/impression', ['token' => $token])->assertOk();
        $this->assertDatabaseCount('impressions', 1);
        $this->postJson('/api/track/viewable', ['token' => $token])->assertOk();
        $this->postJson('/api/track/viewable', ['token' => $token])->assertOk();
        $this->assertSame(1, DB::table('delivery_events')->where('kind', 'viewable')->count());
        $this->travel(16)->minutes();
        $this->postJson('/api/track/impression', ['token' => $token])->assertOk();
        $this->assertDatabaseCount('impressions', 1);
    }

    public function test_clicks_recheck_eligibility_and_deduplicate(): void
    {
        $i = $this->inventory();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120.0 Desktop', 'Referer' => 'https://publisher.example/story']);
        $url = $this->getJson('/api/serve?unit='.$i['unit']->id)->json('ad.click_url');
        $this->get($url)->assertRedirect('https://example.com');
        $this->get($url)->assertRedirect('https://example.com');
        $this->assertDatabaseCount('clicks', 1);
        $url = $this->getJson('/api/serve?unit='.$i['unit']->id)->json('ad.click_url');
        $i['campaign']->update(['status' => 'paused']);
        $this->get($url)->assertRedirect('https://example.com');
        $this->assertDatabaseCount('clicks', 1);
    }

    public function test_reports_bound_dates_and_use_total_ctr(): void
    {
        $i = $this->inventory();
        foreach ([true, false] as $unique) {
            Impression::create(['ad_id' => $i['ad']->id, 'ad_unit_id' => $i['unit']->id, 'campaign_id' => $i['campaign']->id, 'advertiser_id' => $i['advertiser']->id, 'publisher_id' => $i['publisher']->id, 'is_unique' => $unique]);
        }
        $this->getJson('/api/stats/advertiser?from=invalid')->assertUnprocessable();
        $this->getJson('/api/stats/advertiser?from=2020-01-01&to=2026-01-01')->assertUnprocessable();
        $d = now('Asia/Baku')->toDateString();
        $this->getJson('/api/stats/advertiser?from='.$d.'&to='.$d)->assertOk()->assertJsonPath('data.totals.impressions', 2)->assertJsonPath('data.totals.unique_impressions', 1);
    }

    public function test_aggregation_is_idempotent_with_unknown_dimensions(): void
    {
        $i = $this->inventory();
        Impression::create(['ad_id' => $i['ad']->id, 'ad_unit_id' => $i['unit']->id, 'campaign_id' => $i['campaign']->id, 'advertiser_id' => $i['advertiser']->id, 'publisher_id' => $i['publisher']->id, 'is_unique' => true]);
        $this->artisan('stats:aggregate')->assertSuccessful();
        $this->artisan('stats:aggregate')->assertSuccessful();
        $this->assertDatabaseCount('daily_stats', 1);
        $this->assertDatabaseHas('daily_stats', ['impressions' => 1, 'country' => 'ZZ', 'device_type' => 'unknown']);
    }

    public function test_site_scoped_units_and_archive(): void
    {
        $i = $this->inventory();
        $this->postJson('/api/ad-units', ['name' => 'Other', 'ad_format' => 'text', 'website_url' => 'https://evil.example'])->assertUnprocessable();
        $this->deleteJson('/api/ad-units/'.$i['unit']->id)->assertOk();
        $this->assertSoftDeleted('ad_units', ['id' => $i['unit']->id]);
    }

    public function test_oauth_redirect_allowlist(): void
    {
        $this->postJson('/api/auth/wallet/callback', ['code' => 'x', 'code_verifier' => 'y', 'redirect_uri' => 'https://evil.example'])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_health_detects_stale_aggregation(): void
    {
        Cache::forget('stats:last_aggregate');
        $this->artisan('product:health')->assertFailed();
        Cache::put('stats:last_aggregate', now()->toIso8601String());
        $this->artisan('product:health')->assertSuccessful();
        Cache::put('stats:last_aggregate', now()->subHours(3)->toIso8601String());
        $this->artisan('product:health')->assertFailed();
    }

    public function test_retention_preserves_counts_and_recent_identifiers(): void
    {
        $i = $this->inventory();
        $fields = ['ad_id' => $i['ad']->id, 'ad_unit_id' => $i['unit']->id, 'campaign_id' => $i['campaign']->id, 'advertiser_id' => $i['advertiser']->id, 'publisher_id' => $i['publisher']->id, 'is_unique' => true, 'user_agent' => 'Test browser'];
        $old = Impression::create($fields + ['ip' => null, 'created_at' => now()->subDays(100)]);
        $recent = Impression::create($fields + ['ip' => '192.0.2.1']);
        DB::table('impressions')->where('id', $old->id)->update(['created_at' => now()->subDays(100)]);
        $this->artisan('traffic:retain')->assertSuccessful();
        $this->assertDatabaseHas('impressions', ['id' => $old->id, 'user_agent' => 'Test browser']);
        $this->artisan('traffic:retain --apply')->assertSuccessful();
        $this->assertDatabaseCount('impressions', 2);
        $this->assertDatabaseHas('impressions', ['id' => $old->id, 'user_agent' => null, 'is_unique' => true]);
        $this->assertDatabaseHas('impressions', ['id' => $recent->id, 'ip' => '192.0.2.1']);
    }

    public function test_campaign_can_set_only_an_end_date(): void
    {
        $this->owner();
        $payload = $this->payload();
        $payload['end_date'] = now()->addWeek()->toDateString();
        $this->postJson('/api/campaigns', $payload)->assertCreated();
        $payload['request_key'] = (string) Str::uuid();
        $payload['start_date'] = now()->addWeeks(2)->toDateString();
        $this->postJson('/api/campaigns', $payload)->assertUnprocessable();
    }
}
