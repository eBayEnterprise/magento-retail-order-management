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
	 * @return int an Image Id
	 */
	public function getIdByViewAndName($sku, $imageView, $imageName)
	{
		$adapter = $this->_getReadAdapter();
		$select = $adapter->select()
			->from($this->getMainTable(), 'id')
			->where('sku = :sku and view = :image_view and name = :image_name');
		$bind = array(
			':sku'        => (string) $sku,
			':image_name' => (string) $imageName,
			':image_view' => (string) $imageView,
		);
		return $adapter->fetchOne($select, $bind);
	}

	/**
	 * Returns just the earliest-created image id for this SKU
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
			':sku'        => (string) $sku,
		);
		return $adapter->fetchOne($select, $bind);
	}
}
