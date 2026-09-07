=== Barrierepruefung.de – Web Accessibility Checker ===
Contributors: lukasbo
Tags: accessibility, barrierefreiheit, check, bitv, bfsg, wcag
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scans this site for accessibility barriers and embeds your accessibility statement via shortcode.

== Description ==

The plugin connects your WordPress installation to an auditing service for digital
accessibility. From the admin area you can start a scan, review the open findings together with
a note on how to fix them, and output the text of your accessibility statement on a page via
shortcode or block.

Under *Tools → Accessibility* the findings of the last scan are laid out as a work list: for
each rule the severity, the success criterion, the number of occurrences, what needs to be done
and which pages are affected. Expand a rule to see its individual occurrences — page, selector,
the measured values and the HTML snippet — so you can find the spot in WordPress. Below that,
the page quota of the current billing period. You fix things in WordPress; then you start the
next scan from the same place.

Screenshots of the occurrences stay on the service's website and are reachable from the link to
the full report.

The statement is embedded **server-side** — so it is fully present without JavaScript and for
assistive technologies.

= What the plugin does not do =

It does not change your site and does not repair anything automatically. So-called
accessibility overlays do not remove barriers, regularly make things worse for people using
assistive technologies, and are not proof of conformance.

== External services ==

This plugin calls an external service whose address you enter yourself during setup (default:
barrierepruefung.de).

What is transmitted:

* the address of this site,
* the API token you created,
* the ID of your site within the service.

**No** content and **no** personal data of your visitors are transmitted. Transmission happens
when you start a scan, when you verify the domain, when the page *Tools → Accessibility* is
opened (at most every five minutes, from the cache after that), when you expand a rule to see
its occurrences, or when the statement is retrieved (at most once an hour, likewise from the
cache after that).

Privacy policy of the service: https://barrierepruefung.de/datenschutz
Terms of service: https://barrierepruefung.de/agb

== Installation ==

1. Install and activate the plugin. Open *Tools → Accessibility* — the page explains every step
   below, including where each value comes from.
2. Create an account at barrierepruefung.de, or sign in if you already have one.
3. Add this site there, using exactly the address of this WordPress installation. If a different
   address is stored, the domain cannot be verified in step 6.
4. Under *Websites → [site] → Embedding*, section *WordPress plugin and API*, choose *Create
   token*. The API token and the site ID are shown once, in that place.
5. Enter both under *Tools → Accessibility* and leave the service address as it is.
6. Verify the domain — the plugin serves the proof itself, you do not need DNS access.
7. Start a scan and insert the shortcode `[barrierefreiheitserklaerung]` on a page.

== Shortcode ==

`[barrierefreiheitserklaerung]` outputs the text of your accessibility statement, rendered on
the server. The block “Accessibility statement” offers the same scope and the same embedding —
with the settings `teil`, `stand` and `ueberschrift`.

Example for the complete statement:

`[barrierefreiheitserklaerung]`

Example for the non-conformance section only, embedded one level deeper:

`[barrierefreiheitserklaerung teil="maengel" ueberschrift="3"]`

= teil =

Which excerpt of the statement is output.

* `komplett` (default) — the complete statement.
* `maengel` — the section on known barriers only.
* `kontakt` — the section with the contact details for feedback only.

An unknown value returns the complete statement without an error message, exactly like
`komplett` — and so does the case where the requested section is not found in the statement
that was loaded.

= ueberschrift =

The level given to the topmost heading of the output (default `2`). It is measured relative to
the topmost heading that actually occurs in the statement — not fixed to an h1 — so that the
text fits into the heading hierarchy of your page.

Values from `2` to `4` are allowed; smaller or larger values are clamped to that range without
an error message (`1` therefore behaves like `2`, `5` like `4`). Headings further down inside
the statement are shifted along accordingly, but never beyond h6.

= stand =

`ja` (default) appends a paragraph with the version number and the date of the statement — but
only if the statement that was loaded brings a date with it. If it does not, the paragraph is
omitted even with `stand="ja"`. Any other value suppresses the paragraph in every case.

= sprache =

Is accepted, but has no effect in this version — the language of the output follows solely the
language version stored in the service.

== Frequently Asked Questions ==

= Do I have to set a DNS record? =

No. The plugin serves the proof itself, as a meta element and as a file under
`/.well-known/a11y-site-verification.txt`.

= I started a scan — where is the result? =

A scan takes a few minutes. The page does not update on its own, because an automatic reload
would move the focus and interrupt screen reader users (WCAG 2.2.2). While a scan is running,
*Tools → Accessibility* says so and offers “Check whether the scan has finished” as its main
button.

= What happens if the service is unreachable? =

The last successfully loaded version of the statement continues to be delivered — with its
original date. A statement that disappears from the site because of an outage would be a legal
problem for you.

= Which languages does the plugin come in? =

English (source language) and German. The template for further translations ships with the
plugin under `languages/a11y-checker.pot`.

