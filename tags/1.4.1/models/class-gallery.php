<?php

/**
 * Description of Gallery
 *
 * @author drossiter
 */
class Gallery {
   private $atts, $taxa, $comment;
   private $docs = array();
   private $errs = array();

   private static $defaults = array(
       // default: link directly to file (true to link to attachment pg)
       'attachment_pg'  => FALSE,
       'descriptions'   => FALSE,
       // include thumbnail of actual document in gallery display
       //'fancy_thumbs' => FALSE,
       // comma-separated list of attachment ids
       'ids'            => FALSE,
       // if true, all images attached to current page will be included also
       'images'         => FALSE,
       'localpost'      => TRUE,
       'order'          => 'ASC',
       'orderby'        => 'menu_order',
       // only relevant if tax_query used (WP >= 3.1)
       'relation'       => 'AND'
   );

   // templates for HTML output
   private $no_docs = '<!-- No attachments to display. How boring! :( -->';
   private $icon_wrapper;

   /**
    * Builds a gallery object with attributes passed.
    * @param array $atts    Array of attributes used in shortcode.
    */
   public function __construct($atts) {
      // init templates for HTML output
      $this->comment =
         PHP_EOL . '<!-- Generated using Document Gallery. Get yours here: ' .
         'http://wordpress.org/extend/plugins/document-gallery -->' . PHP_EOL;
      $this->icon_wrapper = '<div class="%s">'. PHP_EOL . '%s</div>' . PHP_EOL;

      // values used to construct tax query (may be empty)
      $this->taxa = array_diff_key($atts, self::$defaults);

      // all recognized attributes go here
      $this->atts = shortcode_atts(self::$defaults, $atts);

      // goes through all values in $this->atts, setting $this->errs as needed
      $this->sanitizeDefaults();

      // query DB for all documents requested
      try {
         $docs = $this->getDocuments();
         include_once(DG_PATH . 'models/class-document.php');
         foreach($docs as $doc) {
            $this->docs[] = new Document($doc, $this);
         }
      } catch(InvalidArgumentException $e) {
         // errors will be printed in __toString()
      }
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

      return ($this->atts['ids'] !== FALSE)
         ? $this->getAttachmentsByIds()
         : get_posts($query);
   }

