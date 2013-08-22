<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_InventoriesTest extends EcomDev_PHPUnit_Test_Case
{
	const VFS_ROOT = 'testBase';
	protected $_inventories;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
	}

	/**
	 * testing processFeeds method
	 *
	 * @test
	 * @medium
	 * @loadFixture sample-data.yaml
	 */
	public function testProcessFeeds()
	{

		// Begin vfs Setup:
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		$dummyFiles = array ();
		foreach( $vfsDump['root'][self::VFS_ROOT]['inbound'] as $filename => $contents ) {
			$sampleFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		// Mock the Varien_Io_File object, this is our FsTool for testing purposes
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
			->will($this->returnValue($vfs->url(self::VFS_ROOT.'/inbound')));
		$mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());
		// vfs setup ends

		$inventoryFeedModel = Mage::getModel('eb2cinventory/feed_item_inventories',
			array (
				'base_dir' => $vfs->url(self::VFS_ROOT),
				'fs_tool' => $mockFsTool 
			)
		);
		$inventoriesReflector = new ReflectionObject($inventoryFeedModel);

		$fileTransferHelperMock = $this->getMock(
			'TrueAction_FileTransfer_Helper_Data',
			array('getFile')
		);
		$fileTransferHelperMock->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));


		$inventoryHelperMock = $this->getMock(
			'TrueAction_Eb2cInventory_Helper_Data',
			array('getFileTransferHelper')
		);
		$inventoryHelperMock->expects($this->any())
			->method('getFileTransferHelper')
			->will($this->returnValue($fileTransferHelperMock));

		$helper = $inventoriesReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($inventoryFeedModel, $inventoryHelperMock);

		$this->assertNull(
			$inventoryFeedModel->processFeeds()
		);

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

		$productProperty = $inventoriesReflector->getProperty('_product');
		$productProperty->setAccessible(true);
		$productProperty->setValue($inventoryFeedModel, $productMock);

		$stockItemProperty = $inventoriesReflector->getProperty('_stockItem');
		$stockItemProperty->setAccessible(true);
		$stockItemProperty->setValue($inventoryFeedModel, $stockItemMock);

		$this->assertNull(
			$inventoryFeedModel->processFeeds()
		);
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
		$inventoriesReflector = new ReflectionObject($inventoryFeedModel);
		$stockStatusProperty = $inventoriesReflector->getProperty('_stockStatus');
		$stockStatusProperty->setAccessible(true);
		$stockStatusProperty->setValue($inventoryFeedModel, $stockStatusMock);

		$cleanMethod = $inventoriesReflector->getMethod('_clean');
		$cleanMethod->setAccessible(true);
		$this->assertNull(
			$cleanMethod->invoke($inventoryFeedModel)
		);
	}
}
