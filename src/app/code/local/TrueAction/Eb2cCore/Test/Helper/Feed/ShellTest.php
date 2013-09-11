<?php
/**
 * Test Feed Shell Helper
 */
class TrueAction_Eb2cCore_Test_Helper_Feed_ShellTest extends TrueAction_Eb2cCore_Test_Base
{
	protected $_helper;
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
	}

	/**
	 * Test loading of available models from (simluated) core etc/config.xml
	 *
	 * @test
	 */
	public function testGetConfiguredFeedModels()
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

		$this->_helper = Mage::helper('eb2ccore/feed_shell');

		$configuredFeeds = $this->_helper->getConfiguredFeedModels();

		$this->assertContains( 'eb2corder/status_feed', $configuredFeeds );
		$this->assertContains( 'eb2cproduct/feed_content_master', $configuredFeeds );
		$this->assertNotContains( 'eb2cproduct/feed_image_master', $configuredFeeds );
		
	}
}
