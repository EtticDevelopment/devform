## What changed

<!-- One or two lines. Lead with the behaviour, not the filenames. -->

## Why

<!-- Link the issue if there is one. -->

## Checklist

- [ ] `php -l` clean, PHPCS clean, PHPStan clean
- [ ] Every new user input is sanitised on the way in and escaped on the way out
- [ ] No `serialize()` anywhere near entry data
- [ ] New field or action types declare their own sanitiser
- [ ] Strings are translatable with the `devform` text domain
- [ ] `readme.txt` changelog updated if this is user facing
