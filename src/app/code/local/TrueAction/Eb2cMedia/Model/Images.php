<?php
class TrueAction_Eb2cMedia_Model_Images extends Mage_Core_Model_Abstract
{
	/**
	 * Standard constructor
	 */
	protected function _construct()
	{
        $this->_init('eb2cmedia/images', 'id');
	}

	/**
	 * Product Id, Name and Value combine to form (I think/ hope) a unique key. If I can
	 * load it, I simply update this image.
	 *
	 * id of Image matching
	 */
	public function getIdByName($productId, $imageName, $imageView)
	{
		return $this->_getResource()->getIdByName($productId, $imageName, $imageView);
	}
}
