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
		// initialize feed item with an empty array
		$this->_queue = array();

		// set up base dir if it hasn't been during instantiation
		if (!$this->hasBaseDir()) {
			$this->setBaseDir(Mage::getBaseDir('var') . DS . Mage::helper('eb2cproduct')->getConfigModel()->pricingFeedLocalPath);
		}

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();

		$prod = Mage::getModel('catalog/product');

		return $this->addData(array(
			'extractor' => Mage::getModel('eb2cproduct/feed_item_pricing_extractor'),
			'product' => $prod,
			'stock_status' => Mage::getSingleton('cataloginventory/stock_status'),
			'feed_model' => Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs),
			'default_attribute_set_id' => $prod->getResource()->getEntityType()->getDefaultAttributeSetId(),
			'default_store_id' => Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId(),
			'website_ids' => Mage::getModel('core/website')->getCollection()->getAllIds(),
		));
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
		return $this;
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
		return $this;
	}

	/**
	 * load product by sku
	 *
	 * @param string $sku, the product sku to filter the product table
	 *
	 * @return Mage_Catalog_Model_Product
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
			$product = $this->applyDummyData($product, $sku);
		}
		return $product;
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return self
	 */
	public function processFeeds()
	{
		$productHelper = Mage::helper('eb2cproduct');
		$coreHelper = Mage::helper('eb2ccore');
		$coreHelperFeed = Mage::helper('eb2ccore/feed');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$feedModel = $this->getFeedModel();

		$feedModel->fetchFeedsFromRemote(
			$cfg->pricingFeedRemoteReceivedPath,
			$cfg->pricingFeedFilePattern
		);
		$domDocument = $coreHelper->getNewDomDocument();
		$feeds = $feedModel->lsInboundDir();
		Mage::log(sprintf('[ %s ] Found %d files to import', __CLASS__, count($feeds)), Zend_Log::DEBUG);
		foreach ($feeds as $feed) {
			// load feed files to Dom object
			$domDocument->load($feed);
			Mage::log(sprintf('[ %s ] Loaded xml file %s', __CLASS__, $feed), Zend_Log::DEBUG);
			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $cfg->pricingFeedEventType)) {
				// processing feed items
				$this->_processDom($domDocument);
			}
			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$feedModel->mvToArchiveDir($feed);
			}
		}

		$this->_processQueue();

		Mage::log(sprintf('[ %s ] Complete', __CLASS__), Zend_Log::DEBUG);

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		Mage::helper('eb2ccore')->clean(); // reindex
		return $this;
	}

	/**
	 * determine which action to take for item master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return self
	 */
	protected function _processDom($doc)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$cfgCatId = $cfg->catalogId;
		$cfgClientId = $cfg->clientId;
		$items = $this->getExtractor()->extract(new DOMXPath($doc));
		$numItems = count($items);

		if (!$numItems) {
			Mage::log(sprintf('[ %s ] Found no items in file to import.', __CLASS__), Zend_Log::WARN);
			return $this;
		}

		foreach ($items as $i => $item) {
			Mage::log(sprintf('[ %s ] Attempting to import %d of %d items.', __CLASS__, $i, $numItems), Zend_Log::DEBUG);
			$catId = $item->getCatalogId();
			$clientId = $item->getGsiClientId();
			if ($catId !== $cfgCatId) {
				Mage::log(
					sprintf("[ %s ] Item catalog_id '%s' doesn't match configured catalog_id '%s'.", __CLASS__, $catId, $cfgCatId),
					Zend_Log::WARN
				);
			} elseif ($clientId !== $cfgClientId) {
				Mage::log(
					sprintf("[ %s ] Item client_id '%s' doesn't match configured client_id '%s'.", __CLASS__, $clientId, $cfgClientId),
					Zend_Log::WARN
				);
			} else {
				$this->_queueData($item);
			}
		}
		return $this;
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
	 * @return Mage_Catalog_Model_Product
	 */
	public function applyDummyData($product, $sku)
	{
		try{
			$product->setId(null)
				->addData(
					array(
						'type_id' => 'simple', // default product type
						'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // default not visible
						'attribute_set_id' => $this->getDefaultAttributeSetId(),
						'name' => 'Invalid Product: ' . $sku,
						'status' => 0, // default - disabled
						'sku' => $sku,
						'website_ids' => $this->getWebsiteIds(),
						'category_ids' => $this->_getDefaultCategoryIds(),
						'description' => 'This product is invalid. If you are seeing this product, please do not attempt to purchase and contact customer service.',
						'short_description' => 'Invalid. Please do not attempt to purchase.',
						'price' => 0,
						'weight' => 0,
						'url_key' => $sku,
						'store_ids' => array($this->getDefaultStoreId()),
						'stock_data' => array('is_in_stock' => 1, 'qty' => 999, 'manage_stock' => 1),
						'tax_class_id' => 0,
					)
				)
				->save();
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf('[ %s ] The following error has occurred while creating dummy product for iShip Feed (%d)',	__CLASS__, $e->getMessage()),
				Zend_Log::ERR
			);
		}

		return $product;
	}

	/**
	 * prepare the data to be set to the product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 *
	 * @return array, data to be set to magento product
	 */
	protected function _preparedProductData(Varien_Object $dataObject)
	{
		$event = $this->_selectEvent($dataObject->getEvents());
		$data = array('price' => $event->getPrice(), 'special_price' => null, 'special_from_date' => null, 'special_to_date' => null, 'msrp' => $event->getMsrp());
		if ($event->getEventNumber()) {
			$data['price'] = $event->getAlternatePrice();
			$data['special_price'] = $event->getPrice();
			$data['special_from_date'] = $event->getStartDate();
			$data['special_to_date'] = $event->getEndDate();
		}

		if (Mage::helper('eb2cproduct')->hasEavAttr('price_is_vat_inclusive')) {
			$data['price_is_vat_inclusive'] = $event->getPriceVatInclusive();
		}

		return $data;
	}

	/**
	 * add product to magento
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 *
	 * @return self
	 */
	protected function _processItem(Varien_Object $dataObject)
	{
		$sku = trim($dataObject->getClientItemId());
		if ($dataObject && !is_null($sku) && $sku !== '') {
			// get the product model to import the data into
			$this->_getProductBySku($dataObject->getClientItemId())->addData($this->_preparedProductData($dataObject))->save();
		}

		return $this;
	}
}
