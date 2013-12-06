<?php
class TrueAction_Eb2cProduct_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing getConfigModel method
	 * @test
	 */
	public function testGetConfigModel()
	{
		$configRegistryModelMock = $this->getModelMockBuilder('eb2ccore/config_registry')
			->disableOriginalConstructor()
			->setMethods(array(
				'__get',
				'__set',
				'_getStoreConfigValue',
				'_magicNameToConfigKey',
				'addConfigModel',
				'getConfig',
				'getConfigFlag',
				'getStore',
				'setStore',
			))
			->getMock();

		$configRegistryModelMock->expects($this->any())
			->method('setStore')
			->will($this->returnSelf());
		$configRegistryModelMock->expects($this->any())
			->method('addConfigModel')
			->will($this->returnSelf());
		$configRegistryModelMock->expects($this->any())
			->method('getStore')
			->will($this->returnValue(1));
		$configRegistryModelMock->expects($this->any())
			->method('_getStoreConfigValue')
			->will($this->returnValue(null));
		$configRegistryModelMock->expects($this->any())
			->method('getConfigFlag')
			->will($this->returnValue(1));
		$configRegistryModelMock->expects($this->any())
			->method('getConfig')
			->will($this->returnValue(null));
		$configRegistryModelMock->expects($this->any())
			->method('_magicNameToConfigKey')
			->will($this->returnValue(null));
		$configRegistryModelMock->expects($this->any())
			->method('__get')
			->will($this->returnValue(null));
		$configRegistryModelMock->expects($this->any())
			->method('__set')
			->will($this->returnValue(null));

		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryModelMock);

		$productConfigModelMock = $this->getModelMockBuilder('eb2cproduct/config')
			->disableOriginalConstructor()
			->setMethods(array('hasKey', 'getPathForKey'))
			->getMock();

		$productConfigModelMock->expects($this->any())
			->method('hasKey')
			->will($this->returnValue(null));
		$productConfigModelMock->expects($this->any())
			->method('getPathForKey')
			->will($this->returnValue(null));

		$this->replaceByMock('model', 'eb2cproduct/config', $productConfigModelMock);

		$coreConfigModelMock = $this->getModelMockBuilder('eb2ccore/config')
			->disableOriginalConstructor()
			->setMethods(array('hasKey', 'getPathForKey'))
			->getMock();

		$coreConfigModelMock->expects($this->any())
			->method('hasKey')
			->will($this->returnValue(null));
		$coreConfigModelMock->expects($this->any())
			->method('getPathForKey')
			->will($this->returnValue(null));

		$this->replaceByMock('model', 'eb2ccore/config', $coreConfigModelMock);

		$productHelper = Mage::helper('eb2cproduct');
		$this->assertInstanceOf('TrueAction_Eb2cCore_Model_Config_Registry', $productHelper->getConfigModel());
	}

	public function providerHasEavAttr()
	{
		return array(
			array('known-attr'),
			array('alien-attr'),
		);
	}

	/**
	 * Test that a product attribute is known if it has an id > 0.
	 * @param string $name The attribute name
	 * @test
	 * @dataProvider providerHasEavAttr
	 */
	public function testHasEavAttr($name)
	{
		$atId = $this->expected($name)->getId();
		$att = $this->getModelMock('eav/attribute', array('getId'));
		$att->expects($this->once())
			->method('getId')
			->will($this->returnValue($atId));
		$this->replaceByMock('model', 'eav/attribute', $att);

		$eav = $this->getModelMock('eav/config', array('getAttribute'));
		$eav->expects($this->once())
			->method('getAttribute')
			->with($this->equalTo(Mage_Catalog_Model_Product::ENTITY), $this->equalTo($name))
			->will($this->returnValue($att));
		$this->replaceByMock('model', 'eav/config', $eav);

		// If $atId > 0, the result should be true
		$this->assertSame($atId > 0, Mage::helper('eb2cproduct')->hasEavAttr($name));
	}

	/**
	 * Test that a known product type is validated and an unknown is rejected.
	 */
	public function testHasProdType()
	{
		$this->assertSame(false, Mage::helper('eb2cproduct')->hasProdType('alien'));
		// Normally I would inject a known value into Mage_Catalog_Model_Product_Type::getTypes()
		// so that this test is a true "unit" test and doesn't depend on the environment
		// at all, but getTypes is static, and you can bet there's gonna be a "simple"
		// type in every environment.
		$this->assertSame(true, Mage::helper('eb2cproduct')->hasProdType('simple'));
	}

	/**
	 * Should throw an exception when creating a dummy product template
	 * if the configuration specifies an invalid Magento product type
	 * This test will use hasProdType() so has a real, if marginal, environmental
	 * dependence.
	 *
	 * @expectedException TrueAction_Eb2cProduct_Model_Config_Exception
	 */
	public function testInvalidDummyTypeFails()
	{
		$fakeCfg = new StdClass();
		$fakeCfg->dummyTypeId = 'someWackyTypeThatWeHopeDoesntExist';
		$hlpr = $this->getHelperMock('eb2cproduct/data', array(
			'getConfigModel',
		));
		$hlpr->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($fakeCfg));

		$hlpRef = new ReflectionObject(Mage::helper('eb2cproduct'));
		$getProdTplt = $hlpRef->getMethod('_getProdTplt');
		$getProdTplt->setAccessible(true);
		$getProdTplt->invoke($hlpr);
	}


	/**
	 * Test looking up a product by sku
	 * @param  string $sku SKU of product
	 * @test
	 * @loadFixture
	 * @dataProvider dataProvider
	 */
	public function testGetProductBySku($sku)
	{
		$helper = Mage::helper('eb2cproduct');
		$product = $helper->loadProductBySku($sku);
		$expected = $this->expected($sku);
		$this->assertInstanceOf('Mage_Catalog_Model_Product', $product, 'Method should always return a product instance.');
		$this->assertSame($expected->getId(), $product->getId());
	}
	/**
	 * Test the various dummy defaults.
	 * @test
	 */
	public function testGetDefaults()
	{
		$hlpr = Mage::helper('eb2cproduct');
		$hlpRef = new ReflectionObject($hlpr);
		$getAllWebsiteIds = $hlpRef->getMethod('_getAllWebsiteIds');
		$getDefProdAttSetId = $hlpRef->getMethod('_getDefProdAttSetId');
		$getDefStoreId = $hlpRef->getMethod('_getDefStoreId');
		$getDefStoreRootCatId = $hlpRef->getMethod('_getDefStoreRootCatId');
		$getAllWebsiteIds->setAccessible(true);
		$getDefProdAttSetId->setAccessible(true);
		$getDefStoreId->setAccessible(true);
		$getDefStoreRootCatId->setAccessible(true);
		$this->assertInternalType('array', $getAllWebsiteIds->invoke($hlpr));
		$this->assertInternalType('integer', $getDefProdAttSetId->invoke($hlpr));
		$this->assertInternalType('integer', $getDefStoreId->invoke($hlpr));
		$this->assertInternalType('integer', $getDefStoreRootCatId->invoke($hlpr));
	}

	public function testBuildDummyBoilerplate()
	{
		$fakeCfg = new StdClass();
		$fakeCfg->dummyInStockFlag = true;
		$fakeCfg->dummyManageStockFlag = true;
		$fakeCfg->dummyStockQuantity = 123;
		$fakeCfg->dummyDescription = 'hello world';
		$fakeCfg->dummyPrice = 45.67;
		$fakeCfg->dummyShortDescription = 'hello';
		$fakeCfg->dummyTaxClassId = 890;
		$fakeCfg->dummyTypeId = 'simple';
		$fakeCfg->dummyWeight = 79;
		$hlpr = $this->getHelperMock('eb2cproduct/data', array(
			'_getAllWebsiteIds',
			'_getDefProdAttSetId',
			'_getDefStoreId',
			'_getDefStoreRootCatId',
			'getConfigModel',
		));
		$hlpr->expects($this->once())
			->method('_getAllWebsiteIds')
			->will($this->returnValue(array(980)));
		$hlpr->expects($this->once())
			->method('_getDefProdAttSetId')
			->will($this->returnValue(132));
		$hlpr->expects($this->once())
			->method('_getDefStoreId')
			->will($this->returnValue(531));
		$hlpr->expects($this->once())
			->method('_getDefStoreRootCatId')
			->will($this->returnValue(771));
		$hlpr->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($fakeCfg));
		$expected = array(
			'attribute_set_id'  => 132,
			'category_ids'      => array(771),
			'description'       => 'hello world',
			'price'             => 45.67,
			'short_description' => 'hello',
			'status'            => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
			'stock_data'        => array(
				'is_in_stock'  => true,
				'manage_stock' => true,
				'qty'          => 123,
			),
			'store_ids'         => array(531),
			'tax_class_id'      => 890,
			'type_id'           => 'simple',
			'visibility'        => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
			'website_ids'       => array(980),
			'weight'            => 79,
		);
		$hlpRef = new ReflectionObject(Mage::helper('eb2cproduct'));
		$getProdTplt = $hlpRef->getMethod('_getProdTplt');
		$getProdTplt->setAccessible(true);
		$this->assertSame($expected, $getProdTplt->invoke($hlpr));
	}
	/**
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testApplyDummyData($sku, $name=null)
	{
		$hlpr = $this->getHelperMock('eb2cproduct/data', array('_getProdTplt'));
		$hlpr->expects($this->once())
			->method('_getProdTplt')
			->will($this->returnValue(array()));
		$hlpRef = new ReflectionObject(Mage::helper('eb2cproduct'));
		$applyDummyDataMethod = $hlpRef->getMethod('_applyDummyData');
		$applyDummyDataMethod->setAccessible(true);

		$prod = $applyDummyDataMethod->invoke($hlpr, Mage::getModel('catalog/product'), $sku, $name);
		$this->assertSame($sku, $prod->getSku());
		$this->assertSame($name ?: "Invalid Product: $sku", $prod->getName());
		$this->assertSame($sku, $prod->getUrlKey());
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

		$this->assertSame($expectedOutput,
			Mage::helper('eb2cproduct')->parseTranslations($sampleInput));
	}

	/**
	 * verify the collection filters are prepared as expected
	 */
	public function testLoadProductBySku()
	{
		$sku = 'thesku';
		$product = new Varien_Object();
		$collection = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array(
				'addAttributeToSelect',
				'addFieldToFilter',
				'getFirstItem',
			))
			->getMock();
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);
		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('*'))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addFieldToFilter')
			->with(
				$this->identicalTo('sku'),
				$this->identicalTo(array('eq' => $sku))
			)
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('getFirstItem')
			->will($this->returnValue($product));

		$testModel = Mage::helper('eb2product');
		$this->assertSame(
			$product,
			$testModel->loadProductBySku($sku)
		);
	}
}
