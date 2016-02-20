<?php
defined( 'WPINC' ) OR exit;

global $dg_url_params;
$dg_url_params = array( 'page' => DG_OPTION_NAME, 'tab' => 'thumbnail-management-tab' );

/**
 * Register settings for the tab.
 */
function dg_register_settings() {
    add_settings_section(
        'thumbnail_table', '',
        'dg_render_thumbnail_section', DG_OPTION_NAME );
}

/**
 * Validate settings for the tab.
 */
function dg_validate_settings( $values ) {
    global $dg_options;
    $ret         = $dg_options;
    $responseArr = array( 'result' => false );

    if ( isset( $values['entry'] ) ) {
        $ID = intval( $values['entry'] );
    } else {
        $ID = - 1;
    }

    // Thumbnail(s) cleanup;
    // cleanup value is a marker
    if ( isset( $values['cleanup'] ) && isset( $values['ids'] ) ) {
        $deleted = array_values( array_intersect( array_keys( DG_Thumb::getThumbs() ), $values['ids'] ) );
        DG_Thumb::purgeThumbs( $deleted );
        $responseArr['result']  = true;
        $responseArr['deleted'] = $deleted;
    }

    // Attachment title update
    // title value is a marker
    elseif ( isset( $values['title'] ) && $ID != - 1 ) {
        $attachment = array(
            'ID'         => $ID,
            'post_title' => rawurldecode( addslashes( $values['title'] ) )
        );
        if ( wp_update_post( $attachment ) ) {
            $responseArr['result'] = true;
        }
    }

    // Attachment description update
    // description value is a marker
    elseif ( isset( $values['description'] ) && $ID != - 1 ) {
        $attachment = array(
            'ID'           => $ID,
            'post_content' => rawurldecode( addslashes( $values['description'] ) )
        );
        if ( wp_update_post( $attachment ) ) {
            $responseArr['result'] = true;
        }
    }

    // Thumbnail file manual refresh (one at a time)
    // upload value is a marker
    elseif ( isset( $values['upload'] ) && isset( $_FILES['file'] ) && array_key_exists( $ID, DG_Thumb::getThumbs() ) ) {
        $uploaded_filename = DG_Admin::validateUploadedFile();
        if ( $uploaded_filename && ( $thumb = DG_Thumber::setThumbnail( $ID, $uploaded_filename ) ) ) {
            $responseArr['result'] = true;
            $responseArr['url']    = $thumb->getUrl();
        }
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $responseArr );
        add_filter( 'wp_redirect', 'dg_exit', 1, 0 );
    }

    return $ret;
}

/**
 * Render the Thumbnail table.
 */
