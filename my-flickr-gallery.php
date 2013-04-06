<?php
/*
Plugin Name: My Flickr Gallery
Plugin URI: http://www.uk-dave.com
Description: A Flickr plugin for Wordpress.
Version: 1.0.0
Author: David Bull
Author URI: http://www.uk-dave.com
License: GPL2

Copyright 2013 David Bull (email : david@uk-dave.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once( dirname( __FILE__ ) . '/phpFlickr-3.1/phpFlickr.php' );

/**
 * Init.
 */
function mfg_init() {
	load_plugin_textdomain( 'mfg', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'mfg_init' );



if ( is_admin() ) {
	require_once( dirname( __FILE__ ) . '/admin.php' );
}

/**
 * Enqueus the styles and scripts for our plugin.
 */
function mfg_enqueue_assets() {
	// Load our main stylesheet
	wp_enqueue_style( 'mfg', plugins_url( 'my-flickr-gallery.css', __FILE__ ), array(), '0.1' );

	// Load Bootstrap style rules for thumbnails, but only if Bootstrap is not already used
	$bs = 0;
	$bs |= wp_style_is( 'bootstrap', 'registered' );
	$bs |= wp_style_is( 'bootstrap', 'queue' );
	$bs |= wp_style_is( 'bootstrap', 'done' );
	$bs |= wp_style_is( 'bootstrap', 'to_do' );
	if ( $bs == 0 ) {
		wp_enqueue_style( 'mfg-bootstrap', plugins_url( 'my-flickr-gallery-bootstrap.css', __FILE__ ), array(), '0.1' );
	}

	// Load our main javascript file
	$options = get_option( 'mfg' );
	if ( $options['gallery_ajax'] == 'yes' ) {
		wp_enqueue_script( 'mfg', plugins_url( 'my-flickr-gallery.js', __FILE__ ), array( 'jquery' ), '0.1' );
		wp_localize_script( 'mfg', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}
}
add_action( 'wp_enqueue_scripts', 'mfg_enqueue_assets' );


/**
 * Register a query variable so we can navigate between pages in the gallery.
 */
function mfg_queryvars( $qvars ) {
	$qvars[] = 'mfg_page';
	return $qvars;
}
add_filter( 'query_vars', 'mfg_queryvars' );


/**
 * [mfg] shortcode.
 */
function mfg_shortcode( $atts ) {
	// Get the page number from the query variable
	global $wp_query;
	$page = intval( $wp_query->query_vars['mfg_page'] );
	if ( $page <= 0 ) {
		$page = 1;
	}

	// Generate the HTML
	return mfg_build_gallery( get_the_ID(), $page );
}
add_shortcode( 'mfg', 'mfg_shortcode' );


/**
 * MFG ajax function.
 */
function mfg_ajax() {
	// Get the post_id from the query variable (used for colorbox support)
	$post_id = intval( $_GET['mfg_post_id'] );

	// Get the page number from the query variable
	$page = intval( $_GET['mfg_page'] );
	if ( $page <= 0 ) {
		$page = 1;
	}

	// Ganerate the HTML
	header( 'Content-Type: text/html' );
	echo mfg_build_gallery( $post_id, $page );
	 
	// IMPORTANT: don't forget to "exit"
	exit;
}
add_action( 'wp_ajax_nopriv_mfg_ajax', 'mfg_ajax' );
add_action( 'wp_ajax_mfg_ajax', 'mfg_ajax' );


/**
 * Generates the HTML block the our Flickr gallery.
 *
 * @param post_id the Wordpress post ID (used for colorbox support)
 * @param page the gallery page number
 */
function mfg_build_gallery( $post_id = 0, $page = 1 ) {
	global $mfg_photo_sizes;

	$out = '<div id="mfg">';
	$out .= '<input type="hidden" id="mfg-post-id" name="mfg-post-id" value="' . $post_id . '" />';

	// Get plugin settings
	$options = get_option( 'mfg' );
	$api_key = $options['flickr_api_key'];
	$username = $options['flickr_username'];
	$photos_per_page = intval( $options['gallery_photos_per_page'] );
	$image_size = $options['gallery_image_size'];
	$captions = $options['gallery_captions'] == 'yes';
	$colorbox = $options['gallery_colorbox'] == 'yes';

	// Validate plugin settings
	$options_ok = 1;
	if ( empty( $api_key ) ) {
		$options_ok = 0;
		$out .= '<div class="alert alert-danger">';
		$out .= '<strong>' . __( 'Oh snap!', 'mfg' ) . '</strong> ' . __( 'Flickr API key not set - Check plugin settings page.', 'mfg' );
		$out .= '</div>';
	}
	if ( empty( $username ) ) {
		$options_ok = 0;
		$out .= '<div class="alert alert-danger">';
		$out .= '<strong>' . __( 'Oh snap!', 'mfg' ) . '</strong> ' . __( 'Flickr username not set - Check plugin settings page.', 'mfg' );
		$out .= '</div>';
	}
	if ( $photos_per_page <= 0 ) {
		$options_ok = 0;
		$out .= '<div class="alert alert-danger">';
		$out .= '<strong>' . __( 'Oh snap!', 'mfg' ) . '</strong> ' . __( 'Photos per page not set - Check plugin settings page.', 'mfg' );
		$out .= '</div>';
	}
	if ( $mfg_photo_sizes[$image_size] == NULL ) {
		$options_ok = 0;
		$out .= '<div class="alert alert-danger">';
		$out .= '<strong>' . __( 'Oh snap!', 'mfg' ) . '</strong> ' . __( 'Image size not set - Check plugin settings page.', 'mfg' );
		$out .= '</div>';
	}

	// Get the photos from Flickr...
	if ( $options_ok == 1 ) {
		$f = new phpFlickr( $api_key );
		$f->enableCache( 'fs', dirname( __FILE__ ) . '/cache' );

		// Find the NSID of the username
		$person = $f->people_findByUsername( $username );

		// Get the friendly URL of the user's photos
		$photos_url = $f->urls_getUserPhotos( $person['id'] );

		// Get a page of the user's public photos
		$photos = $f->people_getPublicPhotos( $person['id'], NULL, NULL, $photos_per_page, $page );

		// Loop through the photos and output the html
		$out .= '<ul id="mfg-gallery" class="thumbnails">';
		foreach ( (array) $photos['photos']['photo'] as $photo ) {
			if ( $colorbox ) {
				$link_href = mfg_build_photo_url( $photo, '_z' );
				$img_title = esc_attr( $photo[title] ) . ' by ' . esc_attr( $username ) . ' on &lt;a href=\'' . $photos_url . $photo[id] . '\'&gt;Flickr&lt;/a&gt;';
				$img_class = 'colorbox-' . $post_id;
			} else {
				$link_href = $photos_url . $photo[id];
				$img_title = esc_attr( $photo[title] );
				$img_class = '';
			}
			$out .= '	<li>';
			$out .= '		<a class="thumbnail" href="' . $link_href . '">';
			$out .= '			<figure' . mfg_get_figure_style( $image_size ) . '>';
			$out .= '				<div>';
			$out .= '					<img src="' . mfg_build_photo_url( $photo, $image_size ) . '" class="' . $img_class . '" alt="' . esc_attr( $photo[title] ) . '" title="' . $img_title . '" />';
			$out .= '				</div>';
			if ( $captions ) {
				$out .= '				<figcaption>' . esc_attr( $photo[title] ) . '</figcaption>';
			}
			$out .= '			</figure>';
			$out .= '		</a>';
			$out .= '	</li>';
		}
		$out .= '</ul>';

		// Add pagination
		$pages = $photos[photos][pages]; // returns total number of pages  
		$total = $photos[photos][total]; // returns how many photos there are in total
		$out .= '<p id="mfg-pagination">';
		if ( $page > 1 ) {
			$out .= '	<a id="mfg-prev" href="?mfg_page=' . ( $page - 1 ) . '" class="btn">' . __( '&laquo; Prev', 'mfg' ) . '</a>'; 
		} 
		if ( $page != $pages ) {
			$out .= '	<a id="mfg-next" href="?mfg_page=' . ( $page + 1 ) . '" class="btn">' . __( 'Next &raquo;', 'mfg' ) . '</a>';
		}
		$out .= '	<br/>' . sprintf( __( 'Page %1$d of %2$d', 'mfg' ), $page, $pages ); 
		$out .= '</p>';
	}

	$out .= '</div>';
	return $out;
}


/**
 * Builds the URL for a Flickr photo.
 */
function mfg_build_photo_url( $photo, $size = '' ) {
	$url = '';
	if ( $size == '_o' ) {
		$url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['originalsecret'] . "_o" . "." . $photo['originalformat'];
	} else {
		$url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . $size . ".jpg";
	}
	return $url;
}


/**
 * Generates a style attribute containing a width and height for the given photo size.
 */
function mfg_get_figure_style( $size = '' ) {
	global $mfg_photo_sizes;
	$style = '';
	$props = $mfg_photo_sizes[$size];
	if ( $props ) {
		$style = ' style="width: ' . $props['width'] . 'px; height: ' . $props['height'] . 'px"';
	}
	return $style;
}


$mfg_photo_sizes = array(
	'_s' => array(
		'width' => 75,
		'height' => 75
	),
	'_t' => array(
		'width' => 100,
		'height' => 100
	),
	'_q' => array(
		'width' => 150,
		'height' => 150
	),
	'_m' => array(
		'width' => 240,
		'height' => 240
	),
	'_n' => array(
		'width' => 320,
		'height' => 320
	),
	'' => array(
		'width' => 500,
		'height' => 500
	),
	'_z' => array(
		'width' => 640,
		'height' => 640
	),
	'_c' => array(
		'width' => 800,
		'height' => 800
	),
	'_b' => array(
		'width' => 1024,
		'height' => 1024
	),
	'_o' => array(
	)
);
