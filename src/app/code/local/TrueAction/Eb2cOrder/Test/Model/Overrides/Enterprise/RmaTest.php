<?php
class TrueAction_Eb2cOrder_Test_Model_Overrides_Enterprise_RmaTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function testRmaRewrite()
	{
		$this->assertInstanceOf(
			'TrueAction_Eb2cOrder_Overrides_Model_Enterprise_Rma',
			Mage::getModel('enterprise_rma/rma')
		);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testRmaEmailSuppressionOn($testMethod)
	{
		$this->replaceCoreConfigRegistry(array(
			'isSalesEmailsSuppressed' => true
		));
		$testModel = $this->getModelMock('enterprise_rma/rma');
		$testModel->expects($this->never())
			->method('_sendRmaEmailWithItems')
			->will($this->returnSelf());
		$testModel->$testMethod();
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testRmaEmailSuppressionOff($testMethod)
	{
		$this->replaceCoreConfigRegistry(array(
			'isSalesEmailsSuppressed' => true
		));
		$testModel = $this->getModelMock('enterprise_rma/rma');
		$testModel->expects($this->once())
			->method('_sendRmaEmailWithItems')
			->will($this->returnSelf());
		$testModel->$testMethod();
	}
}
