<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_AttributeTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getProductEntityTypeId method
	 * @test
	 */
	public function testGetProductEntityTypeId()
	{
		$eavEntityTypeModelMock = $this->getModelMockBuilder('eav/entity_type')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$eavEntityTypeModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(4));

		$resourceModelMock = $this->getModelMockBuilder('catalog/resource_product')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$resourceModelMock->expects($this->once())
			->method('getEntityType')
			->will($this->returnValue($eavEntityTypeModelMock));

		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getResource'))
			->getMock();
		$productModelMock->expects($this->once())
			->method('getResource')
			->will($this->returnValue($resourceModelMock));

		$this->replaceByMock('model', 'catalog/product', $productModelMock);

		$this->assertSame(4, Mage::getModel('eb2cproduct/feed_attribute')->getProductEntityTypeId());
	}

	/**
	 * Test getAttributeSetId method
	 * @test
	 */
	public function testGetAttributeSetId()
	{
		$attributeSetModelMock = $this->getModelMockBuilder('eav/entity_attribute_set')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeSetId'))
			->getMock();
		$attributeSetModelMock->expects($this->once())
			->method('getAttributeSetId')
			->will($this->returnValue(172));

		$collectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_set_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'load', 'getFirstItem'))
			->getMock();
		$collectionModelMock->expects($this->at(0))
			->method('addFieldToFilter')
			->with($this->equalTo('entity_type_id'), $this->equalTo(4))
			->will($this->returnSelf());
		$collectionModelMock->expects($this->at(1))
			->method('addFieldToFilter')
			->with($this->equalTo('attribute_set_name'), $this->equalTo('Default'))
			->will($this->returnSelf());
		$collectionModelMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$collectionModelMock->expects($this->once())
			->method('getFirstItem')
			->will($this->returnValue($attributeSetModelMock));

		$entityAttributeSetModelMock = $this->getModelMockBuilder('eav/entity_attribute_set')
			->disableOriginalConstructor()
			->setMethods(array('getCollection'))
			->getMock();
		$entityAttributeSetModelMock->expects($this->once())
			->method('getCollection')
			->will($this->returnValue($collectionModelMock));

		$this->replaceByMock('model', 'eav/entity_attribute_set', $entityAttributeSetModelMock);

		$this->assertSame(172, Mage::getModel('eb2cproduct/feed_attribute')->getAttributeSetId('Default', 4));
	}

	/**
	 * Test isAttributeInAttributeSet method
	 * @test
	 */
	public function testIsAttributeInAttributeSet()
	{
		$entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode', 'setAttributeSetId', 'loadEntityAttributeIdBySet', 'getEntityAttributeId'))
			->getMock();
		$entityAttributeModelMock->expects($this->once())
			->method('loadByCode')
			->with($this->equalTo('catalog_product'), $this->equalTo('color'))
			->will($this->returnSelf());
		$entityAttributeModelMock->expects($this->once())
			->method('setAttributeSetId')
			->with($this->equalTo(172))
			->will($this->returnSelf());
		$entityAttributeModelMock->expects($this->once())
			->method('loadEntityAttributeIdBySet')
			->will($this->returnSelf());
		$entityAttributeModelMock->expects($this->once())
			->method('getEntityAttributeId')
			->will($this->returnValue(193));
		$this->replaceByMock('model', 'eav/entity_attribute', $entityAttributeModelMock);

		$feedAttributeModelMock = $this->getModelMockBuilder('eb2cproduct/feed_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeSetId', 'getProductEntityTypeId'))
			->getMock();
		$feedAttributeModelMock->expects($this->once())
			->method('getAttributeSetId')
			->with($this->equalTo('Default'), $this->equalTo(4))
			->will($this->returnValue(172));
		$feedAttributeModelMock->expects($this->once())
			->method('getProductEntityTypeId')
			->will($this->returnValue(4));
		$this->replaceByMock('model', 'eb2cproduct/feed_attribute', $feedAttributeModelMock);

		$this->assertSame(true, Mage::getModel('eb2cproduct/feed_attribute')->isAttributeInAttributeSet('color', 'Default'));
	}

	/**
	 * Test getAttributeId method
	 * @test
	 */
	public function testGetAttributeId()
	{
		$entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode', 'getAttributeId'))
			->getMock();
		$entityAttributeModelMock->expects($this->once())
			->method('loadByCode')
			->with($this->equalTo('catalog_product'), $this->equalTo('color'))
			->will($this->returnSelf());
		$entityAttributeModelMock->expects($this->once())
			->method('getAttributeId')
			->will($this->returnValue(92));
		$this->replaceByMock('model', 'eav/entity_attribute', $entityAttributeModelMock);

		$this->assertSame(92, Mage::getModel('eb2cproduct/feed_attribute')->getAttributeId('color'));
	}

	/**
	 * Test addAttributeToAttributeSet method
	 * @test
	 */
	public function testAddAttributeToAttributeSet()
	{
		$productAttributeSetApiModelMock = $this->getModelMockBuilder('catalog/product_attribute_set_api')
			->disableOriginalConstructor()
			->setMethods(array('attributeAdd'))
			->getMock();
		$productAttributeSetApiModelMock->expects($this->once())
			->method('attributeAdd')
			->with(
				$this->equalTo(92),
				$this->equalTo(172),
				$this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Attribute::DEFAULT_ATTRIBUTE_SET_GROUP),
				$this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Attribute::DEFAULT_SORT_ORDER)
			)
			->will($this->returnSelf());
		$this->replaceByMock('model', 'catalog/product_attribute_set_api', $productAttributeSetApiModelMock);

		$feedAttributeModelMock = $this->getModelMockBuilder('eb2cproduct/feed_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeSetId', 'getAttributeId', 'getProductEntityTypeId'))
			->getMock();
		$feedAttributeModelMock->expects($this->once())
			->method('getAttributeSetId')
			->with($this->equalTo('Default'), $this->equalTo(4))
			->will($this->returnValue(172));
		$feedAttributeModelMock->expects($this->once())
			->method('getAttributeId')
			->with($this->equalTo('color'))
			->will($this->returnValue(92));
		$feedAttributeModelMock->expects($this->once())
			->method('getProductEntityTypeId')
			->will($this->returnValue(4));
		$this->replaceByMock('model', 'eb2cproduct/feed_attribute', $feedAttributeModelMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Attribute',
			Mage::getModel('eb2cproduct/feed_attribute')->addAttributeToAttributeSet('color', 'Default')
		);
	}

	/**
	 * Test addAttributeToAttributeSet method, where attribute add will throw exception
	 * @test
	 */
	public function testAddAttributeToAttributeSetWithException()
	{
		$productAttributeSetApiModelMock = $this->getModelMockBuilder('catalog/product_attribute_set_api')
			->disableOriginalConstructor()
			->setMethods(array('attributeAdd'))
			->getMock();
		$productAttributeSetApiModelMock->expects($this->once())
			->method('attributeAdd')
			->with(
				$this->equalTo(92),
				$this->equalTo(172),
				$this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Attribute::DEFAULT_ATTRIBUTE_SET_GROUP),
				$this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Attribute::DEFAULT_SORT_ORDER)
			)
			->will($this->throwException(
				new Mage_Api_Exception('UnitTest Simulate Throw Exception on attributeAdd')
			));
		$this->replaceByMock('model', 'catalog/product_attribute_set_api', $productAttributeSetApiModelMock);

		$feedAttributeModelMock = $this->getModelMockBuilder('eb2cproduct/feed_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeSetId', 'getAttributeId', 'getProductEntityTypeId'))
			->getMock();
		$feedAttributeModelMock->expects($this->once())
			->method('getAttributeSetId')
			->with($this->equalTo('Default'), $this->equalTo(4))
			->will($this->returnValue(172));
		$feedAttributeModelMock->expects($this->once())
			->method('getAttributeId')
			->with($this->equalTo('color'))
			->will($this->returnValue(92));
		$feedAttributeModelMock->expects($this->once())
			->method('getProductEntityTypeId')
			->will($this->returnValue(4));
		$this->replaceByMock('model', 'eb2cproduct/feed_attribute', $feedAttributeModelMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Attribute',
			Mage::getModel('eb2cproduct/feed_attribute')->addAttributeToAttributeSet('color', 'Default')
		);
	}
}
