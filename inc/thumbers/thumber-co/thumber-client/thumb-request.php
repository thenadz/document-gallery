<?php

include_once 'thumb-transaction.php';

/**
 * Class ThumberThumbReq Object representation of request sent to Thumber in order to get thumbnail for a file.
 */
class ThumberThumbReq extends ThumberThumbTransaction {
   /**
    * @var string The local URL that will be POSTed to with generated thumbnail. 
    */
   protected $callback;
   
   /**
    * Sets the callback URL.
    * 
    * @param string $callback The callback URL.
    */
   public function setCallback($callback) {
      $this->callback = $callback;
   }
   
   /**
    * Gets the callback URL.
    * 
    * @return string The callback URL.
    */
   public function getCallback() {
      return $this->callback;
   }
   
   /**
    * @var string URL pointing to the file to be thumbed.
    */
   protected $url;
   
   /**
    * Sets the URL pointing to the file to be thumbed.
    * 
    * @param string $url The URL.
    */
   public function setUrl($url) {
      $this->url = $url;
   }
   
   /**
    * Gets the URL pointing to the file to be thumbed.
    * 
    * @return string The URL.
    */
   public function getUrl() {
      return $this->url;
   }
   
   /**
    * The geometry string to use when thumbnail is being sized. 
    * Geometry string should be a valid ImageMagick geometry string 
    * (http://www.imagemagick.org/script/command-line-processing.php#geometry).
    * 
    * @var string Geometry string for sizing thumbnail.
    */
   protected $geometry;
   
   /**
    * Sets the geometry string.
    * 
    * @param string $geometry The geometry string.
    */
   public function setGeometry($geometry) {
      $this->geometry = $geometry;
   }
   
   /**
    * Gets the geometry string.
    * 
    * @return string The geometry string.
    */
   public function getGeometry() {
      return $this->geometry;
   }
   
   /**
    * 1-indexed page number to be used for generated thumbnail.
    * 
    * @var int Page number to be used for generated thumbnail.
    */
   protected $pg;
   
   /**
    * Sets the page number to be used for generated thumbnail.
    * 
    * @param int $pg Page number.
    */
   public function setPg($pg) {
      $this->pg = $pg;
   }
   
   /**
    * Gets the page number to be used for generated thumbnail.
    * 
    * @return int Page number.
    */
   public function getPg() {
      return $this->pg;
   }
   
   /**
    * @var string The mime type of the file being thumbed.
    */
   protected $mimeType;
   
   /**
    * Sets the mime type.
    * 
    * @param string $mimeType The mime type.
    */
   public function setMimeType($mimeType) {
      $this->mimeType = $mimeType;
   }
   
   /**
    * Gets the mime type.
    * 
    * @return string The mime type.
    */
   public function getMimeType() {
      return $this->mimeType;
   }

   /**
    * (non-PHPdoc)
    * @see ThumberBaseTransaction::isValid()
    */
   public function isValid($secret = null) {
      $data = $this->getEncodedData();
      return parent::isValid($secret) &&
         isset($this->uid) &&
         isset($this->callback) &&
         (isset($this->url) || !empty($data)) &&
         isset($this->mimeType);
   }

   /**
    * Creates a ThumberThumbReq instance from the given JSON.
    * @param string $json The JSON to create instance from.
    * @return ThumberThumbReq|NULL The resultant object from parsing the JSON.
    */
   public static function parseJson($json) {
      try {
         return new ThumberThumbReq($json);
      } catch (InvalidArgumentException $e) {
         return null;
      }
   }
}