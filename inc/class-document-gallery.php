<?php

/**
 * DocumentGallery wraps core functionality involved in plugin setup.
 *
 * @author drossiter
 */
class DocumentGallery {

	/*==========================================================================
		* THE SHORTCODE
	   *=========================================================================*/

	/**
	 * Takes values passed from attributes and returns suitable HTML to represent
	 * all valid attachments requested.
	 *
	 * @param array $atts Arguments from the user.
	 *
	 * @return string HTML for the Document Gallery.
	 */
	public static function doShortcode( $atts ) {
		include_once DG_PATH . 'inc/class-gallery.php';

		$start   = microtime( true );
		$gallery = (string) new DG_Gallery( $atts );
		DG_Logger::writeLog( DG_LogLevel::Detail, 'Generation Time: ' . sprintf( '%.2f', ( microtime( true ) - $start ) ) . ' s' );

		return $gallery;
	}

	/**
	 * Enqueue standard DG CSS.
	 */
	public static function enqueueGalleryStyle() {
		wp_enqueue_style( 'document-gallery', DG_URL . 'assets/css/style.min.css', null, DG_VERSION );
	}

	/**
	 * Enqueue script for Document Gallery frontend.
	 */
	public static function enqueueGalleryScript() {
		wp_enqueue_script( 'document-gallery', DG_URL . 'assets/js/gallery.min.js', array( 'jquery' ), DG_VERSION );
	}

	/**
	 * Prints user's custom CSS.
	 */
	public static function printCustomStyle() {
		global $dg_options;

		if ( ! empty( $dg_options['css']['text'] ) ) {
			echo '<style type="text/css">' . $dg_options['css']['text'] . '</style>' . PHP_EOL;
		}

		// need AJAX URL variable in frontend
		?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>
		<?php
	}

	/*==========================================================================
		* I18n
	   *=========================================================================*/

	/**
	 * Loads language files into WP core.
	 */
	public static function loadTextDomain() {
		load_plugin_textdomain( 'document-gallery', false, dirname( DG_BASENAME ) . '/languages/' );
	}

	/*==========================================================================
		* HELPER FUNCTIONS
	   *=========================================================================*/

	/**
	 * Accepts AJAX request containing list of IDs to be generated and returned.
	 */
	public static function ajaxGenerateIcons() {
		$ret = array();

		if ( array_key_exists( 'ids', $_REQUEST ) ) {
			foreach ($_REQUEST['ids'] as $id) {
				$ret[$id] = DG_Thumber::getThumbnail($id);
			}
		}

		wp_send_json($ret);
	}

	/**
	 * @param int $blog ID of the blog to be retrieved in multisite env.
	 *
	 * @return array Options for the blog.
	 */
	public static function getOptions( $blog = null ) {
		global $dg_options;

		return is_null( $blog )
			? $dg_options
			: get_blog_option( $blog, DG_OPTION_NAME, null );
	}

	/**
	 * @param array $options
	 * @param int $blog ID of the blog to be set in multisite env.
	 */
	public static function setOptions( $options, $blog = null ) {
		if ( is_null( $blog ) ) {
			global $dg_options;
			update_option( DG_OPTION_NAME, $options );
			$dg_options = $options;
		} else {
			update_blog_option( $blog, DG_OPTION_NAME, $options );
		}
	}

	/**
	 * @param int $blog ID of the blog to be deleted in multisite env.
	 */
	public static function deleteOptions( $blog = null ) {
		if ( is_null( $blog ) ) {
			delete_option( DG_OPTION_NAME );
		} else {
			delete_blog_option( $blog, DG_OPTION_NAME );
		}
	}

	/**
	 * Adds hook to validate DG options every time save is attempted.
	 */
	public static function addValidation() {
		add_filter( 'pre_update_option_' . DG_OPTION_NAME, array(
			__CLASS__,
			'validateOptionsStructure'
		), 10, 2 );
	}

	/**
	 * Checks whether the given options match the option schema.
	 *
	 * @param array $new The new options to be validated.
	 * @param array $old The old options.
	 *
	 * @return array The options to be saved.
	 */
	public static function validateOptionsStructure( $new, $old ) {
		if ( self::isValidOptionsStructure( $new ) ) {
			$ret = $new;
		} else {
			$ret = $old;
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				'Attempted to save invalid options.' . PHP_EOL . preg_replace( '/\s+/', ' ', print_r( $new, true ) ),
				true,
				true );
		}

		return $ret;
	}

	/**
	 * @param array|mixed $o The options structure to validate.
	 * @param array $schema The schema to validate against (note that only keys matter -- non-array values are ignored).
	 *
	 * @return bool Whether the given options structure matches the schema.
	 */
	public static function isValidOptionsStructure( $o, $schema = null ) {
		if ( is_null( $schema ) ) {
			$schema = DG_Setup::getDefaultOptions( true );
		}

		// simple checks first
		$valid = is_array( $o ) && ( count( $schema ) === count( $o ) );

		if ( $valid ) {
			foreach ( $schema as $sk => $sv ) {
				$valid = array_key_exists( $sk, $o );
				if ( is_array( $sv ) && ! empty( $sv ) ) {
					$valid = $valid && self::isValidOptionsStructure( $o[ $sk ], $sv );
				}

				if ( ! $valid ) {
					break;
				}
			}
		}

		return $valid;
	}

	/**
	 * Function takes a GMT timestamp and returns a date/time string in the
	 * current timezone and WP format.
	 *
	 * @param int $timestamp The GMT timestamp to translate.
	 *
	 * @return string The local time in the WP date/time format.
	 */
	public static function localDateTimeFromTimestamp( $timestamp ) {
		static $gmt_offet = null;
		static $wp_date_format = null;
		static $wp_time_format = null;
		if ( is_null( $gmt_offet ) ) {
			$gmt_offet      = get_option( 'gmt_offset' );
			$wp_date_format = get_option( 'date_format' );
			$wp_time_format = get_option( 'time_format' );
		}

		return '<span class="nowrap">' . date_i18n( $wp_date_format, $timestamp + $gmt_offet * 3600 ) . '</span> <span class="nowrap">' . date_i18n( $wp_time_format, $timestamp + $gmt_offet * 3600 ) . '</span>';
	}

	/**
	 * Blocks instantiation. All functions are static.
	 */
	private function __construct() {

	}
}