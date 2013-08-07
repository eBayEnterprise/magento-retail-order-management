<?php
/**
 *
 *
 */
class TrueAction_Eb2cFraud_Test_Model_ConfigTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 */
	public function testFactoryMethod()
	{
		// Good enough to know we can get one; it's used by core config getters.
		$testFactoryModel = Mage::getModel('eb2cfraud/config');
		$this->assertInstanceOf('TrueAction_Eb2cFraud_Model_Config', $testFactoryModel);
	}
}
