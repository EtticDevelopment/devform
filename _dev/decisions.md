# DevForm decisions

Locked decisions and the sourced facts behind them. Append, do not rewrite. Anything not in here is still open.

## Identity

| Item | Value |
|---|---|
| Display name | DevForm |
| wordpress.org slug | `devform` |
| Text domain | `devform` (forced by the slug) |
| PHP namespace | `DevForm\` |
| Function, hook, option prefix | `devform_` |
| Constant prefix | `DEVFORM_` |
| REST namespace | `devform/v1` |
| Repo | `EtticDevelopment/devform`, public |
| Licence | GPL-2.0-or-later |
| WordPress floor | 6.9 |
| PHP floor | 8.1 |

The `devform_` prefix deliberately breaks the house `ettic_[product]_` convention. The wordpress.org slug forces the text domain to `devform`, and Plugin Check's `Prefix_Scanner` derives expected prefixes from the slug, so `ettic_devform_*` would disagree with the text domain for no benefit. `ettic_` stays the convention for Ettic-branded products.

Name evidence: `wordpress.org/plugins/devform/` returns 301 to search, which means the slug has never been used (a closed plugin returns 200 with a notice). Clean against Plugin Check's 112-term `TRADEMARK_SLUGS` list. Clears WPCS `PrefixAllGlobalsSniff` `MIN_PREFIX_LENGTH = 4`. No npm, Packagist or notable GitHub collision.

Open risk: TMview shows DEVFORMA, French application 5271162, filed 2026-06-22, classes 41 and 42, status Filed. Different string, unregistered, and the class 41 pairing suggests training rather than software. TMview was the only reachable trademark service (USPTO behind a WAF, WIPO behind a proof of work widget, Justia behind Cloudflare), so this is clean on one source, not cleared. Needs a human check at USPTO and EUIPO before brand spend.

Defform was rejected. `defform.com` is a live Japanese SaaS doing AI triage of contact form submissions, and it already ships `defform-contact-form` on wordpress.org. Same category, and guideline 17 forbids a slug beginning with another product's term.

Domains: none registered. `devform.dev`, `devform.org` and `getdevform.com` were verified free via authoritative RDAP on 2026-08-19. Nothing can reserve the wordpress.org slug (guideline 16), so the name is unprotected until a working plugin is submitted.

## Product shape

1. Two modes. Form mode renders the form, default markup overridable from the theme, client editable in wp-admin. Mailbox mode is a pure endpoint the developer's own code POSTs to, returning structured field-level errors. Mailbox mode is also the headless answer, so there is no separate headless feature.
2. Per form, an ordered stack of post-submit actions: email, webhook POST, redirect. The `run_if` condition field exists in the schema from day one, with no conditions UI in v1, so adding one later is not a migration.
3. Entry storage is a per-form choice: store, or pass through and keep nothing.
4. File uploads are in scope, with an extension allowlist, magic byte validation, unguessable filenames and a capability-checked download handler.
5. Auto-delete after N days, global default with a per-form override, off by default.
6. Akismet integrates with the site's existing Akismet plugin, the way Elementor does. Gate on `Akismet::get_api_key()`, call `Akismet::http_post()`. DevForm never manages an Akismet key and never bills for one. It runs last, after honeypot, time trap and rate limiting have dropped the obvious junk, which keeps the site owner inside their own quota.
7. Native conversion tracking, free: GA4, Google Ads, Meta Pixel plus Conversions API, PostHog, and a GTM `dataLayer` event. One server-minted `event_id` per submission shared by the browser and server layers so nothing double counts.
8. Feels like a native part of the WordPress and Jetpack family, not a third-party bolt-on.

Not building: payments, multi-step, calculations, e-signature, multisite, WPML.

## Facts that changed the architecture

Each of these overturned an assumption. Sources are in the research briefs under `_dev/research/`.

1. **Nonces do nothing for logged-out visitors.** `wp_create_nonce()` hashes `tick|action|uid|token`; for an anonymous visitor `uid` is 0 and `wp_get_session_token()` is empty, so every anonymous visitor in the same tick gets a byte-identical string. CF7 has shipped `WPCF7_VERIFY_NONCE = false` since 4.9 (2017), Fluent Forms defaults verification off, and core's own `wp-comments-post.php` has no nonce at all. DevForm does not pretend otherwise.
2. **Page cache TTLs are days, not hours.** LiteSpeed ships a 7 day public TTL, Cloudflare APO 30 days for HTML, WP Rocket 10 hours. Any token embedded in cached HTML must assume it is stale. The design is a deterministic per-form HMAC that carries the issue time for the time trap and rejects blind POSTs, plus an optional short-lived token fetched over an uncached REST GET when `WP_CACHE` is defined, copying CF7's refill pattern. The refresh route is never load-bearing.
3. **A PHP-set cookie is unusable.** It either kills the page cache (batcache, Cloudflare) or is silently discarded (WP Rocket, W3TC, WP Engine).
4. **WP Engine strips `utm_` and `gclid` from the URL before PHP runs.** Server-side attribution capture is impossible there. The whole context field is client-side, read from `location.search`, `document.referrer` and storage, and posted as ordinary fields.
5. **The SSRF picture is patch-level, not version-level.** The expanded `wp_http_validate_url()` blocklist covering link-local, CGNAT and TEST-NET ships in 7.0.3+, 6.9.6+, 6.8.8+, 6.7.7+, 6.6.7+ and 6.5.10+, and is absent from 7.0.0 to 7.0.2 and 6.9.0 to 6.9.5. A `Requires at least` header cannot express a patch level, so DevForm resolves and checks the target itself. Core does re-validate every redirect hop for `wp_safe_remote_*`, so that is not the gap; the real gaps are the DNS rebinding window, IPv6, and dual-stack drift because `gethostbyname()` only sees the A record.
6. **Action Scheduler is not a delivery guarantee.** Its regular tick is scheduled through WP-Cron on `action_scheduler_run_queue`, its fallback is a non-blocking loopback POST to `admin-ajax.php`, the same mechanism Gravity Forms maintains a troubleshooting page for, and it does not auto-retry failed actions. DevForm owns retry state in its own columns. Attempt one fires inline behind `function_exists( 'fastcgi_finish_request' )` so the happy path never touches a queue, with WP-CLI and a documented crontab line for the rest, and a visible "queue last ran" indicator. The honest claim is not guaranteed delivery, which nobody in WordPress can offer. It is: every attempt is recorded, a dead queue is visible, and you can replay.
7. **Rate limiting on IP alone takes a site down behind a CDN.** Fluent Forms trusts no proxy by default and rate-limits 5 per 30 seconds, which behind Cloudflare is a site-wide 429. DevForm ships a trusted-proxy layer defaulting to trusting nothing, walks `X-Forwarded-For` from the right past known proxies rather than taking the leftmost attacker-controlled entry, and fails open with an admin notice when every request appears to come from one address.
8. **Transients are not a rate limit store.** With a persistent object cache they live only in Redis or Memcached, so a flush silently disables the limiter.
9. **The recurring CVE classes are architectural.** Four CVSS 9.8 object injection CVEs in 18 months trace to `unserialize()` on entry meta, so entry data is JSON and `serialize()` is never called on request data. The Forminator RCE (CVE-2026-15748) came from reading field configuration out of the request, so configuration is always server-side state keyed by form handle. Fluent Forms shipped six IDOR CVEs in under a year, so every entry access resolves the entry then checks capability. Everest Forms leaked exports written to disk, so exports stream.
10. **Playground previews work with this schema.** The SQLite integration shipped v3.0.0 on 2026-08-13 with a real MySQL parser and emulated INFORMATION_SCHEMA, and Playground's bundled copy was refreshed on 2026-08-18. The proposed schema was executed against that exact bundle: ENUM, JSON, LONGTEXT, composite keys, prefix indexes with `Sub_part` preserved, and `ALTER TABLE` all passed.
11. **CF7 is not frozen.** The "frozen at 6.2 with a 2028 successor" story appears only in WPForms-owned and Gravity-owned publications. Miyoshi announced 6.2 in December 2025 as a forthcoming major requiring WP 6.9 and PHP 8.3. No strategy should be timed on a CF7 death.
12. **WPCS below 3.4.1 is an RCE in CI.** CVE-2026-45293, CVSS 8.6, in the `WordPress.WP.EnqueuedResourceParameters` sniff, which reconstructed an argument and ran it through `eval()`. It fires when PHPCS lints untrusted PHP, which is exactly a pull request in CI, and it affects the `WordPress` and `WordPress-Extra` rulesets. Pinned to `^3.4.1`.

## CI

Copied from `magicauth` and `opentrust` where the house already had it right: PHP lint matrix, Plugin Check, PHPStan, version consistency, Semgrep, POT freshness.

New in this repo:

1. **PHPCS**, which neither existing plugin has. `WordPress-Extra` plus `PHPCompatibilityWP`, WPCS pinned `^3.4.1` for the CVE above. `dealerdirect/phpcodesniffer-composer-installer` is required explicitly because WPCS only lists it under `allow-plugins`, it is not a dependency.
2. **Playground PR previews**, the reason the repo is public. Playground runs in the visitor's browser and needs an unauthenticated download URL.
3. **Workflow-level hardening** the house does not declare: `permissions: contents: read`, `concurrency` with cancel-in-progress off `main`, and `timeout-minutes`.

Plugin Check specifics that are easy to get wrong: the five valid categories are exactly `general`, `plugin_repo`, `security`, `performance`, `accessibility` (underscore, a hyphen fails). Categories must be newline separated, the action converts them to commas itself. The action does post an idempotent PR comment, added in v1.1.5, but only with `pull-requests: write` and a `repo-token`; its README is stale and says the token is unused.

Semgrep's container was changed from `returntocorp/semgrep` to `semgrep/semgrep`. The old image now describes itself as moved.

## Open

1. Human trademark clearance at USPTO and EUIPO, and whether to docket a watch on DEVFORMA.
2. Whether DevForm ships a client-side validator in form mode, or leaves validation to the server plus native constraint validation.
3. Whether tracking fires into globals another plugin already loaded (Site Kit is at 5M installs, double-loading gtag double counts) or loads its own tags.
4. Where a Meta CAPI access token lives: `wp_options`, which lands in every backup, or a `wp-config.php` constant.
5. Free versus paid, if ever. The evidence says the natural line is delivery reliability and the repeater, since Basin charges for exactly that and Tally gives it away.

## Corrections from the third research pass

The first brief was wrong twice in ways that changed the positioning, and once in a way that would have shipped a permanent bug.

13. **Jetpack Forms is the free baseline, not Contact Form 7.** The module header reads `Requires Connection: No` and `Auto Activate: Yes`, so on 3,000,000 sites the form block is already there with no account. Free and shipped today: local storage in a `feedback` CPT, per-form storage opt-out, multi-step with a progress indicator, file upload, 19 field types, webhooks with the response and error logged to post meta, conditional logic with a PHP evaluator, a React responses inbox with CSV export, per-entry notes, and eight WP Abilities API abilities. Five things the first brief called differentiators are Jetpack features. What survives: signed delivery (nobody), retried delivery (nobody), replay (nobody), cross-action ordering with per-action failure isolation (nobody), retention and auto-delete (nobody in the block or builder cohort), native conversion tracking (nobody), and definition-in-a-file with a public JSON endpoint (nobody). Jetpack cannot follow on the last one: it has no PHP registration API and no public submission route, so it cannot be used headless.
14. **HXFE and Promptless are not evidence of anything.** Both are 0 installs, not under 10, both published within the last three months, both from portfolios of seven-plus zero-install plugins by the same authors, and both ship `CLAUDE.md` or `AGENTS.md` inside the distributed plugin. They are AI-generated plugin portfolios, not a market verdict. The concept is now cheap to generate, which means the moat is distribution, docs and trust, not the idea.
15. **Nobody asks for forms in git.** Across every reachable developer venue, not one developer requests version-controlled form definitions. What they ask for, repeatedly and specifically, is sane extensibility and stable markup. Gravity Forms changed its submit control from `input[type=submit]` to `button[type=submit]` in August 2026 and developers are patching fleets of sites over it. Lead the pitch with extensibility and markup you own. Files are the mechanism, not the headline. This also creates an obligation: if the default template is overridable, template changes are a breaking-change surface and need a deprecation policy.
16. **Turnstile, not Akismet, is what this audience reaches for.** In a 40-comment thread on form spam opened by someone running many Gravity Forms sites, Akismet is mentioned zero times and Cloudflare Turnstile is named by five separate people including the original poster. Akismet stays as the free integration with the site's existing plugin. Turnstile ships as a first-class adapter, not one of three parity options.
17. **A `JSON` column breaks dbDelta on MariaDB, permanently.** MariaDB aliases `JSON` to `LONGTEXT COLLATE utf8mb4_bin`, so `DESCRIBE` returns `longtext`. dbDelta string-compares the declared type against the live type, sees `'longtext' !== 'json'`, and emits an `ALTER TABLE ... CHANGE COLUMN` on every single run, forever. MySQL 8 and the SQLite driver both return `json` and are fine. MariaDB is a large share of managed WordPress hosting. Schema rules: `LONGTEXT` not `JSON`, `VARCHAR(20)` not `ENUM`, no generated columns (they are silently NULL under SQLite), no FULLTEXT (accepted silently in DDL, then `MATCH AGAINST` does not exist), `VARCHAR(191)` ceiling on anything indexed, `DATETIME` written from PHP with `gmdate()`. That schema is also dbDelta-safe, which WP VIP mandates.
18. **Playground previews are safe, but they are not a test.** The SQLite integration shipped v3.0.0 on 2026-08-13 with a real MySQL parser, and Playground's bundle was refreshed on 2026-08-18. The schema runs unmodified. But SQLite silently accepts an out-of-range ENUM value, an over-length VARCHAR and invalid JSON where MySQL errors, so a green Playground preview is never evidence that validation works.
19. **WordPress 7.0 shipped a core Connectors API**, `wp-includes/connectors.php`, with documented precedence: environment variable, then PHP constant, then database. Core registers Akismet through it. On 7.0+ DevForm registers its destinations as connectors and lets core own resolution, storage, masking and the settings surface, with a small internal fallback for older sites. This is the most credible "native part of the WordPress family" move available, and it means not building a secrets store at all.
20. **The EAA does not bind DevForm.** A plugin is not in the Article 2(1) product list. EN 301 549 V4.1.0 is a final draft not yet cited in the Official Journal, so nothing confers presumption of conformity yet. Accessibility stays a design commitment, not a compliance claim. Related: axe-core 4.13 ships exactly one WCAG 2.2 rule and it is off by default, so "WCAG 2.2 AA by construction" cannot be proven in CI and must not be claimed as if it were.

## Source of truth

A form is never defined in two places at once.

1. A file-defined form is authoritative in the file, at render time and validation time. No database mirror, no timestamp comparison, no sync tab. This avoids ACF Local JSON's entire documented failure class.
2. An admin-defined form is authoritative in the database and never participates in file loading.
3. A handle is claimed by exactly one source. A collision is a hard error naming both paths, never a silent precedence rule.
4. Client-editable keys are an overlay, not a fork. The file declares its own allowlist, client edits land in an overrides table keyed by form handle and JSON path, and a git pull never wipes them. The admin screen shows which values are overridden and what the file says underneath.
5. Import and export are explicit commands, never side effects of an admin save.
6. Actions live in the definition. The actions table is demoted to a cached runtime projection. The action runs table stays real, because a run is an event, not configuration.

## Secrets

1. A committed definition holds a reference, never a secret value. Three prefixes resolved at send time: `@env:NAME`, `@const:NAME`, `@secret:handle`. A bare name resolves env, then constant, then database, matching core's own order.
2. On WP 7.0+, register through the core Connectors API rather than building a store.
3. Encrypt the database tier with `sodium_crypto_secretbox`. Core has bundled sodium_compat since 5.2, so there is no dependency and no guideline 13 problem.
4. Key from `DEVFORM_ENCRYPTION_KEY` if defined, otherwise a 32-byte random key in a non-autoloaded option. Never key off `LOGGED_IN_KEY` or the salts: rotating salts is routine incident response, and anything encrypted under them becomes permanently unrecoverable at that moment.
5. Fail loud on decrypt failure. WP Mail SMTP returns the ciphertext on failure and hands it to the SMTP server as if it were the password. Do not copy that.
6. Export is a deny-by-default projection over a declared field list, never an `unset()` blacklist, so a new action type cannot leak a credential by omission. A Fluent Forms export shared for support carries the site's Slack webhook URL today.
7. Be honest in the readme: this protects against log leakage, partial dumps and casual browsing, not a full database dump, because the key lives in the same database. Nobody in WordPress solves that, core included.
