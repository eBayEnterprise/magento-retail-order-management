<?php

class TrueAction_Eb2cOrder_Overrides_Block_Order_View extends Mage_Sales_Block_Order_View
{
	/**
	 * overriding order recent to fetch order summary from eb2c webservice to present to customer the order status
	 */
	protected function _prepareLayout()
	{
		// assigned current session customer id to a local variable
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		// instantiate eb2c customer order search class
		$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');

		// making eb2c customer order search request base on current session customer id and then
		// parse result in a collection of varien object
		$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary($customerId));

		// get order id
		$orderId = $this->getOrder()->getRealOrderId();

		// get eb2c order data
		$ebcData = (isset($orderHistorySearchResults[$orderId]))? $orderHistorySearchResults[$orderId] : null;
		if ($ebcData instanceof Varien_Object) {
			$this->getOrder()->addData(array(
				'status' => Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($ebcData->getStatus()),
				'created_at' => $ebcData->getOrderDate(),
				'grand_total' => $ebcData->getOrderTotal(),
			));
		}

		parent::_prepareLayout();
	}
}
