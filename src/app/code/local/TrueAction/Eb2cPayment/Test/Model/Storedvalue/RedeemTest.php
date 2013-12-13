<?php
class TrueAction_Eb2cPayment_Test_Model_Storedvalue_RedeemTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getRedeem method
	 * @test
	 */
	public function testGetRedeem()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="1">
				<PaymentContext>
					<OrderId>1</OrderId>
					<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>1234</Pin>
				<Amount currencyCode="USD">1.0</Amount>
			</StoredValueRedeemRequest>'
		);

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem'), $this->equalTo('80000000000000'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml'));
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'xsdFileStoredValueRedeem' => 'Payment-Service-StoredValueRedeem-1.0.xsd'
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'request'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'uri' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml',
				'xsd' => 'Payment-Service-StoredValueRedeem-1.0.xsd'
			)))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->returnValue(
				'<StoredValueRedeemReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
					</PaymentContext>
					<ResponseCode>Success</ResponseCode>
					<AmountRedeemed currencyCode="USD">1.00</AmountRedeemed>
					<BalanceAmount currencyCode="USD">1.00</BalanceAmount>
				</StoredValueRedeemReply>'
			));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$redeemModelMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$redeemModelMock->expects($this->once())
			->method('buildStoredValueRedeemRequest')
			->with($this->equalTo('80000000000000'), $this->equalTo('1234'), $this->equalTo(1), $this->equalTo(1.0))
			->will($this->returnValue($doc));

		$testData = array(
			array(
				'expect' => '<StoredValueRedeemReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
					</PaymentContext>
					<ResponseCode>Success</ResponseCode>
					<AmountRedeemed currencyCode="USD">1.00</AmountRedeemed>
					<BalanceAmount currencyCode="USD">1.00</BalanceAmount>
				</StoredValueRedeemReply>',
				'pan' => '80000000000000',
				'pin' => '1234',
				'entityId' => 1,
				'amount' => 1.0
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $redeemModelMock->getRedeem($data['pan'], $data['pin'], $data['entityId'], $data['amount']));
		}
	}

	/**
	 * Test getRedeem method, where getSvcUri return an empty url
	 * @test
	 */
	public function testGetRedeemWithEmptyUrl()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="1">
				<PaymentContext>
					<OrderId>1</OrderId>
					<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>1234</Pin>
				<Amount currencyCode="USD">1.0</Amount>
			</StoredValueRedeemRequest>'
		);

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem'), $this->equalTo('00000000000000'))
			->will($this->returnValue(''));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$redeemModelMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$redeemModelMock->expects($this->once())
			->method('buildStoredValueRedeemRequest')
			->with($this->equalTo('00000000000000'), $this->equalTo('1234'), $this->equalTo(1), $this->equalTo(1.0))
			->will($this->returnValue($doc));

		$testData = array(
			array(
				'expect' => '',
				'pan' => '00000000000000',
				'pin' => '1234',
				'entityId' => 1,
				'amount' => 1.0
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $redeemModelMock->getRedeem($data['pan'], $data['pin'], $data['entityId'], $data['amount']));
		}
	}

	/**
	 * Test getRedeem method, with Zend_Http_Client_Exception exception thrown
	 * @test
	 */
	public function testGetRedeemWithExceptionThrow()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML(
			'<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="1">
				<PaymentContext>
					<OrderId>1</OrderId>
					<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>1234</Pin>
				<Amount currencyCode="USD">1.0</Amount>
			</StoredValueRedeemRequest>'
		);

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem'), $this->equalTo('80000000000000'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml'));
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'xsdFileStoredValueRedeem' => 'Payment-Service-StoredValueRedeem-1.0.xsd'
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->disableOriginalConstructor()
			->setMethods(array('addData', 'request'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'uri' => 'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeem/GS.xml',
				'xsd' => 'Payment-Service-StoredValueRedeem-1.0.xsd'
			)))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with($this->isInstanceOf('TrueAction_Dom_Document'))
			->will($this->throwException(new Zend_Http_Client_Exception('Unittest, simulating when storedvalue redeem request throw exception')));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$redeemModelMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$redeemModelMock->expects($this->once())
			->method('buildStoredValueRedeemRequest')
			->with($this->equalTo('80000000000000'), $this->equalTo('1234'), $this->equalTo(1), $this->equalTo(1.0))
			->will($this->returnValue($doc));

		$testData = array(
			array(
				'expect' => '',
				'pan' => '80000000000000',
				'pin' => '1234',
				'entityId' => 1,
				'amount' => 1.0
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $redeemModelMock->getRedeem($data['pan'], $data['pin'], $data['entityId'], $data['amount']));
		}
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
			Mage::getModel('eb2cpayment/storedvalue_redeem')->parseResponse($storeValueRedeemReply)
		);
	}
	/**
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildStoredValueRedeemRequest($pan, $pin, $entityId, $amount)
	{
		$this->assertSame(
			preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '',
				'<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="clientId-storeId-1">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">4111111ak4idq1111</PaymentAccountUniqueId>
					</PaymentContext>
					<Pin>1234</Pin>
					<Amount currencyCode="USD">50.00</Amount>
				</StoredValueRedeemRequest>'
			)),
			trim(Mage::getModel('eb2cpayment/storedvalue_redeem')->buildStoredValueRedeemRequest($pan, $pin, $entityId, $amount)->C14N())
		);
	}
}
