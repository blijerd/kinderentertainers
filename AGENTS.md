# AGENTS.md — Kinderentertainers.nl

## Purpose

This document is the instruction file for developers and AI agents working on Kinderentertainers.nl.

The platform is a Laravel + Filament + PostgreSQL booking product. It deploys as Docker with `public_html/` as the webroot. Architecture always wins over shortcuts.

Do not copy Blijwin® OS product rules (CMS, Visma, Grav, tracking, Coolify SSH diagnostics) into this repository. Do copy the Laravel architecture below.

---

# TL;DR — Non-negotiable rules

1. Do not place business logic inside Livewire components, Blade views, Filament resources or controllers.
2. Status changes must always go through dedicated Actions with `handle()`.
3. Functional business data belongs in relational tables, not JSON, unless the column is explicitly metadata/logs.
4. Public URLs must never expose incremental database IDs. Use `public_id`, slugs or secure tokens.
5. Laravel backend is always the source of truth.
6. Production must not depend on manual SSH sessions or one-off artisan commands.
7. Integrations belong in Services, Jobs or Events/Listeners — never in Filament, Livewire, controllers or Blade.
8. Important domain changes require tests. Significant releases update README.md and CHANGELOG.md.
9. New or changed database code must be tested against PostgreSQL. Do not change application code solely to support SQLite.
10. Code, comments and technical docs are English. User-facing copy is Dutch.
11. New tables need factories, seeders or explicit test fixtures.
12. SoftDeletes are the default for commercial records (users, entertainers, booking requests, matches, reviews).
13. Temporary debug files must be removed before a change is done.

---

# Tech stack

- PHP `^8.3`
- Laravel `^13`
- PostgreSQL
- Blade, Livewire, Tailwind, Alpine
- Filament `^5`

Do not introduce a React/Vue SPA, microservices or Grav.

---

# Separation of responsibilities

| Responsibility | Layer |
|---|---|
| Domain logic | Actions (`handle()` + constructor DI) |
| Integrations | Services |
| Async processing | Jobs |
| Authorization | Policies |
| API formatting | API Resources |
| Domain events | Events/Listeners |

Controllers, Livewire and Filament may only: validate HTTP input, authorize, orchestrate Actions and manage UI state.

---

# Mandatory patterns

## Status transitions

Never mass-assign `status` on booking requests, matches or reviews.

Use dedicated Actions such as `TransitionBookingRequestStatus`, `CancelBookingRequest`, `RespondToBookingRequestMatch`, `SelectBookingRequestMatch`, `AcceptBookingQuote` and `SubmitReview`. Generic update Actions must reject status.

Each status Action must validate the transition, persist with `forceFill`, and dispatch follow-up work (notifications, calendar sync) without turning a completed flow into a 500.

## Public identifiers

Models that appear in URLs or Filament record routes use `HasPublicIdentifier` (`public_id` UUID, `getRouteKeyName()`).

Public entertainer pages keep `{entertainer:slug}`. Quotes and reviews keep secret tokens. Filament record binding must reject non-UUID keys via `ResolvesPublicRecordRouteBinding`.

## JSON policy

JSON is allowed for logs, credentials blobs and temporary metadata. Do not add new queryable commercial fields as JSON when a relational table is the right model.

## Feature flags

New risky functionality should be feature-flaggable when it can be rolled out independently.

---

# Content CLI (no deploy)

Agents create and update landing pages, blog posts, photos and 301 redirects locally. Do not wait for Coolify.

1. Write markdown in `content/pages/` or `content/blog/` with YAML front matter.
2. Put images in `content/media/` (JPG, PNG, GIF or WebP).
3. Add redirects in `content/redirects.txt` (`/old -> /new`) or run `php artisan content:redirect /old /new`.
4. Run `php artisan content:sync` (or `content:page`, `content:blog`, `content:media`, `content:redirect`).
5. Preview on the local app. PostgreSQL is the runtime source of truth; files are the authoring format.

Front matter keys are English: `title`, `slug`, `intro`, `published`, `published_at`, `noindex`, `tags`, `og_image`, `cover_image`, `cta_label`, `cta_url`, `seo_title`, `meta_description`. Body markdown may reference `media/filename.jpg`. Public URLs use slugs, never incremental IDs. Persist through Actions; do not write content from Livewire, Filament or controllers.

`SYNC_CONTENT_ON_BOOT=auto` imports committed `content/` files on web container start. File-backed records (matching slug/`source_path`) are overwritten on sync. Filament-only records without a source file stay untouched.

---

# Frontend

Only Blade, Livewire, Tailwind and Alpine. Do not change layout, icons, spacing, typography or colors unless the user explicitly asks.

Livewire may handle input, validation, Action orchestration and UI state. It must not contain domain workflows or call integration SDKs.

---

# Filament

Filament is an admin UI layer, not the business layer. Persist mutations through Actions. Do not let admin forms bypass public workflow guards for status, quotes or payments.

---

# Migrations

Migrations may change schema and add lightweight idempotent bootstrap data.

Migrations must not resolve app services, scan filesystems or run large backfills that block deploy. Schema changes during rolling updates must stay expand/contract compatible.

---

# Tests

- Default database is PostgreSQL (`kinderentertainers_test`).
- Architecture tests under `tests/Feature/Architecture*` are mandatory for layering rules.
- `composer test` runs the full suite. `composer test:architecture` runs architecture tests only.
- Development tests must be self-contained: no production state, no external providers.

---

# Definition of Done

A change is complete when:

- architecture rules are followed
- tests pass against PostgreSQL
- no business logic was added to Livewire, Filament or controllers
- public URLs still hide incremental IDs
- README.md / CHANGELOG.md are updated when the change is significant
- temporary debug files are gone
