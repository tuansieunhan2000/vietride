#!/usr/bin/env python3
"""Package public theme files and a <=10MB code/responsive-image update ZIP."""
from pathlib import Path
import re
import zipfile
ROOT = Path(__file__).resolve().parents[1]
FOLDERS = ['assets','css','fonts','global-templates','img','inc','js','languages','loop-templates','page-templates','sidebar-templates','template-parts','woocommerce']
files = list(ROOT.glob('*.php')) + [ROOT/'style.css', ROOT/'screenshot.png', ROOT/'LICENSE']
for folder in FOLDERS:
    files += [p for p in (ROOT/folder).rglob('*') if p.is_file() and not p.is_symlink() and (not p.name.startswith('.') or p.name == '.htaccess')]
files = sorted(set(files))
releases = ROOT/'releases'; releases.mkdir(exist_ok=True)
full = releases/'vietride-theme-2026-11-19.zip'
update = releases/'vietride-upload-04-theme.zip'
with zipfile.ZipFile(full,'w',zipfile.ZIP_DEFLATED) as archive, zipfile.ZipFile(update,'w',zipfile.ZIP_DEFLATED) as patch:
    for file in files:
        data = file.read_bytes()
        assert b'-----BEGIN PRIVATE KEY-----' not in data, file.name
        assert not re.search(r'(service.?account|wp-config|firebase-debug)',file.name,re.I), file.name
        relative = file.relative_to(ROOT).as_posix()
        name = 'vietride/'+relative
        archive.write(file,name)
        if not relative.startswith('img/') or relative.startswith('img/responsive/'):
            patch.write(file,name)
for package in [full,update]:
    with zipfile.ZipFile(package) as archive:
        assert archive.testzip() is None
        for expected in ['img/responsive/.htaccess','inc/vietride/performance.php','inc/vietride/images.php','img/responsive/manifest.json','fonts/remix/vietride-icons.woff2','js/vietride.min.js','css/vietride-base.min.css']:
            assert 'vietride/'+expected in archive.namelist(),expected
        assert not any('node_modules' in n or '/.git/' in n for n in archive.namelist())
        for file in files:
            name='vietride/'+file.relative_to(ROOT).as_posix()
            if name in archive.namelist(): assert archive.read(name)==file.read_bytes(),name
    print(f'PASS {package.name}: {package.stat().st_size/1_000_000:.2f} MB; validated latest theme files')
assert update.stat().st_size < 10_000_000,'Split the responsive assets before uploading to a 10MB host'
