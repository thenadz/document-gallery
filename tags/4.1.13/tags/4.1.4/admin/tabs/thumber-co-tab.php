<?php
defined( 'WPINC' ) OR exit;

/**
 * Register settings for the tab.
 */
function dg_register_settings() {
    global $dg_options;

    add_settings_section(
        'thumber-co', __( 'Thumber.co Subscription', 'document-gallery' ),
        'dg_render_thumber_co_section', DG_OPTION_NAME );

    add_settings_field(
        'thumber-co_uid', __( 'User ID', 'document-gallery' ),
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'thumber-co',
        array(
            'label_for'   => 'label_thumber-co_uid',
            'name'        => 'uid',
            'value'       => esc_attr( $dg_options['thumber-co']['uid'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'User ID assigned by Thumber.co', 'document-gallery' )
        ) );

    add_settings_field(
        'thumber-co_secret', __( 'User Secret', 'document-gallery' ),
        array( 'DG_Admin', 'renderTextField' ),
        DG_OPTION_NAME, 'thumber-co',
        array(
            'label_for'   => 'label_thumber-co_secret',
            'name'        => 'secret',
            'value'       => esc_attr( $dg_options['thumber-co']['secret'] ),
            'option_name' => DG_OPTION_NAME,
            'description' => __( 'User secret assigned by Thumber.co', 'document-gallery' )
        ) );
}

/**
 * Validate settings for the tab.
 */
function dg_validate_settings( $values ) {
    global $dg_options;
    $ret = $dg_options;

    $has_changed = $has_error = false;
    $old_uid          = $dg_options['thumber-co']['uid'];
    $old_secret       = $dg_options['thumber-co']['secret'];
    $old_subscription = $dg_options['thumber-co']['subscription'];

    // handle setting the UID
    if ( isset( $values['uid'] ) && 0 !== strcmp( $values['uid'], $ret['thumber-co']['uid'] ) ) {
        static $guid_regex = '/^[^\W_]{8}-[^\W_]{4}-[^\W_]{4}-[^\W_]{4}-[^\W_]{12}$/';
        if ( '' ===  $values['uid'] || preg_match( $guid_regex, $values['uid'] ) ) {
            $ret['thumber-co']['uid'] = ( '' !== $values['uid'] ) ? $values['uid'] : null;
            $has_changed = true;
        } else {
            add_settings_error( DG_OPTION_NAME, 'thumber-co-uid',
                __( 'Invalid user ID given: ', 'document-gallery' ) . $values['uid'] );
            $has_error = true;
        }
    }

    // handle setting the user secret
    if ( isset( $values['secret'] ) && 0 !== strcmp( $values['secret'], $ret['thumber-co']['secret'] ) ) {
        static $secret_regex = '/^[-A-Z\d]+$/i';
        if ( '' ===  $values['secret'] || preg_match( $secret_regex, $values['secret'] ) ) {
            $ret['thumber-co']['secret'] = ( '' !==  $values['secret'] ) ? $values['secret'] : null;
            $has_changed = true;
        } else {
            add_settings_error( DG_OPTION_NAME, 'thumber-co-secret',
                __( 'Invalid user secret given: ', 'document-gallery' ) . $values['secret'] );
            $has_error = true;
        }
    }

    // test whether we can actually auth w/ given credentials
    if ( $has_changed && ! $has_error ) {
        if ( isset( $ret['thumber-co']['uid'] ) && isset( $ret['thumber-co']['secret'] ) ) {
            include_once DG_PATH . 'inc/thumbers/thumber-co/class-thumber-co-thumber.php';

            // NOTE: We're tricking getSubscription to re-query subscription w/ new credentials
            $dg_options['thumber-co']['subscription'] = null;

            $client = DG_ThumberClient::getInstance();
            $client->setUid( $ret['thumber-co']['uid'] );
            $client->setUserSecret( $ret['thumber-co']['secret'] );
            $ret['thumber-co']['subscription'] = $client->getSubscription( false );
            if ( ! isset( $ret['thumber-co']['subscription'] ) ) {
                $ret['thumber-co']['uid']          = $old_uid;
                $ret['thumber-co']['secret']       = $old_secret;
                $ret['thumber-co']['subscription'] = $old_subscription;

                add_settings_error( DG_OPTION_NAME, 'thumber-co-authenticate',
                    __( 'Failed to authenticate with given user ID and secret.', 'document-gallery' ) );
            } else {
                // auto-enable if we've got newly-working credentials
                $ret['thumber']['active']['thumber-co'] = true;
            }
        } else {
            // auto-disable if we've got newly-broken credentials
            $ret['thumber']['active']['thumber-co'] = false;
        }
    }

    return $ret;
}

/**
 * Render the Thumber Advanced section.
 */
function dg_render_thumber_co_section() { ?>
    <p><?php _e( '<em>Thumber.co</em> is an external service capable of generating ' .
                 'thumbnails for the tricky file types that Document Gallery can\'t handle on its own. Files such as ' .
                 'Word, PowerPoint, and PDFs can all be processed, allowing you to provide a more complete experience ' .
                 'to your users. Further information, including a complete list of supported file types is available '.
                 '<a href="https://www.thumber.co/about" target="_blank">here</a>.', 'document-gallery' ); ?></p>

    <p><?php _e( 'Once you <a href="https://www.thumber.co/subscriptions">register for a Thumber.co subscription</a>, simply enter your credentials below to get started!' ); ?></p>
    <?php if ( ! DG_Util::isPublicSite() ) : ?>
        <p>
            <em><?php _e( 'NOTE: It appears that you are on a private server not accessible from outside your local network. ' .
                    'Thumber.co must be able to access your site in order for thumbnail conversions to work properly.', 'document-gallery' ); ?></em>
        </p>
    <?php endif; ?>
<?php }