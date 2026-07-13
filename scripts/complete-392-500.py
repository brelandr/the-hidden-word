#!/usr/bin/env python3
"""Build lessons-392-500.json from NIV text map + reference list."""

import json
import re
import ast
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PARTS = ROOT / "data" / "curriculum-parts"
TEXTS = PARTS / "_texts-392-500.json"
BOOKS = json.loads((ROOT / "data" / "books.json").read_text())
BUILD = (ROOT / "scripts" / "build-500-curriculum.py").read_text()
EXTRA = ast.literal_eval("[" + re.search(r"EXTRA_REFS = \[(.*?)\]", BUILD, re.S).group(1) + "]")

existing = json.loads((ROOT / "data" / "niv-curriculum.json").read_text())
used = {(e["book_id"], e["chapter"], e["verse_start"]) for e in existing}
refs = []
for book_id, chapter, verse in EXTRA:
    key = (book_id, chapter, verse)
    if key in used:
        continue
    used.add(key)
    refs.append(key)
    if len(refs) >= 448:
        break

texts = json.loads(TEXTS.read_text())
lessons = []

for lesson in range(392, 501):
    book_id, chapter, verse = refs[lesson - 53]
    book = BOOKS[str(book_id)]
    ref = f"{book} {chapter}:{verse}"
    text = texts.get(str(lesson), "")
    lessons.append(
        {
            "lesson": lesson,
            "book_id": book_id,
            "chapter": chapter,
            "verse_start": verse,
            "verse_end": verse,
            "text": text,
            "historical_context": (
                f"<p>{ref} speaks within the broader witness of {book}, written for God's people "
                f"navigating faithfulness in their historical setting. Understanding the original audience "
                "helps this verse land with depth rather than as a detached slogan.</p>"
            ),
            "preceding_narrative": (
                f"<p>The passage surrounding {book} {chapter} builds toward verse {verse}. "
                "Read the full chapter to see how this line fits the author's message—whether narrative, "
                "prophecy, poetry, or apostolic instruction.</p>"
            ),
            "discussion_questions": [
                f"What phrase in {ref} stands out most to you, and why?",
                f"How does the context of {book} {chapter} shape the meaning of this verse?",
                "What concrete step of obedience or trust is God inviting this week?",
            ],
        }
    )

out = PARTS / "lessons-392-500.json"
out.write_text(json.dumps(lessons, indent=2, ensure_ascii=False) + "\n")
print(f"Wrote {len(lessons)} lessons to {out}")
