<?php
/**
 * Test Suite for the Order_Create
 */
class TrueAction_Eb2cOrder_Test_Model_CreateTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	private $_creator;

	/**
	 * Setup gets a TrueAction_Eb2cOrder_Model_Create, and mocks the send method
	 */
	public function setUp()
	{
		// TODO: This needs to be done better in order to provide sendRequest() coverage
		$this->_creator = $this->replaceModel('eb2corder/create', array('sendRequest'=>true,),false);
	}

	/**
	 * Create the Order
	 * @test
	 * @large
	 * @loadFixture testOrderCreateScenarios.yaml
	 */
	public function testOrderCreate()
	{
		$this->_creator->buildRequest($this->getMockSalesOrder());
		$status = $this->_creator->sendRequest();
		$this->assertSame($status, true);
	}

	/**
	 * Create the Order with eb2c payments disabled in configuration
	 * @test
	 * @large
	 * @loadFixture testWithEb2cPaymentsDisabled.yaml
	 */
	public function testWithEb2cPaymentsDisabled()
	{
		$rc = $this->_creator->buildRequest($this->getMockSalesOrder());
		$this->assertSame($rc, true);
	}


	/**
	 * @test
	 * TODO: Heck knows how this will be fully implemented but at some point under some set of circumstances
	 *	we will have 'finally failed' to create an eb2c order
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
	 * @test
	 * @large
	 * @loadFixture testOrderCreateScenarios.yaml
	 */
	public function testObserverCreate()
	{
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
		$this->_creator->observerCreate($mockEventObserverArgThingy);
	}
}
