<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_InventoriesTest extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'testBase';

	/**
	 * Replace the FileTransfer Helper with a mock object.
	 */
	private function _replaceFileTransferHelper()
	{
		$fileTransferHelperMock = $this->getMock(
			'TrueAction_FileTransfer_Helper_Data',
			array('getAllFiles')
		);
		$fileTransferHelperMock->expects($this->any())
			->method('getAllFiles')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'filetransfer', $fileTransferHelperMock);
	}

	/**
	 * Mock the Varien_Io_File object,
	 * this is our FsTool for testing purposes
	 */
	private function _getMockFsTool($vfs)
	{
		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		$sampleFiles = array();
		foreach($vfsDump['root'][self::VFS_ROOT]['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$mockFsTool = $this->getMock('Varien_Io_File', array(
			'cd',
			'checkAndCreateFolder',
			'ls',
			'mv',
			'pwd',
			'setAllowCreateFolders',
			'open',
		));
		$mockFsTool
			->expects($this->any())
			->method('cd')
			->with($this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('checkAndCreateFolder')
			->with($this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('mv')
			->with( $this->stringContains($vfs->url(self::VFS_ROOT)), $this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('ls')
			->will($this->returnValue($sampleFiles));
		$mockFsTool
			->expects($this->any())
			->method('pwd')
			->will($this->returnValue($vfs->url(self::VFS_ROOT . '/inbound')));
		$mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());
		$mockFsTool
			->expects($this->any())
			->method('open')
			->will($this->returnValue(true));

		// vfs setup ends
		return $mockFsTool;
	}

	/**
	 * testing _constructor method - this test to test fs tool is set in the constructor when no paramenter pass
	 *
	 * @test
	 * @medium
	 * @loadFixture sample-data.yaml
	 */
	public function testConstructor()
	{
		// Setup a simple, but fairly useless stub of Varien_Io_File. I'm leaving testing of the interactions
		// between Varien_Io_File and the Eb2cCore_Model_Feed up to Eb2cCore_Model_Feed so just looking to ensure
		// no filesystem interactions take place.
		$fsToolMock = $this->getMock('Varien_Io_File', array(
			'cd', 'checkAndCreateFOlder', 'ls', 'mv', 'pwd', 'setAllowCreateFolders', 'open',
		));
		$fsToolMock->expects($this->any())
			->method('cd')
			->will($this->returnValue(true));
		$fsToolMock->expects($this->any())
			->method('checkAndCreateFOlder')
			->will($this->returnValue(true));
		$fsToolMock->expects($this->any())
			->method('ls')
			->will($this->returnValue(array()));
		$fsToolMock->expects($this->any())
			->method('mv')
			->will($this->returnValue(true));
		$fsToolMock->expects($this->any())
			->method('pwd')
			->will($this->returnValue(''));
		$fsToolMock->expects($this->any())
			->method('setAllowCreateFolders')
			->will($this->returnSelf());
		$fsToolMock->expects($this->any())
			->method('open')
			->will($this->returnValue(true));

		$feed = Mage::getModel('eb2cinventory/feed_item_inventories', array('fs_tool' => $fsToolMock));

		// test the setup
		// when pulled from config, the base dir should be Mage::getBaseDir('var') followed by the configured
		// local dir, in this case, set in the fixture
		$this->assertSame(Mage::getBaseDir('var') . DS . 'TrueAction/Eb2c/Feed/Item/Inventories/', $feed->getBaseDir());
		$this->assertInstanceOf('TrueAction_Eb2cInventory_Model_Feed_Item_Extractor', $feed->getExtractor());
		$this->assertInstanceOf('Mage_CatalogInventory_Model_Stock_Item', $feed->getStockItem());
		$this->assertInstanceOf('Mage_Catalog_Model_Product', $feed->getProduct());
		$this->assertInstanceOf('Mage_CatalogInventory_Model_Stock_Status', $feed->getStockStatus());
		$this->assertInstanceOf('TrueAction_Eb2cCore_Model_Feed', $feed->getFeedModel());
		// make sure the core feed model was instantiated with the proper magic data
		$this->assertSame($feed->getBaseDir(), $feed->getFeedModel()->getBaseDir());
		$this->assertSame($fsToolMock, $feed->getFeedModel()->getFsTool());
	}

	/**
	 * testing processFeeds method - with invalid ftp settings
	 *
	 * @test
	 * @medium
	 * @loadFixture sample-data.yaml
	 */
	public function testProcessFeedsNoProduct()
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		$inventoryFeedModel = Mage::getModel(
			'eb2cinventory/feed_item_inventories',
			array(
				'base_dir' => $vfs->url(self::VFS_ROOT),
				'fs_tool'  => $this->_getMockFsTool($vfs)
			)
		);

		$this->_replaceFileTransferHelper();

		// test with mock product and stock item
		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('loadByAttribute', 'getId')
		);
		$productMock->expects($this->any())
			->method('loadByAttribute')
			->will($this->returnValue(true));
		$productMock->expects($this->any())
			->method('getId');

		$inventoryFeedModel->setProduct($productMock);

		$this->assertNull($inventoryFeedModel->processFeeds());

		$vfs->discard();
	}

	/**
	 * testing processFeeds method, with valid ftp settings - throw connection exceptions
	 *
	 * @test
	 * @medium
	 * @loadFixture sample-data.yaml
	 */
	public function testProcessFeeds()
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		$inventoryFeedModel = Mage::getModel(
			'eb2cinventory/feed_item_inventories',
			array(
				'base_dir' => $vfs->url(self::VFS_ROOT),
				'fs_tool'  => $this->_getMockFsTool($vfs)
			)
		);

		$this->_replaceFileTransferHelper();

		// test with mock product and stock item
		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('loadByAttribute', 'getId')
		);
		$productMock->expects($this->any())
			->method('loadByAttribute')
			->will($this->returnValue(true));
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$stockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('loadByProduct', 'setQty', 'save')
		);
		$stockItemMock->expects($this->any())
			->method('loadByProduct')
			->will($this->returnSelf());
		$stockItemMock->expects($this->any())
			->method('setQty')
			->will($this->returnSelf());
		$stockItemMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		$inventoryFeedModel->setProduct($productMock);
		$inventoryFeedModel->setStockItem($stockItemMock);

		$this->assertNull($inventoryFeedModel->processFeeds());

		$vfs->discard();
	}

	/**
	 * testing _clean method, to cover exception catch section
	 *
	 * @test
	 */
	public function testCleanWithException()
	{
		$stockStatusMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Status',
			array('rebuild')
		);
		$stockStatusMock->expects($this->any())
			->method('rebuild')
			->will($this->throwException(new Exception));

		$inventoryFeedModel = Mage::getModel('eb2cinventory/feed_item_inventories');
		$inventoryFeedModel->setStockStatus($stockStatusMock);
		$inventoriesReflector = new ReflectionObject($inventoryFeedModel);
		$cleanMethod = $inventoriesReflector->getMethod('_clean');
		$cleanMethod->setAccessible(true);
		$this->assertNull(
			$cleanMethod->invoke($inventoryFeedModel)
		);
	}
}
