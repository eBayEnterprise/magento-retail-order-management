<?php
class TrueAction_Eb2cProduct_Test_Model_FeedTest
	extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'var/eb2c';

	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
		// clear out the feed type config models
		Mage::unregister('_singleton/eb2cproduct/feed_item');
		Mage::unregister('_singleton/eb2cproduct/feed_content');
		Mage::unregister('_singleton/eb2cproduct/feed_iship');
		Mage::unregister('_singleton/eb2cproduct/feed_pricing');
		Mage::unregister('_singleton/eb2cproduct/feed_queue');
		Mage::unregister('_model/eb2cproduct/feed_processor');
		Mage::unregister('_model/eb2ccore/feed');
	}

	/**
	 * Mock out the config registry with some default values.
	 * @return $this object
	 */
	protected function _mockConfigWithDefaults()
	{
		$this->replaceCoreConfigRegistry(array(
			'clientId' => 'MAGTNA',
			'catalogId' => '45',
			'gsiClientId' => 'MAGTNA',
			'pricingFeedLocalPath' => self::VFS_ROOT . DS . 'pricing',
			'pricingFeedRemoteReceivedPath' => '/',
			'pricingFeedFilePattern' => '*.xml',
			'pricingFeedEventType' => 'Price',
			'itemFeedLocalPath' => self::VFS_ROOT . DS . 'itemmaster',
			'itemFeedRemoteReceivedPath' => '/',
			'itemFeedFilePattern' => '*.xml',
			'itemFeedEventType' => 'Item',
			'contentFeedLocalPath' => self::VFS_ROOT . DS . 'contentmaster',
			'contentFeedRemoteReceivedPath' => '/',
			'contentFeedFilePattern' => '*.xml',
			'contentFeedEventType' => 'Content',
			'iShipFeedLocalPath' => self::VFS_ROOT . DS . 'iship',
			'iShipFeedRemoteReceivedPath' => '/',
			'iShipFeedFilePattern' => '*.xml',
			'iShipFeedEventType' => 'iShip',
			'processorUpdateBatchSize' => 1,
			'processorDeleteBatchSize' => 1,
			'processorMaxTotalEntries' => 1,
		));
		return $this;
	}

	/**
	 * @ticket EBC-240
	 * @loadFixture feedConfig.yaml
	 * @loadFixture testDescriptionClobbering.yaml
	 * @loadExpectation
	 */
	public function testDescriptionClobbering()
	{
		$vfs = $this->getFixture()->getVfs();
		$coreHelper = $this->getHelperMock('eb2ccore/data', array(
			'validateHeader',
		));
		$coreHelper->expects($this->any())
			->method('validateHeader')
			->will($this->returnValue(true));

		$filesList = array(
			$vfs->url('var/eb2c/pricing/feed.xml'),
		);
		// setup the core feed model
		$coreFeed = $this->getModelMock('eb2ccore/feed', array(
			'fetchFeedsFromRemote',
			'lsInboundDir',
			'mvToArchiveDir',
			'removeFromRemote',
		));
		$coreFeed->expects($this->atLeastOnce())
			->method('lsInboundDir')
			->will($this->onConsecutiveCalls(
				$filesList,
				array(),
				array(),
				array()
			));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		// setup the product helper
		$helper = $this->getHelperMock('eb2cproduct/data', array(
			'loadProductBySku'
		));
		$helper->expects($this->any())
			->method('loadProductBySku')
			->will($this->returnValue(Mage::getModel('catalog/product')));
		$this->replaceByMock('helper', 'eb2cproduct', $helper);

		// setup the processor model
		$dataChecker = function($dataObj) {
			$extendedData = $dataObj->getExtendedAttributes()->getData();
			PHPUnit_Framework_Assert::assertFalse(
				array_key_exists('long_description', $extendedData),
				'long description should not exist'
			);
			PHPUnit_Framework_Assert::assertFalse(
				array_key_exists('short_description', $extendedData),
				'short description should not exist'
			);
			$data = $dataObj->getBaseAttributes()->getData();
			PHPUnit_Framework_Assert::assertFalse(
				array_key_exists('item_description', $data),
				'short description should not exist'
			);
		};

		$processor = $this->getModelMock('eb2cproduct/feed_processor', array(
			'_synchProduct'
		));
		$processor->expects($this->once())
			->method('_synchProduct')
			->will($this->returnCallback($dataChecker));

		$testModel = Mage::getModel('eb2cproduct/feed');
		$queue = $this->_reflectProperty($testModel, '_queue')->getValue($testModel);
		$this->_reflectProperty($queue, '_processor')->setValue($queue, $processor);
		$testModel->processFeeds();
	}

	/**
	 * verify a file's feed type is identified properly and the correct models
	 * are used.
	 * @dataProvider dataProvider
	 * @loadFixture
	 * @large
	 */
	public function testFeedModelSelection($feedFile, $model)
	{
		$vfs = $this->getFixture()->getVfs();
		$filesList = array($vfs->url($feedFile));
		$feedTypeA = $this->getModelMock('eb2cproduct/feed_item', array(
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
			->will($this->returnValue('var/eb2c/itemmaster'));
		$feedTypeA->expects($this->atLeastOnce())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeA->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('ItemMaster'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_item', $feedTypeA);

		$feedTypeB = $this->getModelMock('eb2cproduct/feed_content', array(
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
			->will($this->returnValue('var/eb2c/contentmaster'));
		$feedTypeB->expects($this->atLeastOnce())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeB->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('Content'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_content', $feedTypeB);

		$feedTypeC = $this->getModelMock('eb2cproduct/feed_pricing', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeC->expects($this->atLeastOnce())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeC->expects($this->atLeastOnce())
			->method('getFeedLocalPath')
			->will($this->returnValue('var/eb2c/pricing'));
		$feedTypeC->expects($this->atLeastOnce())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeC->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('Price'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_pricing', $feedTypeC);

		$feedTypeD = $this->getModelMock('eb2cproduct/feed_iship', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeD->expects($this->atLeastOnce())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeD->expects($this->atLeastOnce())
			->method('getFeedLocalPath')
			->will($this->returnValue('var/eb2c/iship'));
		$feedTypeD->expects($this->atLeastOnce())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeD->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('iShip'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_iship', $feedTypeD);

		$testModel = $this->getModelMock('eb2cproduct/feed', array(
			'_getIterableFor',
		));
		$testModel->expects($this->atLeastOnce())
			->method('_getIterableFor')
			->will($this->returnValue(array()));
		$coreFeed = $this->getModelMock('eb2ccore/feed', array(
			'fetchFeedsFromRemote',
			'lsInboundDir',
			'mvToArchiveDir',
			'removeFromRemote',
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

		$testModel->processFeeds();
		$feedModel = $this->_reflectProperty($testModel, '_eventTypeModel')->getValue($testModel);
		$this->assertInstanceOf($model, $feedModel);
	}

	/**
	 * verify a unit that fails initial validation will not be extracted.
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testUnitValidationFail($feedFile)
	{
		$vfs = $this->getFixture()->getVfs();
		$filesList = array($vfs->url($feedFile));

		$testModel = $this->getModelMock('eb2cproduct/feed', array('_extractData'));
		$testModel->expects($this->never())
			->method('_extractData');
		$testModel->processFile($filesList[0]);
	}

	/**
	 * @loadFixture feedConfig.yaml
	 * @loadFixture
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testExtraction($scenario, $feedFile)
	{
		$feedTypeA = $this->getModelMock('eb2cproduct/feed_item', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeA->expects($this->any())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeA->expects($this->any())
			->method('getFeedLocalPath')
			->will($this->returnValue('var/eb2c/itemmaster'));
		$feedTypeA->expects($this->any())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeA->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('ItemMaster'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_item', $feedTypeA);

		$feedTypeB = $this->getModelMock('eb2cproduct/feed_content', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeB->expects($this->any())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeB->expects($this->any())
			->method('getFeedLocalPath')
			->will($this->returnValue('var/eb2c/contentmaster'));
		$feedTypeB->expects($this->any())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeB->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('Content'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_content', $feedTypeB);

		$feedTypeC = $this->getModelMock('eb2cproduct/feed_pricing', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeC->expects($this->any())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeC->expects($this->any())
			->method('getFeedLocalPath')
			->will($this->returnValue('var/eb2c/pricing'));
		$feedTypeC->expects($this->any())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeC->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('Price'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_pricing', $feedTypeC);

		$feedTypeD = $this->getModelMock('eb2cproduct/feed_iship', array(
			'_construct',
			'getFeedRemotePath',
			'getFeedLocalPath',
			'getFeedFilePattern',
			'getFeedEventType',
		));
		$feedTypeD->expects($this->any())
			->method('getFeedRemotePath')
			->will($this->returnValue('some/remote/path'));
		$feedTypeD->expects($this->any())
			->method('getFeedLocalPath')
			->will($this->returnValue('var/eb2c/iship'));
		$feedTypeD->expects($this->any())
			->method('getFeedFilePattern')
			->will($this->returnValue('*.xml'));
		$feedTypeD->expects($this->any())
			->method('getFeedEventType')
			->will($this->returnValue('iShip'));
		$this->replaceByMock('singleton', 'eb2cproduct/feed_iship', $feedTypeD);

		$vfs = $this->getFixture()->getVfs();
		$filesList = array($vfs->url($feedFile));

		$e = $this->expected($scenario);
		$queue = $this->getModelMock('eb2cproduct/feed_queue', array(
			'add',
		));
		$checkData = function($dataObj) use ($e) {
			PHPUnit_Framework_Assert::assertInstanceOf(
				'Varien_Object',
				$dataObj
			);
		};
		$queue->expects($this->atLeastOnce())
			->method('add')
			->with(
				$this->isInstanceOf('Varien_Object'),
				$this->identicalTo('ADD')
			)
			->will($this->returnCallback($checkData));

		$testModel = Mage::getModel('eb2cproduct/feed');
		$this->_reflectProperty($testModel, '_queue')->setValue($testModel, $queue);

		$testModel->processFile($filesList[0]);
	}

	/**
	 * @loadFixture feedConfig.yaml
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testFeedIntegration()
	{
		$vfs = $this->getFixture()->getVfs();
		$coreFeed = $this->getModelMock('eb2ccore/feed', array(
			'fetchFeedsFromRemote',
			'lsInboundDir',
			'mvToArchiveDir',
			'removeFromRemote',
		));
		$coreFeed->expects($this->any())
			->method('lsInboundDir')
			->will($this->onConsecutiveCalls(
				array($vfs->url('var/eb2c/pricing/feed.xml')),
				array($vfs->url('var/eb2c/itemmaster/feed.xml')),
				array($vfs->url('var/eb2c/contentmaster/feed.xml')),
				array($vfs->url('var/eb2c/iship/feed.xml'))
			));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$testModel = Mage::getModel('eb2cproduct/feed');
		$testModel->processFeeds();

		$e = $this->expected('1-1');
		foreach ($e->getSkus() as $sku) {
			// check the results
			$products = Mage::getResourceModel('catalog/product_collection');
			$products->addAttributeToSelect('*')
				->getSelect()
				->where('e.sku = ?', $sku);
			$product = $products->getFirstItem();
			$e = $this->expected($sku);
			$this->markTestIncomplete('the expectations and fixture feed xml need to be finished');
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

	/**
	 * Test processing of the feeds, success and failure
	 * @param  boolean $domLoadSuccess Should the XML be loaded succesfully
	 * @param  boolean $processSuccess Should processing of the DOM be successful
	 * @test
	 * @loadFixture
	 */
	public function testFeedProcessing()
	{
		$itemFile   = 'ItemMaster.xml';
		$itemPath   = 'Local/Master/' . DS . $itemFile;
		$itemRemote = 'item_remote_path';
		$itemGlob   = 'Item*Glob';

		$contentFile   = 'Content.xml';
		$contentPath   = 'Local/Content/' . DS . $contentFile;
		$contentRemote = 'content_remote_path';
		$contentGlob   = 'Content*Glob';

		$priceFile   = 'Price.xml';
		$pricePath   = 'Local/Price/' . DS . $priceFile;
		$priceRemote = 'price_remote_path';
		$priceGlob   = 'Price*Glob';

		$ishipFile   = 'Iship.xml';
		$ishipPath   = 'Local/Iship/' . DS . $ishipFile;
		$ishipRemote = 'iship_remote_path';
		$ishipGlob   = 'iShip*Glob';

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('fetchFeedsFromRemote', 'lsInboundDir', 'mvToArchiveDir', 'removeFromRemote',))
			->getMock();
		$coreFeed->expects($this->exactly(4))
			->method('fetchFeedsFromRemote')
			->with(
				$this->logicalOr(
					$this->identicalTo($itemRemote),
					$this->identicalTo($contentRemote),
					$this->identicalTo($priceRemote),
					$this->identicalTo($ishipRemote)
				),
				$this->logicalOr(
					$this->identicalTo($itemGlob),
					$this->identicalTo($contentGlob),
					$this->identicalTo($priceGlob),
					$this->identicalTo($ishipGlob)
				)
			);
		$coreFeed->expects($this->exactly(4))
			->method('lsInboundDir')
			->will($this->onConsecutiveCalls(
				array($pricePath), array($itemPath), array($contentPath), array($ishipPath)
			));
		$coreFeed->expects($this->exactly(4))
			->method('mvToArchiveDir')
			->with($this->logicalOr(
				$this->identicalTo($itemPath),
				$this->identicalTo($contentPath),
				$this->identicalTo($pricePath),
				$this->identicalTo($ishipPath)
			));
		$coreFeed->expects($this->exactly(4))
			->method('removeFromRemote')
			->with(
				$this->logicalOr(
					$this->identicalTo($itemRemote),
					$this->identicalTo($contentRemote),
					$this->identicalTo($priceRemote),
					$this->identicalTo($ishipRemote)
				),
				$this->logicalOr(
					$this->logicalOr($itemFile),
					$this->logicalOr($contentFile),
					$this->logicalOr($priceFile),
					$this->logicalOr($ishipFile)
				)
			);
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$model = $this->getModelMock('eb2cproduct/feed', array('processFile'));
		$model->expects($this->exactly(4))
			->method('processFile')
			->with($this->logicalOr(
				$this->logicalOr($itemPath),
				$this->logicalOr($contentPath),
				$this->logicalOr($pricePath),
				$this->logicalOr($ishipPath)
			));

		$this->assertSame(4, $model->processFeeds());
	}

}
