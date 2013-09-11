<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Paypal_Set_Express_CheckoutTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_checkout;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_checkout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');

		$paymentHelperMock = $this->getHelperMock('eb2cpayment/data', array('getOperationUri'));
		$paymentHelperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('http://eb2c.rgabriel.mage.tandev.net/eb2c/api/request/PayPalSetExpressCheckoutReply.xml'));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

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

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array(
				'getEntityId', 'getBaseGrandTotal', 'getSubTotal', 'getShippingAmount',
				'getTaxAmount', 'getQuoteCurrencyCode', 'getAllAddresses'
			)
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1234567)
			);
		$quoteMock->expects($this->any())
			->method('getBaseGrandTotal')
			->will($this->returnValue(50.00)
			);
		$quoteMock->expects($this->any())
			->method('getSubTotal')
			->will($this->returnValue(50.00)
			);
		$quoteMock->expects($this->any())
			->method('getShippingAmount')
			->will($this->returnValue(10.00)
			);
		$quoteMock->expects($this->any())
			->method('getTaxAmount')
			->will($this->returnValue(5.00)
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

	/**
	 * testing setExpressCheckout method
	 *
	 * @test
	 * @dataProvider providerSetExpressCheckout
	 * @loadFixture loadConfig.yaml
	 */
	public function testSetExpressCheckout($quote)
	{
		$paypalMock = $this->getModelMockBuilder('eb2cpayment/paypal')
			->setMethods(array('setEb2cPaypalToken', 'save'))
			->getMock();

		$paypalMock->expects($this->any())
			->method('setEb2cPaypalToken')
			->will($this->returnSelf()
			);
		$paypalMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalMock);

		$this->assertNotNull(
			$this->_checkout->setExpressCheckout($quote)
		);
	}

	/**
	 * testing when setExpressCheckout API call throw an exception
	 *
	 * @test
	 * @dataProvider providerSetExpressCheckout
	 * @loadFixture loadConfig.yaml
	 */
	public function testSetExpressCheckoutWithException($quote)
	{
		$apiModelMock = $this->getModelMockBuilder('eb2ccore/api')
			->setMethods(array('setUri', 'request'))
			->getMock();

		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());
		$apiModelMock->expects($this->any())
			->method('request')
			->will($this->throwException(new Exception));

		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$paypalMock = $this->getModelMockBuilder('eb2cpayment/paypal')
			->setMethods(array('setEb2cPaypalToken', 'save'))
			->getMock();

		$paypalMock->expects($this->any())
			->method('setEb2cPaypalToken')
			->will($this->returnSelf()
			);
		$paypalMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);

		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalMock);

		$this->assertSame(
			'',
			trim($this->_checkout->setExpressCheckout($quote))
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
}
