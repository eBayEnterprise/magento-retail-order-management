<?php
class TrueAction_Eb2cProduct_Model_Feed_I_Ship
	extends Mage_Core_Model_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	const OPERATION_TYPE_DELETE = 'DELETE';
	const OPERATION_TYPE_ADD = 'ADD';
	const OPERATION_TYPE_UPDATE = 'UPDATE';

	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		// set up base dir if it hasn't been during instantiation
		if (!$this->hasBaseDir()) {
			$this->setBaseDir(Mage::getBaseDir('var') . DS . Mage::helper('eb2cproduct')->getConfigModel()->iShipFeedLocalPath);
		}

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs['base_dir'] = $this->getBaseDir();
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		$prod = Mage::getModel('catalog/product');
		return $this->addData(array(
			'extractor' => Mage::getModel('eb2cproduct/feed_i_extractor'),
			'product' => $prod,
			'stock_status' => Mage::getSingleton('cataloginventory/stock_status'),
			'feed_model' => Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs),
			'default_attribute_set_id' => $prod->getResource()->getEntityType()->getDefaultAttributeSetId(),
			'default_store_id' => Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId(),
			'website_ids' => Mage::getModel('core/website')->getCollection()->getAllIds(),
		));
	}

	/**
	 * load product by sku
	 *
	 * @param string $sku, the product sku to filter the product table
	 *
	 * @return Mage_Catalog_Model_Product
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
			$cfg->iShipFeedRemoteReceivedPath,
			$cfg->iShipFeedFilePattern
		);

		$domDocument = $coreHelper->getNewDomDocument();
		$feeds = $feedModel->lsInboundDir();
		Mage::log(sprintf('[ %s ] Found %d files to import', __CLASS__, count($feeds)), Zend_Log::DEBUG);
		foreach ($feeds as $feed) {
			// load feed files to Dom object
			$domDocument->load($feed);
			Mage::log(sprintf('[ %s ] Loaded xml file %s', __CLASS__, $feed), Zend_Log::DEBUG);
			// validate feed header
			if ($coreHelperFeed->validateHeader($domDocument, $cfg->iShipFeedEventType)) {
				// processing feed items
				$this->_iShipActions($domDocument);
			}

			// Remove feed file from local server after finishing processing it.
			if (file_exists($feed)) {
				// This assumes that we have process all OK
				$feedModel->mvToArchiveDir($feed);
			}
		}

		Mage::log(sprintf('[ %s ] Complete', __CLASS__), Zend_Log::DEBUG);

		// After all feeds have been process, let's clean magento cache and rebuild inventory status
		Mage::helper('eb2ccore')->clean();

		return $this;
	}

	/**
	 * determine which action to take for iShip (add, update, delete.
	 *
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 *
	 * @return self
	 */
	protected function _iShipActions(DOMDocument $doc)
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
			$prodType = $item->getProductType();
			$opType = trim(strtoupper($item->getOperationType()));
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
			} elseif (!$prdHlpr->hasProdType($prodType)) {
				Mage::log(sprintf('[ %s ] Unrecognized product type "%s"', __CLASS__, $prodType), Zend_Log::WARN);
			} else {
				switch ($opType) {
					case self::OPERATION_TYPE_ADD:
					case self::OPERATION_TYPE_UPDATE:
						$this->_synchProduct($item);
						break;
					case self::OPERATION_TYPE_DELETE:
						$this->_disabledItem($item);
						break;
					default:
						Mage::log(sprintf('[ %s ] Unrecognized operation type "%s"', __CLASS__, $opType), Zend_Log::WARN);
						break;
				}
			}
		}

		return $this;
	}

	/**
	 * add/update magento product with eb2c data
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return self
	 */
	protected function _synchProduct(Varien_Object $dataObject)
	{
		if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
			// we have a valid item, let's check if this product already exists in Magento
			$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));
			$productObject = $this->getProduct()->getId() ? $this->getProduct() : $this->_getDummyProduct($dataObject);

			$productObject->addData(array(
				'type_id' => $dataObject->getProductType(),
				'visibility' => $this->_getVisibilityData($dataObject),
				'attribute_set_id' => $this->getDefaultAttributeSetId(),
				'status' => $dataObject->getBaseAttributes()->getItemStatus(),
				'sku' => $dataObject->getItemId()->getClientItemId(),
				'website_ids' => $this->getWebsiteIds(),
				'store_ids' => array($this->getDefaultStoreId()),
				'url_key' => $dataObject->getItemId()->getClientItemId(),
			))->save(); // saving the product

			$this->_addEb2cSpecificAttributeToProduct($dataObject, $productObject) // adding new attributes
				->_addCustomAttributeToProduct($dataObject, $productObject); // adding custom attributes
		}

		return $this;
	}

	/**
	 * Create dummy products and return new dummy product object
	 *
	 * @param Varien_Object $dataObject, the object with data needed to create dummy product
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _getDummyProduct(Varien_Object $dataObject)
	{
		$productObject = $this->getProduct()->load(0);
		try{
			$productObject->setId(null)
				->addData(
					array(
						'type_id' => 'simple', // default product type
						'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // default not visible
						'attribute_set_id' => $this->getDefaultAttributeSetId(),
						'name' => 'temporary-name - ' . uniqid(),
						'status' => 0, // default - disabled
						'sku' => $dataObject->getItemId()->getClientItemId(),
						'website_ids' => $this->getWebsiteIds(),
						'store_ids' => array($this->getDefaultStoreId()),
						'stock_data' => array('is_in_stock' => 1, 'qty' => 999, 'manage_stock' => 1),
						'tax_class_id' => 0,
						'url_key' => $dataObject->getItemId()->getClientItemId(),
					)
				)
				->save();
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf('[ %s ] The following error has occurred while creating dummy product for iShip Feed (%d)',	__CLASS__, $e->getMessage()),
				Zend_Log::ERR
			);
		}
		return $productObject;
	}

	/**
	 * mapped the correct visibility data from eb2c feed with magento's visibility expected values
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the CatalogClass to determine the proper Magento visibility value
	 *
	 * @return string, the correct visibility value
	 */
	protected function _getVisibilityData(Varien_Object $dataObject)
	{
		// nosale should map to not visible individually.
		$visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;

		// Both regular and always should map to catalog/search.
		// Assume there can be a custom Visibility field. As always, the last node wins.
		$catalogClass = strtoupper(trim($dataObject->getBaseAttributes()->getCatalogClass()));
		if ($catalogClass === 'REGULAR' || $catalogClass === 'ALWAYS') {
			$visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
		}

		return $visibility;
	}

	/**
	 * disabled the product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return self
	 */
	protected function _disabledItem(Varien_Object $dataObject)
	{
		if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
			// we have a valid item, let's check if this product already exists in Magento
			$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));

			if ($this->getProduct()->getId()) {
				try {
					$productObject = $this->getProduct();
					$productObject->addData(
						array(
							'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // mark product not visible
							'status' => 0, // disbled product
						)
					)->save(); // saving the product
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				}
			} else {
				// this item doesn't exists in magento let simply log it
				Mage::log(
					sprintf('[ %s ] iShip Feed Delete Operation for SKU (%d), does not exists in Magento', __CLASS__, $dataObject->getItemId()->getClientItemId()),
					Zend_Log::WARN
				);
			}
		}

		return $this;
	}

	/**
	 * extract eb2c specific attribute data to be set to a product, if those attribute exists in magento
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve eb2c specific attribute product data
	 * @return array, composite array containing eb2c specific attribute to be set to a product
	 */
	protected function _getEb2cSpecificAttributeData(Varien_Object $dataObject)
	{
		$data = array();
		$hlpr = Mage::helper('eb2cproduct');
		foreach (array('is_drop_shipped', 'tax_code', 'hts_codes') as $at) {
			if ($hlpr->hasEavAttr($at)) {
				$data[$at] = $dataObject->getBaseAttributes()->getData($at);
			}
		}
		return $data;
	}

	/**
	 * adding eb2c specific attributes to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add eb2c specific attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set attributes data to
	 *
	 * @return self
	 */
	protected function _addEb2cSpecificAttributeToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$newAttributeData = $this->_getEb2cSpecificAttributeData( $dataObject);
		// we have valid eb2c specific attribute data let's add it and save it to the product object
		if (!empty($newAttributeData)) {
			try{
				$productObject->addData($newAttributeData)->save();
			} catch (Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while adding eb2c specific attributes to product for iShip Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}

		return $this;
	}

	/**
	 * adding custom attributes to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
	 *
	 * @return self
	 */
	protected function _addCustomAttributeToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$prodHlpr = Mage::helper('eb2cproduct');
		$customData = array();
		$customAttributes = $dataObject->getCustomAttributes()->getAttributes();
		if (!empty($customAttributes)) {
			foreach ($customAttributes as $attribute) {
				$attributeCode = $this->_underscore($attribute['name']);
				if ($prodHlpr->hasEavAttr($attributeCode) && strtoupper(trim($attribute['name'])) !== 'CONFIGURABLEATTRIBUTES') {
					// setting custom attributes
					if (strtoupper(trim($attribute['operationType'])) === 'DELETE') {
						// setting custom attributes to null on operation type 'delete'
						$customData[$attributeCode] = null;
					} else {
						// setting custom value whenever the operation type is 'add', or 'change'
						$customData[$attributeCode] = $attribute['value'];
					}
				}
			}
		}

		// we have valid custom data let's add it and save it to the product object
		if (!empty($customData)) {
			try{
				$productObject->addData($customData)->save();
			} catch (Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while adding custom attributes to product for iShip Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}

		return $this;
	}
}
