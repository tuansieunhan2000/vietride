#!/usr/bin/env python3
"""Compare the rendered Info Hub and guide pages against captured production HTML."""
from concurrent.futures import ThreadPoolExecutor
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import urlopen
from urllib.error import HTTPError
import argparse
import importlib.util
import json
import re

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('sync_info_hub', ROOT / 'scripts/sync-info-hub.py')
sync = importlib.util.module_from_spec(spec); spec.loader.exec_module(sync)

class Node:
    def __init__(self, tag='', attrs=()):
        self.tag = tag; self.attrs = dict(attrs); self.children = []
    def find(self, tag=None, cls=None, id=None):
        found = []
        for child in self.children:
            if not isinstance(child, Node): continue
            if (tag is None or child.tag == tag) and (cls is None or cls in child.attrs.get('class', '').split()) and (id is None or child.attrs.get('id') == id): found.append(child)
            found.extend(child.find(tag, cls, id))
        return found
    def text(self):
        return ''.join(child.text() if isinstance(child, Node) else child for child in self.children)
    def links(self):
        return [(a.attrs.get('href', ''), normalize(a.text())) for a in self.find('a')]

class Document(HTMLParser):
    def __init__(self, source):
        super().__init__(convert_charrefs=True); self.root = Node(); self.stack = [self.root]; self.feed(source)
    def handle_starttag(self, tag, attrs):
        n = Node(tag, attrs); self.stack[-1].children.append(n)
        if tag == 'br': n.children.append(' ')
        if tag not in {'area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'}: self.stack.append(n)
    def handle_startendtag(self, tag, attrs): self.handle_starttag(tag, attrs)
    def handle_endtag(self, tag):
        for i in range(len(self.stack) - 1, 0, -1):
            if self.stack[i].tag == tag:
                self.stack = self.stack[:i]; break
    def handle_data(self, data): self.stack[-1].children.append(data)

def normalize(text): return re.sub(r'\s+', ' ', text).strip()
def fetch(url):
    with urlopen(url, timeout=20) as r: return r.read().decode()

def main():
    ap = argparse.ArgumentParser(description=__doc__); ap.add_argument('base')
    ap.add_argument('--source', type=Path, default=ROOT/'docs/reference/info-hub-2026-11-19.html')
    args = ap.parse_args(); base = args.base.rstrip('/')
    _, info, expected = sync.extract(args.source.read_text())
    actual = json.loads((ROOT/'inc/vietride/guides.json').read_text())
    assert list(actual) == list(expected) and actual == expected, 'Guide data differs from source'
    def check_question(node, guide, label):
        source = Document(guide['body']).root
        assert normalize(node.text()) == normalize(source.text()), f'Answer text mismatch: {label}'
        assert node.links() == source.links(), f'Answer links mismatch: {label}'
    for path in ['/', '/guides/']:
        doc = Document(fetch(base+path)).root
        groups = doc.find('details', 'vr-guide-group')
        assert len(groups) == len(info['groups']), f'Wrong group count: {path}'
        for node, name in zip(groups, info['groups']):
            summary = node.find('summary')[0]
            assert normalize(summary.text()) == name, f'Wrong group title/order: {path}'
        questions = doc.find('details', 'vr-guide-question')
        assert [n.attrs['id'] for n in questions] == list(expected), f'Question count/order mismatch: {path}'
        for node, (slug, guide) in zip(questions, expected.items()):
            assert normalize(node.find('summary')[0].text()) == guide['title'], f'Question title mismatch: {slug}'
            check_question(node.find('div', 'vr-guide-answer')[0], guide, path+slug)
        if path == '/guides/':
            assert normalize(doc.find('h1')[0].text()) == info['title']
            assert normalize(doc.find('p', 'vr-lead')[0].text()) == info['headline']
        else:
            hub = doc.find('section', id='faq')[0]
            assert normalize(hub.find('p', 'vr-kicker')[0].text()) == info['title']
            assert normalize(hub.find('h2')[0].text()) == info['headline']
    def check_page(item):
        slug, guide = item; doc = Document(fetch(base+'/guides/'+slug+'/')).root
        assert normalize(doc.find('h1')[0].text()) == guide['title'], f'Detail H1 mismatch: {slug}'
        assert normalize(doc.find('p', 'vr-kicker')[0].text()) == guide['group'], f'Detail group mismatch: {slug}'
        check_question(doc.find('div', 'vr-answer')[0], guide, slug)
    with ThreadPoolExecutor(max_workers=4) as pool: list(pool.map(check_page, expected.items()))
    sitemap = fetch(base+'/wp-sitemap-vietride-1.xml')
    for removed in ['accident-and-emergency', 'privacy-and-rental-enquiries']:
        assert removed not in sitemap, f'Invented topic still in sitemap: {removed}'
        try: fetch(base+'/guides/'+removed+'/')
        except HTTPError as e: assert e.code == 404
        else: raise AssertionError('Invented guide still published: '+removed)
    print('PASS original 3 groups, heading, 19 questions, full answers and source links match on homepage and /guides/')
    print('PASS all 19 detail pages preserve original titles, groups, answers and links')
    print('PASS 2 added topics removed from routes and sitemap')

if __name__ == '__main__': main()
