<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

dg_uninstall();

/**
 * Goodbye, cruel world!!!
 */
function dg_uninstall() {
   $atts = get_posts(array(
       'post_type' => 'attachment',
       'posts_per_page' => -1,
       'post_status' => 'any',
       'post_parent' => null
   ));

   // TODO: This could be very slow on WP installs with lots of attachments
   foreach($atts as $att) {
      $meta = wp_get_attachment_metadata($att->ID, true);
      if (isset($meta['document_meta'])) {
         if ($meta['document_meta']['thumber'] !== false) {
            @unlink($meta['document_meta']['thumb_path']);
         }

         unset($meta['document_meta']);
         wp_update_attachment_metadata($att->ID, $meta);
      }
   }
}

?>
