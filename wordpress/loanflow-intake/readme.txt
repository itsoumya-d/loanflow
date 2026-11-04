=== Loanflow Intake ===
Contributors: itsoumya-d
Tags: loans, forms, oauth, gtm, bigquery
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Accessible loan application intake for WordPress with OAuth 2.0, GTM dataLayer events and server-side validation.

== Description ==

Loanflow Intake renders an accessible loan application form, validates it client-side and server-side, authenticates to the Loanflow API with an OAuth 2.0 client credentials grant, and pushes consent-aware events to the GTM dataLayer.

Features:

* Shortcode `[loanflow_application_form]`.
* OAuth 2.0 client credentials with token caching and 401 retry.
* Server-side validation, honeypot and per-IP rate limiting.
* GTM dataLayer events: viewed, started, submitted, validation_failed.
* No PII is pushed to the dataLayer.
* 44px touch targets, visible focus, reduced-motion support.

== Installation ==

1. Upload the `loanflow-intake` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to Settings → Loanflow Intake and enter the API base URL, OAuth client ID and secret.
4. Add `[loanflow_application_form]` to a page.

== Frequently Asked Questions ==

= Does this store applications in WordPress? =

No. The plugin is an intake client. Applications are stored by the Loanflow API.

= Does it send personal data to GTM? =

No. Only the event name, step and application reference are pushed.

== Changelog ==

= 1.0.0 =
* Initial release.
