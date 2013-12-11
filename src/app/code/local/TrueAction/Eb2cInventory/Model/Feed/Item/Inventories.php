<?php
class TrueAction_Eb2cInventory_Model_Feed_Item_Inventories
	extends Varien_Object
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
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$cfg = Mage::helper('eb2cinventory')->getConfigModel();
		$coreHelperFeed = Mage::helper('eb2ccore/feed');

		$this->getFeedModel()->fetchFeedsFromRemote(
			$cfg->feedRemoteReceivedPath,
			$cfg->feedFilePattern
		);

		foreach ($this->getFeedModel()->lsInboundDir() as $feed) {
			// load feed files to dom object
			$doc->load($feed);

			$expectEventType = $cfg->feedEventType;
			// validate feed header
			if ($coreHelperFeed->validateHeader($doc, $expectEventType)) {
				// run inventory updates
				$this->updateInventories($this->getExtractor()->extractInventoryFeed($doc));
			}
			$this->archiveFeed($feed);
		}

		Mage::dispatchEvent('inventory_feed_processing_complete', array());
	}

	/**
	 * Archive the file after processing - move the local copy to the archive dir for the feed
	 * and delete the file off of the remote sftp server.
	 * @fixme  Dumb copy paste from the core/model/feed/abstract with minor adjustments to get it
	 * working here. This class should really just inherit properly.
	 * @param  string $xmlFeedFile Local path of the file
	 * @return $this object
	 */
	public function archiveFeed($xmlFeedFile)
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'))
			->addConfigModel(Mage::getSingleton('eb2cinventory/config'));
		if ($config->deleteRemoteFeedFiles) {
			$this->getFeedModel()->removeFromRemote($config->feedRemoteReceivedPath, basename($xmlFeedFile));
		}
		$this->getFeedModel()->mvToArchiveDir($xmlFeedFile);
		return $this;
	}
	/**
	 * Set the available quantity for a given item.
	 * @param int $id the product id to update
	 * @param int $qty the amount to set
	 * @return self
	 */
	protected function _setProdQty($id, $qty)
	{
		Mage::getModel('cataloginventory/stock_item')
			->loadByProduct($id)
			->setQty($qty)
			->save();
		return $this;
	}
	/**
	 * Update the inventory level for a given sku.
	 * @param string $sku the stock-keeping unit.
	 * @param int $qty the new quantity available to promise.
	 * @return self
	 */
	protected function _updateInventory($sku, $qty)
	{
		// Get product id from sku.
		$id = Mage::getModel('catalog/product')->getIdBySku($sku);
		if ($id) {
			$this->_setProdQty($id, $qty);
		} else {
			// @codeCoverageIgnoreStart
			Mage::log(sprintf('[ %s ] SKU "%s" not found for inventory update.', __CLASS__, $sku), Zend_Log::WARN);
			// @codeCoverageIgnoreEnd
		}
		return $this;
	}
	/**
	 * Get the sku from the feedItem object
	 * @param Varien_Object $feedItem a nested object of item info
	 * @return string the sku
	 */
	protected function _extractSku(Varien_Object $feedItem)
	{
		return Mage::helper('eb2ccore')->normalizeSku(
			$feedItem->getItemId()->getClientItemId(),
			$feedItem->getCatalogId()
		);
	}
	/**
	 * Update cataloginventory/stock_item with eb2c feed data.
	 * @param array $feedItems the extracted collection of inventory data
	 * @return self
	 */
	public function updateInventories(array $feedItems)
	{
		// @codeCoverageIgnoreStart
		Mage::log(sprintf('[ %s ] Updating inventory for %d items', __CLASS__, count($feedItems)), Zend_Log::INFO);
		// @codeCoverageIgnoreEnd
		foreach ($feedItems as $feedItem) {
			$sku = $this->_extractSku($feedItem);
			$qty = $feedItem->getMeasurements()->getAvailableQuantity();
			$this->_updateInventory($sku, $qty);
		}
		return $this;
	}
}
