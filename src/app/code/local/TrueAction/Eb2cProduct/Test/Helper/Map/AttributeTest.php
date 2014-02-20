<?php
class TrueAction_Eb2cProduct_Test_Helper_Map_AttributeTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test _getEntityTypeId method with the following expectations
	 * Expectation 1: when this test invoked this method TrueAction_Eb2cProduct_Helper_Attribute::_getEntityTypeId
	 *                will set the class property TrueAction_Eb2cProduct_Helper_Attribute::_entityTypeId with a
	 *                catalog/product entity type id
	 */
	public function testGetEntityTypeId()
	{
		$entityTypeId = 4;

		$resourceMock = $this->getResourceModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getTypeId'))
			->getMock();
		$resourceMock->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue($entityTypeId));

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getResource'))
			->getMock();
		$productMock->expects($this->once())
			->method('getResource')
			->will($this->returnValue($resourceMock));
		$this->replaceByMock('model', 'catalog/product', $productMock);

		$attribute = Mage::helper('eb2cproduct/map_attribute');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($attribute, '_entityTypeId', null);

		$this->assertSame($entityTypeId, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attribute,
			'_getEntityTypeId',
			array()
		));
	}

	/**
	 * Test _getAttributeCollection method with the following assumptions
	 * Expectation 1: the class property TrueAction_Eb2cProduct_Helper_Attribute::_attributeCollection
	 *                will be set to a known value of null, so that when this test invoked the method
	 *                TrueAction_Eb2cProduct_Helper_Attribute::_getAttributeCollection the mocked
	 *                methods Mage_Eav_Model_Resource_Entity_Attribute_Collection::addFieldToFilter and
	 *                addExpressionFieldToSelect will be invoked with the specific arguments specified in this test.
	 */
	public function testGetAttributeCollection()
	{
		$entityTypeId = 4;

		$collectionMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'addExpressionFieldToSelect'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('entity_type_id'), $this->identicalTo($entityTypeId))
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('addExpressionFieldToSelect')
			->with(
				$this->identicalTo('lcase_attr_code'),
				$this->identicalTo('LCASE({{attrcode}})'),
				$this->identicalTo(array('attrcode' => 'attribute_code'))
			)
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'eav/entity_attribute_collection', $collectionMock);

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_getEntityTypeId'))
			->getMock();
		$attributeHelperMock->expects($this->once())
			->method('_getEntityTypeId')
			->will($this->returnValue($entityTypeId));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($attributeHelperMock, '_attributeCollection', null);

		$this->assertSame($collectionMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelperMock,
			'_getAttributeCollection',
			array()
		));
	}

	/**
	 * Test getAttributeIdByName method with the following expectations
	 * Expectation 1: when the method TrueAction_Eb2cProduct_Helper_Attribute::getAttributeIdByName get invoked
	 *                by this test it will be give an attribute code name and it will called the mocked method
	 *                TrueAction_Eb2cProduct_Helper_Attribute::_getAttributeCollection which will return a
	 *                a mocked object of Mage_Eav_Model_Resource_Entity_Attribute_Collection and the called the
	 *                getItemByColumnValue, if an actual object is return will return the id or null otherwise
	 */
	public function testGetAttributeIdByName()
	{
		$attributeCode = 'color';
		$attributeId = 92;

		$attributeModelMock = $this->getModelMockBuilder('eav/attribute')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$attributeModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($attributeId));

		$collectionMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('getItemByColumnValue')
			->with($this->identicalTo('attribute_code'), $this->identicalTo($attributeCode))
			->will($this->returnValue($attributeModelMock));

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeCollection'))
			->getMock();
		$attributeHelperMock->expects($this->once())
			->method('_getAttributeCollection')
			->will($this->returnValue($collectionMock));

		$this->assertSame($attributeId, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelperMock,
			'_getAttributeIdByName',
			array($attributeCode)
		));
	}

	/**
	 * Test extracColorValue method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map_Attribute::extractColorValue
	 *                with the a DONNodeList will first extract data into a usable array of color data by calling the
	 *                mocked TrueAction_Eb2cProduct_Helper_Map_Attribute::_extractColorData method that take a DOMNodeList
	 *                object and return an array with code key and description key which is map to an array list of lang/value
	 * Expectation 2: with this array of color data the test expect the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_getDefaultStoreOptionId
	 *                which will take the attribute code, and the option label, it will return the option id for the default store
	 * Expectation 3: calling the mock method TrueAction_Eb2cProduct_Helper_Map_Attribute::_getLanguageCodeByStoreId with the store id
	 *                from the Mock Mage_Catalog_Model_Product::getStoreId method, the _getLanguageCodeByStoreId method will return the language code
	 *                for that specific store
	 * Expectation 4: with the color data and language code the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_retrieveColorByLanguage get called
	 *                and return the value match the lang on the color data array.
	 * Expectation 5: with the color and the current store id simply call the method
	 *                TrueAction_Eb2cProduct_Helper_Map_Attribute::saveOption($optionId, $storeId, $name)
	 *                which will save the option name for that store and return itself
	 */
	public function testExtractColorValue()
	{
		$colorCode = '700';
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<Color>
					<Code>700</Code>
					<Description xml:lang="en-US">Vanilla</Description>
					<Description xml:lang="fr-FR">Vanille</Description>
				</Color>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$nodeList = $xpath->query('Color/Code', $doc->documentElement);

		$coreHelper = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelper->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodeList))
			->will($this->returnValue($colorCode));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);

		$optionId = 172;
		$noOptionId = 0;

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeOptionId', '_addNewOption'))
			->getMock();
		$attributeHelperMock->expects($this->once())
			->method('_getAttributeOptionId')
			->with(
				$this->identicalTo(TrueAction_Eb2cProduct_Helper_Map_Attribute::COLOR),
				$this->identicalTo($colorCode)
			)
			->will($this->returnValue($noOptionId));
		$attributeHelperMock->expects($this->once())
			->method('_addNewOption')
			->with(
				$this->identicalTo(TrueAction_Eb2cProduct_Helper_Map_Attribute::COLOR),
				$this->identicalTo($colorCode)
			)
			->will($this->returnValue($optionId));

		$this->assertSame($optionId, $attributeHelperMock->extractColorValue($nodeList));
	}

	/**
	 * Test _addNewOption method with the following expectations
	 * Expectation 1: give an attribute code and a option code the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_addNewOption
	 *                when invoked by this test will get the attibute id from the attribute code that's pass to it by
	 *                calling TrueAction_Eb2cProduct_Helper_Map_Attribute::_getAttributeByName which will load the
	 *                Mage_Catalog_Model_Resource_Eav_Attribute object in which the addData will take an array with key
	 *                option contain value map with option id of zero and the default stored id map to the option code
	 *                then the save method will be called and the return value of the _getAttributeOptionId will be returned
	 */
	public function testAddNewOption()
	{
		$attributeCode = TrueAction_Eb2cProduct_Helper_Map_Attribute::COLOR;
		$optionCode = '700';
		$attributeId = 82;
		$optionId = 344;

		$eavAttributeMock = $this->getModelMockBuilder('catalog/resource_eav_attribute')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save'))
			->getMock();
		$eavAttributeMock->expects($this->once())
			->method('addData')
			->with($this->identicalTo(array(
				'option' => array('value' => array(0 => array(
					Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID => $optionCode
				)))
			)))
			->will($this->returnSelf());
		$eavAttributeMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_loadEavAttributeModel', '_getAttributeIdByName', '_getAttributeOptionId'))
			->getMock();
		$attributeHelperMock->expects($this->once())
			->method('_loadEavAttributeModel')
			->with($this->identicalTo($attributeId))
			->will($this->returnValue($eavAttributeMock));
		$attributeHelperMock->expects($this->once())
			->method('_getAttributeIdByName')
			->with($this->identicalTo($attributeCode))
			->will($this->returnValue($attributeId));
		$attributeHelperMock->expects($this->once())
			->method('_getAttributeOptionId')
			->with($this->identicalTo($attributeCode), $this->identicalTo($optionCode))
			->will($this->returnValue($optionId));

		$this->assertSame($optionId, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelperMock,
			'_addNewOption',
			array($attributeCode, $optionCode)
		));
	}

	/**
	 * Test _loadEavAttributeModel method with the following expectations
	 * Expectation 1: given an attribute id the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_loadEavAttributeModel will
	 *                instantiate the Mage_Catalog_Model_Resource_Eav_Attribute class object and load it with the given atttibute id
	 */
	public function testLoadEavAttributeModel()
	{
		$attributeId = 83;

		$eavAttributeMock = $this->getModelMockBuilder('catalog/resource_eav_attribute')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$eavAttributeMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'catalog/resource_eav_attribute', $eavAttributeMock);

		$helper = Mage::helper('eb2cproduct/map_attribute');
		$this->assertSame($eavAttributeMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$helper,
			'_loadEavAttributeModel',
			array($attributeId)
		));
	}

	/**
	 * Test _getAttributeOptionId method with the following expectations
	 * Expectation 1: given the attribute code and option code when this test invoked the method
	 *                TrueAction_Eb2cProduct_Helper_Map_Attribute::_getAttributeOptionId it will
	 *                instantiate a Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection class
	 *                call serveral methods to filter by the attribute which will return value of a call to
	 *                _getAttributeIdByName method, add field to filter by using the option value parameter
	 *                then load and return the first item in the collection and return the option id if
	 *                there's a valid first item option return otherwise return zero
	 */
	public function testGetAttributeOptionId()
	{
		$attributeCode = TrueAction_Eb2cProduct_Helper_Map_Attribute::COLOR;
		$optionValue = '700';
		$attributeId = 82;
		$optionId = 174;

		$eavOptionMock = $this->getModelMockBuilder('eav/entity_attribute_option')
			->disableOriginalConstructor()
			->setMethods(array('getOptionId'))
			->getMock();
		$eavOptionMock->expects($this->once())
			->method('getOptionId')
			->will($this->returnValue($optionId));

		$eavOptionCollection = $this->getResourceModelMockBuilder('eav/entity_attribute_option_collection')
			->disableOriginalConstructor()
			->setMethods(array('setAttributeFilter', 'addFieldToFilter', 'setStoreFilter', 'load', 'getFirstItem'))
			->getMock();
		$eavOptionCollection->expects($this->once())
			->method('setAttributeFilter')
			->with($this->identicalTo($attributeId))
			->will($this->returnSelf());
		$eavOptionCollection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('tdv.value'), $this->identicalTo($optionValue))
			->will($this->returnSelf());
		$eavOptionCollection->expects($this->once())
			->method('setStoreFilter')
			->with($this->identicalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID))
			->will($this->returnSelf());
		$eavOptionCollection->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$eavOptionCollection->expects($this->once())
			->method('getFirstItem')
			->will($this->returnValue($eavOptionMock));
		$this->replaceByMock('resource_model', 'eav/entity_attribute_option_collection', $eavOptionCollection);

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeIdByName'))
			->getMock();
		$attributeHelperMock->expects($this->once())
			->method('_getAttributeIdByName')
			->with($this->identicalTo($attributeCode))
			->will($this->returnValue($attributeId));

		$this->assertSame($optionId, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelperMock,
			'_getAttributeOptionId',
			array($attributeCode, $optionValue)
		));
	}

	/**
	 * Test extractConfigurableAttributesData method for the following expectations
	 * Expectation 1: this method TrueAction_Eb2cProduct_Helper_Map_Attribute::extractConfigurableAttributesData
	 *                will be inokved with a DOMNodeList object and a Mage_Catalog_Model_Product object
	 */
	public function testExtractConfigurableAttributesData()
	{
		$confAttr = 'color,size';
		$data = explode(',', $confAttr);
		$result = array(array(
			'id' => null,
			'label' => 'color',
			'position' => 0,
			'values' => array(),
			'attribute_id' => 922,
			'attribute_code' => 'color',
			'frontend_label' => 'color',
		), array(
			'id' => null,
			'label' => 'size',
			'position' => 0,
			'values' => array(),
			'attribute_id' => 923,
			'attribute_code' => 'size',
			'frontend_label' => 'size',
		));

		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<CustomAttributes>
					<Attribute name="ConfigurableAttributes">
						<Value>color</Value>
					</Attribute>
				</CustomAttributes>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$nodeList = $xpath->query('CustomAttributes/Attribute[@name="ConfigurableAttributes"]/Value', $doc->documentElement);

		$simpleTypeMock = $this->getModelMockBuilder('catalog/product_type_simple')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getTypeInstance', 'setTypeId', 'setTypeInstance'))
			->getMock();
		$product->expects($this->at(0))
			->method('getTypeInstance')
			->with($this->identicalTo(true))
			->will($this->returnValue($simpleTypeMock));
		$product->expects($this->once())
			->method('setTypeId')
			->with($this->identicalTo(Mage_catalog_Model_Product_Type::TYPE_CONFIGURABLE))
			->will($this->returnSelf());
		$product->expects($this->once())
			->method('setTypeInstance')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product_Type_Abstract'), $this->identicalTo(true))
			->will($this->returnSelf());

		$existedConfAttrData = array();

		$configurableTypeMock = $this->getModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('getConfigurableAttributesAsArray'))
			->getMock();
		$configurableTypeMock->expects($this->once())
			->method('getConfigurableAttributesAsArray')
			->with($this->identicalTo($product))
			->will($this->returnValue($existedConfAttrData));

		$product->expects($this->at(3))
			->method('getTypeInstance')
			->with($this->identicalTo(true))
			->will($this->returnValue($configurableTypeMock));

		$coreHelper = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelper->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodeList))
			->will($this->returnValue($confAttr));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_getConfiguredAttributeData', '_isSuperAttributeExists'))
			->getMock();
		$attributeHelperMock->expects($this->exactly(2))
			->method('_getConfiguredAttributeData')
			->will($this->returnValueMap(array(
				array($data[0], $result[0]),
				array($data[1], $result[1]),
			)));
		$attributeHelperMock->expects($this->exactly(2))
			->method('_isSuperAttributeExists')
			->will($this->returnValueMap(array(
				array($existedConfAttrData, $data[0], false),
				array($existedConfAttrData, $data[1], false),
			)));

		$this->assertSame($result, $attributeHelperMock->extractConfigurableAttributesData($nodeList, $product));
	}

	/**
	 * Test _getConfiguredAttributeData method with the following assumptions
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_getConfiguredAttributeData with a
	 *                given attribute code will invoked the mocked method TrueAction_Eb2cProduct_Helper_Map_Attribute::_getAttributeInstanceByName
	 *                which will return a Mage_Eav_Model_Entity_Attribute object
	 * Expectation 2: the mock method Mage_Catalog_Model_Product_Type_Configurable_Attribute::setProductAttribute will be invoked and passed
	 *                the Mage_Eav_Model_Entity_Attribute object
	 * Expectation 3: then the tested method will return an array with key value
	 */
	public function testGetConfiguredAttributeData()
	{
		$attributeCode = 'color';
		$result = array(
			'id' => null,
			'label' => 'Color',
			'position' => 0,
			'values' => array(),
			'attribute_id' => 922,
			'attribute_code' => 'color',
			'frontend_label' => 'Color',
		);

		$defaultMock = $this->getModelMockBuilder('eav/entity_attribute_frontend_default')
			->disableOriginalConstructor()
			->setMethods(array('getLabel'))
			->getMock();
		$defaultMock->expects($this->once())
			->method('getLabel')
			->will($this->returnValue($result['frontend_label']));

		$eavAttributeMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'getAttributeCode', 'getFrontEnd'))
			->getMock();
		$eavAttributeMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($result['attribute_id']));
		$eavAttributeMock->expects($this->once())
			->method('getAttributeCode')
			->will($this->returnValue($attributeCode));
		$eavAttributeMock->expects($this->once())
			->method('getFrontEnd')
			->will($this->returnValue($defaultMock));

		$configurableAttMock = $this->getModelMockBuilder('catalog/product_type_configurable_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'getLabel', 'getPosition'))
			->getMock();
		$configurableAttMock->expects($this->once())
			->method('getId')
			->will($this->returnValue($result['id']));
		$configurableAttMock->expects($this->once())
			->method('getLabel')
			->will($this->returnValue($result['label']));
		$configurableAttMock->expects($this->once())
			->method('getPosition')
			->will($this->returnValue($result['position']));

		$attributeHelperMock = $this->getHelperMockBuilder('eb2cproduct/map_attribute')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeInstanceByName', '_getConfigurableAttributeModel'))
			->getMock();
		$attributeHelperMock->expects($this->once())
			->method('_getAttributeInstanceByName')
			->with($this->identicalTo($attributeCode))
			->will($this->returnValue($eavAttributeMock));
		$attributeHelperMock->expects($this->once())
			->method('_getConfigurableAttributeModel')
			->with($this->identicalTo($eavAttributeMock))
			->will($this->returnValue($configurableAttMock));

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelperMock,
			'_getConfiguredAttributeData',
			array($attributeCode)
		));
	}

	/**
	 * Test _getConfigurableAttributeModel method with the following expectations
	 * Expectation 1: this test will inokved the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_getConfigurableAttributeModel
	 *                with a mock of Mage_Eav_Model_Entity_Attribute object and is expected to return a mock object of
	 *                class Mage_Catalog_Model_Product_Type_Configurable_Attribute
	 */
	public function testGetConfigurableAttributeModel()
	{
		$attributeMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$configurableAttributeMock = $this->getModelMockBuilder('catalog/product_type_configurable_attribute')
			->disableOriginalConstructor()
			->setMethods(array('setProductAttribute'))
			->getMock();
		$configurableAttributeMock->expects($this->once())
			->method('setProductAttribute')
			->with($this->identicalTo($attributeMock))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'catalog/product_type_configurable_attribute', $configurableAttributeMock);

		$attributeHelper = Mage::helper('eb2cproduct/map_attribute');

		$this->assertSame($configurableAttributeMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelper,
			'_getConfigurableAttributeModel',
			array($attributeMock)
		));
	}

	/**
	 * Test extractCanSaveConfigurableAttributes method with the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map_Attribute::extractCanSaveConfigurableAttributes
	 *                with a given DOMNodeList will return true
	 */
	public function testExtractCanSaveConfigurableAttributes()
	{
		$result = true;
		$value = 'color';
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<root>
				<foo>
					<value>color</value>
				</foo>
			</root>'
		);

		$xpath = new DOMXPath($doc);

		$nodeList = $xpath->query('foo/value', $doc->documentElement);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('extractNodeVal'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('extractNodeVal')
			->with($this->identicalTo($nodeList))
			->will($this->returnValue($value));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$this->assertSame($result, Mage::helper('eb2cproduct/map_attribute')->extractCanSaveConfigurableAttributes($nodeList));
	}

	/**
	 * Test _getAttributeInstanceByName method with the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Map_Attribute::_getAttributeInstanceByName when invoked
	 *                by this test given the attribute code will return a new mocked instance of
	 *                Mage_Eav_Model_Entity_Attribute with mocked method loadByCode
	 */
	public function testGetAttributeInstanceByName()
	{
		$attributeCode = 'color';
		$attributeMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode'))
			->getMock();
		$attributeMock->expects($this->once())
			->method('loadByCode')
			->with($this->identicalTo(Mage_Catalog_Model_Product::ENTITY), $this->identicalTo($attributeCode))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eav/entity_attribute', $attributeMock);

		$attributeHelper = Mage::helper('eb2cproduct/map_attribute');
		$this->assertSame($attributeMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$attributeHelper,
			'_getAttributeInstanceByName',
			array($attributeCode)
		));
	}

	/**
	 * Test isSuperAttributeExists method for the following expectations
	 * Expectation 1: when this test invoked the method TrueAction_Eb2cProduct_Helper_Map_Attribute::isSuperAttributeExists
	 *                with a given attributeData containing the key (attributeCode) and a second parameter of the attribute code
	 *                to check if exists in the array of attribute data it will return true if found false if not found
	 */
	public function testIsSuperAttributeExists()
	{
		$testData = array(
			array(
				'expect' => true,
				'attributeData' => array(array('attribute_code' => 'color')),
				'attributeCode' => 'color',
			),
			array(
				'expect' => false,
				'attributeData' => array(array('attribute_code' => 'color')),
				'attributeCode' => 'size'
			),
		);

		$attributeHelper = Mage::helper('eb2cproduct/map_attribute');
		foreach ($testData as $data) {
			$this->assertSame($data['expect'], EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$attributeHelper,
				'_isSuperAttributeExists',
				array($data['attributeData'], $data['attributeCode'])
			));
		}
	}
}
