<?php
defined( 'WPINC' ) OR exit;

/**
 * General utility function for Document Gallery.
 *
 * @author drossiter
 */
class DG_Util {

	/**
	 * @param callable $callable The callable.
	 * @return string The string representation of the callable.
	 */
	public static function callableToString($callable) {
		$ret = $callable;
		if ( is_array( $callable ) ) {
			$sep = '::';
			if ( !is_string( $callable[0] ) ) {
				$callable[0] = get_class( $callable[0] );
				$sep = '->';
			}

			$ret = "{$callable[0]}$sep{$callable[1]}";
		}

		return $ret;
	}

	/**
	 * @return int[] All blog IDs.
	 */
	public static function getBlogIds() {
		global $wpdb;
		return $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	}

	/**
	 * Caller should handle removal of the temp file when finished.
	 *
	 * @param string $ext The extension to be given to the temp file.
	 *
	 * @return string A temp file with the given extension.
	 */
	public static function getTempFile( $ext = 'png' ) {
		static $base = null;
		static $tmp;

		if ( is_null( $base ) ) {
			$base = md5( time() );
			$tmp  = untrailingslashit( get_temp_dir() );
		}

		return $tmp . DIRECTORY_SEPARATOR . wp_unique_filename( $tmp, $base . '.' . $ext );
	}

	/**
	 * @param mixed $maybeint Data you wish to have converted to a positive integer.
	 * @return int A positive integer.
	 */
	public static function posint( $maybeint ) {
		return max( absint( $maybeint ), 1 );
	}

	/**
	 * Converts provided value to bool.
	 *
	 * @param mixed $val To be converted.
	 * @param bool|NULL $default The value to return if unable to parse $val.
	 * @return bool|NULL Bool value if can be parsed, else NULL.
	 */
	public static function toBool( $val, $default = null ) {
		if ( is_null( $val ) ) {
			return false;
		}

		if ( is_bool( $val ) ) {
			return $val;
		}

		if ( is_int( $val ) ) {
			if ( 1 === $val ) {
				return true;
			}

			if ( 0 === $val ) {
				return false;
			}
		}

		if ( is_string( $val ) ) {
			$val = strtolower( $val );
			if ( 'true' === $val || '1' === $val ) {
				return true;
			}

			if ( 'false' === $val || '0' === $val ) {
				return false;
			}
		}

		return $default;
	}

	/**
	 * Wrapper method which handles deciding whether to include minified assets. Minified files are not used
	 * in SCRIPT_DEBUG mode to make troubleshooting easier.
	 *
	 * @param $handle string Unique identifier for the script/style.
	 * @param $src string Relative path to asset from DG_URL.
	 * @param string[] $deps Any assets depended on by asset to be enqueued.
	 * @param bool $in_footer For scripts, dictates whether to put in footer.
	 */
	public static function enqueueAsset( $handle, $src, $deps = array(), $in_footer = true ) {
		$src = self::getAssetPath( $src );
		if ( self::endsWith( $src, '.js' ) ) {
			wp_enqueue_script( $handle, $src, $deps, DG_VERSION, $in_footer );
		} else {
			wp_enqueue_style( $handle, $src, $deps, DG_VERSION );
		}
	}
	
	/**
	 * Converts path to min version when WP is not running in debug mode and fully-qualifies path.
	 *
	 * @param $src string Relative path to non-minified asset from DG_URL.
	 * @return string The fully-qualified, potentially min version of the given path.
	 */
	public static function getAssetPath( $src ) {
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			$parts = explode( '.', $src );
			$src = $parts[0] . '.min.' . $parts[1];
		}
		return DG_URL . $src;
	}

	/**
	 * @param $haystack string The string to be tested.
	 * @param $needle string The value to be tested against.
	 * @return bool Whether $haystack starts with $needle.
	 */
	public static function startsWith( $haystack, $needle ) {
		return substr( $haystack, 0, strlen( $needle ) ) === $needle;
	}

	/**
	 * @param $haystack string The string to be tested.
	 * @param $needle string The value to be tested against.
	 * @return bool Whether $haystack ends with $needle.
	 */
	public static function endsWith( $haystack, $needle ) {
		return substr( $haystack, -strlen( $needle ) ) === $needle;
	}

	/**
	 * @return bool Whether the WP host is a public site accessible from the Internet.
	 */
	public static function isPublicSite() {
		$host = parse_url( site_url(), PHP_URL_HOST );
		$is_ip = filter_var( $host, FILTER_VALIDATE_IP );
		return $is_ip ? self::isPublicIp( $host ) : self::isPublicHostname( $host );
	}

	/**
	 * @param $ip string The IP address.
	 * @return bool Whether the given IP is public.
	 */
	private static function isPublicIp( $ip ) {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
				&& '127.0.0.1' !== $ip && '::1' !== $ip;
	}

	/**
	 * @param $hostname string The hostname to test.
	 * @return bool Whether the given hostname has at least one public IP address associated.
	 */
	private static function isPublicHostname( $hostname ) {
		$ret = false;
		foreach ( gethostbynamel( $hostname ) as $ip ) {
			if ( self::isPublicIp( $ip ) ) {
				$ret = true;
				break;
			}
		}

		return $ret;
	}
}