<?php
class TrueAction_Eb2cInventory_Model_Feed_Item_Inventories extends Mage_Core_Model_Abstract
{
	/**
	 * Initialize model
	 */
	protected function _construct()
	{
<<<<<<< HEAD
		$this->setExtractor(Mage::getModel('eb2cinventory/feed_item_extractor'));
		$this->setHelper(Mage::helper('eb2cinventory'));
		$this->setStockItem(Mage::getModel('cataloginventory/stock_item'));
		$this->setProduct(Mage::getModel('catalog/product'));
		$this->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'));
		$this->setFeedModel(Mage::getModel('eb2ccore/feed'));

		return $this;
	}

=======
		$cfg = Mage::helper('eb2cinventory')->getConfigModel();

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$this->setExtractor(Mage::getModel('eb2cinventory/feed_item_extractor'))
			->setStockItem(Mage::getModel('cataloginventory/stock_item'))
			->setProduct(Mage::getModel('catalog/product'))
			->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'))
			->setFeedModel(Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs))
			->setBaseDir($cfg->feedLocalPath);

		return $this;
	}

>>>>>>> master
	/**
	 * Get the item inventory feed from eb2c.
	 */
	protected function _getItemInventoriesFeeds()
	{
<<<<<<< HEAD
		$this->getFeedModel()->setBaseFolder( $this->getHelper()->getConfigModel()->feedLocalPath );
		$remoteFile = $this->getHelper()->getConfigModel()->feedRemoteReceivedPath;
		$configPath =  $this->getHelper()->getConfigModel()->configPath;

		// only attempt to transfer file when the ftp setting is valid
		if ($this->getHelper()->isValidFtpSettings()) {
			// downloading feed from eb2c server down to local server
			$this->getHelper()->getFileTransferHelper()->getFile($this->getFeedModel()->getInboundFolder(), $remoteFile, $configPath, null);
		} else{
=======
		$cfg = Mage::helper('eb2cinventory')->getConfigModel();
		$remoteFile = $cfg->feedRemoteReceivedPath;
		$feedHelper = Mage::helper('eb2ccore/feed');

		// only attempt to transfer file when the ftp setting is valid
		if (Mage::helper('eb2ccore')->isValidFtpSettings()) {
			// Download feed from eb2c server to local server
			Mage::helper('filetransfer')->getFile(
				$this->getFeedModel()->getInboundDir(),
				$remoteFile,
				$feedHelper::FILETRANSFER_CONFIG_PATH
			);
		} else {
>>>>>>> master
			// log as a warning
			Mage::log(
				'[' . __CLASS__ . '] Item Inventories Feed: can\'t transfer file from eb2c server because of invalid ftp setting on the magento store.',
				Zend_Log::WARN
			);
<<<<<<< HEAD

=======
>>>>>>> master
		}
	}

	/**
	 * Process downloaded feeds from eb2c.
	 * @return void
	 */
	public function processFeeds()
	{
		$this->_getItemInventoriesFeeds();
<<<<<<< HEAD
		$domDocument = $this->getHelper()->getDomDocument();
		foreach ($this->getFeedModel()->lsInboundFolder() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = $this->getHelper()->getConfigModel()->feedEventType;
			$expectHeaderVersion = $this->getHelper()->getConfigModel()->feedHeaderVersion;

			// validate feed header
			if ($this->getHelper()->getCoreFeed()->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
=======
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		foreach ($this->getFeedModel()->lsInboundDir() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = Mage::helper('eb2cinventory')->getConfigModel()->feedEventType;
			$expectHeaderVersion = Mage::helper('eb2cinventory')->getConfigModel()->feedHeaderVersion;

			// validate feed header
			if (Mage::helper('eb2cinventory')->getCoreFeed()->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
>>>>>>> master
				// run inventory updates
				$this->_inventoryUpdates($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all ok
<<<<<<< HEAD
				$this->getFeedModel()->mvToArchiveFolder($feed);
			}
			// If this had failed, we could do this: call [mvToErrorFolder() method]
=======
				$this->getFeedModel()->mvToArchiveDir($feed);
			}
			// If this had failed, we could do this: [mvToErrorDir(feed)]
>>>>>>> master
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
<<<<<<< HEAD
		if ($feedItemCollection = $this->getExtractor()->extractInventoryFeed($doc)) {
=======
		$feedItemCollection = $this->getExtractor()->extractInventoryFeed($doc);
		if ($feedItemCollection) {
>>>>>>> master
			// we've import our feed data in a varien object we can work with
			foreach ($feedItemCollection as $feedItem) {
				if (trim($feedItem->getItemId()->getClientItemId()) !== '') {
					// we have a valid item, let's get the product id
					$this->getProduct()->loadByAttribute('sku', $feedItem->getItemId()->getClientItemId());

					if ($this->getProduct()->getId()) {
						// we've gotten a valid magento product, let's update its stock
						$this->getStockItem()->loadByProduct($this->getProduct()->getId())
							->setQty($feedItem->getMeasurements()->getAvailableQuantity())
							->save();
					} else {
						// This item doesn't exists in the Magento App, just logged it as a warning
						Mage::log(
							'[' . __CLASS__ . '] Item Inventories Feed SKU (' . $feedItem->getItemId()->getClientItemId() . '), doesn\'t exists in Magento',
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
