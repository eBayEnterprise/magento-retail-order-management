<?php
class TrueAction_Eb2cProduct_Test_Model_FeedTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test _construct method with the following assumptions when this test run
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Feed::_construct to intialized the
	 *                class property TrueAction_Eb2cProduct_Model_Feed::_eventTypes an array of eventType and event type specific model string
	 * Expectation 2: this test set the class property TrueAction_Eb2cProduct_Model_Feed::_eventTypes to a known state of an empty array
	 *                when the TrueAction_Eb2cProduct_Model_Feed::_construct method get invoke the _eventTypes property is expected to have
	 *                an array of key value
	 */
	public function testConstruct()
	{
		$feed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		// class property _eventTypes to a known state
		$this->_reflectProperty($feed, '_eventTypes')->setValue($feed, array());
		$this->_reflectMethod($feed, '_construct')->invoke($feed);
		$this->assertSame(
			array(
				'ItemMaster' => 'feed_item',
				'Content' => 'feed_content',
				'Price' => 'feed_pricing',
				'iShip' => 'feed_iship',
			),
			$this->_reflectProperty($feed, '_eventTypes')->getValue($feed)
		);
	}

	/**
	 * Test _fetchFiles method with the following assumptions when call with given eb2cproduct/feed_item object as a parameter
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Feed::_fetchFiles method is expected to be called with the a given
	 *                mocked TrueAction_Eb2cProduct_Model_Feed_Item object and is expected to return an array of files
	 * Expectation 2: mocking the class TrueAction_Eb2cProduct_Model_Feed_Item methods getFeedLocalPath, getFeedRemotePath, getFeedFilePattern
	 *                to return known value which can be futher test in the mocking of TrueAction_Eb2cCore_Model_Feed class
	 *                where the fetchFeedsFromRemote method will be invoked once and the value from the TrueAction_Eb2cProduct_Model_Feed_Item class
	 *                are then tested as expected
	 * Expectation 3: the TrueAction_Eb2cCore_Model_Feed::lsInboundDir method is expected to return the array of files
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Item::getFeedLocalPath
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Item::getFeedRemotePath
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Item::getFeedFilePattern
	 * @mock TrueAction_Eb2cCore_Model_Feed::fetchFeedsFromRemote
	 * @mock TrueAction_Eb2cCore_Model_Feed::lsInboundDir
	 */
	public function testFetchFiles()
	{
		$eventTypeModel = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedLocalPath', 'getFeedRemotePath', 'getFeedFilePattern'))
			->getMock();
		$eventTypeModel->expects($this->once())
			->method('getFeedLocalPath')
			->will($this->returnValue('TrueAction/Product/ItemMaster/Inbound/'));
		$eventTypeModel->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue('/ItemMaster/'));
		$eventTypeModel->expects($this->once())
			->method('getFeedFilePattern')
			->will($this->returnValue('ItemMaster*_*.xml'));

		$coreModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('fetchFeedsFromRemote', 'lsInboundDir'))
			->getMock();
		$coreModelMock->expects($this->once())
			->method('fetchFeedsFromRemote')
			->with($this->equalTo('/ItemMaster/'), $this->equalTo('ItemMaster*_*.xml'))
			->will($this->returnValue(null));
		$coreModelMock->expects($this->once())
			->method('lsInboundDir')
			->will($this->returnValue(array('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml')));
		$this->replaceByMock('model', 'eb2ccore/feed', $coreModelMock);

		$feed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			array('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml'),
			$this->_reflectMethod($feed, '_fetchFiles')->invoke($feed, $eventTypeModel)
		);
	}

	/**
	 * Test _unifiedAllFiles method with the following assumptions when call with given of 4 known parameters
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Feed::_unifiedAllFiles method is expected to be called with the a given
	 *                mocked TrueAction_Eb2cProduct_Model_Feed_Item object as its first parameter, then an array to be merge as its second paraemeter
	 *                then an array of files as its third, then an event type as its fourth parameter and then an error file as its fifth parameters
	 *                last it is expected to return an array of file detail
	 * Expectation 2: mocking the class DateTime::getTimeStamp method and mocking TrueAction_Eb2cCore_Helper_Feed::getMessageDate method
	 *                return the mocked DateTime object
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Item::getFeedRemotePath
	 * @mock DateTime::getTimeStamp
	 * @mock TrueAction_Eb2cCore_Helper_Feed::getMessageDate
	 */
	public function testUnifiedAllFiles()
	{
		$eventTypeModel = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array('getFeedRemotePath'))
			->getMock();
		$eventTypeModel->expects($this->once())
			->method('getFeedRemotePath')
			->will($this->returnValue('/ItemMaster/'));

		$dateTimeMock = $this->getMockBuilder('DateTime')
			->setMethods(array('getTimeStamp'))
			->getMock();
		$dateTimeMock->expects($this->once())
			->method('getTimeStamp')
			->will($this->returnValue('2012-07-06 10:09:05'));

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getMessageDate'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getMessageDate')
			->with($this->equalTo('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml'))
			->will($this->returnValue($dateTimeMock));
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreHelperMock);

		$feed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			array(array(
				'local' => 'TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
				'remote' => '/ItemMaster/',
				'timestamp' => '2012-07-06 10:09:05',
				'type' => 'ItemMaster',
				'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
			)),
			$this->_reflectMethod($feed, '_unifiedAllFiles')->invoke(
				$feed, $eventTypeModel,
				array(),
				array('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml'),
				'ItemMaster',
				'/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
			)
		);
	}

	/**
	 * Test _getAllFeedFiles method with the following assumptions when invoked by this test
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Feed::_getAllFeedFiles method is expected to loop through
	 *                the class property TrueAction_Eb2cProduct_Model_Feed::_eventTypes, which is set to a known states
	 *                with the key eventtype (ItemMaster) which is then mapped to the feed_item model
	 * Expectation 2: a mock of class TrueAction_Eb2cProduct_Model_Feed_Item is returned when TrueAction_Eb2cProduct_Model_Feed::_getEventTypeModel
	 *                is called once, then calling the TrueAction_Eb2cProduct_Model_Feed::_fetchFiles method with the mock
	 *                TrueAction_Eb2cProduct_Model_Feed_Item object pass as parameter will return an array of files
	 * Expectation 3: the TrueAction_Eb2cProduct_Helper_Data::buildFileName method is expected to return error file name
	 * Expectation 4: the method TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile is expected to be given the error file name
	 *                and the method TrueAction_Eb2cProduct_Model_Error_Confirmations::initFeed is expected to be given the event type
	 * Expectation 5: the method TrueAction_Eb2cProduct_Model_Feed::_unifiedAllFiles is expected to be given the mocked TrueAction_Eb2cProduct_Model_Feed_Item object
	 *                as it first parameter, then array of files as its second, then array of file list as its third, then event type as its fourth and then the error file name
	 * Expectation 6: the method TrueAction_Eb2cProduct_Model_Feed::_compareFeedFiles is never expected to be called because this test is expected to have a feed files array
	 *                with only has one element
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Item
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_getEventTypeModel
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_fetchFiles
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_unifiedAllFiles
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_compareFeedFiles
	 * @mock TrueAction_Eb2cProduct_Helper_Data::buildFileName
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::initFeed
	 */
	public function testGetAllFeedFiles()
	{
		$eventTypeModel = $this->getModelMockBuilder('eb2cproduct/feed_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('buildFileName'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('buildFileName')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue('TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', 'initFeed'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('loadFile')
			->with($this->equalTo('TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('initFeed')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$feed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_getEventTypeModel', '_fetchFiles', '_unifiedAllFiles', '_compareFeedFiles'))
			->getMock();
		$feed->expects($this->once())
			->method('_getEventTypeModel')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($eventTypeModel));
		$feed->expects($this->once())
			->method('_fetchFiles')
			->with($this->equalTo($eventTypeModel))
			->will($this->returnValue(array('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml')));
		$feed->expects($this->once())
			->method('_unifiedAllFiles')
			->with(
				$this->equalTo($eventTypeModel),
				$this->equalTo(array()),
				$this->equalTo(array('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml')),
				$this->equalTo('ItemMaster'),
				$this->equalTo('TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml')
			)
			->will($this->returnValue(array(array(
				'local' => 'TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
				'remote' => '/ItemMaster/',
				'timestamp' => '2012-07-06 10:09:05',
				'type' => 'ItemMaster',
				'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
			))));
		$feed->expects($this->never())
			->method('_compareFeedFiles')
			->will($this->returnValue(0));

		// class property _eventTypes to a known state
		$this->_reflectProperty($feed, '_eventTypes')->setValue($feed, array('ItemMaster' => 'feed_item',));

		$this->assertSame(
			array(array(
				'local' => 'TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
				'remote' => '/ItemMaster/',
				'timestamp' => '2012-07-06 10:09:05',
				'type' => 'ItemMaster',
				'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
			)),
			$this->_reflectMethod($feed, '_getAllFeedFiles')->invoke($feed)
		);
	}

	/**
	 * Test processFeeds method with the following assumptions when invoked by this test
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Feed::processFeeds method when invoked by this test
	 *                is expected to call TrueAction_Eb2cProduct_Model_Feed::_getAllFeedFiles which will return an array of
	 *                file detail arrays, in which this result will be loop through to the event type via magic by calling
	 *                TrueAction_Eb2cProduct_Model_Feed::setFeedEventType method, and then calling the processFile method
	 *                and then calling the archiveFeed method
	 * Expectation 2: after the loop the TrueAction_Eb2cProduct_Model_Feed_Cleaner::cleanAllProducts method get call once,
	 *                then the two events (product_feed_processing_complete, product_feed_complete_error_confirmation) get dispatch
	 *                and the return value from the processFeeds will be the number of processed feed files
	 * Expectation 3: methods TrueAction_Eb2cProduct_Model_Feed_Cleaner::cleanAllProducts, and TrueAction_Eb2cCore_Model_Indexer::reindexAll
	 *                get invoked when the event (product_feed_processing_complete) get dispatch
	 * Expectation 4: these methods TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile, close, transferFile and archive
	 *                get invoked when the event (product_feed_complete_error_confirmation) get dispatched
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_getAllFeedFiles
	 * @mock TrueAction_Eb2cProduct_Model_Feed::setFeedEventType
	 * @mock TrueAction_Eb2cProduct_Model_Feed::processFile
	 * @mock TrueAction_Eb2cProduct_Model_Feed::archiveFeed
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Cleaner::cleanAllProducts
	 * @mock TrueAction_Eb2cCore_Model_Indexer::reindexAll
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::close
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::transferFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::archive
	 */
	public function testProcessFeeds()
	{
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', 'close', 'transferFile', 'archive'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('loadFile')
			->with($this->equalTo('/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('close')
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('transferFile')
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('archive')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$cleanerModelMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->disableOriginalConstructor()
			->setMethods(array('cleanAllProducts'))
			->getMock();
		$cleanerModelMock->expects($this->once())
			->method('cleanAllProducts')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $cleanerModelMock);

		$coreIndexerModelMock = $this->getModelMockBuilder('eb2ccore/indexer')
			->disableOriginalConstructor()
			->setMethods(array('reindexAll'))
			->getMock();
		$coreIndexerModelMock->expects($this->once())
			->method('reindexAll')
			->will($this->returnValue(null));
		$this->replaceByMock('model', 'eb2ccore/indexer', $coreIndexerModelMock);

		$fileDetail = array(
			'local' => 'TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
			'remote' => '/ItemMaster/',
			'timestamp' => '2012-07-06 10:09:05',
			'type' => 'ItemMaster',
			'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
		);
		$feed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_getAllFeedFiles', 'setFeedEventType', 'processFile', 'archiveFeed'))
			->getMock();
		$feed->expects($this->once())
			->method('_getAllFeedFiles')
			->will($this->returnValue(array($fileDetail)));
		$feed->expects($this->once())
			->method('setFeedEventType')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnSelf());
		$feed->expects($this->once())
			->method('processFile')
			->with($this->equalTo($fileDetail))
			->will($this->returnValue(null));
		$feed->expects($this->once())
			->method('archiveFeed')
			->with(
				$this->equalTo('TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml'),
				$this->equalTo('/ItemMaster/')
			)
			->will($this->returnSelf());

		$this->assertSame(1, $feed->processFeeds());

		$this->assertEventDispatched('product_feed_processing_complete');
		$this->assertEventDispatched('product_feed_complete_error_confirmation');
	}

	/**
	 * Test processDom method with the following assumptions when invoked by this test
	 * Expectation 1: the TrueAction_Eb2cProduct_Model_Feed::processFeeds method when invoked by this test
	 *                is expected to call TrueAction_Eb2cProduct_Model_Feed::_getAllFeedFiles which will return an array of
	 *                file detail arrays, in which this result will be loop through to the event type via magic by calling
	 *                TrueAction_Eb2cProduct_Model_Feed::setFeedEventType method, and then calling the processFile method
	 *                and then calling the archiveFeed method
	 * Expectation 2: after the loop the TrueAction_Eb2cProduct_Model_Feed_Cleaner::cleanAllProducts method get call once,
	 *                then the two events (product_feed_processing_complete, product_feed_complete_error_confirmation) get dispatch
	 *                and the return value from the processFeeds will be the number of processed feed files
	 * Expectation 3: methods TrueAction_Eb2cProduct_Model_Feed_Cleaner::cleanAllProducts, and TrueAction_Eb2cCore_Model_Indexer::reindexAll
	 *                get invoked when the event (product_feed_processing_complete) get dispatch
	 * Expectation 4: these methods TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile, close, transferFile and archive
	 *                get invoked when the event (product_feed_complete_error_confirmation) get dispatched
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_getAllFeedFiles
	 * @mock TrueAction_Eb2cProduct_Model_Feed::setFeedEventType
	 * @mock TrueAction_Eb2cProduct_Model_Feed::processFile
	 * @mock TrueAction_Eb2cProduct_Model_Feed::archiveFeed
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Cleaner::cleanAllProducts
	 * @mock TrueAction_Eb2cCore_Model_Indexer::reindexAll
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::close
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::transferFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::archive
	 */
	public function testProcessDom()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-2BCEC162</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
		);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('process'))
			->getMock();
		$fileModelMock->expects($this->once())
			->method('process')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_file', $fileModelMock);
		$feed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_construct'))
			->getMock();
		$feed->expects($this->never())
			->method('_construct')
			->will($this->returnvalue(null));
		$this->assertSame($feed, $feed->processDom($doc, array(
			'local' => 'TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
			'remote' => '/ItemMaster/',
			'timestamp' => '2012-07-06 10:09:05',
			'type' => 'ItemMaster',
			'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
		)));
	}

	/**
	 * Test _compareFeedFiles method with the following assumptions when invoked by this test
	 * Expectation 1: this test is testing the different set of inputs yielding different expected output
	 *                verify comparing to feed file entries will yield the correct result
	 * Expectation 2: this test is mainly dependent on the known state of the class property
	 *                TrueAction_Eb2cProduct_Model_Feed::_eventTypes, the test begin by setting
	 *                this property to a known state of an array of key value map
	 */
	public function testCompareFeedFiles()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypes')->setValue($feedModelMock, array(
			'ItemMaster' => 'feed_item',
			'Content' => 'feed_content',
			'Price' => 'feed_pricing',
			'iShip' => 'feed_iship',
		));

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
	 * Test _getEventTypeModel method with the following assumptions when invoked by this test
	 * Expectation 1: this set first set the class property TrueAction_Eb2cProduct_Model_Feed::_eventTypes
	 *                to a known state of key value map and then loop through the key of this arrays
	 *                to replace by mock the different feed event specific classes and then asserted
	 *                that they will be the same as the return value when the test method
	 *                TrueAction_Eb2cProduct_Model_Feed::_getEventTypeModel is invoked for each eventType
	 *                key
	 */
	public function testGetEventTypeModel()
	{
		$eventTypes = array(
			'ItemMaster' => 'feed_item',
			'Content' => 'feed_content',
			'Price' => 'feed_pricing',
			'iShip' => 'feed_iship',
		);
		$feedModelMock = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedModelMock, '_eventTypes')->setValue($feedModelMock, $eventTypes);
		foreach (array_keys($eventTypes) as $et) {
			$mock = $this->getModelMockBuilder(sprintf('eb2cproduct/%s', $eventTypes[$et]))
				->disableOriginalConstructor()
				->setMethods(array())
				->getMock();
			$this->replaceByMock('model', sprintf('eb2cproduct/%s', $eventTypes[$et]), $mock);
			$this->assertSame($mock, $this->_reflectMethod($feedModelMock, '_getEventTypeModel')->invoke($feedModelMock, $et));
		}
	}

}
