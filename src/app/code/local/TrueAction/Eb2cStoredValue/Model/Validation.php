<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cStoredValue_Model_Validation
{
	/**
	 * @var null|Mage_Sales_Model_Resource_Order_Collection
	 */
	protected $_customerOrders = null;

	/**
	 * @var null|Mage_Sales_Model_Resource_Order_Collection
	 */
	protected $_customerOrdersEmail = null;

	/**
	 * @return bool
	 */
	public function isValid()
	{
		return true;
	}

	/**
	 * Retrieve the current session
	 *
	 * @return Mage_Adminhtml_Model_Session_Quote|Mage_Customer_Model_Session
	 */
	protected function _getSession()
	{
		if (Mage::app()->getStore()->isAdmin()) {
			/* @var $session Mage_Adminhtml_Model_Session_Quote */
			$session = Mage::getSingleton('adminhtml/session_quote');
		} else {
			/* @var $session Mage_Customer_Model_Session */
			$session = Mage::getSingleton('customer/session');
		}

		return $session;
	}

	/**
	 * Retrieve the current customer
	 *
	 * @return Mage_Customer_Model_Customer
	 */
	protected function _getCustomer()
	{
		return $this->_getSession()->getCustomer();
	}

	/**
	 * Retrieve the customer group id of the current customer
	 *
	 * @return int
	 */
	protected function _getCustomerGroupId()
	{
		$customerGroupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
		if (Mage::app()->getStore()->isAdmin()) {
			$customerGroupId = $this->_getSession()->getQuote()->getCustomerGroupId();
		} else {
			if ($this->_getSession()->isLoggedIn()) {
				$customerGroupId = $this->_getSession()->getCustomerGroupId();
			}
		}

		return $customerGroupId;
	}

	/**
	 * Retrieve the email address of the current customer
	 *
	 * @return string
	 */
	protected function _getCustomerEmail()
	{
		if (Mage::app()->getStore()->isAdmin()) {
			$email = $this->_getCustomer()->getEmail();
		} else {
			if ($this->_getSession()->isLoggedIn()) {
				$email = $this->_getCustomer()->getEmail();
			} else {
				/* @var $quote Mage_Sales_Model_Quote */
				$quote = Mage::getSingleton('checkout/session')->getQuote();
				$email = $quote->getBillingAddress()->getEmail();
			}
		}

		return $email;
	}

	/**
	 * Retrieve the order collection of a specific customer
	 *
	 * @param  int $customerId
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 */
	protected function _getCustomerOrders($customerId)
	{
		if (null === $this->_customerOrders) {
			$orders = Mage::getResourceModel('sales/order_collection')
				->addAttributeToSelect('*')
				->addAttributeToFilter('customer_id', $customerId)
				->addAttributeToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
				->addAttributeToFilter(
					'state',
					array(
						'in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()
					)
				)
				->load();
			$this->_customerOrders = $orders;
		}

		return $this->_customerOrders;
	}
}
