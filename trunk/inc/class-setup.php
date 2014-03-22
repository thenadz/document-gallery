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
                  'fancy'          => false,
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
          'version' => DocumentGallery::version(true)
      );
   }

   /**
    * Runs when DG is activated.
    */
   public static function activation() {
      $options = get_option(DG_OPTION_NAME, null);
      if (is_null($options)) {
         // first installation
         add_option(DG_OPTION_NAME, self::getDefaultOptions());
      } else if (DocumentGallery::version(true) !== $options['version']) {
         // update version number
         $options['version'] = DocumentGallery::version(true);
         update_option(DG_OPTION_NAME, $options);
         if ('' !== $options['css']['text']) {
            DocumentGallery::updateUserGalleryStyle($options['css']['text']);
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
