<?php
class TrueAction_Eb2cPayment_Test_Model_Storedvalue_BalanceTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test that getBalance sets the right URL, returns the response xml or empty string if there's a Zend_Http_Client_Exception
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetBalance($pan, $pin, $tenderType)
	{
		$this->markTestSkipped('skip failing test - needs to have connections to the helper replace by a mock');
		$reqXmlFrmt = '<StoredValueBalanceRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><PaymentAccountUniqueId isToken="false">%d</PaymentAccountUniqueId><Pin>%d</Pin><CurrencyCode>USD</CurrencyCode></StoredValueBalanceRequest>';
		$resXml = '<StoredValueBalanceReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><PaymentAccountUniqueId isToken="true">1</PaymentAccountUniqueId><ResponseCode>Success</ResponseCode><BalanceAmount currencyCode="USD">1.00</BalanceAmount></StoredValueBalanceReply>';
		$reqXml = sprintf($reqXmlFrmt, $pan, $pin);
		$apiUrl = sprintf('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/%s.xml', $tenderType);
		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->any())
			->method('setUri')
			->with($this->identicalTo($apiUrl))
			->will($this->returnSelf());
		$api->expects($this->any())
			->method('request')
			->will($this->returnValue($resXml));
		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$balXml = Mage::getModel('eb2cpayment/storedvalue_balance')->getBalance($pan, $pin);
		$this->assertSame($resXml, $balXml);

		// Expect an empty string when the $pan is out of range.
		$this->assertSame('', Mage::getModel('eb2cpayment/storedvalue_balance')->getBalance('65', 1));

		// Expect an empty string when the request throws a Zend_Http_Client_Exception
		$api->expects($this->once())
			->method('request')
			->will($this->throwException(new Zend_Http_Client_Exception));
		$balXml = Mage::getModel('eb2cpayment/storedvalue_balance')->getBalance($pan, $pin);
		$this->assertSame('', $balXml);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueBalanceReply)
	{
		$this->assertSame(
			array(
				// If you change the order of the elements of this array the test will fail. lol.
				'paymentAccountUniqueId' => '4111111ak4idq1111',
				'responseCode'           => 'Success',
				'balanceAmount'          => 50.00,
			),
			Mage::getModel('eb2cpayment/storedvalue_balance')->parseResponse($storeValueBalanceReply)
		);
	}
}
