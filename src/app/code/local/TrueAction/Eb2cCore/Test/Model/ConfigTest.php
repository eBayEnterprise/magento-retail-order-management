<?php

/**
 * Test the abstract config model which does the majority of work implementing
 * the config model interface required by the TrueAction_Eb2cCore_Helper_Config
 */
class TrueAction_Eb2cCore_Test_Model_ConfigTests extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * Test determining if a config model knows about a key
	 * @test
	 */
	public function testConfigModelHasKey()
	{
		$configModel = new ConfigStub();
		$this->assertTrue($configModel->hasKey('catalog_id'));
		$this->assertFalse($configModel->hasKey('foo_bar_baz'));
	}

	/**
	 * Test getting path for a known key
	 * @test
	 */
	public function testConfigModelGetPath()
	{
		$configModel = new ConfigStub();
		$this->assertSame($configModel->getPathForKey('catalog_id'), 'eb2c/core/catalog_id');
	}

}

/**
 * Simple implementation of the config abstract model.
 * Used to test the concrete implementations in the abstract class.
 *
 * @codeCoverageIgnore
 */
class ConfigStub extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array('catalog_id' => 'eb2c/core/catalog_id');
}
