<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_PricingTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public $keys = array(
		'client_item_id',
		'price',
		'msrp',
		'special_price',
		'special_from_date',
		'special_to_date',
		'price_is_vat_inclusive',
	);

	public static function tearDownAfterClass()
	{
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$write->query('truncate table catalog_product_entity_url_key');
	}

	/**
	 * verify last pricing event is used to update the product
	 * verify the correct pricing information is written to the product
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testProcessFeeds($expectation, $xml, $productId)
	{
		$vfs = $this->getFixture()->getVfs();
		$vfs->apply(array('var' => array('eb2c' => array('foo.txt' => $xml))));
		$this->replaceCoreConfigRegistry(array(
			'clientId' => 'MAGTNA',
			'catalogId' => '45',
			'gsiClientId' => 'MAGTNA',
		));
		$feedModel = $this->getModelMock('eb2ccore/feed', array(
			'lsInboundDir',
			'mvToArchiveDir',
			'fetchFeedsFromRemote',
		));
		$feedModel->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(array($vfs->url('var/eb2c/foo.txt'))));
		$feedModel->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue(true));
		$feedModel->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(true));
		$coreFeedHelper = $this->getHelperMock('eb2ccore/feed');
		$coreFeedHelper->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelper);

		$product = $this->getModelMock('catalog/product', array('addData', 'save'));

		$e = $this->expected($expectation);
		$product->expects($this->atLeastOnce())
			->method('addData')
			->with($this->identicalTo(
				array(
					'price' => $e->getPrice(),
					'special_price' => $e->getSpecialPrice(),
					'special_from_date' => $e->getSpecialFromDate(),
					'special_to_date' => $e->getSpecialToDate(),
					'msrp' => $e->getMsrp(),
					'price_is_vat_inclusive' => $e->getPriceIsVatInclusive()
				)))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array('_getProductBySku'));
		$model->expects($this->once())
			->method('_getProductBySku')
			->with($this->identicalTo($e->getClientItemId()))
			->will($this->returnValue($product));
		$model->setFeedModel($feedModel);
		$model->processFeeds();
	}

	/**
	 * ensure the error branches won't cause an error themselves
	 * @dataProvider dataProvider
	 */
	public function testProcessDomErrors($catalogId, $gsiClientId)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$this->replaceCoreConfigRegistry(array(
			'clientId' => 'MAGTNA',
			'catalogId' => 'catid',
			'gsiClientId' => 'gsiclientid',
		));
		$feedItem = $this->getMock('Varien_Object', array(
			'getCatalogId',
			'getGsiClientId',
		));
		$feedItem->expects($this->once())
			->method('getCatalogId')
			->will($this->returnValue($catalogId));
		$feedItem->expects($this->once())
			->method('getGsiClientId')
			->will($this->returnValue($gsiClientId));

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'getExtractor',
			'extract',
		));
		$model->expects($this->once())
			->method('getExtractor')
			->will($this->returnSelf());
		$model->expects($this->any())
			->method('extract')
			->will($this->returnValue(array($feedItem)));
		$this->_reflectMethod($model, '_processDom')->invoke($model, $doc);		
	}

	/**
	 * verify if product doesnt exist, dummy product is created
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testGetProductBySku($expectation, $productId)
	{
		$this->markTestIncomplete('disabling test due to merged changes');
		$productMock = $this->getModelMock('catalog/product', array(
			'getId',
		));

		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue($productId));
		$productMock->setData(array());

		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue($productId));
		$productMock->setData(array());

		$collection = $this->getResourceModelMockBuilder('catalog/product_collection');
		$collection = $collection->disableOriginalConstructor()
			->setMethods(array(
				'addAttributeToSelect',
				'getSelect',
				'where',
				'load',
				'getFirstItem',
			))
			->getMock();
		$collection->expects($this->any())
			->method('addAttributeToSelect')
			->will($this->returnSelf());
		$collection->expects($this->any())
			->method('getSelect')
			->will($this->returnSelf());
		$collection->expects($this->any())
			->method('where')
			->will($this->returnSelf());
		$collection->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$collection->expects($this->any())
			->method('getFirstItem')
			->will($this->returnValue($productMock));
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'getDefaultAttributeSetId',
			'_getDefaultCategoryIds',
			'getWebsiteIds',
		));
		$model->expects($productId ? $this->never() : $this->once())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(10));
		$model->expects($productId ? $this->never() : $this->once())
			->method('_getDefaultCategoryIds')
			->will($this->returnValue(array(999)));
		$model->expects($productId ? $this->never() : $this->once())
			->method('getWebsiteIds')
			->will($this->returnValue(array(0)));

		$product = $this->_reflectMethod($model, '_getProductBySku')->invoke($model, 'somesku');
		$this->assertSame($productMock, $product);
		$e = $this->expected($expectation);
		$this->assertSame($e->getTypeId(), $product->getTypeId());
		$this->assertSame($e->getSku(), $product->getSku());
		$this->assertSame($e->getName(), $product->getName());
		$this->assertSame($e->getDescription(), $product->getDescription());
		$this->assertSame($e->getShortDescription(), $product->getShortDescription());
		$this->assertSame($e->getWeight(), $product->getWeight());
		$this->assertSame($e->getUrlKey(), $product->getUrlKey());
		$this->assertEquals($e->getWebsiteIds(), $product->getWebsiteIds());
		$this->assertEquals($e->getCategoryIds(), $product->getCategoryIds());
		$this->assertSame($e->getSpecialPrice(), $product->getSpecialPrice());
		$this->assertSame($e->getSpecialFromDate(), $product->getSpecialFromDate());
		$this->assertSame($e->getSpecialToDate(), $product->getSpecialToDate());
		$this->assertSame($e->getPrice(), $product->getPrice());
		$this->assertSame($e->getMsrp(), $product->getMsrp());
		$this->assertSame($e->getTaxClassId(), $product->getTaxClassId());
		$this->assertSame($e->getStatus(), $product->getStatus());
		$this->assertSame($e->getVisibility(), $product->getVisibility());
		$this->assertSame($e->getPriceIsVatInclusive(), $product->getAttributeValue('price_is_vat_inclusive'));
	}

	/**
	 * @large
	 * @loadExpectation
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testProcessFeedsIntegration($expectation)
	{
		$fixtureData = $this->getLocalFixture($expectation);
		$xml = $fixtureData['xml'];

		$vfs = $this->getFixture()->getVfs();
		$vfs->apply(array('var' => array('eb2c' => array('foo.txt' => $xml))));
		$this->replaceCoreConfigRegistry(array(
			'clientId' => 'MAGTNA',
			'catalogId' => '45',
			'gsiClientId' => 'MAGTNA',
		));
		$feedModel = $this->getModelMock('eb2ccore/feed', array(
			'lsInboundDir',
			'mvToArchiveDir',
			'fetchFeedsFromRemote',
		));
		$feedModel->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(array($vfs->url('var/eb2c/foo.txt'))));
		$feedModel->expects($this->any())
			->method('mvToArchiveDir')
			->will($this->returnValue(true));
		$feedModel->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnValue(true));
		$coreFeedHelper = $this->getHelperMock('eb2ccore/feed');
		$coreFeedHelper->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelper);

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'_getDefaultCategoryIds',
			'getWebsiteIds',
		));
		$model->expects($this->any())
			-> method('_getDefaultCategoryIds')
			->will($this->returnValue(array(999)));
		$model->expects($this->any())
			-> method('getWebsiteIds')
			->will($this->returnValue(array(0)));

		$e = $this->expected($expectation);
		// do some precondition checks
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->addAttributeToSelect('*')
			->getSelect()
			->where('e.sku = ?', $e->getSku());
		$product = $products->getFirstItem();
		$this->assertSame($e->getInitialVatFlag(), $product->getPriceIsVatInclusive());

		// run the actual test
		$model->setFeedModel($feedModel);
		$model->processFeeds();

		// check the results
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->addAttributeToSelect('*')
			->getSelect()
			->where('e.sku = ?', $e->getSku());
		$product = $products->getFirstItem();

		$this->assertSame($e->getTypeId(), $product->getTypeId());
		$this->assertSame($e->getSku(), $product->getSku());
		$this->assertSame($e->getName(), $product->getName());
		$this->assertSame($e->getDescription(), $product->getDescription());
		$this->assertSame($e->getShortDescription(), $product->getShortDescription());
		$this->assertSame($e->getWeight(), $product->getWeight());
		$this->assertSame($e->getUrlKey(), $product->getUrlKey());
		$this->assertEquals($e->getWebsiteIds(), $product->getWebsiteIds());
		$this->assertEquals($e->getCategoryIds(), $product->getCategoryIds());
		$this->assertSame($e->getSpecialPrice(), $product->getSpecialPrice());
		$this->assertSame($e->getSpecialFromDate(), $product->getSpecialFromDate());
		$this->assertSame($e->getSpecialToDate(), $product->getSpecialToDate());
		$this->assertSame($e->getPrice(), $product->getPrice());
		$this->assertSame($e->getMsrp(), $product->getMsrp());
		$this->assertSame($e->getTaxClassId(), $product->getTaxClassId());
		$this->assertSame($e->getStatus(), $product->getStatus());
		$this->assertSame($e->getVisibility(), $product->getVisibility());
		$this->assertSame($e->getPriceIsVatInclusive(), $product->getPriceIsVatInclusive());
	}
}
