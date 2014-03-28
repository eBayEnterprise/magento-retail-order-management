<?php
class TrueAction_Eb2cProduct_Test_Helper_Map_StockTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test TrueAction_Eb2cProduct_Helper_Map_Stock::_getStockMap method with the following expectations
	 * Expectation 1: when this test invoked this method TrueAction_Eb2cProduct_Helper_Map_Stock::_getStockMap
	 *                will set the class property TrueAction_Eb2cProduct_Helper_Map_Stock::_StockMap with an
	 *                array of eb2c Stock tender type key map to Magento Stock type
	 */
	public function testGetStockMap()
	{
		$mapData = array(
			'stock' => 'Yes',
			'AdvanceOrderOpen' => 'No',
			'AdvanceOrderLimited' => 'Yes',
		);

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfigData'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo(TrueAction_Eb2cProduct_Helper_Map_Stock::STOCK_CONFIG_PATH))
			->will($this->returnValue($mapData));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);

		$stock = Mage::helper('eb2cproduct/map_Stock');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($stock, '_stockMap', array());

		$this->assertSame($mapData, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$stock,
			'_getStockMap',
			array()
		));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Map_Stock::extractStockData method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map_Stock::extractStockData with
	 *                a DOMNodeList object it will extract the SalesClass value
	 *                and then call the mocked method TrueAction_Eb2cProduct_Helper_Map_Stock::_getStockMap method
	 *                which will return an array of keys which is all possible sale class value map to actual magento manage_stock value
	 *                then then method TrueAction_Eb2cProduct_Helper_Data::parseBool will be call given the salesclass value
	 */
	public function testExtractStockData()
	{
		$value = 'stock';
		$mapValue = 'Yes';
		$mapData = array($value => $mapValue);
		$nodes = new DOMNodeList();
		$productId = 5;
		$valueToBool = true;
		$boolToInt = 1;

		$data = array(
			'manage_stock' => $boolToInt,
			'use_config_manage_stock' => false,
			'product_id' => $productId,
			'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
		);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$productMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($productId));

		$itemMock = $this->getModelMockBuilder('cataloginventory/stock_item')
			->disableOriginalConstructor()
			->setMethods(array('loadByProduct', 'addData', 'save'))
			->getMock();
		$itemMock->expects($this->once())
			->method('loadByProduct')
			->with($this->identicalTo($productId))
			->will($this->returnSelf());
		$itemMock->expects($this->once())
			->method('addData')
			->with($this->identicalTo($data))
			->will($this->returnSelf());
		$itemMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'cataloginventory/stock_item', $itemMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodes))
			->will($this->returnValue($value));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$productHelper = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelper->expects($this->once())
			->method('parseBool')
			->with($this->identicalTo($mapValue))
			->will($this->returnValue($valueToBool));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelper);

		$stockHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_stock')
			->disableOriginalConstructor()
			->setMethods(array('_getStockMap', '_boolToInt'))
			->getMock();
		$stockHelperMock->expects($this->once())
			->method('_getStockMap')
			->will($this->returnValue($mapData));
		$stockHelperMock->expects($this->once())
			->method('_boolToInt')
			->with($this->identicalTo($valueToBool))
			->will($this->returnValue($boolToInt));

		$this->assertSame(null, $stockHelperMock->extractStockData($nodes, $productMock));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Map_Stock::_boolToInt method with the following expectations
	 * Expectation 1: This test will invoked the method TrueAction_Eb2cProduct_Helper_Map_Stock::_boolToInt given
	 *                a bool value and expect the return value is an integer value
	 */
	public function testBoolToInt()
	{
		$value = true;
		$rValue = 1;
		$stockHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_Stock')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($rValue, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$stockHelperMock, '_boolToInt', array($value)
		));
	}

}
