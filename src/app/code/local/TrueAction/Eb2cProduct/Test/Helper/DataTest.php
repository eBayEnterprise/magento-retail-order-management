<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * testing getConfigModel method
	 *
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

	/**
	 * testing clean method
	 *
	 * @test
	 */
	public function testClean()
	{
		$stockStatusModelMock = $this->getModelMockBuilder('cataloginventory/stock_status')
			->disableOriginalConstructor()
			->setMethods(array('rebuild'))
			->getMock();

		$stockStatusModelMock->expects($this->any())
			->method('rebuild')
			->will($this->returnSelf());

		$this->replaceByMock('model', 'cataloginventory/stock_status', $stockStatusModelMock);

		$this->assertInstanceOf('TrueAction_Eb2cProduct_Helper_Data', Mage::helper('eb2cproduct')->clean());
	}

	/**
	 * testing clean method - when rebuild method throw an exception
	 *
	 * @test
	 */
	public function testCleanWithExceptionThrowCaught()
	{
		$stockStatusModelMock = $this->getModelMockBuilder('cataloginventory/stock_status')
			->disableOriginalConstructor()
			->setMethods(array('rebuild'))
			->getMock();

		$stockStatusModelMock->expects($this->any())
			->method('rebuild')
			->will($this->throwException(new Mage_Core_Exception));

		$this->replaceByMock('model', 'cataloginventory/stock_status', $stockStatusModelMock);

		$this->assertInstanceOf('TrueAction_Eb2cProduct_Helper_Data', Mage::helper('eb2cproduct')->clean());
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
}
