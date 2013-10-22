<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Suppression
{
	/**
	 * @var array, hold known none eb2c payment modules
	 */
	protected $_noneEbcPaymentModules = array('Mage_Authorizenet', 'Mage_Paygate', 'Mage_PaypalUk', 'Mage_Paypal');
	/**
	 * disabling magento modules from config as well as from ouputing
	 * @param string $moduleName, the module to disabled
	 * @return self
	 */
	public function disableModule($moduleName)
	{
		// Disable the module itself
		$nodePath = "modules/$moduleName/active";
		if (Mage::helper('core/data')->isModuleEnabled($moduleName)) {
			Mage::getConfig()->setNode($nodePath, 0);
		}

		// Disable its output as well (which was already loaded)
		$outputPath = "advanced/modules_disable_output/$moduleName";
		if (!Mage::getStoreConfig($outputPath)) {
			//Mage::log(sprintf("Inside [%s::%s]\n\r%s: %s\n\r%s: %s", __CLASS__, __METHOD__, 'outputPath', $outputPath, 'class', get_class(Mage::getConfig())), Zend_Log::DEBUG);
			//Mage::getConfig()->saveConfig($outputPath, 0, 'default', 0);
			Mage::app()->getStore()->setConfig($outputPath, true);
		}

		Mage::getConfig()->cleanCache();
		Mage::getConfig()->reinit();

		return $this;
	}

	/**
	 * disabling non-eb2c payment modules if eb2cpayment is enabled
	 * @return void
	 */
	public function disabledNonEb2cPaymentModules()
	{
		$modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
		$section = 'advanced';
		$website = '';
		$store = '';
		$groups = array();
		foreach ($modules as $moduleName) {
			if ($moduleName !== 'Mage_Adminhtml') {
				if (in_array($moduleName, $this->_noneEbcPaymentModules)) {
					$groups['modules_disable_output']['fields'][$moduleName] = array('value' => 1);
				} else {
					$groups['modules_disable_output']['fields'][$moduleName] = array('value' => 0);
				}
			}
		}

		if (!empty($groups)) {
			Mage::getSingleton('adminhtml/config_data')
				->setSection($section)
				->setWebsite($website)
				->setStore($store)
				->setGroups($groups)
				->save();

			// reinit configuration
			Mage::getConfig()->reinit();
		}
	}
}
