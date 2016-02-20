<?php
defined( 'WPINC' ) OR exit;

include_once DG_PATH . 'inc/class-gallery-sanitization.php';

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

	private $instance;

	private $pg_count = 1, $cur_pg = 1;

	// templates for HTML output
	private static $no_docs, $comment, $defaults;

	/*==========================================================================
	 * PUBLIC FUNCTIONS
	 *=========================================================================*/

	/**
	 * @return bool Whether to link to attachment pg.
	 */
	public function linkToAttachmentPg() {
		return $this->atts['attachment_pg'];
	}

	/**
	 * @return bool Whether to open thumb links in new window.
	 */
	public function openLinkInNewWindow() {
		return $this->atts['new_window'];
	}

	/**
	 * @return bool Whether to use "fancy" thumbnails.
	 */
	public function useFancyThumbs() {
		return $this->atts['fancy'];
	}

	/**
	 * @return bool Whether descriptions should be included in output.
	 */
	public function useDescriptions() {
		return $this->atts['descriptions'];
	}

	/*==========================================================================
	 * GET AND SET OPTIONS
	 *=========================================================================*/

	/**
	 * @param int $blog The blog we're retrieving options for (null => current blog).
	 *
	 * @return mixed[] Gets gallery branch of DG options array.
	 */
	public static function getOptions( $blog = null ) {
		$options = DocumentGallery::getOptions( $blog );

		return $options['gallery'];
	}

	/**
	 * @param mixed[] $options New value for gallery branch of DG options array.
	 * @param int $blog The blog we're retrieving options for (null => current blog).
	 */
	public static function setOptions( $options, $blog = null ) {
		$dg_options            = DocumentGallery::getOptions( $blog );
		$dg_options['gallery'] = $options;
		DocumentGallery::setOptions( $dg_options, $blog );
	}

	/*==========================================================================
	 * INIT GALLERY
	 *=========================================================================*/

	/**
	 * Initializes static values for this class.
	 */
	public static function init() {
		if ( ! isset( self::$comment ) ) {
			self::$comment  =
				PHP_EOL . '<!-- ' . __( 'Generated using Document Gallery. Get yours here: ', 'document-gallery' ) .
				'http://wordpress.org/extend/plugins/document-gallery -->' . PHP_EOL;
			self::$no_docs  = '<!-- ' . __( 'No attachments to display. How boring! :(', 'document-gallery' ) . ' -->';
			self::$defaults = array_merge( array( 'include' => '', 'exclude' => '' ), self::getOptions() );
		}
	}

	/**
	 * Builds a gallery object with attributes passed.
	 *
	 * @param mixed[] $atts Array of attributes used in shortcode.
	 */
	public function __construct( $atts ) {
		static $instance = 0;
		$this->instance = ++$instance;

		include_once DG_PATH . 'inc/class-document.php';

		// empty string is passed when no arguments are given, but constructor expects an array
		$atts = empty( $atts ) ? array() : $atts;

		// get_post will return null during AJAX requests
		$post = get_post();
		$post_id = !is_null( $post ) ? $post->ID : -1;

		if ( ! empty( $atts['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $atts['orderby'] ) ) {
				$atts['orderby'] = 'post__in';
			}

			$atts['include'] = $atts['ids'];
			unset( $atts['ids'] );
		}

		// allow abbreviated columns attribute
		if ( ! empty( $atts['cols'] ) ) {
			$atts['columns'] = $atts['cols'];
			unset( $atts['cols'] );
		}

		if ( ! empty( $atts['images'] ) ) {
			if ( DG_Util::toBool( $atts['images'], false ) ) {
				$options = self::getOptions();
				$mimes   = trim( isset( $atts['mime_types'] ) ? $atts['mime_types'] : $options['mime_types'] );
				if ( ! preg_match( '/[,^]image[,$]/', $mimes ) ) {
					$atts['mime_types'] = empty( $mimes ) ? 'image' : ( $mimes . ',image' );
				}
			}

			unset( $atts['images'] );
		}

		/**
		 * @deprecated localpost will be removed at some point.
		 */
		if ( ! empty( $atts['localpost'] ) ) {
			$atts['id'] = -1;
			unset( $atts['localpost'] );
		}

		$defaults = array_merge( array( 'id' => $post_id ), self::$defaults );

		// values used to construct tax query (may be empty)
		$this->taxa = array_diff_key( $atts, $defaults );

		// all recognized attributes go here
		$this->atts = shortcode_atts( $defaults, $atts );

		// goes through all values in atts, setting errs as needed
		$this->atts = self::sanitizeDefaults( $defaults, $this->atts, $this->errs );

		// query DB for all documents requested
		try {
			foreach ( $this->getDocuments() as $doc ) {
				$this->docs[] = new DG_Document( $doc, $this );
			}
		} catch ( InvalidArgumentException $e ) {
			// errors will be printed in __toString()
		}
	}

	/**
	 * Cleans up user input, making sure we don't pass crap on to WP core.
	 *
	 * @param mixed[] $old_defaults The previous set of defaults.
	 * @param mixed[] $defaults The defaults array to sanitize.
	 * @param string[] &$errs The array of errors, which will be appended with any errors found.
	 *
	 * @return mixed[] The sanitized defaults.
	 */
	public static function sanitizeDefaults( $old_defaults, $defaults, &$errs ) {
		if ( is_null( $old_defaults ) ) {
			$old_defaults = self::getOptions();
		}

		// remove invalid keys
		$sanitized = is_array( $defaults )
			? array_intersect_key( $defaults, $old_defaults )
			: array();

		// add any missing keys & sanitize each new value
		foreach ( $old_defaults as $k => $v ) {
			if ( ! isset( $sanitized[ $k ] ) ) {
				if ( is_bool( $v ) ) {
					// checkbox
					$sanitized[ $k ] = false;
				} else {
					// missing value
					$sanitized[ $k ] = $v;
				}
			} else if ( $sanitized[ $k ] !== $v ) { //Sometimes we get boolean in the string form for checkboxes
				// sanitize value if different from old value
				$sanitized[ $k ] = DG_GallerySanitization::sanitizeParameter( $k, $sanitized[ $k ], $errs );
			}
		}

		return $sanitized;
	}

	/**
	 * Gets all valid Documents based on the attributes passed by the user.
	 * NOTE: Keys in returned array are arbitrary and will vary. They should be ignored.
	 * @return WP_Post[] Contains all documents matching the query.
	 * @throws InvalidArgumentException Thrown when $this->errs is not empty.
	 */
	private function getDocuments() {
		$query = array(
			'posts_per_page'    => $this->atts['limit'],
			'offset'            => $this->atts['skip'],
			'orderby'           => $this->atts['orderby'],
			'order'             => $this->atts['order'],
			'post_status'       => $this->atts['post_status'],
			'post_type'         => $this->atts['post_type'],
			'post_mime_type'    => $this->atts['mime_types'],
			'suppress_filters'  => true
		);

		$this->setTaxa( $query );

		if ( ! empty( $this->errs ) ) {
			throw new InvalidArgumentException();
		}

		if ( ! empty( $this->atts['include'] ) ) {
			$query['post__in'] = wp_parse_id_list( $this->atts['include'] );
		} else {
			// id == 0    => all attachments w/o a parent
			// id == null => all matched attachments
			$query['post_parent'] = $this->atts['id'];
			if ( ! empty( $this->atts['exclude'] ) ) {
				$query['post__not_in'] = wp_parse_id_list( $this->atts['exclude'] );
			}
		}

		$wpq            = new WP_Query();
		$attachments    = $wpq->query( $query );
		$this->pg_count = $wpq->max_num_pages;
		if ( $this->atts['skip'] >= 0 && $this->atts['limit'] > 0 ) {
			$this->cur_pg   = min( $this->atts['skip'] / $this->atts['limit'] + 1, $wpq->max_num_pages );
		}

		return $attachments;
	}

	/**
	 * Function loops through all attributes passed that did not match
	 * self::$defaults. If they are the name of a taxonomy, they are plugged
	 * into the query, otherwise $this->errs is appended with an error string.
	 *
	 * @param mixed[] $query Query to insert tax query into.
	 */
	private function setTaxa( &$query ) {
		if ( ! empty( $this->taxa ) ) {
			static $pattern  = '/(.+)_(?:relation|operator)$/i';
			$taxa     = array( 'relation' => $this->atts['relation'] );
			$operator = array();

			// find any relations for taxa
			$operator_keys = array();
			foreach ( $this->taxa as $key => $value ) {
				if ( preg_match( $pattern, $key, $matches ) ) {
					$base = $matches[1];
					if ( isset( $this->taxa[$base] ) ) {
						$operator[$base] = DG_GallerySanitization::sanitizeParameter( 'operator', $value, $this->errs );
						$operator_keys[] = $key;
					}
				}
			}

			// build tax query
			foreach ( $this->taxa as $taxon => $terms ) {
				if ( in_array( $taxon, $operator_keys ) ) continue;

				$terms = $this->getTermIdsByNames( $taxon, explode( ',', $terms ) );

				$taxa[] = array(
					'taxonomy' => $taxon,
					'field'    => 'id',
					'terms'    => $terms,
					'operator' => isset( $operator[ $taxon ] ) ? $operator[ $taxon ] : 'IN'
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
	 *
	 * @param string $taxon The taxon these terms are a member of.
	 * @param string[] $term_names Terms to retrieve.
	 *
	 * @return WP_Term[] All matched terms.
	 */
	private function getTermIdsByNames( $taxon, $term_names ) {
		return $this->getTermXByNames( 'term_id', $taxon, $term_names );
	}

	/**
	 * Returns an array of term slugs when provided with a list of term names.
	 * Also appends an entry onto $errs if any invalid names are found.
	 *
	 * @param string $taxon The taxon these terms are a member of.
	 * @param string[] $term_names Terms to retrieve.
	 *
	 * @return WP_Term[] All matched terms.
	 */
	private function getTermSlugsByNames( $taxon, $term_names ) {
		return $this->getTermXByNames( 'slug', $taxon, $term_names );
	}

	/**
	 * Returns a list of x, where x may be any of the fields within a
	 * term object, when provided with a list of term names (not slugs).
	 * (http://codex.wordpress.org/Function_Reference/get_term_by#Return_Values)
	 *
	 * Also appends an entry onto $errs if any invalid names are found.
	 *
	 * @param string $x Field to retrieve from matched term.
	 * @param string $taxon The taxon these terms are a member of.
	 * @param string[] $term_idents Terms to retrieve, identified by either slug or name.
	 *
	 * @return WP_Term[] All matched terms.
	 */
	private function getTermXByNames( $x, $taxon, $term_idents ) {
		$ret   = array();
		$valid = true;

		// taxons may optionally be prefixed by 'tax_' --
		// this is only useful when avoiding collisions with other attributes
		if ( ! taxonomy_exists( $taxon ) ) {
			if ( DG_Util::startsWith( $taxon, 'tax_' ) && ( $tmp = substr( $taxon, 4 ) ) && taxonomy_exists( $tmp ) ) {
				$taxon = $tmp;
			} else {
				$this->errs[] = sprintf( DG_GallerySanitization::getUnaryErr(), 'taxon', $taxon );
				$valid        = false;
			}
		}

		// only check terms if we first have a valid taxon
		if ( $valid ) {
			foreach ( $term_idents as $ident ) {
				$term = get_term_by( 'slug', $ident, $taxon );
				if ( ! $term ) {
					$term = get_term_by( 'name', $ident, $taxon );
				}

				if ( $term ) {
					$ret[] = $term->{$x};
				} else {
					$this->errs[] = sprintf( __( '%s is not a valid term slug/name in %s.',
						'document-gallery' ), $ident, $taxon );
				}
			}
		}

		return $ret;
	}

	/**
	 * Function returns false for positive ints, true otherwise.
	 *
	 * @param string $var could be anything.
	 *
	 * @return boolean indicating whether $var is not a positive int.
	 */
	private static function negativeInt( $var ) {
		return ! is_numeric( $var )      // isn't numeric
		       || (int) $var != $var   // isn't int
		       || (int) $var < 0;      // isn't positive
	}

	/*==========================================================================
	 * OUTPUT HTML STRING
	 *=========================================================================*/

	/**
	 * @return mixed[] The data to be used in the data-shortcode attribute.
	 */
	private function getShortcodeData() {
		$ret = array_merge( $this->atts, $this->taxa );

		// need to undo nulling of -1 ID for version sent out to JSON
		if ( is_null( $ret['id'] ) ) {
			$ret['id'] = -1;
		}

		return $ret;
	}

	/**
	 * @filter dg_gallery_template Allows the user to filter anything content surrounding the generated gallery.
	 * @filter dg_row_template Filters the outer DG wrapper HTML. Passes a single
	 *    bool value indicating whether the gallery is using descriptions or not.
	 * @return string HTML representing this Gallery.
	 */
	public function __toString() {
		if ( ! empty( $this->errs ) ) {
			return '<p>' . implode( '</p><p>', $this->errs ) . '</p>';
		}

		if ( empty( $this->docs ) ) {
			return self::$no_docs;
		}

		$icon_find       = array( '%class%', '%icons%' );
		$icon_repl		 = array();
		$icon_classes    = array( 'document-icon-row' );
		$gallery_find    = array( '%id%', '%data%', '%rows%', '%class%' );
		$gallery_repl    = array( "document-gallery-$this->instance", ( "data-shortcode='" . wp_json_encode( self::getShortcodeData() ) . "'" ), '' );
		$gallery_classes = array( 'document-gallery' );

		if ( $this->useDescriptions() ) {
			$icon_classes[] = 'descriptions';
		}

		$icon_repl[] = implode( ' ', $icon_classes );

		$icon_wrapper = apply_filters(
			'dg_row_template',
			"<div class='%class%'>" . PHP_EOL . '%icons%' . PHP_EOL . '</div>' . PHP_EOL,
			$this->useDescriptions() );

		if ( $this->useDescriptions() ) {
			foreach ( $this->docs as $doc ) {
				$icon_repl[1] = $doc;
				$gallery_repl[2] .= str_replace( $icon_find, $icon_repl, $icon_wrapper );
			}
		} else {
			$count = count( $this->docs );
			$cols  = !is_null( $this->atts['columns'] ) ? $this->atts['columns'] : $count;

			if ( apply_filters( 'dg_use_default_gallery_style', true ) ) {
				$itemwidth = $cols > 0 ? ( floor( 100 / $cols ) - 1 ) : 100;
				$gallery_repl[1] .= " data-icon-width='$itemwidth'";
			}

			for ( $i = 0; $i < $count; $i += $cols ) {
				$icon_repl[1] = '';

				$min = min( $i + $cols, $count );
				for ( $x = $i; $x < $min; $x++ ) {
					$icon_repl[1] .= $this->docs[ $x ];
				}

				$gallery_repl[2] .= str_replace( $icon_find, $icon_repl, $icon_wrapper );
			}
		}

		// allow user to filter gallery wrapper
		$gallery = apply_filters( 'dg_gallery_template', '<div id="%id%" class="%class%" %data%>' . PHP_EOL . '%rows%</div>', $this->useDescriptions() );

		// build pagination section
		if ( $this->atts['paginate'] && $this->atts['limit'] > 0 && $this->pg_count > 1 ) {
			$args = array(
				'base'    => '#%_%',
				'format'  => 'dg_page=%#%',
				'total'   => $this->pg_count,
				'current' => $this->cur_pg,
				'prev_text' => __( '&laquo;' ),
				'next_text' => __( '&raquo;' )
			);
			$gallery_repl[2] .= '<div class="paginate">' . paginate_links( $args ) . '</div>';
			$gallery_classes[] = 'dg-paginate-wrapper';
		}

		$gallery_repl[] = implode( ' ', $gallery_classes );

		$comment = self::$comment;
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$ids = array();
			foreach ( $this->docs as $doc ) {
				$ids[] = $doc->getId();
			}

			$comment .= '<!-- Attachment IDs: ' . implode( $ids, ', ' ) . ' -->' . PHP_EOL;
		}

		return $comment . str_replace( $gallery_find, $gallery_repl, $gallery );
	}
}