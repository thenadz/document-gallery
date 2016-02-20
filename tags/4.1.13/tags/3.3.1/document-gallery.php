<?php
defined( 'WPINC' ) OR exit;

/*
  Plugin Name: Document Gallery
  Plugin URI: http://wordpress.org/extend/plugins/document-gallery/
  Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
  Version: 3.3.1
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2
  Text Domain: document-gallery
 */

define( 'DG_VERSION', '3.3.1' );

// define helper paths & URLs
define( 'DG_BASENAME', plugin_basename( __FILE__ ) );
define( 'DG_URL', plugin_dir_url( __FILE__ ) );
define( 'DG_PATH', plugin_dir_path( __FILE__ ) );
define( 'DG_WPINC_PATH', ABSPATH . WPINC . '/' );
define( 'DG_WPADMIN_PATH', ABSPATH . 'wp-admin/' );

// init DG options for use throughout plugin
global $dg_options;
define( 'DG_OPTION_NAME', 'document_gallery' );
$dg_options = get_option( DG_OPTION_NAME, null );

// core functionality
include_once DG_PATH . 'inc/class-document-gallery.php';

// DG general utility functions
include_once DG_PATH . 'inc/class-util.php';

// logging functionality
include_once DG_PATH . 'inc/class-logger.php';
add_action( DG_Logger::PurgeLogsAction, array( 'DG_Logger', 'purgeExpiredEntries' ) );

// handle activation, updates, and uninstallation
include_once DG_PATH . 'inc/class-setup.php';
register_activation_hook( __FILE__, array( 'DG_Setup', 'activate' ) );
add_action( 'wpmu_new_blog', array( 'DG_Setup', 'activateNewBlog' ) );
register_uninstall_hook( __FILE__, array( 'DG_Setup', 'uninstall' ) );
DG_Setup::maybeUpdate();

// validate options if desired
if ( $dg_options['validation'] ) {
	add_action( 'init', array( 'DocumentGallery', 'addValidation' ) );
}

// I18n
add_action( 'plugins_loaded', array( 'DocumentGallery', 'loadTextDomain' ) );

// cleanup cached data when thumbed attachment deleted
include_once DG_PATH . 'inc/class-thumber.php';
add_action( 'delete_attachment', array( 'DG_Thumber', 'deleteThumbMeta' ) );

if ( is_admin() ) {
	// admin house keeping
	include_once DG_PATH . 'admin/class-admin.php';

	// add links to plugin index
	add_filter( 'plugin_action_links_' . DG_BASENAME, array( 'DG_Admin', 'addSettingsLink' ) );
	add_filter( 'plugin_row_meta', array( 'DG_Admin', 'addDonateLink' ), 10, 2 );

	// build options page
	add_action( 'admin_menu', array( 'DG_Admin', 'addAdminPage' ) );

	// add meta box for managing thumbnail generation to attachment Edit Media page
	add_action( 'add_meta_boxes', array( 'DG_Admin', 'addMetaBox' ) );
	add_action( 'wp_ajax_dg_upload_thumb', array( 'DG_Admin', 'saveMetaBox' ) );

	// Media Manager integration
	add_action( 'admin_print_footer_scripts', array(
		'DG_Admin',
		'loadCustomTemplates'
	) ); //wp_print_scripts || wp_footer

	if ( DG_Admin::doRegisterSettings() ) {
		add_action( 'admin_init', array( 'DG_Admin', 'registerSettings' ) );
	}
} else {
	// styling for gallery
	if ( apply_filters( 'dg_use_default_gallery_style', true ) ) {
		add_action( 'wp_enqueue_scripts', array( 'DocumentGallery', 'enqueueGalleryStyle' ) );
	}
	add_action( 'wp_print_scripts', array( 'DocumentGallery', 'printCustomStyle' ) );
}

// adds 'dg' shortcode
add_shortcode( 'dg', array( 'DocumentGallery', 'doShortcode' ) );

// public API for developers
include_once DG_PATH . 'inc/class-api.php';
