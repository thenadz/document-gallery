<?php
defined( 'WPINC' ) OR exit;

/**
 * Register settings for the tab.
 */
function dg_register_settings() {
    add_settings_section(
        'logging_table', '',
        'dg_render_logging_section', DG_OPTION_NAME );
}

/**
 * Validate settings for the tab.
 */
function dg_validate_settings( $values ) {
    global $dg_options;
    if (isset($values['clearLog'])) {
        DG_Logger::clearLog();
    }

    return $dg_options;
}

/**
 * Render the Logging table.
 */
function dg_render_logging_section() {
    $log_list = DG_Logger::readLog();
    if ( $log_list ) {
        $levels = array_map( 'dg_get_log_label_span', array_keys( DG_LogLevel::getLogLevels() ) );

        $fmt =
            '<tr>' .
            '<th scope="col" class="manage-column column-date sorted desc"><a href="javascript:void(0);">' .
            '<span>%s</span><span class="sorting-indicator"></span></a>' .
            '</th>' .
            '<th scope="col" class="manage-column column-level"><span>%s</span></th>' .
            '<th scope="col" class="manage-column column-message"><span>%s</span></th>' .
            '</tr>';

        $thead = sprintf( $fmt,
            __( 'Date', 'document-gallery' ),
            __( 'Level', 'document-gallery' ),
            __( 'Message', 'document-gallery' ) );

        ?>
        <div class="log-list-wrapper">
            <div>
                <div class="tablenav top">
                    <div class="alignleft bulkactions">
                        <button class="action expandAll">
                            <?php echo __( 'Expand All', 'document-gallery' ); ?>
                        </button>
                        <button class="action collapseAll">
                            <?php echo __( 'Collapse All', 'document-gallery' ); ?>
                        </button>
                    </div>
                    <div class="levelSelector">
                        <input type="checkbox" id="allLevels" name="lswitch" value="all" checked/>
                        <label for="allLevels" class="allLevels">ALL</label>
                        <?php
                        foreach ( array_keys( DG_LogLevel::getLogLevels() ) as $k ) { ?>
                            <?php
                            $lower = strtolower( $k );
                            $upper = strtoupper( $k );
                            ?>
                            <input type="checkbox" id="<?php echo $lower; ?>Level" name="lswitch"
                                   value="<?php echo $lower; ?>" checked/>
                            <label for="<?php echo $lower; ?>Level"
                                   class="<?php echo $lower; ?>Level"><?php echo $upper; ?></label>
                        <?php }
                        ?>
                    </div>
                </div>
                <table id="LogTable" class="wp-list-table widefat fixed media" cellpadding="0" cellspacing="0">
                    <thead>
                    <?php echo $thead; ?>
                    </thead>
                    <tfoot>
                    <?php echo $thead; ?>
                    </tfoot>
                    <tbody><?php
                    for ( $i = count( $log_list ); $i > 0; $i-- ) {
                        $log_entry = $log_list[ $i - 1 ];
                        $date      = DocumentGallery::localDateTimeFromTimestamp( $log_entry[0] );

                        // convert attachment names to links
                        $log_entry[2] = preg_replace( '/[ ^](attachment #)(\d+)[.,: ]/i', ' <a href="' . home_url() . '/?attachment_id=\2" target="_blank">\1<strong>\2</strong></a> ', $log_entry[2] );

                        // bold the place where log entry was submitted
                        $log_entry[2] = preg_replace( '/^(\((?:\w+(?:::|->))?\w+\)) /', '<strong>\1</strong> ', $log_entry[2] );

                        // italicize any function references within log entry
                        $log_entry[2] = preg_replace( '/(\(?\w+(?:::|->)\w+\)?)/m', '<i>\1</i>', $log_entry[2] );

                        echo '<tr><td class="date column-date" data-sort-value="' . $log_entry[0] . '"><span class="logLabel date">' . $date . '</span></td>' .
                            '<td class="column-level">' . $levels[ $log_entry[1] ] . '</td>' .
                            '<td class="column-entry">' . ( empty( $log_entry[3] ) ? '<pre>' . $log_entry[2] . '</pre>' : '<div class="expander" title="Click to Expand"><pre>' . $log_entry[2] . '</pre><div><span class="dashicons dashicons-arrow-down-alt2"></span></div></div><div class="spoiler-body"><pre>' . $log_entry[3] . '</pre></div>' ) . '</td>' .
                            '</tr>' . PHP_EOL;
                    } ?>
                    </tbody>
                </table>
                <div class="tablenav bottom">
                    <div class="alignright bulkactions">
                        <button class="button action clearLog" name='<?php echo DG_OPTION_NAME; ?>[clearLog]'
                                value='true'>
                            <?php echo __( 'Clear Log', 'document-gallery' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php } else {
        echo '<div class="noLog">' . __( 'There are no log entries at this time.', 'document-gallery' ) . '<br />' . __( 'For Your information:', 'document-gallery' ) . ' <strong><i>' . __( 'Logging', 'document-gallery' ) . '</i></strong> ' . ( DG_Logger::logEnabled() ? '<span class="loggingON">' . __( 'is turned ON', 'document-gallery' ) . '!</span>' : '<span class="loggingOFF">' . __( 'is turned OFF', 'document-gallery' ) . '!</span>' ) . '</div>';
    }
}

/**
 * Takes label name and returns SPAN tag.
 *
 * @param string $e label name.
 *
 * @return string SPAN tag
 */
function dg_get_log_label_span( $e ) {
    return '<span class="logLabel ' . strtolower( $e ) . '">' . strtoupper( $e ) . '</span>';
}