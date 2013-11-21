<?php
class TrueAction_Eb2cPayment_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 */
	public function testGetXmlNs()
	{
		$hlpr = Mage::helper('eb2cpayment');
		$this->assertSame('http://api.gsicommerce.com/schema/checkout/1.0', $hlpr->getXmlNs());
		$this->assertSame('http://api.gsicommerce.com/schema/payment/1.0', $hlpr->getPaymentXmlNs());
	}
	/**
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$hlpr = Mage::helper('eb2cpayment');
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml', $hlpr->getOperationUri('get_gift_card_balance'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml', $hlpr->getOperationUri('get_gift_card_redeem'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml', $hlpr->getOperationUri('get_gift_card_redeem_void'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/paypal/doAuth.xml', $hlpr->getOperationUri('get_paypal_do_authorization'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/paypal/doExpress.xml', $hlpr->getOperationUri('get_paypal_do_express_checkout'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/paypal/void.xml', $hlpr->getOperationUri('get_paypal_do_void'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/paypal/getExpress.xml', $hlpr->getOperationUri('get_paypal_get_express_checkout'));
		$this->assertSame('https://api.example.com/vM.m/stores/storeId/payments/paypal/setExpress.xml', $hlpr->getOperationUri('get_paypal_set_express_checkout'));
	}
	/**
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider dataProvider
	 */
	public function testGetRequestId($incrementId)
	{
		$this->assertSame('clientId-storeId-100000060', Mage::helper('eb2cpayment')->getRequestId($incrementId));
	}
	/**
	 * Test that we return the correct SVC url for the given PAN
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider dataProvider
	 */
	public function testGetSvcUri($partOptIndex, $pan, $tenderType)
	{
		$hlpr = Mage::helper('eb2cpayment');
		$optIndex = 'get_gift_card_' . $partOptIndex;
		$exp = sprintf('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/%s/%s.xml', str_replace('_', '', $partOptIndex), $tenderType);
		$this->assertSame($exp, $hlpr->getSvcUri($optIndex, $pan));
		// Expect the empty string when the $pan is out of range.
		$this->assertSame('', $hlpr->getSvcUri($optIndex, '65'));
	}
}
