<?php
class TrueAction_Eb2cPayment_Test_Model_Paypal_Set_Express_CheckoutTest
	extends TrueAction_Eb2cCore_Test_Base
{
	protected $_checkout;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_checkout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');

		$urlMock = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$urlMock->expects($this->any())
			->method('getUrl')
			->will($this->returnValue('checkout/cart'));

		$this->replaceByMock('singleton', 'core/url', $urlMock);

		$sessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('getCookieShouldBeReceived', 'getSessionIdQueryParam', 'getSessionId', 'getSessionIdForHost'))
			->getMock();
		$sessionMock->expects($this->any())
			->method('getCookieShouldBeReceived')
			->will($this->returnValue(true));
		$sessionMock->expects($this->any())
			->method('getSessionIdQueryParam')
			->will($this->returnValue('name'));
		$sessionMock->expects($this->any())
			->method('getSessionId')
			->will($this->returnValue(1));
		$sessionMock->expects($this->any())
			->method('getSessionIdForHost')
			->will($this->returnValue(1));
		$this->replaceByMock('singleton', 'core/session', $sessionMock);
	}

	public function buildQuoteMock()
	{
		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getAllItems')
		);

		$itemMock = $this->getMock(
			'Mage_Sales_Model_Quote_Item',
			array('getName', 'getQty', 'getPrice')
		);

		$addressMock->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue(array($itemMock))
			);

		$itemMock->expects($this->any())
			->method('getName')
			->will($this->returnValue('Product A')
			);
		$itemMock->expects($this->any())
			->method('getQty')
			->will($this->returnValue(1)
			);
		$itemMock->expects($this->any())
			->method('getPrice')
			->will($this->returnValue(25.00)
			);

		$totals = array();
		$totals['grand_total'] = Mage::getModel('sales/quote_address_total', array(
			'code' => 'grand_total', 'value' => 50.00
		));
		$totals['subtotal'] = Mage::getModel('sales/quote_address_total', array(
			'code' => 'subtotal', 'value' => 50.00
		));
		$totals['shipping'] = Mage::getModel('sales/quote_address_total', array(
			'code' => 'shipping', 'value' => 10.00
		));
		$totals['tax'] = Mage::getModel('sales/quote_address_total', array(
			'code' => 'tax', 'value' => 5.00
		));

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array(
				'getEntityId', 'getTotals', 'getQuoteCurrencyCode', 'getAllAddresses'
			)
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1234567)
			);
		$quoteMock->expects($this->any())
			->method('getTotals')
			->will($this->returnValue($totals)
			);
		$quoteMock->expects($this->any())
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD')
			);
		$quoteMock->expects($this->any())
			->method('getAllAddresses')
			->will($this->returnValue(array($addressMock))
			);

		return $quoteMock;
	}
	public function providerSetExpressCheckout()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}
	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/CheckoutTest/fixtures/PayPalSetExpressCheckoutReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalSetExpressCheckoutReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_checkout->parseResponse($payPalSetExpressCheckoutReply)
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
			'token' => '12373474885888'
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
			->setMethods(array('loadByQuoteId', 'setQuoteId', 'setEb2cPaypalToken', 'save'))
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
			->method('setEb2cPaypalToken')
			->with($this->equalTo('12373474885888'))
			->will($this->returnSelf());
		$paypalModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalModelMock);

		$checkout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');

		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Model_Paypal_Set_Express_Checkout',
			$this->_reflectMethod($checkout, '_savePaymentData')->invoke($checkout, $checkoutObject, $quoteMock)
		);
	}
}
