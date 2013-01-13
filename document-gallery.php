<?php
/*
Plugin Name: Document Gallery
Description: Display non-images in gallery format on page.
Version: 1.1
Author: Dan Rossiter
Author URI: http://danrossiter.org/
License: GPL2
*/

define( 'DG_URL', plugin_dir_url( __FILE__ ) );

// CREATE GALLERY STRING //
function dg_get_attachment_icons($atts) {
	extract( shortcode_atts( array(
		'descriptions'		=> FALSE,
		'orderby'		=> 'menu_order',
		'order'			=> 'ASC',
		'attachment_pg'		=> FALSE, // default: link directly to file (true to link to attachment pg)
		'ids'			=> FALSE // not yet supported
	), $atts) );


	// Some validation of user values
	$errs = array();

	if($descriptions != FALSE){ $descriptions = TRUE; }

	$order = strtoupper( $order );
	if($order != 'ASC' && $order != 'DEC')
		$errs[] = "The order attribute must be either ASC or DEC. You entered $order.";

	if($attachment_pg != FALSE){ $attachment_pg = TRUE; }
	if($ids != FALSE){ $ids = FALSE; } // not yet supported

	// http://www.youtube.com/watch?v=ClnSMCdw6E8
	if( $errs ) return implode(' ', $errs);
	// All's well. Carry on, my wayward son.
	 

	$args = array(
			'numberposts'		=> -1,
			'orderby'		=> $orderby,
			'order'			=> $order,
			'post_type'		=> 'attachment',
			'post_mime_type'	=> 'application,video,text,audio',
			'post_parent'		=> get_the_ID() );
	
	if ( $attachments = get_posts($args) ) {
		$attachment_str = array( PHP_EOL.'<!-- Generated using Document Gallery. Get yours here: '.
					'http://wordpress.org/extend/plugins/document-gallery -->'.PHP_EOL );

		$count = 0;
		foreach( $attachments as $attachment ) { //setup array for more than one file attachment
		 	$url	= $attachment->guid;
			$filename = basename( $url );

			if( $attachment_pg ) // link to attachment page
				$url = get_attachment_link( $attachment->ID );

		 	$title	= get_the_title( $attachment->ID );
			$icon	= dg_get_attachment_image( $attachment->ID, $title, $filename );
			
			if($descriptions) { // start wrapper
				$attachment_str[] = '<div class="document-icon-wrapper descriptions">'.PHP_EOL.
					'   <div class="document-icon">'.PHP_EOL;
			} else { // no description
				if( $count % 4 == 0 ) { // start wrapper
					$attachment_str[] = '<div id="document-icon-wrapper">'.PHP_EOL;
				}
				$attachment_str[] = '   <div class="document-icon">'.PHP_EOL;
			}

			$attachment_str[] = "   <a href=\"$url\">$icon<br>$title</a>".PHP_EOL;

			if($descriptions) { // end icon & add description
				$attachment_str[] = '   </div>'.PHP_EOL.
					"   <p>$attachment->post_content</p>".
					PHP_EOL.'</div>'.PHP_EOL;
			} else { // end icon
				$attachment_str[] = '   </div>'.PHP_EOL;
				if( ++$count % 4 == 0 ) { // end wrapper
					$attachment_str[] = '</div>'.PHP_EOL;
				}
			}
		} // end looping attachments

		// for galleries w/ number of docs != mult of 4
		if( $count % 4 != 0 && !$descriptions ){ // end wrapper
			$attachment_str[] = '</div>'.PHP_EOL;
		}

		// join array & return
		return implode( '', $attachment_str );
	} // end if attachments
	
	return PHP_EOL.'<!-- Document Gallery: No attachments to display. -->'.PHP_EOL;
}
add_shortcode('dg', 'dg_get_attachment_icons');
// Depreciated as of v1.0. left for backward compatibility
add_shortcode('document gallery', 'dg_get_attachment_icons'); 


// ADD SOME STYLING //
function dg_add_header_css() {
	wp_enqueue_style( 'document-gallery-css', plugins_url('style.css', __FILE__) );
}
add_action( 'wp_print_styles', 'dg_add_header_css'); 


// HELPERS //

// pass in $title & $url to avoid mult function calls
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
			// get_attachment_icon is DEPRECIATED! (replaced in v1.1)
			return wp_get_attachment_image( $id, null, true );
	}

	return '<img src="'.DG_URL.'icons/'.$icon."\" title=\"$title\" alt=\"$title\"/>";
}
// Filtering attachment_icon was considered, then dismissed in v1.0.3 because it would mean almost 
// doubling the amount of processing for each icon. The native WP function would create the icon,
// then 99% of the time this function would replace it. Better to just call the native WP function 
// at the end when needed. Filter would look like this:
// add_filter( 'attachment_icon', 'dg_get_attachment_icon', 10, 2 );v
?>
