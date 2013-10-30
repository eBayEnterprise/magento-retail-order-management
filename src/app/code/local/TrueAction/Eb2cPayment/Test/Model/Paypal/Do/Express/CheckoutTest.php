<?php
class TrueAction_Eb2cPayment_Test_Model_Paypal_Do_Express_CheckoutTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_checkout;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_checkout = Mage::getModel('eb2cpayment/paypal_do_express_checkout');
	}

	public function buildQuoteMock()
	{
		$paymentMock = $this->getMock(
			'Mage_Sales_Model_Quote_Payment',
			array('getEb2cPaypalToken', 'getEb2cPaypalPayerId', 'setEb2cPaypalTransactionID', 'save')
		);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paymentMock->expects($this->any())
			->method('getEb2cPaypalPayerId')
			->will($this->returnValue('1234')
			);
		$paymentMock->expects($this->any())
			->method('setEb2cPaypalTransactionID')
			->will($this->returnSelf()
			);
		$paymentMock->expects($this->any())
			->method('save')
			->will($this->returnSelf()
			);

		$addressMock = $this->getMock(
			'Mage_Sales_Model_Quote_Address',
			array('getName', 'getStreet', 'getCity', 'getRegion', 'getCountryId', 'getPostcode', 'getAllItems')
		);
		$addressMock->expects($this->any())
			->method('getName')
			->will($this->returnValue('John Doe')
			);
		$map = array(
			array(1, '1938 Some Street'),
			array(2, 'Line2'),
			array(3, 'Line3'),
			array(4, 'Line4'),
		);
		$addressMock->expects($this->any())
			->method('getStreet')
			->will($this->returnValueMap($map)
			);
		$addressMock->expects($this->any())
			->method('getCity')
			->will($this->returnValue('King of Prussia')
			);
		$addressMock->expects($this->any())
			->method('getRegion')
			->will($this->returnValue('Pennsylvania')
			);
		$addressMock->expects($this->any())
			->method('getCountryId')
			->will($this->returnValue('US')
			);
		$addressMock->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue('19726')
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
				'getEntityId', 'getQuoteCurrencyCode',
				'getBaseGrandTotal', 'getSubtotal', 'getShippingAmount', 'getTaxAmount', 'getAllAddresses', 'getPayment'
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
			->method('getSubtotal')
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
		$quoteMock->expects($this->any())
			->method('getPayment')
			->will($this->returnValue($paymentMock)
			);

		return $quoteMock;
	}

	public function providerDoExpressCheckout()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing doExpressCheckout method
	 *
	 * @test
	 * @dataProvider providerDoExpressCheckout
	 * @loadFixture loadConfig.yaml
	 */
	public function testDoExpressCheckout($quote)
	{
		$paypalMock = $this->getModelMockBuilder('eb2cpayment/paypal')
			->setMethods(array('getEb2cPaypalToken', 'getEb2cPaypalPayerId'))
			->getMock();

		$paypalMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paypalMock->expects($this->any())
			->method('getEb2cPaypalPayerId')
			->will($this->returnValue('PayerId0')
			);
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalMock);

		$this->assertNotNull(
			$this->_checkout->doExpressCheckout($quote)
		);
	}

	/**
	 * testing when doExpressCheckout API call throw an exception
	 *
	 * @test
	 * @dataProvider providerDoExpressCheckout
	 * @loadFixture loadConfig.yaml
	 */
	public function testDoExpressCheckoutWithException($quote)
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
			->setMethods(array('getEb2cPaypalToken', 'getEb2cPaypalPayerId'))
			->getMock();

		$paypalMock->expects($this->any())
			->method('getEb2cPaypalToken')
			->will($this->returnValue('EC-5YE59312K56892714')
			);
		$paypalMock->expects($this->any())
			->method('getEb2cPaypalPayerId')
			->will($this->returnValue('PayerId0')
			);

		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypalMock);

		$this->assertSame(
			'',
			trim($this->_checkout->doExpressCheckout($quote))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/CheckoutTest/fixtures/PayPalDoExpressCheckoutReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalDoExpressCheckoutReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_checkout->parseResponse($payPalDoExpressCheckoutReply)
		);
	}
}
