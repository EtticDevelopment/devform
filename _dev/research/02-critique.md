# Devform research brief: completeness critique

Spot-checked 18 URLs. Two load-bearing claims are refuted at their own cited source. Findings are ordered by how much rework they cause if discovered after the architecture is set.

---

## P0. Wrong at the source, and the architecture rests on it

**1. §7.2.9 SSRF claim is false, and it is the entire justification for a bespoke webhook resolver.**
The brief states: "As of WordPress 7.0.4 the shipping `wp_http_validate_url()` blocklist covers only 127/8, 10/8, 0/8, 172.16-31 and 192.168; **169.254.169.254 is not blocked on any released version**. Only wordpress-develop trunk adds link-local, CGNAT, TEST-NET and multicast."
I fetched the released 7.0.4 tag (`https://raw.githubusercontent.com/WordPress/WordPress/7.0.4/wp-includes/http.php`, confirmed current via `api.wordpress.org/core/version-check/1.7/`, which returns 7.0.4). The shipped code contains:
```php
|| ( 169 === $parts[0] && 254 === $parts[1] )
|| ( 100 === $parts[0] && 64 <= $parts[1] && 127 >= $parts[1] )
|| ( 198 === $parts[0] && 18 <= $parts[1] && 19 >= $parts[1] )
|| ( 224 <= $parts[0] && 239 >= $parts[0] ) || 240 <= $parts[0]
```
plus 192.0.0/24, TEST-NET-1/2/3 and 6to4. The brief's own cited URL (`develop.svn.wordpress.org/tags/7.0.4/...`) returns the same code. The custom resolver may still be worth building, but for reasons the brief never states: core's parts check is IPv4-only (no `::1`, no `fc00::/7`, no IPv4-mapped IPv6), core re-resolves DNS at request time after validating (the rebinding window), and core follows redirects to unvalidated hosts.
*Close it:* re-read `wp_http_validate_url()` and `WP_Http::request()`'s `redirection` handling in the current tag, plus `http_request_host_is_external`, and rewrite the rationale around IPv6, rebinding and redirects.

**2. §6.4.1 "Action Scheduler has no dependency on the WP-Cron system" is wrong, and it contradicts §6.4.3 in the same subsection.**
actionscheduler.org states the scheduler "will attempt to run every minute by attaching itself as a callback to the `'action_scheduler_run_schedule'` hook, which is scheduled using WordPress's built-in WP-Cron system", and that its fallback is "an async loopback request" fired on the `shutdown` hook of WP Admin requests. So: (a) the regular tick *is* WP-Cron, and (b) Action Scheduler uses the exact loopback mechanism the brief rejects three bullets later ("Do not build the queue on a loopback POST... Choosing Action Scheduler sidesteps that entire support category"). It does not sidestep it; it inherits the same cURL 6/7/28 and 401/403 failure modes. The brief also names the hook `action_scheduler_run_queue`; the docs say `action_scheduler_run_schedule`.
Why it matters: signed, retried, logged delivery is headline claim #6. If the runner needs WP-Cron or an admin pageview, the honest SLA is "eventually, if someone visits", which is the same complaint the brief levels at everyone else's cron-based retention.
*Close it:* actionscheduler.org/faq/, `ActionScheduler_QueueRunner::init()` in the source, and the AS behaviour under `DISABLE_WP_CRON` plus system cron. Then write down the actual delivery guarantee.

**3. No WordPress version floor exists anywhere in the document, while claim 10.1#4 hard-depends on WP 6.9 *and* on the theme having a theme.json.**
§9.2 sets a PHP floor and stops. The version half is fine: `api.wordpress.org/stats/wordpress/1.0/` shows 75.2% on 6.9+ (7.0 alone is 66.3%). The theme half is unresearched. I fetched the cited dev-blog article; it confirms `textInput` covers textarea plus email/number/password/search/text/tel/url and that styles apply "including those used in third-party plugins", and it confirms focus states, labels, checkboxes, radios and placeholders are **not** covered. It says nothing about classic themes. The stated audience is "theme builders and custom-built sites", a population full of classic, Timber and Sage themes with no theme.json, where "ship zero CSS" means unstyled inputs and the pitch ("install it and the form already looks like the site") is false.
*Close it:* test a classic theme with no theme.json; read `wp_get_global_stylesheet()` and `WP_Theme_JSON_Resolver::get_theme_data()` for the classic-theme path; decide whether Devform ships a fallback sheet and what the WP floor is.

