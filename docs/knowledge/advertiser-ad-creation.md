# Creating Ads for Reklam.biz

## Overview

There are two types of ads: **text ads** and **banner (image) ads**. Each brand gets both types across multiple campaigns. Banner images are auto-generated using Puppeteer (Chrome headless) with unique HTML/CSS templates per brand.

## Ad Types

### Text Ads
- Title + description + destination URL
- No image needed
- Served on `text` format ad units
- Created via `AdSeeder`

### Banner Ads (Image)
- Auto-generated PNG images using `scripts/generate-banner.cjs`
- 3 sizes per brand: `300x250`, `728x90`, `320x50`
- Each brand has a unique visual template (colors, decorative elements)
- Created via `BannerAdSeeder` which calls the `/api/generate/banner` endpoint

## Brand Templates

Each brand has a unique template in `scripts/generate-banner.cjs`:

| Brand | Template | Colors | Style |
|-------|----------|--------|-------|
| bugun.az | `news` | Dark navy (#0F172A) | Red/orange gradient accent line at top |
| livescore.az | `sports` | Green (#064E3B) | Football field circle element, soccer icon |
| sayt.az | `webdev` | Purple (#2E1065) | Faded code snippets in background |
| ureb.com | `ai` | Cyan (#0C4A6E) | Glowing orb effect |
| flip.az | `marketplace` | Red (#7F1D1D) | Yellow "AL-SAT" tag |
| kredit.az | `finance` | Dark/gold (#1C1917) | Gold accent line, gold title text |

## How to Create Ads for a New Brand

### Step 1: Research the brand
- Visit the website, understand what they do
- Pick a brand color
- Write title (short, bold) and description (one line) in Azerbaijani

### Step 2: Add text ads (AdSeeder)
Edit `database/seeders/AdSeeder.php`, add a new entry:

```php
[
    'campaign' => [
        'name' => 'BrandName - Campaign Name',
        'type' => 'display',
        'budget' => 500,
        'cpc_bid' => 0.15,
        'status' => 'active',
    ],
    'ads' => [
        [
            'title' => 'Short catchy title',
            'description' => 'One line description in Azerbaijani',
            'destination_url' => 'https://brand.az',
            'ad_format' => 'text',
            'status' => 'approved',
        ],
        // Add 2nd variation with different text
    ],
],
```

### Step 3: Add banner ads (BannerAdSeeder)

1. If the brand needs a unique look, add a new template in `scripts/generate-banner.cjs`:

```javascript
brandname: {
    bg: `linear-gradient(145deg, #COLOR1 0%, #COLOR2 100%)`,
    extra: `
      .title { color:#fff; }
      .desc { color:rgba(255,255,255,0.75); }
      .domain-badge { background:...; color:...; padding:6px 16px; border-radius:...; font-weight:700; }
    `,
    extraHtml: `<!-- optional decorative HTML elements -->`,
},
```

2. Add entry in `database/seeders/BannerAdSeeder.php`:

```php
[
    'campaign' => 'BrandName - Sekilli Reklam',
    'title' => 'BrandName',
    'description' => 'Short tagline in Azerbaijani',
    'color' => '#HEXCOLOR',
    'domain' => 'brand.az',
    'destination' => 'https://brand.az',
    'template' => 'brandname',  // must match template key in generate-banner.cjs
    'budget' => 500,
    'cpm_bid' => 2.00,
],
```

### Step 4: Run seeders

```bash
# On local (backend must be running on port 8059 for banner generation)
cd backend
php artisan db:seed --class=AdSeeder
php artisan db:seed --class=BannerAdSeeder

# Fix image URLs if needed (local uses 127.0.0.1, need Tailscale IP)
php artisan tinker --execute="
App\Models\Ad::where('image_url', 'like', '%127.0.0.1%')->update([
    'image_url' => DB::raw(\"REPLACE(image_url, '127.0.0.1', '100.89.150.50')\")
]);
"
```

### Step 5: Deploy to production

On prod, Puppeteer/Chrome is not available. So:

1. Run seeders locally first (generates images locally)
2. Upload banner images to prod:
```bash
scp -P 21098 backend/storage/app/public/banners/*.png root@203.161.35.63:/home/ugn/api.reklam.biz/storage/app/public/banners/
ssh -p 21098 root@203.161.35.63 "chown -R ugn:nobody /home/ugn/api.reklam.biz/storage/app/public/banners/"
```
3. Run text ad seeder on prod:
```bash
ssh -p 21098 root@203.161.35.63
cd /home/ugn/api.reklam.biz
/opt/cpanel/ea-php83/root/usr/bin/php artisan db:seed --class=AdSeeder --force
```
4. Insert banner ad records on prod via tinker using prod image URLs (`https://api.reklam.biz/storage/banners/FILENAME.png`)

## Banner Generator API

```
POST /api/generate/banner (requires auth token)

Body:
{
    "title": "Brand Name",
    "description": "Short tagline",
    "color": "#1E40AF",
    "size": "300x250",      // 300x250, 728x90, or 320x50
    "domain": "brand.az",
    "template": "news"       // news, sports, webdev, ai, marketplace, finance, default
}

Response:
{
    "status": "success",
    "data": {
        "url": "http://host/storage/banners/HASH.png",
        "size": "300x250"
    }
}
```

## Ad Pricing

- Text ads: CPC (cost per click), typical bid 0.10-0.25 AZN
- Banner ads: CPM (cost per 1000 impressions), typical bid 1.50-3.00 AZN
- Higher bid = shown more often (weighted random selection)
- Platform takes 30%, publisher gets 70%

## Ad Serving Priority

Ads are selected using weighted random based on bid amount:
- kredit.az at 3.00 AZN CPM shows most often
- livescore.az at 1.50 AZN CPM shows least often
- But all ads get some impressions (never 0%)

## File Locations

- Text ad seeder: `backend/database/seeders/AdSeeder.php`
- Banner ad seeder: `backend/database/seeders/BannerAdSeeder.php`
- Banner generator script: `backend/scripts/generate-banner.cjs`
- Banner generator API: `backend/app/Http/Controllers/Api/BannerGeneratorController.php`
- Generated images: `backend/storage/app/public/banners/`
- Fraud protection: `backend/app/Http/Controllers/Api/ServeController.php`
- serve.js (embed script): `backend/public/serve.js`
