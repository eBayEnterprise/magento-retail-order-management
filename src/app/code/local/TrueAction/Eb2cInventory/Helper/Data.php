<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $coreHelper;
	public $coreFeed;
	public $constantHelper;
	public $configModel;
	public $apiModel;
	protected $_operation;

	public function __construct()
	{
		$this->getCoreHelper();
		$this->getConfigModel(null);
		$constantHelper = $this->getConstantHelper();
		$this->getCoreFeed();
		$this->_operation = array(
			'check_quantity' => array(
				'pro' => $constantHelper::OPT_QTY,
				'dev' => $this->getConfigModel()->quantityApiUri
			),
			'get_inventory_details' => array(
				'pro' => $constantHelper::OPT_INV_DETAILS,
				'dev' => $this->getConfigModel()->inventoryDetailUri
			),
			'allocate_inventory' => array(
				'pro' => $constantHelper::OPT_ALLOCATION,
				'dev' => $this->getConfigModel()->allocationUri
			),
			'rollback_allocation' => array(
				'pro' => $constantHelper::OPT_ROLLBACK_ALLOCATION,
				'dev' => $this->getConfigModel()->rollbackAllocationUri
			)
		);
	}

	/**
	 * Get core helper instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Helper_Data
	 */
	public function getCoreHelper()
	{
		if (!$this->coreHelper) {
			$this->coreHelper = Mage::helper('eb2ccore');
		}
		return $this->coreHelper;
	}

	/**
	 * Get inventory config instantiated object.
	 *
	 * @return TrueAction_Eb2cInventory_Model_Config
	 */
	public function getConfigModel($store=null)
	{
		$this->configModel = Mage::getModel('eb2ccore/config_registry');
		$this->configModel->setStore($store)
			->addConfigModel(Mage::getModel('eb2cinventory/config'))
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		return $this->configModel;
	}

	/**
	 * Get Constants helper instantiated object.
	 *
	 * @return TrueAction_Eb2cInventory_Helper_Constants
	 */
	public function getConstantHelper()
	{
		if (!$this->constantHelper) {
			$this->constantHelper = Mage::helper('eb2cinventory/constants');
		}
		return $this->constantHelper;
	}

	/**
	 * Get core helper feed instantiated object.
	 *
	 * @return TrueAction_Eb2cCore_Helper_Feed
	 */
	public function getCoreFeed()
	{
		$this->coreFeed = Mage::helper('eb2ccore/feed');
		return $this->coreFeed;
	}

	/**
	 * Getting the NS constant value
	 *
	 * @return string, the ns value
	 */
	public function getXmlNs()
	{
		$constantHelper = $this->getConstantHelper();
		return $constantHelper::XMLNS;
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
		$constantHelper = $this->getConstantHelper();
		$apiUri = $operation['dev'];
		if (!(bool) $this->getConfigModel()->developerMode) {
			$apiUri = $this->getCoreHelper()->getApiUri(
				$constantHelper::SERVICE,
				$operation['pro']
			);
		}
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
		return implode('-', array(
			$this->getConfigModel()->clientId,
			$this->getConfigModel()->storeId,
			$entityId
		));
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
		return implode('-', array(
			$this->getConfigModel()->clientId,
			$this->getConfigModel()->storeId,
			$entityId
		));
	}

	/**
	 * Return the Core API model for issuing requests/ retrieving response:
	 */
	public function getApiModel()
	{
		if( !$this->apiModel ) {
			$this->apiModel = Mage::getModel('eb2ccore/api');
		}
		return $this->apiModel;
	}

	/**
	 * validating ftp settings by simply checking if there's actual setting data.
	 *
	 * @return bool, true valid ftp settings otherwise false
	 */
	public function isValidFtpSettings()
	{
		$cfg = $this->getConfigModel();
		return trim($cfg->sftpLocation) && trim($cfg->sftpUsername) && trim($cfg->sftpPassword);
	}
}
