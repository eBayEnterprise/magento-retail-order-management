<?php
class TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
	/**
	 * Checking field visibility
	 *
	 * @param   Varien_Simplexml_Element $field
	 * @return  bool
	 */
	protected function _canShowField($field)
	{
		$suppression = Mage::getModel('eb2cpayment/suppression');
		$section = $field->xpath('../../groups/..');
		$sectionParent = isset($section[0]) ? $section[0]->xpath('..') : null;
		return !(
			isset($sectionParent[0]) &&
			$sectionParent[0]->getName() === 'sections' &&
			$suppression->isConfigSuppressed($section[0]->getName(), $field->getName())
		) &&
		parent::_canShowField($field);
	}
}
