#!/usr/bin/env python3
"""Build 500-lesson NIV/KJV curriculum JSON from references + enrichment parts."""

from __future__ import annotations

import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

import time

BOLLS_NIV = "https://bolls.life/get-verse/NIV/{book}/{chapter}/{verse}/"

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
NIV_PATH = DATA / "niv-curriculum.json"
KJV_PATH = DATA / "kjv-curriculum.json"
BOOKS_PATH = DATA / "books.json"
PARTS_DIR = DATA / "curriculum-parts"

# 448 additional single-verse references (lessons 53-500). book_id, chapter, verse.
EXTRA_REFS = [
    (1, 1, 1), (1, 1, 27), (1, 12, 2), (1, 15, 6), (1, 28, 15), (1, 50, 20),
    (2, 3, 14), (2, 14, 14), (2, 20, 12), (2, 33, 19), (2, 34, 6), (2, 34, 7),
    (3, 19, 18), (3, 20, 26), (4, 6, 24), (4, 14, 18), (4, 23, 19), (5, 6, 5),
    (5, 6, 24), (5, 8, 2), (5, 31, 6), (5, 31, 8), (6, 1, 9), (6, 24, 15),
    (7, 6, 24), (7, 7, 20), (8, 1, 16), (9, 2, 2), (9, 16, 7), (9, 17, 47),
    (10, 7, 12), (10, 12, 7), (10, 22, 2), (11, 3, 9), (11, 8, 23), (11, 18, 21),
    (12, 6, 16), (12, 20, 9), (13, 16, 11), (14, 7, 14), (15, 8, 10), (16, 8, 10),
    (17, 4, 14), (18, 1, 21), (18, 19, 25), (18, 42, 5), (19, 1, 1), (19, 19, 14),
    (19, 32, 8), (19, 37, 5), (19, 46, 1), (19, 90, 2), (19, 100, 4), (19, 103, 1),
    (19, 118, 24), (19, 121, 1), (19, 139, 14), (19, 145, 9), (20, 1, 7), (20, 3, 5),
    (20, 3, 6), (20, 4, 23), (20, 9, 10), (20, 16, 3), (20, 16, 9), (20, 22, 6),
    (20, 25, 21), (20, 27, 17), (20, 31, 30), (21, 3, 1), (21, 12, 13), (22, 8, 6),
    (23, 6, 8), (23, 7, 14), (23, 9, 6), (23, 26, 3), (23, 40, 8), (23, 43, 1),
    (23, 53, 5), (23, 55, 8), (23, 61, 1), (24, 1, 5), (24, 17, 7), (24, 29, 11),
    (24, 33, 3), (25, 3, 22), (25, 3, 23), (26, 36, 26), (26, 37, 5), (27, 3, 17),
    (27, 6, 10), (28, 6, 6), (29, 2, 28), (30, 4, 6), (31, 1, 14), (32, 2, 2),
    (33, 6, 8), (34, 3, 17), (35, 2, 4), (36, 3, 17), (37, 3, 10), (38, 4, 6),
    (39, 3, 10), (40, 1, 21), (40, 4, 4), (40, 5, 9), (40, 6, 9), (40, 7, 7),
    (40, 10, 39), (40, 16, 24), (40, 18, 20), (40, 19, 26), (40, 22, 37), (40, 24, 35),
    (40, 26, 41), (41, 10, 45), (41, 12, 30), (41, 16, 15), (42, 1, 37), (42, 2, 10),
    (42, 2, 14), (42, 6, 35), (42, 10, 27), (42, 12, 34), (42, 15, 7), (42, 18, 1),
    (42, 19, 10), (42, 23, 34), (42, 24, 32), (43, 1, 12), (43, 4, 24), (43, 6, 35),
    (43, 7, 24), (43, 8, 12), (43, 10, 10), (43, 11, 35), (43, 13, 34), (43, 14, 6),
    (43, 15, 13), (43, 17, 3), (43, 20, 31), (44, 1, 8), (44, 2, 38), (44, 4, 12),
    (44, 5, 20), (44, 7, 55), (44, 9, 31), (44, 10, 38), (44, 16, 31), (45, 1, 16),
    (45, 3, 23), (45, 4, 7), (45, 5, 1), (45, 6, 14), (45, 8, 31), (45, 10, 13),
    (45, 12, 1), (45, 12, 2), (45, 13, 8), (45, 14, 8), (45, 15, 4), (46, 1, 18),
    (46, 2, 9), (46, 10, 13), (46, 13, 4), (46, 15, 10), (46, 15, 55), (47, 5, 17),
    (47, 9, 6), (47, 12, 9), (48, 2, 20), (48, 5, 1), (48, 6, 14), (49, 1, 3),
    (49, 3, 20), (49, 4, 4), (49, 4, 32), (49, 5, 1), (49, 6, 10), (50, 1, 21),
    (50, 2, 5), (50, 3, 13), (50, 4, 13), (51, 1, 15), (51, 2, 6), (51, 3, 1),
    (51, 3, 16), (51, 4, 5), (52, 1, 3), (52, 2, 19), (52, 4, 16), (52, 5, 11),
    (53, 1, 5), (53, 2, 13), (53, 4, 3), (53, 5, 8), (54, 1, 15), (54, 2, 1),
    (54, 4, 12), (54, 6, 12), (55, 1, 7), (55, 3, 16), (55, 4, 7), (56, 2, 11),
    (56, 3, 5), (57, 1, 6), (58, 4, 12), (58, 4, 16), (58, 10, 24), (58, 11, 6),
    (58, 13, 5), (59, 1, 5), (59, 2, 17), (59, 4, 10), (59, 5, 16), (60, 1, 3),
    (60, 2, 9), (60, 3, 15), (60, 4, 8), (60, 5, 7), (61, 1, 3), (61, 3, 9),
    (61, 3, 18), (62, 1, 9), (62, 4, 8), (62, 4, 10), (63, 1, 5), (64, 1, 7),
    (65, 1, 24), (66, 1, 3), (66, 2, 10), (66, 3, 20), (66, 4, 8), (66, 5, 5),
    (66, 7, 9), (66, 12, 11), (66, 19, 11), (66, 21, 5), (66, 22, 12),
    # Continue filling to 448 entries — second pass through OT/NT highlights
    (1, 2, 7), (1, 3, 15), (1, 4, 9), (1, 6, 8), (1, 9, 16), (1, 17, 1),
    (1, 22, 14), (1, 24, 27), (1, 32, 28), (1, 39, 2), (1, 45, 5), (2, 12, 13),
    (2, 15, 2), (2, 16, 20), (2, 19, 5), (2, 25, 8), (2, 29, 45), (3, 11, 45),
    (3, 17, 11), (4, 21, 4), (4, 22, 6), (5, 4, 29), (5, 5, 16), (5, 10, 12),
    (5, 13, 3), (5, 18, 13), (5, 28, 9), (5, 30, 19), (5, 32, 39), (6, 21, 25),
    (7, 13, 22), (8, 2, 12), (9, 12, 24), (9, 15, 22), (10, 5, 4), (10, 23, 2),
    (11, 4, 6), (11, 17, 14), (11, 19, 12), (12, 2, 11), (12, 5, 14), (13, 4, 10),
    (14, 20, 9), (15, 1, 3), (16, 4, 6), (17, 8, 8), (18, 28, 28), (18, 38, 1),
    (19, 4, 8), (19, 8, 1), (19, 16, 11), (19, 18, 2), (19, 25, 4), (19, 29, 11),
    (19, 33, 12), (19, 40, 1), (19, 42, 11), (19, 55, 22), (19, 62, 5), (19, 73, 26),
    (19, 84, 10), (19, 91, 1), (19, 95, 1), (19, 96, 1), (19, 103, 8), (19, 107, 1),
    (19, 111, 1), (19, 116, 1), (19, 119, 11), (19, 130, 5), (19, 133, 1), (19, 139, 23),
    (19, 143, 8), (19, 150, 6), (20, 2, 6), (20, 10, 12), (20, 11, 14), (20, 12, 25),
    (20, 14, 12), (20, 15, 1), (20, 17, 17), (20, 18, 10), (20, 19, 21), (20, 21, 1),
    (20, 23, 7), (20, 28, 13), (20, 29, 18), (20, 30, 5), (21, 1, 2), (21, 4, 12),
    (21, 7, 8), (21, 11, 5), (22, 2, 4), (22, 3, 1), (23, 1, 18), (23, 12, 2),
    (23, 25, 8), (23, 30, 15), (23, 35, 4), (23, 38, 17), (23, 49, 6), (23, 53, 6),
    (23, 57, 15), (23, 58, 11), (23, 60, 1), (23, 62, 11), (23, 64, 4), (23, 66, 1),
    (24, 3, 15), (24, 9, 23), (24, 10, 6), (24, 15, 16), (24, 23, 6), (24, 31, 3),
    (24, 32, 17), (25, 1, 3), (25, 2, 19), (25, 5, 19), (26, 11, 19), (26, 18, 32),
    (26, 34, 26), (26, 47, 14), (27, 1, 8), (27, 9, 9), (27, 12, 3), (28, 2, 6),
    (28, 10, 12), (28, 14, 1), (29, 2, 13), (29, 3, 10), (30, 5, 24), (31, 4, 5),
    (32, 1, 17), (32, 4, 2), (33, 5, 15), (34, 1, 7), (35, 3, 17), (36, 2, 3),
    (37, 2, 4), (38, 1, 3), (39, 1, 14), (40, 3, 2), (40, 5, 4), (40, 7, 12),
    (40, 9, 37), (40, 12, 30), (40, 13, 40), (40, 14, 27), (40, 17, 20), (40, 20, 28),
    (40, 25, 40), (40, 27, 46), (41, 1, 15), (41, 5, 36), (41, 9, 24), (41, 11, 24),
    (41, 13, 31), (41, 14, 36), (41, 15, 34), (42, 1, 47), (42, 4, 18), (42, 5, 32),
    (42, 7, 47), (42, 9, 23), (42, 11, 28), (42, 14, 27), (42, 16, 13), (42, 17, 32),
    (42, 19, 45), (42, 22, 42), (43, 2, 5), (43, 5, 24), (43, 8, 31), (43, 9, 4),
    (43, 12, 32), (43, 13, 35), (43, 16, 33), (43, 17, 17), (43, 18, 37), (43, 21, 15),
    (44, 3, 19), (44, 6, 4), (44, 8, 28), (44, 11, 26), (44, 13, 38), (44, 17, 28),
    (45, 2, 4), (45, 5, 8), (45, 7, 18), (45, 8, 28), (45, 11, 33), (45, 12, 12),
    (45, 13, 10), (45, 14, 33), (45, 16, 20), (46, 3, 16), (46, 6, 19), (46, 9, 24),
    (46, 11, 1), (46, 13, 12), (46, 15, 33), (47, 1, 3), (47, 3, 18), (47, 4, 16),
    (47, 8, 9), (47, 13, 4), (48, 1, 3), (48, 3, 28), (48, 4, 4), (48, 5, 22),
    (49, 2, 8), (49, 2, 10), (49, 5, 22), (49, 6, 12), (50, 1, 6), (50, 2, 8),
    (50, 3, 14), (50, 4, 19), (51, 1, 16), (51, 2, 9), (51, 3, 12), (51, 4, 6),
    (52, 1, 10), (52, 3, 13), (52, 4, 3), (52, 5, 9), (53, 3, 16), (53, 5, 17),
    (54, 3, 16), (54, 4, 8), (54, 6, 6), (55, 1, 9), (55, 2, 15), (55, 4, 18),
    (56, 1, 9), (56, 3, 4), (57, 1, 12), (58, 3, 12), (58, 6, 10), (58, 9, 14),
    (58, 10, 23), (58, 12, 1), (58, 13, 8), (59, 3, 17), (59, 4, 7), (59, 5, 12),
    (60, 1, 15), (60, 2, 24), (60, 3, 18), (60, 4, 10), (60, 5, 10), (61, 2, 9),
    (61, 3, 14), (62, 2, 15), (62, 3, 1), (62, 4, 19), (63, 1, 8), (64, 1, 3),
    (65, 1, 25), (66, 1, 7), (66, 2, 4), (66, 3, 5), (66, 5, 9), (66, 6, 10),
    (66, 8, 12), (66, 14, 13), (66, 15, 3), (66, 17, 17), (66, 19, 6), (66, 20, 15),
    (66, 22, 20),
]


