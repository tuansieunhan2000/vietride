<?php
defined( 'ABSPATH' ) || exit;

/** Server-only Firebase credentials. The JSON key stays outside the web root. */
function vr_fb_config() {
	return get_option( 'vr_firebase', array() );
}
function vr_fb_ready() {
	$config = vr_fb_config();
	return ! empty( $config['project'] ) && ! empty( $config['credentials'] );
}
function vr_fb_key( $suffix ) {
	return 'vr_fb_' . md5( wp_json_encode( vr_fb_config() ) ) . '_' . $suffix;
}
function vr_fb_token() {
	$cached = get_transient( vr_fb_key( 'token' ) );
	if ( $cached ) { return $cached; }
	$config = vr_fb_config();
	$file = realpath( $config['credentials'] ?? '' );
	$webroot = realpath( ABSPATH );
	if ( ! $file || ! is_file( $file ) || ! is_readable( $file ) || filesize( $file ) > 65536 || strpos( $file, $webroot . DIRECTORY_SEPARATOR ) === 0 ) {
		return new WP_Error( 'credentials', 'Place the service account JSON outside the WordPress public folder and enter its absolute path.' );
	}
	$key = json_decode( file_get_contents( $file ), true );
	if ( empty( $key['private_key'] ) || empty( $key['client_email'] ) || ( $key['project_id'] ?? '' ) !== ( $config['project'] ?? '' ) ) {
		return new WP_Error( 'credentials', 'The service account file must belong to the configured Firebase project.' );
	}
	$encode = static function ( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	};
	$now = time();
	$claims = array(
		'iss' => $key['client_email'],
		'scope' => 'https://www.googleapis.com/auth/datastore https://www.googleapis.com/auth/devstorage.read_write',
		'aud' => 'https://oauth2.googleapis.com/token',
		'iat' => $now,
		'exp' => $now + 3600,
	);
	$unsigned = $encode( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) ) . '.' . $encode( wp_json_encode( $claims ) );
	$signature = '';
	if ( ! function_exists( 'openssl_sign' ) || ! openssl_sign( $unsigned, $signature, $key['private_key'], OPENSSL_ALGO_SHA256 ) ) {
		return new WP_Error( 'sign', 'Unable to sign Firebase credentials. Check PHP OpenSSL and the service account file.' );
	}
	$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 20,
		'body' => array( 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $unsigned . '.' . $encode( $signature ) ),
	) );
	if ( is_wp_error( $response ) ) { return $response; }
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( wp_remote_retrieve_response_code( $response ) !== 200 || empty( $body['access_token'] ) ) {
		return new WP_Error( 'auth', 'Firebase authentication failed. Check the service account status and server clock.' );
	}
	set_transient( vr_fb_key( 'token' ), $body['access_token'], 3000 );
	return $body['access_token'];
}
function vr_fb_request( $url, $args = array() ) {
	$token = vr_fb_token();
	if ( is_wp_error( $token ) ) { return $token; }
	$args = wp_parse_args( $args, array( 'timeout' => 25 ) );
	$args['headers']['Authorization'] = 'Bearer ' . $token;
	$response = wp_remote_request( $url, $args );
	if ( is_wp_error( $response ) ) { return $response; }
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		if ( $code === 401 ) { delete_transient( vr_fb_key( 'token' ) ); }
		return new WP_Error( 'firebase', 'Firebase returned HTTP ' . $code . '. Check that Firestore/Storage is enabled and the service account has access.' );
	}
	return json_decode( wp_remote_retrieve_body( $response ), true );
}
function vr_fb_collection() {
	$config = vr_fb_config();
	return 'https://firestore.googleapis.com/v1/projects/' . rawurlencode( $config['project'] ?? '' ) . '/databases/(default)/documents/bikes';
}
function vr_fb_fields( $bike ) {
	$fields = array();
	foreach ( $bike as $key => $value ) {
		if ( is_array( $value ) ) {
			$fields[$key] = array( 'arrayValue' => array( 'values' => array_map( static function ( $text ) { return array( 'stringValue' => $text ); }, $value ) ) );
		} elseif ( is_bool( $value ) ) {
			$fields[$key] = array( 'booleanValue' => $value );
		} else {
			$fields[$key] = array( 'stringValue' => (string) $value );
		}
	}
	return $fields;
}
function vr_fb_list() {
	$bikes = array();
	$page = '';
	do {
		$response = vr_fb_request( vr_fb_collection() . '?pageSize=100&pageToken=' . rawurlencode( $page ) );
		if ( is_wp_error( $response ) ) { return $response; }
		foreach ( $response['documents'] ?? array() as $document ) {
			$bike = array();
			foreach ( $document['fields'] ?? array() as $key => $field ) {
				if ( isset( $field['arrayValue'] ) ) {
					$bike[$key] = array_column( $field['arrayValue']['values'] ?? array(), 'stringValue' );
				} else {
					$bike[$key] = $field['stringValue'] ?? $field['integerValue'] ?? $field['doubleValue'] ?? $field['booleanValue'] ?? '';
				}
			}
			$valid = vr_fb_validate_bike( $bike );
			if ( is_wp_error( $valid ) ) { return $valid; }
			$valid['_version'] = $document['updateTime'] ?? '';
			$bikes[] = $valid;
		}
		$page = $response['nextPageToken'] ?? '';
	} while ( $page );
	return $bikes;
}
function vr_fb_validate_bike( $input ) {
	$bike = array();
	foreach ( array( 'slug', 'name', 'brand', 'engine', 'type', 'description', 'price', 'deposit', 'thumb' ) as $key ) {
		$bike[$key] = sanitize_text_field( $input[$key] ?? '' );
	}
	foreach ( array( 'price_daily', 'price_weekly', 'price_monthly' ) as $field ) {
		$value = trim( (string) ( $input[$field] ?? '' ) );
		if ( $value !== '' && ! preg_match( '/^[0-9]{1,12}$/', $value ) ) {
			return new WP_Error( 'price', 'Enter rental rates as non-negative whole amounts in VND, without separators.' );
		}
		$bike[$field] = $value;
	}
	$bike['fuel'] = $input['fuel'] ?? ( strcasecmp( $bike['engine'], 'Electric' ) === 0 ? 'electric' : 'petrol' );
	if ( ! in_array( $bike['fuel'], array( 'petrol', 'electric' ), true ) ) {
		return new WP_Error( 'fuel', 'Choose Petrol or Electric.' );
	}
	$bike['availability'] = $input['availability'] ?? 'on_request';
	if ( ! in_array( $bike['availability'], array( 'available', 'unavailable', 'on_request' ), true ) ) {
		return new WP_Error( 'availability', 'Choose a valid rental availability.' );
	}
	if ( ! $bike['slug'] || sanitize_title( $bike['slug'] ) !== $bike['slug'] || ! $bike['name'] || ! $bike['brand'] || ! $bike['engine'] || ! in_array( $bike['type'], array( 'Automatic', 'Semi-Automatic' ), true ) ) {
		return new WP_Error( 'bike', 'Enter a valid slug, name, brand, engine and transmission.' );
	}
	foreach ( array( 'images', 'docs', 'included' ) as $key ) {
		$bike[$key] = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $input[$key] ?? array() ) ) ) );
	}
	if ( ! $bike['images'] ) { return new WP_Error( 'images', 'At least one image is required.' ); }
	$bike['thumb'] = $bike['thumb'] ?: $bike['images'][0];
	$uploads = wp_get_upload_dir();
	$upload_base = trailingslashit( $uploads['baseurl'] );
	foreach ( array_merge( $bike['images'], array( $bike['thumb'] ) ) as $image ) {
		if ( strpos( $image, $upload_base ) !== 0 && ! preg_match( '~^https://[^\s]+$~', $image ) && ! preg_match( '~^img/[a-zA-Z0-9_./-]+\.(webp|png|jpe?g)$~', $image ) ) {
			return new WP_Error( 'image', 'Images must use WordPress media URLs, HTTPS URLs or existing img/ paths.' );
		}
	}
	$bike['published'] = ! isset( $input['published'] ) || (bool) $input['published'];
	return $bike;
}
function vr_fb_save( $bike, $create = false, $version = '' ) {
	$bike = vr_fb_validate_bike( $bike );
	if ( is_wp_error( $bike ) ) { return $bike; }
	$url = vr_fb_collection() . '/' . rawurlencode( $bike['slug'] );
	if ( $create ) { $url .= '?currentDocument.exists=false'; }
	elseif ( $version ) { $url .= '?currentDocument.updateTime=' . rawurlencode( $version ); }
	$result = vr_fb_request( $url, array(
		'method' => 'PATCH',
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body' => wp_json_encode( array( 'fields' => vr_fb_fields( $bike ) ) ),
	) );
	if ( ! is_wp_error( $result ) ) {
		delete_transient( vr_fb_key( 'bikes' ) );
		update_option( vr_fb_key( 'active' ), true, false );
	}
	return $result;
}
function vr_firebase_bikes( $fallback ) {
	static $resolved;
	if ( isset( $resolved ) ) { return $resolved; }
	if ( ! vr_fb_ready() || ! get_option( vr_fb_key( 'active' ) ) ) { return $fallback; }
	$cached = get_transient( vr_fb_key( 'bikes' ) );
	if ( $cached === false ) {
		$cached = vr_fb_list();
		if ( is_wp_error( $cached ) ) {
			$cached = get_option( vr_fb_key( 'snapshot' ), $fallback );
			set_transient( vr_fb_key( 'bikes' ), $cached, 60 );
		} else {
			update_option( vr_fb_key( 'snapshot' ), $cached, false );
			set_transient( vr_fb_key( 'bikes' ), $cached, 300 );
		}
	}
	$resolved = array_values( array_filter( $cached, static function ( $bike ) { return $bike['published'] ?? true; } ) );
	return $resolved;
}
function vr_fb_upload( $file, $slug ) {
	$config = vr_fb_config();
	$path = $file['tmp_name'] ?? '';
	if ( ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK || ! is_uploaded_file( $path ) || filesize( $path ) > 8 * MB_IN_BYTES ) {
		return new WP_Error( 'upload', 'Choose an image smaller than 8 MB and try again.' );
	}
	$mime = wp_get_image_mime( $path );
	$extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
	if ( ! isset( $extensions[$mime] ) ) { return new WP_Error( 'mime', 'Use a JPEG, PNG or WebP image.' ); }
	// The free setup stores image files in the site's standard media library.
	if ( empty( $config['bucket'] ) ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'upload', 'You do not have permission to upload images.' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment = media_handle_sideload( $file, 0 );
		if ( is_wp_error( $attachment ) ) { return $attachment; }
		update_post_meta( $attachment, '_wp_attachment_image_alt', sanitize_text_field( $slug . ' rental bike' ) );
		$url = wp_get_attachment_url( $attachment );
		if ( ! $url ) {
			wp_delete_attachment( $attachment, true );
			return new WP_Error( 'upload', 'Unable to get the uploaded image URL.' );
		}
		return $url;
	}
	$object = 'bikes/' . $slug . '/' . wp_generate_uuid4() . '.' . $extensions[$mime];
	$download_token = wp_generate_uuid4();
	$metadata = wp_json_encode( array( 'name' => $object, 'contentType' => $mime, 'metadata' => array( 'firebaseStorageDownloadTokens' => $download_token ) ) );
	$boundary = 'vietride_' . wp_generate_password( 24, false );
	$body = '--' . $boundary . "\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n" . $metadata . "\r\n--" . $boundary . "\r\nContent-Type: " . $mime . "\r\n\r\n" . file_get_contents( $path ) . "\r\n--" . $boundary . "--\r\n";
	$result = vr_fb_request( 'https://storage.googleapis.com/upload/storage/v1/b/' . rawurlencode( $config['bucket'] ) . '/o?uploadType=multipart', array(
		'method' => 'POST', 'timeout' => 60,
		'headers' => array( 'Content-Type' => 'multipart/related; boundary=' . $boundary ),
		'body' => $body,
	) );
	if ( is_wp_error( $result ) ) { return $result; }
	return 'https://firebasestorage.googleapis.com/v0/b/' . rawurlencode( $config['bucket'] ) . '/o/' . rawurlencode( $object ) . '?alt=media&token=' . $download_token;
}

require_once __DIR__ . '/firebase-admin.php';
