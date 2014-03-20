<?php
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_InventoriesTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test fs tool is set in the constructor when no parameter passed.
	 * @test
	 */
	public function testConstructor()
	{
		// mock out some directory config for the inventory feed
		$dirConfig = array('local_directory' => 'some/local', 'event_type' => 'Inventory');
		$cfg = $this->buildCoreConfigRegistry(array('feedDirectoryConfig' => $dirConfig));
		$invHelper = $this->getHelperMockBuilder('eb2cinventory/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$invHelper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($cfg));
		$this->replaceByMock('helper', 'eb2cinventory', $invHelper);

		// set up mock fs tool which should do nothing
		$fsToolMock = $this->getMock('Varien_Io_File');
		// set mock behavior for only the methods needed to get through the core
		// feed model's constructor (which can't be disabled and still allow for
		// meaningful test assertions)
		$fsToolMock->expects($this->any())->method('setAllowCreateFolders')->will($this->returnSelf());
		$fsToolMock->expects($this->any())->method('open')->will($this->returnValue(true));

		$feed = Mage::getModel('eb2cinventory/feed_item_inventories', array('fs_tool' => $fsToolMock));

		// test the setup
		$this->assertInstanceOf('TrueAction_Eb2cInventory_Model_Feed_Item_Extractor', $feed->getExtractor());
		$this->assertInstanceOf('Mage_CatalogInventory_Model_Stock_Item', $feed->getStockItem());
		$this->assertInstanceOf('Mage_CatalogInventory_Model_Stock_Status', $feed->getStockStatus());
		// make sure the core feed model was instantiated with the proper magic data
		$this->assertSame($dirConfig, $feed->getFeedConfig());
	}
	/**
	 * Test processing of the feeds, success and failure
	 * @test
	 */
	public function testFeedProcessing()
	{
		$indexer = $this->getModelMockBuilder('eb2ccore/indexer')->disableOriginalConstructor()->getMock();
		$this->replaceByMock('model', 'eb2ccore/indexer', $indexer);

		$fileDetails = array('local_file' => '/Mage/var/local/file.xml');
		$invFeed = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
			->disableOriginalConstructor()
			->setMethods(array('_getFilesToProcess', 'processFile'))
			->getMock();
		$invFeed->expects($this->once())
			->method('_getFilesToProcess')
			->will($this->returnValue(array($fileDetails)));
		$invFeed->expects($this->once())
			->method('processFile')
			->with($this->identicalTo($fileDetails))
			->will($this->returnSelf());

		$this->assertSame(1, $invFeed->processFeeds());
		// when no files processed, event should not be triggered
		$this->assertEventDispatched('inventory_feed_processing_complete');
	}
	/**
	 * Test processing of the feeds, success and failure
	 * @test
	 */
	public function testFeedProcessingNoFilesToProcess()
	{
		$invFeed = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
			->disableOriginalConstructor()
			->setMethods(array('_getFilesToProcess', 'processFile'))
			->getMock();
		$invFeed->expects($this->once())
			->method('_getFilesToProcess')
			->will($this->returnValue(array()));
		$invFeed->expects($this->never())
			->method('processFile');

		$this->assertSame(0, $invFeed->processFeeds());
		// when no files processed, event should not be triggered
		$this->assertEventNotDispatched('inventory_feed_processing_complete');
	}
	/**
	 * Test processing the DOM for a feed file - should used the instances
	 * extractor to extract feed data and pass it on to updateInventories
	 * @test
	 */
	public function testProcessDom()
	{
		$fileDetails = array('local' => '/Mage/var/processing/file.xml');
		$dom = new TrueAction_Dom_Document();
		$extractedData = array(new Varien_Object(array('some' => 'data')));

		$fii = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
			->disableOriginalConstructor()
			->setMethods(array('updateInventories', 'getExtractor'))
			->getMock();
		$extractor = $this->getModelMockBuilder('eb2cinventory/feed_item_extractor')
			->disableOriginalConstructor()
			->setMethods(array('extractInventoryFeed'))
			->getMock();

		$extractor->expects($this->once())
			->method('extractInventoryFeed')
			->with($this->identicalTo($dom))
			->will($this->returnValue($extractedData));
		$fii->expects($this->once())
			->method('getExtractor')
			->will($this->returnValue($extractor));
		$fii->expects($this->once())
			->method('updateInventories')
			->with($this->identicalTo($extractedData))
			->will($this->returnSelf());

		$this->assertSame($fii, $fii->processDom($dom, $fileDetails));
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
		$id = 23;
		$qty = 42;
		$stockItem = $this->getModelMock('cataloginventory/stock_item', array('loadByProduct', 'setQty', 'setIsInStock', 'save'));
		$stockItem
			->expects($this->once())
			->method('loadByProduct')
			->with($this->identicalTo($id))
			->will($this->returnSelf());
		$stockItem
			->expects($this->once())
			->method('setQty')
			->with($this->identicalTo($qty))
			->will($this->returnSelf());
		$stockItem
			->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'cataloginventory/stock_item', $stockItem);
		$fii = $this->getModelMock('eb2cinventory/feed_item_inventories', array('_updateItemIsInStock'));
		$fii
			->expects($this->once())
			->method('_updateItemIsInStock')
			->with($this->identicalTo($stockItem), $this->identicalTo($qty))
			->will($this->returnSelf());
		$ref = new ReflectionObject($fii);
		$setProdQty = $ref->getMethod('_setProdQty');
		$setProdQty->setAccessible(true);
		$setProdQty->invoke($fii, $id, $qty); // Just verify the right inner methods are called.
	}
	/**
	 * Data provider for testUpdateItemIsInStock, gives quantities where update is greater than,
	 * less than and equal to the min qty to be instock. Also provides whether such quantities
	 * shoudl result in a product that is in or out of stock
	 * @return array Arrays of arguments to be passed to testUpdateItemIsInStock
	 */
	public function providerTestUpdateItemIsInStock()
	{
		return array(
			array(5, 10, 1),
			array(0, 0, 0),
			array(10, 5, 0)
		);
	}
	/**
	 * When the update quantity is greater than the min quantity to be considered in stock,
	 * the stock items should be considered to be in stock
	 * @param  int $minQty    Min qty to be in stock
	 * @param  int $updateQty Qty item is being updated to
	 * @param  int $isInStock Should be considered in stock
	 * @mock Mage_CatalogInventory_Model_Stock_Item::getMinQty return the expected min qty to be in stock
	 * @mock Mage_CatalogInventory_Model_Stock_Item::setIsInStock ensure stock item properly set as in or out of stock
	 * @mock TrueAction_Eb2cInventory_Model_Feed_Item_Inventories mocked to disable constructor, preventing unwanted side-effects & coverage
	 * @test
	 * @dataProvider providerTestUpdateItemIsInStock
	 */
	public function testUpdateItemIsInStock($minQty, $updateQty, $isInStock)
	{
		$stockItem = $this->getModelMock(
			'cataloginventory/stock_item',
			array('getMinQty', 'setIsInStock')
		);
		$stockItem
			->expects($this->any())
			->method('getMinQty')
			->will($this->returnValue($minQty));
		$stockItem
			->expects($this->once())
			->method('setIsInStock')
			->with($this->identicalTo($isInStock))
			->will($this->returnSelf());
		$fii = $this->getModelMockBuilder('eb2cinventory/feed_item_inventories')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$updateMethod = $this->_reflectMethod($fii, '_updateItemIsInStock');
		$this->assertSame($fii, $updateMethod->invoke($fii, $stockItem, $updateQty));
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
