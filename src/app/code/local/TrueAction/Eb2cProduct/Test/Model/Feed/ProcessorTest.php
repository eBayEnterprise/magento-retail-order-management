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
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
		$coreStoreModelMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('getCode', 'getId'))
			->getMock();
		$coreStoreModelMock->expects($this->at(0))
			->method('getCode')
			->will($this->returnValue('en_US'));
		$coreStoreModelMock->expects($this->at(1))
			->method('getCode')
			->will($this->returnValue('fr_FR'));
		$coreStoreModelMock->expects($this->at(2))
			->method('getCode')
			->will($this->returnValue('es_SP'));

		$this->replaceByMock('model', 'eb2ccore/Store', $coreStoreModelMock);

		$feedProcessorModelMock = $this->getModelMockBuilder('eb2cproduct/feed_processor')
			->disableOriginalConstructor()
			->setMethods(array('_getAllStores', '_getStoreById'))
			->getMock();
		$feedProcessorModelMock->expects($this->once())
			->method('_getAllStores')
			->will($this->returnValue(range(1, 3)));
		$feedProcessorModelMock->expects($this->at(2))
			->method('_getStoreById')
			->with($this->equalTo(1))
			->will($this->returnValue($coreStoreModelMock));
		$feedProcessorModelMock->expects($this->at(3))
			->method('_getStoreById')
			->with($this->equalTo(2))
			->will($this->returnValue($coreStoreModelMock));
		$feedProcessorModelMock->expects($this->at(4))
			->method('_getStoreById')
			->with($this->equalTo(3))
			->will($this->returnValue($coreStoreModelMock));

		$this->assertInstanceOf(
			'TrueAction_Eb2cProduct_Model_Feed_Processor',
			$this->_reflectMethod($feedProcessorModelMock, '_initStoreLanguageCodeMap')
				->invoke($feedProcessorModelMock)
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
		$dataObj = new Varien_Object($this->getLocalFixture($scenario));
		$testModel->processUpdates(array($dataObj));
	}

	/**
	 * @loadFixture
	 */
	public function testStockItemData()
	{
		$this->markTestSkipped('Too slow. Make processUpdates faster.');
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$dataObj = new Varien_Object($this->getLocalFixture('itemmaster-exists'));
		// confirm preconditions
		$product = Mage::helper('eb2cproduct')->loadProductBySku('book');
		$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
		$this->assertSame('0', $stock->getData('backorders'));
		$this->assertSame('1', $stock->getData('use_config_backorders'));
		$this->assertSame('100.0000', $stock->getData('qty'));
		// run test
		$testModel->processUpdates(array($dataObj));
		// verify results
		$product = Mage::helper('eb2cproduct')->loadProductBySku('book');
		$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
		$this->assertSame('1', $stock->getData('backorders'));
		$this->assertSame('0', $stock->getData('use_config_backorders'));
		$this->assertSame('100.0000', $stock->getData('qty'));
	}

	/**
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testConfigurableData($scenario)
	{
		$this->markTestSkipped('Too slow. Make processUpdates faster. (Also, only test it once.)');
		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$extractedUnits = $this->getLocalFixture($scenario);
		$processList = array();
		foreach ($extractedUnits as $extractedUnit) {
			$processList[] = new Varien_Object($extractedUnit);
		}
		$dataObj = new Varien_Object();
		// confirm preconditions
		// assert theparent exists
		// assert theotherparent doesnt exist
		// assert 45-000906014545 doesnt exist
		$product = Mage::helper('eb2cproduct')->loadProductBySku('45-000906014545');
		$testModel->processUpdates($processList);
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
	 * Given a mapped array containing language, parse should return
	 * a flattened array, keyed by language
	 * @test
	 */
	public function testParseTranslations()
	{
		$sampleInput = array (
			array (
				'lang'        => 'en-US',
				'description' => 'An en-US translation',
			),
			array (
				'lang'        => 'ja-JP',
				'description' => 'ja-JP に変換',
			),
		);

		$expectedOutput = array (
			'en-US' => 'An en-US translation',
			'ja-JP' => 'ja-JP に変換',
		);

		$testModel = Mage::getModel('eb2cproduct/feed_processor');
		$fn = $this->_reflectMethod($testModel, '_parseTranslations');
		$this->assertSame( $expectedOutput, $fn->invoke($testModel, $sampleInput));
	}
}
