<?php
class TrueAction_Eb2cOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $apiModel;

	/**
	 * Gets a combined configuration model from core and order
	 *
	 * @return
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
	 * Instantiate and save assignment of Core helper
	 *
	 * @return TrueAction_Eb2cCore_Helper
	 */
	public function getCoreHelper()
	{
		return Mage::helper('eb2ccore');
	}

	/**
	 * Helper for feed processing, as from eb2c core
	 *
	 * @return TrueAction_Eb2cCore_Helper_Feed
	 */
	public function getCoreFeedHelper()
	{
		return Mage::helper('eb2ccore/feed');
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		$consts = $this->getConstHelper();
		return $this->getCoreHelper()->getApiUri($consts::SERVICE, $operation);
	}

	/**
	 * Return the Core API model for issuing requests/ retrieving response:
	 */
	public function getApiModel()
	{
		return Mage::getModel('eb2ccore/api');
	}
}