def load_books() -> dict[str, str]:
    return json.loads(BOOKS_PATH.read_text())


def ref_key(book_id: int, chapter: int, verse: int) -> tuple[int, int, int]:
    return (book_id, chapter, verse)


def normalize_entry(entry: dict, lesson: int) -> dict:
    verse = int(entry["verse_start"])
    out = {
        "lesson": lesson,
        "week": lesson,
        "book_id": int(entry["book_id"]),
        "chapter": int(entry["chapter"]),
        "verse_start": verse,
        "verse_end": verse,
        "text": entry.get("text", "").strip(),
        "historical_context": entry.get("historical_context", ""),
        "preceding_narrative": entry.get("preceding_narrative", ""),
        "discussion_questions": entry.get("discussion_questions", []),
    }
    return out



def clean_niv_text(raw: str) -> str:
    text = re.sub(r"<br\s*/?>", " ", raw, flags=re.I)
    text = re.sub(r"<[^>]+>", "", text)
    text = re.sub(r"\s+", " ", text).strip()
    # Drop common section headings prepended to verse 1 lines in some chapters.
    text = re.sub(
        r"^(The Beginning|Judah|Israel|Jerusalem|A prayer of [^:]+:|Psalm \d+|Book \w+)\s+",
        "",
        text,
        flags=re.I,
    )
    return text


