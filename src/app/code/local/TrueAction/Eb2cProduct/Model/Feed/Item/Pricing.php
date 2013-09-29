<?php
class TrueAction_Eb2cProduct_Model_Feed_Item_Pricing
	extends Mage_Core_Model_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	/**
	 * hold a collection of bundle operation data
	 *
	 * @var array
	 */
	protected $_queue;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		// set up base dir if it hasn't been during instantiation
		if (!$this->hasBaseDir()) {
			$this->setBaseDir(Mage::getBaseDir('var') . DS . Mage::helper('eb2cproduct')->getConfigModel()->itemFeedLocalPath);
		}

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$prod = Mage::getModel('catalog/product');
		$this->addData(array(
			'extractor' => Mage::getModel('eb2cproduct/feed_item_pricing_extractor'),
			'product' => $prod,
			'stock_status' => Mage::getSingleton('cataloginventory/stock_status'),
			'feed_model' => Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs),
			'default_attribute_set_id' => $prod->getResource()->getEntityType()->getDefaultAttributeSetId(),
			'default_store_id' => Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId(),
			'website_ids' => Mage::getModel('core/website')->getCollection()->getAllIds(),
		));

		// initialize bundle queue with an empty array
		$this->_queue = array();
		return $this;
	}

	/**
	 * get the last event out of a list of events
	 * @return Varien_Object
	 */
	protected function _selectEvent(array $events)
	{
		$selectedEvent = new Varien_Object();
		foreach ($events as $event) {
			$selectedEvent = $event;
		}
		return $selectedEvent;
	}

	/**
	 * add extracted data to the work queue.
	 * @param  Varien_Object $feedItem
	 * @return self
	 */
	protected function _queueData($itemData)
	{
		if ($itemData) {
			$this->_queue[] = $itemData;
		}
		return ;
	}

	/**
	 * process each item in the work queue.
	 * @return self
	 */
	protected function _processQueue()
	{
		foreach ($this->_queue as $item) {
			try {
				$this->_processItem($item);
			} catch (Mage_Core_Exception $e) {
				Mage::logException($e);
			}
		}
	}

	/**
	 * load product by sku
	 *
	 * @param string $sku, the product sku to filter the product table
	 *
	 * @return catalog/product
	 */
	protected function _getProductBySku($sku)
	{
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->addAttributeToSelect('*');
		$products->getSelect()
			->where('e.sku = ?', $sku);

		$products->load();
		$product = $products->getFirstItem();
		if (!$product->getId()) {
			$this->applyDummyData($product, $sku);
		}
		return ;
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$productHelper = Mage::helper('eb2cproduct');
		$coreHelper = Mage::helper('eb2ccore');
		$coreHelperFeed = Mage::helper('eb2ccore/feed');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		$this->getFeedModel()->fetchFeedsFromRemote(
			$cfg->itemFeedRemoteReceivedPath,
			$cfg->itemFeedFilePattern
		);
		$domDocument = $coreHelper->getNewDomDocument();
		foreach ($this->getFeedModel()->lsInboundDir() as $feed) {
			// load feed files to Dom object
			$domDocument->load($feed);

			$expectEventType = $cfg->itemFeedEventType;
			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $expectEventType)) {
				// processing feed items
				$this->_processDom($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$this->getFeedModel()->mvToArchiveDir($feed);
			}
		}

		$this->_processQueue();
		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * determine which action to take for item master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _processDom($doc)
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		if ($feedItemCollection = $this->getExtractor()->extractPricingFeed($doc)){
			// we've import our feed data in a varien object we can work with
			foreach ($feedItemCollection as $feedItem) {
				// Ensure this matches the catalog id set in the Magento admin configuration.
				// If different, do not update the item and log at WARN level.
				if ($feedItem->getCatalogId() !== $cfg->catalogId) {
					Mage::log(
						'Item Pricing Feed Catalog_id (' . $feedItem->getCatalogId() . '), doesn\'t match Magento Eb2c Config Catalog_id (' .
						$cfg->catalogId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// Ensure that the client_id field here matches the value supplied in the Magento admin.
				// If different, do not update this item and log at WARN level.
				if ($feedItem->getGsiClientId() !== $cfg->clientId) {
					Mage::log(
						'Item Pricing Feed Client_id (' . $feedItem->getGsiClientId() . '), doesn\'t match Magento Eb2c Config Client_id (' .
						$cfg->clientId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				$this->_queueData($feedItem);
			}
		}
	}

	/**
	 * @return array list containing the integer id for the root-category of the default store
	 */
	protected function _getDefaultCategoryIds()
	{
		$storeId = $this->getDefaultStoreId();
		return array(Mage::app()->getStore($storeId)->getRootCategoryId());
	}

	/**
	 * fill a product model with dummy data so that it can be saved and edited later
	 * @see http://www.magentocommerce.com/boards/viewthread/289906/
	 * @param  Mage_Catalog_Model_Product $product product model to be autofilled
	 * @return void
	 */
	public function applyDummyData($product, $sku)
	{
		$product->setAttributeSetId($this->getDefaultAttributeSetId());
		$product->setTypeId('simple');
		$product->setSku($sku);
		$product->setName('Invalid Product: ' . $sku);
		$product->setUrlKey($sku);
		$product->setCategoryIds($this->_getDefaultCategoryIds());
		$product->setWebsiteIds($this->getWebsiteIds());
		$product->setDescription('This product is invalid. If you are seeing this product, please do not attempt to purchase and contact customer service.');
		$product->setShortDescription('Invalid. Please do not attempt to purchase.');
		$product->setPrice(0); # Set some price

		//Default Magento attribute
		$product->setWeight(0);

		$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
		$product->setStatus(0);
		$product->setTaxClassId(0); # default tax class
		$product->setStockData(array(
			'is_in_stock' => 0,
			'qty' => 0
		));
	}

	/**
	 * add product to magento
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 *
	 * @return void
	 */
	protected function _processItem($dataObject)
	{
		$sku = trim($dataObject->getClientItemId());
		if ($dataObject && !is_null($sku) && $sku !== '') {
			// get the product model to import the data into
			$productObject = $this->_getProductBySku($dataObject->getClientItemId());

			$event = $this->_selectEvent($dataObject->getEvents());
			$price = $event->getPrice();
			$priceVatInlcusive = $event->getPriceVatInclusive();
			$specialPrice = null;
			$startDate = null;
			$endDate = null;
			if ($event->getEventNumber()) {
				$price = $event->getAlternatePrice();
				$specialPrice = $event->getPrice();
				$startDate = $event->getStartDate();
				$endDate = $event->getEndDate();
			}
			$productObject->setPrice($price);
			$productObject->setSpecialPrice($specialPrice);
			$productObject->setSpecialFromDate($startDate);
			$productObject->setSpecialToDate($endDate);
			$productObject->setMsrp($event->getMsrp());
			if (Mage::helper('eb2cproduct')->hasEavAttr('price_is_vat_inclusive')) {
				$productObject->setPriceIsVatInclusive($priceVatInlcusive);
			}
			// saving the product
			$productObject->save();
		}
		return ;
	}

	/**
	 * clear magento cache and rebuild inventory status.
	 *
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
			Mage::log($e->getMessage(), Zend_Log::WARN);
		}

		return;
	}
}
