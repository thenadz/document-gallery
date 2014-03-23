<?php
defined('WPINC') OR exit;

/*
  Plugin Name: Document Gallery
  Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
  Version: 2.0.1
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2
  Text Domain: document-gallery
 */

// define helper paths & URLs
define('DG_VERSION', '2.0.1');
define('DG_URL', plugin_dir_url(__FILE__));
define('DG_PATH', plugin_dir_path(__FILE__));
if(!defined('WP_INCLUDE_DIR')) {
   define('WP_INCLUDE_DIR', preg_replace('/wp-content$/', 'wp-includes', WP_CONTENT_DIR));
}
if(!defined('WP_ADMIN_DIR')) {
   define('WP_ADMIN_DIR', preg_replace('/wp-content$/', 'wp-admin', WP_CONTENT_DIR));
}

// init DG options for use throughout plugin
global $dg_options;
define('DG_OPTION_NAME', 'document_gallery');
$dg_options = get_option(DG_OPTION_NAME, null);

// handle activation and uninstallation
include_once DG_PATH . 'inc/class-setup.php';
DG_Setup::maybeUpdate();
register_uninstall_hook(__FILE__, array('DG_Setup', 'uninstall'));

// I18n
add_action('plugins_loaded', array('DocumentGallery', 'loadTextDomain'));

// cleanup cached data when thumbed attachment deleted
include_once DG_PATH . 'inc/class-thumber.php';
add_action('delete_attachment', array('DG_Thumber', 'deleteThumbMeta'));

if (is_admin()) {
   // admin house keeping
   include_once DG_PATH . 'admin/class-admin.php';

   // add settings link
   add_filter('plugin_action_links_' . plugin_basename(__FILE__),
       array('DG_Admin', 'addSettingsLink'));

   // build options page
   add_action('admin_menu', array('DG_Admin', 'addAdminPage'));
   if (!empty($GLOBALS['pagenow'])
       && ('options-general.php' === $GLOBALS['pagenow'] // output
       || 'options.php' === $GLOBALS['pagenow'])) {      // validation
       add_action('admin_init', array('DG_Admin', 'registerAdminStyle'));
       add_action('admin_init', array('DG_Admin', 'registerSettings'));
   }
} else {
   // styling for gallery
   add_action('wp_enqueue_scripts', array('DocumentGallery', 'enqueueGalleryStyle'));
}

// adds 'dg' shortcode
add_shortcode('dg', array('DocumentGallery', 'doShortcode'));

/**
 * DocumentGallery wraps basic functionality to setup the plugin.
 *
 * @author drossiter
 */
class DocumentGallery {

   /*==========================================================================
    * THE SHORTCODE
    *=========================================================================*/

   /**
    * Takes values passed from attributes and returns sutable HTML to represent
    * all valid attachments requested.
    *
    * @param array $atts         Arguments from the user.
    * @return string             HTML for the Document Gallery.
    */
   public static function doShortcode($atts) {
      include_once 'inc/class-gallery.php';
      return new DG_Gallery($atts);
   }

   /**
    * Enqueue standard DG CSS.
    */
   public static function enqueueGalleryStyle() {
      global $dg_options;
      wp_register_style('dg-main', DG_URL . 'assets/css/style.css', null,
          DG_VERSION . ':' . $dg_options['css']['version']);
      wp_enqueue_style('dg-main');
   }

   public static function updateUserGalleryStyle($css) {
      $ret = false;

      if ($css_file = file_get_contents(DG_PATH . 'assets/css/style.css')) {
         $css_file = preg_replace('#/\* CUSTOM USER CSS \*/.*#s',
             "/* CUSTOM USER CSS */\n" . $css, $css_file);
         $ret = (bool)file_put_contents(DG_PATH . 'assets/css/style.css', $css_file, LOCK_EX);
      }

      return $ret;
   }

   /*==========================================================================
    * I18n
    *=========================================================================*/

   public static function loadTextDomain() {
      load_plugin_textdomain('document-gallery', false, DG_PATH . 'languages');
   }

   /*==========================================================================
    * HELPER FUNCTIONS
    *=========================================================================*/

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}

?>