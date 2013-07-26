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
	 * testing getOperationUri method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/payments/storevalue/balance/GS.xml',
			$this->_helper->getOperationUri('get_gift_card_balance')
		);

		$this->assertSame(
			'https://developer-na.gsipartners.com/v1.10/stores/ABCD/payments/storevalue/redeem/GS.xml',
			$this->_helper->getOperationUri('get_gift_card_redeem')
		);
	}


	/**
	 * testing getApiModel method
	 *
	 * @test
	 */
	public function testGetApiModel()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cCore_Model_Api',
			$this->_helper->getApiModel()
		);
	}

	/**
	 * testing getDomDocument method
	 *
	 * @test
	 */
	public function testGetDomDocument()
	{
		$this->assertInstanceOf(
			'TrueAction_Dom_Document',
			$this->_helper->getDomDocument()
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
			'TAN-CLI-ABCD-100000060',
			$this->_helper->getRequestId($incrementId)
		);
	}
}
