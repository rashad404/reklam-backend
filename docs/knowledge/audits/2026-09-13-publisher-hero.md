# Publisher offer as the opening hero

Moved the existing publisher offer directly below the header on the homepage and publisher landing page in all three languages. Preserved the offer copy, fee disclosure, terms link and join action. The advertiser/ad-format section follows below; removed the redundant offer teaser. The opening title is the page's single h1 and subsequent sections use h2.

Local build, lint and formatting passed. Public overflow checks covered 12 routes at 320px, 390px and 1440px; English public accessibility checks passed. A dedicated check covered both affected pages in three languages at all three widths, verifying a single h1, the offer near the header and the join button inside the first viewport. Desktop and mobile screenshots were visually reviewed.

Frontend source `0689963` deployed to `/home/ugn/reklam-releases/20260913-publisher-hero`. Backend remains on the publisher-offer release. Previous frontend publisher-offer release retained for rollback. Production product monitor passed after deployment.

Live verification repeated all 18 first-viewport checks and the 12-route public layout/accessibility review successfully. Offer-to-terms navigation passed on both affected pages in AZ, EN and RU.
