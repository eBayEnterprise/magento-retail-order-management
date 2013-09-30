<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_ExtractorTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * extract item master feed provider method
	 */
	public function providerExtract()
	{
		$document = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$document->load(__DIR__ . '/ExtractorTest/fixtures/sample-feed.xml');
		return array(
			array($document)
		);
	}

	/**
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerExtract
	 */
	public function testExtract($doc)
	{
		$this->assertCount(1, Mage::getModel('eb2cproduct/feed_item_extractor')->extract($doc));
	}
}
