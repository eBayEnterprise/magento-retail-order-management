<?php
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
	 * Stub dom documents created by the core helper
	 * @param  array $loadResults Results of load calls, assoc array of filename => returnValue|Exception
	 * @return TrueAction_Dom_Document  Stubbed DOM document
	 */
	protected function _domStub($loadResults)
	{
		$dom = $this->getMock('TrueAction_Dom_Document', array('load'));
		$dom->expects($this->any())
			->method('load')
			->will($this->returnCallback(function ($arg) use ($loadResults) {
				if (isset($loadResults[$arg])) {
					if ($loadResults[$arg] instanceof Exception) {
						throw $loadResults[$arg];
					} else {
						return $loadResults[$arg];
					}
				}
				return null;
			}));
		return $dom;
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
	 * Test processing of the feeds, success and failure
	 * @test
	 * @loadFixture testFeedProcessing.yaml
	 */
	public function testFeedProcessing()
	{
		$fileName = 'dummy_file_name.xml';
		$remotePath = 'fake_remote_path';
		$filePattern = 'Oh*My*Glob';

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('fetchFeedsFromRemote', 'lsInboundDir', 'mvToArchiveDir', 'removeFromRemote',))
			->getMock();
		$coreFeed->expects($this->once())
			->method('fetchFeedsFromRemote')
			->with($this->identicalTo($remotePath), $this->identicalTo($filePattern));
		$coreFeed->expects($this->once())
			->method('lsInboundDir')
			->will($this->returnValue(array($fileName)));
		$coreFeed->expects($this->once())
			->method('mvToArchiveDir')
			->with($this->identicalTo($fileName));
		$coreFeed->expects($this->once())
			->method('removeFromRemote')
			->with($this->identicalTo($remotePath, $fileName));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$domStub = $this->_domStub(array($fileName => true,));

		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getNewDomDocument'));
		$coreHelper->expects($this->any())
			->method('getNewDomDocument')
			->will($this->returnValue($domStub));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);

		$mockFs = $this->getMock('Varien_Io_File', array('setAllowCreateFolders', 'open'));
		$mockFs->expects($this->any())
			->method('setAllowCreateFolders')
			->will($this->returnSelf());
		$mockFs->expects($this->any())
			->method('open')
			->will($this->returnSelf());
		$model = Mage::getModel('eb2cinventory/feed_item_inventories', array(
			'feed_config'       => 'dummy_config',
			'feed_event_type'   => 'ItemInventories',
			'feed_file_pattern' => $filePattern,
			'feed_local_path'   => 'inbound',
			'feed_remote_path'  => $remotePath,
			'fs_tool'           => $mockFs,
		));
		// if dom document was loaded successfully, the message should be validated and processed
		$feedHelper = $this->getHelperMock('eb2ccore/feed', array('validateHeader'));
		$feedHelper->expects($this->once())
			->method('validateHeader')
			->with($this->identicalTo($domStub), $this->identicalTo('ItemInventories'))
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		// ensure we trigger the reindexer.
		$indexer = $this->getModelMock('eb2ccore/indexer', array('reindexAll'));
		$indexer->expects($this->once())
			->method('reindexAll');
		$this->replaceByMock('model', 'eb2ccore/indexer', $indexer);

		$model->processFeeds();

		// Make sure the event got fired.
		$this->assertEventDispatched('inventory_feed_processing_complete');
	}
	/**
	 * @test
	 */
	public function testUpdateInventories()
	{
		$fii = $this->getModelMock('eb2cinventory/feed_item_inventories', array('_extractSku', '_updateInventory'));
		$fii
			->expects($this->once())
			->method('_extractSku')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue('123'));
		$fii
			->expects($this->once())
			->method('_updateInventory')
			->with($this->isType('string'), $this->isType('integer'))
			->will($this->returnSelf());
		$sampleFeed = array(new Varien_Object(array(
			'catalog_id' => 'foo',
			'client_item_id' => 'bar',
			'measurements' => new Varien_Object(array('available_quantity' => 3)),
		)));
		$fii->updateInventories($sampleFeed); // Just verify the right inner methods are called.
	}
	/**
	 * @test
	 */
	public function testSetProdQty()
	{
		$stockItem = $this->getModelMock('cataloginventory/stock_item', array('loadByProduct', 'setQty', 'save'));
		$stockItem
			->expects($this->once())
			->method('loadByProduct')
			->with($this->isType('integer'))
			->will($this->returnSelf());
		$stockItem
			->expects($this->once())
			->method('setQty')
			->with($this->isType('integer'))
			->will($this->returnSelf());
		$stockItem
			->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'cataloginventory/stock_item', $stockItem);
		$fii = Mage::getModel('eb2cinventory/feed_item_inventories');
		$ref = new ReflectionObject($fii);
		$setProdQty = $ref->getMethod('_setProdQty');
		$setProdQty->setAccessible(true);
		$setProdQty->invoke($fii, 1, 1); // Just verify the right inner methods are called.
	}
	/**
	 * @test
	 */
	public function testExtractSku()
	{
		$fii = Mage::getModel('eb2cinventory/feed_item_inventories');
		$ref = new ReflectionObject($fii);
		$extractSku = $ref->getMethod('_extractSku');
		$extractSku->setAccessible(true);
		$this->assertSame('foo-bar', $extractSku->invoke($fii, new Varien_Object(array(
			'catalog_id' => 'foo',
			'item_id' => new Varien_Object(array('client_item_id' => 'bar')),
		))));
	}
	/**
	 * @test
	 */
	public function testUpdateInventory()
	{
		$prod = $this->getModelMock('catalog/product', array('getIdBySku'));
		$prod
			->expects($this->once())
			->method('getIdBySku')
			->with($this->isType('string'))
			->will($this->returnValue(123));
		$this->replaceByMock('model', 'catalog/product', $prod);
		$fii = $this->getModelMock('eb2cinventory/feed_item_inventories', array('_setProdQty'));
		$fii
			->expects($this->once())
			->method('_setProdQty')
			->with($this->equalTo(123), $this->equalTo(1))
			->will($this->returnSelf());
		$refFii = new ReflectionObject($fii);
		$updateInventory = $refFii->getMethod('_updateInventory');
		$updateInventory->setAccessible(true);
		$updateInventory->invoke($fii, '45-987', 1); // Just verify the right inner methods are called.
	}
}

