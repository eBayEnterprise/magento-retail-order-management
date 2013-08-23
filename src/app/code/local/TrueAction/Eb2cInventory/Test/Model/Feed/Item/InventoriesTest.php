<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_InventoriesTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing processFeeds method - with valid product id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithValidProductId()
	{
		$inventories = Mage::getModel('eb2cinventory/feed_item_inventories');

		$mockHelperObject = new TrueAction_Eb2cInventory_Test_Mock_Helper_Data();
		$inventories->setHelper($mockHelperObject->buildEb2cInventoryHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cInventory_Test_Mock_Model_Core_Feed();
		$inventories->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$mockCatalogModelProduct = new TrueAction_Eb2cInventory_Test_Mock_Model_Catalog_Product();
		$inventories->setProduct($mockCatalogModelProduct->buildCatalogModelProductWithValidProductId());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Item();
		$inventories->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Status();
		$inventories->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($inventories->processFeeds());
	}

	/**
	 * testing processFeeds method - with invalid product id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidProductId()
	{
		$inventories = Mage::getModel('eb2cinventory/feed_item_inventories');

		$mockHelperObject = new TrueAction_Eb2cInventory_Test_Mock_Helper_Data();
		$inventories->setHelper($mockHelperObject->buildEb2cInventoryHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cInventory_Test_Mock_Model_Core_Feed();
		$inventories->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$mockCatalogModelProduct = new TrueAction_Eb2cInventory_Test_Mock_Model_Catalog_Product();
		$inventories->setProduct($mockCatalogModelProduct->buildCatalogModelProductWithInvalidProductId());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Item();
		$inventories->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Status();
		$inventories->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($inventories->processFeeds());
	}

	/**
	 * testing processFeeds method - with invalid ftp settings
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFtpSetting()
	{
		$inventories = Mage::getModel('eb2cinventory/feed_item_inventories');

		$mockHelperObject = new TrueAction_Eb2cInventory_Test_Mock_Helper_Data();
		$inventories->setHelper($mockHelperObject->buildEb2cInventoryHelperWithInvalidFtpSettings());

		$mockCoreModelFeed = new TrueAction_Eb2cInventory_Test_Mock_Model_Core_Feed();
		$inventories->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$mockCatalogModelProduct = new TrueAction_Eb2cInventory_Test_Mock_Model_Catalog_Product();
		$inventories->setProduct($mockCatalogModelProduct->buildCatalogModelProductWithValidProductId());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Item();
		$inventories->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cInventory_Test_Mock_Model_CatalogInventory_Stock_Status();
		$inventories->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($inventories->processFeeds());
	}
}
