<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Catalog_Test_Helper_Map_AttributeTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Data $coreHelper */
    protected $coreHelper;

    public function setUp()
    {
        parent::setUp();
        $this->coreHelper = Mage::helper('eb2ccore');
    }

    /**
     * Test _getEntityTypeId method with the following expectations
     * Expectation 1: when this test invoked this method EbayEnterprise_Catalog_Helper_Attribute::_getEntityTypeId
     *                will set the class property EbayEnterprise_Catalog_Helper_Attribute::_entityTypeId with a
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

        $attribute = Mage::helper('ebayenterprise_catalog/map_attribute');

        EcomDev_Utils_Reflection::setRestrictedPropertyValue($attribute, '_entityTypeId', null);

        $this->assertSame($entityTypeId, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $attribute,
            '_getEntityTypeId',
            array()
        ));
    }

    /**
     * Test _getAttributeCollection method with the following assumptions
     * Expectation 1: the class property EbayEnterprise_Catalog_Helper_Attribute::_attributeCollection
     *                will be set to a known value of null, so that when this test invoked the method
     *                EbayEnterprise_Catalog_Helper_Attribute::_getAttributeCollection the mocked
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

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
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
     * Expectation 1: when the method EbayEnterprise_Catalog_Helper_Attribute::getAttributeIdByName get invoked
     *                by this test it will be give an attribute code name and it will called the mocked method
     *                EbayEnterprise_Catalog_Helper_Attribute::_getAttributeCollection which will return a
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

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
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
     * Test extractColorValue method
     * Given the Color XML Node, ensure the setter is called with correctly parsed arguments.
     */
    public function testExtractColorValue()
    {
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML(
            '<root>
				<Color>
					<Code>700</Code>
					<Description xml:lang="en-us">Red</Description>
				</Color>
			</root>'
        );
        $xpath = new DOMXPath($doc);
        $nodeList = $xpath->query('Color', $doc->documentElement);

        $attrStub = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
            ->disableOriginalConstructor()
            ->setMethods(array('_setOptionValues'))
            ->getMock();

        $attrStub->expects($this->once())
            ->method('_setOptionValues')
            ->with(
                $this->identicalTo('color'),
                $this->identicalTo('700'),
                $this->identicalTo(
                    array(
                        'en-us' => 'Red'
                    )
                )
            )
            ->will($this->returnValue(0));

        $attrStub->extractColorValue($nodeList, Mage::getModel('catalog/product')); // Forcing a call to _setOptionValues is all we are doing
    }
    /**
     * Test _getAttributeOptionId method with the following expectations
     * Expectation 1: given the attribute code and option code when this test invoked the method
     *                EbayEnterprise_Catalog_Helper_Map_Attribute::_getAttributeOptionId it will
     *                instantiate a Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection class
     *                call serveral methods to filter by the attribute which will return value of a call to
     *                _getAttributeIdByName method, add field to filter by using the option value parameter
     *                then load and return the first item in the collection and return the option id if
     *                there's a valid first item option return otherwise return zero
     */
    public function testGetAttributeOptionId()
    {
        $attributeCode = 'color';
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
            ->method('getFirstItem')
            ->will($this->returnValue($eavOptionMock));
        $this->replaceByMock('resource_model', 'eav/entity_attribute_option_collection', $eavOptionCollection);

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
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
     * Expectation 1: this method EbayEnterprise_Catalog_Helper_Map_Attribute::extractConfigurableAttributesData
     *                will be inokved with a DOMNodeList object and a Mage_Catalog_Model_Product object
     */
    public function testExtractConfigurableAttributesData()
    {
        $catalogId = 54;
        $sku = '54-HTSC883399';
        $styleId = 'HTSC883399';
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

        $doc = $this->coreHelper->getNewDomDocument();
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
            ->setMethods(array('getTypeInstance', 'setTypeId', 'setTypeInstance', 'getSku', 'getStyleId'))
            ->getMock();
        $product->expects($this->at(2))
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
        $product->expects($this->once())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $product->expects($this->once())
            ->method('getStyleId')
            ->will($this->returnValue($styleId));

        $existedConfAttrData = array();

        $configurableTypeMock = $this->getModelMockBuilder('catalog/product_type_configurable')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigurableAttributesAsArray'))
            ->getMock();
        $configurableTypeMock->expects($this->once())
            ->method('getConfigurableAttributesAsArray')
            ->with($this->identicalTo($product))
            ->will($this->returnValue($existedConfAttrData));

        $product->expects($this->at(5))
            ->method('getTypeInstance')
            ->with($this->identicalTo(true))
            ->will($this->returnValue($configurableTypeMock));

        $coreHelper = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('extractNodeVal', 'getConfigModel'))
            ->getMock();
        $coreHelper->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodeList))
            ->will($this->returnValue($confAttr));
        $coreHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'catalogId' => $catalogId
            ))));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelper);

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
            ->disableOriginalConstructor()
            ->setMethods(array('_getConfiguredAttributeData', '_isSuperAttributeExists', '_isAttributeInSet', '_turnOffManageStock'))
            ->getMock();
        $attributeHelperMock->expects($this->exactly(2))
            ->method('_getConfiguredAttributeData')
            ->will($this->returnValueMap(array(
                array($data[0], $result[0]),
                array($data[1], $result[1]),
            )));
        $attributeHelperMock->expects($this->any())
            ->method('_isAttributeInSet')
            ->will($this->returnValue(true));
        $attributeHelperMock->expects($this->exactly(2))
            ->method('_isSuperAttributeExists')
            ->will($this->returnValueMap(array(
                array($existedConfAttrData, $data[0], false),
                array($existedConfAttrData, $data[1], false),
            )));
        $attributeHelperMock->expects($this->once())
            ->method('_turnOffManageStock')
            ->with($this->identicalTo($product))
            ->will($this->returnSelf());

        $this->assertSame($result, $attributeHelperMock->extractConfigurableAttributesData($nodeList, $product));
    }

    /**
     * Test extractConfigurableAttributesData method for the following expectations
     * Expectation 1: the size attribute will not be present in the result since it will not be in the
     *                attribute set.
     */
    public function testExtractConfigurableAttributesDataNotInSet()
    {
        $catalogId = 54;
        $sku = '54-HTSC883399';
        $styleId = 'HTSC883399';
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

        $doc = new EbayEnterprise_Dom_Document('1.0', 'UTF-8');
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
            ->setMethods(array('getTypeInstance', 'setTypeId', 'setTypeInstance', 'getSku', 'getStyleId'))
            ->getMock();
        $product->expects($this->at(2))
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
        $product->expects($this->once())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $product->expects($this->once())
            ->method('getStyleId')
            ->will($this->returnValue($styleId));

        $existedConfAttrData = array();

        $configurableTypeMock = $this->getModelMockBuilder('catalog/product_type_configurable')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigurableAttributesAsArray'))
            ->getMock();
        $configurableTypeMock->expects($this->once())
            ->method('getConfigurableAttributesAsArray')
            ->with($this->identicalTo($product))
            ->will($this->returnValue($existedConfAttrData));

        $product->expects($this->at(5))
            ->method('getTypeInstance')
            ->with($this->identicalTo(true))
            ->will($this->returnValue($configurableTypeMock));

        $coreHelper = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('extractNodeVal', 'getConfigModel'))
            ->getMock();
        $coreHelper->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodeList))
            ->will($this->returnValue($confAttr));
        $coreHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'catalogId' => $catalogId
            ))));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelper);

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
            ->disableOriginalConstructor()
            ->setMethods(array('_getConfiguredAttributeData', '_isSuperAttributeExists', '_isAttributeInSet', '_turnOffManageStock'))
            ->getMock();
        $attributeHelperMock->expects($this->exactly(1))
            ->method('_getConfiguredAttributeData')
            ->with($this->identicalTo($data[0]))
            ->will($this->returnValue($result[0]));
        $attributeHelperMock->expects($this->any())
            ->method('_isAttributeInSet')
            ->will($this->returnValueMap(array(
                array('color', $product, true),
                array('size', $product, false),
            )));
        $attributeHelperMock->expects($this->exactly(2))
            ->method('_isSuperAttributeExists')
            ->will($this->returnValueMap(array(
                array($existedConfAttrData, $data[0], false),
                array($existedConfAttrData, $data[1], false),
            )));
        $attributeHelperMock->expects($this->once())
            ->method('_turnOffManageStock')
            ->with($this->identicalTo($product))
            ->will($this->returnSelf());
        $this->assertSame(array($result[0]), $attributeHelperMock->extractConfigurableAttributesData($nodeList, $product));
    }

    /**
     * @see self::testExtractConfigurableAttributesData test, however this test will be focusing on the scenario where
     *      given Mage_Catalog_Model_Product::getSku method don't match the Mage_Catalog_Model_Product::getStyleId and we
     *      expect method Mage_Catalog_Model_Product::getTypeInstance to never be called and we expect the return value
     *      to be null
     */
    public function testExtractConfigurableAttributesDataWhenChildProductIsFound()
    {
        $sku = '54-HTSC883399';
        $styleId = '54-OHS3323';

        $nodeList = new DOMNodeList();
        $product = Mage::getModel('catalog/product')->addData(array('sku' => $sku, 'style_id' => $styleId));

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame(null, $attributeHelperMock->extractConfigurableAttributesData($nodeList, $product));
    }

    /**
     * Test _getConfiguredAttributeData method with the following assumptions
     * Expectation 1: the method EbayEnterprise_Catalog_Helper_Map_Attribute::_getConfiguredAttributeData with a
     *                given attribute code will invoked the mocked method EbayEnterprise_Catalog_Helper_Map_Attribute::_getAttributeInstanceByName
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

        $attributeHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map_attribute')
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
     * Expectation 1: this test will inokved the method EbayEnterprise_Catalog_Helper_Map_Attribute::_getConfigurableAttributeModel
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

        $attributeHelper = Mage::helper('ebayenterprise_catalog/map_attribute');

        $this->assertSame($configurableAttributeMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $attributeHelper,
            '_getConfigurableAttributeModel',
            array($attributeMock)
        ));
    }

    /**
     * Test extractCanSaveConfigurableAttributes method with the following expectations
     * Expectation 1: when this test invoked the method EbayEnterprise_Catalog_Helper_Map_Attribute::extractCanSaveConfigurableAttributes
     *                with a given DOMNodeList will return true
     */
    public function testExtractCanSaveConfigurableAttributes()
    {
        $result = true;
        $value = 'color';
        $doc = $this->coreHelper->getNewDomDocument();
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

        $this->assertSame($result, Mage::helper('ebayenterprise_catalog/map_attribute')->extractCanSaveConfigurableAttributes($nodeList));
    }

    /**
     * Test _getAttributeInstanceByName method with the following expectations
     * Expectation 1: the method EbayEnterprise_Catalog_Helper_Map_Attribute::_getAttributeInstanceByName when invoked
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

        $attributeHelper = Mage::helper('ebayenterprise_catalog/map_attribute');
        $this->assertSame($attributeMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $attributeHelper,
            '_getAttributeInstanceByName',
            array($attributeCode)
        ));
    }

    /**
     * Test isSuperAttributeExists method for the following expectations
     * Expectation 1: when this test invoked the method EbayEnterprise_Catalog_Helper_Map_Attribute::isSuperAttributeExists
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

        $attributeHelper = Mage::helper('ebayenterprise_catalog/map_attribute');
        foreach ($testData as $data) {
            $this->assertSame($data['expect'], EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $attributeHelper,
                '_isSuperAttributeExists',
                array($data['attributeData'], $data['attributeCode'])
            ));
        }
    }

    public function provideAttributeCodes()
    {
        return array(
            array('is_in_set', true),
            array('not_in_set', false),
        );
    }
    /**
     * return true if the attribute is in a product's attribute set
     * @dataProvider provideAttributeCodes
     */
    public function testIsInAttributeSet($attribute, $result)
    {
        $helper = Mage::helper('ebayenterprise_catalog/map_attribute');
        $product = $this->getModelMock('catalog/product', array('getTypeInstance'));
        $typeInstance = $this->getModelMockBuilder('catalog/product_type_abstract')
            ->disableOriginalConstructor()
            ->setMethods(array('getSetAttributes'))
            ->getMock();

        $product->expects($this->any())
            ->method('getTypeInstance')
            ->will($this->returnValue($typeInstance));
        $typeInstance->expects($this->any())
            ->method('getSetAttributes')
            ->will($this->returnValue(array('is_in_set' => new Varien_Object())));

        $this->assertSame(
            $result,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, '_isAttributeInSet', array($attribute, $product))
        );
    }
    /**
     * Test that the method EbayEnterprise_Catalog_Helper_Map_Attribute::_turnOffManageStock
     * will turn of manage stock on a product when called by this test.
     */
    public function testTurnOffManageStock()
    {
        $product = Mage::getModel('catalog/product');
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', array('extractStockData'));
        $stock->expects($this->once())
            ->method('extractStockData')
            ->with($this->isInstanceOf('DOMNodeList'), $product)
            ->will($this->returnValue(null));
        $this->replaceByMock('helper', 'ebayenterprise_catalog/map_stock', $stock);

        $attribute = Mage::helper('ebayenterprise_catalog/map_attribute');

        $this->assertSame($attribute, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $attribute,
            '_turnOffManageStock',
            array($product)
        ));
    }

    /**
     * Scenario: Extract size option
     * Given an XML NodeList object containing size data.
     * When the callback extracts the size option.
     * Then The size option id is returned.
     */
    public function testExtractSizeValue()
    {
        /** @var int $optionId */
        $optionId = 89;
        /** @var DOMDocument $doc */
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML(
            '<root>
                <Size>
                    <Code>77</Code>
                    <Description xml:lang="en-us">Small</Description>
                </Size>
            </root>'
        );
        /** @var DOMXPath $xpath */
        $xpath = $this->coreHelper->getNewDomXPath($doc);
        /** @var DOMNodeList $nodeList */
        $nodeList = $xpath->query('Size', $doc->documentElement);

        /** @var Mock_EbayEnterprise_Catalog_Helper_Map_Attribute $mapAttribute */
        $mapAttribute = $this->getHelperMock('ebayenterprise_catalog/map_attribute', ['_setOptionValues']);
        $mapAttribute->expects($this->once())
            ->method('_setOptionValues')
            ->with($this->identicalTo('size'), $this->identicalTo('77'), $this->identicalTo(['en-us' => 'Small']))
            ->will($this->returnValue($optionId));
        $this->assertSame($optionId, $mapAttribute->extractSizeValue($nodeList));
    }
}
