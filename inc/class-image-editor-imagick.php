<?php
defined('WPINC') OR exit;

include_once WP_INCLUDE_DIR . '/class-wp-image-editor.php';
include_once WP_INCLUDE_DIR . '/class-wp-image-editor-imagick.php';

/**
 * Extends WP_Image_Editor_Imagick to support specifying which
 * page to open for an Imagick file.
 *
 * @author drossiter
 */
class DG_Image_Editor_Imagick extends WP_Image_Editor_Imagick {
   /**
    * @var int The 0-indexed pg number
    */
   private $pg;

   /**
    * @param str $file
    * @param int $pg The 0-indexed pg number to edit.
    */
   public function __construct($file, $pg = null) {
      parent::__construct($file);
      $this->pg = $pg;
   }

   /**
    * Loads the filepath into Imagick object.
    */
   public function load() {
      $ret = parent::load();

      // set correct page number
      if (!is_wp_error($ret) && !is_null($this->pg)) {
         if (is_callable(array($this->image, 'setIteratorIndex'))) {
            $this->image->setIteratorIndex($this->pg);
         }
      }

      return $ret;
   }
}
?>
