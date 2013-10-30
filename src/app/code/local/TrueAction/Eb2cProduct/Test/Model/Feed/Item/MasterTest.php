<?php
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
		$mockModelCatalogProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mockModelCatalogProduct->replaceByMockCatalogModelProduct();
		$mockModelCatalogProduct->replaceByMockCatalogModelProductCollection();

		$feedItemMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_item_master')
			->disableOriginalConstructor()
			->setMethods(array('_construct'))
			->getMock();

		$feedItemMasterMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'eb2cproduct/feed_item_master', $feedItemMasterMock);

		$master = Mage::getModel('eb2cproduct/feed_item_master');
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
		$mockModelCatalogProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mockModelCatalogProduct->replaceByMockCatalogModelProduct();
		$mockModelCatalogProduct->replaceByMockCatalogModelProductCollection();

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelper();

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$mockCatalogInventoryModelStockItem->replaceByMockCatalogInventoryModelStockItem();

		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$mockCatalogInventoryModelStockStatus->replaceByMockCatalogInventoryModelStockStatus();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithInvalidFeedCatalogId());

		$feedItemMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_item_master')
			->disableOriginalConstructor()
			->setMethods(array('hasFsTool'))
			->getMock();

		$feedItemMasterMock->expects($this->any())
			->method('hasFsTool')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cproduct/feed_item_master', $feedItemMasterMock);

		$feedItemExtractorMock = $this->getModelMockBuilder('eb2cproduct/feed_item_extractor')
			->disableOriginalConstructor()
			->setMethods(array('extract'))
			->getMock();

		$feedItemExtractorMock->expects($this->any())
			->method('extract')
			->will($this->returnValue(new Varien_Object()));

		$this->replaceByMock('model', 'eb2cproduct/feed_item_extractor', $feedItemExtractorMock);

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
	public function testProcessFeedsWithInvalidFeedCatalogId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

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

		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
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
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
	}

	/**
	 * testing processFeeds method - with invalid feed item type
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedItemType()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());
		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
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
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithProductsDelete());

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
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
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithProductsDelete());

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Add' and Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsSimpleProductAddNosaleWithInvalidProductId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithSimpleProductsAddNosale());

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Add' and Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsSimpleProductWithInvalidProductIdThrowException()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithSimpleProductsAddNosale());

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());

		$this->markTestIncomplete('Fix error');
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Add' and Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsSimpleProductAddNosaleWithValidProductId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithSimpleProductsAddNosale());

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());
		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
	}

	/**
	 * testing processFeeds method - bundle product with invalid product id where operation type is 'Add' and Catalog Class 'nosale'
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsSimpleProductWithValidProductIdSetColorDescriptionThrowException()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForItemMasterWithSimpleProductsAddNosale());

		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductIdSetColorDescriptionThrowException();

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

		$this->markTestIncomplete('Improved test required - testing for \'this\'');
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Item_Master',
			$master->processFeeds()
		);
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
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

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

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$master->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$mockEavModelEntityAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Entity_Attribute();
		$master->setEavEntityAttribute($mockEavModelEntityAttribute->buildEavModelEntityAttribute());

		$mockCalogModelProductTypeConfigurableAttribute = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product_Type_Configurable_Attribute();
		$master->setProductTypeConfigurableAttribute($mockCalogModelProductTypeConfigurableAttribute->buildCatalogModelProductTypeConfigurableAttribute());
	}
}
