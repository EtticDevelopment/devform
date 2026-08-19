# DevForm Research Brief 2

Second-pass depth research across 8 angles: naming, conversion tracking, Mode B API contract, file uploads, anti-spam, async/retention, validation/i18n/a11y, and native WordPress UX. Everything below was fetched or executed during the research session unless explicitly marked unverified.

Date: 2026-08-19. Baseline: WordPress 7.0.4 stable, 7.1 ships today, 7.2 scheduled 10 December 2026.

---

## 1. Name recommendation

### The pick: DevForm

| Item | Value |
|---|---|
| wordpress.org slug | `devform` |
| Submission `Plugin Name:` header | `DevForm` (exactly this, nothing longer) |
| Directory display name after approval | `DevForm, Forms and Submission Endpoints` |
| Text domain | `devform` (forced by the slug, not a choice) |
| PHP namespace | `DevForm\` |
| Function/hook/option prefix | `devform_` |
| Constant prefix | `DEVFORM_` |
| Class prefix if not namespacing | `DevForm_` |
| REST namespace | `devform/v1` |
| Domains to register now | devform.dev (primary), devform.org, getdevform.com |
| GitHub org | github.com/devform |

### Why the slug is exactly `devform`

1. The slug is auto-generated from the `Plugin Name:` header at submission and is permanent after approval. The handbook FAQ: "Once your plugin is approved, this name cannot be renamed. Please chose wisely." You get exactly one slug change, and only before review begins.
2. The slug forcibly becomes the plugin folder name, the SVN path, and the i18n text domain. That is why the PHP prefix must be `devform_` and not the house `ettic_[product]_` convention: a plugin whose text domain is `devform` but whose functions are `ettic_devform_*` reads inconsistent to reviewers and contributors, and buys nothing.
3. The display name is separate from the slug and IS changeable after approval (it comes from readme.txt, falling back to the plugin header). So submit short, expand the readme heading later. Do not put "WordPress" or "Plugin" in the display name; the directory already adds both to the page title.

### Evidence the name is free

1. **Slug availability.** `https://wordpress.org/plugins/devform/` returns HTTP 301 to `/plugins/search/devform/`. Same for `dev-form`, `devforms`, `dev-forms`, `defform`, `def-form`, `devform-lite`, `devform-forms`. Control: `contact-form-7`, `wpforms-lite` and `fluentform` all return HTTP 200. A closed or removed plugin still returns 200 with a closure notice, so 301-to-search means the slug has never been used. Directory search for "devform" renders "0 plugins".
2. **WordPress automated trademark check.** Plugin Check's `Trademarks_Check.php` `TRADEMARK_SLUGS` list holds 112 terms. No term contains `dev`. The only form-related entries are `contact-form-7-`, `gravity-form-`, `gravity-forms-`, `gravityforms-`, `ninja-forms-`, all of which are prefix-anchored. I ran `devform`, `devforms`, `defform`, `dev-form`, `formsmith`, `formwright`, `ettic-devform` against the real list logic: all clean.
3. **Prefix length.** WPCS `PrefixAllGlobalsSniff` hard-codes `const MIN_PREFIX_LENGTH = 4` and blocklists `wp`, `wordpress`, `_`, `php`. `devform_` at 7 characters clears it comfortably. Plugin Check's `Prefixing_Check.php` runs that exact sniff and derives candidate prefixes from your own PHP files via `Prefix_Scanner`, so aligning the prefix to the slug is what it expects to find.
4. **Trademarks.** TMview (verified to index USPTO and EUIPO: a control query on "wordpress" returned 94 records including US(9) and EM(1)) returns exactly one record containing "devform": DEVFORMA, French office, application 5271162, filed 2026-06-22, Nice classes 41 and 42, applicant Heithem ATAOUI, status "Filed" (not registered). "devforms" returns 0. "defform" returns 0. CodeCanyon search for devform returns nothing.
5. **Non-.org collision.** WordPress rejects slugs matching a plugin folder name in wide use outside the directory, because the update API matches on folder name. GitHub `devform in:name` returns 78 repos, top by stars is `ai4co/devformer` (23 stars, an ICML paper, not a plugin); nothing with an install base. npm returns 404 for `devform`, `devforms`, `defform`. Packagist has no `devform/*` or `defform/*`.
6. **Adjacent namespaces.** `github.com/devform` and `github.com/defform` are free (API 404). `github.com/DevForms` exists but is an empty squatted account from 2023 with 0 repos.
7. **Domains.** Authoritative RDAP via IANA bootstrap, not a proxy: `pubapi.registry.google/rdap/domain/devform.dev` returns 404 (unregistered); control `web.dev` returns 200, so the method is sound. `rdap.publicinterestregistry.org` returns 404 for devform.org. WHOIS: "No match for domain GETDEVFORM.COM". Taken: devform.com (created 2009, parked on Squadhelp), devforms.com (listed at $1,088 on Spaceship), devform.app (expired, Porkbun auction), devform.io (redemptionPeriod, expiry 2026-06-17), devform.co (registered Feb 2026, blank page). Note the rdap.org proxy returned a false 404 for devform.io, so only use registry endpoints.

### TRADEMARK RISK, READ THIS

**DEVFORMA (FR, application 5271162) is a live pending application in classes 41 and 42, filed 2026-06-22.** Class 42 covers software services. It is a different string, it is not registered, and classes 41 plus the French reading of "forma[tion]" point at a developer-training business rather than form software. But Ettic is EU-domiciled, so if it registers at FR or escalates to EUIPO it is assertable. Docket a watch on it. Do not spend on brand assets before someone confirms this by hand.

**The trademark clearance rests on a single source.** TMview was the only search service reachable. `tmsearch.uspto.gov` serves an SPA behind an AWS WAF challenge and its API returns S3 errors; WIPO Global Brand Database gates search behind an ALTCHA proof-of-work widget; `trademarks.justia.com` returns Cloudflare 403. Treat DevForm as "clean on one good source", not "cleared". Have a human run it through the USPTO Trademark Search UI and EUIPO eSearch before any spend.

**Nothing can reserve the slug.** Guideline 16: "Names cannot be 'reserved' for future use or to protect brands" and a complete working plugin zip is required at submission. Someone else can take `devform` at any moment until you submit code. Pending submissions are invisible externally, so there is no way to check.

### Names that are out

1. **Defform is dead. Drop it now.** `defform.com` is a live Japanese commercial SaaS (AI sorting of sales and spam mail out of contact-form submissions, Astro site on CloudFront, registered 2025-07-19), and it already ships a wordpress.org plugin at slug `defform-contact-form` ("Defform Contact Form", author TodoONada, v1.2.0, updated 16 June 2026). Same product category. Guideline 17 forbids a slug beginning with another product's term, and the FAQ's rejection text is "you may not begin your Display Name with someone else's trademarked or common term". This is the OpenTrust/DocuSign pattern replaying.
2. **DevForms (plural) is strictly worse.** Nothing blocks it, but devforms.com costs $1,088, github.com/DevForms is squatted, and `devforms_get_form()` reads clumsy.
3. **Fallbacks if a reviewer objects to `devform` as too generic.** Keep `devform-forms` in your pocket; it is free and preserves the brand, and it is exactly what the one permitted in-review slug change is for. If you must abandon DevForm entirely, Formwright is the cleanest (0 TMview hits, formwright.dev free, formwright.com taken); Formsmith is second (1 dead US mark from 1988, both .com and .dev taken). Avoid formkit-wp (FormKit is a live commercial Vue library) and formcraft (existing commercial WP form builder).

### Do before writing code

1. Register devform.dev, devform.org, getdevform.com, and claim the github.com/devform organisation. These are the only things you can lock ahead of a finished plugin.
2. Run Plugin Check locally (`wp-plugin-check` skill is installed) so `Trademarks_Check` and `Prefixing_Check` pass against real code.
3. Get a human trademark confirmation via USPTO and EUIPO UIs.

---

## 2. Tracking architecture: the differentiator

### The gap, with receipts

No free WordPress form plugin ships server-side conversion tracking, and most paid ones do not either.

1. **WPForms** (6M+ installs) has no first-party GA4 integration and no Meta Pixel integration at any price. Its "Analytics" and "User Journey" addons are internal funnel reporting, gated at Pro: $199.50/yr promo, $399/yr regular. (Webhooks tier is inconsistent between their pricing page and addons index, do not quote it.)
2. **Gravity Forms** puts its Google Analytics add-on in Elite, $259/yr.
3. **Formidable** puts User Tracking and User Flow in Business, $159.60/yr.
4. **Ninja Forms** has no analytics or conversion-tracking add-on among 40+ extensions, at any tier.
5. **Fluent Forms** lists "Form Analytics" but no GA, no Pixel, no conversion tracking, no webhooks in its feature comparison.
6. **Elementor Pro** has none at any tier.
7. **Jetpack Forms** advertises no conversion tracking or analytics anywhere.

Meanwhile every API needed is free at the vendor: GA4 Measurement Protocol, Meta Conversions API, LinkedIn Conversions API and PostHog capture all cost nothing. The cost is engineering, not licensing. And the closest free competitors are partial: GTM4WP (700k installs) is dataLayer-only with no server side; PixelYourSite (500k installs) ships free Meta CAPI but paywalls Google Ads, TikTok, Bing and Reddit tags. PixelYourSite at 500k installs proves the free-Meta-CAPI model is allowed on .org, so this is not unprecedented territory.

One more: Google is building `Enhanced_Conversions` into Site Kit (5M installs, issue #11003, P0, closed), but its `get_user_data()` sources email and name from the **logged-in WordPress user**, which is useless for anonymous lead forms. DevForm has the submitted email in hand. That is precisely the slot.

### The keystone: one server-minted event_id

On every successful submission, PHP mints exactly one event record before any action runs:

```php
[
  'event_id'   => wp_generate_uuid4(),
  'event_name' => 'generate_lead',
  'form_id'    => 12,
  'form_title' => 'Contact',
  'entry_id'   => 4711,          // null in fire-and-forget mode
  'value'      => 30.0,
  'currency'   => 'EUR',
  'page_url'   => '...',
  'referrer'   => '...',
  'user_data'  => [ 'email' => ..., 'phone' => ..., 'first_name' => ..., 'last_name' => ... ],
  'client'     => [
    'ga_client_id'  => '...',
    'ga_session_id' => '...',
    'fbp' => '...', 'fbc' => '...',
    'ph_distinct_id' => '...',
    'ip' => '...', 'user_agent' => '...',
  ],
]
```

Meta deduplicates on `event_id` + `event_name` within 48 hours. LinkedIn takes an `eventId`. GA4 does not need one but it makes debugging tractable. **Generating it server-side and handing it to the browser is what stops the browser tag and the server call double-counting**, and it is the single most common failure in every CAPI implementation.

Ordering matters and is verified: Meta's fallback dedup method "only works for deduplicating events sent first from the browser and then through the server", and "server events will not be discarded if a browser event has not been received in the past 48 hours, even if an identical browser event arrives after the server event." So the browser fires first, the server fires after the response is flushed. That conveniently matches the `fastcgi_finish_request()` pattern anyway.

### Browser layer

Fires first. Every piece is a no-op when the vendor global is absent.

1. **DOM events**, Contact Form 7 shaped but jQuery-free. CF7's `submit.js` dispatches `beforesubmit`, then `mailsent`/`mailfailed`/`invalid`/`unaccepted`/`spam`/`aborted`, then always `submit`, with a rich `detail` object (`contactFormId`, `status`, `inputs`, `formData`, `apiResponse`). Gravity Forms uses jQuery `gform_confirmation_loaded` with positional args. Follow CF7, not GF: jQuery is a dependency DevForm should not take and Mailbox-mode developers on Astro or Next will not have it.
   Proposed: `devform:beforesubmit`, `devform:success`, `devform:invalid`, `devform:spam`, `devform:error`, all bubbling from the form element, all carrying `detail = { formId, entryId, eventId, status, inputs, response }`.
2. **`dataLayer.push`.** This is the highest value-to-effort feature in the whole plugin. GTM's built-in Form Submission trigger provably cannot see an AJAX form: Simo Ahava, "Many forms send their data with custom-built requests (e.g. jQuery's $.ajax or the XMLHttpRequest API), and these prevent the submit event from working, since it's replaced with a custom dispatcher", and propagation stopped by `return false` or `stopPropagation()` breaks the second condition. GTM's own Custom Event docs say custom events are "most commonly" used "when you want to track form submissions, but the default behavior for the form has been altered." Every AJAX form plugin silently breaks GTM. The fix is about 15 lines.
   Emit `dataLayer.push({ event: 'devform_submit', form_id, form_title, entry_id, event_id, value, currency })`, document the Custom Event trigger name in the readme with a screenshot. `dataLayer` is case-sensitive, `event` is reserved, one dataLayer per page.
3. **Built-in senders**, each one toggle plus one paste field:
   - GA4: `gtag('event', 'generate_lead', { currency, value, lead_source })`. `generate_lead` is Google's official recommended event for form-generated leads. `currency` and `value` are each required if the other is set. Do not invent a custom event name by default; the recommended name is what makes it appear in GA4's prebuilt reports and in Ads import without configuration.
   - Google Ads: `gtag('event', 'conversion', { send_to: 'AW-CONVERSION_ID/LABEL', value, currency })`. Separate destination, separate credential field, single paste.
   - Meta: `fbq('track', 'Lead', { value, currency }, { eventID: event_id })`. The eventID is the **fourth** argument. Get this wrong and dedup dies; it is the most common CAPI bug.
   - PostHog: `posthog.capture('form submitted', {...})`. Use their `[object] [verb]` convention, not `devform_submit`. PostHog already autocaptures `<form>` and `<input>` interactions, so DevForm's value-add is the semantic event with entry_id and validated field data.
   - Google Enhanced Conversions: `gtag('set', 'user_data', { email, phone_number, address: {...} })` passing **raw**. Google normalizes and SHA-256 hashes for you. Less code, fewer bugs, keeps DevForm out of the crypto business on the browser side.
4. **client_id capture.** The documented way to get GA4's client id is `gtag('get', 'G-XXXX', 'client_id', callback)`, and the same call gets `session_id`. Google's own example in the gtag reference is literally the offline-event use case. Cookie-parsing `_ga` is a fallback, not the primary path (third-party reports say Google changed the GA4 cookie format in early May 2025 without notice; unverified). Inject both as hidden fields in Form mode; expose a documented helper in Mailbox mode.

### Server layer

Runs on `shutdown` after `session_write_close()` and `fastcgi_finish_request()`, with `ignore_user_abort(true)`, 2 to 3 second timeout per call, failures written to the retry table and drained on the next submission. Never wp-cron, never Action Scheduler (it is wp-cron backed, see section 6).

1. **GA4 Measurement Protocol.** `POST https://www.google-analytics.com/mp/collect?measurement_id=G-XXX&api_secret=SECRET`, or `https://region1.google-analytics.com/mp/collect` when the user ticks "EU data collection". Body: `{ client_id, user_id?, timestamp_micros?, consent?, user_properties?, events: [{ name, params }] }`. Limits: 25 events per request, event names <=40 chars, param values <=100 chars, body <130 kB, backdate up to 72 hours, 100 million non-conversion requests/hour per property.
   **Mandatory:** include `session_id` and `engagement_time_msec`. The reference is explicit: "To ensure accurate session and user engagement metrics in your reports, including Realtime, include the session_id and engagement_time_msec parameters with your events." Without them the event is missing from Realtime and the user will report the plugin as broken.
   Map WP consent into the `consent` object: `ad_user_data` and `ad_personalization`, values `GRANTED`/`DENIED`. `non_personalized_ads` is deprecated in favour of `consent.ad_personalization`.
2. **Meta Conversions API.** `POST https://graph.facebook.com/v25.0/{PIXEL_ID}/events?access_token=TOKEN`, up to 1,000 events per request. Per-event required: `event_name`, `event_time` (Unix seconds), `action_source: "website"`. Send the same `event_id`, plus `event_source_url`, `custom_data` (currency, value), and `user_data` with SHA-256 `em`/`ph` and **unhashed** `client_ip_address`, `client_user_agent`, `fbp`, `fbc`.
   `_fbp` and `_fbc` format is `version.subdomainIndex.creationTime.identifier`, version always `fb`. In Form mode read `$_COOKIE['_fbp']` / `$_COOKIE['_fbc']` in PHP. In Mailbox mode and on a cross-origin decoupled front end those cookies are not on the request, so the documented payload contract must include optional `_fbp`/`_fbc` fields. Ship an fbclid-capture snippet that writes `_fbc` (90-day expiry) when the Pixel is not installed, since many decoupled sites have no pixel at all.
3. **PostHog.** `POST https://us.i.posthog.com/i/v0/e` or `eu.i.posthog.com`, body `{ api_key, distinct_id, event, properties, timestamp }`, `distinct_id` max 200 chars. Public project token only, no auth header, no OAuth. About 30 lines of `wp_remote_post`. Cheapest integration by a wide margin, ship it in v1.
   Do **not** bundle `posthog/posthog-php`; a free .org plugin vendoring dependencies is a review and conflict headache for zero benefit. Reconstruct `distinct_id` server-side from the `ph_<project_api_key>_posthog` persistence key, which embeds the token you already have. Note identified events cost roughly 4x anonymous ones at PostHog, so calling `identify` on every submission must be opt-in.
4. **NOT LinkedIn CAPI in v1.** The API is `POST https://api.linkedin.com/rest/conversionEvents` with OAuth scopes `rw_conversions` + `r_ads`, a `X-Restli-Protocol-Version: 2.0.0` header, and a monthly `Linkedin-Version: {yyyymm}` header with hard sunsets ("Marketing Version 202508 will be sunset on August 17, 2026"). Rate limits 600/min and 500k/day per member token, events must be within 90 days. That is too much maintenance liability for a free plugin. Ship the browser-side Insight Tag conversion only, plus a `devform_tracking_dispatch` action hook so someone else can build it.

### Two normalizers, never one

Meta and Google disagree on what to hash. A single shared "hash the PII" helper will be wrong for one of them, and a wrong hash produces no error, just a silently zero match rate.

| Field | Google (Enhanced Conversions / GA4 user_data) | Meta (CAPI user_data) |
|---|---|---|
| email | raw to gtag (browser) or `sha256_email_address`; trim, lowercase, strip dots before @gmail.com/@googlemail.com | `em`, SHA-256 of trim+lowercase |
| phone | E.164, `+16505551212`, 11-15 digits | `ph`, SHA-256 of `16505551212`, no `+`, no symbols, no leading zeros |
| first/last name | hashed (`sha256_first_name`) | `fn`/`ln`, SHA-256 of lowercase, no punctuation |
| city / state / postal / country | **NOT hashed**, plaintext | `ct`/`st`/`zp`/`country` **hashed**: `newyork`, `ca`, first 5 digits US, ISO 3166-1 alpha-2 lowercase |
| dob | n/a | `db`, SHA-256 of `YYYYMMDD` |
| gender | n/a | `ge`, SHA-256 of `f` or `m` |
| ip / user agent / fbc / fbp | n/a | never hashed |

Build `DevForm\Tracking\Normalize::for_google()` and `::for_meta()` separately, unit-test them with a `@dataProvider` matrix, and keep the per-form field-mapping UI destination-agnostic ("which field is the email?") with normalization applied at dispatch time.

### Consent handling

1. Gate everything behind the **WP Consent API** (200,000+ installs, v2.0.1, the closest thing to an official WordPress standard). Complianz and CookieYes both map their categories to it.
2. Register with `add_filter( 'wp_consent_api_registered_devform', '__return_true' );`
3. Categories: `functional`, `statistics-anonymous`, `statistics`, `preferences`, `marketing`. Map GA4 and PostHog behind `statistics`; Meta, Google Ads and LinkedIn behind `marketing`.
4. PHP API: `wp_has_consent( $category )`, `wp_set_consent()`, `wp_get_consent_type()`.
5. **Critical default:** `wp_get_consent_type()` returns `false` when no CMP is installed. Treat that as **fire** (opt-out region default). Treating it as deny silently breaks tracking on the overwhelming majority of sites and earns exactly the reviews the plugin is trying to avoid.
6. JS: listen for `wp_listen_for_consent_change` and flush a queued event if consent lands late. The documented event shape is `document.addEventListener("wp_listen_for_consent_change", function (e) { var changed = e.detail; ... })`.
7. Do not ship a consent banner. DevForm sits downstream of whatever the site's CMP does. The Google Consent Mode v2 basic-vs-advanced distinction is a documentation point, not a code point.
8. Consent Mode v2 parameters, for the docs: `ad_storage`, `analytics_storage`, `ad_user_data`, `ad_personalization`, plus `functionality_storage`, `personalization_storage`, `security_storage`. The v2 parameter addition dates to a November 2023 Google update. **The widely-cited "March 2024 EEA enforcement" date is not stated on any Google page fetched; treat that date as unconfirmed and do not put it in the readme.**

### Per-provider setup, as the admin sees it

