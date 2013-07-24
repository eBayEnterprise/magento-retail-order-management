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
	 * Don't want to find this order, handle exception correctly.
	 */
	public function testOrderNotFound()
	{
		$status = null;
		$incrementId = 'NO_CHANCE';
		try {
			$this->_creator->buildRequest($incrementId);
			$status = $this->_creator->sendRequest();
		} catch(Exception $e) {
			$status = false;
		}
		$this->assertSame($status, false);
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
}
