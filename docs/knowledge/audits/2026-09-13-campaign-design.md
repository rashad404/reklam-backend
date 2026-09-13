# Public campaign design verification

Frontend source f76bc61. Changes: open publisher hero, red 0% commission poster, clearer advertiser action, refined interactive ad gallery, publisher-page format section, detail panels and closing action. Offer conditions and actual backend commission rules unchanged. The owner explicitly confirmed the 30% rate after six months.

Reviewed Smartbee in a rendered browser and recorded the design rationale in the new plan. Preserved Reklam.biz brand and original components; no competitor assets or claims reused.

Local build, lint and formatting passed. Both public Playwright journeys passed. Checked 12 localized public routes at 320px, 390px, 768px, 1024px and 1440px with no horizontal overflow. English public accessibility passed, including dark mode. Eighteen first-viewport checks confirmed one h1, the publisher section immediately below the header and visible join actions. All 36 language/viewport/ad-format states retained attribution below the creative without overlap. Visually reviewed the complete Azerbaijani homepage and mobile first screen.

Deployed to `/home/ugn/reklam-releases/20260913-campaign-design`. Previous publisher-hero frontend retained for rollback; backend remains publisher-offer. Production health monitor passed. Live first-viewport checks (18), banner format/attribution checks (36), dark accessibility and six localized offer-to-terms journeys passed.

Production SEO checks passed for 21 public localized pages and three private noindex routes.

Lighthouse mobile production lab scores: performance 97, accessibility 100, best-practices 100, seo 100, agentic-browsing 100. These are lab results, not field performance or ranking guarantees.
