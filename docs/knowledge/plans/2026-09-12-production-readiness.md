# Reklam.biz production-readiness plan

Date: 2026-09-12
Status: Proposed, implementation not started
Scope: Product experience, design, SEO, campaign and publisher workflows, moderation, ad delivery, reporting, security and operations. Payment implementation is excluded.

This is a new plan based on the current source. It supersedes the old plans as the proposed roadmap; old documents are retained only as history. A checked source file is evidence of implementation, not proof that the deployed feature works.

## 1. Outcome and scope

An Azerbaijani business owner should understand the offering, create an ad, preview its actual appearance, submit it and understand its delivery status without assistance. A website owner should register a site, create a placement, install it and confirm that it works. An administrator should be able to review content, explain decisions and stop unsuitable inventory. Every journey should work on a small phone as well as desktop.

Preserve Next.js, Laravel, Kimlik.az login, Reklam.biz branding and the three existing languages. Improve the existing product rather than rewrite it or add a large component framework.

Excluded: deposits, checkout, wallet charging, withdrawals, settlement, financial ledger redesign, invoices and payment provider integration. Existing balance/spend correctness is a separately recorded dependency, not a hidden payment workstream in this plan. Do not present completion of this plan as readiness for unrestricted paid operation. Test non-payment journeys using isolated fixtures with predefined balances. Proposed launch behavior for unfinished payment surfaces is to remove actionable payment promises and show an honest unavailable state, without changing financial processing.

Also excluded from the first release: an exchange/RTB system, retargeting, speculative audience targeting, mass SEO articles, complex agency accounts and a general-purpose banner editor.

## 2. Working standard adopted from football

The useful reference is the decision and verification process, not football's layout, colors, traffic profile or infrastructure. In particular, the football documents record rejection of overly editorial designs and prioritize the actual task in the first viewport.

For every implementation slice:

1. State the user task, current friction and observable result before editing.
2. Follow the change through all affected pages, API behavior, locale variants and states. A campaign status must mean the same thing in its list, editor, report and serving decision.
3. Use representative data: long Azerbaijani and Russian labels, many campaigns, multiple placements, absent images, zero traffic, rejected content, expired sessions and failed requests.
4. Inspect rendered UI at 320px, 390px and desktop; exercise navigation and persistence. Passing compilation or a screenshot does not establish that the journey works.
5. Keep the interface compact where people work. Explain what a number counts and why an action is unavailable. An error must never silently become a zero or a success.
6. Add meaningful regression checks for behavior, not tests that repeat labels or CSS classes. Fix issues within the authorized slice before marking it complete.
7. Record actual verification, environment, representative routes and remaining gaps. Distinguish local, staging and production evidence.

ASCII punctuation is required; preserve language-specific letters. Use natural Azerbaijani product wording, not literal translations or filler. Do not invent audience size, performance, customer logos, testimonials, approval times or protection claims.

During the first implementation slice, extract this into `docs/knowledge/product-standard.md` and add a short pointer in the root project instructions. Preserve the frontend's existing Next.js instructions. The root and docs directory are currently outside the two Git repositories, so establish how shared instructions and evidence will be versioned before relying on them across deployments.

## 3. Current source findings

These are source observations, not a live-site audit. No browser review, build, tests, Search Console or production measurements were performed for this plan.

