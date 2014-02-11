<?php
class TrueAction_Eb2cProduct_Test_Model_Pim_AttributeTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify the constructor allows for magento factory initialization
	 * @test
	 */
	public function testConstructor()
	{
		$dom = new TrueAction_Dom_Document();
		$xpath = 'foo';
		$sku = 'somesku';
		$language = 'en-US';
		$value = $dom->createDocumentFragment();
		$value->appendChild($dom->createElement('Foo', 'bar'));
		$model = Mage::getModel('eb2cproduct/pim_attribute', array(
			'destination_xpath' => $xpath, 'sku' => $sku, 'language' => $language, 'value' => $value
		));
		$this->assertSame($xpath, $model->destinationXpath);
		$this->assertSame($sku, $model->sku);
		$this->assertSame($language, $model->language);
		$this->assertSame($value, $model->value);

		$model = Mage::getModel('eb2cproduct/pim_attribute', array(
			'destination_xpath' => $xpath, 'sku' => $sku, 'value' => $value
		));
		$this->assertNull($model->language);
	}
	public function testStringifyValue()
	{
		$attrModel = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$dom = new DOMDocument();

		$method = new ReflectionMethod($attrModel, '_stringifyValue');
		$method->setAccessible(true);

		$n = $dom->createDocumentFragment();
		$n->appendChild($dom->createElement('Foo', 'Value'));
		$n->appendChild($dom->createElement('Bar', 'Thing'));
		$attrModel->value = $n;
		$this->assertSame(
			'<Foo>Value</Foo><Bar>Thing</Bar>',
			$method->invoke($attrModel)
		);
		$n = $dom->createCDATASection('Some String Value');
		$attrModel->value = $n;
		$this->assertSame(
			'Some String Value',
			$method->invoke($attrModel)
		);
		$n = $dom->createElement('Foo', 'Bar');
		$attrModel->value = $n;
		$this->assertSame(
			'<Foo>Bar</Foo>',
			$method->invoke($attrModel)
		);
	}
	public function testToString()
	{
		$dom = new DOMDocument();
		$a = Mage::getModel(
			'eb2cproduct/pim_attribute',
			array(
				'destination_xpath' => 'ItemId',
				'sku' => '45-12345',
				'value' => $dom->createElement('Foo', 'Value')
			)
		);
		$this->assertSame('0ItemId<Foo>Value</Foo>', (string) $a);
		$a = Mage::getModel(
			'eb2cproduct/pim_attribute',
			array(
				'destination_xpath' => 'CustomAttributes/Attribute',
				'sku' => '45-12345',
				'language' => 'en-US',
				'value' => $dom->createElement('Foo', 'Value')
			)
		);
		$this->assertSame('5CustomAttributes/Attributeen-US<Foo>Value</Foo>', (string) $a);
		$a = Mage::getModel(
			'eb2cproduct/pim_attribute',
			array(
				'destination_xpath' => 'CustomAttributes/Attribute',
				'sku' => '45-12345',
				'language' => 'en-US',
				'value' => $dom->createCDATASection('Foo')
			)
		);
		$this->assertSame('5CustomAttributes/Attributeen-USFoo', (string) $a);
		$fragment = $dom->createDocumentFragment();
		$fragment->appendChild($dom->createElement('Foo', 'Bar'));
		$fragment->appendChild($dom->createElement('Baz'));
		$a = Mage::getModel(
			'eb2cproduct/pim_attribute',
			array(
				'destination_xpath' => 'CustomAttributes/Attribute',
				'sku' => '45-12345',
				'language' => 'en-US',
				'value' => $fragment
			)
		);
		$this->assertSame('5CustomAttributes/Attributeen-US<Foo>Bar</Foo><Baz></Baz>', (string) $a);
	}
	/**
	 * Provide constructor args to the PIM Attribute model that should be expected
	 * to trigger an error.
	 * @return array
	 */
	public function provideConstructorArgs()
	{
		return array(
			array(array(), 'User Error: TrueAction_Eb2cProduct_Model_Pim_Attribute::__construct missing arguments: destination_xpath, sku, value are required'),
			array(array('destination_xpath' => 'Some/Xpath', 'sku' => '45-12345', 'value' => 'Not a DOMNode'), 'User Error: TrueAction_Eb2cProduct_Model_Pim_Attribute::__construct called with invalid value argument. Must be DOMNode'),
		);
	}
	/**
	 * An error should be triggered, captured as an Exception in the test, if the
	 * constructor is called without all required key/value pairs.
	 * @test
	 * @dataProvider provideConstructorArgs
	 */
	public function testConstructInvalidArgs($constructorArgs, $message)
	{
		$this->setExpectedException('Exception', $message);
		Mage::getModel('eb2cproduct/pim_attribute', $constructorArgs);
	}
}
