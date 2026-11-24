<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/firebase.php';

function vr_data( $name ) {
	static $data = array();
	if ( ! isset( $data[$name] ) ) {
		$data[$name] = json_decode( file_get_contents( __DIR__ . '/' . $name . '.json' ), true );
	}
	return $name === 'bikes' ? vr_firebase_bikes( $data[$name] ) : $data[$name];
}
function vr_url( $path = '' ) { return esc_url( home_url( '/' . ltrim( $path, '/' ) ) ); }
function vr_asset( $file ) { return esc_url( preg_match( '~^https?://~i', $file ) ? $file : get_theme_file_uri( $file ) ); }
function vr_map_url() {
	return 'https://www.google.com/maps/search/?api=1&query=40+Nguyen+Ba+Huan+An+Khanh+Ho+Chi+Minh+70000+Vietnam';
}
require_once __DIR__ . '/images.php';
function vr_srcset( $stem ) {
	return vr_asset( "img/$stem-480.webp" ) . ' 480w, ' . vr_asset( "img/$stem-960.webp" ) . ' 960w';
}
/** Format legacy line breaks without changing the original words or answer links. */
function vr_format_guide_body( $body ) {
	$body = wp_kses_post( $body );
	if ( preg_match( '~<(?:p|ul|ol|h[1-6]|table|div)\b~i', $body ) ) { return $body; }
	$parts = preg_split( '~\s*<br\s*/?>\s*~i', $body );
	$output = array();
	$in_list = false;
	foreach ( $parts as $part ) {
		$part = trim( $part );
		if ( $part === '' ) { continue; }
		$text = trim( wp_strip_all_tags( $part ) );
		if ( preg_match( '/^-\s/', $text ) ) {
			if ( ! $in_list ) { $output[] = '<ul class="vr-guide-bullets">'; $in_list = true; }
			$output[] = '<li>' . $part . '</li>';
			continue;
		}
		if ( $in_list ) { $output[] = '</ul>'; $in_list = false; }
		$note = preg_match( '/^(NOTE:|Important Note:)/i', $text );
		if ( preg_match( '/^[^:<>]{1,48}:$/', $text ) ) { $part = '<strong>' . $part . '</strong>'; }
		$output[] = '<p' . ( $note ? ' class="vr-guide-note"' : '' ) . '>' . $part . '</p>';
	}
	if ( $in_list ) { $output[] = '</ul>'; }
	return implode( "\n", $output );
}
function vr_icon( $name ) {
	echo '<i aria-hidden="true" class="ri-' . esc_attr( $name ) . '-fill"></i>';
}
function vr_pages() {
	static $pages;
	if ( isset( $pages ) ) { return $pages; }
	$pages = array(
		'motorbikes' => array( 'kind'=>'fleet', 'title'=>'Motorbike Rental Rates in Ho Chi Minh City', 'description'=>'Compare scooter and motorbike rental rates by model in Ho Chi Minh City. Check availability, photos and specs for automatic, semi-automatic and electric bikes.' ),
		'guides' => array( 'kind'=>'guides', 'title'=>'Vietnam Motorbike Rental & Riding Guides', 'description'=>'Use VietRide’s Info Hub to prepare for a motorbike rental in Ho Chi Minh City. Find guidance on documents, deposits, rental periods and riding in Vietnam.' ),
		'routes' => array( 'kind'=>'routes', 'title'=>'Motorbike Routes from Ho Chi Minh City', 'description'=>'Explore Saigon by motorbike or plan a longer road trip through Southern Vietnam with VietRide’s maps and local route notes.' ),
	);
	foreach ( vr_data( 'bikes' ) as $bike ) {
		$name = $bike['brand'] . ' ' . $bike['name'];
		$engine = vr_bike_fuel( $bike ) === 'electric' ? 'electric' : $bike['engine'];
		$specs = $engine . ' ' . strtolower( $bike['type'] );
		$pages['motorbikes/'.$bike['slug']] = array( 'kind'=>'bike', 'item'=>$bike, 'title'=>$name.' Rental in Ho Chi Minh City', 'description'=>"Rent a $name in Ho Chi Minh City. See photos, compare rates and check availability for this $specs bike." );
	}
	foreach ( vr_data( 'guides' ) as $slug => $guide ) {
		$seo = vr_guide_meta( $slug, $guide );
		$pages['guides/'.$slug] = array_merge( array( 'kind'=>'guide', 'item'=>$guide ), $seo );
	}
	$route_titles = array( 'saigon-and-around' => 'Saigon Motorbike Route & Map', 'vietnam-road-trips' => 'Southern Vietnam Motorbike Road Trips' );
	foreach ( vr_data( 'routes' ) as $slug => $route ) {
		$pages['routes/'.$slug] = array( 'kind'=>'route', 'item'=>$route, 'title'=>$route_titles[$slug] ?? $route['title'], 'description'=>$route['intro'] );
	}
	return $pages;
}

