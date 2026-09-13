# Reklam.biz operations

## Scope and source

Frontend and backend are separate Git repositories on main. Deploy immutable archives from reviewed commits. Shared docs are mirrored into both repositories with `python3 scripts/sync-knowledge.py`. The canonical shared docs live in the parent workspace. Each release manifest names both commits and its check evidence.

## Local verification

Use PHP 8.4 and Node 22 or a compatible newer runtime. Backend unit/integration tests use in-memory SQLite through phpunit.xml. Browser fixtures require a separate `/tmp/reklam-preview.sqlite`, `APP_ENV=local`, `DB_CONNECTION=sqlite`, empty `DB_URL`, and `REKLAM_FIXTURE_PASSWORD`. Run migrations and `product:fixtures` with those variables. Never point fixtures at the live database.

Run API on 127.0.0.1:8059 with that isolated database, `AD_DELIVERY_ENABLED=true`, first-party CORS origins and `EMBED_URL=http://127.0.0.1:8059/serve.js`. Run frontend on 3059 with local API, `NEXT_PUBLIC_LOCAL_TEST_LOGIN=true`, and a localhost site URL. Run the independent host fixture using `python3 -m http.server 8060 --bind 127.0.0.1 --directory backend/tests/fixtures`. Playwright uses installed Chrome. Test credentials are read from the environment, with a local temporary-file fallback. Production builds must set local login false.

Run frontend lint/build and backend tests. Browser tests cover AZ/EN/RU mobile/desktop, accessibility, advertiser editing, publisher setup, moderation and duplicate embeds. Screenshots and reports are ignored by Git; publish summarized evidence in the release audit. Read installed Next.js docs before framework changes.

## Production topology

Server: 203.161.35.63, SSH port 21098. Application owner: ugn. PHP CLI: /opt/cpanel/ea-php84/root/usr/bin/php. Existing API database is MySQL ugn_reklambiz. Keep secrets out of commands, logs and documents.

Releases live under `/home/ugn/reklam-releases/`. The API document root at `/home/ugn/api.reklam.biz/public` points to the selected release public directory. API storage remains shared with the preserved original checkout. Each release has a private copy of the production environment. New code does not replace the old checkout or discard its local modifications.

The Next.js process runs as ugn on loopback [::1]:3059 with HOSTNAME=localhost using PM2 name `reklam-frontend`. Nginx's domain-specific include routes the public domain to that process and keeps cPanel's other sites unchanged. Preserve the include and PM2 state in each release backup. Test nginx configuration before reloading it. Canonical host redirects preserve the request URI. Do not redirect technical cPanel hostnames into the product.

## Release procedure

1. Record current source commits, document-root target, nginx include, process definition, scheduler and application environment. Inspect local production modifications and preserve them.
2. Create a private database dump and storage/code backup before migration. Restore the dump into a disposable verification database and compare table counts. The verification database must have a unique release-specific name and must never replace production.
3. Upload commit archives into a new release directory. Install PHP dependencies with PHP 8.4 and production-only dependencies; install frontend dependencies with `npm ci --ignore-scripts` and build on the Linux server with production public settings. Do not copy macOS node_modules.
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


## Release 20260912-v4 details

Both applications are under `/home/ugn/reklam-releases/20260912-v4`. The API public symlink points to its backend/public. The versioned `ecosystem.config.cjs` names the frontend standalone server, cwd, environment and interpreter. Use `/home/ugn/reklam-runtime/node-v22.23.2-linux-x64/bin/node`; do not replace the system Node used by other sites. Set NODE_BINARY to this path for the banner generator. Puppeteer's Chrome cache belongs to ugn at `/home/ugn/.cache/puppeteer`.

The domain include is `/etc/nginx/conf.d/users/ugn/reklam.biz.ug2.news/reklam-next.conf`; its upstream is `http://[::1]:3059`. HOSTNAME=localhost is necessary for this standalone proxy setup. Apache's release public files must be owned by ugn and not group-writable, with the PHP 8.4 handler in .htaccess. Verify external HTTP after a reload, not just process startup.

