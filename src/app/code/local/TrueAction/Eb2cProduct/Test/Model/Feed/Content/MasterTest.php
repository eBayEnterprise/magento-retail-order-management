<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_Feed_Content_MasterTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing loadProductBySku method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testLoadProductBySku()
	{
		$master = new TrueAction_Eb2cProduct_Model_Feed_Content_Master();
		$masterReflector = new ReflectionObject($master);

		$loadProductBySku = $masterReflector->getMethod('_loadProductBySku');
		$loadProductBySku->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Product',
			$loadProductBySku->invoke($master, '123')
		);
	}

	/**
	 * testing loadCategoryByName method - the reason for this test is because the method will be replace by a mock on all the other tests
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testloadCategoryByName()
	{
		$master = new TrueAction_Eb2cProduct_Model_Feed_Content_Master();
		$masterReflector = new ReflectionObject($master);

		$loadCategoryByName = $masterReflector->getMethod('_loadCategoryByName');
		$loadCategoryByName->setAccessible(true);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Category',
			$loadCategoryByName->invoke($master, '123')
		);
	}

	/**
	 * testing _constructor method - this test to test fs tool is set in the constructor when no paramenter pass
	 *
	 * @test
	 */
	public function testConstructor()
	{
		$feedContentMasterMock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->setMethods(array('hasFsTool'))
			->getMock();

		$feedContentMasterMock->expects($this->any())
			->method('hasFsTool')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2cproduct/feed_content_master', $feedContentMasterMock);

		$productFeedModel = Mage::getModel('eb2cproduct/feed_content_master');

		$masterReflector = new ReflectionObject($productFeedModel);
		$constructMethod = $masterReflector->getMethod('_construct');
		$constructMethod->setAccessible(true);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Content_Master',
			$constructMethod->invoke($productFeedModel)
		);
	}

	/**
	 * testing processFeeds method - with invalid feed catalog id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterWithInvalidFeedCatalogId()
	{
		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithInvalidFeedCatalogId()); // give a feed with invalid catalog id

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}

	/**
	 * testing processFeeds method - with invalid feed client id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterWithInvalidFeedClientId()
	{
		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithInvalidFeedClientId()); // give a feed with invalid catalog id

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}

	/**
	 * testing processFeeds method - product with invalid sftp settings
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidSftpSetting()
	{
		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperInvalidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}

	/**
	 * testing processFeeds method - product with valid product id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidProductId()
	{
		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductId();

		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		// to make the _clean method throw an exception we must mock it
		$mockCatalogInventoryModelStockStatus = new TrueAction_Eb2cProduct_Test_Mock_Model_CatalogInventory_Stock_Status();
		$master->setStockStatus($mockCatalogInventoryModelStockStatus->buildCatalogInventoryModelStockStatusWithException());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}

	/**
	 * testing processFeeds method - product with valid product id and valid category id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidProductIdValidCategoryId()
	{
		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductIdValidCategoryId();

		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}

	/**
	 * testing processFeeds method - product with valid product id where saving throw Exception
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithValidProductIdThrowException()
	{
		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithValidProductException();

		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}

	/**
	 * testing processFeeds method - product with invalid product id
	 *
	 * @test
	 * @large
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeedsContentMasterProductWithInvalidProductId()
	{
		$mockContentMaster = new TrueAction_Eb2cProduct_Test_Mock_Model_Feed_Content_Master();
		$mockContentMaster->replaceByMockWithInvalidProductId();

		$master = Mage::getModel('eb2cproduct/feed_content_master');

		$mockHelperObject = new TrueAction_Eb2cProduct_Test_Mock_Helper_Data();
		$mockHelperObject->replaceByMockProductHelper();
		$mockHelperObject->replaceByMockCoreHelperFeed();
		$mockHelperObject->replaceByMockCoreHelperValidSftpSettings();
		$mockHelperObject->replaceByMockFileTransferHelper();

		$mockCoreModelFeed = new TrueAction_Eb2cProduct_Test_Mock_Model_Core_Feed();
		$master->setFeedModel($mockCoreModelFeed->buildEb2cCoreModelFeedForContentMasterWithValidProduct());

		$mockEavModelConfg = new TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config();
		$master->setEavConfig($mockEavModelConfg->buildEavModelConfig());

		$master->processFeeds(); // This should not error out, at least.
		$this->markTestIncomplete('Need a real test here.');
	}
}
