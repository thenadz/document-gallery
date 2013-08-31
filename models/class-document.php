<?php

/**
 * Description of Document
 *
 * @author drossiter
 */
class Document {
   // templates for HTML output
   private $doc_icon, $icon_url;
   private $img_string = '<img src="%s" title="%s" alt="%s" />';

   // general document data
   private $description, $gallery, $ID, $link, $title, $title_attribute;

   // filetype => image mapping
   private static $exts = array(
      // Most Common First
      'pdf' => 'pdf.png',

      // MS Office
      'doc'  => 'msdoc.png',
      'docx' => 'msdoc.png',
      'docm' => 'msdoc.png',
      'dotx' => 'msdoc.png',
      'dotm' => 'msdoc.png',
      'ppt'  => 'msppt.png',
      'pot'  => 'msppt.png',
      'pps'  => 'msppt.png',
      'pptx' => 'msppt.png',
      'pptm' => 'msppt.png',
      'ppsx' => 'msppt.png',
      'ppsm' => 'msppt.png',
      'potx' => 'msppt.png',
      'potm' => 'msppt.png',
      'ppam' => 'msppt.png',
      'sldx' => 'msppt.png',
      'sldm' => 'msppt.png',
      'xla'  => 'msxls.png',
      'xls'  => 'msxls.png',
      'xlt'  => 'msxls.png',
      'xlw'  => 'msxls.png',
      'xlsx' => 'msxls.png',
      'xlsm' => 'msxls.png',
      'xlsb' => 'msxls.png',
      'xltx' => 'msxls.png',
      'xltm' => 'msxls.png',
      'xlam' => 'msxls.png',
      'mdb'  => 'msaccess.png',

      // Video formats
      'avi'  => 'avi.png',
      'divx' => 'divx.png',
      'flv'  => 'flv.png',
      'qt'   => 'mov.png',
      'mov'  => 'mov.png',
      'asf'  => 'wmv.png',
      'asx'  => 'wmv.png',
      'wax'  => 'wmv.png',
      'wmv'  => 'wmv.png',
      'wmx'  => 'wmv.png',
      'mkv'  => 'mkv.png',

      // Audio formats
      'mp3'  => 'mp3.png',
      'wav'  => 'wav.png',
      'ogg'  => 'ogg.png',
      'oga'  => 'ogg.png',
      'midi' => 'midi.png',
      'mid'  => 'midi.png',
      'wma'  => 'wma.png',

      // Text formats
      'rtx'  => 'rtx.png',
      'ics'  => 'ics.png',
      'csv'  => 'csv.png',

      // Msc application formats
      'html' => 'html.png',
      'htm'  => 'html.png', // death to all who use this!
      'css'  => 'css.png',
      'js'   => 'javascript.png',
      'class'=> 'java.png',
      'zip'  =>  'zip.png',
      'tar'  => 'compressed.png',
      'gzip' => 'compressed.png',
      'gz'   => 'compressed.png',
      'bz2'  => 'compressed.png', // not yet WP-supported
      'tgz'  => 'compressed.png', // not yet WP-supported
      'rar'  => 'rar.png', // RAWR!!!
      '7z'   => '7zip.png',
      'exec' => 'exec.png',
      'rtf'  => 'rtf.png',
      'swf'  => 'shockwave.png',

      // OpenDocument formats
      'odt'  => 'opendocument-text.png',
      'odp'  => 'opendocument-presentation.png',
      'ods'  => 'opendocument-spreadsheet.png',
      'odg'  => 'opendocument-graphics.png',
      'odb'  => 'opendocument-database.png',
      'odf'  => 'opendocument-formula.png'
   );

   /**
    * Constructs instance of Document.
    * @param type $attachment Attachment object used to initalize fields.
    * @param type $gallery    Instance of Gallery class.
    */
   public function __construct($attachment, $gallery) {
      // init templates for HTML output
      $this->doc_icon =
         '   <div class="document-icon">' . PHP_EOL .
         '      <a href="%s">%s<br>%s</a>' . PHP_EOL .
         '   </div>' . PHP_EOL;
      $this->icon_url = DG_URL . 'icons/';

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
    * Determines the filetype of the attachment and returns an HTML value to
    * appropriately represent the attachment.
    * @param attachment         object representing the attachment
    * @return string            HTML for the specified attachment's display
    */
   private function getIcon() {
      $url = wp_get_attachment_url($this->ID);
      $filetype = wp_check_filetype(basename($url));

      // identify extension
      if(array_key_exists($filetype['ext'], self::$exts)) {
         $icon = $this->icon_url . self::$exts[$filetype['ext']];
      }
      // handle images
      elseif (wp_attachment_is_image($this->ID) &&
          ($icon = wp_get_attachment_image_src($this->ID, 'thumbnail', false))) {
         $icon = $icon[0];
      }
      // fallback to default icons if not recognized
      elseif (($icon = wp_get_attachment_image_src($this->ID, null, true))) {
         $icon = $icon[0];
      }
      // everything failed. This is bad...
      else {
         $icon = $this->icon_url . 'missing.png';
      }

      return sprintf($this->img_string, $icon,
          $this->title_attribute, $this->title_attribute);
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
      $core = sprintf($this->doc_icon, $this->link, $this->getIcon(), $this->title);

      if($this->gallery->useDescriptions()) {
         $core .= "   <p>$this->description</p>" . PHP_EOL;
      }

      // users may filter icon here
      return apply_filters('dg_doc_icon', $core, $this->ID);
   }
}

?>
