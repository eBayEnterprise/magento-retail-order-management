<?php
class TrueAction_Eb2cMedia_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Get Configuration
	 *
	 * @return TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2cmedia/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * Get the Image Host, the protocol and clientId + '.'
	 * the callers add the URL from the image model record.
	 */
	public function getImageHost()
	{
		return (Mage::app()->getStore()->isCurrentlySecure() ?'https' : 'http') . '://' . $this->getConfigModel()->clientId . '.';
	}
}
