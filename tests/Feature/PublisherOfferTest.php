<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdUnit;
use App\Models\Advertiser;
use App\Models\Campaign;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublisherOfferTest extends TestCase
{
    use RefreshDatabase;

    private function publisher(): Publisher
    {
        return Publisher::create(['user_id' => User::factory()->create()->id, 'website_url' => 'https://publisher.example', 'website_name' => 'A publisher', 'status' => 'pending']);
    }

    public function test_first_approval_starts_six_calendar_months_and_reapproval_does_not_restart_it(): void
    {
        config(['reklam.platform_commission' => 0.30]);
        $this->travelTo(Carbon::parse('2026-08-31 12:00:00', 'UTC'));
        $publisher = $this->publisher();
        $this->assertNull($publisher->commission_free_until);
        $publisher->update(['status' => 'approved']);
        $this->assertSame('2027-02-28 12:00:00', $publisher->commission_free_until->format('Y-m-d H:i:s'));
        $this->assertSame(0.0, $publisher->platformCommissionRate());
        $this->travel(1)->month();
        $publisher->update(['status' => 'suspended']);
        $publisher->update(['status' => 'approved']);
        $this->assertSame('2027-02-28 12:00:00', $publisher->fresh()->commission_free_until->format('Y-m-d H:i:s'));
        $this->travelTo(Carbon::parse('2027-02-28 12:00:00', 'UTC'));
        $this->assertSame(0.30, $publisher->fresh()->platformCommissionRate());
        $this->assertFalse($publisher->fresh()->commission_offer['active']);
        $this->travelBack();
    }

    public function test_cpc_credits_the_entire_ad_charge_during_the_offer_and_deduplicates(): void
    {
        [$publisher, $advertiser, $campaign, $ad, $unit] = $this->inventory('cpc');
        $url = $this->getJson('/api/serve?unit='.$unit->id)->json('ad.click_url');
        $this->get($url)->assertRedirect('https://example.com');
        $this->get($url)->assertRedirect('https://example.com');
        $this->assertEquals(1.00, $publisher->fresh()->balance);
        $this->assertEquals(1.00, $publisher->fresh()->total_earned);
        $this->assertEquals(99.00, $advertiser->fresh()->balance);
        $this->assertEquals(1.00, $campaign->fresh()->spent);
    }

    public function test_cpm_uses_the_same_zero_commission_rule(): void
    {
        [$publisher, $advertiser, $campaign, $ad, $unit] = $this->inventory('cpm');
        $token = $this->getJson('/api/serve?unit='.$unit->id)->json('token');
        $this->postJson('/api/track/impression', ['token' => $token])->assertOk();
        $this->postJson('/api/track/impression', ['token' => $token])->assertOk();
        $this->assertEquals(0.01, $publisher->fresh()->balance);
        $this->assertEquals(0.01, $campaign->fresh()->spent);
    }

    public function test_expired_offer_uses_cached_standard_commission(): void
    {
        [$publisher, $advertiser, $campaign, $ad, $unit] = $this->inventory('cpc');
        $publisher->commission_free_started_at = now()->subMonths(7);
        $publisher->commission_free_until = now()->subMonth();
        $publisher->save();
        config(['reklam.platform_commission' => 0.25]);
        $url = $this->getJson('/api/serve?unit='.$unit->id)->json('ad.click_url');
        $this->get($url)->assertRedirect('https://example.com');
        $this->assertEquals(0.75, $publisher->fresh()->balance);
    }

    private function inventory(string $type): array
    {
        config(['reklam.delivery_enabled' => true, 'reklam.platform_commission' => 0.30]);
        $publisher = $this->publisher();
        $publisher->update(['status' => 'approved', 'verified_at' => now()]);
        $advertiser = Advertiser::create(['user_id' => User::factory()->create()->id, 'company_name' => 'Offer test', 'balance' => 100, 'status' => 'active']);
        $campaign = Campaign::create(['advertiser_id' => $advertiser->id, 'name' => 'Offer test', 'type' => 'text', 'status' => 'active', 'budget' => 100, 'cpc_bid' => $type === 'cpc' ? 1 : null, 'cpm_bid' => $type === 'cpm' ? 10 : null]);
        $ad = Ad::create(['campaign_id' => $campaign->id, 'title' => 'An offer', 'description' => 'Details', 'destination_url' => 'https://example.com', 'ad_format' => 'text', 'status' => 'approved']);
        $unit = AdUnit::create(['publisher_id' => $publisher->id, 'name' => 'Placement', 'website_url' => $publisher->website_url, 'ad_format' => 'text', 'status' => 'active']);
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120.0 Desktop', 'Referer' => 'https://publisher.example/story']);

        return [$publisher, $advertiser, $campaign, $ad, $unit];
    }
}
