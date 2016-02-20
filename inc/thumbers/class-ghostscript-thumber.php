<?php
defined( 'WPINC' ) OR exit;

class DG_GhostscriptThumber extends DG_AbstractThumber {

	/**
	 * Initialize the thumber class for use in thumbnail generation.
	 */
	public static function init() {
		$options  = DG_Thumber::getOptions();
		$active   = $options['active'];
		if ( $active['gs'] && self::isGhostscriptAvailable() ) {
			parent::init();
		}
	}

	/**
	 * Get thumbnail for document with given ID using Ghostscript. Imagick could
	 * also handle this, but is *much* slower.
	 *
	 * @param int $ID The attachment ID to retrieve thumbnail from.
	 * @param int $pg The page number to make thumbnail of -- index starts at 1.
	 *
	 * @return bool|string  False on failure, URL to thumb on success.
	 */
	public function getThumbnail($ID, $pg = 1) {
		static $gs = null;

		if ( is_null( $gs ) ) {
			$options = DG_Thumber::getOptions();
			$gs      = $options['gs'];

			if ( false !== $gs ) {
				$gs = escapeshellarg( $gs ) . ' -sDEVICE=png16m -dFirstPage=%1$d'
					. ' -dLastPage=%1$d -dBATCH -dNOPAUSE -dPDFFitPage -sOutputFile=%2$s %3$s 2>&1';
			}
		}

		if ( false === $gs ) {
			return false;
		}

		$doc_path  = get_attached_file( $ID );
		$temp_path = DG_Util::getTempFile();

		exec( sprintf( $gs, $pg, $temp_path, $doc_path ), $out, $ret );

		if ( $ret != 0 ) {
			DG_Logger::writeLog( DG_LogLevel::Error, __( 'Ghostscript failed: ', 'document-gallery' ) . print_r( $out ) );
			@unlink( $temp_path );

			return false;
		}

		return $temp_path;
	}
	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		return array( 'pdf', 'ps', 'eps' );
	}

	/**
	 * Dynamically determines whether we may call gs through exec().
	 *
	 * NOTE: This does not check the options for gs path. Don't use in
	 * thumbnail generation as it's slow and not configurable.
	 *
	 * @return bool|string If available, returns exe path. False otherwise.
	 */
	public static function getGhostscriptExecutable() {
		static $executable = null;

		if ( is_null( $executable ) ) {
			// we must be able to exec()
			$executable = self::isExecAvailable();
			if ( ! $executable ) {
				return $executable;
			}

			// find on Windows system
			if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
				// look for environment variable
				$executable = getenv( 'GSC' );
				if ( $executable ) {
					return $executable;
				}

				// hope GS in the path
				$executable = exec( 'where gswin*c.exe' );
				if ( ! empty( $executable ) ) {
					return $executable;
				}

				// look directly in filesystem
				// 64- or 32-bit binary
				$executable = exec( 'dir /o:n/s/b "C:\Program Files\gs\*gswin*c.exe"' );
				if ( ! empty( $executable ) ) {
					return $executable;
				}

				// 32-bit binary on 64-bit OS
				$executable = exec( 'dir /o:n/s/b "C:\Program Files (x86)\gs\*gswin32c.exe"' );
				$executable = empty( $executable ) ? false : $executable;

				return $executable;
			}

			// handle Linux systems
			$executable = exec( 'which gs' );
			if ( ! empty( $executable ) ) {
				return $executable;
			}

			// GoDaddy and others aren't setup in such a way that
			// the above works so we need to fallback to a direct
			// filesystem check in most common location
			exec( 'test -e /usr/bin/gs', $dummy, $ret );
			$executable = ( $ret === 0 ) ? '/usr/bin/gs' : false;

			return $executable;
		}

		return $executable;
	}

	/**
	 * @return bool Whether we can use the GS executable.
	 */
	public static function isGhostscriptAvailable() {
		static $ret = null;

		if ( is_null( $ret ) ) {
			$options = DG_Thumber::getOptions();
			$ret     = $options['gs'] && self::isExecAvailable();
		}

		return $ret;
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority() {
		return 75;
	}
}

DG_GhostscriptThumber::init();