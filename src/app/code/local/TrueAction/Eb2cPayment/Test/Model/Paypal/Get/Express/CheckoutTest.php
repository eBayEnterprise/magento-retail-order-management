<?php
class TrueAction_Eb2cPayment_Test_Model_Paypal_Get_Express_CheckoutTest
	extends TrueAction_Eb2cCore_Test_Base
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
