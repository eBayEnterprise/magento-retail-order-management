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
		$this->assertStringEndsWith(
			'create.xml',
			$this->_helper->getOperationUri('create')
		);

		$this->assertStringEndsWith(
			'cancel.xml',
			$this->_helper->getOperationUri('cancel')
		);
	}

	/**
	 * Test method to map Eb2c Status to Mage State
	 * @test
	 * @loadFixture testMapEb2cStatus.yaml
	 */
	public function testMapEb2cStatus()
	{
		$aKnownEb2cStatus = 'Some Test Value Called Horse';
		$this->assertSame(
			'reined_in',
			Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($aKnownEb2cStatus)
		);

		$anUnknownEb2cStatus = '8edfa9d(*&^(*&^(*Q&#^$*(&Q^#$&*^Q#$fa9d3b2';
		$this->assertSame(
			'new',
			Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($anUnknownEb2cStatus)
		);
	}

	/**
	 * Test getting an order history URL for a given store
	 * @test
	 */
	public function testOrderHistoryUrl()
	{
		$order = Mage::getModel('sales/order', array('store_id' => 1, 'entity_id' => 5));

		$urlMock = $this->getModelMock('core/url', array('getUrl'));
		$urlMock->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo('sales/order/view'), $this->identicalTo(array('_store' => 1, 'order_id' => 5)))
			->will($this->returnValue('http://test.example.com/mocked/order/create'));
		$this->replaceByMock('model', 'core/url', $urlMock);
		$this->assertSame(
			'http://test.example.com/mocked/order/create',
			Mage::helper('eb2corder')->getOrderHistoryUrl($order)
		);
	}
}
