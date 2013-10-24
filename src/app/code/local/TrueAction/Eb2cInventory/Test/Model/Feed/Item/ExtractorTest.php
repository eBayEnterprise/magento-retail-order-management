<?php
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_ExtractorTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_extractor = Mage::getModel('eb2cinventory/feed_item_extractor');
	}

	/**
	 * extract item master feed provider method
	 */
	public function providerExtractInventoryFeed()
	{
		$document = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$document->load(__DIR__ . '/ExtractorTest/fixtures/sample-feed.xml');
		return array(
			array($document)
		);
	}

	/**
	 * testing extractInventoryFeed method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerExtractInventoryFeed
	 */
	public function testExtractInventoryFeed($doc)
	{
		$this->assertCount(3, $this->_extractor->extractInventoryFeed($doc));
	}
}