   /**
    * Cleans up user input, making sure we don't pass crap on to WP core.
    * @global string $wp_version
    */
   private function sanitizeDefaults() {
      global $wp_version;

      if($this->atts['attachment_pg'] !== self::$defaults['attachment_pg']) {
         $attachment_pg =
             filter_var($this->atts['attachment_pg'],
                 FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

         if($attachment_pg === null) {
            $this->errs[] =
                'The attachment_pg parameter may only be \'true\' or \'false.\' ' .
                "You entered {$this->atts['attachment_pg']}.";
         } else {
            $this->atts['attachment_pg'] = $attachment_pg;
         }
      }

      if($this->atts['descriptions'] !== self::$defaults['descriptions']) {
         $descriptions =
             filter_var($this->atts['descriptions'],
                 FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

         if($descriptions === null) {
            $this->errs[] =
                'The descriptions parameter may only be \'true\' or \'false.\' ' .
                "You entered {$this->atts['descriptions']}.";
         } else {
            $this->atts['descriptions'] = $descriptions;
         }
      }

      if($this->atts['ids'] !== self::$defaults['ids']) {
         if(strcasecmp('false', $this->atts['ids']) == 0) {
            $this->atts['ids'] = FALSE;
         } else {
            $ids = explode(',', $this->atts['ids']);
            $bad = array_filter($ids, array(__CLASS__, 'negativeInt'));

            if(!empty($bad)) {
               $this->errs[] =
                   'The following ID(s) are not valid: ' .
                   implode(', ', $bad) . '.';
            } else {
               $this->atts['ids'] = $ids;
            }
         }
      }

      if($this->atts['images'] !== self::$defaults['images']) {
         $images =
             filter_var($this->atts['images'],
                 FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

         if($images === null) {
            $this->errs[] =
                'The images parameter may only be \'true\' or \'false.\' ' .
                "You entered {$this->atts['images']}.";
         } else {
            $this->atts['images'] = $images;
         }
      }

      if($this->atts['localpost'] !== self::$defaults['localpost']) {
         $localpost =
             filter_var($this->atts['localpost'],
                 FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
         if($localpost === null) {
            $this->errs[] =
                'The localpost parameter may only be \'true\' or \'false.\' ' .
                "You entered {$this->atts['localpost']}.";
         } else {
            $this->atts['localpost'] = $localpost;
         }
      }

      $order = strtoupper($this->atts['order']);
      if('ASC' !== $order && 'DEC' !== $order) {
         $this->errs[] =
             'The order parameter must be either \'ASC\' or \'DEC.\' '.
             "You entered {$this->atts['order']}.";
      }

      $orderby = strtoupper($this->atts['orderby']) === 'ID'
          ? 'ID' : strtolower($this->atts['orderby']);
      if ($orderby !== 'ID' && $orderby !== 'menu_order'
         && $orderby !== 'author' && $orderby !== 'title'
         && $orderby !== 'name' && $orderby !== 'date'
         && $orderby !== 'modified' && $orderby !== 'parent'
         && $orderby !== 'rand' /* && $orderby !== 'meta_value' */
         // check version-specific parameters
         && version_compare($wp_version, '2.8', '>=') && $orderby !== 'none' /* && $orderby !== 'meta_value_num' */
         && version_compare($wp_version, '2.9', '>=') && $orderby !== 'comment_count'
         && version_compare($wp_version, '3.5', '>=') && $orderby !== 'post__in') {
         $this->errs[] =
            "The orderby parameter value entered, {$this->atts['orderby']}, " .
            "is not valid in WP $wp_version.";
      } else {
         $this->atts['orderby'] = $orderby;
      }

      $relation = strtoupper($this->atts['relation']);
      if ('AND' !== $relation && 'OR' !== $relation) {
         $this->errs[] =
             'The relation parameter must be either \'AND\' or \'OR.\' ' .
             "You entered {$this->atts['relation']}.";
      } else {
         $this->atts['relation'] = $relation;
      }
   }

   /**
    * Returns whether to link to attachment pg.
    * @return bool
    */
   public function linkToAttachmentPg() {
      return $this->atts['attachment_pg'];
   }

   /**
    * Returns whether descriptions should be included in output.
    * @return bool
    */
   public function useDescriptions() {
      return $this->atts['descriptions'];
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
         global $wp_version;
         $taxa = array();

         // use preferred tax_query if supported
         if (version_compare($wp_version, '3.1', '>=')) {
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
         } // fallback to deprecated {tax_name} => {term_slug} construct
         elseif (version_compare($wp_version, '2.3', '>=')) {
            foreach ($this->taxa as $taxon => $terms) {
               $taxa[$taxon] = ($taxon == 'category')
                   ? implode(',', $this->getTermIdsByNames($taxon, explode(',', $terms)))
                   : implode(',', $this->getTermSlugsByNames($taxon, explode(',', $terms)));
            }

            $query = array_merge($taxa, $query);
         } // WP < 2.3 not supported for category/custom taxa
         else {
            $this->errs[] = 'The following attributes are invalid: ' .
                implode(', ', array_keys($this->taxa));
         }
      }
   }


   /**
    * Returns an array of term ids when provided with a list of term names.
    * Also appends an entry onto $errs if any invalid names are found.
    * @param string $taxon
    * @param array $term_names
    * @param &array $errs
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
    * @param &array $errs
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
    * @param &array $errs
    * @return array
    */
   private function getTermXByNames($x, $taxon, $term_names) {
      $ret = array();

      foreach ($term_names as $name) {
         if (($term = get_term_by('name', $name, $taxon))) {
            $ret[] = $term->{$x};
         }
         else {
            $this->errs[] = "$name is not a valid term name in $taxon.";
         }
      }

      return $ret;
   }

   /**
    * Given a list of IDs, all attachments represented by these IDs are returned.
    * @return array        post objects
    */
   private function getAttachmentsByIds() {
      $attachments = array();
      foreach ($this->atts['ids'] as $id) {
         $attachment = get_post($id);
         if ($attachment->post_type === 'attachment')
            $attachments[] = $attachment;
         // else: not an attachment so skip
      }

      return $attachments;
   }

   /**
    * Function returns false for positive ints, true otherwise.
    * @param var $var could be anything.
    * @return boolean indicating whether $var is not a positive int.
    */
   private static function negativeInt($var) {
      return !is_numeric($var)      // isn't numeric
             || (int)$var != $var   // isn't int
             || (int)$var < 0;      // isn't positive
   }


   /**
    * Returns HTML representing this Gallery.
    * @return string
    */
   public function __toString() {
      if(!empty($this->errs)) {
         return '<p>' . implode('</p><p>', $this->errs) . '</p>';
      }

      if(empty($this->docs)) {
         return $this->no_docs;
      }

      $core = '';
      $classes = array('document-icon-wrapper');
      if($this->useDescriptions()) {
         $classes[] = 'descriptions';
      }

      $icon_wrapper = sprintf($this->icon_wrapper, implode(' ', $classes), '%s');

      if($this->useDescriptions()) {
         foreach($this->docs as $doc) {
            $core .= sprintf($icon_wrapper, (string)$doc);
         }
      } else {
         for($i = 0; $i < count($this->docs); $i+=4) {
            $row = '';

            $min = min($i+4, count($this->docs));
            for($x = $i; $x < $min; $x++) {
               $row .= (string)$this->docs[$x];
            }

            $core .= sprintf($icon_wrapper, $row);
         }
      }

      return $this->comment . $core;
   }
}

?>
