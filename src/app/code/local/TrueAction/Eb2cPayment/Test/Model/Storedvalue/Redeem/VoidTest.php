<?php
class TrueAction_Eb2cPayment_Test_Model_Storedvalue_Redeem_VoidTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test that getRedeemVoid sets the right URL, returns the response xml or empty string if there's a Zend_Http_Client_Exception
	 * @test
	 * @dataProvider dataProvider
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetVoid($pan, $pin, $entityId)
	{
		// $this->markTestSkipped('skip failing test - needs to have connections to the helper replace by a mock');
		$reqXml = '<StoredValueRedeemVoidRequest />';
		$apiUrl = 'https://api.u.r/i.xml';
		$amount = 1.00;

		$e = $this->expected('%s-%s-%s', $pan, $pin, $entityId);
		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->once())
			->method('setUri')
			->with($this->identicalTo($apiUrl))
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->will($this->returnValue($e->getResponseXml()));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$testModel = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->disableOriginalConstructor()
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$testModel->expects($this->once())
			->method('buildStoredValueRedeemRequest')
			->with(
				$this->identticalTo($pan),
				$this->identicalTo($pin),
				$this->identicalTo($entityId),
				$this->identicalTo($amount)
			);
		$balXml = Mage::getModel('eb2cpayment/storedvalue_redeem_void')->getRedeemVoid($pan, $pin, $entityId, $amount);
		$this->assertSame($e->getResponseXml(), $balXml);
	}

	/**
	 * Test that getRedeemVoid sets the right URL, returns the response xml or empty string if there's a Zend_Http_Client_Exception
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetVoidWithException()
	{
		// $this->markTestSkipped('skip failing test - needs to have connections to the helper replace by a mock');
		$pan = '15';
		$pin = '1234';
		$entityId = 1;
		$amount = 1.00;

		$api = $this->getModelMock('eb2ccore/api', array('setUri', 'request'));
		$api->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->will($this->throwException(new Zend_Http_Client_Exception));
		$this->replaceByMock('model', 'eb2ccore/api', $api);

		$entityId = 1;
		$testModel = $this->getModelMockBuilder('eb2cpayment/storedvalue_redeem')
			->disableOriginalConstructor()
			->setMethods(array('buildStoredValueRedeemRequest'))
			->getMock();
		$testModel->expects($this->any())
			->method('buildStoredValueRedeemRequest')
			->will($this->returnValue($doc));
		$balXml = Mage::getModel('eb2cpayment/storedvalue_redeem_void')->getRedeemVoid($pan, $pin, $entityId, $amount);
		$this->assertSame('', $balXml);
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
		$this->assertSame('<StoredValueRedeemVoidRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="clientId-storeId-1"><PaymentContext><OrderId>1</OrderId><PaymentAccountUniqueId isToken="false">4111111ak4idq1111</PaymentAccountUniqueId></PaymentContext><Pin>1234</Pin><Amount currencyCode="USD">50.00</Amount></StoredValueRedeemVoidRequest>', Mage::getModel('eb2cpayment/storedvalue_redeem_void')->buildStoredValueRedeemVoidRequest($pan, $pin, $entityId, $amount)->C14N());
	}
}