function dg_render_thumbnail_section() {
    global $dg_url_params;

    include_once DG_PATH . 'inc/class-thumber.php';
    static $limit_options = array( 10, 25, 75 );
    static $order_options = array( 'asc', 'desc' );
    static $orderby_options = array( 'date', 'title' );
    $options = DG_Thumber::getOptions();

    // find subset of thumbs to be included
    $orderby = $dg_url_params['orderby'] = dg_get_orderby_param( $orderby_options );
    $order = $dg_url_params['order'] = dg_get_order_param( $order_options );
    $limit = $dg_url_params['limit'] = dg_get_limit_param();

    /** @var DG_Thumb[] $thumbs */
    $thumbs        = DG_Thumb::getThumbs( $options['width'] . 'x' . $options['height'] );
    uasort( $thumbs, 'dg_cmp_thumb' );
    $thumbs_number = count( $thumbs );
    $lastsheet     = ceil( $thumbs_number / $limit );
    $sheet         = isset( $_REQUEST['sheet'] ) ? absint( $_REQUEST['sheet'] ) : 1;
    if ( $sheet === 0 || $sheet > $lastsheet ) {
        $sheet = 1;
    }

    $offset = ( $sheet - 1 ) * $limit;
    $thumbs = array_slice( $thumbs, $offset, $limit, true );

    // https://core.trac.wordpress.org/ticket/12212
    $posts = array();
    if ( ! empty( $thumbs ) ) {
        $posts = get_posts(
            array(
                'post_type'   => 'any',
                'post_status' => 'any',
                'numberposts' => - 1,
                'post__in'    => array_keys( $thumbs ),
                'orderby'     => 'post__in'
            ) );
    }

    foreach ( $posts as $post ) {
        $path_parts         = pathinfo( $post->guid );

        $thumb				= $thumbs[$post->ID];
        $thumbs[$post->ID]  = array();
        $t					= &$thumbs[$post->ID];
        $t['timestamp']     = $thumb->getTimestamp();
        $t['title']         = dg_get_thumb_title( $post );
        $t['ext']           = isset( $path_parts['extension'] ) ? $path_parts['extension'] : '';
        $t['description']   = $post->post_content;
        $t['icon']          = $thumb->isSuccess() ? $thumb->getUrl() : DG_DefaultThumber::getInstance()->getThumbnail( $post->ID );
    }
    unset( $posts );

    $select_limit        = '';
    foreach ( $limit_options as $l_o ) {
        $select_limit .= '<option value="' . $l_o . '"' . selected( $limit, $l_o, false ) . '>' . $l_o . '</option>' . PHP_EOL;
    }

    $thead = '<tr>' .
        '<th scope="col" class="manage-column column-cb check-column">' .
        '<label class="screen-reader-text" for="cb-select-all-%1$d">' . __( 'Select All', 'document-gallery' ) . '</label>' .
        '<input id="cb-select-all-%1$d" type="checkbox">' .
        '</th>' .
        '<th scope="col" class="manage-column column-icon">' . __( 'Thumbnail', 'document-gallery' ) . '</th>' .
        '<th scope="col" class="manage-column column-title ' . ( ( $orderby != 'title' ) ? 'sortable desc' : 'sorted ' . $order ) . '"><a href="?' . http_build_query( array_merge( $dg_url_params, array(
            'orderby' => 'title',
            'order'   => ( ( $orderby != 'title' ) ? 'asc' : ( ( $order == 'asc' ) ? 'desc' : 'asc' ) )
        ) ) ) . '"><span>' . __( 'File name', 'document-gallery' ) . '</span><span class="sorting-indicator"></span></th>' .
        '<th scope="col" class="manage-column column-description">' . __( 'Description', 'document-gallery' ) . '</th>' .
        '<th scope="col" class="manage-column column-thumbupload"></th>' .
        '<th scope="col" class="manage-column column-date ' . ( ( $orderby != 'date' ) ? 'sortable asc' : 'sorted ' . $order ) . '"><a href="?' . http_build_query( array_merge( $dg_url_params, array(
            'orderby' => 'date',
            'order'   => ( ( $orderby != 'date' ) ? 'desc' : ( ( $order == 'asc' ) ? 'desc' : 'asc' ) )
        ) ) ) . '"><span>' . __( 'Date', 'document-gallery' ) . '</span><span class="sorting-indicator"></span></th>' .
        '</tr>';

    $pagination = '<div class="alignleft bulkactions"><button class="button action deleteSelected">' . __( 'Delete Selected', 'document-gallery' ) . '</button></div><div class="tablenav-pages">' .
        '<span class="displaying-num">' .
        $thumbs_number . ' ' . _n( 'item', 'items', $thumbs_number, 'document-gallery' ) .
        '</span>' . ( $lastsheet > 1 ?
            '<span class="pagination-links">' .
            '<a class="first-page' . ( $sheet == 1 ? ' disabled' : '' ) . '" title="' . __( 'Go to the first page', 'document-gallery' ) . '"' . ( $sheet == 1 ? '' : ' href="?' . http_build_query( $dg_url_params ) . '"' ) . '>«</a>' .
            '<a class="prev-page' . ( $sheet == 1 ? ' disabled' : '' ) . '" title="' . __( 'Go to the previous page', 'document-gallery' ) . '"' . ( $sheet == 1 ? '' : ' href="?' . http_build_query( array_merge( $dg_url_params, array( 'sheet' => $sheet - 1 ) ) ) . '"' ) . '>‹</a>' .
            '<span class="paging-input">' .
            '<input class="current-page" title="' . __( 'Current page', 'document-gallery' ) . '" type="text" name="paged" value="' . $sheet . '" size="' . strlen( $sheet ) . '" maxlength="' . strlen( $sheet ) . '"> ' . __( 'of', 'document-gallery' ) . ' <span class="total-pages">' . $lastsheet . '</span></span>' .
            '<a class="next-page' . ( $sheet == $lastsheet ? ' disabled' : '' ) . '" title="' . __( 'Go to the next page', 'document-gallery' ) . '"' . ( $sheet == $lastsheet ? '' : ' href="?' . http_build_query( array_merge( $dg_url_params, array( 'sheet' => $sheet + 1 ) ) ) . '"' ) . '>›</a>' .
            '<a class="last-page' . ( $sheet == $lastsheet ? ' disabled' : '' ) . '" title="' . __( 'Go to the last page', 'document-gallery' ) . '"' . ( $sheet == $lastsheet ? '' : ' href="?' . http_build_query( array_merge( $dg_url_params, array( 'sheet' => $lastsheet ) ) ) . '"' ) . '>»</a>' .
            '</span>' : ' <b>|</b> ' ) .
        '<span class="displaying-num"><select dir="rtl" class="limit_per_page">' . $select_limit . '</select> ' . __( 'items per page', 'document-gallery' ) . '</span>' .
        '</div>' .
        '<br class="clear" />';
    ?>

    <script type="text/javascript">
        var URL_params = <?php echo wp_json_encode( $dg_url_params ); ?>;
    </script>
    <div class="thumbs-list-wrapper">
        <div>
            <div class="tablenav top"><?php echo $pagination; ?></div>
            <table id="ThumbsTable" class="wp-list-table widefat fixed media"
                   cellpadding="0" cellspacing="0">
                <thead>
                <?php printf( $thead, 1 ); ?>
                </thead>
                <tfoot>
                <?php printf( $thead, 2 ); ?>
                </tfoot>
                <tbody><?php
                foreach ( $thumbs as $tid => $thumb ) {
                    $icon        = $thumb['icon'];
                    $title       = $thumb['title'];
                    $ext         = $thumb['ext'];
                    $description = $thumb['description'];
                    $date        = DocumentGallery::localDateTimeFromTimestamp( $thumb['timestamp'] );
                    ?>
                    <tr data-entry="<?php echo $tid; ?>">
                        <td scope="row" class="check-column">
                            <input
                                type="checkbox"
                                class="cb-ids"
                                name="<?php echo DG_OPTION_NAME; ?>[ids][]"
                                value="<?php echo $tid; ?>">
                        </td>
                        <td class="column-icon media-icon"><img src="<?php echo $icon; ?>" /></td>
                        <td class="title column-title">
                            <strong>
                                <a
                                    href="<?php echo home_url( '/?attachment_id=' . $tid ); ?>"
                                    target="_blank"
                                    title="<?php sprintf( __( "View '%s' attachment page", 'document-gallery' ), $title ); ?>">
                                    <span class="editable-title"><?php echo $title; ?></span>
                                    <sup><?php echo $ext; ?></sup>
                                </a>
                            </strong>
                            <span class="dashicons dashicons-edit"></span>
								<span class="edit-controls">
									<span class="dashicons dashicons-yes"></span>
									<span class="dashicons dashicons-no"></span>
								</span>
                        </td>
                        <td class="column-description">
                            <div class="editable-description"><?php echo $description; ?></div>
                            <span class="dashicons dashicons-edit"></span>
								<span class="edit-controls">
									<span class="dashicons dashicons-yes"></span>
									<span class="dashicons dashicons-no"></span>
									<span class="dashicons dashicons-update"></span>
								</span>
                        </td>
                        <td class="column-thumbupload">
								<span class="manual-download">
									<span class="dashicons dashicons-upload"></span>
									<span class="html5dndmarker">Drop file here<span> or </span></span>
									<span class="buttons-area">
										<input id="upload-button<?php echo $tid; ?>" type="file" />
										<input id="trigger-button<?php echo $tid; ?>" type="button" value="Select File" class="button" />
									</span>
								</span>
                            <div class="progress animate invis">
                                <span><span></span></span>
                            </div>
                        </td>
                        <td class="date column-date"><?php echo $date; ?></td>
                    </tr>
                    <?php
                } ?>
                </tbody>
            </table>
            <div class="tablenav bottom"><?php echo $pagination; ?></div>
        </div>
    </div>
<?php }

