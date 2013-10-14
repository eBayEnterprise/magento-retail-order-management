<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

class TrueAction_Eb2cOrder_Overrides_Block_Order_Recent extends Mage_Sales_Block_Order_Recent
{
	/**
	 * overriding order recent to fetch order summary from eb2c webservice to present to customer the order status
	 */
	 public function __construct()
	{
		parent::__construct();
		// assigned current session customer id to a local variable
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		// instantiate eb2c customer order search class
		$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');

		// making eb2c customer order search request base on current session customer id and then
		// parse result in a collection of varien object
		$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary($customerId));

		$newCollection = new Varien_Data_Collection();
		foreach ($this->getOrders() as $order) {
			// get order id
			$orderId = $order->getRealOrderId();

			// get eb2c order data
			$ebcData = (isset($orderHistorySearchResults[$orderId]))? $orderHistorySearchResults[$orderId] : null;
			$data = $order->getData();
			if ($ebcData instanceof Varien_Object) {
				$data['status'] = Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($ebcData->getStatus());
			}
			$order->setData($data);
			$newCollection->addItem($order);
		}
		$this->setOrders($newCollection);
	}
}
