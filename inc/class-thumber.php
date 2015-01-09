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
    * @param $skeleton bool When true, values that require computation will be
    *        skipped. Useful when only structure of options is needed.
    * @return array
    */
   public static function getDefaultThumbers($skeleton = false) {
      $gs_active = $imagick_active = null;
      if (!$skeleton) {
         $gs_active = (bool)self::getGhostscriptExecutable();
         $imagick_active = self::isImagickAvailable();
      }

      return array('av' => true, 'gs' => $gs_active,
          'imagick' => $imagick_active, 'google' => false);
   }

   /**
    * Sets the thumbnail for the given attachment ID.
    * 
    * @param int $ID Document ID.
    * @param string $path System path to thumbnail.
    * @return bool Whether set was successful.
    */
   public static function setThumbnail($ID, $path, $generator = 'unknown') {
      return self::thumbnailGenerationHarness($generator, $ID, $path);
   }
   
   /**
    * Wraps generation of thumbnails for various attachment filetypes.
    *
    * @param int $ID  Document ID
    * @param int $pg  Page number to get thumb from.
    * @return str     URL to the thumbnail.
    */
   public static function getThumbnail($ID, $pg = 1) {
      static $start = null;
      if (is_null($start)) {
         $start = time();
      }

      $options = self::getOptions();

      // if we haven't saved a thumb, generate one
      if (empty($options['thumbs'][$ID])) {
         // prevent page timing out or user waiting too long for page
         if ((time() - $start) > $options['timeout']) {
            return self::getDefaultThumbnail($ID, $pg);
         }

         // do the processing
         $file = get_attached_file($ID);

         foreach (self::getThumbers() as $ext_preg => $thumber) {
            $ext_preg = '!\.(?:' . $ext_preg . ')$!i';

            if (preg_match($ext_preg, $file)) {
               if (DG_Logger::logEnabled()) {
                  $toLog = sprintf(__('Attempting to generate thumbnail for attachment #%d with (%s)',
                          'document-gallery'), $ID, is_array($thumber) ? implode('::',$thumber) : print_r($thumber, true));
                  DG_Logger::writeLog(DG_LogLevel::Detail, $toLog);
               }
               
               if (self::thumbnailGenerationHarness($thumber, $ID, $pg)) {
                  break;
               }
            }
         }
      }

      $new = empty($options['thumbs'][$ID]);
      if ($new || empty($options['thumbs'][$ID]['thumber'])) {
         if ($new) {
            $options['thumbs'][$ID] = array('timestamp' => time());
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
         DG_Logger::writeLog(DG_LogLevel::Error, __('Could not open file: ', 'document-gallery') . $temp_file);
         return false;
      }

      if (!@fwrite($fp, $metadata['image']['data'])) {
         DG_Logger::writeLog(DG_LogLevel::Error, __('Could not write file: ', 'document-gallery') . $temp_file);
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
         DG_Logger::writeLog(
            DG_LogLevel::Error,
            __('Failed to open file in Imagick: ', 'document-gallery') .
            $err->get_error_message());
         return false;
      }

      $temp_file = self::getTempFile();

      $err = $img->save($temp_file, 'image/png');
      if (is_wp_error($err)) {
         DG_Logger::writeLog(
            DG_LogLevel::Error,
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
                . ' -dLastPage=%d -dBATCH -dNOPAUSE -dPDFFitPage -sOutputFile=%s %s 2>&1';
         }
      }

      if (false === $gs) {
         return false;
      }

      $doc_path = get_attached_file($ID);
      $temp_path = self::getTempFile();

      exec(sprintf($gs, $pg, $pg, $temp_path, $doc_path), $out, $ret);

      if ($ret != 0) {
         DG_Logger::writeLog(DG_LogLevel::Error, __('Ghostscript failed: ', 'document-gallery') . print_r($out));
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
         if (!$executable) {
            return $executable;
         }

         // find on Windows system
         if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            // look for environment variable
            $executable = getenv('GSC');
            if ($executable) {
               return $executable;
            }

            // hope GS in the path
            $executable = exec('where gswin*c.exe');
            if (!empty($executable)) {
               return $executable;
            }

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
         // filesystem check in most common location
         exec('test -e /usr/bin/gs', $dummy, $ret);
         $executable = ($ret === 0) ? '/usr/bin/gs' : false;
         
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
      @set_time_limit($timeout);

      $options = self::getOptions();
      $google_viewer = sprintf($google_viewer, urlencode($doc_url), (int)$pg, $options['width']);

      // get thumbnail from Google Drive Viewer & check for error on return
      $response = wp_remote_get($google_viewer, $args);

      if (is_wp_error($response) || !preg_match('/[23][0-9]{2}/', $response['response']['code'])) {
         DG_Logger::writeLog(DG_LogLevel::Warning, __('Failed to retrieve thumbnail from Google: ', 'document-gallery') .
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
    * @return bool Whether Google Drive can access files on this system.
    */
   public static function isGoogleDriveAvailable() {
      static $available = null;
      
      if (is_null($available)) {
         // to check if we're visible externally, retrieve image for file we know exists.
         $user_agent = 'Lynx/2.8.7rel.2 libwww-FM/2.14 SSL-MM/1.4.1 OpenSSL/1.0.0a';
         $google_viewer = 'https://docs.google.com/viewer?url=%s&a=bi&pagenumber=1&w=1';
         $google_viewer = sprintf($google_viewer, urlencode(DG_URL . 'LICENSE.txt'));
         
         // args for use in HTTP request
         $args = array(
             'redirection' => 5,
             'httpversion' => '1.0',
             'user-agent' => $user_agent,
             'blocking' => true,
             'headers' => array(),
             'cookies' => array(),
             'body' => null,
             'compress' => false,
             'decompress' => true,
             'sslverify' => true
         );
         
         $response = wp_remote_get($google_viewer, $args);

         $available = (!is_wp_error($response) && $response['response']['code'] != 404);
      }
      
      return $available;
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
    * MANUAL THUMBNAILS
    *=========================================================================*/

   /**
    * Set thumbnail for document with given ID from uploaded file.
    *
    * @param str $ID     The attachment ID to retrieve thumbnail for.
    * @param int $pg     The page number to make thumbnail of -- index starts at 1.
    * @return bool|str   False on failure, URL to thumb on success.
    */
   public static function manual($ID, $pg = 1) {
      // checking if any file was delivered
      if (!isset($_FILES['file']))
         return false;
      // we gonna process only first file
      if ( !is_array($_FILES['file']['error']) )
      {
         $upload_err  = $_FILES['file']['error'];
         $upload_name = $_FILES['file']['tmp_name'];
         $upload_size = $_FILES['file']['size'];
         $upload_type = $_FILES['file']['type'];
      } else {
         $upload_err  = $_FILES['file']['error'][0];
         $upload_name = $_FILES['file']['tmp_name'][0];
         $upload_size = $_FILES['file']['size'][0];
         $upload_type = $_FILES['file']['type'][0];
      }
      $info = getimagesize($upload_name);
      if ($info) {
         if ($info['mime']!=$upload_type) {// NB_concern: Should be a "problem" at all or should we just use right extension for the thumbnail?
            DG_Logger::writeLog(
               DG_LogLevel::Warning,
               __('File extension doesn\'t match the MIME type of the image: ', 'document-gallery') .
               $pg.' - '.$info['mime']);// NB_concern: should it be Warning or Error? I believe it's not a software problem, it's issue of a user so - Warning
            return false;
         }
         if ($upload_size>5242880) {// NB_concern: What limit should we use? It should correspond with limits in admin JavaScript and Thumbnails generation code
            DG_Logger::writeLog(
               DG_LogLevel::Warning,
               __('Uploaded file size exceeds the allowable limit: ', 'document-gallery') .
               $pg.' - '.$upload_size.'b');// NB_concern: should it be Warning or Error? I believe it's not a software problem, it's issue of a user so - Warning
            return false;
         }
      } else {
         DG_Logger::writeLog(
            DG_LogLevel::Warning,
            __('Uploaded file is not an image: ', 'document-gallery') .
            $pg);// NB_concern: should it be Warning or Error? I believe it's not a software problem, it's issue of a user so - Warning
         return false;
      }
      if ($upload_err == UPLOAD_ERR_OK && $upload_size > 0) {
         $temp_file = $upload_name;
      } else {
         DG_Logger::writeLog(
            DG_LogLevel::Error,
            __('Failed to get uploaded file: ', 'document-gallery') .
            $upload_err);
         return false;
      }

      return $temp_file;
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
    *      + timestamp - When the thumbnail was generated (or generation failed).
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
    *      + timestamp - When the thumbnail was generated (or generation failed).
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
         if (DG_Logger::logEnabled()) {
            if (count($thumbers) > 0) {
               $entry = __('Thumbnail Generators: ', 'document-gallery');
               foreach ($thumbers as $k => $v) {
                  $thumber = is_array($v) ? implode('::', $v) : print_r($v, true);
                  
                  // TODO: The following works for all internal regexes, but may have unpredictable
                  // results if developer adds additional thumbnail generators using different regexes
                  $filetypes = str_replace('|', ', ', $k);
                  
                  $entry .= PHP_EOL . "$thumber: $filetypes";
               }
            } else {
               $entry = __('No thumbnail generators enabled.', 'document-gallery');
            }
            DG_Logger::writeLog(DG_LogLevel::Detail, $entry);
         }
      }

      return $thumbers;
   }

   /**
    * Template that handles generating a thumbnail.
    * 
    * If image has already been generated through other means, $pg may be set to the system path where the
    * thumbnail is located. In this case, $generator will not be invoked, but *will* be kept for historical purposes.
    *
    * @param callable $generator Takes ID and pg and returns path to temp file or false.
    * @param int $ID      ID for the attachment that we need a thumbnail for.
    * @param int|str $pg  Page number of the attachment to get a thumbnail for or the system path to the image to be used.
    * @return bool        Whether generation was successful.
    */
   private static function thumbnailGenerationHarness($generator, $ID, $pg = 1) {
      // handle system page in $pg variable
      if (is_string($pg) && !is_numeric($pg)) {
         $temp_path = $pg;
      }
      // delegate thumbnail generation to $generator
      elseif (false === ($temp_path = call_user_func($generator, $ID, $pg))) {
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
         DG_Logger::writeLog(
            DG_LogLevel::Error,
            __('Failed to get image editor: ', 'document-gallery') .
            $img->get_error_message());
         return false;
      }

      $options = self::getOptions();
      $img->resize($options['width'], $options['height'], false);
      $err = $img->save($thumb_path);

      if (is_wp_error($err)) {
         DG_Logger::writeLog(
            DG_LogLevel::Error,
            __('Failed to save image: ', 'document-gallery') .
            $err->get_error_message());
         return false;
      }

      // do some cleanup
      @unlink($temp_path);
      self::deleteThumbMeta($ID);

      // store new thumbnail in DG options
      $options['thumbs'][$ID] = array(
            'timestamp'         => time(),
            'thumb_url'         => preg_replace('#'.preg_quote($basename).'$#', $thumb_name, $doc_url),
            'thumb_path'        => $thumb_path,
            'thumber'           => $generator
      );
      self::setOptions($options);
      
      return true;
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
    * Removes the existing thumbnail/document meta for the attachment(s)
    * with the ID(s), if such a thumbnails exists.
    *
    * @param int|array $ids
    */
   public static function deleteThumbMeta($ids) {
      $options = self::getOptions();
      $modified = false;

      foreach ((array)$ids as $id) {
         if (isset($options['thumbs'][$id])) {
            if (isset($options['thumbs'][$id]['thumber'])) {
               @unlink($options['thumbs'][$id]['thumb_path']);
            }

            unset($options['thumbs'][$id]);
            $modified = true;
         }
      }
      
      if ($modified) { self::setOptions($options); }
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