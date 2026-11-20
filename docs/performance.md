# Lighthouse fixes and deployment

## Confirmed production findings

The public homepage and all clean URLs are now reachable. A clean anonymous Lighthouse 13.4.1 desktop run on 2026-11-19 returned Performance 99, Accessibility 100, Best Practices 100 and SEO 92. A prior run contained an unattributable startup task, so both reports were retained in `/tmp/vietride-performance-audit/` to document lab variability.

The reproducible SEO failure is the physical root robots.txt line `Sitemap: /sitemap.xml`. It must use the absolute URL of the active sitemap index. The public Yoast index `/sitemap_index.xml` and its `/vietride-sitemap.xml` child both returned 200.

The clean run also confirmed image-delivery savings of approximately 644 KiB, unnecessary Bootstrap/Remix CSS, 7-day static caching and render-blocking jQuery/framework files. The supplied report's 1MB unused CSS/JS, low-resolution image failure and font-display failure were not reproduced anonymously; session, plugin or extension assets can change the result.

## Changes

- Managed homepage/fleet/guide/route templates use a small compatible Reboot/type base instead of the entire Bootstrap framework. Legacy WordPress pages and active footer widget areas retain full framework styles and scripts. Plugins can still request jQuery through normal WordPress dependencies.
- The Remix subset keeps the exact original glyphs/metrics for the 28 icons used by VietRide. CSS and font total about 5KB instead of 306KB; Zalo remains its existing SVG mask.
- VietRide CSS and vanilla JS are minified. JS includes a usable source map. Versioned asset URLs change after builds.
- Bundled photos have responsive, content-versioned image variants. The two opening images additionally use AVIF with WebP fallback. The original files stay intact; gallery links can still open the original photo. Main-photo srcset is changed with the selected gallery photo, so a previous photo cannot override the new source.
- Native-resolution image candidates and WordPress Media metadata keep uploaded photos sharp. No bike pricing/status/document content is changed.
- The generated responsive-image directory includes its own static cache policy. It applies only to generated images, not page HTML or WordPress administration.
- WordPress's virtual robots output uses the active absolute sitemap URL and respects a private site's setting. The physical robots.txt on this hosting overrides virtual output and must be replaced separately.

## Upload the fixes

1. Back up the existing theme. Extract `releases/vietride-upload-04-theme.zip` into `/domains/vietride.vn/public_html/wp-content/themes/`, allowing overwrite inside its existing `vietride/` directory. This update now includes the generated responsive images; the original image ZIPs do not need to be uploaded again for an already installed site. Keep the ZIP's folder nesting: the result must be `themes/vietride/inc/vietride/performance.php`, not `themes/vietride/vietride/...`.
2. Upload `releases/robots.txt` as `/domains/vietride.vn/public_html/robots.txt`, replacing the old file. Open `https://vietride.vn/robots.txt` and verify the absolute sitemap index URL.
3. To extend caching for the remaining CSS/JS/font assets, append `releases/vietride-static-cache-block.txt` to the theme directory's `.htaccess` (create it if missing). Preserve any existing security/cache rules. This file belongs under `wp-content/themes/vietride/`, not in public_html; no existing theme .htaccess is silently replaced by the update ZIP.
4. Purge existing page/hosting cache via the installed Performance/cache plugin. Do not add multiple cache plugins. Recheck a clean, logged-out or Incognito session.
5. Server document latency is separate from static asset size. If it remains high, configure the hosting's existing cache solution for anonymous public pages, excluding admin/authenticated requests. Purge page cache after Firestore bike changes; avoid long HTML TTLs because rental rates and availability must stay current. Theme files intentionally do not cache public HTML or private credentials.

The report's CSP/HSTS/COOP/Trusted Types entries include manual security guidance, not all failed scored audits. Do not install an untested blanket CSP or HSTS includeSubDomains/preload policy just to remove advisory text; any header policy needs the live service/iframe/HTTPS inventory.

## Build and verify

```sh
npm run vr-assets
python3 scripts/sync-info-hub.py docs/reference/info-hub-2026-11-19.html
python3 scripts/audit-info-hub.py http://tuantran.local
python3 scripts/audit-seo.py http://tuantran.local --expect-noindex
python3 scripts/package-vietride.py
```

Use the separate image/icon build scripts and their documented temporary Python environments when changing source images or icon names. Their generated outputs are committed/deployed assets; visitors need no Python/Node runtime.

Validation included original first-two-section typography and geometry at 375/820/1440px, all 19 Info Hub answers/links, all 42 theme pages, portrait/landscape gallery switching, mobile drawer/header with and without WordPress admin bar, reduced-motion behavior, filters, social new-tab behavior, and legacy/widget/plugin dependency fallbacks. HTTPS/redirect and indexability scores on Local are intentionally different: Local uses HTTP and noindex. The final public-domain score must be checked after deployment.

References:

- https://developer.chrome.com/docs/lighthouse/seo/invalid-robots-txt/
- https://developer.chrome.com/docs/performance/insights/image-delivery

## Final Local Lighthouse 13.4.1 results

Desktop: Performance100/Accessibility100, FCP0.4s/LCP0.5s/TBT0/CLS0; transferred350,389bytes. Mobile: Performance99/Accessibility100, FCP1.4s/LCP2.0s/TBT0/CLS0; transferred206,464bytes. Both passed low-resolution images, font display, unused CSS/JS, labels and robots syntax. Remaining image-delivery opportunity is112KiB desktop/31KiB mobile; these estimates do not mean images are broken.

The original anonymous production run transferred1,141,690bytes; the final Local desktop run transferred350,389bytes (~69% less). Hosting compression/network conditions differ, so this is a measured comparison, not a promised production score. The subset of first-two-section type/layout checks had no differences above1px at375/820/1440px.

Actual Lighthouse's robots audit separately validated the deployment `releases/robots.txt` with zero parse errors. Live SEO and cache headers still require the hosting upload described above.
