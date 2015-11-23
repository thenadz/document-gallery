<?php

/**
 * Holds data specific to a specific thumbnail
 *
 * @author drossiter
 */
class DG_Thumb {

    /**
     * @var array The cached copy of thumbs.
     */
    private static $thumbs;

    /**
     * @var string The meta key identifying DG thumbs.
     */
    private static $meta_key = '_dg_thumbnail';

    /**
     * @var int The DB row ID.
     */
    private $meta_id;

    /**
     * @var int The post (attachment) ID.
     */
    private $post_id;

    /**
     * @return int The post ID.
     */
    public function getPostId() {
        return $this->post_id;
    }

    /**
     * @param $post_id int The post ID.
     */
    public function setPostId($post_id) {
        $this->post_id = $post_id;
    }

    /**
     * @var string The thumb dimensions (format: WIDTHxHEIGHT).
     */
    private $dimensions = '';

    /**
     * @return string The dimensions (WIDTHxHEIGHT).
     */
    public function getDimensions() {
        return $this->dimensions;
    }

    /**
     * @param $dimensions string The dimensions (WIDTHxHEIGHT).
     */
    public function setDimensions($dimensions) {
        $this->dimensions = $dimensions;
    }

    /**
     * @var string The path relative to the WP uploads directory.
     */
    private $relative_path = '';

    /**
     * @return string The path relative to the WP uploads directory.
     */
    public function getRelativePath() {
        return $this->relative_path;
    }

    /**
     * @param $relative_path string The path relative to the WP uploads directory.
     */
    public function setRelativePath($relative_path) {
        $this->relative_path = $relative_path;
    }

    /**
     * @var int The time when thumbnail was generated.
     */
    private $timestamp;

    /**
     * @return int The time when thumbnail was generated.
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * @param $timestamp int The time when thumbnail was generated.
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * @var string The function used in generating this thumb.
     */
    private $generator = '';

    /**
     * @return string The function used in generating this thumb.
     */
    public function getGenerator() {
        return $this->generator;
    }

    /**
     * @param $generator string The function used in generating this thumb.
     */
    public function setGenerator($generator) {
        $this->generator = $generator;
    }

    /**
     * DG_Thumb constructor.
     * @param $db_row object The result of querying WP postmeta.
     */
    public function __construct($db_row = null) {
        if ( is_null ($db_row) ) return;

        $this->meta_id = $db_row->meta_id;
        $this->post_id = $db_row->post_id;
        $this->setMetaValue( $db_row->meta_value );
    }

    /**
     * Meta value is in the following format: [width]x[height]:[timestamp]:[path]:[generator].
     * Note that generator may contain colons, so parsing of this field must be done carefully.
     *
     * @param $meta_value string Populates the dimensions and path from meta_value.
     */
    private function setMetaValue($meta_value) {
        $split = explode( ':', $meta_value, 4 );
        $this->dimensions = $split[0];
        $this->timestamp = absint($split[1]);
        $this->relative_path = $split[2];
        $this->generator = $split[3];
    }

    /**
     * Meta value is in the following format: [width]x[height]:[timestamp]:[path]:[generator].
     *
     * @return string The meta_value generated from the dimensions & path fields.
     */
    private function getMetaValue() {
        return $this->dimensions . ':' . $this->timestamp . ':' . $this->relative_path . ':' . $this->generator;
    }

    /**
     * @return int Parses dimensions string pulling out the width.
     */
    public function getWidth() {
        $split = explode( 'x', $this->dimensions );
        return absint( $split[0] );
    }

    /**
     * @return int Parses dimensions string pulling out the height.
     */
    public function getHeight() {
        $split = explode( 'x', $this->dimensions );
        return absint( $split[1] );
    }

