<?php
defined( 'WPINC' ) OR exit;

// register for all handled actions
add_action( 'wp_ajax_dg_generate_icons', array( 'DG_AjaxHandler', 'generateIcons' ) );
add_action( 'wp_ajax_nopriv_dg_generate_icons', array( 'DG_AjaxHandler', 'generateIcons' ) );

add_action( 'wp_ajax_dg_generate_gallery', array( 'DG_AjaxHandler', 'generateGallery' ) );
add_action( 'wp_ajax_nopriv_dg_generate_gallery', array( 'DG_AjaxHandler', 'generateGallery' ) );

add_action( 'wp_prepare_attachment_for_js', array( 'DG_AjaxHandler', 'dg_prepare_attachment_for_js' ), 10, 3 );

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
			if ( ! headers_sent() ) {
				header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
			}
			echo DocumentGallery::doShortcode( $_REQUEST['atts'] );
		}

		wp_die();
	}
	
	/**
	 * Filters the attachment data prepared for JavaScript.
	 *
	 * @since 3.5.0
	 *
	 * @param array       $response   Array of prepared attachment data. See {@see wp_prepare_attachment_for_js()}.
	 * @param WP_Post     $attachment Attachment object.
	 * @param array|false $meta       Array of attachment meta data, or false if there is none.
	 */
	public static function dg_prepare_attachment_for_js( $response, $attachment, $meta ) {
		$thumb_obj = DG_Thumb::getThumb( $attachment->ID );
		if ( ! is_null( $thumb_obj ) && $thumb_obj->isSuccess() ) {
			// icon has already been generated so include it in generated gallery
			$response['icon'] = $thumb_obj->getUrl();
		}
		// else - no cached thumbnail. Fall back to WordPress default.
		
		return $response;
	}
}