<?php
defined( 'WPINC' ) OR exit;

abstract class DG_AbstractThumber {

	/**
	 * @var DG_AbstractThumber[] The singleton instances, keyed by class name.
	 */
	protected static $instances = array();

	/**
	 * Blocks external instantiation. Thumbers only require a singleton instance.
	 */
	protected function __construct() {
	}

	/**
	 * Initialize the thumber class for use in thumbnail generation.
	 *
	 * @return DG_AbstractThumber The instance for the calling class.
	 */
	public static function init() {
		$class = get_called_class();
		if ( ! isset( self::$instances[$class] ) ) {
			try {
				self::$instances[$class] = new static();
				add_action( 'dg_thumbers', array( $class, 'thumbersFilter' ), 0 );
			} catch ( Exception $e ) {
				DG_Logger::writeLog( DG_LogLevel::Error, "Failed to construct thumber of type $class." );
			}
		}

		return isset( self::$instances[$class] ) ? self::$instances[$class] : null;
	}

	/**
	 * Adds the singleton instance for the calling class as a thumber.
	 *
	 * @param $thumbers DG_AbstractThumber[] The thumbers being filtered.
	 * @return DG_AbstractThumber[] The filtered thumbers.
	 */
	public static function thumbersFilter( $thumbers ) {
		$thumbers[] = static::$instances[get_called_class()];
		return $thumbers;
	}

	/**
	 * @return string[] The extensions supported by this thumber in lowercase.
	 */
	protected abstract function getThumberExtensions();

	/**
	 * @param int $ID The attachment ID to retrieve thumbnail from.
	 * @param int $pg Unused.
	 * @return bool|string  False on failure, URL to thumb on success.
	 */
	public abstract function getThumbnail( $ID, $pg = 1 );

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public abstract function getPriority();

	/**
	 * @param int $ID The attachment ID to retrieve thumbnail from.
	 * @return bool Whether the attachment is supported by this thumber.
	 */
	public function supportsAttachment( $ID ) {
		return in_array( strtolower( self::getAttachmentExt( $ID ) ), $this->getThumberExtensions() );
	}

	/**
	 * Used in sorting an array of thumbers.
	 *
	 * @param $t1 DG_AbstractThumber First thumber.
	 * @param $t2 DG_AbstractThumber Second thumber.
	 * @return int Negative if $t1 has a higher priority, positive if $t1 has a lower priority.
	 */
	public static function cmpThumberByPriority( $t1, $t2 ) {
		return $t2->getPriority() - $t1->getPriority();
	}

	/**
	 * Checks whether exec() may be used.
	 * Source: http://stackoverflow.com/a/12980534/866618
	 *
	 * @return bool Whether exec() is available.
	 */
	public static function isExecAvailable() {
		static $available = null;

		if ( is_null( $available ) ) {
			$available = true;

			if ( ini_get( 'safe_mode' ) ) {
				$available = false;
			} else {
				$d = ini_get( 'disable_functions' );
				$s = ini_get( 'suhosin.executor.func.blacklist' );
				if ( "$d$s" ) {
					$array     = preg_split( '/,\s*/', "$d,$s" );
					$available = ! in_array( 'exec', $array );
				}
			}
		}

		return $available;
	}

	/**
	 * @param $ID int The attachment ID.
	 * @return bool|string The attachment extension on success, false on failure.
	 */
	protected static function getAttachmentExt( $ID ) {
		return self::getExt( get_attached_file( $ID ) );
	}

	/**
	 * Formerly achieved with wp_check_filetype(), but it was only returning
	 * valid results if the active user had permission to upload the given filetype.
	 *
	 * @param string $filename Name of the file to get extension from.
	 *
	 * @return bool|string Returns the file extension on success, false on failure.
	 */
	protected static function getExt( $filename ) {
		if ( $ext = pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$res = preg_grep( '/^(?:.*\|)?' . $ext . '(?:\|.*)?$/i', self::getAllExts() );
			$res = reset( $res );
			if ( $res === false ) {
				$ext = false;
			}
		}

		if ( ! $ext && ( $info = getimagesize( $filename ) ) && ( $ext = image_type_to_extension( $info[2], false ) ) ) {
			return $ext;
		}

		return $ext;
	}

	/**
	 * Addresses issues with getting a complete list of supported MIME types as
	 * described in this issue: https://core.trac.wordpress.org/ticket/32544
	 * @return string[] Contains all MIME types supported by WordPress, including custom types added by plugins.
	 */
	protected static function getAllExts() {
		return array_keys( array_merge( wp_get_mime_types(), get_allowed_mime_types() ) );
	}
}