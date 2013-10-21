<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Suppression
{
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
			Mage::getConfig()->setNode($nodePath, 'false', true);
		}

		// Disable its output as well (which was already loaded)
		$outputPath = "advanced/modules_disable_output/$moduleName";
		if (!Mage::getStoreConfig($outputPath)) {
			Mage::log(sprintf("Inside [%s::%s]\n\r%s: %s\n\r%s: %s", __CLASS__, __METHOD__, 'outputPath', $outputPath, 'class', get_class(Mage::getConfig())), Zend_Log::DEBUG);
			Mage::app()->getStore()->setConfig($outputPath, true);
		}

		return $this;
	}
}
