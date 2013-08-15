<?php
/**
 * Test Suite for the Order_Create
 * TODO: Need to mock up getApiModel()->request() to provide 100% sendRequest coverage
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
	 * 
	 * @test
	 * @large
	 */
	public function testOrderCreate()
	{
		$this->replaceCoreConfigRegistry();
		$this->replaceModel( 'eb2ccore/api',
			array (
				'request'				=> self::SAMPLE_SUCCESS_XML
			),
			false
		);
		$status = Mage::getModel('eb2corder/create')
					->buildRequest($this->getMockSalesOrder())
					->sendRequest();
		$this->assertSame(true, $status);
	}

	/**
	 * Create the Order with eb2c payments disabled in configuration
	 *
	 * @test
	 * @large
	 */
	public function testWithEb2cPaymentsDisabled()
	{
		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceCoreConfigRegistry(
			array (
				'eb2cPaymentsEnabled' => false,
			)
		);

		$this->replaceModel( 'eb2ccore/api',
			array (
				'request'				=> self::SAMPLE_FAILED_XML
			),
			false
		);

		$status = Mage::getModel('eb2corder/create')
					->buildRequest($this->getMockSalesOrder())
					->sendRequest();
		$this->assertSame(false, $status);
	}

	/**
	 * Should throw an exception because an invalid xml response was received
	 * @test
	 * @large
	 * @expectedException Mage_Core_Exception
	 */
	public function testInvalidResponseReceived()
	{
		$this->replaceModel( 'eb2ccore/api',
			array (
				'request'				=> self::SAMPLE_INVALID_XML
			),
			false
		);

		Mage::getModel('eb2corder/create')
			->buildRequest($this->getMockSalesOrder())
			->sendRequest();
	}


	/**
	 * TODO: Heck knows how this will be fully implemented but at some point under some set of circumstances
	 *	we will have 'finally failed' to create an eb2c order
	 *
	 * @test
	 * @large
	 */
	public function testFinallyFailed()
	{
		$orderCreateClass = get_class(Mage::getModel('eb2corder/create'));
		$privateFinallyFailedMethod = new ReflectionMethod($orderCreateClass,'_finallyFailed');
		$privateFinallyFailedMethod->setAccessible(true);
		$privateFinallyFailedMethod->invoke(new $orderCreateClass);
		$this->assertEventDispatched('eb2c_order_create_fail');
	}


	/**
	 * Call the observerCreate method, which is meant to be called by a dispatched event
	 * 
	 * @test
	 * @large
	 */
	public function testObserverCreate()
	{
		$creator = Mage::getModel('eb2corder/create');
		// Now mock up the event
		$mockEvent = $this->getModelMockBuilder('varien/event')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getOrder',
					)
				)
				->getMock();

		// Make the event return the mock order:
		$mockEvent->expects($this->any())
				->method('getOrder')
				->will($this->returnValue($this->getMockSalesOrder()));

		// Now make the fake observer arg return the fake event ... confusingly, this arg is called "an event observer"
		//	thus an event observer is called with an event observer
		$mockEventObserverArgThingy = $this->getModelMockBuilder('varien/event_observer')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getEvent',
					)
				)
				->getMock();

		// Finally set up event observer to return our fakey event
		$mockEventObserverArgThingy->expects($this->any())
				->method('getEvent')
				->will($this->returnValue($mockEvent));

		// TODO: This should be a Mage::dispatchEvent based on config.xml, see also Eb2cFraud where it's done better.
		// still, it covers the code 'good enough' for now.
		$creator->observerCreate($mockEventObserverArgThingy);
	}
}
