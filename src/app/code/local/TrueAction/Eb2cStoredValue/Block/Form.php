<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Block_Form extends Mage_Payment_Block_Form
{
	/**
	 * Construct payment form block and set template
	 *
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('eb2cstoredvalue/form.phtml');
	}

	/**
	 * Returns the pan code of the specific account
	 *
	 * @return string
	 */
	public function getAccountPan()
	{
		return $this->_getAccountData('eb_sv_payment_account_pan');
	}

	/**
	 * Returns the pin of the specific account
	 *
	 * @return string
	 */
	public function getAccountPin()
	{
		return $this->_getAccountData('eb_sv_payment_account_pin');
	}

	/**
	 * Returns the action code of the specific account
	 *
	 * @return string
	 */
	public function getAccountAction()
	{
		return $this->_getAccountData('eb_sv_payment_account_action');
	}

	/**
	 * Returns the specific value of the requested field from the
	 * customer model.
	 *
	 * @param  string $field Attribute to get
	 * @return string Data
	 */
	protected function _getAccountData($field)
	{
		if (!Mage::getStoreConfigFlag('payment/eb2cstoredvalue/save_account_data')) {
			return '';
		}
		$data = $this->getCustomer()->getData($field);
		if (strlen($data) === 0) {
			return '';
		}

		return $this->escapeHtml($data);
	}

	/**
	 * Returns the current customer
	 *
	 * @return Mage_Customer_Model_Customer Customer
	 */
	public function getCustomer()
	{
		if (Mage::app()->getStore()->isAdmin()) {
			return Mage::getSingleton('adminhtml/session_quote')->getCustomer();
		}

		return Mage::getSingleton('customer/session')->getCustomer();
	}
}
