<?php
class TrueAction_Eb2cProduct_Test_Model_FeedTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test running the feed
	 * @test
	 */
	public function testProcessFeeds()
	{
		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getMessageDate'))
			->getMock();
		$coreFeedHelperMock->expects($this->exactly(4))
			->method('getMessageDate')
			->will($this->returnValue(DateTime::createFromFormat('U', 0)));

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelperMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern'))
			->getMock();
		$feedItemModelMock->expects($this->exactly(2))
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('ItemMaster*.xml'));

		$feedContentModelMock = $this->getModelMockBuilder('eb2cproduct/feed_content')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern'))
			->getMock();
		$feedContentModelMock->expects($this->at(0))
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedContentModelMock->expects($this->at(1))
			->method('getFeedFilePattern')
			->will($this->returnValue('Content*.xml'));
		$feedContentModelMock->expects($this->at(2))
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));

		$feedPricingModelMock = $this->getModelMockBuilder('eb2cproduct/feed_pricing')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern'))
			->getMock();
		$feedPricingModelMock->expects($this->exactly(2))
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/Pricing/ABCD123/'));
		$feedPricingModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('Price*.xml'));

		$feedIshipModelMock = $this->getModelMockBuilder('eb2cproduct/feed_iship')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern'))
			->getMock();
		$feedIshipModelMock->expects($this->exactly(2))
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedIshipModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('IShip*.xml'));

		$feedExtractorXpathModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_xpath')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_extractor_xpath', $feedExtractorXpathModelMock);

		$feedQueueModelMock = $this->getModelMockBuilder('eb2cproduct/feed_queue')
			->disableOriginalConstructor()
			->setMethods(array('process'))
			->getMock();
		$feedQueueModelMock->expects($this->once())
			->method('process')
			->will($this->returnValue(null));
		$this->replaceByMock('model', 'eb2cproduct/feed_queue', $feedQueueModelMock);

		$coreFeedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('fetchFeedsFromRemote', 'lsInboundDir'))
			->getMock();
		$coreFeedModelMock->expects($this->at(0))
			->method('fetchFeedsFromRemote')
			->with($this->equalTo('/Inbox/'), $this->equalTo('ItemMaster*.xml'));
		$coreFeedModelMock->expects($this->at(2))
			->method('fetchFeedsFromRemote')
			->with($this->equalTo('/Inbox/'), $this->equalTo('Content*.xml'));
		$coreFeedModelMock->expects($this->at(4))
			->method('fetchFeedsFromRemote')
			->with($this->equalTo('/Inbox/Pricing/ABCD123/'), $this->equalTo('Price*.xml'));
		$coreFeedModelMock->expects($this->at(6))
			->method('fetchFeedsFromRemote')
			->with($this->equalTo('/Inbox/'), $this->equalTo('IShip*.xml'));
		$coreFeedModelMock->expects($this->at(1))
			->method('lsInboundDir')
			->will($this->returnValue(array('/ItemMaster/sample-feed.xml')));
		$coreFeedModelMock->expects($this->at(3))
			->method('lsInboundDir')
			->will($this->returnValue(array('/ContentMaster/sample-feed.xml')));
		$coreFeedModelMock->expects($this->at(5))
			->method('lsInboundDir')
			->will($this->returnValue(array('/Pricing/sample-feed.xml')));
		$coreFeedModelMock->expects($this->at(7))
			->method('lsInboundDir')
			->will($this->returnValue(array('/iShip/sample-feed.xml')));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModelMock);

		$feedCleanerModelMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('cleanAllProducts'))
			->getMock();
		$feedCleanerModelMock->expects($this->once())
			->method('cleanAllProducts')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelMock);

		$coreIndexerModelMock = $this->getModelMockBuilder('eb2ccore/indexer')
			->disableOriginalConstructor()
			->setMethods(array('reindexAll'))
			->getMock();
		$coreIndexerModelMock->expects($this->once())
			->method('reindexAll')
			->will($this->returnValue(null));
		$this->replaceByMock('model', 'eb2ccore/indexer', $coreIndexerModelMock);

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_getEventTypeModel', '_setupCoreFeed', '_compareFeedFiles', 'processFile', 'archiveFeed'))
			->getMock();
		$feedModelMock->expects($this->at(0))
			->method('_getEventTypeModel')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($feedItemModelMock));
		$feedModelMock->expects($this->at(2))
			->method('_getEventTypeModel')
			->with($this->equalTo('Content'))
			->will($this->returnValue($feedContentModelMock));
		$feedModelMock->expects($this->at(4))
			->method('_getEventTypeModel')
			->with($this->equalTo('Price'))
			->will($this->returnValue($feedPricingModelMock));
		$feedModelMock->expects($this->at(6))
			->method('_getEventTypeModel')
			->with($this->equalTo('iShip'))
			->will($this->returnValue($feedIshipModelMock));
		$feedModelMock->expects($this->exactly(4))
			->method('_setupCoreFeed')
			->will($this->returnValue($coreFeedModelMock));
		$feedModelMock->expects($this->any())
			->method('_compareFeedFiles')
			->with($this->isType('array'))
			->will($this->returnValue(0));
		$feedModelMock->expects($this->exactly(4))
			->method('processFile')
			->will($this->returnValue(null));
		$feedModelMock->expects($this->exactly(4))
			->method('archiveFeed')
			->will($this->returnValue(null));

		$this->assertSame(4, $feedModelMock->processFeeds());
	}

	/**
	 * Test processFile method
	 * @test
	 */
	public function testProcessFile()
	{
		$domMock = $this->getMock('TrueAction_Dom_Document', array('load'));
		$domMock->expects($this->once())
			->method('load')
			->with($this->equalTo('/ItemMaster/sample-feed.xml'))
			->will($this->returnSelf());

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($domMock));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('validateHeader'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('validateHeader')
			->with($this->isInstanceOf('TrueAction_Dom_Document'), $this->equalTo('ItemMaster'))
			->will($this->returnValue(true));

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $feedProcessorModelMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_getEventTypeModel', '_determineEventType', '_beforeProcessDom', 'processDom'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_getEventTypeModel')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($feedItemModelMock));
		$feedModelMock->expects($this->once())
			->method('_determineEventType')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue('ItemMaster'));
		$feedModelMock->expects($this->once())
			->method('_beforeProcessDom')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnSelf());
		$feedModelMock->expects($this->once())
			->method('processDom')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnSelf());

		$feedModelMock->processFile('/ItemMaster/sample-feed.xml');
	}

	/**
	 * Test processFile method, where loading the file to the
	 * domdocument will throw an exception
	 * @test
	 */
	public function testProcessFileDomLoadExceptionThrown()
	{
		$domMock = $this->getMock('TrueAction_Dom_Document', array('load'));
		$domMock->expects($this->once())
			->method('load')
			->with($this->equalTo('/ItemMaster/sample-feed.xml'))
			->will($this->throwException(
				new Exception('UnitTest Simulate Throw Exception on Dom load')
			));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($domMock));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $feedProcessorModelMock);

		Mage::getModel('eb2cproduct/feed')->processFile('/ItemMaster/sample-feed.xml');
	}

	/**
	 * Test processFile method, core feed validateHeader is invalid
	 * @test
	 */
	public function testProcessFileWithInvalidHeader()
	{
		$domMock = $this->getMock('TrueAction_Dom_Document', array('load'));
		$domMock->expects($this->once())
			->method('load')
			->with($this->equalTo('/ItemMaster/sample-feed.xml'))
			->will($this->returnSelf());

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($domMock));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('validateHeader'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('validateHeader')
			->with($this->isInstanceOf('TrueAction_Dom_Document'), $this->equalTo('ItemMaster'))
			->will($this->returnValue(false));

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $feedProcessorModelMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_getEventTypeModel', '_determineEventType'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_getEventTypeModel')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($feedItemModelMock));
		$feedModelMock->expects($this->once())
			->method('_determineEventType')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue('ItemMaster'));

		$feedModelMock->processFile('/ItemMaster/sample-feed.xml');
	}

	/**
	 * Test processFile method, where the internal _beforeProcessDom method throw
	 * an exception
	 * @test
	 */
	public function testProcessFileWhereBeforeProccessDomThowException()
	{
		$domMock = $this->getMock('TrueAction_Dom_Document', array('load'));
		$domMock->expects($this->once())
			->method('load')
			->with($this->equalTo('/ItemMaster/sample-feed.xml'))
			->will($this->returnSelf());

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($domMock));

		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('validateHeader'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('validateHeader')
			->with($this->isInstanceOf('TrueAction_Dom_Document'), $this->equalTo('ItemMaster'))
			->will($this->returnValue(true));

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelperMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_processor', $feedProcessorModelMock);

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_getEventTypeModel', '_determineEventType', '_beforeProcessDom'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_getEventTypeModel')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($feedItemModelMock));
		$feedModelMock->expects($this->once())
			->method('_determineEventType')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue('ItemMaster'));
		$feedModelMock->expects($this->once())
			->method('_beforeProcessDom')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->throwException(
				new Mage_Core_Exception('UnitTest Simulate _beforeProcessDom method Throw Exception')
			));

		$feedModelMock->processFile('/ItemMaster/sample-feed.xml');
	}

	/**
	 * Test processDom method
	 * @test
	 */
	public function testProcessDom()
	{
		$feedExtractorXpathModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_xpath')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_extractor_xpath', $feedExtractorXpathModelMock);

		$feedQueueModelMock = $this->getModelMockBuilder('eb2cproduct/feed_queue')
			->disableOriginalConstructor()
			->setMethods(array('add'))
			->getMock();
		$feedQueueModelMock->expects($this->once())
			->method('add')
			->with($this->isInstanceOf('Varien_Object'), $this->equalTo('Update'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_queue', $feedQueueModelMock);

		$unitvalidatorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_specialized_unitvalidator')
			->disableOriginalConstructor()
			->setMethods(array('getValue'))
			->getMock();
		$unitvalidatorModelMock->expects($this->once())
			->method('getValue')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'))
			->will($this->returnValue(true));

		$operationTypeModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_specialized_operationtype')
			->disableOriginalConstructor()
			->setMethods(array('getValue'))
			->getMock();
		$operationTypeModelMock->expects($this->once())
			->method('getValue')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'))
			->will($this->returnValue('Update'));

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getUnitValidationExtractor', 'getOperationExtractor'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getUnitValidationExtractor')
			->will($this->returnValue($unitvalidatorModelMock));
		$feedItemModelMock->expects($this->once())
			->method('getOperationExtractor')
			->will($this->returnValue($operationTypeModelMock));

		$domMock = $this->getMock('TrueAction_Dom_Document', array());

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_getIterableFor', '_extractData'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_getIterableFor')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue(array(new DOMElement('sku', '1234'))));
		$feedModelMock->expects($this->once())
			->method('_extractData')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(new Varien_Object(array('sku' => '1234'))));

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);
		$this->_reflectProperty($feedModelMock, '_xpath')->setValue($feedModelMock, new DOMXPath(new TrueAction_Dom_Document('1.0', 'UTF-8')));

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed',
			$feedModelMock->processDom($domMock)
		);
	}

	/**
	 * Test processDom method continues with exception on operation type on individual unit
	 * @test
	 */
	public function testProcessDomWithInvalidOperationType()
	{
		$feedExtractorXpathModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_xpath')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_extractor_xpath', $feedExtractorXpathModelMock);

		$feedQueueModelMock = $this->getModelMockBuilder('eb2cproduct/feed_queue')
			->disableOriginalConstructor()
			->setMethods(array('add'))
			->getMock();
		$feedQueueModelMock->expects($this->once())
			->method('add')
			->with($this->isInstanceOf('Varien_Object'), $this->equalTo('SomeWackyOperationType'))
			->will($this->throwException(new TrueAction_Eb2cProduct_Model_Feed_Exception('Invalid op type tester')));
		$this->replaceByMock('model', 'eb2cproduct/feed_queue', $feedQueueModelMock);

		$unitvalidatorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_specialized_unitvalidator')
			->disableOriginalConstructor()
			->setMethods(array('getValue'))
			->getMock();
		$unitvalidatorModelMock->expects($this->once())
			->method('getValue')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'))
			->will($this->returnValue(true));

		$operationTypeModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_specialized_operationtype')
			->disableOriginalConstructor()
			->setMethods(array('getValue'))
			->getMock();
		$operationTypeModelMock->expects($this->once())
			->method('getValue')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'))
			->will($this->returnValue('SomeWackyOperationType'));

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getUnitValidationExtractor', 'getOperationExtractor'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getUnitValidationExtractor')
			->will($this->returnValue($unitvalidatorModelMock));
		$feedItemModelMock->expects($this->once())
			->method('getOperationExtractor')
			->will($this->returnValue($operationTypeModelMock));

		$domMock = $this->getMock('TrueAction_Dom_Document', array());

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_getIterableFor', '_extractData'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_getIterableFor')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue(array(new DOMElement('sku', '1234'))));
		$feedModelMock->expects($this->once())
			->method('_extractData')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(new Varien_Object(array('sku' => '1234'))));

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);
		$this->_reflectProperty($feedModelMock, '_xpath')->setValue($feedModelMock, new DOMXPath(new TrueAction_Dom_Document('1.0', 'UTF-8')));

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed',
			$feedModelMock->processDom($domMock)
		);
	}

	/**
	 * Test beforeProcessDom method
	 * @test
	 */
	public function testBeforeProcessDom()
	{
		$feedExtractorXpathModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_xpath')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_extractor_xpath', $feedExtractorXpathModelMock);

		$feedQueueModelMock = $this->getModelMockBuilder('eb2cproduct/feed_queue')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_queue', $feedQueueModelMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getNewXpath'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getNewXpath')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue(new DOMXPath(new TrueAction_Dom_Document('1.0', 'UTF-8'))));

		$coreFeedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_checkPreconditions', '_setupCoreFeed'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_checkPreconditions')
			->will($this->returnValue(null));
		$feedModelMock->expects($this->once())
			->method('_setupCoreFeed')
			->will($this->returnValue($coreFeedModelMock));

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$domMock = $this->getMock('TrueAction_Dom_Document', array());
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed',
			$this->_reflectMethod($feedModelMock, '_beforeProcessDom')->invoke($feedModelMock, $domMock)
		);
	}

	/**
	 * Test beforeProcessDom method, when xpath is not undefined and
	 * Mage_Core_Exception is then thrown
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testBeforeProcessDomWithMageCoreExceptionThrown()
	{
		$feedExtractorXpathModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_xpath')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_extractor_xpath', $feedExtractorXpathModelMock);

		$feedQueueModelMock = $this->getModelMockBuilder('eb2cproduct/feed_queue')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2cproduct/feed_queue', $feedQueueModelMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getNewXpath'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getNewXpath')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue(null));

		$coreFeedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->setMethods(array('_checkPreconditions', '_setupCoreFeed'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_checkPreconditions')
			->will($this->returnValue(null));
		$feedModelMock->expects($this->once())
			->method('_setupCoreFeed')
			->will($this->returnValue($coreFeedModelMock));

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$domMock = $this->getMock('TrueAction_Dom_Document', array());
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed',
			$this->_reflectMethod($feedModelMock, '_beforeProcessDom')->invoke($feedModelMock, $domMock)
		);
	}

	/**
	 * verify comparing to feed file entries will yield the correct result
	 */
	public function testCompareFeedFiles()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$testData = array(
			array(
				'expect' => 0,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'ItemMaster', 'timestamp' => 1),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'ItemMaster', 'timestamp' => 1),
			),
			array(
				'expect' => -1,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'ItemMaster', 'timestamp' => 1),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'Content', 'timestamp' => 1),
			),
			array(
				'expect' => 2,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'Price', 'timestamp' => 1),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'ContentMaster', 'timestamp' => 1),
			),
			array(
				'expect' => -1,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'ItemMaster', 'timestamp' => 1),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'ItemMaster', 'timestamp' => 2),
			),
			array(
				'expect' => 1,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'ItemMaster', 'timestamp' => 2),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'ItemMaster', 'timestamp' => 1),
			),
			array(
				'expect' => 1,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'ItemMaster', 'timestamp' => 2),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'ContentMaster', 'timestamp' => 1),
			),
			array(
				'expect' => -1,
				'a' => array('local' => 'FeedFileA.xml', 'type' => 'Price', 'timestamp' => 1),
				'b' => array('local' => 'FeedFileB.xml', 'type' => 'ContentMaster', 'timestamp' => 2),
			)
		);
		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				$this->_reflectMethod($feedModelMock, '_compareFeedFiles')->invoke($feedModelMock, $data['a'], $data['b'])
			);
		}
	}

	/**
	 * Test _determineEventType method
	 * @test
	 */
	public function testDetermineEventType()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		foreach (array('ItemMaster', 'Content', 'Price', 'iShip') as $eventType) {
			$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
			$doc->loadXML(sprintf('<root><MessageHeader><EventType>%s</EventType></MessageHeader></root>', $eventType));
			$this->assertSame(
				$eventType,
				$this->_reflectMethod($feedModelMock, '_determineEventType')->invoke($feedModelMock, $doc)
			);
		}
	}

	/**
	 * Test _determineEventType method, with wrong event type
	 * will throw Mage_Core_Exception
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testDetermineEventTypeWithWrongEvenTypeThrowException()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$eventType = 'WrongEventType';
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(sprintf('<root><MessageHeader><EventType>%s</EventType></MessageHeader></root>', $eventType));
		$this->assertSame(
			$eventType,
			$this->_reflectMethod($feedModelMock, '_determineEventType')->invoke($feedModelMock, $doc)
		);
	}

	/**
	 * Test _extractData method
	 * @test
	 */
	public function testExtractData()
	{
		$feedExtractorXpathModelMock = $this->getModelMockBuilder('eb2cproduct/feed_extractor_xpath')
			->disableOriginalConstructor()
			->setMethods(array('extract'))
			->getMock();
		$feedExtractorXpathModelMock->expects($this->once())
			->method('extract')
			->with($this->isInstanceOf('DOMXPath'), $this->isInstanceOf('DOMElement'))
			->will($this->returnValue(array('sku' => '1234')));

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getExtractors'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getExtractors')
			->will($this->returnValue(array($feedExtractorXpathModelMock)));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('<root><sku>1234</sku></root>');
		$this->_reflectProperty($feedModelMock, '_xpath')->setValue($feedModelMock, new DOMXPath($doc));

		$result = $this->_reflectMethod($feedModelMock, '_extractData')->invoke($feedModelMock, new DOMElement('sku', '1234'));

		$this->assertSame(array('sku' => '1234'), $result->getData());
	}

	/**
	 * Test _getEventTypeModel method
	 * @test
	 */
	public function testGetEventTypeModel()
	{
		$testData = array(
			array('event' => 'ItemMaster', 'class' => 'TrueAction_Eb2cProduct_Model_Feed_Item'),
			array('event' => 'Content', 'class' => 'TrueAction_Eb2cProduct_Model_Feed_Content'),
			array('event' => 'Price', 'class' => 'TrueAction_Eb2cProduct_Model_Feed_Pricing'),
			array('event' => 'iShip', 'class' => 'TrueAction_Eb2cProduct_Model_Feed_Iship')
		);
		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		foreach ($testData as $data) {
			$this->assertInstanceOf(
				$data['class'],
				$this->_reflectMethod($feedModelMock, '_getEventTypeModel')->invoke($feedModelMock, $data['event'])
			);
		}
	}

	/**
	 * Test _getIterableFor method
	 * @test
	 */
	public function testGetIterableFor()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getBaseXpath'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getBaseXpath')
			->will($this->returnValue('/ItemMaster/Item'));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('<ItemMaster><Item><sku>1234</sku></Item></ItemMaster>');
		$this->_reflectProperty($feedModelMock, '_xpath')->setValue($feedModelMock, new DOMXPath($doc));

		$this->assertInstanceOf(
			'DOMNodeList', $this->_reflectMethod($feedModelMock, '_getIterableFor')->invoke($feedModelMock, $doc)
		);
	}

	/**
	 * Test _setupCoreFeed method
	 * @test
	 */
	public function testSetupCoreFeed()
	{
		$coreFeedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeedModelMock);

		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedLocalPath'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getFeedLocalPath')
			->will($this->returnValue('/ItemMaster/'));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Feed',
			$this->_reflectMethod($feedModelMock, '_setupCoreFeed')->invoke($feedModelMock)
		);
	}

	/**
	 * Test _checkPreconditions method
	 * @test
	 */
	public function testCheckPreconditions()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern', 'getFeedLocalPath', 'getFeedEventType'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('ItemMaster*.xml'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedLocalPath')
			->will($this->returnValue('/ItemMaster/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedEventType')
			->will($this->returnValue('ItemMaster'));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$this->_reflectMethod($feedModelMock, '_checkPreconditions')->invoke($feedModelMock);
	}

	/**
	 * Test _checkPreconditions method, throw Mage_Core_Exception when FeedRemotePath is null
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testCheckPreconditionsThrowExceptionWhenFeedRemotePathIsNull()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue(null));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_missingConfigMessage'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_missingConfigMessage')
			->with($this->equalTo('FeedRemotePath'))
			->will($this->returnValue(sprintf(
				"%s was not setup correctly; 'FeedRemotePath' not configured.",
				get_class($feedItemModelMock)
			)));
		$this->replaceByMock('model', 'eb2cproduct/feed', $feedModelMock);

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$this->_reflectMethod($feedModelMock, '_checkPreconditions')->invoke($feedModelMock);
	}

	/**
	 * Test _checkPreconditions method, throw Mage_Core_Exception when FeedFilePattern is null
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testCheckPreconditionsThrowExceptionWhenFeedFilePatternIsNull()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath','getFeedFilePattern'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue(null));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_missingConfigMessage'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_missingConfigMessage')
			->with($this->equalTo('FeedFilePattern'))
			->will($this->returnValue(sprintf(
				"%s was not setup correctly; 'FeedFilePattern' not configured.",
				get_class($feedItemModelMock)
			)));
		$this->replaceByMock('model', 'eb2cproduct/feed', $feedModelMock);

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$this->_reflectMethod($feedModelMock, '_checkPreconditions')->invoke($feedModelMock);
	}

	/**
	 * Test _checkPreconditions method, throw Mage_Core_Exception when FeedLocalPath is null
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testCheckPreconditionsThrowExceptionWhenFeedLocalPathIsNull()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern','getFeedLocalPath'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('ItemMaster*.xml'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedLocalPath')
			->will($this->returnValue(null));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_missingConfigMessage'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_missingConfigMessage')
			->with($this->equalTo('FeedLocalPath'))
			->will($this->returnValue(sprintf(
				"%s was not setup correctly; 'FeedLocalPath' not configured.",
				get_class($feedItemModelMock)
			)));
		$this->replaceByMock('model', 'eb2cproduct/feed', $feedModelMock);

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$this->_reflectMethod($feedModelMock, '_checkPreconditions')->invoke($feedModelMock);
	}

	/**
	 * Test _checkPreconditions method, throw Mage_Core_Exception when FeedEventType is null
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testCheckPreconditionsThrowExceptionWhenFeedEventTypeIsNull()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath', 'getFeedFilePattern', 'getFeedLocalPath','getFeedEventType'))
			->getMock();
		$feedItemModelMock->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue('/Inbox/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('ItemMaster*.xml'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedLocalPath')
			->will($this->returnValue('/ItemMaster/'));
		$feedItemModelMock->expects($this->once())
			->method('getFeedEventType')
			->will($this->returnValue(null));

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_missingConfigMessage'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_missingConfigMessage')
			->with($this->equalTo('FeedEventType'))
			->will($this->returnValue(sprintf(
				"%s was not setup correctly; 'FeedEventType' not configured.",
				get_class($feedItemModelMock)
			)));
		$this->replaceByMock('model', 'eb2cproduct/feed', $feedModelMock);

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		$this->_reflectMethod($feedModelMock, '_checkPreconditions')->invoke($feedModelMock);
	}

	/**
	 * Test _missingConfigMessage method
	 * @test
	 */
	public function testMissingConfigMessage()
	{
		$feedItemModelMock = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypeModel')->setValue($feedModelMock, $feedItemModelMock);

		foreach (array('FeedRemotePath', 'FeedFilePattern', 'FeedLocalPath', 'FeedEventType') as $mConfig) {
			$this->assertSame(
				sprintf("%s was not setup correctly; '%s' not configured.", get_class($feedItemModelMock), $mConfig),
				$this->_reflectMethod($feedModelMock, '_missingConfigMessage')->invoke($feedModelMock, $mConfig)
			);
		}
	}
}
