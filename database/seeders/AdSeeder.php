<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'rashad.mirza.404@gmail.com')->first();

        if (!$user) {
            $this->command->error('User rashad.mirza.404@gmail.com not found');
            return;
        }

        // Ensure advertiser exists
        $advertiser = $user->advertiser;
        if (!$advertiser) {
            $advertiser = Advertiser::create([
                'user_id' => $user->id,
                'company_name' => $user->name,
                'balance' => 1000,
            ]);
        }

        $ads = [
            // bugun.az - News portal
            [
                'campaign' => [
                    'name' => 'Bugun.az - Xəbər Portalı',
                    'type' => 'display',
                    'budget' => 500,
                    'cpc_bid' => 0.15,
                    'status' => 'active',
                ],
                'ads' => [
                    [
                        'title' => 'Bugun.az - Son Xəbərlər',
                        'description' => 'Azərbaycanın ən sürətli xəbər portalı. Hər gün yüzlərlə xəbər.',
                        'destination_url' => 'https://bugun.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                    [
                        'title' => 'Xəbərləri ilk sən oxu!',
                        'description' => 'Son xəbərlər, namaz vaxtı, valyuta məzənnəsi - hamısı bir yerdə.',
                        'destination_url' => 'https://bugun.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                ],
            ],

            // livescore.az - Live football scores
            [
                'campaign' => [
                    'name' => 'LiveScore.az - Canlı Nəticələr',
                    'type' => 'display',
                    'budget' => 400,
                    'cpc_bid' => 0.12,
                    'status' => 'active',
                ],
                'ads' => [
                    [
                        'title' => 'Canlı futbol nəticələri',
                        'description' => 'Bütün liqalar, bütün oyunlar - canlı izlə! LiveScore.az',
                        'destination_url' => 'https://livescore.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                    [
                        'title' => 'LiveScore.az - Hər qol anında!',
                        'description' => 'Premyer Liqa, La Liga, Serie A - canlı hesablar və statistika.',
                        'destination_url' => 'https://livescore.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                ],
            ],

            // sayt.az - Website development
            [
                'campaign' => [
                    'name' => 'Sayt.az - Veb Sayt Sifarişi',
                    'type' => 'display',
                    'budget' => 600,
                    'cpc_bid' => 0.20,
                    'status' => 'active',
                ],
                'ads' => [
                    [
                        'title' => 'Peşəkar veb sayt sifariş et',
                        'description' => 'Domain, hosting və hazır sayt - hər şey bir yerdə. Sayt.az',
                        'destination_url' => 'https://sayt.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                    [
                        'title' => 'Biznesinizə veb sayt lazımdır?',
                        'description' => 'Sayt.az ilə peşəkar saytınızı bu gün sifariş edin.',
                        'destination_url' => 'https://sayt.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                ],
            ],

            // ureb.com - AI website builder
            [
                'campaign' => [
                    'name' => 'UREB - Öz Saytını Yarat',
                    'type' => 'display',
                    'budget' => 500,
                    'cpc_bid' => 0.18,
                    'status' => 'active',
                ],
                'ads' => [
                    [
                        'title' => 'Öz saytını özün yarat!',
                        'description' => 'Proqramçı olmadan, 5 dəqiqədə peşəkar sayt. UREB.com',
                        'destination_url' => 'https://ureb.com',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                    [
                        'title' => 'UREB - Sayt yaratmaq daha asan',
                        'description' => 'Süni intellekt ilə saytını yarat. Pulsuz başla!',
                        'destination_url' => 'https://ureb.com',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                ],
            ],

            // flip.az - Business marketplace
            [
                'campaign' => [
                    'name' => 'Flip.az - Biznes Al-Sat',
                    'type' => 'display',
                    'budget' => 450,
                    'cpc_bid' => 0.14,
                    'status' => 'active',
                ],
                'ads' => [
                    [
                        'title' => 'Hazır biznes almaq istəyirsən?',
                        'description' => 'Veb saytlar, mobil tətbiqlər, domainlər - Flip.az-da tap!',
                        'destination_url' => 'https://flip.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                    [
                        'title' => 'Biznesini sat, yenisini al!',
                        'description' => 'Azərbaycanın ilk biznes alqı-satqı platforması. Flip.az',
                        'destination_url' => 'https://flip.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                ],
            ],

            // kredit.az - Financial portal
            [
                'campaign' => [
                    'name' => 'Kredit.az - Maliyyə Portalı',
                    'type' => 'display',
                    'budget' => 700,
                    'cpc_bid' => 0.25,
                    'status' => 'active',
                ],
                'ads' => [
                    [
                        'title' => 'Ən sərfəli kredit harada?',
                        'description' => 'Bütün bankların kredit faizlərini müqayisə et. Kredit.az',
                        'destination_url' => 'https://kredit.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                    [
                        'title' => 'Kredit götürmək istəyirsən?',
                        'description' => 'Faiz dərəcələri, şərtlər, kalkulyator - hamısı Kredit.az-da.',
                        'destination_url' => 'https://kredit.az',
                        'ad_format' => 'text',
                        'status' => 'approved',
                    ],
                ],
            ],
        ];

        foreach ($ads as $adGroup) {
            $campaign = Campaign::create(array_merge(
                $adGroup['campaign'],
                ['advertiser_id' => $advertiser->id]
            ));

            foreach ($adGroup['ads'] as $adData) {
                Ad::create(array_merge(
                    $adData,
                    ['campaign_id' => $campaign->id]
                ));
            }

            $this->command->info("Created campaign: {$campaign->name} with " . count($adGroup['ads']) . " ads");
        }

        $this->command->info('');
        $this->command->info("Total: " . count($ads) . " campaigns, " . array_sum(array_map(fn($a) => count($a['ads']), $ads)) . " ads");
    }
}
