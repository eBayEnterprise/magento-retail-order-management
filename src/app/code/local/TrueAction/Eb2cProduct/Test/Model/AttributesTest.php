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
	 * verify attributes are applied on the specified attributeset.
	 * attributeset is a valid attribute set model
	 * attribute to add is tax_code
	 * attribute doesnt exist
	 * attribute not already applied to attribute set
	 * group is testgroup
	 * group already exists
	 */
	public function testApplyDefaultAttributes()
	{
		$attrCode       = 'tax_code';
		$configNode     = new Varien_SimpleXml_Element(self::$configXml);
		$entityTypeId   = 10;
		list($attrNode) = $configNode->xpath('default/' . $attrCode);
		$protoData      = array();

		list($defaultNode) = $configNode->xpath('default');

		$attrConfig = $this->getModelMock('core/config', array('getNode'));
		$attrConfig->expects($this->any())
			->method('getNode')
			->with($this->identicalTo('default'))
			->will($this->returnValue($defaultNode));

		$setup = $this->getResourceModelMockBuilder('catalog/eav_mysql4_setup')
			->disableOriginalConstructor()
			->setMethods(array('addAttribute', 'getAttribute'))
			->getMock();
		$setup->expects($this->once())
			->method('addAttribute')
			->with(
				$this->identicalTo($entityTypeId),
				$this->identicalTo($attrCode),
				$this->identicalTo($protoData)
			)
			->will($this->returnSelf());
		$setup->expects($this->once())
			->method('getAttribute')
			->with(
				$this->identicalTo($entityTypeId),
				$this->identicalTo($attrCode),
				$this->identicalTo('attribute_id')
			)
			->will($this->returnValue(9000));

		$methods = array('_loadDefaultAttributesConfig', '_getTargetEntityTypeIds', '_getPrototypeData');
		$model   = $this->getModelMock('eb2cproduct/attributes', $methods);
		$this->_reflectProperty($model, '_eavSetup')->setValue($model, $setup);
		$model->expects($this->once())
			->method('_getTargetEntityTypeIds')
			->will($this->returnValue(array($entityTypeId)));
		$model->expects($this->once())
			->method('_loadDefaultAttributesConfig')
			->will($this->returnValue($attrConfig));
		$model->expects($this->once())
			->method('_getPrototypeData')
			->will($this->returnValue($protoData));

		$model->applyDefaultAttributes();
	}

	/**
	 * verify the function returns true if the attribute set's entity id
	 * is a valid entity id.
	 * @param  int $eid1
	 * @param  int $eid2
	 * @param  bool $expect
	 * @dataProvider dataProvider
	 */
	public function testIsValidEntityType($eid1, $eid2, $expect)
	{
		$this->markTestIncomplete();
		$attrSet = $this->getModelMock('eav/entity_attribute_set', array('getEntityTypeId'));
		$attrSet->expects($this->once())
			->method('getEntityTypeId')
			->will($this->returnValue($eid1));
		$model   = $this->getModelMock('eb2cproduct/attributes', array('_getDefaultEntityTypeId'));
		$model->expects($this->once())
			->method('_getDefaultEntityTypeId')
			->will($this->returnValue($eid2));
		$val = $this->_reflectMethod($model, '_isValidEntityType')->invoke($model, $attrSet);
		$this->assertSame($expect, $val);
	}
	/**
	 * verify a group is returned when successful and null when unsuccessful.
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testGetAttributeGroup($groupFound)
	{
		$groupFieldName = 'attribute_group_name';
		$groupName      = 'group';
		$attributeSetId = 1;
		$groupId       = 2;
		$e = $this->expected('%s-%s-%s', $groupName, $attributeSetId, (int)$groupFound);
		$mockCollection = $this->getResourceModelMockBuilder('eav/entity_attribute_group_collection')
			->disableOriginalConstructor()
			->setMethods(array('setAttributeSetFilter', 'load', 'addFieldToFilter', 'getFirstItem'))
			->getMock();
		$mock          = $this->getModelMockBuilder('eav/entity_attribute_group')
			->disableOriginalConstructor()
			->setMethods(array('getResourceCollection', 'getId'))
			->getMock();

		// mock out the collection methods
		$mockCollection->expects($this->once())->method('setAttributeSetFilter')
			->with($this->identicalTo($attributeSetId))
			->will($this->returnSelf());
		$mockCollection->expects($this->once())->method('addFieldToFilter')
			->with($this->identicalTo($groupFieldName), $this->equalTo($e->getGroupNameFilter()))
			->will($this->returnSelf());
		$mockCollection->expects($this->once())->method('load')
			->will($this->returnSelf());
		$mockCollection->expects($this->once())->method('getFirstItem')
			->will($this->returnValue($mock));

		// mock out the model methods
		$mock->expects($this->once())->method('getResourceCollection')
			->will($this->returnValue($mockCollection));
		$mock->expects($this->once())->method('getId')
			->will($this->returnValue($groupFound ? $groupId : null));

		$this->replaceByMock('model' ,'eav/entity_attribute_group', $mock);
		$model = Mage::getModel('eb2cproduct/attributes');
		$val   = $this->_reflectMethod($model, '_getAttributeGroup')->invoke($model, $groupName, $attributeSetId);
		if ($groupFound) {
			$this->assertInstanceOf('Mage_Eav_Model_Entity_Attribute_Group', $val);
		} else {
			$this->assertNull($val);
		}
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
