# Reklam.biz API

Laravel 13 / Sanctum. PHP 8.4 is the release baseline. MySQL in production; isolated SQLite for tests. Read docs/knowledge/product-standard.md and docs/knowledge/runbook.md.

`composer install`, configure `.env`, `php artisan key:generate`, then `php artisan migrate`. Local preview: `php artisan serve --host=127.0.0.1 --port=8059`.

Checks: `php artisan test`. Tests use an in-memory database. `product:fixtures` is restricted to local/testing SQLite databases named reklam-preview and needs REKLAM_FIXTURE_PASSWORD. It must never run on production.

Set FRONTEND_URL, EMBED_URL, CORS_ALLOWED_ORIGINS and TRUSTED_PROXIES explicitly. AD_DELIVERY_ENABLED is a delivery stop switch, disabled by default. A verified approved publisher and approved creative are required. Payment endpoints return 503 while payment implementation is excluded.

Run the scheduler every minute. `stats:aggregate` refreshes daily aggregates; `product:health` checks database and aggregation freshness. `traffic:retain` previews retention; `traffic:retain --apply` removes old visitor identifiers without deleting report counts. Default identity retention is 90 days. Delivery replay receipts expire after a day.
