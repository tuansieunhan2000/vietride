# VietRide SEO

The theme renders its content and SEO metadata on the server. No JavaScript or Firebase client connection is required to read bike pages, prices, guides or route links.

## What the theme manages

- One title, description, canonical, robots tag and social sharing metadata for the homepage and theme routes.
- Original Info Hub question titles and descriptions excerpted from the original answers; English document language even when WordPress administration uses Vietnamese.
- One H1 per page, semantic headings, descriptive image alt text and reserved image dimensions.
- Self-referencing canonical URLs without tracking parameters. Known virtual routes redirect to their trailing-slash URL with HTTP 301.
- Visible breadcrumbs matching BreadcrumbList structured data.
- LocalBusiness/Organization, WebSite and WebPage/CollectionPage data; Article data for guides and routes; ItemList for listing pages.
- Bike rentals described as Service with Offer data only for rates actually entered. Day/week/month prices use VND and matching reference quantities. Offers explicitly use the LeaseOut business function. Unconfirmed availability is omitted. No fabricated prices, ratings or review totals.
- Info Hub content follows the captured original wording, grouping and ordering. See `docs/info-hub.md`; UI and SEO changes must not rewrite the advice. Existing guide slugs remain stable.

## Sitemap and Yoast

Without Yoast, the WordPress sitemap index is `/wp-sitemap.xml`. Theme routes appear in `/wp-sitemap-vietride-1.xml`. With Yoast XML sitemaps enabled, use `/sitemap_index.xml`; theme routes appear in `/vietride-sitemap.xml`.

The sitemap includes published bikes and editorial routes. Hidden bikes are excluded and unknown bike URLs return 404. Firestore update times supply bike lastmod values; publication or modification dates are not invented for editorial content. WordPress pages that override a theme URL remain managed by their WordPress sitemap provider.

The theme replaces only the Yoast presenters for metadata it owns on its custom pages, retaining Yoast title and webmaster verification presenters. Other WordPress pages keep Yoast's normal behavior. Compatibility checks used the real Yoast 22.6 presenter classes, provider interface and sitemap XML renderer without activating Yoast or writing fleet records.

## Production checks after uploading

1. Upload the latest theme to the server that the public domain actually uses, activate it, and purge page/CDN caches. Confirm the rendered source includes the new title and a single description/canonical/schema graph.
2. Confirm WordPress Address and Site Address use the intended HTTPS domain. Keep one preferred hostname and redirect HTTP/alternate hostname to it at the hosting level.
3. Confirm `WP_ENVIRONMENT_TYPE` is `production` (or unset) on the live server. Local, development and staging environments intentionally output noindex. Do not copy Local's wp-config.php to hosting.
4. In Settings → Reading, ensure “Discourage search engines from indexing this site” is off for the public website. The theme respects this setting and does not force a private site to become indexable.
5. Open the active sitemap index and confirm it contains the VietRide sitemap. If Yoast XML sitemaps are disabled, enable them or use the WordPress sitemap instead. A physical robots.txt on hosting must allow public pages and reference the active sitemap.
6. Submit that index in Google Search Console. Use URL Inspection for the homepage, one bike, one guide and one route. Validate relevant structured data using Google's Rich Results Test and Schema.org Validator. Rental Service markup is semantic data, not a promise of a Google Shopping or product rich result.
7. Check PageSpeed Insights on the actual hosting/mobile connection. Local checks do not establish live Core Web Vitals.
8. Compare existing indexed URLs in Search Console with the new site before changing any historical slugs. Add permanent redirects for changed URLs once their destinations are verified; do not redirect all missing URLs to the homepage.
9. Keep the Google Business Profile address, phone and opening hours consistent with the website. Confirm company information and review legal/safety content when business policies or regulations change.

## Repeatable audit

Python 3 standard library only:

```sh
python3 scripts/audit-seo.py http://tuantran.local --expect-noindex
python3 scripts/audit-seo.py https://vietride.vn
# If Yoast handles XML sitemaps:
python3 scripts/audit-seo.py https://vietride.vn --sitemap /vietride-sitemap.xml
```

The audit checks every theme sitemap page, unique titles/descriptions, canonical URLs, indexability, English language, heading hierarchy, social metadata, schema graphs, image dimensions, image/map URLs, internal destinations and fragments, true 404s and slash redirects. The public-domain audit is intended for after the new theme is live.

On 2026-11-19 the Local crawl passed for 42 pages and 70 image/map URLs after removing the two added guide topics. The public homepage still served the old theme with duplicate descriptions and Open Graph metadata; the Local changes must be deployed to affect that domain.

## Reference documentation

- [Google: canonical URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- [Google: robots meta tags](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag)
- [Google: structured data guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Schema.org: Offer](https://schema.org/Offer) and [BusinessFunction](https://schema.org/BusinessFunction)
- [Yoast: Metadata API](https://developer.yoast.com/customization/apis/metadata-api/)