**4. Secrets management is never addressed, and it is the unresolved fork between §8.2 and §6.3.**
§8.2.1 puts forms in files with `'actions' => [...]` inside them. §6.3 puts actions in a `devform_actions` table with a `config JSON` column, a signing secret and per-action retry policy. The document never says which is source of truth, what happens when they disagree, or where a webhook secret, CRM API key or signing key lives (git? wp-config constant? DB? env?). This single decision determines the sync model, the admin UI surface, the export format and the guideline-7 exposure.
*Close it:* ACF Local JSON's treatment of settings that must not be committed, the `wp-config` constant convention used by WP Mail SMTP and Gravity Forms add-ons, and how WS Form and GF handle credentials inside exported form JSON.

**5. The non-REST fallback is required twice and specified nowhere. There is no fourth option left.**
§5.1 rejects `admin-post.php`, rejects `admin-ajax.php`, rejects a rewrite endpoint, then §5.1 caveat 2 says "Devform needs a non-REST fallback POST path" and §5.8.4 books it as a known cost. Every rejected option is the fallback.
*Close it:* read how the common blockers actually work (Wordfence, Solid Security, and the "Disable REST API" plugin family all filter `rest_authentication_errors`, which leaves `admin-post.php` reachable), then either name `admin-post.php` as the degraded path and accept the `admin_init` cost, or drop the fallback and ship the admin warning only.

---

## P1. Angles never researched that change the design

**6. Multisite is one sentence in the whole document.**
The only mention is `unfiltered_html` being Super Admin only. Absent: `$wpdb->prefix` vs `base_prefix` (per-site or network tables); the fact that `register_activation_hook` does not fire per site on network activation, so tables never get created; table creation for sites added later (`wp_initialize_site`); uploads at `wp-content/uploads/sites/N` versus the "outside the webroot" rule; network-admin settings and the capability model; one shared theme providing form files to 40 sites with 40 different action stacks and entry stores; Application Passwords under multisite. Every one of these is a schema or bootstrap decision, not a later patch.
*Close it:* `register_activation_hook` reference (its explicit network-activation note), `wp_initialize_site`, `wp_get_upload_dir()` on multisite, and how Action Scheduler and WooCommerce install their tables per site.

**7. Full-page caching is asserted as solved, never measured.**
Three specific holes. (a) §5.3 calls the HMAC token "cache-safe" with a 3-hour window, but no cache TTL was ever looked up; WP Rocket, LiteSpeed, W3TC, Cloudflare and every managed edge routinely hold HTML far longer, which reproduces exactly the intermittent failure the section opens with. (b) §4.1.9 says context is "persisted in a cookie or sessionStorage so it survives full-page caching", but a server cannot set a cookie on a cached response, and many hosts and Cloudflare configs bypass or vary cache on unrecognised cookies, so the fix can silently disable page caching site-wide. (c) §8.4.1 relies on `?wp-form-result=success` on a cached URL, where query-string handling in the cache key differs per product. (d) §7.1.1's honeypot field-name swap must be byte-stable across cached HTML or the cached page carries a dead field name.
*Close it:* WP Rocket cache-lifespan default, LiteSpeed "Default Public Cache TTL", Cloudflare cache rules and cookie behaviour, WP Engine and Pressable cookie-based cache-exclusion lists, and Automattic/batcache's cookie handling (already in the source list, never used).

**8. Rate limiting and IP capture contradict each other, and the failure mode is a site-wide outage.**
§7.1.3 rate-limits per identity off the entries table. §7.2.5 defaults IP capture to `REMOTE_ADDR` only, with proxy headers honoured solely on explicit configuration. Behind Cloudflare, any load balancer or most managed hosts, `REMOTE_ADDR` is the proxy, so "5 per IP per 30 seconds" becomes a site-wide 429 the first time a bot arrives. Separately, the brief never says whether rejected and spam attempts are written to the table; if they are, the limiter is an unbounded bot-driven write amplifier on the same table the admin UI queries.
*Close it:* Cloudflare `CF-Connecting-IP` docs, the hosts that restore `REMOTE_ADDR` (Cloudflare mod_remoteip, WP Engine, Pressable), how Akismet and Fluent Forms resolve client IP, and a decision on persisting rejected submissions.

**9. Form-definition versioning versus entry rendering is a hole the core premise creates and the brief never names.**
Definitions live in git, entries live in the DB. An entry captured against `contact@v1` is later rendered, exported, searched and replayed against whatever the file says today. Rename a field handle and the entry's `field_handle` rows are orphaned; delete a field and the export loses a column with no record it existed. Gravity Forms solved this with form revisions, which the brief cites exactly once, for the consent hash only. There is also no handle-rename story despite `form_handle VARCHAR(64)` being the join key in both tables.
*Close it:* docs.gravityforms.com form revisions, WooCommerce order line-item snapshotting, and Stripe's per-endpoint API version pinning as the webhook-payload precedent. Decide whether the entry row stores a definition snapshot or hash.

