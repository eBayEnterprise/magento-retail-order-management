<?php
class TrueAction_Eb2cInventory_Model_Feed_Item_Inventories
	extends TrueAction_Eb2cCore_Model_Feed_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	/**
	 * Set up extractor, stock item and stock status models to use while
	 * processing the feed. If feed config data hasn't been set, set it to the
	 * inventory feed configuration set in config.xml before calling the
	 * parent::_construct method.
	 * @return self
	 */
	protected function _construct()
	{
		$this->addData(array(
			'extractor' => Mage::getModel('eb2cinventory/feed_item_extractor'),
			'stock_item' => Mage::getModel('cataloginventory/stock_item'),
			'stock_status' => Mage::getSingleton('cataloginventory/stock_status'),
		));

		// get feed config from config registry if it wasn't set through the
		// constructor params
		if (!$this->hasFeedConfig()) {
			$this->setFeedConfig(
				Mage::helper('eb2cinventory')->getConfigModel()->feedDirectoryConfig
			);
		}
		parent::_construct();
		return $this;
	}
	/**
	 * @see parent::processDom
	 */
	public function processDom(TrueAction_Dom_Document $xmlDom, array $fileDetails)
	{
		return $this->updateInventories($this->getExtractor()->extractInventoryFeed($xmlDom));
	}
	/**
	 * Process downloaded feeds from eb2c.
	 * @return void
	 */
	public function processFeeds()
	{
		// Capture the number of feeds processed.
		$res = parent::processFeeds();
		// Only trigger reindexing if at least one feed was processed.
		if ($res) {
			Mage::dispatchEvent('inventory_feed_processing_complete', array());
		}
		return $res;
	}
	/**
	 * Update the stock item "is_in_stock" status
	 * @param Mage_CatalogInventory_Model_Stock_Item $stockItem Stock item for the product being updated
	 * @param int $qty Inventory quantity stock item is being set to
	 * @return self
	 */
	protected function _updateItemIsInStock(Mage_CatalogInventory_Model_Stock_Item $stockItem, $qty)
	{
		$stockItem->setIsInStock($qty > $stockItem->getMinQty() ? 1 : 0);
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
		$stockItem = Mage::getModel('cataloginventory/stock_item')
			->loadByProduct($id)
			->setQty($qty);
		$this->_updateItemIsInStock($stockItem, $qty, $id);
		$stockItem->save();
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
