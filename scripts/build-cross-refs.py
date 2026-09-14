#!/usr/bin/env python3
"""Build a compact TSK-style cross-ref index for HWBL from CrossReferences.org TSV (CC BY 4.0)."""

from __future__ import annotations

import json
import re
import urllib.request
from collections import defaultdict
from pathlib import Path

TSV_URL = (
    "https://raw.githubusercontent.com/CrossReferences-org/"
    "bible-cross-references/main/tsv/crossreferences_kjv.tsv"
)

# OpenBible / CrossReferences English abbreviations → Protestant book IDs 1–66
ABBREV = {
    "gen": 1, "genesis": 1,
    "exod": 2, "ex": 2, "exodus": 2,
    "lev": 3, "leviticus": 3,
    "num": 4, "numbers": 4,
    "deut": 5, "deuteronomy": 5,
    "josh": 6, "joshua": 6,
    "judg": 7, "judges": 7,
    "ruth": 8,
    "1sam": 9, "1 sam": 9, "1samuel": 9,
    "2sam": 10, "2 sam": 10, "2samuel": 10,
    "1kgs": 11, "1kings": 11, "1 kings": 11, "1kin": 11,
    "2kgs": 12, "2kings": 12, "2 kings": 12, "2kin": 12,
    "1chr": 13, "1chron": 13, "1 chronicles": 13,
    "2chr": 14, "2chron": 14, "2 chronicles": 14,
    "ezra": 15,
    "neh": 16, "nehemiah": 16,
    "esth": 17, "est": 17, "esther": 17,
    "job": 18,
    "ps": 19, "psa": 19, "psalm": 19, "psalms": 19,
    "prov": 20, "proverbs": 20,
    "eccl": 21, "ecc": 21, "ecclesiastes": 21,
    "song": 22, "songs": 22, "cant": 22, "sos": 22,
    "isa": 23, "isaiah": 23,
    "jer": 24, "jeremiah": 24,
    "lam": 25, "lamentations": 25,
    "ezek": 26, "eze": 26, "ezekiel": 26,
    "dan": 27, "daniel": 27,
    "hos": 28, "hosea": 28,
    "joel": 29,
    "amos": 30,
    "obad": 31, "obadiah": 31,
    "jonah": 32, "jon": 32,
    "mic": 33, "micah": 33,
    "nah": 34, "nahum": 34,
    "hab": 35, "habakkuk": 35,
    "zeph": 36, "zep": 36, "zephaniah": 36,
    "hag": 37, "haggai": 37,
    "zech": 38, "zec": 38, "zechariah": 38,
    "mal": 39, "malachi": 39,
    "matt": 40, "mat": 40, "matthew": 40,
    "mark": 41, "mar": 41, "mrk": 41,
    "luke": 42, "luk": 42,
    "john": 43, "joh": 43, "jhn": 43,
    "acts": 44, "act": 44,
    "rom": 45, "romans": 45,
    "1cor": 46, "1 cor": 46, "1corinthians": 46,
    "2cor": 47, "2 cor": 47, "2corinthians": 47,
    "gal": 48, "galatians": 48,
    "eph": 49, "ephesians": 49,
    "phil": 50, "php": 50, "philippians": 50,
    "col": 51, "colossians": 51,
    "1thess": 52, "1 thess": 52, "1th": 52, "1thessalonians": 52,
    "2thess": 53, "2 thess": 53, "2th": 53, "2thessalonians": 53,
    "1tim": 54, "1 tim": 54, "1timothy": 54,
    "2tim": 55, "2 tim": 55, "2timothy": 55,
    "titus": 56, "tit": 56,
    "phlm": 57, "philemon": 57, "phm": 57,
    "heb": 58, "hebrews": 58,
    "jas": 59, "james": 59,
    "1pet": 60, "1 pet": 60, "1peter": 60, "1pe": 60,
    "2pet": 61, "2 pet": 61, "2peter": 61, "2pe": 61,
    "1john": 62, "1 john": 62, "1jn": 62, "1jhn": 62,
    "2john": 63, "2 john": 63, "2jn": 63, "2jhn": 63,
    "3john": 64, "3 john": 64, "3jn": 64, "3jhn": 64,
    "jude": 65,
    "rev": 66, "revelation": 66, "re": 66,
}

REF_RE = re.compile(
    r"^\s*(?P<book>\d?\s?[A-Za-z]+)\s+(?P<ch>\d+):(?P<v1>\d+)(?:-(?P<v2>\d+))?\s*$"
)
MAX_PER_VERSE = 16


