<?php
defined( 'WPINC' ) OR exit;

/**
 * General utility function for Document Gallery.
 *
 * @author drossiter
 */
class DG_Util {

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