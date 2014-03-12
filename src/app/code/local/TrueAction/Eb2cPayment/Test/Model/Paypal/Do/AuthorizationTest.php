<?php
class TrueAction_Eb2cPayment_Test_Model_Paypal_Do_AuthorizationTest
	extends TrueAction_Eb2cCore_Test_Base
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
	public function testDoAuthorization()
	{
		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->getMock();
		$api = $this->getModelMock('eb2ccore/api', array('request', 'setStatusHandlerPath'));
		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'getOperationUri'))
			->getMock();
		$auth = $this->getModelMock('eb2cpayment/paypal_do_authorization', array('buildPayPalDoAuthorizationRequest'));
		$config = $this->buildCoreConfigRegistry(array('xsdFilePaypalDoAuth' => 'xsdfile'));
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();

		$this->replaceByMock('model', 'eb2ccore/api', $api);
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($config));
		$helper->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('get_paypal_do_authorization'))
			->will($this->returnValue('/uri/'));

		$auth->expects($this->once())
			->method('buildPayPalDoAuthorizationRequest')
			->with($this->identicalto($quote))
			->will($this->returnValue($doc));
		$api->expects($this->once())
			->method('setStatusHandlerPath')
			->with($this->identicalto(TrueAction_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH))
			->will($this->returnSelf());
		$api->expects($this->once())
			->method('request')
			->with(
				$this->identicalto($doc),
				$this->identicalTo('xsdfile'),
				$this->identicalTo('/uri/')
			)
			->will($this->returnValue('responseText'));

		$this->assertSame('responseText', $auth->doAuthorization($quote));
	}
}
