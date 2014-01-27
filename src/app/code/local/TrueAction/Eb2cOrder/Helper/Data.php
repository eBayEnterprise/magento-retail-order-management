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
}
