<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Paypal_Set_Express_CheckoutTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_checkout;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_checkout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
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
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$checkoutReflector = new ReflectionObject($this->_checkout);
		$helper = $checkoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_checkout, $paymentHelper);

		$this->assertNotEmpty(
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
		$apiModelMock = $this->getMock(
			'TrueAction_Eb2cCore_Model_Api',
			array('setUri', 'request')
		);
		$apiModelMock->expects($this->any())
			->method('setUri')
			->will($this->returnSelf());

		$apiModelMock->expects($this->any())
			->method('request')
			->will(
				$this->throwException(new Exception)
			);

		$paymentHelper = Mage::helper('eb2cpayment');
		$paymentReflector = new ReflectionObject($paymentHelper);
		$apiModel = $paymentReflector->getProperty('apiModel');
		$apiModel->setAccessible(true);
		$apiModel->setValue($paymentHelper, $apiModelMock);

		$checkoutReflector = new ReflectionObject($this->_checkout);
		$helper = $checkoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_checkout, $paymentHelper);

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
		$this->assertSame(
			array('orderId' => 1, 'responseCode' => 'Success', 'token' => 'EC-5YE59312K56892714'),
			$this->_checkout->parseResponse($payPalSetExpressCheckoutReply)
		);
	}
}
