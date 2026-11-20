<?php
/** SEO for server-rendered fleet and editorial pages, including Yoast sites. */
defined( 'ABSPATH' ) || exit;

function vr_guide_meta( $slug, $guide ) {
	$text = html_entity_decode( wp_strip_all_tags( $guide['body'] ), ENT_QUOTES, 'UTF-8' );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	return array(
		'title' => $guide['title'],
		'description' => wp_html_excerpt( $text, 157, '…' ),
	);
}

function vr_seo_url() {
	return home_url( vr_route() ? '/' . vr_route() . '/' : '/' );
}
function vr_title( $title ) {
	$meta = vr_meta();
	return $meta ? $meta['title'] . ' | VietRide' : $title;
}
add_filter( 'pre_get_document_title', 'vr_title', 99 );
add_filter( 'wpseo_title', 'vr_title', 99 );

// Virtual pages must never inherit a WordPress post's canonical, shortlink or embeds.
add_action( 'wp', function () {
	if ( ! vr_meta() ) { return; }
	remove_action( 'wp_head', 'rel_canonical' );
	remove_action( 'wp_head', 'wp_robots', 1 );
	if ( vr_route() ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
} );
// Keep Yoast's title and verification tags. Replace only the metadata we own.
add_filter( 'wpseo_frontend_presenters', function ( $presenters ) {
	if ( ! vr_meta() ) { return $presenters; }
	return array_values( array_filter( $presenters, static function ( $presenter ) {
		$class = get_class( $presenter );
		return ! preg_match( '~\\\\(?:Open_Graph|Twitter)\\\\|\\\\(?:Meta_Description|Canonical|Robots|Schema|Meta_Author|Rel_Prev|Rel_Next)_Presenter$~', $class );
	} ) );
}, 99 );
add_filter( 'language_attributes', function ( $attributes ) {
	return vr_meta() ? preg_replace( '/\b(lang|xml:lang)="[^"]*"/', '$1="en"', $attributes ) : $attributes;
} );
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_404() ) { $robots['noindex'] = true; }
	return $robots;
} );

// Send slash variants to one URL; keep tracking parameters without canonicalizing them.
add_action( 'template_redirect', function () {
	if ( ! vr_route() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) { return; }
	$request = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );
	$path = wp_parse_url( $request, PHP_URL_PATH );
	$canonical_path = wp_parse_url( vr_seo_url(), PHP_URL_PATH );
	if ( $path !== $canonical_path ) {
		$query = wp_parse_url( $request, PHP_URL_QUERY );
		wp_safe_redirect( vr_seo_url() . ( $query ? '?' . $query : '' ), 301, 'VietRide' );
		exit;
	}
}, 1 );

