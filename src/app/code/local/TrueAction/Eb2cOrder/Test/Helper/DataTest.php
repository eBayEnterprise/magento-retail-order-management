<?php
class TrueAction_Eb2cOrder_Test_Helper_DataTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->replaceCoreConfigRegistry(
			array(
				'apiRegion' => 'api_rgn',
				'clientId'  => 'client_id',
			)
		);
		$this->_helper = Mage::helper('eb2corder');
	}

	/**
	 * Accessing constants using our helper and '::' accessor
	 * @test
	 */
	public function testGetConstHelper()
	{
		$consts = $this->_helper->getConstHelper();
		$this->assertSame($consts::CREATE_OPERATION, 'create');
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
		$this->assertStringStartsWith(
			'api_rgn',
			$config->apiRegion
		);
		$this->assertStringStartsWith(
			'client_id',
			$config->clientId
		);
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
		$this->assertStringEndsWith(
			'create.xml',
			$this->_helper->getOperationUri($consts::CREATE_OPERATION)
		);

		$this->assertStringEndsWith(
			'cancel.xml',
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