**10. Conditional logic is a headline feature with zero primary sourcing on semantics.**
§6.3.3 asserts "one condition AST" spanning fields, steps, submit button and actions; §8.3.2 forbids CSS-driven visibility. Unresearched: how one AST is evaluated in both PHP and JS without shipping two engines that drift; whether hidden fields are validated, stored, or nulled server-side; how a `required` field inside a hidden container interacts with native constraint validation (browsers refuse to submit and report "An invalid form control is not focusable", a real and common bug); whether hiding is the `hidden` attribute, `display:none` or DOM removal, which decides whether the honeypot and multi-step survive with the stylesheet removed as §10.2.9 demands. This is the most complex subsystem in the plugin and it has one paragraph.
*Close it:* the GF conditional-logic object docs and Fluent's `ConditionAssesor` (both in the source list, read for storage shape but not semantics), the HTML spec on barred-from-constraint-validation controls, and how competitors handle "clear values on hide".

**11. File uploads have hardening but no submission flow.**
Missing: multipart over `register_rest_route` (REST arg schemas do not validate `$_FILES`); upload-before-submit versus upload-on-submit and orphan cleanup; the `post_max_size` failure where an oversized POST arrives with an empty `$_POST`, no PHP error, a missing token and a blank rejection; chunked uploads; how a stored file survives async action retries; how uploads are handled by the GDPR eraser. Also, "store outside the webroot" is unimplementable on WP VIP (read-only filesystem outside uploads, cited in the source list but never stated), on several managed hosts, and in Playground.
*Close it:* `wp_handle_upload`/`_wp_handle_upload`, docs.wpvip.com filesystem docs, and CF7's `includes/file.php` for what the cited hardening actually does.

**12. The flagship demo channel and the storage layer were designed by different sections and never introduced.**
§9.1 makes Playground PR previews "the highest-leverage addition" and the demo channel for a form plugin with a live backend. §5.2 mandates custom tables, raw `ALTER TABLE`, `SHOW INDEX` introspection, and `ENUM`, `JSON` and `LONGTEXT` columns. Playground runs SQLite through the SQLite Database Integration translator, which supports a subset of MySQL. If the migration routine or the schema does not translate, the demo is a fatal error on activation. Related and also unchecked: WordPress's minimum MySQL/MariaDB version against a native `JSON` column type (MariaDB aliases it to LONGTEXT; older MySQL has no JSON type at all).
*Close it:* `WP_SQLite_Translator` supported syntax and the WordPress/sqlite-database-integration issue tracker for `SHOW INDEX`, `ALTER TABLE` and `ENUM`; a `wp-playground/cli` smoke run of the activation path; and WordPress's stated MySQL minimum versus the JSON type.

**13. No European Accessibility Act angle, in a document written for a Dutch market.**
WCAG 2.2 AA is claim #5 (and the coding-standards mandate checks out verbatim: "Code integrated into the WordPress ecosystem, including WordPress core, WordPress.org websites, and official plugins, is expected to conform to WCAG version 2.2, at level AA"). But GDPR is the only regulation researched. The EAA has been in application since 28 June 2025 and, with EN 301 549, is the reason an EU client will pay for an accessible form. It also implies deliverables the brief does not list: a conformance claim and an accessibility statement. Separately, there is no automated a11y gate in the CI plan despite a11y being a headline claim.
*Close it:* Directive (EU) 2019/882 on EUR-Lex, EN 301 549 v3.2.1 clauses 9 and 11, the Dutch implementation, and axe-core or pa11y run against the Playground preview in CI.

**14. No client-side event contract, which is a migration blocker for the exact audience being targeted.**
Every agency wires GA4, GTM or Meta events to form submits. CF7's `wpcf7mailsent` DOM event and GF's `gform_confirmation_loaded` jQuery event are what those integrations bind to. The brief bans jQuery (correctly) and then specifies no replacement: no `devform:submit`/`devform:success`/`devform:error` events, no documented JS API, no dataLayer guidance. One paragraph of design, but it has to exist before the enhancement layer is written.
*Close it:* CF7's DOM-events doc, GF's `gform_confirmation_loaded`, and GA4 recommended-event naming.

