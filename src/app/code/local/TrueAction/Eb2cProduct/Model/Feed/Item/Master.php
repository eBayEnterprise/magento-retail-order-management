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
				$this->_itemMasterActions($domDocument);
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
						'[' . __CLASS__ . '] Item Master Feed Catalog_id (' . $feedItem->getCatalogId() . '), doesn\'t match Magento Eb2c Config Catalog_id (' .
						$cfg->catalogId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// Ensure that the client_id field here matches the value supplied in the Magento admin.
				// If different, do not update this item and log at WARN level.
				if ($feedItem->getGsiClientId() !== $cfg->clientId) {
					Mage::log(
						'[' . __CLASS__ . '] Item Master Feed Client_id (' . $feedItem->getGsiClientId() . '), doesn\'t match Magento Eb2c Config Client_id (' .
						$cfg->clientId . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// This will be mapped by the product hub to Magento product types.
				// If the ItemType does not specify a Magento type, do not process the product and log at WARN level.
				if (!$this->_isValidProductType($feedItem->getBaseAttributes()->getItemType())) {
					Mage::log(
						'[' . __CLASS__ . '] Item Master Feed item_type (' . $feedItem->getBaseAttributes()->getItemType() . '), doesn\'t match Magento available Item Types (' .
						implode(',', $this->getProductTypeId()) . ')',
						Zend_Log::WARN
					);
					continue;
				}

				// process feed data according to their operations
				switch (trim(strtoupper($feedItem->getOperationType()))) {
					case 'DELETE':
						$this->_deleteItem($feedItem);
						break;
					default:
						$this->_synchProduct($feedItem);
				}
			}
		}
	}

	/**
	 * add/update magento product with eb2c data
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add/update a magento product
	 *
	 * @return void
	 */
	protected function _synchProduct($dataObject)
	{
		if ($dataObject) {
			if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
				// we have a valid item, let's check if this product already exists in Magento
				$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));
				try {
					$productObject = $this->getProduct();
					$productObject->setTypeId($dataObject->getBaseAttributes()->getItemType());
					$productObject->setWeight($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getWeight());
					$productObject->setMass($dataObject->getExtendedAttributes()->getItemDimensionsShipping()->getMassUnitOfMeasure());

					// get color attribute data
					$colorData = $dataObject->getExtendedAttributes()->getColorAttributes()->getColor();

					// if new product
					if (!$productObject->getId()) {
						// adding new product to magento
						$productObject->setId(null);

						// Temporary fix
						$productObject->setName($dataObject->getBaseAttributes()->getItemDescription());

						// only temporarily set color attribute to newly create product, content master feed will update the product with the color value
						if ($this->_isAttributeExists('color')) {
							// setting color attribute, with the first record
							$productObject->setColor($this->_getAttributeOptionId('color', $this->_getFirstColorCode($colorData)));
						}
					} else {
						// also, we want to se the color code to the configurable products as well
						if (trim(strtoupper($dataObject->getBaseAttributes()->getItemType())) === 'CONFIGURABLE' && $this->_isAttributeExists('color')) {
							// setting color attribute, with the first record
							$productObject->setColor($this->_getAttributeOptionId('color', $this->_getFirstColorCode($colorData)));
						}
					}

					// setting configurable color data in the parent configurable product
					if (trim(strtoupper($dataObject->getBaseAttributes()->getItemType())) === 'CONFIGURABLE') {
						// This attribute hold a json color data, that will be used to for the child product storeview color_description.
						if ($this->_isAttributeExists('configurable_color_data')) {
							// setting configurable_color_data attribute
							$productObject->setConfigurableColorData(json_encode($colorData));
						}
					}

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

					// reload the product if it was newly created
					if (!$productObject->getId()) {
						$productObject = $this->_loadProductBySku($dataObject->getItemId()->getClientItemId());
					}

					// we only set child product to parent configurable products products if we have a simple product that has a style_id that belong to a parent product.
					if (trim(strtoupper($dataObject->getBaseAttributes()->getItemType())) === 'SIMPLE' && trim($dataObject->getExtendedAttributes()->getStyleId()) !== '') {
						// when style id for an item doesn't match the item client_item_id (sku), then we have a potential child product that can be added to a configurable parent product
						if (trim(strtoupper($dataObject->getItemId()->getClientItemId())) !== trim(strtoupper($dataObject->getExtendedAttributes()->getStyleId()))) {
							// load the parent product using the child style id, because a child that belong to a parent product will have the parent product style id as the sku to link them together.
							$parentProduct = $this->_loadProductBySku($dataObject->getExtendedAttributes()->getStyleId());
							// we have a valid parent configurable product
							if ($parentProduct->getId()) {
								if (trim(strtoupper($parentProduct->getTypeId())) === 'CONFIGURABLE') {
									// We have a valid configurable parent product to set this child to
									$this->_linkChildToParentConfigurableProduct($parentProduct, $productObject, $this->_extractConfigurableAttributes($dataObject));

									// We can get color description save in the parent product to be saved to this child product.
									$configurableColorData = json_decode($parentProduct->getConfigurableColorData());
									if (!empty($configurableColorData)) {
										$this->_addColorDescriptionToChildProduct($productObject, $configurableColorData);
									}
								}
							}
						}
					}

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
					Mage::log('[' . __CLASS__ . '] Item Master Feed Delete Operation for SKU (' . $dataObject->getItemId()->getClientItemId() . '), does not exists in Magento', Zend_Log::WARN);
				}
			}
		}

		return ;
	}

	/**
	 * link child product to parent configurable product.
	 *
	 * @param Mage_Catalog_Model_Product $parentProductObject, the parent configurable product object
	 * @param Mage_Catalog_Model_Product $childProductObject, the child product object
	 * @param array $configurableAttributes, collection of configurable attribute
	 *
	 * @return void
	 */
	protected function _linkChildToParentConfigurableProduct($parentProductObject, $childProductObject, $configurableAttributes)
	{
		try {
			$configurableData = array();
			foreach ($configurableAttributes as $configAttribute) {
				$attributeObject = $this->_getAttribute($configAttribute);
				$attributeOptions = $attributeObject->getSource()->getAllOptions();
				foreach ($attributeOptions as $option) {
					if ((int) $childProductObject->getData(strtolower($configAttribute)) === (int) $option['value']) {
						$configurableData[$childProductObject->getId()][] = array(
							'attribute_id' => $attributeObject->getId(),
							'label' => $option['label'],
							'value_index' => $option['value'],
						);
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
		} catch (Exception $e) {
			Mage::log('[' . __CLASS__ . '] The following error has occurred while linking child product to configurable parent product for Item Master Feed (' . $e->getMessage() . ')', Zend_Log::ERR);
		}
	}

	/**
	 * getting the first color code from an array of color attributes.
	 *
	 * @param array $colorData, collection of color data
	 *
	 * @return string, the first color code
	 */
	protected function _getFirstColorCode($colorData)
	{
		$colorCode = null;
		if (!empty($colorData)) {
			foreach ($colorData as $color) {
				$colorCode = $color['code'];
				break;
			}
		}

		return $colorCode ;
	}

	/**
	 * add color description per locale to a child product of using parent configurable store color attribute data.
	 *
	 * @param Mage_Catalog_Model_Product $childProductObject, the child product object
	 * @param array $parentColorDescriptionData, collection of configurable color description data
	 *
	 * @return void
	 */
	protected function _addColorDescriptionToChildProduct($childProductObject, $parentColorDescriptionData)
	{
		try {
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); // This is neccessary to dynamically set value for attributes in different store view.
			$allStores = Mage::app()->getStores();
			foreach ($parentColorDescriptionData as $cfgColorData) {
				foreach ($cfgColorData->description as $colorDescription) {
					foreach ($allStores as $eachStoreId => $val) {
						if (trim(strtoupper(Mage::app()->getStore($eachStoreId)->getCode())) === trim(strtoupper($colorDescription->lang))) { // assuming the storeview follow the locale convention.
							$childProductObject->setStoreId($eachStoreId)->setColorDescription($colorDescription->description)->save();
						}
					}
				}
			}
		} catch (Exception $e) {
			Mage::log('[' . __CLASS__ . '] The following error has occurred while adding configurable color data to child product for Item Master Feed (' . $e->getMessage() . ')', Zend_Log::ERR);
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
			Mage::log('[' . __CLASS__ . '] ' . $e->getMessage(), Zend_Log::WARN);
		}

		return;
	}
}
