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

		// set up base dir if it hasn't been during instantiation
		if (!$this->hasBaseDir()) {
			$this->setBaseDir(Mage::getBaseDir('var') . DS . $cfg->itemFeedLocalPath);
		}

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
			// setting default store language
			->setDefaultStoreLanguageCode(Mage::app()->getLocale()->getLocaleCode())
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
	 * @return int
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
	 * add new attributes aptions and return the newly inserted option id
	 *
	 * @param string $attribute, the attribute to used to add the new option
	 * @param string $newOption, the new option to be added for the attribute
	 *
	 * @return int, the newly inserted option id
	 */
	protected function _addAttributeOption($attribute, $newOption)
	{
		$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
		$attributeObject = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute);
		$setup->addAttributeOption(array('attribute_id' => $attributeObject->getAttributeId(),'value' => array('any_option_name' => array($newOption))));

		return $this->_getAttributeOptionId($attribute, $newOption);
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
				if (!$this->_isValidProductType($feedItem->getProductType())) {
					Mage::log(
						'[' . __CLASS__ . '] Item Master Feed product_type (' . $feedItem->getProductType() . '), doesn\'t match Magento available Product Types (' .
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
						break;
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
					$productObject->setTypeId($dataObject->getProductType());
					$productObject->setWeight($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getWeight());
					$productObject->setMass($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure());

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
							$colorCode = $this->_getFirstColorCode($colorData);
							$colorOptionId = $this->_getAttributeOptionId('color', $colorCode);
							$productObject->setColor(($colorOptionId)? $colorOptionId : $this->_addAttributeOption('color', $colorCode));
						}
					} else {
						// also, we want to se the color code to the configurable products as well
						if (trim(strtoupper($dataObject->getProductType())) === 'CONFIGURABLE' && $this->_isAttributeExists('color')) {
							// setting color attribute, with the first record
							$colorCode = $this->_getFirstColorCode($colorData);
							$colorOptionId = $this->_getAttributeOptionId('color', $colorCode);
							$productObject->setColor(($colorOptionId)? $colorOptionId : $this->_addAttributeOption('color', $colorCode));
						}
					}

					// setting configurable color data in the parent configurable product
					if (trim(strtoupper($dataObject->getProductType())) === 'CONFIGURABLE') {
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

					if ($this->_isAttributeExists('item_type')) {
						// setting item_type attribute
						$productObject->setItemType($dataObject->getBaseAttributes()->getItemType());
					}

					if ($this->_isAttributeExists('client_alt_item_id')) {
						// setting client_alt_item_id attribute
						$productObject->setClientAltItemId($dataObject->getItemId()->getClientAltItemId());
					}

					if ($this->_isAttributeExists('manufacturer_item_id')) {
						// setting manufacturer_item_id attribute
						$productObject->setManufacturerItemId($dataObject->getItemId()->getManufacturerItemId());
					}

					if ($this->_isAttributeExists('brand_name')) {
						// setting brand_name attribute
						$productObject->setBrandName($dataObject->getExtendedAttributes()->getBrandName());
					}

					if ($this->_isAttributeExists('brand_description')) {
						// setting brand_description attribute
						$brandDescription = $dataObject->getExtendedAttributes()->getBrandDescription();
						foreach ($brandDescription as $bDesc) {
							if (trim(strtoupper($bDesc['lang'])) === strtoupper($this->getDefaultStoreLanguageCode())) {
								$productObject->setBrandDescription($bDesc['description']);
								break;
							}
						}
					}

					if ($this->_isAttributeExists('buyer_name')) {
						// setting buyer_name attribute
						$productObject->setBuyerName($dataObject->getExtendedAttributes()->getBuyerName());
					}

					if ($this->_isAttributeExists('buyer_id')) {
						// setting buyer_id attribute
						$productObject->setBuyerId($dataObject->getExtendedAttributes()->getBuyerId());
					}

					if ($this->_isAttributeExists('companion_flag')) {
						// setting companion_flag attribute
						$productObject->setCompanionFlag($dataObject->getExtendedAttributes()->getCompanionFlag());
					}

					if ($this->_isAttributeExists('hazardous_material_code')) {
						// setting hazardous_material_code attribute
						$productObject->setHazardousMaterialCode($dataObject->getExtendedAttributes()->getHazardousMaterialCode());
					}

					if ($this->_isAttributeExists('is_hidden_product')) {
						// setting is_hidden_product attribute
						$productObject->setIsHiddenProduct($dataObject->getExtendedAttributes()->getIsHiddenProduct());
					}

					if ($this->_isAttributeExists('item_dimension_shipping_mass_unit_of_measure')) {
						// setting item_dimension_shipping_mass_unit_of_measure attribute
						$productObject->setItemDimensionhippingMassUnitOfMeasure($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure());
					}

					if ($this->_isAttributeExists('item_dimension_shipping_mass_weight')) {
						// setting item_dimension_shipping_mass_weight attribute
						$productObject->setItemDimensionhippingMassWeight($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getWeight());
					}

					if ($this->_isAttributeExists('item_dimension_display_mass_unit_of_measure')) {
						// setting item_dimension_display_mass_unit_of_measure attribute
						$productObject->setItemDimensionDisplayMassUnitOfMeasure($dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getMassUnitOfMeasure());
					}

					if ($this->_isAttributeExists('item_dimension_display_mass_weight')) {
						// setting item_dimension_display_mass_weight attribute
						$productObject->setItemDimensionDisplayMassWeight($dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getWeight());
					}

					if ($this->_isAttributeExists('item_dimension_display_packaging_unit_of_measure')) {
						// setting item_dimension_display_packaging_unit_of_measure attribute
						$productObject->setItemDimensionDisplayPackagingUnitOfMeasure($dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getUnitOfMeasure());
					}

					if ($this->_isAttributeExists('item_dimension_display_packaging_width')) {
						// setting item_dimension_display_packaging_width attribute
						$productObject->setItemDimensionDisplayPackagingWidth($dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getWidth());
					}

					if ($this->_isAttributeExists('item_dimension_display_packaging_length')) {
						// setting item_dimension_display_packaging_length attribute
						$productObject->setItemDimensionDisplayPackagingLength($dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getLength());
					}

					if ($this->_isAttributeExists('item_dimension_display_packaging_height')) {
						// setting item_dimension_display_packaging_height attribute
						$productObject->setItemDimensionDisplayPackagingHeight($dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getHeight());
					}

					if ($this->_isAttributeExists('item_dimension_shipping_packaging_unit_of_measure')) {
						// setting item_dimension_shipping_packaging_unit_of_measure attribute
						$productObject->setItemDimensionhippingPackagingUnitOfMeasure($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getUnitOfMeasure());
					}

					if ($this->_isAttributeExists('item_dimension_shipping_packaging_width')) {
						// setting item_dimension_shipping_packaging_width attribute
						$productObject->setItemDimensionhippingPackagingWidth($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getWidth());
					}

					if ($this->_isAttributeExists('item_dimension_shipping_packaging_length')) {
						// setting item_dimension_shipping_packaging_length attribute
						$productObject->setItemDimensionhippingPackagingLength($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getLength());
					}

					if ($this->_isAttributeExists('item_dimension_shipping_packaging_height')) {
						// setting item_dimension_shipping_packaging_height attribute
						$productObject->setItemDimensionhippingPackagingHeight($dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getHeight());
					}

					if ($this->_isAttributeExists('item_dimension_carton_mass_unit_of_measure')) {
						// setting item_dimension_carton_mass_unit_of_measure attribute
						$productObject->setItemDimensionCartonMassUnitOfMeasure($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getMassUnitOfMeasure());
					}

					if ($this->_isAttributeExists('item_dimension_carton_mass_weight')) {
						// setting item_dimension_carton_mass_weight attribute
						$productObject->setItemDimensionCartonMassWeight($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getWeight());
					}

					if ($this->_isAttributeExists('item_dimension_carton_packaging_unit_of_measure')) {
						// setting item_dimension_carton_packaging_unit_of_measure attribute
						$productObject->setItemDimensionCartonPackagingUnitOfMeasure($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getUnitOfMeasure());
					}

					if ($this->_isAttributeExists('item_dimension_carton_packaging_width')) {
						// setting item_dimension_carton_packaging_width attribute
						$productObject->setItemDimensionCartonPackagingWidth($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getWidth());
					}

					if ($this->_isAttributeExists('item_dimension_carton_packaging_length')) {
						// setting item_dimension_carton_packaging_length attribute
						$productObject->setItemDimensionCartonPackagingLength($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getLength());
					}

					if ($this->_isAttributeExists('item_dimension_carton_packaging_height')) {
						// setting item_dimension_carton_packaging_height attribute
						$productObject->setItemDimensionCartonPackagingHeight($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getHeight());
					}

					if ($this->_isAttributeExists('item_dimension_carton_type')) {
						// setting item_dimension_carton_type attribute
						$productObject->setItemDimensionCartonType($dataObject->getExtendedAttributes()->getItemDimensionCarton()->getType());
					}

					if ($this->_isAttributeExists('lot_tracking_indicator')) {
						// setting lot_tracking_indicator attribute
						$productObject->setLotTrackingIndicator($dataObject->getExtendedAttributes()->getLotTrackingIndicator());
					}

					if ($this->_isAttributeExists('ltl_freight_cost')) {
						// setting ltl_freight_cost attribute
						$productObject->setLtlFreightCost($dataObject->getExtendedAttributes()->getLtlFreightCost());
					}

					if ($this->_isAttributeExists('manufacturing_date')) {
						// setting manufacturing_date attribute
						$productObject->setManufacturingDate($dataObject->getExtendedAttributes()->getManufacturer()->getDate());
					}

					if ($this->_isAttributeExists('manufacturer_name')) {
						// setting manufacturer_name attribute
						$productObject->setManufacturerName($dataObject->getExtendedAttributes()->getManufacturer()->getName());
					}

					if ($this->_isAttributeExists('manufacturer_manufacturer_id')) {
						// setting manufacturer_manufacturer_id attribute
						$productObject->setManufacturerManufacturerId($dataObject->getExtendedAttributes()->getManufacturer()->getId());
					}

					if ($this->_isAttributeExists('may_ship_expedite')) {
						// setting may_ship_expedite attribute
						$productObject->setMayShipExpedite($dataObject->getExtendedAttributes()->getMayShipExpedite());
					}

					if ($this->_isAttributeExists('may_ship_international')) {
						// setting may_ship_international attribute
						$productObject->setMayShipInternational($dataObject->getExtendedAttributes()->getMayShipInternational());
					}

					if ($this->_isAttributeExists('may_ship_usps')) {
						// setting may_ship_usps attribute
						$productObject->setMayShipUsps($dataObject->getExtendedAttributes()->getMayShipUsps());
					}

					if ($this->_isAttributeExists('safety_stock')) {
						// setting safety_stock attribute
						$productObject->setSafetyStock($dataObject->getExtendedAttributes()->getSafetyStock());
					}

					if ($this->_isAttributeExists('sales_class')) {
						// setting sales_class attribute
						$productObject->setSalesClass($dataObject->getExtendedAttributes()->getSalesClass());
					}

					if ($this->_isAttributeExists('serial_number_type')) {
						// setting serial_number_type attribute
						$productObject->setSerialNumberType($dataObject->getExtendedAttributes()->getSerialNumberType());
					}

					if ($this->_isAttributeExists('service_indicator')) {
						// setting service_indicator attribute
						$productObject->setServiceIndicator($dataObject->getExtendedAttributes()->getServiceIndicator());
					}

					if ($this->_isAttributeExists('ship_group')) {
						// setting ship_group attribute
						$productObject->setShipGroup($dataObject->getExtendedAttributes()->getShipGroup());
					}

					if ($this->_isAttributeExists('ship_window_min_hour')) {
						// setting ship_window_min_hour attribute
						$productObject->setShipWindowMinHour($dataObject->getExtendedAttributes()->getShipWindowMinHour());
					}

					if ($this->_isAttributeExists('ship_window_max_hour')) {
						// setting ship_window_max_hour attribute
						$productObject->setShipWindowMaxHour($dataObject->getExtendedAttributes()->getShipWindowMaxHour());
					}

					if ($this->_isAttributeExists('street_date')) {
						// setting street_date attribute
						$productObject->setStreetDate($dataObject->getExtendedAttributes()->getStreetDate());
					}

					if ($this->_isAttributeExists('style_id')) {
						// setting style_id attribute
						$productObject->setStyleId($dataObject->getExtendedAttributes()->getStyleId());
					}

					if ($this->_isAttributeExists('style_description')) {
						// setting style_description attribute
						$productObject->setStyleDescription($dataObject->getExtendedAttributes()->getStyleDescription());
					}

					if ($this->_isAttributeExists('supplier_name')) {
						// setting supplier_name attribute
						$productObject->setSupplierName($dataObject->getExtendedAttributes()->getSupplierName());
					}

					if ($this->_isAttributeExists('supplier_supplier_id')) {
						// setting supplier_supplier_id attribute
						$productObject->setSupplierSupplierId($dataObject->getExtendedAttributes()->getSupplierSupplierId());
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
								if (strtoupper(trim($sizeData['lang'])) === strtoupper($this->getDefaultStoreLanguageCode())) {
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

					// we only set child product to parent configurable products products if we
					// have a simple product that has a style_id that belong to a parent product.
					if (trim(strtoupper($dataObject->getProductType())) === 'SIMPLE' && trim($dataObject->getExtendedAttributes()->getStyleId()) !== '') {
						// when style id for an item doesn't match the item client_item_id (sku),
						// then we have a potential child product that can be added to a configurable parent product
						if (trim(strtoupper($dataObject->getItemId()->getClientItemId())) !== trim(strtoupper($dataObject->getExtendedAttributes()->getStyleId()))) {
							// load the parent product using the child style id, because a child that belong to a
							// parent product will have the parent product style id as the sku to link them together.
							$parentProduct = $this->_loadProductBySku($dataObject->getExtendedAttributes()->getStyleId());
							// we have a valid parent configurable product
							if ($parentProduct->getId()) {
								if (trim(strtoupper($parentProduct->getTypeId())) === 'CONFIGURABLE') {
									// We have a valid configurable parent product to set this child to
									$this->_linkChildToParentConfigurableProduct($parentProduct, $productObject, $dataObject->getConfigurableAttributes());

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
					Mage::log(
						'[' . __CLASS__ . '] Item Master Feed Delete Operation for SKU (' .
						$dataObject->getItemId()->getClientItemId() . '), does not exists in Magento',
						Zend_Log::WARN
					);
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
			Mage::log(
				'[' . __CLASS__ . '] The following error has occurred while linking
				child product to configurable parent product for Item Master Feed (' .
				$e->getMessage() . ')',
				Zend_Log::ERR
			);
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
			// This is neccessary to dynamically set value for attributes in different store view.
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$allStores = Mage::app()->getStores();
			foreach ($parentColorDescriptionData as $cfgColorData) {
				foreach ($cfgColorData->description as $colorDescription) {
					foreach ($allStores as $eachStoreId => $val) {
						// assuming the storeview follow the locale convention.
						if (trim(strtoupper(Mage::app()->getStore($eachStoreId)->getCode())) === trim(strtoupper($colorDescription->lang))) {
							$childProductObject->setStoreId($eachStoreId)->setColorDescription($colorDescription->description)->save();
						}
					}
				}
			}
		} catch (Exception $e) {
			Mage::log(
				'[' . __CLASS__ . '] The following error has occurred while adding configurable
				color data to child product for Item Master Feed (' . $e->getMessage() . ')',
				Zend_Log::ERR
			);
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
