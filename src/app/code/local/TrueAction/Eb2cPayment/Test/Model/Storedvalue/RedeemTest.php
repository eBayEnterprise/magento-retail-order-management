<?php
class TrueAction_Eb2cPayment_Test_Model_Storedvalue_RedeemTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify the response string is returned when
	 * @test
	 * @dataProvider dataProvider
	 */
	public function testGetRedeem($pan, $resXml)
	{
		$pin = '1234';
		$entityId = 1;
		$amount = 1.00;
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$apiUrl = 'https://the.service.u/r/i.xml';

		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->any())
			->method('setUri')
			->with($this->identicalTo($apiUrl))
			->will($this->returnSelf());
		$api->expects($this->any())
			->method('request')
			->with($doc)
			->will($this->returnValue($resXml));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$helper->expects($this->once())
			->method('getSvcUri')
			->with(
				$this->identicalTo('get_gift_card_redeem'),
				$this->identicalTo($pan)
			)
			->will($this->returnValue($apiUrl));
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$testModel = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->disableOriginalConstructor()
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$testModel->expects($this->once())
			->method('buildStoredValueRedeemRequest')
			->with(
				$this->identicalTo($pan),
				$this->identicalTo($pin),
				$this->identicalTo($entityId),
				$this->identicalTo($amount)
			)
			->will($this->returnValue($doc));

		$balXml = $testModel->getRedeem($pan, $pin, $entityId, $amount);
		$this->assertSame($resXml, $balXml);
	}

	/**
	 * verify the empty string is returned if there's a Zend_Http_Client_Exception
	 * @test
	 */
	public function testGetRedeemWithNoUri()
	{
		$pan = '15';
		$pin = '1234';
		$entityId = 1;
		$amount = 1.00;
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$apiUrl = 'https://the.service.u/r/i.xml';

		$api = $this->getModelMock('eb2ccore/api', array('request'));
		$api->expects($this->never())
			->method('request');
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$helper->expects($this->once())
			->method('getSvcUri')
			->will($this->returnValue(''));
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$testModel = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->disableOriginalConstructor()
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$testModel->expects($this->any())
			->method('buildStoredValueRedeemRequest')
			->will($this->returnValue($doc));

		$balXml = $testModel->getRedeem($pan, $pin, $entityId, $amount);
		$this->assertSame('', $balXml);
	}

	/**
	 * verify the empty string is returned when getSvcUri yields an empty string.
	 * @test
	 */
	public function testGetRedeemWithException()
	{
		$reqXml = '<StoredValueRedeemRequest />';
		$resXml = '<this_is_an_unexpected_response />';
		$apiUrl = 'https://api.u.r/i.xml';
		$pan = "15";
		$pin = '1234';
		$entityId = 1;
		$amount = 1.00;
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();

		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->will($this->throwException(new Zend_Http_Client_Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getSvcUri'))
			->getMock();
		$helper->expects($this->once())
			->method('getSvcUri')
			->will($this->returnValue('https://the.service.u/r/i'));
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$testModel = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->disableOriginalConstructor()
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$testModel->expects($this->once())
			->method('buildStoredValueRedeemRequest')
			->will($this->returnValue($doc));
		$balXml = $testModel->getRedeem($pan, $pin, $entityId, $amount);
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
		$this->assertSame('<StoredValueRedeemRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="clientId-storeId-1"><PaymentContext><OrderId>1</OrderId><PaymentAccountUniqueId isToken="false">4111111ak4idq1111</PaymentAccountUniqueId></PaymentContext><Pin>1234</Pin><Amount currencyCode="USD">50.00</Amount></StoredValueRedeemRequest>', Mage::getModel('eb2cpayment/storedvalue_redeem')->buildStoredValueRedeemRequest($pan, $pin, $entityId, $amount)->C14N());
	}
}