def fetch_niv(book_id: int, chapter: int, verse: int) -> str:
    url = BOLLS_NIV.format(book=book_id, chapter=chapter, verse=verse)
    try:
        with urllib.request.urlopen(url, timeout=25) as resp:
            payload = json.loads(resp.read().decode())
            return clean_niv_text(payload.get("text", ""))
    except (urllib.error.URLError, TimeoutError, json.JSONDecodeError, KeyError):
        return ""


def book_category(book_id: int) -> str:
    if book_id <= 5:
        return "Torah"
    if book_id <= 17:
        return "Historical Books"
    if book_id <= 22:
        return "Wisdom Literature"
    if book_id <= 39:
        return "Prophets"
    if book_id <= 43:
        return "Gospels"
    if book_id == 44:
        return "Acts"
    if book_id <= 65:
        return "Epistles"
    return "Revelation"


def rich_enrichment(book_id: int, book: str, chapter: int, verse: int) -> dict:
    ref = f"{book} {chapter}:{verse}"
    category = book_category(book_id)
    testament = "New Testament" if book_id >= 40 else "Old Testament"
    historical_context = (
        f"<p>{ref} sits within the {category} of the {testament}. "
        f"Readers in the ancient world received {book} as Scripture shaped by real communities, "
        f"languages, and historical pressures that color how this line speaks.</p>"
        f"<p>Studying the setting of {book} clarifies why this verse mattered to its first audience "
        "and keeps our interpretation anchored in the Bible's own story rather than modern slogans.</p>"
    )
    preceding_narrative = (
        f"<p>The flow of {book} {chapter} builds toward verse {verse} through teaching, narrative, "
        "or prayer that the author expects readers to hold in mind.</p>"
        f"<p>Tracing the lines immediately before {ref} shows how this verse answers a question, "
        "turns a story, or applies a promise within the chapter's larger movement.</p>"
    )
    return {
        "historical_context": historical_context,
        "preceding_narrative": preceding_narrative,
        "discussion_questions": [
            f"What does {ref} reveal about God's character or purposes?",
            f"How do the earlier lines in {book} {chapter} shape your understanding of this verse?",
            "What concrete step of obedience, trust, or encouragement does this Scripture invite this week?",
        ],
    }


