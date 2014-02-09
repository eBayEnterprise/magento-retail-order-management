<?php
class TrueAction_Eb2cInventory_Test_Model_Request_AbstractTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Data provider for the testMakeRequestForQuote method. Providers the type of
	 * request to make (model alias), operation key, uri config key and whether the
	 * request should be successful or not (whether or not to throw an exception from eb2ccore/api request method)
	 * @return array
	 */
	public function providerMakeRequestForQuote()
	{
		return array(
			array('eb2cinventory/quantity', 'check_quantity', 'xsd_file_quantity', true),
			array('eb2cinventory/details', 'get_inventory_details', 'xsd_file_details', true),
			array('eb2cinventory/quantity', 'check_quantity', 'xsd_file_quantity', false)
		);
	}
	/**
	 * Test making an inventory request for a quote
	 * @param string $requestType    Type of model to use
	 * @param string $operationKey   Helper operation uri key
	 * @param string $xsdConfigKey   Config key for the xsd file
	 * @param bool   $requestSuccess Was the request successful/no exception thrown
	 * @test
	 * @dataProvider providerMakeRequestForQuote
	 */
	public function testMakeRequestForQuote($requestType, $operationKey, $xsdConfigKey, $requestSuccess)
	{
		$quote = $this->getModelMock('sales/quote', array());
		$item = $this->getModelMock('sales/quote_item', array());
		$inventoryHelper = $this->getHelperMockBuilder('eb2cinventory/data')
			->disableOriginalConstructor()
			->setMethods(array('getOperationUri', 'getConfigModel'))
			->getMock();
		$configModel = $this->getModelMock('eb2ccore/config_registry', array('getConfig'));
		$request = $this->getModelMock($requestType, array('_buildRequestMessage', '_canMakeRequestWithQuote'));
		$api = $this->getModelMock('eb2ccore/api', array('request', 'getStatus'));

		$this->replaceByMock('helper', 'eb2cinventory', $inventoryHelper);
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$requestMessage = new DOMDocument();
		$responseMessage = $requestSuccess ? '<TestResponseMessage></TestResponseMessage>' : '';
		$uri = 'http://example.com/operation/uri';
		$xsd = 'MockInventoryDetails.xsd';

		$inventoryHelper
			->expects($this->any())
			->method('getOperationUri')
			->with($this->identicalTo($operationKey))
			->will($this->returnValue($uri));
		$inventoryHelper
			->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($configModel));

		$configModel
			->expects($this->any())
			->method('getConfig')
			->with($this->identicalTo($xsdConfigKey))
			->will($this->returnValue($xsd));

		$request
			->expects($this->once())
			->method('_buildRequestMessage')
			->with($this->identicalTo($quote))
			->will($this->returnValue($requestMessage));
		$request
			->expects($this->once())
			->method('_canMakeRequestWithQuote')
			->with($this->identicalTo($quote))
			->will($this->returnValue(true));
		if ($requestSuccess) {
			$api
				->expects($this->once())
				->method('request')
				->with($this->identicalTo($requestMessage, $xsd, $uri))
				->will($this->returnValue($responseMessage));
			// _handleEmptyRespnose should only be called when API returns an empty response, which doesn't happen here
			$request
				->expects($this->never())
				->method('_handleEmptyResponse');
		} else {
			// cause the API request to fail
			$api
				->expects($this->once())
				->method('request')
				->with($this->identicalTo($requestMessage, $xsd, $uri))
				->will($this->returnValue(''));
			$api
				->expects($this->once())
				->method('getStatus')
				->will($this->returnValue(0));
			// when the request fails, _handleEmptyResponse will trigger, causing an exception to be thrown
			$this->setExpectedException('TrueAction_Eb2cInventory_Exception_Cart');
		}
		$this->assertSame($responseMessage, $request->makeRequestForQuote($quote));
	}
	/**
	 * Test that when an unusable quote is given, no request is made.
	 * @test
	 */
	public function testNoRequestWithBadQuote()
	{
		$quote = $this->getModelMock('sales/quote');
		$request = $this->getModelMock(
			'eb2cinventory/request_abstract',
			array('_canMakeRequestWithQuote', '_buildRequestMessage'),
			true
		);
		$api = $this->getModelMock('eb2ccore/api', array('request'));

		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$request
			->expects($this->once())
			->method('_canMakeRequestWithQuote')
			->with($quote)
			->will($this->returnValue(false));
		// when quote isn't usable, shouldn't attempt to build the request message
		$request
			->expects($this->never())
			->method('_buildRequestMessage');
		// when quote isn't usable, shouldn't make the api request
		$api
			->expects($this->never())
			->method('request');

		// should return empty string when request cannot be made
		$this->assertSame('', $request->makeRequestForQuote($quote));
	}
	/**
	 * When the API returns an empty response, via the API model, the empty response
	 * should be handled by the _handleEmptyResponse method
	 * @test
	 */
	public function testMakeRequestForQuoteWithNoResponse()
	{
		$quote = $this->getModelMock('sales/quote');
		$request = $this->getModelMock(
			'eb2cinventory/request_abstract',
			array('_canMakeRequestWithQuote', '_buildRequestMessage', '_handleEmptyResponse'),
			true
		);
		$api = $this->getModelMock('eb2ccore/api', array('request'));
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$requestDoc = new TrueAction_Dom_Document();

		// boilerplate-ish setup - config for the API model
		$configModel = $this->getModelMock('eb2ccore/config_registry', array('getConfig'));
		$inventoryHelper = $this->getHelperMockBuilder('eb2cinventory/data')
			->disableOriginalConstructor()
			->setMethods(array('getOperationUri', 'getConfigModel'))
			->getMock();
		$this->replaceByMock('helper', 'eb2cinventory', $inventoryHelper);
		$inventoryHelper
			->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('http://api.example.com/operation/uri'));
		$inventoryHelper
			->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($configModel));
		$configModel
			->expects($this->any())
			->method('getConfig')
			->will($this->returnValue('MockInventoryDetails.xsd'));

		$request
			->expects($this->once())
			->method('_buildRequestMessage')
			->with($this->identicalTo($quote))
			->will($this->returnValue($requestDoc));
		$request
			->expects($this->once())
			->method('_canMakeRequestWithQuote')
			->with($this->identicalTo($quote))
			->will($this->returnValue(true));
		// when empty response returned from API, this method should deal with it,
		// throwing the appropriate exception
		$request
			->expects($this->once())
			->method('_handleEmptyResponse')
			->with($this->identicalTo($api))
			->will($this->throwException(new TrueAction_Eb2cInventory_Exception_Cart));
		$api
			->expects($this->once())
			->method('request')
			->will($this->returnValue(''));

		$this->setExpectedException('TrueAction_Eb2cInventory_Exception_Cart');
		$request->makeRequestForQuote($quote);
	}
	/**
	 * Data provider to the testHandlingOfEmptyResponse test. Providers
	 * whether an API model has a status code and if it does, whether that status
	 * code is a blocking status.
	 * @return array Args arrays
	 */
	public function providerHandlingOfEmptyResponses()
	{
		return array(
			// no status on the api model
			array(false, false),
			// non-blocking status on the api model
			array(true, false),
			// blocking status on the api model
			array(true, true),
		);
	}
	/**
	 * Test the handling of empty responses from the API model. When the API model
	 * includes a "blocking" status, a TrueAction_Eb2cInventory_Exception_Cart_Interrupt
	 * exception should be thrown. Otherwise, a TrueAction_Eb2cInventory_Exception_Cart
	 * exception should be thrown.
	 * @param  bool $hasStatus        Does the API model include a status code.
	 * @param  bool $isBlockingStatus Is the API model's status code a "blocking" status
	 * @test
	 * @dataProvider providerHandlingOfEmptyResponses
	 */
	public function testHandlingOfEmptyResponses($hasStatus, $isBlockingStatus)
	{
		$api = $this->getModelMock('eb2ccore/api', array('hasStatus', 'getStatus'));
		$helper = $this->getHelperMock('eb2cinventory/data', array('isBlockingStatus'));
		$this->replaceByMock('helper', 'eb2cinventory', $helper);

		$api
			->expects($this->any())
			->method('hasStatus')
			->will($this->returnValue($hasStatus));
		$api
			->expects($this->any())
			->method('getStatus')
			->will($this->returnValue(123));
		$helper
			->expects($this->any())
			->method('isBlockingStatus')
			->will($this->returnValue($isBlockingStatus));

		if ($hasStatus && $isBlockingStatus) {
			$this->setExpectedException('TrueAction_Eb2cInventory_Exception_Cart_Interrupt');
		} else {
			$this->setExpectedException('TrueAction_Eb2cInventory_Exception_Cart');
		}
		$request = $this->getModelMock('eb2cinventory/request_abstract', array('none'), true);
		$meth = $this->_reflectMethod($request, '_handleEmptyResponse');
		$meth->invoke($request, $api);
	}
}
