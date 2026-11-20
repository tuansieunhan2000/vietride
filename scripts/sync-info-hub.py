#!/usr/bin/env python3
"""Restore guide data from a captured VietRide Info Hub HTML, without rewriting copy."""
from pathlib import Path
import argparse
import hashlib
import html
import json
import re

ROOT = Path(__file__).resolve().parents[1]
GROUPS = [
    ('Riding by the Rules', 'v-pills-service', ['motorcycle-licence-vietnam', 'drink-driving-vietnam', 'motorcycle-speed-limits-vietnam', 'helmet-requirements-vietnam']),
    ('Scooter Essentials', 'v-pills-rates', ['fuel-guide-vietnam', 'flat-tyre', 'motorcycle-parking-saigon', 'riding-lessons', 'luggage-on-a-scooter', 'breakdown-support']),
    ('Rental Rundown', 'v-pills-rental-rundown', ['exchange-and-early-return', 'rental-insurance', 'traveller-and-commuter-rates', 'long-term-rates', 'minimum-rental-period', 'rental-agreement', 'rental-deposit', 'rental-requirements', 'payment-methods']),
]

def plain(text):
    return html.unescape(re.sub(r'<[^>]+>', '', text)).strip()

def extract(source):
    if 'id="faqModal"' in source:
        start = source.index('<section', source.index('id="faqModal"'))
        source = source[start:source.index('</section>', start) + len('</section>')]
    title = re.search(r'<p class="section__subheader">(.*?)</p>', source, re.S)
    headline = re.search(r'<h2 class="section__header">(.*?)</h2>', source, re.S)
    assert title and headline, 'Info Hub heading missing'
    guides = {}
    for group, identifier, slugs in GROUPS:
        match = re.search(r'<div\s+class="tab-pane[^\"]*"\s+id="' + identifier + r'"', source)
        assert match, identifier
        rest = source[match.end():]
        following = re.search(r'<div\s+class="tab-pane', rest)
        segment = rest[:following.start()] if following else rest
        questions = re.findall(r'<div class="card-header"[^>]*>\s*<h2[^>]*>\s*<a\b[^>]*>(.*?)</a>', segment, re.S)
        answers = re.findall(r'<div class="card-body">(.*?)</div>', segment, re.S)
        assert len(questions) == len(answers) == len(slugs), f'Unexpected source structure: {group}'
        for slug, question, answer in zip(slugs, questions, answers):
            guides[slug] = {'title': plain(question), 'body': answer.strip(), 'group': group}
    info = {'title': plain(title[1]), 'headline': plain(headline[1]), 'groups': [g[0] for g in GROUPS]}
    return source, info, guides

def main():
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument('source', type=Path)
    ap.add_argument('--write', action='store_true', help='Replace data with the captured source content')
    args = ap.parse_args()
    fragment, info, guides = extract(args.source.read_text())
    for name, data in [('info-hub', info), ('guides', guides)]:
        path = ROOT / 'inc' / 'vietride' / (name + '.json')
        if args.write:
            path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n')
        else:
            actual = json.loads(path.read_text())
            assert list(actual) == list(data) and actual == data, f'{name}: source mismatch'
    if args.write:
        snapshot = ROOT / 'docs' / 'reference' / 'info-hub-2026-11-19.html'
        snapshot.write_text(fragment + '\n')
        manifest = {'source_url': 'https://vietride.vn/', 'captured_on': '2026-11-19', 'snapshot': snapshot.name, 'sha256': hashlib.sha256(snapshot.read_bytes()).hexdigest(), 'questions': len(guides), 'group_counts': {group: sum(1 for v in guides.values() if v['group'] == group) for group in info['groups']}}
        (snapshot.parent / 'info-hub-2026-11-19.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n')
    print(f'PASS {len(guides)} questions: exact source titles, answer HTML, links, categories and ordering')

if __name__ == '__main__': main()
