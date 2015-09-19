<?php

/**
 * Thumber wraps the functionality required to
 * generate thumbnails for arbitrary documents.
 *
 * @author drossiter
 */
class DG_Thumber {
   /**
    * Blocks instantiation. All functionality is static.
    */
   private function __construct() {

   }

   /**
    * @param type $ID Document ID
    * @return string  URL to the thumbnail.
    */
   public static function getThumbnail($ID) {
      if (!($url = wp_get_attachment_thumb_url($ID))
          && !($url = self::getGhostscriptThumbnail($ID))
          && !($url = self::getGoogleDriveThumbnail($ID))) {
         $url = self::getDefaultThumbnail($ID);
      }

      return $url;
   }

   public static function getAudioVideoThumbnail($ID) {
      global $wp_version;

      // can only pull images from videos/audio after 3.6
      if(version_compare($wp_version, '3.6', '<')) return false;

      $attachment = get_post($ID);
      $doc_path = get_attached_file($ID);
      $doc_url = wp_get_attachment_url($ID);

      if (preg_match('#^video/#', get_post_mime_type($attachment))) {
         $metadata = wp_read_video_metadata($doc_path);
      }
      elseif (preg_match('#^audio/#', get_post_mime_type($attachment))) {
         $metadata = wp_read_audio_metadata($doc_path);
      }
      else {
         return false;
      }

      if (!empty($metadata['image']['data'])) {
         $ext = '.jpg';
         switch ($metadata['image']['mime']) {
            case 'image/gif':
               $ext = '.gif';
               break;
            case 'image/png':
               $ext = '.png';
               break;
         }

         self::deleteExistingThumb($ID);

         $dirname = dirname($doc_path);
         $basename = basename($doc_path);
         $thumb_name = self::getUniqueThumbName(
             $dirname,substr($basename, 0, strrpos($basename, '.')), $ext);
         $basename = str_replace('.', '-', $basename) . '-thumb' . $ext;
         $uploaded = wp_upload_bits($basename, '', $metadata['image']['data']);
         if (false === $uploaded['error']) {
            $attachment = array(
                'post_mime_type' => $metadata['image']['mime'],
                'post_type' => 'attachment',
                'post_content' => '',
            );
            $sub_attachment_id = wp_insert_attachment($attachment, $uploaded['file']);
            $attach_data = wp_generate_attachment_metadata($sub_attachment_id, $uploaded['file']);
            wp_update_attachment_metadata($sub_attachment_id, $attach_data);
            update_post_meta($attachment_id, '_thumbnail_id', $sub_attachment_id);
         }
      }
   }

   /**
    * Get thumbnail for document with given ID using Ghostscript
    * .
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      The page number to make thumbnail of -- index starts at 1.
    * @return bool|string False on failure, URL to thumb on success.
    */
   public static function getGhostscriptThumbnail($ID, $pg = 1) {
      static $gs = false;
      static $exts = array('pdf');

      if (!self::isGhostscriptAvailable())
         return false;

      if (!$gs) {
         // I don't understand why anyone would run a Windows server...
         $gs = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'gswin32c' : 'gs')
             . ' -sDEVICE=png16m -dFirstPage=%d -dLastPage=%d -dBATCH'
             . ' -dNOPAUSE -dPDFFitPage -sOutputFile=%s %s';
      }

      $doc_path = get_attached_file($ID);
      $doc_url = wp_get_attachment_url($ID);
      $dirname = dirname($doc_path);
      $basename = basename($doc_path);

      // check whether filetype supported by Ghostscript
      if (!in_array(strtolower(self::getExt($basename)), $exts))
         return false;

      self::deleteExistingThumb($ID);

      $thumb_name = self::getUniqueThumbName(
          $dirname,substr($basename, 0, strrpos($basename, '.')));
      $thumb_path = $dirname . DIRECTORY_SEPARATOR . $thumb_name;

      exec(sprintf($gs, $pg, $pg, $thumb_path, $doc_path), $out, $ret);

      if ($ret != 0) {
         error_log('DG: Ghostscript failed: ' . $out);
         @unlink($thumb_path);
         return false;
      }

      self::fixNewThumb($thumb_path);

      // store reference to new thumbnail
      wp_update_attachment_metadata($ID, array('thumb' => $thumb_name));

