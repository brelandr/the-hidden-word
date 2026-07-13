#!/usr/bin/env python3
"""Generate WordPress.org icon and banner PNGs for The Hidden Word."""

from pathlib import Path

try:
    from PIL import Image, ImageDraw
except ImportError:
    raise SystemExit('Install Pillow: pip install pillow')

ASSETS = Path(__file__).resolve().parents[1] / 'assets'
NAVY = (26, 82, 118)
CREAM = (245, 240, 230)
GOLD = (212, 175, 55)


def save_icon(size: int, name: str) -> None:
    img = Image.new('RGBA', (size, size), NAVY)
    draw = ImageDraw.Draw(img)
    margin = size // 8
    draw.rounded_rectangle(
        [margin, margin, size - margin, size - margin],
        radius=size // 10,
        fill=CREAM,
        outline=GOLD,
        width=max(2, size // 64),
    )
    cx = size // 2
    draw.rectangle([cx - size // 16, margin + size // 10, cx + size // 16, size - margin - size // 10], fill=GOLD)
    draw.rectangle([margin + size // 5, cx - size // 20, size - margin - size // 5, cx + size // 20], fill=GOLD)
    img.save(ASSETS / name, optimize=True)


def save_banner(width: int, height: int, name: str) -> None:
    img = Image.new('RGB', (width, height), NAVY)
    draw = ImageDraw.Draw(img)
    draw.rectangle([0, 0, width, height // 5], fill=(20, 60, 90))
    margin = height // 6
    draw.rounded_rectangle([margin, margin, height - margin, height - margin], radius=20, fill=CREAM, outline=GOLD, width=4)
    draw.rectangle([margin + height // 3, margin + 20, height - margin - 20, height - margin - 20], fill=GOLD)
    draw.rectangle([margin + 20, height // 2 - 8, height - margin - 20, height // 2 + 8], fill=GOLD)
    img.save(ASSETS / name, optimize=True)


def main() -> None:
    ASSETS.mkdir(parents=True, exist_ok=True)
    save_icon(256, 'icon-256x256.png')
    save_icon(128, 'icon-128x128.png')
    save_banner(1544, 500, 'banner-1544x500.png')
    save_banner(772, 250, 'banner-772x250.png')
    for path in sorted(ASSETS.glob('*.png')):
        print(path.name, path.stat().st_size)


if __name__ == '__main__':
    main()
