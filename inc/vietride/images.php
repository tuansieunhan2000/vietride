<?php
/** Responsive delivery for bundled photos and standard WordPress Media images. */
defined( 'ABSPATH' ) || exit;

function vr_image_manifest() {
	static $manifest;
	if ( ! isset( $manifest ) ) {
		$file = get_theme_file_path( 'img/responsive/manifest.json' );
		$decoded = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
		$manifest = is_array( $decoded ) ? ( $decoded['images'] ?? array() ) : array();
	}
	return $manifest;
}

/** Actual painted width of a contained photo, rather than its larger card frame. */
function vr_fleet_image_sizes( $source ) {
	$path = ltrim( (string) $source, '/' );
	$base = trailingslashit( get_theme_file_uri() );
	if ( strpos( $source, $base ) === 0 ) { $path = substr( $source, strlen( $base ) ); }
	$meta = vr_image_manifest()[$path] ?? array();
	if ( ! $meta ) {
		$uploads = wp_get_upload_dir();
		if ( strpos( $source, trailingslashit( $uploads['baseurl'] ) ) === 0 ) {
			$attachment = attachment_url_to_postid( $source );
			if ( $attachment ) { $meta = wp_get_attachment_metadata( $attachment ) ?: array(); }
		}
	}
	$ratio = ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ? $meta['width'] / $meta['height'] : 0;
	$desktop = $ratio ? min( 1, $ratio / ( 4 / 3 ) ) : 1;
	$mobile = $ratio ? min( 1, $ratio / ( 16 / 11 ) ) : 1;
	$number = static function ( $value ) { return rtrim( rtrim( number_format( $value, 6, '.', '' ), '0' ), '.' ); };
	$d = $number( $desktop ); $m = $number( $mobile );
	return '(min-width: 1200px) ' . $number( 368 * $desktop ) . 'px, '
		. '(min-width: 1024px) calc((100vw - 96px) / 3 * ' . $d . '), '
		. '(min-width: 768px) calc((100vw - 52px) / 2 * ' . $d . '), '
		. '(min-width: 521px) calc((100vw - 60px) / 2 * ' . $d . '), '
		. 'calc((100vw - 40px) * ' . $m . ')';
}

/** Data is also used by gallery links to switch srcset together with src. */
function vr_image_data( $source, $attrs = array() ) {
	$source = (string) $source;
	$path = ltrim( $source, '/' );
	$base = trailingslashit( get_theme_file_uri() );
	if ( strpos( $source, $base ) === 0 ) { $path = substr( $source, strlen( $base ) ); }
	$entry = vr_image_manifest()[$path] ?? null;
	$data = array( 'src' => html_entity_decode( vr_asset( $source ), ENT_QUOTES, 'UTF-8' ) );
	if ( $entry && ! empty( $entry['variants'] ) ) {
		$variants = $entry['variants'];
		// Originals remain available at native size for large gallery/retina requests.
		$last = end( $variants );
		if ( $entry['width'] > $last['width'] ) {
			$variants[] = array( 'file' => $entry['source'], 'width' => $entry['width'], 'height' => $entry['height'] );
		}
		$cap = absint( $attrs['max_width'] ?? 0 );
		if ( $cap ) {
			$bounded = array_values( array_filter( $variants, static function ( $image ) use ( $cap ) { return $image['width'] <= $cap; } ) );
			$variants = $bounded ?: array( $variants[0] );
		}
		$wanted = min( 960, absint( $attrs['width'] ?? 960 ) );
		$selected = end( $variants );
		foreach ( $variants as $variant ) {
			if ( $variant['width'] >= $wanted ) { $selected = $variant; break; }
		}
		$data['src'] = get_theme_file_uri( $selected['file'] );
		$data['width'] = $entry['width'];
		$data['height'] = $entry['height'];
		$data['srcset'] = implode( ', ', array_map( static function ( $image ) { return get_theme_file_uri( $image['file'] ) . ' ' . $image['width'] . 'w'; }, $variants ) );
		$data['sizes'] = $attrs['sizes'] ?? '100vw';
	} else {
		// Uploaded photos retain WordPress's generated sizes without cloud dependencies.
		$uploads = wp_get_upload_dir();
		if ( strpos( $source, trailingslashit( $uploads['baseurl'] ) ) === 0 ) {
			static $attachments = array();
			if ( ! array_key_exists( $source, $attachments ) ) { $attachments[$source] = attachment_url_to_postid( $source ); }
			$attachment = $attachments[$source];
			if ( $attachment ) {
				$width = absint( $attrs['width'] ?? 960 );
				$image = wp_get_attachment_image_src( $attachment, array( $width, $width ), false );
				if ( $image ) {
					$data['src'] = $image[0]; $data['width'] = $image[1]; $data['height'] = $image[2];
				}
				$srcset = wp_get_attachment_image_srcset( $attachment, 'full' );
				if ( $srcset ) { $data['srcset'] = $srcset; $data['sizes'] = $attrs['sizes'] ?? '100vw'; }
			}
		}
	}
	foreach ( array( 'width', 'height', 'sizes' ) as $key ) {
		if ( isset( $attrs[$key] ) ) { $data[$key] = $attrs[$key]; }
	}
	return $data;
}

/** Returns one image tag; the caller supplies its existing layout and accessibility attrs. */
function vr_image_html( $source, $alt = '', $attrs = array() ) {
	$data = vr_image_data( $source, $attrs );
	$max_width = $attrs['max_width'] ?? 0;
	unset( $attrs['max_width'] );
	$data = array_merge( array( 'alt' => $alt, 'decoding' => 'async' ), $data, $attrs );
	$output = '<img';
	foreach ( $data as $name => $value ) {
		if ( ! preg_match( '/^[a-z][a-z0-9_-]*$/i', $name ) || $value === false || $value === null ) { continue; }
		if ( $value === true ) { $output .= ' ' . esc_attr( $name ); }
		else { $output .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"'; }
	}
	$output .= '>';
	// Only the two fixed opening images use picture; gallery img switching stays simple.
	$path = ltrim( (string) $source, '/' );
	$base = trailingslashit( get_theme_file_uri() );
	if ( strpos( $source, $base ) === 0 ) { $path = substr( $source, strlen( $base ) ); }
	$entry = vr_image_manifest()[$path] ?? array();
	if ( ! empty( $entry['avif_variants'] ) ) {
		$variants = $entry['avif_variants'];
		$cap = absint( $max_width );
		if ( $cap ) {
			$bounded = array_values( array_filter( $variants, static function ( $image ) use ( $cap ) { return $image['width'] <= $cap; } ) );
			$variants = $bounded ?: array( $variants[0] );
		}
		$srcset = implode( ', ', array_map( static function ( $image ) { return get_theme_file_uri( $image['file'] ) . ' ' . $image['width'] . 'w'; }, $variants ) );
		return '<picture><source type="image/avif" srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $data['sizes'] ?? '100vw' ) . '">' . $output . '</picture>';
	}
	return $output;
}
