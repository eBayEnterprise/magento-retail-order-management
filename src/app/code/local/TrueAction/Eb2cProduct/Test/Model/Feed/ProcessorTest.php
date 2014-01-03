<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_ProcessorTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test __construct method
	 * @test
	 */
	public function testConstruct()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'getDefaultLanguageCode'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'processorUpdateBatchSize' => 100,
				'processorDeleteBatchSize' => 200,
				'processorMaxTotalEntries' => 200,

			)));
		$productHelperMock->expects($this->once())
			->method('getDefaultLanguageCode')
			->will($this->returnValue('en-US'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getDefaultParentCategoryId', '_getStoreRootCategoryId', '_initLanguageCodeMap'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_getDefaultParentCategoryId')
			->will($this->returnValue(1));
		$feedProcessorModelMock->expects($this->once())
			->method('_getStoreRootCategoryId')
			->will($this->returnValue(2));
		$feedProcessorModelMock->expects($this->once())
			->method('_initLanguageCodeMap')
			->will($this->returnSelf());

		$this->_reflectMethod($feedProcessorModelMock, '__construct')->invoke($feedProcessorModelMock);

		$this->assertSame('en-us', $this->_reflectProperty($feedProcessorModelMock, '_defaultLanguageCode')->getValue($feedProcessorModelMock));
		$this->assertSame(100, $this->_reflectProperty($feedProcessorModelMock, '_updateBatchSize')->getValue($feedProcessorModelMock));
		$this->assertSame(200, $this->_reflectProperty($feedProcessorModelMock, '_deleteBatchSize')->getValue($feedProcessorModelMock));
		$this->assertSame(200, $this->_reflectProperty($feedProcessorModelMock, '_maxTotalEntries')->getValue($feedProcessorModelMock));
		$this->assertSame(1, $this->_reflectProperty($feedProcessorModelMock, '_defaultParentCategoryId')->getValue($feedProcessorModelMock));
		$this->assertSame(2, $this->_reflectProperty($feedProcessorModelMock, '_storeRootCategoryId')->getValue($feedProcessorModelMock));
	}

	/**
	 * verify true is returned when the default translation exists; false otherwise.
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testHasDefaultTranslation($code, $langCode, $expect)
	{
		$translations = array(
			'attr_code' => array(
				'en-US' => 'this is in english'
			)
		);
		$testModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->setMethods(array('none'))
			->disableOriginalConstructor()
			->getMock();
		$this->_reflectProperty($testModel, '_defaultLanguageCode')->setValue($testModel, $langCode);
		$result = $this->_reflectMethod($testModel, '_hasDefaultTranslation')->invoke($testModel, $code, $translations);
		$this->assertSame($expect, $result);
	}
	/**
	 * verify the default translation will be removed from $translations if it exists.
	 * verify the value for the default language will be set in the product data if it exists.
	 * verify $translations will be returned unaltered when the default translation is not found.
	 * verify a code with no translations remaining will be removed from $translations.
	 * @test
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testApplyDefaultTranslations($hasDefaultTranslation, $translationAmount)
	{
		$e = $this->expected("%s-%s", (int) $hasDefaultTranslation, $translationAmount);
		$translations = $e->getTranslations();
		$productData = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('setData'))
			->getMock();
		if ($hasDefaultTranslation) {
			$productData->expects($this->once())
				->method('setData')
				->with(
					$this->identicalTo('attr_code'),
					$this->identicalTo('this is english')
				)
				->will($this->returnSelf());
		} else {
			$productData->expects($this->never())
				->method('setData');
		}
		$testModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->setMethods(array('_hasDefaultTranslation'))
			->disableOriginalConstructor()
			->getMock();
		$testModel->expects($this->once())
			->method('_hasDefaultTranslation')
			->with($this->identicalTo('attr_code'), $this->identicalTo($translations))
			->will($this->returnValue($hasDefaultTranslation));
		$this->_reflectProperty($testModel, '_defaultLanguageCode')
			->setValue($testModel, 'en-US');
		$result = $this->_reflectMethod($testModel, '_applyDefaultTranslations')
			->invoke($testModel, $productData, $translations);
		$this->assertSame($e->getExpectedTranslations(), $result);
	}
	/**
	 * Test processUpdates method
	 * @test
	 */
	public function testProcessUpdates()
	{
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_transformData', '_synchProduct', '_logFeedErrorStatistics'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_transformData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(new Varien_Object()));
		$feedProcessorModelMock->expects($this->once())
			->method('_synchProduct')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(null));
		$feedProcessorModelMock->expects($this->once())
			->method('_logFeedErrorStatistics')
			->will($this->returnValue(null));
		$dataArrayObject = new ArrayObject(array(new Varien_Object()));
		$feedProcessorModelMock->processUpdates($dataArrayObject->getIterator());
	}
	/**
	 * Test _logFeedErrorStatistics method
	 * @test
	 */
	public function testLogFeedErrorStatistics()
	{
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->_reflectProperty($feedProcessorModelMock, '_customAttributeErrors')
			->setValue($feedProcessorModelMock, array(
				'invalid_language' => 5,
				'invalid_operation_type' => 3,
				'missing_operation_type' => 2,
				'missing_attribute' => 1,
			));
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_logFeedErrorStatistics')->invoke($feedProcessorModelMock)
		);
		$this->assertSame(
			array(),
			$this->_reflectProperty($feedProcessorModelMock, '_customAttributeErrors')->getValue($feedProcessorModelMock)
		);
	}
	/**
	 * Test _prepareCustomAttributes method, where custom product type of any case will be recognizeed
	 * @test
	 */
	public function testProductTypeCaseInsensitive()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultProductAttributeSetId'))
			->getMock();
		$productHelperMock->expects($this->exactly(4))
			->method('getDefaultProductAttributeSetId')
			->will($this->returnValue(72));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$outData = $this->getMock('Varien_Object', array('setData'));
		$outData->expects($this->exactly(4))
			->method('setData')
			->with($this->equalTo('product_type'), $this->equalTo('simple'))
			->will($this->returnSelf());
		$testData = array(
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'PROdUctTypE',
					'value' => 'simple'
				))
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'pRodUCTTYPE',
					'value' => 'simple'
				))
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'PRODUCTTYPE',
					'value' => 'simple'
				))
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'producttype',
					'value' => 'simple'
				))
			)
		);
		foreach ($testData as $data) {
			$this->assertInstanceOf(
				$data['expect'],
				$this->_reflectMethod($feedProcessorModelMock, '_prepareCustomAttributes')
				->invoke($feedProcessorModelMock, $data['customAttributes'], $outData)
			);
		}
	}
	const VFS_ROOT = 'var/eb2c';
	/**
	 * @loadFixture
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testTransformation($scenario)
	{
		$e = $this->expected($scenario);
		$checkData = function($dataObj) use ($e) {
			$keys = $e->getData('keys');
			$rootData = $dataObj->getData();
			foreach ($keys as $key) {
				PHPUnit_Framework_Assert::assertArrayHasKey(
					$key,
					$rootData,
					"missing [$key]"
				);
			}
			foreach (array('catalog_id', 'gsi_store_id', 'gsi_client_id') as $key) {
				PHPUnit_Framework_Assert::assertSame(
					$e->getData($key),
					$dataObj->getData($key),
					"value of [$key] is not as expected"
				);
			}
			$expData = $e->getData('item_id');
			$actData = $dataObj->getData('item_id');
			foreach (array_keys($expData) as $key) {
				PHPUnit_Framework_Assert::assertSame(
					$expData[$key],
					$actData->getData($key),
					"value of [$key] is not as expected"
				);
			}
			$expData = $e->getData('extended_attributes');
			$actData = $dataObj->getData('extended_attributes');
			foreach (array_keys($expData) as $key) {
				PHPUnit_Framework_Assert::assertSame(
					$expData[$key],
					$actData->getData($key),
					"value of [$key] is not as expected"
				);
			}
			if ($e->hasData('color_attributes')) {
				$expData = $e->getData('color_attributes');
				$actData = $dataObj->getData('extended_attributes');
				$actData = $actData['color_attributes'];
				foreach (array_keys($expData) as $key) {
					PHPUnit_Framework_Assert::assertSame(
						$expData[$key],
						$actData->getData($key),
						"value of [$key] is not as expected"
					);
				}
			}
			if ($e->hasData('configurable_attributes')) {
				$expData = $e->getData('configurable_attributes');
				$actData = $dataObj->getData('configurable_attributes');
				foreach (array_keys($expData) as $key) {
					PHPUnit_Framework_Assert::assertSame(
						$expData[$key],
						$actData[$key],
						"value of [$key] is not as expected"
					);
				}
			}
		};
		$testModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_synchProduct', '_isAtLimit'))
			->getMock();
		$testModel->expects($this->atLeastOnce())
			->method('_synchProduct')
			->will($this->returnCallback($checkData));
		$dataArrayObject = new ArrayObject(array(new Varien_Object($this->getLocalFixture($scenario))));
		$testModel->processUpdates($dataArrayObject->getIterator());
	}
	/**
	 * Testing that we throw proper exception if we can't find an attribute
	 *
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInGetAttributeOptionId()
	{
		$testModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$fn = $this->_reflectMethod($testModel, '_getAttributeOptionId');
		$fn->invoke($testModel, '', '');
	}
	/**
	 * Testing that we throw proper exception if we can't find an attribute
	 *
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInAddOptionToAttribute()
	{
		$testModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$fn = $this->_reflectMethod($testModel, '_addOptionToAttribute');
		$fn->invoke($testModel, '', '', '');
	}
	/**
	 * Data provider to the testAddStockItemData test, provides the product type,
	 * product id, feed "dataObject" and expected data to be set on the stock item
	 * @return array Arg arrays to be sent to test method
	 */
	public function providerTestAddStockItemData()
	{
		$productId = 46;
		$dataObject = new Varien_Object(array(
			'extended_attributes' => new Varien_Object(array('back_orderable' => false)),
		));
		return array(
			array(
				Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
				$productId,
				$dataObject,
				array(
					'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
					'product_id' => $productId,
					'use_config_backorders' => false,
					'backorders' => false,
				),
			),
			array(
				Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
				$productId,
				$dataObject,
				array(
					'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
					'product_id' => $productId,
					'is_in_stock' => 1,
				),
			),
		);
	}
	/**
	 * Test adding stock data to a product - should create the stock item and populate
	 * it with appropriate data based on the product type. All should get a product_id
	 * and stock_id. Non-config products should also get settings for use_config_backorders and
	 * backorders. Config products should always have is_in_stock set to true (1)
	 * @param  sting         $productType       Product type
	 * @param  int           $productId         Product id
	 * @param  Varien_Object $feedData          Data that would have been pulled from the feed files
	 * @param  array         $expectedStockData array of data that should end up getting set on the stock item
	 * @test
	 * @dataProvider providerTestAddStockItemData
	 * @mock Mage_CatalogInventory_Model_Stock_Item::loadByProduct ensure loaded with given id
	 * @mock Mage_CatalogInventory_Model_Stock_Item::addData ensure proper data set on the model
	 * @mock Mage_CatalogInventory_Model_Stock_Item::save make sure the model is saved in the end
	 * @mock Mage_Catalog_Model_Product::getTypeId return expected type id
	 * @mock Mage_Catalog_Model_Product::getId return expected product id
	 * @mock TrueAction_Eb2cProduct_Model_Feed_Processor disable constructor to prevent side-effects/unwanted coverage
	 */
	public function testAddStockItemData($productType, $productId, $feedData, $expectedStockData)
	{
		$stockItem = $this->getModelMock('cataloginventory/stock_item', array('loadByProduct', 'addData', 'save'));
		$this->replaceByMock('model', 'cataloginventory/stock_item', $stockItem);
		$product = $this->getModelMock('catalog/product', array('getTypeId', 'getId'));
		$processor = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$stockItem
			->expects($this->once())
			->method('loadByProduct')
			->with($this->identicalTo($product))
			->will($this->returnSelf());
		$stockItem
			->expects($this->once())
			->method('addData')
			->with($this->identicalTo($expectedStockData))
			->will($this->returnSelf());
		$stockItem
			->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$product
			->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue($productType));
		$product
			->expects($this->any())
			->method('getId')
			->will($this->returnValue($productId));
		$method = $this->_reflectMethod($processor, '_addStockItemDataToProduct');
		$this->assertSame($processor, $method->invoke($processor, $feedData, $product));
	}

	/**
	 * Test _preparedCategoryLinkData method
	 * @test
	 */
	public function testPreparedCategoryLinkData()
	{
		$categoryModelMock = $this->getModelMockBuilder('catalog/category')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$categoryModelMock->expects($this->at(0))
			->method('getId')
			->will($this->returnValue(15));
		$categoryModelMock->expects($this->at(1))
			->method('getId')
			->will($this->returnValue(17));
		$categoryModelMock->expects($this->at(2))
			->method('getId')
			->will($this->returnValue(21));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_loadCategoryByName', '_deleteCategories'))
			->getMock();
		$feedProcessorModelMock->expects($this->at(0))
			->method('_loadCategoryByName')
			->with($this->equalTo('Kids'))
			->will($this->returnValue($categoryModelMock));
		$feedProcessorModelMock->expects($this->at(1))
			->method('_loadCategoryByName')
			->with($this->equalTo('Toys'))
			->will($this->returnValue($categoryModelMock));
		$feedProcessorModelMock->expects($this->at(2))
			->method('_loadCategoryByName')
			->with($this->equalTo('Teddy Bears'))
			->will($this->returnValue($categoryModelMock));
		$feedProcessorModelMock->expects($this->once())
			->method('_deleteCategories')
			->with($this->isType('array'))
			->will($this->returnSelf());

		$this->_reflectProperty($feedProcessorModelMock, '_defaultParentCategoryId')->setValue($feedProcessorModelMock, 1);
		$this->_reflectProperty($feedProcessorModelMock, '_storeRootCategoryId')->setValue($feedProcessorModelMock, 2);
		$this->assertSame(
			array('0', '1', '2', '15', '17', '21'),
			$this->_reflectMethod($feedProcessorModelMock, '_preparedCategoryLinkData')->invoke($feedProcessorModelMock, new Varien_Object(array(
				'category_links' => array(
					array('name' => 'Kids-Toys-Teddy Bears', 'import_mode' => 'add'),
					array('name' => 'Animals-Giraffe', 'import_mode' => 'delete')
				)
			)))
		);
	}

	/**
	 * Test _deleteCategories method
	 * @test
	 */
	public function testDeleteCategories()
	{
		$catalogResourceModelCategoryMock = $this->getResourceModelMockBuilder('catalog/category_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'addAttributeToSelect', 'load', 'delete'))
			->getMock();
		$catalogResourceModelCategoryMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('name'), $this->isType('array'))
			->will($this->returnSelf());
		$catalogResourceModelCategoryMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->isType('array'))
			->will($this->returnSelf());
		$catalogResourceModelCategoryMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$catalogResourceModelCategoryMock->expects($this->once())
			->method('delete')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/category_collection', $catalogResourceModelCategoryMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($processorModelMock, '_deleteCategories')->invoke($processorModelMock, array('Animals', 'Giraffe'))
		);
	}
}
