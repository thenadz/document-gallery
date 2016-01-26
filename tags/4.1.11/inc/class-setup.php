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
	 * @return mixed[][] Contains default options for DG.
	 */
	public static function getDefaultOptions( $skeleton = false ) {
		include_once DG_PATH . 'inc/class-thumber.php';

		$gs = $donate_link = null;
		if ( ! $skeleton ) {
			$gs          = DG_GhostscriptThumber::getGhostscriptExecutable();
			$donate_link = self::getDonateLink();
		}

		return array(
			'thumber'    => array(
				// Ghostscript path
				'gs'      => $gs,
				// which thumbnail generation methods are available
				'active'  => DG_Thumber::getDefaultThumbers( $skeleton ),
				// max width to generate thumbnails
				'width'   => 200,
				// max height to generate thumbnails
				'height'  => 200
			),
			'thumber-co' => array(
				'uid'           => null,
				'secret'        => null,
				'subscription'  => array(),
				'direct_upload' => false,
				'mime_types'    => array()
			),
			'gallery'    => array(
				// default: link directly to file (true to link to attachment pg)
				'attachment_pg' => false,
				// # of columns to be used in gallery
				'columns'       => 4,
				// include the attachment description in output
				'descriptions'  => false,
				// include thumbnail of actual document in gallery display
				'fancy'         => true,
				// the max number of thumbnails to return
				'limit'         => -1,
				// comma-delimited list of all mime types to be included
				'mime_types'    => implode( ',', self::getDefaultMimeTypes() ),
				// whether to open documents in new window
				'new_window'    => false,
				// ascending/descending order for included documents
				'order'         => 'ASC',
				// which property to order by
				'orderby'       => 'menu_order',
				// whether to paginate galleries with a "limit"
				'paginate'      => true,
				// the status the post must be in when returned by DG
				'post_status'   => 'any',
				// the type of post to be returned
				'post_type'     => 'attachment',
				// AND or OR
				'relation'      => 'AND',
				// how many documents to skip
				'skip'          => 0
			),
			'css'        => array(
				// plain text of CSS to be edited by user
				'text' => ''
			),
			'meta'       => array(
				// current DG version
				'version'     => DG_VERSION,
				// items per page at Thumbnail Management tab
				'items_per_page' => 10,
				// URL to donate to plugin development
				'donate_link' => $donate_link
			),
			// logging options
			'logging'    => array(
				// TODO: more granular -- log_level instead of blanket enable/disable
				'enabled'        => defined( 'WP_DEBUG' ) && WP_DEBUG,
				// max age of log entry (days)
				'purge_interval' => 7
			)
		);
	}

	/**
	 * @return string[] The default MIME types to include in gallery.
	 */
	public static function getDefaultMimeTypes() {
		return array( 'application', 'video', 'text', 'audio', 'image' );
	}

	/**
	 * Runs every page load, updates as needed.
	 */
	public static function maybeUpdate() {
		global $dg_options;
		if ( is_null( $dg_options ) ) {
			return;
		}

		// version has historically been in two locations -- must check both to continue supporting upgrading from those old versions
		$old_version = isset( $dg_options['version'] ) ? $dg_options['version'] : $dg_options['meta']['version'];
		if ( ! is_null( $dg_options ) && DG_VERSION !== $old_version ) {
			DG_Logger::writeLog( DG_LogLevel::Detail, "Upgrading Document Gallery from version $old_version to " . DG_VERSION );

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
		self::threePointFour( $options );
		self::threePointFive( $options );
		self::fourPointZero( $options );
		self::fourPointOne( $options );
		self::fourPointOnePointFive( $options );

		// update plugin meta data
		$options['meta']['version']     = DG_VERSION;
		$options['meta']['donate_link'] = self::getDonateLink();

		// remove previously-failed thumbs
		DG_Thumb::purgeFailedThumbs();

		DocumentGallery::setOptions( $options, $blog );
	}

	/**
	 * The 'created_timestamp' key in each thumb record is being moved
	 * to 'timestamp' as part of a move to store timestamp for failed
	 * thumbnails in addition to successful ones.
	 *
	 * The defaults sub-branch in the gallery branch is being flattened into its parent.
	 *
	 * @param mixed[][] $options The options to be modified.
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
	 * @param mixed[][] $options The options to be modified.
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
	 * @param mixed[][] $options The options to be modified.
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
	 * @param mixed[][] $options The options to be modified.
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
	 * @param mixed[][] $options The options to be modified.
	 */
	private static function threePointTwo( &$options ) {
		if ( version_compare( $options['meta']['version'], '3.2', '<' ) ) {
			$options['gallery']['new_window'] = false;
		}
	}

	/**
	 * Removes minified CSS. Fixing corrupt data for boolean fields that may have gotten strings.
	 *
	 * @param mixed[][] $options The options to be modified.
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
	 * Removes the validation option. Validation is now non-optional.
	 *
	 * @param mixed[][] $options The options to be modified.
	 */
	private static function threePointFour( &$options ) {
		if ( version_compare( $options['meta']['version'], '3.4', '<' ) ) {
			unset( $options['validation'] );

			if ( ! DocumentGallery::isValidOptionsStructure( $options ) ) {
				DG_Logger::writeLog(
					DG_LogLevel::Error,
					'Found invalid options structure. Reverting to default options.',
					false,
					true );
				$options = self::getDefaultOptions();
			}
		}
	}

	/**
	 * There is no longer a concept of gallery load timeout. Missing thumbnails are asynchronously generated after
	 * a gallery is first rendered via AJAX requests.
	 *
	 * @param mixed[][] $options The options to be modified.
	 */
	private static function threePointFive( &$options ) {
		if ( version_compare( $options['meta']['version'], '3.5', '<' ) ) {
			unset( $options['thumber']['timeout'] );
		}
	}

	/**
	 * Adds the meta items_per_page default value.
	 * Paginate & skip options were added.
	 * Moving cached thumbs into postmeta table.
	 *
	 * @param mixed[][] $options The options to be modified.
	 */
	private static function  fourPointZero( &$options ) {
		if ( version_compare( $options['meta']['version'], '4.0', '<' ) ) {
			$options['gallery']['paginate'] = true;
			$options['gallery']['skip'] = 0;
			$options['meta']['items_per_page'] = 10;

			$upload_dir = wp_upload_dir();
			$upload_len = strlen( $upload_dir['basedir'] );
			$dimensions = $options['thumber']['width'] . 'x' . $options['thumber']['height'];
			foreach ( $options['thumber']['thumbs'] as $id => $thumb ) {
				$thumb_obj = new DG_Thumb();
				$thumb_obj->setPostId( $id );
				$thumb_obj->setTimestamp( $thumb['timestamp'] );
				$thumb_obj->setDimensions( $dimensions );
				if ( isset( $thumb['thumb_path'] ) ) {
					$thumb_obj->setRelativePath( substr( $thumb['thumb_path'], $upload_len + 1 ) );
					$thumb_obj->setGenerator( DG_Util::callableToString( $thumb['thumber'] ) );
				}

				$thumb_obj->save();
			}

			unset( $options['thumber']['thumbs'] );
		}
	}

	/**
	 * Adds integration w/ Thumber.co service.
	 * Update existing thumbs to match new thumbnail generation architecture.
	 *
	 * @param mixed[][] $options The options to be modified.
	 */
	private static function fourPointOne( &$options ) {
		if ( version_compare( $options['meta']['version'], '4.1', '<' ) ) {
			$options['thumber']['active']['thumber-co'] = false;
			$options['thumber-co'] = array(
					'uid'           => null,
					'secret'        => null,
					'subscription'  => array(),
					'direct_upload' => false,
					'mime_types'    => array()
			);

			$old_thumbs = DG_Thumb::getThumbs( $options['thumber']['width'] . 'x' . $options['thumber']['height'] );
			DG_Thumb::purgeThumbs( null, null, false );
			foreach ( $old_thumbs as $thumb ) {
				if ( $thumb->isSuccess() ) {
					$generator = $thumb->getGenerator();
					if ( $generator == 'DG_Thumber::getGhostscriptThumbnail' ) {
						$thumb->setGenerator( 'DG_GhostscriptThumber' );
					} elseif ( $generator == 'DG_Thumber::getImagickThumbnail' ) {
						$thumb->setGenerator( 'DG_ImagickThumber' );
					} elseif ( $generator == 'DG_Thumber::getAudioVideoThumbnail' ) {
						$thumb->setGenerator( 'DG_AudioVideoThumber' );
					}

					$thumb->save();
				}
			}
		}
	}

	/**
	 * Cleans up the mess created in the fourPointOne upgrade script. The
	 * thumbnail files were removed, while the thumbnail DB entries were left.
	 *
	 * @param mixed[][] $options The options to be modified.
	 */
	private static function fourPointOnePointFive( &$options ) {
		if ( version_compare( $options['meta']['version'], '4.1.5', '<' ) ) {
			$thumbs = DG_Thumb::getThumbs( $options['thumber']['width'] . 'x' . $options['thumber']['height'] );
			$ids = array();
			foreach ( $thumbs as $thumb ) {
				if ( $thumb->isSuccess() && !@file_exists( $thumb->getPath() ) ) {
					$ids[] = $thumb->getPostId();
				}
			}

			DG_Thumb::purgeThumbs( $ids );
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
		DG_Thumb::purgeThumbs( null, $blog );
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
