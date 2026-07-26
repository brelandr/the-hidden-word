# Tradition doctrine packs

Curated digests + Yes/No/Conditional stance gates for Premium AI Explain and Study Finder.

## Layout

- `index.json` — `slug → { label, file, version, extends? }` for all selectable presets
- `schema.json` — JSON Schema for pack files
- `{slug}.json` — one pack per tradition (plus family bases like `evangelical.json` that are not always in the UI index)
- Optional `"extends": "parent_slug"` — merge parent sources; child stances override by `topic`

## Conventions

1. Digests are **original paraphrases** + public citation pointers. Do not paste Catechism, Canon Law, or full confession copyright text.
2. Stance values: `yes` | `no` | `conditional` | `pastoral` | `silence`.
3. Shared topic taxonomy (used by keyword detection): marriage, divorce, annulment, remarriage, baptism, eucharist, lords_supper, sacraments, confession, confirmation, salvation, scripture_authority, church_authority, sexuality, prayer, holiness, sanctification, spirit_baptism, healing, mission, sabbath, prophecy, additional_scripture.
4. Runtime always injects framing + `general_gates`, then topic-matched citation map rows (capped ~4), sources/stances (capped ~4 / ~6).
5. Prefer `citation_map`: topic → CIC/CCC **numbers** and/or confession **articles** (e.g. BF&M 2000 Art. VII, UPCI Articles of Faith Art. 3) + short `note`. Legacy `canon_map` is still accepted as an alias.
6. Bump `version` in both the pack and `index.json` when content changes so caches invalidate.
7. **UPCI numbering** in `upci.json` follows a condensed Articles of Faith index aligned to curated CSV section numbers (e.g. Art. 3 The One True God, Art. 9 Water Baptism, Art. 12 Holiness). Digests are original paraphrases only—never ship full Manual article text.
8. **Preferred official pointers** (cite identifiers + linkify in Study Finder; never paste full confessions):
   - SBC → Baptist Faith & Message 2000 (`bfm.sbc.net`)
   - Lutheran → Augsburg Confession + LCMS Brief Statement (`lcms.org`)
   - Anglican → Thirty-Nine Articles / BCP Historical Documents
   - Presbyterian/Reformed → Westminster Confession (WCF chapters)
   - Assemblies of God → 16 Fundamental Truths (`ag.org`)
   - UPCI → Articles of Faith (`upci.org`)
   - Eastern Orthodox → Nicene Creed + Seven Ecumenical Councils (`goarch.org/ourfaith`) — not a single handbook dump; not Oriental Miaphysite / First Three only
   - UMC → Articles of Religion (Book of Discipline) — Articles only, not full Discipline ¶ scrape
   - AME → Articles of Religion / Articles of Our Faith (Doctrine and Discipline)
   - Church of the Nazarene → Manual Articles of Faith (`nazarene.org`) — Articles only, not full Manual
   - Seventh-day Adventist → 28 Fundamental Beliefs (`nadadventist.org/beliefs`) — numbered belief pointers only, not full statement dump or EGW quotation dumps
   - Baptist (general) → New Hampshire (1833) / 1689 London Baptist Confession — not BF&M (SBC pack)
   - Mennonite → Confession of Faith in a Mennonite Perspective (1995) (`mennoniteusa.org`)
   - Messianic Jewish → UMJC Statement of Faith (`umjc.org`)
   - LDS → Articles of Faith 1–13 (`churchofjesuschrist.org`) — identifiers only; no BoM/D&C dumps
   - Jehovah’s Witnesses → JW Beliefs (`jw.org`) — no Watchtower article dumps
   - Quaker / Friends → Richmond Declaration of Faith (1887)
   - Churches of Christ → non-creedal NT-pattern teaching identifiers (baptism, Lord’s Supper, autonomy)
   - Salvation Army → 11 Doctrines (`salvationarmyusa.org`)
   - Christian Science → Six Tenets (`christianscience.com`) — no Science and Health dumps
   - General Protestant → Apostles’ Creed + Nicene Creed + Five Solas (WCC creed pages)
   - Non-denominational evangelical → NAE Statement of Faith (`nae.org`)
   - Evangelical Free → EFCA Statement of Faith (`efca.org/sof`)
   - Calvary Chapel → Calvary Chapel Statement of Faith
   - Vineyard → Vineyard USA Core Values & Beliefs (`vineyardusa.org`)
   - Disciples of Christ → Preamble to the Design (`disciples.org`)
   - Congregational / UCC → UCC Statement of Faith (`ucc.org`)
   - Pentecostal / Charismatic → PWF Statement of Faith (`pwfellowship.org`) on classical Pentecostal base
   - Greek / Russian Orthodox → Eastern Orthodox Creed + Seven Councils (`goarch.org/ourfaith`; OCA Essential Teachings) — jurisdiction notes only
   - Oriental Orthodox (standalone) → Nicene Creed + First Three Councils + Miaphysite digests — not Seven Councils / Chalcedon
   - Coptic / Ethiopian / Armenian → extend Oriental Orthodox with local framing
