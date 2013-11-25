<?php
class TrueAction_Eb2cProduct_Test_Model_Feed_ProcessorTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test _setDefaultTranslation method
	 * @test
	 */
	public function testSetDefaultTranslation()
	{
		$longDescriptionSet = array(
			'en-US' => "The Little People Lil' Kingdom Palace lets preschoolers experience fun,
			imaginative house play as they discover this classic 'storybook' theme with the
			charm of Little People characters. The palace features surprise action and music to add to your li",
			'fr-FR' => "Le Little People Lil-Uni Palais permet d'âge préscolaire expérience amusante,
			Maison imaginative jouer comme ils découvrent ce thème classique \"de livre de contes» avec la
			charme de Little People caractères. Le palais dispose d'une action surprise et de la musique à ajouter à votre li"
		);

		$source = new Varien_Object(array(
			'extended_attributes' => new Varien_Object(array(
				'long_description_set' => $longDescriptionSet
			)),
		));

		$target = $this->getMock('Varien_Object');
		$target->expects($this->at(0))
			->method('setData')
			->with($this->equalTo('description'), $this->equalTo($longDescriptionSet['en-US']))
			->will($this->returnSelf());
		$target->expects($this->at(1))
			->method('setData')
			->with($this->equalTo('description'), $this->equalTo($longDescriptionSet['fr-FR']))
			->will($this->returnSelf());

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		foreach (array('en-US', 'fr-FR') as $lCode) {
			$this->_reflectProperty($feedProcessorModelMock, '_defaultStoreLanguageCode')->setValue($feedProcessorModelMock, $lCode);
			$newLongDescriptionSet = $longDescriptionSet;
			unset($newLongDescriptionSet[$lCode]);
			$this->assertSame(
				$newLongDescriptionSet,
				$this->_reflectMethod($feedProcessorModelMock, '_setDefaultTranslation')
					->invoke($feedProcessorModelMock, $source, $target, 'description', 'long_description_set')
			);
		}
	}

	/**
	 * Test _getTranslation method
	 * @test
	 */
	public function testGetTranslation()
	{
		$englishTxt = "The Little People Lil' Kingdom Palace lets preschoolers experience fun,
			imaginative house play as they discover this classic 'storybook' theme with the
			charm of Little People characters. The palace features surprise action and music to add to your li";
		$frenchTxt = "Le Little People Lil-Uni Palais permet d'âge préscolaire expérience amusante,
			Maison imaginative jouer comme ils découvrent ce thème classique \"de livre de contes» avec la
			charme de Little People caractères. Le palais dispose d'une action surprise et de la musique à ajouter à votre li";
		$arrayOfTranslations = array(
			'en-US' => $englishTxt,
			'fr-FR' => $frenchTxt,
		);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$testData = array(
			array(
				'expect' => $englishTxt,
				'languageCode' => 'en-US',
			),
			array(
				'expect' => $frenchTxt,
				'languageCode' => 'fr-FR',
			),
			array(
				'expect' => false,
				'languageCode' => 'es-SP',
			)
		);

		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				$this->_reflectMethod($feedProcessorModelMock, '_getTranslation')
					->invoke($feedProcessorModelMock, $data['languageCode'], $arrayOfTranslations)
			);
		}
	}

	/**
	 * Test _getAllStores method
	 * @test
	 */
	public function testGetAllStores()
	{
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		// there's no currently loaded store
		$this->assertEmpty(
			$this->_reflectMethod($feedProcessorModelMock, '_getAllStores')
				->invoke($feedProcessorModelMock)
		);
	}

	/**
	 * Test _getStoreById method
	 * @test
	 */
	public function testGetStoreById()
	{
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertInstanceOf(
			'Mage_Core_Model_Store',
			$this->_reflectMethod($feedProcessorModelMock, '_getStoreById')
				->invoke($feedProcessorModelMock, 0)
		);
	}

	/**
	 * Test _initStoreLanguageCodeMap method
	 * @test
	 */
	public function testInitStoreLanguageCodeMap()
	{
		$coreStoreModelMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getCode', 'getId', 'getName'))
			->getMock();
		$coreStoreModelMock->expects($this->at(0))
			->method('getCode')
			->will($this->returnValue('eb2c_en_US'));
		$coreStoreModelMock->expects($this->at(2))
			->method('getCode')
			->will($this->returnValue('eb2c_fr_FR'));
		$coreStoreModelMock->expects($this->at(4))
			->method('getCode')
			->will($this->returnValue('es_SP'));
		$coreStoreModelMock->expects($this->exactly(2))
			->method('getId')
			->will($this->returnValue(0));
		$coreStoreModelMock->expects($this->once())
			->method('getName')
			->will($this->returnValue('Test product'));

		$this->replaceByMock('model', 'eb2ccore/Store', $coreStoreModelMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getAllStores', '_getStoreById'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_getAllStores')
			->will($this->returnValue(range(1, 3)));
		$feedProcessorModelMock->expects($this->at(1))
			->method('_getStoreById')
			->with($this->equalTo(1))
			->will($this->returnValue($coreStoreModelMock));
		$feedProcessorModelMock->expects($this->at(2))
			->method('_getStoreById')
			->with($this->equalTo(2))
			->will($this->returnValue($coreStoreModelMock));
		$feedProcessorModelMock->expects($this->at(3))
			->method('_getStoreById')
			->with($this->equalTo(3))
			->will($this->returnValue($coreStoreModelMock));

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_initStoreLanguageCodeMap')
				->invoke($feedProcessorModelMock)
		);
	}

	/**
	 * Test processUpdates method
	 * @test
	 */
	public function testProcessUpdates()
	{
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_transformData', '_synchProduct'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_transformData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(new Varien_Object()));
		$feedProcessorModelMock->expects($this->once())
			->method('_synchProduct')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(null));

		$feedProcessorModelMock->processUpdates(array(new Varien_Object()));
	}

	/**
	 * Test processDeletions method
	 * @test
	 */
	public function testProcessDeletions()
	{
		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_deleteItem'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_deleteItem')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(null));

		$feedProcessorModelMock->processDeletions(array(new Varien_Object()));
	}

	/**
	 * return value passed base on parameter pass
	 * @return mix
	 */
	public function getVarienDataCallBack()
	{
		$args = func_get_args();
		if (count($args) >= 1) {
			switch ($args[0]) {
				case 'catalog_id':
					return '45';
				case 'gsi_client_id':
					return '00045';
				case 'gsi_store_id':
					return 'abc123';
				case 'operation_type':
					return 'add';
				case 'client_item_id':
					return '1234';
				case 'client_alt_item_id':
					return '1223';
				case 'manufacturer_item_id':
					return '4556';
				case 'unique_id':
					return '1234';
				case 'hts_codes':
					return array(array('HTSCode' => 'abc123', 'mfn_duty_rate' => 1.02, 'destination_country' => 'USA', 'restricted' => true));
				case 'catalog_class':
					return 'simple';
				case 'item_dimension_shipping':
					return 'shipping';
				case 'product_links':
					return array();
				case 'category_links':
					return array();
				case 'allow_gift_message':
					return 'true';
				case 'is_drop_shipped':
					return 'false';
				default:
					return null;
			}
		}
		return null;
	}

	/**
	 * return true | false base on the argument pass
	 * @return bool
	 */
	public function hasVarienDataCallBack()
	{
		$args = func_get_args();
		if (count($args) >= 1) {
			switch ($args[0]) {
				case 'client_alt_item_id':
					return true;
				case 'manufacturer_item_id':
					return true;
				case 'unique_id':
					return true;
				case 'hts_codes':
					return true;
				case 'catalog_class':
					return true;
				case 'item_dimension_shipping':
					return true;
				case 'product_links':
					return true;
				case 'category_links':
					return true;
				case 'brand_name':
					return true;
				case 'allow_gift_message':
					return true;
				default:
					return false;
			}
		}
		return false;
	}

	/**
	 * Test _transformData method
	 * @test
	 */
	public function testTransformData()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelperMock->expects($this->at(0))
			->method('parseBool')
			->with($this->equalTo('false'))
			->will($this->returnValue(false));
		$productHelperMock->expects($this->at(1))
			->method('parseBool')
			->with($this->equalTo('true'))
			->will($this->returnValue(true));

		$dataObject = $this->getMock('Varien_Object', array('getData', 'hasData'));
		$dataObject->expects($this->any())
			->method('getData')
			->will($this->returnCallback(array($this, 'getVarienDataCallBack')));
		$dataObject->expects($this->any())
			->method('hasData')
			->will($this->returnCallback(array($this, 'hasVarienDataCallBack')));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_preparePricingEventData', '_getContentExtendedAttributeData', '_prepareCustomAttributes'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_preparePricingEventData')
			->with($this->isInstanceOf('Varien_Object'), $this->isInstanceOf('Varien_Object'))
			->will($this->returnSelf());
		$feedProcessorModelMock->expects($this->once())
			->method('_getContentExtendedAttributeData')
			->with($this->isInstanceOf('Varien_Object'))
			->will($this->returnValue(array()));
		$feedProcessorModelMock->expects($this->once())
			->method('_prepareCustomAttributes')
			->with($this->isInstanceOf('Varien_Object'), $this->isInstanceOf('Varien_Object'))
			->will($this->returnSelf());

		$this->_reflectProperty($feedProcessorModelMock, '_helper')->setValue($feedProcessorModelMock, $productHelperMock);

		$this->assertInstanceOf(
			'Varien_Object',
			$this->_reflectMethod($feedProcessorModelMock, '_transformData')
				->invoke($feedProcessorModelMock, $dataObject)
		);
	}

	/**
	 * Test _deleteItem method
	 * @test
	 */
	public function testDeleteItem()
	{
		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'delete'))
			->getMock();
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$productModelMock->expects($this->once())
			->method('delete')
			->will($this->returnSelf());

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->with($this->equalTo('1234-test'))
			->will($this->returnValue($productModelMock));

		$dataObject = $this->getMock('Varien_Object', array('getClientItemId'));
		$dataObject->expects($this->once())
			->method('getClientItemId')
			->will($this->returnValue('1234-test'));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedProcessorModelMock, '_helper')->setValue($feedProcessorModelMock, $productHelperMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_deleteItem')
				->invoke($feedProcessorModelMock, $dataObject)
		);
	}

	/**
	 * Test _deleteItem method, with exception
	 * @test
	 */
	public function testDeleteItemWithException()
	{
		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'delete'))
			->getMock();
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$productModelMock->expects($this->once())
			->method('delete')
			->will($this->throwException(
				new Mage_Core_Exception('UnitTest Simulate _deleteItem method Throw Exception')
			));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->with($this->equalTo('1234-test'))
			->will($this->returnValue($productModelMock));

		$dataObject = $this->getMock('Varien_Object', array('getClientItemId'));
		$dataObject->expects($this->once())
			->method('getClientItemId')
			->will($this->returnValue('1234-test'));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedProcessorModelMock, '_helper')->setValue($feedProcessorModelMock, $productHelperMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_deleteItem')
				->invoke($feedProcessorModelMock, $dataObject)
		);
	}

	/**
	 * Test _deleteItem method, no valid product found
	 * @test
	 */
	public function testDeleteItemNoValidProduct()
	{
		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getId', 'delete'))
			->getMock();
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(0));
		$productModelMock->expects($this->never())
			->method('delete')
			->will($this->returnSelf());

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('loadProductBySku'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->with($this->equalTo('1234-test'))
			->will($this->returnValue($productModelMock));

		$dataObject = $this->getMock('Varien_Object', array('getClientItemId'));
		$dataObject->expects($this->once())
			->method('getClientItemId')
			->will($this->returnValue('1234-test'));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedProcessorModelMock, '_helper')->setValue($feedProcessorModelMock, $productHelperMock);

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_deleteItem')
				->invoke($feedProcessorModelMock, $dataObject)
		);
	}

	/**
	 * Test _getContentExtendedAttributeData method
	 * @test
	 */
	public function testGetContentExtendedAttributeData()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('parseBool')
			->with($this->equalTo('true'))
			->will($this->returnValue(true));

		$dataObject = $this->getMock('Varien_Object', array('getExtendedAttributes'));
		$dataObject->expects($this->once())
			->method('getExtendedAttributes')
			->will($this->returnValue(new Varien_Object(array(
				'gift_wrap' => 'true',
				'long_description' => array(array(
					'lang' => 'en-US',
					'long_description' => 'Long description content'
				)),
				'short_description' => array(array(
					'lang' => 'en-US',
					'short_description' => 'Short description content'
				))
			))));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedProcessorModelMock, '_helper')->setValue($feedProcessorModelMock, $productHelperMock);

		$this->assertSame(
			array(
				'gift_wrap' => true,
				'long_description_set' => array(
					'en-US' => 'Long description content'
				),
				'short_description_set' => array(
					'en-US' => 'Short description content'
				)
			),
			$this->_reflectMethod($feedProcessorModelMock, '_getContentExtendedAttributeData')
				->invoke($feedProcessorModelMock, $dataObject)
		);
	}

	/**
	 * Test _isDefaultStoreLanguage method
	 * @test
	 */
	public function testIsDefaultStoreLanguage()
	{
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('xmlToMageLangFrmt'))
			->getMock();
		$coreHelperMock::staticExpects($this->at(0))
			->method('xmlToMageLangFrmt')
			->with($this->equalTo('en-US'))
			->will($this->returnValue('en_US'));
		$coreHelperMock::staticExpects($this->at(1))
			->method('xmlToMageLangFrmt')
			->with($this->equalTo('fr-FR'))
			->will($this->returnValue('fr_FR'));
		$coreHelperMock::staticExpects($this->at(2))
			->method('xmlToMageLangFrmt')
			->with($this->equalTo('es-SP'))
			->will($this->returnValue('es_SP'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->_reflectProperty($feedProcessorModelMock, '_defaultStoreLanguageCode')->setValue($feedProcessorModelMock, 'en_US');

		$testData = array(
			array('expect' => false, 'code' => 'en-US'),
			array('expect' => true, 'code' => 'fr-FR'),
			array('expect' => true, 'code' => 'es-SP'),
		);
		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				$this->_reflectMethod($feedProcessorModelMock, '_isDefaultStoreLanguage')
					->invoke($feedProcessorModelMock, $data['code'])
			);
		}
	}

	/**
	 * Test _prepareCustomAttributes method
	 * @test
	 */
	public function testPrepareCustomAttributes()
	{
		$dataObject = $this->getMock('Varien_Object', array('getCustomAttributes'));
		$dataObject->expects($this->once())
			->method('getCustomAttributes')
			->will($this->returnValue(array(
				array(),
				array('name' => 'Color', 'value' => 'Red'),
				array('name' => 'size', 'value' => '1 1/2', 'operation_type' => 'add'),
				array('name' => 'Color', 'value' => 'Blue', 'operation_type' => 'delete')
			)));

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_underscore'))
			->getMock();
		$feedProcessorModelMock->expects($this->at(0))
			->method('_underscore')
			->with($this->equalTo('Color'))
			->will($this->returnValue('color'));
		$feedProcessorModelMock->expects($this->at(1))
			->method('_underscore')
			->with($this->equalTo('size'))
			->will($this->returnValue('size'));
		$feedProcessorModelMock->expects($this->at(2))
			->method('_underscore')
			->with($this->equalTo('Color'))
			->will($this->returnValue('color'));

		$this->_reflectMethod($feedProcessorModelMock, '_prepareCustomAttributes')
			->invoke($feedProcessorModelMock, $dataObject, new Varien_Object());
	}
}
