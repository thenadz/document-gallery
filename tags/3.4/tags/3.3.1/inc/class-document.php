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
	private $description, $gallery, $ID, $link, $title, $title_attribute, $path, $extension, $size;

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
		$this->size            = size_format( filesize( $this->path ) );
	}

	/*==========================================================================
		* OUTPUT HTML STRING
		*=========================================================================*/

	/**
	 * Returns HTML representing this Document.
	 * @filter dg_icon_template Filters the DG icon HTML. Passes a single
	 *    bool value indicating whether the gallery is using descriptions or not.
	 * @return string
	 */
	public function __toString() {
		include_once DG_PATH . 'inc/class-thumber.php';

		$thumb = $this->gallery->useFancyThumbs()
			? DG_Thumber::getThumbnail( $this->ID )
			: DG_Thumber::getDefaultThumbnail( $this->ID );
		$target   = $this->gallery->openLinkInNewWindow() ? '_blank' : '_self';

		$repl        = array( $this->link, $thumb, $this->title_attribute, $this->title, $target, $this->extension, $this->size, $this->path );
		$find        = array( '%link%', '%img%', '%title_attribute%', '%title%', '%target%', '%extension%', '%size%', '%path%' );
		$description = '';

		// if descriptions then add filterable tag and value to replaced tag
		if ( $this->gallery->useDescriptions() ) {
			$repl[]      = $this->description;
			$find[]      = '%description%';
			$description = '   <p>%description%</p>';
		}

		$doc_icon =
			'   <div class="document-icon">' . PHP_EOL .
			'      <a href="%link%" target="%target%"><img src="%img%" title="%title_attribute%" alt="%title_attribute%" /><br>%title%</a>' . PHP_EOL .
			'   </div>' . PHP_EOL .
			$description;

		// allow developers to filter icon output
		$doc_icon = apply_filters(
			'dg_icon_template',
			$doc_icon,
			$this->gallery->useDescriptions(),
			$this->ID );

		return str_replace( $find, $repl, $doc_icon );
	}
}