# Devform: corrections and additions

Brief 3. Compiled 19 August 2026 from 12 gap-closing research passes commissioned against a 30-item critique of brief 1.

**Where this brief and brief 1 disagree, this brief wins.** Brief 1 is superseded on every point listed in section 1. Brief 2 (the critique) is also wrong in four places, named below.

Claims not verified at a primary source are labelled `unverified` inline. Sources that refused automated access are named in section 15 rather than silently omitted.

---

## 1. Corrections to brief 1

### 1.1 SSRF: brief 1 is wrong, the critique is wrong, and the truth changes the design

Brief 1 §7.2.9 said: "As of WordPress 7.0.4 the shipping `wp_http_validate_url()` blocklist covers only 127/8, 10/8, 0/8, 172.16-31 and 192.168; 169.254.169.254 is not blocked on any released version."

The critique said that is false, fetched the 7.0.4 tag, found the expanded blocklist, and concluded core handles it.

Both are wrong. `wp-includes/http.php` was fetched at eleven release tags this session and diffed.

1. The 13-condition blocklist (169.254.0.0/16 with the inline comment "link-local and cloud metadata", 100.64.0.0/10 CGNAT, 192.0.0.0/24, 192.0.2.0/24, 192.88.99.0/24, 198.51.100.0/24, 203.0.113.0/24, 198.18.0.0/15, 224.0.0.0/4, and `240 <= $parts[0]`) is present in **7.0.3+, 6.9.6+, 6.8.8+, 6.7.7+, 6.6.7+ and 6.5.10+**.
2. It is **absent from 7.0.0, 7.0.1, 7.0.2, 6.9.0 through 6.9.5, 6.0.11 and everything older**, which carry exactly the five ranges brief 1 published.
3. Whether 169.254.169.254 is blocked is therefore a function of patch level, not major version, and it cannot be expressed in a `Requires at least:` header.

Three further mechanics, all verified in 7.0.4:

4. **IPv6 is structurally excluded, not partially covered.** Line 579 rejects any host containing `: # ? [ ]`, so `http://[::1]/` and `http://[::ffff:127.0.0.1]/` are rejected by a character check, not an IP check. Line 591 resolves with `gethostbyname()`, which is A-record only, so an AAAA-only hostname returns itself and is rejected as an error. Fail-closed for security and fail-closed for functionality: an IPv6-only webhook endpoint simply does not work through `wp_safe_remote_*`.
5. **The real residual gap is dual-stack drift.** A hostname with a public A record and an AAAA record pointing at `::1` passes validation, because validation only ever sees the A record. `wp-includes/Requests/src/Transport/Curl.php` never sets `CURLOPT_IPRESOLVE`, so libcurl is free to prefer the AAAA. Absence is the finding: nothing in core constrains the address family the transport uses.
6. **DNS rebinding is unmitigated at every hop.** `wp_http_validate_url()` resolves the host, tests the result, then returns the URL string and throws the IP away. The transport re-resolves at connect time. `CURLOPT_RESOLVE` and `CURLOPT_CONNECT_TO` appear nowhere in core. Validating by hostname and connecting by hostname is a TOCTOU by construction.
7. Alternate IPv4 notations are **not** a gap. `gethostbyname('2130706433')`, `gethostbyname('0177.0.0.1')` and `gethostbyname('127.1')` all return `127.0.0.1`, which then hits the 127/8 branch. Tested locally on PHP 8.5.3.
8. `http_request_host_is_external` can override a blocked-range hit, and core itself hooks `allowed_http_request_hosts()` onto that filter, which whitelists anything on the `allowed_redirect_hosts` list. Any plugin that adds a host there silently punches a hole in SSRF validation.

**The critique is wrong about redirects.** `wp_safe_remote_post()` sets `reject_unsafe_urls = true`; `WP_Http::request()` registers `WP_Http::validate_redirects()` on the `requests.before_redirect` hook (since 4.7.5), and `Requests.php` line 808 dispatches that hook on **each** hop before re-requesting. Every hop is re-validated. What redirects actually break is different and worse: `WP_Http::browser_redirect_compatibility()` downgrades a 302 to GET and Requests downgrades 303 to GET, so a signed POST body silently becomes a GET and the signature is void.

Rewritten requirement for the webhook action:

1. Resolve the hostname yourself, once, with `dns_get_record()` for both A and AAAA. `gethostbyname()` cannot see the IPv6 half.
2. Reject every returned address against your own full list. Implement the 13 IPv4 ranges yourself, because a site on 7.0.1 does not have them. Add IPv6: `::1/128`, `::/128`, `fc00::/7`, `fe80::/10`, `::ffff:0:0/96` (decode the embedded v4 and re-check it), `64:ff9b::/96`, `2002::/16`, `100::/64`.
3. Pin the resolved address for the actual request, via `CURLOPT_RESOLVE` through the `http_api_curl` action or your own cURL handle. Without pinning you have re-created the rebinding window core already has.
4. Set `'redirection' => 0`. Not because core fails to validate hops, but because a 302 rewrites your signed POST into a GET. Treat any 3xx as a delivery failure and log it.
5. Force https. Core allows ports 80, 443 and 8080 by default via `http_allowed_safe_ports`; refuse anything but 443.
6. Keep `reject_unsafe_urls` on as a second layer, and log when `http_request_reject_unsafe_urls` or `http_request_host_is_external` has non-default behaviour, because a third-party plugin can disable it.
7. Webhook URLs stay an admin-only, capability-gated setting. That is the actual mitigation; everything above is defence in depth.
8. Document the functional cost: IPv6-only endpoints work only because you do your own resolution.

**Copy the reference implementation.** `Automattic\Jetpack\Forms\Service\Form_Webhooks::is_blocked_ip()` does most of this today, in GPLv2-or-later code, with a source comment stating these ranges are "not covered by `wp_safe_remote_request()`". It adds `rawurldecode()` on the host to defeat `169%2e254%2e169%2e254`, strips IPv6 zone identifiers, and does `dns_get_record($host, DNS_AAAA)` explicitly because "gethostbyname only resolves IPv4".

Sources: `https://raw.githubusercontent.com/WordPress/WordPress/7.0.4/wp-includes/http.php`, `/7.0.2/`, `/6.9.5/`, `/6.9.6/`, `/6.5.10/`, `https://raw.githubusercontent.com/WordPress/WordPress/7.0.4/wp-includes/class-wp-http.php`, `/Requests/src/Requests.php`, `/Requests/src/Transport/Curl.php`, `/wp-includes/default-filters.php`, `https://raw.githubusercontent.com/Automattic/jetpack/trunk/projects/packages/forms/src/service/class-form-webhooks.php`

### 1.2 Action Scheduler: the hook name was right, the loopback claim was wrong

Brief 1 §6.4.1 named the hook `action_scheduler_run_queue`. The critique said the docs call it `action_scheduler_run_schedule`. **Brief 1 is right and actionscheduler.org's homepage prose is wrong.** From `ActionScheduler_QueueRunner`:

```php
const WP_CRON_HOOK     = 'action_scheduler_run_queue';
const WP_CRON_SCHEDULE = 'every_minute';
```

`action_scheduler_run_schedule` is a **filter** on the schedule name, not a hook. Firing it does nothing.

Brief 1 §6.4.3 said choosing Action Scheduler "sidesteps that entire support category" of loopback failures. **That is wrong.** `ActionScheduler_QueueRunner::hook_dispatch_async_request()` adds a `shutdown` action; `maybe_dispatch_async_request()` gates on `is_admin()`, a 60-second lock and a concurrency check, then dispatches through `WP_Async_Request::dispatch()`, which is:

```php
wp_remote_post( esc_url_raw( admin_url('admin-ajax.php') ), array(
  'timeout'   => 0.01,
  'blocking'  => false,
  'cookies'   => $_COOKIE,
  'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
) );
```

That is byte-for-byte the mechanism behind Gravity Forms' `GF_Background_Process` troubleshooting page. Action Scheduler inherits the cURL 6/7/28 and HTTP 401/403 failure category, it does not sidestep it. Core makes the same admission about its own equivalent in `cron.php`: "the wp_remote_post() function does not always respect the timeout and blocking parameters. A timeout of 0.01 may end up taking 1 second." Use core's quote in the docs instead of a competitor's.

Verified defaults, all in trunk (Action Scheduler 4.1.0, released 2026-08-05):

1. **No retry on failure.** `handle_action_error()` fires `action_scheduler_failed_execution` and marks the action failed. It is never re-queued. Brief 1 §6.4.2 was correct.
2. What looks like a retry is `ActionScheduler_QueueCleaner::reset_timeouts( $time_limit = 300 )`, which unclaims actions stuck over five minutes, and `mark_failures( 300 )`, which fails in-progress actions older than five minutes. A PHP fatal mid-action produces one re-run after roughly five minutes, not a retry policy.
3. Batch size 25 from cron (`action_scheduler_queue_runner_batch_size`), 100 from WP-CLI. Concurrent batches 1. Time limit 30 seconds. Memory ceiling `get_memory_limit() * 0.90`.
4. Retention: completed purged after 1 month, failed after 3 months (since AS 4.0.0).
5. WP-CLI is first class: `ActionScheduler::init()` registers `wp action-scheduler`, with `run --batch-size --batches --hooks --group --exclude-groups --free-memory-on --pause --force`.
6. `DISABLE_WP_CRON` kills the `action_scheduler_run_queue` event but not the admin-shutdown loopback, which is AS's own HTTP request. WP 6.9 moved `_wp_cron()` from `wp_loaded` to `shutdown`.
7. AS 4.1.0 requires WP 6.8 and PHP 7.2.

Sources: `https://raw.githubusercontent.com/woocommerce/action-scheduler/trunk/classes/ActionScheduler_QueueRunner.php`, `/ActionScheduler_AsyncRequest_QueueRunner.php`, `/lib/WP_Async_Request.php`, `/classes/ActionScheduler_QueueCleaner.php`, `/classes/abstracts/ActionScheduler_Abstract_QueueRunner.php`, `/classes/WP_CLI/ActionScheduler_WPCLI_Scheduler_command.php`, `https://raw.githubusercontent.com/WordPress/WordPress/7.0.4/wp-includes/cron.php`, `https://actionscheduler.org/faq/`

### 1.3 Contact Form 7 is not frozen, and there is no 2028 source

Brief 1 claim 4 stated "CF7 is publicly frozen at v6.2 with a successor targeted at 2028" as fact.

There is no primary source for it, and CF7's own site says the opposite. From contactform7.com's post "WordPress support policy revised" (2 December 2025), verbatim: "Contact Form 7's next major version, 6.2, will be released in the first half of 2026. The latest major WordPress version at the time will probably be 6.9; therefore, Contact Form 7 6.2 will support WordPress 6.9+. And, because WordPress 6.9 recommends PHP 8.3 or greater, Contact Form 7 6.2 will require PHP 8.3+ to work. In addition to Contact Form 7, we will apply this new policy to any other WordPress plugin product we develop." The last clause is the opposite of a freeze.

Current state: 6.1.7, last updated 2026-08-17, 10,000,000 active installs, requires WP 6.7, requires PHP 7.4, tested to 7.1. So 6.2 is late, not shipped, not final.

The news feed was walked back to March 2024 (four pages) and grepped for "2028", "freeze", "frozen", "successor", "final", "discontinu": zero hits for all of them. The freeze story appears only in WPBeginner (owned by Awesome Motive, which sells WPForms) and the Gravity Forms blog. Both are competitors.

**"Contactable" is real but is not an announced product.** `namespace Contactable\SWV;` opens `includes/swv/php/abstract-rules.php` and every rule class in shipped CF7 6.1.7. The 5.9 release notes say "SWV: Reorganizes the PHP classes for rules under the Contactable\SWV namespace." That the author is building a product identity around the name is a reasonable inference; any 2028 date is unsourced. `contactable.io` did not resolve when tried (`unverified`).

Consequence: brief 1 §11.1.9's CF7-importer timing argument rests on a date nobody published. Rebuild that decision on install counts, not on a window.

Sources: `https://contactform7.com/feed/`, `/feed/?paged=2`, `/feed/?paged=3`, `https://contactform7.com/2025/12/02/wordpress-support-policy-revised/`, `https://plugins.svn.wordpress.org/contact-form-7/trunk/readme.txt`, `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=contact-form-7`

### 1.4 The CF7 storage-addon number is a sum of overlapping populations

Brief 1 §1.3 said "roughly 1.9M ... about 8 to 19 percent". That is four rounded floors added as if disjoint, and it missed two add-ons. Verified counts, 19 August 2026:

| Plugin | Active installs |
|---|---|
| Flamingo | 800,000 |
| Database Addon for CF7 (CFDB7) | 600,000 |
| CF7 Apps | 300,000 |
| Redirection for Contact Form 7 | 200,000 |
| Advanced Contact form 7 DB | 70,000 |
| Database for CF7, WPforms, Elementor forms | 60,000 |
| Database for Contact Form 7 | 7,000 |

The naive sum is 2,037,000, which is 20.4 percent, outside brief 1's own stated range. That is the tell.

Correct statement: **at least 800,000 CF7 sites (8 percent) have installed the most-installed storage add-on. The true union is somewhere in [800k, 2.03M] and is unmeasurable from public data.** Report the largest single add-on, not the sum.

Source: `https://api.wordpress.org/plugins/info/1.2/` per slug.

### 1.5 The market is not "12 plugins with one architecture"

