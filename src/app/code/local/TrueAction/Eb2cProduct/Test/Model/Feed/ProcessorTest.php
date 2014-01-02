<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_ProcessorTest extends TrueAction_Eb2cCore_Test_Base
{
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
		$testModel = $this->getModelMock('eb2cproduct/feed_processor', array('_synchProduct', '_isAtLimit'));
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
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
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
		$entityAttributeCollectionModelMock = $this->getResourceModelMockBuilder('eav/entity_attribute_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$entityAttributeCollectionModelMock->expects($this->once())
			->method('getItemByColumnValue')
			->with($this->equalTo('attribute_code'), $this->equalTo('color'))
			->will($this->returnValue(Mage::getModel('eav/entity_attribute')->addData(array(
				'id' => 92
			))));

		$processorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($processorModelMock, '_attributes')->setValue($processorModelMock, $entityAttributeCollectionModelMock);
		$this->_reflectProperty($processorModelMock, '_storeLanguageCodeMap')->setValue($processorModelMock, array('en' => 1));

		$this->assertInstanceOf(
			'Mage_Eav_Model_Resource_Entity_Attribute_Collection',
			$this->_reflectMethod($processorModelMock, '_addOptionToAttribute')->invoke($processorModelMock, 'color', 700)
		);
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
	 * Test _getAttributeOptionId method
	 * @test
	 */
	public function testGetAttributeOptionId()
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

		$this->assertSame(12, $this->_reflectMethod($processorModelMock, '_getAttributeOptionId')->invoke($processorModelMock, 'color', '700'));
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

		$this->_reflectProperty($processorModelMock, '_entityTypeId')->setValue($processorModelMock, 4);

		$this->assertInstanceOf(
			'Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection',
			$this->_reflectMethod($processorModelMock, '_getAttributeOptionCollection')->invoke($processorModelMock, 'color')
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

		$this->_reflectProperty($processorModelMock, '_entityTypeId')->setValue($processorModelMock, 4);

		$this->assertInstanceOf(
			'Mage_Eav_Model_Resource_Entity_Attribute_Collection',
			$this->_reflectMethod($processorModelMock, '_getAttributeCollection')->invoke($processorModelMock)
		);
	}
}
