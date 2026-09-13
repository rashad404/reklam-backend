# Publisher launch offer

## Offer and scope

User requested a six-month introductory offer with 0% Reklam.biz share of publisher advertising earnings, excluding unavoidable payment fees and applicable taxes. The public wording promises zero platform commission; it does not promise fee-free payments, fixed processor costs, guaranteed traffic or the aggregate budgets of unrelated advertisers.

Six calendar months start on first approval. Existing approved launch partners receive the same period from deployment. Dates persist through suspension/reapproval. Existing balances and earlier earnings are unchanged. The current 30% standard rate is retained after expiry and disclosed in detailed terms; the owner was asked whether to choose another post-offer rate, and this existing-rate assumption was stated while proceeding.

## Implementation

Stored immutable offer dates on publishers and exposed current eligibility/end date/commission through their authenticated data. Enrollment occurs on first approval. Both CPC and CPM revenue-share calculations use zero commission during the interval and the cached standard commission setting afterwards. Added an additive migration to enroll current approved publishers prospectively.

Placed the offer near the homepage's main actions, in its publisher section, on the publisher landing page and FAQ, in publisher signup/site screens and in the publisher overview. Active publishers see their end date; expired publishers see the current standard rate. Fee exclusions are next to the offer and localized detailed terms explain duration and expiry. Preserved the gallery attribution fix.

Payment processing, withdrawals, tax-rate calculations and broad ledger reconciliation remain outside this change. The promotion does not certify existing settlement or financial precision behavior. Actual fees and withholdings must be itemized by the payment implementation before payouts are enabled.

## Verification

26 backend tests passed with 150 assertions, covering six-calendar-month boundaries, reapproval, expiry, cached standard commission, CPC/CPM crediting and replay deduplication, alongside existing product/OAuth coverage. All 7 browser journeys passed again after the final heading accessibility correction. Public pages across all three languages and three viewport sizes passed overflow and accessibility checks. Workspace review found a skipped heading level in the reusable offer; corrected it to h2 in compact account contexts. The final dedicated publisher overview/site checks passed in light and dark themes at 320px, 390px and desktop. The broader workspace rerun hit local login throttling; the dedicated rerun completed with no findings. Frontend build, lint and format checks passed. Backend changed-file formatting passed; the full formatter reports pre-existing failures in six unrelated files.


## Production release

Deployed backend `6748889` and frontend `138a667` to `/home/ugn/reklam-releases/20260913-publisher-offer`. Backed up publisher rows and migration history privately at `/home/ugn/reklam-backups/20260913-publisher-offer/publishers-before.json` before migration. Verified every existing publisher field was preserved and the one approved launch publisher received an active 0% interval. The configured post-offer rate is 30%.

The final HTTP monitor caught group-writable public files from the Git archive, which the server's PHP handler rejected. Removed group/other write permissions from this release's public directory; the API authentication endpoint and product monitor then passed. Future release extraction must normalize public file permissions before switching the API symlink. No database rollback or balance repair was needed.

Live checks passed for offer visibility and terms navigation on homepage/publisher pages in AZ, EN and RU (six combinations), public layouts across 12 routes at 320px/390px/1440px, accessibility on English public pages, 21 localized public metadata pages and three private noindex routes. No synthetic paid traffic was generated. Scheduler and monitor remain on the compatible v4 release with shared database/storage.

Authenticated live admin login, campaign/publisher review pages and support pages loaded successfully with no browser console errors. The verification session was logged out.
