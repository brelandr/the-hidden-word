# WordPress.org — First-Time Plugin Application

The SVN repository `https://plugins.svn.wordpress.org/the-hidden-word/` does **not exist yet**. You must apply and be approved before uploading.

## Apply for the plugin slug

1. Log in at https://wordpress.org/plugins/developers/add/
2. Request slug: **`the-hidden-word`**
3. Paste the reviewer intro from `The-Hidden-Word/docs/REVIEWER-NOTES.md`
4. Upload or link to the latest release zip: `Dist/the-hidden-word-1.1.2.zip`
5. Wait for review (typically days to weeks)

## After approval

WordPress.org will email SVN credentials. Then:

```bash
cd LearnTheBible
bash scripts/deploy-wporg-svn.sh
cd .svn-the-hidden-word
svn status
svn commit -m "Release 1.1.2"
```

## Pre-application checklist (completed in repo)

- [x] GPLv2+ compatible
- [x] No phone-home tracking in free plugin
- [x] No license gates in free plugin
- [x] Premium upsell is link-only
- [x] `readme.txt` Contributors: `brelandr`
- [x] External Services + NIV copyright in readme
- [x] PHPUnit passes (6 tests)
- [x] Release zip builds (`the-hidden-word-1.1.2.zip`)
- [x] Icons and banners in `assets/`
- [x] Screenshots in `docs/screenshots/` (mock UI — replace with real captures when possible)
- [x] Reviewer notes in `docs/REVIEWER-NOTES.md`

## Plugin Check before submit

On a local WP 6.2+ install:

1. Install Plugin Check from wordpress.org
2. Activate The Hidden Word from `Dist/the-hidden-word-1.1.2.zip`
3. Run **Tools → Plugin Check** — confirm 0 errors

## Update Premium store after approval

Change product copy to link to the live URL:

`https://wordpress.org/plugins/the-hidden-word/`
