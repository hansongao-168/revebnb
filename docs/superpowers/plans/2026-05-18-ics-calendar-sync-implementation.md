# ICS external calendar sync (platform admin) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let platform `/admin` attach multiple HTTPS iCal feeds per listing, sync `VEVENT`s into `external_calendar_events`, support manual + scheduled sync, and show a read-only month comparison vs local confirmed bookings and unavailability blocks—without changing guest-site availability.

**Architecture:** `listing_calendar_feeds` stores encrypted URLs and sync metadata; `ExternalCalendarSyncService` fetches ICS via HTTP, parses with `sabre/vobject` through `IcsCalendarParser`, upserts/deletes events by `(feed_id, ical_uid)`; `SyncListingCalendarFeedJob` + hourly `calendar-feeds:sync-due` command; Filament Relation Manager on `ListingResource` + `ViewListingCalendarComparison` page. Night expansion reuses the same half-open semantics as `BookingAvailabilityService::bookingNightsInclusiveHalfOpen`.

**Tech Stack:** Laravel 13, Filament 5, PHPUnit 12, `sabre/vobject`, queue (project already runs `queue:listen` in `composer run dev`).

**Spec:** `docs/superpowers/specs/2026-05-18-ics-calendar-sync-design.md`

---

## File map

| Path | Responsibility |
|------|----------------|
| `composer.json` | add `sabre/vobject` |
| `config/calendar_feeds.php` | defaults, allow_http, allowed_hosts |
| `database/migrations/..._create_listing_calendar_feeds_table.php` | feeds |
| `database/migrations/..._create_external_calendar_events_table.php` | events |
| `app/Enums/CalendarFeedSyncStatus.php` | pending/success/failed |
| `app/Models/ListingCalendarFeed.php` | encrypted url, relations |
| `app/Models/ExternalCalendarEvent.php` | json blocked_nights |
| `app/Models/Listing.php` | `calendarFeeds()` hasMany |
| `database/factories/ListingCalendarFeedFactory.php` | tests |
| `database/factories/ExternalCalendarEventFactory.php` | tests |
| `app/Services/Ics/NormalizedIcsEvent.php` | readonly DTO |
| `app/Services/Ics/IcsCalendarParser.php` | parse string → events |
| `app/Services/ExternalCalendarSyncService.php` | sync one feed |
| `app/Jobs/SyncListingCalendarFeedJob.php` | queue + unique |
| `app/Console/Commands/SyncDueCalendarFeedsCommand.php` | schedule target |
| `routes/console.php` | hourly schedule |
| `app/Policies/ListingCalendarFeedPolicy.php` | admin only |
| `app/Filament/Resources/Listings/RelationManagers/ListingCalendarFeedsRelationManager.php` | CRUD + sync action |
| `app/Filament/Resources/Listings/Pages/ViewListingCalendarComparison.php` | comparison UI |
| `resources/views/filament/resources/listings/pages/view-listing-calendar-comparison.blade.php` | month grid + table |
| `app/Filament/Resources/Listings/ListingResource.php` | register relation + page |
| `app/Filament/Resources/Listings/Pages/EditListing.php` | header: sync all + link to comparison |
| `tests/fixtures/ics/airbnb-sample.ics` | sanitized fixture |
| `tests/Unit/IcsCalendarParserTest.php` | parsing |
| `tests/Unit/ExternalCalendarSyncServiceTest.php` | upsert/delete/empty |
| `tests/Feature/SyncDueCalendarFeedsCommandTest.php` | due selection |
| `tests/Feature/ListingCalendarFeedFilamentTest.php` | admin UI + Http::fake |
| `tests/Feature/SiteListingBrowseTest.php` | assert availability unchanged (regression) |

---

### Task 1: Composer dependency + config

**Files:** `composer.json`, `config/calendar_feeds.php`

- [ ] **Step 1:** Run `composer require sabre/vobject --no-interaction`
- [ ] **Step 2:** Add `config/calendar_feeds.php` per spec §8
- [ ] **Step 3:** Commit: `chore: add sabre/vobject and calendar_feeds config`

---

### Task 2: Migrations + enums + models

**Files:** migrations, `CalendarFeedSyncStatus`, models, factories, `Listing::calendarFeeds()`

- [ ] **Step 1:** Write failing feature test that creates `ListingCalendarFeed` with encrypted `ical_url` and asserts DB value ≠ plaintext
- [ ] **Step 2:** Create migrations for both tables + unique index on `(listing_calendar_feed_id, ical_uid)`
- [ ] **Step 3:** Add enum, models, factories, relation on `Listing`
- [ ] **Step 4:** `php artisan migrate --no-interaction`
- [ ] **Step 5:** Run targeted test; commit: `feat: add calendar feed and external event models`

