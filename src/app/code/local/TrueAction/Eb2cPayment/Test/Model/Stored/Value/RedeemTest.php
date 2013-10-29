<?php
class TrueAction_Eb2cPayment_Test_Model_Stored_Value_RedeemTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_redeem;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_redeem = Mage::getModel('eb2cpayment/stored_value_redeem');
	}

	public function providerGetRedeem()
	{
		return array(
			array('4111111ak4idq1111', '1234', 1, 50.00)
		);
	}

	/**
	 * testing getRedeem method
	 *
	 * @test
	 * @dataProvider providerGetRedeem
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeem($pan, $pin, $entityId, $amount)
	{
		$this->assertNotNull(
			$this->_redeem->getRedeem($pan, $pin, $entityId, $amount)
		);
	}

	/**
	 * testing when getRedeem API call throw an exception
	 *
	 * @test
	 * @dataProvider providerGetRedeem
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeemWithException($pan, $pin, $entityId, $amount)
	{
		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->setMethods(array('setUri', 'request'))
			->getMock();

		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Zend_Http_Client_Exception));

		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$this->assertSame(
			'',
			trim($this->_redeem->getRedeem($pan, $pin, $entityId, $amount))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/RedeemTest/fixtures/StoredValueRedeemReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemReply)
	{
		$this->assertSame(
			array('orderId' => 1, 'paymentAccountUniqueId' => '4111111ak4idq1111', 'responseCode' => 'Success', 'amountRedeemed' => 50.00, 'balanceAmount' => 150.00),
			$this->_redeem->parseResponse($storeValueRedeemReply)
		);
	}
}
