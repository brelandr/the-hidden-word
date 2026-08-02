# Bible geo index

Compact verse → place markers derived from [OpenBible Bible-Geocoding-Data](https://github.com/openbibleinfo/Bible-Geocoding-Data) (CC BY 4.0).

Do not commit the raw `ancient.jsonl` / `modern.jsonl` sources here. Regenerate shards with:

```bash
python3 bin/build-bible-geo-index.py \
  --ancient /path/to/ancient.jsonl \
  --modern /path/to/modern.jsonl \
  --out data/bible-geo
```

**Companion app:** Map UI is deferred until the current mobile store build is approved. The `GET hwbl/v1/bible/places` API is ready for a later companion release.
