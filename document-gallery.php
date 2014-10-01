<?php
defined('WPINC') OR exit;

/*
  Plugin Name: Document Gallery
  Plugin URI: http://wordpress.org/extend/plugins/document-gallery/
  Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
  Version: 2.2.1
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2
  Text Domain: document-gallery
 */

define('DG_VERSION', '2.2.1');

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

// logging functionality
include_once DG_PATH . 'inc/class-logger.php';

// handle activation, updates, and uninstallation
include_once DG_PATH . 'inc/class-setup.php';
register_activation_hook(__FILE__, array('DG_Setup', 'activate'));
add_action('wpmu_new_blog', array('DG_Setup','activateNewBlog'));
register_uninstall_hook(__FILE__, array('DG_Setup', 'uninstall'));
DG_Setup::maybeUpdate();

// validate options if desired
if ($dg_options['validation']) {
   add_action('init', array('DocumentGallery', 'addValidation'));
}

// I18n
add_action('plugins_loaded', array('DocumentGallery', 'loadTextDomain'));

// cleanup cached data when thumbed attachment deleted
include_once DG_PATH . 'inc/class-thumber.php';
add_action('delete_attachment', array('DG_Thumber', 'deleteThumbMeta'));

if (is_admin()) {
   // admin house keeping
   include_once DG_PATH . 'admin/class-admin.php';

   // add settings link
   add_filter('plugin_action_links_' . DG_BASENAME, array('DG_Admin', 'addSettingsLink'));
   
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
    * @param multitype:string $atts Arguments from the user.
    * @return string HTML for the Document Gallery.
    */
   public static function doShortcode($atts) {
      include_once 'inc/class-gallery.php';

      $start = microtime(true);
      $gallery = (string)new DG_Gallery($atts);
      DG_Logger::writeLog(DG_LogLevel::Detail, 'Generation Time: ' . sprintf('%.2f', (microtime(true) - $start)) . ' s');

      return $gallery;
   }

   /**
    * Enqueue standard DG CSS.
    */
   public static function enqueueGalleryStyle() {
      wp_enqueue_style('document-gallery', DG_URL . 'assets/css/style.css', null, DG_VERSION);
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
    * @param multitype:string $vars Variables to be added.
    * @return multitype:string All variables to be included in query string.
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
    * I18n
    *=========================================================================*/

   /**
    * Loads language files into WP core.
    */
   public static function loadTextDomain() {
      load_plugin_textdomain('document-gallery', false, dirname(DG_BASENAME) . '/languages/');
   }

   /*==========================================================================
    * HELPER FUNCTIONS
    *=========================================================================*/
   
   /**
    * @param int $blog ID of the blog to be retrieved in multisite env.
    * @return multitype:unknown Options for the blog.
    */
   public static function getOptions($blog = null) {
      global $dg_options;
      return is_null($blog)
              ? $dg_options
              : get_blog_option($blog, DG_OPTION_NAME, null);
   }
   
   /**
    * @param multitype:unknown $options
    * @param int $blog ID of the blog to be set in multisite env.
    */
   public static function setOptions($options, $blog = null) {
      if (is_null($blog)) {
         global $dg_options;
         update_option(DG_OPTION_NAME, $options);
         $dg_options = $options;
      } else {
         update_blog_option($blog, DG_OPTION_NAME, $options);
      }
   }
   
   /**
    * @param int $blog ID of the blog to be deleted in multisite env.
    */
   public static function deleteOptions($blog = null) {
      if (is_null($blog)) {
         delete_option(DG_OPTION_NAME);
      } else {
         delete_blog_option($blog, DG_OPTION_NAME);
      }
   }
   
   /**
    * Adds hook to validate DG options every time save is attempted.
    */
   public static function addValidation() {
      add_filter('pre_update_option_' . DG_OPTION_NAME, array('DocumentGallery', 'validateOptionsStructure'), 10, 2);
   }
   
   /**
    * Checks whether the given options match the option schema.
    * @param multivar $new The new options to be validated.
    * @param multivar $old The old options.
    * @return array The options to be saved.
    */
   public static function validateOptionsStructure($new, $old) {
      if (self::isValidOptionsStructure($new)) {
         $ret = $new;
      } else {
         $ret = $old;
         DG_Logger::writeLog(DG_LogLevel::Error, 'Attempted to save invalid options.' . PHP_EOL . print_r($new, true), true, true);
      }
      
      return $ret;
   }
   
   /**
    * @param multivar|unknown $o The options structure to validate.
    * @param multivar $schema The schema to validate against (note that only keys matter -- non-array values are ignored).
    * @return bool Whether the given options structure matches the schema.
    */
   private static function isValidOptionsStructure($o, $schema = null) {
      if (is_null($schema)) {
         include_once DG_PATH . 'inc/class-setup.php';
         $schema = DG_Setup::getDefaultOptions(true);
      }
      
      // simple checks first
      $valid = is_array($o) && (count($schema) === count($o));
      
      if ($valid) {
         foreach ($schema as $sk => $sv) {
            $valid = array_key_exists($sk, $o);
            if (is_array($sv) && !empty($sv)) {
               $valid = $valid && self::isValidOptionsStructure($o[$sk], $sv);
            }
            
            if (!$valid) {
               break;
            }
         }
      }
      
      return $valid;
   }
   
   /**
    * Function takes a GMT timestamp and returns a date/time string in the
    * current timezone and WP format.
    * @param int $timestamp The GMT timestamp to translate.
    * @return string The local time in the WP date/time format.
    */
   public static function localDateTimeFromTimestamp($timestamp) {
      static $gmt_offet = null;
      static $wp_format = null;
      if (is_null($gmt_offet)) {
         $gmt_offet = get_option('gmt_offset');
         $wp_format = get_option('date_format').' '.get_option('time_format');
      }
      
      return date($wp_format, $timestamp + $gmt_offet * 3600);
   }
   
   /**
    * Compiles any custom CSS plus the default CSS together,
    * minifying in the process.
    * @param string $custom The custom CSS to compile.
    * @return string Compiled CSS, including both standard and any custom.
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