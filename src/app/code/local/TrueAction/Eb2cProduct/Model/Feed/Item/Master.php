<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Master extends Mage_Core_Model_Abstract
{
	/**
	 *
	 * hold a collection of bundle operation data
	 *
	 * @var array
	 */
	protected $_bundleQueue;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->setExtractor(Mage::getModel('eb2cproduct/feed_item_extractor'));
		$this->setHelper(Mage::helper('eb2cproduct'));
		$this->setStockItem(Mage::getModel('cataloginventory/stock_item'));
		$this->setProduct(Mage::getModel('catalog/product'));
		$this->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'));
		$this->setFeedModel(Mage::getModel('eb2ccore/feed'));
		$this->setDefaultAttributeSetId(Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId());

		// Magento product type ids
		$this->setProductTypeId(array('simple', 'grouped', 'giftcard', 'downloadable', 'virtual', 'configurable', 'bundle'));

		// set the default store id
		$this->setDefaultStoreId(Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId());

		// set array of website ids
		$this->setWebsiteIds(Mage::getModel('core/website')->getCollection()->getAllIds());

		// initalialize bundle queue with an empty array
		$this->_bundleQueue = array();

		return $this;
	}

	/**
	 * add bundle product to a queue to be process later.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to process bundle products
	 *
	 * @return void
	 */
	protected function _queueBundleData($bundleDataObject)
	{
		if ($bundleDataObject) {
			$this->_bundleQueue[] = $bundleDataObject;
		}
		return ;
	}

	/**
	 * load product by sku
	 *
	 * @param string $sku, the product sku to filter the product table
	 *
	 * @return catalog/product
	 */
	protected function _loadProductBySku($sku)
	{
		$products = Mage::getResourceModel('catalog/product_collection');
		$products->getSelect()
			->where('e.sku = ?', $sku);

		$products->load();

		return $products->getFirstItem();
	}

	/**
	 * validating the product type
	 *
	 * @param string $type, the product type to validated
	 *
	 * @return bool, true the inputed type match what's in magento else doesn't match
	 */
	protected function _isValidProductType($type)
	{
		return in_array($type, $this->getProductTypeId());
	}

	/**
	 * Get the item inventory feed from eb2c.
	 *
	 * @return array, All the feed xml document, from eb2c server.
	 */
	protected function _getItemMasterFeeds()
	{
		$this->getFeedModel()->setBaseFolder( $this->getHelper()->getConfigModel()->feedLocalPath );
		$remoteFile = $this->getHelper()->getConfigModel()->feedRemoteReceivedPath;
		$configPath =  $this->getHelper()->getConfigModel()->configPath;

		// downloading feed from eb2c server down to local server
		$this->getHelper()->getFileTransferHelper()->getFile($this->getFeedModel()->getInboundFolder(), $remoteFile, $configPath, null);
	}

	/**
	 * processing downloaded feeds from eb2c.
	 *
	 * @return void
	 */
	public function processFeeds()
	{
		$this->_getItemMasterFeeds();
		$domDocument = $this->getHelper()->getDomDocument();
		foreach ($this->getFeedModel()->lsInboundFolder() as $feed) {
			// load feed files to dom object
			$domDocument->load($feed);

			$expectEventType = $this->getHelper()->getConfigModel()->feedEventType;
			$expectHeaderVersion = $this->getHelper()->getConfigModel()->feedHeaderVersion;

			// validate feed header
			if ($this->getHelper()->getCoreFeed()->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// processing feed items
				$this->_itemMasterActions($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all ok
				$this->getFeedModel()->mvToArchiveFolder($feed);
			}
		}

		// let's process any bundle product that were added to the queue
		$this->processBundleQueue();

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * add bundle object to queue
	 *
	 * @param Varien_Object $feedItem, get bundle object from feed item
	 *
	 * @return void
	 */
	protected function _addBundleToQueue(Varien_Object $feedItem)
	{
		// if this item has bundle data let's queue it, so that we can process later.
		if (!is_null($feedItem->getBundleContents())) {
			$queueBundleObject = new Varien_Object();
			$queueBundleObject->setBundleData($feedItem->getBundleContents());
			$queueBundleObject->setParentSku($feedItem->getItemId()->getClientItemId());
			$this->_queueBundleData($queueBundleObject);
		}
	}

	/**
	 * determine which action to take for item master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _itemMasterActions($doc)
	{
		if ($feedItemCollection = $this->getExtractor()->extractItemItemMasterFeed($doc)){
			// we've import our feed data in a varien object we can work with
			foreach ($feedItemCollection as $feedItem) {
				// Ensure this matches the catalog id set in the Magento admin configuration.
				// If different, do not update the item and log at WARN level.
				if ($feedItem->getCatalogId() !== $this->getHelper()->getConfigModel()->catalogId) {
					Mage::log(
						'Item Master Feed Catalog_id (' . $feedItem->getCatalogId() . '), doesn\'t match Magento Eb2c Config Catalog_id (' .
						$this->getHelper()->getConfigModel()->catalogId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// Ensure that the client_id field here matches the value supplied in the Magento admin.
				// If different, do not update this item and log at WARN level.
				if ($feedItem->getGsiClientId() !== $this->getHelper()->getConfigModel()->clientId) {
					Mage::log(
						'Item Master Feed Client_id (' . $feedItem->getGsiClientId() . '), doesn\'t match Magento Eb2c Config Client_id (' .
						$this->getHelper()->getConfigModel()->clientId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// This will be mapped by the product hub to Magento product types.
				// If the ItemType does not specify a Magento type, do not process the product and log at WARN level.
				if (!$this->_isValidProductType($feedItem->getBaseAttributes()->getItemType())) {
					Mage::log(
						'Item Master Feed item_type (' . $feedItem->getBaseAttributes()->getItemType() . '), doesn\'t match Magento available Item Types (' .
						implode(',', $this->getProductTypeId()) . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// queue bundle data
				$this->_addBundleToQueue($feedItem);

				// pricess feed data according to their operations
				switch (trim(strtoupper($feedItem->getOperationType()))) {
					case 'ADD':
						$this->_addItem($feedItem);
						break;
					case 'CHANGE':
						$this->_updateItem($feedItem);
						break;
					case 'DELETE':
						$this->_deleteItem($feedItem);
						break;
				}
			}
		}
	}

	/**
	 * add product to magento
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the product
	 *
	 * @return void
	 */
	protected function _addItem($dataObject)
	{
		if ($dataObject) {
			if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));
				if (!$this->getProduct()->getId()) {
					try {
						// adding new product to magento
						$this->getProduct()->setId(null)
							->setTypeId($dataObject->getBaseAttributes()->getItemType())
							->setWeight($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getWeight())
							->setMass($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getMassUnitOfMeasure())
							->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
							->setAttributeSetId($this->getDefaultAttributeSetId())
							->setStatus($dataObject->getBaseAttributes()->getItemStatus())
							->setSku($dataObject->getItemId()->getClientItemId())
							->setMsrp($dataObject->getExtendedAttributes()->getMsrp())
							->setPrice($dataObject->getExtendedAttributes()->getPrice())
							// adding new attributes
							->setIsDropShipped($dataObject->getBaseAttributes()->getDropShipped())
							->setTaxCode($dataObject->getBaseAttributes()->getTaxCode())
							->setDropShipSupplierName($dataObject->getDropShipSupplierInformation()->getSupplierName())
							->setDropShipSupplierNumber($dataObject->getDropShipSupplierInformation()->getSupplierNumber())
							->setDropShipSupplierPart($dataObject->getDropShipSupplierInformation()->getSupplierPartNumber())
							->setGiftMessageAvailable($dataObject->getExtendedAttributes()->getAllowGiftMessage())
							->setUseConfigGiftMessageAvailable(false)
							->setCountryOfManufacture($dataObject->getExtendedAttributes()->getCountryOfOrigin())
							->save();
						// adding product stock item data
						$this->getStockItem()->loadByProduct($this->getProduct())
							->setUseConfigBackorders(false)
							->setBackorders($dataObject->getExtendedAttributes()->getBackOrderable())
							->setProductId($this->getProduct()->getId())
							->setStockId(Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID)
							->save();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this item currently exists in magento let simply log it
					Mage::log('Item Master Feed Add Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), already exists in Magento', Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * update product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return void
	 */
	protected function _updateItem($dataObject)
	{
		if ($dataObject) {
			if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));

				if ($this->getProduct()->getId()) {
					try {
						// updating already existed product
						$this->getProduct()->setTypeId($dataObject->getBaseAttributes()->getItemType())
							->setWeight($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getWeight())
							->setMass($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getMassUnitOfMeasure())
							->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
							->setAttributeSetId($this->getDefaultAttributeSetId())
							->setStatus($dataObject->getBaseAttributes()->getItemStatus())
							->setSku($dataObject->getItemId()->getClientItemId())
							->setMsrp($dataObject->getExtendedAttributes()->getMsrp())
							->setPrice($dataObject->getExtendedAttributes()->getPrice())
							// updating new attributes
							->setIsDropShipped($dataObject->getBaseAttributes()->getDropShipped())
							->setTaxCode($dataObject->getBaseAttributes()->getTaxCode())
							->setDropShipSupplierName($dataObject->getDropShipSupplierInformation()->getSupplierName())
							->setDropShipSupplierNumber($dataObject->getDropShipSupplierInformation()->getSupplierNumber())
							->setDropShipSupplierPart($dataObject->getDropShipSupplierInformation()->getSupplierPartNumber())
							->setGiftMessageAvailable($dataObject->getExtendedAttributes()->getAllowGiftMessage())
							->setUseConfigGiftMessageAvailable(false)
							->setCountryOfManufacture($dataObject->getExtendedAttributes()->getCountryOfOrigin())
							->save();
						// updating product stock item data
						$this->getStockItem()->loadByProduct($this->getProduct())
							->setUseConfigBackorders(false)
							->setBackorders($dataObject->getExtendedAttributes()->getBackOrderable())
							->setProductId($this->getProduct()->getId())
							->setStockId(Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID)
							->save();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this item doesn't exists in magento let simply log it
					Mage::log('Item Master Feed Update Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), does not exists in Magento', Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * delete product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to delete the product
	 *
	 * @return void
	 */
	protected function _deleteItem($dataObject)
	{
		if ($dataObject) {
			if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));

				if ($this->getProduct()->getId()) {
					try {
						// deleting the product from magento
						$this->getProduct()->delete();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this item doesn't exists in magento let simply log it
					Mage::log('Item Master Feed Delete Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), does not exists in Magento', Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * processing bundle queue.
	 *
	 * @return void
	 */
	public function processBundleQueue()
	{
		// process bundle only when the queue has actual data
		if (!empty($this->_bundleQueue)) {
			// loop through all queued items
			foreach ($this->_bundleQueue as $bundleObject) {
				// only process when there's a parent sku related to the bundle object
				if (trim($bundleObject->getParentSku()) !== '') {
					// we have a valid item, let's check if this parent product already exists in Magento
					$parentProductObject = $this->_loadProductBySku($bundleObject->getParentSku());
					if ($parentProductObject->getId()) {
						// we have a valid parent product
						try {
							// get all the bundle object and set them as bundle product for the parent product.
							if ($bundleItemCollection = $bundleObject->getBundleData()->getBundleItems()) {
								// before reistering product to Mage registry let's unregister first
								Mage::unregister('product');
								Mage::unregister('current_product');

								// registering the product to Mage registry
								Mage::register('product', $parentProductObject);
								Mage::register('current_product', $parentProductObject);

								// we have our collection of bundle items
								$optionRawData = array();
								$optionRawData[0] = array(
									'required' => 0,
									'position' => 0,
									'type' => 'radio',
									'title' => 'Eb2c Bundle',
									'delete' => ''
								);
								$selectionRawData = array();
								$bundlePositionIndex = 0;
								foreach ($bundleItemCollection as $bundleItemObject) {
									// we have a valid item, let's check if this child bundle product already exists in Magento
									$bundleProductObject = $this->_loadProductBySku($bundleItemObject->getItemId());
									if ($bundleProductObject->getId()) {
										$selectionRawData[0][] = array(
											'product_id' => $bundleProductObject->getId(),
											'position' => $bundlePositionIndex,
											'is_default' => 0,
											'selection_price_type' => '',
											'selection_price_value' => '',
											'selection_qty' => $bundleItemObject->getQuantity(),
											'selection_can_change_qty' => 1,
											'delete' => (trim(strtoupper($bundleItemObject->getOperationType())) === 'DELETE')? 'delete' : ''
										);
									}
									$bundlePositionIndex++;
								}

								$parentProductObject->setBundleOptionsData($optionRawData);
								$parentProductObject->setBundleSelectionsData($selectionRawData);

								$parentProductObject->setCanSaveBundleSelections(true);
								$parentProductObject->setAffectBundleProductSelections(true);

								$parentProductObject->save();
							}
						} catch (Exception $e) {
							Mage::log('The following error has occurred while processing the bundle queue for Item Master Feed (' . $e->getMessage() . ')', Zend_Log::ERR);
						}
					}
				}
			}

			// after looping through the queue, let's reset the bundle queue
			$this->_bundleQueue = array();
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
