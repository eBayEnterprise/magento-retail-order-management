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
