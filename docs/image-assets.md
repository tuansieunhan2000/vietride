# Responsive image delivery

Original images and references in the fleet data are preserved. `scripts/build-vietride-images.py` creates content-versioned WebP derivatives in `img/responsive/`, with AVIF alternatives only for the two fixed opening-section images. The helper wraps those two images in picture with a WebP fallback; gallery images remain ordinary img elements so their source switching works normally.

The builder uses Pillow in a temporary virtualenv. It does not require image tooling on hosting. Images are resized proportionally with Lanczos filtering, never enlarged; embedded color profiles are converted to sRGB when present. Original source files are not overwritten. WebP alpha is encoded at quality100; no separate lossy transparency quantization is applied. AVIF uses quality75, 4:4:4 chroma and WebP fallback. JPEG travel photos use WebP quality78. Other WebP photos use quality80; detailed map previews use quality88.

Fleet preview references resolve to the matching original photo1 when available. Gallery non-cover images receive small192px derivatives for thumbnails and retain the original for full-size viewing. Covers receive192/480/960px derivatives and a native-resolution original candidate for large/retina requests. First-section images also receive768 and1440px options where source resolution allows. The second opening photo is768px at native size and is not enlarged.

## PHP API

- `vr_image_html($source, $alt, $attrs)` returns an escaped image tag (or the two fixed picture elements). Existing class, width/height, loading, fetchpriority, sizes and data attributes can be supplied as before. `max_width` is an internal candidate cap and is not output as an HTML attribute.
- `vr_image_data($source, $attrs)` returns src, srcset, sizes and dimensions for gallery switching. Switch srcset/sizes together with src; clear the old attributes when a new source has no responsive data.
- `vr_fleet_image_sizes($source)` accounts for the painted image width inside an object-fit:contain card frame, rather than requesting pixels for the whole wider frame. It uses source/WordPress Media dimensions when available and a safe generic frame width for external images without known metadata.
- WordPress Media URLs use core attachment sizes/srcset when available. External image URLs remain unchanged and are not fetched by the PHP helper.

## Build and check

```sh
python3 -m venv /tmp/vietride-images-py39
/tmp/vietride-images-py39/bin/python -m pip install Pillow
/tmp/vietride-images-py39/bin/python scripts/build-vietride-images.py --prune
/tmp/vietride-images-py39/bin/python scripts/build-vietride-images.py --check
```

The optional prune operation removes only stale generated content-versioned files in img/responsive; it does not touch originals. Ship the manifest and its referenced derivatives together. The2026-11-19 output is110 derivatives totaling4,994,595 bytes plus39,458-byte manifest, from53 source images and18 preview aliases.
