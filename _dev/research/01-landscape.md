# Devform: research brief

A developer-first WordPress form plugin for theme builders and custom-built sites.
Compiled 19 August 2026 from 9 parallel research passes. Every claim carries a source URL.
Claims the researcher could not verify at the primary source are labelled `unverified` inline.

---

## 1. Executive summary

1. The market is 12 mainstream plugins sharing one architecture: form defined in an admin drag-drop builder, definition stored in the database, rendered server-side inside the vendor's own wrapper divs.
2. Not one of the twelve, paid tiers included, lets you define a form as a file in your repo. That is the single unclaimed position.
3. The two largest incumbents store nothing. Contact Form 7 (10M installs) writes no submissions by default; WPForms Lite (5M installs) ships entries to `wpformsliteconnect.com` and keeps none locally. Roughly 1.9M CF7 sites have bolted on a storage add-on (Flamingo 800k, CFDB7 600k, CF7 Apps 300k, Redirection for CF7 200k), which is about 8 to 19 percent of the base depending on overlap.
4. CF7 is publicly frozen at v6.2 with a successor targeted at 2028, and carries the worst satisfaction in the cohort (4.00 stars, 18.6 percent one-star, 35 percent support resolution).
5. Markup control is not novel: Formidable ships per-field HTML templates free, Ninja Forms supports theme template overrides. Both keep those templates in the database, not in git.
6. Behaviour-in-CSS is the shared architectural bet, and both leaders admit in their own docs that disabling their stylesheet breaks pagination, conditional logic and the honeypot.
7. WordPress 6.9 added `styles.elements.textInput` and `styles.elements.select` to theme.json, which style bare inputs globally. Shipping zero CSS is now a feature, not a gap.
8. The post-submit action stack is where the market is weakest: Gravity Forms webhooks are unsigned with a documented default of 1 retry attempt, and nobody in WordPress does signed, retried, logged delivery. Basin, Tally and Formspree all do.
9. Security scar tissue is public and repetitive: PHP object injection from `unserialize()` on entry meta (four cases at CVSS 8.1 to 9.8 in 18 months), CSV injection on export, unauthenticated CSV export, IDOR on entry lookup, stored XSS from trusting `X-Forwarded-For`.
10. Sobering counter-evidence: two plugins already ship almost exactly this concept (HXFE Code-First Forms, Promptless Forms), both under 10 active installs and zero reviews. The idea is not the hard part. Distribution is.

---

## 2. Competitive landscape

| Plugin | Installs | Licence | Form defined as | Front-end output | Storage | Action model | Developer control of markup |
|---|---|---|---|---|---|---|---|
| Contact Form 7 | 10M+, 4.00 stars, 18.6% 1-star | GPLv2+ | Text template with form-tags (`[text* your-name]`) in `post_content` of `wpcf7_contact_form` CPT | Shortcode; REST `contact-form-7/v1/.../feedback` | None. Flamingo (800k installs) is a separate plugin | Two mail templates (Mail, Mail 2) plus PHP hooks. No stack | Best in class inside `<form>`, but hard-wraps two outer divs and a `.wpcf7-form-control-wrap` span per field |
| WPForms Lite / Pro | 5M+, 4.80 | GPLv2+ | Builder, JSON in `post_content` of private `wpforms` CPT | Shortcode `[wpforms id]`, block `wpforms/form-selector` | Lite: none locally, encrypted POST to `wpformsliteconnect.com`, 1-year retention. Pro: `wpforms_entries`, `_entry_fields`, `_entry_meta` | Per-addon "connections" with conditional logic. Webhook "Secret" is a generated hash, algorithm undocumented | None. `disable-css` level 3 documented as breaking multi-page and layouts |
| Gravity Forms | not on .org, $59 to $259/yr renewal | commercial | Builder; `GFAPI::add_form()` still writes to DB | Shortcode, block, PHP function (needs `gravity_form_enqueue_scripts` before `wp_head`) | `gf_entry` + `gf_entry_meta` EAV, 12 tables | Feed framework, `gf_addon_feed` rows, `feed_order` scoped per add-on only. Async default only since 2.9.32 | CSS custom-property API only. `gform_field_content` is string surgery. `gform_disable_css` documented as breaking honeypot, paging, conditional logic |
| Fluent Forms | 700k+, 4.80 | GPLv2+ | Builder, JSON in `fluentform_forms` | Shortcode, block `fluentfom/guten-block` (vendor typo) | 13 custom tables, zero CPTs. `fluentform_entry_details` has no index beyond PK | Feeds as rows in `fluentform_form_meta`, order is insert order. Best observability in WP: API Logs, per-entry request/response, bulk replay | Server-rendered, `fluentform/form_class` and `html_attributes` filters. No per-field template |
| Ninja Forms | 600k+, 4.40, 10.8% 1-star | GPLv2+ | Builder, 9 custom `nf3_*` tables | Shortcode `[ninja_form id]`, block, `ninja_forms_display_form()` | Submissions as `nf_sub` CPT, one postmeta row per field | Best ordering model in WP: timing (Early/Normal/Late) plus numeric priority, `NF_Abstracts_Action::process()` | Only WP-idiomatic theme overrides: `yourtheme/ninja-forms/templates/`. Cost: JS-only render, SSR payload is a spinner |
| Forminator | 600k+, 4.80 | GPLv3 | Builder, `forminator_forms` CPT | Shortcode, block `forminator/forms` | `frmt_form_entry` + `frmt_form_entry_meta` | Free webhook action (the only free one in the cohort) plus 12 integrations | Design-token driven, `data-design` attributes, a `none` design exists. Removed `Forminator_Addon_Abstract` in 1.39.1 and fatal-errored third-party integrations |
| Formidable Forms | 300k+, 4.80 | GPLv2 | Builder, `frm_forms` + `frm_fields` | Shortcode, block, `FrmFormsController::show_form()` | 7 custom `frm_*` tables | Actions as hidden `frm_form_actions` CPT with JSON in `post_content`, keyed on create/update/draft/import events. 27 classes, 20 Pro-only including the generic HTTP action | Strongest incumbent: free per-field Custom HTML textarea plus `before_html`, `after_html`, `submit_html`, and an `include_form_tag` flag that suppresses `<form>`. Templates live in the DB |
| Everest Forms | 90k+, 4.90 | GPLv3 | Builder, `everest_form` CPT | Shortcode, block.json blocks | `evf_entries` + `evf_entrymeta` | WPForms' hook chain cloned priority-for-priority | Same as WPForms. Architecturally a WPForms clone |
| Bit Form | 10k, 5.00 | GPLv2+ | Builder, `bitforms_form` + `bitforms` CPT | Shortcode, block | `bitforms_form_entries` + `_entrymeta` | Closest to Devform's idea: `bitforms_workflows` table with `workflow_type`, `workflow_condition`, `workflow_action`, three trigger points | Compiled JS bundle, per-form CSS injected inline |
| HappyForms | 20k, 4.40, 11.8% 1-star | GPLv2+ | Builder inside the Customizer (hidden by default on block themes) | Shortcode, block | `happyform` and `happyforms-message` CPTs | Three hooks. Free integrations: reCAPTCHA only | Per-field template filter `happyforms_part_frontend_template_path_{$type}` |
| MetForm | 600k+, 4.70 | GPLv3 | Elementor widgets | Elementor only | `metform-form` and `metform-entry` CPTs | n/a | Elementor's markup. Deduct from the addressable market |
| Kali Forms | 10k, 4.80 | GPLv3 | Builder, `kaliforms_forms` CPT | Shortcode, block | `kaliforms_submitted` CPT registered `public => true` with archive and rewrite slug | n/a | Ships Bootstrap grid, wraps every form in `.bootstrap-wrapper`, opt-out only |
| HTML Forms | 10k, 4.90 | GPL | HTML written in a wp-admin textarea | Shortcode | own tables | 2 actions, 4 filters | Full, but the HTML lives in the DB not the repo |
| WS Form LITE | 10k, 5.00 | GPL | Builder | Shortcode, block | own tables | Conditional actions with a third mode: "Run Immediately", decoupled from submit | Bootstrap 3-5 and Foundation output modes |
| HXFE Code-First Forms | <10, no reviews | GPL | PHP arrays via `hxfe_schemas` filter | Shortcode `[hxfe_form id]` | own | email, webhooks, conditional HTML and redirect rules, `hxfe_after_submit` | full |
| Promptless Forms | <10, no reviews | GPL | JSON in admin UI or PHP | shortcode | own | webhooks with HMAC-SHA256 signing, opt-in REST "Connector" via Application Passwords | full |

Cross-cutting facts:

1. Contact Form 7 is the only mainstream plugin whose front-end submits over the WP REST API. WPForms, Fluent Forms, Ninja Forms, Forminator and Bit Form all POST to `admin-ajax.php`.
2. Gravity Forms is the only product with a full public REST namespace (`gf/v2`, since GF 2.4).
3. Four plugins (WPForms, Ninja Forms, Fluent Forms, Everest Forms) already register WP Abilities API abilities. Ninja Forms exposes `ninjaforms/add-action`, `update-action`, `delete-action`, `list-actions` to agents. Formidable, Forminator, HappyForms, Bit Form, MetForm and Kali Forms have zero.
4. Only four of twelve have plugin source on a public GitHub repo. Licences split GPLv2-or-later (CF7, WPForms, Ninja, Fluent, Bit Form, HappyForms) vs GPLv3 (Forminator, Everest, MetForm, Kali). Pick GPLv2-or-later to keep code flow open in both directions.

---

## 3. What users actually complain about

Ranked by frequency across wordpress.org review corpora, support threads and Capterra. Reddit was unreachable to the research tooling, so this is wordpress.org-heavy; that is also the most primary source available.

### 3.1 Submissions vanish (highest-frequency structural complaint)

WPForms' own Lite-vs-Pro page lists "Form entry storage in WordPress" as Pro-exclusive. In-product string from `src/Lite/Admin/Education/Admin/DidYouKnow.php`: "Entries are not stored in WPForms Lite". Flamingo's plugin page states plainly: "Flamingo is a message storage plugin originally created for Contact Form 7, which doesn't store submitted messages."

- zippomode, 1-star: "webform plagin who doesnt store submissions"
- tjaskren: "I wanted to try the forms before I went pro. Unfortunately, they hid the entries from me until I paid the minimum $50."
- Support thread title: "WTF? Lite doesn't save the form data!?", calling the upgrade prompts "ransomware-style"

Sources: https://wpforms.com/wpforms-lite-vs-pro/ , https://wordpress.org/plugins/flamingo/ , https://wordpress.org/support/topic/wtf-lite-doesnt-save-the-form-data/ , https://wordpress.org/support/topic/the-most-basic-function-is-pro/

### 3.2 Paywalls on table-stakes fields

Roughly 13 of the 30 most recent WPForms Lite 1-star titles are paywall complaints. The specific triggers name the fields:

- mdaxx: "$50+ a year just for the ability to add a phone number field to a contact form?"
- Fluent Forms 1-star, SethO: "Pay just to add a phone number field?"
- Ninja Forms 1-star: "Worst form plugin ever – every function is a extra addon you must buy"
- Ninja Forms: "Basically no default styles and anything more is $49"
- Forminator: "Only free if you want to pay for a support plan??"

Sources: https://wordpress.org/support/plugin/wpforms-lite/reviews/?filter=1 , https://wordpress.org/support/plugin/fluentform/reviews/?filter=1 , https://wordpress.org/support/plugin/ninja-forms/reviews/?filter=1

### 3.3 Email is the storage layer, so every wp_mail failure is data loss

- WPForms: "Does not actually send the forms" (amandapredway), "All messages are ending up in the SPAM folder" (dylesid), "Could not receive leads" (darrenw1designer)
- makigo, 2-star: "Test-Mails kommen an, aber keine Kontaktanfragen" (test mails arrive, contact requests do not)
- CF7: "Sadly, no emails received (due to the rigid rules)" (TKO)

Sources: https://wordpress.org/support/plugin/wpforms-lite/reviews/?filter=1 , https://wordpress.org/support/plugin/contact-form-7/reviews/?filter=1 , https://wordpress.org/support/topic/contact-form-7-not-sending-emails-17/

### 3.4 Post-submit routing is unmet and paywalled

- CF7 forum thread opened 19 August 2026 by hongsingh: "Tool required to send data to CRM directly". Zero replies.
- WPMU DEV on record: "this API is not a REST API. It's a development/cod API" and "Forminator doesn't add/expose any custom REST API."
- agenturvs, 1-star on Forminator's "1000 integrations" claim: "Best joke ever! Since when is a 'webhook' the same as '1000 integrations'? What you're doing is pure scamming!" WPMU DEV conceded the claim "mainly refers to the webhook functionality".
- Redirection for CF7 (200k installs) gates webhooks, conditional submission actions and every CRM behind PRO.

Sources: https://wordpress.org/support/topic/tool-required-to-send-data-to-crm-directly/ , https://wordpress.org/support/topic/how-use-the-api-of-wordpress-plugin-forminator/ , https://wordpress.org/support/topic/a-webhook-is-not-the-same-as-1000-integrations/ , https://wordpress.org/plugins/wpcf7-redirect/

