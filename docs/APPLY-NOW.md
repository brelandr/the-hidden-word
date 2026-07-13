# WordPress.org — Apply Now (v1.1.4)

One-page checklist for your first submission. SVN is **not available** until approved.

## 1. Download the release zip

Local: `LearnTheBible/Dist/the-hidden-word-1.1.4.zip`  
GitHub: https://github.com/brelandr/the-hidden-word/releases/tag/v1.1.4

## 2. Open the application form

https://wordpress.org/plugins/developers/add/

| Field | Value |
|-------|-------|
| Plugin name | The Hidden Word |
| Slug | `the-hidden-word` |
| URL | https://github.com/brelandr/the-hidden-word |

## 3. Paste reviewer notes

From `docs/REVIEWER-NOTES.md` or `docs/PLUGIN-APPLY-FORM.txt`.

## 4. Attach zip

`the-hidden-word-1.1.4.zip`

## 5. Pre-submit verification (automated)

```bash
cd LearnTheBible
bash scripts/smoke-all.sh
```

## 6. After approval

```bash
bash scripts/deploy-wporg-svn.sh
cd .svn-the-hidden-word
svn commit -m "Release 1.1.4"
```

Screenshots and banners upload to SVN `assets/` automatically via the deploy script.

## 7. Update Premium store

Change product copy to link to:

`https://wordpress.org/plugins/the-hidden-word/`
