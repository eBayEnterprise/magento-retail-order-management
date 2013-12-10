<?php
/**
 * @codeCoverageIgnore
 */
class TrueAction_Eb2cPayment_Test_Mock_Model_Stored_Value_Balance extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * replacing by mock of the TrueAction_Eb2cPayment_Model_Storedvalue_Balance class
	 *
	 * @return Mock_TrueAction_Eb2cPayment_Model_Storedvalue_Balance
	 */
	public function buildEb2cPaymentModelStoredValueBalance()
	{
		$paymentModelStoredValueBalanceMock = $this->getMock(
			'TrueAction_Eb2cPayment_Model_Storedvalue_Balance',
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

	/**
	 * replacing by mock of the eb2cpayment/paypal_do_void class
	 *
	 * @return void
	 */
	public function replaceByMockStoredValueBalanceModel()
	{
		$storedValueBalanceMock = $this->getModelMockBuilder('eb2cpayment/storedvalue_balance')
			->disableOriginalConstructor()
			->setMethods(array('getBalance', 'parseResponse'))
			->getMock();
		$storedValueBalanceMock->expects($this->any())
			->method('getBalance')
			->will($this->returnValue(file_get_contents(__DIR__ . '/Xml/StoredValueBalanceReply.xml')));
		$storedValueBalanceMock->expects($this->any())
			->method('parseResponse')
			->will($this->returnValue(array('pin' => '1234', 'paymentAccountUniqueId' => '4111111ak4idq1111', 'balanceAmount' => 50.00)));
		$this->replaceByMock('model', 'eb2cpayment/storedvalue_balance', $storedValueBalanceMock);
	}
}
