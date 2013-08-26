<?php
class TrueAction_Eb2cFraud_Helper_Data extends Mage_Core_Helper_Abstract
{
	const JSC_JS_PATH = 'trueaction_eb2cfraud'; // Relative path where scripts are stored
	const JSC_FIELD_NAME = 'eb2cszyvl'; // Collector field name
	/**
	 * Combined Eb2c_Core and Eb2c_Fraud config model
	 * @var TrueAction_Eb2cCore_Model_Config
	 */
	private $_config;
	/**
	 * Url to our JavaScript
	 * @var string
	 */
	private $_jscUrl;

	/**
	 * Set up _config and _jscUrl.
	 */
	public function __construct()
	{
		$this->_config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2cfraud/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		$this->_jscUrl = Mage::getBaseUrl(
			Mage_Core_Model_Store::URL_TYPE_JS,
			array('_secure'=>true)
		) . self::JSC_JS_PATH;
	}
	/**
	 * @see self::_config
	 */
	public function getConfig()
	{
		return $this->_config;
	}
	/**
	 * @see self::_jscUrl
	 */
	public function getJscUrl()
	{
		return $this->_jscUrl;
	}
}
