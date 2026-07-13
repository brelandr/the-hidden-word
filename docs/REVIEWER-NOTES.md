# WordPress.org Reviewer Notes — The Hidden Word v1.1.1

Paste the paragraph below into the **Notes for reviewers** field on submission.

---

**The Hidden Word** is a free Bible discipleship plugin that seeds up to 500 single-verse `thw_lesson` posts from bundled local JSON (500 NIV verses under Biblica’s gratis-use guidelines, plus public-domain KJV) and displays them on the front end via the `[thw_lesson]` shortcode and Gutenberg block. The free plugin does not call external services or phone home; NIV copyright is documented in `readme.txt` and shown where scripture is displayed. On activation, lessons are created in small background batches (25 at a time) to avoid timeouts on slower hosting, with an admin notice while seeding runs. A separate commercial add-on, The Hidden Word Premium, is mentioned only as an optional link in the readme and settings—there is no license gating or crippleware in the free plugin.

---

## Quick test steps

1. Activate the plugin.
2. Open **The Hidden Word → Bible Lessons** (lessons appear gradually; admin notice shows progress).
3. Create a page with `[thw_lesson]` or the **Bible Lesson** block.
4. View the front end — tabbed lesson UI with NIV/KJV per **Settings**.
5. Uninstall removes all `thw_lesson` posts and plugin options.

## Screenshots

Generated mock UI images live in `docs/screenshots/` (upload to SVN `assets/`, not plugin trunk).
