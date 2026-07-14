#!/usr/bin/env python3
"""Build public-domain WEB/KJV text cache for bundled echo (follow-on) verses."""

from __future__ import annotations

import json
import re
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
NIV_PATH = DATA / "niv-curriculum.json"
BOOKS_PATH = DATA / "books.json"
OUT_PATH = DATA / "echo-verses.json"

BOLLS_CHAPTER_WEB = "https://bolls.life/get-text/WEB/{book}/{chapter}/"


def clean_text(raw: str) -> str:
    text = re.sub(r"<br\s*/?>", " ", raw, flags=re.I)
    text = re.sub(r"<[^>]+>", "", text)
    return re.sub(r"\s+", " ", text).strip()


def fetch_web_chapter(book_id: int, chapter: int) -> dict[int, str]:
    url = BOLLS_CHAPTER_WEB.format(book=book_id, chapter=chapter)
    try:
        with urllib.request.urlopen(url, timeout=30) as resp:
            payload = json.loads(resp.read().decode())
    except (urllib.error.URLError, TimeoutError, json.JSONDecodeError):
        return {}

    verses: dict[int, str] = {}
    if isinstance(payload, list):
        for row in payload:
            if not isinstance(row, dict):
                continue
            verse = int(row.get("verse", 0))
            text = clean_text(str(row.get("text", "")))
            if verse > 0 and text:
                verses[verse] = text
    return verses


def fetch_kjv_chapter(book: str, chapter: int) -> dict[int, str]:
    ref = f"{book} {chapter}"
    url = f"https://bible-api.com/{urllib.parse.quote(ref)}?translation=kjv"
    try:
        with urllib.request.urlopen(url, timeout=30) as resp:
            payload = json.loads(resp.read().decode())
    except (urllib.error.URLError, TimeoutError, json.JSONDecodeError):
        return {}

    verses: dict[int, str] = {}
    for row in payload.get("verses", []):
        if not isinstance(row, dict):
            continue
        verse = int(row.get("verse", 0))
        text = clean_text(str(row.get("text", "")))
        if verse > 0 and text:
            verses[verse] = text
    return verses


def verse_key(book_id: int, chapter: int, verse: int) -> str:
    return f"{book_id}-{chapter}-{verse}"


def collect_refs(lessons: list[dict]) -> set[tuple[int, int, int]]:
    refs: set[tuple[int, int, int]] = set()
    for entry in lessons:
        book_id = int(entry["book_id"])
        chapter = int(entry["chapter"])
        start = int(entry["verse_start"])
        end = int(entry.get("verse_end", start) or start)
        if start > 1:
            refs.add((book_id, chapter, start - 1))
        refs.add((book_id, chapter, end + 1))
    return refs


def main() -> None:
    books = json.loads(BOOKS_PATH.read_text())
    lessons = json.loads(NIV_PATH.read_text())
    refs = collect_refs(lessons)

    by_chapter: dict[tuple[int, int], set[int]] = defaultdict(set)
    for book_id, chapter, verse in refs:
        by_chapter[(book_id, chapter)].add(verse)

    out: dict[str, dict[str, str]] = {}
    if OUT_PATH.is_file():
        existing = json.loads(OUT_PATH.read_text())
        if isinstance(existing, dict):
            out = existing

    chapters = sorted(by_chapter.keys())
    total = len(chapters)

    for idx, (book_id, chapter) in enumerate(chapters, start=1):
        book_name = books[str(book_id)]
        needed = by_chapter[(book_id, chapter)]

        web_chapter = fetch_web_chapter(book_id, chapter)
        time.sleep(0.05)
        kjv_chapter = fetch_kjv_chapter(book_name, chapter)
        time.sleep(0.05)

        for verse in needed:
            key = verse_key(book_id, chapter, verse)
            row: dict[str, str] = out.get(key, {})
            if not row.get("web") and web_chapter.get(verse):
                row["web"] = web_chapter[verse]
            if not row.get("kjv") and kjv_chapter.get(verse):
                row["kjv"] = kjv_chapter[verse]
            if row:
                out[key] = row

        if idx % 25 == 0 or idx == total:
            OUT_PATH.write_text(json.dumps(out, indent=2, ensure_ascii=False) + "\n")

        print(f"{idx}/{total} {book_name} {chapter} ({len(needed)} echo verses)", end="\r")

    print()
    OUT_PATH.write_text(json.dumps(out, indent=2, ensure_ascii=False) + "\n")
    with_text = sum(1 for row in out.values() if row)
    print(f"Wrote {len(out)} echo verses ({with_text} with text) to {OUT_PATH}")


if __name__ == "__main__":
    main()
