<?php
class TrueAction_Eb2cPayment_Test_Model_Paypal_Do_AuthorizationTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_authorization;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_authorization = Mage::getModel('eb2cpayment/paypal_do_authorization');
	}

	public function buildQuoteMock()
	{
		$totals = array();
		$totals['grand_total'] = Mage::getModel('sales/quote_address_total', array(
			'code' => 'grand_total', 'value' => 50.00
		));

		$quoteMock = $this->getMock(
			'Mage_Sales_Model_Quote',
			array('getEntityId', 'getQuoteCurrencyCode', 'getTotals')
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
