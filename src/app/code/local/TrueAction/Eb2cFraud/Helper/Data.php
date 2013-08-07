<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2cFraud_Helper_Data extends Mage_Core_Helper_Abstract
{
	const JSC_JS_PATH = 'trueaction_eb2cfraud';

	public $config;
	public $coreHelper;
	private $_jscUrl;	// Path containing our javascript collectors
	private $_jscSet;	// Currently selected JSC set

	/**
	 * Gets a combined configuration model from core and order
	 *
	 * @return
	 */
	public function getConfig()
	{
		if( !$this->config ) {
			$this->config = Mage::getModel('eb2ccore/config_registry')
							->addConfigModel(Mage::getModel('eb2cfraud/config'))
							->addConfigModel(Mage::getModel('eb2ccore/config'));
		}
		return $this->config;
	}

	/**
	 * Return the path where my Javascript Collector Scripts are stored
	 *
	 * @string
	 */
	public function getJscJsPath()
	{
		return self::JSC_JS_PATH;
	}

	/**
	 * Instantiate and save assignment of Core helper
	 *
	 * @return TrueAction_Eb2cCore_Helper
	 */
	public function getCoreHelper()
	{
		if (!$this->coreHelper) {
			$this->coreHelper = Mage::helper('eb2ccore');
		}
		return $this->coreHelper;
	}


	/**
	 * Get url path to our jsc
	 *
	 * @return path
	 */
	public function getJscUrl()
	{
		if( !$this->_jscUrl ) {
			$this->_jscUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, array('_secure'=>true)) . self::JSC_JS_PATH;
		}
		return $this->_jscUrl;
	}
}
