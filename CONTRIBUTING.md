# Contributing

## Setup

```
composer install
```

Run the checks before you open a pull request:

```
composer lint
composer analyse
composer test
```

## Standards

- WordPress-Extra coding standards, enforced in CI.
- PHPStan level 6.
- PHP 8.1 syntax floor. CI lints up to 8.5.
- Text domain is `devform` on every translatable string.

## Rules that are not negotiable

1. No `serialize()` on request data.
2. Sanitise on the way in, escape on the way out, never store escaped values.
3. Every new field type declares its own sanitiser and its own validator.
4. Every new action type declares its own config schema and its own secret list, so logs redact correctly.
5. New markup ships with the accessibility wiring already done: a real label, `aria-describedby` for hints and errors, `aria-invalid` on failure, and a fieldset with a legend for grouped controls.

## Scope

DevForm does not build payments, multi-step wizards, calculations, e-signature, multisite support or WPML integration. Open an issue to discuss anything near that line before writing code.
