<?php
defined( 'WPINC' ) OR exit;

include_once DG_PATH . 'inc/class-image-editor-imagick.php';

DG_ImagickThumber::init();

class DG_ImagickThumber extends DG_AbstractThumber {

	/**
	 * Initialize the thumber class for use in thumbnail generation.
	 */
	public static function init() {
		$options  = DG_Thumber::getOptions();
		$active   = $options['active'];
		if ( $active['imagick'] && self::isImagickAvailable() ) {
			parent::init();
		}
	}

	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		if ( ! ($ret = DG_Image_Editor_Imagick::query_formats()) ) {
			$ret = array();
		}

		return $ret;
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

		$temp_file = self::getTempFile();

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