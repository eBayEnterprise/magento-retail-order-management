<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_AttributesTest extends TrueAction_Eb2cCore_Test_Base
{
	public static $modelClass = 'TrueAction_Eb2cProduct_Model_Attributes';

	/**
	 * ensure the _getOrCreateFunction returns a model we expect.
	 */
	public function testGetOrCreateAttribute()
	{
		$code = 'tax_code';
		$model = Mage::getModel('eb2cproduct/attributes');
		$getOrCreateAttribute = $this->_reflectMethod($model, '_getOrCreateAttribute');
		$attrModel = $getOrCreateAttribute->invoke($model, $code);
		$this->assertInstanceOf(self::$modelClass, $attrModel);
	}

	public function testLoadDefaultAttributesConfig()
	{
		$configMock = $this->getModelMock('core/config', array('loadModulesConfiguration'));
		$configMock->expects($this->any())
			->method('loadModulesConfiguration')
			->with($this->identicalTo('eb2cproduct_attributes.xml'))
			->will($this->returnSelf());
		$configMock->loadString(self::$configXml);
		$this->replaceByMock('singleton', 'core/config', $configMock);
		$model = Mage::getModel('eb2cproduct/attributes');
		$config = $this->_reflectMethod($model, '_loadDefaultAttributesConfig')->invoke($model);
		$this->assertInstanceOf('Mage_Core_Model_Config', $config);
		$this->assertSame($configMock, $config);
	}

	public function testGetBaseConfig()
	{
		$configMock = $this->getModelMock('core/config', array('loadModulesConfiguration'));
		$configMock->expects($this->any())
			->method('loadModulesConfiguration')
			->with($this->identicalTo('eb2cproduct_attributes.xml'))
			->will($this->returnSelf());
		$configMock->loadString(self::$configXml);
		$this->replaceByMock('singleton', 'core/config', $configMock);
		$model = Mage::getModel('eb2cproduct/attributes');
		$config = $this->_reflectMethod($model, '_getBaseConfig')->invoke($model);
		$this->assertInstanceOf('Mage_Core_Model_Config', $config);
		$this->assertSame($configMock, $config);
	}

	/**
	 * @loadExpectation testGetDefaultAttributesCodeList.yaml
	 */
	public function testGetDefaultAttributesCodeList()
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$fn     = $this->_reflectMethod($model, 'getDefaultAttributesCodeList');
		$result	= $fn->invoke($model);
		$e      = $this->expected('default');
		$this->assertSame($e->getData(), $result);
	}

	/**
	 * @loadExpectation testGetDefaultAttributesCodeList.yaml
	 */
	public function testGetDefaultAttributesCodeListFilterByGroup()
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$fn     = $this->_reflectMethod($model, 'getDefaultAttributesCodeList');
		$result	= $fn->invoke($model, 'Prices');
		$e      = $this->expected('prices');
		$this->assertSame($e->getData(), $result);
	}

	public function testInstallDefaultAttributes()
	{
	}

	protected function _getSetupMock()
	{
		$model = $this->getResourceModelMockBuilder('eb2cproduct/resource_setup')
			->disableOriginalConstructor()
			->getMock();
	}

	public static $configXml  = '';
}
