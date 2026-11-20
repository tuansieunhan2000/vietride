<?php
/** Keep managed editorial pages small; preserve legacy and widget dependencies. */
defined( 'ABSPATH' ) || exit;

function vr_lightweight_page() {
	return (bool) vr_meta() && ! is_active_sidebar( 'footerfull' );
}
add_action( 'wp_enqueue_scripts', function () {
	if ( vr_lightweight_page() ) {
		// Plugins can still request jQuery through their own dependency lists.
		remove_action( 'wp_enqueue_scripts', 'understrap_scripts' );
	}
}, 1 );
add_action( 'wp_enqueue_scripts', function () {
	$light = vr_lightweight_page();
	foreach ( array( 'vietride-icons', 'custom-styles' ) as $handle ) { wp_dequeue_style( $handle ); }
	foreach ( array( 'vietride-ui', 'lozad', 'scrollreveal' ) as $handle ) { wp_dequeue_script( $handle ); }
	if ( $light ) {
		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles' ) as $handle ) { wp_dequeue_style( $handle ); }
		wp_enqueue_style( 'vr-base', get_theme_file_uri( 'css/vietride-base.min.css' ), array(), filemtime( get_theme_file_path( 'css/vietride-base.min.css' ) ) );
	}
	$base = $light ? 'vr-base' : 'understrap-styles';
	$files = array(
		'vr-icons' => $light ? 'fonts/remix/vietride-icons.min.css' : 'fonts/remix/remixicon.css',
		'vr-reference' => 'css/vietride-reference.min.css',
		'vr-design' => 'css/vietride.min.css',
	);
	foreach ( $files as $id => $file ) {
		wp_enqueue_style( $id, get_theme_file_uri( $file ), array( $base ), filemtime( get_theme_file_path( $file ) ) );
	}
	wp_enqueue_script( 'vr-navigation', get_theme_file_uri( 'js/vietride.min.js' ), array(), filemtime( get_theme_file_path( 'js/vietride.min.js' ) ), true );
}, 100 );

// Applies to WordPress's virtual robots.txt. A physical root file takes precedence.
add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! $public ) { return $output; }
	$sitemap = defined( 'WPSEO_VERSION' ) && class_exists( 'WPSEO_Options' ) && WPSEO_Options::get( 'enable_xml_sitemap', true )
		? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
	return preg_replace( '/^Sitemap:.*(?:\r?\n|$)/mi', '', $output ) . "\nSitemap: " . esc_url_raw( $sitemap ) . "\n";
}, 99, 2 );
