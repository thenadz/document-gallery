<?php
defined( 'WPINC' ) OR exit;

// register for all handled actions
add_action( 'wp_ajax_dg_generate_icons', array( 'DG_AjaxHandler', 'generateIcons' ) );
add_action( 'wp_ajax_nopriv_dg_generate_icons', array( 'DG_AjaxHandler', 'generateIcons' ) );

add_action( 'wp_ajax_dg_generate_gallery', array( 'DG_AjaxHandler', 'generateGallery' ) );
add_action( 'wp_ajax_nopriv_dg_generate_gallery', array( 'DG_AjaxHandler', 'generateGallery' ) );

/**
 * Handler to isolate AJAX request handling.
 *
 * @author drossiter
 */
class DG_AjaxHandler {

	/**
	 * Accepts AJAX request containing list of IDs to be generated and returned.
	 * Returns associative array mapping ID to thumbnail URL for all icons that were generated,
	 * skipping any that could not be processed.
	 */
	public static function generateIcons() {
		$ret = array();

		if ( isset( $_REQUEST['ids'] ) ) {
			foreach ( $_REQUEST['ids'] as $id ) {
				// only return URL if different from default -- default image is already displayed on the client side
				$url = DG_Thumber::getInstance()->getThumbnail( $id, 1, true, $is_default );
				if ( ! $is_default ) {
					$ret[$id] = $url;
				}
			}
		}

		wp_send_json($ret);
	}

	/**
	 * Accepts AJAX request containing an array matching any allowable params for the [dg] shortcode.
	 * Returns the resultant gallery HTML.
	 */
	public static function generateGallery() {
		if ( isset( $_REQUEST['atts'] ) ) {
			@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			echo DocumentGallery::doShortcode( $_REQUEST['atts'] );
		}

		wp_die();
	}
}