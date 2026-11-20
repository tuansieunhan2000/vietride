# Live hosting: clean URLs return a server 404

Public checks on 2026-11-19 found:

- `/` returns HTTP 200 and the current VietRide theme.
- `/motorbikes/`, `/guides/`, `/routes/saigon-and-around/` and `/sitemap_index.xml` return LiteSpeed's plain HTTP 404 page before WordPress renders.
- `/index.php/routes/saigon-and-around/` reaches WordPress and redirects to the correct canonical clean URL, which then receives the server 404.

This points to missing/inactive web-server rewriting. Editing the theme templates cannot repair a request that never reaches WordPress.

## Repair on the hosting account

1. In WordPress, open Settings → Permalinks. If the selection is Plain, enable Post name for the site's newly introduced clean URLs. If a pretty/custom structure is already in use, preserve it. Click Save Changes to regenerate rules.
2. Check the routes again. If WordPress says it cannot write .htaccess or LiteSpeed still returns its server 404, back up `/domains/vietride.vn/public_html/.htaccess` in DirectAdmin before editing it.
3. Replace only the section between `# BEGIN WordPress` and `# END WordPress` with the block in `releases/wordpress-rewrite-block.txt`. If no such section exists, add the block. Keep unrelated cache, SSL and security directives. This block is for this single-site installation at the domain root.
4. Save and purge any hosting/page-cache plugin cache, then recheck the public URLs and active sitemap.
5. If the same bare LiteSpeed 404 remains, ask the hosting provider to enable URL rewrite/.htaccess processing for this domain's document root. Provide the failing clean URL and the index.php URL that already reaches WordPress.

Do not add a custom ErrorDocument that converts all 404s into the homepage; unknown URLs must remain real 404s. Do not copy the theme ZIP into public_html as a replacement for WordPress files. The .htaccess belongs beside wp-config.php and index.php, not in the theme directory.

The theme ZIP does not replace the installation's root .htaccess, wp-config.php, database settings or server configuration. Validate clean URLs after each first-time hosting deployment.

References: https://wordpress.org/documentation/article/customize-permalinks/ and https://developer.wordpress.org/advanced-administration/server/web-server/httpd/
