<?php
defined( 'WPINC' ) OR exit;

/**
 * Register settings for the tab.
 */
function dg_register_settings() {
    global $dg_options;

    add_settings_section(
        'advanced', __( 'Advanced Thumbnail Generation', 'document-gallery' ),
        'dg_render_advanced_section', DG_OPTION_NAME );

    add_settings_field(
        'advanced_logging_enabled', __( 'Logging Enabled', 'document-gallery' ),
        array( 'DG_Admin', 'renderCheckboxField' ),
        DG_OPTION_NAME, 'advanced',
        array(
            'label_for'   => 'label_advanced_logging_enabled',
            'name'        => 'logging_enabled',
            'value'       => esc_attr( $dg_options['logging']['enabled'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Whether to log debug and error information related to Document Gallery.', 'document-gallery' )
        ) );

    add_settings_field(
        'advanced_logging_purge_interval', __( 'Logging Purge Interval', 'document-gallery' ),
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'advanced',
        array(
            'label_for'   => 'label_advanced_logging_purge_interval',
            'name'        => 'logging_purge_interval',
            'value'       => esc_attr( $dg_options['logging']['purge_interval'] ),
            'type'        => 'number" min="0" step="1',
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'Number of days to keep old log entries (0 disables purging).', 'document-gallery' )
        ) );

    add_settings_field(
        'advanced_gs', __( 'Ghostscript Absolute Path', 'document-gallery' ),
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'advanced',
        array(
            'label_for'   => 'label_advanced_gs',
            'name'        => 'gs',
            'value'       => esc_attr( $dg_options['thumber']['gs'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => $dg_options['thumber']['gs']
                ? __( 'Successfully auto-detected the location of Ghostscript.', 'document-gallery' )
                : __( 'Failed to auto-detect the location of Ghostscript.', 'document-gallery' )
        ) );

    add_settings_section(
        'advanced_options_dump', __( 'Options Array Dump', 'document-gallery' ),
        'dg_render_options_dump_section', DG_OPTION_NAME );
}

/**
 * Validate settings for the tab.
 */
function dg_validate_settings( $values ) {
    global $dg_options;
    $ret = $dg_options;

    // handle setting the Ghostscript path
    if ( isset( $values['gs'] ) && 0 != strcmp( $values['gs'], $ret['thumber']['gs'] ) ) {
        if ( false === strpos( $values['gs'], ';' ) ) {
            $ret['thumber']['gs'] = $values['gs'];
        } else {
            add_settings_error( DG_OPTION_NAME, 'thumber-gs',
                __( 'Invalid Ghostscript path given: ', 'document-gallery' ) . $values['gs'] );
        }
    }

    // logging settings
    $ret['logging']['enabled'] = isset( $values['logging_enabled'] );
    if ( isset( $values['logging_purge_interval'] ) ) {
        $purge_interval = (int) $values['logging_purge_interval'];
        if ( $purge_interval >= 0 ) {
            $ret['logging']['purge_interval'] = $purge_interval;
        } else {
            add_settings_error( DG_OPTION_NAME, 'thumber-logging-purge-interval',
                __( 'Invalid logging purge interval given: ', 'document-gallery' ) . $values['logging_purge_interval'] );
        }
    }

    return $ret;
}

/**
 * Render the Thumber Advanced section.
 */
function dg_render_advanced_section() {
    include_once DG_PATH . 'inc/class-thumber.php'; ?>
    <p><?php _e( 'Unless you <em>really</em> know what you\'re doing, you should not touch these values.', 'document-gallery' ); ?></p>
    <?php if ( ! DG_AbstractThumber::isExecAvailable() ) : ?>
        <p>
            <em><?php _e( 'NOTE: <code>exec()</code> is not accessible. Ghostscript will not function.', 'document-gallery' ); ?></em>
        </p>
    <?php endif; ?>
<?php }

/**
 * Renders a readonly textfield containing a dump of current DG options.
 */
function dg_render_options_dump_section() {
    global $dg_options; ?>
    <p><?php
        _e( 'The following <em>readonly text</em> should be provided when <a href="http://wordpress.org/support/plugin/document-gallery" target="_blank">reporting a bug</a>:', 'documet-gallery' );
        ?></p>
    <table class="form-table">
        <tbody>
        <tr valign="top">
            <td>
					<textarea readonly="true" rows="10" cols="50" id="options-dump"
                              class="large-text code"><?php print_r( $dg_options ); ?></textarea>
            </td>
        </tr>
        </tbody>
    </table>
<?php }