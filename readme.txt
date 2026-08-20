=== Hidden Word Bible Lessons ===
Contributors: brelandr
Tags: bible, scripture, discipleship, memorization, verse of the day
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.3.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bible discipleship with 500 NIV lessons, memorization, Bible reader, local public-domain Bibles, digests, and BYOK AI — all free.

== Description ==

Hidden Word Bible Lessons helps your church or ministry teach Scripture through structured, engaging weekly lessons. Each lesson breaks down a single verse into five rich sections:

* **The Blueprint** — The core scripture with memorization practice
* **The Context** — Historical and cultural background
* **The Narrative** — Events leading up to the verse
* **The Echo** — Cross-references connecting to the rest of Scripture
* **Discussion** — Reflection questions for small groups

= Features =

* 500-verse curated NIV curriculum (Biblica fair-use maximum)
* King James Version and World English Bible (public domain) included
* **Local Bibles** — download free public-domain translations (English, Spanish, French, German, Chinese, and more, including Catholic/Orthodox-scope texts) into your site database for offline reading and search
* Spaced-repetition memorization (SM-2), Flip cards and other practice modes, and review queue
* Bible chapter reader with Hello AO audio (no key); memorization “Listen to verse” speaks the exact verse text
* Bible Maps with biblical + modern place labels, passage/book toggle, and verse references
* Bible Concordance for word/phrase study (Local Bibles offline; NIV/NLT via Biblia when configured)
* Optional BYOK: API.Bible, Biblia.com, YouVersion Platform, OpenAI / Claude (or WP AI Connectors)
* Licensed translation safety: NIV/ESV/NLT and similar wording is not embedded into AI prompts (display-only via API)
* Verse of the Day, email digests, progress tracking, PDF leader guides
* Custom scheduling, cohorts, study finder, and ask-a-question tools
* Companion app support (church directory, app connect, account deletion)
* Gutenberg blocks and shortcodes; lesson catalog at `/bible-lesson/`

= Coming soon: iPhone and Android apps =

Native companion apps for iPhone and Android are coming soon. We are looking for volunteers to help test before public launch.

To join the testing program, email **sales@landtechwebdesigns.com** with this subject line:

`The hidden word iphone or andrid app testing program to participate.`

Please tell us which phone you wish to test on (iPhone, Android, or both).

= Shortcodes =

* `[hwbl_lesson]` — Current scheduled lesson
* `[hwbl_lesson_list]` — Browse all lessons
* `[hwbl_verse_of_week]` — Compact scheduled verse
* `[hwbl_bible_reader]` — Read/listen to any chapter
* `[hwbl_bible_map]` — Map biblical places for a verse or chapter (OpenBible geocoding data)
* `[hwbl_bible_concordance]` — Word/phrase concordance (Local Bibles offline; NIV/NLT via Biblia when configured)
* `[hwbl_memorize_verse]` / `[hwbl_memorize_reviews]` — Memorize and review
* `[hwbl_verse_of_the_day]` / `[hwbl_study_finder]` / `[hwbl_ask_question]` — Daily verse and AI study tools
* `[hwbl_my_progress]` — Progress and streaks

= NIV Copyright =

Scripture quotations marked NIV are from THE HOLY BIBLE, NEW INTERNATIONAL VERSION®, NIV® Copyright © 1973, 1978, 1984, 2011 by Biblica, Inc.® Used by permission. All rights reserved worldwide.

The bundled NIV text is provided under Biblica's gratis use guidelines for non-commercial WordPress plugins (fewer than 500 verses, no complete books).

== External Services ==

Bundled NIV/KJV/WEB text is stored locally. All other external calls are optional and only happen when a site administrator explicitly enables a feature, supplies keys, or starts an import:

