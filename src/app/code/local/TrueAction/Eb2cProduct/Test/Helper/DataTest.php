<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		// FYI: instantiating using regular Mage::getHelper method create
		// a singleton oject which mess with load fixtures for the config
		$this->_helper = new TrueAction_Eb2cProduct_Helper_Data();
	}

	/**
	 * testing getConfigModel method
	 *
	 * @test
	 */
	public function testGetConfigModel()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Config_Registry',
			$this->_helper->getConfigModel()
		);
	}
}
