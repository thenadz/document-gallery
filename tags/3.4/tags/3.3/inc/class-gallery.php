<?php
defined( 'WPINC' ) OR exit;

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
	private static $no_docs, $comment, $unary_err, $binary_err;

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
	 * @return array Gets gallery branch of DG options array.
	 */
	public static function getOptions( $blog = null ) {
		$options = DocumentGallery::getOptions( $blog );

		return $options['gallery'];
	}

	/**
	 * @param array $options New value for gallery branch of DG options array.
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
			self::$comment    =
				PHP_EOL . '<!-- ' . __( 'Generated using Document Gallery. Get yours here: ', 'document-gallery' ) .
				'http://wordpress.org/extend/plugins/document-gallery -->' . PHP_EOL;
			self::$no_docs    = '<!-- ' . __( 'No attachments to display. How boring! :(', 'document-gallery' ) . ' -->';
			self::$unary_err  = __( 'The %s value entered, "%s", is not valid.', 'document-gallery' );
			self::$binary_err = __( 'The %s parameter may only be "%s" or "%s." You entered "%s."', 'document-gallery' );
		}
	}

	/**
	 * Builds a gallery object with attributes passed.
	 *
	 * @param array $atts Array of attributes used in shortcode.
	 */
	public function __construct( $atts ) {
		include_once DG_PATH . 'inc/class-document.php';

		$post = get_post();

		// empty string is passed when no arguments are given, but constructor expects an array
		$atts = empty( $atts ) ? array() : $atts;

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
			$options = self::getOptions();
			$mimes   = trim( isset( $atts['mime_types'] ) ? $atts['mime_types'] : $options['mime_types'] );
			if ( ! preg_match( '/[,^]image[,$]/', $mimes ) ) {
				$atts['mime_types'] = empty( $mimes ) ? 'image' : ( $mimes . ',image' );
			}
		}

		/**
		 * @deprecated localpost will be removed at some point.
		 */
		if ( ! empty( $atts['localpost'] ) ) {
			$atts['id'] = - 1;
			unset( $atts['localpost'] );
		}

		// merge options w/ default values not stored in options
		$defaults = array_merge(
			array( 'id' => $post->ID, 'include' => '', 'exclude' => '' ),
			self::getOptions() );

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
	 * @param array $old_defaults The previous set of defaults.
	 * @param array $defaults The defaults array to sanitize.
	 * @param array &$errs The array of errors, which will be appended with any errors found.
	 *
	 * @return array The sanitized defaults.
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
				$sanitized[ $k ] = self::sanitizeParameter( $k, $sanitized[ $k ], $errs );
			}
		}

		return $sanitized;
	}

	/**
	 *
	 * @param string $key The key to reference the current value in the defaults array.
	 * @param unknown $value The value to be sanitized.
	 * @param array $errs The array of errors, which will be appended with any errors found.
	 *
	 * @return unknown The sanitized value, falling back to the current default value when invalid value given.
	 */
	private static function sanitizeParameter( $key, $value, &$errs ) {
		// all sanitize methods must be in the following form: sanitize<UpperCammelCaseKey>
		$funct    = $key;
		$funct[0] = strtoupper( $funct[0] );
		$funct    = 'sanitize' . preg_replace_callback( '/_([a-z])/', array( __CLASS__, 'secondCharToUpper' ), $funct );

		$callable = array( __CLASS__, $funct );

		// avoid looking for method beforehand unless we're running in debug mode -- expensive call
		if ( DG_Logger::logEnabled() && ! method_exists( __CLASS__, $funct ) ) {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				__( 'Attempted to call invalid function: ', 'document-gallery' ) . implode( '::', $callable ),
				true );
		}

		// call param-specific sanitization
		$ret = call_user_func_array( $callable, array( $value, &$err ) );

		// check for error and return default
		if ( isset( $err ) ) {
			$defaults = self::getOptions();
			$ret      = $defaults[ $key ];

			$errs[ $key ] = $err;
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The attachment_pg value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool The sanitized attachment_pg value.
	 */
	private static function sanitizeAttachmentPg( $value, &$err ) {
		$ret = DG_Util::toBool( $value );

		if ( is_null( $ret ) ) {
			$err = sprintf( self::$binary_err, 'attachment_pg', 'true', 'false', $value );
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The columns value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return int The sanitized columns value.
	 */
	public static function sanitizeColumns( $value, &$err ) {
		return $value != - 1 ? absint( $value ) : null;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The descriptions value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool The sanitized descriptions value.
	 */
	private static function sanitizeDescriptions( $value, &$err ) {
		$ret = DG_Util::toBool( $value );

		if ( is_null( $ret ) ) {
			$err = sprintf( self::$binary_err, 'descriptions', 'true', 'false', $value );
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The exclude value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool The sanitized exclude value.
	 */
	private static function sanitizeExclude( $value, &$err ) {
		return self::sanitizeIdList( 'Exclude', $value, $err );
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The fancy value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool The sanitized fancy value.
	 */
	private static function sanitizeFancy( $value, &$err ) {
		$ret = DG_Util::toBool( $value );

		if ( is_null( $ret ) ) {
			$err = sprintf( self::$binary_err, 'fancy', 'true', 'false', $value );
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The id value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return int The sanitized id value.
	 */
	private static function sanitizeId( $value, &$err ) {
		return $value != - 1 ? absint( $value ) : null;
	}

	/**
	 * Takes the provided comma-delimited list of IDs and returns null if it is invalid.
	 *
	 * @param string $name Name of the value being sanitized. Used in error string when needed.
	 * @param string $value The ids value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool|multitype:int The sanitized comma-delimited list of IDs value.
	 */
	private static function sanitizeIdList( $name, $value, &$err ) {
		static $regex = '/(?:|\d+(?:,\d+)*)/';

		$ret = $value;

		if ( ! preg_match( $regex, $value ) ) {
			$err = sprintf( __( '%s may only be a comma-delimited list of integers.', 'document-gallery' ), $name );
			$ret = null;
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The ids value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool|multitype:int The sanitized ids value.
	 */
	private static function sanitizeInclude( $value, &$err ) {
		return self::sanitizeIdList( 'Include', $value, $err );
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The limit value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return int The sanitized limit value.
	 */
	private static function sanitizeLimit( $value, &$err ) {
		$ret = intval( $value );

		if ( is_null( $ret ) || $ret < - 1 ) {
			$err = sprintf( self::$unary_err, 'limit', '>= -1' );
			$ret = null;
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The mime_types value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return string The sanitized mime_types value.
	 */
	private static function sanitizeMimeTypes( $value, &$err ) {
		// TODO: do some actual sanitization...
		return $value;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The new_window value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return bool The sanitized new_window value.
	 */
	private static function sanitizeNewWindow( $value, &$err ) {
		$ret = DG_Util::toBool( $value );

		if ( is_null( $ret ) ) {
			$err = sprintf( self::$binary_err, 'new_window', 'true', 'false', $value );
		}

		return $ret;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The order value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return string The sanitized order value.
	 */
	private static function sanitizeOrder( $value, &$err ) {
		$ret = strtoupper( $value );

		if ( ! in_array( $ret, self::getOrderOptions() ) ) {
			$err = sprintf( self::$binary_err, 'order', 'ASC', 'DESC', $value );
			$ret = null;
		}

		return $ret;
	}

	/**
	 * @return array The valid options for order parameter.
	 */
	public static function getOrderOptions() {
		return array( 'ASC', 'DESC' );
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The orderby value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return string The sanitized orderby value.
	 */
	private static function sanitizeOrderby( $value, &$err ) {
		$ret = ( 'ID' === strtoupper( $value ) ) ? 'ID' : strtolower( $value );

		if ( ! in_array( $ret, self::getOrderbyOptions() ) ) {
			$err = sprintf( self::$unary_err, 'orderby', $value );
			$ret = null;
		}

		return $ret;
	}

	/**
	 * @return array The valid options for orderby parameter.
	 */
	public static function getOrderbyOptions() {
		return array(
			'author',
			'comment_count',
			'date',
			'ID',
			'menu_order',
			'modified',
			'name',
			'none',
			'parent',
			'post__in',
			'rand',
			'title'
		);
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The post_status value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return string The sanitized post_status value.
	 */
	private static function sanitizePostStatus( $value, &$err ) {
		$ret = preg_grep( '/^' . preg_quote( $value ) . '$/i', self::getPostStatuses() );
		$ret = reset( $ret );

		if ( $ret === false ) {
			$err = sprintf( self::$unary_err, 'post_status', $value );
		}

		return $ret;
	}

	/**
	 * @return array All registered post statuses.
	 */
	public static function getPostStatuses() {
		static $statuses;
		if ( ! isset( $statuses ) ) {
			$statuses   = get_post_stati();
			$statuses[] = 'any';
			asort( $statuses );
		}

		return $statuses;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The post_type value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return string The sanitized post_type value.
	 */
	private static function sanitizePostType( $value, &$err ) {
		$ret = preg_grep( '/^' . preg_quote( $value ) . '$/i', self::getPostTypes() );
		$ret = reset( $ret );

		if ( $ret === false ) {
			$err = sprintf( self::$unary_err, 'post_type', $value );
		}

		return $ret;
	}

	/**
	 * @return array All registered post types.
	 */
	public static function getPostTypes() {
		static $types;
		if ( ! isset( $types ) ) {
			$types   = get_post_types();
			$types[] = 'any';
			asort( $types );
		}

		return $types;
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $value The relation value to be sanitized.
	 * @param string &$err String to be initialized with error, if any.
	 *
	 * @return string The sanitized relation value.
	 */
	private static function sanitizeRelation( $value, &$err ) {
		$ret = strtoupper( $value );

		if ( ! in_array( $ret, self::getRelationOptions() ) ) {
			$err = sprintf( self::$binary_err, 'relation', 'AND', 'OR', $value );
			$ret = null;
		}

		return $ret;
	}

	/**
	 * @return array The valid options for relation parameter.
	 */
	public static function getRelationOptions() {
		return array( 'AND', 'OR' );
	}

	/**
	 * Takes the provided value and returns a sanitized value.
	 *
	 * @param string $operator The operator value to be sanitized.
	 *
	 * @return string The sanitized operator value.
	 */
	private function sanitizeOperator( $operator ) {
		$ret = strtoupper( $operator );

		if ( ! in_array( $ret, self::getOperatorOptions() ) ) {
			$this->errs[] = sprintf( self::$binary_err, 'IN", "NOT IN", "OR', 'AND', $operator );
			$ret          = null;
		} else if ( $ret === 'OR' ) {
			$ret = 'IN';
		}

		return $ret;
	}

	/**
	 * @return array The valid options for *_relation/*_operator parameter.
	 */
	public static function getOperatorOptions() {
		return array( 'IN', 'NOT IN', 'AND', 'OR' );
	}

	/**
	 * Gets all valid Documents based on the attributes passed by the user.
	 * NOTE: Keys in returned array are arbitrary and will vary. They should be ignored.
	 * @return array Contains all documents matching the query.
	 * @throws InvalidArgumentException Thrown when $this->errs is not empty.
	 */
	private function getDocuments() {
		$query = array(
			'numberposts'    => $this->atts['limit'],
			'orderby'        => $this->atts['orderby'],
			'order'          => $this->atts['order'],
			'post_status'    => $this->atts['post_status'],
			'post_type'      => $this->atts['post_type'],
			'post_mime_type' => $this->atts['mime_types']
		);

		$this->setTaxa( $query );

		if ( ! empty( $this->errs ) ) {
			throw new InvalidArgumentException();
		}

		// NOTE: Derived from gallery shortcode
		if ( ! empty( $this->atts['include'] ) ) {
			$query['include'] = $this->atts['include'];
			$attachments      = get_posts( $query );
		} else {
			// id == 0    => all attachments w/o a parent
			// id == null => all matched attachments
			$query['post_parent'] = $this->atts['id'];
			if ( ! empty( $exclude ) ) {
				$query['exclude'] = $this->atts['exclude'];
			}

			$attachments = get_children( $query );
		}

		return $attachments;
	}

	/**
	 * Function loops through all attributes passed that did not match
	 * self::$defaults. If they are the name of a taxonomy, they are plugged
	 * into the query, otherwise $this->errs is appended with an error string.
	 *
	 * @param array $query Query to insert tax query into.
	 */
	private function setTaxa( &$query ) {
		if ( ! empty( $this->taxa ) ) {
			$taxa     = array( 'relation' => $this->atts['relation'] );
			$operator = array();
			$suffix   = array( 'relation', 'operator' );
			$pattern  = '/(.+)_(?:' . implode( '|', $suffix ) . ')$/i';

			// find any relations for taxa
			$iterable = $this->taxa;
			foreach ( $iterable as $key => $value ) {
				if ( preg_match( $pattern, $key, $matches ) ) {
					$base = $matches[1];
					if ( array_key_exists( $base, $this->taxa ) ) {
						$operator[ $base ] = self::sanitizeOperator( $value );
						unset( $this->taxa[ $key ] );
					}
				}
			}

			// build tax query
			foreach ( $this->taxa as $taxon => $terms ) {
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
	 * @param array $term_names Terms to retrieve.
	 *
	 * @return array All matched terms.
	 */
	private function getTermIdsByNames( $taxon, $term_names ) {
		return $this->getTermXByNames( 'term_id', $taxon, $term_names );
	}

	/**
	 * Returns an array of term slugs when provided with a list of term names.
	 * Also appends an entry onto $errs if any invalid names are found.
	 *
	 * @param string $taxon The taxon these terms are a member of.
	 * @param array $term_names Terms to retrieve.
	 *
	 * @return array All matched terms.
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
	 * @param array $term_names Terms to retrieve.
	 *
	 * @return array All matched terms.
	 */
	private function getTermXByNames( $x, $taxon, $term_names ) {
		$ret   = array();
		$valid = true;

		// taxons may optionally be prefixed by 'tax_' --
		// this is only useful when avoiding collisions with other attributes
		if ( ! taxonomy_exists( $taxon ) ) {
			$tmp = preg_replace( '/^tax_(.*)/', '$1', $taxon, 1, $count );
			if ( $count > 0 && taxonomy_exists( $tmp ) ) {
				$taxon = $tmp;
			} else {
				$this->errs[] = sprintf( self::$unary_err, 'taxon', $taxon );
				$valid        = false;
			}
		}

		// only check terms if we first have a valid taxon
		if ( $valid ) {
			foreach ( $term_names as $name ) {
				if ( ( $term = get_term_by( 'name', $name, $taxon ) ) ) {
					$ret[] = $term->{$x};
				} else {
					$this->errs[] = sprintf( __( '%s is not a valid term name in %s.',
						'document-gallery' ), $name, $taxon );
				}
			}
		}

		return $ret;
	}

	/**
	 * @param string $string To take second char from.
	 *
	 * @return string Capitalized second char of given string.
	 */
	private static function secondCharToUpper( $string ) {
		return strtoupper( $string[1] );
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
	 * @filter dg_gallery_template Allows the user to filter anything content surrounding the generated gallery.
	 * @filter dg_row_template Filters the outer DG wrapper HTML. Passes a single
	 *    bool value indicating whether the gallery is using descriptions or not.
	 * @return string HTML representing this Gallery.
	 */
	public function __toString() {
		static $instance = 0;
		$instance ++;

		static $find = null;
		if ( is_null( $find ) ) {
			$find = array( '%class%', '%icons%' );
		}

		if ( ! empty( $this->errs ) ) {
			return '<p>' . implode( '</p><p>', $this->errs ) . '</p>';
		}

		if ( empty( $this->docs ) ) {
			return self::$no_docs;
		}

		$selector = "document-gallery-$instance";
		$template =
			"<div id='$selector' class='%class%'>" . PHP_EOL .
			'%icons%' . PHP_EOL .
			'</div>' . PHP_EOL;

		$icon_wrapper = apply_filters(
			'dg_row_template',
			$template,
			$this->useDescriptions() );

		$core    = '';
		$classes = array( 'document-icon-wrapper' );
		if ( $this->useDescriptions() ) {
			$classes[] = 'descriptions';
		}

		$repl = array( implode( ' ', $classes ) );
		if ( $this->useDescriptions() ) {
			foreach ( $this->docs as $doc ) {
				$repl[1] = $doc;
				$core .= str_replace( $find, $repl, $icon_wrapper );
			}
		} else {
			global $dg_gallery_style;

			$count = count( $this->docs );
			$cols  = ! is_null( $this->atts['columns'] ) ? $this->atts['columns'] : $count;

			if ( apply_filters( 'dg_use_default_gallery_style', true ) ) {
				$itemwidth = $cols > 0 ? ( floor( 100 / $cols ) - 1 ) : 100;
				$core .= "<style type='text/css'>#$selector .document-icon{width:$itemwidth%}</style>";
			}

			for ( $i = 0; $i < $count; $i += $cols ) {
				$repl[1] = '';

				$min = min( $i + $cols, $count );
				for ( $x = $i; $x < $min; $x ++ ) {
					$repl[1] .= $this->docs[ $x ];
				}

				$core .= str_replace( $find, $repl, $icon_wrapper );
			}
		}

		// allow user to wrap gallery output
		$gallery = apply_filters( 'dg_gallery_template', '%rows%', $this->useDescriptions() );

		return self::$comment . str_replace( '%rows%', $core, $gallery );
	}
}