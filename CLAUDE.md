# DevForm

WordPress form plugin. Read `_dev/decisions.md` before doing anything. It holds the locked decisions and the sourced facts that overturned the obvious approach. Research briefs are in `_dev/research/`.

## Non-negotiable rules

1. **Never `serialize()` request data.** Entry values are JSON. Four CVSS 9.8 CVEs in this market came from `unserialize()` on entry meta.
2. **Field and action configuration is server-side state, keyed by form handle.** Never read any part of it from the request. That is the Forminator RCE.
3. **Resolve the entry, then check capability against that entry's form.** A key or hash is never authorisation. That is the Fluent Forms IDOR run.
4. **Sanitise on the way in, escape on the way out.** Never store escaped values, or exports double-escape.
5. **No wp-cron in a delivery path.** Attempt one runs inline behind `fastcgi_finish_request`, retries come off DevForm's own table, WP-CLI drains it.
6. **No `setcookie()` in the submission path.** It kills the page cache on some hosts and is silently dropped on others.
7. **No server-side reading of `utm_*` or `gclid`.** WP Engine strips them before PHP runs. Attribution is client-side only.
8. **Every new field type declares its own sanitiser and validator. Every new action type declares its own config schema and secret list** so run logs redact correctly.
9. **No `JSON` or `ENUM` column types.** Use `LONGTEXT` and `VARCHAR(20)`. A `JSON` column makes dbDelta emit an `ALTER TABLE` on every run forever on MariaDB. Also no generated columns and no FULLTEXT: both are silently accepted then broken under the Playground SQLite driver.
10. **A green Playground preview is not a test.** SQLite silently accepts an out-of-range ENUM, an over-length VARCHAR and invalid JSON where MySQL errors.
11. **A form is defined in exactly one place.** File or database, never both, and a handle collision is a hard error. Client edits are an overlay keyed by JSON path against a file-declared allowlist, never a second copy of the definition.
12. **Secrets are references in a definition, never values.** `@env:`, `@const:`, `@secret:`. On WP 7.0+ register through core's Connectors API rather than building a store. Never key encryption off the salts.

## Conventions

- Prefixes: `devform_`, `DEVFORM_`, `DevForm\`, REST `devform/v1`. This deliberately breaks the house `ettic_[product]_` rule, see decisions.
- Text domain `devform` on every translatable string.
- PHP 8.1 floor, WordPress 6.9 floor.
- Tabs, WordPress-Extra coding standards, PHPStan level 6.
- No em dashes anywhere, including code comments and commit messages.
- Comments: short, dry, only when the why is non-obvious. Default to none.

## Scope

Not building: payments, multi-step, calculations, e-signature, multisite, WPML. Raise these as issues, do not build them.

## Verify before asserting

Do not trust memory on WordPress APIs, plugin behaviour or vendor pricing. Every factual claim in `_dev/decisions.md` carries a source because several obvious-sounding claims turned out to be wrong, including the CF7 freeze story and the SSRF blocklist.
