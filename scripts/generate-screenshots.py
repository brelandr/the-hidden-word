#!/usr/bin/env python3
"""Generate WordPress.org readme screenshots for The Hidden Word (mock UI)."""

import textwrap
from pathlib import Path

try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    raise SystemExit('Install Pillow: pip install pillow')

OUT = Path(__file__).resolve().parents[1] / 'docs' / 'screenshots'
W, H = 1200, 900
NAVY = (26, 82, 118)
NAVY_DARK = (20, 60, 90)
CREAM = (245, 240, 230)
GOLD = (212, 175, 55)
WHITE = (255, 255, 255)
GRAY = (120, 120, 120)
ADMIN_BG = (240, 240, 241)
ADMIN_SIDEBAR = (30, 30, 30)


def font(size: int, bold: bool = False):
    names = [
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf' if bold else '/System/Library/Fonts/Supplemental/Arial.ttf',
        '/Library/Fonts/Arial Bold.ttf' if bold else '/Library/Fonts/Arial.ttf',
        'arial.ttf',
    ]
    for name in names:
        try:
            return ImageFont.truetype(name, size)
        except OSError:
            continue
    return ImageFont.load_default()


def wrap_text(text: str, width: int = 70) -> str:
    return '\n'.join(textwrap.wrap(text, width=width))


def draw_wrapped(d, xy, text, font_obj, fill, max_width_chars=70, spacing=8):
    d.multiline_text(xy, wrap_text(text, max_width_chars), font=font_obj, fill=fill, spacing=spacing)


