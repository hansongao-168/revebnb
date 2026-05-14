# Guest booking closed loop (Blade Web) — design spec

**Date:** 2026-05-14  
**Status:** Approved (conversation sign-off)  
**Scope:** Public `/stays` booking flow with month calendar, optional email, guest token URLs, and local `localStorage` order list — **no real payments**, **no UniApp** in this phase.

---

## 1. Problem & goal

Deliver an Airbnb-like **booking closed loop** on the existing marketing site: guests pick dates on a **month grid**, see a **nightly-only price line**, submit a **guest booking** (no account required), land on a **confirmation** experience, optionally receive a **recoverable link by email**, and retain orders in **browser storage**. **No payment gateway**; orders remain request-style (`pending`) until landlord/process changes status elsewhere.

---

## 2. Locked product decisions

| Topic | Decision |
|-------|----------|
| Client surface | **Blade Web only** (`/stays`, site layout). UniApp explicitly **out of scope**. |
| Identity | **Guest checkout** — no login required to book. |
| Payments | **None** — no WeChat/Alipay/Stripe; no “paid” transition in this spec. |
| Price display | **Room total = nights × `listing.nightly_price` only.** Cleaning/service/tax lines are **not** auto-calculated; copy may state that extra amounts are **subject to landlord confirmation**. |
| Email | **Optional.** If omitted, **no email** is sent; guest relies on **confirmation page** + **`localStorage`**. If provided, send **Mailable** with **token URL**. |
| Recovery / viewing | **`localStorage`** list of orders + **optional email link** with token; **no** standalone “reference + email lookup” page in this phase. |
| Calendar UX | **Month grid** — unavailable nights/dates **not selectable**; backed by a **read-only availability HTTP API**. |
| Frontend stack | **Alpine.js + `fetch`** in `resources/js/app.js` (site currently has empty JS bundle — add Alpine as dependency). |

---

## 3. In scope / out of scope

### In scope

- New **read-only** listing availability endpoint for calendar disabling.
- Listing **detail booking panel**: Alpine-driven month calendar, guests count, name, optional email, notes; **dynamic room-only total** from selected nights.
- **POST** create `Booking` with extended columns (see §5); reuse **`BookingAvailabilityService`** rules for conflicts, `min_nights`, etc.
- **Confirmation** route: flash plaintext token (or full copy URL) **without** putting token on first redirect query string; “copy booking link” UI.
- **Public booking detail** `GET /bookings/{booking}?token=` — hash-verified token, 404 on failure (enumeration-safe).
- **`GET /me/bookings`** — shell page: list rendered **only** from `localStorage` (no server-side “my trips” list).
- **Optional queued email** when `guest_email` present.
- **Route naming:** **`POST /stays/{listing}/bookings`** as canonical store; **remove or redirect** legacy `.../inquiries` in the same implementation window (prefer **single path**: update Blade `action` + tests, delete old route unless external traffic requires 308 — default **no permanent redirect**, just replace).

### Out of scope

- Real payments, refunds, invoices.
- UniApp / mobile app parity.
- Map search, advanced filters, reviews, host messaging, host public profile pages.
- Account registration / login for guests on Web (API may exist for other clients; not required here).
- E2E browser automation for calendar UI (manual QA checklist acceptable unless team adds Playwright later).

---

## 4. User flows

### 4.1 Happy path (no email)

1. Guest opens listing detail `/stays/{slug}`.
2. Selects check-in / check-out on **month calendar**; chooses guests; fills **name**; leaves email empty.
3. Submits → server validates availability → creates `Booking` (`pending`), stores **hashed** guest access token, persists `guests` + optional `guest_email` (null).
4. Redirect to confirmation → **session flash** exposes plaintext token once → page shows summary + “copy booking link”.
5. Client script appends entry to **`localStorage`** (`revebnb:guestBookings`) including **`detail_url`** with token for later return visits.

### 4.2 With email

Same as §4.1 plus: queue **Mailable** to `guest_email` containing the **same** `GET /bookings/{id}?token=` URL (query string unavoidable for email).

### 4.3 Return visit

- From **`/me/bookings`**: open stored `detail_url` if present.
- From **email**: open token URL.
- Token **expired** → 404 page copy suggests contacting support (no “resend” in this spec unless added later).

---

## 5. Data model

### 5.1 Existing baseline

`bookings` today: `listing_id`, `check_in`, `check_out`, `status`, `guest_name`, `notes` (see migration `2026_05_14_100005_create_bookings_table.php`). New bookings continue **`BookingStatus::Pending`** for guest submissions.

### 5.2 New / changed columns (migration)

