<?php
class TrueAction_Eb2cProduct_Test_Helper_DataTest
	extends TrueAction_Eb2cCore_Test_Base
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

		$this->assertSame($expectedOutput, Mage::helper('eb2cproduct')->parseTranslations($sampleInput));
	}

	/**
	 * Test getDefaultLanguageCode the feed
	 * @test
	 */
	public function testGetDefaultLanguageCode()
	{
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('mageToXmlLangFrmt'))
			->getMock();
		$coreHelperMock::staticExpects($this->once())
			->method('mageToXmlLangFrmt')
			->with($this->equalTo('en_US'))
			->will($this->returnValue('en-US'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('_getLocaleCode'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('_getLocaleCode')
			->will($this->returnValue('en_US'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$this->assertSame('en-US', Mage::helper('eb2cproduct')->getDefaultLanguageCode());
	}

	/**
	 * Test getDefaultProductAttributeSetId the feed
	 * @test
	 */
	public function testGetDefaultProductAttributeSetId()
	{
		$entityTypeModelMock = $this->getModelMockBuilder('eav/entity_type')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode', 'getDefaultAttributeSetId'))
			->getMock();
		$entityTypeModelMock->expects($this->once())
			->method('loadByCode')
			->with($this->equalTo('catalog_product'))
			->will($this->returnSelf());
		$entityTypeModelMock->expects($this->once())
			->method('getDefaultAttributeSetId')
			->will($this->returnValue(4));
		$this->replaceByMock('model', 'eav/entity_type', $entityTypeModelMock);

		$this->assertSame(4, Mage::helper('eb2cproduct')->getDefaultProductAttributeSetId());
	}

	/**
	 * Test parseBool the feed
	 * @test
	 */
	public function testParseBool()
	{
		$testData = array(
			array('expect' => true, 's' => true),
			array('expect' => false, 's' => false),
			array('expect' => false, 's' => array()),
			array('expect' => true, 's' => array(range(1, 4))),
			array('expect' => true, 's' => '1'),
			array('expect' => true, 's' => 'on'),
			array('expect' => true, 's' => 't'),
			array('expect' => true, 's' => 'true'),
			array('expect' => true, 's' => 'y'),
			array('expect' => true, 's' => 'yes'),
			array('expect' => false, 's' => 'false'),
			array('expect' => false, 's' => 'off'),
			array('expect' => false, 's' => 'f'),
			array('expect' => false, 's' => 'n'),
		);
		foreach ($testData as $data) {
			$this->assertSame($data['expect'], Mage::helper('eb2cproduct')->parseBool($data['s']));
		}
	}

	/**
	 * Test getProductAttributeId the feed
	 * @test
	 */
	public function testGetProductAttributeId()
	{
		$entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
			->disableOriginalConstructor()
			->setMethods(array('loadByCode', 'getId'))
			->getMock();
		$entityAttributeModelMock->expects($this->once())
			->method('loadByCode')
			->with($this->equalTo('catalog_product'), $this->equalTo('color'))
			->will($this->returnSelf());
		$entityAttributeModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(92));
		$this->replaceByMock('model', 'eav/entity_attribute', $entityAttributeModelMock);

		$this->assertSame(92, Mage::helper('eb2cproduct')->getProductAttributeId('color'));
	}

	/**
	 * Test extractNodeVal method
	 * @test
	 */
	public function testExtractNodeVal()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('<root><Description xml:lang="en_US">desc1</Description></root>');
		$xpath = new DOMXPath($doc);
		$this->assertSame('desc1', Mage::helper('eb2cproduct')->extractNodeVal($xpath->query('Description', $doc->documentElement)));
	}

	/**
	 * Test extractNodeAttributeVal method
	 * @test
	 */
	public function testExtractNodeAttributeVal()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML('<root><Description xml:lang="en_US">desc1</Description></root>');
		$xpath = new DOMXPath($doc);
		$this->assertSame('en_US', Mage::helper('eb2cproduct')->extractNodeAttributeVal($xpath->query('Description', $doc->documentElement), 'xml:lang'));
	}

	/**
	 * Test prepareProductModel the feed
	 * @test
	 */
	public function testPrepareProductModel()
	{
		$productModelMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getId'))
			->getMock();
		$productModelMock->expects($this->once())
			->method('getId')
			->will($this->returnValue(0));

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('loadProductBySku', '_applyDummyData'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('loadProductBySku')
			->with($this->equalTo('TST-1234'))
			->will($this->returnValue($productModelMock));
		$productHelperMock->expects($this->once())
			->method('_applyDummyData')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo('TST-1234'),
				$this->equalTo('Test Product Title')
			)
			->will($this->returnValue($productModelMock));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Product',
			Mage::helper('eb2cproduct')->prepareProductModel('TST-1234', 'Test Product Title')
		);
	}

	/**
	 * Test getCustomAttributeCodeSet the feed
	 * @test
	 */
	public function testGetCustomAttributeCodeSet()
	{
		$apiModelMock = $this->getModelMockBuilder('catalog/product_attribute_api')
			->disableOriginalConstructor()
			->setMethods(array('items'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('items')
			->with($this->equalTo(172))
			->will($this->returnValue(array(
				array('code' => 'brand_name'),
				array('code' => 'brand_description'),
				array('code' => 'is_drop_shipped'),
				array('code' => 'drop_ship_supplier_name')
			)));
		$this->replaceByMock('model', 'catalog/product_attribute_api', $apiModelMock);

		$helper = Mage::helper('eb2cproduct');

		// setting _customAttributeCodeSets property back to an empty array
		$this->_reflectProperty($helper, '_customAttributeCodeSets')->setValue($helper, array());

		$this->assertSame(
			array('brand_name', 'brand_description', 'is_drop_shipped', 'drop_ship_supplier_name'),
			Mage::helper('eb2cproduct')->getCustomAttributeCodeSet(172)
		);

		$this->assertSame(
			array(172 => array('brand_name', 'brand_description', 'is_drop_shipped', 'drop_ship_supplier_name')),
			$this->_reflectProperty($helper, '_customAttributeCodeSets')->getValue($helper)
		);

		// reseting _customAttributeCodeSets property back to an empty array
		$this->_reflectProperty($helper, '_customAttributeCodeSets')->setValue($helper, array());
	}
	/**
	 * Data provider for testGetConfigAttributesData
	 * @return array Arrays for use as args to the testGetConfigAttributesData test
	 */
	public function providerTestGetConfigAttributesData()
	{
		return array(
			// should return source data as this would be a brand new product - no id
			array(null, null, 'do-not-care', array(), array('source_data'), array('source_data')),
			// should return source data as this is a simple product and could not have existing config attr data
			array(42, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, 'do-not-care', array(), array('source_data'), array('source_data')),
			// should retrn source data as this won't end up being a config product...seems a bit odd but I guess it just means any existing data doesn't matter
			array(42, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, array(), array('source_data'), array('source_data')),
			// should return source data as config product doesn't alreay have config attr data set
			array(42, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, array(), array('source_data'), array('source_data')),
			// should return null as existing config product has already had config attr data set and it cannot/shoudl not be overridden once set
			array(42, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, array('existing_data'), array('source_data'), null),
		);
	}
	/**
	 * Test getting the configurable attributes to add to a product. When the product is an existing product
	 * (has an id, type id), is a configurable or is supposed to be a configurable and already has
	 * configurable attributes data, it should return null. Otherwise, it should return
	 * whatever configurable attributes data is contained in the source data.
	 * @param  int     $existingId          Id of existing product, null if expected to be a new product
	 * @param  string  $existingType        Product type of the existing product
	 * @param  string  $newType             Product type the product is expected to be post import
	 * @param  array   $existingAttributes  Array of configurable attribute data the product already has had applied
	 * @param  array   $sourceAttributes,   Data from the feed that should be applied to the product, may contain configurable_attributes_data to add
	 * @param  array   $expectedAttributes  Attributes that are expected to be returned from the method
	 * @mock Mage_Catalog_Model_Product::getId return expected product id
	 * @mock Mage_Catalog_Model_Product::getTypeId return expected product type id
	 * @mock Mage_Catalog_Model_Product::getTypeInstance return the stub product type model
	 * @mock Mage_Catalog_Model_Product_Type_Abstract::getConfigurableAttributesAsArray return expected existing attributes
	 * @test
	 * @dataProvider providerTestGetConfigAttributesData
	 */
	public function testGetConfigAttributesData($existingId, $existingType, $newType, $existingAttributes, $sourceAttributes, $expectedAttributes)
	{
		$prod = $this->getModelMock('catalog/product', array('getId', 'getTypeId', 'getTypeInstance'));
		$prodType = $this->getModelMock('catalog/product/type/abstract', array('getConfigurableAttributesAsArray'));
		$source = new Varien_Object(array('configurable_attributes_data' => $sourceAttributes));

		$prod
			->expects($this->any())
			->method('getId')
			->will($this->returnValue($existingId));
		$prod
			->expects($this->any())
			->method('getTypeId')
			->will($this->returnValue($existingType));
		$prod
			->expects($this->any())
			->method('getTypeInstance')
			->with($this->isTrue())
			->will($this->returnValue($prodType));
		$prodType
			->expects($this->any())
			->method('getConfigurableAttributesAsArray')
			->with($this->identicalTo($prod))
			->will($this->returnValue($existingAttributes));

		$this->assertSame(
			$expectedAttributes,
			Mage::helper('eb2cproduct')->getConfigurableAttributesData($newType, $source, $prod)
		);
	}
}