def norm_book(name: str) -> str:
    s = name.strip().lower()
    s = re.sub(r"\s+", " ", s)
    s = s.replace(".", "")
    # "1 John" / "1John"
    s2 = s.replace(" ", "")
    return s2 if s2 in ABBREV else s


def book_id(name: str) -> int:
    key = norm_book(name)
    if key in ABBREV:
        return ABBREV[key]
    # try without spaces already handled; try first token variants
    return ABBREV.get(key.replace(" ", ""), 0)


def parse_ref(token: str) -> list[tuple[int, int, int]]:
    token = token.strip()
    if not token:
        return []
    m = REF_RE.match(token)
    if not m:
        return []
    bid = book_id(m.group("book"))
    if bid < 1:
        return []
    ch = int(m.group("ch"))
    v1 = int(m.group("v1"))
    v2 = int(m.group("v2") or v1)
    if v2 < v1:
        v1, v2 = v2, v1
    # Store only endpoints for ranges longer than 3 (keep pack compact)
    if v2 - v1 > 2:
        return [(bid, ch, v1), (bid, ch, v2)]
    return [(bid, ch, v) for v in range(v1, v2 + 1)]


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    out_dir = root / "data" / "cross-refs"
    out_dir.mkdir(parents=True, exist_ok=True)

    print("Downloading TSV…")
    with urllib.request.urlopen(TSV_URL, timeout=120) as resp:
        raw = resp.read().decode("utf-8", errors="replace")

    lines = raw.splitlines()
    index: dict[str, list[dict]] = defaultdict(list)
    seen: dict[str, set[tuple[int, int, int]]] = defaultdict(set)
    skipped = 0
    parsed_rows = 0

    for i, line in enumerate(lines):
        if i == 0 and line.lower().startswith("book\t"):
            continue
        parts = line.split("\t")
        if len(parts) < 5:
            skipped += 1
            continue
        src_book, ch_s, vs_s, _anchor, refs_blob = parts[0], parts[1], parts[2], parts[3], parts[4]
        bid = book_id(src_book)
        try:
            ch = int(ch_s)
            vs = int(vs_s)
        except ValueError:
            skipped += 1
            continue
        if bid < 1 or ch < 1 or vs < 1:
            skipped += 1
            continue
        key = f"{bid}-{ch}-{vs}"
        for token in refs_blob.split("|"):
            for tb, tc, tv in parse_ref(token):
                tup = (tb, tc, tv)
                if tup in seen[key]:
                    continue
                if len(index[key]) >= MAX_PER_VERSE:
                    break
                seen[key].add(tup)
                index[key].append({"book_id": tb, "chapter": tc, "verse": tv})
        parsed_rows += 1

    # Stable sort keys numerically
    def sort_key(k: str):
        a, b, c = k.split("-")
        return int(a), int(b), int(c)

    compact = {k: index[k] for k in sorted(index.keys(), key=sort_key)}
    import gzip
    payload = json.dumps(compact, separators=(",", ":")).encode("utf-8")
    out_path = out_dir / "index.json.gz"
    with gzip.open(out_path, "wb", compresslevel=9) as fh:
        fh.write(payload)
    meta = {
        "source": "CrossReferences.org TSK-derived (CC BY 4.0)",
        "source_url": "https://github.com/CrossReferences-org/bible-cross-references",
        "built_from": "tsv/crossreferences_kjv.tsv",
        "verse_keys": len(compact),
        "total_links": sum(len(v) for v in compact.values()),
        "max_per_verse": MAX_PER_VERSE,
        "attribution": (
            "Cross-reference data © CrossReferences.org, derived from the "
            "public-domain Treasury of Scripture Knowledge. Licensed CC BY 4.0."
        ),
    }
    (out_dir / "ATTRIBUTION.md").write_text(
        "# Cross-reference attribution\n\n"
        + meta["attribution"]
        + f"\n\nSource: {meta['source_url']}\n"
        + f"Pack keys: {meta['verse_keys']:,}\n"
        + f"Links: {meta['total_links']:,}\n"
        + f"Cap: {MAX_PER_VERSE} refs/verse\n",
        encoding="utf-8",
    )
    (out_dir / "meta.json").write_text(json.dumps(meta, indent=2) + "\n", encoding="utf-8")
    print(
        f"Wrote {out_path} keys={len(compact)} links={meta['total_links']} "
        f"rows={parsed_rows} skipped={skipped} size={out_path.stat().st_size}"
    )


if __name__ == "__main__":
    main()
