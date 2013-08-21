<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cProduct_Test_Mock_Model_Eav_Config extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * return a mock of the Mage_Eav_Model_Config class
	 *
	 * @return Mock_Mage_Eav_Model_Config
	 */
	public function buildEavModelConfig()
	{
		$eavModelConfigMock = $this->getMock(
			'Mage_Eav_Model_Config',
			array('getAttribute', 'getId')
		);

		$eavModelConfigMock->expects($this->any())
			->method('getAttribute')
			->will($this->returnSelf());
		$eavModelConfigMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		return $eavModelConfigMock;
	}
}
