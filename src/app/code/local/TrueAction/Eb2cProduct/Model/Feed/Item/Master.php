<?php
class TrueAction_Eb2cProduct_Model_Feed_Item_Master
	extends Mage_Core_Model_Abstract
	implements TrueAction_Eb2cCore_Model_Feed_Interface
{
	const DEFAULT_INVENTORY_QTY = 997;
	const OPERATION_TYPE_DELETE = 'DELETE';
	const OPERATION_TYPE_ADD    = 'ADD';
	const OPERATION_TYPE_UPDATE = 'UPDATE';

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
		return $this->addData(array(
			'default_attribute_set_id'            => $prod->getResource()->getEntityType()->getDefaultAttributeSetId(),
			'default_store_id'                    => Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId(),
			'default_store_language_code'         => Mage::app()->getLocale()->getLocaleCode(),
			'eav_entity_attribute'                => Mage::getModel('eav/entity_attribute'),
			'extractor'                           => Mage::getModel('eb2cproduct/feed_item_extractor'),
			'feed_model'                          => Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs),
			'product'                             => $prod,
			'product_type_configurable_attribute' => Mage::getModel('catalog/product_type_configurable_attribute'),
			'stock_item'                          => Mage::getModel('cataloginventory/stock_item'),
			'stock_status'                        => Mage::getSingleton('cataloginventory/stock_status'),
			'website_ids'                         => Mage::getModel('core/website')->getCollection()->getAllIds(),
		));
	}

	/**
	 * Gets the option id for the option within the given attribute
	 *
	 * @param string $attribute, The attribute code
	 * @param string $option, The option within the attribute
	 * @return int
	 */
	protected function _getAttributeOptionId($attribute, $option)
	{
		$attribute = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute);
		$attributeOptions = Mage::getResourceModel('eav/entity_attribute_option_collection')
			->setAttributeFilter($attribute->getId())
			->setStoreFilter(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID, false); // @todo false = 'don't use default', but I really don't know what that means.

		foreach ($attributeOptions as $attrOption) {
			$optionId    = $attrOption->getOptionId(); // getAttributeId is also available
			$optionValue = $attrOption->getValue();
			if(strtolower($optionValue) === strtolower($option)) {
				return $optionId;
			}
		}
		return 0;
	}

	/**
	 * Add new attribute aption and return the newly inserted option id
	 * @todo newOptionLabel needs to be the array of lang and description, not just a text field
	 *
	 * @param string $attribute, the attribute to which the new option is added
	 * @param string $newOption, the new option itself
	 * @return int, the newly inserted option id
	 */
	protected function _addOptionToAttribute($attribute, $newOption, $newOptionLabel)
	{
		$optionsIndex = 0;
		$values = array();
		$newAttributeOption = array(
			'value'  => array(),
			'order'  => array(),
			'delete' => array(),

		);
		$attributeId = Mage::getModel('catalog/resource_eav_attribute')
			->loadByCode('catalog_product', $attribute)
			->getAttributeId();

		// This entire set of options belongs to this attribute: 
		$newAttributeOption['attribute_id'] = $attributeId;

		$values[Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID] = $newOption;

		// @todo review scope rules to figure out which store we should use, and how do we figure out language?
		// Language is an attribute on the description, but dunno how to parse into a specific store.
		$allStores = Mage::app()->getStores();
		foreach( $allStores as $oneStore) {
			$storeId = $oneStore->getId();
			// @todo: From storeDetails, extract the Language (see spec, it's a naming convention)
			$storeDetails = $oneStore->load($storeId);
			$values[$storeId] = $newOptionLabel;
		}
		
		// Set up the option0 to be the default (i.e. admin) store:
		$newAttributeOption['value'] = array('replace_with_primary_key' => $values);
		$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
		try {
			$setup->addAttributeOption($newAttributeOption);
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] Error creating Admin option "%s" for attribute "%s": %s',
					__CLASS__, $newOption, $attribute, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $this->_getAttributeOptionId($attribute, $newOption); // Get the newly created id
	}

	/**
	 * Load product by sku
	 *
	 * @param string $sku, the product sku to filter the product table
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
	 * Process downloaded feeds from eb2c.
	 * @return self
	 */
	public function processFeeds()
	{
		$coreHelper = Mage::helper('eb2ccore');
		$coreHelperFeed = Mage::helper('eb2ccore/feed');
		$cfg = Mage::helper('eb2cproduct')->getConfigModel();
		$feedModel = $this->getFeedModel();

		$feedModel->fetchFeedsFromRemote(
			$cfg->itemFeedRemoteReceivedPath,
			$cfg->itemFeedFilePattern
		);
		$doc = $coreHelper->getNewDomDocument();
		$feeds = $feedModel->lsInboundDir();
		Mage::log(sprintf('[ %s ] Found %d files to import', __CLASS__, count($feeds)), Zend_Log::DEBUG);
		foreach ($feeds as $feed) {
			$doc->load($feed);
			Mage::log(sprintf('[ %s ] Loaded xml file %s', __CLASS__, $feed), Zend_Log::DEBUG);
			if ($coreHelperFeed->validateHeader($doc, $cfg->itemFeedEventType)) {
				$this->_itemMasterActions($doc); // Process feed data
			}
			if (file_exists($feed)) {
				$feedModel->mvToArchiveDir($feed);
			}
		}
		Mage::log(sprintf('[ %s ] Complete', __CLASS__), Zend_Log::DEBUG);
		Mage::helper('eb2ccore')->clean(); // reindex
		return $this;
	}

	/**
	 * determine which action to take for item master (add, update, delete.
	 * @param DOMDocument $doc, the Dom document with the loaded feed data
	 * @return self
	 */
	protected function _itemMasterActions(DOMDocument $doc)
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
				Mage::log(sprintf('[ %s ] Unrecognized product type "%s" for %s', __CLASS__, $prodType, $item->getItemId()->getClientItemId()), Zend_Log::WARN);
			} else {
				switch ($opType) {
					case self::OPERATION_TYPE_ADD:
					case self::OPERATION_TYPE_UPDATE:
						$this->_synchProduct($item);
						break;
					case self::OPERATION_TYPE_DELETE:
						$this->_deleteItem($item);
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
	 * @param Varien_Object $item, the object with data needed to add/update a magento product
	 * @return self
	 */
	protected function _synchProduct(Varien_Object $item)
	{
		if (trim($item->getItemId()->getClientItemId()) === '') {
			Mage::log(sprintf('[ %s ] Cowardly refusing to import item with no client_item_id.', __CLASS__), Zend_Log::WARN);
		} else {
			// we have a valid item, let's check if this product already exists in Magento
			$prd = $this->_loadProductBySku($item->getItemId()->getClientItemId());
			$this->setProduct($prd);
			$prdObj = $prd->getId() ? $prd : $this->_getDummyProduct($item);
			$prdObj->addData(array(
				'type_id' => $item->getProductType(),
				'weight' => $item->getExtendedAttributes()->getItemDimensionShipping()->getWeight(),
				'mass' => $item->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure(),
				'visibility' => $this->_getVisibilityData($item),
				'attribute_set_id' => $this->getDefaultAttributeSetId(),
				'status' => $item->getBaseAttributes()->getItemStatus(),
				'sku' => $item->getItemId()->getClientItemId(),
				'msrp' => $item->getExtendedAttributes()->getMsrp(),
				'price' => $item->getExtendedAttributes()->getPrice(),
				'website_ids' => $this->getWebsiteIds(),
				'store_ids' => array($this->getDefaultStoreId()),
				'tax_class_id' => 0,
				'url_key' => $item->getItemId()->getClientItemId(),
			))->save(); // saving the product

			$this
				->_addColorToProduct($item, $prdObj)
				->_addEb2cSpecificAttributeToProduct($item, $prdObj)
				->_addCustomAttributeToProduct($item, $prdObj)
				->_addConfigurableDataToProduct($item, $prdObj)
				->_addStockItemDataToProduct($item->getExtendedAttributes()->getBackOrderable(), $prdObj->getId());

		}
		return $this;
	}

	/**
	 * getting the color option, create it if id doesn't exist or just fetch it from magento db
	 * @param Varien_Object $dataObject, the object with data needed to create dummy product
	 * @return int, the option id
	 */
	protected function _getProductColorOptionId(Varien_Object $dataObject)
	{
		$colorOptionId = 0;

		// get color attribute data
		$colorData = $dataObject->getExtendedAttributes()->getColorAttributes()->getColor();
		if (!empty($colorData)) {
			$colorCode = $this->_getFirstColorCode($colorData);
			$colorLabel = $this->_getFirstColorLabel($colorData);
			if(trim($colorCode) !== '') {
				$colorOptionId = (int) $this->_getAttributeOptionId('color', $colorCode);
				if (!$colorOptionId) {
					$colorOptionId = (int) $this->_addOptionToAttribute('color', $colorCode, $colorLabel);
				}
			}
		}
		return $colorOptionId;
	}

	/**
	 * Create dummy products and return new dummy product object
	 * @param Varien_Object $dataObject, the object with data needed to create dummy product
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _getDummyProduct(Varien_Object $item)
	{
		$prd = $this->getProduct()->load(0);
		try {
			$prd
				->unsId()
				->addData(array(
					'type_id'          => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, // default product type
					'visibility'       => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, // default not visible
					'attribute_set_id' => $this->getDefaultAttributeSetId(),
					'name'             => 'temporary-name-' . uniqid() . '-' . $item->getBaseAttributes()->getItemDescription(),
					'status'           => 0, // default - disabled
					'sku'              => $item->getItemId()->getClientItemId(),
					'color'            => $this->_getProductColorOptionId($item),
					'website_ids'      => $this->getWebsiteIds(),
					'store_ids'        => array($this->getDefaultStoreId()),
					'tax_class_id'     => 0,
					'url_key'          => $item->getItemId()->getClientItemId(),
				))
				->save();

			// Create a stock_item for this product. It's not /loaded/ into the product yet!
			Mage::getModel('cataloginventory/stock_item')
				->setData(
					array(
						'product_id'   => $prd->getId(),
						'is_in_stock'  => 1,
						'manage_stock' => 1,
						'stock_id'     => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
					)
				)->save();

		} catch (Mage_Core_Exception $e) {
			Mage::log(sprintf('[ %s ] %s', __CLASS__, $e->getMessage()), Zend_Log::ERR);
		}
		return $prd;
	}

	/**
	 * Create a Configurable Product
	 *
	 * $sku
	 * $attributeSetId          The Attribute Set Used for this Configurable Product and its UsedProducts
	 * $configurableAttributes  Which Attributes within the Attribute Set are used to configure the product
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _createConfigurableProduct($sku, $attributeSetId, $configurableAttributes)
	{
		$newConfigurableProduct = Mage::getModel('catalog/product');
		// The Super Attribute is the glue that holds Configurable to its UsedProduct
		$configurableAttributeData = array();
		foreach ($configurableAttributes as $attrCode) {
			$superAttribute  = $this->getEavEntityAttribute()->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attrCode);
			$configurableAtt = $this->getProductTypeConfigurableAttribute()->setProductAttribute($superAttribute);

			$configurableAttributeData[] = array(
				'id'             => $configurableAtt->getId(),
				'label'          => $configurableAtt->getLabel(),
				'position'       => $superAttribute->getPosition(),
				'values'         => array(),
				'attribute_id'   => $superAttribute->getId(),
				'attribute_code' => $superAttribute->getAttributeCode(),
				'frontend_label' => $superAttribute->getFrontend()->getLabel(),
			);
		}

		$newConfigurableProductData = array(
			'type_id'                      => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			'visibility'                   => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
			'attribute_set_id'             => $attributeSetId,
			'name'                         => 'configurable-temp-' . uniqid(),
			'status'                       => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
			'sku'                          => $sku,
			'website_ids'                  => $this->getWebsiteIds(),
			'store_ids'                    => array($this->getDefaultStoreId()),
			'tax_class_id'                 => 0,
			'configurable_attributes_data' => $configurableAttributeData,
			'url_key'                      => $sku,
			'stock_data'                   => array( 'is_in_stock' => 1, 'qty' => self::DEFAULT_INVENTORY_QTY),
		);

		try {
			$newConfigurableProduct->setData($newConfigurableProductData)->save();
		} catch (Mage_Core_Exception $e) {
			Mage::log(sprintf('[ %s ] Create configurable product sku %s %s', __CLASS__, $sku, $e->getMessage()), Zend_Log::ERR);
		}
		return $this->_loadProductBySku($sku);
	}

	/**
	 * adding stock item data to a product.
	 * @param Varien_Object $dataObject, the object with data needed to add the stock data to the product
	 * @param Mage_Catalog_Model_Product $parentProductObject, the product object to set stock item data to
	 * @return self
	 */
	protected function _addStockItemDataToProduct($backOrderable, $productId)
	{
		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
		$stockItem
			->setQty(self::DEFAULT_INVENTORY_QTY)
			->setUseConfigBackorders(false)
			->setBackorders($backOrderable)
			->save();
		return $this;
	}

	/**
	 * adding color data product configurable products
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
	 * @return self
	 */
	protected function _addColorToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$prodHlpr = Mage::helper('eb2cproduct');
		if (trim(strtolower($dataObject->getProductType())) === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $prodHlpr->hasEavAttr('color')) {
			// setting color attribute, with the first record
			$productObject->addData(
				array(
					'color' => $this->_getProductColorOptionId($dataObject),
					'configurable_color_data' => json_encode($dataObject->getExtendedAttributes()->getColorAttributes()->getColor()),
				)
			)->save();
		}
		return $this;
	}

	/**
	 * delete product.
	 * @param Varien_Object $dataObject, the object with data needed to delete the product
	 * @return self
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
					sprintf(
						'[ %s ] Item Master Feed Delete Operation for SKU (%d), does not exists in Magento',
						__CLASS__, $dataObject->getItemId()->getClientItemId()
					),
					Zend_Log::WARN
				);
			}
		}

		return $this;
	}

	/**
	 * Adds the passed in product id to the configurableProduct passed in
	 * 
	 * @param $configurableProduct
	 * @param $usedProduct
	 * @return self
	 */
	private function _attachProductToConfigurable($configurableProduct, $usedProduct)
	{
		$usedProductIds   = $configurableProduct->getTypeInstance()->getUsedProductIds();
		$usedProductIds[] = $usedProduct->getId();
		Mage::getResourceModel('catalog/product_type_configurable')
			->saveProducts($configurableProduct, array_unique($usedProductIds));
		return $this;
	}

	/**
	 * getting the first color code from an array of color attributes.
	 * @param array $colorData, collection of color data
	 * @return string|null, the first color code
	 */
	protected function _getFirstColorCode(array $colorData)
	{
		if (!empty($colorData)) {
			foreach ($colorData as $color) {
				return $color['code'];
			}
		}
		return null;
	}

	/**
	 * getting the first color description from an array of color attributes.
	 * @param array $colorData, collection of color data
	 * @return string|null, the first color code
	 */
	protected function _getFirstColorLabel(array $colorData)
	{
		if (!empty($colorData)) {
			foreach ($colorData as $color) {
				// @todo language is delievered here in 'lang'
				return $color['description'][0]['description'];
			}
		}
		return null;
	}

	/**
	 * mapped the correct visibility data from eb2c feed with magento's visibility expected values
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the CatalogClass to determine the proper Magento visibility value
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
	 * extract eb2c specific attribute data to be set to a product, if those attribute exists in magento
	 * @param Varien_Object $dataObject, the object with data needed to retrieve eb2c specific attribute product data
	 * @return array, composite array containing eb2c specific attribute to be set to a product
	 */
	protected function _getEb2cSpecificAttributeData(Varien_Object $dataObject)
	{
		$data = array();
		$prodHlpr = Mage::helper('eb2cproduct');
		if ($prodHlpr->hasEavAttr('is_drop_shipped')) {
			// setting is_drop_shipped attribute
			$data['is_drop_shipped'] = $dataObject->getBaseAttributes()->getDropShipped();
		}
		if ($prodHlpr->hasEavAttr('tax_code')) {
			// setting tax_code attribute
			$data['tax_code'] = $dataObject->getBaseAttributes()->getTaxCode();
		}
		if ($prodHlpr->hasEavAttr('drop_ship_supplier_name')) {
			// setting drop_ship_supplier_name attribute
			$data['drop_ship_supplier_name'] = $dataObject->getDropShipSupplierInformation()->getSupplierName();
		}
		if ($prodHlpr->hasEavAttr('drop_ship_supplier_number')) {
			// setting drop_ship_supplier_number attribute
			$data['drop_ship_supplier_number'] = $dataObject->getDropShipSupplierInformation()->getSupplierNumber();
		}
		if ($prodHlpr->hasEavAttr('drop_ship_supplier_part')) {
			// setting drop_ship_supplier_part attribute
			$data['drop_ship_supplier_part'] = $dataObject->getDropShipSupplierInformation()->getSupplierPartNumber();
		}
		if ($prodHlpr->hasEavAttr('gift_message_available')) {
			// setting gift_message_available attribute
			$data['gift_message_available'] = $dataObject->getExtendedAttributes()->getAllowGiftMessage();
			$data['use_config_gift_message_available'] = false;
		}
		if ($prodHlpr->hasEavAttr('country_of_manufacture')) {
			// setting country_of_manufacture attribute
			$data['country_of_manufacture'] = $dataObject->getExtendedAttributes()->getCountryOfOrigin();
		}
		if ($prodHlpr->hasEavAttr('gift_card_tender_code')) {
			// setting gift_card_tender_code attribute
			$data['gift_card_tender_code'] = $dataObject->getExtendedAttributes()->getGiftCardTenderCode();
		}

		if ($prodHlpr->hasEavAttr('item_type')) {
			// setting item_type attribute
			$data['item_type'] = $dataObject->getBaseAttributes()->getItemType();
		}

		if ($prodHlpr->hasEavAttr('client_alt_item_id')) {
			// setting client_alt_item_id attribute
			$data['client_alt_item_id'] = $dataObject->getItemId()->getClientAltItemId();
		}

		if ($prodHlpr->hasEavAttr('manufacturer_item_id')) {
			// setting manufacturer_item_id attribute
			$data['manufacturer_item_id'] = $dataObject->getItemId()->getManufacturerItemId();
		}

		if ($prodHlpr->hasEavAttr('brand_name')) {
			// setting brand_name attribute
			$data['brand_name'] = $dataObject->getExtendedAttributes()->getBrandName();
		}

		if ($prodHlpr->hasEavAttr('brand_description')) {
			// setting brand_description attribute
			$brandDescription = $dataObject->getExtendedAttributes()->getBrandDescription();
			foreach ($brandDescription as $bDesc) {
				if (trim(strtoupper($bDesc['lang'])) === strtoupper($this->getDefaultStoreLanguageCode())) {
					$data['brand_description'] = $bDesc['description'];
					break;
				}
			}
		}

		if ($prodHlpr->hasEavAttr('buyer_name')) {
			// setting buyer_name attribute
			$data['buyer_name'] = $dataObject->getExtendedAttributes()->getBuyerName();
		}

		if ($prodHlpr->hasEavAttr('buyer_id')) {
			// setting buyer_id attribute
			$data['buyer_id'] = $dataObject->getExtendedAttributes()->getBuyerId();
		}

		if ($prodHlpr->hasEavAttr('companion_flag')) {
			// setting companion_flag attribute
			$data['companion_flag'] = $dataObject->getExtendedAttributes()->getCompanionFlag();
		}

		if ($prodHlpr->hasEavAttr('hazardous_material_code')) {
			// setting hazardous_material_code attribute
			$data['hazardous_material_code'] = $dataObject->getExtendedAttributes()->getHazardousMaterialCode();
		}

		if ($prodHlpr->hasEavAttr('is_hidden_product')) {
			// setting is_hidden_product attribute
			$data['is_hidden_product'] = $dataObject->getExtendedAttributes()->getIsHiddenProduct();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_shipping_mass_unit_of_measure')) {
			// setting item_dimension_shipping_mass_unit_of_measure attribute
			$data['item_dimension_shipping_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_shipping_mass_weight')) {
			// setting item_dimension_shipping_mass_weight attribute
			$data['item_dimension_shipping_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getWeight();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_display_mass_unit_of_measure')) {
			// setting item_dimension_display_mass_unit_of_measure attribute
			$data['item_dimension_display_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getMassUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_display_mass_weight')) {
			// setting item_dimension_display_mass_weight attribute
			$data['item_dimension_display_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getWeight();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_display_packaging_unit_of_measure')) {
			// setting item_dimension_display_packaging_unit_of_measure attribute
			$data['item_dimension_display_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()
				->getPackaging()->getUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_display_packaging_width')) {
			// setting item_dimension_display_packaging_width attribute
			$data['item_dimension_display_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getWidth();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_display_packaging_length')) {
			// setting item_dimension_display_packaging_length attribute
			$data['item_dimension_display_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getLength();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_display_packaging_height')) {
			// setting item_dimension_display_packaging_height attribute
			$data['item_dimension_display_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getHeight();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_shipping_packaging_unit_of_measure')) {
			// setting item_dimension_shipping_packaging_unit_of_measure attribute
			$data['item_dimension_shipping_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()
				->getPackaging()->getUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_shipping_packaging_width')) {
			// setting item_dimension_shipping_packaging_width attribute
			$data['item_dimension_shipping_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getWidth();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_shipping_packaging_length')) {
			// setting item_dimension_shipping_packaging_length attribute
			$data['item_dimension_shipping_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getLength();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_shipping_packaging_height')) {
			// setting item_dimension_shipping_packaging_height attribute
			$data['item_dimension_shipping_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getHeight();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_mass_unit_of_measure')) {
			// setting item_dimension_carton_mass_unit_of_measure attribute
			$data['item_dimension_carton_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getMassUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_mass_weight')) {
			// setting item_dimension_carton_mass_weight attribute
			$data['item_dimension_carton_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getWeight();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_packaging_unit_of_measure')) {
			// setting item_dimension_carton_packaging_unit_of_measure attribute
			$data['item_dimension_carton_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()
				->getPackaging()->getUnitOfMeasure();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_packaging_width')) {
			// setting item_dimension_carton_packaging_width attribute
			$data['item_dimension_carton_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getWidth();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_packaging_length')) {
			// setting item_dimension_carton_packaging_length attribute
			$data['item_dimension_carton_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getLength();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_packaging_height')) {
			// setting item_dimension_carton_packaging_height attribute
			$data['item_dimension_carton_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getHeight();
		}

		if ($prodHlpr->hasEavAttr('item_dimension_carton_type')) {
			// setting item_dimension_carton_type attribute
			$data['item_dimension_carton_type'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getType();
		}

		if ($prodHlpr->hasEavAttr('lot_tracking_indicator')) {
			// setting lot_tracking_indicator attribute
			$data['lot_tracking_indicator'] = $dataObject->getExtendedAttributes()->getLotTrackingIndicator();
		}

		if ($prodHlpr->hasEavAttr('ltl_freight_cost')) {
			// setting ltl_freight_cost attribute
			$data['ltl_freight_cost'] = $dataObject->getExtendedAttributes()->getLtlFreightCost();
		}

		if ($prodHlpr->hasEavAttr('manufacturing_date')) {
			// setting manufacturing_date attribute
			$data['manufacturing_date'] = $dataObject->getExtendedAttributes()->getManufacturer()->getDate();
		}

		if ($prodHlpr->hasEavAttr('manufacturer_name')) {
			// setting manufacturer_name attribute
			$data['manufacturer_name'] = $dataObject->getExtendedAttributes()->getManufacturer()->getName();
		}

		if ($prodHlpr->hasEavAttr('manufacturer_manufacturer_id')) {
			// setting manufacturer_manufacturer_id attribute
			$data['manufacturer_manufacturer_id'] = $dataObject->getExtendedAttributes()->getManufacturer()->getId();
		}

		if ($prodHlpr->hasEavAttr('may_ship_expedite')) {
			// setting may_ship_expedite attribute
			$data['may_ship_expedite'] = $dataObject->getExtendedAttributes()->getMayShipExpedite();
		}

		if ($prodHlpr->hasEavAttr('may_ship_international')) {
			// setting may_ship_international attribute
			$data['may_ship_international'] = $dataObject->getExtendedAttributes()->getMayShipInternational();
		}

		if ($prodHlpr->hasEavAttr('may_ship_usps')) {
			// setting may_ship_usps attribute
			$data['may_ship_usps'] = $dataObject->getExtendedAttributes()->getMayShipUsps();
		}

		if ($prodHlpr->hasEavAttr('safety_stock')) {
			// setting safety_stock attribute
			$data['safety_stock'] = $dataObject->getExtendedAttributes()->getSafetyStock();
		}

		if ($prodHlpr->hasEavAttr('sales_class')) {
			// setting sales_class attribute
			$data['sales_class'] = $dataObject->getExtendedAttributes()->getSalesClass();
		}

		if ($prodHlpr->hasEavAttr('serial_number_type')) {
			// setting serial_number_type attribute
			$data['serial_number_type'] = $dataObject->getExtendedAttributes()->getSerialNumberType();
		}

		if ($prodHlpr->hasEavAttr('service_indicator')) {
			// setting service_indicator attribute
			$data['service_indicator'] = $dataObject->getExtendedAttributes()->getServiceIndicator();
		}

		if ($prodHlpr->hasEavAttr('ship_group')) {
			// setting ship_group attribute
			$data['ship_group'] = $dataObject->getExtendedAttributes()->getShipGroup();
		}

		if ($prodHlpr->hasEavAttr('ship_window_min_hour')) {
			// setting ship_window_min_hour attribute
			$data['ship_window_min_hour'] = $dataObject->getExtendedAttributes()->getShipWindowMinHour();
		}

		if ($prodHlpr->hasEavAttr('ship_window_max_hour')) {
			// setting ship_window_max_hour attribute
			$data['ship_window_max_hour'] = $dataObject->getExtendedAttributes()->getShipWindowMaxHour();
		}

		if ($prodHlpr->hasEavAttr('street_date')) {
			// setting street_date attribute
			$data['street_date'] = $dataObject->getExtendedAttributes()->getStreetDate();
		}

		if ($prodHlpr->hasEavAttr('style_id')) {
			// setting style_id attribute
			$data['style_id'] = $dataObject->getExtendedAttributes()->getStyleId();
		}

		if ($prodHlpr->hasEavAttr('style_description')) {
			// setting style_description attribute
			$data['style_description'] = $dataObject->getExtendedAttributes()->getStyleDescription();
		}

		if ($prodHlpr->hasEavAttr('supplier_name')) {
			// setting supplier_name attribute
			$data['supplier_name'] = $dataObject->getExtendedAttributes()->getSupplierName();
		}

		if ($prodHlpr->hasEavAttr('supplier_supplier_id')) {
			// setting supplier_supplier_id attribute
			$data['supplier_supplier_id'] = $dataObject->getExtendedAttributes()->getSupplierSupplierId();
		}

		if ($prodHlpr->hasEavAttr('size')) {
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
	 * @param Varien_Object $dataObject, the object with data needed to add eb2c specific attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set attributes data to
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
						'[ %s ] The following error has occurred while adding eb2c specific attributes to product for Item Master Feed (%d)',
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
	 * @param Varien_Object $dataObject, the object with data needed to add custom attributes to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set custom data to
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
						'[ %s ] The following error has occurred while adding custom attributes to product for Item Master Feed (%d)',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}
		return $this;
	}

	/**
	 * Adding configurable data to product
	 *
	 * @param Varien_Object $dataObject, the object with data needed to add configurable data to a product
	 * @param Mage_Catalog_Model_Product $productObject, the product object to set configurable data to
	 * @return self
	 */
	protected function _addConfigurableDataToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$currentSku        = trim(strtoupper($dataObject->getItemId()->getClientItemId()));
		$productType       = trim(strtolower($dataObject->getProductType()));
		$configurableSku   = trim($dataObject->getExtendedAttributes()->getStyleId());

		/*
	 	 * When we have a Simple product with a parent sku /different/ from our own sku, we may well
		 * be a child of a configurable product.
		 */
		if ( !empty($configurableSku) && $productType === Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && ($currentSku !== strtoupper($configurableSku))) {
			$configurableSku = $dataObject->getCatalogId() . '-' . $configurableSku; // Prepend the catalogId to the SKU - what we get doesn't have it
			$configurableProduct = $this->_loadProductBySku($configurableSku); // Load the Configurable SKU
			if (!$configurableProduct->getId()) {                              // Configurable doesn't exists, let's dummy up a parent
				$configurableProduct = $this->_createConfigurableProduct(
					$configurableSku,
					$this->getDefaultAttributeSetId(),
					$dataObject->getConfigurableAttributes()
				);
			}

			if (trim(strtolower($configurableProduct->getTypeId())) === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
				$this->_attachProductToConfigurable($configurableProduct, $productObject);
			}
		}
		return $this;
	}
}
