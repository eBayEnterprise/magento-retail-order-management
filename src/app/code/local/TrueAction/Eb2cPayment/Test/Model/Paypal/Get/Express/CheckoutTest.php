<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Paypal_Get_Express_CheckoutTest extends EcomDev_PHPUnit_Test_Case_Controller
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
		$this->_checkout = Mage::getModel('eb2cpayment/paypal_get_express_checkout');
	}

	public function buildQuoteMock()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getEb2cPaypalExpressCheckoutToken', 'getQuoteCurrencyCode')
		);
		$quoteMock->expects($this->any())
			->method('getEntityId')
			->will($this->returnValue(1234567)
			);
		$quoteMock->expects($this->any())
			->method('getEb2cPaypalExpressCheckoutToken')
			->will($this->returnValue('EC-5YE59312K56892714')
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
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$checkoutReflector = new ReflectionObject($this->_checkout);
		$helper = $checkoutReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_checkout, $paymentHelper);

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
			trim($this->_checkout->getExpressCheckout($quote))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/CheckoutTest/fixtures/PayPalGetExpressCheckoutReply.xml', true))
		);
	}

	public function expectedParseResponse()
	{
		return array (
			'orderId' => 1,
			'responseCode' => 'ResponseCode0',
			'payerEmail' => 'PayerEmail0',
			'payerId' => 'PayerId0',
			'payerStatus' => 'PayerStatus0',
			'payerName' => array (
				'honorific' => 'Mr.',
				'lastName' => 'John',
				'middleName' => '',
				'firstName' => 'Smith',
			),
			'payerCountry' => 'US',
			'billingAddress' => array (
				'line1' => '123 Main St',
				'line2' => '',
				'line3' => '',
				'line4' => '',
				'city' => 'Philadelphia',
				'mainDivision' => 'PA',
				'countryCode' => 'US',
				'postalCode' => '19019',
				'addressStatus' => 'AddressStatus0',
			),
			'payerPhone' => '215-123-4567',
			'shippingAddress' => array (
				'line1' => '123 Main St',
				'line2' => '',
				'line3' => '',
				'line4' => '',
				'city' => 'Philadelphia',
				'mainDivision' => 'PA',
				'countryCode' => 'US',
				'postalCode' => '19019',
				'addressStatus' => 'AddressStatus1',
			)
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
		$this->assertSame(
			$this->expectedParseResponse(),
			$this->_checkout->parseResponse($payPalGetExpressCheckoutReply)
		);
	}
}
