<?php
defined( 'WPINC' ) OR exit;

include_once DG_PATH . 'inc/class-image-editor-imagick.php';

class DG_ImagickThumber extends DG_AbstractThumber {

	/**
	 * @var string[] Supported file formats.
	 */
	private static $file_formats;

	/**
	 * Initialize the thumber class for use in thumbnail generation.
	 */
	public static function init() {
		$options  = DG_Thumber::getOptions();
		$active   = $options['active'];
		if ( $active['imagick'] && self::isImagickAvailable() ) {
			parent::init();

			if ( !(self::$file_formats = DG_Image_Editor_Imagick::query_formats()) ) {
				self::$file_formats = array();
			}
			$image_exts = array( 'jpg', 'jpeg', 'gif', 'png' );
			self::$file_formats = array_map( 'strtolower', array_diff( self::$file_formats, $image_exts ) );
		}
	}

	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		return self::$file_formats;
	}

	/**
	 * Uses WP_Image_Editor_Imagick to generate thumbnails.
	 *
	 * @param int $ID The attachment ID to retrieve thumbnail from.
	 * @param int $pg The page to get the thumbnail of.
	 *
	 * @return bool|string  False on failure, URL to thumb on success.
	 */
	public function getThumbnail($ID, $pg = 1) {
		$doc_path = get_attached_file( $ID );
		$img      = new DG_Image_Editor_Imagick( $doc_path, $pg - 1 );
		$err      = $img->load();
		if ( is_wp_error( $err ) ) {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				__( 'Failed to open file in Imagick: ', 'document-gallery' ) .
				$err->get_error_message() );

			return false;
		}

		$temp_file = DG_Util::getTempFile();

		$err = $img->save( $temp_file, 'image/png' );
		if ( is_wp_error( $err ) ) {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				__( 'Failed to save image in Imagick: ', 'document-gallery' ) .
				$err->get_error_message() );

			return false;
		}

		return $temp_file;
	}

	/**
	 * @return bool Whether WP_Image_Editor_Imagick can be used on this system.
	 */
	public static function isImagickAvailable() {
		static $ret = null;

		if ( is_null( $ret ) ) {
			$ret = WP_Image_Editor_Imagick::test();
		}

		return $ret;
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority()
	{
		return 50;
	}
}

DG_ImagickThumber::init();