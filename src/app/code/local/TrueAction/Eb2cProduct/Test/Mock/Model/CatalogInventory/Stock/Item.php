<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_CatalogInventory_Model_Stock_Item class
	 *
	 * @return Mock_Mage_CatalogInventory_Model_Stock_Item
	 */
	public function buildCatalogInventoryModelStockItem()
	{
		$catalogInventoryModelStockItemMock = $this->getModelMockBuilder('cataloginventory/stock_item')
			->disableOriginalConstructor()
			->setMethods(array('loadByProduct', 'setUseConfigBackorders', 'setBackorders', 'setProductId', 'setStockId', 'save', 'canSubtractQty'))
			->getMock();

		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('loadByProduct')
			->will($this->returnSelf());
		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('setUseConfigBackorders')
			->will($this->returnSelf());
		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('setBackorders')
			->will($this->returnSelf());
		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('setProductId')
			->will($this->returnSelf());
		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('setStockId')
			->will($this->returnSelf());
		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$catalogInventoryModelStockItemMock->expects($this->any())
			->method('canSubtractQty')
			->will($this->returnValue(false));

		return $catalogInventoryModelStockItemMock;
	}

	/**
	 * replacing by mock of the cataloginventory/stock_status model class
	 *
	 * @return void
	 */
	public function replaceByMockCatalogInventoryModelStockItem()
	{
		$this->replaceByMock('model', 'cataloginventory/stock_item', $this->buildCatalogInventoryModelStockItem());
	}
}
