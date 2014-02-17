<?php
/**
 * Feed Abstract Test
 *
 */
class TrueAction_Eb2cCore_Test_Model_Feed_AbstractTest extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT     = 'root';
	const CLASS_TESTED = 'TrueAction_Eb2cCore_Model_Feed_Abstract';

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
	 * Test Feed Abstract
	 *
	 * @test
	 */
	public function testIsInstanceOf()
	{
		// kill core feed model's constructor so as to not inadvertently hit the file system
		$this->replaceByMock(
			'model',
			'eb2ccore/feed',
			$this->getModelMockBuilder('eb2ccore/feed')->disableOriginalConstructor()->getMock()
		);
		$model = $this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array('param' => $this->_getDummyValueMap())
		);
		$this->assertInstanceOf(self::CLASS_TESTED, $model);
	}

	/**
	 * Test concrete methods
	 *
	 * @test
	 * @loadFixture abstractTestVfs
	 */
	public function testConcreteMethods()
	{
		$vfs = $this->getFixture()->getVfs();
		$vfsDump = $vfs->dump();
		$dummyFiles = array();
		foreach(array_keys($vfsDump[self::VFS_ROOT]['inbound']) as $filename) {
			$dummyFiles[] = array('text' => $filename, 'filetype' => 'xml');
		}

		// Mock the Varien_Io_File object, this is our FsTool for testing purposes
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
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('checkAndCreateFolder')
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('mv')
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('ls')
			->will($this->returnValue($dummyFiles));
		$mockFsTool
			->expects($this->any())
			->method('pwd')
			->will($this->returnValue($vfs->url(self::VFS_ROOT . '/inbound')));
		$mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->will($this->returnSelf());
		$mockFsTool
			->expects($this->any())
			->method('open')
			->will($this->returnValue(true));

		// The transport protocol is mocked - we just pretend we got files
		$mockSftp = $this
			->getModelMockBuilder('filetransfer/protocol_types_sftp')
			->disableOriginalConstructor()
			->setMethods(array( 'getAllFiles', 'deleteFile'))
			->getMock();

		$mockSftp
			->expects($this->any())
			->method('getAllFiles')
			->will($this->returnValue(true));
		$mockSftp
			->expects($this->any())
			->method('deleteFile')
			->will($this->returnValue(true));

		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$mockSftp
		);

		// Mock the client_id, which exercises validateHeader
		$configValuePairs = array (
			'clientId' => 'JUST_TESTING',
		);

		// Build the array in the format returnValueMap wants
		$valueMap = array();
		foreach( $configValuePairs as $configPath => $configValue ) {
			$valueMap[] = array($configPath, $configValue);
		}

		$mockConfig = $this->getModelMock('eb2ccore/config_registry', array('__get'));
		$mockConfig->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($valueMap));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $mockConfig);

		$model = $this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array( 'param' => array(
				'feed_config'       => 'dummy_config',
				'feed_event_type'   => 'OrderStatus',
				'feed_file_pattern' => 'dummy_pattern',
				'feed_local_path'   => 'inbound',
				'feed_remote_path'  => 'dummy_path',
				'fs_tool'           => $mockFsTool,
			)
			)
		);

		$model->processFeeds();

		$feedModelProp = new ReflectionProperty($model, '_coreFeed');
		$feedModelProp->setAccessible(true);
		$coreFeed = $feedModelProp->getValue($model);
		$this->assertSame(Mage::getBaseDir('var') . DS . 'inbound', $coreFeed->getBaseDir());
	}

	/**
	 * Success and fail status of loading xml into a dom document and processing that dom document.
	 * @return array Expectations for testFeedProcessing
	 */
	public function providerFeedProcessing()
	{
		return array(
			array(true, true),
			array(true, false),
			array(false, true),
		);
	}

	/**
	 * Test processing of the feeds, success and failure
	 * @param  boolean $domLoadSuccess Should the XML be loaded succesfully
	 * @param  boolean $processSuccess Should processing of the DOM be successful
	 * @test
	 * @dataProvider providerFeedProcessing
	 * @loadFixture
	 */
	public function testFeedProcessing($domLoadSuccess, $processSuccess)
	{
		$fileName = 'dummy_file_name.xml';
		$remotePath = 'remove_dummy_path';
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

		$domStub = $this->_domStub(array($fileName => ($domLoadSuccess ? $domLoadSuccess : new Mage_Core_Exception),));

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
		$model = $this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array('param' => array(
				'feed_config'       => 'dummy_config',
				'feed_event_type'   => 'OrderStatus',
				'feed_file_pattern' => $filePattern,
				'feed_local_path'   => 'inbound',
				'feed_file_pattern' => $filePattern,
				'feed_remote_path'  => $remotePath,
				'fs_tool'           => $mockFs,
			))
		);
		// if dom document was loaded successfully, the message should be validated and processed
		if ($domLoadSuccess) {
			$feedHelper = $this->getHelperMock('eb2ccore/feed', array('validateHeader'));
			$feedHelper->expects($this->once())
				->method('validateHeader')
				->with($this->identicalTo($domStub), $this->identicalTo('OrderStatus'))
				->will($this->returnValue(true));
			$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

			$processReturn = $this->returnValue(null);
			if (!$processSuccess) {
				$processReturn = $this->throwException(new Mage_Core_Exception);
			}
			$model->expects($this->once())
				->method('processDom')
				->with($this->identicalTo($domStub), $this->isType('array'))
				->will($processReturn);
		}

		// dom loading exceptions are caught and consider the file as processed
		$expectedProcessed = !$processSuccess ? 0 : 1;
		$this->assertSame($expectedProcessed, $model->processFeeds());
	}

	/**
	 * Provider for testFileArchiving
	 * @return array should remote file be deleted
	 */
	public function providerFileArchive()
	{
		return array(
			array(true),
			array(false),
		);
	}

	/**
	 * Test archival of feed files, should always move to the archive dir
	 * but only delete from remote if configured to.
	 * @param  boolean $deleteRemoteConfig Should the remote file be delted.
	 * @test
	 * @dataProvider providerFileArchive
	 */
	public function testFileArchiving($deleteRemoteConfig)
	{
		$fileName = 'dummy_file_name.xml';
		$filePath = 'inbound' . DS . $fileName;
		$remotePath = 'remove_dummy_path';
		$filePattern = 'Oh*My*Glob';

		$mockConfig = $this->getModelMock('eb2ccore/config_registry', array('__get'));
		$mockConfig->expects($this->any())
			->method('__get')
			->will($this->returnValueMap(array(array('deleteRemoteFeedFiles', $deleteRemoteConfig))));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mockConfig);

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('mvToArchiveDir', 'removeFromRemote',))
			->getMock();
		$coreFeed->expects($this->once())
			->method('mvToArchiveDir')
			->with($this->identicalTo($filePath));
		if ($deleteRemoteConfig) {
			$coreFeed->expects($this->once())
				->method('removeFromRemote')
				->with($this->identicalTo($remotePath, $fileName));
		} else {
			$coreFeed->expects($this->never())
				->method('removeFromRemote');
		}
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$mockFs = $this->getMock('Varien_Io_File', array('setAllowCreateFolders', 'open'));
		$mockFs->expects($this->any())
			->method('setAllowCreateFolders')
			->will($this->returnSelf());
		$mockFs->expects($this->any())
			->method('open')
			->will($this->returnSelf());

		$this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array('param' => array(
				'feed_config'       => 'dummy_config',
				'feed_event_type'   => 'OrderStatus',
				'feed_file_pattern' => $filePattern,
				'feed_local_path'   => 'inbound',
				'feed_file_pattern' => $filePattern,
				'feed_remote_path'  => $remotePath,
				'fs_tool'           => $mockFs,
			))
		)->archiveFeed($filePath);
	}

	/**
	 * Return array of complete dummy values required to instantiate the abstract class
	 */
	private function _getDummyValueMap()
	{
		return array(
			'feed_config'       => 'dummy_config',
			'feed_event_type'   => 'dummy_event_type',
			'feed_file_pattern' => 'dummy_pattern',
			'feed_local_path'   => 'dummy_local_path',
			'feed_remote_path'  => 'dummy_local_path',
		);
	}

	/**
	 * Test Feed Abstract throws Exception without feed_remote_path defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testFeedRemotePathException()
	{
		$args = $this->_getDummyValueMap();
		unset($args['feed_remote_path']);
		$this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array(
				'param' => $args
			)
		);
	}

	/**
	 * Test Feed Abstract throws Exception without feed_local_path defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testFeedLocalPathException()
	{
		$args = $this->_getDummyValueMap();
		unset($args['feed_local_path']);
		$this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array(
				'param' => $args
			)
		);
	}

	/**
	 * Test Feed Abstract throws Exception without feed_file_pattern defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testFeedFilePatternException()
	{
		$args = $this->_getDummyValueMap();
		unset($args['feed_file_pattern']);
		$this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array(
				'param' => $args
			)
		);
	}

	/**
	 * Test Feed Abstract throws Exception without feed_config defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testFeedConfigException()
	{
		$args = $this->_getDummyValueMap();
		unset($args['feed_config']);
		$this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array(
				'param' => $args
			)
		);
	}

	/**
	 * Test Feed Abstract throws Exception without feed_event_type defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testFeedEventTypeException()
	{
		$args = $this->_getDummyValueMap();
		unset($args['feed_event_type']);
		$this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array(
				'param' => $args
			)
		);
	}
}
