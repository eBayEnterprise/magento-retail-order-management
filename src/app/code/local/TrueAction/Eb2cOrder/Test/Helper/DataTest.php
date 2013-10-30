<?php
class TrueAction_Eb2cOrder_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = new TrueAction_Eb2cOrder_Helper_Data();
	}

	/**
	 * Accessing constants using our helper and '::' accessor
	 * @test
	 */
	public function testGetConstHelper()
	{
		$consts = $this->_helper->getConstHelper();
		$this->assertSame($consts::CREATE_OPERATION, 'create');
		$this->assertSame($consts::CANCEL_OPERATION, 'cancel');
		$this->assertSame($consts::CREATE_DOM_ROOT_NODE_NAME, 'OrderCreateRequest');
		$this->assertSame($consts::CANCEL_DOM_ROOT_NODE_NAME, 'OrderCancelRequest');
	}

	/**
	 * Make sure we get back a TrueAction_Eb2cCore_Model_Config_Registry and that
	 * we can see some sensible values in it.
	 * @test
	 * @loadFixture basicTestConfig.yaml
	 */
	public function testGetConfig()
	{
		$config = $this->_helper->getConfig();
		$this->assertSame(get_class($config), 'TrueAction_Eb2cCore_Model_Config_Registry');
		$this->assertSame($config->apiRegion, 'api_rgn');
		$this->assertSame($config->clientId, 'client_id');
	}

	/**
	 * Testing getOperationUri method with both create and cancel operations
	 *
	 * @test
	 * @loadFixture basicTestConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$consts = $this->_helper->getConstHelper();
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/orders/create.xml',
			$this->_helper->getOperationUri($consts::CREATE_OPERATION)
		);

		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/orders/cancel.xml',
			$this->_helper->getOperationUri($consts::CANCEL_OPERATION)
		);
	}

	/**
	 * Test helper method mapEb2cOrderStatusToMage
	 * @param string $ebcStatus, the eb2c status to get the mapped magento order status
	 * @dataProvider dataProvider
	 * @test
	 */
	public function testMapEb2cOrderStatusToMage($ebcStatus)
	{
		$this->assertSame(
			$this->expected($ebcStatus)->getValue(),
			Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($ebcStatus)
		);
	}
}
