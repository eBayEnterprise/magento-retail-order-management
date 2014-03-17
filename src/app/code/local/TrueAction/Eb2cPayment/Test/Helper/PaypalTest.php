<?php
class TrueAction_Eb2cPayment_Test_Helper_PaypalTest
{
	/**
	 * Test _savePaymentData method
	 * @test
	 */
	public function testSavePaymentData()
	{
		$quoteId = 51;
		$transId = '12354666233';
		$checkoutObject = new Varien_Object(array(
			'transaction_id' => $transId,
		));
		$quote = $this->getModelMockBuilder('sales/quote')
			->disableOriginalConstructor()
			->setMethods(array('getEntityId'))
			->getMock();
		$quote->expects($this->once())
			->method('getEntityId')
			->will($this->returnValue($quoteId));
		$paypal = $this->getModelMockBuilder('eb2cpayment/paypal')
			->disableOriginalConstructor()
			->setMethods(array('loadByQuoteId', 'save'))
			->getMock();
		$paypal->expects($this->once())
			->method('loadByQuoteId')
			->with($this->equalTo($quoteId))
			->will($this->returnSelf());
		$paypal->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cpayment/paypal', $paypal);
		Mage::helper('eb2cpayment/paypal')->savePaymentData($checkoutObject, $quote);
		$this->assertSame($quoteId, $paypal->getQuoteId());
		$this->assertSame($transId, $paypal->getEb2cPaypalTransactionId());
	}
}
