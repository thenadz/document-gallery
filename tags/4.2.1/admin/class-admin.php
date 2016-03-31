<?php
defined( 'WPINC' ) OR exit;

class DG_Admin {
	/**
	 * @var string The hook for the Document Gallery settings page.
	 */
	private static $hook;

	/**
	 * @var string The current tab being rendered.
	 */
	private static $current;

	/**
	 * NOTE: This should only ever be accessed through getTabs().
	 *
	 * @var string[] Associative array containing all tab names, keyed by tab slug.
	 */
	private static $tabs;

	/**
	 * Returns reference to tabs array, initializing if needed.
	 *
	 * NOTE: This cannot be done in a static constructor due to timing with i18n.
	 */
	public static function &getTabs() {
		if ( ! isset( self::$tabs ) ) {
			self::$tabs = array(
				'general-tab'              => __( 'General',              'document-gallery' ),
				'thumber-co-tab'           => __( 'Thumber.co',           'document-gallery' ),
				'thumbnail-management-tab' => __( 'Thumbnail Management', 'document-gallery' ),
				'logging-tab'              => __( 'Logging',              'document-gallery' ),
				'advanced-tab'             => __( 'Advanced',             'document-gallery' )
			);
		}

		return self::$tabs;
	}

	/**
	 * Renders Document Gallery options page.
	 */
	public static function renderOptions() { ?>
		<div class="wrap">
			<h2><?php echo __( 'Document Gallery Settings', 'document-gallery' ); ?></h2>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( self::getTabs() as $tab => $name ) {
					$class = ( $tab === self::$current ) ? ' nav-tab-active' : '';
					echo '<a id="' . $tab . '-header" class="nav-tab' . $class . '" href="?page=' . DG_OPTION_NAME . '&tab=' . $tab . '">' . $name . '</a>';
				} ?>
			</h2>

			<form method="post" action="options.php?tab=<?php echo self::$current; ?>" id="<?php echo self::$current ?>">
				<?php
				settings_fields( DG_OPTION_NAME );
				do_settings_sections( DG_OPTION_NAME );
				if ( self::$current !== 'thumbnail-management-tab' && self::$current !== 'logging-tab' ) {
					submit_button();
				}
				?>
			</form>
		</div>
	<?php }

	/**
	 * Adds settings link to main plugin view.
	 * @param $links string[] The links being prepended.
	 * @return string[] The given array with settings link prepended.
	 */
	public static function addSettingsLink( $links ) {
		$settings = '<a href="options-general.php?page=' . DG_OPTION_NAME . '">' .
		            __( 'Settings', 'document-gallery' ) . '</a>';
		array_unshift( $links, $settings );

		return $links;
	}

	/**
	 * Adds donate link to main plugin view.
	 * @param $links string[] The links.
	 * @param $file string The file.
	 * @return string[] The given array with donate link appended.
	 */
	public static function addDonateLink( $links, $file ) {
		if ( $file === DG_BASENAME ) {
			global $dg_options;

			$donate  = '<strong><a href="' . $dg_options['meta']['donate_link'] . '">' .
			           '<span class="dashicons dashicons-heart"></span> ' .
			           __( 'Donate', 'document-gallery' ) . '</a></strong>';
			$links[] = $donate;
		}

		return $links;
	}

	/**
	 * Adds Document Gallery settings page to admin navigation.
	 */
	public static function addAdminPage() {
		DG_Admin::$hook = add_options_page(
			__( 'Document Gallery Settings', 'document-gallery' ),
			__( 'Document Gallery', 'document-gallery' ),
			'manage_options', DG_OPTION_NAME, array( __CLASS__, 'renderOptions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueueScriptsAndStyles' ) );
	}

