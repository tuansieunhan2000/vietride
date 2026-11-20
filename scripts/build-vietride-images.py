#!/usr/bin/env python3
"""Build optimized, content-versioned WebP derivatives without changing source photos.

Requires Pillow; use a temporary virtualenv rather than adding runtime dependencies.
"""
from pathlib import Path
import argparse
import hashlib
import io
import json
import re
import sys

from PIL import Image, ImageCms, ImageOps, __version__ as pillow_version

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / 'img' / 'responsive'
MANIFEST = OUTPUT / 'manifest.json'


def specifications():
    specs = {}
    aliases = {}
    def add(source, widths, quality=80, lossless=False):
        record = specs.setdefault(source, {'widths': set(), 'quality': quality, 'lossless': lossless})
        record['widths'].update(widths)
        record['quality'] = max(record['quality'], quality)
        record['lossless'] = record['lossless'] or lossless
    for source, widths in {
        'img/Vietride1.webp': [480, 768, 960, 1440],
        'img/Vietride2.webp': [480, 768],
        'img/logo_red_square.webp': [80, 160],
        'img/vietride-story.webp': [480, 960],
        'img/saigon-streets.jpg': [480, 640, 768, 960],
        'img/mekong-delta.jpg': [480, 640, 768, 960],
        'img/saigon-tour-map.webp': [480, 960],
        'img/saigon-tour-stops.webp': [480, 960],
        'img/vietnam-road-map.webp': [480, 960],
    }.items():
        add(source, widths, quality=88 if 'map' in source or 'stops' in source else (78 if source.endswith('.jpg') else 80),
            lossless='logo_' in source)
    bikes = json.loads((ROOT / 'inc/vietride/bikes.json').read_text())
    for bike in bikes:
        for source in bike['images']:
            if source.startswith('img/') and (ROOT / source).is_file():
                add(source, [192])
        cover = bike['thumb']
        original = re.sub(r'-preview\.webp$', '-1.webp', cover)
        if original in bike['images'] and (ROOT / original).is_file():
            aliases[cover] = original
            cover = original
        if cover.startswith('img/') and (ROOT / cover).is_file():
            add(cover, [192, 480, 960])
    return specs, aliases


def open_image(path):
    im = Image.open(path)
    im = ImageOps.exif_transpose(im)
    profile = im.info.get('icc_profile')
    if profile:
        # Preserve apparent colors when removing metadata from published derivatives.
        try:
            im = ImageCms.profileToProfile(im, ImageCms.ImageCmsProfile(io.BytesIO(profile)),
                                          ImageCms.createProfile('sRGB'), outputMode='RGBA' if 'A' in im.getbands() else 'RGB')
        except (ImageCms.PyCMSError, OSError) as e:
            raise RuntimeError(f'Cannot convert embedded profile: {path.name}') from e
    if im.mode not in ('RGB', 'RGBA'):
        im = im.convert('RGBA' if 'transparency' in im.info else 'RGB')
    return im


def build(check=False):
    specs, aliases = specifications()
    manifest = {'version': 1, 'images': {}}
    total = 0
    for source, options in specs.items():
        path = ROOT / source
        im = open_image(path)
        native_width, native_height = im.size
        widths = sorted(set(min(w, native_width) for w in options['widths']))
        # For sources narrower than our largest derivative, include native resolution.
        # This avoids upscaling (the second opening-section photo is only 768 px).
        fingerprint = hashlib.sha256(path.read_bytes() + json.dumps({
            'quality': options['quality'], 'lossless': options['lossless'],
            'method': 6, 'alpha_quality': 100, 'exact': False, 'avif_quality': 75, 'version': 3, 'pillow': pillow_version,
        }, sort_keys=True).encode()).hexdigest()[:12]
        stem = re.sub(r'[^a-z0-9-]+', '-', path.stem.lower()).strip('-')
        entry = {'source': source, 'width': native_width, 'height': native_height, 'variants': []}
        for width in widths:
            height = max(1, round(native_height * width / native_width))
            relative = f'img/responsive/{stem}-{fingerprint}-{width}.webp'
            target = ROOT / relative
            if check:
                assert target.is_file(), f'Missing derivative: {relative}'
            elif not target.is_file():
                resized = im.resize((width, height), Image.Resampling.LANCZOS) if im.size != (width, height) else im.copy()
                target.parent.mkdir(parents=True, exist_ok=True)
                resized.save(target, format='WEBP', quality=options['quality'],
                             lossless=options['lossless'], method=6, alpha_quality=100, exact=False)
            with Image.open(target) as derivative:
                assert derivative.size == (width, height), f'Wrong derivative dimensions: {relative}'
                assert width <= native_width and height <= native_height, f'Upscaled derivative: {relative}'
                assert derivative.mode == im.mode or (derivative.mode == 'RGB' and im.mode == 'RGBA' and im.getextrema()[-1] == (255, 255)), f'Unexpected color mode: {relative}'
            size = target.stat().st_size
            total += size
            entry['variants'].append({'file': relative, 'width': width, 'height': height, 'bytes': size})
        if source in ('img/Vietride1.webp', 'img/Vietride2.webp'):
            entry['avif_variants'] = []
            for width in widths:
                height = max(1, round(native_height * width / native_width))
                relative = f'img/responsive/{stem}-{fingerprint}-{width}.avif'
                target = ROOT / relative
                if check:
                    assert target.is_file(), f'Missing AVIF derivative: {relative}'
                elif not target.is_file():
                    resized = im.resize((width, height), Image.Resampling.LANCZOS) if im.size != (width, height) else im.copy()
                    resized.save(target, format='AVIF', quality=75, speed=6, max_threads=2, subsampling='4:4:4', icc_profile=b'')
                with Image.open(target) as derivative:
                    assert derivative.size == (width, height), f'Wrong AVIF dimensions: {relative}'
                size = target.stat().st_size
                total += size
                entry['avif_variants'].append({'file': relative, 'width': width, 'height': height, 'bytes': size})
        manifest['images'][source] = entry
    for alias, original in aliases.items():
        manifest['images'][alias] = manifest['images'][original]
    serialized = json.dumps(manifest, ensure_ascii=False, indent=2) + '\n'
    if check:
        assert MANIFEST.read_text() == serialized, 'Image manifest is stale; rebuild derivatives'
    else:
        MANIFEST.parent.mkdir(parents=True, exist_ok=True)
        MANIFEST.write_text(serialized)
    print(f'PASS {len(specs)} original images, {sum(len(e["variants"]) + len(e.get("avif_variants", [])) for s,e in manifest["images"].items() if s not in aliases)} derivatives, {len(aliases)} preview aliases')
    print(f'Derivative bytes: {total:,} ({total/1_000_000:.2f} MB); manifest: {len(serialized.encode()):,} bytes')
    print('Original files unchanged; source dimensions preserved; no upscaling; ICC colors converted to sRGB when present.')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--check', action='store_true')
    parser.add_argument('--prune', action='store_true', help='Remove only stale generated derivatives after a build')
    args = parser.parse_args()
    build(args.check)
    if args.prune and not args.check:
        data = json.loads(MANIFEST.read_text())
        referenced = {variant['file'] for entry in data['images'].values() for variant in entry['variants'] + entry.get('avif_variants', [])}
        for target in OUTPUT.iterdir():
            relative = target.relative_to(ROOT).as_posix()
            if relative not in referenced and re.fullmatch(r'[a-z0-9-]+-[0-9a-f]{12}-[0-9]+\.(webp|avif)', target.name): target.unlink()
