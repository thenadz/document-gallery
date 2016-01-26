<?php

/**
 * Holds data specific to a specific thumbnail
 *
 * @author drossiter
 */
class DG_Thumb {

    /**
     * TODO: Replace w/ https://codex.wordpress.org/Class_Reference/WP_Object_Cache
     * @var DG_Thumb[] The cached copy of thumbs.
     */
    private static $thumbs = null;

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
        $this->timestamp = absint( $split[1] );
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
        $split = explode( 'x', $this->dimensions, 2 );
        return absint( $split[0] );
    }

    /**
     * @return int Parses dimensions string pulling out the height.
     */
    public function getHeight() {
        $split = explode( 'x', $this->dimensions, 2 );
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
        return ! empty( $this->relative_path ) && ! DG_Util::startsWith( $this->getPath(), DG_PATH );
    }

    /**
     * Saves thumb to DB. Either an UPDATE or an INSERT is performed depending on whether meta_id isset and
     * updates the instance meta_id if INSERT is performed.
     */
    public function save() {
        global $wpdb;

        // thumbs are immutable -- once created they can only be read or deleted
        if ( isset( $this->meta_id ) ) return;

        // don't re-save identical thumb
        if ( isset( self::$thumbs[$this->post_id][$this->dimensions] ) &&
            $this == self::$thumbs[$this->post_id][$this->dimensions] ) return;


        // post_id + dimensions must be unique so purge the old entry if one exists
        $old_thumb = self::getThumb( $this->post_id, $this->dimensions );
        if ( ! is_null( $old_thumb ) ) {
            $old_thumb->delete( $old_thumb->relative_path != $this->relative_path );
        }

        DG_Logger::writeLog( DG_LogLevel::Detail, 'Saving thumb with post_id = ' . $this->post_id );

        // perform save to DB
        $values = array( 'post_id' => $this->post_id, 'meta_key' => self::$meta_key, 'meta_value' => $this->getMetaValue() );
        $value_formats = array( '%d', '%s', '%s' );
        $wpdb->insert( $wpdb->postmeta, $values, $value_formats );
        $this->meta_id = $wpdb->insert_id;

        self::initThumbs();
        if ( ! isset( self::$thumbs[$this->post_id] ) ) {
            self::$thumbs[$this->post_id] = array();
        }

        self::$thumbs[$this->post_id][$this->dimensions] = $this;
    }

    /**
     * Deletes the current instances from the DB and filesystem.
     *
     * @param $delete_files bool Whether files for the thumb should be deleted.
     */
    public function delete( $delete_files = true ) {
        if ( ! isset( $this->meta_id ) ) return;

        DG_Logger::writeLog( DG_LogLevel::Detail, 'Deleting thumb with post_id = ' . $this->post_id );

        global $wpdb;
        $wpdb->delete( $wpdb->postmeta, array( 'meta_id' => $this->meta_id ), array( '%d' ) );
        self::cleanupThumbFiles( $this );
        unset( $this->meta_id );
        if ( 1 === sizeof( self::$thumbs[$this->post_id] ) ) {
            unset( self::$thumbs[$this->post_id] );
        } else {
            unset( self::$thumbs[$this->post_id][$this->dimensions] );
        }
    }

    /**
     * Whether the given attachment has a thumb graphic.
     *
     * @param $ID int The id of the attachment to be checked.
     * @param $dimensions string Optional. The dimensions for the thumbnail we're seeking.
     * @param $success_matters bool Optional. Whether return value should be false when DG_Thumb exists, but is not successful.
     * @return bool Whether the given attachment has a thumbnail image.
     */
    public static function thumbExists($ID, $dimensions = null, $success_matters = true) {
        $thumb = self::getThumb( $ID, $dimensions );
        return ! is_null( $thumb ) && ( ! $success_matters || $thumb->isSuccess() );
    }

    /**
     * Whether the given attachment has a thumb graphic.
     *
     * @param $ID int The id of the attachment to be checked.
     * @param $dimensions string The dimensions for the thumbnail we're seeking.
     * @return DG_Thumb|null The thumbnail at the requested dimensions.
     */
    public static function getThumb($ID, $dimensions = null) {
        $thumbs = self::getThumbs();
        $ret = null;
        if ( isset( $thumbs[$ID] ) && ( is_null( $dimensions ) || isset( $thumbs[$ID][$dimensions] ) ) ) {
            $ret = ! is_null( $dimensions ) ? $thumbs[$ID][$dimensions] : reset( $thumbs[$ID] );
        }

        return $ret;
    }

    /**
     * Initializes the thumbs variable if not already initialized.
     */
    private static function initThumbs() {
        if ( !isset( self::$thumbs ) ) {
            DG_Logger::writeLog( DG_LogLevel::Detail, 'Populating thumbnail cache.' );
            global $wpdb;
            self::$thumbs = array();

            $meta_key = self::$meta_key;
            $sql = "SELECT post_id, meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '$meta_key'";
            foreach ( $wpdb->get_results( $sql ) as $row ) {
                $key = $row->post_id;
                $new = new DG_Thumb( $row );
                if ( ! isset( self::$thumbs[$key] ) ) {
                    self::$thumbs[$key] = array();
                } elseif ( isset( self::$thumbs[$key][$new->dimensions] ) ) {
                    // it is possible to end up with duplicate thumbnails -- cleanup here
                    $old = self::$thumbs[$key][$new->dimensions];
                   if ( $old->timestamp < $new->timestamp ) {
                        $old->delete();
                   } else {
                       $new->delete();
                       continue;
                   }
                }

                self::$thumbs[$key][$new->dimensions] = $new;
            }
        }
    }

    /**
     * Gets either a nested associative array mapping ID to dimension to thumb or an associative array mapping ID to thumb.
     *
     * @param $dimensions string WIDTHxHEIGHT
     * @return DG_Thumb[]|DG_Thumb[][] The matched thumbs.
     */
    public static function getThumbs($dimensions = null) {
        self::initThumbs();
        if ( is_null( $dimensions ) ) {
            $ret = self::$thumbs;
        } else {
            $ret = array();
            foreach ( self::$thumbs as $thumbs ) {
                if ( isset( $thumbs[$dimensions] ) ) {
                    $thumb = $thumbs[$dimensions];
                    $ret[$thumb->post_id] = $thumb;
                }
            }
        }

        return $ret;
    }

    /**
     * Removes thumbs from the DB.
     * @param $ids int[]|int|null Optional. The post IDs to be purged. If not given then all are purged.
     * @param $blog_id null|int Optional. The blog to purge from. Defaults to active blog.
     * @param $delete_files bool Whether files for the thumb should be deleted.
     */
    public static function purgeThumbs( $ids = null, $blog_id = null, $delete_files = true ) {
        global $wpdb;

        if ( ! is_null( $ids ) ) {
            $ids = (array)$ids;
        }

        $postmeta = $wpdb->get_blog_prefix( $blog_id ) . 'postmeta';
        $meta_key = self::$meta_key;

        self::initThumbs();

        if ( ! is_array( $ids ) ) {
            $sql = "DELETE FROM $postmeta WHERE meta_key = '$meta_key'";
            $result = $wpdb->query( $sql );

            if ( $result ) {
                // cleanup filesystem
                if ( $delete_files ) {
                    foreach ( self::$thumbs as $thumbs ) {
                        self::cleanupThumbFiles( $thumbs );
                    }
                }

                self::$thumbs = null;
            }
        } else if ( !empty( $ids ) ) {
            $sql = "DELETE FROM $postmeta WHERE meta_key = '$meta_key' AND post_id IN(" . rtrim( str_repeat( '%d,', sizeof( $ids ) ), ',' ) . ")";
            $result = $wpdb->query( $wpdb->prepare( $sql, $ids ) );

            if ( $result ) {
                foreach ( $ids as $id ) {
                    if ( isset( self::$thumbs[$id] ) ) {
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
        global $wpdb;
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '" . self::$meta_key . "' AND meta_value LIKE '%:%::'" );

        // force re-query next time
        self::$thumbs = null;
    }

    /**
     * Removes meta data associated w/ attachment.
     *
     * @param $id int The post ID.
     */
    public static function cleanupAttachmentMeta($id) {
        $thumb = self::getThumb( $id );
        if ( ! is_null( $thumb ) ) {
            self::cleanupThumbFiles($thumb);
        }
    }

    /**
     * @param $thumbs DG_Thumb[]|DG_Thumb Removes files associated with given thumb(s).
     */
    private static function cleanupThumbFiles($thumbs) {
        if ( is_a( $thumbs, __CLASS__ ) ) {
            $thumbs = array( $thumbs );
        }

        foreach ( $thumbs as $thumb ) {
            if ( isset( $thumb->relative_path ) ) {
                @unlink( $thumb->getPath() );
            }
        }
    }
}