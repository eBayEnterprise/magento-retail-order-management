<?php
class TrueAction_Eb2cPayment_Test_Block_Overrides_Adminhtml_System_Config_FormTest
	extends TrueAction_Eb2cCore_Test_Base
{
	/**
	 * @loadFixture
	 */
	public function testInitForm()
	{
		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'getAllowedPaymentConfigGroups',
		));
		$allowedGroups = array('allowed_group');
		$suppression->expects($this->once())
			->method('getAllowedPaymentConfigGroups')
			->will($this->returnValue($allowedGroups));
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);

		$testModel = $this->getBlockMock('adminhtml/system_config_form', array(
			'_initObjects',
			'_initGroup',
			'setForm',
			'_canShowField',
		));
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form',
			$testModel,
			'message'
		);
		$testModel->expects($this->once())
			->method('setForm');
		$testModel->expects($this->exactly(3))
			->method('_canShowField')
			->will($this->returnValue(true));
		$testModel->expects($this->once())
			->method('_initGroup')
			->with(
				$this->isInstanceOf('Varien_Data_Form'),
				$this->callback(
					function ($arg)
					{
						return $arg->getName() === 'allowed_group';
					}),
				$this->isInstanceOf('Mage_Core_Model_Config_Element')
			);

		$config = $this->getModelMock('core/config', array('getSection'));
		$config->loadString('<config />');
		$config->setNode('sections/payment/groups/allowed_group/sort_order', 1);
		$config->setNode('sections/payment/groups/disallowed_group/sort_order', 2);
		$config->expects($this->any())
			->method('getSection')
			->will($this->returnValue($config->getNode('sections/payment')));
		$this->_reflectProperty($testModel, '_configFields')
			->setValue($testModel, $config);

		$testModel->initForm();
	}

	/**
	 * @loadFixture
	 */
	public function testInitFormPaymentsOff()
	{
		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'getAllowedPaymentConfigGroups',
		));
		$allowedGroups = array('allowed_group');
		$suppression->expects($this->once())
			->method('getAllowedPaymentConfigGroups')
			->will($this->returnValue($allowedGroups));
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);

		$testModel = $this->getBlockMock('adminhtml/system_config_form', array(
			'_initObjects',
			'_initGroup',
			'setForm',
			'_canShowField',
		));
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form',
			$testModel,
			'message'
		);
		$testModel->expects($this->once())
			->method('setForm');
		$testModel->expects($this->exactly(4))
			->method('_canShowField')
			->will($this->returnValue(true));
		$testModel->expects($this->exactly(2))
			->method('_initGroup')
			->with(
				$this->isInstanceOf('Varien_Data_Form'),
				$this->callback(
					function ($arg)
					{
						return in_array($arg->getName(), array('allowed_group', 'disallowed_group'));
					}),
				$this->isInstanceOf('Mage_Core_Model_Config_Element')
			);

		$config = $this->getModelMock('core/config', array('getSection'));
		$config->loadString('<config />');
		$config->setNode('sections/payment/groups/allowed_group/sort_order', 1);
		$config->setNode('sections/payment/groups/disallowed_group/sort_order', 2);
		$config->setNode('sections/payment/groups/pbridge_eb2cpayment_cc/sort_order', 3);
		$config->expects($this->any())
			->method('getSection')
			->will($this->returnValue($config->getNode('sections/payment')));
		$this->_reflectProperty($testModel, '_configFields')
			->setValue($testModel, $config);

		$testModel->initForm();
	}
}
