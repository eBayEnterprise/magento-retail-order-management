<?php
class TrueAction_Eb2cPayment_Test_Model_Paypal_Get_Express_CheckoutTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_checkout;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_checkout = Mage::getModel('eb2cpayment/paypal_get_express_checkout');
	}

	public function buildQuoteMock()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getQuoteCurrencyCode')
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1234567)
			);
		$quoteMock->expects($this->any())
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD')
			);

		return $quoteMock;
	}

	public function providerGetExpressCheckout()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing getExpressCheckout method
	 *
	 * @test
	 * @dataProvider providerGetExpressCheckout
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetExpressCheckout($quote)
	{
		$paypalMock = $this->getModelMockBuilder('eb2cpayment/paypal')
			->setMethods(array('getEb2cPaypalToken', 'setEb2cPaypalPayerId', 'save'))
			->getMock();
		$paypalMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paypalMock->expects($this->any())
			->method('setEb2cPaypalPayerId')
			->will($this->returnSelf()
			);
		$paypalMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalMock);

		$this->assertNotNull(
			$this->_checkout->getExpressCheckout($quote)
		);
	}

	/**
	 * testing when getExpressCheckout API call throw an exception
	 *
	 * @test
	 * @dataProvider providerGetExpressCheckout
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetExpressCheckoutWithException($quote)
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

		$paypalMock = $this->getModelMockBuilder('eb2cpayment/paypal')
			->setMethods(array('getEb2cPaypalToken', 'setEb2cPaypalPayerId', 'save'))
			->getMock();

		$paypalMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paypalMock->expects($this->any())
			->method('setEb2cPaypalPayerId')
			->will($this->returnSelf()
			);
		$paypalMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalMock);

		$this->assertSame(
			'',
			trim($this->_checkout->getExpressCheckout($quote))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/CheckoutTest/fixtures/PayPalGetExpressCheckoutReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalGetExpressCheckoutReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_checkout->parseResponse($payPalGetExpressCheckoutReply)
		);
	}
}
