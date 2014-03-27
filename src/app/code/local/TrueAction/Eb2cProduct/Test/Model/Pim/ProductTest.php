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
		$key = 'item_map';
		$attributes = array('_gsi_client_id','sku');

		$result = array();
		for ($i=0; $i < 3; $i++) {
			$result[] = $this->getModelMockBuilder('eb2cproduct/pim_attribute')
				->disableOriginalConstructor()
				->getMock();
		}

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();

		$product = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$factory = $this->getModelMockBuilder('eb2cproduct/pim_attribute_factory')
			->disableOriginalConstructor()
			->setMethods(array('getPimAttribute'))
			->getMock();
		$factory->expects($this->exactly(2))
			->method('getPimAttribute')
			->will($this->returnValueMap(array(
				array($attributes[0], $product, $doc, $key, $result[1]),
				array($attributes[1], $product, $doc, $key, $result[2])
			)));
		$this->replaceByMock('singleton', 'eb2cproduct/pim_attribute_factory', $factory);

		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('setPimAttributes', 'getPimAttributes'))
			->getMock();
		$pimProduct->expects($this->once())
			->method('setPimAttributes')
			->with($this->identicalTo($result))
			->will($this->returnSelf());
		$pimProduct->expects($this->once())
			->method('getPimAttributes')
			->will($this->returnValue(array($result[0])));

		$this->assertSame($pimProduct, $pimProduct->loadPimAttributesByProduct($product, $doc, $key, $attributes));
	}
	/**
	 * verify an exception is thrown when missing arguments
	 * @test
	 */
	public function testConstructInvalidArguments()
	{
		$expectedException = sprintf(
			TrueAction_Eb2cProduct_Model_Pim_Product::ERROR_INVALID_ARGS,
			'TrueAction_Eb2cProduct_Model_Pim_Product::_construct',
			'client_id, catalog_id, sku'
		);
		$initParams = array();
		$this->setExpectedException('Exception', $expectedException);

		$helper = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('triggerError'))
			->getMock();
		$helper->expects($this->once())
			->method('triggerError')
			->with($this->identicalTo($expectedException))
			->will($this->throwException(new Exception($expectedException)));
		$this->replaceByMock('helper', 'eb2ccore', $helper);

		$product = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		EcomDev_Utils_Reflection::invokeRestrictedMethod($product, '_construct', array($initParams));
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
