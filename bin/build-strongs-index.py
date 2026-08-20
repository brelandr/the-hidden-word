#!/usr/bin/env python3
"""Build compact Strong's lexicon + occurrence packs for The Hidden Word."""

from __future__ import annotations

import argparse
import gzip
import json
from pathlib import Path


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--source",
        required=True,
        help="Path to upstream strongs.json (ci/sm/s2e/e2s keys)",
    )
    parser.add_argument(
        "--out",
        required=True,
        help="Output directory (data/strongs)",
    )
    args = parser.parse_args()

    source = Path(args.source)
    out_dir = Path(args.out)
    out_dir.mkdir(parents=True, exist_ok=True)

    data = json.loads(source.read_text(encoding="utf-8"))
    ci = data.get("ci") or {}
    sm = data.get("sm") or {}
    s2e = data.get("s2e") or {}
    e2s = data.get("e2s") or {}

    lexicon = {}
    for num, meta in sm.items():
        entry = {
            "w": meta.get("w", ""),
            "t": meta.get("t", ""),
        }
        glosses = s2e.get(num) or []
        if glosses:
            entry["g"] = glosses[0] if isinstance(glosses[0], str) else str(glosses[0])
            if len(glosses) > 1:
                entry["gs"] = [
                    g if isinstance(g, str) else str(g) for g in glosses[:8]
                ]
        lexicon[num] = entry

    (out_dir / "lexicon.json").write_text(
        json.dumps(lexicon, ensure_ascii=False, separators=(",", ":")),
        encoding="utf-8",
    )

    h_ci = {k: v for k, v in ci.items() if str(k).startswith("H")}
    g_ci = {k: v for k, v in ci.items() if str(k).startswith("G")}
    for name, payload in (
        ("occurrences-h.json.gz", h_ci),
        ("occurrences-g.json.gz", g_ci),
    ):
        with gzip.open(out_dir / name, "wt", encoding="utf-8") as handle:
            json.dump(payload, handle, ensure_ascii=False, separators=(",", ":"))

    english = {}
    for eng, pairs in e2s.items():
        nums = []
        for item in pairs:
            if isinstance(item, list) and item:
                nums.append(item[0])
            elif isinstance(item, str):
                nums.append(item)
        if nums:
            english[str(eng).lower()] = nums[:20]

    (out_dir / "english-index.json").write_text(
        json.dumps(english, ensure_ascii=False, separators=(",", ":")),
        encoding="utf-8",
    )

    meta = {
        "source": (
            "Public domain Strong's Exhaustive Concordance (1890); "
            "occurrence index derived from open KJV+Strong's data."
        ),
        "license": "Public domain",
        "counts": {
            "lexicon": len(lexicon),
            "hebrew_occurrences": len(h_ci),
            "greek_occurrences": len(g_ci),
            "english_index": len(english),
        },
    }
    (out_dir / "meta.json").write_text(json.dumps(meta, indent=2) + "\n", encoding="utf-8")
    print(f"Wrote Strong's packs to {out_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
