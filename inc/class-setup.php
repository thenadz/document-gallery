<?php
defined( 'WPINC' ) OR exit;

/**
 * Holds functions that handle DG setup / uninstallation.
 *
 * @author drossiter
 */
class DG_Setup {

	/**
	 * The default DG options to be used on install and when validating structure of options.
	 *
	 * @param $skeleton bool When true, expensive values are not calculated. Only keys may be trusted when returning skeleton.
	 *
	 * @return array Contains default options for DG.
	 */
	public static function getDefaultOptions( $skeleton = false ) {
		include_once DG_PATH . 'inc/class-thumber.php';

		$gs = $donate_link = null;
		if ( ! $skeleton ) {
			$gs          = DG_Thumber::getGhostscriptExecutable();
			$donate_link = self::getDonateLink();
		}

		return array(
			'thumber'    => array(
				// cached thumbnails, keyed by post ID
				'thumbs'  => array(),
				// Ghostscript path
				'gs'      => $gs,
				// which thumbnail generation methods are available
				'active'  => DG_Thumber::getDefaultThumbers( $skeleton ),
				// max width to generate thumbnails
				'width'   => 200,
				// max height to generate thumbnails
				'height'  => 200,
				// time after which to quite trying to generate new thumbanils for gallery
				'timeout' => 30
			),
			'gallery'    => array(
				// default: link directly to file (true to link to attachment pg)
				'attachment_pg' => false,
				// include the attachment description in output
				'descriptions'  => false,
				// include thumbnail of actual document in gallery display
				'fancy'         => true,
				// comma-delimited list of all mime types to be included
				'mime_types'    => implode( ',', self::getDefaultMimeTypes() ),
				// ascending/descending order for included documents
				'order'         => 'ASC',
				// which property to order by
				'orderby'       => 'menu_order',
				// AND or OR
				'relation'      => 'AND',
				// the status the post must be in when returned by DG
				'post_status'   => 'any',
				// the type of post to be returned
				'post_type'     => 'attachment',
				// the max number of thumbnails to return
				'limit'         => - 1,
				// # of columns to be used in gallery
				'columns'       => 4,
				// whether to open documents in new window
				'new_window'    => false
			),
			'css'        => array(
				// plain text of CSS to be edited by user
				'text' => ''
			),
			'meta'       => array(
				// current DG version
				'version'     => DG_VERSION,
				// URL to donate to plugin development
				'donate_link' => $donate_link
			),
			// logging options
			'logging'    => array(
				// TODO: more granular -- log_level instead of blanket enable/disable
				'enabled'        => false,
				// max age of log entry (days)
				'purge_interval' => 7
			),
			// whether to validate DG option structure on save
			'validation' => false
		);
	}

	/**
	 * @return array The default MIME types to include in gallery.
	 */
	public static function getDefaultMimeTypes() {
		return array( 'application', 'video', 'text', 'audio', 'image' );
	}

	/**
	 * Runs every page load, updates as needed.
	 */
	public static function maybeUpdate() {
		global $dg_options;

		// do update
		if ( ! is_null( $dg_options ) && ( isset( $dg_options['version'] ) || DG_VERSION !== $dg_options['meta']['version'] ) ) {
			$blogs = array( null );

			if ( is_multisite() ) {
				$blogs = DG_Util::getBlogIds();
			}

			foreach ( $blogs as $blog ) {
				self::_update( $blog );
			}
		}
	}

	/**
	 * Runs when update is needed, updating the given blog. If $blog is null,
	 * active blog is updated.
	 *
	 * @param int $blog Blog to update or null if updating current blog.
	 */
	private static function _update( $blog ) {
		$options = DocumentGallery::getOptions( $blog );
		if ( is_null( $options ) ) {
			return;
		}

		// version-specific updates
		self::twoPointTwo( $options );
		self::twoPointThree( $options );
		self::threePointZeroBeta( $options );
		self::threePointOne( $options );
		self::threePointTwo( $options );
		self::threePointThree( $options );

		// update plugin meta data
		$options['meta']['version']     = DG_VERSION;
		$options['meta']['donate_link'] = self::getDonateLink();

		// remove previously-failed thumbs
		$thumbs = $options['thumber']['thumbs'];
		foreach ( $thumbs as $k => $v ) {
			if ( empty( $v['thumber'] ) ) {
				unset( $options['thumber']['thumbs'][ $k ] );
			}
		}

		DocumentGallery::setOptions( $options, $blog );
	}

