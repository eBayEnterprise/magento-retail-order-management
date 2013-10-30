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
				'setStore', 'addConfigModel', 'getStore', '_getStoreConfigValue', 'getConfigFlag', 'getConfig', '_magicNameToConfigKey',
				'__get', '__set'
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
	 * Test normalizing a product style id to match formatting for skus
	 * @param  string $style   The product style id
	 * @param  string $catalog The product catalog id
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testNormalizeStyleId($styleId, $catalogId)
	{
		$normalized = Mage::helper('eb2cproduct')->normalizeStyleId($styleId, $catalogId);
		$this->assertSame($this->expected('%s-%s', $styleId, $catalogId)->getStyleId(), $normalized);
	}

}