### 3.5 Weak developer extension points (the sharpest, lowest-volume cluster)

- KokoBasha, Forminator 1-star, titled "Not useful for developers, very poor API": "The API is very poor"
- Brett Ransley, Ninja Forms: "there is limited ability to extend, hook, or build apon this"
- Torsten Landsiedel (German WP contributor), Fluent Forms 1-star: "You can't use the input from one field as the default value of another"; duplicate select values are silently stripped; pro support acknowledged the problems but showed "no interest in fixing it"
- cirrus123, Fluent Forms: "If you have two of the same forms on a page, they don't worry correctly and interact with one another instead of having unique instance IDs."

Sources: https://wordpress.org/support/topic/not-useful-for-developers-very-poor-api/ , https://wordpress.org/support/topic/annoying-asf-popups-and-inability-to-hook-an-mroe/ , https://wordpress.org/support/topic/bad-support-and-not-very-flexible/ , https://wordpress.org/support/topic/such-a-poorly-constructed-plugin-hacks/

### 3.6 Bloat and site-wide asset loading

wpdynamics, Ninja Forms 1-star, written from an agency seat: "I work in a agency doing professional wordpress development... ninja forms is by far the worst solution from all of then." Charges: AngularJS 1.x admin UI, jQuery dependency, "CSS code, its a BIG BIG BIG mess", assets loaded site-wide regardless of whether a form is present, and a reported 15 to 20 percent Lighthouse improvement with the plugin disabled.

CF7 equivalent, andre1dev: "With each update, a new js file or ajax call appears to slow down the page. If only they were useful, but they are useless" (naming feedback, schema, swv/index.js, hooks.js, i18n.js).

Sources: https://wordpress.org/support/topic/absolutly-horrible-choice-please-read-why/ , https://wordpress.org/support/topic/gets-heavier-with-each-update/

### 3.7 Styling

- Ninja Forms review titled "Styling these forms is a nightmare" (Adrian): "I know CSS, but I shouldn't have to use it these days to be able to style a form!" Reply from saintjk: side-by-side fields do not reorder on a small viewport and there is no ordering control.
- Forminator, reviewplugin: styling individual elements at a fine-grained level "is not possible"; a PHP-added reset button is silently ignored, "the front end does not display it, it's blank, it ignores it."
- Gravity Forms on Capterra (4.6/5, 91 reviews): "progressive CSS knowledge to really manipulate default style sheets".

Sources: https://wordpress.org/support/topic/styling-these-forms-is-a-nightmare/ , https://wordpress.org/support/topic/needs-a-rethink-reset-button-styling-integration/ , https://www.capterra.com/p/206381/Gravity-Forms/reviews/

### 3.8 Admin nag screens

Ninja Forms: "Stupid popup prompt for rating", "bettelei nach bewertungen ist furchtbar". Redirection for CF7, michacassola: "Annoying self-ad for reviews has a bad malfunction! You cannot get rid of it and get redirected to some blank pages or 404 page." WPForms 2-star tier: "Entitled SPAM", "Pretty Spammy", "OK, but kind of sleazy up sell."

A stated no-nag policy in the readme is free differentiation.

Sources: https://wordpress.org/support/topic/why-so-many-popups/ , https://wordpress.org/support/plugin/wpcf7-redirect/reviews/?filter=1 , https://wordpress.org/support/plugin/wpforms-lite/reviews/?filter=2

### 3.9 Spam

CF7, amiens80: after installing WP Mail SMTP tracking they found "a lot of spams was send from the forms build with CT7", plus a UX gap: "we cannot disabled a form in the interface, we need to erase it from the page where it is integrated." The CF7 Honeypot/Apps plugin at 300k installs is the market's measured willingness to install a second plugin for honeypot plus database plus redirect.

Source: https://wordpress.org/support/topic/easy-simple-but-open-to-spam/ , https://wordpress.org/plugins/contact-form-7-honeypot/

### 3.10 What must not be broken

CF7's 1,471 five-star reviews name no features at all: "Simple But Most Useful", "Old but gold", "Just works perfectly", "Still Reliable", "simple and reliable", "A Plugin I've Trusted Throughout My Career", "Can't believe this is free." The thing to preserve is boring dependability. Concretely: the form must still work if every post-submit action is misconfigured, and adding the action stack must not add setup steps to the simple case.

Source: https://wordpress.org/support/plugin/contact-form-7/reviews/?filter=5

### 3.11 Support baseline (set expectations, do not promise)

Threads resolved in the last two months: CF7 14/40 (35%), Ninja Forms 9/13, Forminator 53/62, WPForms 21/24, Formidable 7/7, Fluent Forms 8/8. One-star share: CF7 18.6%, Ninja 10.8%, Formidable 3.8%, Fluent 2.7%, WPForms 2.5%, Forminator 2.1%. Fluent and Forminator bought their low one-star rates with responsive support. A solo-maintained plugin cannot match that, so lean on docs, clear error surfaces and a public issue tracker.

Sources: plugin pages at https://wordpress.org/plugins/contact-form-7/ , /wpforms-lite/ , /ninja-forms/ , /forminator/ , /formidable/ , /fluentform/

---

## 4. Field types

Field breadth is not where this market is won. Every incumbent already ships 25 to 65 types, and the free tiers vary wildly: Forminator gives away 32 field classes including repeating groups and calculations, Fluent Forms gives away 36 including address and the whole payment set, Formidable Lite is text and choice only.

HTML defines exactly 22 input type keywords. Roughly 14 of them are one line of markup each. The real work is labels, errors, autocomplete and storage.
Source: https://html.spec.whatwg.org/multipage/input.html

### 4.1 MVP set (13 types)

1. **Text** with a `type` selector mapping 1:1 to HTML5 `text`, `email`, `url`, `tel`, `number`, `password`. One field class, six behaviours, `pattern`/`minlength`/`maxlength`/`min`/`max`/`step` surfaced as settings. Justification: expose the platform primitive instead of inventing marketing names.
2. **Textarea.** Justification: second most used field after text, and the one people sanitise wrong (`sanitize_text_field()` strips newlines; use `sanitize_textarea_field()`).
3. **Select** (native `<select>`). Justification: accessible by default, zero JS, styleable by theme.json `styles.elements.select` in WP 6.9+.
4. **Radio group**, always wrapped in `<fieldset>` with `<legend>`. Justification: W3C requires it and it is the primitive that later becomes rating, NPS and Likert.
5. **Checkbox group**, same fieldset rule.
6. **Single checkbox / Consent**, storing a content hash of the consent text on the entry and invalidating consent if the text changed between form load and submit. Justification: this is the only implementation detail that makes consent evidentially useful, and only Gravity Forms does it (via form revisions).
7. **Date**: native `input[type=date]` plus a three-part fieldset alternative for known dates, with `bday-day`/`bday-month`/`bday-year` autocomplete presets. Justification: GOV.UK is explicit that a picker is wrong for dates the user already knows.
8. **File upload** with extension allow-list, magic-byte validation, UUID filenames, storage outside the webroot, capability-gated download handler. Justification: the OWASP baseline that no incumbent fully meets.
9. **Context** (one field, not a token soup): page URL, referrer, `utm_source`/`medium`/`campaign`/`term`/`content`, `gclid`, `fbclid`, user ID, IP (opt-out), user agent, first-touch vs last-touch, persisted in a cookie or sessionStorage so it survives full-page caching. Justification: the biggest concrete gap after repeater. Nobody ships UTM capture as a first-class concept and each vendor invented an incompatible dialect (`{ip}` vs `{user_ip}` vs `[_remote_ip]`).
10. **HTML/Content block.** Justification: needed for legal copy and section intros; trivial.
11. **Section heading.** Justification: grouping and document structure.
12. **Page break** (multi-step) with document-title updates, an `<ol>` step indicator carrying visually-hidden "Completed:"/"Current:" prefixes, and WCAG 2.2 SC 3.3.7 carry-forward. Justification: multi-step is Pro in Fluent Forms and a paid Fancy field in WPForms; free multi-step is a cheap, high-visibility win.
13. **Submit.**

Deliberately omitted from v1: Name and Address as composites. Ship them as presets composed of Text fields instead.

### 4.2 v2 set, in priority order

1. **Repeater container**, free, supporting file uploads, conditional logic and calculations inside it, rows stored as structured JSON, min/max rows, Rows and Blocks display modes. Justification: this is the headline. Gravity Forms' Repeater has been beta since 2.4 with "no Form Editor UI component built for this field yet" and forbids File Upload, Signature, Password, conditional logic, calculations, reordering and dynamic population. WPForms, Formidable and Fluent Forms all paywall theirs. Only Forminator ships a free repeating group.
2. **Address composite** with per-sub-field autocomplete tokens (`street-address`, `address-line1/2`, `address-level2`, `address-level1`, `postal-code`, `country-name`) and `billing`/`shipping` scope prefixes. Justification: WCAG 1.3.5 compliance that Gravity Forms' address docs never mention.
3. **Name composite** (`honorific-prefix`, `given-name`, `additional-name`, `family-name`).
4. **Phone** with a country dial-code picker built on the ARIA APG combobox pattern, storing E.164 alongside the raw input, defaulting country from site locale. Justification: GF's International format performs zero validation by its own admission, and defaulting to US is wrong in a European market.
5. **Scale primitive** with presets: star rating, numeric 1-10, NPS 0-10, emoji, Likert grid. One field, five presets. Justification: collapses three competitors' paid add-on category into a day of work on top of the radio-group primitive.
6. **Calculations** evaluated as a dependency graph, treating conditionally hidden fields as 0, locale-aware decimals. Justification: GF's documented constraints ("fields must appear before the calculation field", "must not be hidden by logic", dot decimals only) are implementation leaks presented as rules.
7. **Payment field** mounting Stripe Payment Element. Never build a credit card field. Design the multi-step engine now on the assumption the payment step is terminal and cannot be AJAX-swapped.
8. **Multi-select / combobox** per ARIA APG. Justification: GF's own accessibility checklist tells users to avoid its Multiselect and its enhanced dropdown UI.
9. **Remaining HTML5 types**: `time`, `datetime-local`, `month`, `week`, `range`, `color`. No plugin surfaces `month`, `week` or `search`.
10. **Chained select and Lookup** populated from posts, terms, users or prior entries. Justification: the Formidable Dynamic/Lookup pattern is what app-shaped forms need.
11. **Signature** with a typed-name keyboard alternative. Justification: paywalled everywhere, and no vendor documents any non-pointer path, which is a WCAG 2.1.1 failure.
12. **Rich text**, **Image choice**, **Save and resume**.

Explicitly do not build as fields: credit card, CAPTCHA, or post-creation fields. Gravity Forms' entire Post Fields group and Fluent Forms' Post & Taxonomy group exist only because those plugins predate a proper action architecture. In Devform they are post-submit actions.

### 4.3 Accessibility requirements per field (non-negotiable, enforced by the base class)

WordPress core mandates WCAG 2.2 level AA for the ecosystem including official plugins. Gravity Forms targets 2.1 AA, one version behind.
Source: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/

1. Every field emits a visible `<label for>` whose `for` exactly matches the input `id`. "Hide label" sets a visually-hidden class, it never removes the element. A field cannot render without a label.
2. `placeholder` is a separate optional attribute and never substitutes for a label. W3C: "Assistive technologies, such as screen readers, do not treat placeholder text as labels."
3. Radio and checkbox groups are always in `<fieldset>` with `<legend>`. Keep legends short; screen readers announce them inconsistently.
4. Hint text and error text are wired to the input with `aria-describedby` using stable ids.
5. `aria-invalid="true"` on failure. `required` attribute plus `aria-required` plus a visible "(required)" in the label.
6. Error text states the fix, not the failure: "Enter your name", not "Name is required" (WCAG 3.3.3 Error Suggestion, technique G177).
7. Inline error markup: `<p id="x-error"><span class="visually-hidden">Error:</span> message</p>` placed after hint and before the input, with the input's `aria-describedby` listing hint then error.
8. An error summary at the top of the form with `role="alert"`, which receives focus on render, links to each errored field id (first input for multi-input components, first option id for radio and checkbox groups), and whose text is byte-identical to the inline message. Both W3C and GOV.UK converge on this; the two details every WP form plugin gets wrong are focusing the summary and matching the text.
9. Correct `autocomplete` token by default per field type, from the fixed HTML autofill vocabulary, with a validated dropdown for overrides rather than Gravity Forms' free-text box. This is the cheapest WCAG 1.3.5 AA win available.
10. Multi-step: update `document.title` on step change ("Step 2 of 4: Shipping Address"), render the step indicator as an `<ol>` with visually-hidden state prefixes, carry information forward per SC 3.3.7 Redundant Entry.
11. Native constraint validation stays on by default. Use `setCustomValidity()` to inject the suggestion text, style with `:user-invalid` (Baseline since November 2023), not `:invalid`. Offer `novalidate` as an explicit opt-in. CF7 sets `novalidate` unconditionally; do not copy that.
12. Nothing in the plugin should need to be turned off to be accessible. Gravity Forms' own checklist tells users to disable input masks, enhanced dropdowns, rich text, multiselect and reCAPTCHA v2. That checklist is a competitive spec handed over for free.

