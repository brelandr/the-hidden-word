# WordPress.org Submission & Screenshots

Guide for submitting **The Hidden Word** v1.1.3 to the [WordPress Plugin Directory](https://wordpress.org/plugins/developers/).

---

## Assets already in the repo

Located in `assets/` (bundled in release zip):

| File | Size | Purpose |
|------|------|---------|
| `icon-128x128.png` | 128×128 | Plugin icon |
| `icon-256x256.png` | 256×256 | Retina icon |
| `banner-772x250.png` | 772×250 | Plugin directory banner |
| `banner-1544x500.png` | 1544×500 | Retina banner |

Palette: navy `#1a5276`, cream `#f5f0e6`, gold `#d4af37`.

To regenerate lightweight placeholders:

```bash
python3 -c "
from PIL import Image, ImageDraw
from pathlib import Path
assets = Path('assets')
navy, cream, gold = (26,82,118), (245,240,230), (212,175,55)
# ... see scripts in repo history or edit assets manually
"
```

---

## Screenshots (readme.txt)

The readme already lists five screenshots. Capture these on a **clean WordPress 6.x install** with only The Hidden Word active (Twenty Twenty-Five theme is fine).

### Screenshot 1 — Tabbed lesson view (front end)

**Filename:** `screenshot-1.png`  
**Shows:** Full `[thw_lesson]` output with tabs visible (Blueprint, Context, Narrative, Echo, Discussion).

**Steps:**

1. Activate plugin (500 lessons seed in background batches; admin notice shows progress).
2. Create page **“Today’s Lesson”** with block **Bible Lesson** or shortcode `[thw_lesson]`.
3. View page on front end.
4. Click through one tab so multiple panels are implied (Blueprint selected is fine).
5. Capture at **1280×720** or wider; crop to **1200×900** max for WP.org.

### Screenshot 2 — Memorization widget

**Filename:** `screenshot-2.png`  
**Shows:** Fill-in-the-blanks widget with a few words hidden.

**Steps:**

1. On the same lesson page, scroll to memorization section.
2. Click **“Practice”** or reveal one blank so the interactive state is visible.
3. Capture close enough to read verse fragments and blank inputs.

### Screenshot 3 — Verse of the Week (compact)

**Filename:** `screenshot-3.png`  
**Shows:** `[thw_verse_of_week]` or compact scheduled verse in a sidebar/widget area.

**Steps:**

1. **Settings → The Hidden Word** → Schedule: **Verse of the Week**.
2. Add shortcode `[thw_verse_of_week]` to a sidebar widget or footer template part.
3. Capture front end showing reference + verse snippet.

### Screenshot 4 — Lesson editor (admin)

**Filename:** `screenshot-4.png`  
**Shows:** `thw_lesson` edit screen with meta boxes.

**Steps:**

1. **The Hidden Word → Bible Lessons** → open **Lesson 1: John 3:16** (or similar).
2. Ensure visible meta boxes:
   - Verse Reference (lesson number, book, chapter, verse)
   - Lesson Content (historical context / narrative editors)
   - Discussion Questions
3. Capture admin at **1200×900**; include left admin menu for context.

### Screenshot 5 — Plugin settings

**Filename:** `screenshot-5.png`  
**Shows:** **The Hidden Word → Settings** page.

**Steps:**

1. Open settings screen.
2. Show schedule mode dropdown (Week/Day) and translation (NIV/KJV).
3. Include Premium upsell link at bottom if present.

---

## Capture screenshots (real UI)

If you do not have a live demo site yet, generate placeholder screenshots that match the plugin palette:

```bash
cd The-Hidden-Word
## Capture screenshots (real UI)

With wp-env running and lessons seeded:

```bash
cd LearnTheBible
npm install playwright   # first time only
npx playwright install chromium
bash scripts/capture-screenshots-wp-env.sh
```

Outputs overwrite `The-Hidden-Word/docs/screenshots/screenshot-1.png` … `screenshot-5.png`.

Mock UI fallback (offline):

```bash
cd The-Hidden-Word
python3 scripts/generate-screenshots.py
```
```

Output: `docs/screenshots/screenshot-1.png` … `screenshot-5.png`

Replace with real captures from a clean WP install before final SVN upload if possible.

---

## Capture tools (macOS)

### Option A — Browser DevTools

1. Open page in Chrome.
2. `Cmd+Option+I` → toggle device toolbar → set viewport **1200×900**.
3. `Cmd+Shift+P` → “Capture screenshot” (full size or node).

### Option B — macOS screenshot

1. `Cmd+Shift+4` then `Space` to capture a window.
2. Resize in Preview to max 1200px wide (WP.org resizes anyway).

### Option C — Playwright (repeatable)

```bash
# From a WP install directory — adjust URL
npx playwright screenshot https://yoursite.test/todays-lesson/ screenshot-1.png --viewport-size=1200,900
```

---

## Screenshot file rules (WordPress.org)

- Format: **PNG** or **JPG**
- Max width: **1200px** (recommended)
- No excessive text marketing — show the **actual plugin UI**
- Filenames: `screenshot-1.png` … `screenshot-5.png`
- Upload to **SVN** `assets/` folder (separate from plugin trunk)

---

## SVN deployment workflow

### 1. Request plugin slug (if new)

Use [WordPress Plugin Directory](https://wordpress.org/plugins/developers/add/) — slug should be `the-hidden-word`.

### 2. Checkout SVN

```bash
svn co https://plugins.svn.wordpress.org/the-hidden-word thw-svn
cd thw-svn
```

Structure:

```text
the-hidden-word/
├── trunk/          # development copy
├── tags/1.1.3/     # immutable release
└── assets/         # icons, banners, screenshots (NOT in trunk)
```

### 3. Copy release into trunk

```bash
# Unzip or rsync from Dist/the-hidden-word-1.1.3.zip contents into trunk/
rsync -av --delete /path/to/the-hidden-word/ trunk/ \
  --exclude='.git' --exclude='tests' --exclude='scripts' --exclude='data/curriculum-parts'
```

Ensure `readme.txt` **Stable tag** matches: `1.1.3`.

### 4. Copy assets & screenshots

```bash
cp assets/icon-*.png assets/banner-*.png ../assets/
cp docs/screenshots/screenshot-*.png ../assets/
```

### 5. Tag release

```bash
svn cp trunk tags/1.1.3
svn add tags/1.1.3 assets/*
svn commit -m "Release 1.1.3 — real screenshots and submission docs"
```

WordPress.org builds zip from `tags/1.1.3/` automatically.

---

## Pre-submission checklist

### Code & policy

- [x] GPLv2+ compatible
- [x] No phone-home tracking
- [x] No license gates in free plugin
- [x] Premium upsell is link-only (readme + settings)
- [x] `readme.txt` Contributors: `brelandr`
- [x] External Services section documents no third-party calls in free plugin
- [x] NIV copyright block present

### Technical

- [x] `vendor/bin/phpunit` passes locally
- [x] `create-plugin-zip.sh --free-only` succeeds
- [x] Zip installs on clean WP 6.2+ / PHP 7.4+ (verified via `scripts/smoke-wp-env.sh` on WP 6.7 / PHP 8.2)
- [x] 500 lessons seed on activate (batched cron; ~3 min in wp-env)
- [x] `[thw_lesson]` renders without PHP notices (4299-char lesson UI on week 28)

### Assets

- [x] `icon-128x128.png`, `icon-256x256.png`
- [x] `banner-772x250.png`, `banner-1544x500.png`
- [x] `docs/screenshots/screenshot-1.png` … `screenshot-5.png` (real wp-env captures; upload to SVN `assets/`)

### First-time application

SVN is not available until approved. See `docs/PLUGIN-APPLY.md`.

### After approval

- [ ] Confirm plugin page shows banner and screenshots
- [ ] Test **Install** from wordpress.org on staging
- [ ] Update Premium store page to link to live wp.org URL

---

## readme.txt Screenshots section

Keep captions in sync with uploaded files:

```text
== Screenshots ==

1. Tabbed lesson view with scripture, context, narrative, echo, and discussion tabs
2. Fill-in-the-blanks memorization widget
3. Verse of the Week scheduling on the front end
4. Lesson editor with verse reference and enrichment meta boxes
5. Plugin settings — schedule mode and translation switcher
```

---

## Local demo site setup (fast path)

1. `wp core download` + `wp config create` + `wp db create`
2. `wp plugin install` from `Dist/the-hidden-word-1.1.3.zip`
3. `wp plugin activate the-hidden-word`
4. `wp post create --post_type=page --post_title="Today's Lesson" --post_status=publish`
5. `wp post meta update <page_id> _wp_page_template` (or insert block via admin)
6. Set permalink structure: **Post name**
7. Capture screenshots listed above

Using **WP-CLI** saves time if you already have a local `@wordpress` environment (Local, DevKinsta, wp-env, etc.).