| Area | Observed gap | Implication |
|---|---|---|
| Public trust | `frontend/app/[lang]/page.tsx` hardcodes 500+, 1,200+, 50M+ and 250K+ metrics without a data source. | Remove unless verified and maintained; the page should demonstrate the product instead. |
| SEO | Locale layout supplies the same title/description across pages. No frontend sitemap, robots, canonical, hreflang or social metadata implementation was found. Root HTML language is fixed to `az`. | Search identity and language behavior need deliberate implementation and rendered verification. |
| Routing | Middleware hides `/az`, while the navigation helper does not explicitly share that prefix policy. Saved locale can redirect unprefixed URLs. | Verify direct links, cookie behavior, locale switching and redirect chains. |
| Public/app boundary | Advertiser and publisher routes show public content or dashboards according to client auth. | Separate stable public information from private workspace content and indexability. |
| Campaign editor | Campaign and creative saves use separate requests; editing loads creatives from the first paginated `/ads` result; removed content is not deleted by the editor. | Partial saves and incomplete editing become likely as accounts grow. |
| Moderation | New creatives are explicitly auto-approved. Admin screens are largely placeholders; API admin middleware does exist. | A moderation interface alone would not make approval effective. |
| Publisher eligibility | Ad serving checks active units and eligible campaigns but does not check publisher approval/suspension. | Unapproved inventory can participate in delivery. |
| Delivery | Daily budget and targeting fields exist but are not consulted by selection. CPC and CPM bids are compared directly as weights. | Displayed controls and delivery behavior do not fully agree. |
| Tracking | Visitor IDs use a public client-generated checksum, not a secret signature. Referrer matching uses substring checks; proxy headers are accepted directly. Events are not bound to a server-issued delivery token. | Current checks do not establish authentic delivery and require hardening. |
| Embed | Script initializes all containers on each inclusion, sends an impression immediately after markup insertion and derives API origin from script origin. | Multiple embeds, image failures, layout shifts and frontend/backend host separation need testing. |
| Reports | Overview CTR uses unique events while daily/breakdown calculations use totals. Date ranges are not bounded. Aggregation uses dimensions missing from `DailyStat` fillable fields. | Define metrics and verify aggregation before treating reports as authoritative. |
| Authentication | Each `useAuth` instance fetches independently; failures clear tokens, and absent tokens do not clear the stored user object. OAuth messages lack origin/source validation. | Session state, transient errors and popup handling need a coherent implementation. |
| Performance | Global CSS imports seven font weights remotely and applies transitions broadly. Geo lookup can make an external HTTP request during tracking. | Measure actual cost and keep public pages and publisher sites lightweight. |
| Operations | Tests are framework examples. `restart.sh` kills broad worker matches and any process occupying its ports. READMEs are boilerplate. | Establish reproducible checks and a service-specific release runbook. |

Primary source areas: `frontend/components/advertiser/CampaignForm.tsx`, `frontend/hooks/useAuth.ts`, `frontend/lib/utils/walletAuth.ts`, `frontend/middleware.ts`, `backend/routes/api.php`, `backend/app/Http/Controllers/Api/`, `backend/public/serve.js`, `backend/app/Console/Commands/AggregateStats.php`, and `backend/app/Helpers/TrackingHelper.php`.

## 4. Product and visual direction

Direction: clear, capable, restrained.

- Clear: one main task per screen, specific headings, understandable status explanations and visible next actions.
- Capable: useful campaign rows, accurate reports and a preview that matches the delivered ad.
- Restrained: red brand emphasis, neutral reading surfaces, purposeful spacing and minimal motion.

Keep the logo and red identity. Retain green as a limited publisher accent rather than creating two unrelated designs. Use accessible semantic colors independently of brand colors. Verify red/white text combinations and dark-mode contrast instead of assuming current tokens pass.

Create a small shared vocabulary for buttons, fields, notices, tables, pagination, tabs, status badges, dialogs and empty/error/loading states. Replace repeated arbitrary styling gradually. Use rows and dividers for lists; reserve cards for meaningful groups. Keep readable body text, tabular numerals, consistent date formats and comfortable touch targets. Load only necessary font weights with full Azerbaijani and Cyrillic support.

The public homepage should lead with a concrete explanation and two clear audience paths. A compact interactive ad preview can explain the product better than decorative metrics. Demonstration content must be labeled. Below it, answer real questions: supported formats, creation process, installation, reporting definitions and review requirements. Avoid an oversized hero that delays access to the product.

The workspace should prioritize current campaigns/placements and actionable status. Use a stable role-aware navigation with a visible way to switch between advertiser and publisher tasks for users who have both profiles. Mobile must preserve the primary action and key row information, not merely shrink desktop tables.