def fetch_kjv(book: str, chapter: int, verse: int) -> str:
    ref = f"{book} {chapter}:{verse}"
    url = f"https://bible-api.com/{urllib.parse.quote(ref)}?translation=kjv"
    try:
        with urllib.request.urlopen(url, timeout=20) as resp:
            payload = json.loads(resp.read().decode())
            return payload.get("text", "").strip().replace("\n", " ")
    except (urllib.error.URLError, TimeoutError, json.JSONDecodeError):
        return ""


def template_enrichment(book: str, chapter: int, verse: int) -> dict:
    ref = f"{book} {chapter}:{verse}"
    return {
        "historical_context": (
            f"<p>{ref} comes from the biblical witness to God's work among his people. "
            f"Understanding the original setting of {book} helps readers hear this verse "
            "in its literary and historical context rather than as an isolated slogan.</p>"
        ),
        "preceding_narrative": (
            f"<p>The surrounding passage in {book} {chapter} develops the theme that leads "
            f"into verse {verse}. Read the full chapter to see how this verse fits the "
            "author's argument, story, or prayer.</p>"
        ),
        "discussion_questions": [
            f"What stands out to you most in {ref}?",
            f"How does the message of {book} {chapter} shape the meaning of this verse?",
            "What response is God inviting from you through this Scripture?",
        ],
    }


def load_parts() -> dict[int, dict]:
    merged: dict[int, dict] = {}
    text_only: dict[int, str] = {}
    if not PARTS_DIR.is_dir():
        return merged

    for path in sorted(PARTS_DIR.glob("*.json")):
        data = json.loads(path.read_text())
        if isinstance(data, dict):
            for key, value in data.items():
                if str(key).isdigit() and isinstance(value, str):
                    text_only[int(key)] = value
            continue
        if not isinstance(data, list):
            continue
        for entry in data:
            if not isinstance(entry, dict):
                continue
            lesson = int(entry.get("lesson", 0))
            if lesson:
                merged[lesson] = entry

    if text_only:
        for lesson, text in text_only.items():
            if lesson in merged:
                merged[lesson]["text"] = text
            else:
                merged[lesson] = {"lesson": lesson, "text": text}

    return merged