Sources: https://www.w3.org/WAI/WCAG22/Understanding/error-identification.html , /labels-or-instructions.html , /error-suggestion.html , /identify-input-purpose.html , /status-messages.html , /redundant-entry.html , /accessible-authentication-minimum.html , https://www.w3.org/WAI/tutorials/forms/labels/ , /grouping/ , /notifications/ , /validation/ , https://design-system.service.gov.uk/components/error-summary/ , /error-message/ , /date-input/ , https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofill , https://docs.gravityforms.com/accessibility-checklist/ , https://developer.mozilla.org/en-US/docs/Web/CSS/:user-invalid

---

## 5. Submission backend

### 5.1 Endpoint: the three options and their costs

| Option | Cost | Verdict |
|---|---|---|
| `admin-post.php` | `require_once ABSPATH . 'wp-admin/includes/admin.php'` then `do_action('admin_init')`. Every plugin's `admin_init` handler runs on every anonymous contact-form POST. No schema, no typed args, no status codes. | Reject |
| `admin-ajax.php` | Lighter (no `admin_init`) but `Content-Type: text/html` by default and an unhandled action returns HTTP 200 with body `0` instead of a 404. | Reject as primary. Used by every drag-drop builder |
| `register_rest_route()` | Typed args, `validate_callback` then `sanitize_callback`, real status codes, OPTIONS-discoverable schema, `_fields` and `_envelope` for free. `permission_callback` is mandatory since WP 5.5; a public form route declares `__return_true`. | Pick |

Two REST caveats to design around:

1. `rest_send_nocache_headers` defaults to `is_user_logged_in()`, so anonymous REST responses ship with no cache-control headers. Any token-issuing GET route must force it to true or a CDN will cache the token and rebuild the exact bug you were solving.
2. Security plugins routinely kill the whole REST API via `rest_authentication_errors`, which returns before any route's `permission_callback` runs. CF7 hit this when it moved to REST in 5.0. Devform needs a non-REST fallback POST path and an admin-time warning when a hostile filter is detected.

CF7's route shape is worth copying wholesale: `POST /contact-form-7/v1/contact-forms/{id}/feedback` with `permission_callback => __return_true` for submission, capability-gated routes for CRUD, plus a published `/feedback/schema` route. The 415 content-type guard is a nice touch.

Reject a custom rewrite endpoint: rewrite rules live in one serialised option that any permalink save or migration can wipe, and the failure mode is the client's contact form silently 404ing.

Sources: https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-admin/admin-post.php , /admin-ajax.php , https://make.wordpress.org/core/2020/07/22/rest-api-changes-in-wordpress-5-5/ , https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ , https://developer.wordpress.org/reference/hooks/rest_send_nocache_headers/ , https://developer.wordpress.org/reference/hooks/rest_authentication_errors/ , https://raw.githubusercontent.com/rocklobster-in/contact-form-7/master/includes/rest-api.php , https://developer.wordpress.org/reference/functions/add_rewrite_rule/

### 5.2 Storage: pick custom tables plus JSON, not EAV and not postmeta

The three shipping models:

1. Pure EAV in custom tables (Gravity Forms). One `gf_entry` row plus one `gf_entry_meta` row per field. A 30-field application form writes 31 rows per submission. GravityKit publishes a whole guide on adding indexes because the defaults are insufficient.
2. Hybrid CPT plus custom tables (WPForms Pro, Forminator, Everest). Form config as JSON in a private CPT's `post_content` (which buys revisions for free), entries in custom tables.
3. CPT for everything (Ninja Forms submissions as `nf_sub` with one postmeta row per field, MetForm, Kali, HappyForms). 10,000 submissions times 12 fields is 120,000 postmeta rows.

WooCommerce settled this argument publicly: HPOS replaced the order CPT with `wc_orders` and friends, stable and default for new installs since WooCommerce 8.2 (October 2023), citing "dedicated tables ... and thus dedicated indexes which results in fewer read/write operations".

Recommendation:

1. Form definitions: file-first (section 8), mirrored into a private CPT only when authored in the admin, so revisions come free.
2. Entries: two custom tables. `devform_entries` (id, form_handle, status, created_at, ip_hash, user_id, source_url, referrer, user_agent, spam_reason, payload JSON) and `devform_entry_fields` (id, entry_id, form_handle, field_handle, value LONGTEXT, value_index VARCHAR(191)) for queryable search.
3. Store field values as JSON. Never `serialize()`. This one decision eliminates the entire PHP object injection class documented in section 7.
4. Index from day one: `(form_handle, created_at)`, `(form_handle, status)`, `(entry_id)`, `(form_handle, field_handle)`, and a prefix index on `value_index`. Fluent Forms' `fluentform_entry_details` has no key beyond the primary key, which is a full table scan for any field-value search.
5. Own migrations explicitly. `dbDelta` is create-only in practice: it cannot drop columns, cannot handle FOREIGN KEY, breaks on `IF NOT EXISTS`, and does not reliably add indexes to existing tables. Fluent Forms works around this today with `SHOW INDEX` introspection plus raw `ALTER TABLE`. Copy that pattern with a version option.

Sources: https://docs.gravityforms.com/database-storage-structure-reference/ , https://www.gravitykit.com/article/1051-optimizing-gravity-forms-indexes , https://developer.woocommerce.com/docs/features/high-performance-order-storage/ , https://docs.wpvip.com/databases/custom-tables/ , https://plugins.svn.wordpress.org/fluentform/trunk/database/Migrations/SubmissionDetails.php , https://developer.wordpress.org/reference/functions/dbdelta/

### 5.3 Anonymous submission auth: no nonce, use a signed token

Hard fact: WordPress nonces provide zero CSRF protection for logged-out visitors. `wp_create_nonce()` hashes `tick|action|uid|token`; for an anonymous visitor `uid` is 0 and `wp_get_session_token()` returns an empty string, so every anonymous visitor in the same tick gets a byte-identical string. Lifetime is variable between 12 and 24 hours because `wp_nonce_tick()` uses `ceil(time() / (nonce_life / 2))`, which is why cached-page failures are intermittent.

The whole market has already given up:

1. CF7: `WPCF7_VERIFY_NONCE` is defined `false` by default (since 4.9, August 2017), the field is only emitted `if ($this->nonce_is_active() and is_user_logged_in())`, and `verify_nonce()` returns true early for logged-out users. A failed nonce is a spam signal, not a rejection.
2. Fluent Forms: `validateNonce()` opens with `$shouldVerifyNonce = false;`, opt-in via the `fluentform/nonce_verify` filter.
3. WPForms: nonce-checks only logged-in users, logs a "Cross-site scripting attempt" and fails silently.
4. WordPress core: `wp-comments-post.php` contains no nonce at all. This is the argument to put in the docs when someone asks why Devform has no nonce.

Recommendation: mint a per-form token as `ts|hash_hmac('sha256', ts . '|' . $form_handle, wp_salt('devform'))` rendered into the form, valid for a window (say 3 hours from issue, unbounded upper age rejected). It is cache-safe because it is deterministic per form and time bucket, it doubles as the time-trap (reject if `now - ts < 3` seconds), and it needs no site-owner cache configuration. Every incumbent's answer is "shorten your cache lifespan" (WP Rocket's official CF7 guidance is 8 hours plus excluding the form URL), which is a support burden pushed onto the customer. Core's own pattern is token refresh over an uncached channel (`wp_refresh_heartbeat_nonces`, `wp_refresh_post_nonces`), and Trac #37569 for REST nonce refresh is still open.

For the admin entry viewer, export and delete: nonce plus capability check, always both.

Sources: https://developer.wordpress.org/reference/functions/wp_create_nonce/ , /wp_nonce_tick/ , /wp_get_session_token/ , https://developer.wordpress.org/apis/security/nonces/ , https://raw.githubusercontent.com/rocklobster-in/contact-form-7/master/wp-contact-form-7.php , https://plugins.svn.wordpress.org/fluentform/trunk/app/Services/Form/FormValidationService.php , https://plugins.svn.wordpress.org/wpforms-lite/trunk/includes/class-process.php , https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-comments-post.php , https://core.trac.wordpress.org/ticket/37569 , https://docs.wp-rocket.me/article/1495-contact-form-7-is-not-working

### 5.4 Entry management

Table stakes, identical across products: star and unread flags, view/edit/trash per entry, bulk export and trash, keyword search, date-range filter, per-entry notes, print, resend notifications, a spam view with "Empty Spam", and per-form automatic purge.

Two features that only exist if you own the record, and which are therefore Devform's pitch made concrete:

1. **Resend / replay per action**, with attempt history.
2. **A spam folder that gates the whole action stack**, not just email. Gravity Forms' rule is that spam entries never fire notifications or add-on feeds; copy that.

Build the list UI on Devform's own REST routes rather than `WP_List_Table`. Core's own reference says that class "is marked as private, developers should use this only at their own risk ... subject to change in future WordPress releases". Building on the public API also dogfoods it.

Export: stream it. Gravity Forms' official answer to failed exports is "increase your PHP memory limit and max execution time" or "make multiple smaller exports". A chunked response with keyset pagination on entry id sidesteps memory limits entirely, and never writing the file to `wp-content/uploads` closes the "residual CSV artifacts" leak class (Everest Forms < 3.5.0, CVSS 5.9).

Sources: https://wpforms.com/docs/complete-guide-to-form-entries/ , https://docs.gravityforms.com/gform_entry_is_spam/ , https://developer.wordpress.org/reference/classes/wp_list_table/ , https://docs.gravityforms.com/resolving-issues-gravity-forms-exports/ , https://wpscan.com/plugin/everest-forms/

### 5.5 GDPR

1. Register all three core privacy hooks. `wp_privacy_personal_data_exporters` (callback returns `['data' => array, 'done' => bool]`), `wp_privacy_personal_data_erasers` (returns `items_removed`, `items_retained`, `messages`, `done`), and `wp_add_privacy_policy_content()` called on `admin_init` or later. All three are page-batched, which fits keyset pagination exactly. This is roughly half a day of work and WPForms gates entry management behind Pro, so a free plugin that wires core privacy tools correctly is genuinely differentiated.
2. Per-form retention: retain indefinitely / trash after N days / delete permanently after N days. Copy Gravity Forms' model, including an Identification Field mapping entries to a data subject and per-field export/erase marking. But be honest about cron: on a low-traffic brochure site WP-Cron can silently not run, so surface last-run status in the UI instead of pretending retention is a guarantee.
3. IP: store a salted hash by default, full IP as explicit opt-in. The CJEU settled in Case C-582/14 (Breyer, 19 October 2016) that a dynamic IP is personal data for a website operator with legal means to identify the visitor. A salted hash also serves the rate limiter, so nothing is lost.
4. Never bundle Google reCAPTCHA on by default. See section 7.

Sources: https://developer.wordpress.org/reference/hooks/wp_privacy_personal_data_exporters/ , /wp_privacy_personal_data_erasers/ , https://developer.wordpress.org/reference/functions/wp_add_privacy_policy_content/ , https://docs.gravityforms.com/personal-data-settings/ , https://developer.wordpress.org/plugins/cron/ , https://curia.europa.eu/juris/liste.jsf?num=C-582/14

### 5.6 Deliverability: own the failure record, not the transport

Every major vendor ships deliverability as a separate free companion plugin: WP Mail SMTP (4M+ installs, by the WPForms company), FluentSMTP (600k+, by the Fluent Forms company), Gravity SMTP. Three independent vendors concluded SMTP belongs in another codebase, and they are right: owning transport means owning Gmail and Outlook OAuth flows and provider API churn.

Requirements:

1. Store the submission before attempting any delivery. This converts the market's worst silent failure into a recoverable one.
2. Lock the notification From address to a site-domain address and put the submitter's address in Reply-To. Make it structurally impossible to get wrong. Gravity Forms' own docs: "don't use your visitor's email as the from address; always use your site domain in the from address". `wp_mail`'s default sender is `wordpress@{host}`, which is almost never SPF-authorised for the web server's IP.
3. Hook `wp_mail_failed` and record the `WP_Error` against the entry. It is the only failure signal and it is silent unless someone listens.
4. Never infer delivery from `wp_mail()`'s boolean return. `pre_wp_mail` (WP 5.7+) lets any SMTP plugin short-circuit the function entirely.
5. Show per-entry delivery status with a resend button, and link out to an SMTP plugin from the settings screen.

Sources: https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/pluggable.php , https://developer.wordpress.org/reference/functions/wp_mail/ , https://docs.gravityforms.com/troubleshooting-notifications/ , https://wordpress.org/plugins/wp-mail-smtp/ , https://wordpress.org/plugins/fluent-smtp/

### 5.7 External access

Use capability-gated REST read routes plus core Application Passwords (WP 5.6+, Basic auth over https). Zero credential management on Devform's side, and it inherits core's revocation UI. Gravity Forms built a bespoke API key plus OAuth 1.0a system for `gf/v2`; there is no reason to repeat that. Document the exact curl invocation in the README.

Source: https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/ , https://docs.gravityforms.com/rest-api-v2/

