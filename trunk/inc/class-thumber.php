<?php
defined( 'WPINC' ) OR exit;

// NOTE: Child thumbers are included at bottom of this file
include_once DG_PATH . 'inc/thumbers/class-abstract-thumber.php';

/**
 * Thumber wraps the functionality required to
 * generate thumbnails for arbitrary documents.
 *
 * @author drossiter
 */
class DG_Thumber extends DG_AbstractThumber {

	/**
	 * @var DG_Thumber The singleton instance.
	 */
	private static $instance;

	/**
	 * @return DG_Thumber The singleton instance.
	 */
	public static function getInstance() {
		return isset( self::$instance ) ? self::$instance : ( self::$instance = new DG_Thumber() );
	}

	/**
	 * Wraps generation of thumbnails for various attachment filetypes.
	 *
	 * @param int $ID Document ID
	 * @param int $pg Page number to get thumb from.
	 * @param bool $generate_if_missing Whether to attempt generating the thumbnail if missing.
	 * @param bool|null &$is_default Whether the returned URL points to a default icon.
	 *
	 * @return string URL to the thumbnail.
	 */
	public function getThumbnail( $ID, $pg = 1, $generate_if_missing = true, &$is_default = null ) {
		$options = self::getOptions();
		$dimensions = $options['width'] . 'x' . $options['height'];

		// if we haven't saved a thumb, generate one
		if ( ! DG_Thumb::thumbExists( $ID, $dimensions, false ) ) {
			// short-circuit generation if not required
			if ( ! $generate_if_missing ) {
				return null;
			}

			foreach ( self::getThumbers() as $thumber ) {
				if ( $thumber->supportsAttachment( $ID ) ) {
					if ( DG_Logger::logEnabled() ) {
						$toLog = sprintf( __( 'Attempting to generate thumbnail for attachment #%d with (%s)',
								'document-gallery' ), $ID, get_class( $thumber ) );
						DG_Logger::writeLog( DG_LogLevel::Detail, $toLog );
					}

					if ( $generated = self::thumbnailGenerationHarness( $thumber, $ID, $pg ) ) {
						break;
					}
				}
			}
		}

		$thumb = isset( $generated ) ? $generated : DG_Thumb::getThumb( $ID, $dimensions );
		$is_thumb = is_a( $thumb, 'DG_Thumb' );
		$is_default = ! $is_thumb || ! $thumb->isSuccess();
		if ( ! $is_thumb ) {
			$thumb = self::setThumbnailFailed( $ID );
		}

		return $thumb->isSuccess() ? $thumb->getUrl() : DG_DefaultThumber::getInstance()->getThumbnail( $ID, $pg );
	}

	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		throw new BadFunctionCallException( 'Not implemented.' );
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority() {
		throw new BadFunctionCallException( 'Not implemented.' );
	}

	/**
	 * Returns the default mapping of thumber slug to whether it is active or not.
	 *
	 * @param $skeleton bool When true, values that require computation will be
	 *        skipped. Useful when only structure of options is needed.
	 *
	 * @return bool[] The default thumbnail generation methods.
	 */
	public static function getDefaultThumbers( $skeleton = false ) {
		$gs_active = $imagick_active = null;
		if ( ! $skeleton ) {
			$gs_active         = (bool) DG_GhostscriptThumber::getGhostscriptExecutable();
			$imagick_active    = DG_ImagickThumber::isImagickAvailable();
		}

		return array(
			'av'         => true,
			'gs'         => $gs_active,
			'imagick'    => $imagick_active,
			'thumber-co' => false
		);
	}

	/**
	 * Sets the thumbnail for the given attachment ID.
	 *
	 * @param int $ID Document ID.
	 * @param string $path System path to thumbnail.
	 * @param string $generator Descriptor for generation method -- usually method name.
	 *
	 * @return DG_Thumb|bool Thumb on success, false on failure.
	 */
	public static function setThumbnail( $ID, $path, $generator = 'unknown' ) {
		return self::thumbnailGenerationHarness( $generator, $ID, $path );
	}

	/**
	 * Sets the thumbnail for the given attachment ID to a failed state.
	 *
	 * @param int $ID Document ID.
	 * @return DG_Thumb The failed thumbnail.
	 */
	public static function setThumbnailFailed( $ID ) {
		$options = self::getOptions();
		$thumb = new DG_Thumb();
		$thumb->setPostId( $ID );
		$thumb->setTimestamp( time() );
		$thumb->setDimensions( $options['width'] . 'x' . $options['height'] );
		$thumb->save();

		return $thumb;
	}

	/*==========================================================================
	 * GENERAL THUMBNAIL HELPER FUNCTIONS
	 *=========================================================================*/

	/**
	 * @return mixed[]|null Thumber options from DB or null if options not initialized.
	 */
	public static function getOptions( $blog = null ) {
		$options = DocumentGallery::getOptions( $blog );

		return $options['thumber'];
	}

	/**
	 * @return WP_Post[] Each attachment that has been processed.
	 */
	public static function getThumbed() {
		$thumbs = DG_Thumb::getThumbs();
		if ( ! count( $thumbs ) ) return array();

		$args = array(
			'post_type'     => 'attachment',
			'post_status'   => 'inherit',
			'post_per_page' => - 1,
			'post__in'      => array_keys( $thumbs )
		);

		return get_posts( $args );
	}

