<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_menu_page( 'VietRide Bikes', 'VietRide Bikes', 'manage_options', 'vietride-bikes', 'vr_fb_admin', 'dashicons-car', 25 );
} );

add_action( 'admin_init', function () {
	if ( is_admin() && current_user_can( 'manage_options' ) && ( $_GET['page'] ?? '' ) === 'vietride-bikes' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		$GLOBALS['vr_fb_admin_result'] = vr_fb_admin_post();
	}
} );

function vr_fb_admin_post() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$error = null;
	$notice = '';
	$edit = sanitize_title( wp_unslash( $_GET['bike'] ?? '' ) );
	$posted_bike = null;
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		check_admin_referer( 'vr_firebase_admin' );
		$action = sanitize_key( $_POST['vr_action'] ?? '' );
		if ( $action === 'settings' ) {
			$project = sanitize_text_field( wp_unslash( $_POST['project'] ?? '' ) );
			$bucket = sanitize_text_field( wp_unslash( $_POST['bucket'] ?? '' ) );
			$file = trim( wp_unslash( $_POST['credentials'] ?? '' ) );
			if ( ! preg_match( '/^[a-z][a-z0-9-]{4,28}[a-z0-9]$/', $project ) || ( $bucket !== '' && ! preg_match( '/^[a-z0-9][a-z0-9.-]+$/', $bucket ) ) ) {
				$error = new WP_Error( 'config', 'Enter a valid Project ID and bucket name, without gs:// or https://.' );
			} else {
				update_option( 'vr_firebase', array( 'project' => $project, 'bucket' => $bucket, 'credentials' => $file ), false );
				$notice = 'Connection settings saved.';
			}
		}
		if ( $action === 'save' ) {
			$bike = array();
			foreach ( array( 'slug', 'name', 'brand', 'engine', 'type', 'description', 'price', 'deposit', 'thumb', 'price_daily', 'price_weekly', 'price_monthly', 'availability', 'fuel' ) as $field ) {
				$bike[$field] = sanitize_text_field( wp_unslash( $_POST[$field] ?? '' ) );
			}
			foreach ( array( 'images', 'docs', 'included' ) as $field ) {
				$bike[$field] = preg_split( '/\r?\n/', trim( wp_unslash( $_POST[$field] ?? '' ) ) );
			}
			$bike['fuel'] = $bike['fuel'] ?: ( strcasecmp($bike['engine'], 'Electric') === 0 ? 'electric' : 'petrol' );
			$bike['availability'] = $bike['availability'] ?: 'on_request';
			$bike['published'] = isset( $_POST['published'] );
			$posted_bike = $bike;
			// Validate before uploading a photo.
			$validation = $bike;
			$has_upload = ( $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_NO_FILE;
			if ( $has_upload && ! array_filter( $validation['images'] ) ) { $validation['images'] = array( 'img/Vietride1.webp' ); }
			$valid = vr_fb_validate_bike( $validation );
			if ( is_wp_error( $valid ) ) {
				$error = $valid;
			} else {
				if ( $has_upload ) {
					$photo = vr_fb_upload( $_FILES['photo'], $bike['slug'] );
					if ( is_wp_error( $photo ) ) {
						$error = $photo;
					} else {
						$bike['images'][] = $photo;
						$bike['images'] = array_values( array_filter( $bike['images'] ) );
						$posted_bike = $bike;
					}
				}
				if ( ! $error ) {
					$version = sanitize_text_field( wp_unslash( $_POST['version'] ?? '' ) );
					$result = vr_fb_save( $bike, ! $version, $version );
					if ( is_wp_error( $result ) ) {
						$error = $result;
					} else {
						wp_safe_redirect( admin_url( 'admin.php?page=vietride-bikes&saved=1&bike=' . rawurlencode( $bike['slug'] ) ) );
						exit;
					}
				}
			}
		}
		if ( $action === 'sync' ) {
			$existing = vr_fb_list();
			if ( is_wp_error( $existing ) ) {
				$error = $existing;
			} else {
				update_option( vr_fb_key('snapshot'), $existing, false );
				set_transient( vr_fb_key('bikes'), $existing, 300 );
				update_option( vr_fb_key('active'), true, false );
				$notice = 'Connected to the existing Firebase fleet.';
			}
		}
		if ( $action === 'seed' ) {
			$existing = vr_fb_list();
			if ( is_wp_error( $existing ) ) {
				$error = $existing;
			} else {
				$slugs = array_column( $existing, 'slug' );
				$writes = array();
				$local = json_decode( file_get_contents( __DIR__ . '/bikes.json' ), true );
				foreach ( $local as $bike ) {
					if ( in_array( $bike['slug'], $slugs, true ) ) { continue; }
					$writes[] = array(
						'update' => array( 'name' => 'projects/' . vr_fb_config()['project'] . '/databases/(default)/documents/bikes/' . $bike['slug'], 'fields' => vr_fb_fields( $bike ) ),
						'currentDocument' => array( 'exists' => false ),
					);
				}
				if ( $writes ) {
					$url = substr( vr_fb_collection(), 0, -strlen( '/bikes' ) ) . ':commit';
					$result = vr_fb_request( $url, array( 'method' => 'POST', 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'writes' => $writes ) ) ) );
					if ( is_wp_error( $result ) ) { $error = $result; }
				}
				if ( ! $error ) {
					delete_transient( vr_fb_key( 'bikes' ) );
					update_option( vr_fb_key( 'active' ), true, false );
					$notice = count( $writes ) . ' bikes imported. Existing Firebase bikes were preserved.';
				}
			}
		}
	}
	return compact( 'error', 'notice', 'edit', 'posted_bike' );
}

