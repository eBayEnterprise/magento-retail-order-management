<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_I_ShipTest extends EcomDev_PHPUnit_Test_Case
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
			->will($this->returnValue($vfs->url(self::VFS_ROOT . '/feed_i_ship/inbound')));
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
		$this->markTestIncomplete();
		$mockModelCatalogProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mockModelCatalogProduct->replaceByMockCatalogModelProduct();
		$mockModelCatalogProduct->replaceByMockCatalogModelProductCollection();

		$feedIShipMock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->disableOriginalConstructor()
			->setMethods(array('_construct'))
			->getMock();

		$feedIShipMock->expects($this->any())
			->method('_construct')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $feedIShipMock);

		$ship = Mage::getModel('eb2cproduct/feed_i_ship');
		$shipReflector = new ReflectionObject($ship);

		$loadProductBySku = $shipReflector->getMethod('_loadProductBySku');
		$loadProductBySku->setAccessible(true);

		$this->assertInstanceOf('Mage_Catalog_Model_Product', $loadProductBySku->invoke($ship, '123'));
	}

	/**
	 * testing _constructor method - this test to test fs tool is set in the constructor when no paramenter pass
	 *
	 * @test
	 */
	public function testConstructor()
	{
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithInvalidFeedCatalogId());

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

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $constructMethod->invoke($productFeedModel));
	}

	/**
	 * testing processFeeds method - with invalid feed catalog id - throw connection exceptions
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidFeedCatalogId()
	{
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithInvalidFeedCatalogId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
	}

	private function _mockEavConfig()
	{
		$eav = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$this->replaceByMock('singleton', 'eav/config', $eav->buildEavModelConfig());
		return $this;
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithInvalidFeedClientId());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithInvalidFeedItemType());

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipAddProduct());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsAddNosale());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipAddProduct());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipAddProduct());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsUpdate());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsUpdate());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsUpdateNosale());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsUpdate());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsDelete());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithInvalidProductId();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
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
		$this->markTestIncomplete();
		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelper();

		$coreFeedModel = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModel->buildEb2cCoreModelFeedForIShipWithProductsDelete());

		$mockIShip = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship();
		$mockIShip->replaceByMockWithValidProductException();

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['feed_i_ship']['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$ship = Mage::getModel(
			'eb2cproduct/feed_i_ship',
			array('base_dir' => $vfs->url(self::VFS_ROOT . '/feed_i_ship'), 'fs_tool' => $this->_getMockFsTool($vfs, $sampleFiles))
		);

		$mockCatalogInventoryModelStockItem = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Item();
		$ship->setStockItem($mockCatalogInventoryModelStockItem->buildCatalogInventoryModelStockItem());

		$this->markTestIncomplete('Just tests that the function runs without raising exceptions. Does not adequately test interaction points.');
		$this
			->_mockEavConfig()
			->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_I_Ship', $ship->processFeeds());
	}
}
