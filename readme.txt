=== The Hidden Word ===
Contributors: brelandr
Tags: bible, scripture, discipleship, memorization, verse of the day
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A 52-week Bible discipleship plugin with deep-dive lessons, historical context, memorization tools, and discussion prompts.

== Description ==

The Hidden Word helps your church or ministry teach Scripture through structured, engaging weekly lessons. Each lesson breaks down a single verse into five rich sections:

* **The Blueprint** — The core scripture with memorization practice
* **The Context** — Historical and cultural background
* **The Narrative** — Events leading up to the verse
* **The Echo** — Cross-references connecting to the rest of Scripture
* **Discussion** — Reflection questions for small groups

= Features =

* 52-week curated NIV curriculum (fair-use compliant, under 500 verses)
* King James Version (public domain) included
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
3. Go to **The Hidden Word** in the admin menu — 52 lessons are seeded automatically
4. Add `[thw_lesson]` to any page or use the **Bible Lesson** Gutenberg block
5. Configure schedule and translation under **The Hidden Word → Settings**

== Frequently Asked Questions ==

= How many NIV verses are bundled? =

52 verses (one per week), well within Biblica's 500-verse gratis use limit.

= Can I add my own lessons? =

Yes. Create new Bible Lessons or edit the seeded 52-week curriculum.

= Does this work with page builders? =

Yes. Use the shortcode `[thw_lesson]` in any page builder text widget or the Gutenberg block.

== Changelog ==

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

= 1.0.0 =
Initial release of The Hidden Word.