---

### Task 3: ICS parser (unit tests first)

**Files:** `NormalizedIcsEvent.php`, `IcsCalendarParser.php`, `tests/fixtures/ics/airbnb-sample.ics`, `tests/Unit/IcsCalendarParserTest.php`

- [ ] **Step 1:** Add fixture ICS (multi-day reservation + single night block)
- [ ] **Step 2:** Write failing unit tests: UID extraction, all-day half-open nights, DATE-TIME
- [ ] **Step 3:** Implement parser (skip events without UID)
- [ ] **Step 4:** Run `php artisan test --compact tests/Unit/IcsCalendarParserTest.php`
- [ ] **Step 5:** Commit: `feat: add IcsCalendarParser`

---

### Task 4: ExternalCalendarSyncService

**Files:** `ExternalCalendarSyncService.php`, `tests/Unit/ExternalCalendarSyncServiceTest.php`

- [ ] **Step 1:** Tests with `Http::fake` returning fixture body: upsert 2 events, change ICS to 1 event → second deleted
- [ ] **Step 2:** Test empty ICS clears events when config true
- [ ] **Step 3:** Test HTTP failure leaves old rows and sets `last_sync_status=failed`
- [ ] **Step 4:** Implement service (URL scheme check, host allowlist optional)
- [ ] **Step 5:** Run unit tests; commit: `feat: add ExternalCalendarSyncService`

---

### Task 5: Job + Artisan schedule

**Files:** `SyncListingCalendarFeedJob.php`, `SyncDueCalendarFeedsCommand.php`, `routes/console.php`, `tests/Feature/SyncDueCalendarFeedsCommandTest.php`

- [ ] **Step 1:** Test: feed synced 7h ago with interval 6 → picked up; feed synced 1h ago → skipped
- [ ] **Step 2:** Implement Job (unique id per feed) dispatching service
- [ ] **Step 3:** Implement command + `Schedule::hourly()`
- [ ] **Step 4:** Run feature test; commit: `feat: queue and schedule calendar feed sync`

---

### Task 6: Policy

**Files:** `ListingCalendarFeedPolicy.php`, register in `AuthServiceProvider` if needed

- [ ] **Step 1:** Mirror `ListingPolicy` admin-only rules for feed CRUD
- [ ] **Step 2:** Commit: `feat: add ListingCalendarFeedPolicy`

---

### Task 7: Filament Relation Manager

**Files:** `ListingCalendarFeedsRelationManager.php`, `ListingResource.php`, `EditListing.php`

- [ ] **Step 1:** Feature test: admin creates feed, triggers sync action with `Http::fake`, sees success notification and events count
- [ ] **Step 2:** Implement Relation Manager (password field for URL, masked display)
- [ ] **Step 3:** Header action on EditListing: sync all enabled feeds
- [ ] **Step 4:** Run Filament feature test; commit: `feat: filament calendar feeds relation manager`

---

### Task 8: Calendar comparison page

**Files:** `ViewListingCalendarComparison.php`, blade view, `ListingResource` page route, link from EditListing

- [ ] **Step 1:** Feature test: page returns 200 for admin, shows month param, contains external event summary when seeded
- [ ] **Step 2:** Build view: load listing feeds + events for month; compute local nights via `BookingAvailabilityService`; simple CSS grid for legend colors
- [ ] **Step 3:** Run test; commit: `feat: listing calendar comparison page`

---

### Task 9: Regression + Pint

- [ ] **Step 1:** Run `tests/Feature/SiteListingBrowseTest.php` (availability endpoints unchanged)
- [ ] **Step 2:** `vendor/bin/pint --dirty --format agent`
- [ ] **Step 3:** Full targeted suite: `php artisan test --compact tests/Unit/IcsCalendarParserTest.php tests/Unit/ExternalCalendarSyncServiceTest.php tests/Feature/SyncDueCalendarFeedsCommandTest.php tests/Feature/ListingCalendarFeedFilamentTest.php`

---

## Notes for implementer

- Do **not** modify `BookingAvailabilityService` in this plan.
- When editing existing `Listing` Filament files, follow patterns from `ListingUnavailabilityBlockResource` for Chinese labels.
- Airbnb ICS: treat `DTEND` for `VALUE=DATE` as exclusive checkout day when expanding nights (half-open).
- Ask user before adding Composer packages other than `sabre/vobject`.
