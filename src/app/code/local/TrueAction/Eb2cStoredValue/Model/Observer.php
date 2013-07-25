<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Model_Observer
{
	/**
	 * paymentMethodIsActive
	 *
	 * Checks if storedvaluePayment is allowed for specific customer groups and if a
	 * registered customer has the required minimum amount of orders to be
	 * allowed to order via storedvaluePayment.
	 *
	 * @magentoEvent payment_method_is_active
	 * @param  Varien_Event_Observer $observer Observer
	 * @return void
	 */
	public function paymentMethodIsActive($observer)
	{
		$methodInstance = $observer->getEvent()->getMethodInstance();

		// Check if method is storedvaluePayment
		if ($methodInstance->getCode() != 'eb2cstoredvalue') {
			return;
		}

		// Check if payment method is active
		if (!Mage::getStoreConfigFlag('payment/eb2cstoredvalue/active')) {
			return;
		}

		/* @var $validationModel TrueAction_Eb2cStoredValue_Model_Validation */
		$validationModel = Mage::getModel('eb2cstoredvalue/validation');

		$observer->getEvent()->getResult()->isAvailable = $validationModel->isValid();
	}

	/**
	 * Saves the account data after a successful order in the specific
	 * customer model.
	 *
	 * @magentoEvent sales_order_save_after
	 * @param  Varien_Event_Observer $observer Observer
	 * @return void
	 */
	public function saveAccountInfo($observer)
	{
		$order = $observer->getEvent()->getOrder();
		$methodInstance = $order->getPayment()->getMethodInstance();

		if ($methodInstance->getCode() !== 'eb2cstoredvalue') {
			return;
		}

		$quote = $order->getQuote();

		$newMethodInstance = $quote->getPayment()->getMethodInstance();


		Mage::log("Inside Observer - saveAccountInfo method\n\rPan = " . $newMethodInstance->getAccountPan() . "\n\rPin = " . $newMethodInstance->getAccountPin(), Zend_Log::ERR);


		if (!$methodInstance->getConfigData('save_account_data')) {
			return;
		}
		if ($customer = $this->_getOrderCustomer($order)) {
			$customer->setData('eb_sv_payment_account_update', now())
				->setData('eb_sv_payment_account_pan', $methodInstance->getAccountPan())
				->setData('eb_sv_payment_account_pin', $methodInstance->getAccountPin())
				->setData('eb_sv_payment_account_action', $methodInstance->getAccountAction())
				->save();
		}
	}

	/**
	 * Checks the current order and returns the customer model
	 *
	 * @param  Mage_Sales_Model_Order            $order Current order
	 * @return Mage_Customer_Model_Customer|null Customer model or null
	 */
	protected function _getOrderCustomer($order)
	{
		if ($customer = $order->getCustomer()) {
			if ($customer->getId()) {
				return $customer;
			}
		}

		return false;
	}
}
