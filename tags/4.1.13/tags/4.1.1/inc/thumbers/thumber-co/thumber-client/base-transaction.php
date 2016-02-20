<?php

/**
 * Class ThumberBaseTransaction The base transaction class which performs all necessary cryptographic work in addition
 * to reflectively (de)serializing JSON for all transactions.
 *
 * Any request with no fields other than those required for authentication may be sent directly using this class,
 * other requests with custom parameters will need to extend this class.
 */
class ThumberBaseTransaction {
   /**
    * Constructs new ThumberTransaction instance.
    * 
    * @param string $json JSON string to for populating instance.
    */
   public function __construct($json = null) {
      if (!is_null($json)) {
         $this->fromJson($json);
      }
   }

   /**
    * @var string The UID for API user. Note that only requests will set this value.
    */
   protected $uid;

   /**
    * Sets the UID.
    *
    * @param string $uid The UID.
    */
   public function setUid($uid) {
      $this->uid = $uid;
   }

   /**
    * Gets the UID.
    *
    * @return string The UID.
    */
   public function getUid() {
      return $this->uid;
   }
   
   /**
    * @var string The unique identifier for this transaction set (same used in both req & resultant resp).
    */
   protected  $nonce;
   
   /**
    * Sets the NONCE. Generates the NONCE from microtime() if none is given.
    * 
    * @param string $nonce The NONCE.
    */
   public function setNonce($nonce = null) {
      if (is_null($nonce)) {
         $nonce = md5(microtime());
      }
      
      $this->nonce = $nonce;
   }
   
   /**
    * Gets the NONCE.
    * 
    * @return string The NONCE.
    */
   public function getNonce() {
      return $this->nonce;
   }
   
   /**
    * @var int The UTC timestamp representing when this transaction was sent.
    */
   protected $timestamp;
   
   /**
    * Sets the timestamp.
    * 
    * @param int $timestamp The timestamp.
    */
   public function setTimestamp($timestamp) {
      $this->timestamp = $timestamp;
   }
   
   /**
    * Gets the timestamp.
    * 
    * @return int The timestamp.
    */
   public function getTimestamp() {
      return $this->timestamp;
   }
   
   /**
    * @var string The checksum which is calculated with the contents of the 
    * transaction (minus the checksum) and the user's secret with the HMAC-SHA256 algorithm.
    */
   protected $checksum;
   
   /**
    * Sets the checksum.
    * 
    * @param string $checksum The checksum.
    */
   public function setChecksum($checksum) {
      $this->checksum = $checksum;
   }
   
   /**
    * Gets the checksum.
    * 
    * @return string The checksum.
    */
   public function getChecksum() {
      return $this->checksum;
   }
   
   /**
    * Whether this instance is valid. If secret is provided, then validity will include checksum validation.
    * 
    * @param string $secret The user secret.
    * @return bool Whether this instance is valid.
    */
   public function isValid($secret = null) {
      // NOTE: Doesn't check UID because this class is used in representing req & resp. Not ideal solution.
      return isset($this->nonce) &&
         isset($this->timestamp) &&
         isset($this->checksum) &&
         (is_null($secret) || $this->isValidChecksum($secret));
   }
   
   /**
    * Computes checksum for this instance and compares against the value set as the instance checksum.
    * 
    * @param string $secret The user secret.
    * @return bool Whether this instance's checksum value is valid for this instance's contents.
    */
   public function isValidChecksum($secret) {
      return $this->checksum === $this->computeChecksum($secret);
   }
   
   /**
    * Computes checksum based on instance variables.
    * 
    * @param string $secret The user secret.
    * @return string The checksum representing this instance.
    */
   public function computeChecksum($secret) {
      $arr = $this->toArray();
      unset($arr['checksum']);
      
      // only use up to the first 1024 characters of each value in computing checksum
      foreach ($arr as &$v) {
         if (is_bool($v)) {
            $v = $v ? 'true' : 'false';
         } else {
            $v = substr((string)$v, 0, 1024);
         }
      }

      ksort($arr, SORT_STRING);

      return hash_hmac('sha256', $this->toQuery($arr), $secret, false);
   }
   
   /**
    * Gets array representation of this instance reflectively. Any public/protected instance variables in this
    * or any extending classes will be included.
    * 
    * @return array Array representation of this instance.
    */
   public function toArray() {
     $ret = array();
     
     foreach($this as $k => $v) {
        if (is_null($v)) continue;
        
        // camel case to underscore word delineation
        $k = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $k));
        $ret[$k] = $v;
     }
     
     return $ret;
   }

   /**
    * @param array|NULL $arr The array to be converted. If null, $this->toArray() is used.
    * @return string The resultant query string.
    */
   public function toQuery($arr = null) {
      return self::implode('=', '&', !is_null($arr) ? $arr : $this->toArray());
   }

   /**
    * Creates JSON string with class fields, renaming field names to underscore rather than camel.
    * 
    * @return string JSON representation of defined object accessible non-static properties.
    */
   public function toJson() {
     return json_encode($this->toArray());
  }
  
  /**
   * Populates instance with values from JSON.
   * 
   * @param string $json The JSON string to populate this instance with.
   */
   public function fromJson($json) {
      $json = json_decode($json, true);
      if (is_null($json)) {
         throw new InvalidArgumentException("Provided JSON string is invalid: $json");
      }
      
      foreach ($json as $k => $v) {
         // underscore word delineation to camel case
         $k = preg_replace_callback('/_([a-z])/', array (__CLASS__, 'secondCharToUpper'), $k);
         if (property_exists($this, $k)) {
            $this->$k = $v;
         }
      }
   }

   /**
    * @param string $string To take second char from.
    * @return char Capitalized second char of given string.
    */
   private static function secondCharToUpper($string) {
      return strtoupper($string[1]);
   }
   
   /**
    * Implodes associative array.
    * @param string $inner Glue between key and value.
    * @param string $outer Glue between key-value pairs.
    * @param array $arr The array to be imploded.
    * @return string The implosion.
    */
   private static function implode($inner, $outer, $arr) {
      if (count($arr) == 0) return '';
      $ret = '';
      
      foreach ($arr as $k => $v) {
         $ret .= "$k$inner$v$outer";
      }
      
      return substr($ret , 0, -strlen($outer));
   }
}