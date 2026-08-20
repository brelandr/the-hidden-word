# Strong's concordance data

Public-domain Strong's Exhaustive Concordance (1890) lexicon plus an occurrence index for reverse lookup by number (`H####` / `G####`) or English gloss.

## Files

- `lexicon.json` — lemma, transliteration, primary gloss
- `english-index.json` — English word → Strong's numbers
- `occurrences-h.json.gz` / `occurrences-g.json.gz` — number → verse references
- `meta.json` — attribution

## Regenerate

```bash
python3 bin/build-strongs-index.py \
  --source /path/to/strongs.json \
  --out data/strongs
```

Upstream packs with `ci` / `sm` / `s2e` / `e2s` keys (public-domain KJV+Strong's occurrence data) are supported.
