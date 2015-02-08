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
      $this->description = wptexturize($attachment->post_content);
      $this->ID = $attachment->ID;
      $this->link = $gallery->linkToAttachmentPg()
         ? get_attachment_link($attachment->ID)
         : wp_get_attachment_url($attachment->ID);
      $this->title = wptexturize($attachment->post_title);
      $this->title_attribute = esc_attr(strip_tags($this->title));
   }

   /*==========================================================================
    * OUTPUT HTML STRING
    *=========================================================================*/

   /**
    * Returns HTML representing this Document.
    * @filter dg_icon_template Filters the DG icon HTML. Passes a single
    *    bool value indicating whether the gallery is using descriptions or not.
    * @return string
    */
   public function __toString() {
      $thumb = $this->gallery->useFancyThumbs()
          ? DG_Thumber::getThumbnail($this->ID)
          : DG_Thumber::getDefaultThumbnail($this->ID);

      $repl = array($this->link, $thumb, $this->title_attribute, $this->title);
      $find = array('%link%', '%img%', '%title_attribute%', '%title%');
      $description = '';
      
      // if descriptions then add filterable tag and value to replaced tag
      if ($this->gallery->useDescriptions()) {
         $repl[] = $this->description;
         $find[] = '%description%';
         $description = '   <p>%description%</p>';
      }
      
      $doc_icon =
         '   <div class="document-icon">' . PHP_EOL .
         '      <a href="%link%"><img src="%img%" title="%title_attribute%" alt="%title_attribute%" /><br>%title%</a>' . PHP_EOL .
         '   </div>' . PHP_EOL .
         $description;
      
      // allow developers to filter icon output
      $doc_icon = apply_filters(
         'dg_icon_template',
         $doc_icon,
         $this->gallery->useDescriptions(),
         $this->ID);

      return str_replace($find, $repl, $doc_icon);
   }
}

?>