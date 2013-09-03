<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cCore_Model_Api extends Mage_Core_Model_Abstract
{
	/**
	 * If _timeout is not set via $this->setApiTimeout() for request(), and the configuration does not contain one,
	 * this our default value.  This value is taken from Zend_Http_Client's default.
	 */
	const DEFAULT_TIMEOUT = 10;

	/**
	 * Default method is post. Cannot be changed.
	 */
	const DEFAULT_METHOD = 'POST';

	/**
	 * Default Adapter may be changed by $this->setAdapter()
	 */
	const DEFAULT_ADAPTER = 'Zend_Http_Client_Adapter_Socket';

	/**
	 * Call the API.
	 *
	 * @param DOMDocument $doc The document to send in the request body
	 * @return string The response from the server.
	 */
	public function request(DOMDocument $doc)
	{
		// setting default factory adapter to use socket just in case curl extension isn't install in the server
		// by default, curl will be used as the default adapter
		$client = $this->getHttpClient();
		$client
			->setHeaders('apiKey', $this->getConfig()->apiKey)
			->setUri($this->getUri())
			->setRawData($doc->saveXML())
			->setEncType('text/xml')
			->setConfig(array(
				'adapter' => $this->getAdapter(),
				'timeout' => $this->getTimeout()
			));
		Mage::log('[' . __CLASS__ . ']: Making API request to ' . $client->getUri(), Zend_Log::DEBUG);
		$response = $client->request(self::DEFAULT_METHOD);
		return $response->isSuccessful() ?
			$response->getBody() :
			'';
	}

	protected function _construct()
	{
		$this->setAdapter(self::DEFAULT_ADAPTER);
		$this->setHttpClient(new Varien_Http_Client());
		$this->setConfig(
			Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'))
		);
		$this->setTimeout($this->getConfig()->apiTimeout);
	}

	/**
	 * Set the http client if you do not wish to use the default.
	 */
	public function setHttpClient(Zend_Http_Client $clientIn)
	{
		return $this->setData('http_client', $clientIn);
	}
}
