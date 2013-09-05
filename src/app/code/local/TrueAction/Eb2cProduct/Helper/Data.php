<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $configModel;

	public function __construct()
	{
		$this->getConfigModel(null);
	}

	/**
	 * Get Product config instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		$this->configModel = Mage::getModel('eb2ccore/config_registry');
		$this->configModel->setStore($store)
			->addConfigModel(Mage::getModel('eb2cproduct/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		return $this->configModel;
	}
}
