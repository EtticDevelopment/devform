# DevForm

Forms and submission endpoints for developers.

Every WordPress form plugin is extensible in theory. In practice you find out which half of the form is paywalled, you find out that the markup changed under you on a Tuesday, and you find out that a webhook failed three weeks ago because nothing recorded it.

DevForm is built for the other way of working. You own the markup, down to the form element. Every field type, validator and post-submit action is something you can register yourself. And what happens after a submission is an ordered stack of actions that signs, retries, logs every attempt and lets you replay a failed one.

## Two modes

**Form mode.** DevForm renders the form. Default markup is accessible and carries almost no CSS, so it inherits your theme. Override any part of it from your theme.

**Mailbox mode.** DevForm is only the endpoint. Your own markup, your own JS app, or a decoupled front end POSTs to it. DevForm validates, stores, runs the action stack, and returns structured field-level errors.

## Status

Pre-release scaffold. Nothing is stable yet.

## Requirements

- WordPress 6.9 or newer
- PHP 8.1 or newer

## Development

```
composer install
composer lint      # PHPCS, WordPress-Extra
composer analyse   # PHPStan level 6
composer test      # PHPUnit
```

CI runs PHP lint on 8.1 through 8.5, WordPress Plugin Check, PHPCS, PHPStan, PHPUnit, Semgrep and a version consistency check. Every pull request gets a WordPress Playground preview button, so you can click through a live site running the branch.

## Licence

GPL-2.0-or-later.
