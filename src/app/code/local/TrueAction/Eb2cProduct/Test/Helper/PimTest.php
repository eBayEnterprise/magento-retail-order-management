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

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Pim::getGsiClientId method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Pim::getGsiClientId method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                TrueAction_Dom_Document object
	 * Expectation 2: the method TrueAction_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked TrueAction_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object, the method TrueAction_Eb2cCore_Helper_Feed::getClientId will be called
	 *                and return the client id which will be set in the DOMAttr value property
	 */
	public function testGetGsiClientId()
	{
		$attrValue = '';
		$clientId = 'ABCD';
		$attribute = '_gsi_client_id';
		$domAttr = new DOMAttr('gsi_client_id', $clientId);

		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getClientId'))
			->getMock();
		$feedHelper->expects($this->once())
			->method('getClientId')
			->will($this->returnValue($clientId));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->getGsiClientId($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Pim::getOperationType method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Pim::getOperationType method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                TrueAction_Dom_Document object
	 * Expectation 2: the method TrueAction_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked TrueAction_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object
	 */
	public function testGetOperationType()
	{
		$attrValue = '';
		$operationType = TrueAction_Eb2cProduct_Helper_Pim::DEFAULT_OPERATION_TYPE;
		$attribute = '_operation_type';
		$domAttr = new DOMAttr('operation_type', $operationType);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->getOperationType($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Pim::getCatalogId method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Pim::getCatalogId method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                TrueAction_Dom_Document object
	 * Expectation 2: the method TrueAction_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked TrueAction_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object, the method TrueAction_Eb2cCore_Helper_Feed::getCatalogId will be called
	 *                and return the client id which will be set in the DOMAttr value property
	 */
	public function testGetCatalogId()
	{
		$attrValue = '';
		$catalogId = '1234';
		$attribute = '_catalog_id';
		$domAttr = new DOMAttr('catalog_id', $catalogId);

		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getCatalogId'))
			->getMock();
		$feedHelper->expects($this->once())
			->method('getCatalogId')
			->will($this->returnValue($catalogId));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->getCatalogId($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Pim::getStoreId method for the following expectations
	 * Expectation 1: the method TrueAction_Eb2cProduct_Helper_Pim::getStoreId method will be invoked by this test
	 *                given an attribute value, an attribute code, a mocked Mage_Catalog_Model_Product and mocked
	 *                TrueAction_Dom_Document object
	 * Expectation 2: the method TrueAction_Eb2cProduct_Helper_Pim::_getDomAttr is expected to be invoked and given
	 *                the mocked TrueAction_Dom_Document object and the attribute code which will in turn returned
	 *                the DOMAttr object, the method TrueAction_Eb2cCore_Helper_Feed::getStoreId will be called
	 *                and return the client id which will be set in the DOMAttr value property
	 */
	public function testGetStoreId()
	{
		$attrValue = '';
		$storeId = '9383';
		$attribute = '_store_id';
		$domAttr = new DOMAttr('store_id', $storeId);

		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$feedHelper->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue($storeId));
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$domMock = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(array('_getDomAttr'))
			->getMock();
		$pimHelperMock->expects($this->once())
			->method('_getDomAttr')
			->with($this->identicalTo($domMock), $this->identicalTo($attribute))
			->will($this->returnValue($domAttr));

		$this->assertSame($domAttr, $pimHelperMock->getStoreId($attrValue, $attribute, $productMock, $domMock));
	}

	/**
	 * Test TrueAction_Eb2cProduct_Helper_Pim::_getDomAttr method for the following expectations
	 * Expectation 1: this test will invoked the method TrueAction_Eb2cProduct_Helper_Pim::_getDomAttr given a mocked
	 *                TrueAction_Dom_Document object and a string of attribute code it will then call the method
	 *                TrueAction_Dom_Document::createAttribute given a normalize attribut code
	 *
	 */
	public function testGetDomAttr()
	{
		$nodeAttribute = '_gsi_client_id';
		$normalizeAttribute = 'gsi_client_id';
		$domAttr = new DOMAttr($normalizeAttribute);

		$docMock = $this->getMockBuilder('TrueAction_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('createAttribute'))
			->getMock();
		$docMock->expects($this->once())
			->method('createAttribute')
			->with($this->identicalTo($normalizeAttribute))
			->will($this->returnValue($domAttr));

		$pimHelperMock = $this->getHelperMockBuilder('eb2cproduct/pim')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($domAttr, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$pimHelperMock, '_getDomAttr', array($docMock, $nodeAttribute)
		));
	}
}
