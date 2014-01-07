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
			->setMethods(array('getConfigModel', 'getDefaultLanguageCode', 'getStoreRootCategoryId', 'getDefaultParentCategoryId'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'processorUpdateBatchSize' => 100,
				'processorDeleteBatchSize' => 200,
				'processorMaxTotalEntries' => 200,
				'attributesCodeList' => 'testAttr1,testAttr2',
				'extKeys' => 'brand_name,buyer_id,color,companion_flag,country_of_origin,gift_card_tender_code,hazardous_material_code,long_description,
				lot_tracking_indicator,ltl_freight_cost,may_ship_expedite,may_ship_international,may_ship_usps,msrp,price,safety_stock,sales_class,
				serial_number_type,ship_group,ship_window_max_hour,ship_window_min_hour,short_description,street_date,style_description,style_id,
				supplier_name,supplier_supplier_id',
				'extKeysBool' => 'allow_gift_message,back_orderable,gift_wrap,gift_wrapping_available,is_hidden_product,service_indicator'
			)));
		$productHelperMock->expects($this->once())
			->method('getDefaultLanguageCode')
			->will($this->returnValue('en-US'));
		$productHelperMock->expects($this->once())
			->method('getStoreRootCategoryId')
			->will($this->returnValue(1));
		$productHelperMock->expects($this->once())
			->method('getDefaultParentCategoryId')
			->will($this->returnValue(2));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_initLanguageCodeMap'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_initLanguageCodeMap')
			->will($this->returnSelf());
		$this->_reflectMethod($feedProcessorModelMock, '__construct')->invoke($feedProcessorModelMock);
		$this->assertSame('en-us', $this->_reflectProperty($feedProcessorModelMock, '_defaultLanguageCode')->getValue($feedProcessorModelMock));
		$this->assertSame(100, $this->_reflectProperty($feedProcessorModelMock, '_updateBatchSize')->getValue($feedProcessorModelMock));
		$this->assertSame(200, $this->_reflectProperty($feedProcessorModelMock, '_deleteBatchSize')->getValue($feedProcessorModelMock));
		$this->assertSame(200, $this->_reflectProperty($feedProcessorModelMock, '_maxTotalEntries')->getValue($feedProcessorModelMock));
		$this->assertSame(1, $this->_reflectProperty($feedProcessorModelMock, '_storeRootCategoryId')->getValue($feedProcessorModelMock));
		$this->assertSame(2, $this->_reflectProperty($feedProcessorModelMock, '_defaultParentCategoryId')->getValue($feedProcessorModelMock));
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
		$data = new Varien_Object(array(
			'file_detail' => array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			),
			'item_id' => new Varien_Object(array('client_item_id' => '1234'))
		));

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', 'addErrorConfirmation', 'flush', 'hasError'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('loadFile')
			->with($this->equalTo('/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('addErrorConfirmation')
			->with($this->equalTo('1234'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('flush')
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('hasError')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_transformData', '_synchProduct', '_logFeedErrorStatistics'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_transformData')
			->with(
				$this->equalTo($data),
				$this->equalTo($confirmationsModelMock),
				$this->isType('array')
			)
			->will($this->returnValue($data));
		$feedProcessorModelMock->expects($this->once())
			->method('_synchProduct')
			->with(
				$this->equalTo($data),
				$this->equalTo($confirmationsModelMock),
				$this->isType('array')
			)
			->will($this->returnSelf());
		$feedProcessorModelMock->expects($this->once())
			->method('_logFeedErrorStatistics')
			->will($this->returnValue(null));
		$dataArrayObject = new ArrayObject(array($data));
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
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

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
				)),
				'errorConfirmations' => $confirmationsModelMock,
				'fileDetail' => array(
					'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
					'remote' => '/Inbox/Product',
					'timestamp' => '1364823587',
					'type' => 'ItemMaster',
					'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
				)
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'pRodUCTTYPE',
					'value' => 'simple'
				)),
				'errorConfirmations' => $confirmationsModelMock,
				'fileDetail' => array(
					'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
					'remote' => '/Inbox/Product',
					'timestamp' => '1364823587',
					'type' => 'ItemMaster',
					'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
				)
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'PRODUCTTYPE',
					'value' => 'simple'
				)),
				'errorConfirmations' => $confirmationsModelMock,
				'fileDetail' => array(
					'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
					'remote' => '/Inbox/Product',
					'timestamp' => '1364823587',
					'type' => 'ItemMaster',
					'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
				)
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(array(
					'name' => 'producttype',
					'value' => 'simple'
				)),
				'errorConfirmations' => $confirmationsModelMock,
				'fileDetail' => array(
					'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
					'remote' => '/Inbox/Product',
					'timestamp' => '1364823587',
					'type' => 'ItemMaster',
					'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
				)
			)
		);
		foreach ($testData as $data) {
			$this->assertInstanceOf(
				$data['expect'],
				$this->_reflectMethod($feedProcessorModelMock, '_prepareCustomAttributes')
				->invoke($feedProcessorModelMock, $data['customAttributes'], $outData, $data['errorConfirmations'], $data['fileDetail'])
			);
		}
	}

	/**
	 * Testing that we throw proper exception if we can't find an attribute
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
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testExceptionInAddOptionToAttribute()
	{
		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo(''))
			->will($this->returnValue(null));

		$testModel = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->_reflectProperty($testModel, '_attributes')->setValue($testModel, $entityAttributeCollectionModelMock);
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
		$options = array(
			array(
				'value' => '12',
				'label' => '700',
			),
			array(
				'value' => '13',
				'label' => '800',
			)
		);

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

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('loadCategoryByName'))
			->getMock();
		$productHelperMock->expects($this->at(0))
			->method('loadCategoryByName')
			->with($this->equalTo('Kids'))
			->will($this->returnValue($categoryModelMock));
		$productHelperMock->expects($this->at(1))
			->method('loadCategoryByName')
			->with($this->equalTo('Toys'))
			->will($this->returnValue($categoryModelMock));
		$productHelperMock->expects($this->at(2))
			->method('loadCategoryByName')
			->with($this->equalTo('Teddy Bears'))
			->will($this->returnValue($categoryModelMock));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_deleteCategories'))
			->getMock();
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
			->setMethods(array('addFieldToFilter', 'addExpressionFieldToSelect'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('entity_type_id'), $this->equalTo(4))
			->will($this->returnSelf());
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('addExpressionFieldToSelect')
			->with(
				$this->identicalTo('lcase_attr_code'),
				$this->identicalTo('LCASE({{attrcode}})'),
				$this->identicalTo(array('attrcode' => 'attribute_code'))
			)
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'eav/entity_attribute_collection', $entityAttributeCollectionModelMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'Mage_Eav_Model_Resource_Entity_Attribute_Collection',
			$this->invokeRestrictedMethod($processorModelMock, '_getAttributeCollection', array(4))
		);
	}

	/**
	 * Test _processConfigurableAttributes method
	 * @test
	 */
	public function testProcessConfigurableAttributes()
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

	/**
	 * Test _processConfigurableAttributes method, when getting super attribute object return null and throw exception
	 * @test
	 * @expectedException TrueAction_Eb2cProduct_Model_Feed_Exception
	 */
	public function testProcessConfigurableAttributesWithExceptionThrown()
	{
		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo('color'))
			->will($this->returnValue(null));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($processorModelMock, '_attributes')->setValue($processorModelMock, $entityAttributeCollectionModelMock);

		$this->_reflectMethod($processorModelMock, '_processConfigurableAttributes')->invoke($processorModelMock, array('value' => 'color'), new Varien_Object());
	}

	/**
	 * Test _getCategoryAttributeSetId method
	 * @test
	 */
	public function testGetCategoryAttributeSetId()
	{
		$eavEntityTypeModelMock = $this->getModelMockBuilder('eav/entity_type')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultAttributeSetId'))
			->getMock();
		$eavEntityTypeModelMock->expects($this->once())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));

		$resourceEavAttributeModelMock = $this->getResourceModelMockBuilder('catalog/eav_attribute')
			->disableOriginalConstructor()
			->setMethods(array('getEntityType'))
			->getMock();
		$resourceEavAttributeModelMock->expects($this->once())
			->method('getEntityType')
			->will($this->returnValue($eavEntityTypeModelMock));

		$configModelMock = $this->getResourceModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute'))
			->getMock();
		$configModelMock->expects($this->once())
			->method('getAttribute')
			->with($this->equalTo(Mage_Catalog_Model_Category::ENTITY), $this->equalTo('attribute_set_id'))
			->will($this->returnValue($resourceEavAttributeModelMock));
		$this->replaceByMock('singleton', 'eav/config', $configModelMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(4, $this->_reflectMethod($processorModelMock, '_getCategoryAttributeSetId')->invoke($processorModelMock));
	}

	/**
	 * Test _synchProduct method
	 * @test
	 */
	public function testSynchProduct()
	{
		$item = new Varien_Object(array(
			'item_id' => new Varien_Object(array(
				'client_item_id' => '45-HTC2838'
			)),
			'base_attributes' => new Varien_Object(array(
				'item_description' => 'Fake HTC Product Description',
				'catalog_class' => 'regular',
				'item_status' => 'active'
			)),
			'product_type' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'extended_attributes' => new Varien_Object(array(
				'item_dimension_shipping' => new Varien_Object(array(
					'weight' => 4,
					'mass_unit_of_measure' => 'lbs'
				)),
				'msrp' => 85.99,
				'price' => 99.95,
				'color' => array()
			)),
			'product_links' => array(
				'link_to_unique_id' => '45-BLABLAH'
			),
			'category_links' => array(
				array('name' => 'Kids-Toys-Teddy Bears', 'import_mode' => 'add'),
				array('name' => 'Animals-Giraffe', 'import_mode' => 'delete')
			)
		));

		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'getId'))
			->getMock();
		$productModelMock->expects($this->exactly(2))
			->method('addData')
			->with($this->isType('array'))
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(5));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('prepareProductModel', 'getConfigurableAttributesData'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('prepareProductModel')
			->with($this->equalTo('45-HTC2838'), $this->equalTo('Fake HTC Product Description'))
			->will($this->returnValue($productModelMock));
		$productHelperMock->expects($this->once())
			->method('getConfigurableAttributesData')
			->with(
				$this->equalTo(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
				$this->equalTo($item),
				$this->equalTo($productModelMock)
			)
			->will($this->returnValue(array(
				array(
					'id' => 1,
					'label' => 'Red',
					'position' => 0,
					'values' => array(),
					'attribute_id' => 72,
					'attribute_code' => 'Color',
					'frontend_label' => 'Color',
				)
			)));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array(
				'_getVisibilityData',
				'_getItemStatusData',
				'_preparedCategoryLinkData',
				'_getProductColorOptionId',
				'_applyDefaultTranslations',
				'_mergeTranslations',
				'_getEb2cSpecificAttributeData',
				'_addStockItemDataToProduct',
				'_applyAlternateTranslations'
			))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getVisibilityData')
			->with($this->equalTo($item))
			->will($this->returnValue(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH));
		$processorModelMock->expects($this->once())
			->method('_getItemStatusData')
			->with($this->equalTo('active'))
			->will($this->returnValue(Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
		$processorModelMock->expects($this->once())
			->method('_preparedCategoryLinkData')
			->with($this->equalTo($item))
			->will($this->returnValue(array(1,2,3,4)));
		$processorModelMock->expects($this->once())
			->method('_getProductColorOptionId')
			->with(
				$this->isType('array'),
				$this->equalTo($confirmationsModelMock),
				$this->isType('array')
			)
			->will($this->returnValue(76));
		$processorModelMock->expects($this->once())
			->method('_applyDefaultTranslations')
			->with($this->isInstanceOf('Varien_Object'), $this->isType('array'))
			->will($this->returnValue(array('color' => array('en-us' => 'Red'))));
		$processorModelMock->expects($this->once())
			->method('_mergeTranslations')
			->with($this->equalTo($item))
			->will($this->returnValue(array()));
		$processorModelMock->expects($this->once())
			->method('_getEb2cSpecificAttributeData')
			->with($this->equalTo($item))
			->will($this->returnValue(array()));
		$processorModelMock->expects($this->once())
			->method('_addStockItemDataToProduct')
			->with($this->equalTo($item), $this->equalTo($productModelMock))
			->will($this->returnSelf());
		$processorModelMock->expects($this->once())
			->method('_applyAlternateTranslations')
			->with($this->equalTo(5), $this->isType('array'))
			->will($this->returnSelf());

		$this->assertSame($processorModelMock, $this->_reflectMethod($processorModelMock, '_synchProduct')
			->invoke($processorModelMock, $item, $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _synchProduct method, when the sku in the feed is null
	 * @test
	 */
	public function testSynchProductInvalidFeedSku()
	{
		$item = new Varien_Object(array(
			'item_id' => new Varien_Object(array(
				'client_item_id' => null
			)),
		));

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::INVALID_SKU_ERR),
				$this->equalTo('ItemMaster_TestSubset.xml')
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('addError')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnSelf());

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertNull($this->_reflectMethod($processorModelMock, '_synchProduct')
			->invoke($processorModelMock, $item, $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _synchProduct method, when save the product throw PDOException
	 * @test
	 */
	public function testSynchProductThrowException()
	{
		$item = new Varien_Object(array(
			'item_id' => new Varien_Object(array(
				'client_item_id' => '45-HTC2838'
			)),
			'base_attributes' => new Varien_Object(array(
				'item_description' => 'Fake HTC Product Description',
				'catalog_class' => 'regular',
				'item_status' => 'active'
			)),
			'product_type' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'extended_attributes' => new Varien_Object(array(
				'item_dimension_shipping' => new Varien_Object(array(
					'weight' => 4,
					'mass_unit_of_measure' => 'lbs'
				)),
				'msrp' => 85.99,
				'price' => 99.95,
				'color' => array()
			)),
			'product_links' => array(
				'link_to_unique_id' => '45-BLABLAH'
			),
			'category_links' => array(
				array('name' => 'Kids-Toys-Teddy Bears', 'import_mode' => 'add'),
				array('name' => 'Animals-Giraffe', 'import_mode' => 'delete')
			)
		));

		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'getId'))
			->getMock();
		$productModelMock->expects($this->exactly(2))
			->method('addData')
			->with($this->isType('array'))
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('save')
			->will($this->throwException(
				new PDOException('UnitTest Simulate Throw Exception on save product method')
			));
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(5));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('prepareProductModel', 'getConfigurableAttributesData'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('prepareProductModel')
			->with($this->equalTo('45-HTC2838'), $this->equalTo('Fake HTC Product Description'))
			->will($this->returnValue($productModelMock));
		$productHelperMock->expects($this->once())
			->method('getConfigurableAttributesData')
			->with(
				$this->equalTo(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
				$this->equalTo($item),
				$this->equalTo($productModelMock)
			)
			->will($this->returnValue(array(
				array(
					'id' => 1,
					'label' => 'Red',
					'position' => 0,
					'values' => array(),
					'attribute_id' => 72,
					'attribute_code' => 'Color',
					'frontend_label' => 'Color',
				)
			)));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::SAVE_PRODUCT_EXCEPTION_ERR),
				$this->equalTo('UnitTest Simulate Throw Exception on save product method')
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('addError')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnSelf());

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array(
				'_getVisibilityData',
				'_getItemStatusData',
				'_preparedCategoryLinkData',
				'_getProductColorOptionId',
				'_applyDefaultTranslations',
				'_mergeTranslations',
				'_getEb2cSpecificAttributeData',
				'_addStockItemDataToProduct',
				'_applyAlternateTranslations'
			))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getVisibilityData')
			->with($this->equalTo($item))
			->will($this->returnValue(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH));
		$processorModelMock->expects($this->once())
			->method('_getItemStatusData')
			->with($this->equalTo('active'))
			->will($this->returnValue(Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
		$processorModelMock->expects($this->once())
			->method('_preparedCategoryLinkData')
			->with($this->equalTo($item))
			->will($this->returnValue(array(1,2,3,4)));
		$processorModelMock->expects($this->once())
			->method('_getProductColorOptionId')
			->with(
				$this->isType('array'),
				$this->equalTo($confirmationsModelMock),
				$this->isType('array')
			)
			->will($this->returnValue(76));
		$processorModelMock->expects($this->once())
			->method('_applyDefaultTranslations')
			->with($this->isInstanceOf('Varien_Object'), $this->isType('array'))
			->will($this->returnValue(array('color' => array('en-us' => 'Red'))));
		$processorModelMock->expects($this->once())
			->method('_mergeTranslations')
			->with($this->equalTo($item))
			->will($this->returnValue(array()));
		$processorModelMock->expects($this->once())
			->method('_getEb2cSpecificAttributeData')
			->with($this->equalTo($item))
			->will($this->returnValue(array()));
		$processorModelMock->expects($this->once())
			->method('_addStockItemDataToProduct')
			->with($this->equalTo($item), $this->equalTo($productModelMock))
			->will($this->returnSelf());
		$processorModelMock->expects($this->once())
			->method('_applyAlternateTranslations')
			->with($this->equalTo(5), $this->isType('array'))
			->will($this->returnSelf());

		$this->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_Processor', $this->_reflectMethod($processorModelMock, '_synchProduct')
			->invoke($processorModelMock, $item, $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _mergeTranslations method
	 * @test
	 */
	public function testMergeTranslations()
	{
		$item = new Varien_Object(array(
			'short_description' => 'Short description test',
			'brand_description' => 'Brand description test',
			'long_description' => 'Long description test',
			'custom_attributes' => array('name' => 'blurb', 'value' => 'Custom description test')
		));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getProductTitleSet'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getProductTitleSet')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(array('en-us' => array('title' => 'Example product title name'))));

		$this->assertSame(
			array(
				'short_description' => 'Short description test',
				'brand_description' => 'Brand description test',
				'description' => 'Long description test',
				'name' => array(
					'en-us' => array(
						'title' => 'Example product title name'
					)
				),
				'value' => 'Custom description test'
			),
			$this->_reflectMethod($processorModelMock, '_mergeTranslations')->invoke($processorModelMock, $item)
		);
	}

	/**
	 * Test _initLanguageCodeMap method
	 * @test
	 */
	public function testInitLanguageCodeMap()
	{
		$storeModelMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getName'))
			->getMock();
		$storeModelMock->expects($this->once())
			->method('getName')
			->will($this->returnValue('magtna_magt1'));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getStores', 'getStoreViewLanguage'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getStores')
			->will($this->returnValue(array(
				'1' => $storeModelMock,
				'2' => $storeModelMock
			)));
		$productHelperMock->expects($this->at(1))
			->method('getStoreViewLanguage')
			->with($this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue('en-us'));
		$productHelperMock->expects($this->at(2))
			->method('getStoreViewLanguage')
			->with($this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue(null));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		// set the class property '_storeLanguageCodeMap' to a known state
		$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->setValue($processorModelMock, array());

		$this->assertSame($processorModelMock, $this->_reflectMethod($processorModelMock, '_initLanguageCodeMap')->invoke($processorModelMock));

		$this->assertSame(
			array('en-us' => 1),
			$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->getValue($processorModelMock)
		);
	}

	/**
	 * Test _transformData method
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testTransformData($data)
	{
		// provider has data needed to transform the data
		$dataObject = new Varien_Object($data);

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelperMock->expects($this->exactly(4))
			->method('parseBool')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_preparePricingEventData', '_prepareCustomAttributes', '_getLocalizations'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_preparePricingEventData')
			->with(
				$this->equalTo($dataObject),
				$this->isInstanceOf('Varien_Object')
			)
			->will($this->returnSelf());
		$processorModelMock->expects($this->at(1))
			->method('_getLocalizations')
			->with($this->isInstanceOf('Varien_Object'), $this->equalTo('long_description'))
			->will($this->returnValue(array('en-us' => 'long description example')));
		$processorModelMock->expects($this->at(2))
			->method('_getLocalizations')
			->with($this->isInstanceOf('Varien_Object'), $this->equalTo('short_description'))
			->will($this->returnValue(array('en-us' => 'short description example')));
		$processorModelMock->expects($this->once())
			->method('_prepareCustomAttributes')
			->with(
				$this->isType('array'),
				$this->isInstanceOf('Varien_Object'),
				$this->equalTo($confirmationsModelMock),
				$this->isType('array')
			)
			->will($this->returnSelf());

		$this->_reflectProperty($processorModelMock, '_extKeys')->setValue($processorModelMock, array('brand_name', 'buyer_id', 'color', 'companion_flag'));
		$this->_reflectProperty($processorModelMock, '_extKeysBool')->setValue($processorModelMock, array('allow_gift_message', 'back_orderable', 'gift_wrap'));

		$this->assertInstanceOf('Varien_Object', $this->_reflectMethod($processorModelMock, '_transformData')
			->invoke($processorModelMock, $dataObject, $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _preparePricingEventData method
	 * @test
	 */
	public function testPreparePricingEventData()
	{
		$outData = $this->getMockBuilder('Varien_Object')
			->disableOriginalConstructor()
			->setMethods(array('addData'))
			->getMock();
		$outData->expects($this->once())
			->method('addData')
			->with($this->isType('array'))
			->will($this->returnValue(true));

		$dataObject = new Varien_Object(array(
			'ebc_pricing_event_number' => '00001',
			'price_vat_inclusive' => 'true',
			'price' => 95.99,
			'msrp' => 90.99,
			'start_date' => '2012-07-06 10:09:05',
			'end_date' => '2015-07-06 10:09:05'
		));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('parseBool')
			->with($this->equalTo('true'))
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($processorModelMock, '_preparePricingEventData')->invoke($processorModelMock, $dataObject, $outData)
		);
	}

	/**
	 * Test _applyAlternateTranslations method
	 * @test
	 */
	public function testApplyAlternateTranslations()
	{
		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('load', 'setStoreId', 'setUrlKey', 'setData', 'save'))
			->getMock();
		$productModelMock->expects($this->once())
			->method('load')
			->with($this->equalTo(1))
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('setStoreId')
			->with($this->equalTo(2))
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('setUrlKey')
			->with($this->equalTo(false))
			->will($this->returnSelf());
		$productModelMock->expects($this->at(3))
			->method('setData')
			->with($this->equalTo('description'), $this->equalTo('Ceci est un exemple de description long'))
			->will($this->returnSelf());
		$productModelMock->expects($this->at(4))
			->method('setData')
			->with($this->equalTo('short_description'), $this->equalTo('Ceci est un exemple de description courte'))
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'catalog/product', $productModelMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->setValue($processorModelMock, array('en-us' => 1, 'fr-fr' => 2));
		$this->_reflectProperty($processorModelMock, '_defaultLanguageCode')->setValue($processorModelMock, 'en-us');

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($processorModelMock, '_applyAlternateTranslations')->invoke($processorModelMock, 1, array(
				'description' => array('fr-fr' => 'Ceci est un exemple de description long'),
				'short_description' => array('fr-fr' => 'Ceci est un exemple de description courte')
			))
		);
	}

	/**
	 * Test _getProductColorOptionId method
	 * @test
	 */
	public function testGetProductColorOptionId()
	{
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeOptionId', '_addOptionToAttribute'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getAttributeOptionId')
			->with($this->equalTo('color'), $this->equalTo('Red'))
			->will($this->returnValue(0));
		$processorModelMock->expects($this->once())
			->method('_addOptionToAttribute')
			->with($this->equalTo('color'), $this->isType('array'))
			->will($this->returnValue(1));

		$this->assertSame(1, $this->_reflectMethod($processorModelMock, '_getProductColorOptionId')
			->invoke($processorModelMock, array('code' => 'Red'), $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _getProductColorOptionId method, with adding attribute option throw exception
	 * @test
	 */
	public function testGetProductColorOptionIdWithException()
	{
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::ADD_COLOR_OPTION_ERR),
				$this->equalTo('UnitTest Simulate Throw Exception on add option to attribute method')
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('addError')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnSelf());

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeOptionId', '_addOptionToAttribute'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getAttributeOptionId')
			->with($this->equalTo('color'), $this->equalTo('Red'))
			->will($this->returnValue(0));
		$processorModelMock->expects($this->once())
			->method('_addOptionToAttribute')
			->with($this->equalTo('color'), $this->isType('array'))
			->will($this->throwException(
				new TrueAction_Eb2cProduct_Model_Feed_Exception('UnitTest Simulate Throw Exception on add option to attribute method')
			));

		$this->assertSame(0, $this->_reflectMethod($processorModelMock, '_getProductColorOptionId')
			->invoke($processorModelMock, array('code' => 'Red'), $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _getProductTitleSet method
	 * @test
	 */
	public function testGetProductTitleSet()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			array('en-us' => 'This is an english title'),
			$this->_reflectMethod($processorModelMock, '_getProductTitleSet')->invoke($processorModelMock, new Varien_Object(array(
				'base_attributes' => new Varien_Object(array(
					'title' => array(array('lang' => 'en-us', 'title' => 'This is an english title'))
				))
			)))
		);
	}

	/**
	 * Test _getVisibilityData method
	 * @test
	 */
	public function testGetVisibilityData()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$testData = array(
			array(
				'expect' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
				'dataObject' => new Varien_Object(array(
					'base_attributes' => new Varien_Object(array('catalog_class' => 'nosale'))
				))
			),
			array(
				'expect' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
				'dataObject' => new Varien_Object(array(
					'base_attributes' => new Varien_Object(array('catalog_class' => 'regular'))
				))
			),
			array(
				'expect' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
				'dataObject' => new Varien_Object(array(
					'base_attributes' => new Varien_Object(array('catalog_class' => 'always'))
				))
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $this->_reflectMethod($processorModelMock, '_getVisibilityData')->invoke($processorModelMock, $data['dataObject']));
		}
	}

	/**
	 * Test _getItemStatusData method
	 * @test
	 */
	public function testGetItemStatusData()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$testData = array(
			array(
				'expect' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
				'originalStatus' => 'active'
			),
			array(
				'expect' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
				'originalStatus' => 'inactive'
			),
			array(
				'expect' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
				'originalStatus' => 'anything'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $this->_reflectMethod($processorModelMock, '_getItemStatusData')->invoke($processorModelMock, $data['originalStatus']));
		}
	}

	/**
	 * Test _addColorDescriptionToChildProduct method
	 * @test
	 */
	public function testAddColorDescriptionToChildProduct()
	{
		$storeModelMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$childProductObject = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('setStoreId', 'addData', 'save'))
			->getMock();
		$childProductObject->expects($this->once())
			->method('setStoreId')
			->with($this->equalTo(1))
			->will($this->returnSelf());
		$childProductObject->expects($this->once())
			->method('addData')
			->with($this->equalTo(array('color_description' => 'This is an example')))
			->will($this->returnSelf());
		$childProductObject->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getStores', 'getStoreViewLanguage'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getStores')
			->will($this->returnValue(array(
				'1' => $storeModelMock,
			)));
		$productHelperMock->expects($this->once())
			->method('getStoreViewLanguage')
			->with($this->equalTo($storeModelMock))
			->will($this->returnValue('en-us'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$parentColorDescriptionData = array((object) array('description' => array(
			(object) array('lang' => 'en-us', 'description' => 'This is an example'),
		)));

		$this->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_Processor', $this->_reflectMethod($processorModelMock, '_addColorDescriptionToChildProduct')
			->invoke($processorModelMock, $childProductObject, $parentColorDescriptionData, $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _addColorDescriptionToChildProduct method, where saving the product throw exception
	 * @test
	 */
	public function testAddColorDescriptionToChildProductWithException()
	{
		$storeModelMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$childProductObject = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('setStoreId', 'addData', 'save'))
			->getMock();
		$childProductObject->expects($this->once())
			->method('setStoreId')
			->with($this->equalTo(1))
			->will($this->returnSelf());
		$childProductObject->expects($this->once())
			->method('addData')
			->with($this->isType('array'))
			->will($this->returnSelf());
		$childProductObject->expects($this->once())
			->method('save')
			->will($this->throwException(
				new Exception('UnitTest Simulate Throw Exception on save product method')
			));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getStores', 'getStoreViewLanguage'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getStores')
			->will($this->returnValue(array(
				'1' => $storeModelMock,
			)));
		$productHelperMock->expects($this->once())
			->method('getStoreViewLanguage')
			->with($this->isInstanceOf('Mage_Core_Model_Store'))
			->will($this->returnValue('en-us'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::COLOR_DESCRIPTION_ERR),
				$this->equalTo('UnitTest Simulate Throw Exception on save product method')
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('addError')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnSelf());

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$parentColorDescriptionData = array((object) array('description' => array(
			(object) array('lang' => 'en-us', 'description' => 'This is an example'),
		)));

		$this->assertInstanceOf('TrueAction_Eb2cProduct_Model_Feed_Processor', $this->_reflectMethod($processorModelMock, '_addColorDescriptionToChildProduct')
			->invoke($processorModelMock, $childProductObject, $parentColorDescriptionData, $confirmationsModelMock, array(
				'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
				'remote' => '/Inbox/Product',
				'timestamp' => '1364823587',
				'type' => 'ItemMaster',
				'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
			)));
	}

	/**
	 * Test _getLocalizations method
	 * @test
	 */
	public function testGetLocalizations()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$testData = array(
			array(
				'expect' => array('en-us' => 'long description example'),
				'dataObject' => new Varien_Object(array(
					'long_description' => array(array('lang' => 'en-us', 'long_description' => 'long description example'))
				)),
				'fieldName' => 'long_description'
			),
			array(
				'expect' => array('en-us' => 'short description example'),
				'dataObject' => new Varien_Object(array(
					'short_description' => array(array('lang' => 'en-us', 'short_description' => 'short description example'))
				)),
				'fieldName' => 'short_description'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				$this->_reflectMethod($processorModelMock, '_getLocalizations')->invoke($processorModelMock, $data['dataObject'], $data['fieldName'])
			);
		}
	}

	/**
	 * Test _getEb2cSpecificAttributeData method
	 * @test
	 */
	public function testGetEb2cSpecificAttributeData()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('hasEavAttr', 'parseTranslations'))
			->getMock();
		$productHelperMock->expects($this->exactly(59))
			->method('hasEavAttr')
			->will($this->returnValue(true));
		$productHelperMock->expects($this->once())
			->method('parseTranslations')
			->with($this->isType('array'))
			->will($this->returnValue(array(array('en-us' => 'Blah Blah'))));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('normalizeSku'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('normalizeSku')
			->with($this->equalTo('HTC9383'), $this->equalTo('45'))
			->will($this->returnValue('45-HTC9383'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$dataObject = new Varien_Object(array(
			'catalog_id' => '45',
			'hts_codes' => array(),
			'item_id' => new Varien_Object(array(
				'client_alt_item_id' => 'CAIID',
				'manufacturer_item_id' => 'MII',
			)),
			'base_attributes' => new Varien_Object(array(
				'drop_shipped' => 'true',
				'tax_code' => 'TXC',
				'item_type' => 'Toys',
			)),
			'drop_ship_supplier_information' => new Varien_Object(array(
				'supplier_name' => 'SN',
				'supplier_number' => 'SNB',
				'supplier_part_number' => 'SPN',
			)),
			'extended_attributes' => new Varien_Object(array(
				'allow_gift_message' => 'false',
				'country_of_origin' => 'US',
				'gift_card_tender_code' => 'SV',
				'brand_name' => 'BN',
				'brand_description' => array(array('lang' => 'en-us', 'description' => 'blah blah')),
				'buyer_name' => 'BYN',
				'buyer_id' => 'BI',
				'companion_flag' => 'false',
				'hazardous_material_code' => 'HMC',
				'is_hidden_product' => 'false',
				'item_dimension_shipping' => new Varien_Object(array(
					'mass_unit_of_measure' => 'lbs',
					'weight' => '5.5',
				)),
				'item_dimension_display' => new Varien_Object(array(
					'mass_unit_of_measure' => 'lbs',
					'weight' => '1.5',
					'packaging' => new Varien_Object(array(
						'unit_of_measure' => 'UOM',
						'width' => '4',
						'length' => '2',
						'height' => '5',
					)),
				)),
				'item_dimension_shipping' => new Varien_Object(array(
					'packaging' => new Varien_Object(array(
						'unit_of_measure' => 'UOM',
						'width' => '4',
						'length' => '2',
						'height' => '5',
					)),
				)),
				'item_dimension_carton' => new Varien_Object(array(
					'mass_unit_of_measure' => 'lbs',
					'weight' => '1.5',
					'type' => 'TP',
					'packaging' => new Varien_Object(array(
						'unit_of_measure' => 'UOM',
						'width' => '4',
						'length' => '2',
						'height' => '5',
					)),
				)),
				'lot_tracking_indicator' => 'LTI',
				'ltl_freight_cost' => 'LFC',
				'manufacturer' => new Varien_Object(array(
					'date' => 'd',
					'name' => 'n',
					'id' => 'i',
				)),
				'may_ship_expedite' => 'MSE',
				'may_ship_international' => 'MSI',
				'may_ship_usps' => 'MSU',
				'safety_stock' => 'SS',
				'sales_class' => 'SC',
				'serial_number_type' => 'SNT',
				'service_indicator' => 'SI',
				'ship_group' => 'SG',
				'ship_window_min_hour' => 'SWMH',
				'ship_window_max_hour' => 'SWMH',
				'street_date' => 'SD',
				'style_id' => 'HTC9383',
				'style_description' => 'SD',
				'supplier_name' => 'SN',
				'supplier_supplier_id' => 'SSI',
				'size_attributes' => new Varien_Object(array(
					'size' => array(array('lang' => 'en-us', 'description' => '1 1/2 inch'))
				)),
			))
		));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$this->_reflectProperty($processorModelMock, '_defaultLanguageCode')->setValue($processorModelMock, 'en-us');

		$this->assertSame(
			array(
				'is_drop_shipped' => 'true',
				'tax_code' => 'TXC',
				'drop_ship_supplier_name' => 'SN',
				'drop_ship_supplier_number' => 'SNB',
				'drop_ship_supplier_part' => 'SPN',
				'gift_message_available' => 'false',
				'use_config_gift_message_available' => false,
				'country_of_manufacture' => 'US',
				'gift_card_tender_code' => 'SV',
				'item_type' => 'Toys',
				'client_alt_item_id' => 'CAIID',
				'manufacturer_item_id' => 'MII',
				'brand_name' => 'BN',
				'brand_description' => array(
					array('en-us' => 'Blah Blah')
				),
				'buyer_name' => 'BYN',
				'buyer_id' => 'BI',
				'companion_flag' => 'false',
				'hazardous_material_code' => 'HMC',
				'is_hidden_product' => 'false',
				'item_dimension_display_mass_unit_of_measure' => 'lbs',
				'item_dimension_display_mass_weight' => '1.5',
				'item_dimension_display_packaging_unit_of_measure' => 'UOM',
				'item_dimension_display_packaging_width' => '4',
				'item_dimension_display_packaging_length' => '2',
				'item_dimension_display_packaging_height' => '5',
				'item_dimension_shipping_packaging_unit_of_measure' => 'UOM',
				'item_dimension_shipping_packaging_width' => '4',
				'item_dimension_shipping_packaging_length' => '2',
				'item_dimension_shipping_packaging_height' => '5',
				'item_dimension_carton_mass_unit_of_measure' => 'lbs',
				'item_dimension_carton_mass_weight' => '1.5',
				'item_dimension_carton_packaging_unit_of_measure' => 'UOM',
				'item_dimension_carton_packaging_width' => '4',
				'item_dimension_carton_packaging_length' => '2',
				'item_dimension_carton_packaging_height' => '5',
				'item_dimension_carton_type' => 'TP',
				'lot_tracking_indicator' => 'LTI',
				'ltl_freight_cost' => 'LFC',
				'manufacturing_date' => 'd',
				'manufacturer_name' => 'n',
				'manufacturer_manufacturer_id' => 'i',
				'may_ship_expedite' => 'MSE',
				'may_ship_international' => 'MSI',
				'may_ship_usps' => 'MSU',
				'safety_stock' => 'SS',
				'sales_class' => 'SC',
				'serial_number_type' => 'SNT',
				'service_indicator' => 'SI',
				'ship_group' => 'SG',
				'ship_window_min_hour' => 'SWMH',
				'ship_window_max_hour' => 'SWMH',
				'street_date' => 'SD',
				'style_id' => '45-HTC9383',
				'style_description' => 'SD',
				'supplier_name' => 'SN',
				'supplier_supplier_id' => 'SSI',
				'size' => '1 1/2 inch'
			),
			$this->_reflectMethod($processorModelMock, '_getEb2cSpecificAttributeData')->invoke($processorModelMock, $dataObject)
		);
	}

	/**
	 * Test _getAttributeValueByKey method
	 * @test
	 */
	public function testGetAttributeValueByKey()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getDefaultProductAttributeSetId'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getDefaultProductAttributeSetId')
			->will($this->returnValue(75));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$testData = array(
			array('expect' => 75, 'attributes' => array(), 'field' => 'wrong'),
			array('expect' => 58, 'attributes' => array(array('name' => 'AttributeSet', 'value' => 58)), 'field' => 'AttributeSet'),
		);

		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				$this->_reflectMethod($processorModelMock, '_getAttributeValueByKey')->invoke($processorModelMock, $data['attributes'], $data['field'])
			);
		}
	}

	/**
	 * Test _prepareCustomAttributes method
	 * @test
	 */
	public function testPrepareCustomAttributes()
	{
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError'))
			->getMock();
		$confirmationsModelMock->expects($this->exactly(5))
			->method('addError')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(0))
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::CONFIGURABLE_ATTRIBUTE_ERR),
				$this->equalTo('UnitTest Simulate Throw Exception on processing configurable attribute method')
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(2))
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::INVALID_LANG_CODE_ERR),
				$this->equalTo("'es-sp' for attribute (description)")
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(4))
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::INVALID_ATTRIBUTE_OPERATION_ERR),
				$this->equalTo("'wrong' for attribute (description)")
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(6))
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::MISSING_ATTRIBUTE_OPERATION_ERR),
				$this->equalTo('description')
			)
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(8))
			->method('addMessage')
			->with(
				$this->equalTo(TrueAction_Eb2cProduct_Model_Error_Confirmations::MISSING_IN_ATTRIBUTE_SET_ERR),
				$this->equalTo('wrong')
			)
			->will($this->returnSelf());

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getCustomAttributeCodeSet'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('getCustomAttributeCodeSet')
			->with($this->equalTo(58))
			->will($this->returnValue(array(
				'0' => 'name',
				'1' => 'description',
				'2' => 'short_description',
			)));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getAttributeValueByKey', '_processConfigurableAttributes', '_incrementOperationTypeError'))
			->getMock();
		$processorModelMock->expects($this->once())
			->method('_getAttributeValueByKey')
			->with($this->isType('array'), $this->equalTo('AttributeSet'))
			->will($this->returnValue(58));
		$processorModelMock->expects($this->at(1))
			->method('_processConfigurableAttributes')
			->with($this->isType('array'), $this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(null));
		$processorModelMock->expects($this->at(2))
			->method('_processConfigurableAttributes')
			->with($this->isType('array'), $this->isInstanceOf('Varien_Object'))
			->will($this->throwException(
				new TrueAction_Eb2cProduct_Model_Feed_Exception('UnitTest Simulate Throw Exception on processing configurable attribute method')
			));
		$processorModelMock->expects($this->at(3))
			->method('_incrementOperationTypeError')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_LANGUAGE))
			->will($this->returnSelf());
		$processorModelMock->expects($this->at(4))
			->method('_incrementOperationTypeError')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_OP_TYPE))
			->will($this->returnSelf());
		$processorModelMock->expects($this->at(5))
			->method('_incrementOperationTypeError')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_OP_TYPE))
			->will($this->returnSelf());
		$processorModelMock->expects($this->at(6))
			->method('_incrementOperationTypeError')
			->with($this->equalTo(TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_ATTRIBUTE))
			->will($this->returnSelf());

		$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->setValue($processorModelMock, array('en-us' => 1, 'fr-fr' => 2));
		$this->_reflectProperty($processorModelMock, '_defaultLanguageCode')->setValue($processorModelMock, 'en-us');
		$this->_reflectProperty($processorModelMock, '_customAttributeErrors')->setValue($processorModelMock, array());

		$testData = array(
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'customAttributes' => array(
					array('name' => 'AttributeSet', 'value' => 58),
					array('name' => 'producttype', 'value' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
					array('name' => 'ConfigurableAttributes', 'value' => array()),
					array('name' => 'ConfigurableAttributes', 'value' => array()), // testing throwing exception
					array('name' => 'description', 'value' => array(), 'lang' => 'es-sp', 'operation_type' => 'Add'),
					array('name' => 'description', 'value' => array(), 'lang' => 'en-us', 'operation_type' => 'Add'),
					array('name' => 'description', 'value' => array(), 'lang' => 'en-us', 'operation_type' => 'Delete'),
					array('name' => 'description', 'value' => array(), 'lang' => 'en-us', 'operation_type' => 'wrong'),
					array('name' => 'description', 'value' => array(), 'lang' => 'en-us'),
					array('name' => 'wrong', 'value' => array(), 'lang' => 'en-us'),
				),
				'outData' => new Varien_Object(),
				'errorConfirmations' => $confirmationsModelMock,
				'fileDetail' => array(
					'local' => 'TrueAction/Eb2c/Feed/Product/ItemMaster/inbound/ItemMaster_TestSubset.xml',
					'remote' => '/Inbox/Product',
					'timestamp' => '1364823587',
					'type' => 'ItemMaster',
					'error_file' => '/Product/ItemMaster/outbound/ItemMaster_20140113230330_1234_ABCD.xml'
				)
			),
		);

		foreach ($testData as $data) {
			$this->assertInstanceOf(
				$data['expect'],
				$this->_reflectMethod($processorModelMock, '_prepareCustomAttributes')->invoke(
					$processorModelMock,
					$data['customAttributes'],
					$data['outData'],
					$data['errorConfirmations'],
					$data['fileDetail']
				)
			);
		}
	}

	/**
	 * Test _incrementOperationTypeError method
	 * @test
	 */
	public function testIncrementOperationTypeError()
	{
		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		// set class property '_customAttributeErrors' to a know state
		$this->_reflectProperty($processorModelMock, '_customAttributeErrors')->setValue($processorModelMock, array());
		$testData = array(
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'operationType' => TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_LANGUAGE
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'operationType' => TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_OP_TYPE
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'operationType' => TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_OP_TYPE
			),
			array(
				'expect' => 'TrueAction_Eb2cProduct_Model_Feed_Processor',
				'operationType' => TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_ATTRIBUTE
			),
		);

		foreach ($testData as $data) {
			$this->assertInstanceOf(
				$data['expect'],
				$this->_reflectMethod($processorModelMock, '_incrementOperationTypeError')->invoke($processorModelMock, $data['operationType'])
			);
		}

		$this->assertSame(
			array(
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_LANGUAGE => 1,
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_OP_TYPE => 1,
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_OP_TYPE => 1,
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_ATTRIBUTE => 1
			),
			$this->_reflectProperty($processorModelMock, '_customAttributeErrors')->getValue($processorModelMock)
		);

		foreach ($testData as $data) {
			$this->assertInstanceOf(
				$data['expect'],
				$this->_reflectMethod($processorModelMock, '_incrementOperationTypeError')->invoke($processorModelMock, $data['operationType'])
			);
		}

		$this->assertSame(
			array(
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_LANGUAGE => 2,
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_INVALID_OP_TYPE => 2,
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_OP_TYPE => 2,
				TrueAction_Eb2cProduct_Model_Feed_Processor::CA_ERROR_MISSING_ATTRIBUTE => 2
			),
			$this->_reflectProperty($processorModelMock, '_customAttributeErrors')->getValue($processorModelMock)
		);
	}
}
