<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = Mage::helper('eb2cpayment');
	}

	/**
	 * testing getXmlNs method
	 *
	 * @test
	 */
	public function testGetXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			$this->_helper->getXmlNs()
		);
	}

	/**
	 * testing getPaymentXmlNs method
	 *
	 * @test
	 */
	public function testGetPaymentXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/payment/1.0',
			$this->_helper->getPaymentXmlNs()
		);
	}

	/**
	 * testing getOperationUri method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/payments/storevalue/balance/GS.xml',
			$this->_helper->getOperationUri('get_gift_card_balance')
		);

		$this->assertSame(
			'https://api_env-api_rgn.gsipartners.com/vM.m/stores/store_id/payments/storevalue/redeem/GS.xml',
			$this->_helper->getOperationUri('get_gift_card_redeem')
		);
	}

	public function providerGetRequestId()
	{
		return array(
			array('100000060')
		);
	}

	/**
	 * testing helper data getRequestId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetRequestId
	 */
	public function testGetRequestId($incrementId)
	{
		$this->assertSame(
			'client_id-store_id-100000060',
			$this->_helper->getRequestId($incrementId)
		);
	}
}
