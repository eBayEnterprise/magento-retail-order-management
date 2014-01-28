<?php
class TrueAction_Eb2cCore_Test_Model_FeedTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test _construct method
	 * @test
	 */
	public function testConstruct()
	{
		$fileMock = $this->getMock('Varien_Io_File', array('setAllowCreateFolders', 'open'));
		$fileMock->expects($this->once())
			->method('setAllowCreateFolders')
			->with($this->equalTo(true))
			->will($this->returnSelf());
		$fileMock->expects($this->once())
			->method('open')
			->will($this->returnSelf());

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('hasFsTool', 'setFsTool', 'hasBaseDir', 'setUpDirs', 'getFsTool'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('hasFsTool')
			->will($this->returnValue(false));
		$feedModelMock->expects($this->once())
			->method('setFsTool')
			->with($this->isInstanceOf('Varien_Io_File'))
			->will($this->returnSelf());
		$feedModelMock->expects($this->once())
			->method('hasBaseDir')
			->will($this->returnValue(true));
		$feedModelMock->expects($this->once())
			->method('setUpDirs')
			->will($this->returnValue(null));
		$feedModelMock->expects($this->once())
			->method('getFsTool')
			->will($this->returnValue($fileMock));

		$this->_reflectMethod($feedModelMock, '_construct')->invoke($feedModelMock);
	}

	/**
	 * Test _setCheckAndCreateDir method
	 * @test
	 */
	public function testSetCheckAndCreateDir()
	{
		$fileMock = $this->getMock('Varien_Io_File', array('checkAndCreateFolder'));
		$fileMock->expects($this->once())
			->method('checkAndCreateFolder')
			->with($this->equalTo('TrueAction/Feed/ItemMaster/'))
			->will($this->returnValue(true));

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getFsTool'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('getFsTool')
			->will($this->returnValue($fileMock));

		$this->assertSame(
			true,
			$this->_reflectMethod($feedModelMock, '_setCheckAndCreateDir')
			->invoke($feedModelMock, 'TrueAction/Feed/ItemMaster/')
		);
	}

	/**
	 * Test _remoteCall method
	 * @test
	 */
	public function testRemoteCall()
	{
		$fileTransferMock = $this->getMock('TrueAction_FileTransfer_Helper_Data', array('getAllFiles'));
		$fileTransferMock->expects($this->once())
			->method('getAllFiles')
			->with(
				$this->equalTo('/var/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound'),
				$this->equalTo('/Inbox/'),
				$this->equalTo('ItemMaster*.xml'),
				$this->equalTo('eb2ccore/feed')
			)
			->will($this->returnValue(true));

		$callable = array($fileTransferMock, 'getAllFiles');
		$argArray = array(
			'/var/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound',
			'/Inbox/',
			'ItemMaster*.xml',
			'eb2ccore/feed',
		);

		$registryFeedModelMock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('setStore', 'addConfigModel'))
			->getMock();
		$registryFeedModelMock->expects($this->once())
			->method('setStore')
			->with($this->equalTo(null))
			->will($this->returnSelf());
		$registryFeedModelMock->expects($this->once())
			->method('addConfigModel')
			->with($this->isInstanceOf('TrueAction_Eb2cCore_Model_Config'))
			->will($this->returnValue((object) array(
				'feedFetchConnectAttempts' => 1,
				'feedFetchRetryTimer' => 0.1,
			)));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $registryFeedModelMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectMethod($feedModelMock, '_remoteCall')->invoke($feedModelMock, $callable, $argArray);
	}

	/**
	 * Test _remoteCall method, where file transfer throw connection exception
	 * @test
	 */
	public function testRemoteCallWithConnectionException()
	{
		$fileTransferMock = $this->getMock('TrueAction_FileTransfer_Helper_Data', array('getAllFiles'));
		$fileTransferMock->expects($this->exactly(2))
			->method('getAllFiles')
			->with(
				$this->equalTo('/var/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound'),
				$this->equalTo('/Inbox/'),
				$this->equalTo('ItemMaster*.xml'),
				$this->equalTo('eb2ccore/feed')
			)
			->will($this->throwException(
				new TrueAction_FileTransfer_Exception_Connection('UnitTest Simulate Throw Connection Exception on File Transfer getAllFiles')
			));

		$callable = array($fileTransferMock, 'getAllFiles');
		$argArray = array(
			'/var/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound',
			'/Inbox/',
			'ItemMaster*.xml',
			'eb2ccore/feed',
		);

		$registryFeedModelMock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('setStore', 'addConfigModel'))
			->getMock();
		$registryFeedModelMock->expects($this->once())
			->method('setStore')
			->with($this->equalTo(null))
			->will($this->returnSelf());
		$registryFeedModelMock->expects($this->once())
			->method('addConfigModel')
			->with($this->isInstanceOf('TrueAction_Eb2cCore_Model_Config'))
			->will($this->returnValue((object) array(
				'feedFetchConnectAttempts' => 2,
				'feedFetchRetryTimer' => 0.1,
			)));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $registryFeedModelMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectMethod($feedModelMock, '_remoteCall')->invoke($feedModelMock, $callable, $argArray);
	}

	/**
	 * Test _remoteCall method, where file transfer throw exception
	 * @test
	 */
	public function testRemoteCallWithException()
	{
		$fileTransferMock = $this->getMock('TrueAction_FileTransfer_Helper_Data', array('getAllFiles'));
		$fileTransferMock->expects($this->once())
			->method('getAllFiles')
			->with(
				$this->equalTo('/var/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound'),
				$this->equalTo('/Inbox/'),
				$this->equalTo('ItemMaster*.xml'),
				$this->equalTo('eb2ccore/feed')
			)
			->will($this->throwException(
				new Exception('UnitTest Simulate Throw Exception on File Transfer getAllFiles')
			));

		$callable = array($fileTransferMock, 'getAllFiles');
		$argArray = array(
			'/var/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound',
			'/Inbox/',
			'ItemMaster*.xml',
			'eb2ccore/feed',
		);

		$registryFeedModelMock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array('setStore', 'addConfigModel'))
			->getMock();
		$registryFeedModelMock->expects($this->once())
			->method('setStore')
			->with($this->equalTo(null))
			->will($this->returnSelf());
		$registryFeedModelMock->expects($this->once())
			->method('addConfigModel')
			->with($this->isInstanceOf('TrueAction_Eb2cCore_Model_Config'))
			->will($this->returnValue((object) array(
				'feedFetchConnectAttempts' => 1,
				'feedFetchRetryTimer' => 0.1,
			)));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $registryFeedModelMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectMethod($feedModelMock, '_remoteCall')->invoke($feedModelMock, $callable, $argArray);
	}

	/**
	 * Test setUpDirs method
	 * @test
	 */
	public function testSetUpDirs()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getBaseDir', 'addData', 'getInboundPath', 'getOutboundPath', 'getArchivePath', '_setCheckAndCreateDir'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('getBaseDir')
			->will($this->returnValue('/TrueAction/Eb2c/Feed/Product/ItemMaster/'));
		$feedModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'inbound_path' => '/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound',
				'outbound_path' => '/TrueAction/Eb2c/Feed/Product/ItemMaster//outbound',
				'archive_path' => '/TrueAction/Eb2c/Feed/Product/ItemMaster//archive'
			)))
			->will($this->returnSelf());
		$feedModelMock->expects($this->once())
			->method('getInboundPath')
			->will($this->returnValue('/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound'));
		$feedModelMock->expects($this->once())
			->method('getOutboundPath')
			->will($this->returnValue('/TrueAction/Eb2c/Feed/Product/ItemMaster//outbound'));
		$feedModelMock->expects($this->once())
			->method('getArchivePath')
			->will($this->returnValue('/TrueAction/Eb2c/Feed/Product/ItemMaster//archive'));
		$feedModelMock->expects($this->at(3))
			->method('_setCheckAndCreateDir')
			->with($this->equalTo('/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound'))
			->will($this->returnValue(true));
		$feedModelMock->expects($this->at(5))
			->method('_setCheckAndCreateDir')
			->with($this->equalTo('/TrueAction/Eb2c/Feed/Product/ItemMaster//outbound'))
			->will($this->returnValue(true));
		$feedModelMock->expects($this->at(7))
			->method('_setCheckAndCreateDir')
			->with($this->equalTo('/TrueAction/Eb2c/Feed/Product/ItemMaster//archive'))
			->will($this->returnValue(true));

		$feedModelMock->setUpDirs();
	}

	/**
	 * Test setUpDirs method, with exception throw
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testSetUpDirsWithException()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getBaseDir'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('getBaseDir')
			->will($this->returnValue(null));

		$feedModelMock->setUpDirs();
	}

	/**
	 * Test fetchFeedsFromRemote method
	 * @test
	 */
	public function testFetchFeedsFromRemote()
	{
		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('isValidFtpSettings'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('isValidFtpSettings')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore', $coreFeedHelperMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_remoteCall', 'getInboundPath', 'lsInboundDir', '_acknowledgeReceipt'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_remoteCall')
			->with($this->isType('array'), $this->equalTo(
				array(
					'/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound',
					'/Inbox/',
					'ItemMaster*.xml',
					'eb2ccore/feed'
				)))
			->will($this->returnValue(true));
		$feedModelMock->expects($this->once())
			->method('getInboundPath')
			->will($this->returnValue('/TrueAction/Eb2c/Feed/Product/ItemMaster//inbound'));
		$feedModelMock->expects($this->once())
			->method('_acknowledgeReceipt')
			->will($this->returnSelf());
		$feedModelMock->expects($this->once())
			->method('lsInboundDir')
			->will($this->returnValue(array('dummyfile.xml')));

		$feedModelMock->fetchFeedsFromRemote('/Inbox/', 'ItemMaster*.xml');
	}

	/**
	 * Test fetchFeedsFromRemote method, where ftp setting is invalid
	 * @test
	 */
	public function testFetchFeedsFromRemoteWithInvalidFtpSettings()
	{
		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('isValidFtpSettings'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('isValidFtpSettings')
			->will($this->returnValue(false));
		$this->replaceByMock('helper', 'eb2ccore', $coreFeedHelperMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_remoteCall', 'getInboundPath'))
			->getMock();

		$feedModelMock->fetchFeedsFromRemote('/Inbox/', 'ItemMaster*.xml');
	}

	/**
	 * Test removeFromRemote method
	 * @test
	 */
	public function testRemoveFromRemote()
	{
		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('isValidFtpSettings'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('isValidFtpSettings')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore', $coreFeedHelperMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_remoteCall'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_remoteCall')
			->with($this->isType('array'), $this->equalTo(
				array(
					'/Inbox//Sample-ItemMaster.xml',
					'eb2ccore/feed'
				)))
			->will($this->returnValue(true));

		$feedModelMock->removeFromRemote('/Inbox/', 'Sample-ItemMaster.xml');
	}

	/**
	 * Test removeFromRemote method, with invalid ftp setting
	 * @test
	 */
	public function testRemoveFromRemoteWithInvalidFtpSettings()
	{
		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('isValidFtpSettings'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('isValidFtpSettings')
			->will($this->returnValue(false));
		$this->replaceByMock('helper', 'eb2ccore', $coreFeedHelperMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_remoteCall'))
			->getMock();
		$feedModelMock->expects($this->never())
			->method('_remoteCall')
			->will($this->returnValue(true));

		$feedModelMock->removeFromRemote('/Inbox/', 'Sample-ItemMaster.xml');
	}

	/**
	 * Test lsInboundDir method
	 * @test
	 */
	public function testLsInboundDir()
	{
		$fileMock = $this->getMock('Varien_Io_File', array('cd', 'ls', 'pwd'));
		$fileMock->expects($this->once())
			->method('cd')
			->with($this->equalTo('TrueAction/Feed/ItemMaster/inbound'))
			->will($this->returnSelf());
		$fileMock->expects($this->once())
			->method('ls')
			->will($this->returnValue(array(
				array(
					'filetype' => 'xml',
					'text' => 'Sample1.xml'
				),
				array(
					'filetype' => 'xml',
					'text' => 'Sample2.xml'
				)
			)));
		$fileMock->expects($this->exactly(2))
			->method('pwd')
			->will($this->returnValue('TrueAction/Feed/ItemMaster/inbound'));

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getFsTool', 'getInboundPath'))
			->getMock();
		$feedModelMock->expects($this->exactly(4))
			->method('getFsTool')
			->will($this->returnValue($fileMock));
		$feedModelMock->expects($this->once())
			->method('getInboundPath')
			->will($this->returnValue('TrueAction/Feed/ItemMaster/inbound'));

		$files = $feedModelMock->lsInboundDir();

		$this->assertCount(2, $files);
		$this->assertSame('TrueAction/Feed/ItemMaster/inbound/Sample1.xml', $files[0]);
		$this->assertSame('TrueAction/Feed/ItemMaster/inbound/Sample2.xml', $files[1]);
	}

	/**
	 * Test _mvToDir method
	 * @test
	 */
	public function testMvToDir()
	{
		$fileMock = $this->getMock('Varien_Io_File', array('mv'));
		$fileMock->expects($this->once())
			->method('mv')
			->with(
				$this->equalTo('TrueAction/Feed/ItemMaster/inbound/Sample1.xml'),
				$this->equalTo('TrueAction/Feed/ItemMaster/archive/Sample1.xml')
			)
			->will($this->returnValue(true));

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getFsTool'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('getFsTool')
			->will($this->returnValue($fileMock));

		$this->assertSame(
			true,
			$this->_reflectMethod($feedModelMock, '_mvToDir')
			->invoke($feedModelMock, 'TrueAction/Feed/ItemMaster/inbound/Sample1.xml', 'TrueAction/Feed/ItemMaster/archive')
		);
	}

	/**
	 * Test mvToInboundDir method
	 * @test
	 */
	public function testMvToInboundDir()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_mvToDir', 'getInboundPath'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_mvToDir')
			->with($this->equalTo('feed/Sample1.xml'), $this->equalTo('TrueAction/Feed/ItemMaster/inbound'))
			->will($this->returnValue(true));
		$feedModelMock->expects($this->once())
			->method('getInboundPath')
			->will($this->returnValue('TrueAction/Feed/ItemMaster/inbound'));

		$this->assertSame(
			true,
			$feedModelMock->mvToInboundDir('feed/Sample1.xml')
		);
	}

	/**
	 * Test mvToOutboundDir method
	 * @test
	 */
	public function testMvToOutboundDir()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_mvToDir', 'getOutboundPath'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_mvToDir')
			->with($this->equalTo('feed/Sample1.xml'), $this->equalTo('TrueAction/Feed/ItemMaster/outbound'))
			->will($this->returnValue(true));
		$feedModelMock->expects($this->once())
			->method('getOutboundPath')
			->will($this->returnValue('TrueAction/Feed/ItemMaster/outbound'));

		$this->assertSame(
			true,
			$feedModelMock->mvToOutboundDir('feed/Sample1.xml')
		);
	}

	/**
	 * Test mvToArchiveDir method
	 * @test
	 */
	public function testMvToArchiveDir()
	{
		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_mvToDir', 'getArchivePath'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_mvToDir')
			->with($this->equalTo('feed/Sample1.xml'), $this->equalTo('TrueAction/Feed/ItemMaster/archive'))
			->will($this->returnValue(true));
		$feedModelMock->expects($this->once())
			->method('getArchivePath')
			->will($this->returnValue('TrueAction/Feed/ItemMaster/archive'));

		$this->assertSame(
			true,
			$feedModelMock->mvToArchiveDir('feed/Sample1.xml')
		);
	}

	/**
	 * Test make the base acknowledgement file name from config values
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function test_getBaseAckFileName()
	{
		$phonyFileName = 'utAck_utEventType_utClientId_utStoreId_utDate.xml';
		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			$phonyFileName,
			$this->_reflectMethod($feedModelMock, '_getBaseAckFileName')
				->invoke($feedModelMock, 'utEventType')
		);
	}

	/**
	 * Test that we'll thrown an exception with invalid dom
	 * @test
	 * @loadFixture loadAckSampleSetup.yaml
	 * @expectedException TrueAction_Eb2cCore_Exception
	 */
	public function test_validateAckDomFails()
	{
		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('schemaValidate'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('schemaValidate')
			->will($this->returnValue(false));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);
		$vfs     = $this->getFixture()->getVfs();
		$testDoc = Mage::helper('eb2ccore')->getNewDomDocument();

		$testDoc->load($vfs->url('sample/test.xml'));

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			'Galimatias_DoesntMatter_Expect_Exception_Before_This',
			$this->_reflectMethod($feedModelMock, '_validateAckDom')
				->invoke($feedModelMock, $testDoc)
		);
	}

	/**
	 * Test that we'll thrown an exception with invalid dom
	 * @test
	 * @loadFixture loadAckSampleSetup.yaml
	 */
	public function test_validateAckDomSucceeds()
	{
		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('schemaValidate'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('schemaValidate')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);
		$vfs     = $this->getFixture()->getVfs();
		$testDoc = Mage::helper('eb2ccore')->getNewDomDocument();

		$testDoc->load($vfs->url('sample/test.xml'));

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Feed',
			$this->_reflectMethod($feedModelMock, '_validateAckDom')
				->invoke($feedModelMock, $testDoc)
		);
	}

	/**
	 * Test that we'll successfully validate um valid dom
	 * @test
	 * @loadFixture loadAckSampleSetup.yaml
	 */
	public function test_acknowledgeReceipt()
	{
		$vfs      = $this->getFixture()->getVfs();
		$testFile = $vfs->url('sample/test.xml'); // (Seems nutty, but actually, we should be able to ack an ack O.o)

		$coreFeedHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('sendFile'))
			->getMock();
		$coreFeedHelperMock->expects($this->once())
			->method('sendFile')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2ccore', $coreFeedHelperMock);

		$feedModelMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_validateAckDom', 'getOutboundPath', '_getBaseAckFileName','mvToArchiveDir'))
			->getMock();
		$feedModelMock->expects($this->once())
			->method('_validateAckDom')
			->will($this->returnSelf());
		$feedModelMock->expects($this->once())
			->method('getOutboundPath')
			->will($this->returnValue($vfs->url('sample/outbound')));
		$feedModelMock->expects($this->once())
			->method('_getBaseAckFileName')
			->will($this->returnValue('testOutBound.xml'));
		$feedModelMock->expects($this->once())
			->method('mvToArchiveDir')
			->will($this->returnValue(true));

		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Feed',
			$this->_reflectMethod($feedModelMock, '_acknowledgeReceipt')
				->invoke($feedModelMock, $testFile)
		);
	}
}
