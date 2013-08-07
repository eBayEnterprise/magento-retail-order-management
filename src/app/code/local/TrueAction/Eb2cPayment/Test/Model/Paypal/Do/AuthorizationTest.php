<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Paypal_Do_AuthorizationTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_authorization;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_authorization = Mage::getModel('eb2cpayment/paypal_do_authorization');
	}

	public function buildQuoteMock()
	{
		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getQuoteCurrencyCode', 'getBaseGrandTotal')
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
			->method('getQuoteCurrencyCode')
			->will($this->returnValue('USD')
			);

		return $quoteMock;
	}

	public function providerDoAuthorization()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing doAuthorization method
	 *
	 * @test
	 * @dataProvider providerDoAuthorization
	 * @loadFixture loadConfig.yaml
	 */
	public function testDoAuthorization($quote)
	{
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$authorizationReflector = new ReflectionObject($this->_authorization);
		$helper = $authorizationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_authorization, $paymentHelper);

		$this->assertNotNull(
			$this->_authorization->doAuthorization($quote)
		);
	}

	/**
	 * testing when doAuthorization API call throw an exception
	 *
	 * @test
	 * @dataProvider providerDoAuthorization
	 * @loadFixture loadConfig.yaml
	 */
	public function testDoAuthorizationWithException($quote)
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

		$authorizationReflector = new ReflectionObject($this->_authorization);
		$helper = $authorizationReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_authorization, $paymentHelper);

		$this->assertSame(
			'',
			trim($this->_authorization->doAuthorization($quote))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/AuthorizationTest/fixtures/PayPalDoAuthorizationReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalDoAuthorizationReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_authorization->parseResponse($payPalDoAuthorizationReply)
		);
	}
}
