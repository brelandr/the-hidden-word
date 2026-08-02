#!/usr/bin/env python3
"""
Build compact OpenBible place shards for The Hidden Word.

Usage:
  python3 bin/build-bible-geo-index.py \\
    --ancient /path/to/ancient.jsonl \\
    --modern /path/to/modern.jsonl \\
    --out data/bible-geo

Source: https://github.com/openbibleinfo/Bible-Geocoding-Data (CC-BY-4.0)
"""

from __future__ import annotations

import argparse
import json
import os
from collections import defaultdict
from datetime import date


def parse_lonlat(value):
    if not value or not isinstance(value, str) or "," not in value:
        return None, None
    lon_s, lat_s = value.split(",", 1)
    try:
        return float(lat_s.strip()), float(lon_s.strip())
    except ValueError:
        return None, None


def confidence_label(score_total):
    if score_total is None:
        return "unknown"
    if score_total >= 700:
        return "high"
    if score_total >= 400:
        return "medium"
    if score_total >= 100:
        return "low"
    return "uncertain"


def load_modern(path):
    modern = {}
    with open(path, encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if not line:
                continue
            row = json.loads(line)
            mid = row.get("id")
            if not mid:
                continue
            modern[mid] = {
                "name": row.get("friendly_id") or row.get("url_slug") or mid,
                "lonlat": row.get("lonlat") or "",
            }
    return modern


def build(ancient_path, modern_path, out_dir):
    modern = load_modern(modern_path)
    by_book = defaultdict(lambda: defaultdict(list))
    place_count = 0
    mapped = 0

    with open(ancient_path, encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if not line:
                continue
            row = json.loads(line)
            place_count += 1
            name = row.get("friendly_id") or row.get("url_slug") or row.get("id")
            pid = row.get("id")
            types = row.get("types") or []
            idents = row.get("identifications") or []
            if not idents:
                continue

            def ident_score(ident):
                score = ident.get("score") or {}
                return (score.get("time_total") or 0, score.get("vote_total") or 0)

            best = max(idents, key=ident_score)
            score = best.get("score") or {}
            time_total = score.get("time_total")
            resolutions = best.get("resolutions") or []
            lat = lng = None
            modern_id = best.get("id") if best.get("id_source") == "modern" else None
            for res in resolutions:
                lat, lng = parse_lonlat(res.get("lonlat"))
                if lat is not None:
                    modern_id = res.get("modern_basis_id") or modern_id
                    break

            modern_name = ""
            if modern_id and modern_id in modern:
                modern_name = modern[modern_id]["name"]
                if lat is None:
                    lat, lng = parse_lonlat(modern[modern_id].get("lonlat"))
            if not modern_name and modern_id:
                assoc = (row.get("modern_associations") or {}).get(modern_id) or {}
                modern_name = assoc.get("name") or ""

            if lat is None:
                continue

            mapped += 1
            place = {
                "id": pid,
                "name": name,
                "lat": round(lat, 6),
                "lng": round(lng, 6),
                "confidence": confidence_label(time_total),
                "confidence_score": int(time_total)
                if isinstance(time_total, (int, float))
                else 0,
                "modern_name": modern_name,
                "country": "",
                "types": types[:3] if isinstance(types, list) else [],
            }

            for verse in row.get("verses") or []:
                sort_key = verse.get("sort") or ""
                if len(sort_key) != 8 or not sort_key.isdigit():
                    continue
                by_book[sort_key[:2]][sort_key].append(place)

    by_book_dir = os.path.join(out_dir, "by-book")
    os.makedirs(by_book_dir, exist_ok=True)

    verse_keys = 0
    for book, verses in by_book.items():
        compact = {}
        for sort_key, places in verses.items():
            seen = set()
            unique = []
            for place in places:
                if place["id"] in seen:
                    continue
                seen.add(place["id"])
                unique.append(place)
            unique.sort(key=lambda item: -item.get("confidence_score", 0))
            compact[sort_key] = unique
            verse_keys += 1
        with open(
            os.path.join(by_book_dir, f"{book}.json"), "w", encoding="utf-8"
        ) as handle:
            json.dump(compact, handle, separators=(",", ":"), ensure_ascii=False)

    meta = {
        "source": "OpenBible.info Bible-Geocoding-Data",
        "source_url": "https://github.com/openbibleinfo/Bible-Geocoding-Data",
        "license": "CC-BY-4.0",
        "attribution": "Geographic data © OpenBible.info (CC BY 4.0)",
        "generated": date.today().isoformat(),
        "version": 1,
        "place_count": place_count,
        "mapped_places": mapped,
        "verse_keys": verse_keys,
        "books": sorted(by_book.keys()),
    }
    with open(os.path.join(out_dir, "index-meta.json"), "w", encoding="utf-8") as handle:
        json.dump(meta, handle, indent=2)
        handle.write("\n")

    readme = os.path.join(out_dir, "README.md")
    with open(readme, "w", encoding="utf-8") as handle:
        handle.write(
            "# Bible geo index\n\n"
            "Compact verse → place markers derived from "
            "[OpenBible Bible-Geocoding-Data](https://github.com/openbibleinfo/Bible-Geocoding-Data) "
            "(CC BY 4.0).\n\n"
            "Do not commit the raw `ancient.jsonl` / `modern.jsonl` sources here. "
            "Regenerate shards with:\n\n"
            "```bash\n"
            "python3 bin/build-bible-geo-index.py \\\n"
            "  --ancient /path/to/ancient.jsonl \\\n"
            "  --modern /path/to/modern.jsonl \\\n"
            "  --out data/bible-geo\n"
            "```\n\n"
            "**Companion app:** Map UI is deferred until the current mobile store "
            "build is approved. The `GET hwbl/v1/bible/places` API is ready for a "
            "later companion release.\n"
        )
    return meta


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--ancient", required=True)
    parser.add_argument("--modern", required=True)
    parser.add_argument(
        "--out",
        default=os.path.join(
            os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
            "data",
            "bible-geo",
        ),
    )
    args = parser.parse_args()
    meta = build(args.ancient, args.modern, args.out)
    print(json.dumps(meta, indent=2))


if __name__ == "__main__":
    main()
