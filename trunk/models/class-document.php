<?php

/**
 * Description of Document
 *
 * @author drossiter
 */
class DG_Document {
   // templates for HTML output
   private static $doc_icon = false;
   private static $img_string = '<img src="%s" title="%s" alt="%s" />';

   // general document data
   private $description, $gallery, $ID, $link, $title, $title_attribute;

   /**
    * Constructs instance of Document.
    * @param type $attachment Attachment object used to initalize fields.
    * @param type $gallery    Instance of Gallery class.
    */
   public function __construct($attachment, $gallery) {
      include_once(DG_PATH . 'util/class-thumber.php');

      // init template for HTML output
      if(self::$doc_icon === false)
      {
         self::$doc_icon =
         '   <div class="document-icon">' . PHP_EOL .
         '      <a href="%s">%s<br>%s</a>' . PHP_EOL .
         '   </div>' . PHP_EOL;
      }

      // init general document data
      $this->gallery = $gallery;
      $this->description = $attachment->post_content;
      $this->ID = $attachment->ID;
      $this->link = $gallery->linkToAttachmentPg()
         ? get_attachment_link($attachment->ID)
         : wp_get_attachment_url($attachment->ID);
      $this->title = get_the_title($attachment->ID);
      $this->title_attribute = esc_attr(strip_tags($this->title));
   }

   /**
    * Returns associative array of filetype to icon mapping.
    * @return array
    */
   public static function getFiletypeMapping() {
      return self::$exts;
   }

   /**
    * Takes associative array of filetype to icon mapping.
    *
    * NOTE: This is foundation work for allowing users to add their own
    * filetypes in the future.
    * @param array $new
    */
   public static function setFiletypeMapping($new) {
      self::$exts = $new;
   }

   /**
    * Returns HTML representing this Document.
    * @return string
    */
   public function __toString() {
      $icon = sprintf(self::$img_string, DG_Thumber::getThumbnail($this->ID),
          $this->title_attribute, $this->title_attribute);
      $core = sprintf(self::$doc_icon, $this->link, $icon, $this->title);

      if($this->gallery->useDescriptions()) {
         $core .= "   <p>$this->description</p>" . PHP_EOL;
      }

      // users may filter icon here
      return apply_filters('dg_doc_icon', $core, $this->ID);
   }
}

?>
