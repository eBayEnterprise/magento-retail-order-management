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

	/**
	 * Test _synchProduct method
	 * @test
	 */
	public function testSynchProduct()
	{
		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'save', 'getId'))
			->getMock();
		$productModelMock->expects($this->at(0))
			->method('addData')
			->with($this->equalTo(array(
				'type_id' => 'simple',
				'weight' => 2.5,
				'mass' => 'lbs',
				'visibility' => 4,
				'status' => 1,
				'msrp' => 10.95,
				'price' => 15.95,
				'url_key' => 'T123EST',
				'unresolved_product_links' => 'a:1:{i:0;a:1:{i:0;s:6:"HTC150";}}',
				'category_ids' => array(1,2,3,4),
				'color' => 1,
				'configurable_attributes_data' => array(),
				'is_clean' => 0
			)))
			->will($this->returnSelf());
		$productModelMock->expects($this->at(1))
			->method('addData')
			->with($this->equalTo(array()))
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));

		$item = new Varien_Object(array(
			'item_id' => new Varien_Object(array(
				'client_item_id' => 'T123EST',
			)),
			'base_attributes' => new Varien_Object(array(
				'item_description' => 'T123EST Long Description',
				'catalog_class' => 'regular',
				'item_status' => 'active',
			)),
			'extended_attributes' => new Varien_Object(array(
				'item_dimension_shipping' => new Varien_Object(array(
					'weight' => 2.5,
					'mass' => 'lbs',
					'mass_unit_of_measure' => 'lbs',
				)),
				'msrp' => 10.95,
				'price' => 15.95,
				'color' => array(),
			)),
			'product_type' => 'simple',
			'product_links' => array(array('HTC150')),
			'category_links' => array(array('toys-category')),
			'configurable_attributes_data' => array(),
			'is_clean' => 0,
		));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('prepareProductModel'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('prepareProductModel')
			->with($this->equalTo('T123EST'), $this->equalTo('T123EST Long Description'))
			->will($this->returnValue($productModelMock));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array(
				'_mergeTranslations',
				'_applyDefaultTranslations',
				'_getEb2cSpecificAttributeData',
				'_addStockItemDataToProduct',
				'_applyAlternateTranslations',
				'_prepareProductData'
			))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_mergeTranslations')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(array()));
		$feedProcessorModelMock->expects($this->once())
			->method('_applyDefaultTranslations')
			->with($this->isInstanceOf('Varien_Object'), $this->isType('array'))
			->will($this->returnValue(array('long_description' => array(
				'en_US' => 'Test Product', 'fr_FR' => 'produit d\'essai'
			))));
		$feedProcessorModelMock->expects($this->once())
			->method('_getEb2cSpecificAttributeData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(array()));
		$feedProcessorModelMock->expects($this->once())
			->method('_addStockItemDataToProduct')
			->with($this->isInstanceOf('Varien_Object'), $this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnValue(array()));
		$feedProcessorModelMock->expects($this->once())
			->method('_applyAlternateTranslations')
			->with($this->equalTo(1), $this->isType('array'))
			->will($this->returnSelf());
		$feedProcessorModelMock->expects($this->once())
			->method('_prepareProductData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(array(
				'type_id' => 'simple',
				'weight' => 2.5,
				'mass' => 'lbs',
				'visibility' => 4,
				'status' => 1,
				'msrp' => 10.95,
				'price' => 15.95,
				'url_key' => 'T123EST',
				'unresolved_product_links' => 'a:1:{i:0;a:1:{i:0;s:6:"HTC150";}}',
				'category_ids' => array(1,2,3,4),
				'color' => 1,
				'configurable_attributes_data' => array(),
				'is_clean' => 0
			)));

		$this->_reflectProperty($feedProcessorModelMock, '_helper')->setValue($feedProcessorModelMock, $productHelperMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_synchProduct')->invoke($feedProcessorModelMock, $item)
		);
	}

	/**
	 * Test _synchProduct method, with invalid sku from feed
	 * @test
	 */
	public function testSynchProductInvalidSkuFromFeed()
	{
		$item = new Varien_Object(array(
			'item_id' => new Varien_Object(array(
				'client_item_id' => null,
			)),
		));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_synchProduct')->invoke($feedProcessorModelMock, $item)
		);
	}

	/**
	 * Test _prepareProductData method
	 * @test
	 */
	public function testPrepareProductData()
	{
		$item = new Varien_Object(array(
			'item_id' => new Varien_Object(array(
				'client_item_id' => 'T123EST',
			)),
			'base_attributes' => new Varien_Object(array(
				'item_description' => 'T123EST Long Description',
				'catalog_class' => 'regular',
				'item_status' => 'active',
			)),
			'extended_attributes' => new Varien_Object(array(
				'item_dimension_shipping' => new Varien_Object(array(
					'weight' => 2.5,
					'mass' => 'lbs',
					'mass_unit_of_measure' => 'lbs',
				)),
				'msrp' => 10.95,
				'price' => 15.95,
				'color' => array(),
			)),
			'product_type' => 'simple',
			'product_links' => array(array('HTC150')),
			'category_links' => array(array('name' => 'toys-category', 'import_mode' => 'Update')),
			'configurable_attributes_data' => array(),
			'is_clean' => 0,
		));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getVisibilityData', '_getItemStatusData', '_preparedCategoryLinkData', '_getProductColorOptionId'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_getVisibilityData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH));
		$feedProcessorModelMock->expects($this->once())
			->method('_getItemStatusData')
			->with($this->equalTo('active'))
			->will($this->returnValue(Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
		$feedProcessorModelMock->expects($this->once())
			->method('_preparedCategoryLinkData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(array(1,2,3,4)));
		$feedProcessorModelMock->expects($this->once())
			->method('_getProductColorOptionId')
			->with($this->isType('array'))
			->will($this->returnValue(1));
		$this->_reflectProperty($feedProcessorModelMock, '_mapProductField')
			->setValue($feedProcessorModelMock, array(
				'type_id' => array('map' => 'product_type', 'extractor' => array()),
				'weight' => array('map' => 'extended_attributes/item_dimension_shipping/weight', 'extractor' => array()),
				'mass' => array('map' => 'extended_attributes/item_dimension_shipping/mass_unit_of_measure', 'extractor' => array()),
				'visibility' => array(
					'map' => 'base_attributes/catalog_class',
					'extractor' => array('argument' => 'item', 'object' => $feedProcessorModelMock, 'method' => '_getVisibilityData')
				),
				'status' => array(
					'map' => 'base_attributes/item_status',
					'extractor' => array('argument' => 'value', 'object' => $feedProcessorModelMock, 'method' => '_getItemStatusData')
				),
				'msrp' => array('map' => 'extended_attributes/msrp', 'extractor' => array()),
				'price' => array('map' => 'extended_attributes/price', 'extractor' => array()),
				'url_key' => array('map' => 'item_id/client_item_id', 'extractor' => array()),
				'unresolved_product_links' => array(
					'map' => 'product_links',
					'extractor' => array('argument' => 'value', 'object' => $feedProcessorModelMock, 'method' => '_serializeData')
				),
				'category_ids' => array(
					'map' => 'category_links',
					'extractor' => array('argument' => 'item', 'object' => $feedProcessorModelMock, 'method' => '_preparedCategoryLinkData')
				),
				'color' => array(
					'map' => 'extended_attributes/color',
					'extractor' => array('argument' => 'value', 'object' => $feedProcessorModelMock, 'method' => '_getProductColorOptionId')
				),
				'configurable_attributes_data' => array('map' => 'configurable_attributes_data', 'extractor' => array()),
				'is_clean' => array('map' => 'static', 'extractor' => array('value' => 0)),
			));

		$this->assertSame(
			array(
				'type_id' => 'simple',
				'weight' => 2.5,
				'mass' => 'lbs',
				'visibility' => 4,
				'status' => 1,
				'msrp' => 10.95,
				'price' => 15.95,
				'url_key' => 'T123EST',
				'unresolved_product_links' => 'a:1:{i:0;a:1:{i:0;s:6:"HTC150";}}',
				'category_ids' => array(1,2,3,4),
				'color' => 1,
				'configurable_attributes_data' => array(),
				'is_clean' => 0
			),
			$this->_reflectMethod($feedProcessorModelMock, '_prepareProductData')->invoke($feedProcessorModelMock, $item)
		);

	}

	/**
	 * Test _extractMapData method, testing all the edge cases
	 * @test
	 */
	public function testExtractMapData()
	{
		$testData = array(
			array(
				'expect' => 'T123EST',
				'map' => array('map' => 'item_id/client_item_id', 'extractor' => array()),
				'item' => new Varien_Object(array(
					'item_id' => new Varien_Object(array(
						'client_item_id' => 'T123EST',
					)),
				))
			),
			array(
				'expect' => '',
				'map' => array('map' => '', 'extractor' => array()),
				'item' => new Varien_Object(array(
					'item_id' => new Varien_Object(),
				))
			),
			array(
				'expect' => '',
				'map' => array('map' => 'fake-path', 'extractor' => array('argument' => new Varien_Object())),
				'item' => new Varien_Object(array(
					'item_id' => new Varien_Object(),
				))
			),
			array(
				'expect' => '',
				'map' => array('map' => 'fake_path/more_fake', 'extractor' => array('argument' => new Varien_Object())),
				'item' => new Varien_Object(array(
					'fake_path' => new Varien_Object(),
				))
			)
		);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				$this->_reflectMethod($feedProcessorModelMock, '_extractMapData')->invoke($feedProcessorModelMock, $data['map'], $data['item'])
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
}
