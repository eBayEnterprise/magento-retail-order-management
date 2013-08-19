<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Item_MasterTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing loadProductBySku method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testLoadProductBySku()
	{
		$master = new TrueAction_Eb2cProduct_Model_Feed_Item_Master();
		$masterReflector = new ReflectionObject($master);

		$loadProductBySku = $masterReflector->getMethod('_loadProductBySku');
		$loadProductBySku->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Product',
			$loadProductBySku->invoke($master, '123')
		);
	}

	/**
	 * testing processFeeds method - invalid product id
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$master->setHelper($mockHelperObject->buildEb2cProductHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - valid product id
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithValidProductId()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$master->setHelper($mockHelperObject->buildEb2cProductHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - invalid product throw an exception
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithInvalidProductException()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithInvalidProductException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$master->setHelper($mockHelperObject->buildEb2cProductHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - valid product throwing exception
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWithValidProductException()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWithValidProductException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$master->setHelper($mockHelperObject->buildEb2cProductHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$this->assertNull($master->processFeeds());
	}

	/**
	 * testing processFeeds method - where delete a product will throw an exception
	 *
	 * @test
	 * @medium
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsWhereDeleteThrowException()
	{
		$mockItemMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Item_Master();
		$mockItemMaster->replaceByMockWhereDeleteThrowException();

		$master = Mage::getModel('eb2cproduct/feed_item_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$master->setHelper($mockHelperObject->buildEb2cProductHelper());

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeed());

		$this->assertNull($master->processFeeds());
	}
}
