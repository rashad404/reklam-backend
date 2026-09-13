# Reklam.biz implementation evidence

Status: Local implementation verified; production deployment in progress.

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

- Backend: 14 tests / 88 assertions pass, including retry idempotency, cross-account denial, creative removal, verification/moderation, token/replay handling, reporting and aggregation reruns.
- Frontend: production build passes; lint passes without findings after cleanup.
- Browser: 6 end-to-end checks pass using Chrome and isolated SQLite fixtures. Covers campaign create/reload/edit, publisher placement/code, admin denial and decisions, duplicate independent embeds, public page metadata and language, mobile widths 320/390 and desktop 1440, dark mode and axe accessibility checks.
- Desktop Azerbaijani and narrow Russian homepage screenshots inspected. Screenshot and interaction artifacts are under frontend/test-results locally and are not production data.
- Local browser tests do not prove a real Kimlik.az user's external identity flow. Production initiation and administrator login are checked separately during release.

## Production baseline

The old public domain is a Laravel site. A separate Next.js checkout exists but is not serving the domain. The API has three accounts, fourteen campaigns, one approved livescore.az publisher and approximately 4.5 million impressions. Production modifications in old checkouts are preserved.

A consistent SQL backup was restored into a separate database before migration rehearsal. The live site continues receiving impressions, so a later live row count is expected to exceed the snapshot. Verify restored rows against the snapshot maximum ID instead of claiming two different-time live counts match.

The existing Google service account has no Reklam.biz Search Console property. A Site Verification API capability check returned 403. No other site's property, analytics identifier or traffic data was reused. Sitemap discovery and rendered SEO remain part of this release; Search Console access is an external configuration dependency.
