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
          'css' => array('version' => 0, 'text' => ''),
          'version' => DG_VERSION
      );
   }

   /**
    * Runs every page load, updates when needed.
    */
   public static function maybeUpdate() {
      global $dg_options;

      // first installation
      if (is_null($dg_options)) {
         $options = self::getDefaultOptions();
         add_option(DG_OPTION_NAME, $options);
      }

      // do update
      elseif (DG_VERSION !== $dg_options['version']) {
         $dg_options['version'] = DG_VERSION;
         update_option(DG_OPTION_NAME, $dg_options);

         if ('' !== $dg_options['css']['text']) {
            DocumentGallery::updateUserGalleryStyle($dg_options['css']['text']);
         }
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
