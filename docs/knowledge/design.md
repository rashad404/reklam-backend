# Reklam.biz design standard

The September 2026 redesign replaces the initial visual design. Its source of truth is frontend/app/globals.css and the shared layout components.

## Direction

Visitors should understand that Reklam.biz connects advertisers with publisher websites, then create a campaign or register a site. Be direct, confident and practical: large purposeful public typography, a working ad-format selector, and compact account screens with clear navigation.

Preserve the existing logo and red identity. Use warm public backgrounds, ink-colored type, restrained coral ad artwork and neutral working surfaces. Do not use a separate green publisher brand. Reserve semantic status colors for status.

## System

- Manrope variable font, self-hosted WOFF2 with its OFL license. Preserve Azerbaijani and Cyrillic coverage.
- Main action red #d82c40; smaller red text #c52237 for contrast. Dark theme uses lighter accents.
- Public canvas #fcfbf9, workspace canvas #f6f7f9, ink #17191f. Use CSS variables for themed surfaces and text.
- Buttons and inputs use 10px corners, cards generally 18px. Focus must be visible, controls at least 44px tall, and motion respects reduced-motion preferences.
- Public page hierarchy: composed headline and real house ad, capability row, audience choices, numbered process, final action. Inner pages adapt this hierarchy to their task.
- Authenticated screens use a persistent desktop sidebar, account menu, and responsive horizontal navigation. Keep tables, editor steps, validation, empty states and failures legible.
- Public footer explains the product and links to useful pages. Workspace footer is compact.

## Content and assets

Use localized AZ/EN/RU messages, full language-specific letters, and ASCII punctuation. Describe actual product functionality, not release status. Only payment pages say payments will be available soon.

House ads promote Reklam.biz itself in all three languages and supported banner dimensions. Their links lead to campaign creation. Do not invent customers, performance, traffic or testimonials. The shared ad renderer continues to show advertising attribution. Campaign editor previews remain previews.

## Verification

Inspect public and authenticated pages at 320px, 390px and desktop, including Russian labels, both themes, forms and long lists. Check keyboard focus, overflow, contrast and missing translations. Exercise format selection and links, campaign persistence, placement installation, moderation and support replies. Compilation alone is insufficient. Keep public canonical/hreflang metadata and private noindex intact.