### 5.8 Trade-offs, stated

1. Custom tables cost you `WP_Query`, core search, and every plugin that expects posts. You gain indexes, a stable schema and no postmeta bloat. Given the plugin's entire premise is a scalable submission backend, this is not a close call.
2. JSON payload plus an EAV side table duplicates data. That is deliberate: JSON is the byte-exact record for export and webhooks, the side table exists purely for admin search and filtering, and it can be rebuilt from the JSON.
3. A signed token instead of a nonce means Devform cannot claim "uses WordPress nonces" on a security questionnaire. Answer with `wp-comments-post.php`.
4. REST-first means a percentage of sites with locked-down REST APIs need the fallback POST path. That is a real support cost, and the mitigation (admin warning) is cheap.

---

## 6. The action pipeline

### 6.1 How competitors model it

| Product | Model | Ordering | Conditions | Result-based dependency | Retry | Log |
|---|---|---|---|---|---|---|
| Gravity Forms | Feed rows: `id, form_id, addon_slug, is_active, feed_order, meta, event_type`. Conditions buried at `meta/feed_condition_conditional_logic` | `feed_order` within one add-on only. Cross-add-on order is registration order, PHP-only | Per feed | Only delayed-payment, hard-wired to payment capture | `gform_max_async_feed_attempts`, default 1 | Per-feed status only since 2.9.4, stored as entry meta `feed_{id}_status` |
| Ninja Forms | `NF_Abstracts_Action::process($action_settings, $form_id, $data)` | Best in WP: timing (Early/Normal/Late) plus numeric priority. `$data['extra']` carries payload down the chain | via settings | no | no | no |
| Formidable | Actions as a hidden `frm_form_actions` CPT, JSON in `post_content`, `menu_order` = form id, `post_excerpt` = action type | `action_options['priority']` | `frm_skip_form_action` filter | no | no | no |
| Fluent Forms | Feed rows in `fluentform_form_meta`, dispatched via one filter, order is row-insert order | insert order | `ConditionAssesor` per feed | no | manual only | Best in WP: API Logs screen, per-entry request and response, bulk "Run Selected Actions" replay |
| Forminator | Integration classes, free webhook via `wp_remote_request` | none | per feed | no | no | no |
| Bit Form | `bitforms_workflows` table with `workflow_type`, `workflow_condition`, `workflow_action`, three trigger points | rows | yes | no | no | no |
| WS Form | Conditional actions with three modes including "Run Immediately" (fires when a condition becomes true, independent of submit) | undocumented | strongest in WP | no | no | no |

### 6.2 What nobody does well

1. **Cross-action ordering.** Only Ninja Forms has it. In Gravity Forms you cannot say "Slack before HubSpot" in the UI because they are different add-ons.
2. **Result-based dependency.** The only "run B after A succeeded" primitive in WordPress is Gravity Forms' delayed-payment feature, and it is bound to payment capture. Nobody has `run_if: crm.succeeded`.
3. **Signed webhooks.** Gravity Forms' Webhooks add-on sends an unsigned request and has no retry of its own. WPForms' "Secret" is documented as "will generate a hash (or unique ID) for each completed request" with no algorithm and no statement it is computed over the payload. Outside WordPress the bar is far higher:
   - Basin: `X-Basin-Signature: sha256=<hex>` HMAC-SHA256 over the raw request body ("Always compute the signature against the raw, unmodified request body bytes"), 15 retries over 24 to 28 hours with exponential backoff, full per-submission attempt log, re-fire from the log or the API. Webhooks are gated at their cheapest paid plan ($12.50/mo yearly), which is evidence people pay for reliable delivery.
   - Tally: free for all users. `Tally-Signature` is base64 of HMAC-SHA256 over the raw JSON body. 10 second timeout, retry ladder 5m, 30m, 1h, 6h, 1d, then a one-time failure email to the form owner.
   - Formspree: actions run in parallel behind a Validation node, guaranteed delivery with automatic retries and exponential backoff, on every plan including free. Its only synchronous exception is Stripe, so card declines surface as errors.
   - Netlify: JWS in `X-Webhook-Signature` (HS256, claims `iss: "netlify"` and `sha256` of the payload). More work to verify than `sha256=<hex>` for no benefit; prefer the Basin form.
4. **Retry and dead-lettering.** Third-party plugins exist purely to fill this gap. Retrigger Notifications Gravity Forms (1,000+ installs, 4.6 stars) exists because "Endpoints go down, APIs timeout, and integrations break". Gravity Wiz Feed Forge bulk-reprocesses feeds. Flow Systems Webhook Actions ships the full pipeline free (persistent queue, exponential backoff at roughly 30s, 60s, 120s, 240s, 480s capped at 1h, delivery logs with replay, `X-Webhook-Id` idempotency headers) and has fewer than 10 installs and no HMAC signing.
5. **Per-event triggers.** WPForms' n8n addon already exposes Form Submitted / Entry Marked as Spam / Payment Processed. A single "submitted" event is behind the current shape.

### 6.3 Recommended data model

Two tables. Everything that competitors bury in a serialised meta blob becomes a column.

```
devform_actions
  id                BIGINT UNSIGNED PK
  form_handle       VARCHAR(64)      -- matches the file-defined form
  action_type       VARCHAR(64)      -- 'email' | 'webhook' | 'create_post' | ...
  handle            VARCHAR(64)      -- stable, unique per form: 'notify_sales'
  position          INT UNSIGNED     -- explicit order, drag-reorderable
  mode              ENUM('parallel','sequential')  -- default parallel
  run_if            JSON NULL        -- unified condition AST, see below
  depends_on        JSON NULL        -- [{"handle":"crm","on":"succeeded"}]
  delay_seconds     INT UNSIGNED DEFAULT 0
  retry_policy      JSON NULL        -- {"max":6,"backoff":"exponential","base":30,"cap":3600}
  timeout_ms        INT UNSIGNED DEFAULT 10000
  is_active         TINYINT(1) DEFAULT 1
  config            JSON             -- action-specific settings only
  KEY (form_handle, position)

devform_action_runs
  id                BIGINT UNSIGNED PK
  entry_id          BIGINT UNSIGNED
  action_id         BIGINT UNSIGNED
  action_handle     VARCHAR(64)
  attempt           SMALLINT UNSIGNED
  status            ENUM('queued','running','succeeded','failed','skipped','dead')
  skipped_reason    VARCHAR(64) NULL  -- 'condition_false' | 'dependency_failed' | 'spam'
  started_at        DATETIME
  duration_ms       INT UNSIGNED
  http_status       SMALLINT NULL
  request           LONGTEXT NULL     -- redacted per a declared secret list
  response          LONGTEXT NULL     -- truncated
  error_code        VARCHAR(64) NULL
  error_message     TEXT NULL
  next_attempt_at   DATETIME NULL
  output            JSON NULL         -- typed, referenceable by later actions
  KEY (entry_id), KEY (action_id, status), KEY (status, next_attempt_at)
```

Semantics:

1. **Ordering is explicit and cross-action.** One `position` column per form, drag-reorderable, spanning every action type. Beats Gravity Forms outright and matches Ninja Forms without the two-dimensional timing/priority puzzle.
2. **Parallel by default, sequential opt-in.** Formspree's deliberate design: all actions fire at once so a slow CRM does not block a Slack ping. Sequencing is an explicit `depends_on` edge, which is also how you get result-based dependency for free.
3. **One condition AST** applied uniformly to fields, steps, submit button and every action. Sources are field values, context values (UTM, referrer, user), and prior action outputs (`{{ actions.crm.output.id }}`). Gravity Forms proves feed-level conditional logic is the most-used integration pattern; making it native and free beats WS Form's per-action conditions by unifying the language.
4. **Every action declares typed output.** Later actions reference earlier ones. This is Ninja Forms' `$data['extra']` idea with a schema instead of an untyped array.
5. **Retry is a policy column, not a global filter.** Default `{"max":6,"backoff":"exponential","base":30,"cap":3600}`, matching the Flow Systems ladder. 5xx and 429 retry, 4xx does not. After exhaustion the run goes `dead` and the form owner gets one email, per Tally.
6. **Spam gates the whole stack.** A spam-classified entry runs no actions, and every run row is written `skipped` with `skipped_reason = 'spam'` so it is visible rather than absent.
7. **Idempotency.** Send `X-Devform-Event-Id` and `X-Devform-Delivery-Id` on every outbound call so receivers can dedupe retries.
8. **Signing.** `X-Devform-Signature: t=<unix>,v1=<hex>` where `v1 = HMAC-SHA256(secret, "<t>.<raw_body>")`. Timestamp inside the signed material blocks replay. Publish the verification snippet in the docs; this is the single most legible "built by someone who has consumed webhooks" signal.
9. **Events, not one event.** `entry.submitted`, `entry.marked_spam`, `entry.updated`, `entry.deleted`, `payment.completed`.

### 6.4 Async substrate

Use Action Scheduler for the runner and own the retry policy yourself.

1. Action Scheduler is the only mature WP job queue, uses custom `actionscheduler_*` tables since 3.0, and has "no dependency on the WP-Cron system" (triggered by the `action_scheduler_run_queue` hook, direct runner calls, WP-CLI, and an admin shutdown check). Defaults: batch 25, 1 concurrent batch, 30 second time limit, halt at 90 percent memory.
2. It does **not** auto-retry failed actions. A queue is not a delivery guarantee. Devform's `attempt` counter and `next_attempt_at` column are the retry mechanism; Action Scheduler only wakes the runner.
3. Do not build the queue on a loopback POST to `admin-ajax.php`. That is Gravity Forms' `GF_Background_Process` (a fork of Delicious Brains' wp-background-processing), and Gravity Forms maintains a dedicated troubleshooting page for its failure modes: cURL 6, 7, 28, 35 and HTTP 401, 403, 404 from basic auth, security plugins and firewalls. Choosing Action Scheduler sidesteps that entire support category, and the troubleshooting page is a citable sales argument.
4. `fastcgi_finish_request()` is a latency optimisation, never the delivery mechanism. It is PHP-FPM only, it holds a worker for the whole job ("your PHP-FPM pool misses one worker", risk of exhausting `pm.max_children`), and calling it inside a shutdown function prevents subsequent shutdown functions firing. Enqueue durably first, then optionally kick the runner.
5. Ship `wp devform queue run` for WP-CLI and document the system crontab line. WordPress' own docs concede WP-Cron "does not run continuously, which can be an issue if there are critical tasks that must run on time".

Sources: https://docs.gravityforms.com/feed-object/ , /gffeedaddon/ , /triggering-webhooks-form-submissions/ , /gform_max_async_feed_attempts/ , /gform_is_asynchronous_notifications_enabled/ , /troubleshooting-background-issues/ , /add-delayed-payment-support-feed-add/ , https://community.gravityforms.com/t/webhooks-retry-settings/12983 , https://developer.ninjaforms.com/codex/action-timing-and-priority/ , /registering-actions/ , https://github.com/Strategy11/formidable-forms/blob/master/classes/controllers/FrmFormActionsController.php , https://developers.fluentforms.com/api/classes/integration-manager-controller/ , https://fluentforms.com/docs/fluent-form-api-logs/ , https://wpforms.com/docs/how-to-install-and-use-the-webhooks-addon-with-wpforms/ , https://wpforms.com/docs/n8n-addon/ , https://wsform.com/knowledgebase/control-actions-with-conditional-logic/ , https://docs.usebasin.com/integrations/webhooks , https://tally.so/help/webhooks , https://help.formspree.io/articles/building-your-form/getting-started-with-workflow , https://docs.netlify.com/deploy/deploy-notifications/ , https://actionscheduler.org/ , /api/ , /perf/ , https://blog.blackfire.io/fastcgi_finish_request-advantages-and-pitfalls.html , https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/ , https://wordpress.org/plugins/retrigger-notifications-gravity-forms/ , https://wordpress.org/plugins/flowsystems-webhook-actions/ , https://gravitywiz.com/gravity-forms-feed-forge/

---

## 7. Spam and security

### 7.1 Recommended anti-spam defaults

Ship on, in core, with no third-party call:

1. **Honeypot as a field-name swap**, not a hidden decoy. Antispam Bee (700k installs) renames the real field to `substr(sha1(md5('comment-id' . $salt)), 0, 10)` and leaves a decoy under the obvious name. A bot that skips hidden fields still cannot find the real one.
2. **Signed time trap.** Antispam Bee's version reads a raw `$_POST['ab_init_time']` and is trivially forgeable. Devform's issue timestamp is already inside the HMAC token from section 5.3, so back-dating is impossible. Reject if elapsed time is under 3 seconds.
3. **Rate limiting off the entries table**, copying `wp_check_comment_flood()`: query the last submission from this identity within an hour, `wp_die(..., 429)` if it is under N seconds old, expose the window and threshold through a filter mirroring `comment_flood_filter`. Do not build the limiter on transients: with a persistent object cache, a Redis flush silently disables spam protection and nobody notices until they are drowning. Fluent Forms' limiter is 5 submissions per IP per 30 seconds returning 429, implemented as a COUNT against the submissions table on an unindexed `ip` column; Devform can do the same query correctly indexed.
4. **Per-form enable/disable toggle** in the admin. A reviewer asked for exactly this: "we cannot disabled a form in the interface, we need to erase it from the page".
5. **No CAPTCHA by default.** The W3C's own note is explicit: deploy "the minimum CAPTCHA strength necessary. If a honeypot suffices, use a honeypot", and prefer "non-interactive approaches because these pose no accessibility challenges".

