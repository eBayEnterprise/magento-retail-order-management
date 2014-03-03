<?php

class TrueAction_Eb2cProduct_Test_Model_Pim_Attriubte_FactoryTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Constructor should load and store the PIM feed mappings from the config
	 * @test
	 */
	public function testConstructor()
	{
		// mock mapping from the config.xml
		$mappingConfig = array('sku' => array('xml_dest' => 'Some/Xpath',));
		$defaultPimConfig = array('xml_dest' => 'ConfigurableAttributes/Attribute[@name="%s"]');
		$coreHelper = $this->getHelperMock('eb2ccore/feed', array('getConfigData'));
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreHelper);

		$coreHelper->expects($this->exactly(2))
			->method('getConfigData')
			->will($this->returnValueMap(array(
				array('eb2cproduct/feed_pim_mapping', $mappingConfig),
				array('eb2cproduct/default_pim_mapping', $defaultPimConfig),
			)));

		$factory = Mage::getModel('eb2cproduct/pim_attribute_factory');
		$this->assertSame(
			$mappingConfig,
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($factory, '_attributeMappings')
		);
		$this->assertSame(
			$defaultPimConfig,
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($factory, '_defaultMapping')
		);
	}
	/**
	 * Test creating a PIM Attribute Model for a given product and attribute.
	 * @test
	 */
	public function testGetPimAttribute()
	{
		$doc = new TrueAction_Dom_Document();
		$attributeMapping = array('xml_dest' => 'Some/XPath', 'class' => 'eb2cproduct/pim', 'type' => 'helper');
		$pimAttrConstructorArgs = array(
			'destination_xpath' => 'Some/XPath',
			'sku' => 'SomeSku',
			'value' => $this->getMockBuilder('DOMDocumentFragment')->disableOriginalConstructor()->getMock()
		);

		$product = $this->getModelMock('catalog/product');
		$attribute = $this->getModelMock('catalog/entity_attribute');
		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeMapping', '_resolveMappedCallback'))
			->getMock();

		$this->replaceByMock('model', 'eb2cproduct/pim_attribute', $pimAttribute);

		$factory->expects($this->once())
			->method('_getAttributeMapping')
			->with($this->identicalTo($attribute))
			->will($this->returnValue($attributeMapping));
		$factory->expects($this->once())
			->method('_resolveMappedCallback')
			->with($this->identicalTo($attributeMapping), $this->identicalTo($attribute), $this->identicalTo($product), $this->identicalTo($doc))
			->will($this->returnValue($pimAttrConstructorArgs));

		$this->assertSame($pimAttribute, $factory->getPimAttribute($attribute, $product, $doc));
	}
	/**
	 * When a resolved mapping callback returns null due to a mapping being
	 * disabled, this method should return null instead of a PIM attribute model.
	 * @test
	 */
	public function testGetPimAttributeDisabledMapping()
	{
		$doc = new TrueAction_Dom_Document();
		$attributeMapping = array('xml_dest' => 'Some/XPath');
		$product = $this->getModelMock('catalog/product');
		$attribute = $this->getModelMock('catalog/entity_attribute');
		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeMapping', '_resolveMappedCallback'))
			->getMock();

		$this->replaceByMock('model', 'eb2cproduct/pim_attribute', $pimAttribute);

		$factory->expects($this->once())
			->method('_getAttributeMapping')
			->with($this->identicalTo($attribute))
			->will($this->returnValue($attributeMapping));
		$factory->expects($this->once())
			->method('_resolveMappedCallback')
			->with($this->identicalTo($attributeMapping), $this->identicalTo($attribute), $this->identicalTo($product), $this->identicalTo($doc))
			->will($this->returnValue(null));

		$this->assertSame(null, $factory->getPimAttribute($attribute, $product, $doc));
	}
	/**
	 * Test getting an attribute mapping
	 * @test
	 */
	public function testGetAttributeMapping()
	{
		$skuMapping = array('xml_dest' => 'Some/XPath');
		$attributeMappings = array('mapped_attribute_code' => $skuMapping);
		$attribute = $this->getModelMockBuilder('catalog/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeCode'))
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($factory, '_attributeMappings', $attributeMappings);

		$attribute->expects($this->once())
			->method('getAttributeCode')
			->will($this->returnValue('mapped_attribute_code'));

		$this->assertSame(
			$skuMapping,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($factory, '_getAttributeMapping', array($attribute))
		);
	}
	/**
	 * When the attribute code does not match a mapped attribute, use a default
	 * mapping.
	 * @test
	 */
	public function testGetAttributeMappingUsingDefault()
	{
		$defaultMapping = array('xml_dest' => 'Some/XPath');
		$attributeMappings = array('mapped_attribute_code' => array());
		$attribute = $this->getModelMockBuilder('catalog/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeCode'))
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(array('_getDefaultMapping'))
			->getMock();

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($factory, '_attributeMappings', $attributeMappings);

		$factory->expects($this->once())
			->method('_getDefaultMapping')
			->with($this->identicalTo('unmapped_attribute_code'))
			->will($this->returnValue($defaultMapping));
		$attribute->expects($this->once())
			->method('getAttributeCode')
			->will($this->returnValue('unmapped_attribute_code'));

		$this->assertSame(
			$defaultMapping,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($factory, '_getAttributeMapping', array($attribute))
		);
	}
	/**
	 * Return the generated default mapping - should use the default config value
	 * with the xml_dest evaluated to use the attribute code for the
	 * @name attribute value.
	 * @test
	 */
	public function testGetDefaultMapping()
	{
		$attributeCode = 'attribute_code';
		$defaultMapping = array('xml_dest' => 'CustomAttributes/Attribute[@name="%s"]');

		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($factory, '_defaultMapping', $defaultMapping);

		$this->assertSame(
			array('xml_dest' => 'CustomAttributes/Attribute[@name="attribute_code"]'),
			EcomDev_Utils_Reflection::invokeRestrictedMethod($factory, '_getDefaultMapping', array($attributeCode))
		);
		// ensure the property has not been modified
		$this->assertSame(
			$defaultMapping,
			EcomDev_Utils_Reflection::getRestrictedPropertyValue($factory, '_defaultMapping')
		);
	}
	/**
	 * Should invoke the configured callback using the eb2ccore/feed helper.
	 * Helper method must be called with the callback configuration including
	 * a 'parameters' key including the attribute value, attribute and product.
	 * The method should return an array of arguments to be passed to the PIM
	 * Attribute model's constructor.
	 * @test
	 */
	public function testResolveMappedCallback()
	{
		$doc = new TrueAction_Dom_Document();
		$attribute = $this->getModelMock('catalog/entity_attribute', array('getAttributeCode'));
		$product = $this->getModelMock('catalog/product', array('getDataUsingMethod'));
		$callbackValue = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$coreHelper = $this->getHelperMock('eb2ccore/feed', array('invokeCallback'));
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->setMethods(array('_createPimAttributeArgs'))
			->disableOriginalConstructor()
			->getMock();

		$this->replaceByMock('helper', 'eb2ccore/feed', $coreHelper);

		$attributeMapping = array(
			'xml_dest' => 'Some/XPath',
			'class' => 'eb2cproduct/pim',
			'type' => 'helper',
			'method' => 'doSomeThing',
			'translate' => 1
		);
		$languageCode = 'en-us';
		$sku = '45-12345';
		$attributeCode = 'some_attribute_code';
		$attributeValue = 'some attribute value';
		$callbackConfig = array_merge(
			$attributeMapping,
			array('parameters' => array($attributeValue, $attribute, $product, $doc))
		);
		$pimAttrModelConstructorArgs = array(
			'destination_xpath' => $attributeMapping['xml_dest'],
			'sku' => $sku,
			'value' => $callbackValue,
			'language' => $languageCode,
		);

		$attribute->expects($this->once())
			->method('getAttributeCode')
			->will($this->returnValue($attributeCode));
		$product->expects($this->once())
			->method('getDataUsingMethod')
			->with($this->identicalTo($attributeCode))
			->will($this->returnValue($attributeValue));
		$coreHelper->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($callbackConfig))
			->will($this->returnValue($callbackValue));
		$factory->expects($this->once())
			->method('_createPimAttributeArgs')
			->with(
				$this->identicalTo($callbackConfig),
				$this->identicalTo($callbackValue),
				$this->identicalTo($product)
			)
			->will($this->returnValue($pimAttrModelConstructorArgs));
		$this->assertSame(
			$pimAttrModelConstructorArgs,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$factory,
				'_resolveMappedCallback',
				array($attributeMapping, $attribute, $product, $doc)
			)
		);
	}
	/**
	 * When an attribute configuration is set to the "disabled" type, this method
	 * should simply return null.
	 * @test
	 */
	public function testResolveMappedCallbackDisabledMapping()
	{
		$doc = new TrueAction_Dom_Document();
		$attribute = $this->getModelMock('catalog/entity_attribute', array('getAttributeCode'));
		$product = $this->getModelMock('catalog/product', array('getDataUsingMethod'));
		$coreHelper = $this->getHelperMock('eb2ccore/feed', array('invokeCallback'));

		$attributeMapping = array(
			'xml_dest' => 'Some/XPath',
			'class' => 'eb2cproduct/pim',
			'type' => 'disabled',
			'method' => 'doSomeThing',
			'translate' => 1
		);

		// When the mapping is "disabled", no attempt to do anything with the
		// attribute or product should be made.
		$attribute->expects($this->never())
			->method('getAttributeCode');
		$product->expects($this->never())
			->method('getDataUsingMethod');
		$coreHelper->expects($this->never())
			->method('invokeCallback');

		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->getMock();
		$this->assertNull(
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$factory,
				'_resolveMappedCallback',
				array($attributeMapping, $attribute, $product, $doc)
			)
		);
	}
	/**
	 * Create the array of args to pass to the PIM Attribute model constructor
	 * based on a given attribute mapping, value and product.
	 * @test
	 */
	public function testCreatingPimAttributeArgsWithTranslation()
	{
		$sku = '45-12345';
		$languageCode = 'en-US';

		$product = $this->getModelMock('catalog/product', array('getSku', 'getPimLanguageCode'));
		$callbackValue = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$attributeMapping = array(
			'xml_dest' => 'Some/XPath',
			'class' => 'eb2cproduct/pim',
			'type' => 'helper',
			'method' => 'doSomeThing',
			'translate' => 1
		);
		$pimAttrModelConstructorArgs = array(
			'destination_xpath' => $attributeMapping['xml_dest'],
			'sku' => $sku,
			'language' => $languageCode,
			'value' => $callbackValue,
		);

		$product->expects($this->any())
			->method('getSku')
			->will($this->returnValue($sku));
		$product->expects($this->once())
			->method('getPimLanguageCode')
			->will($this->returnValue($languageCode));
		$this->assertSame(
			$pimAttrModelConstructorArgs,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$factory,
				'_createPimAttributeArgs',
				array($attributeMapping, $callbackValue, $product)
			)
		);
	}
	/**
	 * When the translate key in the config is set to false/0, the language
	 * key in the arg array should not be set.
	 * @test
	 */
	public function testCreatingPimAttributeArgsNoTranslation()
	{
		$product = $this->getModelMock('catalog/product', array('getSku', 'getLanguageCode'));
		$callbackValue = $this->getMockBuilder('DOMDocumentFragment')
			->disableOriginalConstructor()
			->getMock();
		$attributeMapping = array(
			'xml_dest' => 'Some/XPath',
			'class' => 'eb2cproduct/pim',
			'type' => 'helper',
			'method' => 'doSomeThing',
			'translate' => 0
		);
		$sku = '45-12345';
		$pimAttrModelConstructorArgs = array(
			'destination_xpath' => $attributeMapping['xml_dest'],
			'sku' => $sku,
			'language' => null,
			'value' => $callbackValue,
		);
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product->expects($this->any())
			->method('getSku')
			->will($this->returnValue($sku));
		$product->expects($this->never())
			->method('getLanguageCode');

		$this->assertSame(
			$pimAttrModelConstructorArgs,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$factory,
				'_createPimAttributeArgs',
				array($attributeMapping, $callbackValue, $product)
			)
		);
	}
}