| Column | Type | Notes |
|--------|------|-------|
| `guest_email` | `string`, nullable | Validated when present. |
| `guests` | `unsignedSmallInteger`, nullable | Align with form; stop encoding only in `notes` (controller may still append notes for backward compatibility — **implementation choice**: prefer column as source of truth). |
| `guest_access_token_hash` | `string(64)` (or wider if using generic hash output) | Store **hash only**, e.g. `hash('sha256', $plainToken)`. |
| `guest_access_token_expires_at` | `timestamp`, nullable | Default **created_at + 180 days** (make configurable via `config/` if trivial). |

**Token generation:** cryptographically secure random plaintext (length ≥ 32 bytes before encoding); single issue at creation; **never** log plaintext token.

---

## 6. HTTP surface

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/stays/{listing:slug}/availability` | JSON availability for calendar (query: `month=YYYY-MM` or `from`/`to` — **pick one convention in implementation plan**). |
| `POST` | `/stays/{listing:slug}/bookings` | Create booking; name required; email optional. |
| `GET` | `/bookings/{booking}/confirmation` | Post-submit landing; **requires** session flash for the new token (and matching booking). If flash is missing (e.g. refresh), respond **404** or redirect to a neutral “start at listing search” page — **pick one in implementation plan** (recommend **404** for smallest attack surface). |
| `GET` | `/bookings/{booking}` | Public detail when `?token=` valid and not expired. |
| `GET` | `/me/bookings` | Static shell + client list from `localStorage`. |

**Removed:** `POST /stays/{listing:slug}/inquiries` — replace with `bookings` (update `routes/web.php`, Blade form `action`, and all tests referencing old route name).

**Throttle:** apply sensible `throttle` middleware to `availability`, `bookings` store, and token detail (exact values in implementation plan).

---

## 7. Security & privacy

- **Token verify:** `hash_equals` against stored hash; invalid → **404**.
- **No token in URL** on the **first** redirect after POST — use **session flash** on confirmation page only; email/link copy may use query `token`.
- **Rate limit** token guessing and availability scraping.
- **Confirmation copy:** short notice that the link is a **credential** — do not share publicly.
- **Public detail payload:** booking fields + **public** listing fields only (title, slug, city, etc.) — no internal landlord PII beyond what listing page already shows.

---

## 8. Availability API contract (semantic)

- Input: listing slug + time window (month or date range).
- Output: machine-readable set sufficient for the calendar to mark **disabled check-in / disabled ranges** consistently with server rules (`BookingAvailabilityService`, confirmed bookings, unavailability blocks, `min_nights` where applicable — **exact algorithm in implementation plan** must match server-side validation).
- Unpublished listing → **404**.

---

## 9. `localStorage` schema

- **Key:** `revebnb:guestBookings` (fixed).
- **Value:** JSON array of objects, minimum:

```json
{
  "booking_id": 1,
  "listing_title": "…",
  "check_in": "2026-06-01",
  "check_out": "2026-06-04",
  "detail_url": "https://…/bookings/1?token=…"
}
```

- **Write:** on confirmation page after successful flash presence (script guarded if storage unavailable).
- **Read:** `/me/bookings` page.

---

## 10. Email

- **Mailable** + **queued** job when `guest_email` is non-empty.
- **Subject/body:** neutral transactional copy (Chinese per existing site tone); include **token URL** and short expiry notice.
- **Local/dev:** respect `MAIL_MAILER=log` — no extra spec.

---

## 11. Testing (PHPUnit feature tests)

| Case | Expect |
|------|--------|
| Store booking happy path | `pending`, hash set, guests persisted, redirect confirmation, flash token. |
| With email | `Mail::fake()` asserts one mailable with expected URL pattern. |
| Without email | Zero mailables. |
| Availability | Published → 200 + JSON shape; draft/archived → 404. |
| Conflict / block / min nights | Same semantics as today’s inquiry flow (appropriate validation response). |
| Token detail | Good token → 200; bad / expired → 404. |

**Explicit non-goals for automated tests:** Alpine calendar pixel behavior; `localStorage` persistence across browsers.

---

## 12. Risks & follow-ups (not blocking this spec)

- Guests clearing site data lose `localStorage` — mitigated by email when provided.
- Token in URL is sensitive to leaks (screenshots, shared logs) — mitigated by expiry + copy warning.
- Future: payment capture, login merge for guest bookings, “resend link”, landlord notifications.

---

## 13. Approval

- **Product:** conversation sign-off 2026-05-14 (“整份设计通过”).
- **Engineering:** implement per follow-on **implementation plan** (`writing-plans` phase) after written spec review.
