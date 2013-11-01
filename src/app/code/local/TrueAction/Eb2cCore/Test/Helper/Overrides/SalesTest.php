<?php
class TrueAction_Eb2cCore_Test_Overrides_Helper_SalesTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function tearDown()
	{
		Mage::unregister('_helper/sales');
	}

	public function testRewrite()
	{
		$this->assertInstanceOf('TrueAction_Eb2cCore_Overrides_Helper_Sales', Mage::helper('sales'));
	}

	/**
	 * @loadFixture
	 */
	public function testConfig()
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$this->assertSame(true, $config->isSalesEmailsSuppressedFlag);
	}

	/**
	 * verify when eb2c is set to handle the emails:
	 * - the original getter function will not be called
	 * - the getter will return false
	 * @dataProvider dataProvider
	 */
	public function testFlagsWithSuppressionOn($testMethod)
	{
		$this->replaceCoreConfigRegistry(array(
			'isSalesEmailsSuppressed' => true
		));
		$testModel = Mage::helper('sales');
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
			'isSalesEmailsSuppressed' => false
		));
		$testModel = Mage::helper('sales');
		$this->assertTrue($testModel->$testMethod());
	}

	public function testEmailSuppressionOff($testModel, $testMethod)
	{
		$this->markTestIncomplete();
		$this->replaceCoreConfigRegistry(array(
			'isSalesEmailsSuppressed' => false
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

	public function testEmailSuppressionOn($testModel, $testMethod)
	{
		$this->markTestIncomplete();
		$this->replaceCoreConfigRegistry(array(
			'isSalesEmailsSuppressed' => true
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
