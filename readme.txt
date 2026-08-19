=== DevForm ===
Contributors: ettic
Tags: forms, contact form, webhook, submissions, developer
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Forms and submission endpoints for developers. Own your markup, own your data, and run an ordered stack of post-submit actions.

== Description ==

DevForm is a form plugin for people who build WordPress sites by hand. You own the markup, you own the data, and you can register your own field types, validators and post-submit actions.

It works two ways.

**Form mode.** DevForm renders the form. The default markup is accessible and unstyled, so it inherits your theme. Every part of it is overridable from your own theme templates.

**Mailbox mode.** DevForm is only the endpoint. You write the HTML, or the React component, or the mobile client, and POST to DevForm. It validates, stores, runs your actions, and answers with structured field-level errors.

Either way, the submission goes to one place: your database, on your server.

= Post-submit actions =

Every form owns an ordered stack of actions. Send an email. POST a signed webhook. Redirect.

Actions run in the order you set, across every action type. One failing action does not take the others down. Every attempt is recorded with its status code and response, failures retry with backoff, and you can replay any of them by hand. No other WordPress form plugin signs a webhook, and none retries one.

= Conversion tracking, free =

Fire GA4, Google Ads, Meta Pixel and Conversions API, PostHog and a GTM dataLayer event on submission, with one event id shared across browser and server so nothing is double counted. No paid tier.

= What DevForm does not do =

Payments, multi-step wizards, calculations, e-signature, multisite and WPML are out of scope, deliberately.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/devform`, or install it through the plugin screen.
2. Activate it.
3. Register a form in your theme, or create one in the admin.

== Frequently Asked Questions ==

= Does DevForm store submissions? =

That is a per-form choice. Store them, or pass straight through to your actions and keep nothing.

= Does it send its own email? =

It calls `wp_mail()` and records what happened. Deliverability belongs to an SMTP plugin, not a form plugin.

== Changelog ==

= 0.1.0 =
* Initial scaffold.
