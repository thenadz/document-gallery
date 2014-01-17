<?php

/**
 * Thumber wraps the functionality required to
 * generate thumbnails for arbitrary documents.
 *
 * @author drossiter
 */
class DG_Thumber {
   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }

   /**
    * Wraps generation of thumbnails for various attachment filetypes.
    *
    * @param type $ID Document ID
    * @return string  URL to the thumbnail.
    */
   public static function getThumbnail($ID, $pg = 1) {
      static $timeout = false;
      if ($timeout === false) {
         $timeout = time();
      }

      // we cache thumbnails inside document_meta in attachment metadata
      // current values in document_metadata:
      // * thumb_path - System path to thumbnail image.
      // * thumb_url - URL pointing to the thumbnail for this document.
      // * thumber - Generator used to create thumb OR false if failed to gen.
      $meta = wp_get_attachment_metadata($ID, true);
      $meta = is_array($meta) ? $meta : array();
      $doc_meta = isset($meta['document_meta']) ? $meta['document_meta'] : array();

      // if we haven't saved a thumb, generate one
      if (!isset($doc_meta['thumber'])) {
         // prevent page timing out -- generate for no more than 30 sec
         if ((time() - $timeout) > 30) {
            return self::getDefaultThumbnail($ID, $pg);
         }

         $file = get_attached_file($ID);

         foreach (self::getThumbers() as $ext_preg => $thumber) {
            $ext_preg = '!\.(' . $ext_preg . ')$!i';

            if (preg_match($ext_preg, $file)
                && ($thumb = self::getThumbnailTemplate($thumber, $ID, $pg))) {
               $doc_meta['created_timestamp'] = time();
               $doc_meta['thumb_url'] = $thumb['url'];
               $doc_meta['thumb_path'] = $thumb['path'];
               $doc_meta['thumber'] = $thumber;

               $meta['document_meta'] = $doc_meta;
               wp_update_attachment_metadata($ID, $meta);
               break;
            }
         }
      }

      if (!isset($doc_meta['thumber']) || $doc_meta['thumber'] === false) {
         // only execute a database query if necessary
         if (!isset($doc_meta['thumber'])) {
            $doc_meta['thumber'] = false;

            $meta['document_meta'] = $doc_meta;
            wp_update_attachment_metadata($ID, $meta);
         }

         // fallback to default thumb for attachment type
         $url = self::getDefaultThumbnail($ID, $pg);
      } else {
         // use generated thumbnail
         $url = $doc_meta['thumb_url'];
      }

      return $url;
   }

   /*==========================================================================
    * AUDIO VIDEO THUMBNAILS
    *=========================================================================*/

   /**
    * Uses wp_read_video_metadata() and wp_read_audio_metadata() to retrieve
    * an embedded image to use as a thumbnail.
    *
    * NOTE: Caller must verify that WP version >= 3.6.
    *
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      Unused.
    * @return bool|string False on failure, URL to thumb on success.
    */
   public static function getAudioVideoThumbnail($ID, $pg = 1) {
      if(!file_exists(WP_ADMIN_DIR . '/includes/media.php')) {
         return false;
      }

      include_once WP_ADMIN_DIR . '/includes/media.php';

      $attachment = get_post($ID);
      $doc_path = get_attached_file($ID);

      if (preg_match('#^video/#', get_post_mime_type($attachment))) {
         $metadata = wp_read_video_metadata($doc_path);
      }
      elseif (preg_match('#^audio/#', get_post_mime_type($attachment))) {
         $metadata = wp_read_audio_metadata($doc_path);
      }

      // unsupported mime type || no embedded image present
      if(!isset($metadata) || empty($metadata['image']['data'])) {
         return false;
      }

      $ext = 'jpg';
      switch ($metadata['image']['mime']) {
         case 'image/gif':
            $ext = 'gif';
            break;
         case 'image/png':
            $ext = 'png';
            break;
      }

      $temp_file = self::getTempFile($ext);

      if (!$fp = @fopen($temp_file, 'wb')) {
         self::writeLog('Could not open file ' . $temp_file);
         return false;
      }

      if (!@fwrite($fp, $metadata['image']['data'])) {
         self::writeLog('Could not write file ' . $temp_file);
         return false;
      }

      fclose($fp);

      return $temp_file;
   }

   /**
    * @return array All extensions supported by WP Audio Video Media metadata.
    */
   private static function getAudioVideoExts() {
      return array_merge(wp_get_audio_extensions(), wp_get_video_extensions());
   }

   /*==========================================================================
    * IMAGICK THUMBNAILS
    *=========================================================================*/

   /**
    * Uses WP_Image_Editor_Imagick to generate thumbnails.
    *
    * NOTE: Caller must verify that Imagick is present and that the extension is supported.
    *
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      Unused.
    * @return bool|string False on failure, URL to thumb on success.
    */
   public static function getImagickThumbnail($ID, $pg = 1) {
      if (!file_exists(WP_INCLUDE_DIR .'/class-wp-image-editor.php')
          || !file_exists(WP_INCLUDE_DIR .'/class-wp-image-editor-imagick.php')) {
         return false;
      }

      include_once WP_INCLUDE_DIR .'/class-wp-image-editor.php';
      include_once WP_INCLUDE_DIR .'/class-wp-image-editor-imagick.php';

      $doc_path = get_attached_file($ID);

      // if we have a PDF then specify page number
      if (strcasecmp(self::getExt($doc_path), 'pdf') === 0) {
         $doc_path .= '[' . $pg - 1 . ']';
      }

      $img = new WP_Image_Editor_Imagick($doc_path);
      $err = $img->load();
      if(is_wp_error($err)) {
         self::writeLog('Failed to open file in Imagick: ' . $err->get_error_message());
         return false;
      }

      $temp_file = self::getTempFile();

      $err = $img->save($temp_file, 'image/png');
      if (is_wp_error($err)) {
         self::writeLog('Failed to save img in Imagick: ' . $err->get_error_message());
         return false;
      }

      return $temp_file;
   }

   /*==========================================================================
    * GHOSTSCRIPT THUMBNAILS
    *=========================================================================*/

   /**
    * Get thumbnail for document with given ID using Ghostscript. Imagick could
    * also handle this, but is *much* slower.
    *
    * NOTE: Caller must verify that exec and gs are available and that extension is supported.
    *
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      The page number to make thumbnail of -- index starts at 1.
    * @return bool|string False on failure, URL to thumb on success.
    */
   public static function getGhostscriptThumbnail($ID, $pg = 1) {
      static $gs = false;
      if ($gs === false) {
         $gs = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'gswin32c' : 'gs')
             . ' -sDEVICE=png16m -dFirstPage=%d -dLastPage=%d -dBATCH'
             . ' -dNOPAUSE -dPDFFitPage -sOutputFile=%s %s';
      }

      $doc_path = get_attached_file($ID);
      $temp_path = self::getTempFile();

      exec(sprintf($gs, $pg, $pg, $temp_path, $doc_path), $out, $ret);

      if ($ret != 0) {
         self::writeLog('Ghostscript failed: ' . $out);
         @unlink($temp_path);
         return false;
      }

      return $temp_path;
   }

   /**
    * @return array All extensions supported by Ghostscript.
    */
   private static function getGhostscriptExts() {
      return array('pdf');
   }

   /**
    * Checks whether we may call gs through exec().
    *
    * @staticvar bool $available
    * @return bool
    */
   private static function isGhostscriptAvailable() {
      static $available = null;

      if (is_null($available)) {
         $is_win = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
         $gs = $is_win ? 'gswin32c' : 'gs';

         $available = self::isExecAvailable();
         $exec = exec($is_win ? "where $gs" : "which $gs");
         $available = $available && !empty($exec);

         self::writeLog(($available ? 'Found' : 'Didn\'t find') . " the $gs executable.");
      }

      return $available;
   }

   /*==========================================================================
    * GOOGLE DRIVE VIEWER THUMBNAILS
    *=========================================================================*/

   /**
    * Get thumbnail for document with given ID from Google Drive Viewer.
    *
    * NOTE: Caller must verify that extension is supported.
    *
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      The page number to make thumbnail of -- index starts at 1.
    * @return bool|string False on failure, URL to thumb on success.
    */
   public static function getGoogleDriveThumbnail($ID, $pg = 1) {
      // User agent for Lynx 2.8.7rel.2 -- Why? Because I can.
      static $user_agent = "Lynx/2.8.7rel.2 libwww-FM/2.14 SSL-MM/1.4.1 OpenSSL/1.0.0a";
      static $timeout = 60;

      $google_viewer = "https://docs.google.com/viewer?url=%s&a=bi&pagenumber=%d&w=%d";
      $doc_url = wp_get_attachment_url($ID);
      $temp_file = self::getTempFile();

      // args for use in HTTP request
      $args = array(
          'timeout' => $timeout, // these requests can take a LONG time
          'redirection' => 5,
          'httpversion' => '1.0',
          'user-agent' => $user_agent,
          'blocking' => true,
          'headers' => array(),
          'cookies' => array(),
          'body' => null,
          'compress' => false,
          'decompress' => true,
          'sslverify' => true,
          'stream' => true,
          'filename' => $temp_file
      );

      // prevent PHP timeout before HTTP completes
      set_time_limit($timeout);

      $google_viewer = sprintf($google_viewer, urlencode($doc_url), (int) $pg, 150);

      // get thumbnail from Google Drive Viewer & check for error on return
      $response = wp_remote_get($google_viewer, $args);

      if (is_wp_error($response) || !preg_match('/[23][0-9]{2}/', $response['response']['code'])) {
         self::writeLog('Failed to retrieve thumbnail from Google: ' .
             (is_wp_error($response)
               ? $response->get_error_message()
               : $response['response']['message']));

         @unlink($temp_file);
         return false;
      }

      return $temp_file;
   }

   /**
    * @return array All extensions supported by Google Drive Viewer.
    */
   private static function getGoogleDriveExts() {
      return array(
         'tiff', 'bmp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
         'pdf', 'pages', 'ai', 'psd', 'dxf', 'svg', 'eps', 'ps', 'ttf'
      );
   }

   /*==========================================================================
    * DEFAULT THUMBNAILS
    *=========================================================================*/

   /**
    * Get thumbnail for document with given ID from default images.
    *
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      Unused.
    * @return string URL to thumbnail.
    */
   public static function getDefaultThumbnail($ID, $pg = 1) {
      $icon_url = DG_URL . 'icons/';

      $url = wp_get_attachment_url($ID);
      $ext = self::getExt($url);

      // handle images
      if (wp_attachment_is_image($ID) &&
          ($icon = wp_get_attachment_image_src($ID, 'thumbnail', false))) {
         $icon = $icon[0];
      }
      // default extension icon
      elseif ($name = self::getDefaultIcon($ext)) {
         $icon = $icon_url . $name;
      }
      // fallback to standard WP icons
      elseif ($icon = wp_get_attachment_image_src($ID, null, true)) {
         $icon = $icon[0];
      }
      // everything failed. This is bad...
      else {
         $icon = $icon_url . 'missing.png';
      }

      return $icon;
   }

   /**
    * Returns the name of the image to represent the filetype given.
    *
    * @staticvar array $exts
    * @param type $filename
    * @return bool
    */
   private static function getDefaultIcon($ext) {
      // Maps file ext to default image name.
      static $exts = array(
         // Most Common First
         'pdf'                                                       => 'pdf.png',

         // MS Office
         'doc|docx|docm|dotx|dotm'                                   => 'msdoc.png',
         'ppt|pot|pps|pptx|pptm|ppsx|ppsm|potx|potm|ppam|sldx|sldm'  => 'msppt.png',
         'xla|xls|xlt|xlw|xlsx|xlsm|xlsb|xltx|xltm|xlam'             => 'msxls.png',
         'mdb'                                                       => 'msaccess.png',

         // iWork
         'key'                                                       => 'key.png',
         'numbers'                                                   => 'numbers.png',
         'pages'                                                     => 'pages.png',

         // Images
         'jpg|jpeg|jpe|gif|png|bmp|tif|tiff|ico'                     => 'image.png',

         // Video formats
         'asf|asx|wmv|wmx|wm|avi|divx|flv|mov'                       => 'video.png',
         'qt|mpeg|mpg|mpe|mp4|m4v|ogv|webm|mkv'                      => 'video.png',

         // Audio formats
         'mp3|m4a|m4b|ra|ram|wav|ogg|oga|wma|wax|mka'                => 'audio.png',
         'midi|mid'                                                  => 'midi.png',

         // Text formats
         'txt|tsv|csv'                                               => 'text.png',
         'rtx'                                                       => 'rtx.png',
         'rtf'                                                       => 'rtf.png',
         'ics'                                                       => 'ics.png',
         'wp|wpd'                                                    => 'wordperfect.png',

         // Programming
         'html|htm'                                                  => 'html.png',
         'css'                                                       => 'css.png',
         'js'                                                        => 'javascript.png',
         'class'                                                     => 'java.png',
         'asc'                                                       => 'asc.png',
         'c'                                                         => 'c.png',
         'cc|cpp'                                                    => 'cpp.png',
         'h'                                                         => 'h.png',

         // Msc application formats
         'zip|tar|gzip|gz|bz2|tgz|7z|rar'                            => 'compressed.png',
         'exe'                                                       => 'exec.png',
         'swf'                                                       => 'shockwave.png',

         // OpenDocument formats
         'odt'                                                       => 'opendocument-text.png',
         'odp'                                                       => 'opendocument-presentation.png',
         'ods'                                                       => 'opendocument-spreadsheet.png',
         'odg'                                                       => 'opendocument-graphics.png',
         'odb'                                                       => 'opendocument-database.png',
         'odf'                                                       => 'opendocument-formula.png'
      );

      foreach ($exts as $ext_preg => $icon) {
         $ext_preg = '!(' . $ext_preg . ')$!i';
         if (preg_match($ext_preg, $ext)) {
            return $icon;
         }
      }

      return false;
   }

   /*==========================================================================
    * GENERAL THUMBNAIL HELPER FUNCTIONS
    *=========================================================================*/

   /**
    * @filter dg_thumbers Allows developers to filter the Thumbers used
    * for specific filetypes. Index is the regex to match file extensions
    * supported and the value is anything that can be accepted by call_user_func().
    * The function must take two parameters, 1st is the int ID of the attachment
    * to get a thumbnail for, 2nd is the page to take a thumbnail of
    * (may not be relevant for some filetypes).
    *
    * @return array
    */
   private static function getThumbers() {
      static $thumbers = false;

      if ($thumbers === false) {
         global $wp_version;
         $thumbers = array();

         // Audio/Video embedded images
         if (version_compare($wp_version, '3.6', '>=')) {
            $exts = implode('|', self::getAudioVideoExts());
            $thumbers[$exts] = array('DG_Thumber', 'getAudioVideoThumbnail');
         }

         // Ghostscript
         if (self::isGhostscriptAvailable()) {
            $exts = implode('|', self::getGhostscriptExts());
            $thumbers[$exts] = array('DG_Thumber', 'getGhostscriptThumbnail');
         }

         // Imagick
         if (file_exists(WP_INCLUDE_DIR .'/class-wp-image-editor.php')
             && file_exists(WP_INCLUDE_DIR .'/class-wp-image-editor-imagick.php')) {
            include_once WP_INCLUDE_DIR .'/class-wp-image-editor.php';
            include_once WP_INCLUDE_DIR .'/class-wp-image-editor-imagick.php';

            if (call_user_func(array('WP_Image_Editor_Imagick', 'test'))) {
               try {
                  $exts = @Imagick::queryFormats();
                  if($exts) {
                     $exts = implode('|', $exts);
                     $thumbers[$exts] = array('DG_Thumber', 'getImagickThumbnail');
                  }
               }
               catch (Exception $e) {

               }
            }
         }

         // Google Drive Viewer
         $exts = implode('|', self::getGoogleDriveExts());
         $thumbers[$exts] = array('DG_Thumber', 'getGoogleDriveThumbnail');

         // allow users to filter thumbers used
         $thumbers = apply_filters('dg_thumbers', $thumbers);

         // strip out anything that can't be called
         $thumbers = array_filter($thumbers, 'is_callable');
      }

      return $thumbers;
   }

   /**
    * Template that handles generating a thumbnail.
    *
    * @param callable $generator Takes ID and pg and returns path to temp file or false.
    * @param int $ID ID for the attachment that we need a thumbnail for.
    * @param int $pg Page number of the attachment to get a thumbnail for.
    * @return bool|array Array containing 'url' and 'path' values or false.
    */
   public static function getThumbnailTemplate($generator, $ID, $pg = 1) {
      // delegate thumbnail generation to $generator
      if (($temp_path = call_user_func($generator, $ID, $pg)) === false) {
         return false;
      }

      $doc_path = get_attached_file($ID);
      $doc_url = wp_get_attachment_url($ID);
      $dirname = dirname($doc_path);
      $basename = basename($doc_path);
      if (($len = strrpos($basename, '.')) === false) {
         $len = strlen($basename);
      }
      $extless = substr($basename, 0, $len);
      $ext = self::getExt($temp_path);

      $thumb_name = self::getUniqueThumbName($dirname, $extless, $ext);
      $thumb_path = $dirname . DIRECTORY_SEPARATOR . $thumb_name;

      if (!rename($temp_path, $thumb_path)) {
         @unlink($temp_path);
         return false;
      }

      // remove any pre-existing document metadata
      self::deleteThumbMeta($ID);

      // scale to no larger than 150x150px
      $img = wp_get_image_editor($thumb_path);

      if (is_wp_error($img)) {
         self::writeLog('Failed to get image editor: ' . $img->get_error_message());
         return false;
      }

      $img->resize(150, 150, false);
      $img->save($thumb_path);

      return array(
          'path' => $thumb_path,
          'url'  => preg_replace('#'.preg_quote($basename).'$#', $thumb_name, $doc_url));
   }

   /**
    * Constructs name for file's thumbnail, ensuring that it does not conflict
    * with any existing file.
    *
    * @param string $dirname  Directory where the document is located.
    * @param string $extless  Base name, less the extension.
    * @param string $ext      The extension of the image to be created.
    * @return string Name unique within the directory given, derived from the basename given.
    */
   private static function getUniqueThumbName($dirname, $extless, $ext = 'png') {
      return wp_unique_filename($dirname, str_replace('.', '-', $extless) . '-thumb.' . $ext);
   }

   /**
    * Caller must handle removal of the temp file when finished.
    *
    * @staticvar int $count
    * @param string $ext
    */
   private static function getTempFile($ext = 'png') {
      static $base = false;
      static $tmp;

      if ($base === false) {
         $base = md5('Document Gallery');
         $tmp = untrailingslashit(get_temp_dir());
      }

      $file = "$tmp/" . wp_unique_filename($tmp, "$base.$ext");
      self::writeLog("Temp file: $file");
      return $file;
   }

   /**
    * Removes the existing thumbnail/document meta for the attachment
    * with $ID, if such a thumbnail exists.
    *
    * @param int $ID
    */
   public static function deleteThumbMeta($ID) {
      $meta = wp_get_attachment_metadata($ID, true);

      if (is_array($meta) && isset($meta['document_meta'])) {
         if (isset($meta['document_meta']['thumber'])
             && $meta['document_meta']['thumber'] !== false) {
            @unlink($meta['document_meta']['thumb_path']);
         }

         unset($meta['document_meta']);
         wp_update_attachment_metadata($ID, $meta);
      }
   }

   /**
    * Checks whether exec() may be used.
    * Source: http://stackoverflow.com/a/12980534/866618
    *
    * @return bool Whether exec() is available.
    */
   private static function isExecAvailable() {
      static $available = null;

      if (is_null($available)) {
         $available = true;

         if (ini_get('safe_mode')) {
            $available = false;
         }
         else {
            $d = ini_get('disable_functions');
            $s = ini_get('suhosin.executor.func.blacklist');
            if ("$d$s") {
               $array = preg_split('/,\s*/', "$d,$s");
               if (in_array('exec', $array)) {
                  $available = false;
               }
            }
         }

         self::writeLog('exec() ' . ($available ? 'is' : 'isn\'t') . ' available.');
      }

      return $available;
   }

   /**
    * Formerly achieved with wp_check_filetype(), but it was only returning
    * valid results if the active user had permission to upload the given filetype.
    *
    * @param str $filename Name of the file to get extension from.
    * @return str|bool Returns the file extension on success, false on failure.
    */
   private static function getExt($filename) {
      foreach (wp_get_mime_types() as $ext_preg => $unused) {
         $ext_preg = '!\.(' . $ext_preg . ')$!i';
         if (preg_match($ext_preg, $filename, $ext_matches)) {
            return $ext_matches[1];
         }
      }

      return false;
   }

   /**
    * Appends error log with $entry if WordPress is in debug mode.
    *
    * @param str $entry
    */
   private static function writeLog($entry) {
      // TODO: back to error log
      //if (WP_DEBUG) {
         echo('<!-- DG: ' . print_r($entry, true) . ' -->' . PHP_EOL);
      //}
   }
}

?>