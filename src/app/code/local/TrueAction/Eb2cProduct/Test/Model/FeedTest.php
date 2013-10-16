<?php
class TrueAction_Eb2cProduct_Test_FeedTest
	extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'var/eb2c';

	public function setUp()
	{
		parent::setUp();
		$this->replaceCoreConfigRegistry(array(
			'clientId' => 'MAGTNA',
			'catalogId' => '45',
			'gsiClientId' => 'MAGTNA',
			'pricingFeedLocalPath' => self::VFS_ROOT . DS . 'pricing',
			'pricingFeedRemotePath' => '/',
			'pricingFeedFilePattern' => '*.xml',
			'pricingFeedEventType' => 'Price',
			'itemFeedLocalPath' => self::VFS_ROOT . DS . 'itemmaster',
			'itemFeedRemotePath' => '/',
			'itemFeedFilePattern' => '*.xml',
			'itemFeedEventType' => 'Item',
			'contentFeedLocalPath' => self::VFS_ROOT . DS . 'contentmaster',
			'contentFeedRemotePath' => '/',
			'contentFeedFilePattern' => '*.xml',
			'contentFeedEventType' => 'Content',
			'iShipFeedLocalPath' => self::VFS_ROOT . DS . 'iship',
			'iShipFeedRemotePath' => '/',
			'iShipFeedFilePattern' => '*.xml',
			'iShipFeedEventType' => 'iShip',
		));
	}

	/**
	 * verify a file's feed type is identified properly and the correct models
	 * are used.
	 * @dataProvider dataProvider
	 * @loadFixture
	 */
	public function testFeedModelSelection($feedFile, $model)
	{
		$vfs = $this->getFixture()->getVfs();
		$filesList = array($vfs->url($feedFile));
		$feedTypeA = $this->getModelMock('eb2cproduct/feed_item_master', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeA->expects($this->atLeastOnce())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeA->expects($this->atLeastOnce())
			->method('getFeedLocalPath')
			->will($this->returnValue('vfs/var/eb2c/itemmaster'));
		$feedTypeA->expects($this->atLeastOnce())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeA->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('ItemMaster'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_item_master', $feedTypeA);

		$feedTypeB = $this->getModelMock('eb2cproduct/feed_content_master', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeB->expects($this->atLeastOnce())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeB->expects($this->atLeastOnce())
			->method('getFeedLocalPath')
			->will($this->returnValue('vfs/var/eb2c/contentmaster'));
		$feedTypeB->expects($this->atLeastOnce())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeB->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('Content'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_content_master', $feedTypeB);

		$testModel = $this->getModelMock('eb2cproduct/feed', array(
			'_getIterableFor',
		));
		$testModel->expects($this->atLeastOnce())
			->method('_getIterableFor')
			->will($this->returnValue(array()));
		$coreFeed = $this->getModelMock('eb2ccore/feed', array(
			'fetchFeedsFromRemote',
			'lsInboundDir',
		));
		$coreFeed->expects($this->atLeastOnce())
			->method('lsInboundDir')
			->will($this->returnValue($filesList));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);
		$coreHelper = $this->getHelperMock('eb2ccore/data', array(
			'validateHeader',
		));
		$coreHelper->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));

		$testModel->ProcessFeeds();
		$feedModel = $this->_reflectProperty($testModel, '_eventTypeModel')->getValue($testModel);
		$this->assertInstanceOf($model, $feedModel);
	}

	/**
	 * @loadFixture
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testExtraction($scenario, $feedFile)
	{
		$vfs = $this->getFixture()->getVfs();
		$filesList = array($vfs->url($feedFile));

		$e = $this->expected($scenario);
		$queue = $this->getModelMock('eb2cproduct/feed_queue', array(
			'add',
		));
		$queue->expects($this->atLeastOnce())
			->method('add')
			->with(
				$this->isInstanceOf('Varien_Object'),
				$this->identicalTo('ADD')
			);

		$checkData = function($dataObj) use ($e) {
			PHPUnit_Framework_Assert::assertEquals(
				$dataObj->getData(),
				$e->getData()
			);
		};

		$testModel = $this->getModelMock('eb2cproduct/feed', array('_transformData'));
		$testModel->expects($this->atLeastOnce())
			->method('_transformData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnCallback($checkData));
		$this->_reflectProperty($testModel, '_queue')->setValue($testModel, $queue);

		$testModel->processFile($filesList[0]);
	}

	/**
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testFeedIntegration()
	{
		$this->markTestIncomplete();
		$vfs = $this->getFixture()->getVfs();
		$coreFeed = $this->getModelMock('eb2ccore/feed');
		$coreFeed->expects($this->any())
			->method('fetchFeedsFromRemote')
			->will($this->returnSelf());
		$coreFeed->expects($this->any())
			->method('lsInboundDir')
			->will($this->returnValue(array()));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$testModel = $this->getModelMock('eb2cproduct/feed', array(
		));
		$testModel->ProcessFeeds();

		$e = $this->expected('1-1');
		foreach ($e->getSkus() as $sku) {
			// check the results
			$products = Mage::getResourceModel('catalog/product_collection');
			$products->addAttributeToSelect('*')
				->getSelect()
				->where('e.sku = ?', $sku);
			$product = $products->getFirstItem();
			$e = $this->expected($sku);
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
}
