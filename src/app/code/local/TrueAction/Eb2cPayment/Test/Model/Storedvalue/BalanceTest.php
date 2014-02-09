<?php
class TrueAction_Eb2cPayment_Test_Model_Storedvalue_BalanceTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getBalance method
	 * @test
	 */
	public function testGetBalance()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<StoredValueBalanceRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
				<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
				<Pin>1234</Pin>
				<CurrencyCode>USD</CurrencyCode>
			</StoredValueBalanceRequest>'
		);

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_balance'), $this->equalTo('80000000000000'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml'));
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'xsdFileStoredValueBalance' => 'Payment-Service-StoredValueBalance-1.0.xsd'
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('request'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('request')
			->with(
				$this->isInstanceOf('TrueAction_Dom_Document'),
				'Payment-Service-StoredValueBalance-1.0.xsd',
				'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/balance/GS.xml'
			)->will($this->returnValue(
				'<StoredValueBalanceReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentAccountUniqueId isToken="true">80000000000000</PaymentAccountUniqueId>
					<ResponseCode>Success</ResponseCode>
					<BalanceAmount currencyCode="USD">1.00</BalanceAmount>
				</StoredValueBalanceReply>'
			));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$balanceModelMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_balance')
			->setMethods(array('buildStoredValueBalanceRequest'))
			->getMock();
		$balanceModelMock->expects($this->once())
			->method('buildStoredValueBalanceRequest')
			->with($this->equalTo('80000000000000'), $this->equalTo('1234'))
			->will($this->returnValue($doc));

		$testData = array(
			array(
				'expect' => '<StoredValueBalanceReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentAccountUniqueId isToken="true">80000000000000</PaymentAccountUniqueId>
					<ResponseCode>Success</ResponseCode>
					<BalanceAmount currencyCode="USD">1.00</BalanceAmount>
				</StoredValueBalanceReply>',
				'pan' => '80000000000000',
				'pin' => '1234'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $balanceModelMock->getBalance($data['pan'], $data['pin']));
		}
	}
	/**
	 * Test getBalance method, where getSvcUri return an empty url
	 * @test
	 */
	public function testGetBalanceWithEmptyUrl()
	{
		$pan = '00000000000000';
		$pin = '1234';
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			"<StoredValueBalanceRequest xmlns='http://api.gsicommerce.com/schema/checkout/1.0'>
				<PaymentAccountUniqueId isToken='false'>$pan</PaymentAccountUniqueId>
				<Pin>$pin</Pin>
				<CurrencyCode>USD</CurrencyCode>
			</StoredValueBalanceRequest>"
		);
		$payHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$payHelper->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_balance'), $this->equalTo($pan))
			->will($this->returnValue(''));
		$this->replaceByMock('helper', 'eb2cpayment', $payHelper);
		$this->assertSame('', Mage::getModel('eb2cpayment/storedvalue_balance')->getBalance($pan, '1234'));
	}
	/**
	 * Test buildStoredValueBalanceRequest method
	 * @test
	 */
	public function testBuildStoredValueBalanceRequest()
	{
		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getXmlNs'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getXmlNs')
			->will($this->returnValue('http://api.gsicommerce.com/schema/checkout/1.0'));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$testData = array(
			array(
				'expect' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '',
					'<StoredValueBalanceRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentAccountUniqueId isToken="false"><![CDATA[80000000000000]]></PaymentAccountUniqueId>
					<Pin><![CDATA[1234]]></Pin>
					<CurrencyCode><![CDATA[USD]]></CurrencyCode>
					</StoredValueBalanceRequest>')),
				'pan' => '80000000000000',
				'pin' => '1234'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame(
				$data['expect'],
				trim(Mage::getModel('eb2cpayment/storedvalue_balance')->buildStoredValueBalanceRequest($data['pan'], $data['pin'])->saveXml())
			);
		}
	}

	/**
	 * testing parseResponse method
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
