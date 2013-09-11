<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		// FYI: instantiating using regular Mage::getHelper method create
		// a singleton oject which mess with load fixtures for the config
		$this->_helper = new TrueAction_Eb2cInventory_Helper_Data();
	}

	/**
	 * testing getXmlNs method
	 *
	 * @test
	 */
	public function testGetXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			$this->_helper->getXmlNs()
		);
	}

	/**
	 * testing getOperationUri method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/inventory/quantity/get.xml',
			$this->_helper->getOperationUri('check_quantity')
		);

		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/inventory/details/get.xml',
			$this->_helper->getOperationUri('get_inventory_details')
		);

		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/inventory/allocations/create.xml',
			$this->_helper->getOperationUri('allocate_inventory')
		);

		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation')
		);
	}

	public function providerGetRequestId()
	{
		return array(
			array(43)
		);
	}

	/**
	 * testing helper data getRequestId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetRequestId
	 */
	public function testGetRequestId($entityId)
	{
		$this->assertSame(
			'TAN-CLI-ABCD-43',
			$this->_helper->getRequestId($entityId)
		);
	}

	public function providerGetReservationId()
	{
		return array(
			array(43)
		);
	}

	/**
	 * testing helper data getReservationId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetReservationId
	 */
	public function testGetReservationId($entityId)
	{
		$this->assertSame(
			'TAN-CLI-ABCD-43',
			$this->_helper->getReservationId($entityId)
		);
	}

	/**
	 * testing helper data isValidFtpSettings method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testIsValidFtpSettings()
	{
		$this->assertSame(false, $this->_helper->isValidFtpSettings());
	}
}
