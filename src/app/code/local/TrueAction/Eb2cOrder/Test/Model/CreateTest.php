<?php
/**
 * Test Suite for the Order_Create
 */
class TrueAction_Eb2cOrder_Test_Model_CreateTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const SAMPLE_SUCCESS_XML = <<<SUCCESS_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>Success</ResponseStatus>
</OrderCreateResponse>
SUCCESS_XML;

	const SAMPLE_FAILED_XML = <<<FAILED_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>Failed</ResponseStatus>
</OrderCreateResponse>
FAILED_XML;

	const SAMPLE_INVALID_XML = <<<INVALID_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse>
This is a fine mess ollie.
INVALID_XML;

	/**
	 * Create an Order, with success reponse status
	 * @medium
	 * @test
	 */
	public function testOrderCreateWithSuccessResponseStatus()
	{
		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue( (Object) array(
				'isPaymentEnabled' => true,
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$_SERVER['HTTP_ACCEPT'] = '/';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);

		$this->replaceCoreConfigRegistry();
		$this->replaceModel(
			'eb2ccore/api',
			array(
				'request' => self::SAMPLE_SUCCESS_XML,
			),
			false
		);

		$status = Mage::getModel('eb2corder/create')
			->buildRequest($this->getMockSalesOrder())
			->sendRequest();

		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Create', $status);
	}

	/**
	 * Create an Order, with success reponse status and payment method return paypal express
	 * @medium
	 * @test
	 */
	public function testOrderCreateWithSuccessResponseStatusPaymentMethodIsPaypal()
	{
		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue( (Object) array(
				'isPaymentEnabled' => true,
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$_SERVER['HTTP_ACCEPT'] = '/';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);

		$this->replaceCoreConfigRegistry();
		$this->replaceModel(
			'eb2ccore/api',
			array(
				'request' => self::SAMPLE_SUCCESS_XML,
			),
			false
		);

		$status = Mage::getModel('eb2corder/create')
			->buildRequest($this->getMockSalesOrder2())
			->sendRequest();

		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Create', $status);
	}

	/**
	 * Create an Order, with failed reponse status
	 * @medium
	 * @test
	 */
	public function testOrderCreateWithFailedResponseStatus()
	{
		$_SERVER['HTTP_ACCEPT'] = '/';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);

		$this->replaceCoreConfigRegistry();
		$this->replaceModel(
			'eb2ccore/api',
			array(
				'request' => self::SAMPLE_FAILED_XML,
			),
			false
		);

		$status = Mage::getModel('eb2corder/create')
			->buildRequest($this->getMockSalesOrder())
			->sendRequest();

		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Create', $status);
	}

	/**
	 * Testing when sendRequest will throw Zend_Http_Client_Exception by request method
	 * @test
	 */
	public function testSendRequestWithZendHttpClientException()
	{
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
			->will($this->throwException(new Zend_Http_Client_Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$create = Mage::getModel('eb2corder/create');
		$domRequest = $this->_reflectProperty($create, '_domRequest');
		$domRequest->setValue($create, Mage::helper('eb2ccore')->getNewDomDocument());

		$o = $this->_reflectProperty($create, '_o');
		$o->setValue($create, $this->getMockSalesOrder());

		$create->sendRequest();
	}

	/**
	 * Testing when sendRequest will throw Mage_Core_Exception by request method
	 * @test
	 */
	public function testSendRequestWithMageCoreException()
	{
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
			->will($this->throwException(new Mage_Core_Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$create = Mage::getModel('eb2corder/create');
		$domRequest = $this->_reflectProperty($create, '_domRequest');
		$domRequest->setValue($create, Mage::helper('eb2ccore')->getNewDomDocument());

		$o = $this->_reflectProperty($create, '_o');
		$o->setValue($create, $this->getMockSalesOrder());

		$create->sendRequest();
	}

	/**
	 * Call the observerCreate method, which is meant to be called by a dispatched event
	 * Also covers the eb2c payments not enabled case
	 *
	 * @large
	 * @test
	 */
	public function testObserverCreate()
	{
		$_SERVER['HTTP_ACCEPT'] = '/';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);

		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceModel( 'eb2ccore/api', array('request' => self::SAMPLE_SUCCESS_XML), false );
		$this->replaceCoreConfigRegistry( array('isPaymentEnabled' => false)); // Serves dual purpose, cover payments not enabled case.

		Mage::getModel('eb2corder/create')->observerCreate(
			$this->replaceModel(
				'varien/event_observer',
				array(
					'getEvent' =>
					$this->replaceModel(
						'varien/event',
						array(
							'getOrder' => $this->getMockSalesOrder()
						)
					)
				)
			)
		);
	}

	/**
	 * Test the retryOrderCreate method that will be called by a cron job to retry any
	 * order with the state "new"
	 * @test
	 */
	public function testRetryOrderCreate()
	{
		$newCollection = new Varien_Data_Collection();
		$newCollection->addItem($this->getMockSalesOrder());

		$salesResourceModelOrderMock = $this->getResourceModelMockBuilder('sales/order_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'getSelect', 'where', 'load'))
			->getMock();

		$salesResourceModelOrderMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->equalTo('*'))
			->will($this->returnSelf());
		$salesResourceModelOrderMock->expects($this->once())
			->method('getSelect')
			->will($this->returnSelf());
		$salesResourceModelOrderMock->expects($this->once())
			->method('where')
			->with($this->equalTo("main_table.state = 'new'"))
			->will($this->returnSelf());
		$salesResourceModelOrderMock->expects($this->once())
			->method('load')
			->will($this->returnValue($newCollection));

		$this->replaceByMock('resource_model', 'sales/order_collection', $salesResourceModelOrderMock);

		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('buildRequest', 'sendRequest'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('buildRequest')
			->with($this->isInstanceOf('Mage_Sales_Model_Order'))
			->will($this->returnSelf());
		$createModelMock->expects($this->any())
			->method('sendRequest')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/api', $createModelMock);

		$createModelMock->retryOrderCreate();
	}

	/**
	 * Test that when eb2cpayments is explicitly disabled it sends "prepaid" to OMS
	 * @test
	 */
	public function testPaymentRequestGetSendWithPrepaidCreditCardNodeWithWhenEb2cPaymentIsDisabled()
	{
		// let test the model responsible for building the payment request xml
		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();

		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'isPaymentEnabled' => 0
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$_SERVER['HTTP_ACCEPT'] = '/';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);

		$this->replaceCoreConfigRegistry();
		$this->replaceModel(
			'eb2ccore/api',
			array(
				'request' => self::SAMPLE_SUCCESS_XML,
			),
			false
		);

		$createObject = Mage::getModel('eb2corder/create')->buildRequest($this->getMockSalesOrder());

		$domRequest = $this->_reflectProperty($createObject, '_domRequest');
		// let's test the we have a valid TrueAction_Dom_Document in the class property _domRequest
		$this->assertInstanceOf('TrueAction_Dom_Document', $domRequest->getValue($createObject));

		$xmlRequest = $this->_reflectProperty($createObject, '_xmlRequest');
		// let's test request xml has a PrepaidCreditCard node
		$prepaidContentNode = stristr(stristr(
		$xmlRequest->getValue($createObject), '<PrepaidCreditCard>', false),
		'</PrepaidCreditCard>', true);

		$this->assertNotEmpty($prepaidContentNode);
	}

	/**
	 * Tests for correctly as parsed from Pbridge Credit Card extensions 'additional_information' variable.
	 *
	 * @test
	 */
	public function testPbridgeGetAdditionalInformation()
	{
		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue( (Object) array(
				'isPaymentEnabled' => true,
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(
				array(
					'getSessionId',
				)
			)
			->getMock();
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);

		$this->replaceCoreConfigRegistry();

		$orderCreator = Mage::getModel('eb2corder/create')
			->buildRequest($this->getMockSalesOrder());

		$reflectXmlRequest = $this->_reflectProperty($orderCreator, '_xmlRequest');
		$xmlRequestValue = $reflectXmlRequest->getValue($orderCreator);

		$testDom = new DOMDocument();
		$testDom->loadXML($xmlRequestValue);

		$this->assertStringStartsWith(
				'pb_avsResponseCode',
				$testDom->getElementsByTagName('AVSResponseCode')->item(0)->nodeValue,
				'AVS Response Code was incorrect.'
		);

		$this->assertStringStartsWith(
				'pb_bankAuthorizationCode',
				$testDom->getElementsByTagName('BankAuthorizationCode')->item(0)->nodeValue,
				'BankAuthorizationCode was incorrect.'
		);

		$this->assertStringStartsWith(
				'pb_cvv2ResponseCode',
				$testDom->getElementsByTagName('CVV2ResponseCode')->item(0)->nodeValue,
				'CVV2ResponseCode was incorrect.'
		);

		$this->assertStringStartsWith(
				'pb_responseCode',
				$testDom->getElementsByTagName('ResponseCode')->item(0)->nodeValue,
				'ResponseCode was incorrect.'
		);
	}
}
