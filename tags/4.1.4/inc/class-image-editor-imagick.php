<?php
defined( 'WPINC' ) OR exit;

include_once DG_WPINC_PATH . 'class-wp-image-editor.php';
include_once DG_WPINC_PATH . 'class-wp-image-editor-imagick.php';

/**
 * Extends WP_Image_Editor_Imagick to support specifying which
 * page to open for an Imagick file.
 *
 * @author drossiter
 */
class DG_Image_Editor_Imagick extends WP_Image_Editor_Imagick {
	/**
	 * @var int The 0-indexed pg number
	 */
	private $pg;

	/**
	 * @param string $file
	 * @param int $pg The 0-indexed pg number to edit.
	 */
	public function __construct( $file, $pg = null ) {
		parent::__construct( $file );
		$this->pg = $pg;
	}

	/**
	 * Loads the filepath into Imagick object.
	 */
	public function load() {
		$ret = parent::load();

		// set correct page number
		if ( ! is_null( $this->pg ) && ! is_wp_error( $ret )
		     && is_callable( array( $this->image, 'setIteratorIndex' ) ) ) {
			$err = __( 'Failed to set Imagick page number', 'document-gallery' );

			// setIteratorIndex() should return false on failure, but I've found
			// reports of it throwing an error so handling both cases.
			// NOTE: I've also seen it fail and return true, so we may not
			// log anything on failure...
			try {
				if ( ! $this->image->setIteratorIndex( $this->pg ) ) {
					DG_Logger::writeLog( DG_LogLevel::Error, $err . '.' );
				}
			} catch ( Exception $e ) {
				DG_Logger::writeLog( DG_LogLevel::Error, $err . ': ' . $e->getMessage() );
			}
		}

		return $ret;
	}

	/**
	 * @return string[]|bool The formats supported by Imagick, or false
	 */
	public static function query_formats() {
		try {
			return @Imagick::queryFormats();
		} catch ( Exception $ex ) {
			return false;
		}
	}
}