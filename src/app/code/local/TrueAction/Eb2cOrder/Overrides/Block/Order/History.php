<?php
class TrueAction_Eb2cOrder_Overrides_Block_Order_History extends Mage_Sales_Block_Order_History
{
	const TEMPLATE = 'eb2corder_frontend/order/history.phtml';
	/**
	 * Replace internal orders with orders fetched from Eb2c.
	 */
	public function __construct()
	{
		$this->setTemplate(static::TEMPLATE);
		$this->setOrders(Mage::helper('eb2corder')->getCurCustomerOrders());
	}
}
