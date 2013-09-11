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
			array('getFile')
		);
		$fileTransferHelperMock->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'filetransfer', $fileTransferHelperMock);
	}

	/**
	 * Mock the Varien_Io_File object,
	 * this is our FsTool for testing purposes
	 */
	private function _getMockFsTool($vfs, $sampleFiles)
	{
		$mockFsTool = $this->getMock('Varien_Io_File', array(
			'cd',
			'checkAndCreateFolder',
			'ls',
			'mv',
			'pwd',
			'setAllowCreateFolders',
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
		$feedItemInventoriesMock = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
			->setMethods(array('hasFsTool'))
			->getMock();

		$feedItemInventoriesMock->expects($this->any())
			->method('hasFsTool')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cinventory/feed_item_inventories', $feedItemInventoriesMock);

		$inventoryFeedModel = Mage::getModel('eb2cinventory/feed_item_inventories');

		$inventoriesReflector = new ReflectionObject($inventoryFeedModel);
		$constructMethod = $inventoriesReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cInventory_Model_Feed_Item_Inventories',
			$constructMethod->invoke($inventoryFeedModel)
		);
	}

	/**
	 * testing processFeeds method - with invalid ftp settings
	 *
	 * @test
	 * @medium
	 * @loadFixture sample-data.yaml
	 */
	public function testProcessFeedsWithInvalidFtpSettings()
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$inventoryFeedModel = Mage::getModel(
			'eb2cinventory/feed_item_inventories',
			array(
				'base_dir' => $vfs->url(self::VFS_ROOT),
				'fs_tool'  => $this->_getMockFsTool($vfs, $sampleFiles)
			)
		);

		$this->_replaceFileTransferHelper();

		// with invalid ftp setting
		$inventoryHelperMock = $this->getHelperMock('eb2ccore/data', array('isValidFtpSettings'));
		$inventoryHelperMock->expects($this->any())
			->method('isValidFtpSettings')
			->will($this->returnValue(false));
		$this->replaceByMock('helper', 'eb2ccore', $inventoryHelperMock);

		$this->assertNull($inventoryFeedModel->processFeeds());

		$vfs->discard();
	}

	/**
	 * testing processFeeds method, with valid ftp settings
	 *
	 * @test
	 * @medium
	 * @loadFixture sample-data.yaml
	 */
	public function testProcessFeedsWithValidFtpSettings()
	{
		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		foreach($vfsDump['root'][self::VFS_ROOT]['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		$inventoryFeedModel = Mage::getModel(
			'eb2cinventory/feed_item_inventories',
			array(
				'base_dir' => $vfs->url(self::VFS_ROOT),
				'fs_tool'  => $this->_getMockFsTool($vfs, $sampleFiles)
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

		// with valid ftp setting
		$inventoryHelperMock = $this->getHelperMock('eb2ccore/data', array('isValidFtpSettings'));
		$inventoryHelperMock->expects($this->any())
			->method('isValidFtpSettings')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore', $inventoryHelperMock);

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
