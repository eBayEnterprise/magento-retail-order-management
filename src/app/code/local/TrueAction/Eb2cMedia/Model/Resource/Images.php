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
	 */
	public function getIdByName($productId, $imageName, $imageView)
	{
		$adapter = $this->_getReadAdapter();
		$select = $adapter->select()
			->from($this->getMainTable(), 'id')
			->where('product_id = :product_id and name = :image_name and view = :image_view');
		$bind = array(
			':product_id' => (string) $productId,
			':image_name' => (string) $imageName,
			':image_view' => (string) $imageView,
		);
		return $adapter->fetchOne($select, $bind);
	}
}