def rounded_rect(draw, xy, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


def screenshot_1_tabs():
    img = Image.new('RGB', (W, H), CREAM)
    d = ImageDraw.Draw(img)
    d.rectangle([0, 0, W, 72], fill=NAVY)
    d.text((40, 22), "Today's Lesson", font=font(28, True), fill=WHITE)
    tabs = ['The Blueprint', 'The Context', 'The Narrative', 'The Echo', 'Discussion']
    x = 40
    for i, tab in enumerate(tabs):
        bg = GOLD if i == 0 else WHITE
        fg = NAVY
        rounded_rect(d, [x, 100, x + 170, 140], 8, bg, NAVY, 2)
        d.text((x + 14, 112), tab, font=font(14, i == 0), fill=fg)
        x += 182
    rounded_rect(d, [40, 160, W - 40, 520], 12, WHITE, GOLD, 2)
    d.text((60, 190), 'John 3:16 (NIV)', font=font(22, True), fill=NAVY)
    verse = (
        'For God so loved the world that he gave his one and only Son, '
        'that whoever believes in him shall not perish but have eternal life.'
    )
    draw_wrapped(d, (60, 240), verse, font(20), (40, 40, 40), max_width_chars=58)
    d.text((60, 360), 'Memorization practice', font=font(16, True), fill=NAVY)
    rounded_rect(d, [60, 390, W - 60, 490], 8, (252, 250, 245), GOLD, 1)
    d.text((80, 420), 'For God so loved the _____ that he gave his _____ and only Son…', font=font(18), fill=(50, 50, 50))
    d.text((60, 540), 'Scripture quotations marked NIV are from THE HOLY BIBLE, NEW INTERNATIONAL VERSION®…', font=font(11), fill=GRAY)
    img.save(OUT / 'screenshot-1.png', optimize=True)


def screenshot_2_memorization():
    img = Image.new('RGB', (W, H), CREAM)
    d = ImageDraw.Draw(img)
    d.text((40, 40), 'Memorization Widget', font=font(26, True), fill=NAVY)
    rounded_rect(d, [40, 100, W - 40, 620], 12, WHITE, GOLD, 2)
    lines = [
        'For God so loved the [ world ] that he gave his [ one ] and only Son,',
        'that whoever [ believes ] in him shall not [ perish ] but have eternal life.',
    ]
    y = 150
    for line in lines:
        d.text((70, y), line, font=font(22), fill=(40, 40, 40))
        y += 60
    rounded_rect(d, [70, 320, 250, 370], 8, GOLD, NAVY, 2)
    d.text((100, 335), 'Check answers', font=font(16, True), fill=NAVY)
    rounded_rect(d, [270, 320, 400, 370], 8, WHITE, NAVY, 2)
    d.text((300, 335), 'Reveal all', font=font(16), fill=NAVY)
    d.text((70, 420), 'Tip: hide key words, then speak the verse aloud from memory.', font=font(16), fill=GRAY)
    img.save(OUT / 'screenshot-2.png', optimize=True)


def screenshot_3_verse_week():
    img = Image.new('RGB', (W, H), (250, 250, 252))
    d = ImageDraw.Draw(img)
    rounded_rect(d, [80, 120, 520, 360], 12, WHITE, GOLD, 3)
    d.text((110, 150), 'Verse of the Week', font=font(18, True), fill=NAVY)
    d.text((110, 200), 'Romans 8:28', font=font(24, True), fill=GOLD)
    draw_wrapped(
        d,
        (110, 250),
        'And we know that in all things God works for the good of those who love him…',
        font(18),
        (50, 50, 50),
        max_width_chars=36,
    )
    d.text((80, 420), 'Sidebar widget — [thw_verse_of_week]', font=font(14), fill=GRAY)
    img.save(OUT / 'screenshot-3.png', optimize=True)


def admin_chrome(d, title):
    d.rectangle([0, 0, W, 32], fill=NAVY_DARK)
    d.rectangle([0, 32, 200, H], fill=ADMIN_SIDEBAR)
    d.text((20, 80), 'Dashboard', font=font(13), fill=(200, 200, 200))
    d.text((20, 110), 'The Hidden Word', font=font(13, True), fill=WHITE)
    d.text((20, 135), '  Bible Lessons', font=font(12), fill=GOLD)
    d.text((20, 160), '  Settings', font=font(12), fill=(200, 200, 200))
    d.rectangle([200, 32, W, 90], fill=WHITE)
    d.text((220, 52), title, font=font(22, True), fill=(30, 30, 30))


def screenshot_4_editor():
    img = Image.new('RGB', (W, H), ADMIN_BG)
    d = ImageDraw.Draw(img)
    admin_chrome(d, 'Edit Bible Lesson — Lesson 1: John 3:16')
    rounded_rect(d, [220, 110, W - 30, 280], 8, WHITE, (200, 200, 200), 1)
    d.text((240, 130), 'Verse Reference', font=font(16, True), fill=NAVY)
    fields = ['Lesson number: 1', 'Book: John', 'Chapter: 3', 'Verse: 16']
    y = 165
    for f in fields:
        d.text((240, y), f, font=font(14), fill=(50, 50, 50))
        y += 28
    rounded_rect(d, [220, 300, W - 30, 500], 8, WHITE, (200, 200, 200), 1)
    d.text((240, 320), 'Lesson Content', font=font(16, True), fill=NAVY)
    draw_wrapped(
        d,
        (240, 355),
        'Historical context: Jesus speaks to Nicodemus about being born again… '
        'Preceding narrative: After cleansing the temple, Jesus begins teaching…',
        font(13),
        (60, 60, 60),
        max_width_chars=72,
    )
    rounded_rect(d, [220, 520, W - 30, 680], 8, WHITE, (200, 200, 200), 1)
    d.text((240, 540), 'Discussion Questions', font=font(16, True), fill=NAVY)
    d.text((240, 575), '1. What does “eternal life” mean in this passage?', font=font(13), fill=(60, 60, 60))
    img.save(OUT / 'screenshot-4.png', optimize=True)


def screenshot_5_settings():
    img = Image.new('RGB', (W, H), ADMIN_BG)
    d = ImageDraw.Draw(img)
    admin_chrome(d, 'The Hidden Word — Settings')
    rounded_rect(d, [220, 110, W - 30, 520], 8, WHITE, (200, 200, 200), 1)
    d.text((240, 140), 'Schedule mode', font=font(15, True), fill=NAVY)
    rounded_rect(d, [240, 175, 480, 215], 6, (252, 252, 252), (180, 180, 180), 1)
    d.text((255, 185), 'Verse of the Week', font=font(14), fill=(40, 40, 40))
    d.text((240, 240), 'Translation', font=font(15, True), fill=NAVY)
    rounded_rect(d, [240, 275, 480, 315], 6, (252, 252, 252), (180, 180, 180), 1)
    d.text((255, 285), 'NIV (bundled)', font=font(14), fill=(40, 40, 40))
    d.text((240, 350), 'Premium add-on', font=font(15, True), fill=NAVY)
    d.text(
        (240, 385),
        'Learn about The Hidden Word Premium → custom scheduling, PDF guides, API.Bible…',
        font=font(13),
        fill=GOLD,
    )
    img.save(OUT / 'screenshot-5.png', optimize=True)


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    screenshot_1_tabs()
    screenshot_2_memorization()
    screenshot_3_verse_week()
    screenshot_4_editor()
    screenshot_5_settings()
    for path in sorted(OUT.glob('screenshot-*.png')):
        print(path.name, f'{path.stat().st_size // 1024}KB')


if __name__ == '__main__':
    main()
