<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Content_ExtractorTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_extractor = Mage::getModel('eb2cproduct/feed_content_extractor');
	}

	/**
	 * extract content master feed provider method
	 */
	public function providerExtractContentMasterFeed()
	{
		$document = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$document->load(__DIR__ . '/ExtractorTest/fixtures/sample-feed.xml');
		return array(
			array($document)
		);
	}

	/**
	 * testing extractContentMasterFeed method
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerExtractContentMasterFeed
	 */
	public function testExtractContentMasterFeed($doc)
	{
		$this->assertCount(1, $this->_extractor->extractContentMasterFeed($doc));
	}
}
