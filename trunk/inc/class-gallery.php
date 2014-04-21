<?php
defined('WPINC') OR exit;

DG_Gallery::init();

/**
 * Holds data specific to a given document gallery.
 *
 * @author drossiter
 */
class DG_Gallery {

   /*==========================================================================
    * PRIVATE FIELDS
    *=========================================================================*/

   private $atts, $taxa;
   private $docs = array();
   private $errs = array();

   // templates for HTML output
   private static $no_docs = false;
   private static $icon_wrapper = false;
   private static $comment = false;

   private static $binary_err = false;

   /*==========================================================================
    * PUBLIC FUNCTIONS
    *=========================================================================*/

   /**
    * Returns whether to link to attachment pg.
    * @return bool
    */
   public function linkToAttachmentPg() {
      return $this->atts['attachment_pg'];
   }

   public function useFancyThumbs() {
      return $this->atts['fancy'];
   }

   /**
    * Returns whether descriptions should be included in output.
    * @return bool
    */
   public function useDescriptions() {
      return $this->atts['descriptions'];
   }

   /*==========================================================================
    * GET AND SET OPTIONS
    *=========================================================================*/

   /**
    * Gets the DG options specific to Gallery.
    * @return array
    */
   public static function getOptions() {
      global $dg_options;
      return $dg_options['gallery'];
   }

   /**
    * Sets the DG options specific to Gallery.
    * @param array $options
    */
   public static function setOptions($options) {
      global $dg_options;
      $dg_options['gallery'] = $options;
      update_option(DG_OPTION_NAME, $dg_options);
   }

   public static function getDefaults() {
      $options = self::getOptions();
      return $options['defaults'];
   }

   public static function setDefaults($defaults) {
      $options = self::getOptions();
      $options['defaults'] = $defaults;
      self::setOptions($options);
   }

   /*==========================================================================
    * INIT GALLERY
    *=========================================================================*/

   public static function init() {
      self::$comment =
         PHP_EOL . '<!-- ' . __('Generated using Document Gallery. Get yours here: ', 'document-gallery') .
         'http://wordpress.org/extend/plugins/document-gallery -->' . PHP_EOL;
      self::$icon_wrapper = '<div class="%s">'. PHP_EOL . '%s</div>' . PHP_EOL;
      self::$no_docs = '<!-- ' . __('No attachments to display. How boring! :(', 'document-gallery') . ' -->';
      self::$binary_err = __('The %s parameter may only be "%s" or "%s." You entered "%s."', 'document-gallery');
   }

   /**
    * Builds a gallery object with attributes passed.
    * @param array $atts    Array of attributes used in shortcode.
    */
   public function __construct($atts) {
      // empty string is passed when no arguments are given, but constructor expects an array
      $atts = empty($atts) ? array() : $atts;
      $defaults = self::getDefaults();

      // values used to construct tax query (may be empty)
      $this->taxa = array_diff_key($atts, $defaults);

      // all recognized attributes go here
      $this->atts = shortcode_atts($defaults, $atts);

      // goes through all values in $this->atts, setting $this->errs as needed
      $this->atts = self::sanitizeDefaults($this->atts, $this->errs);

      // query DB for all documents requested
      include_once DG_PATH . 'inc/class-document.php';
      try {
         $docs = $this->getDocuments();

         foreach($docs as $doc) {
            $this->docs[] = new DG_Document($doc, $this);
         }
      } catch(InvalidArgumentException $e) {
         // errors will be printed in __toString()
      }
   }

   /**
    * Cleans up user input, making sure we don't pass crap on to WP core.
    * @global string $wp_version
    */
   public static function sanitizeDefaults($defaults, &$errs) {
      $old_defaults = self::getDefaults();

      // remove invalid keys
      $defaults = array_intersect_key($defaults, $old_defaults);

      // add any missing keys
      foreach ($old_defaults as $k => $v) {
         if (!isset($defaults[$k])) {
            if (is_bool($v)) {
               // checkbox
               $defaults[$k] = false;
            } else {
               // missing value
               $defaults[$k] = $v;
            }
         }
      }

      $defaults['attachment_pg'] = self::sanitizeAttachmentPg($defaults['attachment_pg'], $err);
      if (isset($err)) {
         $errs['attachment_pg'] = $err;
         unset($err);
      }

      $defaults['descriptions'] = self::sanitizeDescriptions($defaults['descriptions'], $err);
      if (isset($err)) {
         $errs['descriptions'] = $err;
         unset($err);
      }

      $defaults['fancy'] = self::sanitizeFancy($defaults['fancy'], $err);
      if (isset($err)) {
         $errs['fancy'] = $err;
         unset($err);
      }

      $defaults['ids'] = self::sanitizeIds($defaults['ids'], $err);
      if (isset($err)) {
         $errs['ids'] = $err;
         unset($err);
      }

      $defaults['images'] = self::sanitizeImages($defaults['images'], $err);
      if (isset($err)) {
         $errs['images'] = $err;
         unset($err);
      }

      $defaults['localpost'] = self::sanitizeLocalpost($defaults['localpost'], $err);
      if (isset($err)) {
         $errs['localpost'] = $err;
         unset($err);
      }

      $defaults['order'] = self::sanitizeOrder($defaults['order'], $err);
      if (isset($err)) {
         $errs['order'] = $err;
         unset($err);
      }

      $defaults['orderby'] = self::sanitizeOrderby($defaults['orderby'], $err);
      if (isset($err)) {
         $errs['orderby'] = $err;
         unset($err);
      }

      $defaults['relation'] = self::sanitizeRelation($defaults['relation'], $err);
      if (isset($err)) {
         $errs['relation'] = $err;
         unset($err);
      }

      return $defaults;
   }

