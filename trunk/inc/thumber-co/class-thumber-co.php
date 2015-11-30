<?php
defined( 'WPINC' ) OR exit;

include_once DG_PATH . 'inc/thumber-co/thumber-client/client.php';
include_once DG_PATH . 'inc/thumber-co/class-thumber-client.php';

add_action( 'dg_thumbers', array( 'DG_ThumberCo', 'thumbersFilter' ) );
add_filter( 'allowed_http_origin', array( 'DG_ThumberCo', 'allowThumberWebhooks' ), 10, 2);
add_filter( 'upload_mimes', array( 'DG_ThumberCo', 'customMimeTypes' ) );
add_action( 'admin_post_nopriv_' . DG_ThumberCo::ThumberAction, array( DG_ThumberClient::getInstance(), 'receiveThumbResponse' ), 5, 0);

DG_ThumberCo::init();
class DG_ThumberCo {

   /**
    * @const string Name of the action performed in the webhook.
    */
   const ThumberAction = 'dg_thumber_webhook';

   /**
    * @const string Used in nonce to separate the attachment ID from the "random" segment.
    */
   const NonceSeparator = '_';

   /**
    * @var string URL to webhook.
    */
   private static $webhook;

   /**
    * @var DG_ThumberClient The thumber client instance.
    */
   private static $client;

   /**
    * Initializes the static values for this class.
    */
   public static function init() {
      if ( ! isset( self::$webhook ) ) {
         self::$webhook = admin_url( 'admin-post.php?action=' . self::ThumberAction );
         self::$client = DG_ThumberClient::getInstance();
      }
   }

   /**
    * TODO: This should be a configurable option and should include all Thumber types not default WP-supported.
    * @param $mimes array The MIME types WP knows about.
    * @return array Modified MIME types -- adding additional supported types.
    */
   public static function customMimeTypes($mimes) {
      $mimes['pub'] = 'application/mspublisher';
      return $mimes;
   }

   /**
    * WP by default will not handle POSTs from Thumber so add a special case for the action we want to handle.
    * @param $origin
    * @param $origin_arg
    *
    * @return bool Whether WP will handle the action.
    */
   public static function allowThumberWebhooks($origin, $origin_arg) {
      return $origin || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === self::ThumberAction );
   }

   /**
    *
    * @param array $thumbers The thumbnail generators being used by Document Gallery.
    * @return array The thumbnail generators being used by Document Gallery with Thumber.co appended.
    */
   public static function thumbersFilter( $thumbers ) {
      $thumbers[implode( '|', self::$client->getMimeTypes() )] = array( __CLASS__, 'getThumberThumbnail' );
      return $thumbers;
   }

   /**
    * @param int $ID The attachment ID.
    * @param int $pg The page to thumbnail.
    *
    * @return bool Always false. Asynchronously set the thumbnail in webhook later.
    */
   public static function getThumberThumbnail( $ID, $pg = 1 ) {
      global $dg_options;

      include_once DG_PATH . 'inc/thumber-co/thumber-client/client.php';
      include_once DG_PATH . 'inc/thumber-co/thumber-client/thumb-request.php';

      $options = DG_Thumber::getOptions();
      $url_or_path = get_attached_file( $ID );

      if ( ! self::checkGeometry( $options['width'], $options['height'] ) ) {
         DG_Logger::writeLog( DG_LogLevel::Detail, "Skipping attachment #$ID as it exceeds Thumber.co subscription geometry limit." );
         return false;
      }

      if ( ! self::checkFileSize( $url_or_path ) ) {
         DG_Logger::writeLog( DG_LogLevel::Detail, "Skipping attachment #$ID as it exceeds Thumber.co subscription file size limit." );
         return false;
      }

      $mime_type = get_post_mime_type( $ID );
      if ( ! $dg_options['thumber-co']['direct_upload'] ) {
         $url_or_path = wp_get_attachment_url( $ID );
      }

      if ( ! $url_or_path || ! $mime_type ) {
         return false;
      }

      $req = new ThumberThumbReq();
      $req->setCallback( self::$webhook );
      $req->setMimeType( $mime_type );
      $req->setNonce( $ID . self::NonceSeparator . md5( microtime() ) );
      $req->setPg( $pg );
      $req->setGeometry( $options['width'] . 'x' . $options['height'] );

      if ( $dg_options['thumber-co']['direct_upload'] ) {
         $req->setDecodedData( file_get_contents( $url_or_path ) );
      } else {
         $req->setUrl( $url_or_path );
      }

      $resp = self::$client->sendThumbRequest( $req );

      if ( $resp['http_code'] < 200 || $resp['http_code'] > 399 ) {
         DG_Logger::writeLog( DG_LogLevel::Error, 'Failed to transmit to server: ' . $resp['body'] );
      }

      // always returns false -- we set the thumbnail later when webhook is hit
      return false;
   }

   /**
    * @param string $filename File to be tested.
    * @return bool Whether file is acceptable to be sent to Thumber.
    */
   private static function checkFileSize($filename ) {
      $ret  = true;
      $size = @filesize( $filename );
      if ( $size !== false ) {
         $sub = self::$client->getSubscription();
         $ret = ( ! $sub || empty( $sub['file_size_limit'] ) ) || ( $size > 0 && $size <= $sub['file_size_limit'] );
      }

      return $ret;
   }

   /**
    * @param $width int The requested thumb width.
    * @param $height int The requested thumb height.
    * @return bool Whether the requested geometry meets subscription limitations.
    */
   private static function checkGeometry( $width, $height ) {
      $sub = self::$client->getSubscription();
      return ( ! $sub || empty( $sub['thumb_size_limit'] ) ) || ( $width <= $sub['thumb_size_limit'] && $height <= $sub['thumb_size_limit'] );
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}