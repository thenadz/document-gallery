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
	 * Whether the given attachment has a thumbanil graphic.
	 *
	 * @param $ID int The id of the attachment to be checked.
	 * @return bool Whether the given attachment has a thumbnail image.
	 */
	public static function hasThumb($ID) {
		$options = DocumentGallery::getOptions();
		$thumbs = $options['thumber']['thumbs'];
		return array_key_exists($ID, $thumbs) && ! empty( $thumbs[ $ID ]['thumber'] );
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
}