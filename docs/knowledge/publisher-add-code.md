# Adding Reklam.biz Ads to a Publisher Website

## Overview

To show reklam.biz ads on a website, you need:
1. Create ad units in the reklam.biz database (defines format and which site)
2. Add the embed code to the website's templates
3. The `serve.js` script handles everything else (fetching ads, rendering, tracking)

## Step 1: Create Ad Units in Database

Each ad placement needs an ad unit record. Use tinker on the backend:

```bash
cd backend
php artisan tinker
```

```php
$pub = App\Models\Publisher::first(); // or find the right publisher

// 300x250 banner (sidebar, square ads)
App\Models\AdUnit::create([
    'publisher_id' => $pub->id,
    'name' => 'Right sidebar',
    'ad_format' => 'banner_300x250',
    'website_url' => 'https://site.com',
    'status' => 'active',
]);

// 728x90 leaderboard (wide, inline between content)
App\Models\AdUnit::create([
    'publisher_id' => $pub->id,
    'name' => 'Inline leaderboard',
    'ad_format' => 'banner_728x90',
    'website_url' => 'https://site.com',
    'status' => 'active',
]);

// Text ad (small, fits in sidebars)
App\Models\AdUnit::create([
    'publisher_id' => $pub->id,
    'name' => 'Text ad sidebar',
    'ad_format' => 'text',
    'website_url' => 'https://site.com',
    'status' => 'active',
]);
```

Note the IDs returned - you need them for the embed code.

## Step 2: Add Embed Code to Website

### serve.js Script (load ONCE per page)

Add this once, anywhere on the page. Use `async` so it never blocks the site:

```html
<script async src="https://api.reklam.biz/serve.js"></script>
```

For local development:
```html
<script async src="http://100.89.150.50:8059/serve.js"></script>
```

### Ad Containers (one per placement)

Place these divs where you want ads:

```html
<!-- 300x250 banner -->
<div id="reklam-ad" data-unit="UNIT_ID" data-format="300x250"></div>

<!-- 728x90 leaderboard -->
<div data-reklam data-unit="UNIT_ID" data-format="728x90"></div>

<!-- Text ad -->
<div data-reklam data-unit="UNIT_ID" data-format="text"></div>
```

Replace `UNIT_ID` with the actual ad unit ID from Step 1.

Note: First container uses `id="reklam-ad"`, additional ones use `data-reklam` attribute. Both work.

## Example: How We Did It for football.biz

### Ad Units Created

| ID | Name | Format | Placement |
|----|------|--------|-----------|
| 1 | Right sidebar | banner_300x250 | Right sidebar, below top leagues |
| 2 | Text ad | text | Left sidebar, below countries list |
| 3 | Game list inline | banner_728x90 | Between games, after 10th game |

### Files Modified

**1. Right sidebar - 300x250 banner**

File: `resources/views/desktop/components/sidebar-right.blade.php`

Added at the bottom of the sidebar, after the leagues list:

```html
{{-- Reklam.biz Ads --}}
<div style="margin-top: 12px;">
    <div id="reklam-ad" data-unit="1" data-format="300x250"></div>
</div>
<script async src="https://api.reklam.biz/serve.js"></script>
```

**2. Left sidebar - Text ad**

File: `resources/views/desktop/components/leagues-sidebar.blade.php`

Added after the countries list:

```html
{{-- Reklam.biz Text Ad --}}
<div style="margin-top: 12px; width: 220px; min-width: 220px; max-width: 220px;">
    <div data-reklam data-unit="2" data-format="text" style="font-size:12px;"></div>
</div>
```

Note: Text ads in narrow sidebars need width constraint to match sidebar width (220px for football.biz).

**3. Inline between games - 728x90 leaderboard**

File: `resources/views/desktop/v2/partials/game-list.blade.php`

Added a counter inside the game loop, inserted ad after 10th game:

```php
@php $gameCounter = 0; $adShown = false; @endphp
@foreach($leagues as $leagueGroup)
    @foreach($leagueGroup->games as $game)
        @include('desktop.v2.partials.game-card', ['game' => $game])
        @php $gameCounter++; @endphp
        @if($gameCounter === 10 && !$adShown)
            @php $adShown = true; @endphp
            <div style="padding:6px 0;">
                <div data-reklam data-unit="3" data-format="728x90"></div>
            </div>
        @endif
    @endforeach
@endforeach
```

## Important Notes

### serve.js loads only ONCE
Include the script tag only once per page. It automatically finds ALL `[id="reklam-ad"]` and `[data-reklam]` containers on the page.

### Local vs Production URLs
- Local: `http://100.89.150.50:8059/serve.js`
- Production: `https://api.reklam.biz/serve.js`

On prod, edit blade files to use prod URL. Or better: use an env variable.

### serve.js auto-detects API base
The script figures out the API URL from its own `src`. If loaded from `https://api.reklam.biz/serve.js`, API becomes `https://api.reklam.biz/api`. No hardcoding needed.

### Ad returns relative URLs
The API returns relative paths like `/storage/banners/xxx.png` and `/api/track/click/1`. serve.js resolves them using the same base as the script source. Works on both local and prod automatically.

### Ad badge
Every ad shows a small reklam.biz rocket icon in the bottom-right corner. On hover it expands to "REKLAM.BIZ". Built into serve.js, no extra code needed.

### Async loading
The `async` attribute means reklam.biz never slows down the publisher's site. If reklam.biz is down, the site loads normally and ad spots stay empty.

### Fraud protection
Built into serve.js and backend:
- Signed visitor ID with hidden checksum (forged IDs silently rejected)
- Duplicate click/impression dedup per visitor (2h clicks, 30min impressions)
- Secondary dedup by IP + user agent (catches cleared localStorage)
- Bot detection (blocks curl, headless browsers, crawlers)
- Rate limiting (max 5 clicks/min, 30 impressions/min per IP)
- Referrer validation (must come from publisher's registered domain)

### Deploying to prod
After modifying blade files locally:

```bash
# Push code
cd /Users/macmini/projects/football
git add -A && git commit -m "Add reklam.biz ads" && git push

# Pull on server
ssh -p 21098 root@203.161.35.63
cd /home/lives   # football.biz lives here
chown -R lives:lives resources/
su - lives -c 'cd /home/lives && git pull'
```

## Checklist for Adding Ads to a New Site

1. [ ] Create publisher record (if not exists)
2. [ ] Create ad units in DB with correct format and website_url
3. [ ] Note the ad unit IDs
4. [ ] Find the right template files in the site's codebase
5. [ ] Add `<script async src="https://api.reklam.biz/serve.js"></script>` once
6. [ ] Add `<div>` containers at desired positions with correct unit IDs
7. [ ] Test locally (use local serve.js URL)
8. [ ] Commit and push
9. [ ] Pull on prod server
10. [ ] Replace local serve.js URL with prod URL on server
11. [ ] Verify ads load on prod

## File Locations

- serve.js source: `reklambiz/backend/public/serve.js`
- Ad unit model: `reklambiz/backend/app/Models/AdUnit.php`
- Serve endpoint: `reklambiz/backend/app/Http/Controllers/Api/ServeController.php`
- Football.biz right sidebar: `football/resources/views/desktop/components/sidebar-right.blade.php`
- Football.biz left sidebar: `football/resources/views/desktop/components/leagues-sidebar.blade.php`
- Football.biz game list: `football/resources/views/desktop/v2/partials/game-list.blade.php`
