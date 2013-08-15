<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2cFraud_Helper_Data extends Mage_Core_Helper_Abstract
{
	const JSC_JS_PATH = 'trueaction_eb2cfraud';

	protected $_config;
	protected $_jscUrl;	// Path containing our JavaScript collectors
	protected $_jscSet;	// Currently selected JSC set

	/**
	 * Gets a combined configuration model from core and order
	 * @return TrueAction_Eb2cCore_Model_Config
	 */
	public function getConfig()
	{
		if(!$this->_config) {
			$this->_config = Mage::getModel('eb2ccore/config_registry')
				->addConfigModel(Mage::getModel('eb2cfraud/config'))
				->addConfigModel(Mage::getModel('eb2ccore/config'));
		}
		return $this->_config;
	}

	/**
	 * Get the path where my JavaScript Collector Scripts are stored.
	 * @return string
	 */
	public function getJscJsPath()
	{
		return self::JSC_JS_PATH;
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
