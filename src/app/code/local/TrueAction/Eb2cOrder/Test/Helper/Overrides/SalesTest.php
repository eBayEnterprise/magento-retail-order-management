<?php
class TrueAction_Eb2cOrder_Test_Helper_Overrides_SalesTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function tearDown()
	{
		parent::tearDown();
		// delete the previous helper
		Mage::unregister('_helper/sales');
	}

	public function testRewrite()
	{
		$this->assertInstanceOf('TrueAction_Eb2cOrder_Overrides_Helper_Sales', Mage::helper('sales'));
	}

	/**
	 * @loadFixture
	 */
	public function testConfig()
	{
		$config = Mage::helper('eb2corder')->getConfig();
		$this->assertSame('eb2c', $config->transactionalEmailer);
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
			'transactionalEmailer' => 'eb2c'
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
			'transactionalEmailer' => 'mage'
		));
		$testModel = Mage::helper('sales');
		$this->assertTrue($testModel->$testMethod());
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testEmailSuppressionOff($testModel, $testMethod)
	{
		$this->setExpectedException('Mage_Core_Exception', 'this exception is expected');
		$this->replaceCoreConfigRegistry(array(
			'transactionalEmailer' => 'mage'
		));
		$store = Mage::app()->getStore();
		$testModel = $this->getModelMock($testModel, array('_getEmails', 'getStore'));
		$testModel->expects($this->any())
			->method('getStore')
			->will($this->returnValue($store));
		$testModel->expects($this->once())
			->method('_getEmails')
			->will($this->throwException(
				new Mage_Core_Exception('this exception is expected')
			));
		$testModel->$testMethod();
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testEmailSuppressionOn($testModel, $testMethod)
	{
		$this->replaceCoreConfigRegistry(array(
			'transactionalEmailer' => 'eb2c'
		));
		$store = Mage::app()->getStore();
		$testModel = $this->getModelMock($testModel, array('_getEmails', 'getStore'));
		$testModel->expects($this->any())
			->method('getStore')
			->will($this->returnValue($store));
		$testModel->expects($this->never())
			->method('_getEmails');
		$testModel->$testMethod();
	}
}
