<?php
defined( 'WPINC' ) OR exit;

class DG_FeaturePointers {
    /**
     * @var string Each method used to output a feature pointer must end in this suffix.
     */
    private static $feature_pointer_method_suffix = 'FeaturePointer';

    /**
     * @var string[] The cached pointer methods to avoid reflecting multiple times in a given session.
     */
    private static $feature_pointer_methods;

    /**
     * Enqueues the wp-pointer CSS/JS along with any specific feature pointers.
     */
    public static function enqueueScripts() {
        $seen = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

        $do_add_script = false;
        foreach ( self::getFeaturePointerMethods() as $method ) {
            $fp_id = self::getFeaturePointerIdFromMethodName( $method );
            if ( ! in_array( $fp_id, $seen ) ) {
                $do_add_script = true;
                add_action( 'admin_print_footer_scripts', array( __CLASS__, $method ) );
            }
        }

        // enqueue WP core CSS/JS if we've got pointers
        if ( $do_add_script ) {
            wp_enqueue_script( 'wp-pointer' );
            wp_enqueue_style( 'wp-pointer' );
        }
    }

    /**
     * Feature pointer for DG settings.
     */
    public static function dg41_1FeaturePointer() {
        $title = '<h3>' . __( 'Configure Document Gallery', 'document-gallery' ) . '</h3>';
        $body = '<p>' . __( 'Did you know Document Gallery has lots of configurable settings allowing you to fine tune ' .
                            'what your users experience when viewing a gallery? <em>Click the <strong>Settings</strong> ' .
                            'link above to see for yourself!</em>', 'document-gallery' ) . '</p>';
        self::printFeaturePointer( '#the-list #document-gallery .row-actions', array( 'content' => $title . $body, 'position' => 'top' ) );
    }

    /**
     * Feature pointer for Thumber.co tab in DG settings.
     */
    public static function dg41_2FeaturePointer() {
        $title = '<h3>' . __( 'More Thumbnails!', 'document-gallery' ) . '</h3>';
        $body = '<p>' . __( 'If you need to generate thumbnails for Word documents, PowerPoints, and more then you ' .
                            'need to check out Thumber.co. Free 1-week trial for a limited time! <em>Click the ' .
                            '<strong>Thumber.co</strong> tab above to get started.</em>', 'document-gallery' ) . '</p>';
        self::printFeaturePointer( '#thumber-co-tab-header', array( 'content' => $title . $body, 'position' => 'top' ) );
    }

    /**
     * Feature pointer for visual editor when post contains MS Office files.
     */
    public static function dg424_FeaturePointer() {
        $title = '<h3>' . __( 'More Thumbnails!', 'document-gallery' ) . '</h3>';
        $body = '<p>' . sprintf( __( 'It looks like your gallery includes Word, PowerPoint, or some other Microsoft Office ' .
                                     'files. Did you know that Document Gallery can generate thumbanils for these too? ' .
                                     '<a href="%s">Learn more.</a>', 'document-gallery' ),
                                 'options-general.php?page=' . DG_OPTION_NAME . '&tab=thumber-co-tab' ) . '</p>';
        $position = array( 'edge' => 'top', 'align' => 'left', 'defer_loading' => true );
        self::printFeaturePointer( '#insert-media-button', array( 'content' => $title . $body, 'position' => $position ) );
    }

    /**
     * Print the pointer JavaScript data.
     * NOTE: Taken from WP_Internal_Pointers.
     *
     * @param string $selector The HTML elements, on which the pointer should be attached.
     * @param mixed[]  $args Arguments to be passed to the pointer JS (see wp-pointer.js).
     */
    private static function printFeaturePointer( $selector, $args ) {
        if ( empty( $selector ) || empty( $args ) || empty( $args['content'] ) )
            return;

        // optimize version_compare as much as possible based on PHP version
        $trace = version_compare( PHP_VERSION, '5.4', '>=' )
            ? debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )
            : debug_backtrace( false );
        $pointer_id = self::getFeaturePointerIdFromMethodName( $trace[1]['function'] );

        // NOTE: If below JS is modified, *ensure* that minified is also updated
        ?>
        <script>
            <?php if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ): ?>
            (function($){
                var options = <?php echo wp_json_encode( $args ); ?>, setup;

                if ( ! options )
                    return;

                options = $.extend( options, {
                    close: function() {
                        $.post( ajaxurl, {
                            pointer: '<?php echo $pointer_id; ?>',
                            action: 'dismiss-wp-pointer'
                        });
                    }
                });

                setup = function() {
                    $('<?php echo $selector; ?>').first().pointer( options ).pointer('open');
                };

                if ( options.position && options.position.defer_loading ) {
                        var hdlr = function () {
                                setup();
                        $('<?php echo $selector; ?>').off('ready.dg', hdlr);
                        };
                    $('<?php echo $selector; ?>').on('ready.dg', hdlr);
                } else {
                    $(document).ready(setup);
                }

            })( jQuery );
            <?php else: ?>
            (function(a){var b=<?php echo wp_json_encode( $args ); ?>,c;if(b)if(b=a.extend(b,{close:function(){a.post(ajaxurl,{pointer:"<?php echo $pointer_id; ?>",action:"dismiss-wp-pointer"})}}),c=function(){a("<?php echo $selector; ?>").first().pointer(b).pointer("open")},b.position&&b.position.defer_loading){var d=function(){c();a("<?php echo $selector; ?>").off("ready.dg",d)};a("<?php echo $selector; ?>").on("ready.dg",d)}else a(document).ready(c)})(jQuery);
            <?php endif; ?>
        </script>
        <?php
    }

    /**
     * @param string $method The method name.
     * @return string The feature pointer ID.
     */
    private static function getFeaturePointerIdFromMethodName( $method ) {
        return rtrim( substr( $method, 0, strlen( $method ) - strlen( self::$feature_pointer_method_suffix ) ), '_' );
    }

    /**
     * Reflectively retrieves all of the feature pointer methods, which must end with the feature pointer method suffix.
     * @return string[] The names of all public class methods matching the feature pointer pattern.
     */
    private static function getFeaturePointerMethods() {
        if ( ! isset( self::$feature_pointer_methods ) ) {
            $reflect = new ReflectionClass( __CLASS__ );
            $methods = $reflect->getMethods( ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC );
            self::$feature_pointer_methods =
                array_map(
                    array( __CLASS__, 'getNameFromMethod' ),
                    array_filter( $methods, array( __CLASS__, 'isFilterPointerMethod' ) ) );
        }

        return self::$feature_pointer_methods;
    }

    /**
     * @param $method ReflectionMethod The method to extract name from.
     */
    private static function getNameFromMethod( $method ) {
        return $method->name;
    }

    /**
     * @param $method ReflectionMethod The method name.
     * @return bool Whether the method name matches the filter pointer pattern.
     */
    private static function isFilterPointerMethod( $method ) {
        // NOTE: ReflectionClass returns methods that are static OR public -- must reduce to an AND
        return $method->isPublic() && $method->isStatic() && DG_Util::endsWith( $method->name, self::$feature_pointer_method_suffix );
    }
}