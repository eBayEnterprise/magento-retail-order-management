<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

class TrueAction_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
	/**
	 * overriding this form in order to disabled non-eb2c payment methods
	 * Enter description here...
	 * @return Mage_Adminhtml_Block_System_Config_Form
	 */
	public function initForm()
	{
		$this->_initObjects();

		$form = new Varien_Data_Form();

		$sections = $this->_configFields->getSection(
			$this->getSectionCode(),
			$this->getWebsiteCode(),
			$this->getStoreCode()
		);
		if (empty($sections)) {
			$sections = array();
		}

		foreach ($sections as $section) {
			/* @var $section Varien_Simplexml_Element */
			if (!$this->_canShowField($section)) {
				continue;
			}
			foreach ($section->groups as $groups){
				$groups = (array)$groups;
				usort($groups, array($this, '_sortForm'));

				foreach ($groups as $group){
					/* @var $group Varien_Simplexml_Element */
					if (!$this->_canShowField($group)) {
						continue;
					}
					if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled && strtoupper(trim($sections->label)) === 'PAYMENT METHODS') {
						if (strtoupper(trim($sections->label)) === 'PAYMENT METHODS' && $group->getAttribute('module') !== 'enterprise_pbridge') {
							continue;
						} elseif (strtoupper(trim($sections->label)) === 'PAYMENT METHODS' && strtoupper(trim($group->label)) !== 'PAYMENT BRIDGE') {
							// disabled dependable payment bridge methods
							continue;
						}
					}

					$this->_initGroup($form, $group, $section);
				}
			}
		}

		$this->setForm($form);
		return $this;
	}
}
