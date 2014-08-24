<?php
defined('WPINC') OR exit;

/**
 * Encapsulates the logic required to maintain and read log files.
 */
class DG_Logger {
   /**
    * Appends DG log file if logging is enabled. The following format is used for each line:
    * datetime | level | entry | stacktrace (optional)
    *
    * @param int The level of serverity (should be passed using DG_LogLevel consts).
    * @param string $entry Value to be logged.
    * @param bool $stacktrace Whether to include full stack trace.
    * @param bool $force Whether to ignore logging flag and log no matter what.
    */
   public static function writeLog($level, $entry, $stacktrace = false, $force = false) {
      if ($force || self::logEnabled()) {
         $fp = fopen(self::getLogFileName(), 'a');
         if (false !== $fp) {
            $fields = array(time(), $level, $entry);
            
            if ($stacktrace) {
               ob_start();
               debug_print_backtrace();
               $trace = ob_get_contents();
               ob_end_clean();
            
               // Remove first item from backtrace as it's this function which is redundant.
               $trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);
            
               // Renumber backtrace items.
               $trace = preg_replace('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);
            
               // convert to relative path from WP root
               $fields[] = str_replace(ABSPATH, '', $trace);
            } else {
               $callers = debug_backtrace();
            
               // Remove first item from backtrace as it's this function which is redundant.
               $caller = $callers[1];
               $caller = (isset($caller['class']) ? $caller['class'] : '') . $caller['type'] . $caller['function'];
               $fields[2] = '(' . $caller . ') ' . $fields[2];
            }
            
            fputcsv($fp, $fields);
            fclose($fp);
         } // TODO: else
      }
   }
   
   /**
    * Reads the current blog's log file, placint the values in to a 2-dimensional array.
    * @param int $limit Max number of lines to read.
    * @return multitype:multitype:string|null The rows from the log file or null if no entries exist.
    */
   public static function readLog($limit = PHP_INT_MAX) {
      $fp = fopen(self::getLogFileName(), 'r');
      $ret = null;
      
      if ($fp !== false) {
         $ret = array();
         while (count($ret) < $limit && ($fields = fgetcsv($fp)) !== false) {
            $ret[] = $fields;
         }
      }
      
      return $ret;
   }
   
   /**
    * Clears the log file for the active blog.
    */
   public static function clearLog() {
      // we don't care if the file actually exists -- it won't when we're done
      @unlink(self::getLogFileName());
   }
   
   /**
    * @return bool Whether debug logging is currently enabled.
    */
   public static function logEnabled() {
      global $dg_options;
      return $dg_options['logging'];
   }
   
   /**
    * @return string Full path to log file for current blog.
    */
   private static function getLogFileName() {
      return DG_PATH . 'log/' . get_current_blog_id() . '.log';
   }
}

/**
 * LogLevel acts as an enumeration of all possible log levels.
 */
abstract class DG_LogLevel {
   /**
    * @var int Log level for anything that doesn't indicate a problem.
    */
   const Detail = 0;
   
   /**
    * @var int Log level for when something went wrong.
    */
   const Error = 1;
}