## 5. Proposed route and content structure

Preserve existing working URLs where practical. The default plan is to retain `/advertiser` and `/publisher` for workspaces and add stable public audience pages. Avoid a wholesale `/app` migration unless a baseline review establishes a concrete benefit.

| Surface | Proposed routes | Search treatment |
|---|---|---|
| Product overview | `/` | Indexable, localized |
| Advertiser explanation | `/for-advertisers` | Indexable, localized, practical creation CTA |
| Publisher explanation | `/for-publishers` | Indexable, localized, practical setup CTA |
| Format examples | `/ad-formats` | Indexable only with useful original examples and actual specifications |
| Help | `/help` and a small number of substantive guides | Indexable where content independently solves a task |
| Privacy and terms | Existing routes | Accurate, discoverable; outside the acquisition keyword strategy |
| Advertiser/publisher workspaces | Existing dashboards, editor, placements, reports | Authenticated and noindex; excluded from sitemap |
| Account/admin/OAuth | Existing routes | Private or utility, noindex; excluded from sitemap |

Retain Azerbaijani without a prefix, English under `/en`, Russian under `/ru`. Public URLs should deterministically represent their language; a stored preference should not make a bookmarked Azerbaijani page inaccessible. Preserve an explicit user language choice through auth and return navigation.

Initial guide candidates: preparing a banner, installing a placement, understanding impressions/clicks/CTR, and understanding review/statuses. Publish them only as their workflows are verified. Keyword themes such as online advertising in Azerbaijan, banner advertising and website monetization are hypotheses until Reklam.biz query data or research supports them. Do not transfer football query data, brand history or analytics identifiers.

## 6. Delivery sequence

All phases below are planned. The sequence follows dependencies; a visual pass alone is not the release.

### Phase 0 - Baseline and durable rules

- Establish frontend/backend commits, current runtime versions, environment contract and shared-doc versioning. Read installed Next.js documentation before framework changes.
- Record a route inventory with audience, auth rule, API, indexability and current completeness.
- Capture local desktop/mobile screenshots and production-mode build, lint and type-check results. Run existing backend checks in an isolated test environment and record their limited coverage.
- Establish fixtures for ordinary, dual-role, admin, pending and suspended users; 30+ campaigns/creatives; multiple units; total/unique traffic; empty and unavailable states.
- Verify actual hosting and asset routing before changing URLs. Existing Reklam.biz configuration uses frontend 3059 and backend 8059; azdoc-specific port rules are not this project's configuration. Inspect process ownership before starting or stopping previews.

Exit: reproducible baseline and a tracked gap list with source evidence. No claim of production measurements without production inspection.

### Phase 1 - Product shell, design system and public content

- Implement the visual direction across navigation, homepage and one representative workspace/form, then apply it consistently to the remaining surfaces.
- Remove unsupported hardcoded metrics and inaccurate functionality claims.
- Add stable public audience pages and a meaningful format demonstration. Keep core public content server-rendered and limit client code to actual interaction.
- Standardize shared components, responsive lists, focus/keyboard behavior, notices and recovery actions. Add route loading/error/not-found states.
- Review all three languages for terminology, long labels and number/date formatting. Apply the Azerbaijani wording skill when authoring product copy.
- Present excluded payment surfaces honestly, without inert deposit/withdraw controls masquerading as available features.

Exit: a visitor can explain the product and reach the right starting action; users can navigate the workspace at 320px and 390px without lost actions or horizontal page overflow.

### Phase 2 - Authentication and complete creation journeys

