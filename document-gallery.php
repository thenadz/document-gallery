<?php
/*
Plugin Name: Document Gallery
Description: Display non-images (and images) in gallery format on a page or post with the [dg] shortcode.
Version: 1.3
Author: Dan Rossiter
Author URI: http://danrossiter.org/
License: GPL2
*/

define( 'DG_URL', plugin_dir_url( __FILE__ ) );
define( 'DG_IMG_STRING', '<img src="%s" title="%s" alt="%s" />' );
define( 'DG_DOC_ICON', 
        '   <div class="document-icon">'.PHP_EOL.
        '      <a href="%s">%s<br>%s</a>'.PHP_EOL.
        '   </div>'.PHP_EOL );

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
	$count = 0;
	$errs = array();


	// ATTRIBUTE VALIDATION
	$descriptions = strtolower($descriptions) == "false" ? FALSE : TRUE;

	$order = strtoupper( $order );
	if($order != 'ASC' && $order != 'DEC')
		$errs[] = "The order attribute must be either ASC or DEC. You entered $order.";

	$attachment_pg = strtolower($attachment_pg) == "false" ? FALSE : TRUE;

	$images = strtolower($images) == "false" ? FALSE : TRUE;

	if( strtolower($ids) == "false" ){ $ids = FALSE; }

	// http://www.youtube.com/watch?v=ClnSMCdw6E8
	if( $errs ) return implode(' ', $errs);
	// END VALIDATION (WE MADE IT!)


	// LET'S GET SOME DOCUMENTS!
	if( $ids && ( $ids = explode( ',', $ids ) ) )
		$attachments = dg_get_attachments_by_ids( $ids );

	// if 'ids' was used, skip this
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
	
	if ( $attachments ) {
		$attachment_str = PHP_EOL.'<!-- Generated using Document Gallery. Get yours here: '.
					'http://wordpress.org/extend/plugins/document-gallery -->'.PHP_EOL;

		// DOCUMENT LOOP
		foreach( $attachments as $attachment ) {
			// INIT ATTACHMENT-SPECIFIC VARS
		 	$url	= $attachment->guid;
			$filename = basename( $url );

			if( $attachment_pg ) // link to attachment page
				$url = get_attachment_link( $attachment->ID );

		 	$title	= get_the_title( $attachment->ID );
			$icon	= dg_get_attachment_image( $attachment->ID, $title, $filename );

			// GENERATE OUTPUT
			if($descriptions) { // start description wrapper
				$attachment_str .= '<div class="document-icon-wrapper descriptions">'.PHP_EOL;

			} elseif( $count % 4 == 0 ) { // no description
				$attachment_str .= '<div id="document-icon-wrapper">'.PHP_EOL;
			}

			// insert filtered document-icon
			$attachment_str .= apply_filters( 'dg_doc_icon', 
				sprintf( DG_DOC_ICON, $url, $icon, $title ), $attachment->ID );

			if($descriptions) { // add description	
				$attachment_str .= "   <p>$attachment->post_content</p>".
					PHP_EOL.'</div>'.PHP_EOL;

			} elseif( ++$count % 4 == 0 ) { // end wrapper
				$attachment_str .= '</div>'.PHP_EOL;
			}
		} // END DOCUMENT LOOP

		// for galleries w/ number of docs != mult of 4
		if( $count % 4 != 0 && !$descriptions ) // end wrapper
			$attachment_str .= '</div>'.PHP_EOL;

		return $attachment_str;
	} // END IF

	// NO DOCUMENTS
	return PHP_EOL.'<!-- Document Gallery: No attachments to display. How boring... -->'.PHP_EOL;
}
add_shortcode('dg', 'dg_get_attachment_icons');

// 'document gallery' shortcode depreciated as of v1.0. left for backward compatibility
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

