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

		$product = $this->getModelMock('catalog/product', array(
			'setPrice',
			'setMSRP',
			'setSpecialPrice',
			'setSpecialFromDate',
			'setSpecialToDate',
			'setPriceIsVatInclusive',
			'save',
		));

		$e = $this->expected($expectation);
		$product->expects($this->atLeastOnce())
			->method('setPrice')
			->with($this->identicalTo($e->getPrice()))
			->will($this->returnSelf());
		$product->expects($this->atLeastOnce())
			->method('setMsrp')
			->with($this->identicalTo($e->getMsrp()))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('setSpecialPrice')
			->with($this->identicalTo($e->getSpecialPrice()))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('setSpecialFromDate')
			->with($this->identicalTo($e->getSpecialFromDate()))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('setSpecialToDate')
			->with($this->identicalTo($e->getSpecialToDate()))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('setPriceIsVatInclusive')
			->with($this->identicalTo($e->getPriceIsVatInclusive()))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'_getProductBySku',
			'_clean',
		));
		$model->expects($this->once())
			->method('_getProductBySku')
			->with($this->identicalTo($e->getClientItemId()))
			->will($this->returnValue($product));
		$model->expects($this->any())
			->method('_clean')
			->will($this->returnSelf());
		$model->setFeedModel($feedModel);
		$model->processFeeds();
	}

	/**
	 * verify if product doesnt exit, dummy product is created
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testGetProductBySku($expectation, $productId)
	{
		$product = $this->getModelMock('catalog/product', array(
			'getId',
		));
		$product->expects($this->any())
			->method('getId')
			->will($this->returnValue($productId));

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
			->will($this->returnValue(array($product)));

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'getDefaultAttributeSetId',
			'_getDefaultCategotyIds',
			'getWebsiteIds',
		));
		$collection->expects($this->any())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(10));
		$collection->expects($this->any())
			->method('_getDefaultCategotyIds')
			->will($this->returnValue(array(1, 2, 3)));
		$collection->expects($this->any())
			->method('getWebsiteIds')
			->will($this->returnValue(array(1, 2)));

		$this->_reflectMethod($model, '_loadProductBySku')->invoke($model, 'somesku');
		$e = $this->expected($expectation);
		$this->assertNotNull($itemData->getClientItemId());
	}

	/**
	 * @large
	 * @loadExpectation
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testProcessFeedsIntegration($expectation, $xml)
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

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'_getDefaultCategoryIds',
		));
		$model->expects($this->any())
			-> method('_getDefaultCategoryIds')
			->will($this->returnValue(array(999)));
		$model->setFeedModel($feedModel);
		$model->processFeeds();
		$e = $this->expected($expectation);
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->addAttributeToSelect('*')
			->getSelect()
			->where('e.sku = ?', $e->getSku());
		$product = $products->getFirstItem();
	}
}
