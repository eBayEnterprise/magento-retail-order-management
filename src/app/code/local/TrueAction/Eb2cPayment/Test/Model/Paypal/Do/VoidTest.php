<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Paypal_Do_VoidTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_void;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_void = Mage::getModel('eb2cpayment/paypal_do_void');

		$paymentHelperMock = $this->getHelperMock('eb2cpayment/data', array('getOperationUri'));
		$paymentHelperMock->expects($this->any())
			->method('getOperationUri')
			->will($this->returnValue('http://eb2c.rgabriel.mage.tandev.net/eb2c/api/request/PayPalDoVoidReply.xml'));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);
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
		$this->assertNotNull(
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

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($payPalDoVoidReply)
	{
		$this->assertInstanceOf(
			'Varien_Object',
			$this->_void->parseResponse($payPalDoVoidReply)
		);
	}
}