// pass in $title & $filename to avoid mult function calls
function dg_get_attachment_image( $id, $title, $filename ) {
	$filetype = wp_check_filetype( $filename );

	// identify extension
	switch( $filetype['ext'] ) {
		// Most Common First
		case 'pdf':
			$icon = DG_URL.'icons/pdf.png';
			break;
		// MS Office
		case 'doc':
		case 'docx':
		case 'docm':
		case 'dotx':
		case 'dotm':
			$icon = DG_URL.'icons/msdoc.png';
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
			$icon = DG_URL.'icons/msppt.png';
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
			$icon = DG_URL.'icons/msxls.png';
			break;
		case 'mdb':
			$icon = DG_URL.'icons/msaccess.png';
			break;
		// Video formats
		case 'avi':
			$icon = DG_URL.'icons/avi.png';
			break;
		case 'divx':
			$icon = DG_URL.'icons/divx.png';
			break;
		case 'flv':
			$icon = DG_URL.'icons/flv.png';
			break;
		case 'qt':
		case 'mov':
			$icon = DG_URL.'icons/mov.png';
			break;
		case 'asf':
		case 'asx':
		case 'wax':
		case 'wmv':
		case 'wmx':
			$icon = DG_URL.'icons/wmv.png';
			break;
		case 'mkv':
			$icon = DG_URL.'icons/mkv.png';
			break;
		// Audio formats
		case 'mp3':
			$icon = DG_URL.'icons/mp3.png';
			break;
		case 'wav':
			$icon = DG_URL.'icons/wav.png';
			break;
		case 'ogg':
		case 'oga':
			$icon = DG_URL.'icons/ogg.png';
			break;
		case 'midi':
		case 'mid':
			$icon = DG_URL.'icons/midi.png';
			break;
		case 'wma':
			$icon = DG_URL.'icons/wma.png';
			break;
		// Text formats
		case 'rtx':
			$icon = DG_URL.'icons/rtx.png';
			break;
		case 'ics':
			$icon = DG_URL.'icons/ics.png';
			break;
		case 'csv':
			$icon = DG_URL.'icons/csv.png';
			break;
		// Msc application formats
		case 'html':
		case 'htm': // death to all who use this!
			$icon = DG_URL.'icons/html.png';
			break;
		case 'css':
			$icon = DG_URL.'icons/css.png';
			break;
		case 'js':
			$icon = DG_URL.'icons/javascript.png';
			break;
		case 'class':
			$icon = DG_URL.'icons/java.png';
			break; 
		case 'zip':
			$icon = DG_URL.'icons/zip.png';
			break;
		case 'tar':
		case 'gzip':
		case 'gz':
		case 'bz2': // not yet WP-supported
		case 'tgz': // not yet WP-supported
			$icon = DG_URL.'icons/compressed.png';
			break;
		case 'rar': // RAWR!!!
			$icon = DG_URL.'icons/rar.png';
			break;
		case '7z':
			$icon = DG_URL.'icons/7zip.png';
			break;
		case 'exec':
			$icon = DG_URL.'icons/exec.png';
			break;
		case 'rtf':
			$icon = DG_URL.'icons/rtf.png';
			break;
		case 'swf':
			$icon = DG_URL.'icons/shockwave.png';
			break;
		// OpenOffice formats
		case 'odt':
			$icon = DG_URL.'icons/opendocument-text.png';
			break;
		case 'odp':
			$icon = DG_URL.'icons/opendocument-presentation.png';
			break;
		case 'ods':
			$icon = DG_URL.'icons/opendocument-spreadsheet.png';
			break;
		case 'odg':
			$icon = DG_URL.'icons/opendocument-graphics.png';
			break;
		case 'odb':
			$icon = DG_URL.'icons/opendocument-database.png';
			break;
		case 'odf':
			$icon = DG_URL.'icons/opendocument-formula.png';
			break;
		default:
			// handle images
			if( strpos( $filetype['type'], 'image' ) === 0 &&
				( $icon = wp_get_attachment_image_src( $id, 'thumbnail', false ) ) ){
					$icon = $icon[0];
					break;
			}

			// fallback to default icons if not recognized
			if( $icon = wp_get_attachment_image_src( $id, null, true ) ){
				$icon = $icon[0];
				break;
			}

			// everything failed. This is bad...
			return "<!-- Failed to retrive icon for attachment #$id -->";
	}

	return sprintf( DG_IMG_STRING, $icon, $title, $title );
}

// ADD SOME STYLING
function dg_add_header_css() {
	wp_enqueue_style( 'document-gallery-css', plugins_url('style.css', __FILE__) );
}
add_action( 'wp_print_styles', 'dg_add_header_css'); 

?>
