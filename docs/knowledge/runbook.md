# Reklam.biz operations

## Scope and source

Frontend and backend are separate Git repositories on main. Deploy immutable archives from reviewed commits. Shared docs are mirrored into both repositories with `python3 scripts/sync-knowledge.py`. The canonical shared docs live in the parent workspace. Each release manifest names both commits and its check evidence.

## Local verification

Use PHP 8.4 and Node 22 or a compatible newer runtime. Backend unit/integration tests use in-memory SQLite through phpunit.xml. Browser fixtures require a separate `/tmp/reklam-preview.sqlite`, `APP_ENV=local`, `DB_CONNECTION=sqlite`, empty `DB_URL`, and `REKLAM_FIXTURE_PASSWORD`. Run migrations and `product:fixtures` with those variables. Never point fixtures at the live database.

Run API on 127.0.0.1:8059 with that isolated database, `AD_DELIVERY_ENABLED=true`, first-party CORS origins and `EMBED_URL=http://127.0.0.1:8059/serve.js`. Run frontend on 3059 with local API, `NEXT_PUBLIC_LOCAL_TEST_LOGIN=true`, and a localhost site URL. Run the independent host fixture using `python3 -m http.server 8060 --bind 127.0.0.1 --directory backend/tests/fixtures`. Playwright uses installed Chrome. Test credentials are read from the environment, with a local temporary-file fallback. Production builds must set local login false.

Run frontend lint/build and backend tests. Browser tests cover AZ/EN/RU mobile/desktop, accessibility, advertiser editing, publisher setup, moderation and duplicate embeds. Screenshots and reports are ignored by Git; publish summarized evidence in the release audit. Read installed Next.js docs before framework changes.

## Production topology

Server: 203.161.35.63, SSH port 21098. Application owner: ugn. PHP CLI: /opt/cpanel/ea-php84/root/usr/bin/php. Existing API database is MySQL ugn_reklambiz. Keep secrets out of commands, logs and documents.

Releases live under `/home/ugn/reklam-releases/`. The API document root at `/home/ugn/api.reklam.biz/public` points to the selected release public directory. API storage and environment remain shared with the preserved original checkout. New code does not replace the old checkout or discard its local modifications.

The Next.js process runs as ugn on 127.0.0.1:3059 using PM2 name `reklam-frontend`. Nginx's domain-specific include routes the public domain to that process and keeps cPanel's other sites unchanged. Preserve the include and PM2 state in each release backup. Test nginx configuration before reloading it. Canonical host redirects preserve the request URI. Do not redirect technical cPanel hostnames into the product.

## Release procedure

1. Record current source commits, document-root target, nginx include, process definition, scheduler and application environment. Inspect local production modifications and preserve them.
2. Create a private database dump and storage/code backup before migration. Restore the dump into a disposable verification database and compare table counts. The verification database must have a unique release-specific name and must never replace production.
3. Upload commit archives into a new release directory. Install PHP dependencies with PHP 8.4 and production-only dependencies; install frontend dependencies and build on the Linux server with production public settings. Do not copy macOS node_modules.
4. Set the production frontend/API URLs, correct OAuth client, indexing true, local test login false, API debug false and explicit trusted proxies. Keep the payment routes unavailable. Use only the configured local proxy; never trust arbitrary forwarded headers.
5. Run additive migrations on the live database after rehearsal. Keep historical records. Link shared storage and use PHP 8.4 for the API handler. Generate Laravel configuration and route caches for the new release only.
6. Start the new frontend process as ugn, verify it directly on loopback, then switch the domain routing and API public target. Validate nginx and reload. Preserve all other processes.
7. Add only the Reklam.biz scheduler entry. Run stats:aggregate, schedule:list and product:health. Verify production homepage/languages, metadata, sitemap, robots, auth initiation, private API denial, assets and embed endpoints. Do not manufacture traffic or accounts on production to satisfy a check.
8. Record release paths, commits, measurements, database rehearsal and live checks. Verify the new release's restore/rollback instructions reference real retained files.

## Rollback

For a frontend rollback, restore the previous domain-specific nginx include or previous PM2 release target, test nginx, then reload. For this first cutover the old Laravel frontend is retained at `/home/ugn/reklam.biz` and default cPanel routing reaches it when the new include is removed.

For the API, restore the previous public directory target from the release backup. Keep additive schema columns; do not roll back by dropping production data. Restore the previous environment only if the API configuration change caused the failure. Restore a database dump only for a confirmed data recovery need, after preserving the current database separately.

To stop ad delivery, set AD_DELIVERY_ENABLED=false in the API environment and refresh that release's config cache. This stops new delivery and valid-event processing. Do not stop other sites or payment services.

## Monitoring, retention and limitations

Scheduler runs every minute; daily aggregation runs hourly plus yesterday at 01:00 Asia/Baku. `product:health` fails when aggregation is over two hours old or the database is unavailable. Check HTTP availability, PM2 restart count and API/frontend error logs. Do not log OAuth codes, access tokens or raw profile responses.

Visitor IP/user-agent/referrer fields are anonymized after TRAFFIC_RETENTION_DAYS (default 90, minimum 30), preserving historical aggregate counts. Expired delivery receipts are removed after one day. Keep backups private and apply the host backup retention policy. Financial operations and financial reconciliation are outside this release.

Search Console ownership and analytics ingestion require this site's own verified property. Never reuse football's identifiers or assume its account access. Public crawl/metadata checks are separate from indexing and ranking evidence.
