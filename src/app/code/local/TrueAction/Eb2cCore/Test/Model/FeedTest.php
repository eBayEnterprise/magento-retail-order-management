<?php
class TrueAction_Eb2cCore_Test_Model_FeedTest extends TrueAction_Eb2cCore_Test_Base
{
	const TESTBASE_DIR_NAME = 'testBase';
	protected $_vfs;
	protected $_mockFsTool;

	/**
	 * setUp virtual files/ directory structure
	 */
	public function setUp()
	{
		$this->_vfs = $this->getFixture()->getVfs();
		$this->_vfs->apply(
			array(
				self::TESTBASE_DIR_NAME => array (
					TrueAction_Eb2cCore_Model_Feed::INBOUND_DIR_NAME  => array(),
					TrueAction_Eb2cCore_Model_Feed::OUTBOUND_DIR_NAME => array(),
					TrueAction_Eb2cCore_Model_Feed::ARCHIVE_DIR_NAME  => array(),
				)
			)
		);

		// Mock the Varien_Io_File object, this is our FsTool for testing purposes
		$this->_mockFsTool = $this->getMock('Varien_Io_File', array(
			'cd',
			'checkAndCreateFolder',
			'ls',
			'mv',
			'pwd',
			'setAllowCreateFolders',
		));
		$this->_mockFsTool
			->expects($this->any())
			->method('cd')
			->with($this->stringContains($this->_vfs->url(self::TESTBASE_DIR_NAME)))
			->will($this->returnValue(true));
		$this->_mockFsTool
			->expects($this->any())
			->method('checkAndCreateFolder')
			->with($this->stringContains($this->_vfs->url(self::TESTBASE_DIR_NAME)))
			->will($this->returnValue(true));
		$this->_mockFsTool
			->expects($this->any())
			->method('mv')
			->with($this->identicalTo('foo'), $this->stringContains($this->_vfs->url(self::TESTBASE_DIR_NAME)))
			->will($this->returnValue(true));
		$this->_mockFsTool
			->expects($this->any())
			->method('ls')
			->will($this->returnValue(array(array('text' => 'sampleFile.xml', 'filetype' => 'xml'))));
		$this->_mockFsTool
			->expects($this->any())
			->method('pwd')
			->will($this->returnValue($this->_vfs->url('testBase' . DS . TrueAction_Eb2cCore_Model_Feed::INBOUND_DIR_NAME)));
		$this->_mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());
	}

	/**
	 * Exercises constructor that doesn't have fs_tool mocked nor a base_dir provided.
	 *
	 * @test
	 */
	public function testBareConstructor()
	{
		$feed = Mage::getModel('eb2ccore/feed'); // No args will ensure coverage
		$this->assertEmpty($feed->getInboundPath()); // All these paths should be empty, as no base was provided.
		$this->assertEmpty($feed->getOutboundPath());
		$this->assertEmpty($feed->getArchivePath());
	}

	/**
	 * Cover exception thrown when the setup function is called but no base had ever been defined
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 * @expectedExceptionMessage No base dir specified. Cannot set up dirs.
	 */
	public function testNoBaseConstructor()
	{
		$feed = Mage::getModel('eb2ccore/feed'); // No args will ensure coverage
		$feed->setUpDirs();
	}

	/**
	 * @large
	 * @test
	 */
	public function testFeedMethods()
	{
		// The transport protocol is mocked - we just pretend we got files
		$mockSftp = $this->getMock(
			'TrueAction_FileTransfer_Model_Protocol_Types_Sftp',
			array( 'getAllFiles')
		);

		$mockSftp
			->expects($this->any())
			->method('getAllFiles')
			->will($this->returnValue(true));

		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$mockSftp
		);

		$feed = Mage::getModel('eb2ccore/feed', array(
			'fs_tool' => $this->_mockFsTool,
			'base_dir' => $this->_vfs->url(self::TESTBASE_DIR_NAME),
		));

		foreach ($feed->lsInboundDir() as $aFilePath) {
		}

		$this->assertFileExists($feed->getInboundPath());
		$this->assertFileExists($feed->getOutboundPath());
		$this->assertFileExists($feed->getArchivePath());

		$feed->mvToOutboundDir('foo');
		$feed->mvToArchiveDir('foo');
		$feed->mvToInboundDir('foo');
	}

	/**
	 * Test providing coverage for remote connection exception handling
	 * 
	 * @large
	 * @test
	 */
	public function testFetchFeedsFromRemoteConnectionException()
	{
		$mockSftp = $this->getMock(
			'TrueAction_FileTransfer_Model_Protocol_Types_Sftp',
			array( 'getAllFiles')
		);

		$mockSftp
			->expects($this->any())
			->method('getAllFiles')
			->will($this->throwException( new TrueAction_FileTransfer_Exception_Connection('connection exception error')));

		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$mockSftp
		);

		$this->replaceCoreConfigRegistry(
			array(
				'feedFetchRetryTimer'      => 0,
				'feedFetchConnectAttempts' => 2,
			)
		);

		Mage::getModel('eb2ccore/feed',
			array(
				'fs_tool' => $this->_mockFsTool,
				'base_dir' => $this->_vfs->url(self::TESTBASE_DIR_NAME),
			)
		)->fetchFeedsFromRemote('foo', '*.xml');
	}

	/**
	 * Test providing coverage for remote "any exception other than connection exception" handling
	 * 
	 * @large
	 * @test
	 */
	public function testFetchFeedsFromRemoteOtherException()
	{
		$mockSftp = $this->getMock(
			'TrueAction_FileTransfer_Model_Protocol_Types_Sftp',
			array( 'getAllFiles')
		);

		$mockSftp
			->expects($this->any())
			->method('getAllFiles')
			->will($this->throwException( new TrueAction_FileTransfer_Exception_Authentication('connection exception error')));

		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$mockSftp
		);

		$feed = Mage::getModel('eb2ccore/feed', array(
			'fs_tool' => $this->_mockFsTool,
			'base_dir' => $this->_vfs->url(self::TESTBASE_DIR_NAME),
		));

		$feed->fetchFeedsFromRemote('foo', '*.foo');
	}

	/**
	 * @large
	 * @test
	 */
	public function testFetchFeedsFromRemoteIsOk()
	{
		// The transport protocol is mocked - we just pretend we got files
		$mockSftp = $this->getMock(
			'TrueAction_FileTransfer_Model_Protocol_Types_Sftp',
			array( 'getAllFiles')
		);

		$mockSftp
			->expects($this->any())
			->method('getAllFiles')
			->will($this->returnValue(true));

		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$mockSftp
		);

		$feed = Mage::getModel('eb2ccore/feed', array(
			'fs_tool' => $this->_mockFsTool,
			'base_dir' => $this->_vfs->url(self::TESTBASE_DIR_NAME),
		));

		$feed->fetchFeedsFromRemote('foo', '*.foo');
	}
}
