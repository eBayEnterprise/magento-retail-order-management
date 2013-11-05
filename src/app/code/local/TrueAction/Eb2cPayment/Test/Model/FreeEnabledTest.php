<?php
class TrueAction_Eb2cPayment_Test_Model_FreeEnabledTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
 	 * @test
	 *
	 * @loadFixture freePaymentEnabled.yaml
	 */
	function testFreePaymentEnabled()
	{
		$this->assertEquals(
			1,
			Mage::getStoreConfig(Mage_Payment_Model_Method_Free::XML_PATH_PAYMENT_FREE_ACTIVE),
			'The free payment should have been ENABLED, but was not.'
		);
	}

	/**
 	 * @test
	 *
	 * @loadFixture freePaymentDisabled.yaml
	 */
	function testFreePaymentDisabled()
	{
		$this->assertEquals(
			0,
			Mage::getStoreConfig(Mage_Payment_Model_Method_Free::XML_PATH_PAYMENT_FREE_ACTIVE),
			'The free payment should have been DISABLED, but was not.'
		);
	}
}
