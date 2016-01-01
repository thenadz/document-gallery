<?php

include_once 'base-transaction.php';

/**
 * Class ThumberThumbTransaction Adds support for a binary "data" field, which is transmitted in base64-encoded format.
 * This class handles conversion to and from base64 encoding as needed in a lazy manner that avoids more compute than
 * is necessary only *when* it's necessary.
 */
abstract class ThumberThumbTransaction extends ThumberBaseTransaction {
	/**
	 * The base64-encoded data.
	 *
	 * @var string base64-encoded data.
	 */
	protected $data;

	/**
	 * Sets the base64-encoded data.
	 *
	 * @param string $data The base64-encoded data.
	 */
	public function setEncodedData($data) {
		$this->data = $data;
		$this->decodedData = null;
	}

	/**
	 * Gets the base64-encoded data.
	 *
	 * NOTE: If only raw data is initialized, this method will populate the base64-encoded data from that value.
	 *
	 * @return string The base64-encoded data.
	 */
	public function getEncodedData() {
		if (empty($this->data) && !empty($this->decodedData)) {
			$this->data = base64_encode($this->decodedData);
		}

		return $this->data;
	}

	/**
	 * The raw file data. Private as opposed to protected to avoid being included in JSON by ThumberBaseTransaction
	 * reflection.
	 *
	 * @var data Raw data read from file.
	 */
	private $decodedData;

	/**
	 * Gets the raw file data.
	 *
	 * @param data $decodedData The raw file data.
	 */
	public function setDecodedData($decodedData) {
		$this->decodedData = $decodedData;
		$this->data = null;
	}

	/**
	 * Gets the raw file data.
	 *
	 * NOTE: If only base64 data is initialized, this method will populate the raw data from that value.
	 *
	 * @return data The raw file data.
	 */
	public function getDecodedData() {
		if (empty($this->decodedData) && !empty($this->data)) {
			$this->decodedData = base64_decode($this->data);
		}

		return $this->decodedData;
	}

	/**
	 * Gets array representation of this instance.
	 *
	 * @return array Array representation of this instance.
	 */
	public function toArray() {
		// force generation of Base64 data if not yet generated
		$this->getEncodedData();

		return parent::toArray();
	}
}