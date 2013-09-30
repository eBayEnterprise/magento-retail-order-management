<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_CatalogInventory_Model_Stock_Status class
	 *
	 * @return Mock_Mage_CatalogInventory_Model_Stock_Status
	 */
	public function buildCatalogInventoryModelStockStatusWithException()
	{
		$catalogInventoryModelStockStatusMock = $this->getModelMockBuilder('cataloginventory/stock_status')
			->disableOriginalConstructor()
			->setMethods(array('rebuild'))
			->getMock();

		$catalogInventoryModelStockStatusMock->expects($this->any())
			->method('rebuild')
			->will($this->throwException(new Exception));

		return $catalogInventoryModelStockStatusMock;
	}

	/**
	 * replacing by mock of the cataloginventory/stock_status model class
	 *
	 * @return void
	 */
	public function replaceByMockCatalogInventoryModelStockStatus()
	{
		$this->replaceByMock('model', 'cataloginventory/stock_status', $this->buildCatalogInventoryModelStockStatusWithException());
	}
}