	/**
	 * Enqueues styles and scripts for the admin settings page.
	 * @param $hook string The hook.
	 */
	public static function enqueueScriptsAndStyles( $hook ) {
		include_once DG_PATH . 'admin/class-feature-pointers.php';
		DG_FeaturePointers::enqueueScripts();

		if ( in_array( $hook, array( DG_Admin::$hook, 'post.php', 'post-new.php' ), true ) ) {
			// Settings Page
			DG_Util::enqueueAsset( 'document-gallery-admin', 'assets/css/admin.css' );

			if ( $hook !== self::$hook && get_post_type( get_the_ID() ) !== 'attachment' ) { //if $hook is 'post.php' or 'post-new.php' and it's not an attachment page
				global $dg_options;

				// Media Manager integration
				add_action( 'admin_print_footer_scripts', array(
						'DG_Admin',
						'loadCustomTemplates'
				) ); //wp_print_scripts || wp_footer

				DG_Util::enqueueAsset( 'dg-media-manager', 'assets/js/media_manager.js', array( 'media-views' ) );
				wp_localize_script( 'dg-media-manager', 'DGl10n', array(
					'dgMenuTitle'   => __( 'Create Document Gallery', 'document-gallery' ),
					'dgButton'      => __( 'Create a new Document Gallery', 'document-gallery' ),
					'canceldgTitle' => '&#8592; ' . __( 'Cancel Document Gallery', 'document-gallery' ),
					'updatedg'      => __( 'Update Document Gallery', 'document-gallery' ),
					'insertdg'      => __( 'Insert Document Gallery', 'document-gallery' ),
					'addTodg'       => __( 'Add to Document Gallery', 'document-gallery' ),
					'addTodgTitle'  => __( 'Add to Document Gallery', 'document-gallery' ),
					'editdgTitle'   => __( 'Edit Document Gallery', 'document-gallery' ),
					'unfitSCalert'  => __( 'This DG shortcode is an advanced one. '.
					                       'Sorry there is no way to use standard edit dialog for it. '.
					                       'You should switch to text mode to edit shortcode itself.', 'document-gallery' ),
				) );
				wp_localize_script( 'dg-media-manager', 'dgDefaults', $dg_options['gallery'] );

				// TinyMCE visual editor
				add_filter( 'mce_external_plugins', array( __CLASS__, 'mce_external_plugins' ) );
				add_filter( 'mce_css', array( __CLASS__, 'dg_plugin_mce_css' ) );
			} else {
				DG_Util::enqueueAsset( 'document-gallery-admin', 'assets/js/admin.js', array( 'jquery' ) );
				wp_localize_script( 'document-gallery-admin', 'dg_admin_vars', array( 'upload_limit' => wp_max_upload_size() ) );
			}
		}
	}

	/**
	 * Adds assets/js/gallery.js as registered TinyMCE plugin
	 *
	 * @param string[] $plugins An array of default TinyMCE plugins.
	 *
	 * @return string[] Default TinyMCE plugins plus custom DG plugin.
	 */
	public static function mce_external_plugins( $plugins ) {
		$plugins['dg'] = DG_Util::getAssetPath( 'assets/js/gallery.js' );

		return $plugins;
	}

	/**
	 * Adds assets/css/style.css as registered TinyMCE CSS
	 *
	 * @param string $stylesheets Comma-delimited list of stylesheets.
	 *
	 * @return string Comma-delimited list of stylesheets.
	 */
	public static function dg_plugin_mce_css( $stylesheets ) {
		if ( ! empty( $stylesheets ) ) {
			$stylesheets .= ',';
		}
		$stylesheets .= str_replace( ',', '%2C', DG_Util::getAssetPath( 'assets/css/style.css' ) );

		return $stylesheets;
	}

	/**
	 * Load Document Gallery Custom templates.
	 */
	public static function loadCustomTemplates() {
		include_once DG_PATH . 'admin/media-manager-template.php';
	}

	/**
	 * Registers settings for the Document Gallery options page.
	 */
	public static function registerSettings() {
		self::initCurrentTab();

		register_setting( DG_OPTION_NAME, DG_OPTION_NAME, array( __CLASS__, 'validateSettings' ) );

		include_once DG_PATH . 'admin/tabs/' . self::$current . '.php';
		dg_register_settings();
	}

	/**
	 * Validates submitted options, sanitizing any invalid options.
	 *
	 * @param mixed[] $values User-submitted new options.
	 * @return mixed[] Sanitized new options.
	 */
	public static function validateSettings( $values ) {
		// NOTE: WP double-calls this function -- below logic prevents potential
		//       side effects by processing a maximum of one call to validate
		//       per page load, re-returning the previous result on any
		//       subsequent calls.
		static $ret = null;
		if ( is_null( $ret ) ) {
			self::initCurrentTab();

			if ( isset( $values['ajax'] ) ) {
				unset( $values['ajax'] );
				define( 'DOING_AJAX', true );
			}

			DG_Logger::writeLog( DG_LogLevel::Detail, 'Validating ' . self::$current . ' tab.' );
			include_once DG_PATH . 'admin/tabs/' . self::$current . '.php';
			$ret = dg_validate_settings( $values );
		}

		return $ret;
	}

