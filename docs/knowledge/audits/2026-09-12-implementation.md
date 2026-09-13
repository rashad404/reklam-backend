# Reklam.biz implementation evidence

Status: Implemented and deployed. External account dependencies and verification limits are listed below.

## Implemented

- Shared product standard, versioned knowledge mirrors and release runbook.
- New responsive public pages, role-aware workspaces and common form/status/error patterns. Unsupported homepage metrics removed. AZ/EN/RU routing, metadata, canonicals, hreflang, robots, sitemap and social image. Correct HTML language and private-page noindex.
- Transactional campaign/creative saves with request-key retries, campaign-scoped creative reads, content validation, review on edits and soft deletion. Browser session draft preservation. Exact-size uploads and a renderer shared with the publisher embed.
- Publisher site registration, DNS ownership verification, placement create/edit/pause/archive, installation code and observed-request status.
- Real admin review queues, reasons, approval/verification gates and decision audit records. Dedicated administrator bootstrap command; existing users are not granted privileges automatically.
- Expiring encrypted delivery tokens, event replay receipts, atomic uniqueness/rate counters, trusted-proxy handling, host matching and status rechecks. Independent embed supports duplicate script tags, multiple placements, no-fill, asset failures and viewability events. External geo calls removed from click/impression requests.
- Consistent total-event CTR, total/unique counts, bounded Baku-time date ranges, accessible report tables and deterministic daily aggregation. Visitor identifier retention preserves historical report counts.
- Shared authentication, transient error handling, OAuth origin/source/state/expiry checks, redirect fallback and a validated return path. Unsafe legacy token-in-query callback retired. Tokens expire after 30 days.
- Payments show an unavailable state and payment stub endpoints return 503. No checkout, deposit, withdrawal or ledger implementation was added. Unsupported daily limits/targeting cannot silently opt a legacy campaign into delivery; the editor clears these unsupported settings.
- Frontend lint/build CI, backend behavior CI, browser journey tests, health and retention commands, safe deployment/rollback documentation. Unused frontend dependencies removed.

## Local evidence

- Backend: 18 tests / 111 assertions pass, including retry idempotency, cross-account denial, creative removal, verification/moderation, token/replay handling, reporting and aggregation reruns.
- Frontend: production build passes; lint passes without findings after cleanup.
- Browser: 7 end-to-end checks pass using Chrome and isolated SQLite fixtures. Covers campaign create/reload/edit, publisher placement/code, admin denial and decisions, duplicate independent embeds, public page metadata and language, mobile widths 320/390 and desktop 1440, dark mode and axe accessibility checks.
- Desktop Azerbaijani and narrow Russian homepage screenshots inspected. Screenshot and interaction artifacts are under frontend/test-results locally and are not production data.
- Local browser tests do not prove a real Kimlik.az user's external identity flow. Production initiation and administrator login are checked separately during release.

## Production baseline

The old public domain is a Laravel site. A separate Next.js checkout exists but is not serving the domain. The API has three accounts, fourteen campaigns, one approved livescore.az publisher and approximately 4.5 million impressions. Production modifications in old checkouts are preserved.

A consistent SQL backup was restored into a separate database before migration rehearsal. The live site continues receiving impressions, so a later live row count is expected to exceed the snapshot. Verify restored rows against the snapshot maximum ID instead of claiming two different-time live counts match.

The existing Google service account has no Reklam.biz Search Console property. A Site Verification API capability check returned 403. No other site's property, analytics identifier or traffic data was reused. Sitemap discovery and rendered SEO remain part of this release; Search Console access is an external configuration dependency.

## Final release verification

Release path: `/home/ugn/reklam-releases/20260912-v4`. Frontend source: `d22c50287fd7050f33d20b12dfd0fffbe2da07b8`. Backend source: `8b31af79ce3fd39a6070fa07ae57e1270c10cacd`. Later documentation-only commits mirror this evidence and do not change application behavior.

