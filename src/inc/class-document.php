<?php
defined( 'WPINC' ) OR exit;

/**
 * Holds data specific to a given document.
 *
 * @author drossiter
 */
class DG_Document {

	/*==========================================================================
	 * PRIVATE FIELDS
	 *=========================================================================*/

	// general document data
	private $description, $gallery, $ID, $link, $title, $title_attribute, $path, $extension, $size, $date, $time, $author;

	/*==========================================================================
	 * GETTERS
	 *=========================================================================*/

	/**
	 * @return int The attachment ID.
	 */
	public function getId() {
		return $this->ID;
	}

	/**
	 * @return string This document's description.
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return DG_Gallery The gallery which this document is a member.
	 */
	public function getGallery() {
		return $this->gallery;
	}

	/**
	 * @return string The link to be followed when clicking this document.
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * @return string The title of this document.
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string The title attribute string.
	 */
	public function getTitleAttribute() {
		return $this->title_attribute;
	}

	/**
	 * @return string The path to this document.
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string The extension of this document's file.
	 */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 * @return string The size of this document human-readable formatted.
	 */
	public function getSize() {
		return $this->size;
	}


	/*==========================================================================
	 * INIT GALLERY
	 *=========================================================================*/

	/**
	 * Constructs instance of Document.
	 *
	 * @param WP_Post $attachment Attachment object used to initalize fields.
	 * @param DG_Gallery $gallery Instance of Gallery class.
	 */
	public function __construct( $attachment, $gallery ) {
		// init general document data
		$this->gallery         = $gallery;
		$this->description     = wptexturize( $attachment->post_content );
		$this->ID              = $attachment->ID;
		$this->link            = $gallery->linkToAttachmentPg()
			? get_attachment_link( $attachment->ID )
			: wp_get_attachment_url( $attachment->ID );
		$this->title           = wptexturize( $attachment->post_title );
		$this->title_attribute = esc_attr( strip_tags( $this->title ) );

		$this->path            = get_attached_file( $attachment->ID );
		$wp_filetype           = wp_check_filetype_and_ext( $this->path, basename( $this->path ) );
		$this->extension       = $wp_filetype['ext'];
		$size                  = @filesize( $this->path );
		$this->size            = ($size !== false) ? size_format( $size ) : '0';
		$this->date            = get_post_time( get_option( 'date_format' ), false, $this->ID );
		$this->time            = get_post_time( get_option( 'time_format' ), false, $this->ID );
		$this->author          = $attachment->post_author;
	}

	/*==========================================================================
	 * OUTPUT HTML STRING
	 *=========================================================================*/

	/**
	 * Returns HTML representing this Document.
	 * @filter dg_icon_template Filters the DG icon HTML. Passes a single
	 *    bool value indicating whether the gallery is using descriptions or not.
	 * @return string The gallery HTML.
	 */
	public function __toString() {
		include_once DG_PATH . 'inc/class-thumber.php';

		$data        = 'data-ext="' . $this->extension . '"';
		$description = '';
		$target      = $this->gallery->openLinkInNewWindow() ? '_blank' : '_self';

		if ( $this->gallery->useFancyThumbs() ) {
			$thumb_obj = DG_Thumb::getThumb( $this->ID );
			if ( ! is_null( $thumb_obj ) ) {
				if ( $thumb_obj->isSuccess() ) {
					// icon has already been generated so include it in generated gallery
					$thumb = $thumb_obj->getUrl();
				}
			} else {
				// include a data-* attribute for client side to asynchronously request icon after gallery load
				$data .= ' data-id="' . $this->ID . '"';
			}
		}

		if ( ! isset( $thumb ) ) {
			$thumb = DG_DefaultThumber::init()->getThumbnail( $this->ID );
		}

		$repl = array( $this->link, $thumb, $this->title_attribute, $this->title, $target, $this->extension, $this->size, $this->path, $data, $this->date, $this->time, $this->author  );
		$find = array( '%link%', '%img%', '%title_attribute%', '%title%', '%target%', '%extension%', '%size%', '%path%', '%data%', '%date%', '%time%', '%author%' );

		// if descriptions then add filterable tag and value to replaced tag
		if ( $this->gallery->useDescriptions() ) {
			$repl[]      = $this->description;
			$find[]      = '%description%';
			$description = '   <p>%description%</p>';
		}

		$doc_icon =
			'   <div class="document-icon">' . PHP_EOL .
			'      <a href="%link%" target="%target%">' . PHP_EOL .
			'         <img src="%img%" title="%title_attribute%" alt="%title_attribute%" %data%/>' . PHP_EOL .
			'         <span class="title">%title%</span>' . PHP_EOL .
			'      </a>' . PHP_EOL .
			'   </div>' . PHP_EOL .
			$description;

		// allow developers to filter icon output
		$doc_icon = apply_filters( 'dg_icon_template', $doc_icon, $this->gallery->useDescriptions(), $this->ID );

		return str_replace( $find, $repl, $doc_icon );
	}
}