   private static function sanitizeAttachmentPg($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['attachment_pg'];

      $attachment_pg = self::toBool($value);

      if(is_null($attachment_pg)) {
         $err = sprintf(self::$binary_err, 'attachment_pg', 'true', 'false', $value);
      } else {
         $ret = $attachment_pg;
      }

      return $ret;
   }

   private static function sanitizeDescriptions($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['descriptions'];

      $descriptions = self::toBool($value);

      if(is_null($descriptions)) {
         $err = sprintf(self::$binary_err, 'descriptions', 'true', 'false', $value);
      } else {
         $ret = $descriptions;
      }

      return $ret;
   }

   private static function sanitizeFancy($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['fancy'];

      $fancy = self::toBool($value);

      if(is_null($fancy)) {
         $err = sprintf(self::$binary_err, 'fancy', 'true', 'false', $value);
      } else {
         $ret = $fancy;
      }

      return $ret;
   }

   private static function sanitizeIds($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['ids'];

      if(false === self::toBool($value)) {
         $ret = false;
      } else {
         $value = trim($value);
         $ids = $value ? explode(',', $value) : array();
         $bad = array_filter($ids, array(__CLASS__, 'negativeInt'));

         if(!empty($bad)) {
            $err = _n('The following ID is invalid: ',
                'The following IDs are invalid: ',
                count($bad), 'document-gallery') . implode(', ', $bad);
         } else {
            $ret = $ids;
         }
      }

      return $ret;
   }

   private static function sanitizeImages($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['images'];

      $images = self::toBool($value);

      if(is_null($images)) {
         $err = sprintf(self::$binary_err, 'images', 'true', 'false', $value);
      } else {
         $ret = $images;
      }

      return $ret;
   }

   private static function sanitizeLocalpost($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['localpost'];

      $localpost = self::toBool($value);

      if(is_null($localpost)) {
         $err = sprintf(self::$binary_err, 'localpost', 'true', 'false', $value);
      } else {
         $ret = $localpost;
      }

      return $ret;
   }

   private static function sanitizeOrder($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['order'];

      $order = strtoupper($value);
      if(!in_array($order, self::getOrderOptions())) {
         $err = sprintf(self::$binary_err, 'order', 'ASC', 'DESC', $value);
      } else {
         $ret = $order;
      }

      return $ret;
   }

   public static function getOrderOptions() {
      return array('ASC', 'DESC');
   }

   private static function sanitizeOrderby($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['orderby'];

      $orderby = 'ID' === strtoupper($value) ? 'ID' : strtolower($value);
      if (!in_array($orderby, self::getOrderbyOptions())) {
         $err = sprintf(
            __('The orderby value entered, "%s," is not valid.', 'document-gallery'),
            $value);
      } else {
         $ret = $orderby;
      }

      return $ret;
   }

   public static function getOrderbyOptions() {
      return array('author', 'comment_count', 'date', 'ID',
          'menu_order', 'modified', 'name', 'none',
          'parent', 'post__in', 'rand', 'title');
   }

   private static function sanitizeRelation($value, &$err) {
      $defaults = self::getDefaults();
      $ret = $defaults['relation'];

      $relation = strtoupper($value);
      if(!in_array($relation, self::getRelationOptions())) {
         $err = sprintf(self::$binary_err, 'relation', 'AND', 'OR', $value);
      } else {
         $ret = $relation;
      }

      return $ret;
   }

   public static function getRelationOptions() {
      return array('AND', 'OR');
   }

   /**
    * Gets all valid Documents based on the attributes passed by the user.
    * @return array                    Contains all documents matching the query.
    * @throws InvalidArgumentException Thrown when $this->errs is not empty.
    */
   private function getDocuments() {
      $mime_types = array('application', 'video', 'text', 'audio');
      if ($this->atts['images']) {
         $mime_types[] = 'image';
      }

      $query = array(
          'numberposts'    => -1,
          'orderby'        => $this->atts['orderby'],
          'order'          => $this->atts['order'],
          'post_status'    => 'any',
          'post_type'      => 'attachment',
          'post_mime_type' => implode(',', $mime_types));

      $query['post_parent'] =
          $this->atts['localpost']
          && ($post = get_post()) ? $post->ID : '';

      $this->setTaxa($query);

      if(!empty($this->errs)) {
         throw new InvalidArgumentException();
      }

      return (false !== $this->atts['ids'])
         ? $this->getAttachmentsByIds()
         : get_posts($query);
   }

