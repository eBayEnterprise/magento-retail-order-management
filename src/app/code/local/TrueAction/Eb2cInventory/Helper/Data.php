<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_operation;

	public function __construct()
	{
		$cfg = $this->getConfigModel(null);
		$this->_operation = array(
			'check_quantity' => array(
				'pro' => $cfg->apiOptInventoryQty,
				'dev' => $cfg->quantityApiUri
			),
			'get_inventory_details' => array(
				'pro' => $cfg->apiOptInventoryDetails,
				'dev' => $cfg->inventoryDetailUri
			),
			'allocate_inventory' => array(
				'pro' => $cfg->apiOptInventoryAllocation,
				'dev' => $cfg->allocationUri
			),
			'rollback_allocation' => array(
				'pro' => $cfg->apiOptInventoryRollbackAllocation,
				'dev' => $cfg->rollbackAllocationUri
			)
		);
	}

	/**
	 * Get inventory config instantiated object.
	 *
	 * @return TrueAction_Eb2cInventory_Model_Config
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getModel('eb2cinventory/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
	}

	/**
	 * Getting the NS constant value
	 *
	 * @return string, the ns value
	 */
	public function getXmlNs()
	{
		$cfg = $this->getConfigModel(null);
		return $cfg->apiXmlNs;
	}

	/**
	 * Generate eb2c API operation Uri from configuration settings and constants
	 * @param string $optIndex, the operation index of the associative array
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($optIndex)
	{
		$operation = '';
		if (isset($this->_operation[$optIndex])) {
			$operation = $this->_operation[$optIndex];
		}
		$cfg = $this->getConfigModel(null);
		$apiUri = Mage::helper('eb2ccore')->getApiUri(
			$cfg->apiService,
			$operation['pro']
		);
		return $apiUri;
	}

	/**
	 * Generate eb2c API Universally unique ID used to globally identify to request.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 *
	 * @return string, the request id
	 */
	public function getRequestId($entityId)
	{
		$cfg = $this->getConfigModel(null);
		return implode('-', array($cfg->clientId, $cfg->storeId, $entityId));
	}

	/**
	 * Generate eb2c API Universally unique ID to represent the reservation.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 *
	 * @return string, the reservation id
	 */
	public function getReservationId($entityId)
	{
		$cfg = $this->getConfigModel(null);
		return implode('-', array($cfg->clientId, $cfg->storeId, $entityId));
	}
}
