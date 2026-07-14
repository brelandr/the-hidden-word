=== The Hidden Word ===
Contributors: brelandr
Tags: bible, scripture, discipleship, memorization, verse of the day
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Bible discipleship plugin with 500 NIV verses, deep-dive lessons, historical context, memorization tools, and discussion prompts.

== Description ==

The Hidden Word helps your church or ministry teach Scripture through structured, engaging weekly lessons. Each lesson breaks down a single verse into five rich sections:

* **The Blueprint** — The core scripture with memorization practice
* **The Context** — Historical and cultural background
* **The Narrative** — Events leading up to the verse
* **The Echo** — Cross-references connecting to the rest of Scripture
* **Discussion** — Reflection questions for small groups

= Features =

* 500-verse curated NIV curriculum (Biblica fair-use maximum)
* King James Version (public domain) included
* World English Bible (public domain) included
* Verse of the Week or Verse of the Day scheduling
* Fill-in-the-blanks memorization widget
* Custom post type lesson builder with dedicated meta fields
* Gutenberg block and shortcodes for easy placement
* WordPress comments integration for group discussion

= Shortcodes =

* `[thw_lesson]` — Display the current scheduled lesson
* `[thw_lesson id="123"]` — Display a specific lesson
* `[thw_verse_of_week]` — Compact verse display

= NIV Copyright =

Scripture quotations marked NIV are from THE HOLY BIBLE, NEW INTERNATIONAL VERSION®, NIV® Copyright © 1973, 1978, 1984, 2011 by Biblica, Inc.® Used by permission. All rights reserved worldwide.

The bundled NIV text is provided under Biblica's gratis use guidelines for non-commercial WordPress plugins (fewer than 500 verses, no complete books).

= Premium Add-on =

[The Hidden Word Premium](https://landtechwebdesigns.com/product/the-hidden-word-premium/) adds custom scheduling, PDF leader guides, multi-translation switching via API.Bible (bring your own API key), progress tracking, and AI-assisted lesson drafting.

== External Services ==

This free plugin does not send data to third-party servers. Bundled NIV text is stored locally under Biblica's gratis use guidelines. The optional Premium add-on (sold separately) may connect to external services when the site administrator configures API keys — see the Premium plugin readme.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/the-hidden-word/`
2. Activate through the 'Plugins' menu
3. Go to **The Hidden Word** in the admin menu — 500 lessons seed in the background (progress notice while running). A **Today's Lesson** demo page is created on first activation.
4. Add `[thw_lesson]` to any page or use the **Bible Lesson** Gutenberg block
5. Configure schedule and translation under **The Hidden Word → Settings**

== Frequently Asked Questions ==

= How many NIV verses are bundled? =

500 single-verse lessons, using the full Biblica 500-verse gratis use allowance.

= Can I add my own lessons? =

Yes. Create new Bible Lessons or edit the seeded 500-lesson curriculum.

= Does this work with page builders? =

Yes. Use the shortcode `[thw_lesson]` in any page builder text widget or the Gutenberg block.

== Screenshots ==

1. Tabbed lesson view with scripture, context, narrative, echo, and discussion tabs
2. Fill-in-the-blanks memorization widget
3. Verse of the Week scheduling on the front end
4. Lesson editor with verse reference and enrichment meta boxes
5. Plugin settings — schedule mode and translation switcher

== Changelog ==

= 1.1.5 =
* Add bundled World English Bible (WEB) as a third offline translation
* Re-initialize memorization widget when Premium translation switcher changes verse text

= 1.1.4 =
* Replace deprecated `get_page_by_title()` with `WP_Query` for demo page lookup
* Add combined `smoke-all.sh` and Plugin Check smoke script

= 1.1.3 =
* Replace mock WordPress.org screenshots with real wp-env UI captures
* Seeding-complete admin notice links to the Today's Lesson demo page
* Update WordPress.org reviewer and submission documentation

= 1.1.2 =
* Create a "Today's Lesson" demo page with `[thw_lesson]` on first activation (does not change your homepage)
* Add WordPress.org application form copy and plugin apply guide

= 1.1.1 =
* The bundled 500-lesson curriculum now seeds in small background batches instead of all at once on activation, to avoid timeouts on slower hosting. An admin notice shows progress while it runs.
* Lesson scheduling uses an in-memory lookup map instead of slow meta queries on every page load.

= 1.1.0 =
* Expand bundled NIV curriculum from 52 to 500 verses (Biblica fair-use maximum)
* Add THW_Curriculum helper for verse counting and lesson scheduling
* Scheduler and lesson meta now support lesson numbers 1–500
* Automatic upgrade seeding for existing installs
* WordPress.org assets and GitHub Actions CI

= 1.0.1 =
* Complete 52-week NIV curriculum with historical context, narrative, and discussion questions
* Update project .cursorrules for The Hidden Word

= 1.0.0 =
* Initial release
* 52-week NIV + KJV curriculum
* Lesson CPT with meta boxes
* Tabbed front-end lesson display
* Basic memorization widget
* Gutenberg block and shortcodes

== Upgrade Notice ==

= 1.1.4 =
Fixes WordPress 6.2+ deprecation warning for demo page detection.

= 1.1.3 =
Documentation and screenshot updates for WordPress.org submission. No required action for existing sites.

= 1.1.2 =
Creates a starter front-end page on new installs so lessons are visible immediately after activation.

= 1.1.1 =
Background batched seeding avoids activation timeouts on slower hosts. An admin notice shows progress while lessons are created.

= 1.1.0 =
Expands the bundled curriculum to 500 NIV verses. Existing sites automatically receive new lessons on upgrade.

= 1.0.0 =
Initial release of The Hidden Word.
