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
	 * Return url of first image matching SKU that
	 * we can find.
	 *
	 * @return string Image URL
	 */
	public function getImageUrl()
	{
		$imageModel = Mage::getModel('eb2cmedia/images');
		$imageId = $imageModel->getIdBySku($this->getSku());
		if( $imageId ) {
			return $imageModel->load($imageId)->getUrl();
		}
		return '';
	}

	/**
	 * Media API: Overriding non-magic getters that are unused by Eb2cMedia Images:
	 */

	/**
	 * Return all blanks for Media Attributes
	 *
	 * @return string ''
	 */
	public function getMediaAttributes()
	{
		return '';
	}

	/**
	 * Return all blanks for Media Config
	 *
	 * @return string ''
	 */
	public function getMediaConfig()
	{
		return '';
	}

	/**
	 * Return blank url for Small Image. Overriding the non-magic method.
	 *
	 * @return string ''
	 */ 
	public function getSmallImageUrl()
	{
		return '';
	}

	/**
	 * Return blank url for Thumbnail Image. Overriding the non-magic method.
	 *
	 * @return string ''
	 */ 
	public function getThumbnailImageUrl()
	{
		return '';
	}
}
