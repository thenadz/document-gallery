<?php

/**
 * Class to process sending requests and receiving responses.
 */
class ThumberClient {
   /**
    * The Thumber.co API subdomain.
    */
   const ThumberServerHost = 'api.thumber.co';

   /**
    * The path for creating a new thumbnail.
    */
   const ThumberServerCreatePath = '/create.json';

   /**
    * The path for GETting the user's current subscription info.
    */
   const ThumberSubscriptionPath = '/subscription.json';

   /**
    * The path for GETting the supported MIME types.
    */
   const ThumberServerMimeTypesPath = '/mime_types.json';

   /**
    * @var string The user agent to send HTTP requests as.
    */
   protected $thumberUserAgent;

   /**
    * @var string UID for the user accessing the Thumber API.
    */
   protected $uid;

   /**
    * @param $uid string UID for the user accessing the Thumber API.
    */
   public function setUid($uid) {
      $this->uid = $uid;
   }

   /**
    * @var string The user secret assoicataed with the UID for the user
    * accessing the Thumber API.
    */
   protected $userSecret;

   /**
    * @param $userSecret string The user secret assoicataed with the UID for the user
    * accessing the Thumber API.
    */
   public function setUserSecret($userSecret) {
      $this->userSecret = $userSecret;
   }

   /**
    * @var string Fully-qualified system path to this file.
    */
   private $thumberClientPath;

   /**
    * @var object The subscription object.
    */
   private static $subscription;

   /**
    * @var array The supported MIME types.
    */
   private static $mime_types;

   /**
    * @var ThumberClient Backs the getter.
    */
   private static $instance;

   /**
    * @return ThumberClient The singleton instance.
    */
   public static function getInstance() {
      return isset( self::$instance ) ? self::$instance : ( self::$instance = new ThumberClient() );
   }

   /**
    * Initialized class members.
    */
   protected function __construct() {
      $this->thumberUserAgent = 'Thumber Client 1.0 (PHP ' . phpversion() . '; ' . php_uname() . ')';
      $this->thumberClientPath = dirname( __FILE__ ) . '/';
   }

   /**
    * Sends the provided request to Thumber for processing.
    *
    * NOTE: Caller should consider testing file size against getSubscription()->file_size_limit to avoid unnecessary
    * HTTP requests when the file will be rejected.
    *
    * @param ThumberThumbReq $req The request to be sent.
    * @return array The results of sending the request.
    */
   public function sendThumbRequest($req) {
      if (!($req instanceof ThumberThumbReq)) {
         $err = 'Request must be of type ThumberThumbReq.';
         $this->handleError($err);
         return array('error' => $err);
      }

      return $this->sendRequest($req, 'POST', self::ThumberServerCreatePath);
   }

   /**
    * Processes the POST request, generating a ThumberResponse, validating, and passing the result to $callback.
    * If not using client.php as the webhook, whoever receives webhook response should first invoke this method to
    * validate response.
    */
   public function receiveThumbResponse() {
      include_once $this->thumberClientPath . 'thumb-response.php';

      $json = stream_get_contents(fopen('php://input', 'r'));
      $resp = ThumberThumbResp::parseJson($json);

      if (is_null($resp)) {
         $this->handleError('Failed to parse JSON in POST body: ' . $json);
         return null;
      }

      if (!$resp->isValid($this->userSecret)) {
         $this->handleError('Received invalid response: ' . $json);
         return null;
      }

      // This method should be overridden in order to use response
      return $resp;
   }

   /**
    * @return object|null Returns an object with subscription information. Object includes the following:
    * name, monthly_cost, watermark, direct_upload, thumbs_per_hr_limit, file_size_limit, and thumb_size_limit.
    */
   public function getSubscription() {
      include_once $this->thumberClientPath . 'base-transaction.php';

      if (isset(self::$subscription)) {
         return self::$subscription;
      } else {
         $resp = $this->sendRequest(new ThumberBaseTransaction(), 'GET', self::ThumberSubscriptionPath);
         if (!array_key_exists('error', $resp)) {
            self::$subscription = json_decode($resp['body']);
            return self::$subscription;
         } else {
            $this->handleError('Failed to retrieve subscription: ' . $resp['body']);
            return null;
         }
      }
   }