**15. The JSON authoring path has no translation story, contradicting §8.5.**
§8.5 says "labels, placeholders, errors and notification bodies become ordinary `__()` calls handled by normal .po/.mo tooling with no addon". §8.2.1 offers auto-discovery of `forms/*.json`. JSON cannot call `__()`. So half the authoring surface has no i18n path, and the brief never says whose text domain a theme-defined form's strings belong to (the theme's or Devform's), which decides whether the POT-freshness CI job in §9.1 can even see them.
*Close it:* how ACF handles Local JSON label translation, `load_theme_textdomain` scoping, and whether the JSON path needs a `gettext_context`/domain key per string.

---

## P2. Competitors and prior art missing

**16. Jetpack Forms is absent, and it falsifies two executive-summary claims.**
Verified via `api.wordpress.org`: Jetpack has 3,000,000 active installs and ships a free Form block with locally stored responses plus free Akismet filtering ("Contact form: Easily build unlimited contact forms for free without any coding"; "Anti-spam (Powered by Akismet) blocks spam comments for Jetpack forms, Contact Form 7, Ninja Forms, Gravity Forms, and more"). Consequences: §1.1's "the market is 12 mainstream plugins sharing one architecture: form defined in an admin drag-drop builder" is wrong (Jetpack's form is block-authored, and the brief's own table includes HTML Forms, an admin textarea); §1.3's "the two largest incumbents store nothing" reads as "free form plugins do not store", which Jetpack disproves at 3M installs; and §10.1's claim 2 ("your data stays in your database at every tier") loses its uniqueness. Automattic has also been spinning Forms out as a standalone product.
*Close it:* wordpress.org/plugins/jetpack/, the Jetpack Forms responses docs, the `feedback` CPT in the Jetpack source, and jetpack.com/blog on the standalone.

**17. The block-plugin cohort is absent, and it is the direct substitute for the `devform/form` block.**
Kadence Blocks: 600,000 active installs, ships an Advanced Form block ("easily create a contact or marketing form and style it within the block editor"), verified via the plugin API. Also missing: Spectra/Ultimate Addons, Essential Blocks, Otter Blocks, GenerateBlocks Pro, Stackable. A block-theme developer in 2026 reaches for these before CF7.
*Close it:* the wordpress.org pages for each, plus each product's entry-storage documentation.

**18. Page-builder forms are absent, and they are the stated audience's actual incumbent.**
Devform targets "theme builders and custom-built sites". That population uses Bricks, Oxygen, Breakdance, Elementor Pro and Beaver Themer, all of which ship a form element with an actions-after-submit list, which is the same concept as the action pipeline, sometimes including webhooks. The only acknowledgement is one table row telling the reader to "deduct [MetForm] from the addressable market", which silently deletes the largest slice of the audience with no sizing.
*Close it:* Bricks form-element docs (its action list and webhook support), Elementor Pro "Actions After Submit", Breakdance form docs, and a BuiltWith or W3Techs builder-share figure to size what is being deducted.

**19. ACF is copied as a pattern without researching its failure modes, and never credited as prior art.**
`acf_form()` plus Local JSON is the closest existing "definition in the repo, values in the DB" system in WordPress, at millions of installs and a decade of documented pain: sync conflicts, "field group out of sync", JSON in child themes, ordering churn in diffs. §8.2.2 says "Copy ACF Local JSON exactly" citing only the marketing page.
*Close it:* support.advancedcustomfields.com threads and the ACF GitHub issues on JSON sync conflicts, plus the stated rationale for ACF 6.2's `/key=`, `/name=`, `/type=` filter variants (they exist because the single-filter model broke for people).

**20. Drupal Webform and Drupal config management are the strongest prior art for "forms as files" and appear nowhere.**
Drupal has shipped exportable YAML form config, handler (action) config, conditional "states" logic and submission storage in a git workflow for over a decade, with the config-in-code/content-in-DB split that is exactly Devform's premise, and with well-documented failure modes. Also missing as design models: Craft's Formie, Kirby blueprints, Laravel/Filament schema-driven forms, and Symfony's Form component for the field-type base class.
*Close it:* drupal.org/project/webform handler and config-export docs, plus Drupal's configuration-management docs on what breaks when config and content diverge.

**21. Developer opinion was never gathered from anywhere developers actually talk.**
The brief concedes Reddit, G2 and Trustpilot were blocked and that section 3 is "wordpress.org-heavy". But the audience thesis (developers want files in git) rests entirely on 1-star reviews mostly written by non-developers complaining about paywalls. Untried and reachable: r/ProWordPress, Post Status, WP Tavern comment threads, the Advanced WordPress group, the Bricks and Breakdance community forums, Hacker News, and the Make WordPress Slack archives.
*Close it:* site-scoped searches such as `site:reddit.com/r/ProWordPress form plugin`, `site:news.ycombinator.com wordpress forms`, `site:wptavern.com form plugin`, plus the Bricks forum's form-webhook threads.

