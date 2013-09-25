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
	 * verify if product doesnt exit, dummy product is created
	 * verify product prices are set.
	 * verify last pricing event is used to update the product
	 * verify item added to queue
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
	 * verify product prices are set.
	 * verify last pricing event is used to update the product
	 * verify item added to queue
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	/*

	public function testProcessDom($expectation, $xml)
	{
		$this->markTestSkipped();
		$vfs = $this->getFixture()->getVfs();
		$vfs->apply(array('var' => array('eb2c' => array('foo.txt' => $xml))));
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
			'_getProductBySku',
			'_clean',
			'_processDom',
			'_processQueue',
		));
		$model->expects($this->once())
			->method('_processDom')
			->will($this->returnSelf());
		$model->expects($this->once())
			->method('_processQueue')
			->will($this->returnSelf());
		$model->expects($this->any())
			->method('_clean')
			->will($this->returnSelf());
		$model->setFeedModel($feedModel);
		$model->processFeeds();
	}
	*/
	/**
	 * verify the correct pricing information is written to the product
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testProcessItem($expectation, $data, $productId)
	{
		$this->markTestSkipped();
		$product = $this->getModelMock('catalog/product', array(
			'setBasePrice',
			'setMSRP',
			'setSpecialPrice',
			'setSpecialFromDate',
			'setSpecialToDate',
			'setPriceIsVatInclusive',
			'save',
			'getId',
		));

		$e = $this->expected($expectation);
		$product->expects($this->atLeastOnce())
			->method('setBasePrice')
			->with($this->identicalTo($e->getPrice()))
			->will($this->returnSelf());
		$product->expects($this->atLeastOnce())
			->method('setMsrp')
			->with($this->identicalTo($e->getMsrp()))
			->will($this->returnSelf());
		$product->expects($this->any())
			->method('getId')
			->will($this->returnValue($productId));
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
		$this->replaceByMock('model', 'catalog/product', $product);

		$model = $this->getModelMock('eb2cproduct/feed_item_pricing', array(
			'_loadProductBySku',
			'applyDummyData',
		));
		$model->expects($this->any())
			->method('_loadProductBySku')
			->with($this->identicalTo($e->getSku()))
			->will($this->returnValue($product));

		$model->expects($productId ? $this->never() : $this->once())
			->method('applyDummyData');

		$itemData = new Varien_Object(array_combine($this->keys, $data));
		$this->assertNotNull($itemData->getClientItemId());
		$this->_reflectMethod($model, '_processItem')->invoke($model, $itemData);
	}
}
