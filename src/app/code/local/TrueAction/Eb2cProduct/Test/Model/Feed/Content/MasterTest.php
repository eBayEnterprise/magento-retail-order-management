<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Content_MasterTest extends EcomDev_PHPUnit_Test_Case
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
			->will($this->returnValue($vfs->url(self::VFS_ROOT . '/feed_content_master/inbound')));
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

		$feedContentMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->disableOriginalConstructor()
			->setMethods(array('_construct'))
			->getMock();

		$feedContentMasterMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $feedContentMasterMock);

		$master = Mage::getModel('eb2cproduct/feed_content_master');
		$masterReflector = new ReflectionObject($master);

		$loadProductBySku = $masterReflector->getMethod('_loadProductBySku');
		$loadProductBySku->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Product',
			$loadProductBySku->invoke($master, '123')
		);
	}

	/**
	 * testing loadCategoryByName method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testloadCategoryByName()
	{
		$mockModelCatalogProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mockModelCatalogProduct->replaceByMockCatalogModelProduct();
		$mockModelCatalogProduct->replaceByMockCatalogModelProductCollection();

		$feedContentMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->disableOriginalConstructor()
			->setMethods(array('_construct'))
			->getMock();

		$feedContentMasterMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $feedContentMasterMock);

		$master = Mage::getModel('eb2cproduct/feed_content_master');
		$masterReflector = new ReflectionObject($master);

		$loadCategoryByName = $masterReflector->getMethod('_loadCategoryByName');
		$loadCategoryByName->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Category',
			$loadCategoryByName->invoke($master, '123')
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
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithInvalidFeedCatalogId());

		$feedContentMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->disableOriginalConstructor()
			->setMethods(array('hasFsTool'))
			->getMock();

		$feedContentMasterMock->expects($this->any())
			->method('hasFsTool')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $feedContentMasterMock);

		$feedContentExtractorMock = $this->getModelMockBuilder('eb2cproduct/feed_content_extractor')
			->disableOriginalConstructor()
			->setMethods(array('extractContentMasterFeed'))
			->getMock();

		$feedContentExtractorMock->expects($this->any())
			->method('extractContentMasterFeed')
			->will($this->returnValue(new Varien_Object()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_extractor', $feedContentExtractorMock);

		$productFeedModel = Mage::getModel('eb2cproduct/feed_content_master');

		$masterReflector = new ReflectionObject($productFeedModel);
		$constructMethod = $masterReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Content_Master',
			$constructMethod->invoke($productFeedModel)
		);
	}

	/**
	 * testing processFeeds method - with invalid feed catalog id - throw connection exception
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterWithInvalidFeedCatalogId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithInvalidFeedCatalogId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - with invalid feed client id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterWithInvalidFeedClientId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithInvalidFeedClientId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - product with invalid sftp settings
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidSftpSetting()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidProductId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product id and valid category id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidProductIdValidCategoryId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductIdValidCategoryId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - product with valid product id where saving throw Exception
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidProductIdThrowException()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - product with invalid product id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithInvalidProductId()
	{
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_content_master']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$master = Mage::getModel(
			'eb2cproduct/feed_content_master',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_content_master'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$this->assertNull($master->processFeeds());
	}
}
