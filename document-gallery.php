<?php
defined('WPINC') OR exit;

/*
  Plugin Name: Document Gallery
  Plugin URI: http://wordpress.org/extend/plugins/document-gallery/
  Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
  Version: 2.1
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2
  Text Domain: document-gallery
 */

define('DG_VERSION', '2.1');

// define helper paths & URLs
define('DG_BASENAME', plugin_basename(__FILE__));
define('DG_URL', plugin_dir_url(__FILE__));
define('DG_PATH', plugin_dir_path(__FILE__));
define('DG_WPINC_PATH', ABSPATH . WPINC . '/');
define('DG_WPADMIN_PATH', ABSPATH . 'wp-admin/');

// init DG options for use throughout plugin
global $dg_options;
define('DG_OPTION_NAME', 'document_gallery');
$dg_options = get_option(DG_OPTION_NAME, null);

// handle activation, updates, and uninstallation
include_once DG_PATH . 'inc/class-setup.php';
register_activation_hook(__FILE__, array('DG_Setup', 'activate'));
add_action('wpmu_new_blog', array('DG_Setup','activateNewBlog'));
register_uninstall_hook(__FILE__, array('DG_Setup', 'uninstall'));
DG_Setup::maybeUpdate();

// I18n
add_action('plugins_loaded', array('DocumentGallery', 'loadTextDomain'));

// cleanup cached data when thumbed attachment deleted
include_once DG_PATH . 'inc/class-thumber.php';
add_action('delete_attachment', array('DG_Thumber', 'deleteThumbMeta'));

if (is_admin()) {
   // admin house keeping
   include_once DG_PATH . 'admin/class-admin.php';

   // add settings link
   add_filter('plugin_action_links_' . DG_BASENAME,
       array('DG_Admin', 'addSettingsLink'));
   
   // build options page
   add_action('admin_menu', array('DG_Admin', 'addAdminPage'));
   if (DG_Admin::doRegisterSettings()) {
      add_action('admin_init', array('DG_Admin', 'registerSettings'));
   }
} else {
   // styling for gallery
   if (empty($dg_options['css']['text'])) {
      add_action('wp_enqueue_scripts', array('DocumentGallery', 'enqueueGalleryStyle'));
   } else {
      add_action('template_redirect', array('DocumentGallery', 'buildCustomCss'));
      add_action('wp_enqueue_scripts', array('DocumentGallery', 'enqueueCustomStyle'));
      add_filter('query_vars', array('DocumentGallery', 'addCustomStyleQueryVar'));
   }
}

// adds 'dg' shortcode
add_shortcode('dg', array('DocumentGallery', 'doShortcode'));

/**
 * DocumentGallery wraps basic functionality to setup the plugin.
 *
 * @author drossiter
 */
class DocumentGallery {

   /**
    * @var str Name of the query var used to check whether we should print custom CSS.
    */
   private static $query_var = 'document-gallery-css';

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

      $start = microtime(true);
      $gallery = (string)new DG_Gallery($atts);
      DocumentGallery::writeLog('Generation Time: ' . (microtime(true) - $start) . ' s');