// Resolve only known editorial URLs. Existing WordPress pages keep their own routes.
add_action( 'parse_request', function ( $wp ) {
	$key = trim( $wp->request, '/' );
	if ( ! preg_match( '~^(motorbikes|guides|routes)(/[^/]+)?$~D', $key ) ) { return; }
	if ( isset( vr_pages()[$key] ) && ! get_page_by_path( $key, OBJECT, array( 'page' ) ) ) {
		$GLOBALS['vr_route'] = $key;
		$wp->query_vars = array( 'post_type'=>'page', 'posts_per_page'=>1 );
	}
} );
function vr_route() { return $GLOBALS['vr_route'] ?? ''; }
add_filter( 'pre_handle_404', function ( $pre ) { return vr_route() ? true : $pre; } );
add_filter( 'redirect_canonical', function ( $url ) { return vr_route() ? false : $url; } );
add_filter( 'template_include', function ( $template ) {
	return vr_route() ? get_theme_file_path( 'template-parts/vietride/page.php' ) : $template;
}, 99 );
function vr_meta() {
	if ( vr_route() ) { return vr_pages()[vr_route()]; }
	if ( is_front_page() || is_page_template( 'page-templates/template-home.php' ) ) {
		return array( 'title'=>'Scooter & Motorbike Rental in Ho Chi Minh City', 'description'=>'Rent a scooter or motorbike in Ho Chi Minh City with VietRide. Compare bikes and rental rates, check what to bring and pick up your bike in An Khanh.' );
	}
	return array();
}
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/performance.php';

function vr_bike_fuel( $bike ) {
	return $bike['fuel'] ?? ( strcasecmp( $bike['engine'], 'Electric' ) === 0 ? 'electric' : 'petrol' );
}
function vr_bike_tags( $bike ) {
	$tags = array( sanitize_title( $bike['type'] ), vr_bike_fuel( $bike ) );
	if ( $bike['type'] === 'Automatic' ) { $tags[]='scooter'; }
	if ( in_array( $bike['slug'], array('honda-sh','piaggio-vespa-lx125','piaggio-liberty'), true ) ) { $tags[]='premium'; }
	return $tags;
}
function vr_related_bikes( $bike ) {
	$fuel = vr_bike_fuel( $bike );
	$same_engine = array();
	$other_engines = array();
	foreach ( vr_data( 'bikes' ) as $candidate ) {
		if ( $candidate['slug'] === $bike['slug'] || $candidate['type'] !== $bike['type'] || vr_bike_fuel( $candidate ) !== $fuel ) {
			continue;
		}
		if ( $candidate['engine'] === $bike['engine'] ) {
			$same_engine[] = $candidate;
		} else {
			$other_engines[] = $candidate;
		}
	}
	return array_slice( array_merge( $same_engine, $other_engines ), 0, 2 );
}
function vr_use( $bike ) {
	if ( ! empty( $bike['description'] ) ) { return $bike['description']; }
	if ( vr_bike_fuel( $bike ) === 'electric' ) { return 'Short electric rides around town'; }
	if ( $bike['type'] === 'Semi-Automatic' ) { return 'Exploring at your own pace with manual gear changes'; }
	if ( in_array( 'premium', vr_bike_tags($bike), true ) ) { return 'City days on a premium scooter'; }
	return $bike['engine']==='50cc' ? 'Short neighbourhood trips' : 'Daily commutes and city exploring';
}
function vr_link( $text, $path ) {
	echo '<a class="vr-link" href="'.vr_url($path).'">'.esc_html($text).' ';
	vr_icon('arrow-right');
	echo '</a>';
}

function vr_availability_options() {
	return array('on_request'=>'Check availability', 'available'=>'Available', 'unavailable'=>'Currently unavailable');
}
function vr_availability( $bike ) {
	$options = vr_availability_options();
	return $options[$bike['availability'] ?? 'on_request'] ?? $options['on_request'];
}
function vr_rental_cta( $bike ) {
	return ( $bike['availability'] ?? 'on_request' ) === 'available' ? 'Rent this bike' : 'Enquire about dates';
}