    /**
     * @return string Get fully-qualified URL pointing to this thumb.
     */
    public function getUrl() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/' . $this->relative_path;
    }

    /**
     * @return string Get fully-qualified system path pointing to this thumb.
     */
    public function getPath() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $this->relative_path;
    }

    /**
     * @return bool Whether this instance represents a successful thumb generation.
     */
    public function isSuccess() {
        return ! empty( $this->relative_path );
    }

    /**
     * Saves thumb to DB. Either an UPDATE or an INSERT is performed depending on whether meta_id isset and
     * updates the instance meta_id if INSERT is performed.
     */
    public function save() {
        global $wpdb;

        // thumbs are immutable -- once created they can only be read or deleted
        if ( isset( $this->meta_id ) ) return;

        // post_id + dimensions must be unique so urge the old entry if one exists
        $old_thumb = self::getThumb( $this->post_id, $this->getWidth(), $this->getHeight() );
        if ( ! is_null( $old_thumb ) ) {
            $old_thumb->delete();
        }

        // perform save to DB
        $values = array( 'post_id' => $this->post_id, 'meta_key' => self::$meta_key, 'meta_value' => $this->getMetaValue() );
        $value_formats = array( '%d', '%s', '%s' );
        $wpdb->insert( $wpdb->postmeta, $values, $value_formats );
        $this->meta_id = $wpdb->insert_id;

        self::initThumbs();
        if ( !isset( self::$thumbs[$this->post_id] ) ) {
            self::$thumbs[$this->post_id] = array();
        }

        self::$thumbs[$this->post_id][$this->dimensions] = $this;
    }

    /**
     * Deletes the current instances from the DB and filesystem.
     */
    public function delete() {
        if ( ! isset( $this->meta_id ) ) return;

        global $wpdb;
        $wpdb->delete( $wpdb->postmeta, array( 'meta_id' => $this->meta_id ), array( '%d' ) );
        self::cleanupThumbFiles( $this );
        unset( self::$thumbs[$this->post_id][$this->dimensions] );
        unset( $this->meta_id );
    }

    /**
     * Whether the given attachment has a thumbanil graphic.
     *
     * @param $ID int The id of the attachment to be checked.
     * @param $width int The width we're seeking.
     * @param $height int The height we're seeking.
     * @param $success_matters bool Whether return value should be false when DG_Thumb exists, but is not successful.
     * @return bool Whether the given attachment has a thumbnail image.
     */
    public static function thumbExists($ID, $width, $height, $success_matters = true) {
        $thumbs     = self::getThumbs();
        $dimensions = $width . 'x' . $height;
        return
            array_key_exists( $ID, $thumbs ) &&
            array_key_exists( $dimensions, $thumbs[$ID] ) &&
            ( ! $success_matters || $thumbs[$ID][$dimensions]->isSuccess() );
    }

    /**
     * Whether the given attachment has a thumbanil graphic.
     *
     * @param $ID int The id of the attachment to be checked.
     * @param $width int The width we're seeking.
     * @param $height int The height we're seeking.
     * @return DG_Thumb|null The thumbnail at the requested dimensions.
     */
    public static function getThumb($ID, $width, $height) {
        $ret = null;
        if ( self::thumbExists( $ID, $width, $height, false ) ) {
            $ret = self::$thumbs[$ID][$width . 'x' . $height];
        }

        return $ret;
    }

    /**
     * Initializes the thumbs variable if not already initialized.
     */
    private static function initThumbs() {
        if ( !isset( self::$thumbs ) ) {
            global $wpdb;
            self::$thumbs = array();

            $meta_key = self::$meta_key;
            $sql = "SELECT post_id, meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '$meta_key'";
            $result = $wpdb->get_results( $sql );
            foreach ( $result as $row ) {
                $var_by_ref = get_object_vars( $row );
                $key = array_shift( $var_by_ref );
                if ( !isset( self::$thumbs[$key] ) ) {
                    self::$thumbs[$key] = array();
                }

                $new = new DG_Thumb( $row );
                self::$thumbs[$key][$new->dimensions] = $new;
            }
        }
    }

    /**
     * Gets all thumbs from the DB.
     */
    public static function getThumbs() {
        self::initThumbs();
        return self::$thumbs;
    }

    /**
     * Removes thumbs from the DB.
     * @param $ids array|int|null Optional. The post IDs to be purged. If not given then all are purged.
     * @param $blog_id null|int Optional. The blog to purge from. Defaults to active blog.
     */
    public static function purgeThumbs($ids = null, $blog_id = null) {
        global $wpdb;

        if ( ! is_null( $ids ) ) {
            $ids = (array)$ids;
        }

        $postmeta = $wpdb->get_blog_prefix($blog_id) . 'postmeta';
        self::initThumbs();

        if ( ! is_array( $ids ) ) {
            // cleanup DB
            $wpdb->delete( $postmeta, array( 'meta_key' => self::$meta_key ), array( '%d' ) );

            // cleanup filesystem
            foreach ( self::$thumbs as $thumbs ) {
                self::cleanupThumbFiles( $thumbs );
            }

            unset( self::$thumbs );
        } else {
            $meta_key = self::$meta_key;
            $result = $wpdb->query( $wpdb->prepare(
                "DELETE FROM $postmeta WHERE meta_key = '$meta_key' AND post_id IN(" . rtrim( str_repeat( '%d,', sizeof( $ids ) ), ',' ) . ")",
                $ids ) );

            if ( $result ) {
                foreach ( $ids as $id ) {
                    if ( array_key_exists( $id, self::$thumbs ) ) {
                        self::cleanupThumbFiles( self::$thumbs[$id] );
                        unset( self::$thumbs[$id] );
                    }
                }
            }
        }
    }

    /**
     * Delete all thumb entries that do not have an actual image associated with them due to failed generation.
     */
    public static function purgeFailedThumbs() {
        // find the failed thumbs
        $failed = array();
        foreach ( self::getThumbs() as $thumbs ) {
            foreach ( $thumbs as $thumb ) {
                if ( ! $thumb->isSuccess() ) {
                    $failed[] = $thumb;
                }
            }
        }

        // short-circuit if we have nothing to remove
        if ( ! $failed ) return;

        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $wpdb->postmeta WHERE meta_id IN(" . rtrim( str_repeat( '%d,', sizeof( $failed ) ), ',' ) . ")",
            array_map( array( __CLASS__, 'getId' ), $failed ) ) );

        self::cleanupThumbFiles( $failed );

        // force re-query next time
        unset( self::$thumbs );
    }

    /**
     * @param $thumb DG_Thumb The thumb to retrieve post ID from.
     * @return int The post_id of the given thumb.
     */
    private static function getId($thumb) {
        return $thumb->post_id;
    }

    /**
     * @param $thumbs array|DG_Thumb Removes files associated with given thumb(s).
     */
    private static function cleanupThumbFiles($thumbs) {
        foreach ( (array)$thumbs as $thumb ) {
            if ( isset( $thumb->relative_path ) ) {
                @unlink( $thumb->getPath() );
            }
        }
    }
}