- Next.js 16.3.5, isolated Node 22.23.2 runtime, PHP 8.4, Laravel 13.31.0. Both npm audits and the production Composer audit report zero known advisories at release time.
- Backend: 18 tests, 111 assertions. Includes end-date-only campaigns, stale aggregation failure detection, and retention preserving historical counts and recent identifiers.
- All seven browser journeys pass against an isolated production frontend build. Live public-page browser checks pass in AZ/EN/RU at 320/390/1440px, plus dark-mode and axe checks.
- Live rendered-HTML audit: all 21 sitemap URLs return 200, correct language, self-canonical and AZ/EN/RU/x-default metadata. Three sampled private workspace routes return noindex headers. `/az` redirects to `/` with 308.
- Live administrator email/password login, overview and both review queues work with no browser console errors. The release administrator is a new dedicated account; no existing account was elevated. Credentials are stored only in the local private workspace file `.private/production-admin.txt`, outside both repositories.
- Live Kimlik login initiation reaches its English authorization sign-in page. No real end-user identity credentials were available, so a complete external-provider login is not claimed as tested.
- Real production placement requests and token-bound impressions were observed after cutover. Synthetic checks did not call impression/click tracking endpoints. The existing approved publisher was retained after manual operator verification against cPanel domain ownership; the decision is recorded in `moderation_decisions`. New registrations require DNS verification.
- Mobile Lighthouse on the live homepage: performance 98, accessibility 100, best practices 100, SEO 100; LCP 2.1s, CLS 0.062. This is a lab measurement, not field evidence or a ranking guarantee.
- The patched banner generator produced an exact-size image under the unprivileged production application user with external requests disabled.
- Database, aggregation and HTTP monitor pass. Scheduler runs every minute; read-only product monitor every five minutes. Automated tests demonstrate that stale aggregation makes health checks fail. Monitoring writes a local operational log; no external alert channel was configured or messaged.

Backup: `/home/ugn/reklam-backups/20260912-production-v1/database.sql.gz` (about 180 MB compressed). Its restored snapshot contains 4,536,024 impressions, matching the live table restricted to the snapshot's maximum ID, and 26,841 clicks. The additive migration passed on that restored database before production. Original checkouts, original API public directory and first releases remain available. Final routing uses the versioned API public symlink and the PM2 process named `reklam-frontend`.

## Security finding and external dependencies

During the remote-history merge, commits `f3e0b5d` (frontend) and `f94b202` (backend) contained a `postinstall` hook that downloaded and ran an executable from an unrelated GitHub release into `/tmp/.sshd`. It was excluded from the release and removed from both current repository tips. The checked production package files, payload path and process list did not show that hook/payload running. This limited check does not establish how the commits were introduced or prove that every credential/host is uncompromised. The repository owner should review GitHub account, collaborators, keys and audit history. The malicious history was preserved as evidence rather than force-rewritten.

The available Google service account has no Reklam.biz property and Site Verification returned 403. Search Console ownership, analytics property creation/ingestion and field performance are external account work, not silently marked complete. `www.reklam.biz` has no DNS record at verification time; the canonical apex domain works and the origin redirect is prepared for when that DNS alias is configured. No suitable DNS API credentials were available in the project environment.

Payment implementation, reconciliation and financial correctness certification remain excluded. Existing charge code was not redesigned. Delivery and reporting changes must not be represented as financial certification.


## Support completion

A final operational check found no configured support mailbox or MX record for the advertised info address. The release replaces all rendered email contact links with `/settings/support`, including public help, footer and legal pages. Users can submit private, retry-safe requests and see administrator replies. Administrators use `/admin/support` with open/answered/closed queues. Support messages are included in the privacy disclosure. No email delivery or response-time promise is made.

The request-and-reply browser journey passes, as do API tests for ownership isolation, duplicate retries and administrator authorization. The additive support table migration is also rehearsed against a disposable MySQL schema with the production users-table definition before production migration. The earlier full restore rehearsal database was removed after verification; the private backup remains.

Final live administrator/support queues, public browser checks, rendered SEO checks and health monitor passed after the v4 cutover. Lighthouse was rerun on v4 with the same 98/100/100/100 scores. Backend CI for the support commit passed; frontend build and journey CI are checked separately from local evidence.
