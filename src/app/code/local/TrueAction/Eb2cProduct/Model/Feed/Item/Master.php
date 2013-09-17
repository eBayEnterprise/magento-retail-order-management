<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Master
	extends Mage_Core_Model_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	/**
	 * hold a collection of bundle operation data
	 *
	 * @var array
	 */
	protected $_bundleQueue;

	/**
	 * hold a collection of configurable operation data
	 *
	 * @var array
	 */
	protected $_configurableQueue;

	/**
	 * hold a collection of grouped operation data
	 *
	 * @var array
	 */
	protected $_groupedQueue;

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		// get config
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		// set base dir base on the config
		$this->setBaseDir($cfg->itemFeedLocalPath);

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$this->setExtractor(Mage::getModel('eb2cproduct/feed_item_extractor'))
			->setStockItem(Mage::getModel('cataloginventory/stock_item'))
			->setProduct(Mage::getModel('catalog/product'))
			->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'))
			->setFeedModel(Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs))
			->setEavConfig(Mage::getModel('eav/config'))
			->setEavEntityAttribute(Mage::getModel('eav/entity_attribute'))
			->setProductTypeConfigurableAttribute(Mage::getModel('catalog/product_type_configurable_attribute'))
			// setting default attribute set id
			->setDefaultAttributeSetId(Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId())
			// Magento product type ids
			->setProductTypeId(array('simple', 'grouped', 'giftcard', 'downloadable', 'virtual', 'configurable', 'bundle'))
			// set the default store id
			->setDefaultStoreId(Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId())
			// set array of website ids
			->setWebsiteIds(Mage::getModel('core/website')->getCollection()->getAllIds());

		// initialize bundle queue with an empty array
		$this->_bundleQueue = array();

		// initialize configurable queue with an empty array
		$this->_configurableQueue = array();

		// initialize grouped queue with an empty array
		$this->_groupedQueue = array();

		return $this;
	}

	/**
	 * checking product catalog eav config attributes.
	 *
	 * @param string $attribute, the string attribute code to check if exists for the catalog_product
	 *
	 * @return bool, true the attribute exists, false otherwise
	 */
	protected function _isAttributeExists($attribute)
	{
		return ((int) $this->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute)->getId() > 0)? true : false;
	}

	/**
	 * getting the eav attribute object.
	 *
	 * @param string $attribute, the string attribute code to get the attribute config
	 *
	 * @return Mage_Eav_Model_Config
	 */
	protected function _getAttribute($attribute)
	{
		return $this->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
	}

	/**
	 * getting the attribute selected option.
	 *
	 * @param string $attribute, the string attribute code to get the attribute config
	 * @param string $option, the string attribute option label to get the attribute
	 *
	 * @return Mage_Eav_Model_Config
	 */
	protected function _getAttributeOptionId($attribute, $option)
	{
		$optionId = 0;
		$attributes = $this->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
		$attributeOptions = $attributes->getSource()->getAllOptions();
		foreach ($attributeOptions as $attrOption) {
			if (strtoupper(trim($attrOption['label'])) === strtoupper(trim($option))) {
				$optionId = $attrOption['value'];
			}
		}

		return $optionId;
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
	 * add configurable product to a queue to be process later.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to process configured products
	 *
	 * @return void
	 */
	protected function _queueConfigurableData($configurableDataObject)
	{
		if ($configurableDataObject) {
			$this->_configurableQueue[] = $configurableDataObject;
		}
		return ;
	}

	/**
	 * add grouped product to a queue to be process later.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to process grouped products
	 *
	 * @return void
	 */
	protected function _queueGroupedData($groupedDataObject)
	{
		if ($groupedDataObject) {
			$this->_groupedQueue[] = $groupedDataObject;
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
		$products->addAttributeToSelect('*');
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

		$coreHelperFeed->fetchFeedsFromRemote(
			$cfg->itemFeedRemoteReceivedPath,
			$cfg->itemFeedFilePattern
		);
		$domDocument = $coreHelper->getNewDomDocument();
		foreach ($this->getFeedModel()->lsInboundDir() as $feed) {
			// load feed files to Dom object
			$domDocument->load($feed);

			$expectEventType = $cfg->itemFeedEventType;
			$expectHeaderVersion = $cfg->itemFeedHeaderVersion;

			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// processing feed items
				$this->_itemMasterActions($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$this->getFeedModel()->mvToArchiveDir($feed);
			}
		}

		// let's process any bundle product that were added to the queue
		$this->processBundleQueue();

		// let's process any configurable product that were added to the queue
		$this->processConfigurableQueue();

		// let's process any grouped product that were added to the queue
		$this->processGroupedQueue();

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
	 * add configurable object to queue
	 *
	 * @param Varien_Object $feedItem, get configurable object from feed item
	 *
	 * @return void
	 */
	protected function _addConfigurableToQueue(Varien_Object $feedItem)
	{
		// if this item has configurable data let's queue it, so that we can process later.
		if (!is_null($feedItem->getBundleContents())) {
			$queueConfigurableObject = new Varien_Object();
			$queueConfigurableObject->setConfigurableData($feedItem->getBundleContents());
			$queueConfigurableObject->setParentSku($feedItem->getItemId()->getClientItemId());
			$queueConfigurableObject->setConfigurableAttributes($this->_extractConfigurableAttributes($feedItem));
			$this->_queueConfigurableData($queueConfigurableObject);
		}
	}

	/**
	 * add Grouped object to queue
	 *
	 * @param Varien_Object $feedItem, get Grouped object from feed item
	 *
	 * @return void
	 */
	protected function _addGroupedToQueue(Varien_Object $feedItem)
	{
		// if this item has Grouped data let's queue it, so that we can process later.
		if (!is_null($feedItem->getBundleContents())) {
			$queueGroupedObject = new Varien_Object();
			$queueGroupedObject->setGroupedData($feedItem->getBundleContents());
			$queueGroupedObject->setParentSku($feedItem->getItemId()->getClientItemId());
			$this->_queueGroupedData($queueGroupedObject);
		}
	}

	/**
	 * extract configurable custom attributes feed item
	 *
	 * @param Varien_Object $feedItem, get configurable object from feed item
	 *
	 * @return array, all configurable attributes
	 */
	protected function _extractConfigurableAttributes(Varien_Object $feedItem)
	{
		$configurableAttributes = array();
		// if this item has configurable attribute data let's extract it.
		if (!is_null($feedItem->getCustomAttributes())) {
			// adding custom attributes
			$customAttributes = $feedItem->getCustomAttributes()->getAttributes();
			if (!empty($customAttributes)) {
				foreach ($customAttributes as $attribute) {
					// only process custom attributes that mark is configurable
					if (strtoupper(trim($attribute['name'])) === 'CONFIGURABLEATTRIBUTES') {
						$configurableAttributes = explode(',', $attribute['value']);
					}
				}
			}
		}

		return $configurableAttributes;
	}

	/**
	 * determine which action to take for item master (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _itemMasterActions($doc)
	{
		$productHelper = Mage::helper('eb2cproduct');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		if ($feedItemCollection = $this->getExtractor()->extractItemMasterFeed($doc)){
			// we've import our feed data in a varien object we can work with
			foreach ($feedItemCollection as $feedItem) {
				// Ensure this matches the catalog id set in the Magento admin configuration.
				// If different, do not update the item and log at WARN level.
				if ($feedItem->getCatalogId() !== $cfg->catalogId) {
					Mage::log(
						'Item Master Feed Catalog_id (' . $feedItem->getCatalogId() . '), doesn\'t match Magento Eb2c Config Catalog_id (' .
						$cfg->catalogId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// Ensure that the client_id field here matches the value supplied in the Magento admin.
				// If different, do not update this item and log at WARN level.
				if ($feedItem->getGsiClientId() !== $cfg->clientId) {
					Mage::log(
						'Item Master Feed Client_id (' . $feedItem->getGsiClientId() . '), doesn\'t match Magento Eb2c Config Client_id (' .
						$cfg->clientId . ')',
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

				if (strtoupper(trim($feedItem->getBaseAttributes()->getItemType())) === 'BUNDLE') {
					// queue bundle data
					$this->_addBundleToQueue($feedItem);
				} elseif (strtoupper(trim($feedItem->getBaseAttributes()->getItemType())) === 'CONFIGURABLE') {
					// queue configurable data
					$this->_addConfigurableToQueue($feedItem);
				} elseif (strtoupper(trim($feedItem->getBaseAttributes()->getItemType())) === 'GROUPED') {
					// queue Grouped data
					$this->_addGroupedToQueue($feedItem);
				}

				// process feed data according to their operations
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
						$productObject = $this->getProduct();
						$productObject->setId(null);
						$productObject->setTypeId($dataObject->getBaseAttributes()->getItemType());
						$productObject->setWeight($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getWeight());
						$productObject->setMass($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getMassUnitOfMeasure());

						// Temporary fix
						$productObject->setName($dataObject->getBaseAttributes()->getItemDescription());

						// nosale should map to not visible individually.
						// Both regular and always should map to catalog/search.
						// Assume there can be a custom Visibility field. As always, the last node wins.
						$catalogClass = strtoupper(trim($dataObject->getBaseAttributes()->getCatalogClass()));
						if ($catalogClass === '' || $catalogClass === 'NOSALE') {
							$productObject->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
						} elseif ($catalogClass === 'REGULAR' || $catalogClass === 'ALWAYS') {
							$productObject->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
						}
						$productObject->setAttributeSetId($this->getDefaultAttributeSetId());
						$productObject->setStatus($dataObject->getBaseAttributes()->getItemStatus());
						$productObject->setSku($dataObject->getItemId()->getClientItemId());
						if ($this->_isAttributeExists('msrp')) {
							// setting msrp attribute
							$productObject->setMsrp($dataObject->getExtendedAttributes()->getMsrp());
						}
						$productObject->setPrice($dataObject->getExtendedAttributes()->getPrice());
						// adding new attributes
						if ($this->_isAttributeExists('is_drop_shipped')) {
							// setting is_drop_shipped attribute
							$productObject->setIsDropShipped($dataObject->getBaseAttributes()->getDropShipped());
						}
						if ($this->_isAttributeExists('tax_code')) {
							// setting tax_code attribute
							$productObject->setTaxCode($dataObject->getBaseAttributes()->getTaxCode());
						}
						if ($this->_isAttributeExists('drop_ship_supplier_name')) {
							// setting drop_ship_supplier_name attribute
							$productObject->setDropShipSupplierName($dataObject->getDropShipSupplierInformation()->getSupplierName());
						}
						if ($this->_isAttributeExists('drop_ship_supplier_number')) {
							// setting drop_ship_supplier_number attribute
							$productObject->setDropShipSupplierNumber($dataObject->getDropShipSupplierInformation()->getSupplierNumber());
						}
						if ($this->_isAttributeExists('drop_ship_supplier_part')) {
							// setting drop_ship_supplier_part attribute
							$productObject->setDropShipSupplierPart($dataObject->getDropShipSupplierInformation()->getSupplierPartNumber());
						}
						if ($this->_isAttributeExists('gift_message_available')) {
							// setting gift_message_available attribute
							$productObject->setGiftMessageAvailable($dataObject->getExtendedAttributes()->getAllowGiftMessage());
							$productObject->setUseConfigGiftMessageAvailable(false);
						}
						if ($this->_isAttributeExists('country_of_manufacture')) {
							// setting country_of_manufacture attribute
							$productObject->setCountryOfManufacture($dataObject->getExtendedAttributes()->getCountryOfOrigin());
						}
						if ($this->_isAttributeExists('color')) {
							// setting color attribute
							$productObject->setColor($this->_getAttributeOptionId('color', $dataObject->getExtendedAttributes()->getColorAttributes()->getColorDescription()));
						}
						if ($this->_isAttributeExists('gift_card_tender_code')) {
							// setting gift_card_tender_code attribute
							$productObject->setGiftCardTenderCode($dataObject->getExtendedAttributes()->getGiftCardTenderCode());
						}

						// adding custom attributes
						$customAttributes = $dataObject->getCustomAttributes()->getAttributes();
						if (!empty($customAttributes)) {
							foreach ($customAttributes as $attribute) {
								$attributeCode = $attribute['name'];
								if ($this->_isAttributeExists($attributeCode)) {
									// only process custom attributes that not mark is configurable
									if (strtoupper(trim($attribute['name'])) !== 'CONFIGURABLEATTRIBUTES') {
										// setting custom attributes
										if (strtoupper(trim($attribute['operationType'])) === 'DELETE') {
											// setting custom attributes to null on operation type 'delete'
											$productObject->setData($attributeCode, null);
										} else {
											// setting custom value whenever the operation type is 'add', or 'change'
											$productObject->setData($attributeCode, $attribute['value']);
										}
									}
								}
							}
						}

						if ($this->_isAttributeExists('size')) {
							// setting size attribute
							$sizeAttributes = $dataObject->getExtendedAttributes()->getSizeAttributes()->getSize();
							$size = null;
							if (!empty($sizeAttributes)){
								foreach ($sizeAttributes as $sizeData) {
									if (strtoupper(trim($sizeData['lang'])) === 'EN-GB') {
										$size = $sizeData['description'];
									}
								}
							}
							$productObject->setSize($size);
						}

						// saving the product
						$productObject->save();

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
						$productObject = $this->getProduct();
						$productObject->setTypeId($dataObject->getBaseAttributes()->getItemType());
						$productObject->setWeight($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getWeight());
						$productObject->setMass($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getMassUnitOfMeasure());
						// nosale should map to not visible individually.
						// Both regular and always should map to catalog/search.
						// Assume there can be a custom Visibility field. As always, the last node wins.
						$catalogClass = strtoupper(trim($dataObject->getBaseAttributes()->getCatalogClass()));
						if ($catalogClass === '' || $catalogClass === 'NOSALE') {
							$productObject->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
						} elseif ($catalogClass === 'REGULAR' || $catalogClass === 'ALWAYS') {
							$productObject->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
						}
						$productObject->setAttributeSetId($this->getDefaultAttributeSetId());
						$productObject->setStatus($dataObject->getBaseAttributes()->getItemStatus());
						$productObject->setSku($dataObject->getItemId()->getClientItemId());
						if ($this->_isAttributeExists('msrp')) {
							// setting msrp attribute
							$productObject->setMsrp($dataObject->getExtendedAttributes()->getMsrp());
						}
						$productObject->setPrice($dataObject->getExtendedAttributes()->getPrice());
						// adding new attributes
						if ($this->_isAttributeExists('is_drop_shipped')) {
							// setting is_drop_shipped attribute
							$productObject->setIsDropShipped($dataObject->getBaseAttributes()->getDropShipped());
						}
						if ($this->_isAttributeExists('tax_code')) {
							// setting tax_code attribute
							$productObject->setTaxCode($dataObject->getBaseAttributes()->getTaxCode());
						}
						if ($this->_isAttributeExists('drop_ship_supplier_name')) {
							// setting drop_ship_supplier_name attribute
							$productObject->setDropShipSupplierName($dataObject->getDropShipSupplierInformation()->getSupplierName());
						}
						if ($this->_isAttributeExists('drop_ship_supplier_number')) {
							// setting drop_ship_supplier_number attribute
							$productObject->setDropShipSupplierNumber($dataObject->getDropShipSupplierInformation()->getSupplierNumber());
						}
						if ($this->_isAttributeExists('drop_ship_supplier_part')) {
							// setting drop_ship_supplier_part attribute
							$productObject->setDropShipSupplierPart($dataObject->getDropShipSupplierInformation()->getSupplierPartNumber());
						}
						if ($this->_isAttributeExists('gift_message_available')) {
							// setting gift_message_available attribute
							$productObject->setGiftMessageAvailable($dataObject->getExtendedAttributes()->getAllowGiftMessage());
							$productObject->setUseConfigGiftMessageAvailable(false);
						}
						if ($this->_isAttributeExists('country_of_manufacture')) {
							// setting country_of_manufacture attribute
							$productObject->setCountryOfManufacture($dataObject->getExtendedAttributes()->getCountryOfOrigin());
						}
						if ($this->_isAttributeExists('color')) {
							// setting color attribute
							$productObject->setColor($this->_getAttributeOptionId('color', $dataObject->getExtendedAttributes()->getColorAttributes()->getColorDescription()));
						}
						if ($this->_isAttributeExists('gift_card_tender_code')) {
							// setting gift_card_tender_code attribute
							$productObject->setGiftCardTenderCode($dataObject->getExtendedAttributes()->getGiftCardTenderCode());
						}

						// adding custom attributes
						$customAttributes = $dataObject->getCustomAttributes()->getAttributes();
						if (!empty($customAttributes)) {
							foreach ($customAttributes as $attribute) {
								$attributeCode = $attribute['name'];
								if ($this->_isAttributeExists($attributeCode)) {
									// only process custom attributes that not mark is configurable
									if (strtoupper(trim($attribute['name'])) !== 'CONFIGURABLEATTRIBUTES') {
										// setting custom attribute
										if (strtoupper(trim($attribute['operationType'])) === 'DELETE') {
											// setting custom attributes to null on operation type 'delete'
											$productObject->setData($attributeCode, null);
										} else {
											// setting custom value whenever the operation type is 'add', or 'change'
											$productObject->setData($attributeCode, $attribute['value']);
										}
									}
								}
							}
						}

						if ($this->_isAttributeExists('size')) {
							// setting size attribute
							$sizeAttributes = $dataObject->getExtendedAttributes()->getSizeAttributes()->getSize();
							$size = null;
							if (!empty($sizeAttributes)){
								foreach ($sizeAttributes as $sizeData) {
									if (strtoupper(trim($sizeData['lang'])) === 'EN-GB') {
										$size = $sizeData['description'];
									}
								}
							}
							$productObject->setSize($size);
						}

						// saving the product
						$productObject->save();

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
					// this item doesn't exists in magento let's add it and then log it
					$this->_addItem($dataObject);
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
								// before registering product to Mage registry let's unregister first
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
	 * processing Configurable queue.
	 *
	 * @return void
	 */
	public function processConfigurableQueue()
	{
		// process Configurable only when the queue has actual data
		if (!empty($this->_configurableQueue)) {
			// loop through all queued items
			foreach ($this->_configurableQueue as $configurableObject) {
				// only process when there's a parent sku related to the configurable object
				if (trim($configurableObject->getParentSku()) !== '') {
					// we have a valid item, let's check if this parent product already exists in Magento
					$parentProductObject = $this->_loadProductBySku($configurableObject->getParentSku());
					if ($parentProductObject->getId()) {
						// we have a valid parent product
						try {
							// get all the configurable object and set them as configurable product for the parent product.
							if ($configurableItemCollection = $configurableObject->getConfigurableData()->getBundleItems()) {

								$configurableData = array();

								// all configurable attributes for this configurable parent product
								$configurableAttributes = $configurableObject->getConfigurableAttributes();

								foreach ($configurableItemCollection as $children) {
									$childProduct = $this->_loadProductBySku($children->getItemId());
									if ($childProduct->getId()) {
										foreach ($configurableAttributes as $configAttribute) {
											$attributeObject = $this->_getAttribute($configAttribute);
											$attributeOptions = $attributeObject->getSource()->getAllOptions();
											foreach ($attributeOptions as $option) {
												if ((int) $childProduct->getData(strtolower($configAttribute)) === (int) $option['value']) {
													$configurableData[$childProduct->getId()][] = array(
														'attribute_id' => $attributeObject->getId(),
														'label' => $option['label'],
														'value_index' => $option['value'],
													);
												}
											}
										}
									}
								}

								$configurableAttributeData = array();
								foreach ($configurableAttributes as $attrCode) {
									$superAttribute = $this->getEavEntityAttribute()->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attrCode);
									$configurableAtt = $this->getProductTypeConfigurableAttribute()->setProductAttribute($superAttribute);
									$configurableAttributeData[] = array(
										'id' => $configurableAtt->getId(),
										'label' => $configurableAtt->getLabel(),
										'position' => $superAttribute->getPosition(),
										'values' => array(),
										'attribute_id' => $superAttribute->getId(),
										'attribute_code' => $superAttribute->getAttributeCode(),
										'frontend_label' => $superAttribute->getFrontend()->getLabel(),
									);
								}

								$parentProductObject->setConfigurableProductsData($configurableData);
								$parentProductObject->setConfigurableAttributesData($configurableAttributeData);
								$parentProductObject->setCanSaveConfigurableAttributes(true);

								$parentProductObject->save();
							}
						} catch (Exception $e) {
							Mage::log('The following error has occurred while processing the configurable queue for Item Master Feed (' . $e->getMessage() . ')', Zend_Log::ERR);
						}
					}
				}
			}

			// after looping through the queue, let's reset the configurable queue
			$this->_configurableQueue = array();
		}
	}

	/**
	 * processing Grouped queue.
	 *
	 * @return void
	 */
	public function processGroupedQueue()
	{
		// process Grouped only when the queue has actual data
		if (!empty($this->_groupedQueue)) {
			// loop through all queued items
			foreach ($this->_groupedQueue as $groupedObject) {
				// only process when there's a parent sku related to the grouped object
				if (trim($groupedObject->getParentSku()) !== '') {
					// we have a valid item, let's check if this parent product already exists in Magento
					$parentProductObject = $this->_loadProductBySku($groupedObject->getParentSku());
					if ($parentProductObject->getId()) {
						// we have a valid parent product
						try {
							// get all the grouped object and set them as grouped product for the parent product.
							if ($groupedItemCollection = $groupedObject->getGroupedData()->getBundleItems()) {

								$groupedData = array();

								$groupedItemIndex = 0;
								foreach ($groupedItemCollection as $children) {
									$childProduct = $this->_loadProductBySku($children->getItemId());
									if ($childProduct->getId()) {
										$groupedData[$childProduct->getId()] = array('position' => $groupedItemIndex, 'qty' => $children->getQuantity());
									}
									$groupedItemIndex++;
								}
								$parentProductObject->setGroupedLinkData($groupedData);
								$parentProductObject->save();
							}
						} catch (Exception $e) {
							Mage::log('The following error has occurred while processing the grouped queue for Item Master Feed (' . $e->getMessage() . ')', Zend_Log::ERR);
						}
					}
				}
			}

			// after looping through the queue, let's reset the grouped queue
			$this->_groupedQueue = array();
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