- Use shared typed auth state and deduplicated user loading. Clear user state on logout; distinguish an expired token from a temporary network/server failure.
- Validate OAuth message origin and popup source, require a valid stored state/verifier, clean up listeners and handle cancellation, denied access, blocked popups and mobile redirect fallback. Preserve locale and a validated internal return path. Consolidate duplicate callback implementations without breaking registered redirect URLs.
- Enforce ownership and role checks on the server; hide inaccessible admin navigation and render a useful forbidden state. Add authentication throttling and safe errors.
- Give campaign creation a clear progression: content, pricing model/budget settings, schedule, review. Keep existing payment operations outside this work.
- Implement an atomic campaign-and-creatives save contract, validation and safe retries. Do not lose a draft after validation failure. Fetch campaign-scoped creatives independently of global pagination and define removal behavior explicitly.
- Validate image type, byte size and dimensions; allow image-only, text-only and combined content according to a documented format matrix. Make frontend/API/rendering requirements agree. Reject unsafe destinations and unsupported content.
- Match previews to the actual renderer and selected dimensions, including long text and broken images. Unsupported native formats should be completed or removed from selectable options.

Exit: create, reload, edit, remove a creative and submit successfully; failed saves do not leave partial campaigns or discard entered content. Cross-account access is denied. Login/logout and return paths work in all locales.

### Phase 3 - Publisher setup and moderation

- Complete site registration, pending/rejected/approved states, resubmission and explicit site ownership verification. Start with one site per publisher profile as the current model supports; do not silently imply multi-site account management.
- Complete placement create/edit/pause/archive, filtering and pagination. Show format, site, status, code and installation result in useful rows.
- Provide copyable production embed code from one environment-aware source. A real observed request should confirm installation; a copy-button click should not.
- Build real admin queues with previews, site/destination context, filters, pagination, approve/reject actions and reasons. Retain decision history and actor/time; do not expose administrative details publicly.
- Define status transitions centrally. New content enters review, relevant edits trigger re-review, and rejected/suspended publishers or creatives cannot serve. Explain pending review in the user's own workspace.
- Add a usable report-ad/support path with verified contact details and clear moderation ownership.

Exit: publisher submission -> review -> placement installation -> eligible delivery works end to end, and rejection/suspension stops delivery with an understandable status.

### Phase 4 - Reliable embed, delivery and traffic integrity

- Publish a format/eligibility contract: campaign active/date window, ad approved, publisher approved, unit active, compatible format and applicable existing budget eligibility. Define an inclusive end-date timezone consistently.
- Do not expose unsupported targeting. Define separate treatment of CPC and CPM inventory before mixing selection weights; raw bids are not directly comparable. Actual financial charging/ledger repairs remain a separate dependency.
- Make loader initialization idempotent per container; support multiple units, repeated script tags and documented dynamic initialization. Use unique selectors instead of repeated element IDs in new snippets.
- Resolve script, API and image origins correctly in production and previews. Preserve valid external asset URLs instead of stripping their host indiscriminately.
- Reserve placement space, handle no-fill/image errors/network timeouts and contain styles. Show an accessible advertisement label, use safe URL/DOM construction, and keep the publisher's page usable when delivery fails.
- Distinguish requested, rendered and viewable events. Proposed viewability definition: at least 50% visible for one continuous second while the document is visible. Treat this as a product metric definition, not certification. Do not silently change historical impression meaning.
- Replace checksum-based authenticity assumptions with short-lived server-issued delivery tokens bound to ad, unit and expiry. Verify eligibility at event time; handle replay/retry idempotently. Do not describe a token as proof of a human visitor.
- Use atomic rate/dedup operations, properly configured trusted proxies and exact normalized host/subdomain rules. Test IPv4/IPv6, missing referrers and forged proxy headers. Do not use referrer alone as authentication.
- Avoid external geo calls on the critical delivery/click path. Choose local or asynchronous enrichment after verifying licensing and deployment requirements; unknown geography must remain unknown.

Exit: an independent fixture website with multiple slots works across mobile and desktop, paused/rejected inventory stays out, invalid/replayed events do not count twice, and failures do not disrupt the host site. Record loader size and latency under a stated load.

### Phase 5 - Trustworthy non-payment reporting

