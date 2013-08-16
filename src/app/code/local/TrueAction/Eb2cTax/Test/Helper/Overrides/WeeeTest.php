<?php
/**
 */
class TrueAction_Eb2cTax_Test_Helper_Overrides_WeeeTest extends EcomDev_PHPUnit_Test_Case
{

	public function setUp()
	{
		parent::setUp();
		Mage::unregister('_helper/weee');	// make sure there's a fresh instance of the tax helper for each test
	}

	/**
	 * The only thing this override should do is make sure that weee helper returns false for isEnabled
	 * 
	 * @test
	 */
	public function testNotEnabled()
	{
		$this->assertFalse(Mage::helper('weee')->isEnabled(), "Weee Helper Override is not working properly.");
	}
}

