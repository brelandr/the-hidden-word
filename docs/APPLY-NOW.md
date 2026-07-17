# WordPress.org — Apply Now (v1.1.4)

One-page checklist for your first submission. SVN is **not available** until approved.

## 1. Download the release zip

Local: `LearnTheBible/Dist/hidden-word-bible-lessons-1.1.4.zip`  
GitHub: https://github.com/brelandr/hidden-word-bible-lessons/releases/tag/v1.1.4

## 2. Open the application form

https://wordpress.org/plugins/developers/add/

| Field | Value |
|-------|-------|
| Plugin name | Hidden Word Bible Lessons |
| Slug | `hidden-word-bible-lessons` |
| URL | https://github.com/brelandr/hidden-word-bible-lessons |

## 3. Paste reviewer notes

From `docs/REVIEWER-NOTES.md` or `docs/PLUGIN-APPLY-FORM.txt`.

## 4. Attach zip

`hidden-word-bible-lessons-1.1.4.zip`

## 5. Pre-submit verification (automated)

```bash
cd LearnTheBible
bash scripts/smoke-all.sh
```

## 6. After approval

```bash
bash scripts/deploy-wporg-svn.sh
cd .svn-hidden-word-bible-lessons
svn commit -m "Release 1.1.4"
```

Screenshots and banners upload to SVN `assets/` automatically via the deploy script.

## 7. Update Premium store

Change product copy to link to:

`https://wordpress.org/plugins/hidden-word-bible-lessons/`