---

## P3. Internal contradictions and numbers that will be quoted

**22. The plugin count does not survive its own table.** §1 says "12 mainstream plugins" and "not one of the twelve"; the §2 table has 16 rows; §2 note 4 says "only four of twelve have plugin source on a public GitHub repo" while note 3 enumerates more than twelve. Pick a denominator and state the inclusion rule.

**23. The 1.9M CF7 storage-addon figure is four install counts added as if disjoint.** Verified: Flamingo 800k, CFDB7 600k (both from `api.wordpress.org/plugins/info/1.2/`). Adding 800k+600k+300k+200k and then offering "8 to 19 percent depending on overlap" is an 11-point range that means the number is unusable. Either measure overlap or state the ceiling only.

**24. §1.8 versus §6.2.4 undercut each other.** "Nobody in WordPress does signed, retried, logged delivery" sits alongside Flow Systems Webhook Actions, which "ships the full pipeline free (persistent queue, exponential backoff, delivery logs with replay, X-Webhook-Id idempotency)" at fewer than 10 installs. That is the identical evidence pattern the brief calls "the highest-value question in the whole brief" for HXFE, and it is never applied to the pipeline differentiator. The market has already been offered free reliable delivery and did not install it.

**25. "Zero CSS" is contradicted three bullets later.** §1.7 and §8.3.1 lead with shipping no CSS; §8.3.3 to §8.3.5 then specify `@layer devform`, a two-mode stylesheet copying @tailwindcss/forms, and rules for focus rings, labels, checkbox, radio, error and hint text. The 6.9 article confirms core covers none of those. Devform ships CSS. Say how much, and lead with "no CSS you have to override" instead.

**26. §5.3's token window is self-contradictory and its security property is never stated.** "Valid for a window (say 3 hours from issue, unbounded upper age rejected)" is incoherent as written. More importantly, a deterministic per-form, per-time-bucket token binds to no visitor and no session, so it is a replay and timing signal, not CSRF protection and not a bot cost: a bot fetches the page once and reuses the token for the whole window. That is a defensible position (so is `wp-comments-post.php`), but the brief presents it as replacing the nonce without stating what is given up, and §5.8.3 only frames it as a questionnaire problem.

**27. §1.8 compresses the Gravity Forms retry claim into something misleading.** I confirmed `gform_max_async_feed_attempts` defaults to 1, but it is the async *feed* attempt limit, not a webhook retry setting. §6.1's table has it right; the executive summary, which is what gets quoted, does not.

**28. The Plugin Check CI recipe prescribes values the action metadata does not document.** I fetched `plugin-check-action/main/action.yml`: the inputs list is `repo-token, build-dir, checks, exclude-checks, categories, exclude-files, exclude-directories, ignore-codes, ignore-warnings, ignore-errors, include-experimental, wp-version, severity, error-severity, warning-severity, slug, strict`. Nothing there documents valid category values or the PR-comment behaviour attributed to v1.1.5+. An invalid category string fails the job.
*Close it:* the `Check_Categories` constants in WordPress/plugin-check and the action's release notes for the comment feature.

**29. Two flagged-unverified claims are load-bearing and one is stated as fact anyway.** GF's Repeater status holds for now (the doc still reads "released with Gravity Forms 2.4... currently in BETA. There is no Form Editor UI component built for this field yet", with 15 listed limitations), but verify against the 2.9.x changelog since the whole v2 headline rests on it. Formidable's free per-field Custom HTML is still source-view only and §10.1's claim 3 positions directly against it. And §1.4 states "CF7 is publicly frozen at v6.2" as fact while §11.1.2 says the freeze is secondary-sourced from a WPForms-owned publication and that 6.2 has not shipped.

**30. Nothing on uninstall, data export or exit.** For a plugin whose pitch is "your data stays in your database", there is no `uninstall.php` policy for custom tables (drop and lose data, or keep and get a review complaint), no whole-form entry export/import format, and no documented migration path off Devform. The exit story is product surface for this positioning.

**31. Rejecting `WP_List_Table` has an unpriced consequence.** Building the entry admin on custom REST routes means a JS admin app, which presupposes the npm build step that §11.5 still lists as an open question, contradicts the "no jQuery, minimal assets" ethos, and decides §9.1's one-versus-three Playground workflow split. Several recommendations already assume an answer to a question the document defers.