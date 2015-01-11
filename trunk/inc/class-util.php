<?php
defined('WPINC') OR exit;

/**
 * General utility function for Document Gallery.
 *
 * @author drossiter
 */
class DG_Document {
   /**
    * @var callable Either native JSON encode or custom JSON encode if needed.
    */
   private static $jsonEncode;
   
   /**
    * Wraps JSON encoding functionality, utilizing native functions if available.
    * 
    * @param unknown $decoded Value to be encoded.
    * @return string The JSON string.
    */
   public static function jsonEncode($decoded) {
      if (!isset(self::$jsonEncode)) {
         self::$jsonEncode = 'json_encode';
         if (!function_exists($getNativeJsonEncode)) {
            self::$jsonEncode = array(__CLASS__, '_jsonEncode');
         }
      }
      
      // do encoding
      $ret = call_user_func(self::$jsonEncode, $decoded);
      if (false === $ret) {
         DG_Logger::writeLog(DG_LogLevel::Error, 'Failed to encode JSON: ' . var_dump($decoded));
      }
      
      return $ret;
   }
   
   /**
    * Home-made JSON encode to replace mssing json_encode when needed.
    * 
    * @param unknown $decoded Value to be encoded.
    * @return string The JSON string.
    */
   private static function _jsonEncode($decoded) {
      if (self::isJsonObj($decoded)) {
         $ret = '';
         $first = true;
         foreach ($decoded as $k => $v) {
            if (!$first) $ret .= ',';
            $ret .= "\"$k\":" . self::_jsonEncode($v);
            $first = false;
         }
         
         return "\{$ret\}";
      } elseif (is_array($decoded)) {
         return '[' . implode(',', array_map(array(__CLASS__, __FUNCTION__), $decoded)) . ']';
      } elseif (is_bool($decoded)) {
         static $boolMap = array(false => 'false', true => 'true');
         return $boolMap[$decoded];
      } elseif (is_string($decoded)) {
         return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $decoded) . '"';
      }
      
      return (string)$decoded;
   }
   
   /**
    * Returns true for PHP objects and associative arrays.
    * 
    * @param unknown $decoded Value to be checked.
    * @return bool Whether passed value should be encoded as a JSON object.
    */
   private static function isJsonObj($decoded) {
      $ret = is_object($decoded);
      
      if (!$ret && is_array($decoded)) {
         $next = 0;
         foreach (array_keys($decoded) as $k) {
            if ($next++ !== $k) {
               $ret = true;
               break;
            }
         }
      }
      
      return $ret;
   }
}