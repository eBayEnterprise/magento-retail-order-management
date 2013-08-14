<?php
/**
 * Test Suite for the Order_Create
 * TODO: Need to mock up getApiModel()->request() to provide 100% sendRequest coverage
 */
class TrueAction_Eb2cOrder_Test_Model_CreateTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	/**
	 * Create an Order
	 * 
	 * @test
	 */
	public function testOrderCreate()
	{
		$this->replaceCoreConfigRegistry();
		$creator = $this->replaceModel('eb2corder/create', array('sendRequest'=>true,),false);
		$creator->buildRequest($this->getMockSalesOrder());
		$status = $creator->sendRequest();
		$this->assertSame($status, true);
	}

	/**
	 * Create the Order with eb2c payments disabled in configuration
	 *
	 * @test
	 */
	public function testWithEb2cPaymentsDisabled()
	{
		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceCoreConfigRegistry(
			array (
				'eb2cPaymentsEnabled' => false,
			)
		);
		$creator = Mage::getModel('eb2corder/create');
		$rc = $creator->buildRequest($this->getMockSalesOrder());
		$this->assertSame(true, $rc);
	}


	/**
	 * TODO: Heck knows how this will be fully implemented but at some point under some set of circumstances
	 *	we will have 'finally failed' to create an eb2c order
	 *
	 * @test
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
