<?php
defined( 'WPINC' ) OR exit;

/**
 * All methods within this class may be considered "documented" and
 * developers may rely on their existence moving forward. If there is
 * a need to remove a method in this class, it will go through a
 * deprecation period before being removed.
 *
 * This guarantee is only provided for this file and class-logger. All
 * other methods throughout Document Gallery may be removed or
 * significantly changed from version-to-version and should not be relied
 * on in any way for external development.
 *
 * NOTE: If you are performing actions related to Document Gallery, you
 * you are encouraged to log important events through the Document Gallery
 * logging interface. This interface is available under the DG_Logger class.
 *
 * @author drossiter
 */
class DG_API {
	/**
	 * Sets the thumbnail for the given attachment ID.
	 *
	 * @param int $ID Document ID.
	 * @param string $path System path to thumbnail.
	 * @param string $generator Descriptor for generation method -- usually method name.
	 *
	 * @return bool Whether set was successful.
	 */
	public static function setThumbnail( $ID, $path, $generator = 'unknown' ) {
		include_once DG_PATH . 'inc/class-thumber.php';

		return DG_Thumber::setThumbnail( $ID, $path, $generator );
	}

	/**
	 * Sets the thumbnail for the given attachment ID to a failed state.
	 * This prevents the plugin attempting to generate a thumbnail for this
	 * plugin in the future.
	 *
	 * @param int $ID The attachment ID.
	 * @return DG_Thumb The failed thumb.
	 */
	public static function setThumbnailFailed( $ID ) {
		include_once DG_PATH . 'inc/class-thumber.php';

		return DG_Thumber::setThumbnailFailed( $ID );
	}

	/**
	 *
	 * @param int $ID The attachment ID.
	 * @param int $pg The page number to use (1-based numbering).
	 * @param bool $generate_if_missing Whether to generate the thumbnail if it has not yet been generated.
	 *
	 * @return string The URL for the thumbnail NULL. Note that if generate_if_missing
	 * is true then you will never get NULL -- you will get a default icon if generation fails.
	 */
	public static function getThumbnail( $ID, $pg = 1, $generate_if_missing = false ) {
		include_once DG_PATH . 'inc/class-thumber.php';

		return DG_Thumber::getInstance()->getThumbnail( $ID, $pg, $generate_if_missing );
	}

	/**
	 * Removes all metadata related to a thumbnail for the given attachment ID(s). This allows
	 * the plugin to attempt to re-generate the thumbnail for this attachment next time it
	 * is requested in a gallery or through some other means.
	 *
	 * @param int|int[] $ids Which thumbnails to delete.
	 */
	public static function deleteThumbnails( $ids ) {
		DG_Thumb::purgeThumbs( $ids );
	}
}