<?php
// Borrowed from Google Doc Embedder
// http://wordpress.org/plugins/google-document-embedder/

// define custom path to wp-load.php (usually not necessary)
$path = '';

// bootstrap for getting ABSPATH constant to wp-load.php outside the admin screen
if ( ! defined('WP_LOAD_PATH') ) {
	$classic_root = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/';
	if ( file_exists( $classic_root . 'wp-load.php' ) ) {
		define('WP_LOAD_PATH', $classic_root);
	} else {
		if ( file_exists( $path . 'wp-load.php' ) ) {
			define('WP_LOAD_PATH', $path);
		} else {
			exit( 'Could not find wp-load.php' );
		}
	}
}

// load wp-load.php
require_once( WP_LOAD_PATH . 'wp-load.php' );

define( 'DG_PERM_ERROR', '<p>'.__('You do not have sufficient permissions to access this page.').'</p>' );

?>