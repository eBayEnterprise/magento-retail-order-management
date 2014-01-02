<?php
class TrueAction_Eb2cCore_Model_Api extends Varien_Object
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
	 * Placeholder for validation errors for schema validation
	 */
	private $_schemaValidationErrors;

	/**
	 * the status code from the previous request.
	 */

	/**
	 * Call the API.
	 *
	 * @param DOMDocument $doc The document to send in the request body
	 * @return string The response from the server.
	 */
	public function request(DOMDocument $doc)
	{
		if (!$this->hasXsd()) {
			throw new TrueAction_Eb2cCore_Exception('XSD for schema validation has not been set.');
		}

		if (!$this->_schemaValidate($doc)) {
			throw new TrueAction_Eb2cCore_Exception('Schema validation failed.');
		}

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
		Mage::log(sprintf('[ %s ] Making API request to %s', __CLASS__, $client->getUri()), Zend_Log::DEBUG);
		$response = $client->request(self::DEFAULT_METHOD);
		Mage::log(
			sprintf("[ %s ] Sent request to %s:\n%s", __CLASS__, $client->getUri(), $doc->saveXML()),
			Zend_Log::DEBUG
		);
		Mage::log(
			sprintf("[ %s ] Received response from %s:\n%s", __CLASS__, $client->getUri(), $response->asString()),
			Zend_Log::DEBUG
		);
		if ($response->isSuccessful()) {
			return $response->getBody();
		} else {
			$this->setStatus($response->getStatus());
			Mage::log(
				sprintf('[ %s ] Received response from %s with status %s', __CLASS__, $client->getUri(), $response->getStatus()),
				Zend_Log::WARN
			);
			return '';
		}
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

	/**
	 * Returns an XSD path. xsdName is expected to come from a module's configuration.
	 * If it's just a plain old filename, we look for it in the Core Config apiXsdPath.
	 * Otherwise, we just use it as is.
	 *
	 * @return string Path to send to DOMDocument::schemaValidate
	 */
	private function _getXsdPath($xsdName)
	{
		if( basename($xsdName) === $xsdName ) {
			$xsdName = $this->getConfig()->apiXsdPath . DS . $xsdName;
		}
		return($xsdName);
	}

	/**
	 * Used by set_error_handler() to capture array of errors during schema validation
	 *
	 */
	public function schemaValidationErrorHandler($errno , $errstr, $errfile, $errline)
	{
		$this->_schemaValidationErrors[] = "'$errstr' [Errno $errno]";
	}

	/**
	 * Validates the DOM against a magically-set Validation Schema, which should be a full path
	 * to a sensibile .xsd for this DOMDocument.
	 *
	 * @return boolean true valid false otherwise
	 */
	public function schemaValidate(DOMDocument $doc)
	{
		$this->_schemaValidationErrors = array();
		set_error_handler(
			array(
				$this,
				'schemaValidationErrorHandler'
			)
		);
		$validationResult = $doc->schemaValidate($this->_getXsdPath($this->getXsd()));
		restore_error_handler();
		if( !$validationResult ) {
			foreach( $this->_schemaValidationErrors as $error ) {
				Mage::log( '[ ' . __CLASS__ . ' ]' . $error, Zend_Log::ERR );
			}
		}
		return $validationResult;
	}
}
