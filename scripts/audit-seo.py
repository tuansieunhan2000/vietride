#!/usr/bin/env python3
"""Crawl the theme sitemap and check rendered SEO. Uses only Python's standard library."""
import argparse
from collections import Counter
from concurrent.futures import ThreadPoolExecutor
from html.parser import HTMLParser
import json
import re
from urllib.parse import quote, urljoin, urlsplit, urlunsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener, urlopen
from urllib.error import HTTPError
import xml.etree.ElementTree as ET

class Page(HTMLParser):
    def __init__(self, html):
        super().__init__(convert_charrefs=True)
        self.tags = []; self.links = []; self.ids = set(); self.headings = []
        self.title = ''; self.graphs = []; self.lang = ''; self.in_title = False
        self.in_schema = False; self.schema_text = ''; self.text = ''
        self.h1_text = ''; self.current_crumb = ''
        self.in_h1 = False; self.in_breadcrumb = False; self.in_current_crumb = False
        self.feed(html)
    def handle_starttag(self, tag, attrs):
        a = dict(attrs); self.tags.append((tag, a))
        if tag == 'html': self.lang = a.get('lang', '')
        if a.get('id'): self.ids.add(a['id'])
        if tag == 'a' and a.get('href'): self.links.append(a['href'])
        if re.fullmatch(r'h[1-6]', tag): self.headings.append(int(tag[1]))
        if tag == 'title': self.in_title = True
        if tag == 'h1': self.in_h1 = True
        if tag == 'nav' and a.get('aria-label') == 'Breadcrumb': self.in_breadcrumb = True
        if self.in_breadcrumb and tag == 'span' and a.get('aria-current') == 'page': self.in_current_crumb = True
        if tag == 'script' and a.get('type') == 'application/ld+json':
            self.in_schema = True; self.schema_text = ''
    def handle_endtag(self, tag):
        if tag == 'title': self.in_title = False
        if tag == 'h1': self.in_h1 = False
        if tag == 'nav': self.in_breadcrumb = False
        if tag == 'span': self.in_current_crumb = False
        if tag == 'script' and self.in_schema:
            self.graphs.append(json.loads(self.schema_text)); self.in_schema = False
    def handle_data(self, data):
        if self.in_title: self.title += data
        if self.in_schema: self.schema_text += data
        if self.in_h1: self.h1_text += data
        if self.in_current_crumb: self.current_crumb += data
        self.text += data
    def meta(self, name):
        return [a.get('content', '') for t, a in self.tags if t == 'meta' and (a.get('name') == name or a.get('property') == name)]
    def canonical(self):
        return [a.get('href') for t, a in self.tags if t == 'link' and a.get('rel') == 'canonical']

class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch(url, method='GET', follow=True):
    parts = urlsplit(url)
    # Legacy editorial links may contain literal spaces in file names.
    url = urlunsplit(parts._replace(path=quote(parts.path, safe="/%:@!$&'()*+,;=")))
    open_request = urlopen if follow else build_opener(NoRedirect).open
    try:
        with open_request(Request(url, method=method, headers={'User-Agent': 'VietRide-SEO-Audit/1.0'}), timeout=30) as r:
            return r.status, r.read().decode('utf-8', errors='replace'), r.geturl(), r.headers
    except HTTPError as e:
        return e.code, e.read().decode('utf-8', errors='replace'), e.geturl(), e.headers

def sitemap_urls(url, element='url'):
    status, xml, final, _ = fetch(url)
    assert status == 200 and final == url, f'Sitemap {status}: {url} -> {final}'
    ns = {'s': 'http://www.sitemaps.org/schemas/sitemap/0.9'}
    return [e.text for e in ET.fromstring(xml).findall(f's:{element}/s:loc', ns)]


def check_robots(base, child_sitemap):
    status, body, _, _ = fetch(urljoin(base, 'robots.txt'))
    assert status == 200, f'robots.txt returns {status}'
    hints = re.findall(r'^Sitemap:\s*(\S+)', body, re.I | re.M)
    assert hints and all(urlsplit(url).scheme in ('http', 'https') for url in hints), 'Missing or relative sitemap in robots.txt'
    assert len(hints) == len(set(hints)), 'Duplicate sitemap directives in robots.txt'
    assert child_sitemap in hints or any(
        child_sitemap in sitemap_urls(url, 'sitemap')
        for url in hints if urlsplit(url).netloc == urlsplit(base).netloc
    ), f'robots.txt does not advertise the theme sitemap: {child_sitemap}'

