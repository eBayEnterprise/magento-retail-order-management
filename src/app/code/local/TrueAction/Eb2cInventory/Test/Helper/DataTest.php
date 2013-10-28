<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Helper_DataTest extends TrueAction_Eb2cCore_Test_Base
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
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/inventory/quantity/get.xml',
			$this->_helper->getOperationUri('check_quantity')
		);

		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/inventory/details/get.xml',
			$this->_helper->getOperationUri('get_inventory_details')
		);

		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/inventory/allocations/create.xml',
			$this->_helper->getOperationUri('allocate_inventory')
		);

		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation')
		);
	}

	/**
	 * testing getOperationUri method with a store other than the default
	 *
	 * @test
	 * @loadFixture
	 */
	public function testGetOperationUriNonDefaultStore()
	{
		$this->assertSame('store_id2', Mage::getStoreConfig('eb2ccore/general/store_id', 'canada'), 'storeid for canada not retrieved');
		// check to make sure that if the current store has another value for store id,
		// the store level value is chosen over the default.
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id2/inventory/allocations/delete.xml',
			$this->_helper->getOperationUri('rollback_allocation', 'canada')
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
			'client_id-store_id-43',
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
			'client_id-store_id-43',
			$this->_helper->getReservationId($entityId)
		);
	}
}
