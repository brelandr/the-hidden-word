# How to test Bible Study features

Prerequisites: deploy plugin **2.3.31+** (or run locally), signed-in WordPress user, companion pointed at the same site. Prefer **BSB** for Hello AO audio timings.

## Phase 1

### Note frameworks (SOAP / HEAR / Inductive)
1. Open Bible reader (web shortcode or companion Bible).
2. Sign in → pick a verse → choose **S.O.A.P.** (or HEAR / Inductive).
3. Fill fields → wait for autosave (web) or tap Save (companion).
4. Reload chapter → fields restore. Journal → Bible tab shows framework label.

### Practice this verse
1. Select a verse.
2. Web: tap **Practice this verse** (opens memorize widget). Keep **Add to Memorize** for queue-only.
3. Companion: Verse actions → **Practice this verse** → lands in MemorizePractice for that verse.

### Related verses (cross-refs)
1. Select **John 3:16** (or Gen 1:1).
2. Open **Related verses** → list appears → tap a ref → navigates.
3. Expect ~dozen refs max per verse from the expanded TSK pack.

### Church notes
1. WP Admin → Bible Lessons → **Shared study notes** → publish a note for a chapter/verse.
2. Reader: notes panel shows **My notes | Church notes** when notes exist.
3. Companion: same toggle when signed in.

## Phase 2

### Tap-to-define
1. Companion: long-press a word, or Verse actions → **Define word**.
2. Web: **Define a word** → enter e.g. `love` → Strong’s candidates.

### Context chips
1. Open any chapter → chips (Author / Audience / Purpose / Date) above text.
2. Tap chip → blurb expands.

### Compare translations
1. Select a verse → **Compare translations** → parallel text focuses that verse.

## Phase 3

### Audio read-along
1. Use BSB (or Hello AO–enabled translation) → Listen / Play.
2. Current verse highlights as audio advances (`timing_source: helloao` when available).
3. If timings missing, highlight still approximates by verse length.

### Tags + decks
1. On a verse note, add tag `grace` (or suggestion chip).
2. Tap tag → MemorizePractice deck filtered by that tag.

### Share with leader
1. Plan day → write reflection → enable **Share with leader** → submit.
2. Plan detail → **Load shared reflections** (leaders/editors).

## Phase 4 / upgrades

### Pastor scheduling & series
1. Shared note with Series + Publish on date in the future → not visible until that day.
2. Type **Intro** → chapter shows **From your church** chip / Church intro badge.

### Bulk import
1. Shared study notes → paste markdown blocks separated by `---`:
   ```
   @ref John 3
   @type intro
   @title Nicodemus
   @series John — Believe
   @published yes

   Short pastoral intro…

   ---

   @ref John 3:16
   @type study
   @title Amazing love

   Body…
   ```
2. Import → notes appear in Recent list.

### Engagement counts
1. Open church notes in the reader a few times while signed in.
2. Admin Shared study notes → Engagement list + Views column increase (no user identities).

## Quick API smoke (optional)

```bash
# Replace SITE and NONCE/cookie as needed
curl -s "$SITE/wp-json/hwbl/v1/bible/cross-refs?book_id=43&chapter=3&verse=16" | head
curl -s "$SITE/wp-json/hwbl/v1/bible/book-intro?book_id=43&chapter=3" | head
curl -s "$SITE/wp-json/hwbl/v1/bible/audio-cues?book_id=43&chapter=3&translation=bsb&narrator=david" | head
```
