<?php
class TrueAction_Eb2cOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Gets a combined configuration model from core and order
	 *
	 * @return TrueAction_Eb2cCore_Config_Registry
	 */
	public function getConfig()
	{
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2corder/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * Instantiate and save constants-values helper
	 *
	 * @return TrueAction_Eb2cOrder_Helper_Constants
	 */
	public function getConstHelper()
	{
		return Mage::helper('eb2corder/constants');
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		$consts = $this->getConstHelper();
		return Mage::helper('eb2ccore')->getApiUri($consts::SERVICE, $operation);
	}

	public function getOrderHistoryUrl($order)
	{
		return Mage::getUrl('sales/order/view', array('_store' => $order->getStoreId(), 'order_id' => $order->getId()));
	}

	/**
	 * eb2c order status to magento status and state
	 * @return array
	 */
	public function eb2cOrderStatusToMageOrderState()
	{
		return array(
			array('ebc_status' => 'Draft Order Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Pre-Sell Line Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'OGC Line Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Warranty Line Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'OrderLine Invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Pre-Fulfilled Line Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Reserved Awaiting Acceptance', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Reserved', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Being Negotiated', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Accepted', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Backordered', 'mage_state' => Mage_Sales_Model_Order::STATE_HOLDED),
			array('ebc_status' => 'Unexpected BackOrder', 'mage_state' => Mage_Sales_Model_Order::STATE_HOLDED),
			array('ebc_status' => 'Await Scheduling', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Companion BackOrder', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Accessory BackOrder', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Unscheduled', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Scheduled', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Awaiting Chained Order Creation', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Chained Order Created', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Released', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Included In Shipment', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Shipped', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Ready For Pickup', 'mage_state' => Mage_Sales_Model_Order::STATE_HOLDED),
			array('ebc_status' => 'Pickup Complete', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Pending Pickup Cancel', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'PO Shipped', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'GC Shipped', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'GC Activated', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Warranty Line Processed', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'OGC Activated', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Fulfilled and Invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Return Created', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'In-store Return Created', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Dummy Return Created', 'mage_state' => Mage_Sales_Model_Order::STATE_NEW),
			array('ebc_status' => 'Return Received', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'STS Order Cancelled', 'mage_state' => Mage_Sales_Model_Order::STATE_CANCELED),
			array('ebc_status' => 'ISPU Order Cancelled', 'mage_state' => Mage_Sales_Model_Order::STATE_CANCELED),
			array('ebc_status' => 'OrderLine Invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'PO Shipped higher than fulfilled and invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'GC Activated higher than fulfilled and invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'GC Shipped higher than fulfilled and invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'OGC Shipped higher than fulfilled and invoiced', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Unreceived', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Received', 'mage_state' => Mage_Sales_Model_Order::STATE_COMPLETE),
			array('ebc_status' => 'Receipt Closed', 'mage_state' => Mage_Sales_Model_Order::STATE_PROCESSING),
			array('ebc_status' => 'Cancelled', 'mage_state' => Mage_Sales_Model_Order::STATE_CANCELED),
		);
	}

	/**
	 * retrieve a magento status with a given eb2c status
	 * @param string $ebcStatus, the eb2c status to get the mapped magento order status
	 * @return string, the magento mapped status
	 */
	public function mapEb2cOrderStatusToMage($ebcStatus)
	{
		$maps = $this->eb2cOrderStatusToMageOrderState();
		$cmpStatus = strtoupper($ebcStatus);
		foreach ($maps as $map) {
			if (strtoupper($map['ebc_status']) === $cmpStatus) {
				return $map['mage_state'];
			}
		}
		return Mage_Sales_Model_Order::STATE_NEW;
	}
}