def main():
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument('base'); ap.add_argument('--expect-noindex', action='store_true')
    ap.add_argument('--sitemap', default='/wp-sitemap-vietride-1.xml')
    ap.add_argument('--alternate-origin', action='append', default=[], help='Check a hostname or HTTP variant redirects to the preferred origin')
    args = ap.parse_args(); base = args.base.rstrip('/') + '/'; origin = urlsplit(base).netloc
    theme_sitemap = urljoin(base, args.sitemap)
    check_robots(base, theme_sitemap)
    urls = [base] + sitemap_urls(theme_sitemap)
    assert len(urls) == len(set(urls)), 'Duplicate sitemap URLs'
    def inspect(url):
        status, html, final, headers = fetch(url)
        assert status == 200 and final == url, f'Noncanonical sitemap URL {status}: {url} -> {final}'
        p = Page(html)
        assert p.lang.startswith('en'), f'Wrong content language: {url}'
        assert p.title.strip() and 'VietRide' in p.title, f'Missing title: {url}'
        assert len(p.meta('description')) == 1 and p.meta('description')[0].strip(), f'Description missing/duplicated: {url}'
        assert p.canonical() == [url], f'Canonical mismatch: {url} {p.canonical()}'
        assert len(p.meta('robots')) == 1, f'Duplicate robots: {url}'
        assert ('noindex' in p.meta('robots')[0]) == args.expect_noindex, f'Indexability mismatch: {url}'
        assert args.expect_noindex or 'noindex' not in ','.join(headers.get_all('X-Robots-Tag', [])).lower(), f'HTTP noindex: {url}'
        assert p.meta('og:title') == [p.title] and p.meta('og:url') == [url], f'Wrong share metadata: {url}'
        assert len(p.meta('og:image')) == 1 and p.meta('twitter:card') == ['summary_large_image'], f'Share image missing: {url}'
        assert p.headings.count(1) == 1, f'H1 count: {url}'
        assert p.h1_text.strip(), f'Empty H1: {url}'
        previous = 0
        for level in p.headings:
            assert level <= previous + 1, f'Heading skips H{previous} -> H{level}: {url}'
            previous = level
        assert len(p.graphs) == 1 and p.graphs[0].get('@graph'), f'Missing/duplicate schema graph: {url}'
        nodes = p.graphs[0]['@graph']; types = [n['@type'] for n in nodes]
        assert any('LocalBusiness' in t for t in types), f'Business schema missing: {url}'
        assert 'aggregateRating' not in json.dumps(nodes), f'Unexpected aggregate rating: {url}'
        if url != base:
            breadcrumb = next((node for node in nodes if node['@type'] == 'BreadcrumbList'), None)
            assert breadcrumb, f'Breadcrumb schema missing: {url}'
            assert p.current_crumb.strip() == breadcrumb['itemListElement'][-1]['name'], f'Current breadcrumb does not match schema: {url}'
        for t, a in p.tags:
            if t == 'img':
                assert 'alt' in a, f'Missing image alt: {url}'
                assert a.get('width') and a.get('height'), f'Image dimensions missing: {url}'
        return url, p
    with ThreadPoolExecutor(max_workers=4) as pool:
        pages = dict(pool.map(inspect, urls))
    for key, values in [('title', [p.title for p in pages.values()]), ('description', [p.meta('description')[0] for p in pages.values()])]:
        assert not [v for v, count in Counter(values).items() if count > 1], f'Duplicate {key}'
    internal = set(); assets = set()
    for url, p in pages.items():
        for href in p.links:
            target = urljoin(url, href); parts = urlsplit(target)
            if parts.netloc != origin or parts.scheme not in ('http', 'https'): continue
            clean = urlunsplit((parts.scheme, parts.netloc, parts.path, parts.query, ''))
            if '/wp-content/' in parts.path: assets.add(clean); continue
            internal.add(clean)
            if parts.fragment and clean in pages:
                assert parts.fragment in pages[clean].ids, f'Broken fragment: {target}'
        assets.update(p.meta('og:image'))
        assets.update(urljoin(url, a['src']) for t, a in p.tags if t == 'img' and a.get('src'))
        for t, a in p.tags:
            if t in ('img', 'source') and a.get('srcset'):
                for candidate in a['srcset'].split(','):
                    assets.add(urljoin(url, candidate.strip().rsplit(None, 1)[0]))
    for link in sorted(internal - set(pages)):
        status, _, _, _ = fetch(link)
        assert status == 200, f'Broken internal link: {status} {link}'
    def check_asset(url):
        status, _, _, _ = fetch(url, 'HEAD')
        assert status == 200, f'Broken image/map: {status} {url}'
    with ThreadPoolExecutor(max_workers=4) as pool: list(pool.map(check_asset, assets))
    for section in ('motorbikes', 'guides', 'routes'):
        path = f'{section}/seo-audit-does-not-exist/'
        status, _, _, _ = fetch(urljoin(base, path), follow=False)
        assert status == 404, f'Unknown URL returns {status}: {path}'
    status, _, _, headers = fetch(urljoin(base, 'motorbikes?utm_source=seo-audit'), follow=False)
    assert status == 301 and headers.get('Location') == urljoin(base, 'motorbikes/?utm_source=seo-audit'), 'Slash redirect failed'
    _, tracking, _, _ = fetch(urljoin(base, 'motorbikes/?utm_source=seo-audit'))
    assert Page(tracking).canonical() == [urljoin(base, 'motorbikes/')], 'Tracking parameter in canonical'
    for alternate in args.alternate_origin:
        variant = urljoin(alternate.rstrip('/') + '/', 'motorbikes?utm_source=seo-audit')
        status, _, _, headers = fetch(variant, follow=False)
        assert status == 301 and headers.get('Location') == urljoin(base, 'motorbikes/?utm_source=seo-audit'), f'Origin redirect failed: {variant}'
    for path in ('?paged=2', 'page/2/'):
        status, _, _, headers = fetch(urljoin(base, path), follow=False)
        assert status == 404 or (status == 301 and headers.get('Location') == base), f'Duplicate homepage pagination: {path} ({status} → {headers.get("Location")})'
    _, search, _, headers = fetch(urljoin(base, '?s=seo-audit'))
    assert any('noindex' in value.lower() for value in Page(search).meta('robots') + headers.get_all('X-Robots-Tag', [])), 'Search results must be noindex'
    print(f'PASS {len(pages)} pages: unique titles/descriptions, canonical, robots, headings, language, social tags and schema')
    print(f'PASS {len(internal)} internal destinations and {len(assets)} images/maps; no broken links or fragments')
    print('PASS robots.txt discovery, true 404s, canonical redirects and search noindex')

if __name__ == '__main__': main()