* Hello AO — free Bible text/audio API (no key)
* API.Bible, Biblia.com, YouVersion Platform — optional translation providers (BYOK)
* bible.com — Verse of the Day reference/image (when VOTD is enabled)
* OpenAI or Anthropic — optional AI explain/study/ask (BYOK or Connectors)
* BibleSuperSearch.com, eBible.org, BereanBible.com, theWord module archives (theword-modules.com), and the scrollmapper/bible_databases GitHub repository — used only when an administrator uses the built-in Local Bible Importer (Bible Lessons → Local Bibles) to download an additional public-domain translation
* GitHub (raw.githubusercontent.com, api.github.com) — used only when an administrator browses or installs a shared "Explain Pack" from the optional community catalog, or publishes/updates their own pack
* OpenStreetMap tile servers — used when Bible Maps is enabled with the Leaflet provider (browser loads map tiles; OSM attribution shown on the map)
* Mapbox — used only when an administrator selects Mapbox as the Bible Maps provider and supplies a Mapbox access token. Style presets include Outdoors (default), Light, Satellite, Streets, or a custom Mapbox Studio style URL (e.g. parchment/vintage themes)

Bundled OpenBible.info Bible-Geocoding-Data place index is licensed CC BY 4.0; attribution is shown with every map.

No license phone-home. No paid feature gates.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/hidden-word-bible-lessons/`
2. Activate through the 'Plugins' menu
3. Go to **Hidden Word Bible Lessons** in the admin menu — 500 lessons seed in the background. A **Today's Lesson** demo page is created on first activation.
4. Add `[hwbl_lesson]` to any page or use the **Bible Lesson** Gutenberg block
5. Configure schedule and translation under **Settings**; API keys and digests under **Advanced**
6. Optional: download public-domain Bibles under **Bible Lessons → Local Bibles**
7. If you previously used the separate Premium add-on, deactivate and delete that plugin

== Frequently Asked Questions ==

= How many NIV verses are bundled? =

500 single-verse lessons, using the full Biblica 500-verse gratis use allowance.

= Do I need a separate Premium plugin? =

No. Former Premium features are included in this plugin for free. Deactivate any older separate Premium install.

= Can I store more Bible translations on my site? =

Yes. Use **Bible Lessons → Local Bibles** to download public-domain translations into your database. Licensed editions (NIV, ESV, NLT, etc.) still use your API keys under Advanced.

= Can I add my own lessons? =

Yes. Create new Bible Lessons or edit the seeded 500-lesson curriculum.

= Does this work with page builders? =

Yes. Use the shortcode `[hwbl_lesson]` in any page builder text widget or the Gutenberg block.

= How can I help test the iPhone or Android app? =

Email sales@landtechwebdesigns.com with the subject “The hidden word iphone or andrid app testing program to participate.” and say whether you want to test on iPhone, Android, or both.

= Privacy Policy =

The hub operator’s policy for The Hidden Word (accounts, AI Ask, gospel-response contact details, testimonies, and prayer requests) is published at https://thehiddenword.org/privacy-policy . Church sites that run this plugin process most member data under that church’s own practices; review their privacy information as well.

== Screenshots ==

1. Tabbed lesson view with scripture, context, narrative, echo, and discussion tabs
2. Fill-in-the-blanks memorization widget
3. Verse of the Week scheduling on the front end
4. Lesson editor with verse reference and enrichment meta boxes
5. Plugin settings — schedule mode and translation switcher

== Changelog ==

= 2.3.7 =
* Reading plans: review previous days via Previous/Next and a clickable plan outline (progress stays on the current day)

= 2.3.6 =
* Hello AO: Spanish (Reina-Valera 1909, Biblia en Español Sencillo) and Portuguese (Bíblia Livre) for the Bible reader — text only where AO has no chapter audio
* Fix Young’s Literal Translation map (`ylt` → `eng_ylt`); non-English editions do not fall back to English chapter audio

= 2.3.5 =
* Sign in with Apple: verify identity tokens signed with Apple’s RS256 keys (fixes login failures when only ES256 was accepted)
* Gospel presentation responses: privacy notice, required consent checkbox, and REST rejection without consent
* Admin hint on Gospel Responses list: review new responses promptly (private CPT + admin email path)

= 2.3.4 =
* Companion / hub auth polish for Sign in with Apple account create-or-link flows
* Privacy policy alignment for Apple Hide My Email and opaque Apple user identifiers

= 2.3.3 =
* Rich Bible study plans: study-voice day template (Open → Text → Heart → Practice) and expanded plan seed topics
* Plan slug in REST; Faith Formation / Grow surfaces and deep-link paths for plans

= 2.3.2 =
* Strong’s concordance index data and study tooling for Local Bibles / reader workflows
* Gospel share pages: public `/gospel/{id}` presentation, QR, WhatsApp/SMS share helpers

= 2.3.1 =
* Gospel response capture: optional email/location, nearby church match, private `hwbl_gospel_resp` CPT, admin (+ matched church) email notify
* Testimony and prayer request CPTs with account-scoped create flows and safety report hooks

= 2.3.0 =
* Reading plans CPT, progress, REST, shortcodes/blocks, digest/push hooks, and sample plan seeds
* Discipleship outreach foundations (plans + outward sharing scaffolding)

= 2.2.0 =
* Memorization Flip cards: SM-2 adaptive blank ratio, swipe-to-flip, and “Listen to verse” speaks the exact verse (device/browser TTS) instead of the full chapter
* Bible Maps: biblical + modern place labels, This passage / Whole book toggle, verse references in place details, and a compact places dropdown under the map
* Bible Concordance and map shortcodes/blocks remain available for study and geography
* Announcement: iPhone and Android companion apps coming soon — volunteer testers welcome (email sales@landtechwebdesigns.com; subject: The hidden word iphone or andrid app testing program to participate.; say iPhone, Android, or both)

= 2.1.0 =
* Local Bibles: import public-domain translations into site SQL (language filter, USFX/eBible, scrollmapper JSON, theWord .ont including Torres Amat, and more)
* Expanded book map for Catholic/Orthodox deuterocanonical texts in the Bible reader
* Email verification helpers for front-end registration (pending until confirmed; login blocked until verified)
* Companion app plumbing: church network directory, app connect codes, account deletion endpoint, community safety reports
* AI scripture policy: do not embed licensed translation wording (NIV/ESV/NLT/etc.) into explain prompts
* Explain packs and preload tooling for shared base + tradition override rows
* Bible Maps: OpenBible place markers in the Bible reader, `[hwbl_bible_map]` / Gutenberg block, Leaflet or Mapbox (admin choice); Mapbox style presets (Outdoors default, Light, Satellite, Streets, custom Studio) with on-map style switcher; companion Map UI deferred until current store build is approved
* Bible Concordance: word/phrase study via Local Bibles (offline) or Biblia.com for NIV/NLT; `[hwbl_bible_concordance]` / Gutenberg block + reader panel

= 2.0.2 =
* Fix Verse of the Day Explain 404s: flush CPT rewrite rules when missing so saved explanation permalinks resolve
* Ignore empty/broken saved explanation shells and regenerate usable content
* Silence Plugin Check false positive for wp_get_connectors() via call_user_func

= 2.0.1 =
* Verse of the Day Explain: clearer errors when AI explain is off or no provider is ready; follow site AI toggle when the VOTD explain option has never been saved
* Log AI generation failures for Verse of the Day Explain to help diagnose Connectors/BYOK issues

= 2.0.0 =
* Merge former Premium add-on into this plugin (all features free, no license gate)
* Advanced Settings: Bible API keys, AI (BYOK/Connectors), digests, VOTD, scheduling
* Shortcodes: hwbl_study_finder, hwbl_ask_question, hwbl_verse_of_the_day, hwbl_my_progress, and more
* Remove license phone-home and GitHub updater from bundled modules

= 1.7.2 =
* Escape copyright and lesson list HTML with wp_kses_post at output boundaries
* Remove legacy thw_/THW_ shortcodes, constants, class aliases, and hook bridges (hwbl_/HWBL_ only)

= 1.7.1 =
* Fix “Listen to verse” when site translation has no Hello AO audio (falls back to KJV/WEB/BSB chapter audio)
* Add “Know the reference” memorization mode for book, chapter, and verse practice

= 1.7.0 =
* SRS-first memorization: daily review mode, quality ratings, review queue dashboard shortcode `[hwbl_memorize_reviews]`
* Lesson pages show due-review banner and auto-enroll verses in your SRS deck when logged in
* Engagement: cohort leaderboard + weekly challenge, unified AI assistant, side-by-side translation compare
* PWA offline pack caching for review queue; memorization audio via Hello AO
* API.Bible FUMS script + attribution; merge-ready `hwbl_premium_features_enabled()` helper

= 1.6.0 =
* SM-2 spaced repetition with server-side progress (hwbl/v1/memorize/* REST)
* Practice modes: hide words, type from memory, first-letter hints, word scramble
* Server streak sync with one-time localStorage import on login
* REST namespace bridge: hwbl/v1 primary, thw/v1 deprecated aliases
* Engagement scaffolds: cohort leaderboard, AI assistant shell, personalized digests, translation compare, PWA offline pack

= 1.5.0 =
* Secure memorize-verse REST endpoint with rate limiting; custom verse posts require login
* Memoize bundled KJV/WEB/NIV curriculum JSON loads for better performance
* JavaScript i18n foundation for lesson tabs and memorization widget
* Keyboard accessibility for word-hide memorization (Enter/Space, ARIA labels)
* Align Premium streak claim with hwbl_mem_streak localStorage key

= 1.3.1 =
* Schedule-aware front-end labels: “Today’s / This Week’s / This Month’s Verse to Memorize” based on Bible Lessons → Settings schedule mode
* Rename user-facing “lesson” CTAs/catalog/widget copy toward verse-to-memorize wording

= 1.3.0 =
* Renamed for WordPress.org: Hidden Word Bible Lessons (slug hidden-word-bible-lessons)
* Prefix identifiers as hwbl_/HWBL_ (4+ characters) with legacy thw_* shortcode/block aliases
* Scope curriculum admin notices to plugin screens (Guideline 11)
* Migrate legacy thw_lesson CPT, options, and meta on upgrade

= 1.2.2 =

= 1.2.2 =
* Add optional Enable AI Features setting (requires Premium for front-end AI explain and study search)

= 1.2.1 =
* Fix autoload for translation provider interface on plugin activation

= 1.2.0 =
* Add lesson catalog with `[hwbl_lesson_list]`, Gutenberg block, and `/bible-lesson/` archive
* Add print and copy verse buttons on lesson pages
* Add browser-local memorization streak counter with Premium upsell
* Add classic Verse of the Week widget
* Backfill enriched lesson content and echo verse text from bundled curriculum cache

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
* Create a "Today's Lesson" demo page with `[hwbl_lesson]` on first activation (does not change your homepage)
* Add WordPress.org application form copy and plugin apply guide

= 1.1.1 =
* The bundled 500-lesson curriculum now seeds in small background batches instead of all at once on activation, to avoid timeouts on slower hosting. An admin notice shows progress while it runs.
* Lesson scheduling uses an in-memory lookup map instead of slow meta queries on every page load.

= 1.1.0 =
* Expand bundled NIV curriculum from 52 to 500 verses (Biblica fair-use maximum)
* Add HWBL_Curriculum helper for verse counting and lesson scheduling
* Scheduler and lesson meta now support lesson numbers 1–500
* Automatic upgrade seeding for existing installs
* WordPress.org assets and GitHub Actions CI

= 1.0.1 =
* Complete 52-week NIV curriculum with historical context, narrative, and discussion questions
* Update project .cursorrules for Hidden Word Bible Lessons

= 1.0.0 =
* Initial release
* 52-week NIV + KJV curriculum
* Lesson CPT with meta boxes
* Tabbed front-end lesson display
* Basic memorization widget
* Gutenberg block and shortcodes

== Upgrade Notice ==

= 2.3.7 =
Lets you reopen earlier reading-plan days without losing progress.

= 2.3.6 =
Adds Spanish and Portuguese Hello AO Bibles to the reader; fixes YLT mapping and non-English audio fallback.

= 2.3.5 =
Fixes Sign in with Apple (RS256), and adds gospel-response consent plus privacy notice on public gospel forms.

= 2.3.0 =
Adds reading plans, gospel/testimony/prayer outreach features, and related REST surfaces.

= 2.2.0 =
Improves Flip memorization and Bible Maps, and announces coming iPhone/Android companion apps with a volunteer testing call.

= 2.1.0 =
Adds Local Bibles imports, companion app support, email verification helpers, and safer AI handling for licensed translations.

= 2.0.2 =
Fixes Verse of the Day Explain links that 404 when CPT rewrite rules were not flushed.

= 2.0.1 =
Improves Verse of the Day AI Explain messaging and diagnostics when AI is not ready.

= 1.6.0 =
Adds SM-2 memorization, server streak sync, four practice modes, and hwbl/v1 REST as the primary API namespace.

= 1.2.0 =
Adds lesson catalog browsing, print/copy tools, local streak tracking, and the Verse of the Week widget.

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
Initial release of Hidden Word Bible Lessons.
