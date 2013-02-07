<?php
/*
Plugin Name: Document Gallery
Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
Version: 1.2.1
Author: Dan Rossiter
Author URI: http://danrossiter.org/
License: GPL2
*/

define( 'DG_URL', plugin_dir_url( __FILE__ ) );
define( 'DG_IMG_STRING', '<img src="'.DG_URL.'icons/%s" title="%s" alt="%s" />' );
define( 'DG_DOC_ICON', 
'   <div class="document-icon">
      <a href="%s">%s<br>%s</a>
   </div>
');

// CREATE GALLERY STRING //
function dg_get_attachment_icons($atts) {
	extract( shortcode_atts( array(
		'descriptions'		=> FALSE,
		'orderby'		=> 'menu_order',
		'order'			=> 'ASC',
		'attachment_pg'		=> FALSE, // default: link directly to file (true to link to attachment pg)
		'images'		=> FALSE, // if enabled, all images attached to current page will be included also
		'ids'			=> FALSE // comma-separated list of attachment ids
	), $atts) );

	// INIT
	$attachments = array();
	$errs = array();


	// ATTRIBUTE VALIDATION
	if( strtolower($descriptions) == "false" ){ $descriptions = FALSE; }

	$order = strtoupper( $order );
	if($order != 'ASC' && $order != 'DEC')
		$errs[] = "The order attribute must be either ASC or DEC. You entered $order.";

	if( strtolower($attachment_pg) == "false" ){ $attachment_pg = FALSE; }

	if( strtolower($images) == "false" ){ $images = FALSE; }

	if( strtolower($ids) == "false" ){ $ids = FALSE; }

	// http://www.youtube.com/watch?v=ClnSMCdw6E8
	if( $errs ) return implode(' ', $errs);
	// END VALIDATION (WE MADE IT!)


	// LET'S GET SOME DOCUMENTS!
	if( $ids && ( $ids = explode( ',', $ids ) ) ){
		$attachments = dg_get_attachments_by_ids( $ids );
	}

	// if 'ids' was used, skip
	if( !$attachments ){
		$args = array(
			'numberposts'		=> -1,
			'orderby'		=> $orderby,
			'order'			=> $order,
			'post_type'		=> 'attachment',
			'post_mime_type'	=> 'application,video,text,audio',
			'post_parent'		=> get_the_ID() );
		if( $images ) $args['post_mime_type'] .= ',image';

		$attachments = get_posts($args);
	}
	
	if ( $attachments ) { // DOCUMENT LOOP
		$attachment_str = PHP_EOL.'<!-- Generated using Document Gallery. Get yours here: '.
					'http://wordpress.org/extend/plugins/document-gallery -->'.PHP_EOL;

		$count = 0;
		foreach( $attachments as $attachment ) { //setup array for more than one file attachment
		 	$url	= $attachment->guid;
			$filename = basename( $url );

			if( $attachment_pg ) // link to attachment page
				$url = get_attachment_link( $attachment->ID );

		 	$title	= get_the_title( $attachment->ID );
			$icon	= dg_get_attachment_image( $attachment->ID, $title, $filename );
			
			if($descriptions) { // start description wrapper
				$attachment_str .= '<div class="document-icon-wrapper descriptions">'.PHP_EOL;
			} elseif( $count % 4 == 0 ) { // no description
				$attachment_str .= '<div id="document-icon-wrapper">'.PHP_EOL;
			}

			// insert document-icon
			$attachment_str .= apply_filters( 'dg_doc_icon', 
				sprintf( DG_DOC_ICON, $url, $icon, $title ), $filename );

			if($descriptions) { // add description	
				$attachment_str .= "   <p>$attachment->post_content</p>".
					PHP_EOL.'</div>'.PHP_EOL;

			} elseif( ++$count % 4 == 0 ) { // end wrapper
				$attachment_str .= '</div>'.PHP_EOL;
			}
		} // end looping attachments

		// for galleries w/ number of docs != mult of 4
		if( $count % 4 != 0 && !$descriptions ){ // end wrapper
			$attachment_str .= '</div>'.PHP_EOL;
		}

		// return complete gallery
		return $attachment_str;
	} // END DOCUMENT LOOP
	
	return PHP_EOL.'<!-- Document Gallery: No attachments to display. How boring... -->'.PHP_EOL;
}
add_shortcode('dg', 'dg_get_attachment_icons');
// Depreciated as of v1.0. left for backward compatibility
add_shortcode('document gallery', 'dg_get_attachment_icons'); 



// HELPERS //
function dg_get_attachments_by_ids( $ids ){
	$attachments = array();
	foreach( $ids as $id ){
		$attachment = get_post( $id );
		if( $attachment->post_type == 'attachment' )
			$attachments[] = $attachment;
		// else: not an attachment so skip
	}
	return $attachments;
}

// pass in $title & $url to avoid mult function calls
// Filter: dg_attachment_icon
// 		passes the icon value as well as the file ext
function dg_get_attachment_image( $id, $title, $filename ) {
	$filetype = wp_check_filetype( $filename );

	// identify extension
	switch( $filetype['ext'] ) {
		// Most Common First
		case 'pdf':
			$icon = 'pdf.png';
			break;
		// MS Office
		case 'doc':
		case 'docx':
		case 'docm':
		case 'dotx':
		case 'dotm':
			$icon = 'msdoc.png';
			break;
		case 'ppt':
		case 'pot':
		case 'pps':
		case 'pptx':
		case 'pptm':
		case 'ppsx':
		case 'ppsm':
		case 'potx':
		case 'potm':
		case 'ppam':
		case 'sldx':
		case 'sldm':
			$icon = 'msppt.png';
			break;
		case 'xla':
		case 'xls':
		case 'xlt':
		case 'xlw':
		case 'xlsx':
		case 'xlsm':
		case 'xlsb':
		case 'xltx':
		case 'xltm':
		case 'xlam':
			$icon = 'msxls.png';
			break;
		case 'mdb':
			$icon = 'msaccess.png';
			break;
		// Video formats
		case 'avi':
			$icon = 'avi.png';
			break;
		case 'divx':
			$icon = 'divx.png';
			break;
		case 'flv':
			$icon = 'flv.png';
			break;
		case 'qt':
		case 'mov':
			$icon = 'mov.png';
			break;
		case 'asf':
		case 'asx':
		case 'wax':
		case 'wmv':
		case 'wmx':
			$icon = 'wmv.png';
			break;
		case 'mkv':
			$icon = 'mkv.png';
			break;
		// Audio formats
		case 'mp3':
			$icon = 'mp3.png';
			break;
		case 'wav':
			$icon = 'wav.png';
			break;
		case 'ogg':
		case 'oga':
			$icon = 'ogg.png';
			break;
		case 'midi':
		case 'mid':
			$icon = 'midi.png';
			break;
		case 'wma':
			$icon = 'wma.png';
			break;
		// Text formats
		case 'rtx':
			$icon = 'rtx.png';
			break;
		case 'ics':
			$icon = 'ics.png';
			break;
		case 'csv':
			$icon = 'csv.png';
			break;
		// Msc application formats
		case 'html':
		case 'htm': // death to all who use this!
			$icon = 'html.png';
			break;
		case 'css':
			$icon = 'css.png';
			break;
		case 'js':
			$icon = 'javascript.png';
			break;
		case 'class':
			$icon = 'java.png';
			break; 
		case 'zip':
			$icon = 'zip.png';
			break;
		case 'tar':
		case 'gzip':
		case 'gz':
		case 'bz2': // not yet WP-supported
		case 'tgz': // not yet WP-supported
			$icon = 'compressed.png';
			break;
		case 'rar': // RAWR!!!
			$icon = 'rar.png';
			break;
		case '7z':
			$icon = '7zip.png';
			break;
		case 'exec':
			$icon = 'exec.png';
			break;
		case 'rtf':
			$icon = 'rtf.png';
			break;
		case 'swf':
			$icon = 'shockwave.png';
			break;
		// OpenOffice formats
		case 'odt':
			$icon = 'opendocument-text.png';
			break;
		case 'odp':
			$icon = 'opendocument-presentation.png';
			break;
		case 'ods':
			$icon = 'opendocument-spreadsheet.png';
			break;
		case 'odg':
			$icon = 'opendocument-graphics.png';
			break;
		case 'odb':
			$icon = 'opendocument-database.png';
			break;
		case 'odf':
			$icon = 'opendocument-formula.png';
			break;
		// fallback to default icons if not recognized
		default:
			// handle images
			if( preg_match( '/^image/', $filetype['type'] ) &&
				( $icon = wp_get_attachment_image( $id, 'thumbnail', false ) ) )
					return $icon;

			// fallback to wp defaults - get_attachment_icon is DEPRECIATED! (replaced in dg v1.1)
			if( $icon = wp_get_attachment_image( $id, null, true ) )
				return $icon;

			return "<!-- Failed to retrive icon for attachment #$id -->"; // everything failed. This is bad...
	}

	return sprintf( DG_IMG_STRING, $icon, $title, $title );
}

// Filtering attachment_icon was considered, then dismissed in v1.0.3 because it would mean almost 
// doubling the amount of processing for each icon. The native WP function would create the icon,
// then 99% of the time this function would replace it. Better to just call the native WP function 
// at the end when needed. Filter would look like this:
// add_filter( 'attachment_icon', 'dg_get_attachment_icon', 10, 2 );v

// ADD SOME STYLING //
function dg_add_header_css() {
	wp_enqueue_style( 'document-gallery-css', plugins_url('style.css', __FILE__) );
}
add_action( 'wp_print_styles', 'dg_add_header_css'); 

?>
