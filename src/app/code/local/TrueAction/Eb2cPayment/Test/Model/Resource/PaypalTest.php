<?php
class TrueAction_Eb2cPayment_Test_Model_Resource_PaypalTest extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify the paypal resource model calls the load method correctly.
	 */
	public function testLoadByQuoteId()
	{
		$quoteId = 10;
		$model = $this->getModelMock('eb2cpayment/paypal');
		$testModel = $this->getResourceModelMockBuilder('eb2cpayment/paypal')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$testModel->expects($this->once())
			->method('load')
			->with(
				$this->identicalTo($model),
				$this->identicalTo($quoteId),
				$this->identicalTo('quote_id')
			)
			->will($this->returnSelf());
		$this->assertSame(
			$testModel,
			$testModel->loadByQuoteId($model, $quoteId)
		);
	}
}
