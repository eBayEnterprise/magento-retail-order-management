<?php
class TrueAction_Eb2cCore_Test_Model_ApiTest extends TrueAction_Eb2cCore_Test_Base
{
	public function provideApiCall()
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
		$quantityRequestMessage->createChild(
			'QuantityRequest',
			null,
			array('lineId' => 1, 'itemId' => 'SKU-1234')
		);
		$quantityRequestMessage->createChild(
			'QuantityRequest',
			null,
			array('lineId' => 2, 'itemId' => 'SKU-4321')
		);
		return array(
			array(
				$domDocument,
				'http://example.com/',
				'Inventory-Service-Quantity-1.0.xsd'
			),
		);
	}
	/**
	 * Test that the request method returns a non-empty string on successful response.
	 *
	 * @test
	 */
	public function testRequest()
	{
		$strType = gettype('');
		$request = $this->getMockBuilder('DOMDocument')
			->disableOriginalConstructor()
			->setMethods(array('C14N'))
			->getMock();
		$apiUri = 'http://example.com/';
		$xsdName = 'Inventory-Service-Quantity-1.0.xsd';
		$responseText = 'the response';
		$formattedRequest = 'formatted request';
		$requestText = 'request';
		$httpClient = $this->getMock('Varien_Http_Client', array('setHeaders', 'setUri', 'setConfig', 'setRawData', 'setEncType', 'request'));
		$httpResponse = $this->getMock('Zend_Http_Response', array('getStatus', 'isSuccessful', 'getBody'), array(200, array()));
		$helper = $this->getHelperMock('trueaction_magelog/data', array('logInfo'));
		$api = $this->getModelMock('eb2ccore/api', array('_processResponse', '_processException', 'schemaValidate'));

		$this->replaceByMock('helper', 'trueaction_magelog', $helper);

		$request->expects($this->at(0))
			->method('C14N')
			->will($this->returnValue($requestText));
		$request->expects($this->at(1))
			->method('C14N')
			->will($this->returnValue($formattedRequest));

		$httpClient->expects($this->once())
			->method('setHeaders')
			->with($this->identicalTo('apiKey'), $this->isType($strType))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setUri')
			->with($this->isType($strType))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setConfig')
			->with($this->logicalAnd(
				$this->arrayHasKey('adapter'),
				$this->arrayHasKey('timeout')
			))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setRawData')
			->with($this->isType($strType))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('setEncType')
			->with($this->identicalTo('text/xml'))
			->will($this->returnSelf());
		$httpClient->expects($this->once())
			->method('request')
			->with($this->identicalTo('POST'))
			->will($this->returnValue($httpResponse));

		$helper->expects($this->once())
			->method('logInfo')
			->with($this->identicalTo("[ %s ] Sending request to %s:\n%s"), $this->identicalTo(array('TrueAction_Eb2cCore_Model_Api', $apiUri, $formattedRequest)))
			->will($this->returnSelf());

		$api->expects($this->once())
			->method('_processResponse')
			->with($this->identicalTo($httpResponse), $this->identicalTo($apiUri))
			->will($this->returnValue($responseText));
		$api->expects($this->once())
			->method('schemaValidate')
			->with($this->identicalTo($request), $this->identicalTo($xsdName))
			->will($this->returnSelf());

		$this->assertSame($responseText, $api->request($request, $xsdName, $apiUri, 0, 'foo', $httpClient));
	}
	/**
	 * Test that we can handle a Zend_Http_Client_Exception
	 *
	 * @test
	 * @dataProvider provideApiCall
	 */
	public function testZendHttpClientException($request, $apiUri, $xsdName)
	{
		$configConstraint = new PHPUnit_Framework_Constraint_And();
		$exception = new Zend_Http_Client_Exception;
		$api = $this->getModelMock('eb2ccore/api', array('_setupClient', '_processResponse', '_processException'));
		$httpClient = $this->getMock('Varien_Http_Client', array('setHeaders', 'setUri', 'setConfig', 'setRawData', 'setEncType', 'request'));
		$helper = $this->getHelperMock('trueaction_magelog/data');

		$this->replaceByMock('helper', 'trueaction_magelog', $helper);

		$configConstraint->setConstraints(array(
			$this->arrayHasKey('adapter'),
			$this->arrayHasKey('timeout')
		));

		$httpClient->expects($this->once())
			->method('request')
			->with($this->identicalTo('POST'))
			->will($this->throwException($exception));

		$api->expects($this->once())
			->method('_setupClient')
			->will($this->returnValue($httpClient));
		$api->expects($this->once())
			->method('_processException')
			->with($this->identicalTo($exception), $this->identicalTo($apiUri))
			->will($this->returnValue(''));
		$this->assertSame('', $api->request($request, $xsdName, $apiUri, 0, 'foo', $httpClient));
	}
	/**
	 * Mock out the config helper.
	 */
	protected function _mockConfig()
	{
		$mock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('__get'))
			->getMock();
		$mockConfig = array(
			array('apiEnvironment', 'prod'),
			array('apiMajorVersion', '1'),
			array('apiMinorVersion', '10'),
			array('apiRegion', 'eu'),
			array('apiXsdPath', 'app/code/local/TrueAction/Eb2cCore/xsd'),
			array('storeId', 'store-123'),
		);
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}
	/**
	 * log the response and call the configured method.
	 * @test
	 */
	public function testProcessResponse()
	{
		$statusCode = 200;
		$handlerKey = 'success';
		$uri = 'https://some.url/to/service';
		$responseBody = 'this is some response text';
		$responseText = "some headers\n" . $responseBody;
		$logMethod = 'logDebug';
		$messagPattern = "[%s] Received response from %s with status %s:\n%s";
		$handlerConfig = array('logger' => $logMethod, 'callback' => array('method' => 'foo'));
		$response = $this->getMock('Zend_Http_Response', array('getStatus', 'asString'), array(200, array()));
		$callbackConfigWithArgs = array('method' => 'foo', 'parameters' => array($response));
		$helper = $this->getHelperMock('eb2ccore/data', array('invokeCallback'));
		$logHelper = $this->getHelperMock('trueaction_magelog/data', array($logMethod));
		$api = $this->getModelMock('eb2ccore/api', array('_getHandlerConfig', '_getHandlerKey'));

		$this->replaceByMock('helper', 'trueaction_magelog', $logHelper);
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$api->expects($this->once())
			->method('_getHandlerKey')
			->with($this->identicalTo($response))
			->will($this->returnValue($handlerKey));

		$api->expects($this->once())
			->method('_getHandlerConfig')
			->with($this->identicalTo($handlerKey))
			->will($this->returnValue($handlerConfig));

		$response->expects($this->once())
			->method('getStatus')
			->will($this->returnValue($statusCode));
		$response->expects($this->once())
			->method('asString')
			->will($this->returnValue($responseText));

		$logHelper->expects($this->once())
			->method($logMethod)
			->with($this->identicalTo($messagPattern, array('TrueAction_Eb2cCore_Model_Api', $uri, $statusCode, $responseText)))
			->will($this->returnSelf());

		$helper->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($callbackConfigWithArgs))
			->will($this->returnValue($responseBody));

		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_processResponse', array($response, $uri));
		$this->assertSame($responseBody, $result);
		$this->assertSame($statusCode, $api->getStatus());
	}
	/**
	 * log the response and call the configured method.
	 * @test
	 */
	public function testProcessException()
	{
		$handlerKey = 'no_response';
		$uri = 'https://some.url/to/service';
		$logMethod = 'logWarn';
		$messagPattern = '[ %s ] Failed to send request to %s; API client error: %s';
		$handlerConfig = array('logger' => $logMethod, 'callback' => array('method' => 'foo'));
		$callbackConfig = array('method' => 'foo');
		$exception = new Zend_Http_Client_Exception;
		$helper = $this->getHelperMock('eb2ccore/data', array('invokeCallback'));
		$logHelper = $this->getHelperMock('trueaction_magelog/data', array($logMethod));
		$api = $this->getModelMock('eb2ccore/api', array('_getHandlerConfig', '_getHandlerKey'));

		$this->replaceByMock('helper', 'trueaction_magelog', $logHelper);
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$api->expects($this->once())
			->method('_getHandlerKey')
			->will($this->returnValue($handlerKey));

		$api->expects($this->once())
			->method('_getHandlerConfig')
			->with($this->identicalTo($handlerKey))
			->will($this->returnValue($handlerConfig));

		$logHelper->expects($this->once())
			->method($logMethod)
			->with($this->identicalTo($messagPattern, array('TrueAction_Eb2cCore_Model_Api', $uri, $exception)))
			->will($this->returnSelf());

		$helper->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($callbackConfig))
			->will($this->returnValue('the callback output'));

		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_processException', array($exception, $uri));
		$this->assertSame('the callback output', $result);
		$this->assertSame(0, $api->getStatus());
	}
	/**
	 * get configuration using the value returned from getStatusHandlerConfigPath.
	 * if the path is empty get the default silent config.
	 * otherwise get the default config for the alertLevel defined in the custom config
	 * and merge the two arrays such that the defaults get overwritten by the custom
	 * values.
	 * return the handler config mapped to the supplied status key.
	 * @test
	 */
	public function testGetHandlerConfig()
	{
		$configArray = array('config data');
		$mergedConfig = array('status' => array('success' => array('the success handler')));
		$handlerKey = 'success';
		$configPath = 'eb2ccore/api/status/handler/testhandler';
		$helper = $this->getHelperMock('eb2ccore/data', array('getConfigData'));
		$api = $this->getModelMock('eb2ccore/api', array('getIsFailureLoud', 'getStatusHandlerConfigPath', '_getMergedHandlerConfig'));
		$api->setStatusHandlerPath($configPath);

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($api, '_statusHandlerPath', $configPath);
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$helper->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo($configPath))
			->will($this->returnValue($configArray));
		$api->expects($this->once())
			->method('_getMergedHandlerConfig')
			->with($this->identicalTo($configArray))
			->will($this->returnValue($mergedConfig));

		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getHandlerConfig', array($handlerKey));
		$this->assertSame(array('the success handler'), $result);
	}
	public function provideForTestGetHandlerKey()
	{
		return array(
			array(null, 'no_response'),
			array(0, 'no_response'),
			array(400, 'client_error'),
			array(499, 'client_error'),
			array(500, 'server_error'),
			array(501, 'server_error'),
			array(599, 'server_error'),
		);
	}
	/**
	 * return a string that is a key to the handler config for the class of status codes.
	 * @test
	 * @dataProvider provideFortestGetHandlerKey
	 */
	public function testGetHandlerKey($code, $key)
	{
		$response = $this->getMockBuilder('Zend_Http_Response')
			->disableOriginalConstructor()
			->setMethods(array('isSuccessful', 'isRedirect', 'getStatus'))
			->getMock();
		$api = Mage::getModel('eb2ccore/api');

		$response->expects($this->any())
			->method('isRedirect')
			->will($this->returnValue(false));
		$response->expects($this->any())
			->method('isSuccessful')
			->will($this->returnValue(false));
		$response->expects($this->once())
			->method('getStatus')
			->will($this->returnValue($code));

		$this->assertSame($key, EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getHandlerKey', array($response)));
	}
	public function provideForTestGetHandlerKeySuccess()
	{
		return array(
			array(true, true, 'success'),
			array(true, false, 'success'),
			array(false, true, 'success')
		);
	}
	/**
	 * return a string that is a key to the handler config for the class of status codes.
	 * @test
	 * @dataProvider provideForTestGetHandlerKeySuccess
	 */
	public function testGetHandlerKeySuccess($success, $redirect, $key)
	{
		$response = $this->getMockBuilder('Zend_Http_Response')
			->disableOriginalConstructor()
			->setMethods(array('isSuccessful', 'isRedirect'))
			->getMock();
		$api = Mage::getModel('eb2ccore/api');

		$response->expects($this->any())
			->method('isRedirect')
			->will($this->returnValue($redirect));
		$response->expects($this->any())
			->method('isSuccessful')
			->will($this->returnValue($success));

		$this->assertSame($key, EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getHandlerKey', array($response)));
	}
	/**
	 * by default return the silent config
	 * @test
	 */
	public function testGetMergedHandlerConfigWithEmptyConfig()
	{
		$defaultConfig = array('default config');
		$helper = $this->getHelperMock('eb2ccore/data', array('getConfigData'));
		$api = $this->getModelMock('eb2ccore/api', array('getHandlerConfigPath'));

		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$helper->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo(TrueAction_Eb2cCore_Model_Api::DEFAULT_HANDLER_CONFIG))
			->will($this->returnValue($defaultConfig));

		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getMergedHandlerConfig');
		$this->assertSame(array('default config'), $result);
	}
	/**
	 * return the merged contents of the default (silent) config and the loud config and the passed in array
	 * such that the loud config's values override the silent config's values and the passed config's values
	 * override the loud config's values.
	 * @test
	 */
	public function testGetMergedHandlerConfigWithLoudConfig()
	{
		$config = array('alert_level' => 'loud', 'nested' => array('foo' => 'fie'));
		$loudConfig = array('alert_level' => 'loud', 'nested' => array('foo' => 'fang'));
		$defaultConfig = array('alert_level' => 'silent', 'nested' => array('foo' => 'baz', 'gee' => 'wiz'));
		$helper = $this->getHelperMock('eb2ccore/data', array('getConfigData'));
		$api = Mage::getModel('eb2ccore/api');

		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$helper->expects($this->exactly(2))
			->method('getConfigData')
			->with($this->isType('string'))
			->will($this->returnValueMap(array(
				array(TrueAction_Eb2cCore_Model_Api::DEFAULT_HANDLER_CONFIG, $defaultConfig),
				array(TrueAction_Eb2cCore_Model_Api::DEFAULT_LOUD_HANDLER_CONFIG, $loudConfig),
			)));

		$result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getMergedHandlerConfig', array($config));
		$this->assertSame(array('alert_level' => 'loud', 'nested' => array('foo' => 'fie', 'gee' => 'wiz')), $result);
	}
	/**
	 * set the status handler path
	 * @test
	 */
	public function testSetStatusHandlerPath()
	{
		$api = Mage::getModel('eb2ccore/api');
		$this->assertSame($api, $api->setStatusHandlerPath('some/path'));
		$this->assertSame(
			'some/path',
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($api, '_statusHandlerPath')
		);
	}
}