   /**
    * Retrieves the supported MIME types from Thumber.
    * @return array The supported MIME types reported by the Thumber server.
    */
   public function getMimeTypes() {
      if (!isset(self::$mime_types)) {
         $headers = array('Content-Type: application/json', 'Content-Length: 0');
         $result = $this->sendToThumber('GET', self::getThumberUrl(self::ThumberServerMimeTypesPath), $headers);
         self::$mime_types = !array_key_exists('error', $result) ? json_decode($result['body'], true) : array();
      }

      return self::$mime_types;
   }

   /**
    * Sends the provided request to the API endpoint.
    *
    * @param ThumberBaseTransaction $req The request to be sent. UID, callback, and timestamp
    * will be written by client. Additionally, nonce will be set if not already set.
    * @param string $type The type of HTTP request (GET/POST/etc.)
    * @param string $path The relative path on Thumber server to contact.
    * @return array containing data about success of the cURL request.
    */
   public function sendRequest($req, $type, $path) {
      include_once $this->thumberClientPath . 'base-transaction.php';

      if (!is_a($req, 'ThumberBaseTransaction')) {
         $err = 'Request must be of type ThumberBaseTransaction.';
         $this->handleError($err);
         return array('error' => $err);
      }

      // whether we've changed any field, requiring recompute of checksum
      $changed = false;

      $timestamp = $req->getTimestamp();
      if (empty($timestamp)) {
         $req->setTimestamp(time());
         $changed = true;
      }

      $uid = $req->getUid();
      if (empty($uid)) {
         $req->setUid($this->uid);
         $changed = true;
      }

      $nonce = $req->getNonce();
      if (empty($nonce)) {
         $req->setNonce();
         $changed = true;
      }

      $checksum = $req->getChecksum();
      if ($changed || empty($checksum)) {
         $req->setChecksum($req->computeChecksum($this->userSecret));
      }

      if (!$req->isValid($this->userSecret)) {
         $err = 'Invalid request provided.';
         $this->handleError($err);
         return array('error' => $err);
      }

      $body = '';
      if ($type == 'GET') {
         $path .= '?' . $req->toQuery();
      } else {
         $body = $req->toJson();
      }

      $headers = array('Content-Type: application/json', 'Content-Length: ' . strlen($body));
      $result = $this->sendToThumber($type, self::getThumberUrl($path), $headers, $body);
      $result['nonce'] = !array_key_exists('error', $result) ? $req->getNonce() : '';

      // caller should handle errors sensibly
      return $result;
   }

   /**
    * Sends cURL request to Thumber server.
    * @param $type string GET or POST
    * @param $url string The URL endpoint being targeted.
    * @param $httpHeaders array The headers to be sent.
    * @param $body string The POST body. Ignored if type is GET.
    * @return array The result of the request.
    */
   protected function sendToThumber($type, $url, $httpHeaders, $body = '') {
      // open connection
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_USERAGENT,      $this->thumberUserAgent);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,            $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER,     $httpHeaders);
      if ($type == 'POST') {
         curl_setopt($ch, CURLOPT_POSTFIELDS,  $body);
      }

      // execute post, storing useful information about result
      $response = curl_exec($ch);
      $error = curl_error($ch);
      $result = array (
          'header'          => '',
          'body'            => '',
          'error'           => '',
          'http_code'       => '',
          'last_url'        => ''
      );

      if ($error === '') {
         $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

         $result['header']    = substr($response, 0, $header_size);
         $result['body']      = substr($response, $header_size);
         $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         $result['last_url']  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      } else {
         $result ['error'] = $error;
      }

      curl_close($ch);

      return $result;
   }

   /**
    * @param string $path The path at Thumber to generate URL from.
    * @return string The full URL to the given relative path.
    */
   private static function getThumberUrl($path) {
      return 'http://' . self::ThumberServerHost . $path;
   }

   /**
    * @param $err string Fires on fatal error.
    */
   protected function handleError($err) {
      echo $err;
   }
}