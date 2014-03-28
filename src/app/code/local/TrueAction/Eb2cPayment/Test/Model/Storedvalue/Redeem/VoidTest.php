<?php
class TrueAction_Eb2cPayment_Test_Model_Storedvalue_Redeem_VoidTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * Test getRedeemVoid method
	 * @test
	 */
	public function testGetRedeemVoid()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<StoredValueRedeemVoidRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="1">
				<PaymentContext>
					<OrderId>1</OrderId>
					<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>1234</Pin>
				<Amount currencyCode="USD">1.00</Amount>
			</StoredValueRedeemVoidRequest>'
		);

		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri', 'getConfigModel'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem_void'), $this->equalTo('80000000000000'))
			->will($this->returnValue('https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml'));
		$paymentHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue((object) array(
				'xsdFileStoredValueVoidRedeem' => 'Payment-Service-StoredValueRedeemVoid-1.0.xsd'
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->setMethods(array('request', 'setStatusHandlerPath'))
			->getMock();
		$apiModelMock->expects($this->once())
			->method('setStatusHandlerPath')
			->with($this->equalTo(TrueAction_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH))
			->will($this->returnSelf());
		$apiModelMock->expects($this->once())
			->method('request')
			->with(
				$this->isInstanceOf('TrueAction_Dom_Document'),
				'Payment-Service-StoredValueRedeemVoid-1.0.xsd',
				'https://api.example.com/vM.m/stores/storeId/payments/storedvalue/redeemvoid/GS.xml'
			)->will($this->returnValue(
				'<StoredValueRedeemVoidReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
					</PaymentContext>
					<ResponseCode>Success</ResponseCode>
				</StoredValueRedeemVoidReply>'
			));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$redeemVoidModelMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem_void')
			->setMethods(array('buildStoredValueRedeemVoidRequest'))
			->getMock();
		$redeemVoidModelMock->expects($this->once())
			->method('buildStoredValueRedeemVoidRequest')
			->with($this->equalTo('80000000000000'), $this->equalTo('1234'), $this->equalTo(1), $this->equalTo(1.0))
			->will($this->returnValue($doc));

		$testData = array(
			array(
				'expect' => '<StoredValueRedeemVoidReply xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">80000000000000</PaymentAccountUniqueId>
					</PaymentContext>
					<ResponseCode>Success</ResponseCode>
				</StoredValueRedeemVoidReply>',
				'pan' => '80000000000000',
				'pin' => '1234',
				'entityId' => 1,
				'amount' => 1.0
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $redeemVoidModelMock->getRedeemVoid($data['pan'], $data['pin'], $data['entityId'], $data['amount']));
		}
	}
	/**
	 * Test getRedeemVoid method, where getSvcUri return an empty url
	 * @test
	 */
	public function testGetRedeemVoidWithEmptyUrl()
	{
		$pan = '00000000000000';
		$pin = '1234';
		$entityId = 1;
		$amount = 1.0;
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			"<StoredValueRedeemVoidRequest xmlns='http://api.gsicommerce.com/schema/checkout/1.0' requestId='1'>
				<PaymentContext>
					<OrderId>$entityId</OrderId>
					<PaymentAccountUniqueId isToken='false'>$pan</PaymentAccountUniqueId>
				</PaymentContext>
				<Pin>$pin</Pin>
				<Amount currencyCode='USD'>$amount</Amount>
			</StoredValueRedeemVoidRequest>"
		);
		$payHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$payHelper->expects($this->once())
			->method('getSvcUri')
			->with($this->equalTo('get_gift_card_redeem_void'), $this->equalTo($pan))
			->will($this->returnValue(''));
		$this->replaceByMock('helper', 'eb2cpayment', $payHelper);
		$this->assertSame('', Mage::getModel('eb2cpayment/storedvalue_redeem_void')->getRedeemVoid($pan, $pin, $entityId, $amount));
	}
	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemVoidReply)
	{
		$this->assertSame(
			array(
				// If you change the order of this array the test will fail.
				'orderId'                => 1,
				'paymentAccountUniqueId' => '4111111ak4idq1111',
				'responseCode'           => 'Success',
			),
			Mage::getModel('eb2cpayment/storedvalue_redeem_void')->parseResponse($storeValueRedeemVoidReply)
		);
	}

	/**
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testBuildStoredValueVoidRequest($pan, $pin, $entityId, $amount)
	{
		$this->assertSame(
			preg_replace('/[ ]{2,}|[\t]/', '', str_replace(array("\r\n", "\r", "\n"), '',
				'<StoredValueRedeemVoidRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="clientId-storeId-1">
					<PaymentContext>
						<OrderId>1</OrderId>
						<PaymentAccountUniqueId isToken="false">4111111ak4idq1111</PaymentAccountUniqueId>
					</PaymentContext>
					<Pin>1234</Pin>
					<Amount currencyCode="USD">50.00</Amount>
				</StoredValueRedeemVoidRequest>'
			)),
			trim(Mage::getModel('eb2cpayment/storedvalue_redeem_void')->buildStoredValueRedeemVoidRequest($pan, $pin, $entityId, $amount)->C14N())
		);
	}
}
