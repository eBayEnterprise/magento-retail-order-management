<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_AccountController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Retrieve customer session object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * preDispatch
	 *
	 * @return void
	 */
	public function preDispatch()
	{
		parent::preDispatch();

		if (!Mage::getSingleton('customer/session')->authenticate($this)) {
			$this->setFlag('', 'no-dispatch', true);
		}
	}

	/**
	 * editAction
	 *
	 * @return void
	 */
	public function editAction()
	{
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle($this->__('storedvalue Account Data'));
		$this->renderLayout();
	}

	/**
	 * saveAction
	 *
	 * @throws Mage_Core_Exception
	 * @throws Exception
	 * @return void
	 */
	public function saveAction()
	{
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if (!$customer) {
			return;
		}

		$now = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
		$customer->setData('eb_sv_payment_account_update', $now);

		if ($accountPan = $this->getRequest()->getPost('account_pan')) {
			$customer->setData('eb_sv_payment_account_pan', $accountPan);
		}
		if ($accountPin = $this->getRequest()->getPost('account_pin')) {
			$customer->setData('eb_sv_payment_account_pin', $accountPin);
		}
		if ($accountAction = $this->getRequest()->getPost('account_ation')) {
			$customer->setData('eb_sv_payment_account_ation', $accountAction);
		}

		try {
			$customer->save();
			$this->_getSession()->setCustomer($customer)
				->addSuccess($this->__('Stored Value account information was successfully saved'));
			$this->_redirect('customer/account');

			return;
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
				->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
				->addException($e, $this->__('Can\'t save customer'));
		}
	}
}
