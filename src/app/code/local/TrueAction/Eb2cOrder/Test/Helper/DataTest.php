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
	 * Make sure we get back an instance of TrueAction_Eb2cCore_Helper_Data
	 * @test
	 */
	public function testGetCoreHelper()
	{
		$coreHelper = $this->_helper->getCoreHelper();
		$this->assertInstanceOf('TrueAction_Eb2cCore_Helper_Data', $coreHelper);
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
		$this->assertSame($consts::DOM_ROOT_NS, 'http://api.gsicommerce.com/schema/checkout/1.0');
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
		$this->assertSame($config->apiRegion, 'na' );
		$this->assertSame($config->clientId, 'TAN-CLI');
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
			'https://developer-na.gsipartners.com/v1.10/stores/9999/orders/create.xml',
			$this->_helper->getOperationUri($consts::CREATE_OPERATION)
		);

		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/9999/orders/cancel.xml',
			$this->_helper->getOperationUri($consts::CANCEL_OPERATION)
		);
	}
}
