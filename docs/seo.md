# VietRide SEO

The theme renders its content and SEO metadata on the server. No JavaScript or Firebase client connection is required to read bike pages, prices, guides or route links.

## What the theme manages

- One title, description, canonical, robots tag and social sharing metadata for the homepage and theme routes.
- English search titles and descriptions describe each page’s topic. Info Hub question headings and answers retain their original wording and links. Descriptions use the source answers or a summary of the page.
- One H1 per page, semantic headings, descriptive image alt text and reserved image dimensions.
- Self-referencing canonical URLs without tracking parameters. Known virtual routes redirect to the configured host and trailing-slash URL with HTTP 301, upgrading to HTTPS when the site address uses it. Pagination URLs that would render the VietRide homepage redirect to the homepage in one hop.
- Visible breadcrumbs matching BreadcrumbList structured data.
- LocalBusiness data includes the map link shown in the contact section. Route articles use their own visible photos in structured data. Guides use Article data with their original question as the headline.
- Bike rentals described as Service with Offer data only for rates actually entered. Day/week/month prices use VND and matching reference quantities. Offers explicitly use the LeaseOut business function. Unconfirmed availability is omitted. No fabricated prices, ratings or review totals.
- Info Hub content follows the captured original wording, grouping and ordering. See `docs/info-hub.md`; UI and SEO changes must not rewrite the advice. Existing guide slugs remain stable.

## Sitemap and Yoast

Without Yoast, the WordPress sitemap index is `/wp-sitemap.xml`. Theme routes appear in `/wp-sitemap-vietride-1.xml`. With Yoast XML sitemaps enabled, use `/sitemap_index.xml`; theme routes appear in `/vietride-sitemap.xml`.

The sitemap includes published bikes and editorial routes. Hidden bikes are excluded and unknown bike URLs return 404. Firestore update times supply bike lastmod values. WordPress pages that override a theme URL remain managed by their WordPress sitemap provider. WordPress or Yoast supplies the sitemap directive in virtual robots.txt. On production the physical robots.txt takes precedence.

The theme replaces the Yoast presenters for metadata it owns on its custom pages, retaining Yoast title and webmaster verification presenters. Other WordPress pages keep Yoast's normal behavior.

## Search titles and internal links

| Page | Previous search title | Updated search title |
| --- | --- | --- |
| Homepage | Scooter Rental in Ho Chi Minh City | Scooter & Motorbike Rental in Ho Chi Minh City |
| `/motorbikes/` | Scooter & Motorbike Fleet in Ho Chi Minh City | Motorbike Rental Rates in Ho Chi Minh City |
| `/guides/` | Info Hub | Vietnam Motorbike Rental & Riding Guides |
| `/guides/long-term-rates/` | How do you calculate long-term rates? | Long-Term Motorbike Rental Rates in Ho Chi Minh City |
| `/routes/saigon-and-around/` | Saigon & Around | Saigon Motorbike Route & Map |

The homepage targets local rental searches. The fleet page answers model and rate comparisons. Guide titles describe specific questions about rentals and riding. Each theme page kept its URL.

| Destination | Previous pages linking in | Pages linking in with this update |
| --- | ---: | ---: |
| Fleet and rental rates | 1 | 8 |
| Rental documents | 2 | 5 |
| Honda Vision rental | 2 | 2 |
| Honda Future rental | 2 | 2 |

Counts cover distinct source pages in the homepage, guides and routes, excluding shared navigation, footer and breadcrumbs. The comparison uses public HTML before the update and rendered WordPress HTML after it. The fleet has direct links to every published model. Bike pages also link to up to two published alternatives with the same transmission and fuel type when the inventory has a match.

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
python3 scripts/audit-seo.py https://vietride.vn --sitemap /vietride-sitemap.xml \
  --alternate-origin http://vietride.vn --alternate-origin https://www.vietride.vn
```

The audit checks every theme sitemap page, sitemap discovery through robots.txt, unique titles and descriptions, canonical URLs, robots directives, headings, sharing tags, schema graphs, images and internal destinations. It also checks that search results are noindex and that missing URLs and homepage pagination return the intended status.

## Findings and deployment priorities

The public crawl on 2026-11-23 found 42 reachable pages, an absolute Yoast sitemap in robots.txt and valid 404s on unknown bike, guide and route URLs. The public request `/?paged=2` redirected to `/page/2/?paged=2`, which returned a duplicate homepage with HTTP 200. Public HTTP and www variants of virtual routes also returned 200. The WordPress test fixture reproduced the pagination paths and verified the fix with both a static front page and a separate paginated blog.

1. Deploy the theme and rerun the public audit. Verify the homepage, fleet and updated guides in Search Console after Google recrawls them.
2. Publish approved bike rates through the existing fleet editor. Only one of the 17 models in the public crawl had numeric rental rates in its HTML. A searchable rates page is more useful when visitors can compare several models.
3. Use Search Console query and page data to measure impressions, clicks and indexing for the homepage, rental rates and rental documents. Use Google Business Profile to verify that the address, phone and hours match the website.

The audit used the [Illyism SEO skills](https://github.com/Illyism/seo-skills) for technical SEO, titles and internal links. Search Console, backlink metrics and ranking changes require account data.

## Reference documentation

- [Google: canonical URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- [Google: robots meta tags](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag)
- [Google: structured data guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Schema.org: Offer](https://schema.org/Offer) and [BusinessFunction](https://schema.org/BusinessFunction)
- [Yoast: Metadata API](https://developer.yoast.com/customization/apis/metadata-api/)
