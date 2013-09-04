<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_Content_Master class
	 *
	 * @return void
	 */
	public function replaceByMockWithInvalidProductId()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('_loadProductBySku', '_loadCategoryByName'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithInvalidProductId()));

		$mockCatalogModelCategory = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Category();
		$mock->expects($this->any())
			->method('_loadCategoryByName')
			->will($this->returnValue($mockCatalogModelCategory->buildCatalogModelCategoryWithInvalidCategoryId()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_Content_Master class
	 *
	 * @return void
	 */
	public function replaceByMockWithValidProductId()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('_loadProductBySku', '_loadCategoryByName'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithValidProductId()));

		$mockCatalogModelCategory = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Category();
		$mock->expects($this->any())
			->method('_loadCategoryByName')
			->will($this->returnValue($mockCatalogModelCategory->buildCatalogModelCategoryWithInvalidCategoryId()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_Content_Master class
	 *
	 * @return void
	 */
	public function replaceByMockWithValidProductIdValidCategoryId()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('_loadProductBySku', '_loadCategoryByName'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithValidProductId()));

		$mockCatalogModelCategory = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Category();
		$mock->expects($this->any())
			->method('_loadCategoryByName')
			->will($this->returnValue($mockCatalogModelCategory->buildCatalogModelCategoryWithValidCategoryId()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_Content_Master class
	 *
	 * @return void
	 */
	public function replaceByMockWithInvalidProductException()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithInvalidProductException()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_Content_Master class
	 *
	 * @return void
	 */
	public function replaceByMockWithValidProductException()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('_loadProductBySku', '_loadCategoryByName'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWithValidProductException()));

		$mockCatalogModelCategory = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Category();
		$mock->expects($this->any())
			->method('_loadCategoryByName')
			->will($this->returnValue($mockCatalogModelCategory->buildCatalogModelCategoryWithInvalidCategoryException()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $mock);
	}

	/**
	 * replacing by mock of the TrueAction_Eb2cProduct_Model_Feed_Content_Master class
	 *
	 * @return void
	 */
	public function replaceByMockWhereDeleteThrowException()
	{
		$mock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('_loadProductBySku'))
			->getMock();

		$mockCatalogModelProduct = new TrueAction_Eb2cProduct_Test_Mock_Model_Catalog_Product();
		$mock->expects($this->any())
			->method('_loadProductBySku')
			->will($this->returnValue($mockCatalogModelProduct->buildCatalogModelProductWhereDeleteThrowException()));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $mock);
	}
}
