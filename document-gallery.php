<?php
/*
Plugin Name: Document Gallery
Description: Display non-images in gallery format on page.
Version: 0.8
Author: Dan Rossiter
Author URI: http://danrossiter.org/
License: GPL2
*/

/* much credit to: tgrayimages.com/automate-file-attachments-on-your-wordpress-posts/ */
add_action( 'wp_print_styles', 'add_header_css'); 

function add_header_css() {
  wp_enqueue_style('file-gallery-st-css', plugins_url('style.css', __FILE__));
}

function get_attachment_icons($atts){
	extract(shortcode_atts(array(
	      'descriptions' 	=> FALSE,
	      'echo'			=> FALSE,
		  'orderby'			=> 'menu_order',
		  'order'			=> 'ASC'
     ), $atts));
	 
	if($descriptions){ 
		$sAttachmentString = "<table id='documentIconsWrapper'> \n"; 
	} else{ 
		$sAttachmentString = "<div id='documentIconsWrapper'> \n"; 
	}
	$args = array(
			'numberposts'		=> -1,
			'orderby'			=> $orderby,
			'order'				=> $order,
			'post_type'			=> 'attachment',
			'post_mime_type'	=> 'application',
			'post_parent'		=> get_the_ID() );
	
	if ( $attachments = get_posts($args) ){
		$count = 0;
		foreach( $attachments as $attachment ){ //setup array for more than one file attachment
		 	$attachment_url			= wp_get_attachment_url($attachment->ID);
		 	$attachment_name_array	= explode("/",$attachment_url);
		 	$attachment_title		= wp_get_attachment_link($attachment->ID);
		 	
			if($descriptions){
				$sAttachmentString .= "<tr><td class='documentIcons'><a href='$attachment_url'>";
			} else{
				$sAttachmentString .= "<div class='documentIcons'><a href='$attachment_url'>";
			}
			
			switch(get_post_mime_type( $attachment->ID )){
				case 'application/pdf': // pdf files
					$sAttachmentString .= "<img src='".plugins_url('images/pdf.png', __FILE__)."'/>";
					break;
				case 'application/msword': // word files
					$sAttachmentString .= "<img src='".plugins_url('images/word.png' , __FILE__)."'/>";
					break;
				case 'application/vnd.ms-powerpoint': // powerpoint files
					$sAttachmentString .= "<img src='".plugins_url('images/PowerPoint.png' , __FILE__)."'/>";
					break;
				case 'application/vnd.ms-excel': // excel files
					$sAttachmentString .= "<img src='".plugins_url( 'images/XLS8.png' , __FILE__)."'/>";
					break;
				case 'application/zip': // zip files
					$sAttachmentString .= "<img src='".plugins_url( 'images/zip.png' , __FILE__)."'/>";
					break;
				default:
					continue;
			}
			
			$sAttachmentString .= "</a><br><a href='$attachment_url'>$attachment_title</a>";
			if($descriptions){
				$sAttachmentString .= "</td><td valign='top'><p>$attachment->post_content</p></td></tr>";
			}else{
				$sAttachmentString .= "</div>";
				if(++$count % 4 == 0)
					$sAttachmentString .= "<hr>";
			}
		}
	}

	//Audio Files
	/*$mp3s = get_children(array(   //do only if there are attachments of these qualifications
	 'post_parent' => get_the_ID(),
	 'post_type' => 'attachment',
	 'numberposts' => -1,
	 'post_mime_type' => 'audio',  //MIME Type condition
	 ) );

	if (!empty($mp3s)) :
	$sAttachmentString .= "<ul class='audiofiles'>";
		foreach($mp3s as $mp3) :
    		$sAttachmentString .= "<li>";
			if(!empty($mp3->post_title)) : //checking to make sure the post title isn't empty
				$sAttachmentString .= "<h4 class='title'>".$mp3->post_title."</h4>";
			endif;

			if(!empty($mp3->post_content)) : //checking to make sure something exists in post_content (description)
				$sAttachmentString .= "<p class='description'>".$mp3->post_content."</p>";
			endif;

			$sAttachmentString .= "<object width='470' height='24' id='single".$mp3->ID."' name='single".$mp3->ID."'>";
				$sAttachmentString .= "<param name='movie' value='player.swf'>";
				$sAttachmentString .= "<param name='allowfullscreen' value='true'>";
				$sAttachmentString .= "<param name='allowscriptaccess' value='always'>";
				$sAttachmentString .= "<param name='wmode' value='transparent'>";
				$sAttachmentString .= "<param name='flashvars' value='file=".$mp3->guid."'>";
					$sAttachmentString .= "<embed ";
					  $sAttachmentString .= "id='single".$mp3->ID."' ";
					  $sAttachmentString .= "name='single".$mp3->ID."' ";
					  $sAttachmentString .= "src='".get_bloginfo('template_directory')."/jw/player.swf' ";
					  $sAttachmentString .= "width='470' ";
					  $sAttachmentString .= "height='24' ";
					  $sAttachmentString .= "bgcolor='#ffffff' ";
					  $sAttachmentString .= "allowscriptaccess='always' ";
					  $sAttachmentString .= "allowfullscreen='true' ";
					  $sAttachmentString .= "flashvars='file=".$mp3->guid."' ";

					$sAttachmentString .= "/>";
			$sAttachmentString .= "</object>";
                        $sAttachmentString .= "<a href='".$mp3->guid."'>Download</a>";
			$sAttachmentString .= "</li>";
		endforeach;
	$sAttachmentString .= "</ul>";
	endif;*/

	if($descriptions){
		$sAttachmentString .= "</table>";
	}else{
		$sAttachmentString .= "</div>";
	}
	/*$finalString = implode('', $sAttachmentString);*/
	if($echo){
	    /*echo $finalString;*/
		echo $sAttachmentString;
	}
	return $sAttachmentString; /*$finalString;*/
}
add_shortcode('attachment icons', 'get_attachment_icons');
?>
