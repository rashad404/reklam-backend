<?php

namespace App\Console\Commands;

use App\Models\Advertiser;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Console\Command;

class ProductFixtures extends Command
{
    protected $signature = 'product:fixtures';

    protected $description = 'Create isolated local browser fixtures';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing') || config('database.default') !== 'sqlite' || ! str_contains(config('database.connections.sqlite.database'), 'reklam-preview')) {
            $this->error('Requires an isolated reklam-preview SQLite database.');

            return self::FAILURE;
        }
        $password = env('REKLAM_FIXTURE_PASSWORD');
        if (! $password) {
            $this->error('Set REKLAM_FIXTURE_PASSWORD.');

            return self::FAILURE;
        }
        foreach (['advertiser', 'publisher', 'admin'] as $role) {
            $u = User::firstOrCreate(['email' => $role.'@reklam.test'], ['name' => ucfirst($role).' Fixture', 'password' => $password, 'is_admin' => $role === 'admin']);
            if ($role === 'advertiser') {
                $adv = Advertiser::firstOrCreate(['user_id' => $u->id], ['company_name' => 'Fixture Company', 'balance' => 100]);
                for ($i = 1; $i <= 30; $i++) {
                    $c = $adv->campaigns()->firstOrCreate(['name' => 'Kampaniya '.$i.' - Azərbaycan və Bakı üçün yeni kolleksiya'], ['type' => 'text', 'budget' => 100, 'cpc_bid' => .05, 'status' => $i === 1 ? 'active' : 'draft']);
                    $c->ads()->firstOrCreate(['ad_format' => 'text'], ['title' => 'Eviniz üçün yeni kolleksiya', 'description' => 'Yeni məhsullara baxın.', 'destination_url' => 'https://example.com', 'status' => $i === 1 ? 'approved' : 'pending']);
                }
            }
            if ($role === 'publisher') {
                $p = Publisher::firstOrCreate(['user_id' => $u->id], ['website_url' => 'http://127.0.0.1:8060', 'website_name' => 'Local publisher', 'status' => 'approved', 'verified_at' => now(), 'verification_token' => bin2hex(random_bytes(24))]);
                $p->adUnits()->firstOrCreate(['name' => 'Article text placement'], ['website_url' => $p->website_url, 'ad_format' => 'text', 'status' => 'active']);
            }
        }
        $this->info('Local fixtures ready.');

        return self::SUCCESS;
    }
}
