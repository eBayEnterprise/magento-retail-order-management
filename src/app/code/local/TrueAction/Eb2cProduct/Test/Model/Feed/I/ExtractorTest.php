<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_I_ExtractorTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_extractor = Mage::getModel('eb2cproduct/feed_i_extractor');
	}

	/**
	 * extract item master feed provider method
	 */
	public function providerExtractIShipFeed()
	{
		$document = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$document->load(__DIR__ . '/ExtractorTest/fixtures/sample-feed.xml');
		return array(
			array(new DOMXPath($document))
		);
	}

	/**
	 * testing ExtractIShipFeed method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerExtractIShipFeed
	 */
	public function testExtractIShipFeed($xpath)
	{
		$this->assertCount(1, $this->_extractor->extract($xpath));
	}
}
