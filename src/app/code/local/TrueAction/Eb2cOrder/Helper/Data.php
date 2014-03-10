<?php
class TrueAction_Eb2cOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Gets a combined configuration model from core and order
	 * @return TrueAction_Eb2cCore_Config_Registry
	 */
	public function getConfig()
	{
		return Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2corder/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 * @param string $operation, the operation type (create, cancel)
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		return Mage::helper('eb2ccore')->getApiUri($this->getConfig()->apiService, $operation);
	}

	/**
	 * retrieve order history url
	 * @param Mage_Sales_Model_Order $order, the order object to get the url from
	 * @return string, the url
	 */
	public function getOrderHistoryUrl($order)
	{
		return Mage::getUrl('sales/order/view', array('_store' => $order->getStoreId(), 'order_id' => $order->getId()));
	}

	/**
	 * Retrieves the Magento State mapping to the Eb2c Status passed in eb2cLabelIn
	 * @param eb2cLabelIn - and Eb2c Status Message
	 * @return string mapped state
	 */
	public function mapEb2cOrderStatusToMage($eb2cLabelIn)
	{
			$mageState =  Mage::getModel('sales/order_status')
					->getCollection()
					->joinStates()
					->addFieldToFilter('label', array('eq'=>$eb2cLabelIn))
					->getFirstItem()
					->getState();

			if (!empty($mageState)) {
					return $mageState;
			}
			return Mage_Sales_Model_Order::STATE_NEW;
	}
	/**
	 * Retrieve a collection of orders for order history and recent orders blocks based on the current customer in session.
	 * Since these are manually constructed from the Eb2c response, we don't use a real Mage_Sales_Model_Resource_Order_Collection.
	 *
	 * @return Varien_Data_Collection
	 */
	public function getCurCustomerOrders()
	{
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
		$orderSearchObj = Mage::getModel('eb2corder/customer_order_search');
		$cfg = Mage::getModel('eb2ccore/config_registry')->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		// making eb2c customer order search request base on current session customer id and then
		// parse result in a collection of varien object
		$orderHistorySearchResults = $orderSearchObj->parseResponse(
			$orderSearchObj->requestOrderSummary($cfg->clientCustomerIdPrefix . $customerId)
		);
		$orders = new Varien_Data_Collection();
		$tempId = 0;
		foreach ($orderHistorySearchResults as $orderId => $ebcData) {
			$mgtOrder = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			$gmtShippingAddress = $mgtOrder->getShippingAddress();
			$orders->addItem(
				Mage::getModel('sales/order')->addData(array(
					'created_at' => $ebcData->getOrderDate(),
					'entity_id' => $mgtOrder->getId() ?: 'ebc-' . ++$tempId,
					'exist_in_mage' => (bool) $mgtOrder->getId(), // We will never encounter an order id of 0 or "0".
					'grand_total' => $ebcData->getOrderTotal(),
					'real_order_id' => $orderId,
					'status' => Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($ebcData->getStatus()),
				))->addAddress(Mage::getModel('sales/order_address')->setData(array(
					'address_type' => 'shipping',
					'name' => $gmtShippingAddress ? $gmtShippingAddress->getName() : '',
				)))
			);
		}
		return $orders;
	}
}
