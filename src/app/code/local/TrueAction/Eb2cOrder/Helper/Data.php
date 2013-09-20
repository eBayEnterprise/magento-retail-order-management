<?php
class TrueAction_Eb2cOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Gets a combined configuration model from core and order
	 *
	 * @return TrueAction_Eb2cCore_Config_Registry
	 */
	public function getConfig()
	{
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2corder/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * Instantiate and save constants-values helper
	 *
	 * @return TrueAction_Eb2cOrder_Helper_Constants
	 */
	public function getConstHelper()
	{
		return Mage::helper('eb2corder/constants');
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		$consts = $this->getConstHelper();
		return Mage::helper('eb2ccore')->getApiUri($consts::SERVICE, $operation);
	}
}