Brief 1 §1.1 is wrong three ways, and the denominator (critique #22) is wrong on top.

1. Forms authored as blocks in the post editor: Jetpack (3,000,000), SureForms (500,000), Kadence Blocks (600,000), Otter Blocks (300,000), Essential Blocks (200,000), Spectra Legacy (1,000,000).
2. Forms authored in a builder element: Elementor Pro, Bricks, Breakdance, Oxygen, Beaver Builder, Divi.
3. Forms authored in an admin textarea: HTML Forms (10,000).

There are four architectures, not one, and roughly 25 shipping form implementations, not 12. The corrected framing: **the admin drag-drop builder is the largest architecture, not the only one, and the file-defined form is unoccupied across all four.**

### 1.6 SureForms belongs in the competitive table and is the closest thing to Devform's stated design goal

500,000 active installs, v2.12.3, added 1 April 2024, rating 98/100 from only 87 ratings, by Brainstorm Force (Astra, Spectra, Starter Templates). Half a million installs in 28 months on an 87-rating base is consistent with distribution through a theme and template ecosystem rather than organic adoption (`likely`, inferred).

Its readme already claims Devform's positioning: "Store and manage form entries directly inside your WordPress dashboard. Review submissions, search entries, export data, and keep control of your form information without relying on a separate form-data service", and in the FAQ "Where is form data stored? Form entries are stored in your WordPress database." Webhooks are paid.

It is a bigger threat to Devform's positioning than anything in brief 1's table, and the lesson is distributional: the fastest-growing form plugin in WordPress got there by being bundled with a theme ecosystem, not by being better.

Source: `https://plugins.svn.wordpress.org/sureforms/trunk/readme.txt`, `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=sureforms`

### 1.7 HXFE and Promptless: brief 1's "sobering counter-evidence" is refuted by one API call

Brief 1 claim 10 and §11.1 treat these as evidence that "the market has already been offered this and did not want it". Verified:

| | HXFE Code-First Forms | Promptless Forms |
|---|---|---|
| active_installs | 0 (not "<10") | 0 |
| added | 2026-06-10 | 2026-05-30 |
| last_updated | 2026-07-11 | 2026-08-13 |
| ratings | 0 | 0 |
| author | youheiokubo | promptlesswp |

Both are under three months old. `request[author]=youheiokubo` returns **seven plugins, all 0 installs, all published between 10 June and 28 July 2026**. HXFE ships `CLAUDE.md`, `ai-reference.md`, `llms.txt`, `DESIGN.md` and `HXFE-manual.md` inside the distributed plugin; Promptless ships `AGENTS.md`. These are AI-generated plugin portfolios published at machine speed with no site, no marketing and no support activity.

Rewrite the claim as: **two plugins shipped a similar concept in mid-2026; both are zero-install, weeks-old entries from AI-generated portfolios. They are not evidence of demand failure. They are evidence that the concept is now cheap to generate, which means the moat is distribution, docs and trust.**

One thing HXFE says better than brief 1, from its readme: "Because forms are PHP arrays, AI coding tools (Claude, Cursor, GitHub Copilot, Codex) can read and edit them directly, no screenshots, no GUI walkthroughs, no copy-paste." A file-defined form is an agent-editable form. That framing is worth one paragraph in the readme.

Sources: `https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[author]=youheiokubo`, `https://plugins.svn.wordpress.org/hxfe-code-first-forms/trunk/`, `/promptless-forms/trunk/`

### 1.8 "Nobody does signed, retried, logged delivery" needs narrowing

Jetpack Forms logs per-entry webhook responses today, free, at 3,000,000 installs: `_jetpack_forms_webhook_response` post meta stores `{timestamp, http_code, headers, body}` as JSON and `_jetpack_forms_webhook_error` stores the `WP_Error` message. It does not sign and does not retry (one `wp_safe_remote_request()`, no queue, no backoff).

Corrected claim: **nobody in WordPress does signed, retried delivery. Jetpack logs it; nobody signs or retries it.**

### 1.9 Gravity Forms: two smaller corrections

1. Brief 1's table said the Webhooks add-on had "async default only since 2.9.32". GF's own compatibility table reads "Webhooks | Yes | Yes (versions 1.0+)". Background feed processing has been on since Webhooks 1.0. Correct it.
2. Brief 1 §1.8 compressed `gform_max_async_feed_attempts` into a webhook retry claim. The critique said it is "not a webhook retry setting". Both are half right: it is the retry cap for any **background-processed feed**, default 1, and the docs' own worked example is `if ( $addon_slug == 'gravityformswebhooks' ) { $max_attempts = 2; }`. It is the only retry knob a GF webhook has. Two caveats: it applies only when the feed is async (disableable per feed via `gform_is_feed_asynchronous`), and it counts feed-processing attempts inside `GF_Feed_Processor`, not HTTP failures. The Webhooks feed meta object contains no secret, no signature algorithm, no retry count, no timeout and no idempotency key; `gform_webhooks_post_request` passes an array for a 500 and a `WP_Error` only for transport failure, so the add-on cannot distinguish a 500 from a 200 without user code.

Sources: `https://docs.gravityforms.com/add-on-support-for-background-feed-processing/`, `/webhooks-feed-meta/`, `/gform_max_async_feed_attempts/`, `/gform_webhooks_post_request/`

### 1.10 The core form block: brief 1 got the state wrong

Brief 1 §8.1.1 said gutenberg#44186 "was opened 15 September 2022. Four years experimental."

Verified via the GitHub API: issue #44186 is **closed**, last updated 2023-10-05, and was closed by PR #44214 ("Introduce experimental form & inputs blocks"), merged 2023-10-05T07:44:58Z.

Correct framing: **the proposal was accepted and merged into Gutenberg in October 2023, and in the two years and ten months since it has not graduated to core.** `core/form` and `core/form-input` still carry `"__experimental": true` on Gutenberg trunk behind the `gutenberg-form-blocks` flag, and `wordpress-develop/trunk/src/wp-includes/blocks/form/block.json` returns 404. Every 2026 commit touching `packages/block-library/src/form` is incidental maintenance from a repo-wide sweep (ESLint suppressions, a 40px control deprecation, a docs autogeneration pass). No feature work in 2026.

Platform risk is lower than brief 1 implies, and for a better reason.

Two details worth stealing, both already in core's own block: `render_block_core_form()` uses `WP_HTML_Tag_Processor` to set `action` and `method` on saved markup and injects hidden fields with `str_replace('</form>', ...)`, which is exactly brief 1 §8.2.4's on-ramp mechanism; and it expands `{SITE_URL}` and `{ADMIN_URL}` tokens in the action attribute. Also note `lib/experimental/kses-allowed-html.php` re-adds `input`, `label` and `textarea` to kses when the experiment is on, and still does **not** allow `<form>`. Core's own team would not re-permit it, which is the strongest argument for the server-rendered block plus `data-devform=""` on-ramp and against telling users to paste raw `<form>` markup.

### 1.11 HTML Forms: three corrections and a change of ownership

1. **The GitHub repo is gone.** `github.com/ibericode/html-forms` returns 404 and no public repo exists. The wordpress.org listing now credits **Link Software LLC** (`linksoftware`), and htmlformsplugin.com's footer confirms it. No acquisition announcement is visible (`unverified`). The only readable source is `plugins.svn.wordpress.org/html-forms/trunk/`.
2. **Markup control is not "Full".** `Form::get_html()` hard-wraps the developer's markup with the `<form>` element, `hf-form hf-form-{ID}` classes, data attributes and an HTML comment carrying the plugin version. Correct summary: "full control of the fields, none of the form element. HTML lives in the DB, not the repo."
3. **Hook count is 6 actions and 25 filters**, not "2 actions, 4 filters". Two of them are the naming convention Devform should adopt: `hf_validate_form_{$slug}` and `hf_form_{$form->slug}_success`, keyed on a string handle rather than a numeric id.
4. Storage is one table, `hf_submissions`, with values as `json_encode()`. Not `serialize()`. A working precedent for brief 1 §5.2.3 in a plugin with no object-injection CVE.
5. Premium is $79/99/119 per year and paywalls **webhooks, file uploads and export**, all three of which Devform has in the free scope.

Sources: `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=html-forms`, `https://plugins.svn.wordpress.org/html-forms/trunk/`, `https://htmlformsplugin.com/`

### 1.12 dbDelta does add indexes

Brief 1 §5.2.5 said dbDelta "does not reliably add indexes to existing tables". That is wrong. In `wp-admin/includes/upgrade.php`, dbDelta runs `SHOW INDEX FROM {$table}`, normalises each existing key into a comparable string, and emits `ALTER TABLE {$table} ADD $index` for every declared index with no match. Indexes are added. The real risk is the opposite: a declaration that does not normalise to the same string produces a **duplicate** index.

What dbDelta genuinely cannot do: drop a column, drop an index, rename anything. There is no DROP anywhere in its generated queries.

Two traps brief 1 does not mention, and both bite the schema it proposed:

1. dbDelta splits input with `explode( ';', $queries )`, so a semicolon inside a string literal or DEFAULT value shreds the statement.
2. The column-type regex is `'|`?' . $tablefield->Field . '`? ([^ ]*( unsigned)?)|i'`, which stops at the first space. `status ENUM('in progress','done')` is read as type `enum('in`, and dbDelta emits a corrupt CHANGE COLUMN on every run, forever. Any ENUM value containing a space is a permanent migration footgun.

Source: `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-admin/includes/upgrade.php`

### 1.13 Plugin Check: brief 1 was right, the critique was wrong, and both missed the real problem

1. **The action does post a PR comment.** Release v1.1.5 (2026-01-16) notes: "Display the Plugin Check report in the PR as a comment." `src/main.ts` calls `postPRComment()`, and `src/pr-comment-manager.ts` finds the marker `<!-- wordpress-plugin-check-comment -->` and updates the last matching comment rather than adding a new one. The README is stale and says `repo-token` is "Not currently used", which is what the critique read. Trust the source.
2. **Categories are exactly five slugs**, from `Check_Categories.php`: `general`, `plugin_repo`, `security`, `performance`, `accessibility`. Note the underscore in `plugin_repo`; a hyphen fails.
3. **An invalid category does not fail the job.** `Plugin_Check_Command` parses with `wp_parse_list()` and filters with `array_intersect()`. An unrecognised slug matches nothing and produces a green run that tested nothing. Check slugs are validated; categories are not. The critique's warning is inverted.
4. **The `accessibility` category has zero checks in it.** The constant appears exactly twice in the whole plugin-check 2.1.0 codebase, both in `Check_Categories.php`. `includes/Checker/Checks/` contains only General, Performance, Plugin_Repo and Security. Keep the slug in the CI line (it costs nothing and will start working if checks land) but do not call it an accessibility gate.
5. `categories` is newline-separated in the action, which converts newlines to commas itself. Do not comma-separate it.
6. `permissions: pull-requests: write` is required or the comment silently never appears, and the README's example omits it.
7. `include-experimental` defaults to `'false'` in `action.yml` while the README says `Default: true`. Set it explicitly.

Sources: `https://raw.githubusercontent.com/WordPress/plugin-check/trunk/includes/Checker/Check_Categories.php`, `https://api.github.com/repos/WordPress/plugin-check-action/releases`, `https://raw.githubusercontent.com/WordPress/plugin-check-action/main/src/main.ts`, `/src/pr-comment-manager.ts`, `/action.yml`

### 1.14 WPCS 3.4.1: the advisory is real, one line of brief 1's advice is redundant

GHSA-3pwp-g2mj-5p3v = CVE-2026-45293, CVSS 3.1 score 8.6, published 2026-07-28. Verbatim: "running PHPCS with WordPressCS over untrusted PHP code, for example, in a CI pipeline that lints pull requests ... could lead to arbitrary command execution on the scanning host", root cause "The sniff's `is_falsy()` method reconstructed the argument and ran it through `eval()`". Vulnerable `>= 0.14.1, < 3.4.1`. Affects the `WordPress` and `WordPress-Extra` rulesets; `WordPress-Core` and `WordPress-Docs` are not affected. Brief 1's numbers all check out.

Correction: "Do not let Composer pull PHP_CodeSniffer 4.x" is redundant. WPCS 3.4.1's composer.json requires `squizlabs/php_codesniffer: ^3.13.5`, and a caret constraint already excludes 4.x. Keep the explicit `dealerdirect/phpcodesniffer-composer-installer` requirement, because WPCS does not depend on it (it only lists it under `config.allow-plugins`).

Sources: `https://api.github.com/advisories/GHSA-3pwp-g2mj-5p3v`, `https://raw.githubusercontent.com/WordPress/WordPress-Coding-Standards/3.4.1/composer.json`

### 1.15 Three claims that are no longer differentiators

1. **"Store the submission before attempting any delivery"** (§5.6.1) is shipped and documented by a competitor. Otter Blocks' docs, verbatim: "every valid submission is saved to the database first, before the email and any other delivery actions run, so leads are not lost if email delivery fails." Keep the behaviour, drop the novelty claim.
2. **Per-form store / fire-and-forget** is Jetpack's `saveResponses` block attribute plus the `jp-temp-feedback` post status, shipped on 3M installs. The code comment reads: "Temporary storage when saveResponses is 'no'. We want these responses skip the inbox but we still need to keep them in the database so that filters and integrations continue to work."
3. **"Your data stays in your database at every tier"** (§10.1 claim 2) is matched by Jetpack, SureForms, Bricks, Breakdance free, Otter free and Elementor Pro. What survives is narrower: **format** (JSON in indexed custom tables versus serialized postmeta), **retention** (a purge policy the site owner can see, which none of the builders ship), and **replay** (a failed delivery you can re-fire).

---

## 2. Jetpack Forms and the corrected free-tier picture

Brief 1 omitted Jetpack entirely. It is the most consequential omission in the document.

### 2.1 It is on by default, on 3,000,000 sites, with no account

The module header in `projects/plugins/jetpack/modules/contact-form.php` reads verbatim:

```
Module Name: Forms
Module Description: Add contact, registration, and feedback forms directly from the block editor.
First Introduced: 1.3
Requires Connection: No
Auto Activate: Yes
```

`Requires Connection: No. Auto Activate: Yes.` This contradicts jetpack.com's own support page, which says the Form block is "available on all Jetpack-connected sites". That page is stale. Trust the module header. (Jetpack Forms working fully on a disconnected install for the non-AI, non-Drive features is `likely`; a disconnected install was not tested.)

Jetpack: 3,000,000 active installs, v16.1.1, last updated 11 August 2026, rating 76/100 from 2,405 ratings.

### 2.2 What the free tier actually contains

1. **Free local storage.** Responses are a `feedback` CPT with `show_ui => false`, `show_in_rest => true` and a dedicated REST controller. Two custom post statuses: `spam` and `jp-temp-feedback`.
2. **Free per-form storage opt-out**, via the `saveResponses` block attribute.
3. **Free multi-step**, with a progress indicator: `form-step`, `form-step-container`, `form-step-divider`, `form-step-navigation`, `form-progress-indicator` all exist as blocks. Brief 1 §4.1.12 called free multi-step "a cheap, high-visibility win". It is shipped.
4. **Free file upload** (`field-file` plus `dropzone`), free rating, free slider, free consent, free image-select. The full field-block set is 19 field types plus 9 support blocks.
5. **Free webhooks with delivery logging.** Block attribute array `{webhook_id, url, method, format, enabled, verified}`, methods POST/GET/PUT, formats json or urlencoded, fired synchronously on `grunion_after_feedback_post_inserted`, spam-gated with `if ($is_spam) { return; }` before anything else. No signing, no retry. Response and error logged to post meta.
6. **Free conditional logic with a PHP evaluator and an Interactivity API client.** See section 8.
7. **A React responses inbox** at Jetpack > Forms > Responses, with unread counts, status counts, bulk actions, print, and CSV plus Google Drive export.
8. **Per-entry notes for free, with zero new schema**, by registering the CPT with `'supports' => array('comments')` and a `comments_open` filter restricting them to logged-in users. Cheap idea worth stealing.
9. **Eight WP Abilities API abilities**: `jetpack-forms/list-forms`, `get-form`, `create-form`, `delete-form`, `get-responses`, `update-response`, `bulk-update-responses`, `get-status-counts`. It can create a form from an agent, which none of the four plugins brief 1 named can do.

### 2.3 Free Akismet is conditional, and that matters for Devform's own integration

`class-contact-form-plugin.php` line 218:

```php
if ( defined( 'AKISMET_VERSION' ) || function_exists( 'akismet_http_post' ) ) {
    add_filter( 'jetpack_contact_form_is_spam', array( $this, 'is_spam_akismet' ), 10, 2 );
}
```

Jetpack does not bundle Akismet. "Free Akismet" means "free if the site already has the Akismet plugin and a key". The wordpress.org listing's anti-spam claim is Jetpack Anti-Spam marketing, a paid product. Devform's Akismet adapter (brief 1 §7.1.7) is unaffected: gate on `Akismet::get_api_key()` exactly as planned.

Note also that Akismet's own comment path ships the whole `$_SERVER` superglobal except the cookie header, and every string `$_POST` value, to akismet.com. Brief 1 §7.1.8 attributed that to CF7. It is Akismet's core behaviour, which CF7 inherits. Devform building its own payload with an explicit allow-list remains a genuine differentiator.

### 2.4 Where Jetpack cannot follow

1. **No PHP registration API, no file path, no import.** Forms are block markup in `post_content`, a `jetpack_form` CPT with revisions, or the legacy `[contact-form]` shortcode. The forms-as-files position is unoccupied.
2. **No public submission REST route.** Submission is `wp_ajax_grunion-contact-form` and `wp_ajax_nopriv_grunion-contact-form` plus a plain-POST path. `Jetpack_Form_Endpoint` registers only `/preview-url` and `/status-counts`, both permission-gated. **Jetpack Forms cannot be used headless.** That is Devform Mode B's opening.
3. **Field values are serialized PHP in postmeta.** `update_post_meta($post_id, '_feedback_extra_fields', ...)` with an array, run through `maybe_serialize()`. This is exactly the object-injection surface brief 1 §7.2.1 warns about, in Automattic's own code. Devform's JSON-only rule now has a named counter-example.
4. **Three coexisting hook prefixes** (`grunion_`, `contact_form_`, `jetpack_forms_`) from a decade of renames. Lesson: pick one prefix and never change it.

### 2.5 It is not being spun out

Two independent absences: `projects/plugins/` in the Jetpack monorepo has 21 directories and **no `forms`** (Social, Stats, Protect, Search, Backup and VideoPress all have one), and `api.wordpress.org` returns 404 for the `jetpack-forms` slug. The word "standalone" in Jetpack issue titles refers to a standalone response detail page inside the dashboard. Verified for the absence; an unannounced spin-out cannot be ruled out.

### 2.6 The corrected free-tier picture, and what survives

| Capability | CF7 | WPForms Lite | Jetpack | SureForms | Kadence | Otter | Bricks | Breakdance |
|---|---|---|---|---|---|---|---|---|
| Local entry storage | no | no | **free** | **free** | Pro | **free** | free (off by default) | **free** |
| Email notification | free | free | free | free | free | free | free | **Pro** |
| Webhook | no | Pro | **free, unsigned, no retry** | Pro | Pro | Pro | free (2.0+) | Pro |
| Multi-step | no | Pro | **free** | free | Pro | Pro | no | free |
| File upload | free | Pro | **free** | free | free | Pro | free | free |
| Retention / auto-delete | n/a | no | no | no | no | no | **none at all** | no |
| Signed delivery | no | no | no | no | no | no | no | no |
| Retried delivery | no | no | no | no | no | no | no | no |
| Replay | no | no | no | no | no | no | no | no |

Every vendor in the builder cohort paywalls a different half of the form. Breakdance free stores submissions but paywalls sending the email. Kadence free sends the email but paywalls storing the submission. Essential Blocks free does neither. They have all independently concluded that the second half of a form is where the money is.

**What survives as differentiation, after Jetpack:** signed plus retried plus replayable delivery (nobody), explicit cross-action ordering with per-action failure isolation (nobody), retention and auto-delete (nobody in the block or builder cohort), native conversion tracking (nobody), and definition-in-a-file with a JSON submission endpoint (nobody).

Sources: `https://raw.githubusercontent.com/Automattic/jetpack/trunk/projects/plugins/jetpack/modules/contact-form.php`, `/projects/packages/forms/src/contact-form/class-contact-form.php`, `/class-contact-form-plugin.php`, `/class-conditional-logic.php`, `/src/service/class-form-webhooks.php`, `/src/abilities/class-forms-abilities.php`, `/src/dashboard/README.md`, `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=jetpack`

---

## 3. The block-plugin and page-builder cohort, with numbers

Critique items 17 and 18 asked for sizing. Here it is, from HTTP Archive's July 2026 crawl (open API, published methodology, monthly), mobile origins, all geographies, all ranks. Denominator: WordPress = 2,819,587 origins.

### 3.1 Page builders

| Product | Origins | Share of WordPress |
|---|---|---|
| Elementor | 939,017 | 33.30% |
| wpBakery | 234,884 | 8.33% |
| Divi | 161,746 | 5.74% |
| Beaver Builder | 30,811 | 1.09% |
| SiteOrigin | 20,814 | 0.74% |
| Oxygen | 12,316 | 0.44% |
| Bricks | 10,676 | 0.38% |
| Themify | 6,333 | 0.22% |
| Breakdance | 5,305 | 0.19% |
| **All builders** | **1,421,902** | **50.43%** |

**Bricks + Oxygen + Breakdance = 28,297 origins = 1.00% of WordPress.** That is the "theme builders and custom-built sites" audience brief 1 writes for, as measured. Even trebled to account for fingerprint evasion (these three sell clean output, so they are systematically under-detected: Oxygen self-reports 180,000 sites against 12,316 detected, a 15x gap) it is about 3 percent.

Trend: Bricks 394 origins (Jan 2023) to 10,676 (Jul 2026), +51.7% year on year. Breakdance +51.7% YoY. Oxygen peaked at 14,085 in Jul 2024 and is down 12.6%. Elementor peaked at 963,315 in Jan 2026 and posted its first recorded decline.

Netherlands slice (WordPress = 74,965 origins): Elementor 24.84%, wpBakery 8.07%, Divi 6.19%, Kadence 1.89%, Bricks 0.51%, Oxygen 0.50%, Breakdance 0.31%. One NL divergence worth knowing: **Gravity Forms is 7.04% of Dutch WordPress origins against 3.53% globally**, twice as strong here. CF7 is 22.06% NL against 29.74% globally.

### 3.2 Block plugins with a form block

| Product | Origins | Share | Active installs |
|---|---|---|---|
| Kadence Blocks | 46,723 | 1.66% | 600,000 |
| Spectra Legacy | 21,269 | 0.75% | 1,000,000 |
| Otter Blocks | 4,473 | 0.16% | 300,000 |
| Essential Blocks | 3,040 | 0.11% | 200,000 |
| **Combined** | **75,505** | **2.68%** | |

Block plugins with **no** form block: GenerateBlocks 13,723 origins / 200,000 installs, Stackable 8,048 / 100,000. Greenshift ships a markup-only "Form Elements" block with no documented submission handling.

Caveat on method: HTTP Archive crawls one landing page per origin from the Chrome UX Report URL list and fingerprints with Wappalyzer. It counts origins whose home page fingerprints, not installs. Kadence jumps from 19,868 (Jul 2024) to 38,567 (Jan 2025), which is almost certainly a Wappalyzer rule change; do not quote its series as a trend.

### 3.3 What each cohort paywalls

1. **Kadence Blocks.** Free post-submit actions are exactly four, read from the shipped `advanced-form-ajax.php` switch: `email`, `redirect`, `mailerlite`, `fluentCRM`. **Database Entry and Webhook are both Blocks Pro** ($99/yr minimum). A user opened a wordpress.org thread titled "Send Webhook after submit is not free" and Kadence support replied "This should void future confusion from this feature being Pro."
2. **Spectra.** The vendor split forms out entirely. Spectra was renamed "Spectra Legacy" and frozen ("receives security and compatibility updates and full support, but no new features"); its successor Spectra Blocks ships **no form block at all**. Forms moved to SureForms. The largest block-plugin vendor concluded a form block does not belong in a block plugin.
3. **Otter Blocks.** Form block, entry storage and email are free; **webhook and CSV/WXR export are Pro**. Webhook config is name, URL, method, headers plus per-field mapped names, with an `otter_form_webhook_payload` filter. No retry, no signing, no auth documented.
4. **Essential Blocks.** Harshest paywall in the cohort: **email notifications, response storage and Mailchimp are all Pro**. The free form block cannot send an email. No webhook at any tier. Its library separately ships Fluent Forms and WPForms styler blocks, which is the vendor assuming its users will bring a real form plugin.
5. **Bricks.** 14 actions including Webhook (2.0) and Conditional actions (2.4). Webhook has endpoints, headers, JSON or form-data, a rate limit defaulting to 60 requests/hour, a "Continue on error" toggle and a `bricks/webhook/timeout` filter. **No signing, no retry, no delivery log, no replay.** Submission storage arrived in 1.9.2, is off by default, and has **no automatic retention, no auto-delete and no scheduled purge**. Deletion is manual only.
6. **Breakdance and Oxygen** (same engine; Oxygen 6 abandoned its own forms and now runs the Breakdance Forms extension). Free tier stores submissions and **paywalls sending the email**, plus export, webhook, popups and every CRM.
7. **Elementor Pro.** The entire Forms widget is Pro, confirmed in Elementor's own wordpress.org readme. Collect Submissions is on by default, 15 actions including WebHook. No retention documented.

### 3.4 The honest answer to "why would a Bricks or Kadence user switch"

Not for field breadth, not for a nicer builder, not for styling. They have all three, and their forms are styled by the builder. Five wedges, each backed by a named gap at the vendor's own source:

1. **Entry storage that is not paywalled.** Hits Kadence, Essential Blocks, Elementor, Beaver Builder and Spectra Legacy (1M installs, email-only, no local record). Does **not** hit Bricks, Breakdance, Otter or SureForms.
2. **Webhooks that are not paywalled and are actually reliable.** Kadence, Otter, Breakdance and Elementor free all paywall or omit it. Bricks 2.0 and Elementor Pro have it in the box, but neither signs, retries, logs or replays. Bricks' entire reliability surface is a 60/hour rate limit and a "Continue on error" toggle.
3. **Ordered actions with per-action failure isolation.** Bricks runs actions in insert order with a hard-coded hack that shoves `redirect` to the end, and a failing action aborts the rest. A user documented the exact failure: "Actions 0 to 3 run successfully, but action 4 throws an error and stops the process ... the email has already been sent and a record has been saved to the database. When the user tries to resubmit the form, it leads to duplicate entries and multiple emails." Bricks staff declined to fix it on the record: "Some users might rely on the fact that the next action will not run if the previous fails."
4. **Retention and auto-delete.** Bricks, Elementor, Breakdance and Otter document none. All four are used heavily in the EU and all four store personal data indefinitely. Genuinely unclaimed.
5. **Conversion tracking.** Bricks dispatches `bricks/form/success` and expects you to write the gtag call. A user asked exactly that in July 2025 ("I have this code from google to track a conversion ... How do I get this to trigger on successful form submission with a bricks form?"). Nobody in this cohort ships GA4, Google Ads, Meta CAPI, PostHog or GTM as a configured action.

**The counterweight, in a Bricks user's own words**, from the same forum: "Don't need to waste time on something that a dedicated form plugin will always be able to do better. Use a dedicated form plugin that has this function such as FluentForms, WSForm etc." The builder-cohort default when the native form falls short is to install an **established** name. Distribution, not capability, is the binding constraint.

### 3.5 Where the beachhead actually is

**GenerateBlocks and Stackable users.** 21,771 detected origins, 300,000 combined installs, plus GeneratePress at 500,000 theme installs and 62,655 origins. No form block at all, nothing to displace, no configured form to abandon, no vendor loyalty. That is a cleaner first target than Bricks.

### 3.6 No native builder element is needed for v1

1. **Page builders cannot render Gutenberg blocks.** Bricks' `wordpress/wordpress` element offers classic widgets only (archives, calendar, categories, pages, recent comments, recent posts, tag cloud, taxonomy). There is no block renderer.
2. **The shortcode is the only universal embed.** Bricks ships `wordpress/shortcode` with a "Don't render in builder" toggle; Elementor ships a Shortcode widget; Breakdance and Oxygen ship code/HTML elements.
3. **What the biggest vendor actually built is styling controls, not a native element.** SureForms markets "Page Builder Styling ... Gutenberg Block Editor, Elementor, Bricks Builder" and charges for it in SureForms Business. Devform does not need that: its whole premise is that you style the form with your own CSS, which is what this audience does anyway.
4. Ship four embed paths: `[devform handle="contact"]`, the server-rendered block, a `devform_render($handle)` PHP function for template files, and per-builder "how to embed" docs. If one native element is ever built, build the Bricks one first: `bricks/elements/form/controls` and a published element API make it the cheapest, and Bricks has the loudest documented dissatisfaction.

Sources: `https://cdn.httparchive.org/v1/adoption?technology=Bricks&geo=ALL&rank=ALL&start=latest` and per technology, `https://httparchive.org/faq`, `https://plugins.svn.wordpress.org/kadence-blocks/trunk/includes/advanced-form/advanced-form-ajax.php`, `https://plugins.svn.wordpress.org/ultimate-addons-for-gutenberg/trunk/readme.txt`, `/spectra-blocks/trunk/readme.txt`, `https://docs.themeisle.com/otter-page-builder-blocks-extensions/form-blocks`, `https://essential-blocks.com/docs/eb-form-block/`, `https://academy.bricksbuilder.io/article/form-element/`, `/builder/features/save-form-submissions/`, `https://forum.bricksbuilder.io/t/wait-form-actions-process-flow/36419`, `/t/bricks-builder-form-has-no-webhook/18144`, `/t/google-ads-conversion-tracking/34250`, `https://breakdance.com/pricing/`, `https://oxygenbuilder.com/documentation/forms/form-actions-api/`, `https://elementor.com/help/actions-after-submit/`, `https://docs.wpbeaverbuilder.com/beaver-builder/layouts/modules/contact-form`

---

## 4. Developer sentiment, by source quality

Critique item 21 asked for developer opinion from developer venues. Method note first, because brief 1's stated limitation is fixable.

### 4.1 What was reachable and what was not

**Reddit is reachable, contrary to brief 1 §11.1.5.** `reddit.com/r/<sub>/search.rss?q=...` and `reddit.com/r/<sub>/comments/<id>/.rss` return real Atom with titles, authors, dates, permalinks and selftext. Rate limiting is severe (roughly one successful request per 20 to 45 seconds, 429 in between), so a retry-with-sleep loop is required. Blocked: reddit.com HTML and `.json`, old.reddit.com, every redlib mirror (403, 503, or an Anubis interstitial), and `api.pullpush.io` (429 with the body "This website does not provide free scraping resources for agents").

**Also open and used:** the HN Algolia API, the StackExchange API (WebFetch is blocked on wordpress.stackexchange.com; the API is not), the Bricks Discourse `search.json` and `t/{id}.json` endpoints, and the GitHub API.

**Unreachable, stated plainly:**
1. `wpcampus.org` returned HTTP 502 on every URL, and web.archive.org returned 429. **The WPCampus Gravity Forms accessibility audit could not be confirmed to exist.** It is probably the most relevant prior art for what a Devform accessibility report should look like; re-check it before citing it.
2. Indie Hackers (JS shell, one character of extractable text) and x.com ("JavaScript is not available"). Nothing is inferred from either.
3. Breakdance's community and forum domains did not connect at all.
4. WebSearch budget was exhausted before these passes started, so every source was reached by directly constructed URL or API. No keyword discovery was possible; coverage is broad on named products and thin on anything not guessed.

**Source-quality correction: WP Tavern is dormant.** Its own REST API shows the newest post dated 2025-01-08. The critique's suggestion to mine its comment threads returns archive, not sentiment. Post Status and make.wordpress.org expose wp-json but appear to ignore the `search` parameter; treat those two as not-yet-searched rather than empty.

### 4.2 The finding that hurts most: nobody asks for forms in git

Absence finding, and it is load-bearing. Across every developer-populated source reached, **not one developer asks for form definitions in version control.**

1. HN Algolia comment search for "form builder git version control": 23 hits, none about forms. "code-first forms": 119 hits, no WordPress or web-form hits in the top results.
2. r/ProWordPress search for `version control forms git` returned two threads, both a user debugging a hand-written shortcode form.
3. In the two most on-topic Reddit threads found ("Form building plugin with good DX?" and "Interest in new form plugin"), the words git, repo, version control, file, YAML and JSON-in-theme appear zero times.

This does not make the idea bad. It makes it an unarticulated need, which is the same evidence pattern brief 1 flags as a red light for HXFE. Positioning that leads with the git workflow is selling a benefit nobody has asked for; positioning that leads with the two things developers do ask for (below) and mentions files as the mechanism is selling the same product with better copy.

### 4.3 What developers actually ask for

**Verbatim, r/ProWordPress, u/zumoro, 16 November 2024** (high source quality: a named developer, unprompted, in a professional subreddit):

> "I've been habitually using Gravity Forms for a while now and uh... I'm not loving it. Every time I try to extend it it's a literal coin toss; do I just need to add a few filters, or literally hot-swap enitre classes to rewrite a single method? ... I need something with sane extensibility for things like configuring custom control types, overridding styles, and somewhat granular filtering of output. If the DX is good enough I don't care if I need to roll my own Mailchimp integration, for instance."

The last clause is the strategic one: this developer trades integrations away for DX. A week later, the same user wrote a five-item API spec: "Ability to register custom/alternative field types, filter output before render, prepopulate certain fields dynamically, and most importantly filter the submitted data for both things like additional validation, sanitizing, and processing for uses like sending to other APIs."

**Markup churn is a live, dated, fleet-scale pain. r/ProWordPress, u/cakelly789, 11 August 2026**, eight days before brief 1 was compiled:

> "Gravity Forms newest update changes the submit button from an input field to a button. If you were targeting the input[type=\"submit\"] in your styling in any way, you will want to update to button[type=\"submit\"]. I am partway through updating this on a whole bunch of sites at the moment."

Replies show the fleet cost: a defensive-selector workaround (`.gform_wrapper [type="submit"]` matches both), and "I have a pretty advanced form that probably needs rebuilt outside of GF now due to how it's failing validation checks."

This is far stronger empirical support for the overridable-markup premise than the paywall complaints brief 1 leans on. It also creates an obligation: **if the default template is overridable, template changes are a breaking-change surface and need a versioning and deprecation policy.**

**Silent failure is a recognised category.** r/ProWordPress, 8 May 2026: "the one where the site is technically 'up' ... Contact form silently failing, checkout throwing an error after the button click ... The kind of thing your monitoring doesn't catch." And on fleet inventory, from the Gravity Forms markup thread: "On one install we were reporting 2 forms. The plugin's own table had 292, 154 of them active. The 290 we were missing weren't broken, they were on pages nobody thought to check, behind a popup trigger, or in a block template instead of a page."

### 4.4 Akismet is absent from professional discourse; Turnstile is the consensus

In a 40-plus-comment r/ProWordPress thread on form spam (15 April 2025, opened by someone running "many, many sites running Gravity Forms"), **Akismet is mentioned zero times.** Already tried and failing, per the OP: multiple captchas, multiple honeypots, CleanTalk, Wordfence bot detection with IP and country blocking, delayed-submission blocking.

What respondents actually named: **Cloudflare Turnstile** (five separate users, including the OP adopting it), Gravity Forms Zero Spam, WP Armour, OOPSpam, CleanTalk, Antispam Bee, cf7-antispam, Cloudflare WAF. One data point on cost: OOPSpam quoted at "$500/yr minimum" for the plan this user needed. One on performance: reCAPTCHA "adds like 200ms to a page load".

One respondent independently specified brief 1's own honeypot plan: "Set a validation field on the form after the form page has been open more than x seconds. A form filled out in less than, say, 5-10 sec is unlikely to be human. Set a validation field based on tab presses or mousemove events."

**Change to brief 1 §7.1:** ship Cloudflare Turnstile as a first-class adapter alongside Akismet, not as one of three parity adapters. Akismet stays a fine free default; it is not what this audience reaches for.

### 4.5 What people search for, from Stack Exchange

Top wordpress.stackexchange questions by view count, verified via the API: "How to handle form submission?" (54,959 views), "Custom Form with Ajax" (50,067), "How to use nonce with front end submission form?" (33,115), "Plugin Form Submission Best Practice" (12,334, score 18), "What is the real intention for admin-post.php?" (8,124), **"Nonces and Cache" (5,275)**, "Submitting form via admin-post.php and handling errors" (3,479).

Two consequences:

1. "Nonces and Cache" at 5,275 views corroborates brief 1 §5.3 as a real, searched-for problem rather than a theoretical one.
2. The accepted answers point at `admin-ajax.php` and `admin-post.php`, not the REST API. For critique item 5 (the unspecified non-REST fallback), **admin-post.php is the path the community already knows.** Name it as the documented degraded path and accept the `admin_init` cost, rather than inventing a fourth option.

The 2011 question that became "Plugin Form Submission Best Practice" states Devform Mode B verbatim: "I am developing an Events plugin that will book a ticket from the frontend... I am hoping to completely abstract the form processing from any template details."

### 4.6 Hacker News: the ask is an endpoint, not a builder

Two threads, both substantial. "Self-Hostable Form Back End, OSS Alternative to Formspree" (177 points, 36 comments, 6 January 2025) and "Self-hosting forms, the sane way" (127 points, 92 comments, 27 April 2024). Nobody asks for a builder. Notable comments:

1. A vendor (Kwes Forms) on spam: "I've seen some pretty insane rates of spam usage... everything from intelligent rate limiting to now a user scoring service that updates based on data about the user."
2. Measured honeypot data from an operator: "In late 2021, I added a trivial hidden-by-CSS ... honeypot ... For two and a half years, this filtered out all spam, except for one message in early 2023. But ... since 2024-02-10, I've received approximately 268 spam messages."
3. The structural advantage Devform cannot replicate, stated honestly: "the advantage of having a single source for submissions for thousands of forms, is that you can filter out these more easily as well." A self-hosted plugin has no cross-tenant signal.
4. Drupal Webform surfaced organically as the answer to "Is there really no good open source form backend?"
5. "This doesn't take long to build with LLM but what I find challenging to make is a beautiful and intuitive form builder."
6. In "Ask HN: Contact Form Solution?" the asker's requirement #3 is "Integrate with all major PPC/SEM and media platforms to track the lead origin", which is Devform's conversion-tracking differentiator asked for unprompted.

HN is not a distribution channel for a WordPress form plugin: every WordPress-form story in the Algolia index sits at 1 to 2 points with a single comment.

### 4.7 The nearest live competitor, and its unanswered question

r/ProWordPress, u/cabalos, 21 November 2024, "Interest in new form plugin". An agency built almost exactly Devform's positioning:

> "100% composed via blocks ... Full Site Editing, fully theme-able via FSE theme.json. Accessibility at its core, goal is to make it impossible to configure a form with accessibility issues. Interactivity API for syncing server & frontend state ... Data is stored as a custom post type. We also have the ability to auto delete stored data after a number of days."

On accessibility, in one line: "All the larger form plugin love to put buzzwords out about being accessible but then allow users to hide labels, hide required field indicators, use input placeholders, etc. When you give someone the building blocks, you have to put them into a position of success."

And the strategic question they could not answer, which is the same fork Devform faces: "We're not sure who the audience for the plugin would be. Is it Jetpack users who are looking for something simple with more styling ability or is it devs who need the full scope of a form system like Gravity? Our agency is kind of in the middle."

No public release appears to have followed. That is itself a data point. Note also that this developer treats **Jetpack**, not CF7, as the free baseline, independently confirming section 2.

### 4.8 Two design inputs from the same thread

1. **Interactivity API cost, measured by someone who shipped on it: 13.6 kB gzip, 37 kB total.** From the same developer: "It automatically injects state and handles bindings on the server and automatically rehydrates on the frontend... It allows for both standard POST submits and via AJAX." The dual-runtime API for the one-AST problem is `store("ns").state.registeredForms.contact.fields.firstName.value` in JS alongside `wp_interactivity_state("ns", [...])` in PHP. A dissenting voice preferred Alpine: "I do not like how wordpress is wrapping itself around (p)react yet again."
2. **A neat answer to the hidden-required-field problem**, proposed by a third user: "take the input's 'value' prop, change it to a 'data-disabled-value' prop (so it's still stored somewhere), and remove the 'value' prop (so it would not be included in the form data). That way you wouldn't need tons of custom in-memory logic, but can just rely on the native HTML form API."

### 4.9 Developer voice versus site-owner voice

Brief 1 §3 is almost entirely site-owner voice, and the two populations complain about different things.

- **Developers** (r/ProWordPress, Bricks forum, Elementor GitHub, HN, WPSE): filters, hooks, selectors, payloads, timeouts, metadata, action order, entry export. **Not one developer source complains about a paywalled phone-number field**, which is brief 1 §3.2's modal complaint.
- **Site owners** (r/Wordpress): "Watched someone install a 2MB plugin yesterday to add a contact form. The plugin came with an email marketing suite, a CRM, analytics dashboards, and approximately 600 features they'll never touch. The form itself? Three fields." Also nag screens, and "the Fluent Forms XSS vulnerability" as a reason to distrust plugins generally.

Both are valid. Brief 1 §3 is research for the audience Devform will **acquire** on wordpress.org; section 4 here is research for the audience it claims to **serve**. Do not conflate them, and do not build the roadmap off the wrong one.

Sources: `https://www.reddit.com/r/ProWordPress/comments/1gsc54b/form_building_plugin_with_good_dx/`, `/1gwglxo/interest_in_new_form_plugin/`, `/1vlvg7r/gravity_forms_changed_their_submit_markup/`, `/1k01k9a/im_at_my_wits_end_with_form_spam_what_pro_tricks/`, `/1t79lqg/whats_the_worst_silent_failure_youve_had_on_a/`, `https://news.ycombinator.com/item?id=42614316`, `?id=40179398`, `?id=33362642`, `https://wordpress.stackexchange.com/questions/60758/how-to-handle-form-submission`, `/questions/21237/plugin-form-submission-best-practice`, `/questions/123637/nonces-and-cache`, `https://github.com/elementor/elementor/issues/11086`, `/issues/20452`, `https://forum.bricksbuilder.io/t/custom-form-actions/6491`, `https://wptavern.com/wp-json/wp/v2/posts?per_page=3`

---

## 5. Prior art outside WordPress, and the form definition file format

### 5.1 Drupal Webform is the decade of prior art, and its two structural mistakes are free to avoid

1. **It addresses conditions by jQuery selector.** The stored shape is `'visible' => [':input[name="toggle_me"]' => ['checked' => TRUE]]`. Webform then has to regex the selector back into a field key: `WebformSubmissionConditionsValidator::validateCondition()` opens with `getSelectorInputName($selector)` then `getInputNameAsArray()`, with a special case that string-matches `:input[name="files[` to find upload elements. Core issue #1149078 ("States API doesn't work with multiple select fields") was opened 6 May 2011 and closed against 10.2.x with 201 comments, root cause being that a multi-select value is an array compared with `===`. **Address conditions by field handle. Never by selector.**
2. **Boolean operators are positional string sentinels.** `['visible' => [[selector => cond], 'or', [selector => cond]]]`. Unvalidatable, and drupal.org's own documented example is broken PHP (a flat array with the same selector key twice, which PHP silently collapses). Use an explicit tree.
3. **The exported config writes every default.** The shipped four-field contact form is **301 lines of YAML, of which 22 lines are the fields.** The rest is empty strings, `false` values, a fully expanded access block and two email handlers writing 24 keys each. Rule: write only non-default keys and let the loader fill defaults, or a one-word label change produces a diff no reviewer will read, which destroys the entire git-workflow pitch.
4. **The field tree is a YAML string inside the YAML** (`elements: |`), so Drupal's config schema system can validate nothing about any field. Keep fields as native structure.

What to copy from Webform:

5. **The handler shape.** A handler instance is exactly eight keys: `id` (plugin type), `label`, `notes`, `handler_id` (instance key), `status`, `conditions`, `weight`, `settings`. Conditions and ordering are first-class in the data model, which is what brief 1 §6.3 specifies. Note that both shipped handlers on the contact form carry `conditions: {}` present and unused, which validates modelling conditions in v1 without a UI.
6. **Two constants worth stealing** from `WebformHandlerInterface`: `cardinality()` (can this action type be added more than once per form?) and the `RESULTS_IGNORED` / `RESULTS_PROCESSED` pair (an action declares whether the form still needs to store an entry, which is how a webhook-only form justifies storing nothing).
7. **The submission table beats brief 1's proposed schema.** `webform_submission_data` is `webform_id varchar(32)`, `sid int`, `name varchar(128)`, `property varchar(128) default ''`, `delta int unsigned default 0`, `value text`, with `PRIMARY KEY (sid, name, property, delta)` and four indexes. `delta` is how a checkbox group storing three selections becomes three rows rather than a blob; `property` is how an address composite stores sub-values under one field name; the composite PK removes the surrogate id and makes upsert-on-edit natural. Brief 1's `devform_entry_fields` has a surrogate `id` and neither column.
8. **Drupal blocks a destructive rename.** `WebformEntityElementsValidator::validateSubmissions()` errors with "The %key element can not be removed because the %title webform has results", offering three remedies: delete all submissions, delete the element through the UI, or set `'#access' => false`. The config path is guarded and only the explicit UI path destroys data. That is the correct pairing.
9. **Drupal core warns with counts before a config import destroys content.** `FieldHooks` emits, verbatim: "This synchronization will delete data from the fields: %fields." Copy that string almost verbatim on Devform's sync screen.
10. **Drupal never deletes field data inline.** `field.purge.inc`: "the appropriate field data items, fields, and/or field storages are marked as deleted so that subsequent load or query operations will not return them. Later, a separate process cleans up, or 'purges', the marked-as-deleted data." Batch size defaults to 50.

### 5.2 What every other system decided

1. **Statamic is the only system where files are the runtime source of truth and the UI writes them back.** `BlueprintRepository::save()` is three lines: clear caches, `$blueprint->writeFile()`. No database mirror, therefore no sync step, no sync UI and no sync bug class. Form config splits across `resources/forms/{handle}.yaml` (title, email) and `resources/blueprints/forms/{handle}.yaml` (fields). Validation rides Laravel's stringly-typed syntax inline (`validate: required|max:200`), which is the property that lets rules survive serialisation.
2. **Craft forbids hand-editing the YAML it version-controls.** Verbatim: "Directly editing YAML files can cause inconsistencies and instability. Craft should have exclusive control over the `config/project` directory."
3. **Craft's own form plugin keeps forms out of that config.** Formie forms are Craft elements (database rows), not project config. Only stencils, statuses, templates and integration settings go in, and even that slice produced 17 project-config bug entries in Formie's changelog. **The most mature commercial form builder on a CMS that has a git-YAML config system chose not to put forms in it.** That is the strongest counter-evidence to Devform's premise and it deserves an explicit rebuttal in the positioning rather than silence.
4. **Drupal's git workflow only moves config between copies of the same site.** Verbatim: "This only works if you're moving configuration between two copies of the same site ... because the site UUIDs must match." And: "Don't try to change the active configuration on your site by changing files in a module's config/install directory. This will NOT work."
5. **Symfony proves the right architecture.** Its validator accepts constraints as PHP attributes, YAML, XML or a `loadValidatorMetadata()` method, because all four are front-ends over one metadata model. Its form types get inheritance without class inheritance via `getParent(): string`, which is how Devform gets "one Text field class, six HTML5 behaviours" and later the Scale primitive with five presets without a class explosion. Its data transformers formalise three representations of every value (model, norm, view) with a `TransformationFailedException` carrying both a log message and a user-facing message. Brief 1 has no equivalent layer and will grow one badly: the E.164 phone field (§4.2.4), the three-part date fieldset (§4.1.7) and the address composite (§4.2.2) are all transformer problems.
6. **Laravel's stringly-typed rules are the one validation DSL that survives serialisation.** `'required|max:255'` is a string, so it round-trips through JSON, YAML and a database column unchanged. Named conditional rules (`required_if:status,active`, `required_unless`, `exclude_if`) keep that property; closures do not. Wildcard addressing (`items.*.id`) is the pre-solved scheme for the v2 repeater.
7. **Filament is the right ergonomics and the wrong evaluation model.** `TextInput::make('company_name')->hidden(fn (Get $get): bool => ! $get('is_company'))`. A closure cannot round-trip, cannot be written back by a UI and cannot ship to the browser. Its `->live()` is a server round-trip a static WordPress front end cannot pay for.
8. **Contentful chose imperative migrations and ships `changeFieldId(currentId, newId)`.** That is the sourced answer to the handle-rename hole (critique #9). Its `transformEntries()` family exists precisely because a schema change without a content migration orphans data.
9. **Contentful also documents the only non-aesthetic argument for a builder DSL**, and it is error messages: "it is recommended to use the chained approach since validation errors will display context information whenever an error is detected, along with a line number. The object notation will lead the validation error to only show the line where the object is described." Mitigation without adopting a DSL: validate the array against a JSON Schema and report the failing JSON Pointer path.
10. **JSON Schema is not a form language.** No field order, no labels, no widget choice, which is why JSON Forms needs two coupled documents per form. And `format` is an annotation by default: under the required Format-Annotation vocabulary, format values "MUST be collected as an annotation" and validation "MUST be disabled by default". `"format": "email"` validates nothing. Reject as the field-definition format; keep it as a validator for Devform's own file shape.
11. **Steal JSON Forms' four effects verbatim**: `HIDE`, `SHOW`, `ENABLE`, `DISABLE`. Naming the effect in the data is what lets the renderer decide consistently in PHP and JS whether hiding means the `hidden` attribute or DOM removal.
12. **Piklist is the forgotten WordPress prior art nobody occupies.** `piklist::process_parts()` scans `{theme-or-plugin}/parts/{folder}/*.php` and reads file-header metadata with `get_file_data()`, exactly like WordPress reads a plugin header. No registration call: the docblock at the top of the file **is** the registration. It supports an `extend` header so a child theme can override one part by id, which is the child-theme problem ACF Local JSON never solved. Its `parts/` directory has `forms/`, `fields/`, `settings/`, `shortcodes/`, `workflows/`. Piklist was closed on wordpress.org in February 2022 with `Tested up to: 4.9`, so the pattern is unoccupied.

### 5.3 The format decision: PHP is canonical, JSON is an equal citizen, YAML is disqualified

Scorecard across the five criteria, from the evidence above.

| Criterion | PHP array | JSON | YAML | Builder DSL |
|---|---|---|---|---|
| Git diff quality | good | good | good | good on single edits, worse on reorder |
| IDE autocomplete | fair (documented shape) | good (`$schema` key) | poor | best |
| **i18n** | **decisive win** | workable at a cost | workable at a cost | n/a |
| **UI write-back** | **impossible** | **decisive win** | possible | impossible |
| Schema validation | after load | native | native | n/a |

Two facts decide it:

1. **i18n.** `wp i18n make-pot` extracts `__()` from PHP under the theme's own text domain, with zero registration API and zero Devform involvement. The extraction is static: "The strings for translation are extracted from the source without executing the PHP associated with it", which is also why "Do not use variable names or constants for the text domain portion". A form at `wp-content/themes/acme/forms/contact.php` calling `__( 'Your name', 'acme' )` lands in the theme's POT when the theme author runs make-pot on their own directory. No incumbent can offer that. **This closes critique item 15.**
2. **UI write-back.** A wp-admin screen must never write executable PHP to disk. The client-editable requirement forces a JSON path to exist regardless.

**JSON can be translated, at a known cost.** Core already ships the mechanism: `translate_settings_using_i18n_schema( $i18n_schema, $settings, $textdomain )` in `wp-includes/l10n.php`, since 5.9, driven by a shape-mirror schema file whose leaf values are the gettext contexts (`wp-includes/block-i18n.json` is the working example). Two catches: the function is marked `@access private`, and `wp i18n make-pot`'s `JsonSchemaExtractor` is generic but the CLI wiring hardcodes `block.json` and `theme.json` with no flag for a custom schema. So the JSON path costs a `devform-i18n.json`, a copied private core function, and a `wp devform make-pot` subcommand. That is a day, not a blocker, and PHP does not cost it.

**YAML is out on platform grounds.** PHP's YAML extension "is not bundled with PHP" and requires `pecl install yaml`. Core ships no YAML parser; its only file-decoding helper is `wp_json_file_decode()` (5.9, JSON only). YAML means bundling symfony/yaml or spyc on every install for a plugin whose pitch is minimal weight, and it fights WordPress's own standardisation on JSON for structured config.

**Kirby shows the fallback if the core i18n-schema route is rejected**: inline per-language maps (`label: {en: Street, de: Straße}`) plus a `*` sentinel key that defers to a translation key. Kirby also concedes the general point: "For more complex setups, a PHP blueprint could be a better alternative ... if you need to add elements conditionally, e.g. based on user role, config options etc." Devform's audience hits that case constantly.

### 5.4 The worked example

Both files load into the same `Devform\Form` object. Fields and actions are ordered maps keyed by handle: insertion order is display and run order, and a keyed map makes a duplicate handle impossible.

`wp-content/themes/acme/forms/contact.php`:

```php
<?php
return [
  'label' => __( 'Contact', 'acme' ),
  'store' => true,
  'fields' => [
    'name' => [
      'type'         => 'text',
      'label'        => __( 'Your name', 'acme' ),
      'required'     => true,
      'autocomplete' => 'name',
    ],
    'email' => [
      'type'         => 'email',
      'label'        => __( 'Email address', 'acme' ),
      'required'     => true,
      'autocomplete' => 'email',
      'errors'       => [ 'required' => __( 'Enter your email address', 'acme' ) ],
    ],
    'topic' => [
      'type'    => 'select',
      'label'   => __( 'What is this about?', 'acme' ),
      'options' => [
        'sales'   => __( 'Sales', 'acme' ),
        'support' => __( 'Support', 'acme' ),
      ],
    ],
    'order_id' => [
      'type'       => 'text',
      'label'      => __( 'Order number', 'acme' ),
      'previously' => [ 'order_number' ],
      'when'       => [ 'show' => [ 'field' => 'topic', 'is' => 'support' ] ],
    ],
    'message' => [
      'type'     => 'textarea',
      'label'    => __( 'Message', 'acme' ),
      'required' => true,
      'rules'    => 'max:2000',
    ],
    'consent' => [
      'type'     => 'consent',
      'label'    => __( 'I agree to the privacy policy', 'acme' ),
      'required' => true,
    ],
  ],
  'actions' => [
    'notify' => [
      'type'   => 'email',
      'config' => [
        'to'       => get_option( 'admin_email' ),
        'subject'  => __( 'New contact form message', 'acme' ),
        'reply_to' => '{{ email }}',
      ],
    ],
    'crm' => [
      'type'   => 'webhook',
      'when'   => [ 'field' => 'topic', 'is' => 'sales' ],
      'config' => [
        'url'    => 'https://crm.example.com/hooks/leads',
        'secret' => [ 'const' => 'ACME_CRM_WEBHOOK_SECRET' ],
      ],
    ],
    'thanks' => [
      'type'   => 'redirect',
      'config' => [ 'to' => '/thank-you/' ],
    ],
  ],
];
```

The JSON twin is the same document with literal strings, a `"textdomain": "acme"` key, and a `"$schema"` pointing at Devform's published schema so editors validate it.

Notes on the shape:

1. `previously` is the declared-rename primitive, borrowed from Contentful's `changeFieldId`. A diff cannot tell a rename from a delete-plus-add; only a declaration can. See section 7.
2. `when` is author-friendly sugar that compiles to the AST in section 8. The compiled form is what ships to the browser and what both evaluators consume.
3. `secret` is a reference, never a value. See section 6.
4. Write only non-defaults. The loader fills the rest. Drupal's 301-line contact form is what skipping that rule costs.

### 5.5 The field iterator: steal Statamic's vocabulary verbatim

Brief 1 §8.2.3 proposes six per-field variables (`handle`, `label`, `required`, `input`, `error`, `describedby`). Statamic exposes thirteen, and three of them are things brief 1 has no answer for:

- Per field: `display`, `instructions`, `field` (pre-rendered HTML), `type`, `handle`, `name` (the input name attribute), `value`, `default`, **`old`** (previous submission value on error), `error`, `validate`, `width`, `show_field`.
- Per form: `fields`, `errors`, `error`, `old`, `success`, and **`submission_created`**, which "differs from success in that it will actually return falsey when the honeypot is filled".

Add all three: `old` (repopulation after a validation failure, which brief 1 never specifies), `name` separate from `handle` (needed the moment array-valued fields exist), and the success/`submission_created` split so the honeypot does not show a fake success message to a bot.

Sources: `https://git.drupalcode.org/project/webform/-/raw/6.x/src/WebformEntityElementsValidator.php`, `/src/WebformSubmissionStorageSchema.php`, `/src/Plugin/WebformHandlerInterface.php`, `/config/install/webform.webform.contact.yml`, `https://api.drupal.org/api/drupal/core%21modules%21field%21field.purge.inc/group/field_purge/10`, `https://raw.githubusercontent.com/drupal/drupal/11.x/core/modules/field/src/Hook/FieldHooks.php`, `https://www.drupal.org/docs/administering-a-drupal-site/configuration-management/managing-your-sites-configuration`, `https://raw.githubusercontent.com/statamic/cms/5.x/src/Fields/BlueprintRepository.php`, `https://statamic.dev/tags/form-create`, `https://craftcms.com/docs/5.x/system/project-config.html`, `https://raw.githubusercontent.com/verbb/formie/craft-5/CHANGELOG.md`, `https://symfony.com/doc/current/validation.html`, `/form/data_transformers.html`, `https://laravel.com/docs/12.x/validation`, `https://filamentphp.com/docs/4.x/schemas/overview`, `https://raw.githubusercontent.com/contentful/contentful-migration/main/README.md`, `https://json-schema.org/draft/2020-12/json-schema-validation`, `https://jsonforms.io/docs/uischema/rules`, `https://www.php.net/manual/en/yaml.installation.php`, `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/l10n.php`, `https://developer.wordpress.org/cli/commands/i18n/make-pot/`, `https://getkirby.com/docs/guide/blueprints/extending-blueprints`

---

## 6. Source of truth: files versus database, and where secrets live

This closes critique item 4. Brief 1 left the fork open between §8.2 (forms in files, actions inside them) and §6.3 (actions in a `devform_actions` table). Here is the pick.

### 6.1 The evidence: every two-store sync model documents its own pain

1. **ACF merges the file over the database row at runtime.** `_acf_apply_get_local_internal_posts()`, hooked to `acf/load_field_groups` at priority 20, does `$i = array_search( $post['key'], $map, true ); if ( $i !== false ) { unset( $post['ID'] ); $posts[$i] = array_merge( $posts[$i], $post ); }`. The file wins key by key on the front end, shallow, and the database row stays stale until a human clicks Sync. That silent runtime precedence combined with a purely advisory Sync tab is the structural source of every ACF sync bug.
2. **ACF's out-of-sync test is an mtime-derived timestamp comparison**: `$modified > get_post_modified_time( 'U', true, $post['ID'] )`, where `modified` is written into the JSON at save time. Git breaks this by construction: a checkout rewrites mtimes, so an unchanged file can look newer, and checking out an older commit can make a real change look older.
3. **ACF's sync is a one-way file-to-database overwrite** with no merge, no three-way diff and no conflict state. `check_sync()` disables the JSON controller first, with the comment "Disable 'Local JSON' controller to prevent the .json file from being modified during import".
4. **Every admin save unconditionally rewrites the git-tracked file.** ACF only added a failure warning in 6.8.1 ("ACF saved your changes to the database, but could not update the Local JSON file"), and even that is only recorded when the file already existed, so a first write to an unwritable directory still fails silently.
5. **The open bugs are exactly what this architecture predicts.** Issue #405 (open since 2020-11-04): sync offers already-installed disabled groups and "they are being imported as a duplicate of the already-installed field groups... See how even the field group key is the same for the duplicates? ... Clicking 'sync' again would create the next batch of duplicates." Issue #160 (open since 2019-03-22): a group "stuck in the status of 'Sync available'... I don't know how to clear this out." Issue #599 (open since 2022-01-20, zero comments, four and a half years): "Local JSON 'Awaiting save'" forever with correct permissions. Issue #832 (closed as a feature request) names the canonical failure: "fields created by Developer A overwritten or deleted because Developer B forgets to sync the fields first."
6. **Diff churn is a real, fixed-in-code problem.** ACF 6.2.0 shipped a dedicated `acf/json/eof_newline` filter because a trailing newline byte was churning everyone's diffs. 6.3.1 fixed "Fields moved between field groups now correctly updates both JSON files". 6.2.9 fixed Windows warnings.
7. **Craft says do not hand-edit.** Drupal requires matching site UUIDs and keeps a **third** store (a snapshot of the last import) so local divergence is detectable, warning "The following items in your active configuration have changes since the last import that may be lost on the next import." That snapshot is exactly what ACF lacks.
8. **Statamic has no fork because it has no database config store.** `BlueprintRepository::save()` is three lines.

### 6.2 The pick

**No form is ever the definition in two stores at once.**

1. **A file-defined form is authoritative in the file, always, at render time and at validation time.** There is no database mirror of the definition, no timestamp comparison, no merge, no Sync tab.
2. **An admin-defined form is authoritative in the database**, carries `source = 'db'`, and never participates in file loading.
3. **The two never overlap.** A handle is claimed by exactly one source. A collision is a hard error at load time with both paths named, not a silent precedence rule.
4. **Client-editable keys are an overlay, not a fork.** The file declares its own allow-list (`'client_editable' => ['actions.notify.config.to', 'actions.notify.config.subject', 'actions.thanks.config.to']`). Client edits go to `devform_form_overrides`, keyed `(form_handle, json_path)`. A git pull never wipes them, the overlay is always representable as a diff, and the admin screen shows exactly which values are overridden and what the file says underneath. Everything not on the allow-list is read-only in wp-admin, with a copy-as-JSON button and a one-line explanation of why.
5. **Import and export are explicit commands, never side effects.** `wp devform import <handle>` and `wp devform export <handle>`, plus the equivalent buttons. Nothing writes a git-tracked file on an admin save. This is the single rule that prevents ACF's clobbering class.
6. **Actions live in the definition, not in a table.** Brief 1 §6.3's `devform_actions` table is demoted to a runtime projection: a resolved, cached view of the definition plus overlay, rebuilt on load, never the record. `devform_action_runs` stays a real table, because a run is an event, not config. This resolves the §8.2-versus-§6.3 contradiction in favour of §8.2.
7. **Conflict resolution is git's job for the file half and last-write-wins for the overlay half.** Two developers editing `forms/contact.php` get a merge conflict in their editor, which is the correct tool. Two admins editing the same overridden key get last-write-wins with an audit row, which is what wp-admin already does for every other option.
8. **Detect divergence by content hash, never by mtime.** Hash the canonical form of the definition with volatile keys stripped. Used for the version table (section 7) and for the `wp devform doctor` output, not for a sync decision, because there is no sync decision.

### 6.3 Where secrets live

Brief 1 never says. The answer is now canonical rather than a convention, because **WordPress 7.0.0 shipped a Connectors API** in `wp-includes/connectors.php` (present in the released 7.0.4 tag; WP 7.0 is 66.3% of installs).

1. `_wp_connectors_get_api_key_source( $setting_name, $env_var_name, $constant_name )` returns one of `'env'`, `'constant'`, `'database'`, `'none'`. Its docblock: "Checks in order: environment variable, PHP constant, database."
2. Core registers Akismet through it: `'setting_name' => 'wordpress_api_key', 'constant_name' => 'WPCOM_API_KEY'`, type `spam_filtering`. Built-in connectors "cannot be unhooked".
3. Masking is `str_repeat("\u{2022}", min(strlen($key) - 4, 16)) . substr($key, -4)`, applied in `_wp_connectors_rest_settings_dispatch()` on `rest_post_dispatch`. **The database tier is plaintext**: `register_setting()` with `sanitize_text_field` and `show_in_rest => true`. Masking is display-only.
4. `type` is validated only as a non-empty string, so a plugin can register `form_destination` connectors.

**Requirements:**

1. **A committed definition never contains a secret value, only a reference.** Three prefixes, resolved at send time and never at load time: `@env:NAME` (getenv), `@const:NAME` (defined constant), `@secret:handle` (Devform's own store, which is what the admin UI edits). A bare name resolves env, then constant, then database, matching core exactly.
2. **Record and display which source won**, the way core's function returns it, and mask on display using core's own shape.
3. **On WP 7.0+, do not build the store at all.** Register each Devform destination as a connector on `wp_connectors_init` and let core own resolution, storage, masking, the REST surface and the settings screen. Ship a small internal fallback implementing the same three-tier order for pre-7.0 sites and delete it when the floor moves. This is the single most credible "feels like a native part of the WordPress family" move available.
4. **Support env, never require it.** Core reads env vars in exactly four places (`wp_get_environment_type()`, four update-URL constants, speculative loading, connectors) and every one has a constant or default fallback. There is no mainstream `.env` loader in WordPress outside Bedrock, which is a project scaffold, not a plugin: the wordpress.org plugin named `dotenv` has 20 active installs.

**Encryption at rest: yes, and do not key it off the salts.**

5. Use `sodium_crypto_secretbox`. Core has bundled sodium_compat since **WordPress 5.2** (`wp-includes/sodium_compat/autoload.php` exists at the 5.2 tag and 404s at 5.1), so XSalsa20-Poly1305 is available everywhere with no dependency and no bundled library, which also satisfies wordpress.org guideline 13.
6. Key from `DEVFORM_ENCRYPTION_KEY` if defined, otherwise a 32-byte random key in a non-autoloaded option. This is what WP Mail SMTP (`WPMS_CRYPTO_KEY`) and Fluent Forms (`FLUENTFORM_ENCRYPTION_KEY`) both do.
7. **Do not follow Google Site Kit into `LOGGED_IN_KEY`.** Its `Data_Encryption` uses unauthenticated `aes-256-ctr` keyed from `GOOGLESITEKIT_ENCRYPTION_KEY`, then `LOGGED_IN_KEY`, then the hardcoded literal `'das-ist-kein-geheimer-schluessel'`, and returns plaintext silently if OpenSSL is missing. Rotating salts is routine incident response (it already logs everyone out and voids every nonce, because `wp_generate_auth_cookie()` and `wp_create_nonce()` both go through `wp_hash()` to `wp_salt()`), and anything encrypted under those keys becomes permanently unrecoverable at that moment.
8. **`wp_salt('devform')` is not a wp-config secret.** For an unknown scheme, `wp_salt()` takes `SECRET_KEY` only if defined, else `get_site_option('secret_key')`, auto-generated and stored in the database. `SECRET_KEY` is not in `wp-config-sample.php` and is not emitted by `api.wordpress.org/secret-key/1.1/salt/`. **This is also a correction to brief 1 §5.3's token design**, which assumed a wp-config-level secret.
9. **Fail loud on decrypt failure.** Follow Fluent Forms' `v2:` path ("A v2 blob is unrecoverable without openssl, so fail loud instead of handing the ciphertext back as if it were the key"), not WP Mail SMTP's `decrypt()`, which "returns encrypted message on any failure" and therefore ships ciphertext to the SMTP server as if it were the password.
10. **Be honest in the readme about what this buys**: protection against log leakage, partial dumps, backup snippets and casual admin browsing, not against a full database dump, because the key lives in the same database. Nobody in WordPress solves that, core included. WordPress's own Two Factor plugin stores TOTP secrets in plaintext user meta (`const SECRET_META_KEY = '_two_factor_totp_key';`, no encryption anywhere in the file), which is the strongest evidence there is no ecosystem-wide expectation here.

**Export must redact.** Fluent Forms' form export includes every `fluentform_form_meta` row except `_total_views`, and integration feeds (notifications, Slack, Mailchimp) are stored as form meta, so **a Fluent Forms export shared for support carries the site's Slack webhook URL today.** Devform's exporter must be a deny-by-default projection over a declared field list, not an `unset()` blacklist, so a new action type cannot leak a credential by omission. That is the same declared-secret-list mechanism brief 1 already needs for redacting `devform_action_runs.request`. Build it once, use it twice.

**One guideline note.** Guideline 8 is narrower than usually claimed. Its actual text prohibits "Calling third party CDNs for reasons other than font inclusions; all non-service related JavaScript and CSS must be included locally", which permits vendor service endpoints. And there is **no wordpress.org guideline about storing credentials, secrets or encryption anywhere in the 18 guidelines**; the only applicable line is "All code in the directory should be made as secure as possible." The real guideline-7 surface is the conversion-tracking feature: every destination ships disabled, is enabled per form by the site owner with their own credentials, and gets a readme entry naming the service and linking its terms.

Sources: `https://raw.githubusercontent.com/WordPress/WordPress/7.0.4/wp-includes/connectors.php`, `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/pluggable.php`, `https://raw.githubusercontent.com/WordPress/WordPress/5.2/wp-includes/sodium_compat/autoload.php`, `https://plugins.svn.wordpress.org/wp-mail-smtp/trunk/src/Helpers/Crypto.php`, `https://raw.githubusercontent.com/google/site-kit-wp/develop/includes/Core/Storage/Data_Encryption.php`, `https://raw.githubusercontent.com/WordPress/two-factor/master/providers/class-two-factor-totp.php`, `https://github.com/AdvancedCustomFields/acf/issues/405`, `/issues/160`, `/issues/599`, `/issues/832`, `https://www.advancedcustomfields.com/resources/local-json/`, `https://raw.githubusercontent.com/drupal/drupal/11.x/core/modules/config/src/Form/ConfigSync.php`, `https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/`

### 6.4 One thing to ship in v1 that ACF took a decade to add

ACF 6.8.0, released 30 March 2026: "ACF now includes WP-CLI support with new `wp acf json` commands for importing, exporting, syncing, and checking the status of ACF JSON files." After roughly a decade of Local JSON. `wp devform status` and `wp devform import` are cheap, they make the whole failure class CI-detectable rather than discovered on a Friday deploy, and they are the most obvious feature for this audience.

Also worth noting for positioning: ACF 6.8.1 (13 May 2026) lets PRO blocks define fields inline in `block.json` "with field keys auto-generated and scoped to the block". That is ACF conceding that hand-managed global keys are the wrong model, which is where Devform's string handles already are. And ACF 6.8.0 added Abilities API integration behind an `enable_acf_ai` flag, opt-in, which is the right default.

---

## 7. Form definition versioning versus entries

Closes critique item 9. Brief 1 has no answer; here is the pick and the schema.

### 7.1 What the incumbents actually do

1. **Gravity Forms' `gf_form_revisions` is a consent feature wearing a versioning feature's name.** The table is real (`id`, `form_id`, `display_meta`, `date_created`) and `display_meta` is a full form snapshot, but `maybe_create_form_revision()` returns immediately unless `GFCommon::has_consent_field()`, and then only inserts if a consent field's **description** changed. Renaming a field, deleting a field, changing choices or labels: no revision. The docs say it outright: "At the time of writing, this documentation is only used for Consent fields."
2. **GF's consent display is a pointer, not a copy**, and it degrades silently: `get_field_description_from_revision()` runs one uncached `SELECT display_meta FROM {revisions} WHERE form_id=%d AND id=%d` per consent field per entry, json_decodes the whole form, and falls back to the **current** description if the row is gone.
3. **GF's re-consent on drift is the one piece worth copying wholesale.** It emits the revision id and the checkbox label as hidden inputs and, on submit, discards a checked state if either changed since page load, forcing reconfirmation.
4. **Revisions are never pruned** and are deleted only with the form. No cap, no GC, no `WP_POST_REVISIONS` analogue.
5. **GF field deletion is destructive twice over.** `delete_field_values()` deletes the field's entry meta and then, under the comment `// Delete leads with no details`, runs `DELETE FROM {entry} WHERE form_id=%d AND id NOT IN (SELECT DISTINCT(entry_id) FROM {entry_meta} ...)`. Delete the only field of a one-field form and the **entries** disappear. The docs are blunt: "There is no undo; this data cannot be recovered once it has been removed."
6. **Formidable hard-deletes entry values on field delete** (`FrmField::destroy()` runs `DELETE FROM frm_item_metas WHERE field_id=%d`), but is structurally immune to handle renames because `frm_item_metas` joins on a numeric `field_id` with `field_key` as a separate unique column. That is the exact inverse of Devform's premise, where the human-authored handle **is** the join key.
7. **Fluent Forms keeps everything and shows nothing.** Values for removed fields stay in `fluentform_entry_details` and inside the `response` JSON forever, but `FormDataParser::parseData()` iterates the **current** form's fields, so the data is in the database, out of the viewer, out of the labels map and out of exports. Worst of both.
8. **ACF is the named hazard for handle-as-join-key.** `acf_update_value()` writes two meta rows per value, `{name} => value` and `_{name} => field_abc123`. Storage is keyed on the **name**; the immutable field key is only a back-reference stored under a name-derived key. A rename orphans both rows at once.

### 7.2 What everyone outside WordPress does: snapshot into the immutable record

1. **WooCommerce** copies the product name into `woocommerce_order_items.order_item_name` and the prices into item meta (`_product_id`, `_qty`, `_line_total`, ...), keeping only a soft reference (`get_product(): WC_Product|bool`). The stated rationale for the new `coupon_info` meta is that the data is "needed to apply the coupon again when doing recalculations on the order when the coupon no longer exists". HPOS did not touch this: order items already had their own tables and were out of scope.
2. **WordPress core already does the same for comments.** `wp_handle_comment_submission()` copies `$user->display_name`, `$user->user_email` and `$user->user_url` onto the comment row alongside a `user_id` pointer that may later point at a renamed or deleted user. Cite this on any review thread that calls the denormalisation un-WordPressy.
3. **Jetpack** stores `key`, `label`, `value`, `type`, `meta` and `form_field_id` per field inside each response, so the label as presented travels with the value. No revision table, no hash: the cheapest correct model.
4. **Stripe pins the payload shape per webhook endpoint** at creation time and freezes the event forever: `api_version` is "The Stripe API version used to render `data` when the event was created. The contents of `data` never change." Its documented upgrade path is to add a second endpoint at the new version and cut over, never to mutate an existing pin.
5. **Shopify** pins per webhook subscription and echoes `X-Shopify-Api-Version`, with a documented fall-forward when your version expires. **GitHub versions webhook payloads not at all**, additive-only, with the advice to check type and action before processing. That works for a first-party API with a public changelog; it does not work when the payload keys are written by whoever edited the theme.
6. **In WordPress, no webhook payload carries a version.** Forminator's payload is the submitted data plus exactly two keys, `form-title` (read from the **current** form settings at send time, so a retry after a rename carries the new title with old data) and `entry-time`.

### 7.3 The pick: a content-addressed version table plus a per-field label snapshot

Neither a full snapshot per entry nor nothing. Measured cost of the naive option: Drupal Webform's shipped four-field contact form is 7,361 bytes of config, of which the `elements:` block alone is 614 bytes. A full-config snapshot per entry is roughly 7 KB and a fields-only snapshot roughly 0.6 KB, so 70 MB versus 6 MB at 10,000 entries for data that is identical across almost every row. A `CHAR(64)` hex hash is 64 bytes; a `BIGINT UNSIGNED` version id is 8. Also, with `ROW_FORMAT=DYNAMIC`, InnoDB stores long TEXT off-page with a 20-byte pointer, so every entry-list query that selects a snapshot column pays an extra overflow page read per row.

**Schema implication.** One new table and five new columns:

```sql
CREATE TABLE {prefix}devform_form_versions (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_handle     VARCHAR(64)     NOT NULL,
  definition_hash CHAR(64)        NOT NULL,
  definition      LONGTEXT        NOT NULL,
  source          VARCHAR(16)     NOT NULL,
  first_seen      DATETIME        NOT NULL,
  last_seen       DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY form_hash (form_handle, definition_hash),
  KEY form_last_seen (form_handle, last_seen)
);
```

- `devform_entries`: add `form_version_id BIGINT UNSIGNED NULL` plus `KEY (form_version_id)`, and `hidden_fields LONGTEXT NULL` (see section 8).
- `devform_entry_fields`: add `label VARCHAR(255) NOT NULL DEFAULT ''` (the label as presented), `field_type VARCHAR(32) NOT NULL DEFAULT ''`, `property VARCHAR(64) NOT NULL DEFAULT ''` and `delta SMALLINT UNSIGNED NOT NULL DEFAULT 0`. Replace the surrogate `id` with `PRIMARY KEY (entry_id, field_handle, property, delta)`, which is Drupal Webform's natural key and makes duplicate values impossible by construction.

**Thirteen rules that fall out:**

1. **Hash the fields array, not the file.** Canonicalise (sorted keys, only semantic properties: handle, type, label, required, choices, consent text, order), sha256, then `INSERT ... ON DUPLICATE KEY UPDATE last_seen = UTC_TIMESTAMP()`. One row per distinct definition over the form's life, typically single digits. Actions, notification bodies and admin settings must **not** be in the hash, or every SMTP tweak mints a version. Gravity Forms went too narrow (one property of one field type); hashing the whole fields array is one line of code and is general.
2. **Copy each field's label and type onto the entry_fields row at write time.** This is Jetpack's model and WooCommerce's model.
3. **Never trust the submitted version.** Emit the hash as a hidden input (GF emits its revision id), but treat it strictly as a staleness claim. Validation always uses the server-side definition resolved from the handle. The submitted hash decides exactly two things: whether to reset consent, and whether to flag the entry as submitted against an older definition.
4. **Renames are declared, never inferred.** A diff cannot distinguish a rename from delete-plus-add, which is why Drupal blocks the drop and ACF silently orphans. The `previously` key from section 5.4 drives an idempotent migration (`UPDATE devform_entry_fields SET field_handle = new WHERE form_handle = h AND field_handle = old`), recorded in a `devform_form_migrations` table keyed `(form_handle, from_handle, to_handle)` so it never runs twice, logging the affected row count. Without the key, treat the old handle as removed and the new one as added, and say so on the sync screen.
5. **Deletions are tombstones, never DELETEs.** Removing a field from a file marks state, it does not touch data. Orphaned handles are computable as (DISTINCT field_handle in entry_fields) minus (handles in the current definition). Show them in the entry viewer under a "no longer in this form" group, keep them in exports, and offer purge as a separate, counted, capability-gated action. Drupal's rule: mark deleted, hide from normal queries, purge later in batches.
6. **Warn at sync time, with counts.** Copy Drupal core's string almost verbatim on the CLI and the admin screen: "This synchronization will remove N stored values from the fields: contact.phone, contact.company." Refuse in `--strict`/CI mode, warn interactively. This one screen is what turns "definitions in git" from a liability into a selling point.
7. **Export columns are the union** of the current definition's handles and every handle present in the selected entry set, ordered by current definition order then by version `first_seen`. Header text comes from the newest `entry_fields.label` for that handle, falling back to the handle. Deriving columns from the current definition alone is exactly how a column silently disappears with no record it existed.
8. **Search is unaffected**, because the handle is stable. Build the admin field filter from the union set, or removed fields become unsearchable the moment they are removed.
9. **Replay re-sends the stored payload byte for byte**, per Stripe's rule that the contents of `data` never change. Destination and credentials come from the current config; the body comes from the entry and its pinned `form_version_id`. Never rebuild the body from today's definition, or a replay after a rename silently changes the keys the receiver sees.
10. **The admin viewer resolves labels** in order: `entry_fields.label`, then the joined version row, then the current definition, then the raw handle. Load the version row once per request; GF does an uncached `get_var` per consent field per entry.
11. **Consent gets three things, not one.** The checkbox label verbatim on the entry (short, hot, needed in list views), the long consent text in the version row (large, cold, deduplicated), and the version id on the entry. Store the text hash too for tamper evidence, but **never only the hash**: EDPB Guidelines 05/2020 para 108 requires "a copy of the information that was presented to the data subject at that time" and says "It would not be sufficient to merely refer to a correct configuration of the respective website." Brief 1 §4.1.6's content-hash plan is insufficient on its own. Para 110 adds that if processing "change[s] or evolve[s] considerably then the original consent is no longer valid", which is what GF's re-consent state validation implements. Exempt consent-referenced version rows from GC until those entries are purged, then purge them together.
12. **The webhook payload carries two versions**, because they change for different reasons. `payload_version` is Devform's envelope schema, pinned per action at creation time (Stripe's and Shopify's model), sent as `X-Devform-Payload-Version` and repeated in the body, never mutated on an existing action; migrate by adding a second action. `form_version` is the definition hash the entry was captured against, alongside `form_handle` and entry id. Inline the fields as `{handle, label, type, value}` the way Jetpack serialises, so no receiver has to fetch the definition to render a notification.
13. **Mode B makes the drift three-way.** The decoupled front end is a third copy of the definition. Ship `GET /devform/v1/forms/{handle}` returning the definition plus its hash, echo the hash in every 422 body, and accept a client-supplied hash only to answer "your form is stale, refetch" with a distinct error code. Reject outright only when the hash is unknown to the site or a consent text changed; otherwise accept and record.

Sources: `https://docs.gravityforms.com/database-storage-structure-reference/`, `/consent/`, `/how-to-delete-a-field-in-a-form-without-losing-existing-data/`, `https://raw.githubusercontent.com/pronamic/gravityforms/3.0.2/forms_model.php`, `/includes/fields/class-gf-field-consent.php`, `https://git.drupalcode.org/project/webform/-/raw/6.x/src/WebformSubmissionStorageSchema.php`, `https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/class-wc-order-item-product.php`, `https://developer.woocommerce.com/2024/02/08/changes-in-order-coupons-line-item-storage/`, `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/comment.php`, `https://raw.githubusercontent.com/Automattic/jetpack/trunk/projects/packages/forms/src/contact-form/class-feedback-field.php`, `https://docs.stripe.com/api/events/object`, `https://shopify.dev/docs/api/usage/versioning`, `https://www.edpb.europa.eu/system/files/documents/files/file1/edpb_guidelines_202005_consent_en.pdf`, `https://dev.mysql.com/doc/refman/8.4/en/innodb-row-format.html`

---

## 8. Conditional logic

Closes critique item 10. Brief 1 gives this one paragraph. It is the most complex subsystem in the plugin.

### 8.1 What the market actually ships

1. **Nobody in WordPress ships a nestable condition AST.** Gravity Forms and WPForms store a flat rule list with one all/any flag. Fluent Forms bolts on exactly one extra level (`condition_groups` OR'd, rules inside hard-coded to AND). Only WS Form stores a real IF/THEN/ELSE tree, with the connective attached per condition as an infix `logic_previous` and a per-condition `case_sensitive` flag.
2. **GF's operator set is seven**: `is`, `isnot`, `<`, `>`, `contains`, `starts_with`, `ends_with`. No `>=`, no `<=`, no empty/not-empty, no `in`, no case-sensitivity flag. WPForms has ten, documented as case-insensitive.
3. **GF documents that it runs two engines and tells you to patch both.** Verbatim: "The `gform_is_value_match` filter has both a JavaScript version and a PHP version. Both versions may need to be used. The JavaScript version only overrides the result on the front-end. The PHP version overrides the result when the conditional logic is evaluated during submission." Sources named in the docs: `js/conditional_logic.js` and `RGFormsModel::is_value_match()`.
4. **Fluent Forms' two engines demonstrably disagree, in the same release.** PHP `ConditionAssesor::assess()` handles `length_equal`, `length_less_than`, `length_greater_than`, which the JS lacks (it falls off its ternary chain and returns undefined). JS handles `list_match` and `list_not_match`, which the PHP switch falls through on (returns false). PHP's `contains` is `Str::contains()`, which lowercases both sides; its `startsWith`/`endsWith` use `===`, so its three string operators are not self-consistent, and the JS `contains` is `indexOf`, which is case-sensitive. PHP returns false for a field absent from the payload; JS passes undefined through and can return true. JS resolves transitive visibility recursively with a `_visited` cycle guard; PHP does not. PHP's empty-group case returns true; JS's returns false.
5. **Hidden-field server semantics differ per vendor, and one is a bypass.** GF recomputes server-side from entry values. Fluent Forms recomputes and `array_intersect_key`s hidden fields out of the payload entirely, so they are neither validated nor stored (its older `FormHandler` path does not, and validates hidden required fields). **Formidable renders `<input type="hidden" name="frm_hide_fields_{form_id}">` carrying the browser's list of hidden fields and reads it back on submit.** That is a client-controlled input to server-side validation. Do not copy it.
6. **Nobody vendors a shared interpreter.** Grepping Fluent Forms, WPForms Lite, Formidable and WS Form LITE for JsonLogic returns nothing.
7. **Drupal solved the serialisation half a decade ago and explicitly gave up on the semantics half.** Core's `FormHelper::processStates()` docblock, verbatim: "Since states are driven by JavaScript only, it is important to understand that all states are applied on presentation only, none of the states force any server-side logic, and that they will not be applied for site visitors without JavaScript support." The transport is one line: `$elements[$key]['data-drupal-states'] = Json::encode($elements['#states']);`. Webform then wrote a complete parallel PHP evaluator against the same structure. Two engines, one syntax, and it works.
8. **Jetpack solved the flash-of-hidden-fields problem and the forged-POST problem.** Its PHP evaluator is `Conditional_Logic::evaluate()` / `resolve_visibility()`; its client is the Interactivity API; and a server-side pre-render pass adds `jetpack-field--conditionally-hidden` via `WP_HTML_Tag_Processor` so fields do not flash before hydration. Its storage rule carries this comment verbatim: "Integrations must not see a field the visitor was never shown. MailPoet in particular reads this payload directly for explicit consent and the subscriber's email, so a forged POST naming a hidden consent field could otherwise subscribe someone off a question that was never on screen."

### 8.2 The AST

One nestable JSON tree, versioned, used unchanged for field visibility, step visibility, submit-button state, field requiredness and action `run_if`. Exactly four node kinds, so both interpreters stay under 200 lines.

```json
{"v":1,"root":{"all":[
  {"src":{"field":"country"},"op":"eq","value":"NL"},
  {"any":[
    {"src":{"field":"budget"},"op":"gte","value":5000,"as":"number"},
    {"src":{"ctx":"utm_source"},"op":"eq","value":"google","ci":true}
  ]},
  {"not":{"src":{"field":"consent"},"op":"is_empty"}}
]}}
```

1. Nodes: `{"all":[...]}`, `{"any":[...]}`, `{"not":node}`, and the leaf `{src, op, value?, as?, ci?}`. Nesting beats every incumbent.
2. `src` kinds: `{"field":"handle"}`, `{"ctx":"utm_source"}`, `{"entry":"user_id"}`, `{"action":"crm.output.id"}`.
3. `as` is `string|number|bool|date`, default `string`. `ci` defaults false, per rule. Only WS Form has a per-condition case flag today, and it is the fix for Fluent shipping a case-insensitive `contains` next to a case-sensitive `startsWith`.
4. Address by **field handle**, never by DOM selector. That is the Drupal Webform mistake in section 5.1.
5. Effects are named in the data, borrowed from JSON Forms: `show`, `hide`, `enable`, `disable`. The renderer, not the author, decides what hiding means, identically in PHP and JS.

### 8.3 The operator set

Seventeen operators, each with semantics defined in the spec document and never delegated to the host language: `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `contains`, `not_contains`, `starts_with`, `ends_with`, `in`, `not_in`, `is_empty`, `is_not_empty`, `is_set`, `is_not_set`, plus the three `length_*` if you want them.

1. **`eq`/`neq` compare after explicit coercion per `as`, never with PHP `==` or JS `==`.** Measured on PHP 8.5.3 versus Node 25.6.1, six of eight sample comparisons diverge: `"1"=="01"` true/false, `"10"=="1e1"` true/false, `0==""` false/true, `null==0` true/false, `null==false` true/false, `"0.0"=="0"` true/false. Only `"abc"==0` and `" 1"==1` agree. **This is why JsonLogic cannot be adopted as-is**: its docs say "`==` Tests equality, with type coercion" and json-logic-php implements it as literally `return $a == $b`.
2. `gt`/`gte`/`lt`/`lte` are numeric only and return false if either side is not a finite number.
3. `contains` is substring for strings and membership for arrays, on both sides.
4. `is_set` means "key present in the payload" and is deliberately distinct from `is_empty`. That is precisely where Fluent's two engines disagree.
5. **Do not ship a regex operator in v1.** PCRE and ECMAScript regex are not the same language, and Fluent's PHP wraps the pattern in slashes and swallows compile errors with `@preg_match` returning false. Add it later behind a documented ASCII subset if customers ask.

### 8.4 Hidden-field semantics: the server is the only authority

1. **Recompute the entire visibility set server-side from the raw POST body.** Never accept a client-supplied hidden-field list.
2. **Resolve the dependency graph topologically and transitively**, so a field whose controller is itself hidden is hidden. Fluent's JS does this recursively with a cycle guard and its PHP does not; specify it once, implement it once. Reject cycles at form-load time with a clear admin error, not at submit time.
3. A rule whose source field is currently hidden reads as that field's **empty value**, not as false.
4. **Hidden means not validated, not stored, not exported, not available to merge tags, and not visible to the action scope.** Drop the keys before validation runs. That is also the answer to "does a required field inside a hidden container blow up server-side": it never reaches validation.
5. **Write the resolved visibility set onto the entry** as `hidden_fields` JSON, so exports and replays are reproducible against a definition that has since changed.
6. **Mode B needs zero extra code, which is the payoff.** Because the server recomputes from the payload, a decoupled front end that posts JSON gets identical conditional semantics with no browser involved. Say that in the README; it is a genuine differentiator.
7. **Clear-on-hide is the default**, because it is the only semantics consistent with "hidden means not submitted", but expose it as a per-field boolean and show it in the UI. GF applies it invisibly (opt-out only, via a JS filter added in 2.0.7.1); WS Form makes it an authored ELSE action (`visibility:off` plus `reset`), which is better UX.

### 8.5 The DOM strategy: `hidden` **and** `disabled`, not one or the other

This is the part every incumbent gets wrong, and the spec settles it.

1. **`hidden` does not bar an element from constraint validation.** HTML spec 4.10.21.1 lists the barring conditions: disabled, readonly on input, `input type=hidden`, `input type=reset`, `input type=button`, and a datalist ancestor. The `hidden` content attribute is not among them.
2. **A required invalid control that is not rendered aborts submission and shows the user nothing.** Spec 4.10.21.2: "If one of the controls is not being rendered (e.g. it has the hidden attribute set), then user agents may report a script error. Return a negative result." Blink implements exactly that (`An invalid form control with name='%name' is not focusable.` at error level, then returns false); Gecko has `InvalidFormControlUnfocusable` in `dom.properties`. Dead submit button, no message.
3. **Removing only `required` is insufficient.** It clears `valueMissing` and leaves `typeMismatch`, `patternMismatch`, `tooLong`, `tooShort`, `rangeUnderflow`, `rangeOverflow`, `stepMismatch`, `badInput` and `customError`. A half-typed email that then gets hidden still blocks submission.
4. **`disabled` is the only attribute that both bars constraint validation and removes the field from the submitted entry list** (spec 4.10.22.4: "If any of the following are true: field has a datalist element ancestor; field is disabled; ... then continue"). A `disabled` fieldset propagates to every descendant control except those inside the first `legend`, so it is one attribute.
5. **`hidden` handles presentation and the accessibility tree.** Spec 6.1: `hidden` "indicates that the element is not yet, or is no longer, directly relevant to the page's current state", and "if something is marked hidden, it is hidden from all presentations, including, for instance, screen readers." Its display comes from the UA stylesheet at normal weight (`[hidden]:not([hidden=until-found i]):not(embed) { display: none; }`, **no `!important`**, unlike `input[type=hidden i]`), so it survives deleting Devform's stylesheet but loses to any theme rule setting `display` on the same element.
6. **Therefore visibility cannot be the correctness boundary; `disabled` must be.** With both applied, a theme rule that un-hides a conditional field degrades to a cosmetic bug rather than a data bug. Document that as the reason the pair exists, rather than shipping a defensive `!important` that contradicts the no-CSS pitch.
7. **Do not use `inert`.** Spec 6.3: "In most cases, authors should not specify the inert attribute on individual form controls. In these instances, the disabled attribute is probably more appropriate."
8. **Do not remove nodes from the DOM.** You lose typed values on re-show, you break `aria-describedby` targets, and you have to re-run enhancement.
9. **If a value must persist while hidden** (admin prefill, tracking), mirror it into `input type=hidden`, which is barred from validation but is not disabled and therefore still submits.
10. **Move focus before hiding.** If focus is inside the subtree, move it to the controlling field first, or focus falls to `body` and screen-reader users lose their place. Nothing in WCAG requires this and every plugin gets it wrong.
11. **Render the correct initial state server-side.** Bind `hidden` and `disabled` off one derived value per field with `data-wp-bind` and let `wp_interactivity_process_directives()` apply it, so a no-JS visitor gets the right state and there is no flash before hydration. Jetpack does the pre-render pass with `WP_HTML_Tag_Processor` for the same reason.
12. **One caveat on multi-step.** The spec also says "The hidden attribute must not be used to hide content that could legitimately be shown in another presentation. For example, it is incorrect to use hidden to hide panels in a tabbed dialog." That argues against `hidden` for inactive steps even though it is right for conditional fields. Use `disabled` plus your own class there, or accept the deviation deliberately.

### 8.6 Accessibility of show and hide: WCAG settles it in Devform's favour, twice

1. **Revealed fields are not a status message.** Understanding SC 4.1.3, verbatim, using an example that is exactly this case: "After a user completes a survey question which indicates they are unhappy, a series of new questions are added to the page about customer satisfaction. The new inputs do not meet the definition of status message ... and so are not required to meet this success criterion." Followed by: "Note: Creating a status message about these questions being added, or notifying the user in advance that content changes may take place based on the user's response, are best practices but are not requirements in this scenario."
2. **Revealing fields is not a change of context.** Understanding SC 3.2.2: "If the user selects the meeting option, additional fields are displayed on the page ... Because only parts of the entry change and the overall structure remains the same, the basic context remains for the user."
3. If you do opt into an announcement, `aria-live` defaults to `off` when omitted; use `polite`, and per MDN "don't use the assertive value unless the interruption is imperative."
4. Removal needs nothing, because `hidden` removes content from all presentations including screen readers, so there is nothing left in the tree to announce. (Whether removal from a live region is announced at all is `unverified`; neither MDN nor the W3C forms tutorial documents it.)

### 8.7 The one-engine-two-runtimes answer

Two interpreters are unavoidable: nothing shippable exists in both PHP and JavaScript. JsonLogic is the closest (frozen spec, MIT, `jwadhams/json-logic-php` is a single 430-line file with zero dependencies, php >= 7.2, "Secure. We never eval()"), but its `==` is host-coerced, which is the exact drift you are trying to remove, and its `map`/`reduce`/`filter`/`substr` are operators you do not want an admin editing. json-rules-engine has the right shape (recursive all/any) and no PHP. CEL has no PHP and needs a parser. **Steal JsonLogic's shape and its no-eval property; do not adopt it as a dependency.**

What is avoidable is drift, and the mechanism is a fixture file, not discipline:

1. Ship `tests/fixtures/conditions.json` holding N cases of `{name, ast, scope, expect}`.
2. One PHPUnit test and one Node test read the same file. CI fails if either engine disagrees.
3. Add a table-driven test asserting the two operator key sets are **identical**. That alone would have caught every Fluent Forms divergence in section 8.1.
4. Ship the AST to the client verbatim as Interactivity API server state (Drupal's `data-drupal-states` pattern, modernised). Brief 1 §8.4.3 already commits to the Interactivity API, so this costs nothing extra.
5. **Actions use the same AST and the same evaluator**, evaluated after the entry is written, against a scope of `{visible fields, context, entry properties, prior action outputs}`, recording the outcome in `devform_action_runs.skipped_reason = 'condition_false'`. GF already proves the pattern is right by storing the identical `conditionalLogic` object at `meta/feed_condition_conditional_logic_object`.

**The one-line version for the positioning doc:** every incumbent evaluates conditions twice with two hand-written engines that provably disagree, hides fields with CSS that its own docs admit breaks when disabled, and either trusts the browser about what was hidden or never checks server-side at all.

Sources: `https://docs.gravityforms.com/conditional-logic-object/`, `/gform_is_value_match/`, `/gform_reset_pre_conditional_logic_field_action/`, `/gform_disable_css/`, `https://plugins.svn.wordpress.org/fluentform/trunk/app/Services/ConditionAssesor.php`, `/assets/js/fluentform-advanced.js`, `/app/Services/Form/SubmissionHandlerService.php`, `/app/Services/Parser/Extractor.php`, `https://raw.githubusercontent.com/drupal/drupal/11.x/core/lib/Drupal/Core/Form/FormHelper.php`, `/core/misc/states.js`, `https://raw.githubusercontent.com/Automattic/jetpack/trunk/projects/packages/forms/src/contact-form/class-conditional-logic.php`, `https://html.spec.whatwg.org/multipage/form-control-infrastructure.html`, `/form-elements.html`, `/interaction.html`, `/rendering.html`, `https://raw.githubusercontent.com/chromium/chromium/main/third_party/blink/renderer/core/html/forms/html_form_element.cc`, `https://raw.githubusercontent.com/mozilla/gecko-dev/master/dom/locales/en-US/chrome/dom/dom.properties`, `https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html`, `/on-input.html`, `https://jsonlogic.com/operations.html`, `https://github.com/jwadhams/json-logic-php`, `https://wiki.php.net/rfc/string_to_number_comparison`, `https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference/`

---

## 9. Caching and proxies

Closes critique items 7 and 8. Brief 1 asserted the token was cache-safe with a 3-hour window and never looked up a single TTL.

### 9.1 Real default TTLs

| Product | Default full-page TTL | Source |
|---|---|---|
| Pressable (batcache) | **5 minutes**, after 2 hits in 2 minutes | knowledgebase + batcache source (`$max_age = 300`, `$times = 2`, `$seconds = 120`) |
| WP Engine | 10 minutes | wpengine.com/support/cache/ |
| Kinsta | 1 hour | kinsta.com knowledgebase |
| W3 Total Cache | 3600 s, but page cache **off** by default | `ConfigKeys.php`: `pgcache.enabled = false`, `pgcache.lifetime = 3600` |
| Cloudflare (no APO) | HTML **not cached at all** by default; 120 minutes for a 200 with no cache headers once forced | developers.cloudflare.com default-cache-behavior |
| WP Rocket | **10 hours** | `inc/admin/upgrader.php`: `'purge_cron_interval' => 10, 'purge_cron_unit' => 'HOUR_IN_SECONDS'` |
| LiteSpeed Cache | **7 days** (604800 s) | `data/const.default.json`: `cache-ttl_pub = 604800` |
| Cloudflare APO | **30 days** | "APO automatically caches content for 30 days and invalidates on change within 30 seconds" |

**The spread a token must survive is 5 minutes to 30 days. Four orders of magnitude.** A 3-hour window is dead in cached HTML on a default LiteSpeed site for 6 days and 21 hours out of every 7 days.

Corroborating detail: WP Rocket's settings screen ships the string "Reduce lifespan to 10 hours or less if you notice issues that seem to appear periodically", linking to a KB article that says "Nonces ... are only valid for a certain length of time: 12 hours by default" and "On a cached page, the nonce can expire in the background while its ID is still present in the HTML source code." That is the market leader pushing the workaround onto the customer, exactly as brief 1 says. Note brief 1's "8 hours" is from the CF7-specific article; the general one says 10.

**The ecosystem already maintains a per-plugin nonce allowlist.** LiteSpeed ships `data/esi.nonces.txt` naming `gfrom_*` (Gravity Forms, vendor's typo), `form_nonce` (MetForm), `wsf_post` (WS Form), `acf_nonce`, `af_form_nonce`, `wp_rest`, `elementor-pro-frontend` and more, with the header "If you want to predefine new items, please send a Pull Request." LiteSpeed also ships a dedicated ESI block for WordPress core's own comment form. Documentary proof the problem is industry-wide, and a distribution channel to join.

### 9.2 What the token can and cannot protect against

The critique is right and brief 1 must be rewritten to say so plainly.

**What a deterministic HMAC over (form handle, time bucket) buys:**

1. It rejects blind POSTs from scrapers that never fetched the form page.
2. It carries the issue timestamp, which is the **only** way the time-trap works on a cached page, since no server-side session exists to record page-load time.
3. It binds the submission to one form handle, so a payload cannot be replayed against a different form.
4. It is a cheap tamper check on the form identity.

**What it does not buy:** CSRF protection (there is no user session to ride), replay protection (one GET harvests it and every POST for the whole window replays it, no browser, no cookie jar), or any per-request cost to an attacker.

**Two supporting facts for the docs:**

5. Core does not even attempt this: `wp-comments-post.php` contains no nonce and hands straight to `wp_handle_comment_submission()`. Brief 1 already cites this; keep it.
6. Akismet reports the comment nonce to the API as a signal (`'passed'`, `'failed'`, `'inactive'`) rather than enforcing it.
7. **Automattic made the same trade at 3M installs.** Jetpack's `jetpack_contact_form_jwt` uses `hash_hkdf('sha256', $secret, 32, 'jetpack-forms-jwt-hmac-v2')` for signing and a separate HKDF key for AES-256-GCM, and **there is no expiry claim in the payload**. Deterministic per form, cache-safe by construction, bound to no visitor and no session. That is the citation to put in the docs.

**Correction to brief 1's cited precedent:** `wp_refresh_post_nonces()` and `wp_refresh_heartbeat_nonces()` live in `wp-admin/includes/misc.php` and return early unless `current_user_can('edit_post', $post_id)`. They are **admin-only** and cannot be cited as core's pattern for anonymous front-end refresh. Absence is the finding: core ships no anonymous equivalent.

### 9.3 The corrected token design: two layers

**Layer 1, static and permanent.** A deterministic per-form HMAC over form handle plus a coarse issue timestamp, rendered into the HTML, with **no upper age limit enforced**. Validated only as an integrity and identity check plus the time-trap floor. State plainly in the docs what it is and is not.

**Layer 2, refreshed over an uncached channel.** A public `GET /devform/v1/forms/{handle}/refresh` with `permission_callback => __return_true`, called from JS on window load, returning a short-lived token that upgrades the submission from "unproven" to "proven".

**CF7 already ships exactly this pattern and it is the model to copy.** Three verified pieces:

1. `wpcf7_enqueue_scripts()` inlines `var wpcf7 = {...}` and, `if ( defined( 'WP_CACHE' ) and WP_CACHE )`, adds `'cached' => 1`. Every page-cache plugin defines `WP_CACHE` in wp-config, so **this is a free, reliable "I may be cached" signal available to any plugin.**
2. `includes/js/index.js` does `window.addEventListener("load", ... if (wpcf7.cached) form.reset())` and issues `GET contact-forms/{id}/refill`.
3. `includes/rest-api.php` registers `/contact-forms/(?P<id>\d+)/refill` with `WP_REST_Server::READABLE` and `'permission_callback' => '__return_true'`. The same JS explicitly does `delete i["X-WP-Nonce"]` on every request: CF7 removes the REST nonce from its own fetches.

Formality (100 installs) ships the other half of the pattern: two public routes, one minting an encrypted timestamp token, one accepting the submission. Its token route is guarded by a nonce, which for logged-out visitors is the same string for everyone, so the guard is a bot-cost signal and not CSRF protection, exactly as the critique says.

**Rules:**

1. Never make the refresh route load-bearing. If it 404s or is blocked, the form still submits with the static token.
2. **Harden the refresh route against being cached itself.** On a default LiteSpeed install it would be cached for **7 days**: `cache-rest = 1` and `cache-ttl_rest = 604800`, `Control::init_cacheable()` hooks `rest_api_init`, and `Control::finalize()` decides cacheability entirely from its own settings without ever reading the application's `Cache-Control` header (grepping `src/control.cls.php` for `headers_list` returns only its own `X-LiteSpeed-Cache-Control` emission). LiteSpeed's shipped `data/cache_nocacheable.txt` excludes only `^/wp-json/wp/v2` and `^/wp-json/elementor/v1`. Meanwhile core's `rest_send_nocache_headers` defaults to `is_user_logged_in()`, which is false for exactly the visitor who needs the token.
   So: force `rest_send_nocache_headers` true, call `do_action( 'litespeed_control_set_nocache', 'devform token route' )` (registered in `src/api.cls.php`), register the token field via `apply_filters( 'litespeed_esi_nonces', $nonces )`, and submit the field name to LiteSpeed's `data/esi.nonces.txt`.
3. Any static-HTML honeypot field-name swap must be **byte-stable across the whole cache lifetime**, which now means derived from the form handle and a site salt, never from a time bucket.

### 9.4 Cookies: the Context field cannot use a server-set cookie

Brief 1 §4.1.9 says context is "persisted in a cookie or sessionStorage so it survives full-page caching". A PHP-set cookie on a cacheable page is either a hard cache-kill or is silently discarded. There is no product where it just works.

**Cache-kill:** batcache iterates the response headers and, on finding `set-cookie`, deletes its generation lock and returns without caching (`// Do not cache if cookies were set`). Cloudflare: "Cloudflare does not cache the resource when ... The Set-Cookie header exists."

**Silent discard:** WP Rocket never calls `headers_list()` anywhere in its Buffer, Cache or front directories, so the static HTML file is written and the Set-Cookie is lost for every subsequent visitor. W3TC does read `headers_list()` but `_get_cached_headers()` only stores `Location`, `X-WP-Total`, `X-WP-TotalPages` plus the user's list. WP Engine says it outright: "If the page is cached, the cookie cannot be generated and perform its action with the page load as expected", and it "specifically ignores headers that define a PHPSESSID cookie" because "Unique IDs effectively bust cache".

**Unrecognised cookie names:** only the batcache family bypasses on them, and it bypasses on **any** cookie starting `wp`, `wordpress` or `comment_author`. Cloudflare APO publishes a fixed bypass-prefix list: `wp-`, `wordpress`, `comment_`, `woocommerce_`, `xf_`, `edd_`, `jetpack`, `yith_wcwl_session_`, `yith_wrvp_`, `wpsc_`, `ecwid`, `ec_`, `bookly_`, `bookly`. WP Rocket, W3TC, LiteSpeed and SG Optimizer all ignore cookies they do not know about (SG hard-codes exactly five bypass prefixes).

**The precedent is a cautionary tale.** WooCommerce Order Attribution uses Sourcebuster to set `sbjs_current`, `sbjs_first`, `sbjs_session`, `sbjs_udata` and friends, capturing traffic source, UTM parameters and device: functionally identical to Devform's Context field. Pressable publishes an article telling people to turn it off: "The presence of these cookies can interfere with Pressable's Batcache page caching system ... Pages must be generated fresh for each request."

**Requirements:**

1. The Context field is **100 percent client-side**. No `setcookie()` anywhere in the request path. Read `location.search`, `document.referrer` and any stored first-touch value in JS and post them as ordinary form fields.
2. Server-side `$_GET['utm_source']` is not merely stale on WP Engine, **it is absent**. Verbatim: "A request is received by the server and the `utm_` or `gclid` variables are stripped from the end of the URL before sending the request to be generated by PHP. Once the request is compiled, the variables are re-attached to the URL to be returned to the user." WP Engine's own advice: "it's far better to ensure that any action based on these variables is either executed with JavaScript or to use a variable of a different name".
3. For first-touch persistence use sessionStorage or localStorage first, a cookie only as fallback, and name it `devform_ctx`. **Never** anything starting `wp`, `wordpress`, `comment_`, `woocommerce_`, `edd_`, `jetpack` or `wpsc_`.

### 9.5 The success signal

A single novel query parameter is safe everywhere, because it either bypasses the cache or gets its own entry.

1. WP Rocket: `Tests::can_process_query_string()` returns false for **any** query string unless the key is one of four hard-coded values (`lang`, `s`, `permalink_name`, `lp-variation-id`) or in the user's list, default empty. Served uncached.
2. W3TC: `pgcache.cache.query` defaults false. Not cached.
3. Cloudflare: the default cache key is the full URL including the query string. APO bypasses on any parameter outside a 26-item allowlist.
4. Batcache and SG Optimizer: own cache entry.

**Rules:** emit exactly one parameter, always the same name, never a bag whose order can vary. WordPress VIP treats `?a=1&b=1` and `?b=1&a=1` as different entries (batcache `ksort`s internally, the platform layer above it does not). And do not reuse a name in anyone's ignore list (`utm_*`, `gclid`, `fbclid`, `_ga`, `ref`, `amp`, `_locale`), because those are stripped from the cache key and your success page will be served to the next visitor. LiteSpeed's `cache-drop_qs` defaults to `fbclid\ngclid\nutm*\n_ga`; W3TC ships a roughly 120-entry exempt list; SG Optimizer drops 17 named parameters from the cache filename.

### 9.6 Trusted proxies and rate limiting

**The failure is real and shipping.** Fluent Forms 6.2.12 implements trusted-proxy resolution correctly (`InteractsWithIPTrait::resolveIp()` returns `REMOTE_ADDR` unless `REMOTE_ADDR` is itself in a trusted CIDR, only then reading `HTTP_CF_CONNECTING_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_REAL_IP`, validating each with `FILTER_VALIDATE_IP`; its own comment says "By default, NO proxies are trusted") and then **ships no `config/trustedproxy.php` at all**, so the list is empty and no forwarded header is ever trusted. Meanwhile `FormValidationService::preventMaliciousAttacks()` runs `$maxSubmissionCount = 5`, `$minSubmissionInterval = 30`, and counts submissions `where('ip', $clientIp)` **with no `form_id` in the WHERE clause**. Behind Cloudflare on a host that does not restore `REMOTE_ADDR`, five submissions in thirty seconds anywhere on the site 429s everyone.

**Header facts:**

1. `CF-Connecting-IP` is the only trustworthy header and only when `REMOTE_ADDR` is a Cloudflare IP. `True-Client-IP` is identical but Enterprise-only.
2. **`X-Forwarded-For` is appended to, not replaced.** Cloudflare: "If an X-Forwarded-For header was already present in the request to Cloudflare, Cloudflare will append the IP address of the HTTP proxy connecting to Cloudflare to the header." A client sending `X-Forwarded-For: 1.2.3.4` produces `1.2.3.4, <real client IP>` at the origin. **Any implementation that takes the leftmost entry is trivially spoofable.**
3. Cloudflare publishes 15 IPv4 and 7 IPv6 prefixes, warns you must list all of them "to prevent IP spoofing", and notes "that list of prefixes needs to be updated regularly."
4. **WP Engine does not restore it without a support ticket.** Verbatim: "please contact WP Engine Support to request we enable the interpretation of X-Forwarded-For/True-Client-IP headers for your website", plus a warning that the platform firewall itself misattributes abuse to the proxy, "which could cause it to be denied. This will typically result in a 403 error."
5. **Kinsta and Pressable are `unverified`.** Kinsta's knowledge-base URL returns a PNG to tooling; Pressable publishes nothing on inbound visitor IP (its sitemap has an outbound-IP article only). Both are Cloudflare-fronted, so assume neither restores `REMOTE_ADDR`.
6. Core uses `$_SERVER['REMOTE_ADDR']` verbatim for comments, with the docblock "If you are behind a proxy, you should ensure that it is properly set, such as in wp-config.php" and the `pre_comment_user_ip` filter as the only extension point. Akismet's `get_ip_address()` is one line returning `REMOTE_ADDR`, but `auto_check_comment()` unconditionally ships every `HTTP_*` server variable except the cookie header, so the proxy header reaches akismet.com anyway.

**The design:**

1. Ship a trusted-proxy layer that trusts **nothing** by default. Copy Fluent Forms' trait structurally; do not copy its empty default.
2. Provide a one-click "I am behind Cloudflare" preset pinning the 22 Cloudflare prefixes as a bundled constant, refreshed on plugin update, never fetched at request time (guideline 7).
3. **Walk `X-Forwarded-For` from the right** and take the first hop that is not a trusted proxy. Never the leftmost entry.
4. Validate with `FILTER_VALIDATE_IP` before use and escape at render. Three CVEs live in exactly this spot.
5. **Fail open, not closed, on ambiguous identity.** If the resolved client IP equals the connecting proxy for more than N distinct submissions, emit an admin notice ("all traffic appears to originate from 172.x.x.x, configure trusted proxies or the rate limiter will block everyone") and downgrade IP limiting to a per-form global ceiling plus honeypot and time-trap, rather than 429ing the site.
6. **Hash the full address, not the anonymised one.** `wp_privacy_anonymize_ip()` truncates IPv4 to the /24 and masks IPv6 to a /64, so anonymising before hashing puts an entire ISP subnet in one bucket. Anonymise only for storage.

### 9.7 Rate-limiter storage

**Correction: `wp_check_comment_flood_db()` does not exist.** Brief 1 §7.1.3 names a function that is not in core. The real chain is `check_comment_flood_db()` (a pure `add_filter` wrapper since 4.7) into `wp_check_comment_flood( $is_flood, $ip, $email, $date, $avoid_die )`, which returns false immediately for anyone with `manage_options` or `moderate_comments`, then runs one prepared `SELECT comment_date_gmt ... WHERE comment_date_gmt >= %s AND ( comment_author_IP = %s OR comment_author_email = %s ) ORDER BY comment_date_gmt DESC LIMIT 1` with a one-hour lookback, then applies `comment_flood_filter`, whose default callback `wp_throttle_comment_flood()` is `if ( ( $time_newcomment - $time_lastcomment ) < 15 ) return true;`. **The real threshold is 15 seconds**, identity is `user_id` for logged-in users and `comment_author_IP` for anonymous, moderators are exempt, and the response is `wp_die(..., 429)`.

**Transients are confirmed unsafe for this**, and brief 1's assertion now has a source. `set_transient()` in `wp-includes/option.php`: `if ( wp_using_ext_object_cache() || wp_installing() ) { wp_cache_set(...) } else { ...option rows... }`, and `get_transient()` mirrors it with **no fallback read from the options table**. Redis evicts independently of TTL under `maxmemory` with `allkeys-lru`/`allkeys-lfu`/`allkeys-random`. Pressable says the same about its Memcached layer: "As the data is stored in memory, this means that it can be removed at any time."

**Requirements:**

1. Use a dedicated table, `devform_rate`, with `PRIMARY KEY (identity_hash, bucket)` written with `INSERT ... ON DUPLICATE KEY UPDATE count = count + 1`, pruned on bucket rollover. Constant-size storage per identity per window and one row-lock write per attempt, versus Fluent Forms' `COUNT` over a growing table.
2. **Never persist rejected or spam-discarded submissions into the entries table.** Akismet's own protocol has the precedent: `$discard = ( $commentdata['akismet_pro_tip'] === 'discard' && self::allow_discard() ); ... if ( $discard ) { ... }` with the comment "The spam is obvious, so we're bailing out early." No comment row is created. Otherwise a bot flood is an unbounded write amplifier on the exact table the admin UI queries. This answers the second half of critique item 8.
3. Being DB-backed, the limiter survives a Redis flush.

Sources: `https://codeload.github.com/wp-media/wp-rocket/tar.gz/refs/heads/develop`, `https://docs.wp-rocket.me/article/975-nonces-and-cache-lifespan/`, `https://plugins.svn.wordpress.org/litespeed-cache/trunk/data/const.default.json`, `/data/esi.nonces.txt`, `/data/cache_nocacheable.txt`, `https://plugins.svn.wordpress.org/w3-total-cache/trunk/ConfigKeys.php`, `/PgCache_QsExempts.php`, `https://raw.githubusercontent.com/Automattic/batcache/master/advanced-cache.php`, `https://developers.cloudflare.com/cache/concepts/default-cache-behavior/`, `/automatic-platform-optimization/reference/query-parameters/`, `/fundamentals/reference/http-headers/`, `https://www.cloudflare.com/ips-v4/`, `https://wpengine.com/support/cache/`, `/support/utm-gclid-variables-caching/`, `/support/using-a-reverse-proxy-with-wp-engine/`, `/support/cookies-and-php-sessions/`, `https://pressable.com/knowledgebase/how-does-batcache-page-caching-work/`, `/knowledgebase/woocommerce-order-attribution-tracking-cookies-sbjs_-and-caching/`, `https://docs.wpvip.com/infrastructure/caching/page-cache/`, `https://downloads.wordpress.org/plugin/contact-form-7.zip`, `https://downloads.wordpress.org/plugin/fluentform.zip`, `https://downloads.wordpress.org/plugin/akismet.zip`, `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/comment.php`, `/src/wp-includes/option.php`, `/src/wp-admin/includes/misc.php`, `https://redis.io/docs/latest/develop/reference/eviction/`

---

## 10. Database portability

Closes critique item 12, and the critique's premise is obsolete.

### 10.1 The premise changed on 13 August 2026

`WP_SQLite_Translator` no longer exists. sqlite-database-integration v3.0.0 (released 2026-08-13) replaced the regex translator with a full MySQL lexer, parser and emulation layer, and its changelog says "The new driver is always used. The legacy driver was removed." The repo is now a monorepo with `packages/mysql-parser`, `packages/mysql-on-sqlite`, `packages/mysql-proxy` and the plugin. Supported per its README: "MySQL-specific forms such as `INSERT IGNORE`, `ON DUPLICATE KEY UPDATE`, and joined updates", "Creating, altering, dropping, and truncating tables", "Numeric, character, binary, temporal, `ENUM`, `SET`, `JSON`, and spatial type declarations", "`INFORMATION_SCHEMA`, `SHOW`, `DESCRIBE`". Requirements: PHP 7.2 to 8.5, SQLite 3.37.0+.

**Playground bundles it.** `sqlite-integration-registry.js` pins `trunk`, a weekly cron refreshes it, and the committed `sqlite-database-integration-trunk.zip` (files dated 08-18-2026) contains `wp-includes/database/sqlite/class-wp-mysql-on-sqlite.php` and `define( 'SQLITE_DRIVER_VERSION', '3.0.0' )`. Playground's compiled libsqlite3 is 3.51.0.

**Brief 1's schema was executed against that exact bundled zip** on PHP 8.5.3 / SQLite 3.51.2 (driver reports `8.0.38-mysql-on-sqlite-3.0.0`). Every statement passed: `CREATE TABLE` with `ENUM`, `JSON` and `LONGTEXT`, composite `KEY`, prefix index with `Sub_part` preserved, `ALTER TABLE ADD/DROP COLUMN`, `ADD/DROP INDEX`, `MODIFY`, `CHANGE`, `SHOW INDEX FROM` (with `WHERE`), `DESCRIBE`, `SHOW CREATE TABLE`, `ON DUPLICATE KEY UPDATE`, `INSERT IGNORE`, `REPLACE`, keyset pagination, `DATE_SUB(NOW(), INTERVAL 30 DAY)`, `information_schema.COLUMNS`, CTEs, `ROW_NUMBER() OVER`, foreign keys with `ON DELETE CASCADE`, `GET_LOCK`, transactions.

**There is no activation fatal. The demo works.**

### 10.2 What actually breaks, and it is not SQLite

**SQLite's real problem is silent acceptance.** Default `@@sql_mode` is MySQL 8's real default including `STRICT_TRANS_TABLES`, and yet: `INSERT INTO t (st) VALUES ('zzz')` into `st ENUM('a','b')` succeeds and reads back `'zzz'`; a 16-character string goes into `VARCHAR(10)` intact; `'{not json'` goes into a `JSON` column intact. On MySQL 8 those are errors 1265, 1406 and 3140; on MariaDB the auto-added `CHECK (json_valid(...))` raises 4025. **A Playground PR preview cannot be used as evidence that validation works.**

**STORED generated columns are silently NULL.** Both `AS (JSON_UNQUOTE(JSON_EXTRACT(p,'$.email'))) STORED` and `AS (p->>'$.email') STORED` are accepted at CREATE, accepted at INSERT, and return NULL on SELECT with no error at any point. This independently validates brief 1 §5.2's materialised `value_index` column populated by PHP.

**Missing functions:** `JSON_UNQUOTE`, `JSON_CONTAINS`, `JSON_LENGTH`, `LAST_INSERT_ID()` as SQL (though `PDO::lastInsertId()` and therefore `$wpdb->insert_id` work), `COLLATE` in a query expression, `MATCH ... AGAINST` (while `FULLTEXT KEY` in CREATE TABLE is accepted **silently**, so the DDL lies to you), `JSON_TABLE`. Working: `JSON_EXTRACT`, `->`, `->>`, `JSON_SET`, `JSON_VALID`, `JSON_TYPE`, `JSON_OBJECT`, `JSON_ARRAY`.

**The real portability threat is MariaDB, and it is fatal.** MariaDB docs, verbatim: "`JSON` is an alias for `LONGTEXT COLLATE utf8mb4_bin` introduced for compatibility reasons with MySQL's JSON data type." `DESCRIBE` returns `longtext` for a column declared `JSON`. dbDelta reads the live schema with `DESCRIBE {$table}` and does a plain string comparison, `if ( $tablefield->Type !== $fieldtype_lowercased )`, with no JSON normalisation. `'longtext' !== 'json'` is true, so dbDelta emits `ALTER TABLE ... CHANGE COLUMN payload payload json`, MariaDB stores it as longtext again, and the next run repeats. **A JSON column produces an ALTER TABLE on every dbDelta run on every MariaDB site, forever.** MySQL 8 is fine (DESCRIBE returns `json`), and so is the SQLite driver (also returns `json`). MariaDB is the outlier, and MariaDB is a very large share of managed WordPress hosting.

Also note MySQL gained a native JSON type only in **5.7.8**, and WordPress core's hard floor is MySQL 5.5.5 (`$required_mysql_version = '5.5.5';` in `wp-includes/version.php`), where JSON does not exist at all.

**And WP VIP mandates dbDelta.** docs.wpvip.com/databases/custom-tables/: "Use `dbDelta` as part of an upgrade routine to add/update the table." That conflicts with brief 1 §5.2.5's plan to own migrations with raw ALTER TABLE, and a JSON column would break dbDelta there anyway. A schema with no JSON and no ENUM is a schema dbDelta handles correctly, which resolves both.

### 10.3 The corrected CREATE TABLE statements

Rules: `LONGTEXT` not `JSON`, `VARCHAR(20)` not `ENUM`, no generated columns, no FULLTEXT, `VARCHAR(191)` max for anything indexed (191 x 4 bytes utf8mb4 = 764, under the legacy 767-byte InnoDB key limit), `DATETIME` written from PHP with `gmdate()` rather than `DEFAULT CURRENT_TIMESTAMP`, and no semicolons or spaces inside any DDL string literal that passes through dbDelta.

```sql
CREATE TABLE {prefix}devform_entries (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_handle     VARCHAR(64)     NOT NULL,
  form_version_id BIGINT UNSIGNED NULL,
  status          VARCHAR(20)     NOT NULL DEFAULT 'new',
  created_at      DATETIME        NOT NULL,
  ip_hash         CHAR(64)        NULL,
  user_id         BIGINT UNSIGNED NULL,
  source_url      TEXT            NULL,
  referrer        TEXT            NULL,
  user_agent      TEXT            NULL,
  spam_reason     VARCHAR(64)     NULL,
  payload         LONGTEXT        NULL,
  hidden_fields   LONGTEXT        NULL,
  PRIMARY KEY (id),
  KEY form_created (form_handle, created_at),
  KEY form_status (form_handle, status),
  KEY form_version (form_version_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE {prefix}devform_entry_fields (
  entry_id     BIGINT UNSIGNED  NOT NULL,
  form_handle  VARCHAR(64)      NOT NULL,
  field_handle VARCHAR(64)      NOT NULL,
  property     VARCHAR(64)      NOT NULL DEFAULT '',
  delta        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  label        VARCHAR(255)     NOT NULL DEFAULT '',
  field_type   VARCHAR(32)      NOT NULL DEFAULT '',
  value        LONGTEXT         NULL,
  value_index  VARCHAR(191)     NULL,
  PRIMARY KEY (entry_id, field_handle, property, delta),
  KEY form_field (form_handle, field_handle),
  KEY value_idx (form_handle, value_index(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE {prefix}devform_form_versions (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_handle     VARCHAR(64)     NOT NULL,
  definition_hash CHAR(64)        NOT NULL,
  definition      LONGTEXT        NOT NULL,
  source          VARCHAR(16)     NOT NULL,
  first_seen      DATETIME        NOT NULL,
  last_seen       DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY form_hash (form_handle, definition_hash),
  KEY form_last_seen (form_handle, last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE {prefix}devform_action_runs (
  id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  entry_id        BIGINT UNSIGNED  NOT NULL,
  action_handle   VARCHAR(64)      NOT NULL,
  action_type     VARCHAR(64)      NOT NULL,
  attempt         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status          VARCHAR(20)      NOT NULL DEFAULT 'queued',
  skipped_reason  VARCHAR(64)      NULL,
  started_at      DATETIME         NULL,
  duration_ms     INT UNSIGNED     NULL,
  http_status     SMALLINT UNSIGNED NULL,
  request         LONGTEXT         NULL,
  response        LONGTEXT         NULL,
  error_code      VARCHAR(64)      NULL,
  error_message   TEXT             NULL,
  next_attempt_at DATETIME         NULL,
  output          LONGTEXT         NULL,
  PRIMARY KEY (id),
  KEY entry (entry_id),
  KEY status_due (status, next_attempt_at),
  KEY entry_action (entry_id, action_handle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE {prefix}devform_rate (
  identity_hash CHAR(64)        NOT NULL,
  bucket        INT UNSIGNED    NOT NULL,
  hits          INT UNSIGNED    NOT NULL DEFAULT 0,
  PRIMARY KEY (identity_hash, bucket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Status values live in PHP (`Status::ALL`), not in the schema. Three independent reasons: SQLite does not enforce ENUM membership even under strict mode, so the demo accepts garbage production rejects; adding a value to a MySQL ENUM is a table rebuild while adding one to a PHP allow-list is a constant; and dbDelta's type regex stops at the first space, so one ENUM value containing a space corrupts the migration permanently.

### 10.4 Migrations and CI

1. **Use dbDelta for table creation**, not because it is good but because VIP mandates it and a JSON-free, ENUM-free schema is exactly what dbDelta handles correctly.
2. Keep a `devform_db_version` option and a numbered migration array for what dbDelta structurally cannot do (drop a column, rename, backfill, drop an index), each step guarded by `SHOW INDEX FROM` or `information_schema.COLUMNS` introspection before the raw `ALTER TABLE`. All of that is confirmed working under the SQLite driver.
3. **Add one CI job**, because Playground pulls the driver from trunk on a weekly cron and your DB layer can change between merges with no change on your side: require `packages/mysql-on-sqlite/src/load.php` from a pinned checkout and run the full migration set plus a representative query load. Pure PHP, zero dependencies, sub-second. That is the only compatibility contract that exists: the project's own tracking issue (#162) is stale enough to list working syntax as unsupported, and there is no maintained supported-syntax matrix anywhere.

### 10.5 Playground demo blueprint

1. `register_activation_hook` fires normally. `activate-plugin.ts` runs `wp_set_current_user()` then core's real `activate_plugin()`, so table creation happens on its own. **Emit zero output during activation** or wp-admin shows a bogus error notice (the step re-checks the active plugins list rather than trusting the return value, because "if the plugin activation produces any output, WordPress will assume it's an activation error").
2. Blueprints v1 is the documented and action-emitted format. A v2 schema exists in source (`packages/playground/blueprints/src/lib/v2/`) but is undocumented; do not write it yet.
3. Shape: `$schema`, `preferredVersions: {php: "8.3", wp: "latest"}`, `features: {networking: true}` (needed for the webhook action to fire), `landingPage` pointing at a page containing the form, and steps `login`, `installPlugin`, `runPHP` (create the demo page and capture its ID), optionally `runSql` (seed entries; the step "assumes a presence of the sqlite-database-integration plugin").
4. `action-wp-playground-pr-preview@v3` exists, published 2026-05-28, latest v3 tag; inputs are `mode`, `playground-host`, `blueprint`, `blueprint-url`, `plugin-path`, `theme-path`, `description-template`, `comment-template`, `restore-button-if-removed`, `github-token`, `pr-number`. Pass a full `blueprint` string rather than `plugin-path: .`, because activating the plugin is not the demo.
5. **Use the two-workflow split**, pinned to `@v3` in both, even with no build step. The README: "Public fork PRs usually receive a read-only `GITHUB_TOKEN`, so the action may be unable to edit the PR description. If fork contributors need working previews, use the two-workflow build/publish setup below even when the build command is just a small zip step." Devform accepts fork contributions and the entry admin likely needs an npm build, so it is two files regardless. **The repo must be public**: "Playground runs in the user's browser and needs unauthenticated download URLs."
6. Other documented gotchas: zips must extract to a slug-named folder; `workflow_run` workflows always read their YAML from the default branch; the shared `ci-artifacts` release must be a prerelease, not a draft.
7. **Do not promise the notification email in the demo.** Playground now captures sendmail (`sendmail.ts` "creates a sendmail-compatible null transport ... dispatched as a `sendmail.spawned` event, and never delivered", merged 2026-08-13), but PR #4103 says plainly "this PR adds that plumbing only; it does not add an inbox UI", and "Capture starts after `startPlaygroundWeb()` completes, so mail sent during initial boot or Blueprint execution is not retained." Demo the stored entry instead, which is conveniently the thing Devform differentiates on.

### 10.6 Uploads outside the webroot is a capability, not a rule

Brief 1 §7.2.4 mandates storing uploads outside the webroot. **That is unimplementable on WP VIP.** Only `/tmp` is writable, and it is per-request: "Files and directories can only be relied on for the duration of the current request" because requests route to different, transient containers. The VIP File System maps `/wp-content/uploads/` to an external object store that "lacks a true directory structure", so `scandir()` behaves differently. VIP's own answer is filter-based ACL (`pre_option_vip_files_acl_restrict_unpublished_enabled`, `pre_option_vip_files_acl_restrict_all_enabled`) in `/client-mu-plugins`, not filesystem location.

Pressable manages core file permissions and they "cannot be changed"; wp-content is the writable area at 755/644, and Pressable documents nothing about PHP execution in uploads, so assume it is not blocked. WP Engine does block it: "WP Engine servers block the execution of .php files from within the wp-content/uploads/ directory."

**Ship a three-tier fallback**: outside the webroot where possible, otherwise an uploads subdirectory with UUID paths plus a capability-checked PHP delivery handler, and report which tier is active in Site Health so the site owner knows.

Sources: `https://github.com/WordPress/sqlite-database-integration`, `/issues/162`, `https://raw.githubusercontent.com/WordPress/wordpress-playground/trunk/packages/playground/wordpress-builds/build/sqlite-integration-registry.js`, `/packages/playground/blueprints/src/lib/steps/activate-plugin.ts`, `/packages/php-wasm/util/src/lib/spawn-handlers/sendmail.ts`, `/packages/docs/site/docs/blueprints/03-data-format.md`, `https://raw.githubusercontent.com/WordPress/action-wp-playground-pr-preview/v3/README.md`, `/v3/action.yml`, `https://mariadb.com/docs/server/reference/data-types/string-data-types/json.md`, `https://dev.mysql.com/doc/refman/5.7/en/json.html`, `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/version.php`, `/src/wp-admin/includes/upgrade.php`, `https://docs.wpvip.com/databases/custom-tables/`, `/vip-file-system/local-file-operations/`, `/security-controls/access-controlled-files/`, `https://wpengine.com/support/platform-settings/`, `https://pressable.com/knowledgebase/wordpress-file-permissions/`, `https://registry.npmjs.org/@wp-playground/cli`

---

## 11. Async delivery: the substrate and the honest guarantee

**The substrate.** Ship Action Scheduler as the default, own the retry policy in your own columns, and make WP-CLI a first-class equal path. The alternatives lose: a custom table plus system cron alone is correct where cron exists and never runs at all on shared hosting with no cron UI and no WP-CLI; `fastcgi_finish_request()` is FPM-only, php.net warns that excessive use exhausts `pm.max_children` and produces gateway errors, sessions stay locked unless you `session_write_close()` first, writing to the output buffer afterwards makes the script exit silently, and `max_execution_time` still applies; a pure loopback is what AS already falls back to, and core's own `cron.php` docblock concedes the timeout and blocking arguments are unreliable. Concretely: (a) store the delivery row before responding, always; (b) if `function_exists('fastcgi_finish_request')`, flush and attempt delivery 1 inline so the happy path never touches the queue; (c) on failure or absence of FPM, schedule a one-off AS action carrying only the row id; (d) retry, backoff and attempt cap live in Devform's `attempt` and `next_attempt_at` columns, never in AS; (e) register `wp devform deliver --due-now` and put the crontab line in the README next to `wp action-scheduler run`; (f) show "queue last ran" and "N deliveries overdue" on the settings screen, because the failure is otherwise invisible. Host reality worth writing down: WP Engine's Alternate Cron sets `DISABLE_WP_CRON` true and curls wp-cron.php every minute, and its own docs say it "does not work for websites that are utilizing other forms of custom password protection (basic auth, etc) because it must remain publicly accessible", which is exactly a password-protected staging site with a silently dead queue.

**The guarantee, in one paragraph a customer could read.** Devform records the delivery intent in a database row the moment the submission is stored, so nothing is lost once the submission succeeds. Execution is a different promise: the queue is woken by a WP-Cron event (`action_scheduler_run_queue`, scheduled every 60 seconds) that only fires when someone loads a page, or, if the host sets `DISABLE_WP_CRON`, when the host's system cron hits wp-cron.php, plus a best-effort non-blocking loopback POST to admin-ajax.php fired at most once a minute on wp-admin page loads, which fails silently behind basic auth, a WAF, or a firewall that blocks self-requests. So on a busy site delivery is typically within a minute; on a zero-traffic brochure site with no system cron and nobody logged into wp-admin, delivery is "whenever someone next visits". Action Scheduler never retries an action that threw, it only un-claims one that was stuck for over five minutes, so retry with backoff is Devform's own `attempt` and `next_attempt_at` columns, not the queue's. Every site therefore gets a visible "queue last ran N minutes ago" indicator and a documented `wp action-scheduler run` and `wp devform deliver` escape hatch, and the docs say plainly that a real system cron every minute is the only configuration in which delivery latency is bounded.

**The competitive claim then becomes honest and still differentiating:** not "guaranteed delivery", which nobody in WordPress can offer, but "we record every attempt, we show you when the queue is dead, and you can replay."

Sources: `https://www.php.net/manual/en/function.fastcgi-finish-request.php`, `https://wpengine.com/support/wp-cron-wordpress-scheduling/`, `https://raw.githubusercontent.com/WordPress/WordPress/7.0.4/wp-includes/cron.php`, `https://actionscheduler.org/wp-cli/`

---

## 12. Accessibility regulation: EAA, EN 301 549, and the CI gate

Closes critique item 13. Two framings in the critique need correcting before the obligations make sense.

### 12.1 Devform is not a regulated product

1. **A WordPress plugin appears nowhere in the EAA's product list.** Article 2(1) is exhaustive: consumer general-purpose computer hardware and its operating systems; self-service terminals; consumer terminal equipment for electronic communications; the same for audiovisual media; e-readers. Standalone application software is absent. The only software in scope is "e-books and dedicated software" as a service under 2(2)(e).
2. Article 3(21) defines an economic operator as "the manufacturer, the authorised representative, the importer, the distributor or the service provider"; 3(4) defines a service provider as one who "provides a service on the Union market". A plugin vendor is neither.
3. **Consequence: Devform has no CE marking or declaration-of-conformity duty and cannot be fined under the EAA.** Its customers running covered services can be. Accessibility is a channel argument and a liability-transfer argument, not a compliance obligation on the plugin.

### 12.2 A contact form on a covered site is in scope

1. Annex I Section III(c), the general requirement for all covered services: "making websites, including the related online applications, and mobile device-based services, including mobile applications, accessible in a consistent and adequate way by making them perceivable, operable, understandable and robust." Unqualified. Not scoped to checkout or identification.
2. Section IV adds service-specific duties: (e) consumer banking (identification, electronic signatures, payment services, plus information not exceeding CEFR level B2) and (g) e-commerce (accessibility information about goods, plus identification, security and payment functionality). A plain contact form is neither, so Section III(c) is what binds it.
3. Article 2(4)'s exclusions (pre-2025 time-based media, pre-2025 office formats, online maps, archived content, and third-party content "neither funded, developed by, or under the control of, the economic operator concerned") do not reach a form the site owner installed and configured.
4. **Dates, all past.** Applies from 28 June 2025 (Article 31(2)); transposition was due 28 June 2022; Article 32 gives a transitional window to 28 June 2030 for products already in lawful use and lets pre-2025 service contracts run at most five more years.
5. **The microenterprise exemption is generous and dents the sales thesis.** Article 4(5) exempts microenterprises providing services outright, and Article 3(23) defines one as fewer than 10 persons **and** turnover or balance sheet not exceeding EUR 2 million. A six-person Dutch webshop is exempt. The buyers actually bound are mid-market and up, plus every public-sector body under the separate 2016/2102 regime.

### 12.3 EN 301 549: correct the version story

1. **The current published version is V3.2.1 (2021-03), and it was made under the mandate for Directive 2016/2102, not the EAA.** Its Annex A maps only to 2016/2102.
2. **V4.1.0 exists only as a Final draft dated 2026-06**, in the ETSI vote phase. Its foreword: "The present document has been prepared under the Commission's standardisation request C(2022) 6456 final [M 587] to provide one voluntary means of conforming to the essential requirements of Directive (EU) 2019/882 ... The minimum requirements of Directive (EU) 2019/882 are explicitly detailed in Annex ZB", followed by "Once the present document is cited in the Official Journal ... compliance with the normative clauses ... confers ... a presumption of conformity." Its announced transposition dates are relative (doa = 3 months after ETSI publication), so an OJ citation before 2027 is unlikely.
3. **Therefore no harmonised standard currently confers presumption of conformity under the EAA.** An EAA conformance claim today is a self-assessment against Annex I, not a standard-based safe harbour. (Article 15(3) lets the Commission adopt technical specifications by implementing act when standardisation is delayed; whether one exists is `unverified`.)
4. That absence is the opportunity, not the problem: with no safe harbour, service providers must self-assess and publish, which makes a per-form generated conformance paragraph valuable rather than redundant.

### 12.4 What a covered service must publish, and the product opportunity

Article 13(2), verbatim: "Service providers shall prepare the necessary information in accordance with Annex V and shall explain how the services meet the applicable accessibility requirements. The information shall be made available to the public **in written and oral format**, including in a manner which is accessible to persons with disabilities. Service providers shall keep that information for as long as the service is in operation." Article 13(4) requires immediate notification of the competent authority on non-conformity. Annex V point 1 puts it in "the general terms and conditions, or equivalent document" and requires a general description in accessible formats, operating explanations, and "a description of how the relevant accessibility requirements set out in Annex I are met by the service". Article 14 allows a fundamental-alteration or disproportionate-burden defence assessed against Annex VI.

**Product opportunity: Devform generates the per-form paragraph the client pastes into that document**, listing which fields are used, which criteria the default markup satisfies, and what remains the site owner's responsibility. Nobody in the WordPress forms market does this.

### 12.5 EN 301 549 clause 11.8 is a spec for Devform's admin UI

The strongest finding in this pass. Clause 11.0 covers authoring tools; clause 11.8.0's note says "This is applicable both to standalone and to web based authoring tools", so a wp-admin form builder is caught even though it is itself a web page.

1. **11.8.2 Accessible content creation:** "Authoring tools shall enable and guide the production of content that conforms to clauses 9 (Web content) or 10 (Non-Web content) as applicable."
2. **11.8.3:** preservation of accessibility information across restructuring or re-coding transformations.
3. **11.8.4 Repair assistance:** "If the accessibility checking functionality of an authoring tool can detect that content does not meet a requirement of clauses 9 or 10 ... then the authoring tool shall provide repair suggestion(s)." Note the perverse incentive: shipping no checker satisfies this vacuously. Ship one anyway.
4. **11.8.5 Templates:** "When an authoring tool provides templates, at least one template that supports the creation of content that conforms ... shall be available and identified as such." **Label Devform's default markup in the UI as the conforming template, and make deviation deliberate.**
5. **Clause 11.7** requires software to follow platform user preferences for "units of measurement, colour, contrast, font type, font size, and focus cursor". In CSS terms: honour `prefers-contrast`, `forced-colors` and `prefers-reduced-motion`, and do not replace the UA focus ring with something weaker.
6. **Clause 12.1.2** requires the documentation itself to be published in a format conforming to clause 9 or 10, and 12.1.1 requires it to "list and explain how to use the accessibility and compatibility features". 12.2 extends this to support channels.
7. **Clause 9.6** is what clause 9 adds beyond WCAG: all five WCAG conformance requirements, including **non-interference**, defined as "all content on the page, including content that is not otherwise relied upon to meet conformance, meets clauses 9.1.4.2, 9.2.1.2, 9.2.2.2 and 9.2.3.1" (audio control, no keyboard trap, pause/stop/hide, three flashes). Three are direct constraints on an embedded form: no focus trap in the enhancement layer, no auto-updating region without a pause control, and "complete processes" means a multi-step form conforms only if every step does.

Design consequences for brief 1 §4.3: convert those field rules into **builder** requirements. Hiding a label, blanking an error string, or overriding markup in Mode B should each produce an inline warning with a one-click fix (11.8.4), not a warning alone.

### 12.6 The Netherlands

1. The implementation is the **Implementatiewet toegankelijkheidsvoorschriften producten en diensten** (Wet of 8 April 2024, BWBR0049571), in force 28 June 2025, a bundle of amendments to the Warenwet, the Telecommunicatiewet, BW Boek 6, the Wft and the Wet handhaving consumentenbescherming, plus five sector decrees. There is deliberately no e-commerce decree; e-commerce sits in the Civil Code.
2. **The operative article for a webshop is art. 6:230fb BW**, which requires the service to comply with "de toegankelijkheidsvoorschriften in bijlage I, afdeling III en IV, onder g, en bijlage V bij richtlijn (EU) 2019/882". Art. 6:230fc(1) carries the microenterprise exemption verbatim; 6:230fc(3) removes the disproportionate-burden defence if you took external funding for accessibility improvements.
3. **The enforcer is the ACM** (AFM for financial services), via Whc art. 8.15 and hoofdstuk 8a. Art. 8a.0 obliges immediate notification on non-conformity; art. 8a.1 requires the burden assessment to be documented, redone at least every five years or on request, retained five years. Whc art. 2.15(1) caps the fine at "ten hoogste EUR 900.000 of, indien dat meer is, 1% van de omzet van de overtreder" (`likely`: the chapter-2 powers were traced but the Whc bijlage listing was not walked).
4. **The Netherlands went beyond the directive.** Article VIII(4) of the Implementatiewet withholds the entire transitional period from e-commerce providers: "Dit artikel is ... niet van toepassing op dienstverleners van e-handelsdiensten". **Dutch webshops had no grace period after 28 June 2025.**
5. Public sector is separate and older: the Tijdelijk besluit digitale toegankelijkheid overheid (BWBR0040936) set 23 September 2019 / 2020 / 23 June 2021 deadlines and requires a published toegankelijkheidsverklaring per site. Note its definition of the standard still reads "waaronder de WCAG 2.0 richtlijnen": the Dutch decree is frozen two WCAG versions behind.

### 12.7 What to publish

1. **`accessibility.txt` in the plugin root, in v1.** The WordPress accessibility team defined the format and it is **mandatory for Accessibility Ready themes as of 30 June 2026** and recommended for plugins. Sections: `=== Accessibility Statement ===`, `== Quick Information ==` (keys: Accessibility audit completed, Most recent audit date, Tested to standard and conformance level, Audited by, Accessibility Conformance Report), `== Testing Tools and Methodology ==`, `== Screen Reader Text Class ==`, `== Accessibility Features ==`, `== Accessibility Help Contact ==`, `== Where to Report Issues ==`.
   **Adoption check via SVN: zero of fourteen plugins ship one** (contact-form-7, wpforms-lite, ninja-forms, forminator, fluentform, formidable, everest-forms, happyforms, bit-form, html-forms, ws-form, jetpack, kadence-blocks, and, notably, accessibility-checker, Equalize Digital's own plugin). Half an hour of work, and the `Screen Reader Text Class` key forces a decision brief 1 has not made.
2. **An ACR (a completed VPAT) for v1.1**, using the ITI template's **EN 301 549 edition**, not the Section 508 one, hosted at a stable URL and referenced from accessibility.txt. The WP accessibility team's own framing: "If you're hoping to sell or distribute your theme or plugin to government, education, or enterprise organizations, creating an Accessibility Conformance Report can help you stand out from competitor solutions and may be required."
   **No WordPress form plugin publishes one.** gravityforms.com/accessibility/ makes a marketing claim ("everything you need to comply with government standards for Section 508 and WCAG 2.1 AA") with no ACR linked; /vpat/ and /accessibility-conformance-report/ both 404. wpforms.com/accessibility/, ninjaforms.com/accessibility/, formidableforms.com/accessibility/ and kadencewp.com/accessibility/ all 404. Elementor publishes a statement citing WCAG **2.0** under Israeli Standard 5568. (Absence finding from probing constructed URLs, not an exhaustive sweep.)
3. Note what WordPress itself does and does not require: core "aims to make the WordPress Admin and bundled themes fully compliant with WCAG 2.2 AA", but the theme handbook is blunt that "'Accessibility Ready' does not mean that the theme meets the WCAG guidelines AA-level", and **none of the 18 wordpress.org plugin guidelines mentions accessibility at all.** Every accessibility commitment Devform makes is voluntary and must be self-evidenced.
4. One distribution angle: accessibility-ready themes must not make "inaccessible plugin recommendations", so theme authors have a rule-based reason to prefer a documented-accessible form plugin.

### 12.8 The CI gate recipe, and its honest ceiling

**Keep `accessibility` in the plugin-check categories list (it is a valid slug and costs nothing), but delete any claim that it tests something.** See section 1.13.

The real gate is Playwright driving a real WordPress. Current pins verified this session: `@axe-core/playwright` 4.13.0, `axe-core` 4.13.0, `@playwright/test` 1.62.1, `@wp-playground/cli` 3.1.50, `@wordpress/env` 11.13.0, `pa11y-ci` 4.1.1, `@lhci/cli` 0.15.1.

1. **Boot.** Either wp-env (localhost:8888, admin/password; note "Automatic port selection is disabled when CI is set", so a busy port is a hard failure) or the Docker-free `npx @wp-playground/cli@latest server --blueprint=blueprint.json --mount=.:/wordpress/wp-content/plugins/devform`, default port 9400. The Playground path reuses the PR-preview blueprint, so one artefact serves demo and test.
2. **Scan three states per fixture form: pristine, submitted-invalid, submitted-success.** Most form failures only exist in the error state (`aria-invalid`, error-summary focus, `aria-describedby` wiring), so a pristine-only scan tests almost nothing.
3. **Filter with the EN tag** so failures print as EN clause numbers: `new AxeBuilder({ page }).include('.devform').withTags(['wcag2a','wcag2aa','wcag21a','wcag21aa','EN-301-549']).analyze()`. 69 axe-core rules carry the `EN-301-549` tag, each paired with a clause tag such as `EN-9.1.3.5`.
4. **Explicitly add `label-title-only`.** It is tagged `best-practice`, so a WCAG-tag filter excludes it, and it is the rule that catches placeholder-as-label, the exact anti-pattern brief 1 §4.3.2 forbids.
5. Rules that matter here and that axe does cover: `label`, `select-name`, `input-button-name`, `button-name`, `form-field-multiple-labels`, `aria-input-field-name`, `aria-required-attr`, `aria-required-children`, `duplicate-id-aria` (which catches the same form rendered twice on one page, the defect a Fluent Forms reviewer named), `autocomplete-valid` (tagged `EN-9.1.3.5`, which directly tests brief 1's autocomplete-token requirement), and `color-contrast`.
6. **Skip Lighthouse CI as a second gate.** lighthouse 13.4.1 depends on axe-core ^4.12.1 and weights audits by "axe user impact assessments"; it is a coarser wrapper on the same engine. pa11y-ci is a cheaper alternative but only visits URLs and cannot drive a submit.
7. **Write the honest sentence in the README and in accessibility.txt's methodology section.** Deque claims "on average 57% of WCAG issues" with no linked methodology; the UK GDS tool audit injected 142 deliberate barriers and the best of 13 tools detected 40 percent (13 percent for the worst, 50 percent for the best when counting items flagged for human review); WebAIM's own note is the line to quote: "Absence of detected errors does not indicate that a page is accessible or conformant."
8. **WCAG 2.2 AA cannot be proven by CI, which undercuts brief 1's claim 5 as stated.** axe-core 4.13 ships **exactly one** WCAG 2.2 rule, `target-size`, and it is **disabled by default** ("These rules are disabled by default, until WCAG 2.2 is more widely adopted and required"). Five of WCAG 2.2's A/AA additions bear directly on forms (3.3.7 Redundant Entry for multi-step carry-forward, 3.3.8 Accessible Authentication, 2.4.11 Focus Not Obscured, 2.5.8 Target Size, 3.2.6 Consistent Help) and axe covers one, off by default. Restate the claim as "WCAG 2.2 AA by construction, verified by a published manual checklist plus fixtures, with an automated gate covering the subset tools can reach." For a developer audience that admission is more persuasive than a badge. Note also that WCAG 2.2 **removed** 4.1.1 Parsing, which weakens the case for HTML validation in CI.

**The empirical anchor for why this matters.** WebAIM Million 2026, 1,000,000 home pages: 95.9 percent had detected WCAG 2 failures (up from 94.8 percent, reversing six years of improvement); 56.1 errors per page, up 10.1 percent year on year. Top failure types: low contrast 83.9 percent of pages, missing image alt 53.1 percent, **missing form input labels 51 percent**, empty links 46.3 percent, empty buttons 30.6 percent. And: "Home pages had 6.9 form inputs on average, a 36% increase in the last 3 years. One third (33.1%) of those form inputs were not properly labeled." **Three of the six most common failures on the web are things a form plugin emits.** That is a citable dataset, not a vendor assertion.

**Prior art on audits, and one unresolved gap.** There is essentially no published third-party audit of any WordPress form plugin. The most-cited piece (WP Tavern, Toby Cryns, 5 March 2024) is a WAVE smoke test: "According to the WAVE web accessibility tool, Gravity Forms scores a perfect score out-of-the-box for their front-end forms. Same with Ninja Forms, Contact Form 7, and WP Forms." Gravity Forms' Morgan Kay is quoted confirming a WCAG 2.1 AA target and that "A senior accessibility consultant has done a complete audit of our plugin in the dashboard"; that audit has not been published. Consultant Joe Dolson on the free tier: "Contact Form 7 allows you to create very accessible forms, but you have to know what you're doing ... I wouldn't recommend it to the average user." **The WPCampus Gravity Forms audit could not be verified** (wpcampus.org returned 502 on every URL, web.archive.org returned 429). If it exists it is the benchmark for what a Devform audit report should look like; re-check before citing.

Sources: `http://publications.europa.eu/resource/celex/32019L0882`, `https://www.etsi.org/deliver/etsi_en/301500_301599/301549/03.02.01_60/en_301549v030201p.pdf`, `/04.01.00_30/en_301549v040100va.pdf`, `https://wetten.overheid.nl/BWBR0049571`, `/BWBR0020586`, `/BWBR0005289`, `/BWBR0040936`, `https://wpaccessibility.org/docs/topics/documenting-accessibility/accessibility-txt/`, `/docs/topics/documenting-accessibility/acr/`, `https://wordpress.org/about/accessibility/`, `https://make.wordpress.org/themes/handbook/review/accessibility/`, `https://raw.githubusercontent.com/dequelabs/axe-core/develop/doc/rule-descriptions.md`, `https://raw.githubusercontent.com/dequelabs/axe-core-npm/develop/packages/playwright/README.md`, `https://alphagov.github.io/accessibility-tool-audit/`, `https://webaim.org/projects/million/`, `https://www.w3.org/TR/WCAG22/#new-features-in-wcag-2-2`, `https://wptavern.com/certain-wp-form-plugins-make-accessibility-easy`

---

## 13. Portability: uninstall, export, and which importer to build first

Closes critique item 30.

### 13.1 Uninstall

**There is no wordpress.org rule.** None of the 18 detailed plugin guidelines mentions uninstall, data deletion or leftover tables. The only machine-checked rule is plugin-check's `Plugin_Uninstall_Check`, added in 1.6.0, whose entire logic is: if `uninstall.php` exists, the regex `#defined\s*\(.*WP_UNINSTALL_PLUGIN.*\)#` must match, else error code `uninstall_missing_constant_check` at severity 7. There is no check that you delete data and no check that you keep it. **The uninstall policy is a product decision, not a compliance decision.**

**The actual constraint is what core promises the user.** `is_uninstallable_plugin()` returns true if `uninstall.php` exists **or** an uninstall hook is registered, and `wp-admin/plugins.php` then labels the plugin `'%1$s by %2$s (will also <strong>delete its data</strong>)'` and switches the confirmation between "Yes, delete these files and data" and "Yes, delete these files". So shipping `uninstall.php` while keeping entries makes core over-promise; shipping nothing makes core under-promise and leaves you unable to clean even options and scheduled actions.

**What the respected plugins do.** WooCommerce: `if ( defined( 'WC_REMOVE_ALL_DATA' ) && true === WC_REMOVE_ALL_DATA )`, with the in-code rationale "to prevent data loss when deleting the plugin from the backend and to ensure only the site owner can perform this action". WPForms: returns early unless `$settings['uninstall-data']`, then drops 15+ tables, wipes `wpforms\_%` options, rmdirs `uploads/wpforms/` and cancels all Action Scheduler tasks. Everest Forms: `( defined('EVF_REMOVE_ALL_DATA') && true === EVF_REMOVE_ALL_DATA ) || 'yes' === get_option('everest_forms_uninstall_option')`. Forminator: an option defaulting to false, plus `forminator_before_uninstall`/`forminator_after_uninstall` hooks and an explicit per-site loop using `$wpdb->get_blog_prefix($blog_id)` on multisite. Gravity Forms and Formidable decouple it entirely: a deliberate in-admin action while the plugin stays installed (Formidable then calls `deactivate_plugins()` with the comment "so the tables don't get added right back"). Yoast SEO and Akismet ship no uninstall routine at all.

**Two anti-patterns:** Ninja Forms registers `register_uninstall_hook( __FILE__, 'ninja_forms_uninstall' )` whose function body is the single comment "Nothing to see here.", so WordPress says "will also delete its data" and nothing is deleted, forever. Contact Form 7 force-deletes every `wpcf7_contact_form` post and drops its legacy table with no setting and no constant; its submissions survive only because they live in a different plugin.

**The policy, in three tiers:**

1. **Tier 1, always on plugin delete:** options, transients, capabilities, scheduled events and queued Action Scheduler actions. This makes core's promise true.
2. **Tier 2, opt-in, two independent switches ORed** (Everest's pattern): a `DEVFORM_REMOVE_ALL_DATA` wp-config constant and a settings toggle, default off. Drops the entry tables, the version table, the uploads directory and the CPT mirror. Copy WPForms' consent copy pattern: "Remove ALL Devform data upon plugin deletion. All forms, entries, and uploaded files will be unrecoverable."
3. **Tier 3, a deliberate in-admin "Delete all Devform data" action**, capability plus nonce, decoupled from plugin deletion. This is the only path that works when the owner wants the data gone but the plugin to stay.
4. Put the policy in the readme FAQ verbatim. "We do not delete your data unless you tell us to" is on-brand for a plugin whose pitch is that the data stays in your database.
5. **Because `uninstall.php` runs without the plugin loaded**, keep table names and paths in a tiny standalone helper both the plugin and `uninstall.php` include, rather than WPForms' `require_once 'wpforms.php'` of the whole main file.

**Absence of evidence, stated:** I could not retrieve user reviews complaining about either behaviour. The WebSearch budget was spent, every fallback engine served an anti-bot page or ignored phrase operators, and wordpress.org's forum search is a JS widget with bbPress topics absent from its REST API. Grepping the first page of 1-star and 2-star reviews for six form plugins for uninstall wording returned zero hits, which is roughly 20 reviews per filter and is not evidence of absence.

### 13.2 Export format

**Nobody in the cohort ships a round-trippable single-file bundle of definition plus entries in JSON.** Gravity Forms exports forms as JSON and states "You cannot use this process to import forms from other Form formats", has **no native entry import at all** ("Gravity Forms does not include a feature for importing entries"), and points at three third-party products "as suggestions only". WPForms exports forms as JSON only (`wpforms-form-export-MM-DD-YYYY.json`) and says "This guide covers moving forms between sites. To move form entries (submissions) instead, see our guide". Fluent Forms exports forms as JSON and entries separately as csv, ods, xlsx or json. **Formidable is the only one bundling both, and it does it in a WXR-shaped XML** with an RSS `<channel>` and `the_generator('export')`; CSV is declared entries-only.

**Formidable's import rule is the one thing worth copying**, stated in its own UI: "If your imported form key and creation date match a form on your site, that form will be updated." A natural-key upsert, which is exactly what Devform needs given handles are the join key.

**Import is an `unfiltered_html` escalation path, and Fluent Forms documents it in shipped code.** Two verbatim comments: "SECURITY (FINDING-07): the editor save path routes form_fields through Updater::sanitizeFields (skipped only for unfiltered_html users), but import stored them verbatim, so an importer without unfiltered_html could plant stored XSS (e.g. a field label of `<img onerror=...>`)." And FINDING-08: "Customizer::store() refuses to save custom JS/CSS without unfiltered_html; import must honor the same boundary. Sanitizing _custom_form_js via fluentform_kses_js is insufficient because the value is JS *code* executed inside a `<script>` block (kses only strips `<script>` tags), so skip these keys entirely for importers who cannot author raw JS/CSS." **Devform inherits this problem doubly, because its selling point is fully overridable markup in the form definition.**

**The format:**

```json
{
  "devform_export_version": 1,
  "generated_at": "...",
  "site_url": "...",
  "plugin_version": "...",
  "forms": [ ... ],
  "entries": [ ... ]
}
```

1. **Entries are optional and off by default**, requested with an explicit flag, so support exports stay small and PII-free. GF's own docs ask users to export one form at a time.
2. **Every entry carries the definition hash it was captured against**, which is Jetpack's `post_mime_type = 'v3'` trick generalised and also closes critique item 9 on the export side.
3. **Secrets redact by default** with typed placeholders, restored only with an explicit include-secrets flag, via the deny-by-default projection from section 6.3.
4. **Import is an upsert on the form handle**, idempotent, never destructive. Do not copy Fluent's `resetEntries()`.
5. **Import re-runs the authoring path's sanitisation** and skips raw HTML/JS/CSS payloads when the importing user lacks `unfiltered_html`, with a user-facing notice.
6. **CSV stays a lossy entries-only side export**, consistent with brief 1's CSV-injection position.
7. **Do not invent an XML format.** Formidable's is a legacy WXR echo, not a virtue.
8. No numeric ids, no timestamps, no site URLs, no `modified` key. That last one is ACF's mtime coupling; Gravity Forms' 40-key database dump is the other anti-pattern.

**Say plainly in the docs that Tools > Export (WXR) will not carry Devform entries.** Verified in core: `wp-admin/includes/export.php` defines `WXR_VERSION 1.2`, walks only `get_post_types( array( 'can_export' => true ) )`, and emits authors, terms, posts, comments and their meta. The only extension points are the `export_wp` action (before headers), `export_wp_filename`, and the two skip filters. **There is no hook for injecting arbitrary data.** WP-CLI's own doc says the file contains "authors, terms, posts, comments, and attachments" and "WXR files do not include site configuration (options)". Because brief 1 chose custom tables, a client doing a normal WordPress migration loses every submission silently. Answer it in the product: a Devform export step in the docs, a Site Health notice, `wp devform export --with-entries`, and `wp db export --tables=` as the honest developer answer for whole-table moves.

**Core's privacy exporter is a free win**, and its shape is already yours: `wp_privacy_generate_personal_data_export_file()` produces a ZIP containing exactly `export.json` and `index.html`, requires `ZipArchive`, and the link expires after `apply_filters( 'wp_privacy_export_expiration', 3 * DAY_IN_SECONDS )`. Jetpack registers `$exporters['jetpack-feedback']` with a `$per_page = 250` default, which is a sane batch size to copy.

### 13.3 Which importer to build first

**Contact Form 7 plus Flamingo, bundled in the plugin, not sold separately.**

1. **Size.** CF7 is 10,000,000 installs and Flamingo is 800,000, so the pool of "has forms, has stored entries, no builder loyalty" is the largest available.
2. **Cost is low and the storage is public.** CF7: the `wpcf7_contact_form` CPT with `_form`, `_mail`, `_mail_2`, `_messages`, `_additional_settings`, `_locale`, `_hash` post meta. Flamingo: the `flamingo_inbound` CPT with `const spam_status = 'flamingo-spam'`, the `flamingo_inbound_channel` taxonomy whose term slug is the CF7 form's `post_name`, and per-message meta `_subject`, `_from`, `_from_name`, `_from_email`, `_fields` (the whole array) plus one `_field_{key}` row per field, `_meta`, `_akismet`, `_recaptcha`, `_consent`, `_hash`, `_submission_status`. **Both sides are readable with plain `get_posts()` and `get_post_meta()` even when both plugins are deactivated**, which no shipped competitor's importer manages.
3. **The market validates the target.** Fluent Forms bundles five migrators (Caldera, CF7, Gravity, Ninja, WPForms); WPForms Lite bundles three plus a dedicated Entry Importer whose docs say "Your existing CF7 submissions stored by Flamingo can also be migrated to WPForms."
4. **Bundle it.** Formidable ships its importers as standalone plugins and they have **300 and 20 active installs**. An importer only converts if it is inside the plugin being installed.
5. **The shipped state of the art is beatable on four axes**, all verified in Fluent Forms' `BaseMigrator`: a `DEFAULT_ENTRY_MIGRATION_MAX_LIMIT = 1000` cap (with the in-source TODO "more-then 5000/6000 (based on sever) entries process make timout response ... need silently async processing"); `resetEntries($fluentFormId)` under the comment `//delete prev entries` before every insert, so a re-run wipes what is there; hard-coded `'source_url' => '', 'browser' => '', 'device' => '', 'ip' => '', 'user_id' => get_current_user_id()`, discarding submission metadata; and a hard dependency on the source plugin being active. Devform's importer should be queued, resumable, idempotent on the source post id, uncapped, and preserve `created_at`, `source_url`, IP and spam status.

**Explicitly defer the rest, for stated reasons:** Elementor Pro is closed source with no verifiable schema (the free plugin's `modules/` directory has 60 entries and none is forms or submissions; the commonly cited `e_submissions` table names are **unverified** here). Gravity Forms is a paid base reachable only through `GFAPI` and its users are the least likely to switch. WPForms Lite has no entries, so an importer buys form definitions only. **Jetpack Forms is the strategic second at 3M installs** but costs three payload-format parsers before a single field mapping: v1 legacy text split on `<!--more-->` and `JSON_DATA`, v2 JSON and v3 JSON, dispatched on `post_mime_type`. That last fact is also the design lesson: **stamp a schema version on the stored entry payload from day one.**

### 13.4 WP-CLI

Model the tree on Gravity Forms, not Ninja Forms. GF's CLI add-on ships `wp gf form list|get|create|update|delete|duplicate|export|import`, `wp gf form field ...`, `wp gf entry create|delete|duplicate|edit|export|get|import|list|update`, `wp gf tool ...`, with `--format=table,csv,json,count`, `--porcelain`, `--dir`, `--filename`, `--file`, `--page-size`, `--offset`. Note `wp gf entry import` exists on the CLI even though the admin UI has no entry import. Ninja Forms' entire CLI is `info|form|list|get|delete|delete-all-forms` with no export, no import and no `--format`. WPForms Lite and Fluent Forms register no CLI at all.

Ship: `wp devform form list|get|export|import|delete`, `wp devform entry list|get|export|delete|purge`, `wp devform action replay <entry-id> [--action=<handle>]`, `wp devform queue run|status`, `wp devform doctor`. Honour `--format` on every list, `--porcelain` on anything producing an id or path, `WP_CLI::confirm` on every destructive command, and register behind `if ( defined( 'WP_CLI' ) && WP_CLI )`. Synopsis is PHPDoc with `## OPTIONS` and `## EXAMPLES`, and the inline `---` block for defaults and enums. Put the crontab line for `wp devform queue run` in the README beside `wp action-scheduler run`.

### 13.5 Site Health

Two hooks, both since WP 5.2: `debug_information` (Info tab; sections take label, description, show_count, private and fields; fields take label, value, `debug` and private) and `site_status_tests` (`'direct' => [id => [label, test, skip_cron]]` and `'async' => [... has_rest, async_direct_test]`). WPForms already ships a Site Health section reporting version, install dates, uploads directory, DB tables, total forms and total submissions.

Highest-value Devform entries: whether the tables exist and at what schema version, entry count per form, pending queue depth, last retention run, which uploads tier is active (section 10.6), whether trusted proxies are configured and whether the resolved client IP looks like a proxy (section 9.6), and whether the REST namespace is reachable, which turns critique item 5's non-REST-fallback question into a diagnostic.

Sources: `https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/`, `/plugins/plugin-basics/uninstall-methods/`, `https://raw.githubusercontent.com/WordPress/plugin-check/trunk/includes/Checker/Checks/Plugin_Repo/Plugin_Uninstall_Check.php`, `https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-admin/includes/plugin.php`, `/src/wp-admin/plugins.php`, `/src/wp-admin/includes/export.php`, `/src/wp-admin/includes/privacy-tools.php`, `https://plugins.svn.wordpress.org/woocommerce/trunk/uninstall.php`, `/wpforms-lite/trunk/uninstall.php`, `/everest-forms/trunk/uninstall.php`, `/forminator/trunk/uninstall.php`, `/ninja-forms/trunk/ninja-forms.php`, `/contact-form-7/trunk/uninstall.php`, `/jetpack/trunk/uninstall.php`, `/contact-form-7/trunk/includes/contact-form.php`, `/flamingo/trunk/includes/class-inbound-message.php`, `/fluentform/trunk/app/Services/Transfer/TransferService.php`, `/app/Services/Migrator/Classes/BaseMigrator.php`, `/formidable/trunk/classes/controllers/FrmXMLController.php`, `/classes/helpers/FrmFormMigratorsHelper.php`, `https://docs.gravityforms.com/importing-form-entries/`, `/managing-forms-wp-cli/`, `https://github.com/gravityforms/gravityformscli`, `https://make.wordpress.org/cli/handbook/guides/commands-cookbook/`, `https://developer.wordpress.org/reference/hooks/site_status_tests/`

---

## 14. Revised risks and open questions

### 14.1 Revised risk list

Ordered by how much rework each causes if discovered late.

1. **The audience thesis has no articulated demand.** Zero developers in any reachable venue ask for form definitions in git. The two things they do ask for, unprompted and repeatedly, are a predictable extension surface and stable markup. **Mitigation:** lead the positioning with "markup you own and a submission record you can trust", keep files as the mechanism rather than the headline.
2. **Distribution, and it is now measured.** The developer-builder audience is 1.00 percent of WordPress origins. SureForms got to 500,000 installs in 28 months by being bundled with a theme ecosystem. HN is not a channel (every WordPress-form story sits at 1 to 2 points). Devform has no bundle and no ecosystem.
3. **Jetpack, not CF7, is the free baseline**, at 3M installs, on by default, with free local storage, free multi-step, free file upload, free logged webhooks and free conditional logic. Five of brief 1's differentiators die against it.
4. **Overridable markup is a breaking-change surface.** Gravity Forms changed its submit control from `input` to `button` in August 2026 and a developer is patching "a whole bunch of sites". If Devform's default template is overridable, template changes need a versioning and deprecation policy from v1.
5. **Two engines will drift.** Fluent Forms ships provably divergent PHP and JS condition evaluators in the same release. Without the shared fixture file in section 8.7, Devform ships the same bug.
6. **MariaDB plus dbDelta plus a JSON column is an infinite ALTER loop** on every MariaDB site forever. Solved by the schema in section 10.3, unsolvable afterwards without a migration.
7. **The Playground demo cannot prove validation works.** SQLite accepts ENUM values outside the set, over-length VARCHARs and invalid JSON under strict mode, and computes STORED generated columns to NULL silently.
8. **Delivery latency is unbounded on low-traffic sites** and there is no honest fix. Section 11 is the paragraph that must go in the README rather than a guarantee that cannot be kept.
9. **The rate limiter is a site-wide outage waiting to happen** behind any CDN. Fluent Forms ships that bug today at 700k installs. The fail-open design in section 9.6 is the mitigation.
10. **Import is a stored-XSS vector** into a plugin whose selling point is raw markup in the definition. Fluent Forms carries two named findings in shipped comments for getting this wrong.
11. **Secrets in exported definitions.** Fluent Forms exports Slack webhook URLs today. Deny-by-default projection, not an unset() blacklist.
12. **Solo maintainer versus the support baseline.** Unchanged from brief 1, and reinforced: Fluent and Forminator bought 2 percent one-star rates with 85 to 100 percent thread resolution.
13. **Craft's Formie is the honest counter-example.** The most mature commercial form builder on a CMS with a git-YAML config system deliberately kept forms out of it, and the slice it did put in produced 17 changelog bug entries. Devform should have a written answer to "why did Formie decide the other way".
14. **Retracted from brief 1's risk list:** the core `core/form` block is a lower risk than stated (merged 2023, untouched by feature work in 2026), and the HXFE/Promptless "demand failure" risk does not exist.

### 14.2 Revised open questions for Nol

Brief 1's twelve, updated, with the ones research has now answered removed.

1. **Answered, remove from the list:** question 1 (HXFE and Promptless install counts, see 1.7), and most of question 9 (the CF7 importer window does not exist because the 2028 date is unsourced; build the importer on install counts instead, see 13.3).
2. **What is the one-sentence pitch, given that "forms in git" has no observed demand and "your data stays in your database" is no longer differentiated?** This is now the highest-value question in the brief. The evidence points at "markup you own, deliveries you can see and replay, and a retention policy you control", with files as the mechanism.
3. **Do you accept 1.00 percent as the beachhead, or retarget?** GenerateBlocks and Stackable users (300,000 installs, no form block at all, nothing to displace) are a cleaner first target than Bricks, whose users' documented default is to install an established name.
4. **Free versus paid.** Unchanged in substance, sharper in evidence: every vendor in the builder cohort paywalls the second half of the form, and Devform giving away both halves means there is no revenue path in that segment and the incumbents can close the gap the day they choose, because they already built the feature.
5. **WP floor: 7.0 or lower?** New question. WP 7.0 is 66.3 percent of installs and buys the core Connectors API for secrets, which removes a whole subsystem. Below that you write the fallback yourself. Note also that relying on core for SSRF blocking requires 7.0.3+/6.9.6+/6.8.8+/6.7.7+/6.6.7+/6.5.10+, which is not expressible as a floor, so Devform does its own IP checks regardless.
6. **PHP floor.** Unchanged: 7.4, 8.0 or 8.1, costing 18 to 22 percent at the top end.
7. **Is there an npm build step?** Now partly answered: use the two-workflow Playground split regardless, because fork PRs get a read-only token. The question that remains is whether the entry admin is a JS app (critique #31's unpriced consequence). Note the measured cost of the alternative: Automattic's answer for the Jetpack responses inbox is a Redux app with a router, fixtures and a webpack build.
8. **How much admin UI?** Sharpened by section 6.2: the answer is a read-only viewer plus a declared `client_editable` allow-list writing to an overrides table, which is smaller than brief 1's ACF-style Sync tab and avoids ACF's entire bug class.
9. **Repeater free or paid?** Unchanged.
10. **Support model.** Unchanged.
11. **Name, slug and domain availability.** Still unverified.
12. **Semgrep as a hard gate.** Unchanged.
13. **Register WP Abilities API abilities at launch?** Sharpened: at least five plugins do, Jetpack registers eight including `create-form`, ACF added them in 6.8.0 behind an `enable_acf_ai` opt-in flag, and Gravity Forms has created empty `mcp-filters` and `mcp-actions` doc categories (post count 0), so an MCP add-on is prepared and undocumented. A file-defined form is trivially agent-editable; the cost is low and the "behind, not lean" risk is real.
14. **New: does Devform ship Cloudflare Turnstile as a first-class default alongside Akismet?** The professional audience's consensus answer to spam is Turnstile, and Akismet appears zero times in the largest developer thread on the subject.
15. **New: does the plugin publish an ACR?** Half a day for `accessibility.txt` (which nobody in the cohort ships), a few days for an EN 301 549 VPAT (which no WordPress form plugin has). It is the cheapest credible differentiator found in this whole pass.

---

## 15. Sources by section

Full URL lists are inline at the end of each section. This is the index plus the access notes.

**Section 1 (corrections):** wordpress-develop and WordPress tags on raw.githubusercontent.com (http.php at 11 release tags, class-wp-http.php, Requests, cron.php, upgrade.php, default-filters.php); woocommerce/action-scheduler trunk; actionscheduler.org; contactform7.com `/feed/`; plugins.svn.wordpress.org (contact-form-7, html-forms, sureforms, jetpack, fluentform, kadence-blocks); api.wordpress.org plugins and stats endpoints; docs.gravityforms.com; WordPress/plugin-check and plugin-check-action on GitHub; GHSA-3pwp-g2mj-5p3v.

**Section 2 (Jetpack):** Automattic/jetpack trunk (`projects/plugins/jetpack/modules/contact-form.php`, `projects/packages/forms/src/**`), api.wordpress.org, jetpack.com support pages.

**Section 3 (builders and blocks):** cdn.httparchive.org `/v1/adoption` per technology plus `geo=Netherlands` and `rank=Top 1M` slices; httparchive.org/faq for methodology; plugins.svn.wordpress.org readmes; academy.bricksbuilder.io; forum.bricksbuilder.io Discourse JSON; breakdance.com; oxygenbuilder.com; elementor.com/help; docs.themeisle.com; essential-blocks.com; docs.wpbeaverbuilder.com.

**Section 4 (sentiment):** reddit.com `search.rss` and `comments/{id}/.rss` (the only working Reddit channel); hn.algolia.com API; api.stackexchange.com (site=wordpress); forum.bricksbuilder.io `search.json`; GitHub issues elementor/elementor#11086 and #20452; wptavern.com wp-json.

**Section 5 (prior art and format):** git.drupalcode.org raw (webform 6.x, drupal 11.x); api.drupal.org; drupal.org config docs; statamic.dev and statamic/cms 5.x; craftcms.com docs; verbb/formie craft-5; symfony.com; laravel.com; filamentphp.com; livewire.laravel.com; sanity.io; contentful/contentful-migration; json-schema.org; jsonforms.io; getkirby.com; php.net yaml; wordpress-develop l10n.php and block-i18n.json; developer.wordpress.org i18n and CLI docs; wp-cli/i18n-command.

**Section 6 (source of truth and secrets):** WordPress 7.0.4 connectors.php and wordpress-develop trunk; pluggable.php; sodium_compat at the 5.2 tag; wp-config-sample.php; api.wordpress.org/secret-key; ACF 6.8.7 zip and GitHub issues 160/405/599/832; ACF readme.txt changelog; wp-mail-smtp and fluentform trunk; google/site-kit-wp; WordPress/two-factor; Drupal ConfigSync and ConfigImporter; roots/bedrock; docs.wpvip.com.

**Section 7 (versioning):** pronamic/gravityforms 3.0.2 mirror (forms_model.php, class-gf-field-consent.php, entry_detail.php, form_display.php); docs.gravityforms.com; Drupal Webform schema; woocommerce trunk order-item classes and the coupon-storage dev blog; wordpress-develop comment.php; jetpack Feedback/Feedback_Field; docs.stripe.com; shopify.dev; docs.github.com; formidable and forminator zips; EDPB Guidelines 05/2020; dev.mysql.com row-format docs.

**Section 8 (conditional logic):** docs.gravityforms.com; fluentform trunk PHP and JS; wpforms-lite, formidable and ws-form zips; drupal 11.x FormHelper and states.js; jetpack Conditional_Logic; html.spec.whatwg.org (four sections); chromium and gecko sources; w3.org WCAG 2.2 Understanding pages; jsonlogic.com and jwadhams/json-logic-php; wiki.php.net string-to-number RFC.

**Section 9 (caching and proxies):** wp-media/wp-rocket develop tarball; litespeed-cache trunk data files and src; w3-total-cache trunk; sg-cachepress zip; Automattic/batcache; developers.cloudflare.com (four pages) and cloudflare.com/ips-v4 and -v6; wpengine.com support (four pages); pressable.com knowledgebase; kinsta.com knowledgebase; docs.wpvip.com; contact-form-7, fluentform and akismet zips; wordpress-develop comment.php, option.php, misc.php, functions.php; redis.io eviction docs.

**Section 10 (database):** WordPress/sqlite-database-integration trunk and issue 162; WordPress/wordpress-playground trunk (registry, blueprint steps, sendmail, docs, committed zip); WordPress/action-wp-playground-pr-preview v3; mariadb.com and dev.mysql.com JSON docs; wordpress-develop version.php and upgrade.php; docs.wpvip.com; wpengine.com; pressable.com; registry.npmjs.org. Local execution: PHP 8.5.3 / SQLite 3.51.2 against Playground's bundled `sqlite-database-integration-trunk.zip`.

**Section 11 (async):** php.net fastcgi_finish_request; wpengine.com wp-cron; WordPress 7.0.4 cron.php; actionscheduler.org and its wp-cli page; action-scheduler trunk.

**Section 12 (accessibility regulation):** publications.europa.eu CELEX 32019L0882 (EUR-Lex itself served an AWS WAF challenge; the Publications Office cellar serves the same official OJ text); etsi.org deliver directory for EN 301 549 V3.2.1 and the V4.1.0 final draft; wetten.overheid.nl (BWBR0049571, BWBR0020586, BWBR0005289, BWBR0040936); digitoegankelijk.nl; wpaccessibility.org; wordpress.org/about/accessibility; make.wordpress.org themes handbook; dequelabs/axe-core and axe-core-npm; alphagov accessibility-tool-audit; webaim.org/projects/million; w3.org WCAG 2.2; wptavern.com.

**Section 13 (portability):** developer.wordpress.org plugin handbook and CLI handbook; WordPress/plugin-check; wordpress-develop plugin.php, plugins.php, export.php, privacy-tools.php; plugins.svn.wordpress.org uninstall.php files for woocommerce, wpforms-lite, everest-forms, forminator, ninja-forms, contact-form-7, jetpack; contact-form-7 contact-form.php; flamingo class-inbound-message.php; fluentform TransferService and BaseMigrator; formidable FrmXMLController and FrmFormMigratorsHelper; docs.gravityforms.com and gravityforms/gravityformscli; make.wordpress.org/cli handbook.

### 15.1 Sources that refused automated access

Stated so nobody quotes this brief as complete.

1. **WebSearch was unavailable for every pass in this run** (a 200-call session budget was already spent). Every source was reached by directly constructed URL or by API. No keyword discovery was possible, so coverage is broad on named products and thin on anything not guessed. Every fallback engine was blocked: DuckDuckGo html and lite (HTTP 202 anti-bot), Mojeek (JS captcha), searx.be ("Verifying your browser"), Brave (JS SPA), Bing RSS (returns results but silently ignores quoted phrases and `site:`).
2. **eur-lex.europa.eu**: AWS WAF challenge, HTTP 202 with an empty body. Worked around via the EU Publications Office cellar.
3. **contactform7.com/news/ and ideasilo.wordpress.com**: HTTP 403 to WebFetch and to curl with a browser UA (Cloudflare interstitial). Worked around via their `/feed/` endpoints.
4. **wpcampus.org**: HTTP 502 on every URL. **web.archive.org**: HTTP 429. The WPCampus Gravity Forms audit is therefore unconfirmed.
5. **kinsta.com/knowledgebase/wordpress-ip-address/**: returns HTTP 200 with Content-Type image/png, a bot-mitigation response. **pressable.com** publishes nothing on inbound visitor IP. Both marked unverified in section 9.6.
6. **generateblocks.com, docs.generateblocks.com, generatepress.com**: Cloudflare 403. The "GenerateBlocks Pro has no form block" claim rests on the free readme's own Pro enumeration only.
7. **Divi**: elegantthemes.com/documentation per-module articles all 404 and are absent from the sitemap. No primary Divi claim is made anywhere in this brief.
8. **core.trac.wordpress.org**: JavaScript challenge blocking both WebFetch and curl, so whether an open ticket proposes a core encryption API is unverified.
9. **contactable.io**: did not resolve or connect.
10. **wordpress.org forum search**: a Jetpack Search JS widget, and bbPress topics are not registered in its REST API, so review-corpus keyword research was not possible.
11. **themeisle.com and essential-blocks.com pricing**: 403 and JS-rendered respectively. Otter Pro and Essential Blocks Pro prices not captured.
12. **indiehackers.com and x.com**: JS shells. Nothing inferred from either.
13. **Elementor Pro**: closed source and not on wordpress.org. The commonly cited submission table names are unverified.
14. **GitHub REST API rate-limited** before the calendar dates of the WordPress 7.0.3 / 6.9.6 / 6.8.8 security releases could be pinned. The version boundary itself is verified by direct file fetch; only the release dates are unverified.
