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

	/**
	 * Test _savePaymentData method
	 * @test
	 */
	public function testSavePaymentData()
	{
		$checkoutObject = new Varien_Object(array(
			'order_id' => '0005400000182',
			'response_code' => 'Success',
			'payer_id' => 'payerId000',
			'payer_status' => 'Pending',
			'payer_name_honorific' => 'Mr.',
			'payer_name_last_name' => 'Doe',
			'payer_name_middle_name' => null,
			'payer_name_first_name' => 'Doe',
			'payer_country' => 'US',
			'billing_address_line1' => 'Fake Street Rd',
			'billing_address_line2' => null,
			'billing_address_line3' => null,
			'billing_address_line4' => null,
			'billing_address_city' => 'Fake City Village',
			'billing_address_main_division' => 'PA',
			'billing_address_country_code' => 'US',
			'billing_address_postal_code' => '55555',
			'billing_address_status' => 'unkown',
			'payer_phone' => '555-555-5555',
			'shipping_address_line1' => 'Fake Street Rd',
			'shipping_address_line2' => null,
			'shipping_address_line3' => null,
			'shipping_address_Line4' => null,
			'shipping_address_city' => 'Fake City Village',
			'shipping_address_main_division' => 'PA',
			'shipping_address_country_code' => 'US',
			'shipping_address_postal_code' => '55555',
			'shipping_address_status' => 'unkown',
		));

		$quoteMock = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getEntityId'))
			->getMock();
		$quoteMock->expects($this->exactly(2))
			->method('getEntityId')
			->will($this->returnValue(51));

		$paypalModelMock = $this->getModelMockBuilder('eb2cpayment/paypal')
			->disableOriginalConstructor()
			->setMethods(array('loadByQuoteId', 'setQuoteId', 'setEb2cPaypalPayerId', 'save'))
			->getMock();
		$paypalModelMock->expects($this->once())
			->method('loadByQuoteId')
			->with($this->equalTo(51))
			->will($this->returnSelf());
		$paypalModelMock->expects($this->once())
			->method('setQuoteId')
			->with($this->equalTo(51))
			->will($this->returnSelf());
		$paypalModelMock->expects($this->once())
			->method('setEb2cPaypalPayerId')
			->with($this->equalTo('payerId000'))
			->will($this->returnSelf());
		$paypalModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalModelMock);

		$checkout = Mage::getModel('eb2cpayment/paypal_get_express_checkout');

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Get_Express_Checkout',
			$this->_reflectMethod($checkout, '_savePaymentData')->invoke($checkout, $checkoutObject, $quoteMock)
		);
	}
}
