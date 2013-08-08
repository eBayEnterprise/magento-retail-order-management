<?php
/**
 * Test Suite for the Order_Create
 */
class TrueAction_Eb2cOrder_Test_Model_CreateTest extends EcomDev_PHPUnit_Test_Case
{
	private $_creator;

	/**
	 * Setup gets a TrueAction_Eb2cOrder_Model_Create, and mocks the send method
	 */
	public function setUp()
	{
		$this->_creator = $this->getMock('TrueAction_Eb2cOrder_Model_Create', array('sendRequest'));
		$this->_creator->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue(true));
	}

	/**
	 * @test
	 * Test factory method returns proper class
	 */
	public function testFactoryMethod()
	{
		$testFactoryCreator = Mage::getModel('eb2corder/create');
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Model_Create', $testFactoryCreator );
	}


	/**
	 * @test
	 * @large
	 * @loadFixture testOrderCreateScenarios.yaml
	 * Get a collection; try creating order for last one
	 */
	public function testOrderCreateFromCollection()
	{
		$status = null;
		$testId = Mage::getModel('sales/order')->getCollection()->getLastItem()->getIncrementId();
		try {
			$this->_creator->buildRequest($testId);
			$status = $this->_creator->sendRequest();
		} catch(Exception $e) {
			echo $e->getMessage();
		}
		$this->assertSame($status, true);
	}

	/**
	 * @test
	 * @loadFixture testOrderCreateScenarios.yaml
	 * One known order is create by increment Id value
	 */
	public function testOrderCreateOneOff()
	{
		$status = null;
		$incrementId = '100000002';
		try {
			$this->_creator->buildRequest($incrementId);
			$status = $this->_creator->sendRequest();
		} catch(Exception $e) {
			echo $e->getMessage();
			$status = false;
		}
		$this->assertSame($status, true);
	}

	/**
	 * @test
	 * @todo expectedException ???
	 * Don't want to find this order, handle exception correctly.
	 */
	public function testOrderNotFound()
	{
		$status = null;
		$incrementId = 'NO_CHANCE';
		$this->_creator->buildRequest($incrementId);
	}

	/**
	 * @test
	 * @loadFixture
	 * This fixture was setup to fail with a syntactically correct URL that couldn't really answer us in any sensible way.
	 */
	public function testWithEb2cPaymentsEnabled()
	{
		$status = null;

		$this->_creator = Mage::getModel('eb2corder/create');
		$incrementId = '100000003';
		try {
			$this->_creator->buildRequest($incrementId);
			$status = $this->_creator->sendRequest();
		}
		catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);
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
	 * @loadFixture testOrderCreateScenarios.yaml
	 */
	public function testObserverCreate()
	{
		$dummyOrder = Mage::getModel('sales/order')->getCollection()->getLastItem();
		$this->_creator = Mage::getModel('eb2corder/create');

		// Now mock up the event
		$mockEvent = $this->getModelMockBuilder('varien/event')
				->disableOriginalConstructor()
				->setMethods(
					array(
						'getOrder',
					)
				)
				->getMock();

		// Make the event return the mock quote:
		$mockEvent->expects($this->any())
				->method('getOrder')
				->will($this->returnValue($dummyOrder));

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

		// TODO: This should be a Mage::dispatchEvent based on config.xml, sell also Eb2cFraud where it's done better.
		// still, it covers the code 'good enough' for now.
		$this->_creator->observerCreate($mockEventObserverArgThingy);
	}
}
