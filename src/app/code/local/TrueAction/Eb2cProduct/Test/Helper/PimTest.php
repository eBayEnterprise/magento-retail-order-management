<?php
class TrueAction_Eb2cProduct_Test_Helper_PimTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * return a cdata node from a given string value.
	 * @test
	 */
	public function testGetTextAsNode()
	{
		$attrValue = 'simple string value';
		$product = $this->getModelMock('catalog/product');
		$attribute = $this->getModelMock('catalog/entity_attribute');
		$doc = new TrueAction_Dom_Document();
		$result = Mage::helper('eb2cproduct/pim')
			->getTextAsNode($attrValue, $attribute, $product, $doc);
		$this->assertInstanceOf('DOMCDataSection', $result);

		$doc->appendChild($result);
		$this->assertSame($attrValue, $doc->C14N());
	}
	public function provideDefaultValue()
	{
		return array(array('some attribute value'), array(null));
	}
	/**
	 * return inner value element contining $attrValue.
	 * @test
	 * @dataProvider provideDefaultValue
	 */
	public function testGetValueAsDefault($attrValue)
	{
		$product = $this->getModelMock('catalog/product');
		$attribute = $this->getModelMock('catalog/entity_attribute');
		$doc = new TrueAction_Dom_Document();
		$result = Mage::helper('eb2cproduct/pim')
			->getValueAsDefault($attrValue, $attribute, $product, $doc);
		$this->assertInstanceOf('DOMNode', $result);
		$doc->appendChild($result);
		$this->assertSame(sprintf('<Value>%s</Value>', $attrValue), $doc->C14N());
	}
	/**
	 * return null if the value is null.
	 * @test
	 */
	public function testGetTextAsNodeNullValue()
	{
		$product = $this->getModelMock('catalog/product');
		$attribute = $this->getModelMock('catalog/entity_attribute');
		$doc = new TrueAction_Dom_Document();
		$this->assertNull(
			Mage::helper('eb2cproduct/pim')->getTextAsNode(null, $attribute, $product, $doc)
		);
	}
}
