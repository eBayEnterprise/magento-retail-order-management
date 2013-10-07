<?php
class TrueAction_Eb2cMedia_Model_Images extends Mage_Core_Model_Abstract
{
	/**
	 * Standard constructor
	 *
	 */
	protected function _construct()
	{
		$this->_init('eb2cmedia/images', 'id');
	}

	/**
	 * SKU, View and Name combine to form a unique key. This function
	 * is to assist in updating/ adding images.
	 *
	 * @return int id of image matching
	 */
	public function getIdByViewAndName($sku, $imageView, $imageName)
	{
		return $this->_getResource()->getIdByViewAndName($sku, $imageView, $imageName);
	}

	/**
	 * Get first image matching SKU and Name
	 *
	 * @return int id of image matching
	 */
	public function getIdBySkuAndName($sku, $imageName)
	{
		return $this->_getResource()->getIdBySkuAndName($sku, $imageName);
	}

	/**
	 * Load first image found that matches SKU
	 *
	 * @return TrueAction_Eb2cMedia_Model_Images
	 */
	public function loadBySkuAndName($sku, $imageName)
	{
		$this->load($this->getIdBySkuAndName($sku, $imageName));
		return $this;
	}

	/**
	 * Get first id matching SKU
	 *
	 * @return int id of first image matching this SKU
	 */
	public function getIdBySku($sku)
	{
		return $this->_getResource()->getIdBySku($sku);
	}

	/**
	 * Load first image found that matches SKU
	 *
	 * @return TrueAction_Eb2cMedia_Model_Images
	 */
	public function loadBySku($sku)
	{
		$this->load($this->getIdBySku($sku));
		return $this;
	}
}
