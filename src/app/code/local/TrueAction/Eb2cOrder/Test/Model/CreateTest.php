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
	 * Create an Order
	 * @medium
	 * @test
	 */
	public function testOrderCreate()
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
	 * Should throw an exception because an invalid xml response was received
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testInvalidResponseReceived()
	{
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
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
			->will($this->returnValue(self::SAMPLE_INVALID_XML));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);
		//$this->replaceModel('eb2ccore/api', array('request' => self::SAMPLE_INVALID_XML), false );
		Mage::getModel('eb2corder/create')->sendRequest();
	}

	/**
	 * Dispatch eb2c_order_create_fail event
	 * @test
	 */
	public function testFinallyFailed()
	{
		$orderCreateClass = get_class(Mage::getModel('eb2corder/create'));
		$privateFinallyFailedMethod = new ReflectionMethod($orderCreateClass, '_finallyFailed');
		$privateFinallyFailedMethod->setAccessible(true);
		$privateFinallyFailedMethod->invoke(new $orderCreateClass);
		$this->assertEventDispatched('eb2c_order_create_fail');
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
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceModel( 'eb2ccore/api', array('request' => self::SAMPLE_INVALID_XML), false );
		$this->replaceCoreConfigRegistry( array('eb2cPaymentsEnabled' => false)); // Serves dual purpose, cover payments not enabled case.

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
}
