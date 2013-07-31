<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Paypal_Do_VoidTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_void;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_void = Mage::getModel('eb2cpayment/paypal_do_void');
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

	public function providerDoVoid()
	{
		return array(
			array($this->buildQuoteMock())
		);
	}

	/**
	 * testing doVoid method
	 *
	 * @test
	 * @dataProvider providerDoVoid
	 * @loadFixture loadConfig.yaml
	 */
	public function testDoVoid($quote)
	{
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$voidReflector = new ReflectionObject($this->_void);
		$helper = $voidReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_void, $paymentHelper);

		$this->assertNotEmpty(
			$this->_void->doVoid($quote)
		);
	}

	/**
	 * testing when doVoid API call throw an exception
	 *
	 * @test
	 * @dataProvider providerDoVoid
	 * @loadFixture loadConfig.yaml
	 */
	public function testDoVoidWithException($quote)
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

		$voidReflector = new ReflectionObject($this->_void);
		$helper = $voidReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_void, $paymentHelper);

		$this->assertSame(
			'',
			trim($this->_void->doVoid($quote))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(__DIR__ . '/VoidTest/fixtures/PayPalDoVoidReply.xml', true))
		);
	}

	public function expectedParseResponse()
	{
		return array (
			'orderId' => 1,
			'responseCode' => 'Success'
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalDoVoidReply)
	{
		$this->assertSame(
			$this->expectedParseResponse(),
			$this->_void->parseResponse($payPalDoVoidReply)
		);
	}
}
