# Applying the azdoc design research to Reklam.biz

## Source and interpretation

Found and read the saved azdoc research at `/Users/macmini/projects/azdoc/docs/how-to-detect-ai-generated-ui-ux.md` and its companion `modern-startup-website-design.md`. Its distinction matters: an "AI-generated" impression is not evidence of origin. The useful problems to inspect are generic visual combinations, weak contextual decisions and incomplete execution.

## Changes

Replaced the repeated icon-benefit and numbered-slogan sections with an open advertiser section, a distinct publisher section and practical questions. Reduced redundant hero labels, duplicate wordmarks and nested ad-preview frames. The ad selector still shows real localized Reklam.biz house ads, supports all formats and links to campaign creation.

The advertiser section names concrete offers, budget choices and ad results. The publisher section describes real placement options. Copy is shorter and more specific in AZ/EN/RU. The FAQ uses native details/summary controls and answers where ads run, whether a banner is needed, and who chooses placement. There is a compact final action instead of another large slogan panel. The existing logo, brand colors, font, workspace and backend are preserved.

No fabricated network size, customers, testimonials, guaranteed results or stock people were introduced. This is an editorial/design judgment about specificity and usability, not a claim to have measured perceived authorship.

## Verification

Production build, ESLint, formatting and whitespace checks passed. All 7 Playwright journeys passed, including the new disclosure interaction, campaign persistence, publisher embeds, moderation and support. Additional browser review covered four acquisition routes in three languages at 1440px, 390px and 320px. No overflow or missing translations; the English pages passed WCAG checks with an answer expanded. Inspected Azerbaijani desktop and Russian mobile screenshots, and checked the dark theme. Tightened FAQ spacing after visual review.

Deployed frontend release `20260913-editorial`, source commit `7bd3360`. PM2 uses its standalone server with zero restarts; database and aggregation health checks passed. The prior marketing release is retained for rollback. Backend and Kimlik login fix remain unchanged.

Live review passed for all 12 acquisition pages across AZ/EN/RU, including 320px, 390px and desktop widths, expanded FAQ content and English-page accessibility checks. No overflow or missing translations. Public canonical/hreflang/language/indexability checks passed for the 21 sitemap URLs and private noindex routes.

Live mobile Lighthouse: performance 97, accessibility 100, best practices 100, SEO 100. LCP 2.6s, CLS 0.03, TBT 0ms. These are lab measurements, not field Core Web Vitals or proof of a particular subjective impression. Local public regression tests passed again after the final FAQ spacing adjustment.
