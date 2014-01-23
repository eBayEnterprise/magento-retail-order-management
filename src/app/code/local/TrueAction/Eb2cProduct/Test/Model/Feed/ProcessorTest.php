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
				'attributesCodeList' => 'testAttr1,testAttr2',
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
			->method('_initLanguageCodeMap')
			->will($this->returnSelf());
		$this->_reflectMethod($feedProcessorModelMock, '__construct')->invoke($feedProcessorModelMock);
		$this->assertSame('en-us', $this->_reflectProperty($feedProcessorModelMock, '_defaultLanguageCode')->getValue($feedProcessorModelMock));
		$this->assertSame(100, $this->_reflectProperty($feedProcessorModelMock, '_updateBatchSize')->getValue($feedProcessorModelMock));
		$this->assertSame(200, $this->_reflectProperty($feedProcessorModelMock, '_deleteBatchSize')->getValue($feedProcessorModelMock));
		$this->assertSame(200, $this->_reflectProperty($feedProcessorModelMock, '_maxTotalEntries')->getValue($feedProcessorModelMock));
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
		$e = $this->expected('%s-%s', (int) $hasDefaultTranslation, $translationAmount);
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
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInGetAttributeOptionId()
	{
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$fn = $this->_reflectMethod($testModel, '_getAttributeOptionId');
		$fn->invoke($testModel, '', '');
	}
	/**
	 * Testing that we throw proper exception if we can't find an attribute
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInAddOptionToAttribute()
	{
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$fn = $this->_reflectMethod($testModel, '_addOptionToAttribute');
		$fn->invoke($testModel, '', '', '');
	}

	/**
	 * Test _addOptionToAttribute method
	 * @test
	 */
	public function testAddOptionToAttribute()
	{
		$entitySetupMock = $this->getMockBuilder('Mage_Eav_Model_Entity_Setup')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeOption'))
			->getMock();
		$entitySetupMock->expects($this->once())
			->method('addAttributeOption')
			->with($this->isType('array'))
			->will($this->returnSelf());

		$entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$entityAttributeModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(92));

		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo('color'))
			->will($this->returnValue($entityAttributeModelMock));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getEntitySetup', '_getAttributeOptionId'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getEntitySetup')
			->will($this->returnValue($entitySetupMock));
		$processorModelMock->expects($this->once())
			->method('_getAttributeOptionId')
			->with($this->equalTo('color'), $this->equalTo(700))
			->will($this->returnValue(12));

		$this->_reflectProperty($processorModelMock, '_attributes')->setValue($processorModelMock, $entityAttributeCollectionModelMock);
		$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->setValue($processorModelMock, array('en-US' => 1));

		$this->assertSame(12, $this->_reflectMethod($processorModelMock, '_addOptionToAttribute')->invoke($processorModelMock, 'color', array(
			'code' => 700,
			'localization' => array('en-US' => 'English')
		)));
	}

	/**
	 * Test _addOptionToAttribute method, when adding new option throw exception
	 * @test
	 */
	public function testAddOptionToAttributeThrowException()
	{
		$entitySetupMock = $this->getMockBuilder('Mage_Eav_Model_Entity_Setup')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeOption'))
			->getMock();
		$entitySetupMock->expects($this->once())
			->method('addAttributeOption')
			->with($this->isType('array'))
			->will($this->throwException(
				new Mage_Core_Exception('UnitTest Simulate Throw Exception when adding new options to an attribute')
			));

		$entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$entityAttributeModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(92));

		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo('color'))
			->will($this->returnValue($entityAttributeModelMock));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getEntitySetup', '_getAttributeOptionId'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getEntitySetup')
			->will($this->returnValue($entitySetupMock));
		$processorModelMock->expects($this->once())
			->method('_getAttributeOptionId')
			->with($this->equalTo('color'), $this->equalTo(700))
			->will($this->returnValue(12));

		$this->_reflectProperty($processorModelMock, '_attributes')->setValue($processorModelMock, $entityAttributeCollectionModelMock);
		$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->setValue($processorModelMock, array('en-US' => 1));

		$this->assertSame(12, $this->_reflectMethod($processorModelMock, '_addOptionToAttribute')->invoke($processorModelMock, 'color', array(
			'code' => 700,
			'localization' => array('en-US' => 'English')
		)));
	}

	/**
	 * Data provider to the testAddStockItemData test, provides the product type,
	 * product id, feed "dataObject" and expected data to be set on the stock itemprocessDeletions
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
	 * Test _getAttributeOptionId method
	 * @test
	 */
	public function testGetAttributeOptionId()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($processorModelMock, '_attributeOptions')->setValue($processorModelMock, array(
			'color' => array(
				array(
					'value' => '12',
					'label' => '700',
				),
				array(
					'value' => '13',
					'label' => '800',
				)
			),
		));

		$this->assertSame(12, $this->_reflectMethod($processorModelMock, '_getAttributeOptionId')->invoke($processorModelMock, 'color', '700'));
	}
	/**
	 * Test _extractDeletedItemSkus method
	 * @test
	 */
	public function testExtractDeletedItemSkus()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$list = new ArrayObject(array(
			new Varien_Object(array('client_item_id' => 'SKU-1234')),
			new Varien_Object(array('client_item_id' => 'SKU-4321')),
			new Varien_Object(array('client_item_id' => 'SKU-3412')),
			new Varien_Object(array('client_item_id' => 'SKU-2143'))
		));
		$this->assertSame(
			array('SKU-1234', 'SKU-4321', 'SKU-3412', 'SKU-2143'),
			$this->_reflectMethod($processorModelMock, '_extractDeletedItemSkus')->invoke(
				$processorModelMock,
				$list->getIterator()
			)
		);
	}

	/**
	 * Test _getAttributeOptionId method, throw execption when invalid attribute code is passed
	 * @test
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testGetAttributeOptionIdThrowException()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->_reflectProperty($processorModelMock, '_attributeOptions')->setValue($processorModelMock, array());
		$this->_reflectMethod($processorModelMock, '_getAttributeOptionId')->invoke($processorModelMock, 'wrong', 'fake');
	}

	/**
	 * Test _getAttributeOptionId method, there's no attribute option found
	 * @test
	 */
	public function testGetAttributeOptionIdNoOptionFound()
	{
		$newCollection = new Varien_Data_Collection();
		$newCollection->addItem(Mage::getModel('eav/entity_attribute_option')->addData(array(
			'option_id' => '12',
			'value' => '700',
		)));
		$newCollection->addItem(Mage::getModel('eav/entity_attribute_option')->addData(array(
			'option_id' => '13',
			'value' => '800',
		)));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->_reflectProperty($processorModelMock, '_attributeOptions')->setValue($processorModelMock, array(
			'color' => $newCollection,
		));

		$this->assertSame(0, $this->_reflectMethod($processorModelMock, '_getAttributeOptionId')->invoke($processorModelMock, 'color', '900'));
	}

	/**
	 * Test _getAttributeOptionCollection method
	 * @test
	 */
	public function testGetAttributeOptionCollection()
	{
		$entityAttributeOptionCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_option_collection')
			->disableOriginalConstructor()
			->setMethods(array('join', 'setStoreFilter', 'addFieldToFilter', 'addExpressionFieldToSelect'))
			->getMock();

		$entityAttributeOptionCollectionModelMock->expects($this->any())
			->method('join')
			->with($this->isType('array'), $this->equalTo('main_table.attribute_id = attributes.attribute_id'), $this->isType('array'))
			->will($this->returnSelf());
		$entityAttributeOptionCollectionModelMock->expects($this->any())
			->method('setStoreFilter')
			->with($this->equalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID), $this->equalTo(false))
			->will($this->returnSelf());
		$entityAttributeOptionCollectionModelMock->expects($this->at(2))
			->method('addFieldToFilter')
			->with($this->equalTo('attributes.attribute_code'), $this->equalTo('color'))
			->will($this->returnSelf());
		$entityAttributeOptionCollectionModelMock->expects($this->at(3))
			->method('addFieldToFilter')
			->with($this->equalTo('attributes.entity_type_id'), $this->equalTo(4))
			->will($this->returnSelf());
		$entityAttributeOptionCollectionModelMock->expects($this->once())
			->method('addExpressionFieldToSelect')
			->with($this->equalTo('lcase_value'), $this->equalTo('LCASE({{value}})'), $this->equalTo('value'))
			->will($this->returnSelf());

		$this->replaceByMock('resource_model', 'eav/entity_attribute_option_collection', $entityAttributeOptionCollectionModelMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection',
			$this->_reflectMethod($processorModelMock, '_getAttributeOptionCollection')->invoke($processorModelMock, 'color', 4)
		);
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
	 * Test _deleteItems method
	 * @test
	 */
	public function testDeleteItems()
	{
		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'addAttributeToSelect', 'load', 'delete'))
			->getMock();
		$catalogResourceModelProductMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('sku'), $this->isType('array'))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->isType('array'))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('delete')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $catalogResourceModelProductMock);
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($processorModelMock, '_deleteItems')->invoke($processorModelMock, array('SKU-1234', 'SKU-4321', 'SKU-3412', 'SKU-2143'))
		);
	}
	/**
	 * Test processDeletions method
	 * @test
	 */
	public function testProcessDeletions()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_extractDeletedItemSkus', '_deleteItems'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_extractDeletedItemSkus')
			->with($this->isInstanceOf('ArrayIterator'))
			->will($this->returnValue(array('SKU-1234', 'SKU-4321', 'SKU-3412', 'SKU-2143')));
		$processorModelMock->expects($this->once())
			->method('_deleteItems')
			->with($this->isType('array'))
			->will($this->returnSelf());
		$list = new ArrayObject(array(
			new Varien_Object(array('client_item_id' => 'SKU-1234')),
			new Varien_Object(array('client_item_id' => 'SKU-4321')),
			new Varien_Object(array('client_item_id' => 'SKU-3412')),
			new Varien_Object(array('client_item_id' => 'SKU-2143'))
		));
		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$processorModelMock->processDeletions($list->getIterator())
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

	/**
	 * Test _getAttributeCollection method
	 * @test
	 */
	public function testGetAttributeCollection()
	{
		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('entity_type_id'), $this->equalTo(4))
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'eav/entity_attribute_collection', $entityAttributeCollectionModelMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'Mage_Eav_Model_Resource_Entity_Attribute_Collection',
			$this->_reflectMethod($processorModelMock, '_getAttributeCollection')->invoke($processorModelMock, 4)
		);
	}

	/**
	 * Test _processConfigurableAttributes method
	 * @test
	 */
	public function testGrocessConfigurableAttributes()
	{
		$entityAttributeBackendDefault = $this->getModelMockBuilder('eav/entity_attribute_backend_default')
			->disableOriginalConstructor()
			->setMethods(array('getLabel'))
			->getMock();
		$entityAttributeBackendDefault->expects($this->at(0))
			->method('getLabel')
			->will($this->returnValue('color'));
		$entityAttributeBackendDefault->expects($this->at(1))
			->method('getLabel')
			->will($this->returnValue('size'));

		$productTypeConfigurableAttributeModelMock = $this->getModelMockBuilder('catalog/product_type_configurable_attribute')
			->disableOriginalConstructor()
			->setMethods(array('setProductAttribute', 'getId', 'getLabel'))
			->getMock();
		$productTypeConfigurableAttributeModelMock->expects($this->exactly(2))
			->method('setProductAttribute')
			->with($this->isInstanceOf('Mage_Eav_Model_Entity_Attribute'))
			->will($this->returnSelf());
		$productTypeConfigurableAttributeModelMock->expects($this->at(1))
			->method('getId')
			->will($this->returnValue(12));
		$productTypeConfigurableAttributeModelMock->expects($this->at(4))
			->method('getId')
			->will($this->returnValue(34));
		$productTypeConfigurableAttributeModelMock->expects($this->at(2))
			->method('getLabel')
			->will($this->returnValue('Red'));
		$productTypeConfigurableAttributeModelMock->expects($this->at(5))
			->method('getLabel')
			->will($this->returnValue('1 1/2'));
		$this->replaceByMock('model', 'catalog/product_type_configurable_attribute', $productTypeConfigurableAttributeModelMock);

		$outData = $this->getMock('Varien_Object', array('setData'));
		$outData->expects($this->once())
			->method('setData')
			->with($this->equalTo('configurable_attributes_data'), $this->isType('array'))
			->will($this->returnSelf());

		$entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getPosition', 'getId', 'getAttributeCode', 'getFrontend'))
			->getMock();
		$entityAttributeModelMock->expects($this->at(0))
			->method('getPosition')
			->will($this->returnValue(1));
		$entityAttributeModelMock->expects($this->at(4))
			->method('getPosition')
			->will($this->returnValue(2));
		$entityAttributeModelMock->expects($this->at(1))
			->method('getId')
			->will($this->returnValue(92));
		$entityAttributeModelMock->expects($this->at(5))
			->method('getId')
			->will($this->returnValue(93));
		$entityAttributeModelMock->expects($this->at(2))
			->method('getAttributeCode')
			->will($this->returnValue('color'));
		$entityAttributeModelMock->expects($this->at(6))
			->method('getAttributeCode')
			->will($this->returnValue('size'));
		$entityAttributeModelMock->expects($this->exactly(2))
			->method('getFrontend')
			->will($this->returnValue($entityAttributeBackendDefault));

		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->at(0))
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo('color'))
			->will($this->returnValue($entityAttributeModelMock));
		$entityAttributeCollectionModelMock->expects($this->at(1))
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo('size'))
			->will($this->returnValue($entityAttributeModelMock));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($processorModelMock, '_attributes')->setValue($processorModelMock, $entityAttributeCollectionModelMock);

		$this->_reflectMethod($processorModelMock, '_processConfigurableAttributes')->invoke($processorModelMock, array('value' => 'color,size'), $outData);
	}
}