	/**
	 * The 'created_timestamp' key in each thumb record is being moved
	 * to 'timestamp' as part of a move to store timestamp for failed
	 * thumbnails in addition to successful ones.
	 *
	 * The defaults sub-branch in the gallery branch is being flattened into its parent.
	 *
	 * @param array $options The options to be modified.
	 */
	private static function twoPointTwo( &$options ) {
		if ( isset( $options['version'] ) && version_compare( $options['version'], '2.2', '<' ) ) {
			$thumbs = array();

			// "created_timestamp" moving to just "timestamp"
			foreach ( $options['thumber']['thumbs'] as $id => $thumb ) {
				if ( false === $thumb ) {
					continue;
				}

				$thumbs[ $id ] = array(
					'timestamp'  => $thumb['created_timestamp'],
					'thumb_url'  => $thumb['thumb_url'],
					'thumb_path' => $thumb['thumb_path'],
					'thumber'    => $thumb['thumber']
				);
			}

			$options['thumber']['thumbs'] = $thumbs;

			// adding default thumbnail generation timeout
			$options['thumber']['timeout'] = 30;

			// flatten out "defaults" level
			$options['gallery'] = $options['gallery']['defaults'];

			// adding "validation" branch
			$options['validation'] = false;

			// adding "logging" branch
			$options['logging'] = false;
		}
	}

	/**
	 * Some of the data previously stored along with custom CSS is no longer needed.
	 *
	 * @param array $options The options to be modified.
	 */
	private static function twoPointThree( &$options ) {
		if ( isset( $options['version'] ) && version_compare( $options['version'], '2.3', '<' ) ) {
			include_once DG_PATH . 'inc/class-thumber.php';

			unset( $options['css']['last-modified'] );
			unset( $options['css']['etag'] );
			unset( $options['css']['version'] );

			// need to recalculate minified, excluding static CSS which was previously included
			//$options['css']['minified'] = DocumentGallery::compileCustomCss($options['css']['text']);

			// if user inadvertantly enabled google drive viewer on system where it's not supported
			// then avoid locking it in the on state
			if ( $options['thumber']['active']['google'] ) {
				$options['thumber']['active']['google'] = false /* DG_Thumber::isGoogleDriveAvailable() */
				;
			}

			$options['gallery']['post_status'] = 'any';
			$options['gallery']['post_type']   = 'attachment';
			$options['gallery']['limit']       = - 1;
		}
	}

	/**
	 * Creating new meta branch in options to store plugin meta information.
	 *
	 * "Localpost" no longer supported. Replaced by "id" attribute.
	 * "Images" no longer supported. Replaced by "mime_types" attribute.
	 * "Ids" still supported, but not stored in DB.
	 *
	 * Google thumber no longer supported.
	 *
	 * Added "columns" attribute.
	 * Added "mime_types" attribute.
	 *
	 * @param array $options The options to be modified.
	 */
	private static function threePointZeroBeta( &$options ) {
		if ( isset( $options['version'] ) /*&& version_compare($options['version'], '3.0.0-beta', '<')*/ ) {
			$options['meta'] = array( 'version' => $options['version'] );
			unset( $options['version'] );

			$images = $options['gallery']['images'];

			unset( $options['gallery']['localpost'] );
			unset( $options['gallery']['ids'] );
			unset( $options['gallery']['images'] );

			unset( $options['thumber']['active']['google'] );

			$defaults                         = self::getDefaultOptions();
			$options['gallery']['columns']    = $defaults['gallery']['columns'];
			$options['gallery']['mime_types'] = $defaults['gallery']['mime_types'];
			if ( $images ) {
				$options['gallery']['mime_types'] .= ',image';
			}
		}
	}

	/**
	 * Flat logging option split out into multiple options in a nested array.
	 *
	 * Added scheduled log purge event to handle rollovers.
	 *
	 * @param array $options The options to be modified.
	 */
	private static function threePointOne( &$options ) {
		if ( version_compare( $options['meta']['version'], '3.1', '<' ) ) {
			$logging_enabled    = $options['logging'];
			$options['logging'] = array(
				'enabled'        => $logging_enabled,
				'purge_interval' => 7
			);

			// purge log entries regularly
			wp_schedule_event( time(), 'daily', DG_Logger::PurgeLogsAction );
		}
	}