      // return URL pointing to new thumbnail
      return str_replace($basename, $thumb_name, $doc_url);
   }

   /**
    * Get thumbnail for document with given ID from Google Drive Viewer.
    *
    * @param string $ID   The attachment ID to retrieve thumbnail from.
    * @param int $pg      The page number to make thumbnail of -- index starts at 1.
    * @return bool|string False on failure, URL to thumb on success.
    */
   public static function getGoogleDriveThumbnail($ID, $pg = 1) {
      // User agent for Lynx 2.8.7rel.2 -- Why? Because I can.
      static $user_agent = "Lynx/2.8.7rel.2 libwww-FM/2.14 SSL-MM/1.4.1 OpenSSL/1.0.0a";
      static $timeout = 90;
      static $exts = array(
         'tiff', 'bmp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
         'pdf', 'pages', 'ai', 'psd', 'dxf', 'svg', 'eps', 'ps', 'ttf'
      );

      $google_viewer = "https://docs.google.com/viewer?url=%s&a=bi&pagenumber=%d&w=%d";
      $doc_path = get_attached_file($ID);
      $doc_url = wp_get_attachment_url($ID);
      $basename = basename($doc_path);
      $dirname = dirname($doc_path);

      // check whether filetype supported by Google Drive Viewer
      if (!in_array(strtolower(self::getExt($basename)), $exts))
         return false;

      self::deleteExistingThumb($ID);

      $thumb_name = self::getUniqueThumbName(
          $dirname,substr($basename, 0, strrpos($basename, '.')));
      $thumb_path = $dirname . DIRECTORY_SEPARATOR . $thumb_name;

      // args for use in HTTP request
      $args = array(
          'timeout' => $timeout, // these requests can take a LONG time
          'redirection' => 5,
          'httpversion' => '1.0',
          'user-agent' => self::$user_agent,
          'blocking' => true,
          'headers' => array(),
          'cookies' => array(),
          'body' => null,
          'compress' => false,
          'decompress' => true,
          'sslverify' => true,
          'stream' => true,
          'filename' => $thumb_path
      );

      // prevent PHP timeout before HTTP completes
      set_time_limit($timeout);

      $google_viewer = sprintf($google_viewer, urlencode($doc_url), (int) $pg, 150);

      // get thumbnail from Google Drive Viewer & check for error on return
      $response = wp_remote_get($google_viewer, $args);

      if (is_wp_error($response) || $response['response']['code'] != 200) {
         error_log('DG: Failed to retrieve thumbnail from Google: ' .
             (is_wp_error($response)
               ? $response->get_error_message()
               : $response['response']['message']));

         @unlink($thumb_path);
         return false;
      }

      self::fixNewThumb($thumb_path);

      // store reference to new thumbnail
      wp_update_attachment_metadata($ID, array('thumb' => $thumb_name));

      // return URL pointing to new thumbnail
      return str_replace($basename, $thumb_name, $doc_url);
   }

   /**
    * Get thumbnail for document with given ID from default images.
    *
    * @param string $ID The attachment ID to retrieve thumbnail from.
    * @return string    URL to thumb on success.
    */
   public static function getDefaultThumbnail($ID) {
      $icon_url = DG_URL . 'icons/';

      $url = wp_get_attachment_url($ID);
      $ext = self::getExt(basename($url));

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
    * @param string $dirname  Directory where the document is located.
    * @param string $basename Filename of document minue the extension.
    * @param string $ext      The extension of the image to be created.
    * @return string Name unique within the directory given, derived from the basename given.
    */
   private static function getUniqueThumbName($dirname, $basename, $ext = '.png') {
      return wp_unique_filename($dirname, str_replace('.', '-', $basename) . '-thumb' . $ext);
   }

   /**
    * Removes the existing thumbnail for the attachment
    * with $ID, if such a thumbnail exists.
    *
    * Substantially borrowed from wp_delete_attachment() in wp-includes/post.php
    *
    * @filter wp_delete_file Filter the path of the file to delete.
    * @global type $wpdb
    * @param type $ID
    */
   private static function deleteExistingThumb($ID) {
      global $wpdb;

      $meta = wp_get_attachment_metadata($ID);

      if (empty($meta['thumb']))
         return;

      $doc_path = get_attached_file($ID);

      // Don't delete the thumb if another attachment uses it
      $SQL = 'SELECT meta_id '
          . "FROM $wpdb->postmeta "
          . 'WHERE meta_key = \'_wp_attachment_metadata\' '
          . 'AND meta_value LIKE %s '
          . 'AND post_id <> %d';

      if (!$wpdb->get_row($wpdb->prepare($SQL, '%' . $meta['thumb'] . '%', $ID))) {
         $thumb_path = str_replace(basename($doc_path), $meta['thumb'], $doc_path);
         // This filter is documented in wp-admin/custom-header.php
         $thumb_path = apply_filters('wp_delete_file', $thumb_path);
         @unlink($thumb_path);
      }
   }

   /**
    * Cleans up dimensions and permissions for a newly-created thumbnail.
    * @param type $thumb_path
    * @return type
    */
   private static function fixNewThumb($thumb_path) {
      // ensure perms are correct
      $stat = stat(dirname($thumb_path));
      $perms = $stat['mode'] & 0000666;
      @chmod($thumb_path, $perms);

      // scae to no larger than 150x150px
      $img = wp_get_image_editor($thumb_path);

      if (is_wp_error($img)) {
         error_log('DG: Failed to get image editor. Can\'t resize thumbnail.');
         return;
      }

      $img->resize(150, 150, false);
      $img->save($thumb_path);
   }

   /**
    * Checks whether we may call gs through exec().
    * @staticvar bool $available
    * @return bool
    */
   private static function isGhostscriptAvailable() {
      static $available;

      if (!isset($available)) {
         $is_win = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
         $gs = $is_win ? 'gswin32c' : 'gs';

         $exec = exec($is_win ? "where $gs" : "which $gs");
         $available = self::isExecAvailable() && !empty($exec);

         if (WP_DEBUG && $available) {
            error_log("DG: Found the $gs executable.");
         }
         else if (WP_DEBUG) {
            error_log("DG: Didn\'t find the $gs executable.");
         }
      }

      return $available;
   }

   /**
    * Checks whether exec() may be used.
    * Source: http://stackoverflow.com/a/12980534/866618
    * @staticvar bool $available
    * @return bool
    */
   private static function isExecAvailable() {
      static $available;

      if (!isset($available)) {
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

         if (WP_DEBUG) {
            error_log('DG: exec() ' . ($available ? 'is' : 'isn\'t') . ' available.');
         }
      }

      return $available;
   }

   /**
    * Formerly achieved with wp_check_filetype(), but it was only returning
    * valid results if the active user had permission to upload the given filetype.
    *
    * @param type $filename Name of the file to get extension from.
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
    * Returns the name of the image to represent the filetype given.
    * @staticvar array $exts
    * @param type $filename
    * @return boolean
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
}

?>
