<?php

/**
 * Registers plugin options with the Settings API.
 */
function mfg_admin_init() {
	add_settings_section(
		'mfg_section_flickr',            // ID used to identify this section and with which to register options
		__( 'Flickr Settings', 'mfg' ),  // Title to be displayed on the administration page
		'mfg_section_flickr_callback',   // Callback used to render the description of the section
		'mfg_plugin_page'                // Page on which to add this section of options
	);
	add_settings_section(
		'mfg_section_gallery',
		__( 'Gallery Settings', 'mfg' ),
		'mfg_section_gallery_callback',
		'mfg_plugin_page'
	);

	add_settings_field(
		'mfg[flickr_api_key]',           // ID used to identify the field throughout the plugin
		__( 'Flickr API Key', 'mfg' ),   // The label to the left of the option interface element
		'mfg_api_key_callback',          // The name of the function responsible for rendering the option interface
		'mfg_plugin_page',               // The page on which this option will be displayed
		'mfg_section_flickr',            // The name of the section to which this field belongs
		array(                           // The array of arguments to pass to the callback. In this case, just a description.
			__( 'Don\'t have a Flickr API Key? Get it from <a target="blank" href="http://www.flickr.com/services/api/keys/">here</a>. Make sure to read the <a href="http://www.flickr.com/services/api/tos/">Flickr API Terms of Service</a>.', 'mfg' )
		)
	);
	add_settings_field(
		'mfg[flickr_username]',
		__( 'Flickr Username', 'mfg' ),
		'mfg_username_callback',
		'mfg_plugin_page',
		'mfg_section_flickr',
		array(
			__( 'Your Flickr username.', 'mfg' )
		)
	);
	add_settings_field(
		'mfg[gallery_photos_per_page]',
		__( 'Photos per Page', 'mfg' ),
		'mfg_photos_per_page_callback',
		'mfg_plugin_page',
		'mfg_section_gallery',
		array(
			__( 'The number of photos to display per page.', 'mfg' )
		)
	);
	add_settings_field(
		'mfg[gallery_image_size]',
		__( 'Photos per Page', 'mfg' ),
		'mfg_image_size_callback',
		'mfg_plugin_page',
		'mfg_section_gallery',
		array(
			__( 'The size of the images.', 'mfg' )
		)
	);
	add_settings_field(
		'mfg[gallery_captions]',
		__( 'Captions', 'mfg' ),
		'mfg_captions_callback',
		'mfg_plugin_page',
		'mfg_section_gallery',
		array(
			__( 'Display captions?', 'mfg' )
		)
	);
	add_settings_field(
		'mfg[gallery_colorbox]',
		__( 'Colorbox', 'mfg' ),
		'mfg_colorbox_callback',
		'mfg_plugin_page',
		'mfg_section_gallery',
		array(
			__( 'Enable support for <a href="https://wordpress.org/extend/plugins/jquery-colorbox/">jQuery Colorbox</a>?', 'mfg' )
		)
	);
	add_settings_field(
		'mfg[gallery_ajax]',
		__( 'AJAX Navigation', 'mfg' ),
		'mfg_ajax_callback',
		'mfg_plugin_page',
		'mfg_section_gallery',
		array(
			__( 'Enable AJAX navigation?', 'mfg' )
		)
	);

	register_setting(
		'mfg_options',                   // The option group
		'mfg',                           // The option name
		'msg_options_validate'           // The options validation callback
	);
}
add_action( 'admin_init', 'mfg_admin_init' );


/**
 * Adds a sub-menu for our plugin under the main Settings menu.
 */
function mfg_admin_menu() {
	add_options_page(
		'My Flickr Gallery',             // The text to be displayed in the title tags of the page when the menu is selected
		'My Flickr Gallery',             // The text to be used for the menu
		'manage_options',                // The capability required for this menu to be displayed to the user
		'mfg_plugin_page',               // The slug name to refer to this menu by (should be unique for this menu)
		'mfg_admin_html_page'            // The function to be called to output the content for this page
	);
}
add_action( 'admin_menu', 'mfg_admin_menu' );


