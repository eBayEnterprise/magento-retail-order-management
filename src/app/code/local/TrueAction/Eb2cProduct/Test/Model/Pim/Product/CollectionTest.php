<?php

class TrueAction_Eb2cProduct_Test_Model_Pim_Product_Collection
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test formatting a sku, client id and catalog id into an item identifier
	 * for an item in the collection.
	 * @test
	 */
	public function testFormatId()
	{
		$collection = Mage::getModel('eb2cproduct/pim_product_collection');
		$this->assertSame(
			'45-12345-clientId-catalogId',
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$collection,
				'_formatId',
				array('45-12345', 'clientId', 'catalogId')
			)
		);
	}
	/**
	 * Create the item's identifier by using the sku, client id and catalog id
	 * of the item to create an ID.
	 * @test
	 */
	public function testGetItemId()
	{
		$item = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->setMethods(array('getSku', 'getClientId', 'getCatalogId'))
			->getMock();
		$collection = $this->getModelMock('eb2cproduct/pim_product_collection', array('_formatId'));

		$item->expects($this->once())
			->method('getSku')
			->will($this->returnValue('45-12345'));
		$item->expects($this->once())
			->method('getClientId')
			->will($this->returnValue('clientId'));
		$item->expects($this->once())
			->method('getCatalogId')
			->will($this->returnValue('catalogId'));

		$collection->expects($this->once())
			->method('_formatId')
			->with($this->identicalTo('45-12345'), $this->identicalTo('clientId'), $this->identicalTo('catalogId'))
			->will($this->returnValue('45-12345-clientId-catalogId'));

		$this->assertSame(
			'45-12345-clientId-catalogId',
			EcomDev_Utils_Reflection::invokeRestrictedMethod($collection, '_getItemId', array($item))
		);
	}

	/**
	 * Test getting an item from the collection for a given product.
	 * @test
	 */
	public function testGetItemForProduct()
	{
		$sku = '45-12345';
		$clientId = 'client_id';
		$catalogId = 'catalog_id';
		$id = "{$sku}-{$clientId}-{$catalogId}";

		$collection = $this->getModelMock(
			'eb2cproduct/pim_product_collection',
			array('getItemById', '_formatId')
		);

		$item = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->getMock();
		$product = $this->getModelMock('catalog/product', array('getSku', 'getStore'));
		$store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()->getMock();
		$config = $this->buildCoreConfigRegistry(array('clientId' => $clientId, 'catalogId' => $catalogId));
		$prodHelper = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));

		$this->replaceByMock('helper', 'eb2cproduct', $prodHelper);

		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue($sku));
		$product->expects($this->once())
			->method('getStore')
			->will($this->returnValue($store));
		$prodHelper->expects($this->once())
			->method('getConfigModel')
			->with($this->identicalTo($store))
			->will($this->returnValue($config));
		$collection->expects($this->once())
			->method('_formatId')
			->with($this->identicalTo($sku), $this->identicalTo($clientId), $this->identicalTo($catalogId))
			->will($this->returnValue($id));
		$collection->expects($this->once())
			->method('getItemById')
			->with($this->identicalTo($id))
			->will($this->returnValue($item));

		$this->assertSame($item, $collection->getItemForProduct($product));
	}
	/**
	 * Will return the first item if there are items in the collection.
	 * @test
	 */
	public function testGetFirstItem()
	{
		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->getMock();
		$collection = Mage::getModel('eb2cproduct/pim_product_collection');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collection, '_items', array($pimProduct));
		$this->assertSame($pimProduct, $collection->getFirstItem());
	}
	/**
	 * When there are no items in the collection, should thrown an exception.
	 * @test
	 */
	public function testGetFirstItemEmptyCollection()
	{
		$collection = Mage::getModel('eb2cproduct/pim_product_collection');
		$this->setExpectedException('TrueAction_Eb2cProduct_Model_Pim_Product_Collection_Exception', 'TrueAction_Eb2cProduct_Model_Pim_Product_Collection::getFirstItem cannot get item from an empty collection');
		$collection->getFirstItem();
	}
	/**
	 * Will return the last item if there are items in the collection.
	 * @test
	 */
	public function testGetLastItem()
	{
		$pimProduct = $this->getModelMockBuilder('eb2cproduct/pim_product')
			->disableOriginalConstructor()
			->getMock();
		$collection = Mage::getModel('eb2cproduct/pim_product_collection');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($collection, '_items', array($pimProduct));
		$this->assertSame($pimProduct, $collection->getLastItem());
	}
	/**
	 * When there are no items, should throw an exception.
	 * @test
	 */
	public function testGetLastItemEmptyCollection()
	{
		$collection = Mage::getModel('eb2cproduct/pim_product_collection');
		$this->setExpectedException('TrueAction_Eb2cProduct_Model_Pim_Product_Collection_Exception', 'TrueAction_Eb2cProduct_Model_Pim_Product_Collection::getLastItem cannot get item from an empty collection');
		$collection->getLastItem();
	}
	/**
	 * Cannot be implemented for the PIM Product model so will always raise a
	 * NotImplemented error (or something like that)
	 * @test
	 */
	public function testGetNewEmptyItem()
	{
		$this->setExpectedException('TrueAction_Eb2cCore_Exception_NotImplemented', 'TrueAction_Eb2cProduct_Model_Pim_Product_Collection::getNewEmptyItem is not implemented');
		$collection = Mage::getModel('eb2cproduct/pim_product_collection');
		$collection->getNewEmptyItem();
	}
}
