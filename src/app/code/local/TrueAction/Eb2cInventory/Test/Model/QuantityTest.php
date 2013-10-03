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

	public function providerRequestQuantity()
	{
		return array(
			array('qty' => 1, 'itemId' => 1, 'sku' => '1234-TA')
		);
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

	/**
	 * testing Building Quantity Request Message
	 *
	 * @test
	 * @dataProvider providerBuildQuantityRequestMessage
	 */
	public function testBuildQuantityRequestMessage($items)
	{
		$qtyRequestMsg = Mage::helper('eb2ccore')->getNewDomDocument();
		$qtyRequestMsg->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<QuantityRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><QuantityRequest lineId="1" itemId="SKU_TEST_1"/><QuantityRequest lineId="2" itemId="SKU_TEST_2"/><QuantityRequest lineId="3" itemId="SKU_TEST_3"/></QuantityRequestMessage>');
		$this->assertSame(
			$qtyRequestMsg->saveXML(),
			$this->_quantity->buildQuantityRequestMessage($items)->saveXML()
		);
	}

	/**
	 * testing requestQuantity method, when exception is throw from API call
	 *
	 * @test
	 * @dataProvider providerRequestQuantity
	 */
	public function testRequestQuantityRequesetException($qty, $itemId, $sku)
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

	/**
	 * testing requestQuantity method, when exception is throw from API call
	 *
	 * @test
	 * @dataProvider providerRequestQuantity
	 */
	public function testRequestQuantityRequesetAvailableInventory($qty, $itemId, $sku)
	{
		$apiModelMock = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->returnValue('<?xml version="1.0" encoding="UTF-8"?>
<QuantityResponseMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<QuantityResponse itemId="1234-TA" lineId="1">
		<Quantity>1020</Quantity>
	</QuantityResponse>
</QuantityResponseMessage>
			'));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame(
			1020,
			$this->_quantity->requestQuantity($qty, $itemId, $sku)
		);
	}

	/**
	 * Test parsing quantity data from a quantity response.
	 *
	 * @test
	 */
	public function testGetAvailStockFromResponse()
	{
		$responseMessage = '<?xml version="1.0" encoding="UTF-8"?>
<QuantityResponseMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<QuantityResponse itemId="1234-TA" lineId="1">
		<Quantity>1020</Quantity>
	</QuantityResponse>
	<QuantityResponse itemId="4321-TA" lineId="1">
		<Quantity>55</Quantity>
	</QuantityResponse>
</QuantityResponseMessage>';

		$this->assertSame(
			array('1234-TA' => 1020, '4321-TA' => 55),
			$this->_quantity->getAvailableStockFromResponse($responseMessage)
		);
	}

	/**
	 * Empty quantity response should be considered 0 stock
	 *
	 * @test
	 */
	public function testGetAvailStockFromEmptyResponse()
	{
		$responseMessage = ' ';

		$this->assertSame(
			array(),
			$this->_quantity->getAvailableStockFromResponse($responseMessage)
		);
	}

}
