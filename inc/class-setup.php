<?php
defined('WPINC') OR exit;

/**
 * Holds functions that handle DG setup / uninstallation.
 *
 * @author drossiter
 */
class DG_Setup {

   /**
    * Note that changes to the structure of the default options require also
    * changing the schema in options-schema.php
    * @return array Contains default options for DG.
    */
   public static function getDefaultOptions() {
      include_once DG_PATH . 'inc/class-thumber.php';
      $date = gmdate('D, d M Y H:i:s');
      $etag = md5($date);
      return array(
          'thumber' => array(
              'thumbs'  => array(),
              'gs'      => DG_Thumber::getGhostscriptExecutable(),
              'active'  => DG_Thumber::getDefaultThumbers(),
              'width'   => 200,
              'height'  => 200,
              'timeout' => 30
          ),
          'gallery' => array(
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
              'relation'       => 'AND'
          ),
          'css' => array(
              'text' => '',
              'last-modified' => $date,
              'etag' => $etag,
              'version' => 0
          ),
          'version' => DG_VERSION,
          'validation' => false,
          'logging' => false
      );
   }

   /**
    * Runs every page load, updates as needed.
    */
   public static function maybeUpdate() {
      global $dg_options;

      // do update
      if (null != $dg_options && DG_VERSION !== $dg_options['version']) {
         $blogs = array(null);
         
         if (is_multisite()) {
            global $wpdb;
            $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
         }
         
         foreach ($blogs as $blog) {
            self::_update($blog);
         }
      }
   }
   
   /**
    * Runs when update is needed, updating the given blog. If blog is null,
    * active blog is updated.
    * @param int $blog Blog to update or null if updating current blog.
    */
   private static function _update($blog) {
      $options = DocumentGallery::getOptions($blog);
      if (is_null($options)) return;

      // version-specific updates
      self::twoPointTwo($options);
      
      // update plugin version
      $options['version'] = DG_VERSION;

      // setup CSS
      if (isset($options['css']['text'])) {
         // Only populate minified if it will be used
         $options['css']['minified'] =
                 DocumentGallery::compileCustomCss($options['css']['text']);
      }
      $options['css']['last-modified'] = gmdate('D, d M Y H:i:s');
      $options['css']['etag'] = md5($options['css']['last-modified']);

      // remove previously-failed thumbs
      $thumbs = $options['thumber']['thumbs'];
      foreach ($thumbs as $k => $v) {
         if (empty($v['thumber'])) {
            unset($options['thumber']['thumbs'][$k]);
         }
      }

      DocumentGallery::setOptions($options, $blog);
   }
   
   /**
    * The 'created_timestamp' key in each thumb record is being moved
    * to 'timestamp' as part of a move to store timestamp for failed
    * thumbnails in addition to successful ones.
    * 
    * The defaults sub-branch in the gallery branch is being flattened into its parent.
    * 
    * @param array $options The options to be modified.
    */
   private static function twoPointTwo(&$options) {
      if (version_compare($options['version'], '2.2', '<')) {
         $thumbs = array();
         
         // "created_timestamp" moving to just "timestamp"
         foreach ($options['thumber']['thumbs'] as $id => $thumb) {
            if (false === $thumb) continue;
            
            $thumbs[$id] = array(
                'timestamp'   => $thumb['created_timestamp'],
                'thumb_url'   => $thumb['thumb_url'],
                'thumb_path'  => $thumb['thumb_path'],
                'thumber'     => $thumb['thumber']
            );
         }
         
         $options['thumber']['thumbs'] = $thumbs;
         
         // adding default thumbnail generation timeout
         $options['thumber']['timeout'] = 30;
         
         // flatten out "defaults" level
         $options['gallery'] = $options['gallery']['defaults'];
         
         // adding "validation" branch
         $options['validation'] = false;
         
         // adding "logging" branch
         $options['logging'] = false;
      }
   }
   
   /**
    * Sets up Document Gallery on all blog(s) activated.
    * @param bool $networkwide Whether this is a network-wide update (multisite only).
    */
   public static function activate($networkwide) {
      $blogs = array(null);
      
      if (is_multisite()) {
         // check if it is a network activation
         if ($networkwide) {
            global $wpdb;
            $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
         }
      }
      
      foreach ($blogs as $blog) {
         self::_activate($blog);
      }
   }
   
   /**
    * Hooked into wpmu_new_blog to handle activating a new blog when plugin
    * is already network activated.
    * See discussion: https://core.trac.wordpress.org/ticket/14170
    * @param int $blog Blog ID.
    */
   public static function activateNewBlog($blog) {
      if (is_plugin_active_for_network(DG_BASENAME)) {
         self::_activate($blog);
      }
   }
   
   /**
    * Runs activation setup for Document Gallery on all blog(s) it is activated on.
    * @param int $blog Blog to update or null if updating current blog.
    */
   private static function _activate($blog) {
      $options = DocumentGallery::getOptions($blog);
      
      // first activation
      if (is_null($options)) {
         DocumentGallery::setOptions(self::getDefaultOptions(), $blog);
      }
   }

   /**
    * Runs when DG is uninstalled.
    */
   public static function uninstall() {
      if (!current_user_can('activate_plugins')) return;
      check_admin_referer('bulk-plugins');
      
      $blogs = array(null);
      
      if (is_multisite()) {
         global $wpdb;
         $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
      }
      
      foreach ($blogs as $blog) {
         self::_uninstall($blog);
      }
   }
   /**
    * Runs when DG is uninstalled for an individual blog.
    */
   private static function _uninstall($blog) {
      $options = DG_Thumber::getOptions($blog);
      if (is_null($options)) return;

      foreach ($options['thumbs'] as $val) {
         if (isset($val['thumber'])) {
            @unlink($val['thumb_path']);
         }
      }

      DocumentGallery::deleteOptions($blog);
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}

?>