- Publish metric definitions for total/unique impressions, clicks, CTR, date boundaries and update delay. Use the same basis in summary, charts, breakdowns and detail pages.
- Validate and bound date ranges, fill empty days and make the reporting timezone visible. Treat absent data separately from failed loading.
- Correct and regression-test daily aggregation dimensions, idempotency and totals against raw fixtures. Decide which views read aggregates and which read recent events; avoid double-counting at the boundary.
- Replace repeated per-row queries with bounded grouped queries and appropriate indexes. Keep historical records when a campaign/placement is archived.
- Add campaign/unit filters, useful empty states and an accessible data table for each chart. Add CSV export if the verified primary reporting task needs it, with ownership checks and bounded exports.
- Label existing money fields carefully or withhold unverified ones; do not invent date-filtered spend or earnings. Financial reconciliation is outside this plan.

Exit: known fixture totals reconcile across advertiser and publisher reports, all dates and CTR definitions agree, and an API error never appears as zero traffic.

### Phase 6 - SEO, accessibility and performance release pass

SEO begins with Phase 1 route/content decisions; this phase completes and verifies it.

- Create page-specific localized titles, descriptions, a clear H1 and useful heading structure. Render the correct HTML language for each locale.
- Generate absolute self-canonicals and reciprocal language alternates for equivalent published pages. Use one shared URL policy for navigation, metadata, sitemap and redirects. Do not canonicalize translated pages to Azerbaijani.
- Add sitemap and robots output, social images/metadata and truthful Organization/WebSite structured data where appropriate. Include only canonical, successful, indexable public URLs in the sitemap; use actual modification dates.
- Apply noindex to private/utility pages and protect confidential content with authorization. Robots disallow is not a replacement for noindex or access control. Keep previews private and out of indexing.
- Verify HTTP/HTTPS, www/apex, `/az`, language links, invalid routes, query variants and unknown IDs over HTTP. Preserve paths and queries in necessary redirects; avoid chains, loops and soft 404s. Verify cache behavior does not mix languages or private data.
- Link public audience, formats and help pages with useful anchor text. Keep thin or unfinished guide pages unpublished.
- Test keyboard navigation, focus restoration, form labels/errors, color contrast, screen-reader status announcements, reduced motion, text zoom and both themes. Include chart/table accessibility and popup alternatives.
- Reduce unused client dependencies, repeated auth requests, unnecessary font weights and broad animations based on measurements. Use image dimensions, appropriate sizing, compression and caching.

