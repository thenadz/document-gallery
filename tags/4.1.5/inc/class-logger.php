<?php
defined( 'WPINC' ) OR exit;

/**
 * Encapsulates the logic required to maintain and read log files.
 */
class DG_Logger {

	/**
	 * @var string Name of the log purge action.
	 */
	const PurgeLogsAction = 'document-gallery_purge-logs';

	/**
	 * Appends DG log file if logging is enabled.
	 *
	 * @param int $level The level of serverity (should be passed using DG_LogLevel consts).
	 * @param string $entry Value to be logged.
	 * @param bool $stacktrace Whether to include full stack trace.
	 * @param bool $force Whether to ignore logging flag and log no matter what.
	 *                         Only should be used when the user *must* know about something.
	 */
	public static function writeLog( $level, $entry, $stacktrace = false, $force = false ) {
		if ( $force || self::logEnabled() ) {
			$fp = fopen( self::getLogFileName(), 'a' );
			if ( false !== $fp ) {
				$fields = array( time(), $level, $entry );

				$trace = debug_backtrace( false );
				if ( $stacktrace ) {
					unset( $trace[0] );
					$fields[] = self::getStackTraceString( $trace );
				} else {
					// Ignore first item from backtrace as it's this function which is redundant.
					$caller    = $trace[1];

					$class = isset( $caller['class'] ) ? $caller['class'] : '';
					$type = isset( $caller['type'] ) ? $caller['type'] : '';
					$caller    = $class . $type . $caller['function'];

					$fields[2] = '(' . $caller . ') ' . $fields[2];
				}

				fputcsv( $fp, $fields );
				fclose( $fp );
			} // TODO: else
		}
	}

	/**
	 * Reads the current blog's log file, placing the values in to a 2-dimensional array.
	 *
	 * @param int $skip How many lines to skip before returning rows.
	 * @param int $limit Max number of lines to read.
	 *
	 * @return string[][]|null The rows from the log file or null if failed to open log.
	 */
	public static function readLog( $skip = 0, $limit = PHP_INT_MAX ) {
		$ret = null;
		$fp  = @fopen( self::getLogFileName(), 'r' );

		if ( $fp !== false ) {
			$ret = array();
			while ( count( $ret ) < $limit && false !== ( $fields = fgetcsv( $fp ) ) ) {
				if ( $skip > 0 ) {
					$skip --;
					continue;
				}

				if ( ! is_null( $fields ) ) {
					$ret[] = $fields;
				}
			}

			@fclose( $fp );
		}

		return $ret;
	}

	/**
	 * Clears the log file for the active blog.
	 */
	public static function clearLog() {
		// we don't care if the file actually exists -- it won't when we're done
		@unlink( self::getLogFileName() );
	}

	/**
	 * Clears the log file for all blogs.
	 */
	public static function clearLogs() {
		// we don't care if the files actually exist -- they won't when we're done
		foreach ( DG_Util::getBlogIds() as $id ) {
			@unlink( self::getLogFileName( $id ) );
		}
	}

	/**
	 * Truncates all blog logs to the current purge interval.
	 *
	 * TODO: This is a memory hog. Consider switching to stream filter.
	 */
	public static function purgeExpiredEntries() {
		self::writeLog( DG_LogLevel::Detail, 'Beginning scheduled log file purge.' );

		$blogs = array( null );
		if ( is_multisite() ) {
			$blogs = DG_Util::getBlogIds();
		}

		// truncate each blog's log file
		$time = time();
		foreach ( $blogs as $blog ) {
			$blog_num       = ! is_null( $blog ) ? $blog : get_current_blog_id();
			$options        = self::getOptions( $blog );
			$purge_time     = $time - $options['purge_interval'] * DAY_IN_SECONDS;

			// purging is disabled for this blog
			if ( $purge_time >= $time ) {
				continue;
			}

			// do purge for this blog
			$file = self::getLogFileName( $blog_num );
			if ( file_exists( $file ) ) {
				$fp = @fopen( $file, 'r' );

				if ( $fp !== false ) {
					$truncate = false;
					$offset   = 0;

					// find the first non-expired entry
					while ( ( $fields = fgetcsv( $fp ) ) !== false ) {
						if ( ! is_null( $fields ) && intval( $fields[0] ) >= $purge_time ) {
							// we've reached the recent entries -- nothing beyond here will be removed
							break;
						}

						$offset   = @ftell( $fp );
						if ( false === $offset ) {
							break;
						}

						$truncate = true;
					}

					@fclose( $fp );

					// if any expired entries exist -- remove them from the file
					if ( $truncate ) {
						self::writeLog( DG_LogLevel::Detail, "Purging log entries for blog #$blog_num." );
						$data = file_get_contents( $file, false, null, $offset );
						file_put_contents( $file, $data, LOCK_EX );
					}
				}
			}
		}
	}

