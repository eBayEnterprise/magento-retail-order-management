<?php
/**
 * Test the Order Stats Feed Module
 */
class TrueAction_Eb2cOrder_Test_Model_Status_VfsFeedTest extends TrueAction_Eb2cOrder_Test_Abstract
{
	const VFS_ROOT = 'testBase';

	/**
	 * Fakes up some files to send to the feed processor
	 *
	 * @test
	 * @loadFixture sampleFeeds.yaml
	 */
	public function testWithFixture()
	{
		$vfs = $this->getFixture()->getVfs();

		// Set up a Varien_Io_File style array for dummy file listing.
		$vfsDump = $vfs->dump();
		$dummyFiles = array ();
		foreach( $vfsDump['root'][self::VFS_ROOT]['inbound'] as $filename => $contents ) {
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
			->with($this->stringContains($vfs->url(self::VFS_ROOT)))
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
			->will($this->returnValue($vfs->url(self::VFS_ROOT.'/inbound')));
		$mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());

		// The transport protocol is mocked - we just pretend we got files
		$this->replaceModel('filetransfer/protocol_types_sftp', array('getFile' => true,));

		// Mock the core config registry, only value passed is the vfs filename
		$this->replaceCoreConfigRegistry(
			array (
				'statusFeedLocalPath' => $vfs->url(self::VFS_ROOT),
			)
		);

		$this->assertSame(
			count($dummyFiles),
			Mage::getModel(
					'eb2corder/status_feed',
					array(
						'fs_tool' => $mockFsTool
					)
				)->processFeeds()
		);

	}


	/**
	 * Test constructing the status feed with a base_dir argument
	 *
	 * @test
	 */
	public function testWithBaseDirArg()
	{
		// The core feed is mocked, I'm not going to need anything of it for this test.
		$this->replaceModel('eb2ccore/feed', array());

		// The transport protocol is mocked
		$this->replaceModel('filetransfer/protocol_types_sftp', array());

		// This test covers code for passing base_dir as an argument, test that we get the right object
		$this->assertInstanceOf(
			'TrueAction_Eb2cOrder_Model_Status_Feed',
			Mage::getModel(
					'eb2corder/status_feed',
					array(
						'base_dir'=> 'SomeMadeUpBaseDir'
					)
				)
		);
	}
}
