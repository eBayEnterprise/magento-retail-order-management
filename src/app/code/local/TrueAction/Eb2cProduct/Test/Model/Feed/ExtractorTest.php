<?php

class TrueAction_Eb2cProduct_Test_Model_Feed_ExtractorTest
	extends TrueAction_Eb2cCore_Test_Base
{

	/**
	 * Load callback config and ensure it gets stored on the $_callbacks property.
	 * @test
	 */
	public function testConstructor()
	{
		$configArray = array(array('callback' => 'things'));
		$coreHelper = $this->getHelperMock('eb2ccore/feed', array('getConfigData'));
		$coreHelper->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo(TrueAction_Eb2cProduct_Model_Feed_Extractor::CALLBACK_CONFIG_PATH))
			->will($this->returnValue($configArray));
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreHelper);

		$extractor = Mage::getModel('eb2cproduct/feed_extractor');
		$this->assertSame(
			$configArray,
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($extractor, '_callbacks')
		);
	}
	/**
	 * Build array of key => value pairs of product attribute to value for a
	 * single item in the feed.
	 * Iterate over configured callbacks
	 * - Query XPath to get DOMNodeList of feed data
	 * - Invoke callback with callback configuration, including necessary parameters key
	 * @test
	 */
	public function testExtractItem()
	{
		$callbackConfig = array(
			'sku' => array('xpath' => 'Xpath/To/Sku', 'type' => 'helper'),
			'bad_path' => array('xpath' => 'Xpath/To/Bad', 'type' => 'helper'),
		);
		$itemData = array('sku' => 'abc-123');
		$product = $this->getModelMock('catalog/product');

		$feedHelper = $this->getHelperMock('eb2ccore/feed', array('invokeCallback'));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);
		$xpath = $this->getMockBuilder('DOMXPath')
			->disableOriginalConstructor()
			->setMethods(array('evaluate'))
			->getMock();
		$contextNode = $this->getMock('DOMNode');

		$skuNodeList = $this->getMockBuilder('DOMNodeList')->disableOriginalConstructor()->getMock();
		$skuCallback = array('xpath' => 'Xpath/To/Sku', 'type' => 'helper', 'parameters' => array($skuNodeList, $product));
		$emptyNodeList = $this->getMockBuilder('DOMNodeList')->disableOriginalConstructor()->getMock();
		$xpathToNodeListMap = array(
			array($callbackConfig['sku']['xpath'], $contextNode, null, $skuNodeList),
			array($callbackConfig['bad_path']['xpath'], $contextNode, null, $emptyNodeList),
		);
		$validateValueMap = array(
			array($skuNodeList, true),
			array($emptyNodeList, false)
		);

		$xpath->expects($this->exactly(2))
			->method('evaluate')
			->will($this->returnValueMap($xpathToNodeListMap));
		// SKU callback should contain all key/value pairs in callback config, plus
		// a 'parameters' key with the result of the XPath query as the value
		$feedHelper->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($skuCallback))
			->will($this->returnValue($itemData['sku']));

		$extractor = $this->getModelMockBuilder('eb2cproduct/feed_extractor')
			->disableOriginalConstructor()
			->setMethods(array('_validateResult'))
			->getMock();
		$extractor->expects($this->exactly(2))
			->method('_validateResult')
			->will($this->returnValueMap($validateValueMap));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($extractor, '_callbacks', $callbackConfig);

		$this->assertSame(
			$itemData,
			$extractor->extractItem($xpath, $contextNode, $product)
		);
	}

	/**
	 * Test _validateResult method with the following expectations
	 * Expectation 1: this test invoked the TrueAction_Eb2cProduct_Model_Feed_Extractor::_validateResult method with a given
	 *                parameter of false it will return false, or if the given parameter is a nodeList with no item it will return false
	 *                if the given parameter is 0 it will return true and if the given parameter is a DOMNodeList with an item in it will
	 *                return true
	 * @test
	 */
	public function testValidateResultWhenPassEmptyDomNodeListReturnFalse()
	{
		$result = new DOMNodeList();

		$extractor = $this->getModelMockBuilder('eb2cproduct/feed_extractor')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame(false, EcomDev_Utils_Reflection::invokeRestrictedMethod($extractor, '_validateResult', array($result)));
	}

	/**
	 * @see testValidateResultWhenPassEmptyDomNodeListReturnFalse but this time will be passing
	 *      a DOMNodeList with actual item to TrueAction_Eb2cProduct_Model_Feed_Extractor::_validateResult method and
	 *      it will return true
	 * @test
	 */
	public function testValidateResultWhenPassDomNodeListWithItemReturnTrue()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root><sku>1234</sku></root>');
		$xpath = new DOMXPath($doc);
		$result = $xpath->evaluate('/root/sku', $doc->documentElement);

		$extractor = $this->getModelMockBuilder('eb2cproduct/feed_extractor')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame(true, EcomDev_Utils_Reflection::invokeRestrictedMethod($extractor, '_validateResult', array($result)));
	}

	/**
	 * @see testValidateResultWhenPassEmptyDomNodeListReturnFalse but this time we will be passing
	 *      a string value to the TrueAction_Eb2cProduct_Model_Feed_Extractor::_validateResult method
	 *      and it will return true
	 */
	public function testValidateResultWhenPassStringWilReturnTrue()
	{
		$result = 'anything';

		$extractor = $this->getModelMockBuilder('eb2cproduct/feed_extractor')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame(true, EcomDev_Utils_Reflection::invokeRestrictedMethod($extractor, '_validateResult', array($result)));
	}
	/**
	 * Test extracting a SKU from a DOMNode containing an item.
	 * @param string $xml XML snipped to extract a SKU from
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testExtractSku($xml)
	{
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
		$node = $xpath->query('/root/Item')->item(0);

		$extractor = $this->getModelMockBuilder('eb2cproduct/feed_extractor')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame('45-12345', $extractor->extractSku($xpath, $node));
	}
}
