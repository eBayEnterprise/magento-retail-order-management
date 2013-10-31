<?php
class TrueAction_Eb2cCore_Test_Overrides_Helper_SalesTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * verify when eb2c is set to handle the emails:
	 * - the original getter function will not be called
	 * - the getter will return false
	 * @dataProvider dataProvider
	 */
	public function testFlagsWithSuppressionOn($testMethod)
	{
		$this->replaceCoreConfigRegistry(array(
			'emailsSentByEb2cFlag' => true
		));
		$testModel = $this->getHelperMock('sales/data', array($testMethod));
		$testModel->expects($this->never())
			->method($testMethod);
		$this->assertFalse($testModel->$testMethod());
	}

	/**
	 * verify when eb2c is not set to handle the emails the getters
	 * will return what is in the configuration.
	 * @dataProvider dataProvider
	 * @loadFixture
	 */
	public function testFlagsWithSuppressionOff($testMethod)
	{
		$this->replaceCoreConfigRegistry(array(
			'emailsSentByEb2cFlag' => false
		));
		$testModel = Mage::helper('sales');
		$this->assertInstanceOf('TrueAction_Eb2cCore_Overrides_Helper_Sales', $testModel);
		$this->assertTrue($testModel->$testMethod());
	}

	public function testOrderEmailSuppressionOff($sentByEb2c, $testModel, $testMethod)
	{
		$this->markTestIncomplete();
		$this->replaceCoreConfigRegistry(array(
			'emailsSentByEb2cFlag' => $sentByEb2c
		));
		$store = Mage::app()->getStore();
		$testModel = $this->getModelMock($testModel, array('_getEmails', 'getStore'));
		$testModel->expects($this->never())
			->method('_getStore')
			->will($this->returnValue($store));
		$testModel->expects($this->never())
			->method('_getEmails');
		$testModel->$testMethod();
	}

	public function testOrderEmailSuppressionOn($sentByEb2c, $testModel, $testMethod)
	{
		$this->markTestIncomplete();
		$this->replaceCoreConfigRegistry(array(
			'emailsSentByEb2cFlag' => $sentByEb2c
		));
		$store = Mage::app()->getStore();
		$testModel = $this->getModelMock($testModel, array('_getEmails', 'getStore'));
		$testModel->expects($this->never())
			->method('_getStore')
			->will($this->returnValue($store));
		$testModel->expects($this->never())
			->method('_getEmails');
		$testModel->$testMethod();
	}

	public function testRmaEmailSuppressionOn()
	{
		$this->markTestIncomplete();
		$rmaConfig = $this->getModelMockBuilder('enterprise_rma/config')
			->disableOriginalConstructor()
			->setMethods('');
	}
}
