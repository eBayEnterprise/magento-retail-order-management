<?php
 /**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Feed_Item_Inventories extends Mage_Core_Model_Abstract
{
	protected $_stockItem;
	protected $_product;
	protected $_stockStatus;
	protected $_feedModel;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->_stockItem = $this->_getStockItem();
		$this->_product = $this->_getProduct();
		$this->_stockStatus = $this->_getStockStatus();

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}
		$this->_feedModel = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);

		return $this;
	}

	/**
	 * Get cataloginventory/stock_item instantiated object.
	 *
	 * @return cataloginventory/stock_item
	 */
	protected function _getStockItem()
	{
		if (!$this->_stockItem) {
			$this->_stockItem = Mage::getModel('cataloginventory/stock_item');
		}
		return $this->_stockItem;
	}

	/**
	 * Get catalog/product instantiated object.
	 *
	 * @return catalog/product
	 */
	protected function _getProduct()
	{
		if (!$this->_product) {
			$this->_product = Mage::getModel('catalog/product');
		}
		return $this->_product;
	}

	/**
	 * Get cataloginventory/stock_status instantiated object.
	 *
	 * @return cataloginventory/stock_status
	 */
	protected function _getStockStatus()
	{
		if (!$this->_stockStatus) {
			$this->_stockStatus = Mage::getSingleton('cataloginventory/stock_status');
		}
		return $this->_stockStatus;
	}

	/**
	 * Get the item inventory feed from eb2c.
	 */
	protected function _getItemInventoriesFeeds()
	{
		$cfg = Mage::helper('eb2cinventory')->getConfigModel();
		$remoteFile = $cfg->feedRemoteReceivedPath;
		$configPath = $cfg->configPath;
		$feedHelper = Mage::helper('eb2ccore/feed');

		// Download feed from eb2c server to local server
		Mage::helper('filetransfer')->getFile(
			$this->_feedModel->getInboundDir(),
			$remoteFile,
			$feedHelper::FILETRANSFER_CONFIG_PATH
		);
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$this->_getItemInventoriesFeeds();
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		foreach ($this->_feedModel->lsInboundDir() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = Mage::helper('eb2cinventory')->getConfigModel()->feedEventType;
			$expectHeaderVersion = Mage::helper('eb2cinventory')->getConfigModel()->feedHeaderVersion;

			// validate feed header
			if (Mage::helper('eb2cinventory')->getCoreFeed()->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// run inventory updates
				$this->_inventoryUpdates($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all ok
				$this->_feedModel->mvToArchiveDir($feed);
			}
			// If this had failed, we could do this: $this->_feedModel->mvToErrorDir($feed);
		}

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * update cataloginventory/stock_item with eb2c feed data.
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _inventoryUpdates($doc)
	{
		$feedXpath = new DOMXPath($doc);

		$inventories = $feedXpath->query('//Inventory');
		foreach ($inventories as $inventory) {
			$gsiClientId = $inventory->getAttribute('gsi_client_id');
			$clientItemId = $feedXpath->query('//Inventory[@gsi_client_id="' . $gsiClientId . '"]/ItemId/ClientItemId');
			$availableQuantity = $feedXpath->query('//Inventory[@gsi_client_id="' . $gsiClientId . '"]/Measurements/AvailableQuantity');

			$sku = '';
			if ($clientItemId->length) {
				$sku = trim($clientItemId->item(0)->nodeValue);
			}

			$qty = 0;
			if ($availableQuantity->length) {
				$qty = (int) $availableQuantity->item(0)->nodeValue;
			}

			if ($sku !== '') {
				// we have a valid item, let's get the product id
				$this->_getProduct()->loadByAttribute('sku', $sku);

				if ($this->_getProduct()->getId()) {
					// we've gotten a valid magento product, let's update its stock
					$this->_getStockItem()->loadByProduct($this->_getProduct()->getId())
						->setQty($qty)
						->save();
				} else {
					// This item doesn't exists in the Magento App, just logged it as a warning
					Mage::log('[' . __CLASS__ . '] ' . "Item Inventories Feed SKU (${sku}), doesn't exists in Magento", Zend_Log::WARN);
				}
			}
		}
	}

	/**
	 * clear magento cache and rebuild inventory status.
	 *
	 * @return void
	 */
	protected function _clean()
	{
		try {
			// STOCK STATUS
			$this->_getStockStatus()->rebuild();

			// CLEAN CACHE
			Mage::app()->cleanCache();
		} catch (Exception $e) {
			Mage::log('[' . __CLASS__ . '] ' . $e->getMessage(), Zend_Log::WARN);
		}

		return;
	}
}
