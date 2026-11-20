# VietRide icon subset

`fonts/remix/vietride-icons.css` and `vietride-icons.woff2` contain only the Remix Icon glyphs used by the theme. The original `remixicon.css`, `remixicon.woff2` and license remain intact for pages that need the complete family. The subset keeps the same `remixicon` family, original Unicode codepoints, outlines, widths and vertical metrics, with `font-display: swap`.

Zalo remains the existing SVG mask in `src/sass/custom-style.scss`; it is intentionally not added to the font. The generated font-face has an explicit Unicode range, allowing the original font to remain a fallback for other glyphs if both families are loaded.

The build script scans PHP, the custom Sass and VietRide JavaScript for literal icon classes and `vr_icon()` calls. Dynamic array call sites are explicitly reviewed; actual literal icon names in those files are included automatically. New dynamic call sites fail the build until reviewed. Rebuild after adding icons; the check mode fails when generated selectors are stale.

Install build dependencies only in a temporary environment:

```sh
python3 -m venv /tmp/vietride-icons-venv
/tmp/vietride-icons-venv/bin/python -m pip install 'fonttools[woff]'
/tmp/vietride-icons-venv/bin/python scripts/build-vietride-icons.py
/tmp/vietride-icons-venv/bin/python scripts/build-vietride-icons.py --check
```

If the Homebrew Python installation cannot bootstrap its environment, use `/usr/bin/python3` to create the temporary venv instead. No Python dependencies are needed by WordPress or site visitors. The implementation was built and checked with fontTools 4.60.2 and Brotli 1.2.1 in `/tmp/vietride-icons-py39`, created using `/usr/bin/python3`.

The builder disables bounding-box recalculation to preserve the original font’s origin/side-bearing relationship. Validation checks each required selector, exact font cmap, glyph outlines, bounds, origin flags and horizontal/vertical metrics against the original font; it also verifies that Zalo keeps its SVG implementation. The generated files are regular theme assets and should be included in deployment ZIPs. Only change frontend enqueue integration after testing the first two sections, menu, contact dock, gallery, guide controls and reviews.
