<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Feed_Item_Inventories extends Mage_Core_Model_Abstract
{
	protected $_helper;
	protected $_stockItem;
	protected $_product;
	protected $_stockStatus;
	protected $_feedModel;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->_helper = $this->_getHelper();
		$this->_stockItem = $this->_getStockItem();
		$this->_product = $this->_getProduct();
		$this->_stockStatus = $this->_getStockStatus();
		$this->_feedModel = $this->_getFeedModel();

		return $this;
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2cInventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2cinventory');
		}
		return $this->_helper;
	}

	/**
	 * Get Feed Model object.
	 *
	 * @return TrueAction_Eb2cCore_Model_Feed
	 */
	protected function _getFeedModel()
	{
		if (!$this->_feedModel) {
			$this->_feedModel = Mage::getModel('eb2ccore/feed');
		}
		return $this->_feedModel;
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
	 *
	 * @return array, All the feed xml document, from eb2c server.
	 */
	protected function _getItemInventoriesFeeds()
	{
		$this->_getFeedModel()->setBaseFolder( $this->_getHelper()->getConfigModel()->feedLocalPath );
		$remoteFile = $this->_getHelper()->getConfigModel()->feedRemoteReceivedPath;
		$configPath =  $this->_getHelper()->getConfigModel()->configPath;

		// downloading feed from eb2c server down to local server
		$this->_getHelper()->getFileTransferHelper()->getFile($this->getFeedModel()->getInboundFolder(), $remoteFile, $configPath, null);
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$this->_getItemInventoriesFeeds();
		$domDocument = $this->_getHelper()->getDomDocument();
		foreach ($this->_getFeedModel()->lsInboundFolder() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = $this->_getHelper()->getConfigModel()->feedEventType;
			$expectHeaderVersion = $this->_getHelper()->getConfigModel()->feedHeaderVersion;

			// validate feed header
			if ($this->_getHelper()->getCoreFeed()->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// run inventory updates
				$this->_inventoryUpdates($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all ok
				$this->_getFeedModel()->mvToArchiveFolder($feed);
			}
			// If this had failed, we could do this: $this->_getFeedModel()->mvToErrorFolder($feed);
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
					Mage::log("Item Inventories Feed SKU (${sku}), doesn't exists in Magento", Zend_Log::WARN);
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
			Mage::log($e->getMessage(), Zend_Log::WARN);
		}

		return;
	}
}
