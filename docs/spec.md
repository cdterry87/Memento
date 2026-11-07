# Memento: Project Specification

| | |
|---|---|
| **Status** | Approved decisions recorded in [section 11](#11-decisions); repository archived and Laravel scaffolded on the `rebuild` branch |
| **Last updated** | 2026-11-06 |
| **Legacy baseline** | `master` at `ce60584` (Laravel 5.8, Vue 2, Vuetify 2) |
| **Rebuild target** | Laravel 13, Vue 3.5, Inertia 3, Vuetify 4, Laravel Sail, MySQL, Cloudflare R2 |

## 1. Summary

Memento is a private, short-form journal and memory tracker. Once a day, you record:

- your overall mood, as an emoji
- what caused it, as emojis
- optionally, a few photos, a short voice message, and a message of up to 255 characters

You can scroll back through past days, see "on this day" reminders from earlier years, and read a yearly recap of your good memories with charts of your emotions.

It is a place to reflect, not a social product. There are no friends, followers, sharing, or public pages.

This document has two parts:

- **Part 1 (sections 2 to 4)** records what the legacy app does today, including its defects, so that nothing is lost or repeated by accident.
- **Part 2 (sections 5 to 12)** specifies the rebuild.

The legacy analysis comes from reading the source. The app was not run, so its runtime behaviour is inferred from the code.

---

# Part 1: Legacy application

## 2. Stack and structure

| Area | Legacy |
|---|---|
| Backend | Laravel 5.8, PHP 7.4 in Docker (`composer.json` allows `^7.3`, README says 7.2) |
| Frontend | Vue 2.6, Vue Router 3, Vuetify 2, MDI icons, animate.css, moment, lodash, Laravel Mix 4 |
| Database | MySQL 5.7 |
| Rendering | Blade shell that mounts a Vue SPA, with auth screens as Blade forms wrapping Vue components |
| API | JSON routes in `routes/web.php` under an `/api` prefix, using session auth and CSRF |
| Files | Laravel public disk through the `storage:link` symlink |
| Dev environment | Docker Compose (app, nginx, MySQL), added in 2025 |
| Tests | Framework example tests only |
| Committed build output | `public/js/app.js` (8.2 MB), `public/css/app.css` (696 KB) |
| Other | Web manifest (`display: standalone`), favicons |

The README credits the Reflectly app as inspiration. There are 50 commits, and 11 stale Dependabot branches on the remote.

## 3. Legacy features

### 3.1 Screens

| Route | Screen | Behaviour |
|---|---|---|
| `/` | Welcome | Logo, tagline, and Login/Register links, or "Get Started!" when signed in |
| `/login` | Login | Email, password, remember me. Displays demo credentials |
| `/register` | Register | Name, email, password with confirmation (min 8 characters) |
| `/password/reset` | Password reset | Standard Laravel email reset flow |
| `/home` | Memory list | All of the user's memories, newest first, as cards with cover photo, title (cut to 20 characters), date, details (cut to 100), and mood icon. Cards fade in when scrolled into view. Has an empty state and a floating add button |
| `/memory/:id` | Memory detail | Full content, mood and reason icons, photo grid, and a speed dial for edit, emojis, cover photo, and delete |
| `/account` | Account | Sign out, edit name and email, change password |
| `*` | Not found | Message and link home |

### 3.2 Creating a memory

A full-screen, four-step wizard:

1. **Date:** date picker, defaulting to today. Any date is allowed, including future dates.
2. **Mood:** a slider across 11 moods, defaulting to "Good".
3. **Reasons:** "Why was your day good?" with a checklist of 10 reasons.
4. **Details:** required title (50 characters) and optional details (no limit).

On save, the app opens the new memory's detail page, where photos can be added.

### 3.3 Managing a memory

- Edit the title, date, and details.
- Change the mood and reasons in a dialog.
- Upload multiple photos at once. EXIF orientation is corrected on the server.
- View photos in a lightbox with wrap-around previous/next.
- Choose or clear a primary photo, which becomes the card cover.
- Delete a photo immediately, with no confirmation.
- Delete a memory, with confirmation.

### 3.4 Seed data

| Moods (11) | Reasons (10) |
|---|---|
| Exhausting, Awful, Bad, Frustrating, Okay, Good, Great, Fun, Awesome, Romantic, Exciting | Work, Family, Relationship, Friends, School, Travel, Entertainment, Personal Goals, Finances, Other |

Both lists render as MDI icon fonts, not real emoji. The seeder also creates `admin@example.com` with the password `password`.

### 3.5 Data model

```
users ──< memories >── emotions
             │  │
             │  └──< memories_reasons >── reasons
             └─────< memories_photos
```

| Table | Columns |
|---|---|
| `users` | id, name, email (unique), email_verified_at, password, remember_token, timestamps |
| `password_resets` | email, token, created_at |
| `emotions` | id, emotion, icon |
| `reasons` | id, reason, icon |
| `memories` | id, user_id, emotion_id, date, title (50, nullable), details (text, nullable), photo (string path, nullable), timestamps |
| `memories_photos` | id, memory_id, filename, timestamps |
| `memories_reasons` | id, reason_id, memory_id, timestamps |

There is no unique constraint on user and date, so a user can have several memories per day.

### 3.6 API routes

All routes require a session.

| Method and path | Action |
|---|---|
| `GET /api/emotions`, `GET /api/reasons` | List lookup data |
| `GET /api/memories` | List the user's memories, unpaginated |
| `POST /api/memories` | Create |
| `GET`, `PUT`, `DELETE /api/memories/{memory}` | Read, update, delete |
| `POST /api/memories/{id}/upload` | Upload photos |
| `POST /api/memories/{id}/photo` | Set primary photo |
| `POST /api/memories/{id}/emojis` | Update mood and reasons |
| `DELETE /api/photo/{id}` | Delete a photo |
| `GET /api/user`, `POST /api/account`, `POST /api/password` | Account |
| `GET /api/logout` | Sign out |

### 3.7 Visual design

The legacy look is a dark Vuetify theme on a teal-to-mint gradient (`#48bebe` to `#8ef3b8`) with deep-purple and teal accents. It uses Leckerli One for the logo and Fugaz One and Nunito for text.

## 4. Legacy defects

The rebuild must not repeat any of these. Line references are to `master` at `ce60584`.

### 4.1 Security and privacy

| Severity | Issue | Location |
|---|---|---|
| Critical | **Any signed-in user can delete any memory.** `destroy` has no ownership check | `MemoryController.php:131` |
| Critical | **Any signed-in user can edit any memory.** `update` and `updateEmojis` compare the session user to a `user_id` sent by the client, so sending your own ID passes | `MemoryController.php:77`, `:99` |
| Critical | **No ownership check on photo actions.** Upload, photo delete, and set-primary all accept any ID | `MemoryController.php:145`, `:171`, `:187` |
| Critical | **Private photos are public.** Files are served from `/storage/...` with no authentication | `MemoryController.php:152` |
| High | **Uploads are not validated.** Any file type or size is accepted into a web-served directory | `MemoryController.php:145` |
| High | **Deleted photos survive.** Each upload appears to write two copies (`store()` to the local disk, then `move()` to the public disk). Deleting a memory removes only the private copy, so public files stay reachable. Deleting a photo removes only the public copy | `MemoryController.php:136`, `:151-152`, `:176` |
| High | **Saving your account can return another user's data.** `Auth::user()->first()` runs a new query and returns the first row in `users` | `UserController.php:34` |
| High | **Weak account updates.** No server validation on name, email, or password, and no current password is required to change it | `UserController.php:49` |
| High | **Demo credentials ship in the product.** The default admin login is seeded and printed on the login page | `Login.vue:15`, `UserSeeder.php` |
| Medium | **Sign-out is a GET route**, so another site can trigger it | `routes/web.php:16` |
| Medium | **`photo` accepts any string**, which is then rendered as an image source | `MemoryController.php:187` |
| Medium | **Mass assignment is open:** `Memory` sets `$guarded = []` | `Memory.php` |

### 4.2 Functional and quality

- `new Date().toISOString()` picks the UTC date, so in the evening in US time zones "today" can already be tomorrow (`Home.vue:167`).
- Reasons are attached by array index plus one, which assumes sequential database IDs.
- Deleting a primary photo leaves `memories.photo` pointing at a missing file.
- `GET /api/memories` returns every row with no pagination.
- Requesting another user's memory returns an empty 200 rather than a 403 or 404.
- Unknown `/api/*` paths return the SPA HTML with a 200.
- Requests are not validated when creating or updating memories.
- `Route::resource` registers `create` and `edit` routes with no controller methods.
- The orientation fix has a broken PNG path and a catch block that never matches.
- `user-scalable=no` and `user-select: none` harm accessibility.
- The README lists the login as `admin`, but it is actually `admin@example.com`.
- Every dependency is long past end of life, including the PHP, Laravel, Vue 2, and MySQL 5.7 versions.

---

# Part 2: Rebuild specification

## 5. Goals and non-goals

### Goals

1. **Fast daily ritual.** A check-in takes under 30 seconds and works one-handed on a phone.
2. **Private by construction.** One user's data can't reach another user or the public, including media files.
3. **Rewarding to return to.** The timeline, "on this day", and the yearly recap are why people keep journaling.
4. **Extremely simple UI.** Modern, airy, light, fun, and uplifting.
5. **Minimal, current stack.** Latest Laravel, Vue, and Vuetify, with few other dependencies.
6. **Easy to extend.** Moods and reasons are data, so adding one needs no UI or analytics changes.

### Non-goals

- Friends, followers, sharing, comments, or public links
- Tailwind, Bootstrap, shadcn, or any UI or CSS framework other than Vuetify
- Long-form journaling or rich text
- Mobile apps, or a PWA with offline support. This is an online-only web app for now; a mobile app is a possible future addition
- Push or SMS notifications (email only)
- AI features, third-party analytics, or trackers

### Legacy feature disposition

| Legacy feature | Decision | Reason |
|---|---|---|
| Accounts, login, registration, password reset, profile | Keep | Rebuilt on Fortify, and registration is off in demo mode |
| Vuetify | Keep, upgraded | Vuetify 4, restyled from dark to a light, airy theme with optional dark mode |
| Slider for mood | Replace | A tap on an emoji grid is faster and clearer |
| MDI icons for moods and reasons | Replace | The brief calls for real emoji. MDI remains for interface icons only |
| Date as first wizard step | Change | Defaults silently to today; other days are reached from the timeline |
| Title field | Remove | The brief describes a single short message |
| Unlimited details | Change | Limited to 255 characters |
| Multiple entries per day | Change | One check-in per day, editable |
| Primary photo picker | Simplify | The first photo is the cover, and photos can be reordered |
| Card scroll animations | Keep, lighter | Native `IntersectionObserver` and CSS |
| Web manifest | Keep | Icons and home-screen name only, with no service worker |
| Voice messages, "on this day", yearly recap, daily quotes, email reminders, demo mode, data export, dark mode | **New** | Not in the legacy app |

## 6. Features

### 6.1 Today (home screen)

The screen a signed-in user lands on, in this order:

1. **Greeting and date**, such as "Good morning, Sam" and "Friday, November 6".
2. **Check-in card.** If there is no entry yet, shows "How's today going?" and the emoji grid, so the first tap has already started the check-in. Otherwise shows today's entry with an edit action.
3. **On this day**, only when there are matching memories.
4. **Daily quote**, unless hidden in Account settings.
5. **Recap banner**, only during the recap window (see 6.5).

### 6.2 Daily check-in

A short flow with one question per screen, a visible progress indicator, and auto-advance where possible.

| Step | Prompt | Input | Rules |
|---|---|---|---|
| 1 | "How was your day?" | Emoji grid, one choice. Selecting advances automatically | **Required** |
| 2 | "What made it that way?" | Emoji grid, multiple choice | Optional |
| 3 | "Anything to remember?" | Message box with a live counter, plus add-photo and record-voice buttons | All optional. The message is gently encouraged by placeholder text, never enforced |

- The save button is always reachable after step 1, so a mood-only check-in takes one or two taps.
- The label of a selected emoji is shown as a caption ("Great") and is always available to screen readers.
- Saving ends with a small celebratory animation, which respects `prefers-reduced-motion`.
- An unsaved message is kept as a local draft, so an accidental refresh doesn't lose it.

**Rules**

| Rule | Value |
|---|---|
| Entries per day | One per user, per calendar date |
| Message | 255 characters maximum, counted by Unicode code point on both client and server (`[...text].length` in JS, since `.length` would count most emoji twice) |
| Photos | Up to 4 per day; JPEG, PNG, or WebP; 10 MB each |
| Voice | One per day; 60 seconds maximum; 5 MB maximum |
| Dates | Today or any past date. Future dates are rejected. The server decides "today" using the user's time zone |

**Saving.** The check-in is saved first, then each file uploads separately with its own progress and retry. A failed upload on a bad connection therefore never loses the entry.

### 6.3 Photos

- Files are stored on a **private** disk and served only through an authorized route (section 8.4).
- On upload, the server applies EXIF orientation, **strips all metadata including GPS location**, resizes to a 2048px maximum edge, and creates a 640px thumbnail.
- Files get random names, and the original filename is discarded.
- Viewing uses a custom lightbox with swipe and keyboard support.
- Deleting a photo asks for confirmation and removes every file variant.
- HEIC must be tested on real iPhones. Safari usually converts on upload, but that needs confirming. If it doesn't, HEIC support requires Imagick with libheif.

### 6.4 Voice messages

- Recording uses the native `MediaRecorder` API with a custom UI: tap to record, a live timer, automatic stop at 60 seconds, then preview, re-record, or keep.
- **Format negotiation** via `MediaRecorder.isTypeSupported`: prefer `audio/mp4` (AAC), since it plays on every target browser, then fall back to `audio/webm;codecs=opus`. No server transcoding is planned, which avoids an ffmpeg dependency.
- Playback uses a custom player on top of a native `<audio>` element.
- Audio delivery must support **HTTP Range requests**, which Safari needs for playback. R2 signed URLs provide this (section 8.4).
- If microphone permission is denied or `MediaRecorder` is unavailable, the app shows a friendly message and the rest of the check-in still works.
- Microphone access requires HTTPS in production. `localhost` is exempt during development.

### 6.5 Timeline, on this day, recap

**Timeline**

- Infinite scroll, newest first, grouped under month headings, using cursor pagination (20 per page).
- Only days with entries appear, with no gaps or guilt-inducing empty days.
- Each card shows the mood emoji, reason emojis, message, a photo thumbnail strip, and a voice indicator.
- Tapping a card opens the day view, where everything can be viewed, edited, or deleted.
- The empty state invites the first check-in.

**On this day**

- Shows entries from the same month and day in **earlier years**, newest first, labelled "1 year ago", "3 years ago", and so on.
- Displayed as swipeable cards on the Today screen. Hidden entirely when there is nothing to show.
- On February 28 in non-leap years, entries from February 29 are included.
- Also available as an optional email (section 6.8).

**Yearly recap** at `/recap/{year}`

- Available for any year with entries. The current year counts as year-to-date.
- Promoted on the Today screen from December 26 to January 31.
- Designed as a warm scrolling story, with an uplifting tone even in a difficult year.

| Section | Content | Display |
|---|---|---|
| The year in numbers | Check-ins, longest streak, photos, voice messages | Stat tiles |
| Your year in moods | The mood of every day of the year | "Year in pixels" calendar grid; tapping a day opens it |
| How the year felt | Average mood score per month | Line or area chart |
| Mood mix | Share of each mood | Horizontal bars with emoji labels |
| What shaped your year | Most-selected reasons | Ranked bars |
| What lifted you | Reasons most associated with high-scoring days, shown only with enough data | Short list |
| Best month | Highest average mood score | Highlight card |
| Your good memories | Up to 12 entries scoring 4 or 5, preferring those with photos or a message, spread across the year | Photo-led cards |

Charts are **hand-built SVG Vue components**, since Vuetify has no general chart components. This avoids a chart library, keeps the bundle small, and gives full control of the look in both themes. Each chart needs a text alternative.

Recap figures are computed on request, since one user's year is at most 366 rows. Add caching only if measurements call for it.

### 6.6 Daily quote

- One short inspirational quote per day, with attribution, shown on the Today screen.
- Everyone sees the quote for the date: `quotes[dayOfYear % count]`.
- Stored in the database and seeded from a curated list of about 366 quotes. **No external API**, so the app sends nothing to third parties and works without another service.
- Use public-domain or original quotes only, and check attributions, since misattributed quotes are common.
- Users can hide quotes with a setting in Account (`users.show_quotes`, on by default).

### 6.7 Accounts

- Register, sign in, sign out (POST), forgot and reset password, and remember me.
- **Public registration is on, unless demo mode is enabled** (section 6.9).
- **Email verification is required**, because registration is public and reminders go by email.
- Edit name and email, and change password (the current password is required).
- Time zone is detected from the browser at registration and editable in Account.
- Account settings: appearance (system, light, dark), show quotes, and email preferences.
- **Export data:** a queued job builds a ZIP of JSON and media files, then emails a signed link that expires after 24 hours. The ZIP is deleted after expiry.
- **Delete account:** requires confirmation and password re-entry, and removes all database rows **and all media files**.
- Because the app is public, a short privacy page explains what is stored and how to export or delete it.
- Nothing is seeded in production except lookup data and quotes, plus the demo user when demo mode is on.

### 6.8 Email notifications

Email is the only notification channel. Both emails are **opt-in and off by default**.

| Email | Sent | Content |
|---|---|---|
| Daily check-in reminder | At the user's chosen local time (default 8 PM), only if there's no entry yet that day | A short nudge and a link to check in |
| On this day | At 8 AM local time, only if there are matches | "You have memories from this day" and a link |

- **Email bodies contain no journal content**, photos, or moods, because email is not private. They only link back into the app.
- Every email has a one-click unsubscribe link, and preferences live in Account settings.
- Only verified addresses receive email, and the demo account never does.
- An hourly scheduled command finds users whose local time matches, and queues the emails. A `notification_sends` record prevents duplicates, including around daylight saving changes.
- The queue uses Laravel's `database` driver. Production therefore needs a scheduler and a queue worker, along with a mail provider (still to be chosen).

### 6.9 Demo mode

Enabled with `DEMO_MODE=true`. It lets anyone try the app without creating an account.

**When demo mode is on**

- Public registration is **hidden and disabled**. The Fortify feature is turned off, so the routes don't exist, rather than just removing the link.
- The login page shows a "Try the demo" button that signs in as the demo user. No credentials are displayed.
- A small banner tells demo users that changes reset nightly.
- The owner's own account keeps working normally, and a nightly reset never touches it.

**Demo account restrictions**

Everyone shares this account, so the following are blocked on the server, not just hidden:

- Changing the name, email, or password
- Deleting the account or exporting data
- Enabling emails
- **Uploading photos or voice messages.** Otherwise, anonymous visitors could put arbitrary files in the storage bucket. Seeded media can still be viewed, and the upload controls explain why they're unavailable.

Creating and editing check-ins with moods, reasons, and messages is allowed. Messages are visible to other demo visitors until the reset, and the banner says so.

**Seeded data and the nightly reset**

- `php artisan demo:reset` runs nightly from the scheduler, and only when demo mode is on.
- It deletes the demo user's entries and media, then regenerates them **relative to the current date**.
- It covers more than two years, so that "on this day" and the recap always have something to show, and leaves today empty, so visitors can try the check-in.
- Data includes realistic gaps, a mix of moods, and varied messages.
- Photos and voice clips come from a small set of bundled, license-free sample files.
- The command refuses to run unless demo mode is on, and it acts only on the single demo user.

## 7. Design

### 7.1 Principles

- **Airy:** generous whitespace, one idea per screen, and few visible controls.
- **Light and uplifting:** soft pastel gradients on near-white, with big joyful emoji as the main decoration.
- **Fun but calm:** springy micro-interactions on selection and completion, never in the way.
- **Mobile first:** a single column, thumb-reachable actions, and a centred column of about 560px on desktop. The recap can widen for charts.

### 7.2 Vuetify theme

Vuetify 4 provides the components and theming. The default Material look is deliberately overridden through theme and component defaults, so that the app feels soft and airy, not like a stock Material app.

Starting values for `createVuetify`:

```ts
const light = {
  dark: false,
  colors: {
    background: '#fbfaff',
    surface: '#ffffff',
    primary: '#6c5ce7',
    secondary: '#48bebe', // legacy nod
    error: '#d64545',
    'on-background': '#2b2a3d',
    'on-surface': '#2b2a3d',
    'mood-1': '#8fa8d8',
    'mood-2': '#a9b8e8',
    'mood-3': '#d9d4ee',
    'mood-4': '#ffd27d',
    'mood-5': '#ffa96b',
  },
}

const dark = {
  dark: true,
  colors: {
    background: '#17162b',
    surface: '#232240',
    primary: '#a99ffa',
    secondary: '#6fd6d0',
    error: '#ff8a8a',
    'on-background': '#f3f1ff',
    'on-surface': '#f3f1ff',
    'mood-1': '#6d86ba',
    'mood-2': '#8695c9',
    'mood-3': '#a9a4c6',
    'mood-4': '#e6b95f',
    'mood-5': '#f09252',
  },
}

const defaults = {
  VBtn: { rounded: 'pill', elevation: 0 },
  VCard: { rounded: 'xl', elevation: 0 },
  VTextField: { variant: 'solo-filled', flat: true, rounded: 'lg' },
  VTextarea: { variant: 'solo-filled', flat: true, rounded: 'lg' },
}
```

**Dark mode**

- Users choose system, light, or dark in Account. The default is system.
- The choice is stored in `users.theme` so that it follows them across devices.
- The theme is applied before the first paint to prevent a flash of the wrong colours.
- Page backgrounds use a soft pastel gradient in light mode, and a deep, muted equivalent in dark mode.

### 7.3 Styling approach

- Vuetify theme and component defaults come first, then Vuetify's SASS variables if needed. Scoped component CSS covers what remains, with no `!important` overrides.
- **Tailwind and other CSS frameworks are not used.** Vuetify's utility classes are acceptable for simple spacing.
- `vite-plugin-vuetify` auto-imports and tree-shakes components, so only those in use ship.
- Interface icons use tree-shaken SVG from `@mdi/js`, not the full icon font.
- Fonts are **self-hosted**, so that opening a private journal doesn't call Google. Body text uses Nunito, and the logo keeps Leckerli One for continuity with the legacy brand.
- Emoji use the native system font. They will look different per platform, which is acceptable and adds no dependency.

### 7.4 Core components

Vuetify provides the foundations: `VApp`, `VBottomNavigation`, `VBtn`, `VCard`, `VTextField`, `VTextarea`, `VDialog`, `VSnackbar`, `VInfiniteScroll`, `VProgressLinear`, `VSwitch`, and `VSelect`.

Custom components are built on top of them: `EmojiPicker`, `MessageBox`, `PhotoPicker`, `PhotoLightbox`, `VoiceRecorder`, `VoicePlayer`, `MemoryCard`, `OnThisDayCarousel`, `QuoteCard`, `DemoBanner`, and the SVG charts `YearInPixels`, `TrendChart`, and `BarList`.

### 7.5 Accessibility

- WCAG 2.2 AA as the baseline, including 4.5:1 text contrast (verify the theme colours above before finalizing them).
- Emoji pickers are real radio or checkbox groups with text labels and full keyboard support.
- Both the light and dark themes must meet the contrast requirements.
- Tap targets are at least 44×44px, with visible focus states.
- Motion respects `prefers-reduced-motion`.
- Zoom and text selection are never disabled.

## 8. Technical architecture

### 8.1 Stack

Versions were checked against Packagist and npm on 2026-11-06. Pin the current releases at scaffold time.

| Layer | Choice | Version |
|---|---|---|
| PHP | | 8.5 (Laravel requires at least 8.3) |
| Framework | Laravel | 13.x |
| Frontend | Vue with `<script setup>` and TypeScript | 3.5.x |
| App glue | Inertia (`inertia-laravel`, `@inertiajs/vue3`) | 3.x |
| UI | Vuetify, `vite-plugin-vuetify`, `@mdi/js` | 4.x, 2.x, 7.x |
| Build | Vite, `laravel-vite-plugin`, `@vitejs/plugin-vue` | 8.x, 3.x, 6.x |
| Auth | Laravel Fortify (headless) | 1.x |
| Images | Intervention Image (`intervention/image-laravel`) | 4.x |
| Database | MySQL, in the Sail container locally | 8.4 |
| Media storage | Cloudflare R2 through Laravel's S3 driver (`league/flysystem-aws-s3-v3`) | 3.x |
| Local environment | Laravel Sail with MySQL, Mailpit, and RustFS | 1.x, PHP 8.5 runtime |
| Queue and scheduler | Laravel `database` queue and scheduler | |
| PHP quality | Pest, Pint, Larastan | Pest 5.x |
| JS quality | Vitest, Vue Test Utils, ESLint, Prettier, `vue-tsc` | current |
| Browser tests | Playwright, for key flows and media on WebKit | current |

**Why Inertia:** it gives a Vue SPA experience with Laravel routing, controllers, session auth, validation, and CSRF, without a separate API layer or token handling. Vue Router, Pinia, and Sanctum are unnecessary as a result. If a mobile app follows, the actions and services in section 8.6 can back a new API.

**Don't use the Laravel Vue starter kit:** it brings Tailwind and shadcn-vue. Start from the plain Laravel skeleton and add Vuetify.

**Deliberately excluded:** Tailwind and other CSS frameworks, chart libraries, moment and lodash (use `Intl` and native JS), Vue Router, Pinia, Sanctum, and Redis.

### 8.2 Data model

```
users ──< memories >── moods
            │  │  │
            │  │  └──< memory_reason >── reasons
            │  └─────< memory_photos
            └────────── voice_notes (0 or 1)

quotes (standalone)
```

| Table | Columns | Constraints and notes |
|---|---|---|
| `users` | Laravel defaults, plus `timezone` (default `UTC`), `theme` (`system`, `light`, `dark`), `show_quotes` (default true), `reminder_emails` (default false), `reminder_time` (default 20:00), `on_this_day_emails` (default false), `is_demo` (default false) | Only the seeder sets `is_demo` |
| `moods` | id, `slug`, `label`, `emoji`, `score` (1 to 5), `position`, `is_active` | Unique `slug` |
| `reasons` | id, `slug`, `label`, `emoji`, `position`, `is_active` | Unique `slug` |
| `memories` | id, `user_id`, `mood_id`, `date`, `body` (varchar 255, nullable), timestamps | **Unique (`user_id`, `date`)**. `user_id` cascades on delete. `mood_id` restricts delete |
| `memory_reason` | `memory_id`, `reason_id` | Composite primary key, cascades from memory |
| `memory_photos` | id, `memory_id`, `path`, `thumbnail_path`, `width`, `height`, `size_bytes`, `position`, timestamps | Index (`memory_id`, `position`). Lowest position is the cover |
| `voice_notes` | id, `memory_id` (unique), `path`, `mime_type`, `duration_seconds`, `size_bytes`, timestamps | |
| `quotes` | id, `text`, `author` | |
| `notification_sends` | id, `user_id`, `type`, `local_date`, timestamps | Unique (`user_id`, `type`, `local_date`), prevents duplicate emails |

Fortify and Laravel's defaults add the remaining framework tables (`password_reset_tokens`, `sessions`, `jobs`, and so on).

**Notes**

- `date` is the user's local calendar date, with no time zone conversion. Don't derive it from `created_at`.
- `score` is new. The legacy list is not a linear scale, so analytics need an explicit value.
- Lookup rows are matched by `slug`, and code never assumes IDs.
- Database cascades don't fire model events, so **file cleanup is handled explicitly** by a `DeleteMemory` action and the account deletion path, not by foreign keys.
- `path` columns hold object keys relative to the `media` disk, never full URLs, so that the storage provider can change.

### 8.3 Moods and reasons

**Adding a mood or reason is a data change, not a code change:**

1. Add a row to `database/data/moods.php` or `database/data/reasons.php`.
2. Run the idempotent `LookupSeeder`, which upserts by `slug` and is safe in production.

Everything else follows automatically:

- Pickers render from the database, and their grids wrap to any number of items.
- Analytics and charts group by `id` and `score`. Chart colours come from a mood's score, not its identity, so a new mood needs no new colour.
- To retire an option, set `is_active` to false. It disappears from pickers but stays on past entries and in past recaps. Rows are never deleted.
- A test confirms that an added mood appears in the picker and recap without other changes.

Moods (emoji are proposed):

| Slug | Emoji | Score | | Slug | Emoji | Score |
|---|---|---|---|---|---|---|
| `awful` | 😭 | 1 | | `good` | 🙂 | 4 |
| `bad` | 😞 | 2 | | `great` | 😄 | 5 |
| `exhausting` | 😵 | 2 | | `fun` | 😜 | 5 |
| `frustrating` | 😠 | 2 | | `awesome` | 😎 | 5 |
| `okay` | 😐 | 3 | | `romantic` | 😍 | 5 |
| `energetic` | ⚡ | 5 | | `exciting` | 🤩 | 5 |

Reasons (emoji are proposed):

| Slug | Emoji | | Slug | Emoji |
|---|---|---|---|---|
| `work` | 💼 | | `travel` | 🌍 |
| `family` | 🏠 | | `entertainment` | 🎬 |
| `relationship` | 💕 | | `personal-goals` | 🎯 |
| `friends` | 🤝 | | `finances` | 💰 |
| `school` | 🎓 | | `health` | 💪 |
| `other` | ✨ | | | |

### 8.4 Security and privacy requirements

These respond directly to section 4.

1. **Ownership enforced on the server for every record.** Queries are scoped through `$request->user()->memories()`, and photo and voice routes use policies. A record belonging to someone else returns **404**, so its existence isn't revealed. Client-supplied user IDs are never read.
2. **An automated test proves this for every route:** user B gets a 404 on each of user A's records and media.
3. **Media lives in a private R2 bucket with no public access**, on a Laravel disk named `media`. There is no `storage:link` for user content.
4. **Media routes authorize, then redirect to a short-lived signed URL** (`Storage::temporaryUrl`, 10 minutes), with `Cache-Control: private, no-store` on the redirect. R2 serves the file directly and supports Range requests, so large files don't pass through PHP.
5. **Validate every request with Form Requests.** Check uploads by real content type, not by extension. Re-encoding images also neutralizes malicious files.
6. **Audio MIME handling needs care:** content sniffing may report WebM or MP4 audio as `video/webm` or `video/mp4`. The allow-list must account for this, and the stored `mime_type` must be normalized to an audio type.
7. **Duration limits** are enforced in the recorder. The server's hard limit is file size.
8. **Rate limiting** on login, password reset, and uploads.
9. **Mass assignment:** explicit `$fillable`, excluding `user_id`.
10. **State changes** use POST, PUT, PATCH, or DELETE only, with CSRF protection.
11. **Production:** HTTPS only, secure and HttpOnly cookies, HSTS, a Content Security Policy, and `noindex` for all app pages.
12. **Backups** must include both the database and the R2 bucket. Losing either one loses people's memories.
13. **Public registration protections:** email verification, rate limits on registration, and bounded storage per user (the daily limits in section 6.2 already cap this).
14. **Demo restrictions** are enforced by a policy or middleware on the server, based on `is_demo`, as listed in section 6.9.

Journal content relies on encryption at rest provided by the database host and R2. Application-level encryption is not planned.

### 8.5 Routes

Web routes return Inertia pages. Memories are addressed by date, for example `/days/2026-11-06`, and always resolve through the signed-in user. This makes the URLs readable and makes cross-user access impossible by construction.

| Method and path | Purpose |
|---|---|
| `GET /` | Welcome for guests, redirect to `/today` for users |
| `GET /today` | Today screen |
| `GET /timeline` | Timeline, cursor paginated |
| `GET /days/{date}` | Day view |
| `GET /days/{date}/check-in` | Create or edit flow |
| `PUT /days/{date}` | Create or update (idempotent) |
| `DELETE /days/{date}` | Delete the entry and its files |
| `POST /days/{date}/photos` | Upload one photo |
| `PATCH /days/{date}/photos/order` | Reorder photos |
| `DELETE /days/{date}/photos/{photo}` | Delete a photo |
| `PUT /days/{date}/voice`, `DELETE /days/{date}/voice` | Replace or delete the voice note |
| `GET /media/photos/{photo}/{variant}` | Authorize, then redirect to a signed URL for `full` or `thumb` |
| `GET /media/voice/{voiceNote}` | Authorize, then redirect to a signed URL |
| `GET /recap`, `GET /recap/{year}` | Redirect to the latest year, or show a year |
| `GET /account`, `PATCH /account/preferences` | Settings, including theme, quotes, and emails |
| `POST /account/export` | Queue a data export |
| `GET /account/export/{export}` | Download an export (signed, expires) |
| `DELETE /account` | Delete account |
| `GET /unsubscribe/{user}/{type}` | One-click email unsubscribe (signed) |
| `POST /demo` | Sign in as the demo user (only when demo mode is on) |
| `GET /privacy` | Privacy page |
| Fortify routes | Login, register, logout, reset, profile, password |

### 8.6 Application structure

```
app/
  Actions/           StoreMemory, DeleteMemory, StorePhoto, StoreVoiceNote, DeleteAccount
  Console/Commands/  SendDailyEmails, ResetDemo
  Jobs/              BuildDataExport
  Notifications/     CheckInReminder, OnThisDayReminder, DataExportReady
  Http/Controllers/  TodayController, TimelineController, DayController,
                     PhotoController, VoiceNoteController, MediaController,
                     RecapController, AccountController, DemoController
  Http/Requests/     One Form Request per write
  Http/Resources/    API Resources shaping every Inertia prop
  Models/            User, Memory, Mood, Reason, MemoryPhoto, VoiceNote, Quote
  Policies/          MemoryPolicy, MemoryPhotoPolicy, VoiceNotePolicy
  Services/          RecapBuilder, OnThisDay, DailyQuote, ImageProcessor, DemoSeeder
resources/
  css/               app.css with a few global overrides
  js/
    app.ts
    plugins/         vuetify.ts (themes and defaults)
    pages/           Today, Timeline, Day, CheckIn, Recap, Account, auth/*
    components/      See section 7.4
    composables/     useRecorder, useUpload, useDraft, useLocalDate, useAppTheme
    layouts/         AppLayout, GuestLayout
    types/
```

**Conventions:** thin controllers, business logic in actions and services, API Resources for every prop so that nothing leaks by default, and Composition API with TypeScript throughout.

### 8.7 Web only

- This is an online-only web app. There is no service worker, offline mode, or push notifications.
- The web manifest and icons carry over and are refreshed, so that adding it to a phone's home screen looks right.

### 8.8 Local development and deployment

**Local development with Laravel Sail**

- Services: the Laravel app (PHP 8.5 runtime), `mysql` (8.4), `mailpit` for reading email locally, and `rustfs` as an S3-compatible stand-in for R2.
- Using RustFS means local signed URLs and Range requests work the same way as on R2, with one code path. The URLs must use a host the browser can reach, not the container's internal hostname.
- Run everything through Sail: `sail artisan`, `sail composer`, `sail npm`, and `sail test`.
- Tests use Sail's dedicated MySQL `testing` database, matching production, with `Storage::fake` for media.
- The legacy `Dockerfile`, `docker-compose.yml`, and `docker/` are removed with the rest of the old code.

**Cloudflare R2**

- Configure the `media` disk with the `s3` driver, region `auto`, the R2 endpoint, and a private bucket.
- API credentials are limited to that bucket and live only in the environment.
- The app host is not chosen yet. It needs PHP 8.5, MySQL, HTTPS, a scheduler, and a queue worker. Local file storage isn't required, since media lives on R2, except for temporary processing.

**General**

- Required PHP extensions: `pdo_mysql`, `gd` or `imagick`, `exif`, `mbstring`.
- Upload limits must agree across the web server, PHP (`upload_max_filesize`, `post_max_size`), and validation. Set them to 12 MB per request, since files upload individually.
- `public/build` is gitignored, and no compiled assets are committed.
- GitHub Actions runs Pint, Larastan, Pest with a MySQL service, ESLint, `vue-tsc`, Vitest, and a production build on every pull request. Dependabot stays on.

Environment settings specific to this app:

```dotenv
DEMO_MODE=false
FILESYSTEM_DISK=local
MEDIA_DISK=media
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=
QUEUE_CONNECTION=database
```

---

## 9. Testing and acceptance

### Test requirements

- **Authorization:** the cross-user test from section 8.4, covering every route.
- **Check-in:** create, update, the one-per-day rule, 255-character boundary (including emoji), rejection of future dates, and the correct "today" across time zones.
- **Media:** rejection of invalid types and sizes, EXIF and GPS removal, file limits, and **no file left behind** after deleting a photo, voice note, memory, or account.
- **On this day:** multi-year matches, the February 29 rule, and exclusion of the current year.
- **Recap:** streak calculation, empty and sparse years, year boundaries, and leap years.
- **Moods and reasons:** a newly seeded mood or reason appears everywhere, and a retired one disappears from pickers but stays on old entries.
- **Email:** opt-in is respected, sends match the user's local time, no duplicates occur across daylight saving changes, unverified and demo users are skipped, unsubscribe works, and no journal content appears in an email.
- **Demo mode:** registration routes are absent when it's on, each restricted action is refused for the demo user, and the reset leaves other users' data untouched and refuses to run when demo mode is off.
- **Settings and export:** hiding quotes works, the theme preference persists, and an export contains all of a user's data and only that user's data.
- **Browser:** the complete check-in with photo and voice in Chromium and WebKit, plus manual checks on a real iPhone and Android phone.

### Release acceptance criteria

- [ ] A one-tap mood check-in saves in a single flow from the Today screen
- [ ] A complete check-in with photos, voice, and message works on iOS Safari and Android Chrome
- [ ] A voice note recorded on one platform plays on the other
- [ ] One entry per day is enforced by the database
- [ ] Past days can be scrolled, opened, edited, and deleted
- [ ] "On this day" appears only when there are matches
- [ ] Recap works for full, partial, and sparse years
- [ ] A new quote appears each day, and hiding quotes removes it
- [ ] An unauthenticated request to any media URL is refused
- [ ] Cross-user access returns 404 on every route
- [ ] Uploaded photos contain no EXIF or GPS data
- [ ] `package.json` contains no UI or CSS framework other than Vuetify
- [ ] Light, dark, and system themes all work with no flash of the wrong theme
- [ ] Reminder and on-this-day emails respect opt-in and contain no journal content
- [ ] Data export and account deletion both work, and deletion leaves no files in R2
- [ ] With demo mode on, registration is unavailable and the nightly reset restores fresh demo data
- [ ] Adding a mood or reason requires only a data file entry and a seeder run
- [ ] WCAG 2.2 AA checks pass on all primary screens, in both themes
- [ ] All CI checks pass

## 10. Delivery plan

### 10.1 Archive and reset the repository

**Completed 2026-11-06.** Recorded for reference.

```bash
# 1. Commit this spec on master and push
git switch master
git add docs/spec.md
git commit -m "Add project specification"
git push origin master

# 2. Rename master to main, locally and on GitHub
gh api -X POST repos/cdterry87/Memento/branches/master/rename -f new_name=main
git fetch origin --prune
git branch -m master main
git branch -u origin/main main
git remote set-head origin -a

# 3. Archive the legacy code
git branch archive-2019
git push origin archive-2019

# 4. Work on a rebuild branch
git switch -c rebuild

# 5. Clear everything except docs
git ls-files | grep -v '^docs/' | xargs git rm -q --
git commit -m "Clear legacy application for rebuild"

# 6. Scaffold in a temp directory, since create-project needs an empty target
composer create-project laravel/laravel /tmp/memento-new
rsync -a --exclude .git /tmp/memento-new/ ./
composer require laravel/sail --dev
php artisan sail:install --with=mysql,mailpit,rustfs
```

The rebuild merges into `main` through a pull request. This keeps the full history, and the old app stays available on `archive-2019`.

Things to note:

- The 11 stale Dependabot branches were deleted, which closed their open pull requests.
- Tailwind, which the Laravel skeleton includes, was removed from `package.json` and `vite.config.js`.
- Default ports can clash with other local Docker projects. Override them in `.env` with `APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, and `VITE_PORT`.
- The 8 MB compiled bundles remain in Git history, which is harmless.

### 10.2 Milestones

| # | Milestone | Scope |
|---|---|---|
| 0 | Archive and scaffold | Section 10.1, Laravel, Sail, Vue, Inertia, Vuetify, TypeScript, CI, linting |
| 1 | Design foundation | Vuetify themes and defaults, dark mode, fonts, layouts |
| 2 | Accounts | Fortify, email verification, auth screens, account page, time zones |
| 3 | Check-in core | Schema, data-driven moods and reasons, check-in flow, message, policies, and the cross-user test suite |
| 4 | Timeline and day view | Infinite scroll, day view, edit, delete |
| 5 | Photos | R2 storage, signed URLs, processing, upload, lightbox, reorder |
| 6 | Voice | Recorder, player, cross-device testing |
| 7 | Today | Quotes and hide setting, on this day, final home screen |
| 8 | Recap | Builder service, SVG charts, story layout |
| 9 | Email | Queue, scheduler, reminders, on-this-day email, unsubscribe |
| 10 | Demo mode | Demo flag, restrictions, seeded data, nightly reset |
| 11 | Release polish | Export, account deletion, privacy page, accessibility audit, performance, deployment |

Voice is the highest-risk item because of browser differences, so its cross-device check should happen early in milestone 6, before building the full UI.

## 11. Decisions

### Confirmed

| # | Topic | Decision |
|---|---|---|
| 1 | Entries per day | One, editable |
| 2 | Backfilling | Any past date, never future |
| 3 | Moods | The 11 legacy moods plus Energetic, and easy to expand (section 8.3) |
| 4 | Emoji and scores | As in section 8.3 |
| 5 | Reasons | The 10 legacy reasons plus Health |
| 6 | Custom reasons per user | Not in the first release |
| 7 | Limits | 4 photos, 60-second voice message, 255-character message |
| 8 | Notifications | Email only, with a mobile app as a possible future addition |
| 9 | Recap timing | Promoted December 26 to January 31; past years always available |
| 10 | Good memories | Mood score 4 or 5, up to 12 |
| 11 | Quotes | Shown by default, with a user setting to hide them |
| 12 | Data rights | Both account deletion and data export at launch |
| 13 | Users | Owner account, public registration, and an optional demo mode with a nightly reset; registration hidden and disabled in demo mode |
| 14 | Existing data | Start fresh, with no import |
| 16 | Legacy deployment | Not deployed anywhere |
| 17 | Frontend | Inertia and Vuetify. **This replaces the original "custom styling, no UI libraries" direction** |
| 18 | Language | TypeScript |
| 19 | Database and local environment | MySQL, with Laravel Sail for local development |
| 20 | Encryption | Host-level at rest, with no application-level encryption |
| 21 | Hosting | App host to be decided; Cloudflare R2 for file storage |
| 22 | Connectivity | Web app only, online required |
| 23 | Branding | Keep the Memento name and Leckerli One logo |
| 24 | Colours | Soft pastels on near-white with a purple accent |
| 25 | Dark mode | Included, with system, light, and dark options |
| 26 | Default branch | Rename `master` to `main` |
| 27 | Archive | `archive-2019` branch from `main` |
| 28 | Merge path | Rebuild on a branch and merge by pull request |
| 29 | Dependabot branches | Delete the 11 stale branches |
| 30 | Spec | Commit and push before archiving |

### Assumptions to confirm

Choices made while applying the decisions above:

| Topic | Assumption | Reason |
|---|---|---|
| Demo uploads | Demo visitors can't upload photos or voice messages | Anonymous uploads to the bucket invite abuse |
| Demo entry | A "Try the demo" button, with no credentials shown | Simpler, and avoids repeating the legacy pattern |
| Emails | Both opt-in, off by default, and free of journal content | Email isn't private |
| Email verification | Required for all registered users | Needed for public registration and reminders |
| Energetic | ⚡ with a score of 5 | Fits the positive end of the scale |

### Still to choose

- App hosting provider
- Mail provider

## 12. Future ideas

Not part of the first release:

- A mobile app, backed by a new API
- Search and mood filtering
- A calendar view
- Custom reasons per user
- Gentle streaks, without penalties
- A shareable recap image, generated by you and saved to your device, which would need care given the no-sharing stance
