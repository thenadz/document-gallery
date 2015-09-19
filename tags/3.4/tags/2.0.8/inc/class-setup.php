<?php
defined('WPINC') OR exit;

/**
 * Holds functions that handle DG setup / uninstallation.
 *
 * @author drossiter
 */
class DG_Setup {

   /**
    * @return array Contains default options for DG.
    */
   public static function getDefaultOptions() {
      include_once DG_PATH . 'inc/class-thumber.php';
      $date = gmdate('D, d M Y H:i:s');
      $etag = md5($date);
      return array(
          'thumber' => array(
              'thumbs' => array(),
              'gs'     => DG_Thumber::getGhostscriptExecutable(),
              'active' => DG_Thumber::getDefaultThumbers(),
              'width'  => 200,
              'height' => 200
          ),
          'gallery' => array(
              'defaults' => array(
                  // default: link directly to file (true to link to attachment pg)
                  'attachment_pg'  => false,
                  'descriptions'   => false,
                  // include thumbnail of actual document in gallery display
                  'fancy'          => true,
                  // comma-separated list of attachment ids
                  'ids'            => false,
                  // if true, all images attached to current page will be included also
                  'images'         => false,
                  'localpost'      => true,
                  'order'          => 'ASC',
                  'orderby'        => 'menu_order',
                  // only relevant if tax_query used (WP >= 3.1)
                  'relation'       => 'AND'
              )
          ),
          'css' => array(
              'text' => '',
              'last-modified' => $date,
              'etag' => $etag,
              'version' => 0
          ),
          'version' => DG_VERSION
      );
   }

   /**
    * Runs every page load, updates when needed.
    */
   public static function maybeUpdate() {
      global $dg_options;

      // first installation
      if (empty($dg_options)) {
         $dg_options = self::getDefaultOptions();
         add_option(DG_OPTION_NAME, $dg_options);
      }

      // do update
      elseif (DG_VERSION !== $dg_options['version']) {
         $dg_options['css']['minified'] =
                 DocumentGallery::compileCustomCss($dg_options['css']['text']);
         
         // update plugin version
         $dg_options['version'] = DG_VERSION;
         
         // used in dynamic CSS HTTP headers
         $dg_options['css']['last-modified'] = gmdate('D, d M Y H:i:s');
         $dg_options['css']['etag'] = md5($dg_options['css']['last-modified']);

         // remove previously-failed thumbs
         $thumbs = $dg_options['thumber']['thumbs'];
         foreach ($thumbs as $k => $v) {
            if (false === $v) {
               unset($dg_options['thumber']['thumbs'][$k]);
            }
         }

         // commit DB changes
         update_option(DG_OPTION_NAME, $dg_options);
      }
   }

   /**
    * Runs when DG is uninstalled.
    */
   public static function uninstall() {
      if (!current_user_can('activate_plugins')) return;
      check_admin_referer('bulk-plugins');

      $options = DG_Thumber::getOptions();

      foreach ($options['thumbs'] as $val) {
         if (false !== $val) {
            @unlink($val['thumb_path']);
         }
      }

      delete_option(DG_OPTION_NAME);
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}

?>