	/**
	 * @filter dg_thumbers Allows developers to filter the Thumbers used
	 * for specific filetypes. Index is the regex to match file extensions
	 * supported and the value is anything that can be accepted by call_user_func().
	 * The function must take two parameters, 1st is the int ID of the attachment
	 * to get a thumbnail for, 2nd is the page to take a thumbnail of
	 * (may not be relevant for some filetypes).
	 *
	 * @return DG_AbstractThumber[]
	 */
	private static function getThumbers() {
		static $thumbers = null;

		if ( is_null( $thumbers ) ) {
			// allow users to filter thumbers used
			$thumbers = apply_filters( 'dg_thumbers', array() );
			$thumbers = array_filter( $thumbers, array( __CLASS__, 'isThumber' ) );
			usort( $thumbers, array( 'DG_AbstractThumber', 'cmpThumberByPriority' ) );

			// log which thumbers are being used
			if ( DG_Logger::logEnabled() ) {
				if ( count( $thumbers ) > 0 ) {
					$names = array_map( 'get_class', $thumbers );
					$entry = __( 'Thumbnail Generators: ', 'document-gallery' ) . implode( ', ', $names );
				} else {
					$entry = __( 'No thumbnail generators enabled.', 'document-gallery' );
				}

				DG_Logger::writeLog( DG_LogLevel::Detail, $entry );
			}
		}

		return $thumbers;
	}

	/**
	 * @param $maybe_thumber mixed Variable to be tested.
	 * @return bool Whether given variable is a thumber.
	 */
	private static function isThumber( $maybe_thumber ) {
		$ret = is_a( $maybe_thumber, 'DG_AbstractThumber' );
		if ( !$ret ) {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				'Attempted to add non-DG_AbstractThumber in thumbnail generation: ' . print_r( $maybe_thumber, true ) );
		}

		return $ret;
	}

	/**
	 * Template that handles generating a thumbnail.
	 *
	 * If image has already been generated through other means, $pg may be set to the system path where the
	 * thumbnail is located. In this case, $generator will not be invoked, but *will* be kept for historical purposes.
	 *
	 * @param DG_AbstractThumber|string $generator Takes ID and pg and returns path to temp file or false.
	 * @param int $ID ID for the attachment that we need a thumbnail for.
	 * @param int|string $pg Page number of the attachment to get a thumbnail for or the system path to the image to be used.
	 *
	 * @return DG_Thumb|bool The generated thumbnail or false on failure.
	 */
	private static function thumbnailGenerationHarness( $generator, $ID, $pg = 1 ) {
		// handle system page in $pg variable
		if ( is_string( $pg ) && ! is_numeric( $pg ) ) {
			$temp_path = $pg;
		} elseif ( is_a( $generator, 'DG_AbstractThumber' ) ) {
			// delegate thumbnail generation to $generator
			if ( false === ( $temp_path = $generator->getThumbnail( $ID, $pg ) ) ) {
				return false;
			}

			// NOTE: get string representation to be stored with thumb in DB
			$generator = get_class( $generator );
		} else {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				'Attempted to call thumbnailGenerationHarness with invalid generator: ' . print_r( $generator, true ) );
			return false;
		}

		// get some useful stuff
		$doc_path = get_attached_file( $ID );
		$dirname  = dirname( $doc_path );
		$basename = basename( $doc_path );
		if ( false === ( $len = strrpos( $basename, '.' ) ) ) {
			$len = strlen( $basename );
		}
		$extless = substr( $basename, 0, $len );
		$ext     = self::getExt( $temp_path );

		$thumb_name = self::getUniqueThumbName( $dirname, $extless, $ext );
		$thumb_path = "$dirname/$thumb_name";

		// scale generated image down
		$img = wp_get_image_editor( $temp_path );

		if ( is_wp_error( $img ) ) {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				__( 'Failed to get image editor: ', 'document-gallery' ) . $img->get_error_message() );

			return false;
		}

		$options = self::getOptions();
		$img->resize( $options['width'], $options['height'], false );
		$err = $img->save( $thumb_path );

		if ( is_wp_error( $err ) ) {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				__( 'Failed to save image: ', 'document-gallery' ) .
				$err->get_error_message() );

			return false;
		}

		// do some cleanup
		@unlink( $temp_path );

		// save new thumb
		DG_Logger::writeLog( DG_LogLevel::Detail, 'Creating thumb object.' );
		$upload = wp_upload_dir();
		$thumb  = new DG_Thumb();
		$thumb->setPostId( $ID );
		$thumb->setDimensions( $options['width'] . 'x' . $options['height'] );
		$thumb->setTimestamp( time() );
		$thumb->setRelativePath( substr( $thumb_path, strlen( $upload['basedir'] ) + 1 ) );
		$thumb->setGenerator( $generator );
		$thumb->save();

		return $thumb;
	}

	/**
	 * Constructs name for file's thumbnail, ensuring that it does not conflict
	 * with any existing file.
	 *
	 * @param string $dirname Directory where the document is located.
	 * @param string $extless Base name, less the extension.
	 * @param string $ext The extension of the image to be created.
	 *
	 * @return string          Name unique within the directory given, derived from the basename given.
	 */
	private static function getUniqueThumbName( $dirname, $extless, $ext = 'png' ) {
		return wp_unique_filename( $dirname, str_replace( '.', '-', $extless ) . '-thumb.' . $ext );
	}
}

// include all internal DG thumbers
foreach ( glob( DG_PATH . 'inc/thumbers/*.php' ) as $path ) {
	/** @noinspection PhpIncludeInspection */
	include_once $path;
}