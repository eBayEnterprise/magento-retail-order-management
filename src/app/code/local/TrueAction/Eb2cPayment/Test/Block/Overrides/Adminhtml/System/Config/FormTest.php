<?php
class TrueAction_Eb2cPayment_Test_Block_Overrides_Adminhtml_System_Config_FormTest
	extends TrueAction_Eb2cCore_Test_Base
{
	public function tearDown()
	{
		parent::tearDown();
		Mage::unregister('_helper/eb2cpayment');
		Mage::unregister('_helper/Core');
	}

	/**
	 * verify when $field is not suppressed, the result of the parent _canShowField is returned
	 */
	public function testCanShowFieldUnsuppressed()
	{
		$cfg = Mage::getModel('core/config');
		$cfg->loadString('<config/>');
		$cfg->setNode('sections/payment/groups/allowed_group/if_module_enabled', '1');
		$cfg->setNode('sections/payment/groups/allowed_group/show_in_default', '1');
		$testModel = $this->getBlockMock('adminhtml/system_config_form', array('getScope'));
		$testModel->expects($this->once())
			->method('getScope')
			->will($this->returnValue(Mage_Adminhtml_Block_System_Config_Form::SCOPE_DEFAULT));
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form',
			$testModel,
			'message'
		);
		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'isConfigSuppressed',
		));
		$suppression->expects($this->once())
			->method('isConfigSuppressed')
			->with(
				$this->identicalTo('payment'),
				$this->identicalTo('allowed_group')
			)
			->will($this->returnValue(false));
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);
		$mageHelper = $this->getHelperMock('core/data', array('isModuleEnabled'));
		$mageHelper->expects($this->once())
			->method('isModuleEnabled')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'Core', $mageHelper);
		$this->assertInstanceOf('Varien_Simplexml_Element', $cfg->getNode('sections/payment/groups/allowed_group'));
		$result = $this->_reflectMethod($testModel, '_canShowField')->invoke(
			$testModel,
			$cfg->getNode('sections/payment/groups/allowed_group')
		);
		$this->assertTrue((bool) $result);
	}

	/**
	 * verify when $field is suppressed, the result is false
	 */
	public function testCanShowFieldSuppressed()
	{
		$cfg = Mage::getModel('core/config');
		$cfg->loadString('<config/>');
		$cfg->setNode('sections/payment/groups/disallowed_group/if_module_enabled', '1');
		$cfg->setNode('sections/payment/groups/disallowed_group/show_in_default', '1');
		$testModel = $this->getBlockMock('adminhtml/system_config_form', array('getScope'));
		$testModel->expects($this->never())
			->method('getScope');
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form',
			$testModel,
			'message'
		);
		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'isConfigSuppressed',
		));
		$suppression->expects($this->once())
			->method('isConfigSuppressed')
			->with(
				$this->identicalTo('payment'),
				$this->identicalTo('disallowed_group')
			)
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);
		$this->assertInstanceOf('Varien_Simplexml_Element', $cfg->getNode('sections/payment/groups/disallowed_group'));
		$result = $this->_reflectMethod($testModel, '_canShowField')->invoke(
			$testModel,
			$cfg->getNode('sections/payment/groups/disallowed_group')
		);
		$this->assertFalse((bool) $result);
	}

	/**
	 * verify when $field is not a valid group node, the result of the parent is returned
	 */
	public function testCanShowFieldInvalidGroup()
	{
		$cfg = Mage::getModel('core/config');
		$cfg->loadString('<config/>');
		$cfg->setNode('sections/payment/if_module_enabled', '1');
		$cfg->setNode('sections/payment/show_in_default', '1');
		$cfg->setNode('sections/payment/groups/some_group/invalid_group/if_module_enabled', '1');
		$cfg->setNode('sections/payment/groups/some_group/invalid_group/show_in_default', '1');
		$testModel = $this->getBlockMock('adminhtml/system_config_form', array('getScope'));
		$testModel->expects($this->any())
			->method('getScope')
			->will($this->returnValue(Mage_Adminhtml_Block_System_Config_Form::SCOPE_DEFAULT));
		$this->assertInstanceOf(
			'TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form',
			$testModel,
			'message'
		);
		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'isConfigSuppressed',
		));
		$suppression->expects($this->never())
			->method('isConfigSuppressed');
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);
		$mageHelper = $this->getHelperMock('core/data', array('isModuleEnabled'));
		$mageHelper->expects($this->any())
			->method('isModuleEnabled')
			->will($this->returnValue(true));
		$this->replaceByMock('helper', 'Core', $mageHelper);

		$result = $this->_reflectMethod($testModel, '_canShowField')->invoke(
			$testModel,
			$cfg->getNode('sections/payment')
		);
		$this->assertTrue((bool) $result);

		$result = $this->_reflectMethod($testModel, '_canShowField')->invoke(
			$testModel,
			$cfg->getNode('sections/payment/groups/some_group/invalid_group')
		);
		$this->assertTrue((bool) $result);
	}
}
