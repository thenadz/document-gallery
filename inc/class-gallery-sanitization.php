<?php
defined( 'WPINC' ) OR exit;

DG_GallerySanitization::init();

/**
 * Holds data specific to a given document gallery.
 *
 * @author drossiter
 */
class DG_GallerySanitization {

    /*==========================================================================
     * PRIVATE FIELDS
     *=========================================================================*/

    private static $unary_err, $binary_err;

    /**
     * @return string The unary error format string.
     */
    public static function getUnaryErr() {
        return self::$unary_err;
    }

    /**
     * @return string The binary error format string.
     */
    public static function getBinaryErr() {
        return self::$binary_err;
    }

    /*==========================================================================
     * INIT SANITIZATION
     *=========================================================================*/

    /**
     * Initializes static values for this class.
     */
    public static function init() {
        if ( ! isset( self::$unary_err ) ) {
            self::$unary_err  = __( 'The %s value entered, "%s", is not valid.', 'document-gallery' );
            self::$binary_err = __( 'The %s parameter may only be "%s" or "%s." You entered "%s."', 'document-gallery' );
        }
    }

    /*==========================================================================
     * SANITIZATION
     *=========================================================================*/

    /**
     * Sanitize the given key/value pair, passing any error to $errs if given.
     *
     * @param string $key The key to reference the current value in the defaults array.
     * @param mixed $value The value to be sanitized.
     * @param string[] $errs The array of errors, which will be appended with any errors found.
     *
     * @return mixed The sanitized value, falling back to the current default value when invalid value given.
     */
    public static function sanitizeParameter( $key, $value, &$errs = null ) {
        // all sanitize methods must be in the following form: sanitize<UpperCamelCaseKey>
        $funct    = $key;
        $funct[0] = strtoupper( $funct[0] );
        $funct    = 'sanitize' . preg_replace_callback( '/_([a-z])/', array( __CLASS__, 'secondCharToUpper' ), $funct );

        $callable = array( __CLASS__, $funct );

        // avoid looking for method beforehand unless we're running in debug mode -- expensive call
        if ( DG_Logger::logEnabled() && ! method_exists( __CLASS__, $funct ) ) {
            DG_Logger::writeLog(
                DG_LogLevel::Error,
                __( 'Attempted to call invalid function: ', 'document-gallery' ) . implode( '::', $callable ),
                true );
        }

        // call param-specific sanitization
        $ret = call_user_func_array( $callable, array( $value, &$err ) );

        // check for error and return default
        if ( isset( $err ) ) {
            $defaults = DG_Gallery::getOptions();
            $ret      = $defaults[ $key ];

            if ( ! is_null($errs) ) {
                $errs[ $key ] = $err;
            }
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The attachment_pg value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool The sanitized attachment_pg value.
     */
    private static function sanitizeAttachmentPg( $value, &$err ) {
        $ret = DG_Util::toBool( $value );

        if ( is_null( $ret ) ) {
            $err = sprintf( self::$binary_err, 'attachment_pg', 'true', 'false', $value );
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The columns value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return int The sanitized columns value.
     */
    public static function sanitizeColumns( $value, &$err ) {
        return $value != -1 ? absint( $value ) : null;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The descriptions value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool The sanitized descriptions value.
     */
    private static function sanitizeDescriptions( $value, &$err ) {
        $ret = DG_Util::toBool( $value );

        if ( is_null( $ret ) ) {
            $err = sprintf( self::$binary_err, 'descriptions', 'true', 'false', $value );
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The exclude value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool The sanitized exclude value.
     */
    private static function sanitizeExclude( $value, &$err ) {
        return self::sanitizeIdList( 'Exclude', $value, $err );
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The fancy value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool The sanitized fancy value.
     */
    private static function sanitizeFancy( $value, &$err ) {
        $ret = DG_Util::toBool( $value );

        if ( is_null( $ret ) ) {
            $err = sprintf( self::$binary_err, 'fancy', 'true', 'false', $value );
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The id value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return int The sanitized id value.
     */
    private static function sanitizeId( $value, &$err ) {
        return $value != -1 ? absint( $value ) : null;
    }

    /**
     * Takes the provided comma-delimited list of IDs and returns null if it is invalid.
     *
     * @param string $name Name of the value being sanitized. Used in error string when needed.
     * @param string $value The ids value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool|multitype:int The sanitized comma-delimited list of IDs value.
     */
    private static function sanitizeIdList( $name, $value, &$err ) {
        static $regex = '/(?:|\d+(?:,\d+)*)/';

        $ret = $value;

        if ( ! preg_match( $regex, $value ) ) {
            $err = sprintf( __( '%s may only be a comma-delimited list of integers.', 'document-gallery' ), $name );
            $ret = null;
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The ids value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool|multitype:int The sanitized ids value.
     */
    private static function sanitizeInclude( $value, &$err ) {
        return self::sanitizeIdList( 'Include', $value, $err );
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The limit value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return int The sanitized limit value.
     */
    private static function sanitizeLimit( $value, &$err ) {
        $ret = intval( $value );

        if ( is_null( $ret ) || $ret < -1 ) {
            $err = sprintf( self::$unary_err, 'limit', '>= -1' );
            $ret = null;
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The mime_types value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized mime_types value.
     */
    private static function sanitizeMimeTypes( $value, &$err ) {
        // TODO: do some actual sanitization...
        return $value;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The new_window value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return bool The sanitized new_window value.
     */
    private static function sanitizeNewWindow( $value, &$err ) {
        $ret = DG_Util::toBool( $value );

        if ( is_null( $ret ) ) {
            $err = sprintf( self::$binary_err, 'new_window', 'true', 'false', $value );
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The order value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized order value.
     */
    private static function sanitizeOrder( $value, &$err ) {
        $ret = strtoupper( $value );

        if ( ! in_array( $ret, self::getOrderOptions() ) ) {
            $err = sprintf( self::$binary_err, 'order', 'ASC', 'DESC', $value );
            $ret = null;
        }

        return $ret;
    }

    /**
     * @return string[] The valid options for order parameter.
     */
    public static function getOrderOptions() {
        return array( 'ASC', 'DESC' );
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The orderby value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized orderby value.
     */
    private static function sanitizeOrderby( $value, &$err ) {
        $ret = ( 'ID' === strtoupper( $value ) ) ? 'ID' : strtolower( $value );

        if ( ! in_array( $ret, self::getOrderbyOptions() ) ) {
            $err = sprintf( self::$unary_err, 'orderby', $value );
            $ret = null;
        }

        return $ret;
    }

    /**
     * @return string[] The valid options for orderby parameter.
     */
    public static function getOrderbyOptions() {
        return array(
            'author',
            'comment_count',
            'date',
            'ID',
            'menu_order',
            'modified',
            'name',
            'none',
            'parent',
            'post__in',
            'rand',
            'title'
        );
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The paginate value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized paginate value.
     */
    private static function sanitizePaginate( $value, &$err ) {
        $ret = DG_Util::toBool( $value );

        if ( is_null( $ret ) ) {
            $err = sprintf( self::$binary_err, 'paginate', 'true', 'false', $value );
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The post_status value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized post_status value.
     */
    private static function sanitizePostStatus( $value, &$err ) {
        $ret = preg_grep( '/^' . preg_quote( $value ) . '$/i', self::getPostStatuses() );
        $ret = reset( $ret );

        if ( $ret === false ) {
            $err = sprintf( self::$unary_err, 'post_status', $value );
        }

        return $ret;
    }

    /**
     * @return string[] All registered post statuses.
     */
    public static function getPostStatuses() {
        static $statuses;
        if ( ! isset( $statuses ) ) {
            $statuses   = get_post_stati();
            $statuses[] = 'any';
            asort( $statuses );
        }

        return $statuses;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The post_type value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized post_type value.
     */
    private static function sanitizePostType( $value, &$err ) {
        $ret = preg_grep( '/^' . preg_quote( $value ) . '$/i', self::getPostTypes() );
        $ret = reset( $ret );

        if ( $ret === false ) {
            $err = sprintf( self::$unary_err, 'post_type', $value );
        }

        return $ret;
    }

    /**
     * @return string[] All registered post types.
     */
    public static function getPostTypes() {
        static $types;
        if ( ! isset( $types ) ) {
            $types   = get_post_types();
            $types[] = 'any';
            asort( $types );
        }

        return $types;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The relation value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized relation value.
     */
    private static function sanitizeRelation( $value, &$err ) {
        $ret = strtoupper( $value );

        if ( ! in_array( $ret, self::getRelationOptions() ) ) {
            $err = sprintf( self::$binary_err, 'relation', 'AND', 'OR', $value );
            $ret = null;
        }

        return $ret;
    }

    /**
     * @return string[] The valid options for relation parameter.
     */
    public static function getRelationOptions() {
        return array( 'AND', 'OR' );
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $value The skip value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized skip value.
     */
    private static function sanitizeSkip( $value, &$err ) {
        $ret = intval( $value );

        if ( is_null( $ret ) || $ret < 0 ) {
            $err = sprintf( self::$unary_err, 'skip', '>= 0' );
            $ret = null;
        }

        return $ret;
    }

    /**
     * Takes the provided value and returns a sanitized value.
     *
     * @param string $operator The operator value to be sanitized.
     * @param string &$err String to be initialized with error, if any.
     *
     * @return string The sanitized operator value.
     */
    private static function sanitizeOperator( $operator, &$err ) {
        $ret = strtoupper( $operator );

        if ( ! in_array( $ret, self::getOperatorOptions() ) ) {
            $err = sprintf( self::$binary_err, 'IN", "NOT IN", "OR', 'AND', $operator );
            $ret = null;
        } else if ( $ret === 'OR' ) {
            $ret = 'IN';
        }

        return $ret;
    }

    /**
     * @return string[] The valid options for *_relation/*_operator parameter.
     */
    public static function getOperatorOptions() {
        return array( 'IN', 'NOT IN', 'AND', 'OR' );
    }

    /**
     * @param string $string To take second char from.
     *
     * @return string Capitalized second char of given string.
     */
    private static function secondCharToUpper( $string ) {
        return strtoupper( $string[1] );
    }
}