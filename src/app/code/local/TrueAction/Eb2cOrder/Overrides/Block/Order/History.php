<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

class TrueAction_Eb2cOrder_Overrides_Block_Order_History extends Mage_Sales_Block_Order_History
{
	/**
	 * overriding order history to fetch order summary from eb2c webservice to present to customer the order status
	 */
	public function __construct()
	{
        $this->setTemplate('eb2corder_frontend/order/history.phtml');
		// assigned current session customer id to a local variable
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		// instantiate eb2c customer order search class
		$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');

		// making eb2c customer order search request base on current session customer id and then
		// parse result in a collection of varien object
		$orderHistorySearchResults = $orderSearchObj->parseResponse($orderSearchObj->requestOrderSummary($customerId));

		$newCollection = new Varien_Data_Collection();

		$tempId = 1;
		foreach ($orderHistorySearchResults as $orderId => $ebcData) {
			$mgtOrder = Mage::getModel('sales/order')->loadByIncrementId($ebcData->getCustomerOrderId());
			$gmtShippingAddress = $mgtOrder->getShippingAddress();
			$shipToName = ($gmtShippingAddress)? $gmtShippingAddress->getName() : '';

			$order = Mage::getModel('sales/order');
			$order->addData(array(
				'entity_id' => ($mgtOrder->getId())? $mgtOrder->getId() : 'ebc-' . $tempId,
				'real_order_id' => $ebcData->getCustomerOrderId(),
				'created_at' => $ebcData->getOrderDate(),
				'status' => Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($ebcData->getStatus()),
				'grand_total' => $ebcData->getOrderTotal(),
				'exist_in_mage' => ($mgtOrder->getId())? true : false,
			));
			$shippingAddress = Mage::getModel('sales/order_address');
			$shippingAddress->setData(array('name' => $$shipToName, 'address_type' => 'shipping'));
			$order->addAddress($shippingAddress);

			$newCollection->addItem($order);
			$tempId++;
		}

		$this->setOrders($newCollection);
	}
}
