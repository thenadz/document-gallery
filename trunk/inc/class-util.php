<?php
defined('WPINC') OR exit;

/**
 * General utility function for Document Gallery.
 *
 * @author drossiter
 */
class DG_Document {
   private static $jsonEncode;
   
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
   
   private static function _jsonEncode($decoded) {
      $isJsonObj = is_object($decoded) || isAssociativeArray($decoded);
      
      if ($isJsonObj) {
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
         static $boolMap = array('false', 'true');
         return $boolMap[(int)$decoded];
      } elseif (is_string($decoded)) {
         return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $decoded) . '"';
      } else {
         return (string)$decoded;
      }
   }
   
   private static function isAssociativeArray($array) {
      $ret = false;
      
      if (is_array($array)) {
         $next = 0;
         foreach (array_keys($array) as $k) {
            if ($next++ !== $k) {
               $ret = true;
               break;
            }
         }
      }
      
      return $ret;
   }
}