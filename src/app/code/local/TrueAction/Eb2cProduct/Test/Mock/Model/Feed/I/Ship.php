<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Feed_I_Ship extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_I_Ship class
	 *
	 * @return void
	 */
	public function replaceByMockWithInvalidProductId()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithInvalidProductId()));

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_I_Ship class
	 *
	 * @return void
	 */
	public function replaceByMockWithValidProductId()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithValidProductId()));

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_I_Ship class
	 *
	 * @return void
	 */
	public function replaceByMockWithInvalidProductException()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithInvalidProductException()));

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_I_Ship class
	 *
	 * @return void
	 */
	public function replaceByMockWithValidProductException()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithValidProductException()));

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_I_Ship class
	 *
	 * @return void
	 */
	public function replaceByMockWhereDeleteThrowException()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_i_ship')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWhereDeleteThrowException()));

		$this->replaceByMock('model', 'eb2cproduct/feed_i_ship', $mock);
	}
}