Build in a new release directory. Copy public and .next/static into .next/standalone before switching PM2. When the script path changes, inspect `pm2 describe reklam-frontend`, remove only that verified Reklam process, then use `pm2 start <release>/ecosystem.config.cjs --only reklam-frontend` as ugn and `pm2 save`. This PM2 version retained the old script path with startOrReload; verify the resulting script path and cwd. Switch the API symlink using a temporary symlink and atomic rename. Update only Reklam's scheduler/monitor entries to the new backend path.

The original API public directory is `/home/ugn/api.reklam.biz/public.before-20260912`. The original frontend is `/home/ugn/reklam.biz`. The initial backup directory is `/home/ugn/reklam-backups/20260912-production-v1`. The v1 and v2 release directories are also retained. Roll back application routing without dropping additive database columns.

Health monitor: `bash <release>/backend/scripts/product-monitor.sh`. Its output is `/home/ugn/api.reklam.biz/storage/logs/product-monitor.log`. Scheduler output is in the same directory's scheduler.log. No email/Slack alerts are sent. The admin bootstrap refuses existing accounts; a new account requires REKLAM_ADMIN_PASSWORD with at least 24 characters. Keep credentials outside Git.


Support is handled inside the product. Operators should check `/admin/support` and answer requests there; users read replies at `/settings/support`. There is no configured email notification channel. The support table is additive and may remain when rolling back the UI. Do not restore a database merely to roll back a support-page change.


Copy follow-up: frontend release `20260912-v5` removes public rollout/payment disclaimers and uses coming-soon copy only on payment pages. The API and its scheduler remain on `20260912-v4`. The v5 ecosystem file controls the frontend.


Design rebuild: final frontend release `20260912-v8` uses the new public and account design. Its ecosystem file controls the frontend standalone server. Source commit `b7febd4` includes the full redesign from `9e1315e` plus stable initial rendering and the larger ad attribution target. API, cron and monitoring remain on `20260912-v4`. To roll back only the design, switch the verified Reklam PM2 process to the retained v6 ecosystem file; do not roll back the database.

Kimlik login hotfix: backend commit `28298f4` was applied atomically to the active v4 AuthController after matching its original hash. OAuth credentials must be read through services.wallet configuration, never runtime env(), because production uses config:cache. Backup: `/home/ugn/reklam-backups/20260913-oauth-config/AuthController.php`. Details: audits/2026-09-12-kimlik-login-fix.md. Frontend remains v8.


Public marketing copy is deployed as frontend `20260913-marketing`, source commit `d283b2e`. The ecosystem file in that release selects its standalone server. v8 remains available for a frontend rollback. Backend v4 retains the Kimlik configuration hotfix.

Specificity revision: frontend release `20260913-editorial`, source commit `7bd3360`, applies the azdoc research to public copy and composition. It replaces repeated feature/step blocks with audience-specific content and native FAQs. The prior `20260913-marketing` release is retained for rollback. Backend remains v4 with the Kimlik fix. Evidence: audits/2026-09-12-specific-design.md.

Gallery attribution fix: frontend release `20260913-ad-label`, source `3668c0c`, moves the public gallery's label below the creative. Prior `20260913-editorial` release is retained for rollback. Backend and publisher embeds are unchanged.


Publisher offer release: API public symlink and frontend PM2 now use `20260913-publisher-offer`, backend source `6748889`, frontend source `138a667`. Shared storage remains `/home/ugn/api.reklam.biz/storage`. Scheduler/monitor still use v4, intentionally. Publisher-row backup: `/home/ugn/reklam-backups/20260913-publisher-offer/publishers-before.json` (private).

Before API cutover, remove group/other write permissions on the new backend public directory; cPanel's PHP handler rejects group-writable index.php. Probe the unauthenticated auth/user endpoint for 401 immediately after cutover and run the product monitor before publishing dependent frontend changes.

Retain publisher offer dates during rollback. Do not run the offer migration's down method or restore old balances. An API rollback to v4 must first carry forward Publisher's offer dates/rate method, the config key and both ServeController commission calls, so existing promised 0% periods remain honored. Frontend-only rollback can use the retained ad-label release.
