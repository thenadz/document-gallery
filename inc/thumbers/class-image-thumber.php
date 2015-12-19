<?php
defined( 'WPINC' ) OR exit;

class DG_ImageThumber extends DG_AbstractThumber {

	/**
	 * @return string[] The extensions supported by this thumber.
	 */
	protected function getThumberExtensions() {
		return array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' );
	}

	/**
	 * @param string $ID The attachment ID to retrieve thumbnail from.
	 * @param int $pg Unused.
	 *
	 * @return bool|string  False on failure, URL to thumb on success.
	 */
	public function getThumbnail( $ID, $pg = 1 ) {
		$options = DG_Thumber::getOptions();
		$ret     = false;

		if ( $icon = image_downsize( $ID, array( $options['width'], $options['height'] ) ) ) {
			$ret = $icon[0];
		}

		return $ret;
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority() {
		return 100;
	}
}

DG_ImageThumber::init();