<?php
class TrueAction_Eb2cPayment_Test_Model_Stored_Value_Redeem_VoidTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_void;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_void = Mage::getModel('eb2cpayment/stored_value_redeem_void');
	}

	public function providerGetRedeemVoid()
	{
		return array(
			array('4111111ak4idq1111', '1234', 1, 50.00)
		);
	}

	/**
	 * testing getRedeemVoid method
	 *
	 * @test
	 * @dataProvider providerGetRedeemVoid
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeemVoid($pan, $pin, $incrementId, $amount)
	{
		$this->assertNotNull(
			$this->_void->getRedeemVoid($pan, $pin, $incrementId, $amount)
		);
	}

	/**
	 * testing when getRedeemVoid API call throw an exception
	 *
	 * @test
	 * @dataProvider providerGetRedeemVoid
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeemVoidWithException($pan, $pin, $incrementId, $amount)
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

		$this->assertSame('', trim($this->_void->getRedeemVoid($pan, $pin, $incrementId, $amount)));
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/VoidTest/fixtures/StoredValueRedeemVoidReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemVoidReply)
	{
		$this->assertSame(
			array('orderId' => 1, 'paymentAccountUniqueId' => '4111111ak4idq1111', 'responseCode' => 'Success'),
			$this->_void->parseResponse($storeValueRedeemVoidReply)
		);
	}
}
