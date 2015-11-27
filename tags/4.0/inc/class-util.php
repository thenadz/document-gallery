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
	 * @return array All blog IDs.
	 */
	public static function getBlogIds() {
		global $wpdb;
		return $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	}

	/**
	 * @param mixed $maybeint Data you wish to have converted to a positive integer.
	 *
	 * @return int A positive integer.
	 */
	public static function posint($maybeint) {
		return max(absint($maybeint), 1);
	}

	/**
	 * Converts provided value to bool.
	 *
	 * @param mixed $val To be converted.
	 * @param bool|NULL $default The value to return if unable to parse $val.
	 *
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
	 * in WP_DEBUG mode to make troubleshooting easier.
	 *
	 * @param $handle string Unique identifier for the script/style.
	 * @param $src string Relative path to asset from DG_URL.
	 * @param array $deps Any assets depended on by asset to be enqueued.
	 * @param bool $in_footer For scripts, dictates whether to put in footer.
	 */
	public static function enqueueAsset( $handle, $src, $deps = array(), $in_footer = true ) {
		$src = self::getAssetPath( $src );
		if ( stripos( strrev( $src ), 'sj.' ) === 0 ) {
			wp_enqueue_script( $handle, $src, $deps, DG_VERSION, $in_footer );
		} else {
			wp_enqueue_style( $handle, $src, $deps, DG_VERSION );
		}
	}
	
	/**
	 * Converts path to min version when WP is not running in debug mode and fully-qualifies path.
	 *
	 * @param $src string Relative path to asset from DG_URL.
	 * @return string The fully-qualified, potentially min version of the given path.
	 */
	public static function getAssetPath( $src ) {
		if ( !defined('WP_DEBUG') || !WP_DEBUG ) {
			$src = preg_replace( '/^(.*)\.(css|js)$/', '$1.min.$2', $src, 1 );
		}
		return DG_URL . $src;
	}
}