Adapters, inert until a key is entered:

6. **Cloudflare Turnstile** is the recommended CAPTCHA. Free (20 widgets, 10 hostnames each), works without routing traffic through Cloudflare, and is the only major CAPTCHA with a published WCAG 2.2 AA compliance claim. Verify strictly, which the market leader does not: CF7's `verify()` only tests `$response_body['success']` and never compares the returned `hostname` or `action`, and never sends `remoteip`. Devform must check `hostname` against the site host, `action` against the form handle, send `remoteip`, treat any `error-codes` entry as a hard fail, and log the reason. Tokens are single-use, max 2048 chars, valid 300 seconds. Default Pre-clearance off so the site owner can honestly claim a no-cookie configuration.
7. **Akismet**, run last in the pipeline after honeypot, time trap and rate limit have dropped the obvious junk. That turns the 500-checks-per-month Pro quota (EUR 9.95/mo for any commercial site) from a blocker into a comfortable ceiling. Integration costs nothing: gate on `Akismet::get_api_key()` and call `Akismet::http_post($query_string, 'comment-check')`. Use `comment_type=contact-form` and pass `honeypot_field_name`. Honour `X-akismet-pro-tip: discard` by never writing the row. Surface the `usage-limit` endpoint in the admin so quota burn is visible; nobody else does this.
8. **Privacy note on the Akismet adapter.** CF7 ships nearly the whole `$_SERVER` superglobal to Akismet (`array_diff_key($_SERVER, array_flip(['HTTP_COOKIE','HTTP_COOKIE2','PHP_AUTH_PW']))`). Devform sends an explicit filterable allow-list and documents exactly what leaves the site.
9. **hCaptcha and CleanTalk adapters for parity**, not recommendation. hCaptcha's free tier is the interactive image grid (passive mode is USD 99 to 139/month), which is the exact thing W3C calls an accessibility barrier. CleanTalk has no free tier but is EUR 10/year for one site with unlimited API calls, which undercuts Akismet's commercial tier by roughly 12x.
10. **reCAPTCHA: opt-in only, lazy-loaded on first field interaction, blockable by a consent gate.** Google's own FAQ concedes it "sets a necessary cookie (_GRECAPTCHA)". Austria's Bundesverwaltungsgericht (W298 2274626-1, decided 13 September 2024, published 28 November 2024) found transmitting personal data to Google via reCAPTCHA after the user declined consent unlawful (`unverified` at primary source: the GDPRhub case page was unreachable, reported via secondary coverage).
11. **Do not publish effectiveness percentages.** There is no credible public study of honeypot vs time-trap vs CAPTCHA catch rates; every figure in circulation traces to vendor marketing, including a widely-cited "2024 OWASP Automated Threats report" figure that matches no OWASP publication. Instead, ship per-check spam telemetry in the entry backend so the developer measures their own site. That is a genuine differentiator and consistent with the developer-first framing.

### 7.2 Hard security requirements, derived from other people's CVEs

Public vulnerability counts (WPScan, August 2026): Ninja Forms 73, Forminator 46, Fluent Forms 41, WPForms 21, Everest Forms 20, Database for CF7/WPForms/Elementor 20 across 60k installs, Formidable 19, Contact Form 7 only 8 across 10M installs. CF7 is safe largely because it stores nothing and deletes uploads after 60 seconds. Devform wants CF7's attack surface with a storage backend, which means the security story has to be architectural.

1. **Never `serialize()` entry data. JSON only.** This eliminates the highest-severity recurring class outright: PHP object injection from `unserialize()` on entry metadata. CVE-2026-2599 (Database for CF7 < 1.4.8, CVSS 9.8, deserialization in `download_csv`), the same plugin at < 1.4.4 (CVSS 9.8, arbitrary file deletion), Everest Forms < 3.4.4 (CVSS 9.8, "via Form Entry Metadata"), Ninja Forms < 3.11.1 (CVSS 8.1), Formidable < 4.02.01 (CVSS 9.8). Also guard the PHAR variant: any file operation on an entry-supplied path can trigger deserialization, so paths resolve against a stored allow-list, never from the request.
2. **Field configuration is server-side state keyed by form handle. Never read any part of it from the request.** Validate the submitted field set against the stored schema and reject unknown keys. This single rule kills CVE-2026-15748 (Forminator <= 1.56.1, CVSS 9.8, fixed 31 July 2026), where the sanitizer returned nested Select values unchanged and an attacker forged an upload-field configuration through a legitimate Select field.
3. **File uploads: allow-list extensions, verify with finfo/magic bytes, never a denylist.** The same Forminator CVE bypassed an exact-match dangerous-extension blocklist with pipe-alternative MIME keys. Also assume the upload directory is web-reachable and make the stored filename non-executable regardless.
4. **Store uploads outside the webroot with UUID filenames and serve them through a capability-checked PHP handler** sending `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`. Copy CF7's hardening as the floor (`.htaccess` with `Require all denied`, per-submission random subdirectory, control-character stripping in filenames, `chmod 0400`), then go further because Devform persists files where CF7 deletes them after 60 seconds. `.htaccess` does nothing on nginx or LiteSpeed. Note that `wp_check_filetype_and_ext()` only genuinely validates images.
5. **IP capture defaults to `$_SERVER['REMOTE_ADDR']` only.** Proxy headers are honoured only when the developer declares a trusted proxy list via constant or filter, and every captured value is validated with `filter_var(..., FILTER_VALIDATE_IP)` before storage and escaped at render. Three plugins shipped this wrong: CVE-2021-25080 (Contact Form Entries < 1.1.7, CVSS 7.5, unauthenticated stored XSS via a `Client-IP: <script>` header rendered in wp-admin), CVE-2024-13666 (Fluent Forms < 6.0.0), Formidable < 6.1.
6. **Every entry read and write resolves the entry first, then checks the current user's capability against that entry's form.** Never treat a key as authorisation. Fluent Forms shipped six IDOR CVEs in under a year, including CVE-2026-17567 (< 6.2.9, brute-forceable transaction hashes exposing name, email, billing address, order items and payment status) plus authorization bypasses via `table` and `form_id` parameters. Any tokenised public link (receipt, confirmation, resume-draft) uses a 128-bit random token with constant-time comparison and an expiry.
7. **Export goes through a capability check plus nonce on the export handler itself, independent of the display layer.** CVE-2026-0825 (Database for CF7 < 1.4.6) leaked all entries because "while the shortcode properly filters displayed entries by user, the CSV export handler completely bypasses this filtering". Never write export files to disk (Everest Forms < 3.5.0, "residual CSV artifacts", CVSS 5.9). Ninja Forms shipped both unauthenticated export via CSRF (< 3.8.1) and unauthenticated token generation with submission disclosure (< 3.13.3, CVSS 7.5).
8. **Default the export format to JSON, not CSV.** OWASP states plainly that the usual escaping "techniques are not reliable in Microsoft Excel after saving and re-opening the CSV file". CSV injection is a repeat cross-vendor bug (CVE-2022-3604 Contact Form Entries < 1.3.0, CVE-2022-3463 FluentForm < 4.3.13, plus CF7 Database Addon, Easy Registration Forms and weForms). If CSV is offered, prefix any cell starting with `=`, `+`, `-`, `@`, tab, CR or LF, and expose a documented filter to disable that for non-Excel consumers. "We export JSON by default because CSV injection is unfixable" is a defensible position.
9. **Webhook SSRF is not solved by `wp_safe_remote_post()`.** As of WordPress 7.0.4 the shipping `wp_http_validate_url()` blocklist covers only 127/8, 10/8, 0/8, 172.16-31 and 192.168; **169.254.169.254 is not blocked on any released version**. Only wordpress-develop trunk adds link-local, CGNAT, TEST-NET and multicast. Devform's webhook action must resolve the host itself, reject 169.254.0.0/16, ::1, fc00::/7 and 100.64.0.0/10, pin the resolved IP to close the DNS rebinding window (core resolves at validation time and the request re-resolves), set `'redirection' => 0` or re-validate every hop, force https, and treat webhook destinations as a capability-gated admin-only setting. Precedent: CVE-2024-1812, Everest Forms < 2.0.8, unauthenticated SSRF, CVSS 7.2.
10. **Sanitise per declared field type, escape late per output context.** Attach a sanitizer to each field type in the schema, filterable, so a custom field type must declare one. Escape at render, never at store, so the stored value stays byte-exact for CSV, JSON and webhook payloads. Storing escaped values is how you get double-escaped exports.
11. **wordpress.org guideline 7 makes every third-party service opt-in by directory rule**, and guideline 8 bans loading JS or CSS from third-party CDNs (fonts excepted). Structure the core plugin to make zero outbound requests; every spam service is a separately-enabled adapter, inert without a key. Turnstile's `api.js` is a documented service so it is permitted, but only once the adapter is on.
12. **Run Plugin Check in CI and pass the entire `plugin_repo` category before first submission.** Note that Plugin Check's PHPCS ruleset is deliberately narrow (prepared SQL, nonce verification, sanitised input, a handful of discouraged functions); a green Plugin Check does not mean WPCS-clean.

Sources: https://www.w3.org/TR/turingtest/ , https://plugins.svn.wordpress.org/antispam-bee/trunk/antispam_bee.php , https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/comment.php , https://developer.wordpress.org/reference/functions/set_transient/ , https://developers.cloudflare.com/turnstile/ , /plans/ , /get-started/server-side-validation/ , /frequently-asked-questions/ , https://www.cloudflare.com/turnstile-privacy-policy/ , https://plugins.svn.wordpress.org/contact-form-7/trunk/modules/turnstile/service.php , /modules/recaptcha/service.php , /modules/akismet/akismet.php , /includes/file.php , https://akismet.com/developers/detailed-docs/comment-check/ , /usage-limit/ , https://akismet.com/pricing/ , https://www.hcaptcha.com/pricing , https://cleantalk.org/price-anti-spam , https://developers.google.com/recaptcha/docs/faq , https://ppc.land/google-recaptcha-ruled-unlawful-without-consent-by-austrian-court/ , https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html , https://owasp.org/www-community/attacks/CSV_Injection , https://developer.wordpress.org/reference/functions/wp_check_filetype_and_ext/ , https://developer.wordpress.org/apis/security/sanitizing/ , /escaping/ , https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/wp-includes/http.php , https://develop.svn.wordpress.org/tags/7.0.4/src/wp-includes/http.php , https://wpscan.com/plugin/ninja-forms/ , /forminator/ , /fluentform/ , /wpforms-lite/ , /everest-forms/ , /contact-form-entries/ , /formidable/ , /contact-form-7/ , https://securityonline.info/cve-2026-15748-forminator-rce/ , https://patchstack.com/whitepaper/state-of-wordpress-security-in-2026/ , https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/ , /common-issues/ , https://github.com/WordPress/plugin-check/blob/trunk/phpcs-rulesets/plugin-check.ruleset.xml

---

## 8. Front-end authoring

### 8.1 The platform position in August 2026

