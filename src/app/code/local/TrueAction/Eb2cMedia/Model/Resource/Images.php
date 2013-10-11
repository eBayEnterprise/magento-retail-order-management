<?php
class TrueAction_Eb2cMedia_Model_Resource_Images extends Mage_Core_Model_Resource_Db_Abstract
{
	/**
	 * Standard resource constructor
	 *
	 */
	protected function _construct()
	{
		$this->_init('eb2cmedia/images', 'id');
	}

	/**
	 * Returns just the id if we find a match on product/ imagename/ imageview
	 *
	 * @param string SKU of product to which this image belongs
	 * @param string imageView the eb2c View of the image
	 * @param string imageName the eb2c Name of the image
	 * @param string imageWidth the eb2c Width of the image
	 * @param string imageHeight the eb2c Height of the image
	 * @return int an Image Id
	 */
	public function getIdBySkuViewNameSize($sku, $imageView, $imageName, $imageWidth, $imageHeight)
	{
		$adapter = $this->_getReadAdapter();
		$select = $adapter->select()
			->from($this->getMainTable(), 'id')
			->where('sku = :sku and view = :image_view and name = :image_name and height = :image_height and width = :image_width');
		$bind = array(
			':sku'          => (string) $sku,
			':image_name'   => (string) $imageName,
			':image_view'   => (string) $imageView,
			':image_width'  => (string) $imageWidth,
			':image_height' => (string) $imageHeight,
		);
		return $adapter->fetchOne($select, $bind);
	}

	/**
	 * Returns the first image id for this SKU
	 *
	 * @return int an Image Id
	 */
	public function getIdBySku($sku)
	{
		$adapter = $this->_getReadAdapter();
		$select = $adapter->select()
			->from($this->getMainTable(), 'id')
			->where('sku = :sku')
			->order(array('id'))
			->limit(1);
		$bind = array(
			':sku' => (string) $sku,
		);
		return $adapter->fetchOne($select, $bind);
	}

	/**
	 * Returns the first image id matching SKU and name (i.e., ignores view)
	 *
	 * @return int an Image Id
	 */
	public function getIdBySkuAndName($sku, $imageName)
	{
		$adapter = $this->_getReadAdapter();
		$select = $adapter->select()
			->from($this->getMainTable(), 'id')
			->where('sku = :sku and name = :image_name')
			->order(array('id'))
			->limit(1);
		$bind = array(
			':sku'        => (string) $sku,
			':image_name' => (string) $imageName,
		);
		return $adapter->fetchOne($select, $bind);
	}
}
