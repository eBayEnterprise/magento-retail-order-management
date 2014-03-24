<?php
class TrueAction_Eb2cFraud_Helper_Data extends Mage_Core_Helper_Abstract
{
	// Relative path where scripts are stored
	const JSC_JS_PATH = 'trueaction_eb2cfraud';
	// Form field name that will contain the name of the randomly selected JSC
	// form field. Used to find the generated JSC data in the POST data
	const JSC_FIELD_NAME = 'eb2cszyvl';
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
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		$this->_jscUrl = Mage::getBaseUrl(
			Mage_Core_Model_Store::URL_TYPE_JS,
			array('_secure' => true)
		) . self::JSC_JS_PATH;
	}
	/**
	 * @see _config
	 */
	public function getConfig()
	{
		return $this->_config;
	}
	/**
	 * @see _jscUrl
	 */
	public function getJscUrl()
	{
		return $this->_jscUrl;
	}
	/**
	 * Find the generated JS data from the given request's POST data. This uses
	 * a known form field in the POST data, self::JSC_FIELD_NAME, to find the
	 * form field populated by the JS collector. As the form field populated is
	 * selected at random, this mapping is the only way to find the data
	 * populated by the collector.
	 * @param  Mage_Core_Controller_Request_Http $request
	 * @return string
	 */
	public function getJavaScriptFraudData($request)
	{
		return $request->getPost($request->getPost(static::JSC_FIELD_NAME, ''), '');
	}
}
