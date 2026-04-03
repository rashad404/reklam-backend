<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class BannerAdSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'rashad.mirza.404@gmail.com')->first();

        if (!$user) {
            $this->command->error('User rashad.mirza.404@gmail.com not found');
            return;
        }

        $advertiser = $user->advertiser;
        if (!$advertiser) {
            $this->command->error('User has no advertiser profile');
            return;
        }

        $token = $user->createToken('seeder')->plainTextToken;
        $apiBase = 'http://127.0.0.1:8059/api';

        $bannerAds = [
            [
                'campaign' => 'Bugun.az - Şəkilli Reklam',
                'title' => 'Bugun.az',
                'description' => 'Son xəbərlər hər gün, hər an',
                'color' => '#1E40AF',
                'domain' => 'bugun.az',
                'destination' => 'https://bugun.az',
                'template' => 'news',
                'budget' => 500,
                'cpm_bid' => 2.00,
            ],
            [
                'campaign' => 'LiveScore.az - Şəkilli Reklam',
                'title' => 'LiveScore.az',
                'description' => 'Canlı futbol nəticələri',
                'color' => '#16A34A',
                'domain' => 'livescore.az',
                'destination' => 'https://livescore.az',
                'template' => 'sports',
                'budget' => 400,
                'cpm_bid' => 1.50,
            ],
            [
                'campaign' => 'Sayt.az - Şəkilli Reklam',
                'title' => 'Sayt.az',
                'description' => 'Peşəkar veb sayt sifariş et',
                'color' => '#7C3AED',
                'domain' => 'sayt.az',
                'destination' => 'https://sayt.az',
                'template' => 'webdev',
                'budget' => 600,
                'cpm_bid' => 2.50,
            ],
            [
                'campaign' => 'UREB - Şəkilli Reklam',
                'title' => 'Öz saytını yarat!',
                'description' => 'Proqramçısız, 5 dəqiqədə',
                'color' => '#0891B2',
                'domain' => 'ureb.com',
                'destination' => 'https://ureb.com',
                'template' => 'ai',
                'budget' => 500,
                'cpm_bid' => 2.00,
            ],
            [
                'campaign' => 'Flip.az - Şəkilli Reklam',
                'title' => 'Flip.az',
                'description' => 'Hazır biznes al, biznesini sat!',
                'color' => '#DC2626',
                'domain' => 'flip.az',
                'destination' => 'https://flip.az',
                'template' => 'marketplace',
                'budget' => 450,
                'cpm_bid' => 1.80,
            ],
            [
                'campaign' => 'Kredit.az - Şəkilli Reklam',
                'title' => 'Kredit.az',
                'description' => 'Ən sərfəli kredit faizləri',
                'color' => '#D97706',
                'domain' => 'kredit.az',
                'destination' => 'https://kredit.az',
                'template' => 'finance',
                'budget' => 700,
                'cpm_bid' => 3.00,
            ],
        ];

        $sizes = ['300x250', '728x90', '320x50'];

        foreach ($bannerAds as $adData) {
            $campaign = Campaign::create([
                'advertiser_id' => $advertiser->id,
                'name' => $adData['campaign'],
                'type' => 'display',
                'budget' => $adData['budget'],
                'cpm_bid' => $adData['cpm_bid'],
                'status' => 'active',
            ]);

            foreach ($sizes as $size) {
                // Generate banner
                $response = Http::withToken($token)->timeout(30)->post("{$apiBase}/generate/banner", [
                    'title' => $adData['title'],
                    'description' => $adData['description'],
                    'color' => $adData['color'],
                    'size' => $size,
                    'template' => $adData['template'] ?? 'default',
                    'domain' => $adData['domain'],
                ]);

                $imageUrl = $response->json('data.url', '');
                $format = 'banner_' . $size;

                Ad::create([
                    'campaign_id' => $campaign->id,
                    'title' => $adData['title'] . ' - ' . $adData['description'],
                    'description' => $adData['description'],
                    'image_url' => $imageUrl,
                    'destination_url' => $adData['destination'],
                    'ad_format' => $format,
                    'status' => 'approved',
                ]);

                $this->command->info("  {$size}: {$imageUrl}");
            }

            $this->command->info("Created: {$adData['campaign']} (3 sizes)");
        }

        $this->command->info('');
        $this->command->info('Total: ' . count($bannerAds) . ' campaigns, ' . (count($bannerAds) * 3) . ' banner ads');

        // Clean up seeder token
        $user->tokens()->where('name', 'seeder')->delete();
    }
}
