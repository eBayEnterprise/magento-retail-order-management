<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Test_Model_Stored_Value_RedeemTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_redeem;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_redeem = Mage::getModel('eb2cpayment/stored_value_redeem');
	}

	public function providerGetRedeem()
	{
		return array(
			array('4111111ak4idq1111', '1234', 1, 50.00)
		);
	}

	/**
	 * testing getRedeem method
	 *
	 * @test
	 * @dataProvider providerGetRedeem
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeem($pan, $pin, $entityId, $amount)
	{
		$paymentHelper = new TrueAction_Eb2cPayment_Helper_Data();
		$redeemReflector = new ReflectionObject($this->_redeem);
		$helper = $redeemReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_redeem, $paymentHelper);

		$this->assertNotEmpty(
			$this->_redeem->getRedeem($pan, $pin, $entityId, $amount)
		);
	}

	/**
	 * testing when getRedeem API call throw an exception
	 *
	 * @test
	 * @dataProvider providerGetRedeem
	 * @loadFixture loadConfig.yaml
	 */
	public function testGetRedeemWithException($pan, $pin, $entityId, $amount)
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

		$redeemReflector = new ReflectionObject($this->_redeem);
		$helper = $redeemReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_redeem, $paymentHelper);

		$this->assertSame(
			'',
			trim($this->_redeem->getRedeem($pan, $pin, $entityId, $amount))
		);
	}

	public function providerParseResponse()
	{
		return array(
			array(file_get_contents(dirname(__FILE__) . '/RedeemTest/fixtures/StoredValueRedeemReply.xml', true))
		);
	}

	/**
	 * testing parseResponse method
	 *
	 * @test
	 * @dataProvider providerParseResponse
	 * @loadFixture loadConfig.yaml
	 */
	public function testParseResponse($storeValueRedeemReply)
	{
		$this->assertSame(
			array('orderId' => 1, 'paymentAccountUniqueId' => '4111111ak4idq1111', 'responseCode' => 'Success', 'amountRedeemed' => 50.00, 'balanceAmount' => 150.00),
			$this->_redeem->parseResponse($storeValueRedeemReply)
		);
	}
}
