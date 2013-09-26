<?php
/**
 * 
 */
class TrueAction_Eb2cMedia_Test_Model_Images_FeedTest extends TrueAction_Eb2cCore_Test_Base
{
	const CLASS_NAME            = 'TrueAction_Eb2cMedia_Model_Images_Feed';
	const MAGE_MODEL_NAME       = 'eb2cmedia/images_feed';
	const VFS_ROOT              = 'testImageRoot';
	const NUMBER_OF_DUMMY_FILES = 2; // How many files I expect to process as found in vfs fixture

	/**
	 * Instantiate the Model, test we get the expected model back
	 *
	 * @test
	 * @loadFixture mockFsTool
	 */
	public function testIsInstanceOf()
	{
		$this->_setMockFileTransfer($this->returnValue(true));
		$this->assertInstanceOf(
			self::CLASS_NAME,
			$this->_getTestModel($this->getFixture()->getVfs())
		);
	}

	/**
	 * Feeds must implement 'TrueAction_Eb2cCore_Model_Feed_Interface'
	 *
	 * @test
	 * @loadFixture mockFsTool
	 */
	public function testImplementsInterface()
	{
		$this->_setMockFileTransfer($this->returnValue(true));
		$this->assertContains(
			'TrueAction_Eb2cCore_Model_Feed_Interface',
			class_implements($this->_getTestModel($this->getFixture()->getVfs()))
		);
	}

	/**
	 * Test with a mock'd up filesystem
	 * 
	 * @test
	 * @loadFixture mockFsTool
	 */
	public function testFeedProcess()
	{
		$this->_setMockFileTransfer($this->returnValue(true));

		$this->markTestIncomplete('Incomplete, needs a replacy-by-mock for catalog/product');

		$this->assertSame(
			self::NUMBER_OF_DUMMY_FILES,
			$this->_getTestModel(
				$this->getFixture()
				->getVfs()
			)
			->processFeeds()
		);
	}

	/**
	 * Instantiate the test model, with phony remote and local paths and a mock fs
	 *
	 */
	private function _getTestModel($vfs)
	{
		$this->replaceCoreConfigRegistry(
			array(
				'clientId'              => 'MWS',
				'imagesFeedEventType'   => 'ImageMetaData',
				'imagesFeedFilePattern' => 'dummy_feed_file_pattern',
				'imagesFeedLocalPath'   => 'dummy_feed_local_path',
				'imagesFeedRemotePath'  => 'dummy_feed_remote_path',
			)
		);

		return Mage::getModel(
			self::MAGE_MODEL_NAME,
			array(
				'fs_tool' => $this->_setMockFsTool($vfs),
			)
		);
	}

	/**
	 * Replace file transfer mechanism
	 *
	 */
	private function _setMockFileTransfer($will)
	{
		// Mock the transport  - to just pretend we got files
		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$this->_buildModelMock(
				'filetransfer/protocol_types_sftp',
				array(
					'getFile' => $will,
				)
			)
		);
	}

	/**
	 * Instantiate a mock fs, it's necessary to invoke any instance of the image feed master for testing
	 *
	 */
	private function _setMockFsTool($vfs)
	{
		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		$dummyFiles = array ();
		foreach( $vfsDump['root'][self::VFS_ROOT][TrueAction_Eb2cCore_Model_Feed::INBOUND_DIR_NAME] as $filename => $contents ) {
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

		return $mockFsTool;
	}
}
