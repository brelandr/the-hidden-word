# WordPress.org Reviewer Notes — The Hidden Word v1.1.3

Paste the paragraph below into the **Notes for reviewers** field on submission.

---

**The Hidden Word** is a free Bible discipleship plugin that seeds up to 500 single-verse `thw_lesson` posts from bundled local JSON (500 NIV verses under Biblica's gratis-use guidelines, plus public-domain KJV) and displays them on the front end via the `[thw_lesson]` shortcode and Gutenberg block. The free plugin does not call external services or phone home; NIV copyright is documented in `readme.txt` and shown where scripture is displayed. On activation, lessons are created in small background batches (25 at a time) to avoid timeouts on slower hosting, with an admin notice while seeding runs. A **Today's Lesson** demo page is created automatically on first activation. A separate commercial add-on, The Hidden Word Premium, is mentioned only as an optional link in the readme and settings—there is no license gating or crippleware in the free plugin.

---

## Quick test steps

1. Activate the plugin.
2. Open **Bible Lessons** in the admin menu (lessons appear gradually; admin notice shows progress).
3. Visit the auto-created **Today's Lesson** page (or add `[thw_lesson]` to any page).
4. View the front end — tabbed lesson UI with NIV/KJV per **Settings**.
5. Uninstall removes all `thw_lesson` posts and plugin options.

## Screenshots

Real UI captures live in `docs/screenshots/` (upload to SVN `assets/`, not plugin trunk). Regenerate from wp-env:

```bash
cd LearnTheBible && bash scripts/capture-screenshots-wp-env.sh
```