= I mistyped something while connecting — how do I get back? =

Under *Tools → Accessibility* the stored connection is shown together with a “Disconnect”
button. The form then reappears and you can enter token, site ID and address again. The service
address can also be reset to its default value on its own there. Your account is not affected;
the token stays valid and is revoked in the account.

= What is left behind after uninstalling? =

Nothing. Uninstalling removes all options and caches, including the token and the last loaded
version of the statement — in a network, for every subsite individually.

= Does the automated scan replace an expert audit? =

No. Automated tests cover only part of the requirements. The service guides you through the
remaining test steps; the statement explicitly states that it is based on a self-assessment.

== Changelog ==

= 0.5.0 =
* New: The page explains the setup on first use - that the plugin does not scan on its own but is
  the way into the service, that the token is valid for this one site, and then step by step where
  account, token and site ID come from (*Websites -> your site -> Embedding*, section *WordPress
  plugin and API*). It used to be one sentence that assumed an account nobody knew about yet.
* New: The instructions state the address of this installation. If a different one is stored with
  the service, domain verification fails later without a visible reason.
* New: After activation a one-time notice points to the page, and the plugins list carries a "Set
  up" link.
* New: The token field says that the number and the vertical bar in front belong to the token.
* Changed: While a scan is running, "Check whether the scan has finished" is the primary button and
  the running-scan notice sits above the buttons instead of below them. Previously the grey
  "Refresh status" button was lost next to the blue "Scan now", so whoever had started a scan could
  not see how to get to the result.
* Changed: "Scan now" is hidden while a scan is running - a second run would only use up quota.
* Changed: While the first scan is still running the findings section says so, instead of pointing
  at a button that does not exist at that moment.
* Changed: The page now also says *why* it does not update on its own: an automatic reload would
  move the focus and interrupt screen reader users (WCAG 2.2.2).

= 0.4.0 =
* New: Every rule in the findings list can be expanded. "Show N occurrences" fetches the
  individual occurrences of that one rule - page, selector, measurements such as "contrast 2.41:1
  instead of 4.5:1", colour values, the state in which the element becomes visible, the viewport
  and the HTML snippet. Paging happens in steps of 20.
* New: Expanding and paging are ordinary links. Still no JavaScript in the plugin, and only the
  rule you are actually working on is fetched.
* Changed: Findings now come before the quota. They are the reason someone opens the page.
* Changed: Screenshots are not loaded into the WordPress admin area. The page points to the full
  report instead and says that opening it asks you to sign in first.
* Fixed: For signed-out users the link to the full report ended in a bare error page. The service
  now sends signed-out visitors to the sign-in page and back to the report afterwards.

= 0.3.1 =
* Changed: The readme and the plugin description are in English now, as the WordPress Plugin
  Directory requires.
* Changed: English is now the source language of the plugin's own strings. German ships as a
  translation in languages/a11y-checker-de_DE.po - a German installation shows exactly the same
  text as before.
* Changed: Tested up to WordPress 7.1.
* Known limitation: only the de_DE translation is bundled. Installations running de_AT, de_CH or
  de_DE_formal fall back to English until the plugin is listed in the directory and
  translate.wordpress.org supplies those language packs.

= 0.3.0 =
* New: Tools → Accessibility shows the open findings of the last scan - for each rule the
  severity, the success criterion, the number of occurrences, a note on how to fix it and the
  pages affected. Fixing happens in WordPress, "Scan now" closes the round.
* New: The page states the page quota of the current billing period.
* New: A "Refresh status" button fetches quota and findings again. It deliberately replaces an
  automatic page reload, which would be disruptive for screen reader users.
* New: If a scan is currently running, the page says so instead of showing an outdated result.
* Changed: State and findings are cached for five minutes; starting a scan and "Refresh status"
  discard the cache.
* Changed: If the site cannot be retrieved, the reason given by the service is now shown with it.

= 0.2.1 =
* New: The stored connection is visible under Tools → Accessibility and can be undone again.
  Until now the plugin hid the form as soon as token and site ID had been saved once - even if
  they were wrong.
* New: The service address can be reset to its default value on its own; the form suggests the
  address entered last instead of always the default.
* Fixed: A discarded address remained as an empty option; every request afterwards went to a
  relative path. In that case the default value now applies again.

= 0.2.0 =
* Fixed: The heading level is now measured against the topmost heading actually output. Until
  now the h1 of the statement stayed an h1 at the default setting - in the middle of a page
  that is itself a violation of WCAG 1.3.1 -, and a section embedded with teil="maengel" or
  teil="kontakt" came out one level too deep. If you have been balancing the levels by hand
  with ueberschrift="3", switch to ueberschrift="2" now (or leave the attribute out).
* Fixed: The feedback message in the admin area was URL-decoded twice, which made a message
  containing a literal percent sequence (e.g. "%41") arrive as a character.

= 0.1.0 =
* First release: connection, domain verification, starting a scan, shortcode and block.
* German and English language version, complete uninstall.
