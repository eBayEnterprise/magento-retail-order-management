<?php
class TrueAction_Eb2cMedia_Model_Product extends Mage_Catalog_Model_Product
{
	/**
	 * Retrieve media gallery images
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
	 * Retrieve base magento media gallery images
	 *
	 * @return Varien_Data_Collection
	 */
	public function getBaseMediaGalleryImages()
	{
		return parent::getMediaGalleryImages();
	}

	/**
	 * Return url of first image matching SKU
	 * @todo Rather than willy nilly select an image, specify a View and Name in the store config for 'Base Image',
	 * and then call _getUrl with name and view instead.
	 *
	 * @return string Image URL
	 */
	public function getImageUrl()
	{
		return Mage::helper('eb2cmedia')->getImageHost() .
			Mage::getModel('eb2cmedia/images')->loadBySku($this->getSku())->getUrl();
	}

	/**
	 * Media API: Overriding non-magic getters that are nice convenient methods on the frontend.
	 */

	/**
	 * Return url for Small Image; Overriding the non-magic method.
	 * @todo Rather than hardcode 'small_image' here, specify a View and Name in the store config for 'Small Image'
	 *
	 * @param int width ignored (supplied to satisfy overriden model's parameters).
	 * @param int height ignored  "
	 * @return string ''
	 */
	public function getSmallImageUrl($width=88, $height=77)
	{
		return $this->_getUrl('small_image');
	}

	/**
	 * Return for Thumbnail Image; Overriding the non-magic method.
	 * @todo Rather than hardcode 'thumbnail' here, specify a View and Name in the store config for 'Thumbnail'
	 *
	 * @param int width ignored (supplied to satisfy overriden model's parameters).
	 * @param int height ignored  "
	 * @return string ''
	 */
	public function getThumbnailUrl($width=75, $height=75)
	{
		return $this->_getUrl('thumbnail');
	}

	/**
	 * Return url for first image matching name
	 * @todo Change this to also accept View, then do loadBySkuViewName() instead.
	 * @param name
	 * @return string url
	 */
	private function _getUrl($name) 
	{
		return Mage::helper('eb2cmedia')->getImageHost() .
			Mage::getModel('eb2cmedia/images')->loadBySkuAndName($this->getSku(), $name)->getUrl();
	}
}