Performance targets: aim for 95+ mobile Lighthouse performance and 100 accessibility/best-practices/SEO on representative public pages; investigate failures rather than treating scores as product proof. Once sufficient field data exists, target p75 LCP <= 2.5s, INP <= 200ms and CLS <= 0.1, assessed by device class. These thresholds follow [Web Vitals guidance](https://web.dev/articles/vitals). The ad loader requires separate host-page impact measurements.

SEO implementation should follow Google's guidance on [canonicals](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls), [localized versions](https://developers.google.com/search/docs/specialty/international/localized-versions), [sitemaps](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap) and [developer SEO fundamentals](https://developers.google.com/search/docs/fundamentals/get-started-developers). Sitemap acceptance, a valid canonical and a good lab score do not establish indexing or ranking.

Exit: saved rendered-HTML and browser evidence covers every public template and all locale variants; private routes remain protected and excluded from discovery surfaces; measured performance and accessibility gaps are documented.

### Phase 7 - Operations and controlled release

- Replace broad restart behavior with verified project-owned service management. Document frontend/backend versions, build commands, storage paths, environment keys without secrets, scheduler, queue, caching and rollback.
- Add CI for production build, lint/type checks, focused backend behavior tests and primary journey tests. Include ad-serving/ownership/moderation/aggregation regressions and the independent embed fixture.
- Harden upload handling and the banner generator process, validate resource limits and remove raw debug output from public errors. Split public embed CORS needs from private dashboard API needs; apply headers/CSP appropriate to each surface.
- Set production debug off, redact OAuth secrets and sensitive visitor data from logs, and define retention for raw traffic. Align privacy/help wording with actual storage and tracking; verify requirements separately before making legal claims.
- Verify database/storage backups and a restore rehearsal. Monitor API availability, ad fill/errors, invalid events, scheduler freshness and frontend exceptions. Use safe synthetic checks that do not contaminate real reporting.
- Produce a release manifest tying together both Git commits and versioned docs, migrations, configuration and assets. Test additive migration and rollback behavior against a production-like copy; do not erase historical data to roll back.
- Use a controlled non-payment release scope. Record live HTTP/browser checks after authorized deployment and maintain an operational stop path for ad delivery.

Exit: another operator can reproduce deployment and rollback, monitoring detects a deliberately simulated failure, and no unresolved core product/security blocker is labeled complete.

## 7. Release acceptance matrix

| Journey | Required evidence |
|---|---|
| Public discovery | Correct first HTML, honest claims, clear audience paths, localized metadata and crawlable canonical links. |
| Login and return | Success, cancellation, expiry, blocked popup, transient API failure, logout, locale preservation and safe internal return path. |
| Advertiser | Text/image/combined creation, realistic preview, failed-save recovery, editing beyond 20 creatives, removal, review, pause and reload. |
| Publisher | Site registration/verification, rejected and approved states, multiple units, embed copy, real installation confirmation, pause and no-fill. |
| Admin | Non-admin denial, working paginated queues, reasons, audit trail and effective serve exclusion after rejection/suspension. |
| Delivery | Independent host, repeat initialization, image error, slow network, blocked storage, invalid token, replay, missing referrer and boundary dates. |
| Reports | Known total/unique fixtures, zero days, timezone boundaries, bounded dates, matching CTR and aggregation rerun without duplicates. |
| Design | 320px/390px/desktop, long AZ/RU labels, both themes, keyboard, zoom, visible errors and no misleading zeros. |
| Release | Both commits identified, build/check evidence, safe migrations, restore/rollback rehearsal, live host verification and monitoring. |

Use isolated data for destructive and adversarial checks. Documentation-only work does not require runtime or browser tests.

## 8. Measurement after release

Establish Reklam.biz's own Search Console property and analytics mapping when access is available. Never reuse another project's credentials/property configuration by assumption. Capture a dated baseline for queries, landing pages, countries and devices; account for reporting delay. Measure advertiser setup completion, campaign submission, publisher setup and confirmed installation without sending ad content, tokens or personal information to analytics. Verify report ingestion rather than accepting a successful collection request as proof.

Review real errors and abandoned steps first, then improve content based on actual query demand. Maintain a small evidence record of what changed, why and what improved. Rankings and conversion gains are outcomes to measure, not promises in the roadmap.

## 9. First concrete implementation slice

Start with Phase 0 and the shared product standard, then deliver a coherent vertical slice: public advertiser page -> login -> campaign creation/preview -> saved draft -> campaign list. Include its error states, mobile review and metadata. This creates the reusable design and API conventions before applying them to publisher setup and administration.

Do not begin with a homepage-only repaint, a framework migration or a large article backlog. The highest priority is a trustworthy complete task supported by a clear public explanation.

## 10. References and planning evidence

Read for working principles:

- `/Users/macmini/projects/football/AGENTS.md`
- `/Users/macmini/projects/football/docs/knowledge/fpredict-product-standard.md`
- `/Users/macmini/projects/football/docs/knowledge/fpredict.md` (design, copy and SEO sections)
- `/Users/macmini/projects/football/docs/knowledge/seo.md`
- `/Users/macmini/projects/football/docs/knowledge/plans/2026-09-12-fpredict-production-release.md`
- `/Users/macmini/projects/football/docs/knowledge/plans/2026-09-12-fpredict-seo-audit.md`

Reklam.biz findings come from the current frontend/backend source and current knowledge guides. Old Reklam.biz plans were not used as the task backlog. Football-specific statistics, domain strategy and infrastructure have not been adopted. This planning task changes documentation only.
