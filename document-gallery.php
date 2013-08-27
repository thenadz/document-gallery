<?php

/*
  Plugin Name: Document Gallery
  Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
  Version: 1.4
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPL2
 */

define('DG_URL', plugin_dir_url(__FILE__));
define('DG_PATH', dirname(__FILE__).'/');

/**
 * Takes values passed from attributes and returns sutable HTML to represent
 * all valid attachments requested.
 *
 * @param array $atts         Arguments from the user.
 * @return string             HTML for the Document Gallery.
 */
function dg_get_attachment_icons($atts) {
   include_once(DG_PATH . 'models/class-gallery.php');
   return (string)(new Gallery($atts));
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