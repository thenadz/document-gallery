<?php
defined( 'WPINC' ) OR exit;

/**
 * General utility function for Document Gallery.
 *
 * @author drossiter
 */
class DG_Util {
	/**
	 * @var callable Either native JSON encode or custom JSON encode if needed.
	 */
	private static $nativeJsonEncode;

	/**
	 * Wraps JSON encoding functionality, utilizing native functions if available.
	 *
	 * @param unknown $decoded Value to be encoded.
	 *
	 * @return string The JSON string.
	 */
	public static function jsonEncode( $decoded ) {
		if ( ! isset( self::$nativeJsonEncode ) ) {
			self::$nativeJsonEncode = function_exists( 'json_encode' );
		}

		// do encoding
		return self::$nativeJsonEncode ? json_encode( $decoded ) : self::_jsonEncode( $decoded );
	}

	/**
	 * Home-made JSON encode to replace missing json_encode when needed.
	 *
	 * @param unknown $decoded Value to be encoded.
	 *
	 * @return string The JSON string.
	 */
	private static function _jsonEncode( $decoded ) {
		if ( self::isJsonObj( $decoded ) ) {
			$ret   = '';
			$first = true;
			foreach ( $decoded as $k => $v ) {
				if ( ! $first ) {
					$ret .= ',';
				}
				$ret .= "\"$k\":" . self::_jsonEncode( $v );
				$first = false;
			}

			return "\{$ret\}";
		} elseif ( is_array( $decoded ) ) {
			return '[' . implode( ',', array_map( array( __CLASS__, __FUNCTION__ ), $decoded ) ) . ']';
		} elseif ( is_bool( $decoded ) ) {
			static $boolMap = array( 'false', 'true' );

			return $boolMap[ (int) $decoded ];
		} elseif ( is_string( $decoded ) ) {
			return '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $decoded ) . '"';
		}

		return (string) $decoded;
	}

	/**
	 * Returns true for PHP objects and associative arrays.
	 *
	 * @param unknown $decoded Value to be checked.
	 *
	 * @return bool Whether passed value should be encoded as a JSON object.
	 */
	private static function isJsonObj( $decoded ) {
		$ret = is_object( $decoded );

		if ( ! $ret && is_array( $decoded ) ) {
			$next = 0;
			foreach ( array_keys( $decoded ) as $k ) {
				if ( $next ++ !== $k ) {
					$ret = true;
					break;
				}
			}
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
	 * Converts provided value to bool.
	 *
	 * @param unknown $val To be converted.
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
}