/**
 * Adds a 'settings' action link for our plugin on the main Plugins page.
 */
function mfg_add_settings_links( $links, $file ) {
	if ( $file == plugin_basename( dirname( __FILE__ ) . '/my-flickr-gallery.php' ) ) {
		$settings_link = '<a href="options-general.php?page=mfg_plugin_page">Settings</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'mfg_add_settings_links', 10, 2 );


/**
 * Renders our settings page.
 */
function mfg_admin_html_page() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2>My Flickr Gallery Settings</h2>
		<form action="options.php" method="POST">
			<?php settings_fields( 'mfg_options' ); ?>
			<?php do_settings_sections( 'mfg_plugin_page' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}


/**
 *
 */
function msg_options_validate($input) {
	if ( empty( $input['flickr_api_key'] ) ) {
		add_settings_error( 'mfg[flickr_api_key]', 'mfg_flickr_api_key', __( 'API key not set', 'mfg' ) );
	}
	if ( empty( $input['flickr_username'] ) ) {
		add_settings_error( 'mfg[flickr_username]', 'mfg_flickr_username', __( 'Username not set', 'mfg' ) );
	}
	if ( empty( $input['gallery_photos_per_page'] ) ) {
		add_settings_error( 'mfg[gallery_photos_per_page]', 'mfg_gallery_photos_per_page', __( 'Photos per page not set', 'mfg' ) );
	}
	return $input;
}



/* ------------------------------------------------------------------------ * 
 * Section Callbacks 
 * ------------------------------------------------------------------------ */ 

/** 
 * This function provides a simple description for the Flickr settings section.  
 * 
 * It is called from the 'mfg_admin_init' function by being passed as a parameter 
 * in the add_settings_section function. 
 */ 
function mfg_section_flickr_callback() {
	_e( 'Some help text goes here.', 'mfg' );
}

/** 
 * This function provides a simple description for the Gallery settings section.  
 * 
 * It is called from the 'mfg_admin_init' function by being passed as a parameter 
 * in the add_settings_section function. 
 */ 
function mfg_section_gallery_callback() {
	_e( 'Some help text goes here.', 'mfg' );
}


/* ------------------------------------------------------------------------ * 
 * Field Callbacks 
 * ------------------------------------------------------------------------ */   

/** 
 * This function renders the interface elements for the Flickr API key setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */  
function mfg_api_key_callback( $args ) {
	$options = get_option( 'mfg' );
	echo '<input type="text" class="regular-text" name="mfg[flickr_api_key]" value="' . esc_attr( $options['flickr_api_key'] ) . '" />';
	echo '<p class="description">' . $args[0] . '</p>';
}

/** 
 * This function renders the interface elements for the Flickr username setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */
function mfg_username_callback( $args ) {
	$options = get_option( 'mfg' );
	echo '<input type="text" class="regular-text" name="mfg[flickr_username]" value="' . esc_attr( $options['flickr_username'] ) . '" />';
	echo '<p class="description">' . $args[0] . '</p>';
}

/** 
 * This function renders the interface elements for the photos per page setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */
function mfg_photos_per_page_callback( $args ) {
	$options = get_option( 'mfg' );
	echo '<input type="text" class="small-text" name="mfg[gallery_photos_per_page]" value="' . esc_attr( $options['gallery_photos_per_page'] ) . '" />';
	echo '<p class="description">' . $args[0] . '</p>';
}

/** 
 * This function renders the interface elements for the image size setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */
function mfg_image_size_callback( $args ) {
	$options = get_option( 'mfg' );
	$image_size = $options['gallery_image_size'];
	echo '<select name="mfg[gallery_image_size]">';
	echo '	<option value="_s"' . ( $image_size == '_s' ? 'selected="selected"': '' ) . '>'. __( 'Small Square (75x75)', 'mfg' ) . '</option>';
	echo '	<option value="_t"' . ( $image_size == '_t' ? 'selected="selected"': '' ) . '>'. __( 'Thumbnail (max 100px)', 'mfg' ) . '</option>';
	echo '	<option value="_q"' . ( $image_size == '_q' ? 'selected="selected"': '' ) . '>'. __( 'Large Square (150x150)', 'mfg' ) . '</option>';
	echo '	<option value="_m"' . ( $image_size == '_m' ? 'selected="selected"': '' ) . '>'. __( 'Small (max 240px)', 'mfg' ) . '</option>';
	echo '	<option value="_n"' . ( $image_size == '_n' ? 'selected="selected"': '' ) . '>'. __( 'Small (max 320px)', 'mfg' ) . '</option>';
	echo '	<option value=""'   . ( $image_size == ''   ? 'selected="selected"': '' ) . '>'. __( 'Medium (max 500px)', 'mfg' ) . '</option>';
	echo '	<option value="_z"' . ( $image_size == '_z' ? 'selected="selected"': '' ) . '>'. __( 'Medium (max 640px)', 'mfg' ) . '</option>';
	echo '	<option value="_c"' . ( $image_size == '_c' ? 'selected="selected"': '' ) . '>'. __( 'Medium (max 800px)', 'mfg' ) . '</option>';
	echo '	<option value="_b"' . ( $image_size == '_b' ? 'selected="selected"': '' ) . '>'. __( 'Large (max 1024px)', 'mfg' ) . '</option>';
	echo '	<option value="_o"' . ( $image_size == '_o' ? 'selected="selected"': '' ) . '>'. __( 'Original', 'mfg' ) . '</option>';
	echo '</select>';
	echo '<p class="description">' . $args[0] . '</p>';
}

/** 
 * This function renders the interface elements for the captions setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */
function mfg_captions_callback( $args ) {
	$options = get_option( 'mfg' );
	$captions = $options['gallery_captions'];
	echo '<select name="mfg[gallery_captions]">';
	echo '	<option value="yes"' . ( $captions == 'yes' ? 'selected="selected"': '' ) . '>' . __( 'Yes', 'mfg' ) . '</option>';
	echo '	<option value="no"' . ( $captions == 'no' ? 'selected="selected"': '' ) . '>' . __( 'No', 'mfg' ) . '</option>';
	echo '</select>';
	echo '<p class="description">' . $args[0] . '</p>';
}

/** 
 * This function renders the interface elements for the colorbox setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */
function mfg_colorbox_callback( $args ) {
	$options = get_option( 'mfg' );
	$captions = $options['gallery_colorbox'];
	echo '<select name="mfg[gallery_colorbox]">';
	echo '	<option value="yes"' . ( $colorbox == 'yes' ? 'selected="selected"': '') . '>' . __( 'Yes', 'mfg' ) . '</option>';
	echo '	<option value="no"' . ( $colorbox == 'no' ? 'selected="selected"': '') . '>' . __( 'No', 'mfg' ) . '</option>';
	echo '</select>';
	echo '<p class="description">' . $args[0] . '</p>';
}

/** 
 * This function renders the interface elements for the AJAX navigation setting.
 *  
 * It accepts an array of arguments and expects the first element in the array to be the description 
 * to be displayed next to the input field. 
 */
function mfg_ajax_callback( $args ) {
	$options = get_option( 'mfg' );
	$ajax = $options['gallery_ajax'];
	echo '<select name="mfg[gallery_ajax]">';
	echo '	<option value="yes"' . ( $ajax == 'yes' ? 'selected="selected"': '') . '>' . __( 'Yes', 'mfg' ) . '</option>';
	echo '	<option value="no"' . ( $ajax == 'no' ? 'selected="selected"': '') . '>' . __( 'No', 'mfg' ) . '</option>';
	echo '</select>';
	echo '<p class="description">' . $args[0] . '</p>';
}