	/**
	 * Validates uploaded file as a semi for potential thumbnail.
	 *
	 * @param  string $var File field name.
	 *
	 * @return bool|string   False on failure, path to temp file on success.
	 */
	public static function validateUploadedFile( $var = 'file' ) {
		// checking if any file was delivered
		if ( ! isset( $_FILES[ $var ] ) ) {
			return false;
		}
		// we gonna process only first one
		if ( ! is_array( $_FILES[ $var ]['error'] ) ) {
			$upload_err  = $_FILES[ $var ]['error'];
			$upload_path = $_FILES[ $var ]['tmp_name'];
			$upload_size = $_FILES[ $var ]['size'];
			$upload_type = $_FILES[ $var ]['type'];
			$upload_name = $_FILES[ $var ]['name'];
		} else {
			$upload_err  = $_FILES[ $var ]['error'][0];
			$upload_path = $_FILES[ $var ]['tmp_name'][0];
			$upload_size = $_FILES[ $var ]['size'][0];
			$upload_type = $_FILES[ $var ]['type'][0];
			$upload_name = $_FILES[ $var ]['name'][0];
		}
		$info = getimagesize( $upload_path );
		if ( $info ) {
			if ( $info['mime'] !== $upload_type ) {// in DG_Thumber::getExt() we'll define and set appropriate extension
				DG_Logger::writeLog(
					DG_LogLevel::Warning,
					__( 'File extension doesn\'t match the MIME type of the image: ', 'document-gallery' ) .
					$upload_name . ' - ' . $info['mime'] );
			}
			if ( $upload_size > wp_max_upload_size() ) {
				DG_Logger::writeLog(
					DG_LogLevel::Warning,
					__( 'Uploaded file size exceeds the allowable limit: ', 'document-gallery' ) .
					$upload_name . ' - ' . $upload_size . 'b' );

				return false;
			}
		} else {
			DG_Logger::writeLog(
				DG_LogLevel::Warning,
				__( 'Uploaded file is not an image: ', 'document-gallery' ) .
				$upload_name );

			return false;
		}
		if ( $upload_err == UPLOAD_ERR_OK && $upload_size > 0 ) {
			$temp_file = $upload_path;
		} else {
			DG_Logger::writeLog(
				DG_LogLevel::Error,
				__( 'Failed to get uploaded file: ', 'document-gallery' ) .
				$upload_err );

			return false;
		}

		return $temp_file;
	}

	/**
	 * @return bool Whether to register settings.
	 */
	public static function doRegisterSettings() {
		if ( ! is_multisite() ) {
			$script = ! empty( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : null;
		} else {
			$script = parse_url( $_SERVER['REQUEST_URI'] );
			$script = basename( $script['path'] );
		}

		return ! empty( $script ) && ( 'options-general.php' === $script || 'options.php' === $script );
	}

	/**
	 * Adds meta box to the attachments' edit pages.
	 */
	public static function addMetaBox() {
		$screens = array( 'attachment' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				DG_OPTION_NAME . '_gen_box',
				__( '<b>Thumbnail</b> for <i><b>Document Gallery</b></i>', 'document-gallery' ),
				array( __CLASS__, 'renderMetaBox' ),
				$screen,
				'normal'
			);
		}
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueueScriptsAndStyles' ) );
	}

	/**
	 * Render a Meta Box.
	 * @param $post WP_Post The post.
	 */
	public static function renderMetaBox( $post ) {
		// Disabling scripts that have nothing to do with Edit Media pages
		wp_dequeue_script( 'dg-media-manager' );
		remove_filter( 'mce_external_plugins', array( __CLASS__, 'mce_external_plugins' ) );
		remove_filter( 'mce_css', array( __CLASS__, 'dg_plugin_mce_css' ) );

		wp_nonce_field( DG_OPTION_NAME . '_meta_box', DG_OPTION_NAME . '_meta_box_nonce' );
		$ID      = $post->ID;
		$options = DG_Thumber::getOptions();
		$thumb   = DG_Thumb::getThumb( $ID, $options['width'] . 'x' . $options['height'] );
		$icon    = ! is_null( $thumb ) && $thumb->isSuccess()
						? $thumb->getUrl()
						: DG_DefaultThumber::getInstance()->getThumbnail( $ID );

		echo '<table id="ThumbsTable" class="wp-list-table widefat fixed media" cellpadding="0" cellspacing="0">' .
		     '<tbody><tr data-entry="' . $ID . '"><td class="column-icon media-icon"><img src="' .
		     $icon . '" />' . '</td><td class="column-thumbupload">' .
		     '<span class="manual-download">' .
		     '<span class="dashicons dashicons-upload"></span>' .
		     '<span class="html5dndmarker">Drop file here<span> or </span></span>' .
		     '<span class="buttons-area">' .
		     '<input id="upload-button' . $ID . '" type="file" />' .
		     '<input id="trigger-button' . $ID . '" type="button" value="Select File" class="button" />' .
		     '</span>' .
		     '</span>' .
		     '</td></tr></tbody></table>' .
		     ( is_null( $thumb ) ? '<span class="dashicons dashicons-info"></span><span class="">Please note this attachment hasn&#39;t been used in any Document Gallery instance and so there is no autogenerated thumbnail, in the meantime default one is used instead.</span>' : '' ) . PHP_EOL;
	}

