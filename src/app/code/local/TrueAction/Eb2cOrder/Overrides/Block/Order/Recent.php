<?php
class TrueAction_Eb2cOrder_Overrides_Block_Order_Recent extends Mage_Sales_Block_Order_Recent
{
	const TEMPLATE = 'eb2corder_frontend/order/recent.phtml';
	/**
	 * Replace internal orders with orders fetched from Eb2c.
	 */
	public function __construct()
	{
		$this->setTemplate(static::TEMPLATE);
		$this->setOrders(Mage::helper('eb2corder')->getCurCustomerOrders());
	}
}
