<?php
defined( 'WPINC' ) OR exit;

class DG_ThumberClient extends ThumberClient {

	/**
	 * @var DG_ThumberClient Backs the getter.
	 */
	private static $instance;

	/**
	 * @return DG_ThumberClient The singleton instance.
	 */
	public static function getInstance() {
		return isset( self::$instance ) ? self::$instance : ( self::$instance = new DG_ThumberClient() );
	}

	/**
	 * Enforce singleton.
	 */
	protected function __construct() {
		parent::__construct();

		global $dg_options;

		$this->uid = $dg_options['thumber-co']['uid'];
		$this->userSecret = $dg_options['thumber-co']['secret'];
		$this->thumberUserAgent = 'Document Gallery Thumber Client 1.0 (PHP ' . phpversion() . '; ' . php_uname() . ')';
	}

	/**
	 * Sends HTTP request to Thumber server.
	 * @param $type string GET or POST
	 * @param $url string The URL endpoint being targeted.
	 * @param $httpHeaders string[] The headers to be sent.
	 * @param $body string The POST body. Ignored if type is GET.
	 * @return mixed[] The result of the request.
	 */
	protected function sendToThumber($type, $url, $httpHeaders, $body = '') {
		$headers = array();
		foreach ( $httpHeaders as $v ) {
			$kvp = explode( ':', $v );
			$headers[trim( $kvp[0] )] = trim( $kvp[1] );
		}

		// NOTE: Failure was local so not actual HTTP error, but makes error checking much
		// simpler if we set the value to something above the success range
		$result = array (
			'http_code'       => 600,
			'header'          => '',
			'body'            => '',
			'last_url'        => ''
		);
		$args = array(
			'headers'      => $headers,
			'user-agent'   => $this->thumberUserAgent
		);

		switch ( $type ) {
			case 'GET':
				if ( ! empty( $body ) ) {
					$args['body'] = $body;
				}

				$resp = wp_remote_get( $url, $args );
				break;

			case 'POST':
				if (!empty($body)) {
					$args['body'] = $body;
				}

				$resp = wp_remote_post( $url, $args );
				break;

			default:
				$err = 'Invalid HTTP type given: ' . $type;
				self::handleError( $err );
				$result['error'] = 'Invalid HTTP type given: ' . $type;
		}

		if ( isset( $resp ) ) {
			if ( ! is_wp_error( $resp ) ) {
				$result['http_code'] = $resp['response']['code'];
				$result['body'] = $resp['body'];
			} else {
				$result['body'] = $resp->get_error_message();
			}
		}

		return $result;
	}

	/**
	 * Processes the POST request, generating a ThumberResponse, validating, and passing the result to $callback.
	 * If not using client.php as the webhook, whoever receives webhook response should first invoke this method to
	 * validate response.
	 */
	public function receiveThumbResponse() {
		$resp = parent::receiveThumbResponse();
		if ( is_null( $resp ) ) {
			return;
		}

		$nonce = $resp->getNonce();
		$split = explode( DG_ThumberCoThumber::NonceSeparator, $nonce );
		if ( $resp->getSuccess() && count( $split ) === 2 ) {
			$ID = absint( $split[0] );
			$tmpfile = DG_Util::getTempFile();

			file_put_contents( $tmpfile, $resp->getDecodedData() );

			DG_Thumber::setThumbnail( $ID, $tmpfile, array( __CLASS__, 'getThumberThumbnail' ) );
			DG_Logger::writeLog( DG_LogLevel::Detail, "Received thumbnail from Thumber for attachment #{$split[0]}." );
		} else {
			$ID = ( count( $split ) > 0) ? $split[0] : $nonce;
			DG_Logger::writeLog( DG_LogLevel::Warning, "Thumber was unable to process attachment #$ID: " . $resp->getError() );
		}
	}

	/**
	 * @param $update_options bool Optional. Whether the returned value should be updated in options array.
	 * @return array Returns all MIME types that are supported by Thumber and WP.
	 */
	public function getSubscription($update_options = true) {
		global $dg_options;
		$ret = $dg_options['thumber-co']['subscription'];
		if ( empty( $ret ) ) {
			static $whitelist = array( 'direct_upload', 'file_size_limit', 'thumb_size_limit' );
			$ret = array_intersect_key( (array)parent::getSubscription(), array_flip( $whitelist ) );
			if ( $update_options ) {
				$dg_options['thumber-co']['subscription'] = $ret;
				DocumentGallery::setOptions( $dg_options );
			}
		}

		return $ret;
	}

	/**
	 * Retrieves the supported MIME types from Thumber that are also compatible with WordPress.
	 * @return string[] The supported MIME types reported by the Thumber server.
	 */
	public function getMimeTypes() {
		global $dg_options;
		if ( empty( $dg_options['thumber-co']['mime_types'] ) ) {
			// avoid values being removed as a result of current user but also include any MIME types
			// that are added outside of the default WP values
			$wp_types = array_merge( wp_get_mime_types(), get_allowed_mime_types() );

			$allowed = array_intersect( $wp_types, parent::getMimeTypes() );
			$dg_options['thumber-co']['mime_types'] = array_keys( $allowed );
			DocumentGallery::setOptions( $dg_options );
		}

		return $dg_options['thumber-co']['mime_types'];
	}

	/**
	 * @param $err string Fires on fatal error.
	 */
	protected function handleError($err) {
		DG_Logger::writeLog( DG_LogLevel::Error, $err );
	}
}