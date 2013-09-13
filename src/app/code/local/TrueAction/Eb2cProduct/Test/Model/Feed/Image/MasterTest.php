<?php
/**
 * 
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Image_MasterTest extends TrueAction_Eb2cCore_Test_Base
{
	const MAGE_MODEL_NAME = 'eb2cproduct/feed_image_master';
	const VFS_ROOT        = 'testImageRoot';

	/**
	 * Instantiate the Model, test we get the expected model back
	 *
	 * @test
	 */
	public function testIsInstanceOf()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Image_Master',
			Mage::getModel(self::MAGE_MODEL_NAME)
		);
	}

	/**
	 * Feeds must implement 'TrueAction_Eb2cCore_Model_Feed_Interface'
	 *
	 * @test
	 */
	public function testImplementsInterface()
	{
		$this->assertContains(
			'TrueAction_Eb2cCore_Model_Feed_Interface',
			class_implements(Mage::getModel(self::MAGE_MODEL_NAME))
		);
	}

	/**
	 * Test with a mock'd up filesystem
	 * 
	 * @test
	 * @loadFixture
	 */
	public function testFeedProcess()
	{
		$vfs = $this->getFixture()->getVfs();

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
			->with($this->stringContains($vfs->url(self::VFS_ROOT)))
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('mv')
			->with( $this->stringContains($vfs->url(self::VFS_ROOT)), $this->stringContains($vfs->url(self::VFS_ROOT)))
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
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());

		// Mock the transport  - to just pretend we got files
		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp',
			$this->_buildModelMock(
				'filetransfer/protocol_types_sftp',
				array(
					'getFile' => $this->returnValue(true),
				)
			)
		);

		// @todo: Mock the core config registry to know where our local files are to process

		$this->assertSame(
			1,
			Mage::getModel(
				'eb2cproduct/feed_image_master',
				array(
					'remote_path'  => 'dummy_remote_path',
					'fs_tool'      => $mockFsTool
				)
			)->processFeeds()
		);
	}
}
