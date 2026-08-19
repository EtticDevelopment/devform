# Security policy

## Reporting a vulnerability

Report privately through [GitHub Security Advisories](https://github.com/EtticDevelopment/devform/security/advisories/new), or email security@ettic.nl.

Do not open a public issue for a vulnerability.

## Supported versions

The latest release only, until DevForm reaches 1.0.

## Design commitments

These are architectural, not features. They exist because the same bug classes recur across every WordPress form plugin.

1. Entry data is stored as JSON. `serialize()` is never called on anything that came from a request, which removes the PHP object injection class outright.
2. Field configuration is server-side state, resolved from the form handle. No part of it is ever read from the request.
3. Every entry read and write resolves the entry first, then checks the caller's capability against that entry's form. A key is never treated as authorisation.
4. Uploads are validated against an extension allowlist and their magic bytes, stored under unguessable names, and served only through a capability-checked handler.
5. Outbound webhook targets are resolved and checked against private, link-local and loopback ranges before the request, and redirects are not followed.
6. Exports stream, and default to JSON. CSV formula injection is not reliably fixable at the escaping layer.
