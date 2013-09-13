<?php
/**
 * Exercise the various methods for core shell feed
 *
 */
class TrueAction_Eb2cCore_Test_Model_Feed_ShellTest extends TrueAction_Eb2cCore_Test_Base
{
	private $_shellCore;
	private $_config;

	public function setUp()
	{
		$this->_shellCore = Mage::getModel('eb2ccore/feed_shell');
	}

	/**
	 * Is our _shellCore is the correct object
	 *
	 * @test
	 */
	public function testObjectInstance()
	{
		$this->assertInstanceOf('TrueAction_Eb2cCore_Model_Feed_Shell', $this->_shellCore);
	}

	/**
	 * Status should match Eb2cOrder Status Feed
	 *
	 * @test
	 */
	public function testMatchFeedNames()
	{
		$this->assertInstanceOf(get_class(Mage::getModel('eb2corder/status_feed')), $this->_shellCore->getFeedModel('status'));
	}

	/**
	 * Status should match Eb2cOrder Status Feed given short name
	 *
	 * @test
	 */
	public function testMatchShortName()
	{
		$this->assertInstanceOf(get_class(Mage::getModel('eb2corder/status_feed')), $this->_shellCore->getFeedModel('sta'));
	}

	/**
	 * Loop over an array of names, should get valid runners
	 *
	 * @test
	 */
	public function testMultipleNames()
	{
		// This mocks the available nodes construct, as found in config.xml
		$configValuePairs = array (
			'feedAvailableModels' => array(
				'eb2cinventory' => array(
					'feed_item_Inventories' => 0
				),
				'eb2corder' => array(
					'status_feed' => 1
				),
				'eb2cproduct' => array(
					'feed_content_master' => 1,
					'feed_image_master' => 0,
				),
			),
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

		$multipleFeeds = array( 'status', 'content', );
		foreach( $multipleFeeds as $aFeed ) {
			$model = $this->_shellCore->getFeedModel($aFeed);
			if( $aFeed === 'status' ) {
				$this->assertInstanceOf(get_class(Mage::getModel('eb2corder/status_feed')), $model);
			} else {
				$this->assertInstanceOf(get_class(Mage::getModel('eb2cproduct/feed_content_master')), $model);
			}
		}
	}

	/**
	 * Test a valid feed name configured, but pointing to model that does not implement the appropriate interface
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testModelLacksImplementation()
	{
		$fakeShell = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'eb2ccore/feed',
				)
			)
		);
		$rc = $fakeShell->runFeedModel('feed');
	}

	/**
	 * Test a valid feed name configured, but pointing to a non-existent model
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testInvalidModelConfigured()
	{
		$fakeShell = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'someGarbageName43346dfbdc2c57f0889d37ce061e58c57daffe6e/some_invalid_model',
				)
			)
		);
		$rc = $fakeShell->runFeedModel('GarbageName');
	}

	/**
	 * Test that we can really call processFeeds()
	 *
	 * @test
	 */
	public function testRunFeedModel()
	{
		$dummyReturnValue = 42;

		// Mock the Varien_Io_File, need a mock file system
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
			->will($this->returnValue(array()));
		$mockFsTool
			->expects($this->any())
			->method('pwd')
			->will($this->returnValue('doesnMatter'));
		$mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());

		// Mock order status feeds so I can fake a call to processFeeds(), this is
		// where I need a mock FS.
		$mockOrderStatusFeed = $this->getModelMock(
			'eb2corder/status_feed',
			array(
				'processFeeds',
			),
			false,
			array(
				array (
					'fs_tool' => $mockFsTool,
				)
			)
		);

		$mockOrderStatusFeed
			->expects($this->any())
			->method('processFeeds')
			->will($this->returnValue($dummyReturnValue));

		$fakeShell = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'eb2corder/status_feed'
				)
			)
		);

		$this->replaceByMock('model', 'eb2corder/status_feed', $mockOrderStatusFeed);

		$this->assertEquals($dummyReturnValue, $fakeShell->runFeedModel('stat'));
	}

	/**
	 * Non-matches are invalid. If you ask for some nonsense name, should be false
	 *
	 * @test
	 */
	public function testInvalidFeedIsFalse()
	{
		$this->assertfalse($this->_shellCore->getFeedModel('2d9af10bd2a388151591c37f100d72d731ba1427'));
	}

	/**
	 * Multiple matches are invalid. If you just say 'feed' that matches all of them, so should be false
	 *
	 * @test
	 */
	public function testMultipleMatchesIsFalse()
	{
		$this->assertfalse($this->_shellCore->getFeedModel('feed'));
	}

	/**
	 * Just make sure we get a string back from listAvailableFeeds
	 *
	 * @test
	 */
	public function testListAvailableString()
	{
		$this->assertNotEmpty( $this->_shellCore->listAvailableFeeds() );
	}
}
