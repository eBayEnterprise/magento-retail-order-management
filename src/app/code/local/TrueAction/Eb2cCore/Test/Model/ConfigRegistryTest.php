<?php
/**
 * Test the helper/config class. Should ensure that:
 * - Looking up a config value through the helper returns
 *   the same results as looking it up through the
 *   Mage::getStoreConfig or Mage::getStoreConfigFlag methods.
 * - The appropriate store view is used when looking up config values
 * - Multiple config classes can be used to look up paths
 * - When using multiple config models, the last one in takes precedence
 */
class TrueAction_Eb2cCore_Test_Model_ConfigRegistryTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Create a stub config model to populate the config helper with keys/paths.
	 */
	protected function _createConfigStub()
	{
		$stub = $this->getMock('TrueAction_Eb2cCore_Model_Config_Abstract');

		$keyMap = array(
			array('catalog_id', true),
			array('another_setting', false),
			array('api_key', true),
			array('test_mode', true),
		);
		$stub->expects($this->any())
			->method('hasKey')
			->will($this->returnValueMap($keyMap));

		$pathMap = array(
			array('catalog_id', 'eb2c/core/catalog_id'),
			array('api_key', 'eb2c/core/api_key'),
			array('test_mode', 'eb2c/core/test_mode'),
		);
		$stub->expects($this->any())
			->method('getPathForKey')
			->will($this->returnValueMap($pathMap));

		return $stub;
	}

	/**
	 * Create another stub config model to populate the config helper
	 * with additional config keys/paths.
	 */
	protected function _createAltConfigStub()
	{
		$stub = $this->getMock('TrueAction_Eb2cCore_Model_Config_Abstract');

		$keyMap = array(
			array('catalog_id', true),
			array('another_setting', true),
			array('api_key', false),
			array('test_mode', false),
		);
		$stub->expects($this->any())
			->method('hasKey')
			->will($this->returnValueMap($keyMap));

		$pathMap = array(
			array('catalog_id', 'eb2c/another/catalog_id'),
			array('another_setting', 'eb2c/another/module/setting'),
		);
		$stub->expects($this->any())
			->method('getPathForKey')
			->will($this->returnValueMap($pathMap));

		return $stub;
	}

	/**
	 * Ensure that the config values returned by the config helper match up to
	 * the values that would have been returned by simply using Magento's
	 * Mage::getStoreConfig and Mage::getStoreConfigFlag methods.
	 *
	 * @test
	 * @loadFixture configData
	 */
	public function testGetConfig()
	{
		$config = Mage::getModel('eb2ccore/config_registry');
		$config->addConfigModel($this->_createConfigStub());

		// ensure a value is returned
		$this->assertNotNull($config->getConfig('catalog_id'));

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->getConfig('catalog_id'), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// when explicitly passing a storeId, should return value for that store
		$this->assertSame($config->getConfig('api_key', 2), Mage::getStoreConfig('eb2c/core/api_key', 2));

		// when store id is set on config object, will use that value for the store id
		$config->setStore(2);
		$this->assertSame($config->getConfig('catalog_id'), Mage::getStoreConfig('eb2c/core/catalog_id', 2));

		// can still use an explicit store id which should override the one set on the store
		$this->assertSame($config->getConfig('api_key', 3), Mage::getStoreConfig('eb2c/core/api_key', 3));

		// can even explicitly set store id to null
		$this->assertSame($config->getConfig('catalog_id', null), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// indicate the config is a "flag" to explicitly return a boolean value
		$this->assertSame($config->getConfigFlag('test_mode', 1), Mage::getStoreConfigFlag('eb2c/core/test_mode', 1));
	}

	/**
	 * Run through a similar test as $this::testGetConfig but this time run them
	 * all through the overloaded __get method via "magic" properties.
	 *
	 * @test
	 * @loadFixture configData
	 */
	public function testMagicPropConfig()
	{
		$config = Mage::getModel('eb2ccore/config_registry');
		$config->addConfigModel($this->_createConfigStub())
			->addConfigModel($this->_createAltConfigStub());

		// should get some config value for both of these
		// this will come from the first Config_Stub
		$this->assertNotNull($config->apiKey);
		// this will come from the second Alt_Config_Stub
		$this->assertNotNull($config->anotherSetting);

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->apiKey, Mage::getStoreConfig('eb2c/core/api_key'));

		// should be able to get config added by either settings model
		$this->assertSame($config->anotherSetting, Mage::getStoreConfig('eb2c/another/module/setting'));

		// keys can collide...last added are used
		// this path is in the first config model added and will not be used
		$this->assertNotSame($config->catalogId, Mage::getStoreConfig('eb2c/core/catalog_id'));
		// this is in the second config model and will be used
		$this->assertSame($config->catalogId, Mage::getStoreConfig('eb2c/another/catalog_id'));
	}

	/**
	 * If getConfig is called and the key is not found, an exception should be raised.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testConfigNotFoundExceptions()
	{
		Mage::getModel('eb2ccore/config_registry')->getConfig('nonexistent_config');
	}

	/**
	 * Same as $this::testConfigNotFoundException except this time via
	 * the overloaded __get method via "magic" properties.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testUnknownPropError()
	{
		Mage::getModel('eb2ccore/config_registry')->nonexistentConfig;
	}
	/**
	 * Getting a nonexistent property should error but still return null.
	 * @test
	 */
	public function testUnknownProp()
	{
		// ensure code execution continues after the error is triggered
		$handler = set_error_handler(function(){});
		$config = Mage::getModel('eb2ccore/config_registry');
		$nonexistent = $config->nonexistentConfig;
		$this->assertNull($nonexistent);
		// set error handler back to initial value
		set_error_handler($handler);
	}

	/**
	 * All properties on the config helper should be readonly.
	 * Attempting to set a property on the object should trigger an error.
	 * @test
	 * @expectedException Exception
	 */
	public function testAllPropsReadonlyError()
	{
		Mage::getModel('eb2ccore/config_registry')->someConfig = 'foo';
	}

	/**
	 * Sidestep the error and ensure that values are not getting set.
	 * @test
	 * @loadFixture configData
	 */
	public function testAllPropsReadonly()
	{
		$handler = set_error_handler(function(){});
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel($this->_createConfigStub());
		$config->catalogId = 'foo';
		$this->assertSame(Mage::getStoreConfig('eb2c/core/catalog_id'), $config->catalogId);
		set_error_handler($handler);
	}

}
