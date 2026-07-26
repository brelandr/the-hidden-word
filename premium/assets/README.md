# Plugin Assets — The Hidden Word Premium

Premium-branded variants of the free plugin artwork (navy `#1a5276`, cream, gold palette with **PREMIUM** badge).

## WordPress / distribution

| File | Dimensions | Description |
|------|------------|-------------|
| `icon-128x128.png` | 128×128 | Plugin icon |
| `icon-256x256.png` | 256×256 | Retina icon |
| `banner-772x250.png` | 772×250 | Banner |
| `banner-1544x500.png` | 1544×500 | Retina banner |

## WooCommerce store (`store/`)

| File | Dimensions | WooCommerce use |
|------|------------|-----------------|
| `store/product-main-800x800.png` | 800×800 | **Product image** (main) |
| `store/product-feature-1200x628.png` | 1200×628 | **Product gallery** image #1 |

Regenerate all assets:

```bash
cd The-Hidden-Word-Premium
python3 scripts/generate-assets.py
```

Requires Pillow: `pip install pillow`