1. Core still has no form block. `core/form` and `core/form-input` carry `"__experimental": true` on Gutenberg trunk and only register behind the `gutenberg-form-blocks` experiment flag. The proposal (gutenberg#44186) was opened 15 September 2022. Four years experimental is both low platform risk and evidence that block-tree form authoring is hard.
2. **WordPress 6.9 added `styles.elements.textInput` and `styles.elements.select` to theme.json.** `textInput` covers `<textarea>` and `<input>` of types email, number, password, search, text, tel, url. The dev blog is explicit that these apply globally, "including those used in third-party plugins", and warns: "Form elements in third-party plugins may override theme.json styles unless those plugins update their CSS accordingly." Core does not ship focus states, caret colour, labels, checkbox/radio or placeholder styling.
3. Block Bindings cannot be used for form fields. Its supported list is exactly seven blocks (image, heading, paragraph, button, navigation-link, navigation-submenu, post-date). Rule it out for prefilling values.
4. The Interactivity API is stable since 6.5 and can drive a submit via `data-wp-on--submit` wrapped in `withSyncEvent()`, but contains zero form or validation primitives through 7.0, and imposes a hard server/client HTML parity requirement. A claim circulating in search summaries that 6.9 added rule-based form validation to the Interactivity API is not supported by any primary source.
5. `<form>` was removed from kses-allowed post HTML in WordPress 5.0.1. Only users with `unfiltered_html` (Administrator and Editor on single site, Super Admin only on multisite) can save raw form markup in post content.
6. The Shortcode API cannot carry structure: nested same-name shortcodes fail, attribute names are lowercased, and square brackets inside attributes break the parser. A shortcode can carry a slug and nothing else.

### 8.2 Recommended authoring model

The source of truth is a version-controlled file in the theme. Everything else is a render surface over it.

1. **Registration.** `devform_register_form( 'contact', [ 'fields' => [...], 'actions' => [...] ] )` on `init`, plus auto-discovery of `forms/*.php` and `forms/*.json` in the theme. String handles only. No numeric ids, no timestamps, no `markupVersion`. Gravity Forms' form JSON is a database dump (40+ top-level keys, `"version": "2.3-rc-5"`, `"id": "14"`, per-field `"formId"`, `"pageNumber"`), which is unmergeable in git and unwritable by hand. Do the opposite.
2. **Sync.** Copy ACF Local JSON exactly: a `devform/save_json` and `devform/load_json` filter pair (with the `/key=`, `/name=`, `/type=` modifier variants ACF added in 6.2), a modified-timestamp comparison, and a Sync tab in the admin. ACF's stated benefits are Devform's pitch verbatim: it "allows for version control over your field settings" and lets "multiple devs to work on a project, use git to push/pull files, and keep all databases synchronized".
3. **Primary render: a field iterator, not a template with hooks.** `devform( 'contact' )` echoes sane defaults; `devform_get_form( 'contact' )` returns an iterable of field objects exposing `handle`, `label`, `required`, `input` (pre-rendered input HTML), `error`, `describedby`. The theme writes its own wrappers. This is Statamic's model (`{{ form:super_fans }}{{ fields }}<label>{{ display }}</label>{{ field }}{{ /fields }}{{ /form }}`), and it is the right answer because core's two form template functions demonstrate both failure modes of the alternative: `comment_form()` fires 9 actions and 8 filters and takes 35 args and theme authors still replace the whole function, while `wp_login_form()`'s three positional filters only let you inject strings between fixed markup.
4. **On-ramp: `data-devform="contact"` on hand-written `<form>` markup in a theme template**, rewritten at render time with `WP_HTML_Tag_Processor` to set `method` and `action` and inject the hidden handle and signed token. This is Netlify's model (`data-netlify="true"` plus a `name`, stripped at deploy with a hidden `form-name` injected) minus the build step, which also removes Netlify's whole class of JS-rendered-form workarounds. Use `WP_HTML_Tag_Processor`, not `WP_HTML_Processor`: the latter aborts on elements inside TABLE and on foreign content, and a form inside a table layout would make it bail. Document the kses limit up front.
5. **Editor: exactly one server-rendered `devform/form` block** with `render`, `viewScript` and `viewStyle` in block.json, plus a `variations.js` that generates one inserter entry per registered form. Core's own form block does exactly this for its comment-form and privacy-request templates. One block type to maintain, and the user picks "Contact form" from the inserter rather than "Form" then a slug dropdown.
6. **`[devform id="contact"]`** for Classic Editor and page-builder text widgets, carrying nothing but a slug.
7. **Assets enqueue on first render.** Never require a separate pre-registration hook. Gravity Forms' `gravity_form_enqueue_scripts()` "must be called before execution of wp_head", which is exactly the papercut to design away.
8. **Unique per-instance ids** so the same form renders twice on one page without interference. A Fluent Forms reviewer named this specifically.

### 8.3 Styling strategy

1. **Ship no CSS by default.** With WP 6.9 theme.json element styles, bare `<input>`, `<textarea>` and `<select>` inherit the theme automatically. The pitch is "install it and the form already looks like the site", and it is now true rather than aspirational.
2. **Behaviour lives in markup and JS, never in CSS.** Both market leaders bet the other way and both admit in their own docs that opting out breaks the product: WPForms' "No Styling" option removes all form layouts and makes every page of a multi-page form visible at once; `gform_disable_css` makes the honeypot and hidden fields visible, breaks multi-page forms, and makes conditional logic "stop working properly". A Devform stylesheet must be provably removable.
3. **If any stylesheet ships, wrap it in `@layer devform`** so both unlayered theme CSS and Tailwind utilities win without `!important`. Tailwind v4 users are structurally burned today because WP plugin CSS is unlayered and "Unlayered CSS is stronger than any layer".
4. **Two strategies, copying @tailwindcss/forms**: a `base` mode that styles bare elements, and a `class` mode that only defines `.devform-input` and friends and applies nothing until asked.
5. **Cover only what core leaves out**: focus rings, labels, checkbox and radio, error text, hint text. WP 7.1's new pseudo-state support is currently limited to Button and Navigation Link blocks, so focus styling stays Devform's job.
6. **No framework output modes.** WS Form's Bootstrap 3-5 and Foundation 5-6.4 modes are the wrong abstraction in 2026. One semantic markup contract plus class hooks.
7. The positioning precedent is Radix Primitives: "Components ship without styles, giving you complete control over the look and feel." Statamic ships no form CSS at all.

### 8.4 Progressive enhancement

1. Plain POST is the primary path. The fetch is an enhancement. Core's own form block gets this backwards (its email path is JS-only) but its success signal is worth copying: a `?wp-form-result=success` query param on the same URL, which is cache-friendly.
2. Native constraint validation stays on. CF7 sets `novalidate` unconditionally; make that an opt-in.
3. Enhancement layer via `data-wp-on--submit` wrapped in `withSyncEvent()` and `supports.interactivity: true`, kept DOM-additive rather than re-rendering fields, because of the parity requirement. In classic themes directives need an explicit `wp_interactivity_process_directives()` call in the outermost template.
4. No jQuery, and no assets enqueued on pages without a form. Both are cheap at greenfield and are the exact claims that win an agency evaluation against Ninja Forms and CF7. Publish the bytes-on-the-page number.

### 8.5 Multilingual

Code-defined forms dissolve most of this. Labels, placeholders, errors and notification bodies become ordinary `__()` calls handled by normal .po/.mo tooling with no addon. That matters because today WPML needs a dedicated "WPML Multilingual for CF7" addon and Polylang needs a third-party bridge (9,000+ installs, last updated 7 months ago) using `{curly brace}` placeholders.

For DB-authored forms, register each string with both `pll_register_string()` and `do_action('wpml_register_single_string', ...)` under a `Devform` context on the same `init` pass that loads the form. "No translation addon required" is credible and no incumbent can claim it.

Sources: https://raw.githubusercontent.com/WordPress/gutenberg/trunk/packages/block-library/src/form/block.json , /form/variations.js , /form/view.js , /form/index.php , https://github.com/WordPress/gutenberg/issues/44186 , https://developer.wordpress.org/news/2025/11/how-wordpress-6-9-gives-forms-a-theme-json-makeover/ , https://make.wordpress.org/core/2026/08/05/pseudo-and-custom-style-states-in-wordpress-7-1/ , https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/ , /block-variations/ , /block-metadata/ , https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference/ , https://make.wordpress.org/core/2025/03/24/interactivity-api-best-practices-in-6-8/ , https://developer.wordpress.org/reference/functions/wp_kses_allowed_html/ , https://wordpress.org/news/2018/12/wordpress-5-0-1-security-release/ , https://developer.wordpress.org/apis/shortcode/ , https://developer.wordpress.org/reference/classes/wp_html_tag_processor/ , /wp_html_processor/ , https://developer.wordpress.org/reference/functions/comment_form/ , /wp_login_form/ , https://www.advancedcustomfields.com/resources/local-json/ , https://statamic.dev/forms , https://docs.netlify.com/manage/forms/setup/ , https://docs.gravityforms.com/form-object/ , /gravity_form/ , /theme-framework/ , /css-api/ , /gform_disable_css/ , https://wpforms.com/docs/how-to-choose-an-include-form-styling-setting/ , https://github.com/tailwindlabs/tailwindcss/discussions/16934 , https://github.com/tailwindlabs/tailwindcss-forms , https://www.radix-ui.com/primitives/docs/overview/introduction , https://wpml.org/documentation/related-projects/using-contact-form-7-with-wpml/ , https://polylang.pro/doc/function-reference/ , https://wordpress.org/plugins/multilingual-contact-form-7-with-polylang/

---

## 9. CI and repo

The Ettic house baseline is two workflows per plugin repo (`ci.yml` and `semgrep.yml`) across magicauth and opentrust. It covers `php -l` on a PHP matrix, WordPress Plugin Check, PHPStan, version consistency, POT freshness (opentrust only), PHPUnit (magicauth only), and a weekly Semgrep SARIF upload. Four real holes: no PHPCS/WPCS anywhere, no release automation (both repos' zips were attached by hand), no `.gitattributes`, and no governance files or branch protection. Neither plugin resolves on the wordpress.org plugin API, so the SVN deploy path has never been exercised.

### 9.1 Recommended `.github` folder

**`ci.yml`** with these jobs:

1. `php-lint`: **copy from opentrust**, edit the matrix. `find ... | xargs -0 -n1 -P4 php -l` across floor..8.5. This is better than adding PHPCompatibility, whose last stable is 9.3.5 with 10.0.0 still alpha, so it would give false confidence on PHP 8.2+ syntax.
2. `plugin-check`: **copy from opentrust**, one line: `uses: wordpress/plugin-check-action@v1`. **Delete magicauth's 60-line bespoke wp-env block**: it worked around a bug upstream fixed in v1.1.7 (2026-06-15, PR #590), which replaced the URL-based bootstrap with a WP-CLI install plus a `wp cli info` smoke check inside a retry wrapper. Add the inputs the house does not use: `slug: devform`, `categories: plugin_repo,security,performance,general,accessibility`, `ignore-warnings: true` initially, and `permissions: pull-requests: write` so the action posts its report as a PR comment (v1.1.5+).
3. `phpstan`: **copy from opentrust's config shape, not magicauth's**. PHPStan ^2.2 with szepeviktor/phpstan-wordpress ^2.0 at level 6, and a separate `.phpstan-bootstrap.php` defining the plugin constants. magicauth bootstraps the real plugin file, which executes its side effects during analysis. The house is currently split across PHPStan majors (magicauth 1.x level 6, opentrust 2.x level 5); there is no standard to inherit.
4. `phpcs`: **new**. `vendor/bin/phpcs -q --report=checkstyle` against a new `phpcs.xml.dist` using `WordPress-Extra` plus `WordPress.WP.I18n` with `text_domain=devform`. Pin `wp-coding-standards/wpcs:^3.4.1` and `dealerdirect/phpcodesniffer-composer-installer:^1.2`, plus the `allow-plugins` config block or the ruleset silently does not register. **3.4.1 is a security release**: GHSA-3pwp-g2mj-5p3v / CVE-2026-45293 (CVSS 8.6) is an RCE in the `WordPress.WP.EnqueuedResourceParameters` sniff that fires when PHPCS lints untrusted PHP, which is precisely the public-PR-in-CI scenario. Affects the `WordPress` and `WordPress-Extra` rulesets. Do not let Composer pull PHP_CodeSniffer 4.x; WPCS 3.4.1 requires `^3.13.5`.
5. `phpunit`: **copy from magicauth**, including the pdo/pdo_sqlite/sqlite3 extensions and the SQLite `$wpdb` shim. That approach is right for a data-heavy plugin (magicauth's README: "atomic UPDATE-with-WHERE semantics are tested against a real database, not mocks"). **Add a second, new integration job** on wp-env for the action pipeline and REST routes, using `WP_ENV_PHP_VERSION` and `WP_ENV_CORE` env vars as WordPress/plugin-check's `php-test.yml` does.
6. `pot-freshness`: **copy from opentrust verbatim**, slug changed. The `-I 'POT-Creation-Date'` flag on the `git diff --exit-code` is what makes it work at all. **Add `wp i18n make-json`** if there are any JS strings, or the block editor strings silently stay untranslated.
7. `version-consistency`: **copy from either, then extend**. Currently compares plugin header Version, the PHP version constant and readme.txt Stable tag. Add `package.json` if there is a Node build, and add a tag-match assertion in the release workflow so `git tag v1.2.0` on a tree that says 1.1.0 fails before the SVN push rather than after.
8. **New, workflow-level**: `permissions: contents: read`, `concurrency: { group: ${{ github.workflow }}-${{ github.ref }}, cancel-in-progress: ${{ github.ref != 'refs/heads/main' }} }`, `timeout-minutes: 20`, and paths filters. The house declares none of these; WordPress/plugin-check declares all four.

**`semgrep.yml`**: **copy from both** (they are byte-identical), with two changes: the container becomes `semgrep/semgrep` (Docker Hub now labels `returntocorp/semgrep` as moved), and decide deliberately whether `|| true` stays. For a plugin storing untrusted input, gating above a severity floor is arguably worth the noise.

**`pr-preview-build.yml` + `pr-preview-publish.yml`**: **new, and the highest-leverage addition**. WordPress Playground PR previews via `WordPress/action-wp-playground-pr-preview@v3`. For a form plugin this is the demo channel: a reviewer clicks one button and lands on a working form with a live submission backend. Notes:
   - The single-workflow no-build path is `plugin-path: .` with `permissions: contents: read` plus `pull-requests: write`. It requires a **public repo** because Playground runs in the visitor's browser and needs unauthenticated download URLs.
   - Any npm build step forces the two-workflow split (`preview-build.yml@v3` on `pull_request`, `preview-publish.yml@v3` on `workflow_run` with `contents: write` and `pull-requests: write` on **both** the workflow and the job, or you get a `startup_failure` with no logs). Zips are hosted on a `ci-artifacts` prerelease, never a draft.
   - Steal WordPress/plugin-check's zip recipe: `rsync -a --delete --exclude-from='.distignore' ...` into a slug-named directory, which makes `.distignore` serve double duty as the CI zip manifest.
   - Set `landingPage` to Devform's own admin screen, not the WP dashboard.
   - `workflow_run` workflows always read their YAML from the default branch, so publish-side edits do not take effect until merged.

**`release.yml`**: **new**. On `release: types: [published]`: `composer install --no-dev --optimize-autoloader`, then `10up/action-wordpress-plugin-deploy@stable` (2.3.0) with `generate-zip: true` and `SVN_USERNAME`/`SVN_PASSWORD`, then `softprops/action-gh-release@v3` attaching `steps.deploy.outputs.zip-path`. **Do not copy WordPress/plugin-check's `deploy.yml` verbatim**: its `actions/upload-release-asset@v1` is archived (last push 2021-03-03), as is `actions/create-release`. Skip release-please: its generic updater needs an inline `x-release-please-version` annotation on the same line, which cannot safely go on readme.txt's bare `Stable tag:` header. Write a `bin/bump-version.sh` instead and keep the version-consistency job as the gate.

**`dependabot.yml`**: **copy from magicauth** (github-actions plus composer, weekly) and **add npm** if there is a build step. opentrust omits composer despite having composer dev dependencies.

**Supporting files, all new**: `.github/CODEOWNERS`, `.github/PULL_REQUEST_TEMPLATE.md`, `.github/ISSUE_TEMPLATE/{bug_report.yml,feature_request.yml,config.yml}`, `SECURITY.md`, `CONTRIBUTING.md`, `.editorconfig`, `.gitattributes` (with `export-ignore` mirroring `.distignore`, so GitHub's Download ZIP matches the wordpress.org zip), `.distignore`, `.wordpress-org/` (banner, icon, screenshots), `.wp-env.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`. For a developer-first plugin the governance files are product surface: a bug-report issue form that asks for the form definition file and the action stack does more for adoption than another CI job.

**Action pin bumps**: the house is one to three majors behind. `actions/checkout@v6` to v7.0.1, `actions/cache@v5` to v6.1.0, `actions/setup-node@v4` to v7.0.0. `shivammathur/setup-php@v2` and `github/codeql-action@v4` are current. Float major tags in read-only jobs; SHA-pin every third-party action in any job with `contents: write` or `pull-requests: write`, which is what plugin-check-action v1.1.8 and the Playground publish workflow both do.

Roughly 60 percent of the folder is a slug rename away from done. The three genuinely new pieces are PHPCS, Playground previews, and the release pipeline.

### 9.2 Version floors

`api.wordpress.org/stats/php/1.0/` current distribution: 7.4 = 17.5%, 8.0 = 4.2%, 8.1 = 11.7%, 8.2 = 24.8%, 8.3 = 24.8%, 8.4 = 8.3%, 8.5 = 2.6%. WordPress core's own minimum is PHP 7.4. An 8.1 floor (opentrust's choice) excludes about 22 percent of the base; an 8.0 floor (magicauth's) excludes about 18 percent. For comparison: CF7 declares 7.4, WPForms 7.2, Fluent Forms 7.4. For a developer-first plugin aimed at custom-built sites, 8.1 is defensible, but make it a stated decision rather than a copied default.

Sources: https://github.com/EtticDevelopment/magicauth/blob/main/.github/workflows/ci.yml , https://github.com/EtticDevelopment/opentrust/blob/main/.github/workflows/ci.yml , https://github.com/WordPress/plugin-check-action/pull/590 , https://raw.githubusercontent.com/WordPress/plugin-check-action/main/action.yml , https://github.com/WordPress/WordPress-Coding-Standards/security/advisories/GHSA-3pwp-g2mj-5p3v , https://github.com/WordPress/WordPress-Coding-Standards/blob/3.4.1/composer.json , https://raw.githubusercontent.com/WordPress/action-wp-playground-pr-preview/v3/README.md , https://make.wordpress.org/playground/2026/06/02/pr-preview-with-wordpress-playground-what-changes-in-version-3-of-the-github-action/ , https://github.com/WordPress/plugin-check/blob/trunk/.github/workflows/pr-playground-preview.yml , /php-test.yml , /php-lint.yml , https://github.com/10up/action-wordpress-plugin-deploy , https://api.github.com/repos/softprops/action-gh-release/releases/latest , https://github.com/googleapis/release-please/blob/main/docs/customizing.md , https://wordpress.org/plugins/readme.txt , https://developer.wordpress.org/cli/commands/i18n/ , https://api.wordpress.org/stats/php/1.0/ , https://hub.docker.com/v2/repositories/semgrep/semgrep/ , https://repo.packagist.org/p2/phpcompatibility/php-compatibility.json

---

## 10. Positioning

### 10.1 The pitch

**Devform is a WordPress form plugin where the form lives in your theme repo, the submissions live in your database, and the post-submit actions are an ordered, conditional, signed and retried pipeline you can read the logs of.**

Supporting claims, each checkable:

1. Your forms are files in git and deploy with your theme. No mainstream plugin, paid tiers included, does this.
2. Your data stays in your database at every tier. Aimed at WPForms Lite's 5M installs.
3. You control 100 percent of the emitted HTML, including the form element and every field wrapper, from templates in your theme rather than textareas in the database.
4. Zero CSS by default, so WordPress 6.9 theme.json element styles apply. Nothing needs to be turned off for the form to be accessible or to match your theme.
5. WCAG 2.2 AA by construction, one WCAG version ahead of Gravity Forms' stated target.
6. Webhooks signed with HMAC-SHA256 over the raw body, retried with exponential backoff, with a per-attempt log and a replay button. Standard at Basin and Tally, absent from every WordPress form plugin.
7. Free tier includes entry storage, phone, address, file upload, conditional logic, multi-step, repeater and webhooks. Those are precisely the items the 1-star reviews name.
8. No nag screens, ever. Stated in the readme.

### 10.2 Explicitly NOT building

1. **A drag-and-drop form builder as the primary authoring surface.** An admin UI that writes definition files is acceptable later; a builder that owns the definition is not.
2. **A credit card field.** Mount a provider Element (Stripe Payment Element first) and never touch PAN data.
3. **A CAPTCHA of our own**, and no CAPTCHA on by default.
4. **SMTP or email transport.** Own the failure record, link out to WP Mail SMTP or FluentSMTP. Three vendors independently put this in a separate codebase.
5. **Post-creation, user-registration and taxonomy fields.** Those are post-submit actions. Gravity Forms' Post Fields group exists only because it predates a proper action architecture.
6. **An opinionated stylesheet, a CSS framework, or per-framework markup modes.** No Bootstrap grid, no Foundation output, no design themes.
7. **jQuery, and any asset on a page without a form.**
8. **A hosted cloud service** for entries or spam scoring. Everything is self-hosted; adapters are opt-in and inert without a key.
9. **Layout-in-CSS.** Multi-step, conditional visibility and the honeypot must survive with every stylesheet removed.
10. **N-integrations marketing.** Never describe one webhook as 1,000 integrations; that specific claim is actively punished in reviews.
11. **A CPT for submissions.** Settled by WooCommerce HPOS.
12. **`serialize()` anywhere near entry data.**

---

## 11. Open questions for Nol

Research cannot decide these.

1. **Why do HXFE Code-First Forms and Promptless Forms have fewer than 10 installs each?** They ship nearly this exact concept. Neither has a review, a support thread, or any traceable discussion. It is either a distribution failure or a demand failure, and the answer changes whether Devform should be built at all. This is the highest-value question in the whole brief. Note both chose shortcodes over blocks, both put the HTML in the database or admin UI rather than the theme repo, and neither leads with local submission storage.
2. **Free versus paid, and where the line sits.** Every complaint cluster argues for a large free tier, and none of the evidence shows developers paying for a form backend specifically. Basin gates webhooks behind its cheapest paid plan; Tally gives signed retried webhooks away free. If Devform monetises, the natural line is delivery reliability (retry, logs, replay, dead-letter alerts) and the repeater, but that is a business decision.
3. **Is Devform going on wordpress.org at all?** Neither Ettic plugin is listed yet, so the SVN deploy path is unproven in the org and `release.yml` would be written blind. It also decides how hard the guideline 7 and 8 constraints bind.
4. **PHP floor.** 7.4, 8.0 or 8.1. Costs 18 to 22 percent of the installed base at the top end. The house is already split.
5. **Is there an npm build step** (block editor UI, form builder React app)? Decides whether the Playground preview is one workflow file or three, and whether dependabot needs an npm entry.
6. **How much admin UI at all?** A files-only plugin is purest and hardest to sell to the agency's client. An admin that reads files and writes files (ACF Local JSON model) is the compromise, and it doubles the surface area.
7. **Repeater free or paid?** Three of five vendors paywall it, which proves willingness to pay. Forminator gives it away. It is simultaneously the strongest free hook and the most obvious first paid feature.
8. **Support model.** Fluent Forms and Forminator bought 2 percent one-star rates with 85 to 100 percent thread resolution. A solo maintainer cannot match that. Is the answer docs plus a public issue tracker and no wordpress.org support promise?
9. **CF7 migration importer: build it or not?** The window is defined by Contactable.io's 2028 target. The CF7 tag syntax is regular and there are only about 18 tag types, so an importer is cheap, and importing Flamingo and CFDB7 entries is the other half. But it drags Devform toward the CF7 audience, which is not the theme-builder audience.
10. **Name and slug availability** on wordpress.org and the domain set. Not verified in this research.
11. **Does the org want Semgrep to be a hard gate?** Currently `|| true` in both house repos.
12. **Should Devform register WP Abilities API abilities at launch?** Four competitors already do, and Ninja Forms exposes its action stack to agents. A declarative pipeline is far easier to expose than a builder's serialised blob, so the cost is low and the "behind, not lean" risk is real.

### 11.1 Research caveats worth knowing

1. `contactform7.com` returns HTTP 403 to automated fetches. Every CF7 doc claim here comes from the GitHub mirror or the shipped source, which is more authoritative, but no vendor wording can be quoted verbatim.
2. The **CF7 feature freeze is secondary-sourced** (`unverified` at primary source). WPBeginner and the Gravity Forms blog agree on substance but differ on timing detail ("WordCamp Asia 2026" versus "WordCamp Asia in April 2026"), and WPBeginner is owned by the company that sells WPForms. The wordpress.org changelog says nothing and Stable tag is still 6.1.7, so 6.2 has not shipped.
3. **Formidable's free per-field Custom HTML editor was read from the settings view source only** (`unverified` in a live install). It is the single most important claim to verify hands-on before positioning against it.
4. **Gravity Forms' Repeater status** was read from documentation still describing it as beta with no editor UI. The whole repeater strategy rests on it. Verify against a recent changelog before committing.
5. Reddit was fully blocked to the research tooling, so r/ProWordPress and r/WordPress opinion is absent. G2 and Trustpilot returned 403. Capterra covers Gravity Forms only.
6. **WPForms' readme and its own docs disagree** about which Fancy and Payment fields are in Lite. Settle by installing before publishing any comparison.
7. Gravity Forms has no public install count and is not on wordpress.org, so any market-share framing including it needs a BuiltWith or W3Techs estimate.
8. No public dataset ranks WordPress form **field usage**. The available data (Zuko, HubSpot, Baymard) measures abandonment, not frequency.

---

## 12. Sources, grouped

**Market and install data**
https://wordpress.org/plugins/contact-form-7/ · /wpforms-lite/ · /fluentform/ · /ninja-forms/ · /forminator/ · /formidable/ · /everest-forms/ · /metform/ · /happyforms/ · /bit-form/ · /kali-forms/ · /flamingo/ · /contact-form-cfdb7/ · /contact-form-7-honeypot/ · /wpcf7-redirect/ · /html-forms/ · /ws-form/ · /hxfe-code-first-forms/ · /promptless-forms/ · https://api.wordpress.org/plugins/info/1.2/ · https://api.wordpress.org/stats/php/1.0/ · https://api.wordpress.org/stats/wordpress/1.0/ · https://api.wordpress.org/core/version-check/1.7/ · https://www.gravityforms.com/pricing/ · https://www.capterra.com/p/206381/Gravity-Forms/reviews/

**Competitive architecture (source reads)**
https://github.com/rocklobster-in/contact-form-7 (contact-form.php, submission.php, rest-api.php, file.php, formatting.php, contact-form-template.php, modules/) · https://downloads.wordpress.org/plugin/wpforms-lite.zip · /fluentform.zip · /ninja-forms.zip · /forminator.zip · /formidable.zip · /everest-forms.zip · /bit-form.zip · /metform.zip · /kali-forms.zip · /happyforms.zip · https://plugins.svn.wordpress.org/ (fluentform, wpforms-lite, formidable, forminator, contact-form-7, antispam-bee trunks) · https://github.com/Strategy11/formidable-forms · https://github.com/fluentform/fluentform · https://github.com/wpeverest/everest-forms · https://github.com/wpmudev/forminator-ui · https://github.com/bit-apps-pro/bit-form-frontend · https://github.com/wp-premium/gravityforms (class-gf-feed-addon.php, gf-background-process.php)

**Gravity Forms docs**
https://docs.gravityforms.com/ : form-fields/ · form-object/ · feed-object/ · gffeedaddon/ · what-is-a-feed/ · working-with-multiple-feeds/ · triggering-webhooks-form-submissions/ · gform_webhooks_post_request/ · gform_max_async_feed_attempts/ · gform_is_asynchronous_notifications_enabled/ · gform_is_feed_asynchronous/ · gform_after_submission/ · gform_addon_pre_process_feeds/ · gform_post_process_feed/ · add-delayed-payment-support-feed-add/ · gform_is_delayed_pre_process_feed/ · rest-api-v2/ · database-storage-structure-reference/ · upgrading-to-gravity-forms-2-3/ · personal-data-settings/ · exporting-form-entries/ · resolving-issues-gravity-forms-exports/ · troubleshooting-notifications/ · troubleshooting-background-issues/ · troubleshooting-form-submission-performance/ · theme-framework/ · css-api/ · gform_disable_css/ · gform_field_content/ · accessibility-checklist/ · accessibility-for-developers/ · repeater-field/ · list/ · consent/ · address/ · phone/ · date/ · signature/ · stripe-field/ · page-break/ · using-calculations/ · conditional-logic-object/ · using-dynamic-population/ · merge-tags-reference/ · gf_field/ · gravity_form/ · shortcodes/ · file-upload-security/ · gform_entry_is_spam/

**Other vendor docs**
https://wpforms.com/docs/ (fancy fields, payment fields, file upload, repeater, conditional logic, entries, spam entries, webhooks, activity logging, n8n addon, lite connect, form styling setting, import-export) · https://wpforms.com/wpforms-lite-vs-pro/ · https://wpforms.com/where-does-wpforms-data-go/ · https://fluentforms.com/fluent-forms-input-fields/ · https://developers.fluentforms.com/database/ · /hooks/actions/ · /api/classes/integration-manager-controller/ · https://fluentforms.com/docs/fluent-form-api-logs/ · https://formidableforms.com/knowledgebase/field-types/ · /database-schema/ · /frm_skip_form_action/ · https://developer.ninjaforms.com/codex/ (registering-actions, action-timing-and-priority, submission-processing-hooks, custom-field-templates) · https://wsform.com/knowledgebase/control-actions-with-conditional-logic/ · https://htmlformsplugin.com/kb/

**User complaints (review threads quoted in section 3)**
https://wordpress.org/support/plugin/{contact-form-7,wpforms-lite,ninja-forms,fluentform,forminator,formidable,wpcf7-redirect}/reviews/?filter={1,2,3,5} · individual threads: /topic/the-most-basic-function-is-pro/ · /topic/free-version-is-useless-99/ · /topic/wtf-lite-doesnt-save-the-form-data/ · /topic/tool-required-to-send-data-to-crm-directly/ · /topic/how-use-the-api-of-wordpress-plugin-forminator/ · /topic/a-webhook-is-not-the-same-as-1000-integrations/ · /topic/not-useful-for-developers-very-poor-api/ · /topic/absolutly-horrible-choice-please-read-why/ · /topic/gets-heavier-with-each-update/ · /topic/styling-these-forms-is-a-nightmare/ · /topic/needs-a-rethink-reset-button-styling-integration/ · /topic/why-so-many-popups/ · /topic/annoying-self-ad-for-reviews-has-a-bad-malfunction/ · /topic/easy-simple-but-open-to-spam/ · /topic/bad-support-and-not-very-flexible/ · /topic/such-a-poorly-constructed-plugin-hacks/ · /topic/contact-form-7-not-sending-emails-17/ · /topic/css-wpforms-base-styling-only/ · CF7 freeze: https://www.wpbeginner.com/news/contact-form-7-freezes-new-features-what-wordpress-users-should-do-next/ · https://www.gravityforms.com/blog/contact-form-7-feature-freeze-why-consider-gravity-forms/ · https://www.therepository.email/contact-form-7-creator-reveals-contactable-io-will-launch-as-restful-api

**Fields and accessibility**
https://html.spec.whatwg.org/multipage/input.html · /form-control-infrastructure.html#autofill · https://www.w3.org/WAI/WCAG22/Understanding/ (error-identification, labels-or-instructions, error-suggestion, identify-input-purpose, status-messages, redundant-entry, accessible-authentication-minimum) · https://www.w3.org/WAI/tutorials/forms/ (labels, grouping, instructions, validation, notifications, multi-page) · https://www.w3.org/WAI/ARIA/apg/patterns/combobox/ · https://design-system.service.gov.uk/components/ (date-input, error-summary, error-message) · https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/ · https://developer.mozilla.org/en-US/docs/Web/HTML/Guides/Constraint_validation · /Web/CSS/:user-invalid · https://wptavern.com/certain-wp-form-plugins-make-accessibility-easy

**Submission backend and WordPress internals**
https://developer.wordpress.org/rest-api/extending-the-rest-api/ (adding-custom-endpoints, routes-and-endpoints, schema) · /rest-api/using-the-rest-api/ (authentication, global-parameters) · https://make.wordpress.org/core/2020/07/22/rest-api-changes-in-wordpress-5-5/ · https://developer.wordpress.org/reference/hooks/ (rest_send_nocache_headers, rest_authentication_errors, wp_privacy_personal_data_exporters, wp_privacy_personal_data_erasers) · /reference/functions/ (wp_create_nonce, wp_nonce_tick, wp_get_session_token, dbdelta, add_rewrite_rule, wp_mail, wp_check_filetype_and_ext, wp_handle_upload, set_transient, wp_add_privacy_policy_content, wp_refresh_heartbeat_nonces, wp_refresh_post_nonces, wp_http_validate_url, wp_safe_remote_post, comment_form, wp_login_form, wp_kses_allowed_html, wp_enqueue_script_module) · /reference/classes/ (wp_list_table, wp_html_tag_processor, wp_html_processor) · /apis/security/ (nonces, sanitizing, escaping, data-validation) · /plugins/cron/ · https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/src/ (wp-admin/admin-post.php, wp-admin/admin-ajax.php, wp-comments-post.php, wp-includes/comment.php, wp-includes/pluggable.php, wp-includes/http.php) · https://develop.svn.wordpress.org/tags/7.0.4/src/wp-includes/http.php · https://core.trac.wordpress.org/ticket/37569 · https://developer.woocommerce.com/docs/features/high-performance-order-storage/ · https://docs.wpvip.com/databases/custom-tables/ · /caching/page-cache/ · https://github.com/Automattic/batcache/blob/master/advanced-cache.php · https://www.gravitykit.com/article/1051-optimizing-gravity-forms-indexes · https://gravitywiz.com/everything-you-need-to-know-about-gravity-forms-database-structure/ · https://docs.wp-rocket.me/article/1495-contact-form-7-is-not-working · https://curia.europa.eu/juris/liste.jsf?num=C-582/14

**Action pipeline, external benchmarks and queueing**
https://docs.usebasin.com/integrations/webhooks · https://usebasin.com/pricing · https://tally.so/help/webhooks · https://help.formspree.io/articles/ (getting-started-with-workflow, plugins/webhooks, plugins/plugins) · https://docs.netlify.com/ (deploy/deploy-notifications, manage/forms/setup, manage/forms/notifications) · https://forminit.com/docs/ · https://actionscheduler.org/ · /api/ · /perf/ · /faq/ · https://github.com/woocommerce/action-scheduler · https://github.com/deliciousbrains/wp-background-processing · https://blog.blackfire.io/fastcgi_finish_request-advantages-and-pitfalls.html · https://www.php.net/manual/en/function.fastcgi-finish-request.php · https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/ · https://wordpress.org/plugins/retrigger-notifications-gravity-forms/ · /flowsystems-webhook-actions/ · /wp-webhooks/ · /suretriggers/ · https://gravitywiz.com/gravity-forms-feed-forge/ · https://gravityflow.io/articles/order-gravity-forms-feeds/ · https://automatorplugin.com/knowledge-base/action-filters-conditions/ · https://www.make.com/en/help/app/gravity-forms · https://n8n.io/integrations/webhook/and/gravity-forms/

**Spam and security**
https://www.w3.org/TR/turingtest/ · https://akismet.com/developers/detailed-docs/comment-check/ · /usage-limit/ · https://akismet.com/pricing/ · https://developers.cloudflare.com/turnstile/ (index, plans, get-started/client-side-rendering, get-started/server-side-validation, reference/content-security-policy, frequently-asked-questions) · https://www.cloudflare.com/turnstile-privacy-policy/ · https://www.hcaptcha.com/pricing · https://cleantalk.org/price-anti-spam · https://developers.google.com/recaptcha/docs/faq · https://ppc.land/google-recaptcha-ruled-unlawful-without-consent-by-austrian-court/ · https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html · https://owasp.org/www-community/attacks/CSV_Injection · https://wpscan.com/plugin/ (ninja-forms, forminator, fluentform, wpforms-lite, everest-forms, contact-form-entries, formidable, contact-form-7) · https://wpscan.com/vulnerability/ (CVE-2021-25080, CVE-2022-3604, CVE-2022-3463, CVE-2026-0825, CVE-2026-2599, CVE-2026-17567, CVE-2024-1812, CVE-2020-35489) · https://securityonline.info/cve-2026-15748-forminator-rce/ · https://patchstack.com/whitepaper/state-of-wordpress-security-in-2026/ · https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/ · /common-issues/ · https://wordpress.org/plugins/plugin-check/

**Front-end authoring**
https://raw.githubusercontent.com/WordPress/gutenberg/trunk/packages/block-library/src/ (form/block.json, form/save.js, form/view.js, form/index.php, form/variations.js, form-input/block.json) · https://github.com/WordPress/gutenberg/issues/44186 · /pull/44214 · https://developer.wordpress.org/block-editor/reference-guides/block-api/ (block-metadata, block-variations, block-bindings) · /interactivity-api/ (index, api-reference, core-concepts/server-side-rendering, iapi-about) · https://make.wordpress.org/core/2025/03/24/interactivity-api-best-practices-in-6-8/ · /2025/11/12/changes-to-the-interactivity-api-in-wordpress-6-9/ · /2026/02/23/changes-to-the-interactivity-api-in-wordpress-7-0/ · https://developer.wordpress.org/news/2025/11/how-wordpress-6-9-gives-forms-a-theme-json-makeover/ · https://make.wordpress.org/core/2026/08/05/pseudo-and-custom-style-states-in-wordpress-7-1/ · https://developer.wordpress.org/apis/shortcode/ · https://wordpress.org/news/2018/12/wordpress-5-0-1-security-release/ · https://www.advancedcustomfields.com/resources/local-json/ · https://statamic.dev/forms · https://docs.netlify.com/manage/forms/setup/ · https://github.com/tailwindlabs/tailwindcss/discussions/16934 · https://github.com/tailwindlabs/tailwindcss-forms · https://www.radix-ui.com/primitives/docs/overview/introduction · https://wpml.org/documentation/related-projects/using-contact-form-7-with-wpml/ · https://wpml.org/wpml-hook/wpml_register_single_string/ · https://polylang.pro/doc/function-reference/ · https://core-forms.com/blog/forms-block-vs-shortcode/

**CI and repo**
https://github.com/EtticDevelopment/magicauth (ci.yml, semgrep.yml, dependabot.yml, composer.json, phpstan.neon.dist, phpunit.xml.dist, .distignore, README.md) · https://github.com/EtticDevelopment/opentrust (ci.yml, dependabot.yml, composer.json, phpstan.neon, .phpstan-bootstrap.php, .distignore) · https://github.com/WordPress/plugin-check (php-lint.yml, php-test.yml, deploy.yml, pr-playground-preview.yml, pr-playground-preview-publish.yml, .gitattributes, phpcs-rulesets/) · https://github.com/WordPress/plugin-check-action (action.yml, PR #590, releases) · https://github.com/WordPress/WordPress-Coding-Standards (releases, 3.4.1 composer.json, GHSA-3pwp-g2mj-5p3v) · https://raw.githubusercontent.com/WordPress/action-wp-playground-pr-preview/v3/ (action.yml, README.md) · https://make.wordpress.org/playground/2026/06/02/pr-preview-with-wordpress-playground-what-changes-in-version-3-of-the-github-action/ · https://playground.wordpress.net/blueprint-schema.json · https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli/ · https://github.com/10up/action-wordpress-plugin-deploy · https://github.com/googleapis/release-please/blob/main/docs/customizing.md · https://wordpress.org/plugins/readme.txt · https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/ · https://developer.wordpress.org/cli/commands/i18n/ · https://raw.githubusercontent.com/WordPress/gutenberg/trunk/packages/env/README.md · https://repo.packagist.org/p2/ (squizlabs/php_codesniffer, wp-coding-standards/wpcs, phpstan/phpstan, phpunit/phpunit, phpcompatibility/php-compatibility, dealerdirect/phpcodesniffer-composer-installer) · https://docs.github.com/en/rest/repos/rules · https://hub.docker.com/v2/repositories/semgrep/semgrep/
