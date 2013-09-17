<?php
/**
 * Feed Abstract Test
 *
 */
class TrueAction_Eb2cCore_Test_Model_Feed_AbstractTest extends TrueAction_Eb2cCore_Test_Base
{
	const VFS_ROOT = 'root';

	/**
	 * Test Feed Abstract
	 *
	 * @test
	 */
	public function testIsInstanceOf()
	{
		$model = $this->getMockForAbstractClass(
			'TrueAction_Eb2cCore_Model_Feed_Abstract',
			array( 'param' => array(
					'local_path'  => 'dummy_path',
					'remote_path' => 'dummy_path'
				)
			)
		);
		$this->assertInstanceOf('TrueAction_Eb2cCore_Model_Feed_Abstract', $model);
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
		$dummyFiles = array ();
		foreach( $vfsDump[self::VFS_ROOT]['inbound'] as $filename => $contents ) {
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

		// The transport protocol is mocked - we just pretend we got files
		$mockSftp = $this->getMock(
			'TrueAction_FileTransfer_Model_Protocol_Types_Sftp',
			array( 'getFile')
		);

		$mockSftp
			->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));

		$this->replaceByMock(
			'model',
			'filetransfer/protocol_types_sftp', 
			$mockSftp
		);

		$model = $this->getMockForAbstractClass(
			'TrueAction_Eb2cCore_Model_Feed_Abstract',
			array( 'param' => array(
					'remote_path' => 'dummy_path',
					'local_path'  => $vfs->url('inbound'),
					'fs_tool'  => $mockFsTool,
				)
			)
		);

		$model->processFeeds();
	}


	/**
	 * Test Feed Abstract throws Exception without remote_path defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testRemotePathException()
	{
		$model = $this->getMockForAbstractClass(
			'TrueAction_Eb2cCore_Model_Feed_Abstract',
			array( 'param' => array(
					'local_path'  => 'dummy_path',
				)
			)
		);
	}

	/**
	 * Test Feed Abstract throws Exception without local_path defined.
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testLocalPathException()
	{
		$model = $this->getMockForAbstractClass(
			'TrueAction_Eb2cCore_Model_Feed_Abstract',
			array( 'param' => array(
					'remote_path'  => 'dummy_path',
				)
			)
		);
	}
}