	/**
	 * Adds 'new_window' under gallery options.
	 *
	 * @param array $options The options to be modified.
	 */
	private static function threePointTwo( &$options ) {
		if ( version_compare( $options['meta']['version'], '3.2', '<' ) ) {
			$options['gallery']['new_window'] = false;
		}
	}

	/**
	 * Removes minified CSS. Fixing corrupt data for boolean fields that may have gotten strings.
	 *
	 * @param array $options The options to be modified.
	 */
	private static function threePointThree( &$options ) {
		if ( version_compare( $options['meta']['version'], '3.3', '<' ) ) {
			unset( $options['css']['minified'] );

			$defaults = self::getDefaultOptions();
			foreach ( $defaults as $class => $block ) {
				if ( is_array($block) ) {
					foreach ( $block as $prop => $value ) {
						if ( is_bool( $value ) && isset( $options[$class][$prop] ) && ! is_bool( $options[$class][$prop] ) ) {
							$options[$class][$prop] = DG_Util::toBool( $options[$class][$prop], $value );
						}
					}
				} elseif ( is_bool( $block ) && isset( $options[$class] ) && ! is_bool( $options[$class] ) ) {
					$options[$class] = DG_Util::toBool( $options[$class], $block );
				}
			}
		}
	}

	/**
	 * Sets up Document Gallery on all blog(s) activated.
	 *
	 * @param bool $networkwide Whether this is a network-wide update (multisite only).
	 */
	public static function activate( $networkwide ) {
		$blogs = array( null );

		if ( is_multisite() ) {
			// check if it is a network activation
			if ( $networkwide ) {
				$blogs = DG_Util::getBlogIds();
			}
		}

		foreach ( $blogs as $blog ) {
			self::_activate( $blog );
		}

		// handle purging log entries regularly
		wp_schedule_event( time(), 'daily', DG_Logger::PurgeLogsAction );
	}

	/**
	 * Hooked into wpmu_new_blog to handle activating a new blog when plugin
	 * is already network activated.
	 * See discussion: https://core.trac.wordpress.org/ticket/14170
	 *
	 * @param int $blog Blog ID.
	 */
	public static function activateNewBlog( $blog ) {
		if ( is_plugin_active_for_network( DG_BASENAME ) ) {
			self::_activate( $blog );
		}
	}

	/**
	 * Runs activation setup for Document Gallery on all blog(s) it is activated on.
	 *
	 * @param int $blog Blog to update or null if updating current blog.
	 */
	private static function _activate( $blog ) {
		$options = DocumentGallery::getOptions( $blog );

		// first activation
		if ( is_null( $options ) ) {
			DocumentGallery::setOptions( self::getDefaultOptions(), $blog );
		}
	}

	/**
	 * Runs when DG is uninstalled.
	 */
	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		check_admin_referer( 'bulk-plugins' );

		$blogs = array( null );

		if ( is_multisite() ) {
			$blogs = DG_Util::getBlogIds();
		}

		foreach ( $blogs as $blog ) {
			self::_uninstall( $blog );
		}

		wp_clear_scheduled_hook( DG_Logger::PurgeLogsAction );
	}

	/**
	 * Runs when DG is uninstalled for an individual blog.
	 */
	private static function _uninstall( $blog ) {
		$options = DG_Thumber::getOptions( $blog );
		if ( is_null( $options ) ) {
			return;
		}

		foreach ( $options['thumbs'] as $val ) {
			if ( isset( $val['thumber'] ) ) {
				@unlink( $val['thumb_path'] );
			}
		}

		DocumentGallery::deleteOptions( $blog );
	}

	/**
	 * NOTE: This is expensive as is involves file I/O reading the README. Only use when
	 * the equivalent value in options array is not viable.
	 * @return string URL where users can donate to plugin.
	 */
	private static function getDonateLink() {
		$data = get_file_data( DG_PATH . 'README.txt', array( 'donate' => 'Donate link' ) );

		return $data['donate'];
	}

	/**
	 * Blocks instantiation. All functions are static.
	 */
	private function __construct() {

	}
}
