<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_AttributesTest extends TrueAction_Eb2cCore_Test_Base
{
	public static $modelClass = 'TrueAction_Eb2cProduct_Model_Attributes';

	/**
	 * verify the function returns true if the attribute set's entity id
	 * is a valid entity id.
	 * @param  int $eid1
	 * @param  int $eid2
	 * @param  bool $expect
	 * @dataProvider dataProvider
	 */
	public function testIsValidEntityType($eid1, $expect)
	{
		$model   = $this->getModelMock('eb2cproduct/attributes', array('_getTargetEntityTypeIds'));
		$model->expects($this->any())
			->method('_getTargetEntityTypeIds')
			->will($this->returnValue(array(10)));
		$val = $this->_reflectMethod($model, '_isValidEntityType')->invoke($model, $eid1);
		$this->assertSame($expect, $val);
	}

	/**
	 * ensure the tax code is readable
	 * @loadFixture
	 * @large
	 * NOTE: ticket EB2C-14
	 * NOTE: marked large because this is an integration test.
	 */
	public function testReadingAttributeValue()
	{
		$fixture = $this->getFixture()->getStorage()->getLocalFixture();
		$product = Mage::getModel('catalog/product');
		$taxCode = 'thecode';
		$product->load(1);
		$product->setTaxCode($taxCode);
		$product->save();
		$product->load(1);
		$this->assertNotNull($product->getId());
		$this->assertTrue($product->hasTaxCode(), 'product does not have a tax_code value');
		$this->assertSame($taxCode, $product->getTaxCode());
	}

	/**
	 * verify a the model field name is returned when it is defined in the map
	 * and the input field name is returned if not in the map.
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldName($fieldName, $expected)
	{
		$map   = array('field_in_map' => 'model_field_name');
		$model = Mage::getModel('eb2cproduct/attributes');
		$this->_reflectProperty($model, '_fieldNameMap')->setValue($model, $map);
		$modelFieldName = $this->_reflectMethod($model, '_getMappedFieldName')
			->invoke($model, $fieldName);
		$this->assertSame($expected, $modelFieldName);
	}

	/**
	 * verify a the function returns a value in the correct format for the field as
	 * per the mapping
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldValue($fieldName, $data, $expected)
	{
		$xml      = "<?xml version='1.0'?>\n<{$fieldName}>{$data}</{$fieldName}>";
		$dataNode = new Varien_SimpleXml_Element($xml);
		$model    = Mage::getModel('eb2cproduct/attributes');
		$value    = $this->_reflectMethod($model, '_getMappedFieldValue')
			->invoke($model, $fieldName, $dataNode);
		$this->assertSame($expected, $value);
	}

	/**
	 * verify a the correct field name for the frontend type is returned.
	 * @dataProvider dataProvider
	 */
	public function testGetDefaultValueFieldName($frontendType, $expected)
	{
		$model    = Mage::getModel('eb2cproduct/attributes');
		$value    = $this->_reflectMethod($model, '_getDefaultValueFieldName')
			->invoke($model, $frontendType);
		$this->assertSame($expected, $value);
	}

	/**
	 * verify a new model is returned and contains the correct data for each field
	 * @loadExpectation
	 */
	public function testGetPrototypeData()
	{
		$dataNode = new Varien_SimpleXml_Element(self::$configXml);
		$result   = $dataNode->xpath('/eb2cproduct_attributes/default/tax_code');
		// start precondition checks
		$this->assertSame(1, count($result));
		list($taxCodeNode) = $result;
		$this->assertInstanceOf('Varien_SimpleXml_Element', $taxCodeNode);
		$this->assertSame('tax_code', $taxCodeNode->getName());
		// end preconditions checks

		$model = Mage::getModel('eb2cproduct/attributes');
 		$attrData = $this->_reflectMethod($model, '_getPrototypeData')
 			->invoke($model, $taxCodeNode);
		$this->assertNotEmpty($attrData);
		$e = $this->expected('tax_code');
		$this->assertEquals($e->getData(), $attrData);
	}

	public function testGetPrototypeDataCache()
 	{
 		// setup input data
 		$dataNode = new Varien_SimpleXml_Element(self::$configXml);
		$result = $dataNode->xpath('/eb2cproduct_attributes/default/tax_code');
		$this->assertSame(1, count($result));
		list($taxCodeNode) = $result;
		$this->assertInstanceOf('Varien_SimpleXml_Element', $taxCodeNode);
		$this->assertSame('tax_code', $taxCodeNode->getName());

 		// mock functions to make sure they're not called
 		$model = $this->getModelMock('eb2cproduct/attributes', array('_getDefaultValueFieldName', '_getMappedFieldName', '_getMappedFieldValue'));
 		// mock up the cache
 		$dummyObject = new Varien_Object();
 		$this->_reflectProperty($model, '_prototypeCache')
			->setValue($model, array('tax_code' => $dummyObject));
 		$attrData = $this->_reflectMethod($model, '_getPrototypeData')
 			->invoke($model, $taxCodeNode);
		$this->assertNotEmpty($attrData);
		$this->assertInstanceOf('Varien_Object', $dummyObject);
		$this->assertSame($dummyObject, $attrData);
	}

	public function callbackGetModuleDir($dir, $module)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('app/code/local/TrueAction');
		return $url . DS . $module . DS . 'etc';
	}

	/**
	 * verify the default config in the config.xml can be overridden by another xml file.
	 * @loadExpectation attributesConfig.yaml
	 * @dataProvider provideOverrideXmlVfsStructure
	 */
	public function testLoadDefaultAttributesConfig($expectation, $vfsStructure)
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$config = $this->_reflectMethod($model, '_loadDefaultAttributesConfig')->invoke($model);
		$this->assertInstanceOf('Mage_Core_Model_Config', $config);
		$e           = $this->expected($expectation);
		$configArray = $config->getNode('default')->asArray();
		$this->assertSame($e->getData('tax_code'), $configArray['tax_code']);
	}

	/**
	 * verify a list of default codes is generated from the config.
	 * @loadExpectation testGetDefaultAttributesCodeList.yaml
	 */
	public function testGetDefaultAttributesCodeList()
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$fn     = $this->_reflectMethod($model, 'getDefaultAttributesCodeList');
		$result	= $fn->invoke($model);
		$e      = $this->expected('default');
		$this->assertSame($e->getData(), $result);
	}

	/**
	 * verify the list of codes can be filtered by group.
	 * @loadExpectation testGetDefaultAttributesCodeList.yaml
	 */
	public function testGetDefaultAttributesCodeListFilterByGroup()
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$fn     = $this->_reflectMethod($model, 'getDefaultAttributesCodeList');
		$result	= $fn->invoke($model, 'Prices');
		$e      = $this->expected('prices');
		$this->assertSame($e->getData(), $result);
	}

	public function provideOverrideXmlVfsStructure()
	{
		return array(
			array('base_config', $this->_getOverrideXmlVfsStructure()),
		);
	}

	protected function _getOverrideXmlVfsStructure(array $etcContents = array())
	{
		return array(
			'app' => array(
				'code' => array(
					'local' => array(
						'TrueAction' => array(
							'Eb2cProduct' => array(
								'etc' => $etcContents
			))))));
	}

	public static $configXml  = '
		<eb2cproduct_attributes>
			<default>
				<tax_code>
					<scope>Store</scope>
					<label>Tax Code2</label>
					<group>Prices</group>
					<input_type>boolean</input_type>
					<unique>Y</unique>
					<product_types><![CDATA[simple,configurable,virtual,bundle,downloadable]]></product_types>
					<default><![CDATA[N]]></default>
				</tax_code>
			</default>
		</eb2cproduct_attributes>';
}
