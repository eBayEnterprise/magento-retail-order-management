<?php
class TrueAction_Eb2cInventory_Model_Feed_Item_Inventories
	extends Mage_Core_Model_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$cfg = Mage::helper('eb2cinventory')->getConfigModel();

		// set up base dir if it hasn't been during instantiation
		if (!$this->hasBaseDir()) {
			$this->setBaseDir(Mage::getBaseDir('var') . DS . $cfg->feedLocalPath);
		}

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$this->setExtractor(Mage::getModel('eb2cinventory/feed_item_extractor'))
			->setStockItem(Mage::getModel('cataloginventory/stock_item'))
			->setProduct(Mage::getModel('catalog/product'))
			->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'))
			->setFeedModel(Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs));

		return $this;
	}

	/**
	 * Process downloaded feeds from eb2c.
	 * @return void
	 */
	public function processFeeds()
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$cfg = Mage::helper('eb2cinventory')->getConfigModel();
		$coreHelperFeed = Mage::helper('eb2ccore/feed');

		$this->getFeedModel()->fetchFeedsFromRemote(
			$cfg->feedRemoteReceivedPath,
			$cfg->feedFilePattern
		);

		foreach ($this->getFeedModel()->lsInboundDir() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = $cfg->feedEventType;
			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $expectEventType)) {
				// run inventory updates
				$this->_inventoryUpdates($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all ok
				$this->getFeedModel()->mvToArchiveDir($feed);
			}
			// If this had failed, we could do this: [mvToErrorDir(feed)]
		}

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * Update cataloginventory/stock_item with eb2c feed data.
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 * @return void
	 */
	protected function _inventoryUpdates($doc)
	{
		$feedItemCollection = $this->getExtractor()->extractInventoryFeed($doc);
		if ($feedItemCollection) {
			// we've import our feed data in a varien object we can work with
			foreach ($feedItemCollection as $feedItem) {
				// For inventory, we must prepend the client-id
				$mageSku = $feedItem->getCatalogId() . '-' . trim($feedItem->getItemId()->getClientItemId());
				if ($mageSku !== '') {
					// We have a sku, let's get the product id
					$mageProduct = $this->getProduct()->loadByAttribute('sku', $mageSku);
					if ($mageProduct) {
						// We've retrieved a valid magento product, let's update its stock. We're doing a lightweight load
						// by only bringing in the stockItem object itself - we are *not* loading the entire product. 
						// We could do that - it would also get the stockItem, but would also do a lot more that we don't
						// really need in this context.
						Mage::getModel('cataloginventory/stock_item')
							->loadByProduct($mageProduct->getId())
							->setQty($feedItem->getMeasurements()->getAvailableQuantity())
							->save();
					} else {
						// This item doesn't exist in the Magento App, just logged it as a warning
						Mage::log(
							'[' . __CLASS__ . '] Item Inventories Feed SKU (' . $feedItem->getItemId()->getClientItemId() . '), not found.',
							Zend_Log::WARN
						);
					}
				}
			}
		}
	}

	/**
	 * Clear Magento cache and rebuild inventory status.
	 * @return void
	 */
	protected function _clean()
	{
		Mage::log(sprintf('[ %s ] Disabled during testing; manual reindex required', __METHOD__), Zend_Log::WARN);
		return;
		try {
			// CLEAN CACHE
			Mage::app()->cleanCache();

			// STOCK STATUS
			$this->getStockStatus()->rebuild();
		} catch (Exception $e) {
			Mage::log('[' . __CLASS__ . '] ' . $e->getMessage(), Zend_Log::WARN);
		}
	}
}
