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
		'motorbikes' => array( 'kind'=>'fleet', 'title'=>'Scooter & Motorbike Fleet in Ho Chi Minh City', 'description'=>'Compare scooters, semi-automatic and electric motorbikes for rent in Ho Chi Minh City. View rental rates, photos and availability with VietRide.' ),
		'guides' => array( 'kind'=>'guides', 'title'=>vr_data('info-hub')['title'], 'description'=>vr_data('info-hub')['headline'] ),
		'routes' => array( 'kind'=>'routes', 'title'=>'Motorcycle Routes from Ho Chi Minh City', 'description'=>'Plan a city loop or a longer ride in Southern Vietnam with VietRide’s downloadable maps and local trip notes.' ),
	);
	foreach ( vr_data( 'bikes' ) as $bike ) {
		$name = $bike['brand'] . ' ' . $bike['name'];
		$pages['motorbikes/'.$bike['slug']] = array( 'kind'=>'bike', 'item'=>$bike, 'title'=>$name.' Rental in Ho Chi Minh City', 'description'=>"Rent a $name in Ho Chi Minh City. View photos, ".$bike['engine'].' specifications and rental information, then ask VietRide about your dates.' );
	}
	foreach ( vr_data( 'guides' ) as $slug => $guide ) {
		$seo = vr_guide_meta( $slug, $guide );
		$pages['guides/'.$slug] = array( 'kind'=>'guide', 'item'=>$guide, 'title'=>$seo['title'], 'description'=>$seo['description'] );
	}
	foreach ( vr_data( 'routes' ) as $slug => $route ) {
		$pages['routes/'.$slug] = array( 'kind'=>'route', 'item'=>$route, 'title'=>$route['title'], 'description'=>$route['intro'] );
	}
	return $pages;
}

// Resolve only known editorial URLs. Existing WordPress pages keep their own routes.
add_action( 'parse_request', function ( $wp ) {
	$key = trim( $wp->request, '/' );
	if ( isset( vr_pages()[$key] ) && ! get_page_by_path( $key ) ) {
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
		return array( 'title'=>'Scooter Rental in Ho Chi Minh City', 'description'=>'Rent a scooter from VietRide in An Khanh. Explore the fleet, check rental requirements and plan a city ride or a road trip in Southern Vietnam.' );
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
