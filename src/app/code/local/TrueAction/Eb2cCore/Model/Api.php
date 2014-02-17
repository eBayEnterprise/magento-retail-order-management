<?php
class TrueAction_Eb2cCore_Model_Api
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
	 * @var int the status of the last response.
	 */
	protected $_status = 0;
	/**
	 * Call the API.
	 *
	 * @param DOMDocument $doc The document to send in the request body
	 * @param string $xsdName The basename of the xsd file to validate $doc (The dirname is in config.xml)
	 * @param string $uri The uri to send the request to
	 * @param int $timeout The amount of time in seconds after which the connection is terminated
	 * @param string $adapter The classname of a Zend_Http_Client_Adapter
	 * @param Zend_Http_Client $client
	 * @return string The response from the server
	 */
	public function request(DOMDocument $doc, $xsdName, $uri, $timeout=self::DEFAULT_TIMEOUT, $adapter=self::DEFAULT_ADAPTER, Zend_Http_Client $client=null)
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$this->schemaValidate($doc, $xsdName);
		$xmlStr = $doc->C14N();
		$client = $this->_setupClient($client, $cfg->apiKey, $uri, $xmlStr, $adapter, $timeout);
		$log = Mage::helper('trueaction_magelog');
		try {
			$response = $client->request(self::DEFAULT_METHOD);
		} catch (Zend_Client_Exception $e) {
			$log->logErr('[ %s ] Failed to send request to %s; API client error: %s', array(__CLASS__, $uri, $e));
			$this->_status = 0;
			return '';
		}
		$log->logDebug("[ %s ] Sent request to %s:\n%s", array(__CLASS__, $uri, $xmlStr));
		return $this->_processResponse($response, $uri);
	}
	/**
	 * Instantiate (if necessary) and set up the http client.
	 *
	 * @param Zend_Http_Client $client
	 * @param string $apiKey for authentication
	 * @param string $uri
	 * @param string $data raw data to send
	 * @param string $adapter the classname of a Zend_Http_Client_Adapter
	 * @param int $timeout
	 * @return Zend_Http_Client
	 */
	protected function _setupClient(Zend_Http_Client $client=null, $apiKey, $uri, $data, $adapter, $timeout)
	{
		$client = $client ?: new Varien_Http_Client();
		$client
			->setHeaders('apiKey', $apiKey)
			->setUri($uri)
			->setRawData($data)
			->setEncType('text/xml')
			->setConfig(array(
				'adapter' => $adapter,
				'timeout' => $timeout,
			));
		return $client;
	}
	/**
	 * Handle the response and return the response body.
	 *
	 * @param Zend_Http_Response $response
	 * @param string $uri
	 * @return string response body or empty string
	 */
	protected function _processResponse(Zend_Http_Response $response, $uri)
	{
		$this->_status = $response->getStatus();
		$log = Mage::helper('trueaction_magelog');
		$msgPat = "[%s] Received response from %s with status %s:\n%s";
		$msgArgs = array(__CLASS__, $uri, $this->_status, $response->asString());
		if ($response->isSuccessful()) {
			$log->logDebug($msgPat, $msgArgs);
			return $response->getBody();
		}
		$log->logWarn($msgPat, $msgArgs);
		return '';
	}
	/**
	 * @see self::_status
	 */
	public function getStatus()
	{
		return $this->_status;
	}
	/**
	 * Validates the DOM against a validation schema,
	 * which should be a full path to an .xsd for this DOMDocument.
	 *
	 * @param DomDocument $doc The document to validate
	 * @param string $xsdName xsd file basename with which to validate the doc
	 * @throws TrueAction_Eb2cCore_Exception_InvalidXml when the schema is invalid
	 * @return self
	 */
	public function schemaValidate(DOMDocument $doc, $xsdName)
	{
		$errors = array();
		set_error_handler(function ($errno, $errstr) use (&$errors) {
			$errors[] = "'$errstr' [Errno $errno]";
		});
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$isValid = $doc->schemaValidate(Mage::getBaseDir() . DS . $cfg->apiXsdPath . DS . $xsdName);
		restore_error_handler();
		if (!$isValid) {
			$msg = sprintf(
				"[ %s ] Schema validation failed, encountering the following errors:\n%s",
				__CLASS__,
				implode("\n", $errors)
			);
			throw new TrueAction_Eb2cCore_Exception_InvalidXml($msg);
		}
		return $this;
	}
}