	/**
	 * Save a Meta Box.
	 */
	public static function saveMetaBox( $post_id ) {
		// Check if our nonce is set.
		// Verify that the nonce is valid.
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( ! isset( $_POST[ DG_OPTION_NAME . '_meta_box_nonce' ] ) || ! wp_verify_nonce( $_POST[ DG_OPTION_NAME . '_meta_box_nonce' ], DG_OPTION_NAME . '_meta_box' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		$responseArr = array( 'result' => false );
		if ( isset( $_POST[ DG_OPTION_NAME ]['entry'] ) ) {
			$ID = intval( $_POST[ DG_OPTION_NAME ]['entry'] );
		} else {
			$ID = - 1;
		}

		if ( isset( $_POST[DG_OPTION_NAME]['upload'] ) && isset( $_FILES['file'] ) ) {
			$uploaded_filename = self::validateUploadedFile();
			if ( $uploaded_filename && ( $thumb = DG_Thumber::setThumbnail( $ID, $uploaded_filename ) ) ) {
				$responseArr['result'] = true;
				$responseArr['url']    = $thumb->getUrl();
			}
		}

		if ( isset( $_POST[ DG_OPTION_NAME ]['ajax'] ) ) {
			wp_send_json( $responseArr );
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param mixed[] $args
	 */
	public static function renderCheckboxField( $args ) {
		$args['disabled'] = isset( $args['disabled'] ) ? $args['disabled'] : false;
		printf( '<label><input type="checkbox" value="1" name="%1$s[%2$s]" id="%3$s" %4$s %5$s/> %6$s</label>',
			$args['option_name'],
			$args['name'],
			$args['label_for'],
			checked( $args['value'], 1, false ),
			disabled( $args['disabled'], true, false ),
			$args['description'] );
	}

	/**
	 * Render a text field.
	 *
	 * @param mixed[] $args
	 */
	public static function renderTextField( $args ) {
		printf( '<input type="%1$s" value="%2$s" name="%3$s[%4$s]" id="%5$s" /> %6$s',
			isset( $args['type'] ) ? $args['type'] : 'text',
			$args['value'],
			$args['option_name'],
			$args['name'],
			$args['label_for'],
			$args['description'] );
	}

	/**
	 * Accepts a two-dimensional array where each inner array consists of valid arguments for renderTextField.
	 *
	 * @param mixed[] $args
	 */
	public static function renderMultiTextField( $args ) {
		foreach ( $args as $arg ) {
			self::renderTextField( $arg );
		}
	}

	/**
	 * Render a select field.
	 *
	 * @param mixed[] $args
	 */
	public static function renderSelectField( $args ) {
		printf( '<select name="%1$s[%2$s]" id="%3$s">',
			$args['option_name'],
			$args['name'],
			$args['label_for'] );

		foreach ( $args['options'] as $val ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>',
				$val,
				selected( $val, $args['value'], false ),
				$val,
				$args['description'] );
		}

		print '</select> ' . $args['description'];
	}

	/**
	 * Initializes the current tab value.
	 */
	private static function initCurrentTab() {
		if ( empty( $_GET['tab'] ) || ! array_key_exists( $_GET['tab'], self::getTabs() ) ) {
			reset( self::getTabs() );
			self::$current = key( self::getTabs() );
		} else {
			self::$current = $_GET['tab'];
		}
	}

	/**
	 * Blocks instantiation. All functions are static.
	 */
	private function __construct() {

	}
}