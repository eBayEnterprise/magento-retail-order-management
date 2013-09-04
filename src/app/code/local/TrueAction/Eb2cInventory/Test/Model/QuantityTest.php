<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_QuantityTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_quantity;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_quantity = Mage::getModel('eb2cinventory/quantity');
		$this->_quantity->setHelper(new TrueAction_Eb2cInventory_Helper_Data());
	}

	public function providerBuildQuantityRequestMessage()
	{
		return array(
			array(
				array(
					array('id' => 1, 'sku' => 'SKU_TEST_1'),
					array('id' => 2, 'sku' => 'SKU_TEST_2'),
					array('id' => 3, 'sku' => 'SKU_TEST_3')
				)
			)
		);
	}

	public function expectedBuildQuantityRequestMessage()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
			preg_replace('/[\t\n\r]/', '', '<QuantityRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
				<QuantityRequest lineId="1" itemId="SKU_TEST_1"/><QuantityRequest lineId="2" itemId="SKU_TEST_2"/>
				<QuantityRequest lineId="3" itemId="SKU_TEST_3"/></QuantityRequestMessage>'
			);
	}

	/**
	 * testing Building Quantity Request Message
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerBuildQuantityRequestMessage
	 */
	public function testBuildQuantityRequestMessage($items)
	{
		$this->assertSame(
			trim($this->expectedBuildQuantityRequestMessage()),
			trim($this->_quantity->buildQuantityRequestMessage($items)->saveXML())
		);
	}

	public function providerRequestQuantity()
	{
		return array(
			array('qty' => 1, 'itemId' => 100, 'sku' => '1234-TA')
		);
	}

	/**
	 * testing requestQuantity method, when exception is throw from API call
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerRequestQuantity
	 */
	public function testRequestQuantity($qty=0, $itemId, $sku)
	{
		$apiModelMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Api',
			array('setUri', 'request')
		);
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception));

		$inventoryHelper = Mage::helper('eb2cinventory');
		$inventoryReflector = new ReflectionObject($inventoryHelper);
		$apiModel = $inventoryReflector->getProperty('apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($inventoryHelper, $apiModelMock);

		$this->_quantity->setHelper($inventoryHelper);

		$this->assertSame(
			0,
			$this->_quantity->requestQuantity($qty, $itemId, $sku)
		);
	}
}
