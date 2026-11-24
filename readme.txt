=== Benjamin Marc SEO Checker ===
Contributors: benjaminmarc
Tags: seo, audit, image alt, headings, checklist
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, read-only SEO checklist for published WordPress posts and pages.

== Description ==
Benjamin Marc SEO Checker provides a simple table under Tools > BM SEO Checker to help editors review saved post and page content. It checks whether a post title exists, whether a meta description is available in supported SEO plugin metadata, whether exactly one H1 appears in saved content, and whether image elements in saved content have nonempty ALT text.

The checker does not modify site content or send data to external services. It is not a full rendered-page crawler. A theme may add an H1 outside post content. SEO plugins may generate titles and descriptions dynamically. Results should be reviewed manually.

== Installation ==
1. Upload the plugin ZIP using Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Open Tools > BM SEO Checker as an administrator or editor with the edit_others_posts capability.

== Frequently Asked Questions ==
= Does this change my SEO settings? =
No. All checks are read-only.

= Why is my meta description marked for review? =
The plugin reads common metadata keys for Yoast SEO, Rank Math, and legacy All in One SEO. Other SEO plugins and dynamically generated descriptions may not be detected.

= Does the H1 check include the theme? =
No. It checks saved post content only.

== Changelog ==
= 1.0.2 =
* Added nonce verification for admin report pagination.

= 1.0.1 =
* Initial release with title, metadata, H1, and image ALT checks.
