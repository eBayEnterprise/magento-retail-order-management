<?php
class TrueAction_Eb2cMedia_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Get Product config instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		$configModel = Mage::getModel('eb2ccore/config_registry');
		$configModel->setStore($store)
			->addConfigModel(Mage::getModel('eb2cmedia/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		return $configModel;
	}
}
