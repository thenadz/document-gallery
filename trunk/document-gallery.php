<?php

/*
  Plugin Name: Document Gallery
  Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
  Version: 1.4.3
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2
 */

define('DG_URL', plugin_dir_url(__FILE__));
define('DG_PATH', trailingslashit(dirname(__FILE__)));
if(!defined('WP_INCLUDE_DIR')) {
   define('WP_INCLUDE_DIR', preg_replace('/wp-content$/', 'wp-includes', WP_CONTENT_DIR));
}
if(!defined('WP_ADMIN_DIR')) {
   define('WP_ADMIN_DIR', preg_replace('/wp-content$/', 'wp-admin', WP_CONTENT_DIR));
}

/**
 * Takes values passed from attributes and returns sutable HTML to represent
 * all valid attachments requested.
 *
 * @param array $atts         Arguments from the user.
 * @return string             HTML for the Document Gallery.
 */
function dg_get_attachment_icons($atts) {
   include_once DG_PATH . 'models/class-gallery.php';

   // empty string is passed when no arguments are given, but constructor expects an array
   return (string)(new DG_Gallery(empty($atts) ? array() : $atts));
}

/**
 * 'document gallery' shortcode depreciated as of v1.0. left for backward compatibility
 */
add_shortcode('document gallery', 'dg_get_attachment_icons');
add_shortcode('dg', 'dg_get_attachment_icons');

/**
 * Include stylesheet for some basic design.
 */
function dg_add_header_css() {
   wp_enqueue_style('document-gallery-css', DG_URL . 'style.css');
}
add_action('wp_print_styles', 'dg_add_header_css');

/**
 * Delete thumbnail when attachment is deleted
 * @param int $ID ID of the attachment being deleted.
 */
function dg_delete_thumb($ID) {
   include_once DG_PATH . 'util/class-thumber.php';
   DG_Thumber::deleteThumbMeta($ID);
}
add_action('delete_attachment', 'dg_delete_thumb');

?>