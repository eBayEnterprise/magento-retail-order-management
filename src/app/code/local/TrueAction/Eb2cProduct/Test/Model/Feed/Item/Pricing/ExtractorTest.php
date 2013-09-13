<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_Pricing_ExtractorTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify an array is returned.
	 * verify correct data is extracted.
	 * @dataProvider dataProvider
	 */
	public function testExtractEvent($scenario, $xml)
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		$doc->loadXml($xml);
		$eventNode = $doc->documentElement;
		$model = Mage::getModel('eb2cproduct/feed_item_pricing_extractor');
		$result = $this->_reflectMethod($model, '_extractEvent')->invoke($model, $eventNode);
		$this->assertTrue(is_array($result), 'result is not an array');
		$e = $this->expected($scenario);
		$this->assertEquals($e->getEvent(), $result);
	}

	/**
	 * verify an array is returned.
	 * verify correct data is extracted.
	 * @dataProvider dataProvider
	 */
	public function testExtractPricePerItem($scenario, $xml)
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->preserveWhiteSpace = false;
		$doc->loadXml($xml);
		$eventNode = $doc->documentElement;
		$model = Mage::getModel('eb2cproduct/feed_item_pricing_extractor');
		$result = $this->_reflectMethod($model, '_extractPricePerItem')->invoke($model, $eventNode);
		$this->assertTrue(is_array($result), 'result is not an array');
		$e = $this->expected($scenario);
		$this->assertEquals($e->getResult(), $result);
	}
}
