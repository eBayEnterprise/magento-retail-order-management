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

	public function providerHasEavAttr()
	{
		$eavConfigModelMock = $this->getModelMockBuilder('eav/config')
			->disableOriginalConstructor()
			->setMethods(array('getAttribute', 'getId'))
			->getMock();
		$eavConfigModelMock->expects($this->any())
			->method('getAttribute')
			->will($this->returnSelf());
		$eavConfigModelMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$contenMasterModelMock = $this->getModelMockBuilder('eb2cproduct/feed_content_master')
			->disableOriginalConstructor()
			->setMethods(array('getEavConfig'))
			->getMock();

		$contenMasterModelMock->expects($this->any())
			->method('getEavConfig')
			->will($this->returnValue($eavConfigModelMock));

		return array(
			array($contenMasterModelMock, 'color'),
		);
	}

	/**
	 * testing hasEavAttr method
	 *
	 * @param TrueAction_Eb2cCore_Model_Feed_Interface $feed
	 * @param string $attr
	 *
	 * @test
	 * @dataProvider providerHasEavAttr
	 */
	public function testHasEavAttr(TrueAction_Eb2cCore_Model_Feed_Interface $feed, $attr)
	{
		$this->assertSame(true, Mage::helper('eb2cproduct')->hasEavAttr($feed, $attr));
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
}
