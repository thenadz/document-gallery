<?php
defined( 'WPINC' ) OR exit;

class DG_AudioVideoThumber extends DG_AbstractThumber {

	/**
	 * Initialize the thumber class for use in thumbnail generation.
	 */
	public static function init() {
		$options  = DG_Thumber::getOptions();
		$active   = $options['active'];
		if ( $active['av'] ) {
			parent::init();
		}
	}

	/**
	 * Uses wp_read_video_metadata() and wp_read_audio_metadata() to retrieve
	 * an embedded image to use as a thumbnail.
	 *
	 * @param string $ID The attachment ID to retrieve thumbnail from.
	 * @param int $pg Unused.
	 *
	 * @return bool|string  False on failure, URL to thumb on success.
	 */
	public function getThumbnail( $ID, $pg = 1 ) {
		include_once DG_WPADMIN_PATH . 'includes/media.php';

		$doc_path   = get_attached_file( $ID );
		$mime_type = get_post_mime_type( $ID );

		if ( DG_Util::startsWith( $mime_type, 'video/' ) ) {
			$metadata = wp_read_video_metadata( $doc_path );
		} elseif ( DG_Util::startsWith( $mime_type, 'audio/' ) ) {
			$metadata = wp_read_audio_metadata( $doc_path );
		}

		// unsupported mime type || no embedded image present
		if ( ! isset( $metadata ) || empty( $metadata['image']['data'] ) ) {
			return false;
		}

		$ext = 'jpg';
		switch ( $metadata['image']['mime'] ) {
			case 'image/gif':
				$ext = 'gif';
				break;
			case 'image/png':
				$ext = 'png';
				break;
		}

		$temp_file = DG_Util::getTempFile( $ext );

		if ( ! $fp = @fopen( $temp_file, 'wb' ) ) {
			DG_Logger::writeLog( DG_LogLevel::Error, __( 'Could not open file: ', 'document-gallery' ) . $temp_file );

			return false;
		}

		if ( ! @fwrite( $fp, $metadata['image']['data'] ) ) {
			DG_Logger::writeLog( DG_LogLevel::Error, __( 'Could not write file: ', 'document-gallery' ) . $temp_file );
			fclose( $fp );

			return false;
		}

		fclose( $fp );

		return $temp_file;
	}

	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		return array_merge( wp_get_audio_extensions(), wp_get_video_extensions() );
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority() {
		return 95;
	}
}

DG_AudioVideoThumber::init();