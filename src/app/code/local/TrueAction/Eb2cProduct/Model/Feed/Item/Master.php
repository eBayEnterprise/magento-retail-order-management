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

		$this->setData(
			array(
				'extractor' => Mage::getModel('eb2cproduct/feed_item_extractor'),
				'stock_item' => Mage::getModel('cataloginventory/stock_item'),
				'product' => Mage::getModel('catalog/product'),
				'stock_status' => Mage::getSingleton('cataloginventory/stock_status'),
				'feed_model' => Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs),
				'eav_config' => Mage::getModel('eav/config'),
				'eav_entity_attribute' => Mage::getModel('eav/entity_attribute'),
				'product_type_configurable_attribute' => Mage::getModel('catalog/product_type_configurable_attribute'),
				// setting default attribute set id
				'default_attribute_set_id' => Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId(),
				// Magento product type ids
				'product_type_id' => array('simple', 'grouped', 'giftcard', 'downloadable', 'virtual', 'configurable', 'bundle'),
				// set the default store id
				'default_store_id' => Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId(),
				// setting default store language
				'default_store_language_code' => Mage::app()->getLocale()->getLocaleCode(),
				// set array of website ids
				'website_ids' => Mage::getModel('core/website')->getCollection()->getAllIds(),
			)
		);

		return $this;
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
	 * helper method to get attribute into the right format.
	 *
	 * @param string $attribute, the string attribute
	 *
	 * @return string, the correct attribute format
	 */
	protected function _attributeFormat($attribute)
	{
		$attributeData = preg_split('/(?=[A-Z])/', trim($attribute));
		$correctFormat = '';
		$index = 0;
		$size = sizeof($attributeData);
		foreach ($attributeData as $attr) {
			if (trim($attr) !== '') {
				$correctFormat .= strtolower($attr);
				if ($index < $size) {
					$correctFormat .= '_';
				}
			}
			$index++;
		}
		return $correctFormat;
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
	protected function _itemMasterActions(DOMDocument $doc)
	{
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$feedItemCollection = $this->getExtractor()->extractItemMasterFeed($doc);

		if ($feedItemCollection){
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
	protected function _synchProduct(Varien_Object $dataObject)
	{
		if (trim($dataObject->getItemId()->getClientItemId()) !== '') {
			// we have a valid item, let's check if this product already exists in Magento
			$this->setProduct($this->_loadProductBySku($dataObject->getItemId()->getClientItemId()));
			if (!$this->getProduct()->getId()){
				// this is new product let's set default value for it in order to create it successfully.
				$productObject = $this->_getDummyProduct($dataObject);
			} else {
				$productObject = $this->getProduct();
			}
			try {
				$productObject->addData(
					array(
						'type_id' => $dataObject->getProductType(),
						'weight' => $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getWeight(),
						'mass' => $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure(),
						'visibility' => $this->_getVisibilityData($dataObject),
						'attribute_set_id' => $this->getDefaultAttributeSetId(),
						'status' => $dataObject->getBaseAttributes()->getItemStatus(),
						'sku' => $dataObject->getItemId()->getClientItemId(),
						'msrp' => $dataObject->getExtendedAttributes()->getMsrp(),
						'price' => $dataObject->getExtendedAttributes()->getPrice(),
					)
				)->save(); // saving the product
			} catch (Mage_Core_Exception $e) {
				Mage::logException($e);
			}

			// adding color data to product
			$this->_addColorToProduct($dataObject, $productObject);

			// adding new attributes
			$this->_addEb2cSpecificAttributeToProduct($dataObject, $productObject);

			// adding custom attributes
			$this->_addCustomAttributeToProduct($dataObject, $productObject);

			// adding configurable data to product
			$this->_addConfigurableDataToProduct($dataObject, $productObject);

			// adding product stock item data
			$this->_addStockItemDataToProduct($dataObject, $productObject);
		}

		return ;
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
		// get color attribute data
		$colorData = $dataObject->getExtendedAttributes()->getColorAttributes()->getColor();
		$colorCode = $this->_getFirstColorCode($colorData);
		$colorOptionId = $this->_getAttributeOptionId('color', $colorCode);

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
						'sku' => $dataObject->getUniqueID(),
						'color' => ($colorOptionId)? $colorOptionId : $this->_addAttributeOption('color', $colorCode),
					)
				)
				->save();
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				'[' . __CLASS__ . '] The following error has occurred while creating dummy product for Item Master Feed (' .
				$e->getMessage() . ')',
				Zend_Log::ERR
			);
		}
		return $this->_loadProductBySku($dataObject->getItemId()->getClientItemId());
	}

	/**
	 * adding stock item data to a product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add the stock data to the product
	 * @param Mage_Catalog_Model_Product $parentProductObject, the product object to set stock item data to
	 *
	 * @return void
	 */
	protected function _addStockItemDataToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$this->getStockItem()->loadByProduct($productObject)
			->addData(
				array(
					'use_config_backorders' => false,
					'backorders' => $dataObject->getExtendedAttributes()->getBackOrderable(),
					'product_id' => $productObject->getId(),
					'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
				)
			)
			->save();
	}

	/**
	 * adding color data product configurable products
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
	 *
	 * @return void
	 */
	protected function _addColorToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$prodHlpr = Mage::helper('eb2cproduct');

		// get color attribute data
		$colorData = $dataObject->getExtendedAttributes()->getColorAttributes()->getColor();

		if (trim(strtoupper($dataObject->getProductType())) === 'CONFIGURABLE' && $prodHlpr->hasEavAttr($this, 'color')) {
			// setting color attribute, with the first record
			$colorCode = $this->_getFirstColorCode($colorData);
			$colorOptionId = $this->_getAttributeOptionId('color', $colorCode);
			$productObject->addData(
				array(
					'color' => ($colorOptionId)? $colorOptionId : $this->_addAttributeOption('color', $colorCode),
					'configurable_color_data' => json_encode($colorData),
				)
			)->save();
		}
	}

	/**
	 * delete product.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to delete the product
	 *
	 * @return void
	 */
	protected function _deleteItem(Varien_Object $dataObject)
	{
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

		return ;
	}

	/**
	 * link child product to parent configurable product.
	 *
	 * @param Mage_Catalog_Model_Product $pPObj, the parent configurable product object
	 * @param Mage_Catalog_Model_Product $pCObj, the child product object
	 * @param array $confAttr, collection of configurable attribute
	 *
	 * @return void
	 */
	protected function _linkChildToParentConfigurableProduct(Mage_Catalog_Model_Product $pPObj, Mage_Catalog_Model_Product $pCObj, array $confAttr)
	{
		try {
			$configurableData = array();
			foreach ($confAttr as $configAttribute) {
				$attributeObject = $this->_getAttribute($configAttribute);
				$attributeOptions = $attributeObject->getSource()->getAllOptions();
				foreach ($attributeOptions as $option) {
					if ((int) $pCObj->getData(strtolower($configAttribute)) === (int) $option['value']) {
						$configurableData[$pCObj->getId()][] = array(
							'attribute_id' => $attributeObject->getId(),
							'label' => $option['label'],
							'value_index' => $option['value'],
						);
					}
				}
			}

			$configurableAttributeData = array();
			foreach ($confAttr as $attrCode) {
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

			$pPObj->addData(
				array(
					'configurable_products_data' => $configurableData,
					'configurable_attributes_data' => $configurableAttributeData,
					'can_save_configurable_attributes' => true,
				)
			)->save();
		} catch (Mage_Core_Exception $e) {
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
	protected function _getFirstColorCode(array $colorData)
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
	 * add color description per locale to a child product of using parent configurable store color attribute data.
	 *
	 * @param Mage_Catalog_Model_Product $childProductObject, the child product object
	 * @param array $parentColorDescriptionData, collection of configurable color description data
	 *
	 * @return void
	 */
	protected function _addColorDescriptionToChildProduct(Mage_Catalog_Model_Product $childProductObject, array $parentColorDescriptionData)
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
							$childProductObject->setStoreId($eachStoreId)->addData(array('color_description' => $colorDescription->description))->save();
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
	 * extract eb2c specific attribute data to be set to a product, if those attribute exists in magento
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve eb2c specific attribute product data
	 *
	 * @return array, composite array containing eb2c specific attribute to be set to a product
	 */
	protected function _getEb2cSpecificAttributeData(Varien_Object $dataObject)
	{
		$data = array();

		$prodHlpr = Mage::helper('eb2cproduct');

		if ($prodHlpr->hasEavAttr($this, 'is_drop_shipped')) {
			// setting is_drop_shipped attribute
			$data['is_drop_shipped'] = $dataObject->getBaseAttributes()->getDropShipped();
		}
		if ($prodHlpr->hasEavAttr($this, 'tax_code')) {
			// setting tax_code attribute
			$data['tax_code'] = $dataObject->getBaseAttributes()->getTaxCode();
		}
		if ($prodHlpr->hasEavAttr($this, 'drop_ship_supplier_name')) {
			// setting drop_ship_supplier_name attribute
			$data['drop_ship_supplier_name'] = $dataObject->getDropShipSupplierInformation()->getSupplierName();
		}
		if ($prodHlpr->hasEavAttr($this, 'drop_ship_supplier_number')) {
			// setting drop_ship_supplier_number attribute
			$data['drop_ship_supplier_number'] = $dataObject->getDropShipSupplierInformation()->getSupplierNumber();
		}
		if ($prodHlpr->hasEavAttr($this, 'drop_ship_supplier_part')) {
			// setting drop_ship_supplier_part attribute
			$data['drop_ship_supplier_part'] = $dataObject->getDropShipSupplierInformation()->getSupplierPartNumber();
		}
		if ($prodHlpr->hasEavAttr($this, 'gift_message_available')) {
			// setting gift_message_available attribute
			$data['gift_message_available'] = $dataObject->getExtendedAttributes()->getAllowGiftMessage();
			$data['use_config_gift_message_available'] = false;
		}
		if ($prodHlpr->hasEavAttr($this, 'country_of_manufacture')) {
			// setting country_of_manufacture attribute
			$data['country_of_manufacture'] = $dataObject->getExtendedAttributes()->getCountryOfOrigin();
		}
		if ($prodHlpr->hasEavAttr($this, 'gift_card_tender_code')) {
			// setting gift_card_tender_code attribute
			$data['gift_card_tender_code'] = $dataObject->getExtendedAttributes()->getGiftCardTenderCode();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_type')) {
			// setting item_type attribute
			$data['item_type'] = $dataObject->getBaseAttributes()->getItemType();
		}

		if ($prodHlpr->hasEavAttr($this, 'client_alt_item_id')) {
			// setting client_alt_item_id attribute
			$data['client_alt_item_id'] = $dataObject->getItemId()->getClientAltItemId();
		}

		if ($prodHlpr->hasEavAttr($this, 'manufacturer_item_id')) {
			// setting manufacturer_item_id attribute
			$data['manufacturer_item_id'] = $dataObject->getItemId()->getManufacturerItemId();
		}

		if ($prodHlpr->hasEavAttr($this, 'brand_name')) {
			// setting brand_name attribute
			$data['brand_name'] = $dataObject->getExtendedAttributes()->getBrandName();
		}

		if ($prodHlpr->hasEavAttr($this, 'brand_description')) {
			// setting brand_description attribute
			$brandDescription = $dataObject->getExtendedAttributes()->getBrandDescription();
			foreach ($brandDescription as $bDesc) {
				if (trim(strtoupper($bDesc['lang'])) === strtoupper($this->getDefaultStoreLanguageCode())) {
					$data['brand_description'] = $bDesc['description'];
					break;
				}
			}
		}

		if ($prodHlpr->hasEavAttr($this, 'buyer_name')) {
			// setting buyer_name attribute
			$data['buyer_name'] = $dataObject->getExtendedAttributes()->getBuyerName();
		}

		if ($prodHlpr->hasEavAttr($this, 'buyer_id')) {
			// setting buyer_id attribute
			$data['buyer_id'] = $dataObject->getExtendedAttributes()->getBuyerId();
		}

		if ($prodHlpr->hasEavAttr($this, 'companion_flag')) {
			// setting companion_flag attribute
			$data['companion_flag'] = $dataObject->getExtendedAttributes()->getCompanionFlag();
		}

		if ($prodHlpr->hasEavAttr($this, 'hazardous_material_code')) {
			// setting hazardous_material_code attribute
			$data['hazardous_material_code'] = $dataObject->getExtendedAttributes()->getHazardousMaterialCode();
		}

		if ($prodHlpr->hasEavAttr($this, 'is_hidden_product')) {
			// setting is_hidden_product attribute
			$data['is_hidden_product'] = $dataObject->getExtendedAttributes()->getIsHiddenProduct();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_shipping_mass_unit_of_measure')) {
			// setting item_dimension_shipping_mass_unit_of_measure attribute
			$data['item_dimension_shipping_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_shipping_mass_weight')) {
			// setting item_dimension_shipping_mass_weight attribute
			$data['item_dimension_shipping_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getWeight();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_display_mass_unit_of_measure')) {
			// setting item_dimension_display_mass_unit_of_measure attribute
			$data['item_dimension_display_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getMassUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_display_mass_weight')) {
			// setting item_dimension_display_mass_weight attribute
			$data['item_dimension_display_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getWeight();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_display_packaging_unit_of_measure')) {
			// setting item_dimension_display_packaging_unit_of_measure attribute
			$data['item_dimension_display_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()
				->getPackaging()->getUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_display_packaging_width')) {
			// setting item_dimension_display_packaging_width attribute
			$data['item_dimension_display_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getWidth();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_display_packaging_length')) {
			// setting item_dimension_display_packaging_length attribute
			$data['item_dimension_display_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getLength();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_display_packaging_height')) {
			// setting item_dimension_display_packaging_height attribute
			$data['item_dimension_display_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getHeight();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_shipping_packaging_unit_of_measure')) {
			// setting item_dimension_shipping_packaging_unit_of_measure attribute
			$data['item_dimension_shipping_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()
				->getPackaging()->getUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_shipping_packaging_width')) {
			// setting item_dimension_shipping_packaging_width attribute
			$data['item_dimension_shipping_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getWidth();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_shipping_packaging_length')) {
			// setting item_dimension_shipping_packaging_length attribute
			$data['item_dimension_shipping_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getLength();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_shipping_packaging_height')) {
			// setting item_dimension_shipping_packaging_height attribute
			$data['item_dimension_shipping_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getHeight();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_mass_unit_of_measure')) {
			// setting item_dimension_carton_mass_unit_of_measure attribute
			$data['item_dimension_carton_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getMassUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_mass_weight')) {
			// setting item_dimension_carton_mass_weight attribute
			$data['item_dimension_carton_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getWeight();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_packaging_unit_of_measure')) {
			// setting item_dimension_carton_packaging_unit_of_measure attribute
			$data['item_dimension_carton_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()
				->getPackaging()->getUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_packaging_width')) {
			// setting item_dimension_carton_packaging_width attribute
			$data['item_dimension_carton_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getWidth();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_packaging_length')) {
			// setting item_dimension_carton_packaging_length attribute
			$data['item_dimension_carton_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getLength();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_packaging_height')) {
			// setting item_dimension_carton_packaging_height attribute
			$data['item_dimension_carton_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getHeight();
		}

		if ($prodHlpr->hasEavAttr($this, 'item_dimension_carton_type')) {
			// setting item_dimension_carton_type attribute
			$data['item_dimension_carton_type'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getType();
		}

		if ($prodHlpr->hasEavAttr($this, 'lot_tracking_indicator')) {
			// setting lot_tracking_indicator attribute
			$data['lot_tracking_indicator'] = $dataObject->getExtendedAttributes()->getLotTrackingIndicator();
		}

		if ($prodHlpr->hasEavAttr($this, 'ltl_freight_cost')) {
			// setting ltl_freight_cost attribute
			$data['ltl_freight_cost'] = $dataObject->getExtendedAttributes()->getLtlFreightCost();
		}

		if ($prodHlpr->hasEavAttr($this, 'manufacturing_date')) {
			// setting manufacturing_date attribute
			$data['manufacturing_date'] = $dataObject->getExtendedAttributes()->getManufacturer()->getDate();
		}

		if ($prodHlpr->hasEavAttr($this, 'manufacturer_name')) {
			// setting manufacturer_name attribute
			$data['manufacturer_name'] = $dataObject->getExtendedAttributes()->getManufacturer()->getName();
		}

		if ($prodHlpr->hasEavAttr($this, 'manufacturer_manufacturer_id')) {
			// setting manufacturer_manufacturer_id attribute
			$data['manufacturer_manufacturer_id'] = $dataObject->getExtendedAttributes()->getManufacturer()->getId();
		}

		if ($prodHlpr->hasEavAttr($this, 'may_ship_expedite')) {
			// setting may_ship_expedite attribute
			$data['may_ship_expedite'] = $dataObject->getExtendedAttributes()->getMayShipExpedite();
		}

		if ($prodHlpr->hasEavAttr($this, 'may_ship_international')) {
			// setting may_ship_international attribute
			$data['may_ship_international'] = $dataObject->getExtendedAttributes()->getMayShipInternational();
		}

		if ($prodHlpr->hasEavAttr($this, 'may_ship_usps')) {
			// setting may_ship_usps attribute
			$data['may_ship_usps'] = $dataObject->getExtendedAttributes()->getMayShipUsps();
		}

		if ($prodHlpr->hasEavAttr($this, 'safety_stock')) {
			// setting safety_stock attribute
			$data['safety_stock'] = $dataObject->getExtendedAttributes()->getSafetyStock();
		}

		if ($prodHlpr->hasEavAttr($this, 'sales_class')) {
			// setting sales_class attribute
			$data['sales_class'] = $dataObject->getExtendedAttributes()->getSalesClass();
		}

		if ($prodHlpr->hasEavAttr($this, 'serial_number_type')) {
			// setting serial_number_type attribute
			$data['serial_number_type'] = $dataObject->getExtendedAttributes()->getSerialNumberType();
		}

		if ($prodHlpr->hasEavAttr($this, 'service_indicator')) {
			// setting service_indicator attribute
			$data['service_indicator'] = $dataObject->getExtendedAttributes()->getServiceIndicator();
		}

		if ($prodHlpr->hasEavAttr($this, 'ship_group')) {
			// setting ship_group attribute
			$data['ship_group'] = $dataObject->getExtendedAttributes()->getShipGroup();
		}

		if ($prodHlpr->hasEavAttr($this, 'ship_window_min_hour')) {
			// setting ship_window_min_hour attribute
			$data['ship_window_min_hour'] = $dataObject->getExtendedAttributes()->getShipWindowMinHour();
		}

		if ($prodHlpr->hasEavAttr($this, 'ship_window_max_hour')) {
			// setting ship_window_max_hour attribute
			$data['ship_window_max_hour'] = $dataObject->getExtendedAttributes()->getShipWindowMaxHour();
		}

		if ($prodHlpr->hasEavAttr($this, 'street_date')) {
			// setting street_date attribute
			$data['street_date'] = $dataObject->getExtendedAttributes()->getStreetDate();
		}

		if ($prodHlpr->hasEavAttr($this, 'style_id')) {
			// setting style_id attribute
			$data['style_id'] = $dataObject->getExtendedAttributes()->getStyleId();
		}

		if ($prodHlpr->hasEavAttr($this, 'style_description')) {
			// setting style_description attribute
			$data['style_description'] = $dataObject->getExtendedAttributes()->getStyleDescription();
		}

		if ($prodHlpr->hasEavAttr($this, 'supplier_name')) {
			// setting supplier_name attribute
			$data['supplier_name'] = $dataObject->getExtendedAttributes()->getSupplierName();
		}

		if ($prodHlpr->hasEavAttr($this, 'supplier_supplier_id')) {
			// setting supplier_supplier_id attribute
			$data['supplier_supplier_id'] = $dataObject->getExtendedAttributes()->getSupplierSupplierId();
		}

		if ($prodHlpr->hasEavAttr($this, 'size')) {
			// setting size attribute
			$sizeAttributes = $dataObject->getExtendedAttributes()->getSizeAttributes()->getSize();
			$size = null;
			if (!empty($sizeAttributes)){
				foreach ($sizeAttributes as $sizeData) {
					if (strtoupper(trim($sizeData['lang'])) === strtoupper($this->getDefaultStoreLanguageCode())) {
						$data['size'] = $sizeData['description'];
						break;
					}
				}
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
	 * @return void
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
					'[' . __CLASS__ . '] The following error has occurred while adding eb2c
					specific attributes to product for Item Master Feed (' . $e->getMessage() . ')',
					Zend_Log::ERR
				);
			}
		}
	}

	/**
	 * adding custom attributes to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
	 *
	 * @return void
	 */
	protected function _addCustomAttributeToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$prodHlpr = Mage::helper('eb2cproduct');
		$customData = array();
		$customAttributes = $dataObject->getCustomAttributes()->getAttributes();
		if (!empty($customAttributes)) {
			foreach ($customAttributes as $attribute) {
				$attributeCode = $this->_attributeFormat($attribute['name']);
				if ($prodHlpr->hasEavAttr($this, $attributeCode) && strtoupper(trim($attribute['name'])) !== 'CONFIGURABLEATTRIBUTES') {
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
					'[' . __CLASS__ . '] The following error has occurred while adding
					custom attributes to product for Item Master Feed (' . $e->getMessage() . ')',
					Zend_Log::ERR
				);
			}
		}
	}

	/**
	 * adding configurable data to a product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add configurable data to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set configurable data to
	 *
	 * @return void
	 */
	protected function _addConfigurableDataToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
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
	}

	/**
	 * clear magento cache and rebuild inventory status.
	 *
	 * @return void
	 */
	protected function _clean()
	{
		Mage::log(sprintf('[ %s ] Start rebuilding stock data for all products.', __CLASS__), Zend_Log::DEBUG);
		try {
			// STOCK STATUS
			$this->getStockStatus()->rebuild();
		} catch (Exception $e) {
			Mage::log($e->getMessage(), Zend_Log::WARN);
		}
		Mage::log(sprintf('[ %s ] Done rebuilding stock data for all products.', __CLASS__), Zend_Log::DEBUG);
		return $this;
	}
}