	/**
	 * Generally not necessary to call external to this class -- only use if generating
	 * log entry will take significant resources and you want to avoid this operation
	 * if it will not actually be logged.
	 *
	 * @return bool Whether debug logging is currently enabled.
	 */
	public static function logEnabled() {
		$options = self::getOptions();

		return $options['enabled'];
	}

	/**
	 * Gets logging options.
	 *
	 * @param int $blog ID of the blog to be retrieved in multisite env.
	 *
	 * @return mixed[] Logger options for the blog.
	 */
	public static function getOptions( $blog = null ) {
		$options = DocumentGallery::getOptions( $blog );

		return $options['logging'];
	}

	/**
	 * @param $id int The ID of the blog to retrieve log file name for. Defaults to current blog.
	 *
	 * @return string Full path to log file for current blog.
	 */
	private static function getLogFileName( $id = null ) {
		$id = ! is_null( $id ) ? $id : get_current_blog_id();

		return DG_PATH . 'log/' . $id . '.log';
	}

	/**
	 * @param mixed[][] $trace Array containing stack trace to be converted to string.
	 *
	 * @return string The stack trace in human-readable form.
	 */
	private static function getStackTraceString( $trace ) {
		$trace_str = '';
		$i         = 1;

		foreach ( $trace as $node ) {
			$trace_str .= "#$i ";

			$file = '';
			if ( isset( $node['file'] ) ) {
				// convert to relative path from WP root
				$file = str_replace( ABSPATH, '', $node['file'] );
			}

			if ( isset( $node['line'] ) ) {
				$file .= "({$node['line']})";
			}

			if ( $file ) {
				$trace_str .= "$file: ";
			}

			if ( isset( $node['class'] ) ) {
				$trace_str .= "{$node['class']}{$node['type']}";
			}

			if ( isset( $node['function'] ) ) {
				// only include args for first item in stack trace
				$args = '';
				if ( 1 === $i && isset( $node['args'] ) ) {
					$args = implode( ', ', array_map( array( __CLASS__, 'print_r' ), $node['args'] ) );
				}

				$trace_str .= "{$node['function']}($args)" . PHP_EOL;
			}
			$i ++;
		}

		return $trace_str;
	}

	/**
	 * Wraps print_r passing true for the return argument.
	 *
	 * @param mixed $v Value to be printed.
	 *
	 * @return string Printed value.
	 */
	private static function print_r( $v ) {
		return preg_replace( '/\s+/', ' ', print_r( $v, true ) );
	}

	/**
	 * Blocks instantiation. All functions are static.
	 */
	private function __construct() {

	}
}

/**
 * LogLevel acts as an enumeration of all possible log levels.
 */
class DG_LogLevel {
	/**
	 * @var int Log level for anything that doesn't indicate a problem.
	 */
	const Detail = 0;

	/**
	 * @var int Log level for anything that is a minor issue.
	 */
	const Warning = 1;

	/**
	 * @var int Log level for when something went wrong.
	 */
	const Error = 2;

	/**
	 * @var ReflectionClass Backs the getter.
	 */
	private static $ref = null;

	/**
	 * @return ReflectionClass Instance of reflection class for this class.
	 */
	private static function getReflectionClass() {
		if ( is_null( self::$ref ) ) {
			self::$ref = new ReflectionClass( __CLASS__ );
		}

		return self::$ref;
	}

	/**
	 * @var int[] Backs the getter.
	 */
	private static $levels = null;

	/**
	 * @return int[] Associative array containing all log level names mapped to their int value.
	 */
	public static function getLogLevels() {
		if ( is_null( self::$levels ) ) {
			$ref          = self::getReflectionClass();
			self::$levels = $ref->getConstants();
		}

		return self::$levels;
	}

	/**
	 * @param string $name Name to be checked for validity.
	 *
	 * @return bool Whether given name represents valid log level.
	 */
	public static function isValidName( $name ) {
		return array_key_exists( $name, self::getLogLevels() );
	}

	/**
	 * @param int $value Value to be checked for validity.
	 *
	 * @return bool Whether given value represents valid log level.
	 */
	public static function isValidValue( $value ) {
		return ( false !== array_search( $value, self::getLogLevels() ) );
	}

	/**
	 * @param string $name The name for which to retrieve a value.
	 *
	 * @return int|null The value associated with the given name.
	 */
	public static function getValueByName( $name ) {
		$levels = self::getLogLevels();

		return array_key_exists( $name, self::getLogLevels() ) ? $levels[ $name ] : null;
	}

	/**
	 * @param int $value The value for which to retrieve a name.
	 *
	 * @return string|null The name associated with the given value.
	 */
	public static function getNameByValue( $value ) {
		$ret = array_search( $value, self::getLogLevels() );

		return ( false !== $ret ) ? $ret : null;
	}

	/**
	 * Blocks instantiation. All functions are static.
	 */
	private function __construct() {

	}
}