/**
 * @param $t1 DG_Thumb Thumbnail #1.
 * @param $t2 DG_Thumb Thumbnail #2
 *
 * @return int The result of comparing the two thumbs using arguments in $URL_params.
 */
function dg_cmp_thumb($t1, $t2) {
    global $dg_url_params;
    $ret = 0;
    switch ( $dg_url_params['orderby'] ) {
        case 'date':
            $ret = $t1->getTimestamp() - $t2->getTimestamp();
            break;

        case 'title':
            $ret = strcmp( dg_get_thumb_title( $t1->getPostId() ), dg_get_thumb_title( $t2->getPostId() ) );
            break;
    }

    return 'asc' === $dg_url_params['order'] ? $ret : -$ret;
}

/**
 * @return int The limit, which may or may not be a member of $limit_options.
 */
function dg_get_limit_param() {
    global $dg_options;
    $limit = isset( $_REQUEST['limit'] ) ? DG_Util::posint( $_REQUEST['limit'] ) : $dg_options['meta']['items_per_page'];
    if ( $limit !== $dg_options['meta']['items_per_page'] ) {
        $dg_options['meta']['items_per_page'] = $limit;
        DocumentGallery::setOptions( $dg_options );
    }

    return $limit;
}

/**
 * @param $order_options string[] The possible options for order.
 * @return string The order value.
 */
function dg_get_order_param($order_options) {
    $ret = isset( $_REQUEST['order'] ) ? strtolower( $_REQUEST['order'] ) : '';
    return in_array($ret, $order_options) ? $ret : $order_options[0];
}

/**
 * @param $orderby_options string[] The possible options for orderby.
 * @return string The orderby value.
 */
function dg_get_orderby_param($orderby_options) {
    $ret = isset( $_REQUEST['orderby'] ) ? strtolower( $_REQUEST['orderby'] ) : '';
    return in_array( $ret, $orderby_options ) ? $ret : $orderby_options[0];
}

/**
 * @param $post int|WP_Post The post to get title of.
 * @return string The title.
 */
function dg_get_thumb_title( $post ) {
    if ( is_numeric( $post ) ) {
        $post = get_post( $post );
    }

    return ! empty( $post->post_title ) ? $post->post_title : pathinfo( $post->guid, PATHINFO_FILENAME );
}

/**
 * Wraps the PHP exit language construct.
 */
function dg_exit() {
    exit;
}