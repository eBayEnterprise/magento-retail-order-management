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
		parent::__construct();
		$this->setTemplate('sales/order/history.phtml');

		$orders = Mage::getResourceModel('sales/order_collection')
			->addFieldToSelect('*')
			->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
			->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
			->setOrder('created_at', 'desc');
		$newOrders = new Varien_Data_Collection();
		foreach($orders as $order) {
			$newOrders->addItem(new Varien_Object(array(
				'real_order_id' => $order->getRealOrderId(),
				'created_at_store_date' => $order->getCreatedAtStoreDate(),
				'shipping_address' => $order->getShippingAddress(),
				'grand_total' => $order->getGrandTotal(),
				'formatPrice' => $order->formatPrice($order->getGrandTotal()),
				'status_label' => 'wrong',
			)));
		}
		$this->setOrders($newOrders);

		Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('root')->setHeaderTitle(Mage::helper('sales')->__('My Orders'));
	}
}