      return $gallery;
   }

   /**
    * Enqueue standard DG CSS.
    */
   public static function enqueueGalleryStyle() {
      wp_register_style('document-gallery', DG_URL . 'assets/css/style.css', null, DG_VERSION);
      wp_enqueue_style('document-gallery');
   }

   /**
    * Enqueue user's custom DG CSS.
    */
   public static function enqueueCustomStyle() {
      global $dg_options;
      wp_register_style('document-gallery', add_query_arg(self::$query_var, 1, home_url('/')),
              null, DG_VERSION . ':' . $dg_options['css']['version']);
      wp_enqueue_style('document-gallery');
   }

   /**
    * Add query custom CSS query string.
    * Taken from here: http://ottopress.com/2010/dont-include-wp-load-please/
    * @param array $vars
    * @return array
    */
   public static function addCustomStyleQueryVar($vars) {
      $vars[] = self::$query_var;
      return $vars;
   }

   /**
    * Constructs user's custom CSS dynamically, then instructs
    * browser to cache for a year. Cache is busted by versioning
    * CSS any time the user makes a change.
    */
   public static function buildCustomCss() {
      if (1 == intval(get_query_var(self::$query_var))) {
            global $dg_options;
            
            header('Content-type: text/css');
            header('Cache-Control: no-transform,public,maxage=' . 31536000);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            header('Last-Modified: ' . $dg_options['css']['last-modified']);
            header('ETag: ' . $dg_options['css']['etag']);
            
            echo $dg_options['css']['minified'];
            exit;
      }
   }

   /*==========================================================================
    * Logging
    *=========================================================================*/

   /**
    * Appends error log with $entry if WordPress is in debug mode.
    *
    * @param str $entry
    */
   public static function writeLog($entry) {
      if (self::logEnabled()) {
         // NOTE: First entry in stack trace is this method -- need to get second
         $callers = debug_backtrace();
         $caller = $callers[1];
         $caller = (isset($caller['class']) ? $caller['class'] : '') . $caller['type'] . $caller['function'];
         
         // build log entry, removing any extra spaces
         $err = preg_replace('/\s+/', ' ', trim(print_r($entry, true)));
         $err = 'DG (' . $caller . '): ' . $err . PHP_EOL;
         
         // insert log entry
         if (defined('ERRORLOGFILE')) {
            error_log($err, 3, ERRORLOGFILE);
         } else {
            error_log($err);
         }
      }
   }
   
   /**
    * @return bool Whether debug logging is currently enabled.
    */
   public static function logEnabled() {
      return defined('WP_DEBUG') && WP_DEBUG;
   }

   /*==========================================================================
    * I18n
    *=========================================================================*/

   public static function loadTextDomain() {
      load_plugin_textdomain('document-gallery', false, dirname(DG_BASENAME) . '/languages/');
   }

   /*==========================================================================
    * HELPER FUNCTIONS
    *=========================================================================*/
   
   /**
    * @param int $blog ID of the blog to be retrieved in multisite env.
    * @return array Options for the blog.
    */
   public static function getOptions($blog = null) {
      global $dg_options;
      return is_null($blog)
              ? $dg_options
              : get_blog_option($blog, DG_OPTION_NAME, null);
   }
   
   public static function setOptions($options, $blog = null) {
      if (is_null($blog)) {
         global $dg_options;
         update_option(DG_OPTION_NAME, $options);
         $dg_options = $options;
      } else {
         update_blog_option($blog, DG_OPTION_NAME, $options);
      }
   }
   
   public static function deleteOptions($blog = null) {
      if (is_null($blog)) {
         delete_option(DG_OPTION_NAME);
      } else {
         delete_blog_option($blog, DG_OPTION_NAME);
      }
   }
   
   /**
    * Compiles any custom CSS plus the default CSS together,
    * minifying in the process.
    * @param str $custom The custom CSS to compile.
    * @return str Compiled CSS, including both standard and any custom.
    */
   public static function compileCustomCss($custom) {
      $css = file_get_contents(DG_PATH . 'assets/css/style.css');
      $css .= str_replace('&gt;', '>', esc_html($custom));
      
      return $css;
   }
   
   /**
    * Removes all comments & space from CSS string.
    * Source: http://stackoverflow.com/a/15195752/866618
    */
   private static function minifyCss($css) {
      # remove comments first (simplifies the other regex)
      $re1 = <<<EOS
(?sx)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # comments
  /\* (?> .*? \*/ )
EOS;

      $re2 = <<<EOS
(?six)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # ; before } (and the spaces after it while we're here)
  \s*+ ; \s*+ ( } ) \s*+
|
  # all spaces around meta chars/operators
  \s*+ ( [*$~^|]?+= | [{};,>~+-] | !important\b ) \s*+
|
  # spaces right of ( [ :
  ( [[(:] ) \s++
|
  # spaces left of ) ]
  \s++ ( [])] )
|
  # spaces left (and right) of :
  \s++ ( : ) \s*+
  # but not in selectors: not followed by a {
  (?!
    (?>
      [^{}"']++
    | "(?:[^"\\]++|\\.)*+"
    | '(?:[^'\\]++|\\.)*+' 
    )*+
    {
  )
|
  # spaces at beginning/end of string
  ^ \s++ | \s++ \z
|
  # double spaces to single
  (\s)\s+
EOS;

      $css = preg_replace("%$re1%", '$1', $css);
      return preg_replace("%$re2%", '$1$2$3$4$5$6$7', $css);
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}

?>