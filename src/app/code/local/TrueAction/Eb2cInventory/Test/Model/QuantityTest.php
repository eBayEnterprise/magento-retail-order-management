<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_QuantityTest
	extends TrueAction_Eb2cCore_Test_Base
{
	protected $_quantity;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_quantity = Mage::getModel('eb2cinventory/quantity');
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
		$apiModelMock = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame(
			0,
			$this->_quantity->requestQuantity($qty, $itemId, $sku)
		);
	}
}
