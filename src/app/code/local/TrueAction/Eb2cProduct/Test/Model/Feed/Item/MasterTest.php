<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_MasterTest extends EcomDev_PHPUnit_Test_Case
{
	const VFS_ROOT = 'testBase';

	/**
	 * Mock the Varien_Io_File object,
	 * this is our FsTool for testing purposes
	 */
	private function _getMockFsTool($vfs, $sampleFiles)
	{
		$mockFsTool = $this->getMock('Varien_Io_File', array('cd', 'checkAndCreateFolder', 'ls', 'mv', 'pwd', 'setAllowCreateFolders',));
		$mockFsTool->expects($this->any())
			->method('cd')
			->with($this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool->expects($this->any())
			->method('checkAndCreateFolder')
			->with($this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool->expects($this->any())
			->method('mv')
			->with( $this->stringContains($vfs->url(self::VFS_ROOT)), $this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool->expects($this->any())
			->method('ls')
			->will($this->returnValue($sampleFiles));
		$mockFsTool->expects($this->any())
			->method('pwd')
			->will($this->returnValue($vfs->url(self::VFS_ROOT . '/feed_item_master/inbound')));
		$mockFsTool->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());
		// vfs setup ends
		return $mockFsTool;
	}

	/**
	 * testing loadProductBySku method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testLoadProductBySku()
	{
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId());

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId());

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
	 * testing processFeeds method - with invalid feed catalog id - but throw Connection Exception
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedCatalogIdThrowConnectionException()
	{
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelperThrowConnectionException();

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
	 * testing processFeeds method - with invalid feed catalog id - but throw Authentication Exception
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedCatalogIdThrowAuthenticationException()
	{
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelperThrowAuthenticationException();

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
	 * testing processFeeds method - with invalid feed catalog id - but throw Transfer Exception
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedCatalogIdThrowTransferException()
	{
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelperThrowTransferException();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedClientId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedItemType());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperInvalidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAddNosale());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdateNosale());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsUpdate());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsDelete());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithBundleProductsDelete());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWhereDeleteThrowException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedFortItemMasterWithConfigurableProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedFortItemMasterWithConfigurableProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithGroupedProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithGroupedProductsAdd());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_item_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_item_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_item_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();

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
