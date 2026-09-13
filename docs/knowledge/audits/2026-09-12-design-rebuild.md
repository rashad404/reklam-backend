# Reklam.biz design rebuild evidence

## Scope delivered

Rebuilt the public page composition and shared visual system, replaced the header and account navigation, redesigned dashboard actions, and restyled campaign, publisher, report, moderation, support, settings and authentication screens. Help and legal pages use the same new typography and hierarchy. Preserved the existing logo and red identity.

Self-hosted Manrope WOFF2 supports Azerbaijani, English and Russian. Localized Reklam.biz house ads replace generic sample artwork in all three banner sizes. The format selector also supports text ads and real campaign-creation links. No synthetic clients or performance claims were added.

Removed rollout/payment disclaimers from public pages. Payment pages alone say availability is coming soon. Header sign-in uses a person icon. Updated canonical design.md and saved the current design plan in plans/2026-09-12-design-rebuild.md.

## Local verification

- Production build, ESLint, formatting and whitespace checks passed.
- All 7 Playwright journeys passed: AZ/EN/RU public layout and metadata, campaign create/edit persistence, publisher placement installation, admin authorization/moderation, independent embeds, dark theme, and support replies.
- Additional browser review covered 12 authenticated routes at 1440px, 390px and 320px. No horizontal overflow; automated accessibility checks passed in light and dark themes. Inspected dashboard, campaign editor, publisher site and ad-format screenshots.
- Verified the house ad opens the localized campaign-creation route.
- Fixed small-label contrast and theme-transition contrast. The live audit then exposed a generic streaming spinner that moved the footer into and out of view, plus an undersized attribution link. Removed the global loading boundary while retaining task-specific loading states and enlarged the house ad attribution target.
- Rebuilt and reran all 7 journeys after these fixes. Local mobile Lighthouse: performance 92, accessibility 100, best practices 100, SEO 100, CLS 0, TBT 10ms. Live results below are the deployment evidence, not a field-performance claim.

## Production verification

The redesign first shipped as frontend v7, with final stability fixes in v8. Source commits: 9e1315e (full redesign), b7febd4 (layout stability and attribution). Frontend v6 remains available for rollback. API and scheduler remain on v4; no backend application or payment-processing change was needed.

Live checks on v7 passed for 21 public pages, six authenticated payment pages across AZ/EN/RU, and read-only admin overview, moderation and support pages. Browser console had no errors. Canonical/hreflang/language/indexability checks passed for 21 public URLs and private noindex routes. Health monitor reported database and aggregation OK.

Final v8 is live at https://reklam.biz, with PM2 pointing to `/home/ugn/reklam-releases/20260912-v8/frontend/.next/standalone/server.js`, zero restarts and healthy database/aggregation checks. The final mobile Lighthouse audit reports performance 97, accessibility 100, best practices 100 and SEO 100. LCP 2.6s, CLS 0.028, TBT 20ms. These are lab results, not measured field Core Web Vitals or ranking evidence.

The browser accessibility regression now waits for the house ad to render and includes WCAG 2.2 target-size rules. The independent workspace review exercised 12 routes in both themes; no violations or overflow were reported. Test fixtures are local only; no synthetic campaigns, impressions or support records were inserted into production for this design review.

Source CI passed for both the initial redesign and final stability commit (GitHub Actions runs 34730830287 and 34731006740). All 7 journeys also passed with the expanded WCAG 2.2 checks. Final v8 checks again passed for 21 public pages, six authenticated payment pages and SEO metadata/private noindex. Existing site ownership/analytics configuration limitations from the original release remain unchanged.
