<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Model_Stored_Value_Balance class
	 *
	 * @return Mock_TrueAction_Eb2cPayment_Model_Stored_Value_Balance
	 */
	public function buildEb2cPaymentModelStoredValueBalance()
	{
		$paymentModelStoredValueBalanceMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Stored_Value_Balance',
			array('getBalance', 'parseResponse')
		);

		$paymentModelStoredValueBalanceMock->expects($this->any())
			->method('getBalance')
			->will($this->returnValue(file_get_contents(__DIR__ . '/Xml/StoredValueBalanceReply.xml')));
		$paymentModelStoredValueBalanceMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('pin' => '1234', 'paymentAccountUniqueId' => '4111111ak4idq1111', 'balanceAmount' => 50.00)));

		return $paymentModelStoredValueBalanceMock;
	}
}
