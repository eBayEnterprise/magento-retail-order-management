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
		$model = $this->getMockForAbstractClass(
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
		$model = $this->getMockForAbstractClass(
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
		$model = $this->getMockForAbstractClass(
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
		$model = $this->getMockForAbstractClass(
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
		$model = $this->getMockForAbstractClass(
			self::CLASS_TESTED,
			array(
				'param' => $args
			)
		);
	}
}
