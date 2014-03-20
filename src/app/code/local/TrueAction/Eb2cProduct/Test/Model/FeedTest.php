<?php
class TrueAction_Eb2cProduct_Test_Model_FeedTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function setUp()
	{
		parent::setUp();
		Mage::app()->disableEvents();
	}
	public function tearDown()
	{
		parent::tearDown();
		Mage::app()->enableEvents();
	}
	/**
	 * The protected construct method should set up an array of core feed models,
	 * each loaded with the config data for one of the feed types handled by this
	 * model. Using those core feed models, it should then also create an array
	 * of event types used when sorting the feed files.
	 * @test
	 */
	public function testConstruct()
	{
		$eventType = 'SomeEvent';
		$configKeys = array('itemFeed');
		$config = $this->buildCoreConfigRegistry(array(
			'itemFeed' => array('event_type' => $eventType, 'local_directory' => 'local'),
		));
		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$prodFeed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->getMock();

		// setup the feed config keys used by the product feed model so only some
		// expected subset of the feed types are worked with
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$prodFeed,
			'_feedConfigKeys',
			$configKeys
		);

		$prodHelper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($config));
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getEventType'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$coreFeed->expects($this->once())
			->method('getEventType')
			->will($this->returnValue($eventType));

		EcomDev_Utils_Reflection::invokeRestrictedMethod($prodFeed, '_construct');
		$this->assertSame(
			array($eventType),
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($prodFeed, '_eventTypes')
		);
		$this->assertSame(
			array($coreFeed),
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($prodFeed, '_coreFeedTypes')
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
	 * @test
	 */
	public function testUnifiedAllFiles()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->getMock();
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getMessageDate'))
			->getMock();
		$dateTimeMock = $this->getMockBuilder('DateTime')
			->setMethods(array('getTimeStamp'))
			->getMock();

		$prodFeed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreHelperMock);

		// details about the file(s) being added to the current list
		$localFile = '/Mage/var/local/file_two.xml';
		$fileTime = '2012-07-06 10:07:05';
		$errorFile = 'error_file_two.xml';

		$coreHelperMock->expects($this->once())
			->method('getMessageDate')
			->with($this->equalTo($localFile))
			->will($this->returnValue($dateTimeMock));
		$dateTimeMock->expects($this->once())
			->method('getTimeStamp')
			->will($this->returnValue($fileTime));

		// list of files already retrieved
		$currentFiles = array(array(
			'local_file' => '/Mage/var/local/file_one.xml',
			'timestamp' => '2012-03-02 10:07:03',
			'core_feed' => $coreFeed,
			'error_file' => 'error_file.xml'
		));
		$mergedFileList = array(
			$currentFiles[0],
			array(
				'local_file' => $localFile, 'timestamp' => $fileTime,
				'core_feed' => $coreFeed, 'error_file' => $errorFile,
			)
		);
		$this->assertSame(
			$mergedFileList,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$prodFeed,
				'_unifiedAllFiles',
				array(
					$currentFiles,
					array($localFile),
					$coreFeed,
					$errorFile,
				)
			)
		);
	}

	/**
	 * Get files to process should return an array of file details for all files
	 * that should be processed by the product feed model. A list of files for
	 * each type of feed (item, content, etc.) should be retrieved using each
	 * of the core feed models loaded during construction. The maps of file
	 * details should all be created using the _unifiedAllFiles method to create
	 * the maps and merge them with any previously created file details.
	 * The list of file details should finally be sorted, using the
	 * _compareFeedFiles method.
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_unifiedAllFiles
	 * @mock TrueAction_Eb2cProduct_Model_Feed::_compareFeedFiles
	 * @mock TrueAction_Eb2cProduct_Helper_Data::buildFileName
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::loadFile
	 * @mock TrueAction_Eb2cProduct_Model_Error_Confirmations::initFeed
	 * @test
	 */
	public function testGetFilesToProcess()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('lsLocalDirectory', 'getEventType'))
			->getMock();
		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('buildFileName'));
		$errorConfirmations = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', 'initFeed'))
			->getMock();
		$prodFeed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_unifiedAllFiles', '_compareFeedFiles'))
			->getMock();

		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $errorConfirmations);
		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$localFile = '/Mage/var/local/file.xml';
		$eventType = 'SomeEvent';
		$errorFile = 'error_file.xml';
		$fileDetails = array(
			'local_file' => $localFile, 'timestamp' => '2014-03-02 11:11:11',
			'core_feed' => $coreFeed, 'error_file' => $errorFile,
		);

		$coreFeed->expects($this->once())
			->method('lsLocalDirectory')
			->will($this->returnValue(array($localFile)));
		$coreFeed->expects($this->once())
			->method('getEventType')
			->will($this->returnValue($eventType));
		$prodHelper->expects($this->once())
			->method('buildFileName')
			->with($this->identicalTo($eventType))
			->will($this->returnValue($errorFile));
		$errorConfirmations->expects($this->once())
			->method('loadFile')
			->with($this->identicalTo($errorFile))
			->will($this->returnSelf());
		$errorConfirmations->expects($this->once())
			->method('initFeed')
			->with($this->identicalTo($eventType))
			->will($this->returnSelf());
		$prodFeed->expects($this->once())
			->method('_unifiedAllFiles')
			->with(
				$this->identicalTo(array()),
				$this->identicalTo(array($localFile)),
				$this->identicalTo($coreFeed),
				$this->identicalTo($errorFile)
			)
			->will($this->returnValue(array($fileDetails)));
		$prodFeed->expects($this->any())
			->method('_compareFeedFiles')
			->will($this->returnValue(0));

		// set the core feed models to a single known core feed
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($prodFeed, '_coreFeedTypes', array($coreFeed));
		$this->assertSame(
			array($fileDetails),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$prodFeed, '_getFilesToProcess'
			)
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
		$cleanerModelMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->disableOriginalConstructor()
			->setMethods(array('cleanAllProducts'))
			->getMock();
		$cleanerModelMock->expects($this->once())
			->method('cleanAllProducts')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $cleanerModelMock);

		$prodFeed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_getFilesToProcess', 'processFile'))
			->getMock();

		$filesToProcess = array(
			array('local_file' => '/Mage/var/local/file.xml'),
		);

		$prodFeed->expects($this->once())
			->method('_getFilesToProcess')
			->will($this->returnValue($filesToProcess));
		$prodFeed->expects($this->once())
			->method('processFile')
			->with($this->identicalTo($filesToProcess[0]))
			->will($this->returnSelf());

		$this->assertSame(1, $prodFeed->processFeeds());
	}
	/**
	 * When feeds fail to process, the cleaner should not be triggered, the error
	 * message should be logged as a warning and the method should report back
	 * that 0 files were processed.
	 * @test
	 */
	public function testProcessFeedsFailure()
	{
		$cleanerModelMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->disableOriginalConstructor()
			->setMethods(array('cleanAllProducts'))
			->getMock();
		// When no feeds are processed, cleaner shouldn't be triggered
		$cleanerModelMock->expects($this->never())
			->method('cleanAllProducts');
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $cleanerModelMock);

		$prodFeed = $this->getModelMockBuilder('eb2cproduct/feed')
			->disableOriginalConstructor()
			->setMethods(array('_getFilesToProcess', 'processFile'))
			->getMock();
		$logger = $this->getHelperMockBuilder('trueaction_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logWarn'))
			->getMock();

		$this->replaceByMock('helper', 'trueaction_magelog', $logger);

		$filesToProcess = array(
			array('local_file' => '/Mage/var/local/file.xml'),
		);

		$prodFeed->expects($this->once())
			->method('_getFilesToProcess')
			->will($this->returnValue($filesToProcess));
		$prodFeed->expects($this->once())
			->method('processFile')
			->with($this->identicalTo($filesToProcess[0]))
			->will($this->throwException(new Mage_Core_Exception()));
		$logger->expects($this->once())
			->method('logWarn')
			->will($this->returnSelf());

		$this->assertSame(0, $prodFeed->processFeeds());
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
			'local_file' => 'TrueAction/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
			'core_feed' => 'core feed mock',
			'timestamp' => '2012-07-06 10:09:05',
			'error_file' => '/TrueAction/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
		)));
	}
}
