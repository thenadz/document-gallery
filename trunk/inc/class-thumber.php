<?php
defined('WPINC') OR exit;

/**
 * Thumber wraps the functionality required to
 * generate thumbnails for arbitrary documents.
 *
 * @author drossiter
 */
class DG_Thumber {

   /**
    * Returns the default mapping of thumber slug to whether it is active or not.
    * @return array
    */
   public static function getDefaultThumbers() {
      $gs_active = (bool)self::getGhostscriptExecutable();
      $imagick_active = self::isImagickAvailable();

      return array('av' => true, 'gs' => $gs_active,
          'imagick' => $imagick_active, 'google' => false);
   }

   /**
    * Wraps generation of thumbnails for various attachment filetypes.
    *
    * @param int $ID  Document ID
    * @param int $pg  Page number to get thumb from.
    * @return str     URL to the thumbnail.
    */
   public static function getThumbnail($ID, $pg = 1) {
      static $timeout = null;
      if (is_null($timeout)) {
         $timeout = time();
      }

      $options = self::getOptions();

      // if we haven't saved a thumb, generate one
      if (!isset($options['thumbs'][$ID])) {
         // prevent page timing out -- generate for no more than 30 sec
         if ((time() - $timeout) > 30) {
            return self::getDefaultThumbnail($ID, $pg);
         }

         // do the processing
         $file = get_attached_file($ID);

         foreach (self::getThumbers() as $ext_preg => $thumber) {
            $ext_preg = '!\.(' . $ext_preg . ')$!i';

            if (preg_match($ext_preg, $file)
                && ($thumb = self::getThumbnailTemplate($thumber, $ID, $pg))) {
               $options['thumbs'][$ID] = array(
                   'created_timestamp' => time(),
                   'thumb_url'         => $thumb['url'],
                   'thumb_path'        => $thumb['path'],
                   'thumber'           => $thumber
               );
               self::setOptions($options);
               break;
            }
         }
      }

      if (!isset($options['thumbs'][$ID]) || false === $options['thumbs'][$ID]) {
         if (!isset($options['thumbs'][$ID])) {
            $options['thumbs'][$ID] = false;
            self::setOptions($options);
         }

         // fallback to default thumb for attachment type
         $url = self::getDefaultThumbnail($ID, $pg);
      } else {
         // use generated thumbnail
         $url = $options['thumbs'][$ID]['thumb_url'];
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
    * @param str $ID    The attachment ID to retrieve thumbnail from.
    * @param int $pg    Unused.
    * @return bool|str  False on failure, URL to thumb on success.
    */
   public static function getAudioVideoThumbnail($ID, $pg = 1) {
      include_once DG_WPADMIN_PATH . 'includes/media.php';

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
         DocumentGallery::writeLog(__('Could not open file: ', 'document-gallery') . $temp_file);
         return false;
      }

      if (!@fwrite($fp, $metadata['image']['data'])) {
         DocumentGallery::writeLog(__('Could not write file: ', 'document-gallery') . $temp_file);
         fclose($fp);
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
    * @param int $ID    The attachment ID to retrieve thumbnail from.
    * @param int $pg    The page to get the thumbnail of.
    * @return bool|str  False on failure, URL to thumb on success.
    */
   public static function getImagickThumbnail($ID, $pg = 1) {
      include_once DG_PATH . 'inc/class-image-editor-imagick.php';

      $doc_path = get_attached_file($ID);
      $img = new DG_Image_Editor_Imagick($doc_path, $pg - 1);
      $err = $img->load();
      if(is_wp_error($err)) {
         DocumentGallery::writeLog(
             __('Failed to open file in Imagick: ', 'document-gallery') .
             $err->get_error_message());
         return false;
      }

      $temp_file = self::getTempFile();

      $err = $img->save($temp_file, 'image/png');
      if (is_wp_error($err)) {
         DocumentGallery::writeLog(
             __('Failed to save image in Imagick: ', 'document-gallery') .
             $err->get_error_message());
         return false;
      }

      return $temp_file;
   }

   /**
    * @return bool Whether WP_Image_Editor_Imagick can be used on this system.
    */
   public static function isImagickAvailable() {
      static $ret = null;

      if (is_null($ret)) {
         include_once DG_WPINC_PATH . 'class-wp-image-editor.php';
         include_once DG_WPINC_PATH . 'class-wp-image-editor-imagick.php';
         $ret = WP_Image_Editor_Imagick::test();
      }

      return $ret;
   }

   /*==========================================================================
    * GHOSTSCRIPT THUMBNAILS
    *=========================================================================*/

   /**
    * Get thumbnail for document with given ID using Ghostscript. Imagick could
    * also handle this, but is *much* slower.
    *
    * @param int $ID    The attachment ID to retrieve thumbnail from.
    * @param int $pg    The page number to make thumbnail of -- index starts at 1.
    * @return bool|str  False on failure, URL to thumb on success.
    */
   public static function getGhostscriptThumbnail($ID, $pg = 1) {
      static $gs = null;

      if (is_null($gs)) {
         $options = self::getOptions();
         $gs = $options['gs'];
         
         if (false !== $gs) {
            $gs = escapeshellarg($gs) . ' -sDEVICE=png16m -dFirstPage=%d'
                . ' -dLastPage=%d -dBATCH -dNOPAUSE -dPDFFitPage -sOutputFile=%s %s';
         }
      }

      if (false === $gs) {
         return false;
      }

      $doc_path = get_attached_file($ID);
      $temp_path = self::getTempFile();

      exec(sprintf($gs, $pg, $pg, $temp_path, $doc_path), $out, $ret);

      if ($ret != 0) {
         DocumentGallery::writeLog(__('Ghostscript failed: ', 'document-gallery') . print_r($out));
         @unlink($temp_path);
         return false;
      }

      return $temp_path;
   }

   /**
    * @return array All extensions supported by Ghostscript.
    */
   private static function getGhostscriptExts() {
      return array('pdf', 'ps', 'eps');
   }

   /**
    * Dynamically determines whether we may call gs through exec().
    * 
    * NOTE: This does not check the options for gs path. Don't use in
    * thumbnail generation as it's slow and not configurable.
    *
    * @return bool|str If available, returns exe path. False otherwise.
    */
   public static function getGhostscriptExecutable() {
      static $executable = null;

      if (is_null($executable)) {
         // we must be able to exec()
         $executable = self::isExecAvailable();
         if (!$executable) return $executable;

         // find on Windows system
         if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            // look for environment variable
            $executable = getenv('GSC');
            if ($executable) return $executable;

            // hope GS in the path
            $executable = exec('where gswin*c.exe');
            if (!empty($executable)) return $executable;

            // look directly in filesystem
            // 64- or 32-bit binary
            $executable = exec('dir /o:n/s/b "C:\Program Files\gs\*gswin*c.exe"');
            if (!empty($executable)) {
               return $executable;
            }

            // 32-bit binary on 64-bit OS
            $executable = exec('dir /o:n/s/b "C:\Program Files (x86)\gs\*gswin32c.exe"');
            $executable = empty($executable) ? false : $executable;
            return $executable;
         }

         // handle Linux systems
         $executable = exec('which gs');
         if (!empty($executable)) {
            return $executable;
         }
         
         // GoDaddy and others aren't setup in such a way that
         // the above works so we need to fallback to a direct
         // filesystem check
         $executable = file_exists('/usr/bin/gs') ? '/usr/bin/gs' : false;
         
         return $executable;
      }

      return $executable;
   }
   
   /**
    * @return bool Whether we can use the GS executable.
    */
   public static function isGhostscriptAvailable() {
      static $ret = null;
      
      if (is_null($ret)) {
         $options = self::getOptions();
         $ret = $options['gs'] && self::isExecAvailable();
      }
      
      return $ret;
   }

   /*==========================================================================
    * GOOGLE DRIVE VIEWER THUMBNAILS
    *=========================================================================*/

   /**
    * Get thumbnail for document with given ID from Google Drive Viewer.
    *
    * NOTE: Caller must verify that extension is supported.
    *
    * @param str $ID     The attachment ID to retrieve thumbnail for.
    * @param int $pg     The page number to make thumbnail of -- index starts at 1.
    * @return bool|str   False on failure, URL to thumb on success.
    */
   public static function getGoogleDriveThumbnail($ID, $pg = 1) {
      // User agent for Lynx 2.8.7rel.2 -- Why? Because I can.
      static $user_agent = 'Lynx/2.8.7rel.2 libwww-FM/2.14 SSL-MM/1.4.1 OpenSSL/1.0.0a';
      static $timeout = 60;

      $google_viewer = 'https://docs.google.com/viewer?url=%s&a=bi&pagenumber=%d&w=%d';
      $doc_url = wp_get_attachment_url($ID);
      if (!$doc_url) {
         return false;
      }

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

      $options = self::getOptions();
      $google_viewer = sprintf($google_viewer, urlencode($doc_url), (int)$pg, $options['width']);

      // get thumbnail from Google Drive Viewer & check for error on return
      $response = wp_remote_get($google_viewer, $args);

      if (is_wp_error($response) || !preg_match('/[23][0-9]{2}/', $response['response']['code'])) {
         DocumentGallery::writeLog(__('Failed to retrieve thumbnail from Google: ', 'document-gallery') .
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

   /**
    * TODO: Currently always returns true.
    * @return bool Whether Google Drive can access files on this system.
    */
   public static function isGoogleDriveAvailable() {
      return true;
   }

   /*==========================================================================
    * DEFAULT THUMBNAILS
    *=========================================================================*/

   /**
    * Get thumbnail for document with given ID from default images.
    *
    * @param str $ID  The attachment ID to retrieve thumbnail from.
    * @param int $pg  Unused.
    * @return str     URL to thumbnail.
    */
   public static function getDefaultThumbnail($ID, $pg = 1) {
      $options = self::getOptions();
      $width = $options['width'];
      $height = $options['height'];
      $icon_url = DG_URL . 'assets/icons/';

      $url = wp_get_attachment_url($ID);
      $ext = self::getExt($url);

      // handle images
      if ($icon = image_downsize($ID, array($width, $height))) {
         $icon = $icon[0];
      }
      // default extension icon
      elseif ($name = self::getDefaultIcon($ext)) {
         $icon = $icon_url . $name;
      }
      // fallback to standard WP icons
      elseif (!$icon = wp_mime_type_icon($ID)) {
         // everything failed. This is bad...
         $icon = $icon_url . 'missing.png';
      }

      return $icon;
   }

   /**
    * Returns the name of the image to represent the filetype given.
    *
    * @param str $ext
    * @return str
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
    * @return array WP_Post objects for each attachment that has been processed.
    */
   public static function getThumbed() {
      $options = self::getOptions();
      $args = array(
         'post_type'     => 'attachment',
         'post_status'   => 'inherit',
         'post_per_page' => -1,
         'post__in'      => array_keys($options['thumbs'])
      );

      return count($args['post__in']) ? get_posts($args) : array();
   }

   /**
    * Key: Attachment ID
    * Val: array
    *      + created_timestamp - When the thumbnail was generated.
    *      + thumb_path - System path to thumbnail image.
    *      + thumb_url - URL pointing to the thumbnail for this document.
    *      + thumber - Generator used to create thumb OR false if failed to gen.
    * @return array|null Thumber options from DB or null if options not initialized.
    */
   public static function getOptions($blog = null) {
      $options = DocumentGallery::getOptions($blog);
      return $options['thumber'];
   }

   /**
    * Key: Attachment ID
    * Val: array
    *      + created_timestamp - When the thumbnail was generated.
    *      + thumb_path - System path to thumbnail image.
    *      + thumb_url - URL pointing to the thumbnail for this document.
    *      + thumber - Generator used to create thumb OR false if failed to gen.
    * @param array $options Thumber options to store in DB
    */
   private static function setOptions($options, $blog = null) {
      $dg_options = DocumentGallery::getOptions($blog);
      $dg_options['thumber'] = $options;
      DocumentGallery::setOptions($dg_options, $blog);
   }

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
      static $thumbers = null;

      if (is_null($thumbers)) {
         $options = self::getOptions();
         $active = $options['active'];
         $thumbers = array();

         // Audio/Video embedded images
         if ($active['av']) {
            $exts = implode('|', self::getAudioVideoExts());
            $thumbers[$exts] = array(__CLASS__, 'getAudioVideoThumbnail');
         }

         // Ghostscript
         if ($active['gs'] && self::isGhostscriptAvailable()) {
            $exts = implode('|', self::getGhostscriptExts());
            $thumbers[$exts] = array(__CLASS__, 'getGhostscriptThumbnail');
         }

         // Imagick
         if ($active['imagick'] && self::isImagickAvailable()) {
            include_once DG_PATH . 'inc/class-image-editor-imagick.php';
            if ($exts = DG_Image_Editor_Imagick::query_formats()) {
               $exts = implode('|', $exts);
               $thumbers[$exts] = array(__CLASS__, 'getImagickThumbnail');
            }
         }

         // Google Drive Viewer
         if ($active['google']) {
            $exts = implode('|', self::getGoogleDriveExts());
            $thumbers[$exts] = array(__CLASS__, 'getGoogleDriveThumbnail');
         }

         // allow users to filter thumbers used
         $thumbers = apply_filters('dg_thumbers', $thumbers);
         
         // strip out anything that can't be called
         $thumbers = array_filter($thumbers, 'is_callable');
         
         // log which thumbers are being used
         if (DocumentGallery::logEnabled()) {
            if (count($thumbers) > 0) {
               $entry = __('Thumbnail Generators: ', 'document-gallery');
               foreach ($thumbers as $k => $v) {
                  $entry .= '{' . $k . ' => ' . print_r($v, true) . '} ';
               }
            } else {
               $entry = __('No thumbnail generators enabled.', 'document-gallery');
            }
            DocumentGallery::writeLog($entry);
         }
      }

      return $thumbers;
   }

   /**
    * Template that handles generating a thumbnail.
    *
    * @param callable $generator Takes ID and pg and returns path to temp file or false.
    * @param int $ID      ID for the attachment that we need a thumbnail for.
    * @param int $pg      Page number of the attachment to get a thumbnail for.
    * @return bool|array  Array containing 'url' and 'path' values or false.
    */
   public static function getThumbnailTemplate($generator, $ID, $pg = 1) {
      // delegate thumbnail generation to $generator
      if (false === ($temp_path = call_user_func($generator, $ID, $pg))) {
         return false;
      }

      // get some useful stuff
      $doc_path = get_attached_file($ID);
      $doc_url = wp_get_attachment_url($ID);
      $dirname = dirname($doc_path);
      $basename = basename($doc_path);
      if (false === ($len = strrpos($basename, '.'))) {
         $len = strlen($basename);
      }
      $extless = substr($basename, 0, $len);
      $ext = self::getExt($temp_path);

      $thumb_name = self::getUniqueThumbName($dirname, $extless, $ext);
      $thumb_path = $dirname . DIRECTORY_SEPARATOR . $thumb_name;

      // scale generated image down
      $img = wp_get_image_editor($temp_path);

      if (is_wp_error($img)) {
         DocumentGallery::writeLog(
             __('Failed to get image editor: ', 'document-gallery') .
             $img->get_error_message());
         return false;
      }

      $options = self::getOptions();
      $img->resize($options['width'], $options['height'], false);
      $err = $img->save($thumb_path);

      if (is_wp_error($err)) {
         DocumentGallery::writeLog(
             __('Failed to save image: ', 'document-gallery') .
             $err->get_error_message());
         return false;
      }

      // do some cleanup
      @unlink($temp_path);
      self::deleteThumbMeta($ID);

      return array(
          'path' => $thumb_path,
          'url'  => preg_replace('#'.preg_quote($basename).'$#', $thumb_name, $doc_url));
   }

   /**
    * Caller should handle removal of the temp file when finished.
    *
    * @param str $ext
    */
   private static function getTempFile($ext = 'png') {
      static $base = null;
      static $tmp;

      if (is_null($base)) {
         $base = md5(time());
         $tmp = untrailingslashit(get_temp_dir());
      }

      return $tmp . DIRECTORY_SEPARATOR . wp_unique_filename($tmp, "$base.$ext");
   }

   /**
    * Constructs name for file's thumbnail, ensuring that it does not conflict
    * with any existing file.
    *
    * @param str $dirname  Directory where the document is located.
    * @param str $extless  Base name, less the extension.
    * @param str $ext      The extension of the image to be created.
    * @return str          Name unique within the directory given, derived from the basename given.
    */
   private static function getUniqueThumbName($dirname, $extless, $ext = 'png') {
      return wp_unique_filename($dirname, str_replace('.', '-', $extless) . '-thumb.' . $ext);
   }

   /**
    * Removes the existing thumbnail/document meta for the attachment
    * with $ID, if such a thumbnail exists.
    *
    * @param int $ID
    */
   public static function deleteThumbMeta($ID) {
      $options = self::getOptions();

      if (isset($options['thumbs'][$ID])) {
         if (false !== $options['thumbs'][$ID]) {
            @unlink($options['thumbs'][$ID]['thumb_path']);
         }

         unset($options['thumbs'][$ID]);
         self::setOptions($options);
      }
   }

   /**
    * Checks whether exec() may be used.
    * Source: http://stackoverflow.com/a/12980534/866618
    *
    * @return bool Whether exec() is available.
    */
   public static function isExecAvailable() {
      static $available = null;

      if (is_null($available)) {
         $available = true;

         if (ini_get('safe_mode')) {
            $available = false;
         } else {
            $d = ini_get('disable_functions');
            $s = ini_get('suhosin.executor.func.blacklist');
            if ("$d$s") {
               $array = preg_split('/,\s*/', "$d,$s");
               $available = !in_array('exec', $array);
            }
         }
      }

      return $available;
   }

   /**
    * Formerly achieved with wp_check_filetype(), but it was only returning
    * valid results if the active user had permission to upload the given filetype.
    *
    * @param str $filename  Name of the file to get extension from.
    * @return str|bool      Returns the file extension on success, false on failure.
    */
   private static function getExt($filename) {
      foreach (array_keys(wp_get_mime_types()) as $ext_preg) {
         $ext_preg = '!\.(' . $ext_preg . ')$!i';
         if (preg_match($ext_preg, $filename, $ext_matches)) {
            return $ext_matches[1];
         }
      }

      return false;
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}

?>