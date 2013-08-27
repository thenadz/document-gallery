<?php

// access WP functions externally
require_once('bootstrap.php');

$type = $url = null;

// no access if parent plugin is disabled
if ( ! function_exists('dg_get_attachment_icons') ) {
	wp_die(DG_PERM_ERROR);
}

if(isset($_GET['type'])) {
   if($_GET['type'] === 'not_supported') {
      wp_die('<p>'.__('This attachment type is not yet supported. Sorry for the inconvenience!').'</p>');
   }
   $type = $_GET['type'];
}
else {
	wp_die(DG_PERM_ERROR);
}

if(isset($_GET['id'])) {
   $url = wp_get_attachment_url($_GET['id']);
   
   if($url === false) {
      wp_die('<p>'.__('The ID provided is invalid.').'</p>');
   }
}
else {
	wp_die(DG_PERM_ERROR);
}

switch($type) {
   case 'google' :
      break;
   case 'video' :
      break;
   case 'audio' :
      break;
   default :
      wp_die(DG_PERM_ERROR);
}