| Destination | Credentials the user pastes | Test mechanism | Default |
|---|---|---|---|
| GTM / dataLayer | none | GTM Preview mode | ON (no external call, no consent needed for a dataLayer push itself) |
| GA4 browser | Measurement ID `G-XXXX` (or reuse Site Kit's gtag) | GA4 DebugView | OFF |
| GA4 server | Measurement ID + API secret + EU checkbox | `https://www.google-analytics.com/_debug_/mp/collect`, surface `validationMessages` verbatim | OFF |
| Google Ads | `AW-CONVERSION_ID/LABEL` single paste | Ads diagnostics (external) | OFF |
| Meta browser | Pixel ID | Meta Events Manager Test Events | OFF |
| Meta server | Pixel ID + access token + optional `test_event_code` | `test_event_code` round trip | OFF |
| PostHog | Project token + US/EU region | Live events feed | OFF |
| LinkedIn / X / TikTok | paste-your-snippet generic hook only | none | OFF |

### The test button is the product

Silent failure is the actual problem in this category. Wire the GA4 settings screen to the real debug endpoint `https://www.google-analytics.com/_debug_/mp/collect` (EU: `region1.`), same request shape plus optional `validation_behavior: "ENFORCE_RECOMMENDATIONS"`. A valid event returns `{"validationMessages": []}`; an invalid one returns entries with `fieldPath`, `description`, `validationCode` (`NAME_INVALID`, `VALUE_INVALID`, `VALUE_OUT_OF_BOUNDS`). Documented limitation: it does not validate `api_secret` or `firebase_app_id`, and events sent there do not appear in reports. Wire Meta's to `test_event_code`. **No WordPress form plugin does this.**

### Admin UI shape

1. A global **Tracking** settings screen: one card per destination, each collapsed until enabled, each with a status pill (Not configured / Configured / Last event OK at HH:MM / Last event failed) and a "Send test event" button that renders vendor validation output verbatim rather than a green tick.
2. Per-form **Tracking** tab: event name override, `value` (static or mapped to a field), `currency`, `lead_source`, and a field-mapping table (email / phone / first name / last name / city / postal / country) that is destination-agnostic.
3. A **Delivery** log listing the last N tracking dispatches with destination, HTTP status, response excerpt, and a Retry button. Same table pattern as the webhook delivery log in section 6, ideally the same code.
4. Do not build a charts dashboard. DevForm's job is getting the event into the tools the user already pays for, not becoming a fourth analytics product.

### Mailbox mode is where this becomes uncatchable

The submit endpoint's JSON response carries a ready-to-fire `tracking` block with the correct server-minted `event_id`:

```json
{
  "ok": true,
  "submission_id": "sub_01JBX8QK3M7Z9V",
  "tracking": {
    "event_id": "0f2c...",
    "event_name": "generate_lead",
    "dataLayer": { "event": "devform_submit", "form_id": 12, "value": 30, "currency": "EUR" },
    "ga4":  { "name": "generate_lead", "params": { "currency": "EUR", "value": 30 } },
    "meta": { "event_name": "Lead", "eventID": "0f2c...", "custom_data": { "currency": "EUR", "value": 30 } },
    "posthog": { "event": "form submitted", "properties": { } }
  }
}
```

A developer on Astro or Next.js posts to WordPress, gets back the payload with the correct `event_id`, and fires the browser half themselves while DevForm has already fired the server half. No competitor offers this, because no competitor has a mailbox mode.

### Two decay facts to put in the readme

1. **Ad blockers.** PostHog states a reverse proxy "typically increases event capture by 10-30% depending on your user base" because ad blockers maintain lists of known analytics domains. That number is the honest justification for the server-side half. It also suggests a stretch feature: a same-origin proxy REST route (`/wp-json/devform/v1/px/...`) so a WordPress site gets first-party-domain tracking without touching nginx. Cost it before committing; see the risk in section 10.
2. **Safari ITP.** WebKit deletes "all of a website's script-writable storage after seven days of Safari use without user interaction on the site", covering IndexedDB, LocalStorage, SessionStorage, media keys and Service Worker registrations, aligning them with the existing seven-day client-side cookie limit. So `_ga`, `_fbp` and PostHog's cookie are capped at 7 days on Safari. That destroys attribution for the classic B2B cycle (ad click, return three weeks later, fill in a form). Server-set cookies are not subject to the script-writable cap, and a WordPress plugin is uniquely well placed to set one. No form plugin currently does.

### wordpress.org compliance for tracking

Guideline 7: "Plugins may not contact external servers without explicit and authorized consent." Every destination ships OFF with empty credentials; the site owner pasting their own Measurement ID, Pixel ID or project token **is** the consent. Guideline 6 requires each service be "clearly documented in the readme file submitted with the plugin, preferably with a link to the service's Terms of Use." So the readme must enumerate every endpoint (`google-analytics.com`, `region1.google-analytics.com`, `graph.facebook.com`, `us.i.posthog.com`, `eu.i.posthog.com`) with a link to each vendor's terms. Documentation task, not an architectural blocker, but it gets a plugin bounced on first submission if skipped.

---

## 3. Mode B API contract

Written as if it were the public documentation. Design notes are in square brackets.

### Routes

```
POST    /wp-json/devform/v1/forms/{key}/submissions    the mailbox
GET     /wp-json/devform/v1/forms/{key}                public field schema (per-form opt-in)
OPTIONS /wp-json/devform/v1/forms/{key}/submissions    preflight, handled by core
```

`{key}` is a per-form public key (random base62), never the post ID. On sites without pretty permalinks the same route is reachable as `?rest_route=/devform/v1/forms/{key}/submissions`. `rest_url()` emits the correct form, so print that value in the admin UI rather than hand-building the URL.

[Namespace registered on `rest_api_init`. `permission_callback => '__return_true'`, which the handbook explicitly endorses for public routes and which Contact Form 7's own public feedback route uses.]

### Authentication

1. The public key in the path is the default and is sufficient for browser calls.
2. Per-form opt-in `require_secret` adds `Authorization: Bearer df_sk_...` for server-to-server callers. `Authorization` is already in core's allowed-CORS-headers list, so it needs no filter.
3. Two tiers, two rate limits. This mirrors Forminit (ex-getform.io): public mode 1 request per 30 seconds, protected mode with an API key 5 requests per second.
4. **No nonce, ever.** Verified in `rest_cookie_check_errors()`: a missing nonce means `wp_set_current_user(0)` and the request continues; a **stale** nonce returns `WP_Error('rest_cookie_invalid_nonce', 'Cookie check failed', ['status' => 403])`. `wp_nonce_tick()` is `ceil(time() / (nonce_life / 2))` with `nonce_life` defaulting to `DAY_IN_SECONDS` and two ticks accepted, so a nonce lives 12 to 24 hours. A nonce baked into a cached page is strictly worse than no nonce: it turns a working form into a hard 403. This is the verified root cause of the classic "my cached contact form started 403ing" bug, and it means DevForm's Mode A markup must also contain zero per-user tokens. Cache-safe by construction is a real differentiator against every nonce-using WP form plugin.
5. CSRF is not the threat model for an unauthenticated write that creates no privileged state. The defence budget goes to origin allowlist, honeypot, time trap, Akismet, rate limiting and an optional CAPTCHA token.

### Request

Accepted content types (WordPress core parses all three natively via `WP_REST_Request::get_parameter_order()`):

1. `multipart/form-data` **(documented happy path)**
2. `application/x-www-form-urlencoded`
3. `application/json` (power-user path)

Anything else returns `415 devform_unsupported_media_type`. [Core parses but does not gate; the media-type gate is the one piece you must write. `is_json_content_type()` delegates to `wp_is_json_media_type()`, which since 5.6 matches `application/json` and any `+json` suffix. Uploads are deliberately not merged into `get_params()`; they come from `get_file_params()`.]

**Post multipart and skip preflight entirely.** `multipart/form-data` and `application/x-www-form-urlencoded` are CORS-simple requests. JSON triggers a preflight round trip. Document this loudly; it is free latency.

Reserved control fields use a `_devform_` prefix so they can never collide with a real field name:

| Field | Purpose |
|---|---|
| `_devform_hp` | honeypot; name randomizable per form |
| `_devform_t` | render timestamp for the time trap; advisory only, never a hard reject |
| `_devform_meta` | nested object for per-submission overrides (Formspark-style `_email.*` nesting) |
| `_devform_idem` | idempotency key fallback for no-JS posts that cannot set headers |
| `_devform_fbp`, `_devform_fbc`, `_devform_ga_cid`, `_devform_ga_sid` | client identifiers a decoupled front end forwards |

[Web3Forms uses unprefixed reserved names (`email`, `subject`, `redirect`, `botcheck`, `webhook`), so a real form field named `subject` silently changes behaviour. FormSubmit is worse: `_replyto`, `_next`, `_subject`, `_cc`, `_blacklist`, `_captcha`, `_autoresponse`, `_template`, `_webhook`, all single-underscore, on an endpoint that is literally `https://formsubmit.co/{your-email-address}` with no registration.]

Optional headers:

```
Idempotency-Key: 01JBX8QK3M7Z9VABCDEF
Accept: application/json
Origin: https://example.com
```

### Response format switch

JSON is returned when `Accept` matches `wp_is_json_media_type()`, or `Content-Type` is JSON, or `X-Requested-With: XMLHttpRequest`. Otherwise the endpoint answers `303 See Other` with a `Location:` header to the configured target.

**Never sniff for JS.** The Accept header is the switch. Formspree does exactly this ("AJAX forms, those with an accept-type header set to application/json, will respond with a 429 error code" while "HTML forms that reach the limit will be redirected to an error page"). Formcarry requires `Accept: application/json` for a JSON response. Web3Forms uses 303 for the redirect path.

**303, not 302.** 303 forces the follow-up request to be GET, which kills the browser's resubmit-on-refresh dialog.

### Success

`201 Created` when the submission was stored, `202 Accepted` in fire-and-forget mode. Same body:

```json
{
  "ok": true,
  "submission_id": "sub_01JBX8QK3M7Z9V",
  "form": "contact",
  "received_at": "2026-08-19T10:14:22+00:00",
  "stored": true,
  "next": "https://example.com/thanks/",
  "actions": [
    { "type": "email",    "status": "sent" },
    { "type": "webhook",  "status": "queued", "delivery_id": "dlv_01JBX..." },
    { "type": "redirect", "status": "skipped" }
  ],
  "tracking": { }
}
```

1. `next` keeps Formspree's key name so their client code ports over. Formspree's entire success body is `{ "next": "<url>" }`, with no submission id and no timestamp, so a client cannot correlate, retry or dedupe. DevForm returns more.
2. `actions[]` is the direct fix for Static Forms returning **200 on a silently failed delivery**: their own docs say 200 is "Also returned when delivery is silently blocked (e.g. bounced recipient address), the submission is recorded but no email is sent." A 200 that hides a failed action is a lie to the client. Here the request succeeded and the client can still see that the email action was skipped.
3. `tracking` is the block described in section 2.
4. **Honeypot hit returns 201 with a normal success body**, submission dropped. Formspree "silently ignore[s]" a filled `_gotcha`; Netlify "will quietly reject the form submission" and it does not even appear in the spam list; Static Forms 403s, which hands the bot free feedback. Follow the first two. See the conflict in section 10 about what this does to conversion tracking.

### Errors

Use WordPress core's envelope verbatim, because that is what `apiFetch`, the block editor and every WP-aware client already unwrap.

```json
{
  "code": "devform_invalid_fields",
  "message": "Invalid parameter(s): email, message",
  "data": {
    "status": 422,
    "params": {
      "email":   ["This is not a valid email address."],
      "message": ["This field is required."]
    },
    "details": {
      "email":   { "code": "devform_type_email",      "path": ["email"] },
      "message": { "code": "devform_required_empty",  "path": ["message"] }
    }
  }
}
```

1. `data.params` is a field-keyed map, which gives Formcarry's three-line renderability. Formcarry is the only surveyed service that returns a field-keyed map, and its own example client does `document.querySelector('[name="'+key+'"]')`, so the key must be the DOM `name` attribute.
2. `data.params[field]` is an **array** of messages (Laravel's shape), because a field can fail more than one rule.
3. `data.details[field].code` is the stable machine contract (Formspree's enum discipline: `REQUIRED_FIELD_EMPTY`, `TYPE_EMAIL`, `FILES_TOO_BIG`, ...). The human `message` is not stable and its wording can change. Point `code` at a documentation anchor.
4. `data.details[field].path` is an array (Zod's shape) so repeatable and nested fields work: `["attachments", 0, "size"]`. Decide this now; retrofitting a path format is a breaking response change.
5. **Every `WP_Error` must carry `['status' => N]`.** `rest_convert_error_to_response()` derives the HTTP status by `array_reduce` over all error data looking for a numeric `status` key and **defaults to 500** when none is set. A bare `WP_Error` leaks as a server error.

### Status codes

| Code | Error code | Cause |
|---|---|---|
| 400 | `devform_invalid_json` | body will not decode (core emits `rest_invalid_json` with `json_error_code`/`json_error_message`) |
| 401 | `devform_invalid_key` | bad or missing form key / bearer secret |
| 403 | `devform_origin_not_allowed` | Origin not on the per-form allowlist |
| 403 | `devform_form_closed` | form disabled or past its close date |
| 404 | `devform_form_not_found` | unknown key |
| 409 | `devform_duplicate_submission` | concurrent retry of an in-flight Idempotency-Key |
| 413 | `devform_payload_too_large` | includes the `post_max_size` case, see section 4 |
| 415 | `devform_unsupported_media_type` | content type not in the accepted list |
| 422 | `devform_invalid_fields` | field validation failure |
| 422 | `devform_idempotency_mismatch` | same key, different payload |
| 429 | `devform_rate_limited` | with `Retry-After` |
| 500 | `devform_action_failed` | an action stack failure that could not be queued |

**422 versus 400 for field validation.** WordPress core itself emits `rest_invalid_param` at 400. Formcarry uses 422 and it is semantically correct (the request was well-formed but semantically wrong). **Pick 422**, reserve 400 for malformed requests, and document the deviation. This is the one place worth deviating from core; the envelope stays identical so nothing breaks.

### CORS

WordPress ships two contradictory CORS postures and the REST one is the trap.

1. **The trap, verified in `wp-includes/rest-api.php`:** `rest_api_default_filters()` registers `rest_send_cors_headers` on `rest_pre_serve_request`. That function, when an `Origin` header is present and is not the literal `null`, sends `Access-Control-Allow-Origin: <that origin>`, `Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE`, **`Access-Control-Allow-Credentials: true`** and `Vary: Origin`. **There is no allowlist check whatsoever.** Every REST route on every WordPress site reflects any origin with credentials allowed.
2. Contrast `send_origin_headers()` in `wp-includes/http.php` (used by admin-post.php and admin-ajax.php), which checks `is_allowed_http_origin()` against `get_allowed_http_origins()` (defaulting to the http and https forms of the admin_url and home_url hosts, filterable via `allowed_http_origins` / `allowed_http_origin`) and answers a non-allowed OPTIONS preflight with 403 and exits.
3. **DevForm must strip, not add.** Hook `rest_pre_serve_request` at a priority above 10, and for DevForm routes only: remove core's reflected `Access-Control-Allow-Origin`, drop `Access-Control-Allow-Credentials` entirely, then re-emit from a per-form allowlist. Default the allowlist to `get_allowed_http_origins()`, which gives same-site-only out of the box and one filter for site owners who already use it.
4. **Send `Access-Control-Max-Age: 600`.** Grepping `rest-api.php`, `class-wp-rest-server.php` and `http.php` for Max-Age returns nothing. Core never emits it, and per MDN the browser default when absent is **5 seconds**. Every cross-origin JSON POST currently pays a full extra round trip.
5. Allowed request headers default to only `Authorization`, `X-WP-Nonce`, `Content-Disposition`, `Content-MD5`, `Content-Type`. `Idempotency-Key` is not CORS-safelisted, so add it via `rest_allowed_cors_headers`. Since WP 6.3 that filter receives `$request`, so scope the addition to DevForm routes rather than loosening the whole site.
6. Exposed response headers default to `X-WP-Total`, `X-WP-TotalPages`, `Link`, filtered by `rest_exposed_cors_headers`. Add `Retry-After` and `X-DevForm-Delivery-Id` if you want clients to read them cross-origin.
7. Preflight itself is handled by `rest_handle_options_request()` on `rest_pre_dispatch`. You do not register an OPTIONS route.

### Rate limiting

1. **WordPress core has no rate-limiting primitive.** Grepping `rest-api.php` and `class-wp-rest-server.php` for `rate_limit|ratelimit` returns zero hits.
2. **Do not use transients as the counter.** The Transients API docs: "transient expiration times are a maximum time. There is no minimum age. Transients might disappear one second after you set them" and "Transients should also never be assumed to be in the database." A transient-backed limiter fails open.
3. **Do not use `wp_cache_incr` unconditionally.** `WP_Object_Cache` is "by default non-persistent... data stored in the cache resides in memory only and only for the duration of the request" unless a persistent caching plugin is installed.
4. Implementation: `wp_cache_incr` in a dedicated group when `wp_using_ext_object_cache()` is true, otherwise an atomic `UPDATE ... SET n = n + 1` against a purpose-built table row.
5. Wire format: `429` plus `Retry-After` (RFC 9110, the only standards-track signal). Send `RateLimit` and `RateLimit-Policy` as best-effort only; they are still `draft-ietf-httpapi-ratelimit-headers-11` with IESG state "I-D Exists" and an HTTPDIR early review marked "Not ready".
6. Calibration from the field: Formspree 20/min per form; Static Forms 10-20/min plus 30-100/hour with a 1-hour block after 15 minutes of sustained violation; Forminit public mode 1 per 30s, protected 5/s. **Default DevForm to 10/min per IP per form**, filterable.
7. Resolve the client IP through a filterable resolver that honours `CF-Connecting-IP` or `X-Forwarded-For` **only when the site owner has opted into a trusted-proxy setting**. Trusting them unconditionally makes the limiter spoofable by anyone who can reach the origin directly.

### Idempotency

**Zero of eleven surveyed services support an idempotency key.** Two of them instead document duplicate submissions as a support problem: Formspree's limits page advises "disable the submit button after the form is submitted until the response is returned" and their troubleshooting index carries "Delay on Submit or Double Submissions"; Basin's carries "Duplicate Submissions on Safari" and exposes a server-side content-matching `duplicate_filter` rather than a client token. This is the cheapest genuine differentiator in the whole contract.

Implement `draft-ietf-httpapi-idempotency-key-header` semantics exactly:

1. Header is an RFC 8941 Structured Header string; recommend a UUID.
2. First request processes normally and the response is stored.
3. A retry after completion **replays the stored response**.
4. A concurrent retry gets `409 Conflict`.
5. The same key with a different payload gets `422 Unprocessable Content`.
6. A missing key on an operation that requires one gets `400`. (DevForm never requires one.)
7. Retention: 24 hours.
8. **Body fallback:** a plain no-JS browser form post cannot set headers, so `_devform_idem` is accepted in the body. Additionally run a 60-second server-side dedupe on a normalized payload hash for the no-JS case.
9. Remember the CORS consequence: `Idempotency-Key` is not safelisted, so it must be added to `rest_allowed_cors_headers` or every cross-origin preflight fails.

### Caching

1. **The POST is never cached.** Cloudflare's default cache behaviour explicitly excludes any method other than GET, and "The Cloudflare CDN does not cache HTML or JSON by default."
2. **But WordPress sends no no-cache headers to logged-out visitors.** `WP_REST_Server::serve_request()` computes `$send_no_cache_headers = apply_filters( 'rest_send_nocache_headers', is_user_logged_in() )`. Every Mode B caller is logged out, so core sends nothing. **Force `Cache-Control: no-store` on the submit route.**
3. The GET schema endpoint is safe and desirable to cache. Document that, since it lets a decoupled front end fetch field definitions from the edge.

### The public schema endpoint

`GET /wp-json/devform/v1/forms/{key}` returns the field definitions, validation rules, allowed upload extensions, size caps and file counts. Contact Form 7 already ships this pattern at `GET /contact-form-7/v1/contact-forms/{id}/feedback/schema` with `permission_callback => '__return_true'`.

It is what makes a decoupled front end able to render and client-validate without duplicating config, and it is edge-cacheable. It also publishes your field names and validation rules to anyone with the form key. **Recommendation: ship it, default it on.** The allowlist is not a secret and obscurity buys nothing; an attacker who can POST can discover the rules by probing anyway. Make it per-form disableable for the paranoid.

### Redirect targets, and a hole three competitors ship

FormSubmit's `_next`, Web3Forms' `redirect` and Static Forms' `redirectTo` all accept an arbitrary URL supplied in the request body with no stated host restriction. That is an open redirect.

DevForm resolves the redirect target **server-side from the stored per-form config**. If a request-supplied override is allowed at all, validate it with `wp_validate_redirect()` against an allowlist of hosts and silently fall back to the configured target on failure. Call this out in the readme; it is a concrete security win over three named competitors.

### Progressive enhancement

Mode A ships the same URL with `method="post"`, zero per-user tokens in the markup, and a JS enhancer that only changes the `Accept` header. What breaks under full-page caching is not the POST but anything per-render baked into the cached HTML: a nonce (do not use one) or a signed render timestamp for the time trap. So the time trap must be advisory (feed it into a spam score, never hard-reject) or be injected by JS at submit time where it is always fresh.

### Prior art that is NOT prior art

Tally and Fillout are hosted form builders with Bearer-token management APIs, not mailbox endpoints. Tally's documented Forms endpoints are list/create/fetch/update/delete forms, list questions, list/fetch/delete submissions; there is **no create-submission endpoint at all**. Fillout has a "Create submissions" endpoint but it sits behind the account API key, so it is a server-to-server import path, not a browser-facing mailbox. Do not benchmark against them. Their relevance is negative: the hosted-builder segment has abandoned the bring-your-own-HTML use case entirely, which is exactly the gap DevForm targets. Worth saying out loud in positioning.

### Error format standards, and why RFC 9457 loses

RFC 9457 Problem Details (`application/problem+json`, members `type`, `status`, `title`, `detail`, `instance`) is the standards-track choice and Formspark uses it on their management API. But WordPress core's envelope is `{code, message, data:{status}}` produced by `rest_convert_error_to_response()`, and matching it is the whole "native part of WordPress" thesis. Borrow RFC 9457's discipline (machine `code` is the stable contract, human `detail` is not, point `code` at a doc anchor) without its envelope. If DevForm ever ships a separate authenticated management API, that one can be `application/problem+json`.

---

## 4. File upload policy

The framing that survives all the evidence: **content validation is theatre, extension control plus a non-executing store is the whole game.** Every documented CVE root cause in this category falls into four buckets, and each needs an independent defence: no authorization check, denylist gap, configuration taken from the request, or predictable filename in an executable directory.

### The evidence that content checks do not work

All of this was executed locally on PHP 8.5.3 during the research session.

1. **GIF/PHP polyglot.** A 28-byte file containing the literal bytes `GIF89a<?php echo "PWNED"; ?>` returns `image/gif` from `finfo_file(FILEINFO_MIME_TYPE)`, `image/gif` from `getimagesize()['mime']`, and `image/gif` from `exif_imagetype()`. `wp_check_filetype_and_ext()` calls `wp_get_image_mime()`, which tries `exif_imagetype()` first and falls back to `getimagesize()`. Both agree with the extension, so the file passes.
2. **WordPress image validation is weaker than getimagesize alone.** A 30-byte non-image starting with JFIF magic bytes returns FALSE from `getimagesize()` but `image/jpeg` from `exif_imagetype()`. Whenever ext-exif is loaded, WP's check is the weaker of the two.
3. **Text and document payloads are worse.** A `.txt` reading "Dear sir, here is my CV.\n\n<?php system($_GET['c']); ?>" returns `text/plain`. A CSV with the same payload returns `text/plain`. A file starting `%PDF-1.4` with the payload in the middle returns `application/pdf`. All three sail through `wp_check_filetype_and_ext()`. Only a file that is *nothing but* `<?php ... ?>` reports `text/x-php` and gets rejected, so the check catches only the naive case.
4. **Office documents are arbitrary zips.** A 297 KB plugin zip renamed to `renamed.docx` and `renamed.xlsx` reports `application/zip` for both, and `wp_check_filetype_and_ext()` explicitly waves that through: its `$nonspecific_types` list includes `application/zip`, and any zip claiming an `application/*` extension is accepted.
5. **Compressed wrappers defeat everything.** An SVG containing `<script>alert(1)</script>` reports `image/svg+xml`. The same file gzipped as `.svgz` reports `application/gzip`, and `getimagesize()`/`exif_imagetype()` both return FALSE. A sanitiser gating on MIME type or handing bytes to DOMDocument sees an opaque blob and fails open. Browsers, served it with `Content-Encoding: gzip`, render it as SVG and run the script. That is exactly CVE-2026-13340.
6. **Re-encoding images DOES work.** A 65-byte valid GIF with `<?php echo "PWNED"; ?>` appended: before, 65 bytes, contains `<?php`. After `imagecreatefromgif()` + `imagegif()`: 43 bytes, no `<?php`. OWASP agrees: "image rewriting techniques destroys any kind of malicious content injected in an image."
7. **`sanitize_file_name()` has two live gaps.** I ran core's verbatim function against 31 attack filenames. It handles most: `shell.php.jpg` becomes `shell.php_.jpg`, `shell.phar.jpg` becomes `shell.phar_.jpg`, `.htaccess` becomes `htaccess`, `../../wp-config.php` becomes `wp-config.php`, `shell.php%00.jpg` becomes `shell.php00.jpg`. But **`shell.jpg.php` passes through unchanged** (the function never inspects the final extension) and **`shell.php<U+200B>.jpg` passes through unchanged** (U+200B is Unicode category Cf; core only replaces `\p{Zs}`).
8. **Contact Form 7 is stricter than core and is the reference to copy.** `wpcf7_antiscript_file_name()` runs `preg_replace('/[\r\n\t -]+/','-',$filename)` then `preg_replace('/[\pC\pZ]+/iu','',$filename)`, stripping Unicode categories C (control and format, which covers U+200B) and Z. I ran it: `shell.php<U+200B>.jpg` becomes `shell.php_.jpg` and `shell.jpg.php` becomes `shell.jpg.php_.txt`. That `[\pC\pZ]` strip is the CVE-2020-35489 fix and core still does not do it. CF7's gaps: it misses `phar` and `inc`, and leaves `.htaccess` alone (saved only because `wp_unique_filename()` runs `sanitize_file_name()` afterwards).

### The default allowlist (closed literal list)

Shipped default, per form, on: **`jpg, jpeg, png, gif, webp, pdf`**. Each maps to exactly one expected MIME in DevForm's own strict map (`image/jpeg`, `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `application/pdf`), not WordPress's lenient map.

Opt-in bundles a form owner can enable:

1. **Documents:** `doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp, rtf, txt, csv`. **Never** the macro-enabled `docm, xlsm, xlsb, xlam, pptm, ppsm, potm, ppam, sldm` variants, all of which core ships in its default map.
2. **Archives:** `zip` only, with a declared-size cap, a 100:1 ratio ceiling and an entry-count cap read via `ZipArchive::statIndex()`. **Never extracted**, ever. Stored and served opaquely. (Verified: a 4,975-byte zip containing one 5,000,000-byte entry reports `size=5000000 comp_size=4863`, a 1028:1 ratio, readable without extracting a byte. But the declared size comes from attacker-controlled central-directory headers, so it can lie. Simplest correct answer: never extract.)
3. **Media:** `mp3, mp4, m4a, webm, ogg, wav`.

**Never available at any setting, not filterable, not constant-overridable:** `svg, svgz, html, htm, xhtml, xml, xsl, xslt, mhtml, mht, js, mjs, cjs`.

Do **not** derive the list from `get_allowed_mime_types()` the way WPForms does. That function is gated on the `unfiltered_html` capability (not `unfiltered_upload`) and passes through the site-global `upload_mimes` filter, so it inherits whatever any other plugin added, including SVG. That is exactly why WPForms had to bolt `Content-Disposition: attachment` onto its temp directory in 1.10.1.1, with the comment "we force the browser to download (rather than render inline) anything served from here, which prevents stored XSS via file types such as SVG on sites that have enabled SVG support."

Note core's default map contains **no `svg` entry at all** (zero occurrences in `wp_get_mime_types()`), but it does ship `htm|html`, `js`, `css`, `zip`, `rar`, `7z`, `class`, `mdb`, `swf`, `exe`. It is a Media Library list for trusted authors, not an allowlist for anonymous strangers.

### The hard-blocked list (second, independent gate)

Applied to **every dot-separated segment of the filename, not just the last.** Any hit rejects the whole upload with no attempt at repair.

`php, php2, php3, php4, php5, php6, php7, php8, phps, pht, phtm, phtml, phar, inc, hphp, ctp, shtml, shtm, stm, cgi, pl, pm, py, pyc, pyo, rb, sh, bash, zsh, ksh, csh, lua, tcl, asp, aspx, ascx, ashx, asmx, asa, asax, cer, cdx, jsp, jspx, jspf, jsw, jsv, jhtml, war, jar, class, java, exe, dll, com, bat, cmd, msi, msp, msc, scr, cpl, sys, drv, pif, vb, vbe, vbs, ws, wsf, wsc, wsh, ps1, ps2, psc1, psc2, hta, lnk, reg, gadget, application, htaccess, htpasswd, htgroup, ini, conf, config, so, dylib, elf, bin, run, app, deb, rpm, pkg, dmg, sql, db, sqlite, svg, svgz, html, htm, xhtml, js, mjs, cjs, xml, xsl, xslt, mht, mhtml`

The allowlist is the gate; this list is the tripwire. A file must pass both. This exists because **Gravity Forms CVE-2025-12974 was a denylist that omitted `.phar`** and nothing else: the vulnerable pattern is literally `if ( in_array( $extension, $blacklist ) ) { // Reject upload }`. Every-segment matching is what catches `shell.phar.jpg` on servers where Apache's mod_mime does what it documents: "Files can have more than one extension; the order of the extensions is normally irrelevant" and "Care should be taken when a file with multiple extensions gets associated with both a media-type and a handler. This will usually result in the request being handled by the module associated with the handler."

### Validation order, fail closed at every step, no repair, no coercion

0. `REQUEST_METHOD === 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0` returns a specific `devform_post_max_size_exceeded` error. When POST data exceeds `post_max_size`, "the $_POST and $_FILES superglobals are empty" with no error code, so the nonce, the form id and the files are all gone. **Put the form key in the endpoint's query string / path, not the body, so it survives this case.**
1. `$_FILES[...]['error'] === UPLOAD_ERR_OK`, with a distinct message per `UPLOAD_ERR_*` value (1 INI_SIZE, 2 FORM_SIZE, 3 PARTIAL, 4 NO_FILE, 6 NO_TMP_DIR, 7 CANT_WRITE, 8 EXTENSION; 5 is unused).
2. `is_uploaded_file( $tmp_name )`. Mandatory, no exceptions.
3. **Resolve field config from the stored form definition only.** Ignore every config-shaped key in the request body. Not merged, not defaulted-to, **ignored**. Treat the POST as containing only field VALUES, never field CONFIGURATION.
4. File count against `min( field setting, ini max_file_uploads )`.
5. Size from `filesize( $tmp_name )`, never `$_FILES['size']`, against `min( field setting, upload_max_filesize, post_max_size )`.
6. Filename: `wp_basename()` then strip `/[\pC\pZ]+/u` then `sanitize_file_name()` then cap the base at 100 bytes.
7. Segment scan against the hard-blocked list, every segment.
8. Final extension must be in (global allowlist INTERSECT per-form allowlist).
9. `finfo_file(FILEINFO_MIME_TYPE)` must equal the strict expected MIME for that extension. For images, `exif_imagetype()`/`getimagesize()` must also agree. **Document this internally as a data-integrity check, not a security control**, so nobody later relaxes something else because "we check content."
10. Images: re-encode through `WP_Image_Editor`. Reject if the re-encode fails. Gate on a size cap and reject oversized images rather than skipping the re-encode.
11. **Discard the user's extension and filename entirely.** On-disk name is `bin2hex(random_bytes(16)) . '.' . $derived_ext`. Original name goes in a DB column, escaped on output only.
12. `move_uploaded_file()` into the target directory, then `chmod 0400` (CF7's choice).

**Use `wp_handle_upload`, never `wp_handle_sideload`.** They are both thin wrappers around `_wp_handle_upload()`, and the action string drives two branches: `$test_uploaded_file = 'wp_handle_upload' === $action ? is_uploaded_file(...) : @is_readable(...)`, and the move is `move_uploaded_file()` versus `copy()`. Sideload downgrades exactly the check that stops an attacker pointing `tmp_name` at an arbitrary server path.

Pass `test_form => false` (there is no `$_POST['action']` on a REST endpoint) and always pass an explicit `mimes` array in `$overrides`. Never add types via the site-global `upload_mimes` filter. Also note: `wp_handle_upload()` **physically cannot write outside `wp_upload_dir()`** (`$new_file = $uploads['path'] . "/$filename"` is hardcoded, with no destination override key). The only escapes are the `upload_dir` filter, the `pre_move_uploaded_file` filter, or hand-rolling the move after an equivalent validation chain. **The third option is cleanest** and also avoids the global filter path entirely.

### Storage

```
wp-content/uploads/devform/{hmac_of_site_salt}/pending/{session_uuid}/
wp-content/uploads/devform/{hmac_of_site_salt}/entries/{entry_uuid}/{file_uuid}.{ext}
```

Guard files written on **every request** via a stat-cached check (WPForms' `File::is_file_updated` pattern, so a host migration or a security plugin wiping them self-heals):

1. `.htaccess` with Contact Form 7's content, which is stronger than WPForms' because it also blocks direct reads:
   ```
   # Apache 2.4+
   <IfModule authz_core_module>
       Require all denied
   </IfModule>

   # Apache 2.2
   <IfModule !authz_core_module>
       Deny from all
   </IfModule>
   ```
2. `index.html` in every directory.
3. `web.config` with `<handlers><clear/></handlers>` for IIS.
4. A `DEVFORM_UPLOAD_DIR` constant so a competent developer can point storage outside the docroot. OWASP's priority order is: different host > outside the webroot > inside the webroot with write-only permissions and access control. Option 2 is achievable in WordPress; default to uploads because it is always writable, but make the serving proxy the only read path so nothing breaks when someone moves it.

**WordPress core writes no `.htaccess` and no `index.php` into `wp-content/uploads`.** Verified against the shipped WP 7.0.4 zip: grepping all of wp-admin/includes and wp-includes for `.htaccess` returns only the site-root rewrite and multisite paths. No uploads directory is shipped at all. Uploads hardening is 100% the plugin's job, on every install, forever.

**The nginx hole is the biggest unaddressed problem in the whole category.** nginx has no per-directory runtime config; "Changes made in the configuration file will not be applied until the command to reload configuration is sent to nginx or it is restarted." Gravity Forms' own File Upload Security doc describes an `.htaccess` plus `index.html` files and says nothing about nginx. CF7 writes `Require all denied`, ignored. WPForms writes `SetHandler none` / `RemoveHandler .cgi .php .php3 .php4 .php5 .phtml .pl .py .pyc .pyo` / `php_flag engine off`, ignored. On nginx every one of these plugins' uploads is directly reachable at a public URL and PHP execution depends entirely on the host's `location ~ \.php$` block.

**DevForm's answer must not be an .htaccess.** It is: (a) never write an executable extension, (b) store under a path the plugin controls and serve through PHP, (c) ship a Site Health check that actually HTTP-fetches a canary file from the upload directory and reports two booleans: *publicly readable* and *PHP executes here*. No competitor has that check.

Also steal from CF7: a random subdirectory. But not their implementation. `wpcf7_maybe_add_random_dir()` uses `zeroise( wp_rand(), 10 )`, at most about 31 bits, and on nginx that obscurity is the only protection since the .htaccess is inert. Use `bin2hex(random_bytes(16))`.

### Never attach to the Media Library

Verified in `wp-includes/post.php`: the `attachment` post type registers with `'public' => true`, `'show_in_rest' => true`, `'rest_base' => 'media'`, `'rest_controller_class' => 'WP_REST_Attachments_Controller'`. That controller extends `WP_REST_Posts_Controller`, whose `get_items_permissions_check()` returns bare `true` unless `'edit' === $request['context']`. So `GET /wp-json/wp/v2/media` is **anonymous-readable** and every item carries a read-only `source_url` in view, edit and embed contexts.

Attaching a job applicant's CV or an ID scan to the Media Library publishes it to the open internet. Store form uploads as plain files with a private DB row. "Copy to Media Library" can exist as an explicit, per-form, off-by-default action with a plain-language warning.

### Serving

Endpoint: `/wp-json/devform/v1/file/{entry_uuid}/{file_uuid}`. Random 128-bit ids, never sequential integers.

Default: capability check (`edit_devform_entries`, filterable per form). Opt-in alternative for "let the submitter re-download their own file": an HMAC-signed expiring token over `entry|file|exp` keyed on `wp_salt()`, compared with `hash_equals()`.

Fixed response headers:

```
Content-Type: <from DevForm's own extension map, never from the file, never from the request>
X-Content-Type-Options: nosniff
Content-Disposition: attachment; filename="cv.pdf"; filename*=UTF-8''cv.pdf
Cache-Control: private, no-store
Referrer-Policy: no-referrer
Content-Security-Policy: default-src 'none'; sandbox
```

`nosniff` alone is not enough. MDN: it "prevents MIME type sniffing... causing the browser to use the declared Content-Type without examining the response content", but it does **not** stop a correctly-declared active type from executing. An SVG served as `image/svg+xml` in a top-level navigation still runs its script. Hence `Content-Disposition: attachment`, which WPForms only learned the hard way. Use both `filename` and `filename*=UTF-8''`, and avoid percent escapes in `filename` because Firefox and Chrome decode them while Safari does not. An "inline preview" toggle should be restricted to the re-encoded image types only.

**Invert Gravity Forms' posture.** Their docs state plainly that download URLs are "not access-controlled", meaning "anyone with the correct file URL can download uploaded files without requiring any additional authentication", with a salted HMAC-MD5 folder suffix as "a security token to prevent guessing or enumeration attacks." Capability-URL semantics are defensible for a marketing form and indefensible for a CV or an ID scan: any leak of the URL (referrer header, forwarded email, browser sync, proxy log) is a permanent breach. DevForm: capability check by default, signed expiring links as the explicit opt-in.

### Per-form overrides narrow only

`apply_filters( 'devform_allowed_upload_extensions', $exts, $form_id, $field_id )` is **intersected** with the global allowlist and then re-run through the hard-blocked list, in that order. A filter can never widen past the ceiling and can never reintroduce a blocked extension. Widening the global list requires an explicit `DEVFORM_ALLOW_EXTRA_UPLOAD_TYPES` constant, mirroring `ALLOW_UNFILTERED_UPLOADS`, and even then the hard-blocked list is absolute. SVG has no path in at all.

(Note: `ALLOW_UNFILTERED_UPLOADS` plus a logged-in admin would let any type through `wp_handle_upload`. DevForm's own allowlist must be enforced *before* core is ever called so that constant cannot widen a public form endpoint.)

### Scanning

Ship none.

1. **ClamAV is out.** Its own Docker guide states recommended RAM is minimum 3 GiB / preferred 4 GiB because "ClamAV uses upwards of 1.2 GiB of RAM simply to load the signature definitions", roughly doubling during the daily database reload. The memory alone exceeds a typical shared PHP `memory_limit` by an order of magnitude, before you even ask whether `exec()` is available or the binary is installed.
2. **VirusTotal is out.** Public API is "500 requests per day and a rate of 4 requests per minute", plus "The Public API must not be used in commercial products or services."
3. Ship `apply_filters( 'devform_scan_upload', true, $tmp_path, $context )` returning `true` or a `WP_Error`, document it, and let hosts, security plugins or a paid add-on attach. DevForm's posture must not depend on scanning at all.

### Cleanup

1. Uploads land in `pending/` during the upload step; only a successful, validated submission moves them into `entries/{uuid}/`.
2. Bounded sweeper on the `shutdown` hook, capped at ~50 deletions per request, fired probabilistically to avoid a stampede. This is Contact Form 7's pattern: `add_action( 'shutdown', 'wpcf7_cleanup_upload_files', 20, 0 )` with `wpcf7_cleanup_upload_files( $seconds = 60, $max = 100 )` that skips anything where `time() < $mtime + $seconds` and breaks at `$max`.
3. `realpath()` containment check against the DevForm upload root before every `unlink()`.
4. Absolute paths only. The working directory changes during PHP shutdown.
5. If Action Scheduler happens to be present, use it for the bulk retention sweep and keep the shutdown sweeper as fallback. Never wp-cron on the production path.

### What not to build in v1

**No chunked or resumable uploads.** Two of Gravity Forms' three 2025 upload CVEs were in that path, for the structural reason that validation and assembly happen at different moments on different names. CVE-2025-13407 (CVSS 10, Marc Montpas) is literally "does not properly prevent users from uploading dangerous files through its chunked upload functionality." If it is ever added: validate the assembled file under its final derived name, in a staging directory that is itself non-public and non-executable.

### CVE evidence, by rule

| DevForm rule | CVE / evidence |
|---|---|
| Allowlist, never denylist | **Gravity Forms CVE-2025-12974** (<= 2.9.21.1): extension blacklist omitted `.phar`, reachable through the legacy chunked upload endpoint |
| No chunked uploads in v1 | **GF CVE-2025-13407** (< 2.9.23.1, CVSS 10) chunked upload type validation; **GF CVE-2025-12974** same path |
| Never trust remote URLs / validate everything you copy | **GF CVE-2025-12352** (<= 2.9.20, CVSS 9.8): `copy_post_image()` copies files from external URLs to uploads without validating type or extension when `allow_url_fopen` is On |
| **Config from the stored form only, never the request** | **Forminator CVE-2026-15748** (<= 1.56.1, CVSS 9.8, patched 1.56.2, published 17 Aug 2026, flagged "Known to be exploited"): "unauthenticated arbitrary file upload via forged upload field configuration". This is the single most important finding for Mode B architecture. |
| Same, plus authz checks | **Forminator CVE-2023-4596** (<= 1.24.6, CVSS 9.8, mass-exploited) |
| Never pass attacker-influenced paths to filesystem functions | **Forminator CVE-2025-6464** (<= 1.44.2, CVSS 7.5): "Unauthenticated PHP Object Injection (PHAR) Triggered via Administrator Form Submission Deletion" |
| Unicode-aware filename stripping `[\pC\pZ]` | **Contact Form 7 CVE-2020-35489** (< 5.3.2): filename separator bug, `test.php<tab>.jpg` landed as `test.php` |
| Hard-block `.phar` (two reasons) | executable as PHP by the server; and on PHP 7.x any file function touching a `phar://` path triggers deserialization. PHP 8.0 UPGRADING: "Metadata associated with a phar will no longer be automatically unserialized" |
| Never accept SVG | **SVG Support**: 8 patched vulnerabilities including **CVE-2026-13340** (< 2.5.17, published 18 Aug 2026) "Author+ Stored XSS via .svgz Sanitization Bypass". **Safe SVG** (which wraps the canonical `enshrined/svg-sanitize`): 4, including **CVE-2024-8378** and a sanitization bypass at <= 1.9.9. The best sanitiser in PHP is wrapped by a plugin that still shipped a bypass. |
| Never enable `LIBXML_PARSEHUGE` | verified locally: `loadXML($billionLaughs)` with no flags fails with "Detected an entity reference loop", but `loadXML($bomb, LIBXML_PARSEHUGE\|LIBXML_NOENT)` succeeds and expands at three nesting levels. `enshrined/svg-sanitize` passes that flag when `setAllowHugeFiles(true)` |
| No authz check on the handler | **Chaty Pro CVE-2025-26776** (<= 3.3.3, CVSS 10, ~18,000 installs): `chaty_front_form_save_data()` had no authorization or nonce check, defined a `$file_allowed` array that was never enforced, called `move_uploaded_file()` on unsanitized data, and used `timestamp+rand(100,1000)` filenames |
| Random 128-bit ids, never sequential | **Forminator**: IDOR to unauthenticated disclosure (<= 1.55.0.2), Arbitrary File Download (<= 1.55.0.2), Unauthenticated Arbitrary File Read (<= 1.52.1), IDOR to Submission Manipulation (<= 1.36.0). **WPForms**: Authenticated Arbitrary File Access (<= 1.7.5.3). **Ninja Forms**: IDOR / Info Exposure (<= 3.13.2), Arbitrary File Deletion (<= 3.6.24) |
| Others in the roster | WordPress File Upload CVE-2024-11613 (<= 4.24.15, CVSS 10, RCE + file read + file deletion); Form Block CVE-2025-54693 (<= 1.5.5, CVSS 9, "mass-exploit campaigns"); WooCommerce Upload Files CVE-2024-10820 (<= 84.3, CVSS 9.8); User Submitted Posts CVE-2023-45603 (<= 20230902, CVSS 9.0) |

Note on advisory sources: the free unauthenticated Wordfence Intelligence v2 feed now returns HTTP 410 ("The requested version of this API has now been removed"), v3 returns 401 requiring a Bearer token, and wpscan.com vulnerability pages return 403. **Patchstack's public database is the only source that answers unauthenticated.** Worth knowing before anyone plans a security-advisory feature.

### Effective limits must be computed and surfaced

PHP defaults: `upload_max_filesize` 2M, `post_max_size` 8M, `max_file_uploads` 20, `memory_limit` 128M. The first three are **INI_PERDIR**, so a plugin cannot raise them with `ini_set()`; only `memory_limit` is INI_ALL.

1. Effective per-file cap = `min( per-field setting, upload_max_filesize, post_max_size )`.
2. Effective per-request cap = `min( post_max_size, sum of file sizes + field data )`.
3. Effective file count = `min( per-field setting, max_file_uploads )`. Note "Upload fields left blank on submission do not count towards this limit."

Compute all three, render them in the admin UI **and in the Mode B schema response**, enforce client-side as a courtesy and server-side as the rule.

---

## 5. Anti-spam stack

Four layers, ordered so the free local layers run first and the metered remote ones only ever see traffic that survived them. That ordering is the whole design, because it is what makes Akismet's 500-calls-per-month commercial ceiling survivable.

### Layer 0: always on, no config, no network

1. Honeypot field plus a JS-written timestamp time trap (reject under about 2 seconds, over about 1 hour).
2. Per-IP rate limiting (see section 3).
3. Copy Akismet's own field pattern: a visually hidden `<textarea name="ak_hp_textarea" maxlength="100">` equivalent, plus a hidden input seeded with a random integer that an inline script overwrites with `Date.now()`.
4. **Document the two field names as part of the Mode B contract** so a headless developer can include them.
5. Costs nothing, discloses nothing, requires no privacy policy change.

Evidence: there is **no primary quantitative study** of honeypot or time-trap catch rates that I could find, and the search budget was exhausted before regulator and academic sources could be swept. **Do not put a catch-rate percentage in DevForm's marketing.** The argument is behavioural: Akismet, the largest anti-spam vendor in WordPress, ships both as its own front line alongside its paid API, formalises honeypots as a first-class API concept via the `honeypot_field_name` parameter, and its 5.7 changelog (23 April 2026) reads "Improve detection of automated spam submissions on the front end."

### Layer 1: on by default, self-hosted, bundled

**ALTCHA proof-of-work.** MIT licensed (`altcha-org/altcha`, 2,677 stars, last pushed 19 Aug 2026), with an official MIT PHP library (`altcha-org/altcha-lib-php`). MIT is GPL-compatible, so bundle it and serve the challenge from DevForm's own REST endpoints. No external call, no cookie, no account, nothing to add to a privacy policy, and nothing that trips wordpress.org guideline 7. There is essentially no ALTCHA presence in the WordPress plugin directory today.

Mechanism: the server generates a random salt (at least ~10 chars) plus a hidden secret number, concatenates and hashes them to form the challenge, and HMAC-signs it; the client iterates integers hashing salt+n until it matches; the server recomputes the challenge and revalidates the HMAC, requiring both.

**Be straight in the docs about its limit:** proof-of-work imposes cost, it does not identify bots. It kills volume spam and does nothing against a targeted attacker willing to burn CPU. It also taxes low-end mobile devices. And see section 10: **in Mode B there is no widget**, so ALTCHA cannot be the layer-1 default for headless submissions.

### Layer 2: opt-in, one key pair, recommended in setup

**Cloudflare Turnstile**, implemented natively.

1. Validation: `POST https://challenges.cloudflare.com/turnstile/v0/siteverify`, accepting `application/x-www-form-urlencoded` or JSON, always returning JSON. Parameters: `secret` (required), `response` (required, from the `cf-turnstile-response` field), `remoteip` (optional), `idempotency_key` (optional UUID for safe retries).
2. Response: `success`, `challenge_ts`, `hostname`, `error-codes[]`, `action`, `cdata`, `metadata`.
3. Tokens: max 2048 chars, valid **300 seconds**, single-use; replays fail with `timeout-or-duplicate`. **Handle expiry with `turnstile.reset()`** on long forms.
4. Free plan: unlimited challenges and verification requests, up to 20 widgets per account, 10 hostnames per widget, all three widget modes (Managed, Non-Interactive, Invisible), pre-clearance support, 7-day analytics. "Turnstile can be embedded into any website without sending traffic through Cloudflare."
5. **If the user selects Invisible mode, DevForm must automatically inject the Turnstile Privacy Addendum reference into the form's privacy notice.** Cloudflare states it as a condition: "As a condition of enabling invisible mode, you must reference Cloudflare's Turnstile Privacy Addendum in your own privacy policy." The site owner will otherwise breach it without knowing.
6. Doc inconsistency to be aware of: the Turnstile index page says "WCAG 2.2 AA compliant", the plans page column says "WCAG 2.2 AAA compliance".
7. **There is no official Cloudflare Turnstile WordPress plugin.** The category leader is a third-party plugin (`simple-cloudflare-turnstile`, 200,000 installs); nothing else is close (600 to 1,000 installs each). For comparison, hCaptcha's own first-party plugin has 70,000. A free form plugin with first-class built-in Turnstile removes a plugin from every user's stack and sidesteps the fragile pattern where a generic CAPTCHA plugin has to detect and patch each form plugin.

### Layer 3: opt-in, off by default, "you already have a key"

**Akismet.**

**The decisive constraint is pricing.** Personal is "Name your price / Pay what you can" and non-commercial only; error code 30001 enforces it ("Our Personal subscription is intended solely for use with personal, non-commercial sites and blogs... any sites created mainly to serve commercial purposes require one of our premium subscriptions"), hard-coded in the plugin as `Akismet::ALERT_CODE_COMMERCIAL = 30001`. The entry commercial plan is **EUR 9.95/month billed yearly for 500 spam calls/month on one site** (Business is EUR 47.95/month for unlimited sites and 5,000 monthly checks). A spam check is defined as "each time Akismet checks a comment, form submission, or other content for spam." 500 checks a month is roughly 16 submissions a day including spam; one scripted run exhausts it. (Prices are geo-localised and were fetched from a Netherlands IP, so these are EUR figures; USD needs a re-check.)

DevForm's target user is theme builders and agencies building custom client sites, which is exactly the commercial case that cannot use the free tier. **Akismet cannot be the default.**

Integration path:

1. **Do not store or transmit the user's raw API key, and do not ship your own HTTP client.** Call the installed plugin's public statics. Feature-detect the way Jetpack and CF7 do: `is_callable( ['Akismet','comment_check'] )` first, `method_exists( 'Akismet', 'http_post' )` second.
2. `Akismet::comment_check( array $comment_data, ?string $api_key = null ) : object|false` is new in 5.7 (released 23 April 2026) and merges in `blog`, `blog_lang`, `blog_charset`, `user_ip`, `user_agent` automatically. I verified by downloading 5.3.7, 5.4, 5.5, 5.6 and 5.7 that it does not exist before 5.7. **Do not declare a hard 5.7 dependency.** `Akismet::http_post( string $request, string $path, ?string $ip = null ) : array` is present at least as far back as 4.2.5, returns `[$headers, $body]`, and injects the site's api_key itself.
3. **There is no generic integration hook.** `Akismet::init_hooks()` hard-codes exactly five form products by hook name (Jetpack, Gravity Forms, CF7, Formidable, Fluent Forms), and `load_form_js_via_filter()` whitelists five shortcodes. The "Compatible plugins" panel added in 5.4 is driven by a remote allowlist at `https://rest.akismet.com/1.2/compatible-plugins`, which currently returns exactly 12 entries. Getting DevForm listed requires Automattic to add it server-side; worth a conversation once there are installs, not a launch blocker.
4. Send `comment_type` (see the contradiction in section 10), map named fields to `comment_author` / `comment_author_email` / `comment_author_url`, and send **every other freely typed field** as `contact_form_field_[normalized_label]`. Akismet's guide is explicit: "For every other field in your form that is free text typed by the user, send each as its own parameter formatted like contact_form_field_fieldname", with a `Phone Number` field becoming `contact_form_field_phone_number`. Two hard rules: omit a parameter entirely rather than sending it blank, and "Do not include any content in your request that was not freely typed by the user. For example, selections from dropdowns and the labels of checked checkboxes should be omitted."
   **Neither CF7 nor Jetpack implements this convention.** CF7 concatenates everything into `comment_content`; Jetpack merges the raw form array plus `$_SERVER`. DevForm's field schema already knows which fields are text versus select, so it can implement it correctly and automatically. Free accuracy win.
5. Pass `honeypot_field_name` plus the honeypot value so layer 0 feeds layer 3.
6. Reuse `Akismet::get_akismet_form_fields()`, `Akismet::load_form_js()` and `Akismet::prepare_custom_form_values()` to pick up the behavioural `ak_b*` signals (keypress counts and timings, mouse click timings and coordinates, modifier and correction keys, mousemove counts, touch, scroll count). The file header states "no actual input is being saved here, only counts and timings between events."
7. **Strip `ak_*` out of stored entries, emails and webhook payloads**, the way CF7 does with its 14 `ak_b*` keys, or every submission record gets polluted.
8. **Implement `submit-spam` and `submit-ham`.** Akismet's own launch checklist demands it and Contact Form 7, at 10M installs, contains no such call at all. Persist the original request payload for exactly **15 days**, matching Jetpack (`_feedback_akismet_values` post meta deleted by `daily_akismet_meta_cleanup()`) and Akismet's own internal retention. That is the minimum needed to support the calls and it is a defensible GDPR limit.
9. **Do not copy CF7's opt-in field tagging.** `wpcf7_akismet_submitted_params()` returns `false`, skipping Akismet entirely, unless at least one form tag carries an `akismet:author` / `akismet:author_email` / `akismet:author_url` option. That silently disables protection for anyone who does not know about it.
10. **Guard on `get_option('akismet_ssl_disabled')` and skip the layer while it is set.** `Akismet::http_post()` builds `http://{$http_host}/1.1/{$path}` and upgrades to https only if `wp_http_supports(['ssl'])` and that option is unset. On a `WP_Error` it retries HTTPS once, then retries plain HTTP; success there sets `update_option('akismet_ssl_disabled', time())`, suppressing HTTPS **for 24 hours**. The class still defines `API_PORT = 80`. Every field of a submission can transit in cleartext for a day after one transient TLS failure. DevForm claims a hard security posture; fail open to the local filters instead.
11. Use the `X-akismet-pro-tip: discard` header to drive the existing per-form "store entries or fire-and-forget" switch. Akismet measures discard at "approximately 80% of spam", so it is a real storage saving. Model **three outcomes, not two**: ham, spam (store, flag, do not run notification actions), discard (drop, store nothing).
12. Other response headers to read: `X-akismet-recheck-after` (seconds, on ham responses, implies a deferred re-check that needs a scheduler; given the wp-cron constraint, either use the retry queue or document that recheck is unsupported), `X-akismet-debug-help`, `x-akismet-guid`, `x-akismet-error`.
13. Test triggers for the integration test suite: `comment_author` = `akismet-guaranteed-spam` or `comment_author_email` = `akismet-guaranteed-spam@example.com` always returns true; `user_role=administrator` with `is_test=true` always returns false. **Footgun:** `user_role=administrator` alone always returns false, so do not pass through logged-in roles blindly.
14. Surface `GET https://rest.akismet.com/1.2/usage-limit` (returns `{limit, usage, percentage, throttled}`, where `limit` is an integer or the string `"none"`) as a **live quota meter in the DevForm admin**, with a message showing how many calls layers 0 to 2 saved this month. That turns the ordering decision into a visible benefit. Nobody else does it.

API surface, for reference: four POST endpoints on `rest.akismet.com` (`/1.1/comment-check`, `/1.1/submit-spam`, `/1.1/submit-ham`, `/1.1/verify-key`) plus a GET-or-POST `/1.2/usage-limit`. All form-urlencoded with plain-text bodies, not JSON. `comment-check` returns the literal string `true` or `false` with `Content-Type: text/plain`. Required: `api_key`, `blog` (full URI including scheme), `user_ip`. Strongly recommended: `user_agent`, `referrer` (single-r spelling, unlike the HTTP header), `permalink`, `comment_type`, `comment_author`, `comment_author_email`, `comment_author_url`, `comment_content`, `comment_date_gmt` (ISO 8601), `comment_post_modified_gmt`, `blog_lang`, `blog_charset`, `user_role`, `is_test`, `recheck_reason`, `honeypot_field_name`, `comment_context[]`.

Do **not** bundle `Automattic/akismet-sdk-php` (the official PHP SDK). Calling the installed plugin's statics avoids a second HTTP path plus a dependency to keep current. But `Automattic/akismet-api` (the official OpenAPI spec, last pushed 2026-07-26) is the authoritative parameter list to check DevForm's request builder against; pin a note to re-read it before release since it changed recently.

### Explicitly not shipping

1. **reCAPTCHA.** Free tier is now "10,000 assessments" that are "per organization. The limit aggregates use across all accounts and all sites" (then $8 flat to 100k, then $1 per 1,000). Disqualifying for an agency. Worse, the legacy v3 path fails open: "If a v3 site key exceeds its monthly quota, then site_verify may fail open by returning a static score 0.9 and an error message 'Over free quota.' for the remainder of the month. There are no user-visible indications when v3 sites are over quota." An over-quota site silently accepts all spam. If DevForm supports it at all for migration compatibility, it must detect the `Over free quota.` string and warn loudly. It also sets a `_GRECAPTCHA` cookie and requires visible attribution if the badge is hidden.
   **Do not make a legal claim about reCAPTCHA in the EU.** I could not verify any specific EU data protection authority decision against it; the frequently cited 2022 German ruling concerned Google Fonts, which is a different matter. Argue on the documented grounds above instead.
2. **Stop Forum Spam.** Their terms: "Your use of this data and supporting software is non-commercial." Combined with guideline 5's requirement to comply with third-party API terms, shipping it in a free plugin aimed at commercial site builders is a licensing and review risk. (Their `europe.stopforumspam.org` regional endpoint, offered "for compliance with company or country privacy regulations", is a nice pattern to remember if DevForm ever builds its own reputation service.)
3. **hCaptcha as a recommendation.** Basic is genuinely free, but "Low Friction 99.9% Passive Mode" is Pro at $99/month billed yearly, and full "Passive (No-CAPTCHA) Mode" is Enterprise. The free tier is the friction-bearing tier, which is the opposite of what DevForm wants. Support it if asked; never put it ahead of Turnstile in the setup wizard.

### GDPR note

Disclosure yes, consent no. That is the **processor's own stated position**: akismet.com/gdpr says "only the personal data needed to carry out its core function... In the language of the GDPR, this is a 'legitimate interest' use of that data. By displaying the notice of 'This site uses Akismet to reduce spam...' (which can be enabled in the plugin settings), you're letting visitors know that Akismet is collecting data for our legitimate interest and how we're processing it." Retention: "short retention periods of between two weeks and ninety days for the vast majority of our spam-related data." Transfers outside the EU on Standard Contractual Clauses; a DPA is available on request.

Cloudflare's Turnstile Privacy Addendum is comparably clean: it processes "client IP address, TLS Fingerprint, User-Agent Header and Sitekey and associated origin", asserts "Cloudflare does not have the ability to directly identify any individuals from any of the Signals", splits roles (processor for providing the service, controller for improving bot detection under legitimate interests), calls the signals "strictly necessary", and the Turnstile docs add "Turnstile does not access, store, or transmit user communications, form entries, or other page inputs." DPO contact is dpo@cloudflare.com.

DevForm's obligations:

1. Call `wp_add_privacy_policy_content()` with accurate text naming **every** service DevForm can call. Akismet's own suggested text is a good template: it names "the commenter's IP address, user agent, referrer, and Site URL... their name, username, email address, and the comment itself."
2. Ship the per-form privacy notice **ON by default** whenever an external spam service is enabled. Akismet defaults its notice to `'hide'` with the source comment "Default is to not display the notice, leaving the choice to site admins, or integrators." Do better; DevForm's users are agencies who will not read any of this.
3. Register both `wp_privacy_personal_data_exporters` and `wp_privacy_personal_data_erasers` for stored entries. That is what makes "GDPR compliance is in scope" a real claim rather than a bullet point, and no free form plugin does it well. Akismet's own eraser deletes the local `akismet_as_submitted` comment meta with the note "this information would be automatically deleted after 15 days."
4. Be honest in the docs that **Akismet accuracy is materially lower in Mode B**, because none of the `akismet-frontend.js` behavioural signals exist for a headless submission. Do not claim parity.

Also: the Akismet Abilities API registered in 5.7 (`akismet/comment-check` for WP 6.9+'s `wp_register_ability()`) is **not usable for front-end submissions**, because `Akismet_Ability::current_user_has_permission()` returns `current_user_can('moderate_comments')`. It is an admin and agent tool. It is a reasonable model for DevForm's own admin-side "recheck this entry" action, and note its input schema conspicuously omits `contact_form_field_*` and `honeypot_field_name`, reinforcing that it was built for comments.

---

## 6. Async, retries and retention

### Why wp-cron is out, precisely

1. The Plugin Handbook: "WP-Cron works by checking, on every page load, a list of scheduled tasks" and "WP-Cron does not run constantly as the system cron does; it is only triggered on page load." Its own canonical failure case: "Scheduling errors could occur if you schedule a task for 2:00PM and no page loads occur until 5:00PM."
2. `wp_schedule_single_event()` **silently ignores** an event scheduled within 10 minutes of an existing event with the same hook unless `$args` differ. Per-submission retry jobs would silently collapse into one.
3. `WP_CRON_LOCK_TIMEOUT` defaults to 60 seconds, so at most one cron spawn per minute.
4. Managed hosts already disable it, with wildly different cadence. **Kinsta** disables WP-Cron by default and runs a server cron every **15 minutes**, citing missed schedules, PHP thread starvation ("WordPress will spawn the cron, but the cron has to wait for the worker, and therefore just sits there") and wp-cron.php being a public DoS vector. **WP Engine's** Alternate Cron sets `DISABLE_WP_CRON` and pings wp-cron.php every **minute** with a hard 60-second timeout, over which you get a 502.
5. `ALTERNATE_WP_CRON` is worse: `spawn_cron()` implements it by appending a `doing_wp_cron` query arg and calling `wp_redirect()`, so it mutates the visitor's URL. The wp-config doc itself warns "This method has certain risks, since it depends on a non-native WordPress service." Useless for a headless Mode B POST that never renders a page. **Do not detect it, do not depend on it.**

Consequence: retry backoff granularity finer than about 15 minutes is unreliable if cron is the only drain path.

### The primitive core itself uses

`wp-cron.php` contains, verbatim:

```php
// Don't run cron until the request finishes, if possible.
if ( function_exists( 'fastcgi_finish_request' ) ) {
    fastcgi_finish_request();
} elseif ( function_exists( 'litespeed_finish_request' ) ) {
    litespeed_finish_request();
}
```

with `ignore_user_abort( true );` at the top and a `doing_cron` transient lock holding `sprintf( '%.22F', microtime( true ) )`.

So deferring work with `fastcgi_finish_request()` is not an exotic hack a reviewer will flag; it is literally what core does. Copy the shape exactly, including the LiteSpeed fallback.

Caveats from php.net, all of which need code:

1. FPM SAPI only. It does not exist under Apache mod_php, plain CGI, or the CLI server. Detect with `function_exists()`.
2. The script still occupies an FPM process, so heavy use can exhaust `pm.max_children` and cause gateway errors. Do not treat it as a free background worker.
3. Sessions stay locked; call `session_write_close()` **before** it. Same for any flock or DB lock.
4. Writing to the output buffer afterwards makes the script exit with no error message. Emit **zero output** after the call, and set `ignore_user_abort(true)`.
5. "The code continues to execute and is still subject to max_execution_time and set_time_limit() settings." So the deferred tail must be bounded.

`register_shutdown_function` / the WordPress `shutdown` action is the portable companion. Shutdown functions run after script completion, on `exit()`, and **even when max_execution_time is exceeded**: "That means even if a process is terminated for running too long, shutdown functions will still be called. Additionally, if the max_execution_time runs out while a shutdown function is running it will not be terminated." They do not run on SIGKILL. **The working directory changes during shutdown (typically to `/` or ServerRoot), so all file paths in the tail must be absolute.** Calling `exit()` inside one halts all remaining shutdown callbacks.

### Why not Action Scheduler

Action Scheduler is genuinely production-grade: `woocommerce/action-scheduler` 4.1.0 (2026-08-05), GPL-3.0-or-later, 8,173,466 Composer installs, "Specifically designed for distribution in WordPress plugins (and themes), no server access required", custom `actionscheduler_*` tables since 3.0, multi-copy version election on `plugins_loaded`. And **bundling it is not blocked by wordpress.org guideline 13**, which only covers libraries WordPress itself ships (jQuery, Atom Lib, SimplePie, PHPMailer, PHPass).

But it is the wrong default dependency here, for two concrete reasons:

1. **It still boots from WP-Cron.** `ActionScheduler_QueueRunner` declares `const WP_CRON_HOOK = 'action_scheduler_run_queue';` and `const WP_CRON_SCHEDULE = 'every_minute';`, registering via `wp_schedule_event()`. It also hooks `shutdown` for an async loopback runner. Going CLI-only requires installing a separate "Disable Default Queue Runner" plugin, and the docs warn that if you do, "you must run Action Scheduler via WP CLI or another method, otherwise no scheduled actions will be processed." Adopting it does not escape the thing the brief says to avoid.
2. **It has no retry mechanism at all.** `ActionScheduler_Abstract_QueueRunner::process_action()` installs an error handler that converts recoverable fatals to exceptions, then on catch calls `$this->store->mark_failure( $action_id )` and fires `action_scheduler_failed_execution`. The action is not retried and not rescheduled. For recurring actions it refuses to reschedule a consistently-failing action. Neither the FAQ nor the changelog mentions retries.

You have to own the attempt counter, the backoff table and the dead-letter record regardless. Owning the queue table too is a small marginal cost and removes a large dependency. Also note AS 4.1.0 declares `Requires at least: 6.8`, which would drag DevForm's WP minimum up.

**Detect it, do not bundle it.** If `function_exists('as_enqueue_async_action')` and the site owner opts in, route enqueues through Action Scheduler. About 40 lines, and it gives WooCommerce sites a shared, already-monitored queue.

### The recommended architecture

**One queue table plus one attempts table.**

```
{prefix}devform_jobs
  id, entry_id, form_id, action_type (email|webhook|redirect|tracking),
  payload JSON, status (pending|claimed|done|failed), attempts,
  next_attempt_at, claimed_at, claim_token, last_error, created_at
  INDEX (status, next_attempt_at)

{prefix}devform_job_attempts
  id, job_id, attempt_no, started_at, duration_ms, http_status,
  request_snapshot (redactable), response_snapshot (truncated ~4KB), error
```

**Four drain paths, in priority order.** A job enqueued by submission N is almost always executed by submission N itself.

1. **In-request tail (primary).** At the end of the submission handler: `session_write_close()`, then `fastcgi_finish_request()` or `litespeed_finish_request()`, then `ignore_user_abort(true)`, then drain. Bound it hard: **max 20 jobs, or 20 seconds, or 80% of `memory_limit`, whichever comes first.** (Action Scheduler's own calibration across millions of sites is batch 25, 30 second time limit, 90% memory, 1 concurrent batch. DevForm should sit slightly under that because it is running inside a visitor's request, not a dedicated runner.)
2. **Shutdown hook (portable fallback).** When `fastcgi_finish_request` does not exist, drain on `shutdown` with a tighter budget. Register a `register_shutdown_function` fatal-catcher that releases the lease and schedules a retry, so a fatal mid-job never strands a claimed row.
3. **Loopback dispatcher (tier 2, opt-out).** Only if due work remains after the tail. Use the `WP_Async_Request` shape: `wp_remote_post()` with `'timeout' => 5`, `'blocking' => false`, `'sslverify' => apply_filters('https_local_ssl_verify', false)`, validated on receipt with `check_ajax_referer()`. Rate-limit dispatch with a transient lock. **Expect it to fail on some hosts:** WordPress ships a Site Health test for exactly this ("Your site could not complete a loopback request... Loopback requests are used to run scheduled events"), and self-signed certs, staging HTTP auth, firewalls and hosts blocking self-requests all break it. `blocking => false` means you get no error back at all. Never rely on it alone.
4. **Safety net.** A WP-CLI command `wp devform queue run [--max-jobs] [--max-time]` documented for a real system crontab, plus a low-frequency wp-cron `devform_queue_safety_net` (every 5 minutes) that only runs if nothing has drained recently. Cron is the belt, not the trousers.

**Leases and locking.** Claim with a single `UPDATE ... SET status='claimed', claimed_at=NOW(), claim_token=? WHERE status='pending' AND next_attempt_at <= NOW() LIMIT 20`, then select by claim token. Treat `claimed_at` older than **5 minutes** as stalled and re-claimable (Action Scheduler's `action_scheduler_failure_period`). That makes concurrent drains from paths 1 to 4 safe without a global lock.

### Retry policy

**Nobody in the WordPress form space does retries.** Gravity Forms Webhooks documents none. WP Webhooks documents none (it has a Pro log feature, IP whitelist, access token, auth modes, and "Post-delay webhook triggers. (Triggers are fired before PHP shuts down to catch plugin changes)", but no retries and no dead-letter handling). WooCommerce core does not retry either: it counts consecutive non-2xx, accepts 200 to 302 as success, and after **5 failures** disables the webhook, firing `woocommerce_webhook_disabled_due_delivery_failures` (threshold filterable). This is unclaimed territory and it costs one extra table.

Recommended schedule for webhooks: **attempt 1 immediate, then +30s, +2m, +10m, +1h, +6h, +24h.** Seven attempts over about 31 hours, with plus or minus 20% jitter, capped at 24h between attempts. Deliberately between Svix's 8 attempts over 42 hours (immediate, 5s, 5m, 30m, 2h, 5h, 10h, 10h) and Stripe's 3 days, and coarse enough that a Kinsta-style 15-minute cron still lands the later attempts. Email gets a shorter schedule.

1. **Success is 2xx only.** Follow Stripe, not WooCommerce: "We consider redirect responses to webhook requests as failures." Send `redirection => 0`. Hookdeck's default is likewise "any non-2xx status code will trigger a retry".
2. **Request timeout 10 seconds default**, filterable. WooCommerce uses `MINUTE_IN_SECONDS`; that is wrong here because it holds an FPM child hostage. Hookdeck's destination timeout is 60s, but they are a cloud service, not a PHP worker.
3. Use **`wp_safe_remote_post()`**, not `wp_remote_post()`, for SSRF protection on user-supplied URLs.
4. **Cap retry age well under 48 hours** for anything that feeds Meta CAPI: Meta deduplicates only within 48 hours of the first event with a given `event_id`, so a queued conversion sitting in a failed-job table for two days will double-count rather than dedupe.
5. After exhaustion: `status=failed`, full attempt history retained, surfaced in a Delivery admin screen with per-attempt status codes and a Retry button. **A successful manual retry cancels pending automatic ones** (Hookdeck's rule).
6. **Endpoint auto-disable** on Svix's heuristic, not WooCommerce's: disable after failing for 5 days with at least 12 hours between the first and last failure. WooCommerce's naive 5-consecutive-failures trips on a single brief outage.
7. **Surface failures.** Add a Site Health test and a dismissible admin notice when failed deliveries exist. A silent dead-letter queue is worthless.
8. Recovery affordances worth copying from Svix: retry one, bulk-retry all failures since a date ("Recover Failed"), and "Replay Missing". Stripe allows manual resend for 15 days from the dashboard, 30 via CLI.

### Outbound webhook signing

Copy Svix's construction, not WooCommerce's. WooCommerce's `generate_signature()` is `base64_encode( hash_hmac( 'sha256', $json_payload, $secret, true ) )` over the payload alone, which has **no replay protection**.

```
devform-id: dlv_01JBX8QK3M7Z9V
devform-timestamp: 1755600000
devform-signature: v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=
```

1. Signed content is `"{id}.{timestamp}.{raw_body}"`, HMAC-SHA256. Stripe's variant is `"{timestamp}.{raw_body}"` with header `t=...,v1=<hex>`; Formspree copies Stripe exactly with `Formspree-Signature: t=<unix>,v1=<hex>`.
2. Space-delimited list of versioned signatures supports **rotation**: send two during a secret roll (Stripe allows up to 24h of overlap).
3. Documented tolerance: **5 minutes** (Stripe's default; Svix publishes no number). Stripe's own warning: "Don't use a tolerance value of 0."
4. Compare with `hash_equals()`.
5. **Sign the raw bytes.** Svix: "You need to use the raw request body when verifying webhooks." Formspree's docs enumerate the four failure modes they see in support: re-serializing JSON before signing, omitting the `t.` prefix, comparing against the whole header string instead of extracting `v1`, and using the API key instead of the webhook signing secret. Document all four in DevForm's receiver guide.
6. `devform-id` is **stable across retries**, so receivers can dedupe. DevForm is at-least-once; say so.
7. This same construction is what an optional HMAC-authenticated **inbound** Mode B mode would use for server-to-server callers.

### Deferral shape to copy from WooCommerce core

`wc-webhook-functions.php` registers `add_action( 'shutdown', 'wc_webhook_execute_queue' )`. `wc_webhook_process_delivery()` stashes webhooks in a global rather than delivering inline; `wc_webhook_execute_queue()` then enqueues `woocommerce_deliver_webhook_async`. Collect during the request, dispatch on shutdown, execute via the queue. Also copy their header set (`X-WC-Webhook-Source`, `-Topic`, `-Resource`, `-Event`, `-Signature`, `-ID`, `-Delivery-ID`) and their safety details (`wp_safe_remote_request`, `redirection => 0`, a delivery-ID header). Do not copy the 60-second timeout.

### What must NOT be deferred

The **Akismet check sits on the critical path**, because you need its verdict to build the response. Give it a short timeout and fail open. Same for any CAPTCHA verification.

### Retention

The competitive bar is low and mostly paywalled:

1. **WPForms** gates "Purge Entries Automatically" (Settings, General, Advanced, per form, in days) behind any paid plan; "The free Lite version does not support entry storage or auto-deletion." Scheduling in weeks or months, field-value filtering and export-before-delete are Elite-only via the Entry Automation addon.
2. **Gravity Forms** offers retain indefinitely / trash automatically / delete permanently automatically, one-day minimum, executed on "the daily cron task", and warns entries may persist "up to an additional day" because "deletion/trashing depends on WordPress's scheduled background job timing rather than precise clock intervals."
3. **Jetpack Forms** documents no retention setting at all.

DevForm shipping per-form and global auto-delete free, disabled by default, beats all three. And because DevForm drains its own queue on submission traffic plus CLI plus a cron safety net, it can promise tighter bounds on busy sites than Gravity Forms does.

Design:

1. **One batching contract, reused everywhere:** `purge_batch( $cursor ): array( 'done' => bool, 'cursor' => mixed )`, 200 rows per pass. This is exactly the contract WordPress's own privacy API uses: exporter and eraser callbacks take `( $email_address, $page = 1 )` and return `done`, and the handbook says "a well behaved plugin will limit the amount of data it attempts to erase per page" specifically "to prevent timeout issues." One routine, driven by the deferred tail, the safety-net cron, WP-CLI, or the privacy eraser.
2. **Never an unbounded `DELETE ... WHERE created_at < ?`.**
3. **Delete files first, then rows**, in the same batch, with absolute paths and a `realpath()` containment check. WPForms had to change this in 1.6.6: "once you delete the entire form entry, the associated file will be deleted" because "previously uploaded files were not affected when an entire entry was deleted." A retention purge that leaves orphaned uploads is a GDPR failure, not untidiness.
4. Fire `devform_before_entry_delete` and `devform_after_purge_batch` so integrators can extend.
5. In the UI, state the honest bound ("entries older than N days are removed within 24 hours"), not implied clock precision.
6. **Consider a shorter clock for files than for entry rows.** An entry row and a 20 MB PDF have different GDPR and disk-cost profiles.
7. Trashing gets free permanent deletion via core's existing daily `wp_scheduled_delete` event and `EMPTY_TRASH_DAYS`. Jetpack hooks its own `daily_akismet_meta_cleanup` onto that same event rather than scheduling a new one, batch-deleting meta older than 15 days with `LIMIT 10000` and before/after actions. That is a legitimate free ride for the *trash* layer even under the no-wp-cron rule, because you are not registering an event, you are attaching to one core already schedules. See the tension flagged in section 10.
8. **Retention must cover the queue.** `devform_job_attempts` rows contain submission data and are therefore in GDPR scope themselves. Proposed default: 30 days for successful attempts, 90 for failed, both filterable. And a queued or failed tracking dispatch holds hashed PII, so the retry table must honour the same deletion window as the entries it references. No source states how long WooCommerce retains its own webhook delivery logs; this is DevForm's own policy to set.

---

## 7. Validation, errors, accessibility and i18n

### The validation engine: use WordPress's own

WordPress core has **no general-purpose form validation API**. The Security handbook's "Validating Data" page offers only helper predicates (`is_email()`, `in_array()` with strict checking, `mb_strlen()`, `preg_match()`, `validate_file()`, `balanceTags()`) plus hand-rolled regex examples. No rules engine, no error collector, no message catalogue. That absence is itself the market gap.

But the REST API ships a substantial JSON Schema subset that is more than good enough:

1. `rest_validate_value_from_schema()` returns `true` or `WP_Error`.
2. `rest_sanitize_value_from_schema()` returns sanitized data or `WP_Error`.
3. The documented order is: "always first validate the data using rest_validate_value_from_schema, and then if that function returns true, sanitize the data using rest_sanitize_value_from_schema."
4. Types: string, null, number, integer, boolean, array, object, plus union types.
5. Keywords: `format` (date-time RFC3339, uri via `esc_url_raw`, email via `is_email`, ip, uuid, hex-color), `minLength`/`maxLength` (inclusive, multibyte-aware), `pattern` (ECMA 262), `minimum`/`maximum`/`exclusiveMinimum`/`exclusiveMaximum`/`multipleOf`, `items`/`minItems`/`maxItems`/`uniqueItems`, `properties`/`required`/`additionalProperties`/`patternProperties`/`minProperties`/`maxProperties`, `enum`, `oneOf`/`anyOf`.
6. Gotcha: supplying your own `sanitize_callback` on an arg means "the built-in JSON Schema validation will not apply."

**Do not write a validator from scratch and do not pull in a Composer dependency.** Model each field type's rules as a JSON Schema fragment and delegate primitives to core. One engine, zero dependencies, and it is already the thing every WordPress developer's REST client understands.

**Contact Form 7 has already migrated to exactly this architecture** and it validates the plan. Its SWV layer defines `wpcf7_swv_create_rule( $name, $properties )` and `wpcf7_swv_available_rules()` (a name-to-class map, filterable so third parties can register new rule types), ships 24 built-in rules (required, requiredfile, file, email, url, tel, number, date, time, enum, dayofweek, minlength, maxlength, minnumber, maxnumber, mindate, maxdate, minitems, maxitems, minfilesize, maxfilesize, stepnumber, all, any), assembles the per-form schema on an action (`add_action( 'wpcf7_swv_create_schema', 'wpcf7_swv_add_text_rules', 10, 2 )`), and every rule carries a `field` key and a resolved `error` string. It exposes the schema at a public REST GET endpoint.

**One schema, two modes.** A `DevForm_Schema` built per form on a `devform_build_schema` action. Mode A renders from it; Mode B validates a raw POST against it; the public GET endpoint publishes it so a decoupled front end can mirror validation without duplicating rules.

**`WP_Error` is the error collector.** It already supports multiple codes, multiple messages per code, arbitrary data per code (multiple data items per code since 5.6.0), and merge semantics via `merge_from()` / `export_to()`. Model a validation failure as `WP_Error` where the code is the field key or path, each rule failure `add()`s a message under that code, and `add_data()` carries `{ rule, params, value_excerpt }`. `merge_from()` then makes folding in errors from a `devform_validate_field` filter trivial. No custom exception hierarchy.

Borrow from the ecosystem without importing it: Laravel's three-layer message resolution (rule default, then per-field override, then per-form override) and its `bail` / `stopOnFirstFailure` semantics; Symfony's Constraint plus ConstraintValidator **split** as the registration contract for custom rules; Zod's array `path`. Skip Symfony's validation groups and Laravel's pipe-string DSL. One idea from Symfony's groups does map onto a real need though: validating the same schema in `render` context (which fields to show) versus `submit` context (which to require).

### Sanitisation table

The handbook's own priority order: **validate first (reject, do not repair), sanitize second, escape last.** "Validation is preferred over sanitization because validation is more specific." "Data validation should be performed as early as possible." "It is best to do the output escaping as late as possible, ideally as data is being outputted."

Sanitizing before validating destroys the evidence needed for a WCAG 3.3.3 correction suggestion: you cannot tell the user "that email is missing an @" if `sanitize_email()` already ate the bad characters.

| Field type | Validate | Sanitize (storage) | Escape (output) | autocomplete token |
|---|---|---|---|---|
| single-line text | `mb_strlen`, `preg_match` | `sanitize_text_field()` | `esc_html()` / `esc_attr()` | (per field) |
| textarea / multiline | `mb_strlen` | **`sanitize_textarea_field()`** | `esc_textarea()` | n/a |
| rich text (HTML wanted) | allowlist | `wp_kses( $c, $allowed, $protocols )` | `wp_kses_post()` | n/a |
| email | `is_email()` | `sanitize_email()` | `esc_html()` | `email` |
| url | `wp_allowed_protocols()` | `sanitize_url()` / `esc_url_raw()` | `esc_url()` | `url` |
| tel | `preg_match` E.164-ish | `sanitize_text_field()` | `esc_html()` | `tel` |
| integer | range check | `absint()` (non-negative only) or `(int)` | `esc_html()` | n/a |
| float | range check | `(float)` | `esc_html()` | n/a |
| select / radio / checkbox | `in_array( $v, $choices, true )` **strict** | n/a (already safelisted) | `esc_attr()` | (per field) |
| hidden / key | `preg_match` | `sanitize_key()` | `esc_attr()` | n/a |
| hex colour | pattern | `sanitize_hex_color()` | `esc_attr()` | n/a |
| css class | pattern | `sanitize_html_class()` | `esc_attr()` | n/a |
| file | see section 4 | `sanitize_file_name()` after `[\pC\pZ]` strip | `esc_html()` on the display name | n/a |
| name | `mb_strlen` | `sanitize_text_field()` | `esc_html()` | `given-name` / `family-name` / `name` |
| address | `mb_strlen` | `sanitize_text_field()` | `esc_html()` | `street-address`, `postal-code`, `country-name` |
| inline JS context | n/a | n/a | `esc_js()` | n/a |
| XML / feed | n/a | n/a | `esc_xml()` | n/a |

**Ship this as a declarative column in field-type registration**, so a third-party field type is forced to declare `sanitize`, `escape` and `autocomplete`. That single design decision is the highest-leverage security move in the plugin, and the escape side is what every competitor gets wrong.

Five traps to encode in tests:

1. **`sanitize_text_field()` is destructive to newlines.** Its documented steps include "Removes line breaks, tabs, and extra whitespace" and "Strips percent-encoded characters". `sanitize_textarea_field()` (since 4.7.0) "preserves new lines (\n) and other whitespace, which are legitimate input in textarea elements." Using the former on a message body silently ruins every multi-paragraph submission.
2. **`sanitize_email()` is not validation.** It strips disallowed characters and returns an empty string for garbage; some legal addresses are silently altered. `is_email()` is the validator, and its own docblock warns "Does not grok i18n domains. Not RFC compliant." Its `is_email` filter contexts are useful for error codes: `email_too_short`, `email_no_at`, `local_invalid_chars`, `domain_period_sequence`, `domain_period_limits`, `domain_no_periods`, `sub_hyphen_limits`, `sub_invalid_chars`.
3. **`absint()` is `abs( (int) $x )`.** `absint(-10)` returns `10`. Using it on a number field that legitimately accepts negatives silently flips the sign.
4. **`esc_url()` encodes `&` as `&#038;` and `'` as `&#039;`** and returns an empty string for a protocol not in `wp_allowed_protocols()` (http, https, ftp, ftps, mailto, news, irc, gopher, nntp, feed, telnet). Storing `esc_url()` output persists those entities into your webhook payloads. `sanitize_url()` / `esc_url_raw()` for storage and outbound HTTP; `esc_url()` only at render.
5. **Never let an "allow HTML" field setting fall through to `wp_kses_post()`** for untrusted anonymous submitters; that context allows iframes and more. `wp_kses()` also expects **unslashed** input.

### Error UX rules

The W3C canonical pattern, verbatim from the WAI forms tutorial:

```html
<div role="alert">
  <h4>There are 2 errors in this form</h4>
  <ul>
    <li>
      <a href="#firstname" id="firstname_error">
        The First name field is empty; it is a required field and must be filled in.
      </a>
    </li>
  </ul>
</div>
...
<input type="text" id="firstname" aria-describedby="firstname_error" aria-invalid="true">
```

Rules for DevForm's default Mode A markup:

1. Render the `role="alert"` summary container **on first paint, empty**, so later content changes are announced. Populate it on failure.
2. Each summary item references the control's label, describes the problem, says how to correct it, and links to the control.
3. `aria-invalid="true"` and `aria-describedby="{field}_error"` on each failing control.
4. **Move focus to the first errored input** after submission. The guide "emphasizes setting focus to the first input containing an error after submission."
5. Stable error id scheme. Contact Form 7's working example is `[unit_tag]-ve-[field_name]`; DevForm's can be `df-{form_key}-ve-{field_key}`.
6. Surface the count in the page `<h1>` and `<title>` when the failure re-renders a full page (`<title>3 Errors, Billing Address</title>`).
7. `aria-live` values: `polite` for feedback while typing that must not interrupt, `assertive` for validation on focus change that should interrupt.
8. `required` **and** `aria-required="true"` together: "The aria-required attribute informs assistive technologies about required controls so that they are appropriately announced."
9. Use HTML5 constraint attributes as progressive enhancement (input types email/url/number/range/date/time, `pattern`, `maxlength`, `min`, `max`, `step`), and still validate server-side. The tutorial says so explicitly.
10. **Mode B cannot do any of this.** So the endpoint response must carry enough structure for the developer's front end to rebuild it: field key, message, machine code, and an `idref`-style hint. Include that hint in Mode B errors even though DevForm renders nothing.
11. Never clear the form on failure. See 3.3.7 below.

### WCAG 2.2 criteria list

WCAG 2.2 is a W3C Recommendation dated 12 December 2024. The eight criteria a form plugin is on the hook for:

| SC | Level | Text (abbreviated) | What DevForm must do |
|---|---|---|---|
| 3.3.1 Error Identification | A | "If an input error is automatically detected, the item that is in error is identified and the error is described to the user in text." | `role="alert"` summary + `aria-invalid` + per-field text. Techniques G83, ARIA21, ARIA18/19, G84/G85, SCR18/SCR32 |
| 3.3.2 Labels or Instructions | A | "Labels or instructions are provided when content requires user input." | real `<label for>`, `<fieldset>`/`<legend>` for groups, format examples, instructions at the start. Techniques G131, H44, H71, G167, G89, G184, G162, H90 |
| 3.3.3 Error Suggestion | AA | "If an input error is automatically detected and suggestions for correction are known, then the suggestions are provided" | technique G177: suggested correction text. This is why validation must run before sanitisation |
| 3.3.4 Error Prevention | AA | reversible / checked / confirmed for legal, financial and user-controllable data | out of scope for most forms, but relevant if a form writes user data |
| 1.3.5 Identify Input Purpose | AA | "The purpose of each input field collecting information about the user can be programmatically determined" | ship `autocomplete` tokens from the field-type table: name, given-name, family-name, email, tel, username, organization, street-address, postal-code, country-name, url, bday |
| 2.4.6 Headings and Labels | AA | "Headings and labels describe topic or purpose." | do not use placeholder text as a label |
| **2.5.8 Target Size (Minimum)** | AA, **new in 2.2** | "The size of the target for pointer inputs is at least 24 by 24 CSS pixels" with spacing/inline/essential exceptions | the default stylesheet controls this: checkbox and radio hit areas and the submit button need >=24x24 CSS px |
| **3.3.7 Redundant Entry** | A, **new in 2.2** | "Information previously entered by or provided to the user that is required to be entered again in the same process is either auto-populated, or available for the user to select." Its own example: "After form submission errors, previously entered data ... remain visible rather than cleared." | **Mode A must repopulate every field from the failed POST.** A plain form round trip that clears the form is a Level A failure, and several competitors do exactly that |

(3.3.8 Accessible Authentication applies only if DevForm ever gates a form behind a cognitive-function test. It does not.)

**Two of these are cheap and nobody advertises them.** 3.3.7 repopulation and 1.3.5 autocomplete tokens are both mechanical once the field-type table exists.

### i18n

1. **Since WordPress 6.8, no plugin with `Text Domain` and `Domain Path` headers needs `load_plugin_textdomain()` at all.** WP 6.6/6.7 posts: "Since WordPress 4.6, plugins and themes no longer need load_plugin_textdomain()... WordPress automatically loads the translations for you when needed", and 6.8 "expanded to all other plugins and themes by looking at the text domain information provided by the plugin/theme." Ship the headers, delete the call.
2. **WP 6.7 added a `doing_it_wrong` warning for loading translations before the current user is known**: "When attempting to load translations before `after_setup_theme` or `init`, WordPress tries to load the current user earlier than usual." The fix is to "defer the class instantiation until after `init`, or defer the translation call until later when it is actually needed."
   **Consequence for DevForm: do not call any `__()` at file-load or plugin-construction time.** The field-type registry cannot hold pre-translated message strings; it must hold message **keys** resolved at validation time. Design that in now; retrofitting is painful.
3. `has_translation()` (new in 6.7) tests for a translation without loading files. WP 6.5 added the `.l10n.php` format and 6.6 added PHP translation file support with a `lang_dir_for_domain` filter. A 2023 performance analysis found localized sites "up to 50% slower", which is the reason those exist.
4. Text domain rules: must match the slug (`devform`), dashes not underscores, lowercase, no spaces. **The domain argument must be a literal.** "Do not use variable names or constants for the text domain portion of a gettext function."
5. Translator comments must "start with the word `translators:`, in all lowercase, and be the last PHP comment before the gettext call". Use numbered placeholders (`%1$s`, `%2$s`) because "in most languages, word order is different than English". Use `_x()` for disambiguation. Keep HTML out of strings.
6. **Draw a hard line between plugin strings and content.** GlotPress "looks through all PHP files in a plugin and finds each gettext call", and a language pack is only generated "once a plugin or theme translation reaches 90% of the strings from the code translated and approved". Readme translations are separate and not required for pack generation.
   So: plugin-authored strings (default rule messages, admin UI, the fallback "This field is required.") go through `__()` with the literal `devform` domain and are translated for free. **User-authored strings (field labels, help text, per-form custom validation messages, confirmation text) are entry content and must never be wrapped in `__()`.**
7. Give the multilingual crowd a seam instead: `devform_message( $message, $key, $field, $form )` and `devform_field_label( $label, $field, $form )` filters that Polylang, WPML or TranslatePress can bind to. **That is the honest, principled answer to "WPML is not in scope": you provide the seam, not the integration.**
8. JS translations, if DevForm ships client-side validation or a block editor UI: `wp_set_script_translations( $handle, 'devform', $path )`, hooked on `wp_enqueue_scripts` at a late priority so the script is registered first. `@wordpress/i18n` exposes `__`, `_x`, `_n`, `_nx`. Build pipeline: `wp i18n make-pot ./ languages/devform.pot` then `wp i18n make-json devform-{locale}.po --no-purge`, which emits the md5 filename form (`${domain}-${locale}-${md5}.json`) that WordPress expects. Register `wp-i18n` as a dependency. This path is safely after `init`, so it does not conflict with rule 2.

### Testing

WordPress's test suite already provides the REST base classes:

1. `WP_Test_REST_TestCase extends WP_UnitTestCase` provides `assertErrorResponse( $code, $response, $status = null, $message = '' )`.
2. `WP_Test_REST_Controller_Testcase` instantiates `Spy_REST_Server` and runs `do_action( 'rest_api_init', $wp_rest_server )` in `set_up()`.
3. `rest_do_request( WP_REST_Request|string $request ): WP_REST_Response` dispatches internally.
4. Suite rules: snake_case `set_up()` / `tear_down()` with `parent::` calls first and last respectively; fixtures via `self::factory()->user->create()`; WordPress assertions `assertEqualSets()`, `assertWPError()`, `assertSameSetsWithIndex()`; isolation by MySQL transaction with rollback.
5. **Isolation caveat:** transaction rollback does not undo files written to disk or transients in a persistent object cache. Upload tests must clean up explicitly.
6. Scaffold with `wp scaffold plugin-tests devform` then `bash bin/install-wp-tests.sh`. Note the handbook page still documents `.travis.yml`, so hand-write the GitHub Actions matrix (PHP x WP version) yourself.

Core test plan:

1. A `@dataProvider` matrix per field type crossed with (valid, empty, too-long, wrong-format, injection payload), asserting **both** the validator verdict and the sanitized value. That matrix is the core of the suite.
2. Integration tests that `rest_do_request()` a Mode B POST and `assertErrorResponse( 'devform_invalid_fields', $response, 422 )`, one per failure mode in the status table.
3. Normalizer unit tests for `for_google()` and `for_meta()` with known-good hash fixtures.
4. Upload tests using the polyglot fixtures from section 4.

---

## 8. Extension surface

### Naming convention: `devform_` with underscores

WordPress core, WooCommerce and Gravity Forms all use underscore-delimited prefixes. Fluent Forms is the outlier with slash namespacing (`fluentform/validate_input_item_{element}`). **Pick underscores.** Slash namespacing reads as a third-party bolt-on, which contradicts the "native part of WordPress" thesis, and it breaks the muscle memory of anyone who has ever written `add_filter( 'woocommerce_...' )`.

Two structural patterns to adopt:

1. **WooCommerce's before/after pairing** around every render point (`woocommerce_before_checkout_form` / `woocommerce_after_checkout_form`, and so on).
2. **Gravity Forms' three-tier dynamic suffixes**: `hook`, `hook_{form_id}`, `hook_{form_id}_{field_key}`. That is the granularity developers expect.

And one parameter that must be there from v1: **`$context`**. Gravity Forms had to retrofit exactly this in 2.6.3.2, adding a fifth `$context` argument to `gform_field_validation` with values `form-submit`, `api-submit`, `api-validate`. DevForm's equivalent is `form` | `endpoint` | `preview`, and it is the single parameter that makes one hook surface serve both modes. Add it now or bolt it on later like GF did.

The handbook's own guidance backs the whole approach: "An important, but often overlooked practice is using custom hooks in your plugin so that other developers can extend and modify it", and "We recommend using apply_filters() on any text that is output to the browser. Particularly on the frontend." **Every user-visible string DevForm renders must be behind a filter, not just a setting.** That is the difference between a plugin with hooks and a developer plugin.

### Proposed hook list

**Schema and build**

| Hook | Type | Args |
|---|---|---|
| `devform_register_field_types` | filter | `$types` |
| `devform_register_action_types` | filter | `$types` |
| `devform_build_schema` | action | `&$schema, $form, $context` |
| `devform_field_schema` | filter | `$fragment, $field, $form` |

**Submission and validation** (each with `_{form_id}` and, where sensible, `_{form_id}_{field_key}` variants)

| Hook | Type | Args |
|---|---|---|
| `devform_pre_validate` | filter | `$raw_data, $form, $context` |
| `devform_validate_field` | filter | `$result, $value, $field, $form, $context` |
| `devform_validate` | filter | `$wp_error, $data, $form, $context` |
| `devform_validation_failed` | action | `$wp_error, $data, $form, $context` |

**Spam**

| Hook | Type | Args |
|---|---|---|
| `devform_spam_check` | filter | `$verdict, $data, $form` (verdict is `ham`\|`spam`\|`discard`) |
| `devform_spam_detected` | action | `$verdict, $data, $form` |

Akismet is just the default subscriber to `devform_spam_check` and can be swapped or removed entirely.

**Storage**

| Hook | Type | Args |
|---|---|---|
| `devform_pre_store` | filter | `$entry_data, $form` |
| `devform_should_store` | filter | `$bool, $entry_data, $form` (drives fire-and-forget) |
| `devform_entry_stored` | action | `$entry_id, $entry_data, $form` |
| `devform_entry_deleted` | action | `$entry_id, $form` |
| `devform_before_entry_delete` | action | `$entry_id, $form` |

**Action stack** (each with `_{action_type}` variants, or integrators will hook the generic one and branch on a string)

| Hook | Type | Args |
|---|---|---|
| `devform_actions` | filter | `$actions, $entry, $form` (reorder / inject) |
| `devform_pre_action` | filter | `$action_args, $action, $entry, $form` |
| `devform_action_result` | filter | `$result, $action, $entry, $form` |
| `devform_action_failed` | action | `$wp_error, $action, $entry, $form` |
| `devform_after_actions` | action | `$results, $entry, $form, $is_spam` |

**Queue and delivery**

| Hook | Type | Args |
|---|---|---|
| `devform_job_enqueued` | action | `$job` |
| `devform_job_attempt` | action | `$job, $attempt_no` |
| `devform_job_exhausted` | action | `$job` |
| `devform_retry_schedule` | filter | `$seconds, $attempt_no, $job` |
| `devform_webhook_request_args` | filter | `$args, $job` |
| `devform_webhook_timeout` | filter | `$seconds, $job` |

**Tracking**

| Hook | Type | Args |
|---|---|---|
| `devform_tracking_event` | filter | `$event, $entry, $form` |
| `devform_tracking_destinations` | filter | `$destinations, $event` |
| `devform_tracking_dispatch` | action | `$event, $destination` (the LinkedIn / TikTok escape hatch) |
| `devform_tracking_consent` | filter | `$allowed, $category, $destination` |

**Uploads**

| Hook | Type | Args |
|---|---|---|
| `devform_allowed_upload_extensions` | filter | `$exts, $form_id, $field_id` (narrows only) |
| `devform_upload_filename` | filter | `$filename, $file, $field, $form` |
| `devform_scan_upload` | filter | `$result, $tmp_path, $context` (returns `true` or `WP_Error`) |
| `devform_file_download_permission` | filter | `$allowed, $entry_id, $file_id` |

**Response**

| Hook | Type | Args |
|---|---|---|
| `devform_response` | filter | `$payload, $entry, $form, $context` |
| `devform_error_response` | filter | `$wp_error, $form, $context` |
| `devform_allowed_origins` | filter | `$origins, $form` |
| `devform_rate_limit` | filter | `$limit, $window, $form, $ip` |

**Markup, Mode A only** (before/after pairs, per WooCommerce)

| Hook | Type | Args |
|---|---|---|
| `devform_before_form` / `devform_after_form` | action | `$form` |
| `devform_form_attributes` | filter | `$attrs, $form` |
| `devform_before_field` / `devform_after_field` | action | `$field, $form` |
| `devform_field_markup` | filter | `$html, $field, $value, $errors, $form` (+ `_{form_id}`, `_{form_id}_{field_key}`) |
| `devform_error_summary` | filter | `$html, $wp_error, $form` |
| `devform_submit_button` | filter | `$html, $form` |

`devform_field_markup` is the direct analogue of Gravity Forms' `gform_field_content` ("executed before creating the field's content, allowing users to completely modify the way the field is rendered"), which is what makes the "fully overridable markup" promise real.

**Strings**

| Hook | Type | Args |
|---|---|---|
| `devform_message` | filter | `$message, $key, $field, $form` |
| `devform_field_label` | filter | `$label, $field, $form` |

Single seam for both plugin-authored defaults and user-authored per-form overrides, and the hook multilingual plugins bind to.

### PHP API for custom field types

Array of callables, not an abstract base class.

```php
devform_register_field_type( 'rating', [
    'label'        => __( 'Rating', 'devform' ),   // called on init or later
    'schema'       => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ],
    'sanitize'     => 'absint',
    'escape'       => 'esc_html',
    'autocomplete' => null,
    'render'       => 'my_render_rating',      // callable( $field, $value, $errors, $form ) : string
    'messages'     => [                        // message KEYS, not translated strings
        'required'  => 'devform.required',
        'range'     => 'devform.rating_range',
    ],
    'supports'     => [ 'required', 'default', 'help_text' ],
] );
```

Rationale: Gravity Forms' `GF_Fields::register( new GF_Field_ClassName() )` demands overriding eight-plus methods including editor-UI ones (`get_form_editor_field_title()`, `get_form_editor_button()`, `get_form_editor_field_settings()`, `get_field_input()`, `validate()`, `get_value_save_entry()`, `get_value_entry_detail()`, `get_value_merge_tag()`), which is a heavy tax. CF7's `wpcf7_swv_available_rules` name-to-class map is lighter and closer to how `register_post_type()` feels.

**`sanitize` and `escape` are required keys.** A field type that does not declare both fails registration. That is what makes safety structural rather than aspirational.

**`messages` holds keys, not translated strings**, because of the WP 6.7 early-translation rule (section 7).

### PHP API for custom actions

```php
devform_register_action_type( 'slack', [
    'label'           => __( 'Slack notification', 'devform' ),
    'settings_schema' => [
        'type'       => 'object',
        'properties' => [
            'webhook_url' => [ 'type' => 'string', 'format' => 'uri' ],
            'channel'     => [ 'type' => 'string' ],
        ],
        'required'   => [ 'webhook_url' ],
    ],
    'run'        => 'my_run_slack',   // callable( $settings, $entry, $form ) : true|WP_Error
    'async'      => true,             // goes through the queue rather than inline
    'retryable'  => true,             // participates in the backoff schedule
    'icon'       => 'dashicons-...',  // for the action-stack UI
] );
```

The `settings_schema` gives you the admin form for free (render controls from JSON Schema) and validates the saved config with the same `rest_validate_value_from_schema()` used everywhere else.

Model the "is this configured and active" affordance on Jetpack's integration metadata shape: `type`, `file`, `settings_url`, `marketing_redirect_slug`, `title`, `subtitle`, `active_tooltip`, `enabled_by_default`, `icon_url`.

### Ordering guarantee, and the Gravity Forms footgun

Gravity Forms' documented order is validation, then **notifications**, then entry creation, then `gform_after_submission`. Notifications firing before the entry is persisted is a well-known footgun: the email can reference an entry id that does not exist yet if storage fails.

**DevForm's order: validate, spam-check, persist (if storage is on), then run actions, passing the entry id into every action.**

And Gravity Forms' `gform_after_submission` "runs for all entries, including those marked as spam". That is a trap; integrators accidentally forward spam to a CRM. **`devform_after_actions` must either not fire for spam-flagged submissions, or carry an explicit `$is_spam` flag.** Prefer the flag, so a spam-analytics integration is still possible.

### Front-end escape hatch without touching PHP

The DOM events from section 2 (`devform:beforesubmit`, `devform:success`, `devform:invalid`, `devform:spam`, `devform:error`, bubbling from the form element with a `detail` object) are core to the "for theme builders" positioning: a developer gets a hook without writing a line of PHP, and it works identically on a jQuery-free Astro front end.

---

## 9. Native UX

The one-line thesis: **be native by emitting less, not by imitating more.**

### There is no native place, and no core forms feature coming

1. `core/form` exists in Gutenberg but is `"__experimental": true`, along with `core/form-input`, `core/form-submit-button` and `core/form-submission-notification`. Its README carries "This block is experimental and may change or be removed without notice."
2. It is **not registered in WordPress 7.0.4** at all: listing `wp-includes/blocks` at that tag shows no `form` directory, only the built `block-library/form/view.js` script module.
3. The last 8 commits touching `packages/block-library/src/form` are sweeping refactors (ESLint bulk suppressions, control deprecations, category rename); the newest substantive one is "Form blocks: Update block categories" from **2026-04-24**.
4. Neither the WordPress 7.1 roadmap (19 Jun 2026) nor the 7.1 Field Guide (5 Aug 2026) mentions forms. Searching Gutenberg issues for "Jetpack Forms" returns 18 results, none a core-merge proposal.
5. **Caveat, this is absence not proof.** The web-search budget was exhausted, so make.wordpress.org/core blog posts and WordCamp talks were not swept. Plan as if no core forms feature arrives before 7.2 at the earliest.

Two ideas worth stealing from `core/form` anyway: the `{SITE_URL}` / `{ADMIN_URL}` token replacement in the `action` attribute (useful for Mailbox mode docs), and the `render_block_core_form_extra_fields` filter pattern for injecting hidden fields (honeypot, timing, tracking payload).

### The highest-leverage decision is markup, not chrome

**theme.json element styling.** `WP_Theme_JSON::ELEMENTS` in core 7.0.4 contains:

```php
'button'    => '.wp-element-button, .wp-block-button__link',
'textInput' => 'textarea, input:where([type=email],[type=number],[type=password],[type=search],[type=text],[type=tel],[type=url])',
'select'    => 'select',
```

I diffed core tags: **6.8.3 has zero occurrences of `textInput`; 6.9 and 6.9.1 have it.** WordPress 6.9 shipped 2 December 2025. The Gutenberg commit is "Global styles: add element support for text related inputs (#70378)", 3 September 2025. The class docblock was never updated and the handbook's Global Settings and Styles guide does not mention `textInput` or `select` at all. It is real, shipped, and undocumented.

Gutenberg trunk has now also registered `'label' => 'label'`. **Gutenberg issue #34198, "Form elements in Global Styles, iteration for WP 7.2", updated 18 Aug 2026**, states the goal verbatim: "Let users style common form elements consistently from Global Styles, without writing custom CSS or editing theme.json by hand. The `button`, `textInput`, and `select` elements are already supported in theme.json. This iteration exposes that work in the editor, adds the missing label element, and improves support for interactive input states." Scope items #81647 (register label) and #81648 (expose in Global Styles typography and color) are closed; #81649 (add a "Form controls" section), #81650 (expose Labels), #71382 (`::placeholder` pseudo-element), #76534 (pseudo-selectors for textInput/select) are open. **WordPress 7.2 is 10 December 2026.**

So: emit bare `<label>`, `<input type=...>`, `<textarea>`, `<select>`, and on 10 December 2026 every DevForm form on every block-theme site gains a native Form controls panel in Global Styles, and DevForm ships nothing. **That is a marketing moment worth planning a release around.** No competitor gets it: Fluent Forms, Forminator and CF7 all render class-scoped controls that theme.json cannot reach.

Two gaps to cover with your own CSS until 7.2: `::placeholder` is not stylable via theme.json, and `:hover`/`:focus` pseudo-selectors do not work for `textInput`/`select` in 7.0.

**The specificity contract is precise and load-bearing.** Core does **not** wrap `textInput`/`select` element styles in `:root :where()`; they emit at raw element specificity (0,0,1). `button` and `caption` **are** wrapped, at (0,1,0). From `WP_Theme_JSON::get_styles_for_block()`, verbatim comment: "Top-level element styles using element-only specificity selectors should not get wrapped in `:root :where()` to maintain backwards compatibility." The mechanism is `__EXPERIMENTAL_ELEMENT_CLASS_NAMES`, which contains only `button` and `caption`.

Therefore: **every declaration in DevForm's default stylesheet must sit inside `:where()`**, computing to specificity (0,0,0), so a theme's `textInput` rule at (0,0,1) always wins, and so does a developer's hand-written CSS. Core's own `form-input/style.scss` is the model: every rule is `:where(.wp-block-form-input__input) { ... }`, setting only `padding`, `font-size: 1em`, `margin-bottom`, `min-height`, `line-height`, `border-width: 1px`, `border-style: solid`. `elements.scss` is four lines total: `.wp-element-button { cursor: pointer; }`.

Set structure (display, gap, flex-direction, width) plus the two affordances core sets (`border-width: 1px; border-style: solid`) and the WCAG 2.5.8 minimum target sizes. Set **no colours, no font sizes, no radii**.

**Do not skip the default stylesheet entirely.** I parsed Twenty Twenty-Five's theme.json at tag 7.0.4: its `styles.elements` keys are exactly `button, caption, h1..h6, heading, link`. **No `textInput`.** There is no Twenty Twenty-Six in 7.0.4. So on the newest default theme, relying on theme.json alone gives unstyled inputs. The submit button, by contrast, needs nothing: TT5 fully specifies `button` including `:hover` and a `:focus` outline.

**The weight benchmark.** Measured from the shipped Jetpack 16.1.1 zip: `grunion.css` is 43,788 bytes, `phone-field.css` 15,238, `dist/modules/form/view.js` 37,468, `dist/blocks/editor.css` 82,329, and `dist/modules/field-phone/view.js` **155,473** (libphonenumber-js, paid on any page with a phone field). **Target: a working, theme-styled, validated DevForm form in under 3 KB of CSS.** That is achievable precisely because theme.json elements and `core/button` do the visual work.

### Block integration

1. **Do not build a submit-button block.** Jetpack does not either: every one of its form patterns serialises `<!-- wp:button {"tagName":"button","type":"submit"} --><div class="wp-block-button"><button type="submit" class="wp-block-button__link wp-element-button">Contact us</button></div><!-- /wp:button -->` inside the form block. Both classes on that button are targeted by `styles.elements.button`, so you inherit theme button styling, hover and focus pseudo-states (core 7.0 added `VALID_BLOCK_PSEUDO_SELECTORS = array( 'core/button' => array( ':hover', ':focus', ':focus-visible', ':active' ) )`), the whole button toolbar, and zero maintenance.
2. **Declare `supports.layout` with a flex default**, as Jetpack's `jetpack/contact-form` does: `default: { type: 'flex', flexWrap: 'wrap', orientation: 'horizontal', justifyContent: 'left', verticalAlignment: 'top' }`, with `allowSwitching: false, allowEditing: true, allowOrientation: true, allowVerticalAlignment: true, allowJustification: true, allowWrap: false`. That is what gives the form block native block-gap and justification controls without custom grid CSS.
3. **Register field blocks with `ancestor: ['devform/form']`, not `parent`**, so they can nest inside a Group or Columns inside the form.
4. **Share styling attributes down via block context**, the way Jetpack does. Its `jetpack/input` block declares `usesContext: [ 'jetpack/field-share-attributes' ]` and the parent provides it; each field block still supports per-field overrides through block supports (color text/background, `__experimentalBorder` with color/radius/style/width, typography with fontSize/lineHeight/fontFamily/fontWeight/fontStyle/textTransform/textDecoration/letterSpacing). Note `border` is still `__experimentalBorder` in core 7.0.4; only skip-serialization uses the bare `'border'`.
5. **Use a custom block `category`** so DevForm's blocks group in the inserter, as Jetpack's `contact-form` category does.
6. **Register form variations from PHP** via the `get_block_type_variations` filter, so saved forms generate inserter entries server-side. Variations declare `name`, `title`, `description`, `attributes`, `innerBlocks`, `icon`, `scope` (`block`|`inserter`|`transform`), `isActive` and `isDefault`.
7. **Block Bindings is the wrong tool.** It supports only `core/image`, `core/heading`, `core/paragraph`, `core/button`, `core/navigation-link`, `core/navigation-submenu` and `core/post-date`, with fixed attribute lists. Do not plan around it for field values, prefill or dynamic defaults. Narrow exception: `core/button.text` is bindable if you ever want a bound submit label.
8. **Front end runs on the Interactivity API**, via `"supports": { "interactivity": true }` and `"viewScriptModule": "file:./view.js"`, built with `wp-scripts --experimental-modules`. Core 7.0.4 registers `interactivity` and `interactivity-router` as script modules, so the runtime is shared and costs nothing extra. Jetpack's whole form front end already runs on it with store namespace `jetpack/form`, covering conditional visibility, steps, rating, phone, file and slider fields. **Steal their bfcache fix**: re-dispatch `input` events on `pageshow` when `event.persisted`, or you ship that bug yourself.
9. In Mailbox mode the plugin ships **no front-end JS at all**. That is the point of Mailbox mode.

### Admin information architecture

1. **Top-level menu named "Forms".** Not Tools, not Settings, not "Feedback". The handbook's "single option page belongs under Settings or Tools" advice does not apply to a product with a forms list, a responses inbox, per-form settings and global settings. Every competitor (CF7 `add_menu_page` with `dashicons-email`, Fluent Forms, Forminator) uses a top-level menu, and so does Jetpack (as a submenu under its own product menu, at `edit_pages`, position 10).
2. **"Feedback" is a dead label.** Automattic spent two years killing it: the forms package changelog records "Jetpack Forms dashboard now replaces the 'Feedback' menu entry in WP Admin", "Add feature filter flags and code for moving submenu item from Feedback > Forms responses to Jetpack > Forms", "Disable default listing UI for Feedback post types if the menu item is removed", "Hide legacy Feedback menu on new sites". The user-facing change landed in Jetpack 14.8 (1 July 2025).
3. **Call the data "Responses."** That is Jetpack's term and its CPT labels are literally "Form Responses".
4. **Do not put the product name in the menu title.** Use the noun the user is looking for.
5. Capabilities: Jetpack uses `edit_pages` throughout. I would default **settings to `manage_options` and reading responses to `edit_pages`**, both filterable. Submission data is more sensitive than page content, and Jetpack's `capability_type => 'page'` means an Editor can read every response.
6. Screens: **Forms** (list), **Responses** (inbox), per-form editor with tabs (Fields, Actions, Tracking, Spam, Settings), **Settings** (global), **Delivery** (queue and webhook attempt log), plus Site Health entries for the upload canary and the loopback.

### Storage: a Jetpack-shaped CPT, invisible to the classic admin

```php
register_post_type( 'devform_response', [
    'show_ui'              => false,
    'show_in_menu'         => false,
    'show_in_admin_bar'    => false,
    'public'               => false,
    'rewrite'              => false,
    'query_var'            => false,
    'show_in_rest'         => true,
    'rest_controller_class' => DevForm\REST\Responses_Controller::class,
    'supports'             => [ 'comments' ],
    'map_meta_cap'         => true,
] );
```

This is exactly Jetpack's `feedback` shape. You inherit trash, search, meta, capabilities, pagination and `X-WP-Total` headers for free by extending `WP_REST_Posts_Controller`, and `supports => ['comments']` gives you per-response team notes with zero schema (Jetpack has a `feedback-comments` component for exactly that). A second CPT holds the forms themselves. Remap `create_posts => 'do_not_allow'` so nothing can create a response from the admin.

### React versus classic admin: the debate is over, but ration DataViews

**Core now ships a first-party React admin page system.** `@wordpress/build` (the `wp-build` CLI) reads a root `package.json` `wpPlugin` object and generates `build/pages/<id>/page.php` (full-page) and `page-wp-admin.php` (embedded in wp-admin chrome). Its README states verbatim: "Pages boot through the `@wordpress/boot` script module, which ships with WordPress Core 7.0+ and the Gutenberg plugin. Plugins do not need their own `packages/boot`." I verified `boot` and `route` are present in `wp-includes/js/dist/script-modules/` at tag 7.0.4. `@wordpress/admin-ui` provides `Page`, `NavigableRegion`, `Breadcrumbs` and `getAdminThemeColors()`, which "reads the active WordPress admin color scheme from the `admin-color-*` body class". Jetpack Forms is mid-migration to it (a parallel `src/dashboard/wp-build/` tree exists alongside the legacy app).

**But the cost is real and measured.** `@wordpress/dataviews`, `@wordpress/admin-ui`, `@wordpress/ui` and `@wordpress/fields` are **not registered scripts in WordPress 7.0.4**. I enumerated all 65 handles in `wp-includes/assets/script-loader-packages.php`: `components.js` and `theme.js` are there; `dataviews.js`, `admin-ui.js`, `ui.js`, `fields.js` are not. `@wordpress/dataviews@18.0.0` depends on `@ariakit/react`, `date-fns`, `colord`, `deepmerge`, `remove-accents`, `fast-deep-equal`, `clsx` and `@wordpress/ui`, all of which land in your bundle.

Measured from the shipped Jetpack 16.1.1 zip:

| File | Raw | Gzip |
|---|---|---|
| `build/routes/responses/content.min.js` | 1,206,646 | **315,943** |
| `build/routes/forms/content.min.js` | 1,020,286 | 255,285 |
| legacy `dist/dashboard/jetpack-forms-dashboard.js` | 2,774,875 | 583,467 |

**300 KB gzip for a submissions list is indefensible for a free plugin whose pitch is leanness.** Jetpack absorbs it because it is already a 31 MB download.

**The pick:** DataViews on the **Responses screen only**, imported from `@wordpress/dataviews/wp` (the entry point that externalises `@wordpress/*` to core globals under wp-scripts, per their README), code-split so it never loads elsewhere. Everything else (forms list, per-form settings, global settings, delivery log) on `@wordpress/components`, which is free from core as `wp-components`.

Why DataViews at all: it is the bar. Jetpack's Responses screen is a DataViews table with folder pills (Inbox / Spam / Trash surfaced as a "Folder is: Inbox" filter), date/source/status filters, a search bar, bulk actions and a count-labelled Export button. Its `defaultView` is `{ type: 'table', filters: [{ field: 'status', operator: 'is', value: 'all' }], perPage: 20, titleField: 'title', fields: ['entries','status','modified'] }`. DevForm's conversion-tracking differentiator adds columns Jetpack does not have (referrer, landing page, UTM, tracking dispatch status), which is a natural fit for DataViews' `fields` plus `enableHiding`.

Treat `wpPlugin.pages` as the direction but **not the day-one dependency**: it is marked Experimental and it generates PHP, which is an unnecessary conversation to have with the review team on a first submission. Hand-register the menu.

### Design tokens

WordPress 7.1 (today) registers a `wp-theme` style and script handle exposing `--wpds-*` design tokens (W3C Design Tokens Community Group spec) and a React `ThemeProvider` with props `color.primary`, `color.background`, `cursor.control`, `cornerRadius` (none/subtle/moderate/pronounced), `isRoot`. In 7.1 it is applied to the Site Editor and used by `@wordpress/ui`. **Dark mode is explicitly not included yet**, nor is expansion to the broader admin.

It is genuinely new: core 7.0.4 registers `theme.js` in `script-loader-packages.php` but there is no `wp-includes/css/dist/theme` directory, and grepping `script-loader.php` at 7.0.4 for `wp-theme` finds only the unrelated `wp-theme-plugin-editor` handle. Jetpack works around the gap today with its own `jetpack-admin-ui-design-tokens` stylesheet, "the shared, token-only WPDS design-tokens stylesheet, enqueued on every Jetpack admin page so that `var(--wpds-*)` values resolve at runtime instead of falling back to their hand-written hex defaults."

**Build DevForm's admin against `--wpds-*` tokens now, with hex fallbacks, and enqueue `wp-theme` as a dependency when available.** Use `getAdminThemeColors()` to respect the user's Midnight/Ocean admin scheme. **Do not hardcode `#3858e9` or `#1e1e1e` anywhere.** That one-line decision makes DevForm track the admin's visual evolution for free, including whenever dark mode arrives.

Do **not** adopt `@wordpress/ui` yet; its own README at 0.20.0 says "This package is still experimental. 'Experimental' means this is an early implementation subject to drastic and breaking changes."

### Submission endpoint: REST, not admin-ajax

Jetpack still posts to `admin-ajax.php?action=grunion-contact-form` (`fetch(url, { method:'POST', body: formData, headers: { Accept: 'application/json' } })`). The two `register_rest_route` call sites in the entire forms package are both **reading** controllers; there is no REST route for creating a submission. That is legacy, and it is DevForm's opening. See section 3.

### Free wins that cost almost nothing

1. **Privacy tools.** Register `wp_privacy_personal_data_exporters` and `wp_privacy_personal_data_erasers` so DevForm data flows through Tools, Export Personal Data and Erase Personal Data, screens the site owner already knows. Core hooks them at `wp-admin/includes/privacy-tools.php` lines 816 and 949. Jetpack Forms is one of only four files in the whole monorepo that hook the exporter. Roughly 80 lines, and worth more than any bespoke "GDPR settings" tab.
2. **Trash cleanup for free** via core's daily `wp_scheduled_delete` and `EMPTY_TRASH_DAYS`.
3. **The fill-duration timing field** as a spam signal, which Jetpack already ships as `jetpack_form_fill_duration`.
4. **Site Health checks**: the upload-directory canary (section 4) and the loopback test.

### The competitive baseline, stated plainly

Live wordpress.org API figures fetched this session: contact-form-7 v6.1.7 / 10,000,000 installs / rating 80 / requires 6.7 / tested 7.1; wpforms-lite v2.0.0.5 / 5,000,000 / 96; jetpack v16.1.1 / 3,000,000 / 76 / requires 6.9; fluentform v6.2.12 / 700,000 / 96; forminator v1.57.1 / 600,000 / 96; ninja-forms v3.15.0 / 600,000 / 88; formidable v6.34 / 300,000 / 96.

Grepping the downloaded zips: **zero of CF7, Fluent Forms and Forminator reference `@wordpress/` in any JS file.** CF7 is classic PHP with one `WP_List_Table` subclass and no `register_setting()` calls at all. Fluent Forms ships Vue bundles. Forminator ships a webpack React/mixed build. The entire category renders as a third-party application dropped into wp-admin. The gap the brief identifies is real and measurable.

### Version floor

WordPress 7.0.4 is stable (12 Aug 2026); 7.1 ships 19 Aug 2026; 7.2 is 10 Dec 2026. Options:

1. `Requires at least: 6.9` guarantees `textInput`/`select` theme.json elements. Jetpack itself requires 6.9. **This is the pick.**
2. `Requires at least: 6.5` gets the Interactivity API and `viewScriptModule` as the floor and treats element styling as progressive enhancement. Broader reach, but you then have to ship more of your own CSS.
3. Do not set 7.1 as a floor on day one.

Note React 19 lands in 7.1, so test anything built on `@wordpress/element` against both 18 and 19.

---

## 10. Contradictions and risks

### A. Silent honeypot success versus conversion tracking

**The conflict.** Section 3 says a honeypot hit returns `201` with a normal success body, because Formspree "silently ignore[s]" and Netlify "quietly reject[s]" while Static Forms 403s and hands the bot free feedback. Section 2 says every successful submission mints an event and fires it to GA4, Meta and PostHog. **A faked success would register a conversion for every bot**, polluting exactly the metric the plugin is selling.

**The resolution.** The success response for a spam-flagged submission carries `"tracking": null` and no `event_id`, and the server layer is never invoked. The bot sees a 201; the analytics see nothing. Same for `X-akismet-pro-tip: discard`. Make the rule explicit in the action-stack ordering: **spam verdict short-circuits both the action stack and the tracking dispatch, but not the HTTP response shape.** This is the sharpest interaction in the whole design and it must be written into the spec, not discovered at implementation time.

### B. 422 versus 400 for field validation

**The conflict.** The Mode B angle recommends `422 devform_invalid_fields`, matching Formcarry and semantically correct. The validation angle recommends emitting core's `rest_invalid_param` verbatim, which is `400`.

**The resolution.** Keep core's **envelope** (`{code, message, data:{status, params, details}}`) and use **422** as the status. The envelope is what makes `apiFetch` and every WP-aware client work; the status code is a deliberate, documented deviation. This is genuinely a fork in the "feel native" thesis, and it should be Nol's call, but 422 wins on semantics and nothing breaks.

### C. ALTCHA-by-default cannot work in Mode B

**The conflict.** The anti-spam angle recommends bundling ALTCHA proof-of-work and enabling it by default (layer 1), because it makes zero external calls. But proof-of-work requires a client-side widget solving a challenge. **A decoupled Next.js or Astro front end posting to the endpoint has no widget**, and a server-to-server caller certainly does not.

**The resolution.** ALTCHA is on by default in **Mode A only**. In Mode B it is per-form opt-in, and when enabled the schema endpoint must publish the challenge URL and the developer must integrate the widget themselves. The default Mode B stack is layer 0 (honeypot, time trap, rate limiting) plus optional Turnstile plus optional Akismet, and the docs must say plainly that **Mode B spam protection is weaker than Mode A**, both because of ALTCHA and because none of Akismet's `ak_b*` behavioural signals exist for a headless submission.

### D. Retention on `wp_scheduled_delete` versus "avoid wp-cron"

**The conflict.** The native-UX angle recommends hooking retention cleanup onto core's existing daily `wp_scheduled_delete` event, as Jetpack does, and notes this avoids registering a new cron event. The async angle says wp-cron is unreliable (Kinsta 15-minute drain, WP Engine 1-minute with a 60-second ceiling, page-load-triggered by default) and recommends DevForm's own queue with an in-request tail plus WP-CLI.

**The resolution.** They are not actually in conflict if you split the two jobs. **Trash cleanup** rides `wp_scheduled_delete` and `EMPTY_TRASH_DAYS` for free; it is best-effort and nobody cares if it slips a day. **Retention purge** goes through DevForm's own bounded `purge_batch()` contract, drained by whichever path is running (in-request tail, loopback, WP-CLI, safety-net cron). Then state the honest bound in the UI rather than implying clock precision. But do note: **Gravity Forms' documented caveat that entries may persist "up to an additional day"** is exactly the symptom DevForm is claiming to beat, and on a low-traffic site with no system cron DevForm will have the same problem. Do not over-promise.

### E. The upload two-step problem

**The conflict.** Mode B's decoupled audience wants a two-step upload (upload first, get a token, submit later): progress bars, survives `post_max_size` better, matches how modern front ends work. Section 4's security posture says single-request multipart is far safer and is what CF7 does; two-step creates an orphan problem and an unauthenticated write endpoint that must be independently rate-limited.

**No clean resolution.** My pick is single-request in v1, with the `pending/` staging directory built anyway so two-step can be added later without moving files around. If two-step ever ships: validate on the final assembled file under its final derived name, rate-limit the upload endpoint separately and harder, expire pending tokens in minutes, and never let the token carry any configuration.

### F. Akismet's own docs contradict themselves on `comment_type`

The parameter reference for comment-check, submit-spam and submit-ham all list **`contact-form`** (hyphen), and so does the 2012 engineering blog post (updated 2 Aug 2026). The page written specifically for form-plugin authors, "Using Akismet on contact forms", says **`contact_form`** (underscore). Automattic's own two implementations disagree: Jetpack Forms sets `comment_type = 'contact_form'`; Contact Form 7 sets `'contact-form'`.

Both are accepted (the API takes an arbitrary string), but `comment_type` **materially affects classification accuracy** per Akismet's own blog. My pick is `contact_form`, because the page that prescribes it is the one written for this exact use case and it also defines the `contact_form_field_*` convention, and because Automattic's own forms product uses it. **Worth one support email to Akismet before shipping rather than a coin flip.**

### G. Akismet downgrades to plain HTTP, which collides with the hard security posture

`Akismet::http_post()` builds `http://` URLs, upgrades to https conditionally, and after two TLS failures sets `akismet_ssl_disabled` and sends submission content over port 80 for 24 hours. DevForm claims a hard security posture and GDPR scope. Skipping the layer while that option is set (fail open to the local filters) is the recommendation, but it means DevForm silently disables the user's paid spam protection under a condition they cannot see. **At minimum, surface it as an admin notice.**

### H. wordpress.org review rules that a recommendation touches

1. **Guideline 7, external servers.** Every tracking destination, Turnstile and Akismet ship OFF with empty credentials; the site owner pasting their own key is the consent. The readme must enumerate every endpoint with a link to each vendor's terms (guideline 6). Not a blocker, but it bounces a first submission if skipped.
2. **Guideline 5, third-party terms.** This is what kills Stop Forum Spam ("Your use of this data and supporting software is non-commercial"). It is also why VirusTotal is out ("must not be used in commercial products or services").
3. **Guideline 13, default libraries.** Only covers libraries WordPress itself ships (jQuery, Atom Lib, SimplePie, PHPMailer, PHPass). Bundling ALTCHA and, if you ever changed your mind, Action Scheduler, is permitted. Bundling `enshrined/svg-sanitize` would also be permitted, and you should still not do it.
4. **Guideline 4, human-readable code.** Minified bundles are fine but you must either include the source in the deployed plugin or link in the readme to the development location, and obfuscation is banned. Ship `build/` only and point the readme at a public GitHub repo documenting `npm install && npm run build`. This removes the last real argument for classic PHP admin screens.
5. **`wpPlugin.pages` generated PHP is a review risk.** It is marked Experimental in `@wordpress/build`'s README and generates PHP render callbacks, which is exactly the kind of thing guideline 4 conversations turn on. Do not depend on it for a first submission. Worth asking in #pluginreview before committing.
6. **Guideline 16, no reservations.** You cannot squat `devform`. A complete working plugin is required at submission.
7. **Custom database tables.** No primary source was found on the review team's position on plugins creating custom tables. DevForm's design needs at least three (jobs, job_attempts, rate limits) plus possibly idempotency records. WooCommerce and Action Scheduler both do it, so precedent is strong, but check the handbook's data storage guidance before finalising the schema.
8. **The same-origin tracking proxy is the one genuinely risky idea.** A REST route at `/wp-json/devform/v1/px/...` that relays browser events to Google and Meta from the site's own domain recovers the 10-30% lost to ad blockers, but it also makes DevForm a general-purpose analytics proxy, which is uncomfortably close to "third-party advertisement mechanisms which track usage and/or views" in guideline 7 and to circumventing user-installed blocking software. **Do not ship it in v1.** If it ever ships, restrict it to endpoints DevForm itself controls, require explicit opt-in, and get a pre-review read.

### I. Version floor collisions

1. theme.json `textInput`/`select` requires **WP 6.9**.
2. Action Scheduler 4.1.0 requires **WP 6.8** (moot if you do not bundle it).
3. `@wordpress/boot` ships in **WP 7.0+**.
4. `--wpds-*` tokens ship in **WP 7.1**.
5. Akismet `comment_check()` requires **Akismet 5.7** (use `http_post()` fallback).
6. WordPress officially still runs on **PHP 7.4+** while recommending 8.3+.

**Picks:** `Requires at least: 6.9`, `Requires PHP: 8.1`. The PHP floor is a deliberate exclusion, not a free choice, and should be stated as such. It buys enums (ideal for field and action types), readonly properties, `never` return type and first-class callable syntax. Setting the header means wordpress.org blocks installation on older hosts rather than fatal-erroring. Anything that needs 7.0 or 7.1 (boot, WPDS tokens) must be feature-detected with a graceful fallback, not required.

### J. Things asserted from memory that must be re-verified before implementation

1. **LinkedIn Insight Tag `lintrk('track', {conversion_id: N})`** syntax: the docs page 404'd. **Unverified.**
2. **X/Twitter `twq()` conversion syntax**: `business.x.com` returned HTTP 402. **Unverified.**
3. **TikTok `ttq.track('SubmitForm')` and the Events API endpoint**: docs render client-side and returned no body. **Unverified.**
   All three reinforce the design: treat them as generic "paste your snippet, we fire an event with your payload" hooks rather than first-class integrations, because their documentation is not even reliably reachable.
4. **Akismet's `contact-form` versus `contact_form`**: see F.
5. **Google's "March 2024 EEA Consent Mode v2 enforcement" date**: not on any Google page fetched. **Unverified.** Do not cite it.
6. **"reCAPTCHA is illegal in the EU"**: no regulator decision located. **Unverified**, and frequently conflated with the 2022 German Google Fonts ruling. Do not make the claim.
7. **GA4 cookie format change in early May 2025**: third-party report only. **Unverified.**
8. **WPForms' Webhooks addon tier**: their pricing page says Elite, their addons index reads as Pro. **Contradictory**, do not quote.
9. **PixelYourSite PRO pricing**: their pricing page is behind a Cloudflare interstitial. **Unverified.** Matters for competitive framing since they are the closest free competitor.
10. **Akismet USD pricing**: fetched from a Netherlands IP, so only EUR figures are confirmed. Tier structure and call limits are currency-independent.
11. **Forminit (ex-getform.io) success and error bodies**: their docs are client-rendered and a plain fetch returns only the shell. Endpoint shape and rate limits are confirmed; response bodies are not.
12. **Turnstile cookies in default Managed mode**: `cf_clearance` is documented for JavaScript detections and pre-clearance, but whether a basic non-pre-clearance widget sets any cookie at all is **unconfirmed**. This is the first question a Dutch or German agency client will ask about cookie-banner scope.
13. **Gravity Forms CVE root causes** are drawn from ZeroPath and Patchstack analyses rather than vendor advisories: **likely, not verified.**
14. **No published effectiveness data exists for honeypots or time traps in isolation.** Do not publish a catch-rate number.

### K. Operational risks worth naming

1. **`fastcgi_finish_request` does not exist under Apache mod_php, plain CGI, or the CLI server.** On those hosts DevForm degrades to a `shutdown`-hook drain with a tighter budget and blocking outbound calls, so the visitor waits. That is a real UX difference between hosts, and it should be visible in Site Health. Whether it exists under FrankenPHP, RoadRunner or ngx_php was not tested.
2. **The loopback dispatcher will fail on some hosts** and gives you no error, because `blocking => false`. WordPress ships a Site Health test for it precisely because it routinely breaks.
3. **The nginx upload gap has no clean fix.** How aggressively to surface it (passive Site Health note versus refusing to enable file upload fields until the canary check passes) is a genuine product decision with a support-load cost. Refusing is the honest security position.
4. **Rate-limit storage on shared hosting with no persistent object cache** falls back to a custom table. Transients demonstrably fail open, so there is no low-footprint alternative.
5. **The retry table holds hashed PII** for failed conversion dispatches. If auto-delete removes an entry after N days, the queue must honour the same window or the GDPR story has a hole.
6. **Meta's 48-hour dedup window caps retry age.** A tracking job retried past 48 hours double-counts rather than dedupes.
7. **Imagick has its own CVE history** (ImageTragick, the ghostscript delegate chain for PDF and EPS). If DevForm accepts PDF and Imagick is installed, nothing in the pipeline should ever hand a PDF to Imagick. Add an explicit guard, and decide whether the re-encode step forces GD.
8. **Loading a second gtag alongside Site Kit (5M installs) risks double pageviews and duplicate conversions.** Default to firing into existing globals with an explicit "load the tag for me" opt-in.
9. **Where the Meta CAPI access token lives.** `wp_options` means it lands in every database backup and every migration, and it is a bearer credential that can write conversion data to an ad account. Recommend a System User token scoped to `ads_management` on that pixel only, document it, and support a `wp-config.php` constant.

---

## 11. Open questions for Nol

Grouped by whether they block work.

### Blocking, decide before code

1. **Prefix convention.** Break the house `ettic_[product]_` rule and use `devform_`? The slug forces the text domain to `devform`, so `ettic_devform_*` functions disagree with it for no reviewer benefit. My recommendation is break it, and keep `ettic_` for Ettic-branded products. Your call.
2. **Publishing account.** An Ettic-branded wordpress.org account (signals the vendor, helps with the organisation submission path) or a neutral project account?
3. **PHP floor 8.1 and WP floor 6.9.** Both are deliberate exclusions. 8.1 buys enums, readonly props and first-class callables; 6.9 guarantees theme.json form elements. Confirm.
4. **422 or 400** for field validation errors in Mode B. See contradiction B.
5. **Error path format**, dot string (`users.0.email`) or array (`["users", 0, "email"]`). Even without multi-step, a checkbox group or a repeated file input needs one. Picking late is a breaking response-shape change. I lean array.
6. **Does the ordered action stack run synchronously or deferred?** The `actions[]` array in the success body implies synchronous, which is honest but makes a slow SMTP server or a dead webhook host into a slow form. Deferred means reporting `queued` and needing somewhere to report eventual failure. My recommendation: email and redirect synchronous, webhook and tracking deferred, with `actions[]` reporting real per-action state either way.
7. **Single-request uploads or two-step?** See contradiction E. I lean single-request in v1.
8. **Domain spend.** devform.com has been on Squadhelp since 2009 and the asking price was unreadable (Cloudflare 403). If the answer is no, devform.dev carries the brand alone. Decide before announcing anything. devform.io drops from redemption within weeks; backorder or not?

### Product shape

9. **Does DevForm ship a client-side JS validator** that consumes the published schema (CF7 does), or leave it entirely to the developer? Shipping it means the make-pot to make-json pipeline in every release plus a second implementation of every rule to keep in sync. Not shipping it means Mode A has a worse default UX than every competitor.
10. **Does DevForm load its own gtag/fbq/posthog snippets, or only fire into globals that Site Kit, GTM4WP or PixelYourSite already loaded?** My instinct is fire-into-existing-globals by default with an explicit opt-in, but this needs a decision before the settings screen is built.
11. **Is there a "CF7 mode"** where files are attached to the notification email and deleted within 60 seconds, never stored at all? It is by far the safest posture and it is what the most-installed form plugin actually does. It pairs naturally with the existing "storage optional per form" decision.
12. **Does the public schema endpoint ship in v1, default on?** It is what makes decoupled front ends viable, and it is edge-cacheable, but it publishes your field names and validation rules to anyone with the form key. My instinct is yes, default on.
13. **Retention default for uploaded files versus entry rows.** An entry row and a 20 MB PDF have different GDPR and disk-cost profiles. Files at 90 days, entry metadata kept longer?
14. **How aggressively to surface the nginx upload gap.** Passive Site Health note, prominent admin notice, or refuse to enable file upload fields until the canary check passes? Refusing is honest and generates support load.
15. **Does the Mode B response include a delivery or job id** so a decoupled front end can poll delivery status? Real differentiator, but it implies a public read endpoint with its own auth surface.
16. **Where does the Meta CAPI access token live?** `wp_options` (better UX, lands in every backup) or a `wp-config.php` constant (safer, worse UX)? Decide before the settings screen exists.

### Needs a human or a follow-up pass

17. **Manual trademark clearance** for DevForm at USPTO and EUIPO. TMview is a single source and both direct services were unreachable. Also decide whether to docket a watch on DEVFORMA (FR 5271162).
18. **One support email to Akismet** on `contact_form` versus `contact-form`, and on typical p95 latency for comment-check since it sits on the critical path of every submission.
19. **Re-verify the three unreachable ad platforms** (LinkedIn Insight Tag, X, TikTok) against live vendor docs before implementing anything for them.
20. **Build a throwaway DataViews-only bundle** to get the true floor. Jetpack's 316 KB gzip includes their router, store, integrations UI and email-template icons. The real DataViews-alone number might change the section 9 recommendation.
21. **Test the `fastcgi_finish_request` detection matrix** across Kinsta, WP Engine, Pressable, SiteGround, Cloudways and a plain LAMP box before committing to it as the primary drain path.
22. **Ask #pluginreview** about `wpPlugin.pages` generated PHP and about custom database tables, before finalising either.
23. **Check whether any real block theme defines `styles.elements.textInput` today.** TT5 does not. If adoption is near zero, the section 9 styling payoff is mostly a 7.2 bet rather than a present-day win, which changes how hard to lean on it in launch messaging.
24. **Confirm whether the default Turnstile Managed widget sets a cookie.** First question a Dutch or German agency client will ask.

---

## 12. Sources by section

### 1. Naming
- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/
- https://developer.wordpress.org/plugins/wordpress-org/common-issues/
- https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- https://make.wordpress.org/plugins/2018/11/21/reminder-we-cant-rename-plugins-post-approval/
- https://wordpress.org/plugins/developers/add/
- https://raw.githubusercontent.com/WordPress/plugin-check/trunk/includes/Checker/Checks/Plugin_Repo/Trademarks_Check.php
- https://raw.githubusercontent.com/WordPress/plugin-check/trunk/includes/Checker/Checks/Plugin_Repo/Prefixing_Check.php
- https://raw.githubusercontent.com/WordPress/plugin-check/trunk/includes/Traits/Prefix_Utils.php
- https://raw.githubusercontent.com/WordPress/WordPress-Coding-Standards/develop/WordPress/Sniffs/NamingConventions/PrefixAllGlobalsSniff.php
- https://www.tmdn.org/tmview/api/search/results
- https://defform.com/ and https://wordpress.org/plugins/defform-contact-form/
- https://data.iana.org/rdap/dns.json , https://pubapi.registry.google/rdap/domain/devform.dev , https://rdap.publicinterestregistry.org/rdap/domain/devform.org
- https://api.github.com/users/devform , https://api.github.com/users/DevForms , https://registry.npmjs.org/devform , https://packagist.org/search.json?q=devform
- Unreachable (absence noted): https://tmsearch.uspto.gov/search/search-information , https://branddb.wipo.int/branddb/en/service/search?query=devform , https://trademarks.justia.com/search?q=devform

### 2. Tracking
- https://developers.google.com/tag-platform/gtagjs/reference/events
- https://developers.google.com/analytics/devguides/collection/ga4/reference/events
- https://support.google.com/analytics/answer/9267568
- https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events
- https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference
- https://developers.google.com/analytics/devguides/collection/protocol/ga4/validating-events
- https://developers.google.com/tag-platform/gtagjs/reference
- https://developers.google.com/tag-platform/devguides/conversions
- https://support.google.com/google-ads/answer/13262500
- https://developers.google.com/google-ads/api/docs/conversions/enhanced-conversions/web
- https://developers.google.com/analytics/devguides/collection/ga4/uid-data
- https://developers.google.com/tag-platform/security/guides/consent
- https://support.google.com/analytics/answer/9976101
- https://developers.facebook.com/docs/meta-pixel/reference
- https://developers.facebook.com/docs/marketing-api/conversions-api/using-the-api
- https://developers.facebook.com/docs/marketing-api/conversions-api/deduplicate-pixel-and-server-events
- https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
- https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc
- https://learn.microsoft.com/en-us/linkedin/marketing/integrations/ads-reporting/conversions-api
- https://posthog.com/docs/api/capture , /docs/libraries/php , /docs/libraries/js/persistence , /docs/product-analytics/capture-events , /docs/getting-started/identify-users , /docs/advanced/proxy
- https://webkit.org/blog/10218/full-third-party-cookie-blocking-and-more/
- https://www.simoahava.com/analytics/track-form-engagement-with-google-tag-manager/
- https://developers.google.com/tag-platform/tag-manager/datalayer , https://support.google.com/tagmanager/answer/7679219
- https://raw.githubusercontent.com/rocklobster-in/contact-form-7/master/includes/js/src/submit.js
- https://docs.gravityforms.com/gform_confirmation_loaded/
- https://wordpress.org/plugins/wp-consent-api/ , https://wpconsentapi.org/ , https://github.com/rlankhorst/wp-consent-level-api , https://complianz.io/wp-consent-api/ , https://www.cookieyes.com/documentation/wp-consent-api/
- https://wpforms.com/pricing/ , /addons/ , /addons/user-journey-addon/
- https://www.gravityforms.com/pricing/ , https://fluentforms.com/pricing/ , https://formidableforms.com/pricing/ , https://ninjaforms.com/pricing/ , https://elementor.com/pricing-plugin/
- https://wordpress.org/plugins/duracelltomi-google-tag-manager/ , /pixelyoursite/ , /google-site-kit/ , https://github.com/google/site-kit-wp/issues/11003
- https://www.php.net/manual/en/function.fastcgi-finish-request.php , https://actionscheduler.org/

### 3. Mode B API
- https://raw.githubusercontent.com/WordPress/WordPress/master/wp-includes/rest-api.php
- https://raw.githubusercontent.com/WordPress/WordPress/master/wp-includes/rest-api/class-wp-rest-server.php
- https://raw.githubusercontent.com/WordPress/WordPress/master/wp-includes/rest-api/class-wp-rest-request.php
- https://raw.githubusercontent.com/WordPress/WordPress/master/wp-includes/http.php , /wp-admin/admin-post.php , /wp-includes/pluggable.php
- https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ , /routes-and-endpoints/ , /using-the-rest-api/authentication/
- https://developer.wordpress.org/reference/functions/wp_is_json_media_type/
- https://developer.wordpress.org/reference/classes/wp_object_cache/ , https://developer.wordpress.org/apis/transients/
- https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS
- https://datatracker.ietf.org/doc/html/draft-ietf-httpapi-idempotency-key-header
- https://datatracker.ietf.org/doc/draft-ietf-httpapi-ratelimit-headers/
- https://www.rfc-editor.org/rfc/rfc9457.html
- https://raw.githubusercontent.com/formspree/formspree-js/main/packages/formspree-core/src/core.ts and /submission.ts
- https://help.formspree.io/articles/form-and-project-settings/system-limits.md , /building-your-form/honeypot-spam-filtering , /building-your-form/file-uploads.md , /form-and-project-settings/restrict-to-domain , /advanced-features/verify-webhook-signatures.md
- https://docs.formcarry.com/features/field-validations , https://formcarry.com/docs/getting-started
- https://docs.web3forms.com/getting-started/api-reference
- https://docs.netlify.com/manage/forms/setup/ , /spam-filters/
- https://www.staticforms.xyz/docs/api-reference
- https://documentation.formspark.io/examples/ajax.html , /html-form/form-validation.html , /setup/file-uploads.html , /setup/spam-protection.html , /api/errors.html
- https://docs.usebasin.com/creating-forms/basin-js/ , /file-uploads/ , /developer-features/api-reference/ , /troubleshooting/overview/
- https://docs.getform.io/getting-started/introduction/
- https://formsubmit.co/
- https://developers.tally.so/api-reference/introduction , https://www.fillout.com/help/fillout-rest-api
- https://developers.cloudflare.com/cache/concepts/default-cache-behavior/ , /fundamentals/reference/http-headers/

### 4. File uploads
- https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-admin/includes/file.php
- https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/functions.php , /formatting.php , /capabilities.php , /post.php
- https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php
- https://developer.wordpress.org/reference/functions/wp_handle_upload/ , /wp_handle_sideload/ , /wp_check_filetype_and_ext/ , /get_allowed_mime_types/ , /wp_get_image_editor/ , /wp_insert_attachment/
- https://developer.wordpress.org/rest-api/reference/media/
- https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- https://httpd.apache.org/docs/2.4/mod/mod_mime.html , https://nginx.org/en/docs/beginners_guide.html
- https://www.php.net/manual/en/ini.core.php , /features.file-upload.errors.php , /security.filesystem.nullbytes.php , /ziparchive.statindex.php , /phar.fileformat.phar.php , /function.move-uploaded-file.php
- https://raw.githubusercontent.com/php/php-src/PHP-8.0/UPGRADING
- https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/X-Content-Type-Options , /Content-Disposition
- https://developer.mozilla.org/en-US/docs/Web/SVG/Reference/Element/script
- https://github.com/darylldoyle/svg-sanitizer
- https://downloads.wordpress.org/plugin/contact-form-7.zip , /wpforms-lite.zip , https://wordpress.org/latest.zip
- https://docs.gravityforms.com/file-upload/ , /file-upload-security/ , /gform_secure_file_download_location/ , /gform_secure_file_download_url/
- Patchstack: /plugin/gravityforms/... , /plugin/forminator/... , /plugin/contact-form-7/ , /plugin/wpforms-lite/ , /plugin/ninja-forms/ , /plugin/svg-support/ , /plugin/safe-svg/ , /plugin/wp-file-upload/... , /plugin/form-block/... , /plugin/woocommerce-upload-files/... , /plugin/user-submitted-posts/... , https://patchstack.com/articles/unauthenticated-arbitrary-file-upload-vulnerability-patched-in-chaty-pro-plugin/
- https://zeropath.com/blog/gravity-forms-cve-2025-12352-summary , /blog/cve-2025-12974-gravity-forms-arbitrary-file-upload-summary
- https://blog.wpsec.com/contact-form-7-vulnerability/
- Unreachable (absence noted): Wordfence Intelligence v2 (HTTP 410) and v3 (401), wpscan.com (403)

### 5. Anti-spam
- https://akismet.com/developers/detailed-docs/comment-check/ , /submit-spam-missed-spam/ , /submit-ham-false-positives/ , /key-verification/ , /usage-limit/ , /building-your-application/use-akismet-to-filter-contact-form-submissions/
- https://akismet.com/blog/pro-tip-tell-us-your-comment_type/ , https://akismet.com/blog/theres-a-ninja-in-your-akismet/
- https://akismet.com/pricing/ , https://akismet.com/gdpr/ , https://akismet.com/privacy/ , https://akismet.com/developers/detailed-docs/errors/akismet-error-30001/ , https://akismet.com/developers/getting-started/
- https://downloads.wordpress.org/plugin/akismet.5.7.2.zip (and 5.6, 5.5, 5.4, 5.3.7, 4.2.5) , https://wordpress.org/plugins/akismet/
- https://rest.akismet.com/1.2/compatible-plugins
- https://github.com/Automattic/akismet-sdk-php , https://github.com/Automattic/akismet-api
- https://github.com/Automattic/jetpack/blob/trunk/projects/packages/forms/src/contact-form/class-contact-form-plugin.php
- https://developers.cloudflare.com/turnstile/ , /get-started/server-side-validation/ , /plans/ , /concepts/widget/ , https://www.cloudflare.com/turnstile-privacy-policy/ , https://developers.cloudflare.com/fundamentals/reference/policies-compliances/cloudflare-cookies/
- https://www.hcaptcha.com/pricing , https://www.hcaptcha.com/gdpr
- https://cloud.google.com/recaptcha/pricing , https://developers.google.com/recaptcha/docs/faq , https://cloud.google.com/terms/service-terms , https://www.google.com/recaptcha/about/
- https://www.stopforumspam.com/usage
- https://github.com/altcha-org/altcha , https://github.com/altcha-org/altcha-lib-php , https://altcha.org/docs/ , https://altcha.org/docs/proof-of-work/
- https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[search]=turnstile , https://wordpress.org/plugins/simple-cloudflare-turnstile/ , https://wordpress.org/plugins/hcaptcha-for-forms-and-more/

### 6. Async, retries, retention
- https://developer.wordpress.org/plugins/cron/ , /understanding-wp-cron-scheduling/ , /hooking-wp-cron-into-the-system-task-scheduler/
- https://developer.wordpress.org/reference/functions/wp_schedule_single_event/ , /spawn_cron/ , /wp_privacy_delete_old_export_files/
- https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
- https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-cron.php
- https://kinsta.com/knowledgebase/disable-wp-cron/ , https://wpengine.com/support/wp-cron-wordpress-scheduling/
- https://www.php.net/manual/en/function.fastcgi-finish-request.php , /function.register-shutdown-function.php
- https://developer.wordpress.org/reference/hooks/shutdown/ , https://developer.wordpress.org/reference/classes/wp_site_health/get_test_loopback_requests/
- https://github.com/deliciousbrains/wp-background-processing (wp-async-request.php)
- https://actionscheduler.org/ , /faq/ , /perf/ , /api/ , /wp-cli/
- https://packagist.org/packages/woocommerce/action-scheduler , https://wordpress.org/plugins/action-scheduler/
- https://raw.githubusercontent.com/woocommerce/action-scheduler/trunk/classes/ActionScheduler_QueueRunner.php , /classes/abstracts/ActionScheduler_Abstract_QueueRunner.php , /classes/ActionScheduler_QueueCleaner.php , /classes/ActionScheduler_AsyncRequest_QueueRunner.php , /readme.txt
- https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/wc-webhook-functions.php , /includes/class-wc-webhook.php , https://woocommerce.com/document/webhooks/
- https://docs.svix.com/retries , /receiving/verifying-payloads/how , /how-manual
- https://docs.stripe.com/webhooks , https://hookdeck.com/docs/retries
- https://docs.gravityforms.com/webhooks/ , https://wordpress.org/plugins/wp-webhooks/
- https://wpforms.com/how-to-auto-delete-old-form-entries-for-gdpr-compliance/ , https://wpforms.com/introducing-wpforms-1-6-6-delete-file-uploads-limit-payment-notifications/
- https://docs.gravityforms.com/personal-data/ , https://jetpack.com/support/jetpack-blocks/form-block/
- https://developer.wordpress.org/plugins/privacy/ , /adding-the-personal-data-eraser-to-your-plugin/ , /adding-the-personal-data-exporter-to-your-plugin/

### 7. Validation, errors, a11y, i18n
- https://developer.wordpress.org/apis/security/data-validation/ , /sanitizing/ , /escaping/
- https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
- https://developer.wordpress.org/reference/classes/wp_error/ , /classes/wp_rest_request/has_valid_params/
- https://developer.wordpress.org/reference/functions/sanitize_text_field/ , /sanitize_textarea_field/ , /sanitize_email/ , /is_email/ , /absint/ , /esc_url/ , /wp_kses/ , /rest_do_request/
- https://plugins.svn.wordpress.org/contact-form-7/trunk/includes/swv/swv.php , /modules/text.php , /includes/rest-api.php , /modules/akismet/akismet.php
- https://docs.gravityforms.com/gform_field_validation/ , /gform_validation/ , /gf_field/ , /gform_field_content/ , /gform_after_submission/
- https://developers.fluentforms.com/hooks/filters/form/ , /hooks/filters/
- https://laravel.com/docs/12.x/validation , https://symfony.com/doc/current/validation.html , https://zod.dev/basics
- https://www.w3.org/WAI/tutorials/forms/ , /notifications/ , /validation/
- https://www.w3.org/TR/WCAG22/ and the Understanding pages for 3.3.1, 3.3.2, 3.3.3, 1.3.5, 2.5.8, 3.3.7
- https://make.wordpress.org/core/2024/10/21/i18n-improvements-6-7/ , /2025/03/12/i18n-improvements-6-8/ , https://make.wordpress.org/core/tag/i18n/
- https://make.wordpress.org/meta/handbook/documentation/translations/
- https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/ , /localization/ , https://developer.wordpress.org/apis/internationalization/internationalization-guidelines/
- https://developer.wordpress.org/reference/functions/wp_set_script_translations/ , https://developer.wordpress.org/block-editor/how-to-guides/internationalization/
- https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/testcase-rest-api.php , /testcase-rest-controller.php
- https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/ , /phpunit/ , https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/
- https://wordpress.org/about/requirements/
- https://developer.wordpress.org/apis/security/nonces/

### 8. Extension surface
- https://developer.wordpress.org/plugins/hooks/ , /hooks/custom-hooks/
- https://woocommerce.github.io/code-reference/hooks/hooks.html
- https://docs.gravityforms.com/category/developers/php-api/hooks/ (plus the individual hook pages above)
- https://developers.fluentforms.com/hooks/filters/
- https://plugins.svn.wordpress.org/contact-form-7/trunk/includes/swv/swv.php

### 9. Native UX
- https://github.com/WordPress/WordPress/blob/7.0.4/wp-includes/class-wp-theme-json.php
- https://github.com/WordPress/gutenberg/blob/trunk/lib/class-wp-theme-json-gutenberg.php
- https://github.com/WordPress/gutenberg/issues/34198 , /81647 , /76534
- https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/form/block.json , /form/index.php , /form/variations.js , /form-input/style.scss , /elements.scss
- https://github.com/WordPress/WordPress/blob/7.0.4/wp-content/themes/twentytwentyfive/theme.json
- https://github.com/WordPress/WordPress/blob/7.0.4/wp-includes/assets/script-loader-packages.php , /wp-includes/js/dist/script-modules , /wp-includes/blocks , /wp-includes/block-supports/border.php , /wp-admin/menu.php , /wp-includes/default-filters.php , /wp-admin/includes/privacy-tools.php
- https://github.com/WordPress/gutenberg/blob/trunk/packages/wp-build/README.md , /packages/boot/README.md , /packages/admin-ui/README.md , /packages/theme/README.md , /packages/dataviews/README.md , /packages/ui/README.md
- https://registry.npmjs.org/@wordpress/dataviews/latest
- https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/ , /packages-scripts/
- https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/ , /block-supports/ , /block-variations/ , /block-bindings/
- https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/
- https://developer.wordpress.org/plugins/administration-menus/
- https://github.com/Automattic/jetpack/blob/trunk/projects/packages/forms/src/dashboard/class-dashboard.php , /src/contact-form/class-contact-form-plugin.php , /src/contact-form/class-contact-form-endpoint.php , /src/contact-form/class-util.php , /src/blocks/input/index.jsx , /src/blocks/contact-form/block.json , /src/modules/form/shared.ts , /src/modules/form/view.js , /src/dashboard/forms/views.ts , /package.json
- https://jetpack.com/support/jetpack-blocks/contact-form/managing-contact-form-responses-and-integrations/ , https://jetpack.com/forms/
- https://downloads.wordpress.org/plugin/jetpack.zip , /contact-form-7.zip , /fluentform.zip , /forminator.zip
- https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=contact-form-7 (and wpforms-lite, jetpack)
- https://wordpress.org/download/releases/ , https://wordpress.org/about/roadmap/ , https://make.wordpress.org/core/2026/06/19/roadmap-to-7-1/ , /2026/08/05/wordpress-7-1-field-guide/ , /2026/07/31/design-system-theming-in-wordpress-7-1/ , /2026/07/07/merge-proposal-design-system-theming/

### 10, 11. Contradictions, risks, open questions
Cross-references sources already listed above, plus:
- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/ (guidelines 4, 5, 6, 7, 8, 13, 16, 17)
- Unreachable this session, treat as absence not evidence: https://learn.microsoft.com/en-us/linkedin/marketing/conversions/insight-tag (404), https://business.x.com/en/help/campaign-measurement-and-analytics/conversion-tracking-for-websites.html (402), TikTok Events API portal (client-rendered, empty body), https://contactform7.com/dom-events/ (403), https://akismet.com/development/api/ (403)
