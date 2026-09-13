# Public marketing copy revision

User feedback: "Yoxlamaya göndərin" is an unnecessary marketing step. Public pages should attract advertisers and publishers rather than explain the implementation.

Rewrote the existing public text slots around advertiser customer discovery, publisher advertising revenue, control over spending/placements and measurable interest. The three numbered blocks now express reasons to join. Advertiser and publisher landing-page details no longer sell review or ownership verification as product benefits. Shared workspace instructions remain available where users need them.

Updated Azerbaijani first, then the corresponding English and Russian public messaging and descriptions. Preserved the layout, assets, styling, section order, actions and destinations. No claimed audience size, guaranteed results or payout promise. Public copy has dedicated keys where workspace wording was previously reused.

Validation: production build, ESLint, formatting and whitespace checks passed. Both public Playwright journeys passed, covering localized metadata/layouts and theme accessibility. Inspected the Azerbaijani desktop and Russian 320px full-page screenshots. Source commit: d283b2e.

Deployed frontend release 20260913-marketing. Live browser verification passed on 12 acquisition pages across AZ/EN/RU: new value-focused text visible, no previous review-step text, no mobile overflow or JavaScript errors. PM2 points to the new release and reports zero restarts.
