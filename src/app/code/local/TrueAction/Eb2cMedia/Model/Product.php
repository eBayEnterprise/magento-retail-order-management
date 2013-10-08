<?php
class TrueAction_Eb2cMedia_Model_Product extends Mage_Catalog_Model_Product
{
	/**
	 * Retrive media gallery images
	 *
	 * @return Varien_Data_Collection
	 */
	public function getMediaGalleryImages()
	{
		return Mage::getModel('eb2cmedia/images')
			->getCollection()
			->addFieldToFilter(
				'sku',
				array(
					'eq',
					$this->getSku()
				)
			);
	}

	/**
	 * Return url of first image matching SKU
	 *
	 * @return string Image URL
	 */
	public function getImageUrl()
	{
		return Mage::getModel('eb2cmedia/images')->loadBySku($this->getSku())->getUrl();
	}

	/**
	 * Media API: Overriding non-magic getters that are unused by Eb2cMedia Images:
	 */

	/**
	 * Return url for Small Image; Overriding the non-magic method.
	 *
	 * @param int width ignored (supplied to satisfy overriden model's parameters).
	 * @param int height ignored  "
	 * @return string ''
	 */
	public function getSmallImageUrl($width=88, $height=77)
	{
		return Mage::getModel('eb2cmedia/images')->loadBySkuAndName($this->getSku(), 'small_image')->getUrl();
	}

	/**
	 * Return for Thumbnail Image; Overriding the non-magic method.
	 *
	 * @param int width ignored (supplied to satisfy overriden model's parameters).
	 * @param int height ignored  "
	 * @return string ''
	 */
    public function getThumbnailUrl($width=75, $height=75)
	{
		return Mage::getModel('eb2cmedia/images')->loadBySkuAndName($this->getSku(), 'thumbnail')->getUrl();
	}
}