function vr_seo_image( $meta ) {
	if ( ( $meta['kind'] ?? '' ) === 'bike' ) {
		$bike = $meta['item'];
		return array( 'url' => html_entity_decode( vr_asset( vr_bike_photos( $bike )[0] ), ENT_QUOTES, 'UTF-8' ), 'alt' => $bike['brand'] . ' ' . $bike['name'] . ' rental bike in Ho Chi Minh City' );
	}
	if ( ( $meta['kind'] ?? '' ) === 'route' ) {
		return array( 'url' => html_entity_decode( vr_asset( 'img/' . $meta['item']['photo'] ), ENT_QUOTES, 'UTF-8' ), 'alt' => $meta['item']['alt'] );
	}
	return array( 'url' => html_entity_decode( vr_asset( 'img/Vietride1-960.webp' ), ENT_QUOTES, 'UTF-8' ), 'alt' => 'VietRide scooter rental fleet in Ho Chi Minh City' );
}
function vr_bike_photos( $bike ) {
	$cover = $bike['thumb'];
	$original = preg_replace( '~-preview\.webp$~', '-1.webp', $cover );
	if ( $original !== $cover && in_array( $original, $bike['images'], true ) ) { $cover = $original; }
	return array_values( array_unique( array_merge( array( $cover ), $bike['images'] ) ) );
}
function vr_seo_robots() {
	$robots = apply_filters( 'wp_robots', array() );
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$private = ! get_option( 'blog_public' ) || wp_get_environment_type() !== 'production' || preg_match( '/(^localhost$|\.(local|localhost|test)$)/i', $host );
	if ( ! vr_route() && get_post_meta( get_queried_object_id(), '_yoast_wpseo_meta-robots-noindex', true ) === '1' ) { $private = true; }
	if ( $private ) { $robots['noindex'] = true; }
	if ( ! empty( $robots['noindex'] ) ) { unset( $robots['index'] ); }
	$robots['max-image-preview'] = 'large';
	$robots['max-snippet'] = -1;
	$robots['max-video-preview'] = -1;
	$values = array();
	foreach ( $robots as $name => $value ) {
		if ( $value === true ) { $values[] = $name; }
		elseif ( $value !== false ) { $values[] = $name . ':' . $value; }
	}
	return implode( ', ', $values );
}
function vr_seo_head() {
	$meta = vr_meta();
	if ( ! $meta ) { return; }
	$url = vr_seo_url();
	$image = vr_seo_image( $meta );
	echo '<meta name="description" content="' . esc_attr( $meta['description'] ) . '">' . "\n";
	echo '<meta name="robots" content="' . esc_attr( vr_seo_robots() ) . '">' . "\n";
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	$tags = array(
		'og:locale' => 'en_US', 'og:type' => in_array( $meta['kind'] ?? '', array( 'guide', 'route' ), true ) ? 'article' : 'website',
		'og:site_name' => 'VietRide', 'og:title' => vr_title( '' ), 'og:description' => $meta['description'],
		'og:url' => $url, 'og:image' => $image['url'], 'og:image:alt' => $image['alt'],
	);
	foreach ( $tags as $property => $value ) { echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $value ) . '">' . "\n"; }
	foreach ( array( 'card' => 'summary_large_image', 'title' => vr_title( '' ), 'description' => $meta['description'], 'image' => $image['url'], 'image:alt' => $image['alt'] ) as $name => $value ) {
		echo '<meta name="twitter:' . esc_attr( $name ) . '" content="' . esc_attr( $value ) . '">' . "\n";
	}
	echo '<script type="application/ld+json">' . wp_json_encode( vr_seo_graph( $meta ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'vr_seo_head', 20 );

function vr_breadcrumbs( $meta ) {
	$crumbs = array( array( 'name' => 'Home', 'url' => home_url( '/' ) ) );
	if ( vr_route() ) {
		$parent = explode( '/', vr_route() )[0];
		$labels = array( 'motorbikes' => 'Motorbikes', 'guides' => 'Info Hub', 'routes' => 'Routes' );
		$crumbs[] = array( 'name' => $labels[$parent], 'url' => home_url( '/' . $parent . '/' ) );
		if ( ! empty( $meta['item'] ) ) {
			$name = $meta['kind'] === 'bike' ? $meta['item']['brand'] . ' ' . $meta['item']['name'] : $meta['title'];
			$crumbs[] = array( 'name' => $name, 'url' => vr_seo_url() );
		}
	}
	return $crumbs;
}
function vr_seo_graph( $meta ) {
	$home = home_url( '/' ); $url = vr_seo_url(); $image = vr_seo_image( $meta ); $kind = $meta['kind'] ?? 'home';
	$business_id = $home . '#business'; $website_id = $home . '#website';
	$graph = array(
		array( '@type' => array( 'LocalBusiness', 'Organization' ), '@id' => $business_id, 'name' => 'VietRide', 'url' => $home,
			'logo' => html_entity_decode( vr_asset( 'img/logo_red_square.webp' ), ENT_QUOTES, 'UTF-8' ), 'image' => html_entity_decode( vr_asset( 'img/vietride-story.webp' ), ENT_QUOTES, 'UTF-8' ),
			'telephone' => '+84333578594', 'email' => 'hello@vietride.vn',
			'address' => array( '@type' => 'PostalAddress', 'streetAddress' => '40 Nguyễn Bá Huân, An Khánh', 'addressLocality' => 'Hồ Chí Minh', 'postalCode' => '70000', 'addressCountry' => 'VN' ),
			'openingHoursSpecification' => array( array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ), 'opens' => '09:00', 'closes' => '18:00' ) ),
			'sameAs' => array( 'https://www.instagram.com/vietride.vn/', 'https://www.facebook.com/VIETRIDE' ) ),
		array( '@type' => 'WebSite', '@id' => $website_id, 'url' => $home, 'name' => 'VietRide', 'inLanguage' => 'en', 'publisher' => array( '@id' => $business_id ) ),
	);
	$page = array( '@type' => in_array( $kind, array( 'fleet', 'guides', 'routes' ), true ) ? 'CollectionPage' : 'WebPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => $meta['title'], 'description' => $meta['description'], 'inLanguage' => 'en', 'isPartOf' => array( '@id' => $website_id ), 'about' => array( '@id' => $business_id ), 'primaryImageOfPage' => array( '@type' => 'ImageObject', 'url' => $image['url'], 'caption' => $image['alt'] ) );
	if ( vr_route() ) {
		$items = array();
		foreach ( vr_breadcrumbs( $meta ) as $index => $crumb ) { $items[] = array( '@type' => 'ListItem', 'position' => $index + 1, 'name' => $crumb['name'], 'item' => $crumb['url'] ); }
		$graph[] = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items );
		$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}
	if ( in_array( $kind, array( 'guide', 'route' ), true ) ) {
		// No invented author, publication date, review or rating.
		$article = array( '@type' => 'Article', '@id' => $url . '#article', 'headline' => $meta['title'], 'description' => $meta['description'], 'url' => $url, 'image' => $image['url'], 'inLanguage' => 'en', 'mainEntityOfPage' => array( '@id' => $page['@id'] ), 'publisher' => array( '@id' => $business_id ) );
		$page['mainEntity'] = array( '@id' => $article['@id'] ); $graph[] = $article;
	}
	if ( $kind === 'bike' ) {
		$bike = $meta['item']; $name = $bike['brand'] . ' ' . $bike['name'];
		$service = array( '@type' => 'Service', '@id' => $url . '#rental', 'name' => $name . ' rental in Ho Chi Minh City', 'serviceType' => 'Motorbike rental', 'url' => $url, 'description' => vr_use( $bike ), 'image' => array_map( static function ( $photo ) { return html_entity_decode( vr_asset( $photo ), ENT_QUOTES, 'UTF-8' ); }, vr_bike_photos( $bike ) ), 'provider' => array( '@id' => $business_id ), 'areaServed' => array( '@type' => 'City', 'name' => 'Ho Chi Minh City' ) );
		$offers = array();
		foreach ( array( 'daily' => array( '1 day', 'DAY' ), 'weekly' => array( '1 week', 'WEE' ), 'monthly' => array( '1 month', 'MON' ) ) as $period => $unit ) {
			$price = $bike['price_' . $period] ?? '';
			if ( $price === '' || ! preg_match( '/^[0-9]+$/', (string) $price ) ) { continue; }
			$offer = array( '@type' => 'Offer', 'name' => $name . ' rental — ' . $unit[0], 'url' => $url, 'price' => $price, 'priceCurrency' => 'VND', 'businessFunction' => 'http://purl.org/goodrelations/v1#LeaseOut', 'seller' => array( '@id' => $business_id ), 'itemOffered' => array( '@id' => $service['@id'] ), 'priceSpecification' => array( '@type' => 'UnitPriceSpecification', 'price' => $price, 'priceCurrency' => 'VND', 'referenceQuantity' => array( '@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => $unit[1] ) ) );
			if ( ( $bike['availability'] ?? '' ) === 'available' ) { $offer['availability'] = 'https://schema.org/InStock'; }
			elseif ( ( $bike['availability'] ?? '' ) === 'unavailable' ) { $offer['availability'] = 'https://schema.org/OutOfStock'; }
			$offers[] = $offer;
		}
		if ( $offers ) { $service['offers'] = $offers; }
		$page['mainEntity'] = array( '@id' => $service['@id'] ); $graph[] = $service;
	}
	if ( in_array( $kind, array( 'fleet', 'guides', 'routes' ), true ) ) {
		$items = array();
		foreach ( vr_pages() as $path => $entry ) {
			if ( $entry['kind'] !== array( 'fleet' => 'bike', 'guides' => 'guide', 'routes' => 'route' )[$kind] ) { continue; }
			$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => $entry['title'], 'url' => home_url( '/' . $path . '/' ) );
		}
		$graph[] = array( '@type' => 'ItemList', '@id' => $url . '#list', 'numberOfItems' => count( $items ), 'itemListElement' => $items );
		$page['mainEntity'] = array( '@id' => $url . '#list' );
	}
	$graph[] = $page;
	return array( '@context' => 'https://schema.org', '@graph' => $graph );
}

function vr_sitemap_urls() {
	$urls = array();
	foreach ( vr_pages() as $path => $page ) {
		// Actual WordPress pages are handled by their own sitemap provider.
		if ( get_page_by_path( $path ) ) { continue; }
		$entry = array( 'loc' => home_url( '/' . $path . '/' ) );
		if ( $page['kind'] === 'bike' && ! empty( $page['item']['_version'] ) ) {
			$time = strtotime( $page['item']['_version'] );
			if ( $time !== false ) { $entry['lastmod'] = gmdate( 'c', $time ); }
		}
		$urls[] = $entry;
	}
	return $urls;
}
add_action( 'wp_sitemaps_init', function ( $server ) {
	$server->registry->add_provider( 'vietride', new class extends WP_Sitemaps_Provider {
		public function __construct() { $this->name = 'vietride'; $this->object_type = 'page'; }
		public function get_url_list( $page_num, $object_subtype = '' ) {
			return array_slice( vr_sitemap_urls(), ( max( 1, (int) $page_num ) - 1 ) * wp_sitemaps_get_max_urls( $this->object_type ), wp_sitemaps_get_max_urls( $this->object_type ) );
		}
		public function get_max_num_pages( $object_subtype = '' ) { return (int) ceil( count( vr_sitemap_urls() ) / wp_sitemaps_get_max_urls( $this->object_type ) ); }
	} );
} );
add_filter( 'wpseo_sitemaps_providers', function ( $providers ) {
	if ( ! interface_exists( 'WPSEO_Sitemap_Provider' ) ) { return $providers; }
	$providers[] = new class implements WPSEO_Sitemap_Provider {
		public function handles_type( $type ) { return $type === 'vietride'; }
		public function get_index_links( $max_entries ) {
			$links = array(); $count = (int) ceil( count( vr_sitemap_urls() ) / max( 1, (int) $max_entries ) );
			for ( $page = 1; $page <= $count; $page++ ) {
				$links[] = array( 'loc' => home_url( '/vietride-sitemap' . ( $page === 1 ? '' : $page ) . '.xml' ) );
			}
			return $links;
		}
		public function get_sitemap_links( $type, $max_entries, $current_page ) {
			if ( ! $this->handles_type( $type ) ) { return array(); }
			$entries = array_slice( vr_sitemap_urls(), ( max( 1, (int) $current_page ) - 1 ) * max( 1, (int) $max_entries ), max( 1, (int) $max_entries ) );
			if ( ! $entries ) { throw new OutOfBoundsException( 'Unknown VietRide sitemap page.' ); }
			return array_map( static function ( $entry ) {
				$link = array( 'loc' => $entry['loc'] );
				if ( isset( $entry['lastmod'] ) ) { $link['mod'] = $entry['lastmod']; }
				return $link;
			}, $entries );
		}
	};
	return $providers;
} );
