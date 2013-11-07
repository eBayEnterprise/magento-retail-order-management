<?php
class TrueAction_Eb2cPayment_Test_Model_Stored_Value_RedeemTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test that getRedeem sets the right URL, returns the response xml or empty string if there's a Zend_Http_Client_Exception
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeem($pan, $pin, $tenderType)
	{
		$reqXmlFrmt = '<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="1"><PaymentContext><OrderId>1</OrderId><PaymentAccountUniqueId isToken="false">%s</PaymentAccountUniqueId></PaymentContext><Pin>%s</Pin><Amount currencyCode="USD">50</Amount></StoredValueRedeemRequest>';
		$resXml = '<StoredValueRedeemReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><PaymentContext><OrderId>1</OrderId><PaymentAccountUniqueId isToken="false">1</PaymentAccountUniqueId></PaymentContext><ResponseCode>Success</ResponseCode><AmountRedeemed currencyCode="USD">1.00</AmountRedeemed><BalanceAmount currencyCode="USD">1.00</BalanceAmount></StoredValueRedeemReply>';
		$reqXml = sprintf($reqXmlFrmt, $pan, $pin);
		$apiUrl = sprintf('https://apiEnv-apiRgn.gsipartners.com/vM.m/stores/storeId/payments/storedvalue/redeem/%s.xml', $tenderType);
		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->any())
			->method('setUri')
			->with($this->identicalTo($apiUrl))
			->will($this->returnSelf());
		$api->expects($this->any())
			->method('request')
			->will($this->returnValue($resXml));
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$balXml = Mage::getModel('eb2cpayment/stored_value_redeem')->getRedeem($pan, $pin, 1, 1.00);
		$this->assertSame($resXml, $balXml);

		// Expect an empty string when the $pan is out of range.
		$this->assertSame('', Mage::getModel('eb2cpayment/stored_value_redeem')->getRedeem('65', 1, 1, 1.00));

		// Expect an empty string when the request throws a Zend_Http_Client_Exception
		$api->expects($this->once())
			->method('request')
			->will($this->throwException(new Zend_Http_Client_Exception));
		$balXml = Mage::getModel('eb2cpayment/stored_value_redeem')->getRedeem($pan, $pin, 1, 1.00);
		$this->assertSame('', $balXml);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemReply)
	{
		$this->assertSame(
			array(
				// If you change the order of the elements in this array the test will fail.
				'orderId'                => 1,
				'paymentAccountUniqueId' => '4111111ak4idq1111',
				'responseCode'           => 'Success',
				'amountRedeemed'         => 50.00,
				'balanceAmount'          => 150.00,
			),
			Mage::getModel('eb2cpayment/stored_value_redeem')->parseResponse($storeValueRedeemReply)
		);
	}
	/**
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildStoredValueRedeemRequest($pan, $pin, $entityId, $amount)
	{
		$this->assertSame('<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="clientId-storeId-1"><PaymentContext><OrderId>1</OrderId><PaymentAccountUniqueId isToken="false">4111111ak4idq1111</PaymentAccountUniqueId></PaymentContext><Pin>1234</Pin><Amount currencyCode="USD">50.00</Amount></StoredValueRedeemRequest>', Mage::getModel('eb2cpayment/stored_value_redeem')->buildStoredValueRedeemRequest($pan, $pin, $entityId, $amount)->C14N());
	}
}
