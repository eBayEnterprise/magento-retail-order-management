<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_MasterTest extends EcomDev_PHPUnit_Test_Case
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
		$master = new TrueAction_Eb2cProduct_Model_Feed_Item_Master();
		$masterReflector = new ReflectionObject($master);

		$loadProductBySku = $masterReflector->getMethod('_loadProductBySku');
		$loadProductBySku->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Product',
			$loadProductBySku->invoke($master, '123')
		);
	}

	/**
	 * testing _constructor method - this test to test fs tool is set in the constructor when no paramenter pass
	 *
	 * @test
	 */
	public function testConstructor()
	{
		$feedItemMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_item_master')
			->setMethods(array('hasFsTool'))
			->getMock();

		$feedItemMasterMock->expects($this->any())
			->method('hasFsTool')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cproduct/feed_item_master', $feedItemMasterMock);

		$productFeedModel = Mage::getModel('eb2cproduct/feed_item_master');

		$masterReflector = new ReflectionObject($productFeedModel);
		$constructMethod = $masterReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
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
		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId()); // give a feed with invalid catalog id

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
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
		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedClientId()); // give a feed with invalid catalog id

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
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
		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedItemType()); // give a feed with invalid catalog id

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
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
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperInvalidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductAddWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Add' and Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductAddNosaleWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAddNosale());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with valid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductAddWithValidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product invalid product throw an exception where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductAddWithInvalidProductException()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Change'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductUpdateWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with valid product id where operation type is 'Change'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductUpdateWithValidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with valid product id where operation type is 'Change' Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductUpdateNosaleWithValidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdateNosale());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with valid product throwing exception where operation type is 'Change'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductUpdateWithValidProductException()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Delete'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductDeleteWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsDelete());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - bundle product where delete a product will throw an exception where operation type is 'Delete'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsBundleProductDeleteWhereDeleteThrowException()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWhereDeleteThrowException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithBundleProductsDelete());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - Configurable product with invalid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsConfigurableProductAddWithValidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedFortItemMasterWithConfigurableProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - configurable product with invalid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsConfigurableProductAddWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedFortItemMasterWithConfigurableProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - grouped product with invalid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsGroupedProductAddWithValidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithGroupedProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - grouped product with invalid product id where operation type is 'Add'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsGroupedProductAddWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForItemMasterWithGroupedProductsAdd());

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->assertNull($master->processFeeds());
	}
}
