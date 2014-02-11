<?php

class TrueAction_Eb2cProduct_Test_Model_Pim_ProductTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify loads a product and calls the factory for each pim attribute
	 * @test
	 */
	public function testLoadPimAttributesByProduct()
	{
		$doc = new TrueAction_Dom_Document();
		$product = $this->getModelMock('catalog/product', array('getSku', 'getStoreId', 'getAttributes'));
		$attribute = $this->getModelMockBuilder('catalog/entity_attribute')
			->disableOriginalConstructor()
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(array('getPimAttribute'))
			->getMock();
		$this->replaceByMock('singleton', 'eb2cproduct/pim_attribute_factory', $factory);

		$pimAttribute = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
			->disableOriginalConstructor()
			->getMock();

		$product->expects($this->once())
			->method('getAttributes')
			->will($this->returnValue(array('attribute_code' => $attribute)));
		$factory->expects($this->once())
			->method('getPimAttribute')
			->with($this->identicalTo($attribute), $this->identicalTo($product), $doc)
			->will($this->returnValue($pimAttribute));

		$pimProduct = Mage::getModel(
			'eb2cproduct/pim_product',
			array('client_id' => 'CID', 'catalog_id' => '45', 'sku' => '45-12345')
		);
		$pimProduct->loadPimAttributesByProduct($product, $doc);
		$this->assertSame(array($pimAttribute), $pimProduct->getPimAttributes(), $doc);
	}
	/**
	 * Any null values returned by the factory should be filtered out of the list
	 * of PIM attribute models
	 * @test
	 */
	public function testLoadPimAttributesByProductFilterNullValues()
	{
		$doc = new TrueAction_Dom_Document();
		$product = $this->getModelMock('catalog/product', array('getSku', 'getStoreId', 'getAttributes'));
		$attribute = $this->getModelMockBuilder('catalog/entity_attribute')
			->disableOriginalConstructor()
			->getMock();
		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(array('getPimAttribute'))
			->getMock();
		$this->replaceByMock('singleton', 'eb2cproduct/pim_attribute_factory', $factory);

		$pimAttribute = null;

		$product->expects($this->once())
			->method('getAttributes')
			->will($this->returnValue(array('attribute_code' => $attribute)));
		$factory->expects($this->once())
			->method('getPimAttribute')
			->with($this->identicalTo($attribute), $this->identicalTo($product), $doc)
			->will($this->returnValue($pimAttribute));

		$pimProduct = Mage::getModel(
			'eb2cproduct/pim_product',
			array('client_id' => 'CID', 'catalog_id' => '45', 'sku' => '45-12345')
		);
		$pimProduct->loadPimAttributesByProduct($product, $doc);
		$this->assertSame(array(), $pimProduct->getPimAttributes());
	}
	/**
	 * verify an exception is thrown when missing arguments
	 * @test
	 */
	public function testConstructInvalidArguments()
	{
		$initArray = array();
		$this->setExpectedException(
			'Exception',
			'User Error: TrueAction_Eb2cProduct_Model_Pim_Product::_construct missing arguments: client_id, catalog_id, sku'
		);
		$pimProduct = Mage::getModel('eb2cproduct/pim_product', $initArray);
	}
	public function testConstructor()
	{
		$constructorArgs = array(
			'client_id' => 'ClientId',
			'catalog_id' => 'CatalogId',
			'sku' => '45-12345',
			'pim_attributes' => array(1,2,3),
		);
		$pimProduct = Mage::getModel('eb2cproduct/pim_product', $constructorArgs);
		$this->assertSame('ClientId', $pimProduct->getClientId());
		$this->assertSame('CatalogId', $pimProduct->getCatalogId());
		$this->assertSame('45-12345', $pimProduct->getSku());
		// pim_attributes should always be set to an empty array when constructing
		// a new PIM Product model.
		$this->assertSame(array(), $pimProduct->getPimAttributes());
	}
}
