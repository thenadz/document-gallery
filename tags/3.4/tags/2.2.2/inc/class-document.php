<?php
defined('WPINC') OR exit;

/**
 * Holds data specific to a given document.
 *
 * @author drossiter
 */
class DG_Document {

   /*==========================================================================
    * PRIVATE FIELDS
    *=========================================================================*/

   // general document data
   private $description, $gallery, $ID, $link, $title, $title_attribute;

   /*==========================================================================
    * INIT GALLERY
    *=========================================================================*/

   /**
    * Constructs instance of Document.
    * @param type $attachment Attachment object used to initalize fields.
    * @param type $gallery    Instance of Gallery class.
    */
   public function __construct($attachment, $gallery) {
      include_once DG_PATH . 'inc/class-thumber.php';
      
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

   /*==========================================================================
    * OUTPUT HTML STRING
    *=========================================================================*/

   /**
    * Returns HTML representing this Document.
    * @filter dg_icon_template Filters the DG icon HTML. Passes a single
    *    bool value indicating whether the gallery is using descriptions or not.
    * @filter dg_doc_icon Deprecated. To be removed in a future relesase.
    * @return string
    */
   public function __toString() {
      static $find = null;
      if (is_null($find)) {
         $find = array("%link%", "%img%", "%title_attribute%", "%title%");
      }
      
      $thumb = $this->gallery->useFancyThumbs()
          ? DG_Thumber::getThumbnail($this->ID)
          : DG_Thumber::getDefaultThumbnail($this->ID);

      $repl = array($this->link, $thumb, $this->title_attribute, $this->title);
      
      $doc_icon = apply_filters(
         'dg_icon_template',
         '   <div class="document-icon">' . PHP_EOL .
         '      <a href="%link%"><img src="%img%" title="%title_attribute%" alt="%title_attribute%" /><br>%title%</a>' . PHP_EOL .
         '   </div>' . PHP_EOL,
         $this->gallery->useDescriptions(),
         $this->ID);
      
      $core = str_replace($find, $repl, $doc_icon);
      
      if($this->gallery->useDescriptions()) {
         $core .= "   <p>$this->description</p>" . PHP_EOL;
      }

      // users may filter icon here
      return apply_filters('dg_doc_icon', $core, $this->ID, $this->gallery->useDescriptions());
   }
}

?>