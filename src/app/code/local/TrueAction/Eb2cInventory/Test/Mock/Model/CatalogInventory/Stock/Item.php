<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Item extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_CatalogInventory_Model_Stock_Item class
	 *
	 * @return Mock_Mage_CatalogInventory_Model_Stock_Item
	 */
	public function buildCatalogInventoryModelStockItem()
	{
		$catalogInventoryModelStockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('loadByProduct', 'setUseConfigBackorders', 'setBackorders', 'setProductId', 'setStockId', 'save')
		);

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

		return $catalogInventoryModelStockItemMock;
	}
}