def build() -> None:
    if NIV_PATH.is_file():
        existing = json.loads(NIV_PATH.read_text())
        if len(existing) >= 500 and all(e.get('text') for e in existing):
            print(f'NIV curriculum complete ({len(existing)} lessons); rebuilding KJV only')
            books = load_books()
            kjv_lessons = []
            for entry in existing:
                book_name = books[str(entry['book_id'])]
                kjv_text = fetch_kjv(book_name, entry['chapter'], entry['verse_start'])
                kjv_entry = dict(entry)
                kjv_entry['text'] = kjv_text or entry.get('text', '')
                kjv_lessons.append(kjv_entry)
            KJV_PATH.write_text(json.dumps(kjv_lessons, indent=2, ensure_ascii=False) + '\n')
            print(f'KJV rebuilt: {len(kjv_lessons)} lessons')
            return

    books = load_books()
    existing = json.loads(NIV_PATH.read_text())
    parts = load_parts()
    used: set[tuple[int, int, int]] = set()
    lessons: list[dict] = []

    for idx, entry in enumerate(existing, start=1):
        was_multi = int(entry.get("verse_end", entry["verse_start"])) != int(entry["verse_start"])
        normalized = normalize_entry(entry, idx)
        used.add(ref_key(normalized["book_id"], normalized["chapter"], normalized["verse_start"]))
        if idx in parts:
            normalized.update({k: v for k, v in parts[idx].items() if k not in ("lesson", "book_id", "chapter", "verse_start", "verse_end")})
        if was_multi:
            niv = fetch_niv(normalized["book_id"], normalized["chapter"], normalized["verse_start"])
            if niv:
                normalized["text"] = niv
        lessons.append(normalized)

    lesson_num = 53
    for book_id, chapter, verse in EXTRA_REFS:
        if lesson_num > 500:
            break
        key = ref_key(book_id, chapter, verse)
        if key in used:
            continue
        used.add(key)
        book_name = books[str(book_id)]
        base = {
            "lesson": lesson_num,
            "week": lesson_num,
            "book_id": book_id,
            "chapter": chapter,
            "verse_start": verse,
            "verse_end": verse,
            "text": "",
            "historical_context": "",
            "preceding_narrative": "",
            "discussion_questions": [],
        }
        if lesson_num in parts:
            part = parts[lesson_num]
            base.update({k: v for k, v in part.items() if k not in ("lesson", "book_id", "chapter", "verse_start", "verse_end")})
        if not base.get("text") or not base.get("historical_context"):
            enrich = rich_enrichment(book_id, book_name, chapter, verse)
            if not base["historical_context"]:
                base["historical_context"] = enrich["historical_context"]
            if not base["preceding_narrative"]:
                base["preceding_narrative"] = enrich["preceding_narrative"]
            if not base["discussion_questions"]:
                base["discussion_questions"] = enrich["discussion_questions"]
        lessons.append(base)
        lesson_num += 1

    if len(lessons) < 500:
        print(f"WARNING: only {len(lessons)} lessons generated; need {500 - len(lessons)} more references", file=sys.stderr)

    lessons = lessons[:500]
    lessons.sort(key=lambda e: e["lesson"])

    for entry in lessons:
        if entry.get("text"):
            continue
        entry["text"] = fetch_niv(entry["book_id"], entry["chapter"], entry["verse_start"])
        time.sleep(0.12)
        print(f"NIV {entry['lesson']}/500", end="\r")

    missing_niv = [e for e in lessons if not e.get("text")]
    if missing_niv:
        print(f"WARNING: {len(missing_niv)} NIV lessons still missing text", file=sys.stderr)

    for entry in lessons:
        entry.setdefault("week", entry["lesson"])

    DATA.mkdir(parents=True, exist_ok=True)
    NIV_PATH.write_text(json.dumps(lessons, indent=2, ensure_ascii=False) + "\n")

    kjv_lessons: list[dict] = []
    for entry in lessons:
        book_name = books[str(entry["book_id"])]
        kjv_text = fetch_kjv(book_name, entry["chapter"], entry["verse_start"])
        kjv_entry = dict(entry)
        kjv_entry["text"] = kjv_text or entry.get("text", "")
        kjv_lessons.append(kjv_entry)
        print(f"KJV {entry['lesson']}/500", end="\r")

    print()
    KJV_PATH.write_text(json.dumps(kjv_lessons, indent=2, ensure_ascii=False) + "\n")

    verse_count = sum(e["verse_end"] - e["verse_start"] + 1 for e in lessons)
    print(f"Built {len(lessons)} lessons, {verse_count} NIV verses")


if __name__ == "__main__":
    build()