   /**
    * Function loops through all attributes passed that did not match
    * self::$defaults. If they are the name of a taxonomy, they are plugged
    * into the query, otherwise $this->errs is appended with an error string.
    * @global string $wp_version    Determines which tax query to use.
    * @param array $query           Query to insert tax query into.
    */
   private function setTaxa(&$query) {
      if(!empty($this->taxa)) {
         $taxa = array();

         // only include relation if we have multiple taxa
         if(count($this->taxa) > 1) {
            $taxa['relation'] = $this->atts['relation'];
         }

         foreach ($this->taxa as $taxon => $terms) {
            $terms = $this->getTermIdsByNames($taxon, explode(',', $terms));

            $taxa[] = array(
               'taxonomy' => $taxon,
               'field' => 'id',
               'terms' => $terms
            );
         }

         // create nested structure
         $query['tax_query'] = $taxa;
      }
   }

   /*==========================================================================
    * HELPER FUNCTIONS
    *=========================================================================*/

   /**
    * Returns an array of term ids when provided with a list of term names.
    * Also appends an entry onto $errs if any invalid names are found.
    * @param string $taxon
    * @param array $term_names
    * @return array
    */
   private function getTermIdsByNames($taxon, $term_names) {
      return $this->getTermXByNames('term_id', $taxon, $term_names);
   }

   /**
    * Returns an array of term slugs when provided with a list of term names.
    * Also appends an entry onto $errs if any invalid names are found.
    * @param string $taxon
    * @param array $term_names
    * @return array
    */
   private function getTermSlugsByNames($taxon, $term_names) {
      return $this->getTermXByNames('slug', $taxon, $term_names);
   }

   /**
    * (WP >= 2.3) Returns a list of x, where x may be any of the fields within a
    * term object, when provided with a list of term names (not slugs).
    * (http://codex.wordpress.org/Function_Reference/get_term_by#Return_Values)
    *
    * Also appends an entry onto $errs if any invalid names are found.
    * @param string $x
    * @param string $taxon
    * @param array $term_names
    * @return array
    */
   private function getTermXByNames($x, $taxon, $term_names) {
      $ret = array();

      foreach ($term_names as $name) {
         if (($term = get_term_by('name', $name, $taxon))) {
            $ret[] = $term->{$x};
         } else {
            $this->errs[] = sprintf(__('%s is not a valid term name in %s.',
                'document-gallery'), $name, $taxon);
         }
      }

      return $ret;
   }

   /**
    * Given a list of IDs, all attachments represented by these IDs are returned.
    * @return array        post objects
    */
   private function getAttachmentsByIds() {
      $args = array(
         'post_type'     => 'attachment',
         'post_status'   => 'inherit',
         'numberposts'   => -1,
         'post__in'      => $this->atts['ids'],
         'orderby'       => 'post__in'
      );

      return count($args['post__in']) ? get_posts($args) : array();
   }

   /**
    * Function returns false for positive ints, true otherwise.
    * @param string $var could be anything.
    * @return boolean indicating whether $var is not a positive int.
    */
   private static function negativeInt($var) {
      return !is_numeric($var)      // isn't numeric
             || (int)$var != $var   // isn't int
             || (int)$var < 0;      // isn't positive
   }

   private static function toBool($val) {
      if (is_bool($val)) {
         return $val;
      }

      if (is_string($val)) {
         $val = strtolower($val);
         if ('true' === $val || '1' === $val) {
            return true;
         }

         if ('false' === $val || '0' === $val) {
            return false;
         }
      }

      if (is_null($val)) {
         return false;
      }

      return null;
   }

   /*==========================================================================
    * OUTPUT HTML STRING
    *=========================================================================*/

   /**
    * Returns HTML representing this Gallery.
    * @return string
    */
   public function __toString() {
      if(!empty($this->errs)) {
         return '<p>' . implode('</p><p>', $this->errs) . '</p>';
      }

      if(empty($this->docs)) {
         return self::$no_docs;
      }

      $core = '';
      $classes = array('document-icon-wrapper');
      if($this->useDescriptions()) {
         $classes[] = 'descriptions';
      }

      $icon_wrapper = sprintf(self::$icon_wrapper, implode(' ', $classes), '%s');

      if($this->useDescriptions()) {
         foreach($this->docs as $doc) {
            $core .= sprintf($icon_wrapper, $doc);
         }
      } else {
         for($i = 0; $i < count($this->docs); $i+=4) {
            $row = '';

            $min = min($i+4, count($this->docs));
            for($x = $i; $x < $min; $x++) {
               $row .= $this->docs[$x];
            }

            $core .= sprintf($icon_wrapper, $row);
         }
      }

      return self::$comment . $core;
   }
}

?>