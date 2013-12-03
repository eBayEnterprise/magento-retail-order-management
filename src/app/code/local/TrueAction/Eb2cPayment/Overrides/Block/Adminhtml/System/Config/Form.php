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
	 * @var $section Varien_Simplexml_Element
	 * @var $group Varien_Simplexml_Element
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
		$allowedGroups = Mage::getModel('eb2cpayment/suppression')
			->getAllowedPaymentConfigGroups();

		foreach ($sections as $section) {
			if (!$this->_canShowField($section)) {
				continue;
			}
			foreach ($section->groups as $groups){
				$groups = (array) $groups;
				usort($groups, array($this, '_sortForm'));

				foreach ($groups as $group){
					if (!$this->_canShowField($group)) {
						continue;
					}

					if ($section->getName() === 'payment') {
						if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
							if (!in_array($group->getName(), $allowedGroups)) {
								continue;
							}
						} else {
							// don't allow eBay Enterprise payment method config if eb2cpayment is disabled
							if ($group->getName() === 'pbridge_eb2cpayment_cc') {
								// disabled dependable payment bridge methods
								continue;
							}
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