function vr_fb_admin() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$state = $GLOBALS['vr_fb_admin_result'] ?? array();
	$error = $state['error'] ?? null;
	$notice = $state['notice'] ?? '';
	$posted_bike = $state['posted_bike'] ?? null;
	$edit = $state['edit'] ?? sanitize_title( wp_unslash( $_GET['bike'] ?? '' ) );
	$config = vr_fb_config();
	echo '<div class="wrap"><h1>VietRide Bikes</h1>';
	if ( ! empty( $_GET['saved'] ) ) { $notice = 'Bike saved to Firebase.'; }
	if ( $notice ) { echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>'; }
	if ( $error ) { echo '<div class="notice notice-error"><p>' . esc_html( $error->get_error_message() ) . '</p></div>'; }
	?>
	<details <?php if ( ! vr_fb_ready() ) { echo 'open'; } ?>>
		<summary><strong>Firebase connection</strong></summary>
		<p>Use Firestore for bike information. With an empty Storage bucket, photos are saved in the WordPress media library. The service account needs the Cloud Datastore User role.</p>
		<form method="post">
			<?php wp_nonce_field( 'vr_firebase_admin' ); ?>
			<input type="hidden" name="vr_action" value="settings">
			<table class="form-table">
				<tr><th><label for="project">Project ID</label></th><td><input class="regular-text" id="project" name="project" required value="<?php echo esc_attr( $config['project'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="bucket">Storage bucket (optional)</label></th><td><input class="regular-text" id="bucket" name="bucket" placeholder="project-id.firebasestorage.app" value="<?php echo esc_attr( $config['bucket'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="credentials">Service account file</label></th><td><input class="large-text" id="credentials" name="credentials" required value="<?php echo esc_attr( $config['credentials'] ?? '' ); ?>"><p class="description">Absolute path to a private JSON file outside the WordPress public directory. Keep the file readable only by the server account.</p></td></tr>
			</table>
			<?php submit_button( 'Save connection' ); ?>
		</form>
	</details>
	<?php
	if ( ! vr_fb_ready() ) {
		echo '<p>Configure Firebase above to manage bikes. The website currently uses the existing fleet.</p></div>';
		return;
	}
	if ( empty( $config['bucket'] ) ) {
		echo '<div class="notice notice-info"><p>Free setup is active. Bike information is saved in Firestore and photos are saved in the WordPress media library.</p></div>';
	}
	$bikes = vr_fb_list();
	if ( is_wp_error( $bikes ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $bikes->get_error_message() ) . '</p></div></div>';
		return;
	}
	$bike = array();
	foreach ( $bikes as $record ) { if ( $record['slug'] === $edit ) { $bike = $record; } }
	if ( $posted_bike ) { $bike = $posted_bike + array( '_version' => sanitize_text_field( wp_unslash( $_POST['version'] ?? '' ) ) ); }
	?>
	<p>Connected to <strong><?php echo esc_html( $config['project'] ); ?></strong>. Public fleet refreshes within five minutes of changes made in the Firebase Console.</p>
	<form method="post"><?php wp_nonce_field('vr_firebase_admin'); ?><input type="hidden" name="vr_action" value="sync"><?php submit_button('Use existing Firebase fleet', 'primary', 'submit', false); ?></form><p>Use this after connecting a new WordPress installation to your Firebase project.</p>
	<form method="post"><?php wp_nonce_field( 'vr_firebase_admin' ); ?><input type="hidden" name="vr_action" value="seed"><?php submit_button( 'Import missing bikes from current fleet', 'secondary', 'submit', false ); ?><p class="description">Copies the current bike information and image references to Firestore. Upload replacement images or select them from the media library in the editor below.</p></form>
	<table class="widefat striped"><thead><tr><th>Photo</th><th>Bike</th><th>Rental rates (VND)</th><th>Availability</th><th>Visibility</th><th>Manage</th></tr></thead><tbody>
	<?php foreach ( $bikes as $record ) : ?>
		<tr><td><img class="vr-admin-list-photo" src="<?php echo vr_asset($record['thumb']); ?>" width="90" height="65" alt="<?php echo esc_attr($record['name']); ?>"></td><td><?php echo esc_html( $record['brand'] . ' ' . $record['name'] ); ?></td><td><?php get_template_part('template-parts/vietride/rates', null, array('bike'=>$record)); ?></td><td><?php echo esc_html(vr_availability($record)); ?></td><td><?php echo $record['published'] ? 'Published' : 'Hidden'; ?></td><td><a href="<?php echo esc_url( admin_url( 'admin.php?page=vietride-bikes&bike=' . rawurlencode( $record['slug'] ) ) ); ?>">Edit bike</a></td></tr>
	<?php endforeach; ?>
	</tbody></table>
	<h2><?php echo $bike ? 'Edit bike' : 'Add bike'; ?></h2>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=vietride-bikes' ) ); ?>">Add a new bike</a></p>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'vr_firebase_admin' ); ?>
		<input type="hidden" name="vr_action" value="save">
		<input type="hidden" name="version" value="<?php echo esc_attr( $bike['_version'] ?? '' ); ?>">
		<table class="form-table">
		<?php foreach ( array( 'slug' => 'URL slug', 'name' => 'Model name', 'brand' => 'Brand', 'engine' => 'Engine / displacement', 'description' => 'Recommended use / description', 'price' => 'Legacy price note (optional)', 'deposit' => 'Deposit', 'thumb' => 'Preview image URL or img/ path (optional)' ) as $field => $label ) : ?>
			<tr><th><label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label></th><td><input class="large-text" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $bike[$field] ?? '' ); ?>" <?php if ( in_array( $field, array( 'slug', 'name', 'brand', 'engine' ), true ) ) { echo 'required'; } ?> <?php if ( $field === 'slug' && ! empty( $bike['_version'] ) ) { echo 'readonly'; } ?>>
				<?php if ( $field === 'thumb' ) : ?><button type="button" class="button" id="vr-choose-preview">Choose cover photo</button><div id="vr-cover-preview" class="vr-cover-preview"></div><?php endif; ?></td></tr>
		<?php endforeach; ?>
			<tr><th>Rental rates (VND)</th><td><div class="vr-admin-rates">
				<?php foreach(array('price_daily'=>'1 day', 'price_weekly'=>'1 week', 'price_monthly'=>'1 month') as $field=>$label): ?>
				<label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?><input type="number" min="0" max="999999999999" step="1" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($bike[$field] ?? ''); ?>"></label>
				<?php endforeach; ?>
			</div><p class="description">Enter the total price for each rental period. Leave blank to show “Contact for rates”.</p></td></tr>
			<tr><th><label for="availability">Rental availability</label></th><td><select name="availability" id="availability">
				<?php foreach(vr_availability_options() as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($bike['availability'] ?? 'on_request', $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
			</select><p class="description">Currently unavailable bikes remain visible, with an enquiry button to check future dates. Visibility below controls whether the listing is published.</p></td></tr>
			<tr><th><label for="fuel">Fuel / power</label></th><td><select id="fuel" name="fuel"><?php foreach(array('petrol'=>'Petrol', 'electric'=>'Electric') as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected(empty($bike) ? 'petrol' : vr_bike_fuel($bike), $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><p class="description">For electric bikes, enter motor power or “Electric” in the engine field.</p></td></tr>
			<tr><th><label for="type">Transmission</label></th><td><select id="type" name="type"><?php foreach ( array( 'Automatic', 'Semi-Automatic' ) as $type ) : ?><option <?php selected( $bike['type'] ?? '', $type ); ?>><?php echo esc_html( $type ); ?></option><?php endforeach; ?></select></td></tr>
			<?php foreach ( array( 'images' => 'Gallery images (first image is the main photo)', 'docs' => 'Required documents', 'included' => 'Included with rental' ) as $field => $label ) : ?>
				<tr><th><label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label></th><td><textarea class="large-text" rows="5" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>"><?php echo esc_textarea( implode( "\n", $bike[$field] ?? array() ) ); ?></textarea><p class="description">One item per line. Reorder or remove lines to change the gallery or list.</p>
					<?php if ( $field === 'images' ) : ?><button type="button" class="button" id="vr-choose-photos">Choose from media library</button><div id="vr-gallery-editor" class="vr-gallery-editor"></div><p class="description">Use the photo controls to set the main photo, reorder or remove it from this bike. Files remain in the media library.</p><?php endif; ?></td></tr>
			<?php endforeach; ?>
			<tr><th><label for="photo">Upload photo</label></th><td><input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"><p class="description">JPEG, PNG or WebP, up to 8 MB. Added to the gallery when you save.</p></td></tr>
			<tr><th>Visibility</th><td><label><input type="checkbox" name="published" value="1" <?php checked( $bike['published'] ?? true ); ?>> Publish on the website</label></td></tr>
		</table>
		<?php submit_button( 'Save bike to Firebase' ); ?>
	</form></div>
	<?php
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'toplevel_page_vietride-bikes' || ! current_user_can( 'manage_options' ) ) { return; }
	wp_enqueue_media();
	wp_enqueue_style('vr-fleet-admin', get_theme_file_uri('css/vietride-admin.css'), array(), filemtime(get_theme_file_path('css/vietride-admin.css')));
	wp_localize_script('media-views', 'vrFleetMedia', array('themeUrl'=>trailingslashit(get_theme_file_uri())));

	wp_enqueue_script(
		'vr-fleet-media',
		get_theme_file_uri( 'js/vietride-admin.js' ),
		array( 'media-views' ),
		filemtime( get_theme_file_path( 'js/vietride-admin.js' ) ),
		true
	);
} );
