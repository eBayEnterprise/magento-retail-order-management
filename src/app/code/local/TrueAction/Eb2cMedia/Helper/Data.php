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
}
