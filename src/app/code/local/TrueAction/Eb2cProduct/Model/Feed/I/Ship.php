<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_I_Ship
	extends Mage_Core_Model_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		// get config
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		// set base dir base on the config
		$this->setBaseDir($cfg->iShipFeedLocalPath);

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$this->setExtractor(Mage::getModel('eb2cproduct/feed_i_extractor'))
			->setProduct(Mage::getModel('catalog/product'))
			->setStockStatus(Mage::getSingleton('cataloginventory/stock_status'))
			->setFeedModel(Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs))
			->setEavConfig(Mage::getModel('eav/config'))
			// setting default attribute set id
			->setDefaultAttributeSetId(Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId())
			// Magento product type ids
			->setProductTypeId(array('simple', 'grouped', 'giftcard', 'downloadable', 'virtual', 'configurable', 'bundle'))
			// set the default store id
			->setDefaultStoreId(Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId())
			// set array of website ids
			->setWebsiteIds(Mage::getModel('core/website')->getCollection()->getAllIds());

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
	 * Get the item inventory feed from eb2c.
	 *
	 * @return array, All the feed xml document, from eb2c server.
	 */
	protected function _getIShipFeeds()
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$coreHelper = Mage::helper('eb2ccore');
		$remoteFile = $cfg->iShipFeedRemoteReceivedPath;
		$configPath = $cfg->configPath;
		$feedHelper = Mage::helper('eb2ccore/feed');
		$productHelper = Mage::helper('eb2cproduct');

		// only attempt to transfer file when the FTP setting is valid
		if ($coreHelper->isValidFtpSettings()) {
			// Download feed from eb2c server to local server
			Mage::helper('filetransfer')->getFile(
				$this->getFeedModel()->getInboundDir(),
				$remoteFile,
				$feedHelper::FILETRANSFER_CONFIG_PATH
			);
		} else {
			// log as a warning
			Mage::log(
				'[' . __CLASS__ . '] I Ship Feed: can\'t transfer file from eb2c server because of invalid ftp setting on the magento store.',
				Zend_Log::WARN
			);
		}
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

		$this->_getIShipFeeds();
		$domDocument = $coreHelper->getNewDomDocument();
		foreach ($this->getFeedModel()->lsInboundDir() as $feed) {
			// load feed files to Dom object
			$domDocument->load($feed);

			$expectEventType = $cfg->iShipFeedEventType;
			$expectHeaderVersion = $cfg->iShipFeedHeaderVersion;

			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $expectEventType, $expectHeaderVersion)) {
				// processing feed items
				$this->_iShipActions($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$this->getFeedModel()->mvToArchiveDir($feed);
			}
		}

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		$this->_clean();
	}

	/**
	 * determine which action to take for I Ship (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return void
	 */
	protected function _iShipActions($doc)
	{
		$productHelper = Mage::helper('eb2cproduct');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();

		if ($feedItemCollection = $this->getExtractor()->extractIShipFeed($doc)){
			// we've import our feed data in a varien object we can work with
			foreach ($feedItemCollection as $feedItem) {
				// Ensure this matches the catalog id set in the Magento admin configuration.
				// If different, do not update the item and log at WARN level.
				if ($feedItem->getCatalogId() !== $cfg->catalogId) {
					Mage::log(
						'I Ship Feed Catalog_id (' . $feedItem->getCatalogId() . '), doesn\'t match Magento Eb2c Config Catalog_id (' .
						$cfg->catalogId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// Ensure that the client_id field here matches the value supplied in the Magento admin.
				// If different, do not update this item and log at WARN level.
				if ($feedItem->getGsiClientId() !== $cfg->clientId) {
					Mage::log(
						'I Ship Feed Client_id (' . $feedItem->getGsiClientId() . '), doesn\'t match Magento Eb2c Config Client_id (' .
						$cfg->clientId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// This will be mapped by the product hub to Magento product types.
				// If the ItemType does not specify a Magento type, do not process the product and log at WARN level.
				if (!$this->_isValidProductType($feedItem->getBaseAttributes()->getItemType())) {
					Mage::log(
						'I Ship Feed item_type (' . $feedItem->getBaseAttributes()->getItemType() . '), doesn\'t match Magento available Item Types (' .
						implode(',', $this->getProductTypeId()) . ')',
						Zend_Log::WARN
					);
					continue;
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
						$this->_disabledItem($feedItem);
						break;
				}
			}
		}
	}

	/**
	 * add product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
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
						$productObject = $this->getProduct();
						$productObject->setTypeId($dataObject->getBaseAttributes()->getItemType());
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
						// adding new attributes
						if ($this->_isAttributeExists('is_drop_shipped')) {
							// setting is_drop_shipped attribute
							$productObject->setIsDropShipped($dataObject->getBaseAttributes()->getDropShipped());
						}
						if ($this->_isAttributeExists('tax_code')) {
							// setting tax_code attribute
							$productObject->setTaxCode($dataObject->getBaseAttributes()->getTaxCode());
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

						if ($this->_isAttributeExists('hts_codes')) {
							// setting hts_codes attribute
							$productObject->setHtsCodes($dataObject->getHtsCodes());
						}

						// saving the product
						$productObject->save();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this item already exists in magento let simply log it
					Mage::log('I Ship Feed Add Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), already exists in Magento', Zend_Log::WARN);
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
						// adding new attributes
						if ($this->_isAttributeExists('is_drop_shipped')) {
							// setting is_drop_shipped attribute
							$productObject->setIsDropShipped($dataObject->getBaseAttributes()->getDropShipped());
						}
						if ($this->_isAttributeExists('tax_code')) {
							// setting tax_code attribute
							$productObject->setTaxCode($dataObject->getBaseAttributes()->getTaxCode());
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

						if ($this->_isAttributeExists('hts_codes')) {
							// setting hts_codes attribute
							$productObject->setHtsCodes($dataObject->getHtsCodes());
						}

						// saving the product
						$productObject->save();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this item doesn't exists in magento let's add it and then log it
					$this->_addItem($dataObject);
					Mage::log('I Ship Feed Update Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), does not exists in Magento', Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * disabled the product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return void
	 */
	protected function _disabledItem($dataObject)
	{
		if ($dataObject) {
			if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));

				if ($this->getProduct()->getId()) {
					try {
						$productObject = $this->getProduct();
						$productObject->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE); // mark product not visible
						$productObject->setStatus(0); // disbled product
						// saving the product
						$productObject->save();
					} catch (Mage_Core_Exception $e) {
						Mage::logException($e);
					}
				} else {
					// this item doesn't exists in magento let simply log it
					Mage::log('I Ship Feed Delete Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), does not exists in Magento', Zend_Log::WARN);
				}
			}
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
