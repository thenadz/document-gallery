<?php
defined( 'WPINC' ) OR exit;

class DG_DefaultThumber extends DG_AbstractThumber {

	/**
	 * @var DG_DefaultThumber The singleton instance.
	 */
	private static $instance;

	/**
	 * @return DG_DefaultThumber The singleton instance.
	 */
	public static function getInstance() {
		return isset( self::$instance ) ? self::$instance : ( self::$instance = new DG_DefaultThumber() );
	}

	/**
	 * Get thumbnail URL for document with given ID from default images.
	 *
	 * @param string $ID The attachment ID to retrieve thumbnail from.
	 * @param int $pg Unused.
	 *
	 * @return string     URL to thumbnail.
	 */
	public function getThumbnail( $ID, $pg = 1 ) {
		static $image_exts = array( 'jpg', 'jpeg', 'gif', 'png' );
		$icon_url = DG_URL . 'assets/icons/';
		$ext = self::getExt( wp_get_attachment_url( $ID ) );

		// handle images
		if ( in_array( $ext, $image_exts ) && ( $icon = self::getImageThumbnail( $ID ) ) ) {
			// Nothing to do
		} elseif ( $name = self::getDefaultIcon( $ext ) ) {
			// try DG custom default icons first
			$icon = $icon_url . $name;
			// then fall back to standard WP default icons
		} elseif ( ! $icon = wp_mime_type_icon( $ID ) ) {
			// everything failed. This is bad...
			$icon = $icon_url . 'missing.png';
		}

		return $icon;
	}

	/**
	 * @param string $ID The attachment ID to retrieve thumbnail from.
	 *
	 * @return bool|string  False on failure, URL to thumb on success.
	 */
	private static function getImageThumbnail( $ID ) {
		$options = DG_Thumber::getOptions();
		$ret     = false;

		if ( $icon = image_downsize( $ID, array( $options['width'], $options['height'] ) ) ) {
			$ret = $icon[0];
		}

		return $ret;
	}

	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		return self::getAllExts();
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority() {
		return 0;
	}

	/**
	 * Returns the name of the image to represent the filetype given.
	 *
	 * @param string $ext
	 *
	 * @return string Default icon based on extension.
	 */
	private static function getDefaultIcon( $ext ) {
		// Maps file ext to default image name.
		static $exts = array(
			// Most Common First
			'pdf'                                                      => 'pdf.png',
			// MS Office
			'doc|docx|docm|dotx|dotm'                                  => 'msdoc.png',
			'ppt|pot|pps|pptx|pptm|ppsx|ppsm|potx|potm|ppam|sldx|sldm' => 'msppt.png',
			'xla|xls|xlt|xlw|xlsx|xlsm|xlsb|xltx|xltm|xlam'            => 'msxls.png',
			'mdb'                                                      => 'msaccess.png',
			// iWork
			'key'                                                      => 'key.png',
			'numbers'                                                  => 'numbers.png',
			'pages'                                                    => 'pages.png',
			// Images
			'jpg|jpeg|jpe|gif|png|bmp|tif|tiff|ico'                    => 'image.png',
			// Video formats
			'asf|asx|wmv|wmx|wm|avi|divx|flv|mov'                      => 'video.png',
			'qt|mpeg|mpg|mpe|mp4|m4v|ogv|webm|mkv'                     => 'video.png',
			// Audio formats
			'mp3|m4a|m4b|ra|ram|wav|ogg|oga|wma|wax|mka'               => 'audio.png',
			'midi|mid'                                                 => 'midi.png',
			// Text formats
			'txt|tsv|csv'                                              => 'text.png',
			'rtx'                                                      => 'rtx.png',
			'rtf'                                                      => 'rtf.png',
			'ics'                                                      => 'ics.png',
			'wp|wpd'                                                   => 'wordperfect.png',
			// Programming
			'html|htm'                                                 => 'html.png',
			'css'                                                      => 'css.png',
			'js'                                                       => 'javascript.png',
			'class'                                                    => 'java.png',
			'asc'                                                      => 'asc.png',
			'c'                                                        => 'c.png',
			'cc|cpp'                                                   => 'cpp.png',
			'h'                                                        => 'h.png',
			// Msc application formats
			'zip|tar|gzip|gz|bz2|tgz|7z|rar'                           => 'compressed.png',
			'exe'                                                      => 'exec.png',
			'swf'                                                      => 'shockwave.png',
			// OpenDocument formats
			'odt'                                                      => 'opendocument-text.png',
			'odp'                                                      => 'opendocument-presentation.png',
			'ods'                                                      => 'opendocument-spreadsheet.png',
			'odg'                                                      => 'opendocument-graphics.png',
			'odb'                                                      => 'opendocument-database.png',
			'odf'                                                      => 'opendocument-formula.png'
		);

		foreach ( $exts as $ext_preg => $icon ) {
			$ext_preg = '!(' . $ext_preg . ')$!i';
			if ( preg_match( $ext_preg, $ext ) ) {
				return $icon;
			}
		}

		return false;
	}
}