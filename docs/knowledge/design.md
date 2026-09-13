# Reklam.biz Design Guidelines

## Brand

- Primary color: #FF3131 (red)
- Dark variant: #E01B1B
- Light variant: #FF5A5A
- Publisher accent: Green (#16A34A, #059669) for publisher-facing pages
- Logo: Red rocket/arrow icon + "REKLAM" black text + ".BIZ" red text

## Colors in Code

- Brand red: `text-[#FF3131]`, `bg-[#FF3131]`, `hover:bg-[#E01B1B]`
- Active nav links: `text-[#FF3131]`
- Publisher buttons: `!bg-green-600 hover:!bg-green-700`
- Never use brand-orange or indigo/purple as primary (those are alert.az colors)

## Typography

- Font: Inter (Google Fonts, loaded in globals.css)
- Page titles: `text-2xl font-bold`
- Section titles in cards: `text-sm font-semibold` or `text-lg font-semibold`
- Body text: default size, `text-gray-600 dark:text-gray-400`
- Small labels: `text-xs text-gray-500`

## Components

### Card
`className="card"` - white bg, rounded-2xl, border, p-6

### Primary Button
`className="btn-primary"` - red bg, white text, rounded-xl, hover lifts

### Secondary Button
`className="btn-secondary"` - bordered, red text, transparent bg

### Input Fields
`w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#FF3131] focus:border-transparent text-base`

### Stat Cards (Dashboard)
Icon in colored rounded-lg box + label (text-sm gray) + value (text-xl bold)

### Quick Action Links
`card hover-lift flex items-center gap-4` with icon box + title + subtitle

### Empty States
Centered card with icon circle + title + description + action button

## Page Structure

### With back button
Container + flex row (back arrow link + h1 title) + content

### With title + action
Container + flex justify-between (h1 + btn-primary) + content

### Narrow form pages
`max-w-xl mx-auto`

## Auth Pattern

Every protected page:
```
const { isAuthenticated, isLoading } = useAuth();
if (isLoading) return <LoadingSpinner />;
if (!isAuthenticated) return <AuthRequiredCard />;
```

Hooks BEFORE conditional returns (React rules).

Public pages with dashboard after login:
```
if (isLoading) return <LoadingSpinner />;
if (isAuthenticated) return <Dashboard />;
return <PublicLanding />;
```

## Dark Mode

Every element needs dark variants:
- Backgrounds: `bg-white dark:bg-gray-900` or `dark:bg-gray-950`
- Text: `text-gray-900 dark:text-white` (headings), `text-gray-600 dark:text-gray-400` (body)
- Borders: `border-gray-200 dark:border-gray-800`
- Subtle bg: `bg-gray-100 dark:bg-gray-800`
- Inputs: `bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700`

## i18n Rules

- ALL user-facing text must use translation keys via `useTranslations()`
- Never hardcode English, Azerbaijani, or Russian in components
- Azerbaijani MUST use proper special characters: ə, ç, ş, ı, ü, ö, ğ
- Translation files: `messages/az.json`, `messages/en.json`, `messages/ru.json`
- Add translations to ALL 3 files when adding new keys

## Icons

- Use `lucide-react` for all icons
- No emoji icons in the UI
- Common: Eye, MousePointer, TrendingUp, DollarSign, Plus, ArrowLeft, ArrowRight, Check, X, Loader2, LogIn, Pencil, BarChart3

## Landing Pages (public, before auth)

- Gradient hero section with brand color
- Feature cards with icons
- How-it-works steps with numbered circles
- CTA button at bottom
- Advertiser pages: red theme
- Publisher pages: green theme

## Status Badges

- draft: gray-100 text-gray-600
- active: green-100 text-green-700
- paused: yellow-100 text-yellow-700
- completed: blue-100 text-blue-700
- All with dark mode variants using /30 opacity

## Static Pages (help, privacy, terms, etc.)

- Simple layout: container + h1 + content sections
- Use `card` for content blocks
- All text translated
- No auth required
- Match overall site styling

## Logo in Header

Use plain `<img>` tags (not next/image) for SVG logos to avoid sizing warnings:
```html
<img src="/images/logo.svg" alt="Reklam.biz" width={130} height={32} className="dark:hidden" />
<img src="/images/logo-white.svg" alt="Reklam.biz" width={130} height={32} className="hidden dark:block" />
```

## File Structure

- Pages: `app/[lang]/section/page.tsx`
- Root pages (no i18n): `app/section/page.tsx`
- Components: `components/section/ComponentName.tsx`
- Shared UI: `components/ui/`
- Auth: `components/auth/`
- Layout: `components/layout/Header.tsx`, `Footer.tsx`
- API client: `lib/api/client.ts`
- Auth hook: `hooks/useAuth.ts`
- Translations: `messages/az.json`, `en.json`, `ru.json`
- Middleware skips `/auth/` paths (for OAuth callbacks)
