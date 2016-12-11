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
	 * @return string[]|bool The formats supported by Imagick, or false
	 */
	public static function query_formats() {
		try {
			return @Imagick::queryFormats();
		} catch ( Exception $ex ) {
			return false;
		}
	}

	/**
	 * TODO: Can be removed once WP < 4.7 is no longer supported
	 *
	 * Checks to see if current environment supports Imagick.
	 *
	 * We require Imagick 2.2.0 or greater, based on whether the queryFormats()
	 * method can be called statically.
	 *
	 * @since 3.5.0
	 *
	 * @static
	 * @access public
	 *
	 * @param array $args
	 * @return bool
	 */
	public static function test( $args = array() ) {
		global $wp_version;
		if ( version_compare( $wp_version, '4.7.0', '>=' ) ) {
			return parent::test( $args );
		} else {
			return parent::test( $args ) && in_array( 'readimage', array_map( 'strtolower', get_class_methods( 'Imagick' ) ) );
		}
	}

	/**
	 * Loads image from $this->file into new Imagick Object.
	 *
	 * @since 3.5.0
	 * @access protected
	 *
	 * @return true|WP_Error True if loaded; WP_Error on failure.
	 */
	public function load() {
		if ( $this->image instanceof Imagick ) {
			return true;
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.7.0', '>=' ) ) {
			$ret = parent::load();
		} else {
			// TODO: Can be removed once WP < 4.7 is no longer supported

			if ( ! is_file( $this->file ) && ! preg_match( '|^https?://|', $this->file ) )
				$ret = new WP_Error( 'error_loading_image', __('File doesn&#8217;t exist?'), $this->file );

			/*
			 * Even though Imagick uses less PHP memory than GD, set higher limit
			 * for users that have low PHP.ini limits.
			 */
			if ( version_compare( $wp_version, '4.6.0', '>=' ) ) {
				wp_raise_memory_limit( 'image' );
			} else {
				// TODO: Can be removed once WP < 4.6 is no longer supported
				@ini_set( 'memory_limit', apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );
			}

			try {
				$this->image = new Imagick();
				$file_parts = pathinfo( $this->file );
				$filename = $this->file;

				if ( 'pdf' == strtolower( $file_parts['extension'] ) ) {
					$filename = $this->pdf_setup();
				}

				// Reading image after Imagick instantiation because `setResolution`
				// only applies correctly before the image is read.
				$this->image->readImage( $filename );

				if ( ! $this->image->valid() ) {
					$ret = new WP_Error( 'invalid_image', __( 'File is not an image.' ), $this->file );
				} else {
					$this->mime_type = $this->get_mime_type( $this->image->getImageFormat() );
				}
			}
			catch ( Exception $e ) {
				$ret = new WP_Error( 'invalid_image', $e->getMessage(), $this->file );
			}

			if ( ! isset( $ret ) ) {
				$updated_size = $this->update_size();
				if ( is_wp_error( $updated_size ) ) {
					$ret = $updated_size;
				} else {
					$ret = $this->set_quality();
				}
			}
		}

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
	 * Sets up Imagick for PDF processing.
	 * Increases rendering DPI and only loads first page.
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @return string|WP_Error File to load or WP_Error on failure.
	 */
	protected function pdf_setup() {
		$page = !is_null($this->pg) ? "[{$this->pg}]" : '[0]';

		global $wp_version;
		if ( version_compare( $wp_version, '4.7.0', '>=' ) ) {
			$ret = rtrim( parent::pdf_setup(), '[0]' ) . $page;
		} else {
			// TODO: Can be removed once WP < 4.7 is no longer supported

			try {
				// By default, PDFs are rendered in a very low resolution.
				// We want the thumbnail to be readable, so increase the rendering DPI.
				$this->image->setResolution( 128, 128 );

				// Only load the first page.
				$ret = $this->file . $page;
			}
			catch ( Exception $e ) {
				$ret = new WP_Error( 'pdf_setup_failed', $e->getMessage(), $this->file );
			}
		}

		if ( is_wp_error( $ret ) ) {
			DG_Logger::writeLog( DG_LogLevel::Error, $ret->get_error_code() . ': ' . $ret->get_error_message() );
		}

		return $ret;
	}
}