<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_Pricing_ExtractorTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify feed data is extracted into varien objects
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testExtractPricingFeed($scenario, $xml)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXml($xml);
		$extractor = $this->getModelMock('eb2cproduct/feed_item_pricing_extractor', array('_extractPricePerItem'));
		$e = $this->expected($scenario);
		switch($e->getItemsCount()) {
			case 1:
				$invoked = $this->once();
				break;
			default:
				$invoked = $this->exactly($e->getItemsCount());
				break;
		}
		$extractor->expects($invoked)
			->method('_extractPricePerItem')
			->with($this->isInstanceOf('TrueAction_Dom_Element'))
			->will($this->returnValue(array()));
		$result = $extractor->extractPricingFeed($doc);
		$this->assertTrue(is_array($result), 'extractPricingFeed did not return an array');
		$this->assertSame($e->getItemsCount(), count($result));
		foreach ($result as $item) {
			$this->assertInstanceOf('Varien_Object', $item);
		}
	}

	/**
	 * verify an array is returned.
	 * verify correct data is extracted.
	 * @dataProvider dataProvider
	 */
	public function testExtractEvent($scenario, $xml)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
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
	 * @loadExpectation
	 */
	public function testExtractPricePerItem($scenario, $xml)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->preserveWhiteSpace = false;
		$doc->loadXml($xml);
		$eventNode = $doc->documentElement;
		$model = Mage::getModel('eb2cproduct/feed_item_pricing_extractor');
		$result = $this->_reflectMethod($model, '_extractPricePerItem')->invoke($model, $eventNode);
		$this->assertTrue(is_array($result), 'result is not an array');
		$e = $this->expected($scenario);
		$obj = new Varien_Object($result);
		$this->assertInstanceOf('Varien_Object', $obj);
		$this->assertSame($e->getGsiStoreId(), $obj->getGsiStoreId());
		$this->assertSame($e->getGsiClientId(), $obj->getGsiClientId());
		$this->assertSame($e->getCatalogId(), $obj->getCatalogId());
		$this->assertSame($e->getClientItemId(), $obj->getClientItemId());
		$this->assertSame($e->getEventsCount(), count($obj->getEvents()));
	}
}
