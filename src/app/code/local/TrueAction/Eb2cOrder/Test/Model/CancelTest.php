<?php
/**
 * Test Suite for the Order_Cancel
 */
class TrueAction_Eb2cOrder_Test_Model_CancelTest extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'testBase';

	/**
	 * Test building order cancel request
	 * @param string $orderType, the order type
	 * @param string $orderId, the order id
	 * @param string $reasonCode, the reason code
	 * @param string $reason, the reason
	 * @dataProvider dataProvider
	 * @loadExpectation
	 * @test
	 */
	public function testBuildRequest($orderType, $orderId, $reasonCode, $reason)
	{
		$request = Mage::getModel('eb2corder/cancel')->buildRequest($orderType, $orderId, $reasonCode, $reason);

		// assert that buildRequest method return itself
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Cancel', $request);

		$domRequest = $this->_reflectProperty($request, '_domRequest');

		// let's assert that the instance of the reflected property is an insteand of TrueAction_Dom_Document
		$this->assertInstanceOf('TrueAction_Dom_Document', $domRequest->getValue($request));

		// let's assert that the content in the reflected property document is what we expect from our expected fixtures
		$this->assertSame(
			$this->expected('request')->getXml(),
			// removing tab, carriage and line breaks from response.
			preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '', $domRequest->getValue($request)->saveXML()))
		);
	}

	/**
	 * Test sending request
	 * @loadExpectation
	 * @loadFixture
	 * @test
	 */
	public function testSendRequest()
	{
		$helperMock = $this->getHelperMockBuilder('eb2corder/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfig', 'getOperationUri'))
			->getMock();
		$helperMock->expects($this->exactly(2))
			->method('getConfig')
			->will($this->returnValue( (Object) array(
				'developerMode' => true,
				'developerCancelUri' => 'https://dev-mode-test.com',
				'serviceOrderTimeout' => null,
				'xsdFileCancel' => 'Order-Service-Cancel-1.0.xsd',
				'apiCancelOperation' => 'cancel',
			)));
		$helperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('https://dev-mode-test.com'));

		$this->replaceByMock('helper', 'eb2corder', $helperMock);

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('setUri', 'setTimeout', 'setXsd', 'request'))
			->getMock();
		$apiModelMock->expects($this->any())
			->method('setUri')
			->with($this->equalTo('https://dev-mode-test.com'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('setTimeout')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('setXsd')
			->with($this->equalTo('Order-Service-Cancel-1.0.xsd'))
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->returnValue(
				file_get_contents($vfs->url(self::VFS_ROOT . '/cancel_response/sample.xml'))
			));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$cancel = Mage::getModel('eb2corder/cancel');

		$domRequest = $this->_reflectProperty($cancel, '_domRequest');
		$domRequest->setValue($cancel, Mage::helper('eb2ccore')->getNewDomDocument());

		$domReponse = $cancel->sendRequest();

		// assert that sendRequest method return itself
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Cancel', $domReponse);

		$domResponse = $this->_reflectProperty($domReponse, '_domResponse');

		// let's assert that the instance of the reflected property is an insteand of TrueAction_Dom_Document
		$this->assertInstanceOf('TrueAction_Dom_Document', $domResponse->getValue($domReponse));

		// let's assert that the content in the reflected property document is what we expect from our expected fixtures
		$this->assertSame(
			$this->expected('response')->getXml(),
			// removing tab, carriage and line breaks from response.
			preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '', $domResponse->getValue($domReponse)->saveXML()))
		);
	}
	/**
	 * Test process reponse method with valid response from eb2c
	 * @loadFixture testSendRequest.yaml
	 * @test
	 */
	public function testProcessResponse()
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		$cancel = Mage::getModel('eb2corder/cancel');
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(file_get_contents($vfs->url(self::VFS_ROOT . '/cancel_response/sample.xml')));

		$domResponse = $this->_reflectProperty($cancel, '_domResponse');
		$domResponse->setValue($cancel, $doc);

		$response = $cancel->processResponse();

		// assert that sendRequest method return itself
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Cancel', $response);
	}

	/**
	 * Test process reponse method with fail response
	 * @loadFixture testProcessResponseFailResponse.yaml
	 * @expectedException TrueAction_Eb2cOrder_Model_Cancel_Exception
	 * @test
	 */
	public function testProcessResponseWithFailedResponse()
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		$cancel = Mage::getModel('eb2corder/cancel');
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(file_get_contents($vfs->url(self::VFS_ROOT . '/cancel_response/sample.xml')));

		$domResponse = $this->_reflectProperty($cancel, '_domResponse');
		$domResponse->setValue($cancel, $doc);

		$response = $cancel->processResponse();

		// assert that sendRequest method return itself
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Cancel', $response);
	}

}
