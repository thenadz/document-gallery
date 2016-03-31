<?php
defined( 'WPINC' ) OR exit;

/**
 * Register settings for the tab.
 */
function dg_register_settings() {
    global $dg_options;

    include_once DG_PATH . 'inc/class-gallery.php';
    include_once DG_PATH . 'inc/class-gallery-sanitization.php';
    include_once DG_PATH . 'inc/class-thumber.php';

    $defaults = $dg_options['gallery'];
    $active   = $dg_options['thumber']['active'];

    add_settings_section(
        'gallery_defaults', __( 'Default Settings', 'document-gallery' ),
        'dg_render_default_settings_section', DG_OPTION_NAME );

    add_settings_section(
        'thumbnail_generation', __( 'Thumbnail Generation', 'document-gallery' ),
        'dg_render_thumber_section', DG_OPTION_NAME );

    add_settings_section(
        'css', __( 'Custom CSS', 'document-gallery' ),
        'dg_render_css_section', DG_OPTION_NAME );

    add_settings_field(
        'gallery_defaults_attachment_pg', 'attachment_pg',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_attachment_pg',
            'name'        => 'gallery_defaults][attachment_pg',
            'value'       => esc_attr( $defaults['attachment_pg'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Link to attachment page rather than to file.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_columns', 'columns',
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_columns',
            'name'        => 'gallery_defaults][columns',
            'value'       => esc_attr( $defaults['columns'] ),
            'type'        => 'number" min="1" step="1',
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'The number of columns to display when not rendering descriptions.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_descriptions', 'descriptions',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_descriptions',
            'name'        => 'gallery_defaults][descriptions',
            'value'       => esc_attr( $defaults['descriptions'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Include document descriptions.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_fancy', 'fancy',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_fancy',
            'name'        => 'gallery_defaults][fancy',
            'value'       => esc_attr( $defaults['fancy'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Use auto-generated document thumbnails.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_order', 'order',
        array( 'DG_Admin', 'renderSelectField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_order',
            'name'        => 'gallery_defaults][order',
            'value'       => esc_attr( $defaults['order'] ),
            'options'     => DG_GallerySanitization::getOrderOptions(),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Ascending or descending sorting of documents.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_orderby', 'orderby',
        array( 'DG_Admin', 'renderSelectField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_orderby',
            'name'        => 'gallery_defaults][orderby',
            'value'       => esc_attr( $defaults['orderby'] ),
            'options'     => DG_GallerySanitization::getOrderbyOptions(),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Which field to order documents by.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_relation', 'relation',
        array( 'DG_Admin', 'renderSelectField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_relation',
            'name'        => 'gallery_defaults][relation',
            'value'       => esc_attr( $defaults['relation'] ),
            'options'     => DG_GallerySanitization::getRelationOptions(),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Whether matched documents must have all taxa_names (AND) or at least one (OR).', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_limit', 'limit',
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_limit',
            'name'        => 'gallery_defaults][limit',
            'value'       => esc_attr( $defaults['limit'] ),
            'type'        => 'number" min="-1" step="1',
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Limit the number of documents included. -1 means no limit.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_mime_types', 'mime_types',
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_mime_types',
            'name'        => 'gallery_defaults][mime_types',
            'value'       => esc_attr( $defaults['mime_types'] ),
            'type'        => 'text',
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Comma-delimited list of <a href="http://en.wikipedia.org/wiki/Internet_media_type#List_of_common_media_types">MIME types</a>.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_new_window', 'new_window',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_new_window',
            'name'        => 'gallery_defaults][new_window',
            'value'       => esc_attr( $defaults['new_window'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Open thumbnail links in new window.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_paginate', 'paginate',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_paginate',
            'name'        => 'gallery_defaults][paginate',
            'value'       => esc_attr( $defaults['paginate'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'When a limit exists, paginate rather than truncating gallery.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_post_status', 'post_status',
        array( 'DG_Admin', 'renderSelectField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_post_status',
            'name'        => 'gallery_defaults][post_status',
            'value'       => esc_attr( $defaults['post_status'] ),
            'options'     => DG_GallerySanitization::getPostStatuses(),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Which post status to look for when querying documents.', 'document-gallery' )
        ) );

    add_settings_field(
        'gallery_defaults_post_type', 'post_type',
        array( 'DG_Admin', 'renderSelectField' ),
        DG_OPTION_NAME, 'gallery_defaults',
        array(
            'label_for'   => 'label_gallery_defaults_post_type',
            'name'        => 'gallery_defaults][post_type',
            'value'       => esc_attr( $defaults['post_type'] ),
            'options'     => DG_GallerySanitization::getPostTypes(),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Which post type to look for when querying documents.', 'document-gallery' )
        ) );

    add_settings_field(
        'thumbnail_generation_thumber-co', __( 'Thumber.co', 'document-gallery' ),
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'thumbnail_generation',
        array(
            'label_for'   => 'label_thumbnail_generation_thumber-co',
            'name'        => 'thumbnail_generation][thumber-co',
            'value'       => esc_attr( $active['thumber-co'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Use your <a href="http://thumber.co" target="_blank">Thumber.co</a> subscription to remotely generate document thumbnails.' , 'document-gallery' ),
            'disabled'    => ! DG_ThumberCoThumber::isThumberCoAvailable()
        ) );

    add_settings_field(
        'thumbnail_generation_av', __( 'Audio/Video', 'document-gallery' ),
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'thumbnail_generation',
        array(
            'label_for'   => 'label_thumbnail_generation_av',
            'name'        => 'thumbnail_generation][av',
            'value'       => esc_attr( $active['av'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => esc_html__( 'Locally generate thumbnails for audio & video files.', 'document-gallery' )
        ) );

    add_settings_field(
        'thumbnail_generation_gs', 'Ghostscript',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'thumbnail_generation',
        array(
            'label_for'   => 'label_thumbnail_generation_gs',
            'name'        => 'thumbnail_generation][gs',
            'value'       => esc_attr( $active['gs'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => DG_GhostscriptThumber::isGhostscriptAvailable()
                ? __( 'Use <a href="http://www.ghostscript.com/" target="_blank">Ghostscript</a> for faster local PDF processing (compared to Imagick).', 'document-gallery' )
                : __( 'Your server is not configured to run <a href="http://www.ghostscript.com/" target="_blank">Ghostscript</a>.', 'document-gallery' ),
            'disabled'    => ! DG_GhostscriptThumber::isGhostscriptAvailable()
        ) );

    add_settings_field(
        'thumbnail_generation_imagick', 'Imagick',
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'thumbnail_generation',
        array(
            'label_for'   => 'label_thumbnail_generation_imagick',
            'name'        => 'thumbnail_generation][imagick',
            'value'       => esc_attr( $active['imagick'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => DG_ImagickThumber::isImagickAvailable()
                ? __( 'Use <a href="http://www.php.net/manual/en/book.imagick.php" target="_blank">Imagick</a> to handle lots of filetypes locally.', 'document-gallery' )
                : __( 'Your server is not configured to run <a href="http://www.php.net/manual/en/book.imagick.php" target="_blank">Imagick</a>.', 'document-gallery' ),
            'disabled'    => ! DG_ImagickThumber::isImagickAvailable()
        ) );

    add_settings_field(
        'thumbnail_generation_width', __( 'Max Thumbnail Dimensions', 'document-gallery' ),
        array( 'DG_Admin', 'renderMultiTextField' ),
        DG_OPTION_NAME, 'thumbnail_generation',
        array(
            array(
                'label_for'   => 'label_advanced_width',
                'name'        => 'thumbnail_generation][width',
                'value'       => esc_attr( $dg_options['thumber']['width'] ),
                'type'        => 'number" min="1" step="1',
                'option_name' => DG_OPTION_NAME,
                'description' => ' x '
            ),
            array(
                'label_for'   => 'label_advanced_height',
                'name'        => 'thumbnail_generation][height',
                'value'       => esc_attr( $dg_options['thumber']['height'] ),
                'type'        => 'number" min="1" step="1',
                'option_name' => DG_OPTION_NAME,
                'description' => __( 'The max width and height (in pixels) that thumbnails will be generated.', 'document-gallery' )
            )
        ) );
}

/**
 * Validate settings for the tab.
 */
function dg_validate_settings( $values ) {
    global $dg_options;
    $ret = $dg_options;

    include_once DG_PATH . 'inc/class-gallery.php';

    $thumbs_cleared = false;

    // handle gallery shortcode defaults
    $errs           = array();
    $ret['gallery'] = DG_Gallery::sanitizeDefaults( null, $values['gallery_defaults'], $errs );

    foreach ( $errs as $k => $v ) {
        add_settings_error( DG_OPTION_NAME, str_replace( '_', '-', $k ), $v );
    }

    // handle setting width
    if ( isset( $values['thumbnail_generation']['width'] ) ) {
        $width = (int) $values['thumbnail_generation']['width'];
        if ( $width > 0 ) {
            $ret['thumber']['width'] = $width;
        } else {
            add_settings_error( DG_OPTION_NAME, 'thumber-width',
                __( 'Invalid width given: ', 'document-gallery' ) . $values['thumbnail_generation']['width'] );
        }

        unset( $values['thumbnail_generation']['width'] );
    }

    // handle setting height
    if ( isset( $values['thumbnail_generation']['height'] ) ) {
        $height = (int) $values['thumbnail_generation']['height'];
        if ( $height > 0 ) {
            $ret['thumber']['height'] = $height;
        } else {
            add_settings_error( DG_OPTION_NAME, 'thumber-height',
                __( 'Invalid height given: ', 'document-gallery' ) . $values['thumbnail_generation']['height'] );
        }

        unset( $values['thumbnail_generation']['width'] );
    }

    // delete thumb cache to force regeneration if max dimensions changed
    if ( $ret['thumber']['width'] !== $dg_options['thumber']['width'] ||
        $ret['thumber']['height'] !== $dg_options['thumber']['height'] ) {
        DG_Thumb::purgeThumbs();
    }

    // handle setting the active thumbers
    foreach ( array_keys( $ret['thumber']['active'] ) as $k ) {
        $ret['thumber']['active'][ $k ] = isset( $values['thumbnail_generation'][ $k ] );
    }

    // if new thumbers available, clear failed thumbnails for retry
    if ( ! $thumbs_cleared ) {
        DG_Thumb::purgeFailedThumbs();
    }

    // handle modified CSS
    if ( trim( $ret['css']['text'] ) !== trim( $values['css'] ) ) {
        $ret['css']['text'] = trim( $values['css'] );
    }

    return $ret;
}

/**
 * Render the Default Settings section.
 */
function dg_render_default_settings_section() { ?>
    <p><?php _e( 'The following values will be used by default in the shortcode. You can still manually set each of these values in each individual shortcode.', 'document-gallery' ); ?></p>
<?php }


/**
 * Render the Thumber section.
 */
function dg_render_thumber_section() { ?>
    <p><?php _e( 'Select which tools to use when generating thumbnails.', 'document-gallery' ); ?></p>
<?php }


/**
 * Renders a text field for use when modifying the CSS to be printed in addition to the default CSS.
 */
function dg_render_css_section() {
    global $dg_options; ?>
    <p><?php printf(
            __( 'Enter custom CSS styling for use with document galleries. To see which ids and classes you can style, take a look at <a href="%s" target="_blank">style.css</a>.', 'document-gallery' ),
            DG_URL . 'assets/css/style.css' ); ?></p>
    <table class="form-table">
        <tbody>
        <tr valign="top">
            <td>
					<textarea name="<?php echo DG_OPTION_NAME; ?>[css]" rows="10" cols="50"
                              class="large-text code"><?php echo $dg_options['css']['text']; ?></textarea>
            </td>
        </tr>
        </tbody>
    </table>
<?php }