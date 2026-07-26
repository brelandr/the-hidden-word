# Explain packs

Shareable ZIP packs of pre-generated Bible reader AI explanations.

## Pack ZIP layout

```
manifest.json
explains.jsonl
```

### `manifest.json`

```json
{
  "format": "hwbl_explain_pack_v1",
  "id": "bsb-nondenom-verse",
  "translation": "bsb",
  "tradition": "nondenom",
  "scopes": ["verse"],
  "version": "1.0.0",
  "count": 31000,
  "created_at": "2026-07-25T00:00:00+00:00",
  "generator": "hidden-word-bible-lessons"
}
```

### `explains.jsonl`

One JSON object per line:

```json
{"book_id":43,"chapter":3,"verse":16,"scope":"verse","reference":"John 3:16","html":"<p>…</p>","flagged":false}
```

## Hub workflow (thehiddenword.org only)

1. Preload Explains on the hub.
2. **Bible Lessons → Explain Packs → Export** for a Bible + tradition.
3. On the hub only: save a GitHub PAT (repo scope) and click **Publish to GitHub Release**.
4. The hub uploads the ZIP to [brelandr/hwbl-explain-packs](https://github.com/brelandr/hwbl-explain-packs) and updates `explain-packs-catalog.json`.
5. Optionally check **Remove from this site after export**.

Default catalog URL (all sites):

`https://raw.githubusercontent.com/brelandr/hwbl-explain-packs/main/explain-packs-catalog.json`

Churches never need a GitHub token — they only download public Release assets.

## Church workflow

1. Open **Bible Lessons → Explain Packs**.
2. Install from the catalog, a GitHub Release ZIP URL, or an uploaded ZIP.
3. Reader Explains for that Bible + tradition resolve from the imported posts (no AI cost).

## Catalog JSON

```json
{
  "format": "hwbl_explain_pack_catalog_v1",
  "packs": [
    {
      "id": "bsb-nondenom-verse",
      "translation": "bsb",
      "tradition": "nondenom",
      "scopes": ["verse"],
      "version": "1.0.0",
      "label": "BSB · Non-denominational · Verses",
      "url": "https://github.com/ORG/REPO/releases/download/v1.0.0/bsb-nondenom-verse-1.0.0.zip",
      "count": 31000,
      "bytes": 45000000
    }
  ]
}
```

Host that file on GitHub (raw or Pages) and point sites at it with the catalog URL setting / filter.
