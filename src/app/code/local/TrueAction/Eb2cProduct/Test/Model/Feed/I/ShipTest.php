<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_I_ShipTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing loadProductBySku method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testLoadProductBySku()
	{
		$ship = new TrueAction_Eb2cProduct_Model_Feed_I_Ship();
		$shipReflector = new ReflectionObject($ship);

		$loadProductBySku = $shipReflector->getMethod('_loadProductBySku');
		$loadProductBySku->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Product',
			$loadProductBySku->invoke($ship, '123')
		);
	}

	/**
	 * testing _constructor method - this test to test fs tool is set in the constructor when no paramenter pass
	 *
	 * @test
	 */
	public function testConstructor()
	{
		$feedIShipMock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->setMethods(array('hasFsTool'))
			->getMock();

		$feedIShipMock->expects($this->any())
			->method('hasFsTool')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $feedIShipMock);

		$productFeedModel = Mage::getModel('eb2cproduct/feed_i_ship');

		$shipReflector = new ReflectionObject($productFeedModel);
		$constructMethod = $shipReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_I_Ship',
			$constructMethod->invoke($productFeedModel)
		);
	}

	/**
	 * testing processFeeds method - with invalid feed catalog id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedCatalogId()
	{
		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithInvalidFeedCatalogId()); // give a feed with invalid catalog id

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - with invalid feed client id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedClientId()
	{
		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithInvalidFeedClientId()); // give a feed with invalid catalog id

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - with invalid feed item type
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeeditemType()
	{
		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithInvalidFeedItemType()); // give a feed with invalid catalog id

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - when sftp setting is invalid
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidSftpSettings()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperInvalidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$ship->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with invalid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsAddProductWithInvalidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipAddProduct());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with invalid product id where operation type is 'Add' and Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsAddProductNosaleWithInvalidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsAddNosale());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductAddWithValidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipAddProduct());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product invalid product throw an exception where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductAddWithInvalidProductException()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductException();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipAddProduct());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with invalid product id where operation type is 'Change'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductUpdateWithInvalidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsUpdate());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product id where operation type is 'Change'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductUpdateWithValidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsUpdate());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product id where operation type is 'Change' Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductUpdateNosaleWithValidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsUpdateNosale());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product throwing exception where operation type is 'Change'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductUpdateWithValidProductException()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductException();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsUpdate());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product with invalid product id where operation type is 'Delete'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductDeleteWithInvalidProductId()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsDelete());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

	/**
	 * testing processFeeds method - product where delete a product will throw an exception where operation type is 'Delete'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsProductDeleteWhereDeleteThrowException()
	{
		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductException();

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$ship->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForIShipWithProductsDelete());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$ship->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($ship->processFeeds());
	}

}
