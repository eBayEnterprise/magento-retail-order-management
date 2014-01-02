<?php
class TrueAction_Eb2cProduct_Model_Feed_Processor
{
	const DEFAULT_BATCH_SIZE = 128;
	/**
	 * default language code
	 */
	protected $_defaultLanguageCode;
	/**
	 * A map of store Ids to LanguageCodes
	 */
	protected $_storeLanguageCodeMap = array();
	/**
	 * We keep a tally of CustomAttribute errors for help analyzing feeds
	 */
	protected $_customAttributeErrors = array();
	const CA_ERROR_INVALID_LANGUAGE   = 'invalid_language';
	const CA_ERROR_INVALID_OP_TYPE    = 'invalid_operation_type';
	const CA_ERROR_MISSING_OP_TYPE    = 'missing_operation_type';
	const CA_ERROR_MISSING_ATTRIBUTE  = 'missing_attribute';
	protected $_extKeys = array(
		'brand_name',
		'buyer_id',
		'color',
		'companion_flag',
		'country_of_origin',
		'gift_card_tender_code',
		'hazardous_material_code',
		'long_description',
		'lot_tracking_indicator',
		'ltl_freight_cost',
		'may_ship_expedite',
		'may_ship_international',
		'may_ship_usps',
		'msrp',
		'price',
		'safety_stock',
		'sales_class',
		'serial_number_type',
		'ship_group',
		'ship_window_max_hour',
		'ship_window_min_hour',
		'short_description',
		'street_date',
		'style_description',
		'style_id',
		'supplier_name',
		'supplier_supplier_id',
	);
	protected $_extKeysBool = array(
		'allow_gift_message',
		'back_orderable',
		'gift_wrap',
		'gift_wrapping_available',
		'is_hidden_product',
		'service_indicator',
	);
	protected $_updateBatchSize = self::DEFAULT_BATCH_SIZE;
	protected $_deleteBatchSize = self::DEFAULT_BATCH_SIZE;
	protected $_maxTotalEntries = self::DEFAULT_BATCH_SIZE;

	/**
	 * @var array, hold collection array of attribute codes
	 */
	protected $_attributeOptions = array();

	/**
	 * @var int, hold product entity type id
	 */
	protected $_entityTypeId = 0;

	/**
	 * initialize
	 */
	public function __construct()
	{
		$helper = Mage::helper('eb2cproduct');
		$config = $helper->getConfigModel();
		$this->_defaultLanguageCode = strtolower($helper->getDefaultLanguageCode());
		$this->_initLanguageCodeMap();
		$this->_updateBatchSize = $config->processorUpdateBatchSize;
		$this->_deleteBatchSize = $config->processorDeleteBatchSize;
		$this->_maxTotalEntries = $config->processorMaxTotalEntries;

		$this->_entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();

		foreach (explode(',', $config->attributesCodeList) as $attributeCode) {
			$this->_attributeOptions[$attributeCode] = $this->_getAttributeOptionCollection($attributeCode);
		}
	}

	/**
	 * get attribute option collection by code
	 * @param string $attributeCode, the attribute code
	 * @return Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection
	 */
	protected function _getAttributeOptionCollection($attributeCode)
	{
		return Mage::getResourceModel('eav/entity_attribute_option_collection')
			->join(
				array('attributes' => 'eav/attribute'),
				'main_table.attribute_id = attributes.attribute_id',
				array('attribute_code')
			)
			->setStoreFilter(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID, false)
			->addFieldToFilter('attributes.attribute_code', $attributeCode)
			->addFieldToFilter('attributes.entity_type_id', $this->_entityTypeId)
			->addExpressionFieldToSelect('lcase_value', 'LCASE({{value}})', 'value');
	}

	/**
	 * Creates a map of language codes (as dervied from the store view code) to store ids
	 * @todo The parse-language-from-store-view code might need to be closer to Eb2cCore.
	 */
	protected function _initLanguageCodeMap()
	{
		foreach (Mage::app()->getStores() as $storeId) {
			$storeCodeParsed = explode('_', Mage::app()->getStore($storeId)->getCode(), 3);
			if (count($storeCodeParsed) > 2) {
				$langCode = strtolower(Mage::helper('eb2ccore')->mageToXmlLangFrmt($storeCodeParsed[2]));
				$this->_storeLanguageCodeMap[$langCode] = Mage::app()->getStore($storeId)->getId();
			} else {
				Mage::log(
					sprintf('Incompatible Store View Name ignored: "%s"', Mage::app()->getStore($storeId)->getName()),
					Zend_log::INFO
				);
			}
		}
		return $this;
	}
	/**
	 * transform each product item data to be imported and processing
	 * adding or updating item to Magento catalog
	 * @param ArrayIterator $dataObjectList, list of product data to be imported
	 * @return self
	 */
	public function processUpdates(ArrayIterator $dataObjectList)
	{
		Mage::log(sprintf('[ %s ] Processing %d updates.', __CLASS__, count($dataObjectList)), Zend_Log::INFO);
		foreach ($dataObjectList as $dataObject) {
			$dataObject = $this->_transformData($dataObject);
			$this->_synchProduct($dataObject);
		}
		$this->_logFeedErrorStatistics();
		return $this;
	}
	/**
	 * log any custom error that occurred while processing feed import and
	 * set _customAttributeErrors property to an empty array.
	 * @return self
	 */
	protected function _logFeedErrorStatistics()
	{
		foreach($this->_customAttributeErrors as $err) {
			Mage::log(sprintf('[ %s ] Feed Error Statistics %s', __CLASS__, print_r($err, true)), Zend_Log::DEBUG);
		}
		array_splice($this->_customAttributeErrors, 0); // truncate array
		return $this;
	}
	public function processDeletions($dataObjectList)
	{
		Mage::log(sprintf('[ %s ] Processing %d deletes.', __CLASS__, count($dataObjectList)));
		foreach ($dataObjectList as $dataObject) {
			$this->_deleteItem($dataObject);
		}
		return $this;
	}
	/**
	 * Transform the data extracted from the feed into a generic set of feed data
	 * including item_id, base_attributes, extended_attributes and custome_attributes
	 * @param  Varien_Object $dataObject Data extraced from the feed
	 * @return Varien_Object             Data that can be imported to a product
	 */
	protected function _transformData(Varien_Object $dataObject)
	{
		$helper = Mage::helper('eb2cproduct');
		$outData = new Varien_Object(array(
			'catalog_id' => $dataObject->getData('catalog_id'),
			'gsi_client_id' => $dataObject->getData('gsi_client_id'),
			'gsi_store_id' => $dataObject->getData('gsi_store_id'),
			'operation_type' => $dataObject->getData('operation_type'),
		));
		$outData->setData('item_id', new Varien_Object(array(
			'client_item_id' => $dataObject->getData('client_item_id'),
			'client_alt_item_id' => $dataObject->hasData('client_alt_item_id') ? $dataObject->getData('client_alt_item_id') : false,
			'manufacturer_item_id' => $dataObject->hasData('manufacturer_item_id') ? $dataObject->getData('manufacturer_item_id') : false,
		)));
		// add the unique id from the content feed.
		if ($dataObject->hasData('unique_id')) {
			$outData->getData('item_id')->setData('client_item_id', $dataObject->getData('unique_id'));
		}
		// add iShip HTS codes, which will be a serialized array of hts codes and associated data
		if ($dataObject->hasData('hts_codes')) {
			$outData->setData('hts_codes', serialize($dataObject->getData('hts_codes')));
		}
		// prepare base attributes
		$baseAttributes = new Varien_Object();
		$baseAttributes->setData('drop_shipped', $helper->parseBool($dataObject->getData('is_drop_shipped')));
		foreach (array('catalog_class', 'item_description', 'item_type', 'item_status', 'tax_code', 'title') as $key) {
			if ($dataObject->hasData($key)) {
				$baseAttributes->setData($key, $dataObject->getData($key));
			}
		}
		$outData->setData('base_attributes', $baseAttributes);
		$outData->setData('drop_ship_supplier_information', new Varien_Object(array(
			// Name of the Drop Ship Supplier fulfilling the item
			'supplier_name' => $dataObject->hasData('drop_ship_supplier_name') ? $dataObject->getData('drop_ship_supplier_name') : false,
			// Unique code assigned to this supplier.
			'supplier_number' => $dataObject->hasData('drop_ship_supplier_number') ? $dataObject->getData('drop_ship_supplier_number') : false,
			// Id or SKU used by the drop shipper to identify this item.
			'supplier_part_number' => $dataObject->hasData('drop_ship_supplier_part_number') ? $dataObject->getData('drop_ship_supplier_part_number') : false,
		)));
		// prepare the extended attributes
		$extData = new Varien_Object();
		foreach (array('item_dimension_shipping', 'item_dimension_display', 'item_dimension_carton') as $section) {
			$extData->setData($section, new Varien_Object(array(
				// Shipping weight of the item.
				'mass_unit_of_measure' => $dataObject->hasData(sprintf('%s_mass_unit_of_measure', $section)) ?
				$dataObject->getData(sprintf('%s_mass_unit_of_measure', $section)) : false,
				// Shipping weight of the item.
				'weight' => $dataObject->hasData(sprintf('%s_mass_weight', $section)) ? $dataObject->getData(sprintf('%s_mass_weight', $section)) : false,
				'packaging' => new Varien_Object(array(
					// Unit of measure used for these dimensions.
					'unit_of_measure' => $dataObject->hasData(sprintf('%s_packaging_mass_unit_of_measure', $section)) ?
					$dataObject->getData(sprintf('%s_packaging_mass_unit_of_measure', $section)) : false,
					'width' => $dataObject->hasData(sprintf('%s_packaging_width', $section)) ? $dataObject->getData(sprintf('%s_packaging_width', $section)) : false,
					'length' => $dataObject->hasData(sprintf('%s_packaging_length', $section)) ? $dataObject->getData(sprintf('%s_packaging_length', $section)) : false,
					'height' => $dataObject->hasData(sprintf('%s_packaging_height', $section)) ? $dataObject->getData(sprintf('%s_packaging_height', $section)) : false,
				)),
			)));
		}
		$extData->getItemDimensionCarton()->setData(
			'type', $dataObject->hasData('item_dimension_carton_type') ? $dataObject->getData('item_dimension_carton_type') : false
		);
		$extData->setData('manufacturer', new Varien_Object(array(
			// Date the item was build by the manufacturer.
			'date' => $dataObject->hasData('manufacturer_date') ? $dataObject->getData('manufacturer_date') : false,
			// Company name of manufacturer.
			'name' => $dataObject->hasData('manufacturer_name') ? $dataObject->getData('manufacturer_name') : false,
			// Unique identifier to denote the item manufacturer.
			'id' => $dataObject->hasData('manufacturer_id') ? $dataObject->getData('manufacturer_id') : false,
		)));
		// @todo Does this actually do anything?
		$extData->setData('size_attributes', new Varien_Object(array(
			'size' => $dataObject->getData('size')
		)));
		// @todo Does this actually do anything?
		$extData->setData('color_attributes', new Varien_Object(array(
			'color' => $dataObject->getData('color')
		)));
		foreach ($this->_extKeys as $key) {
			if ($dataObject->hasData($key)) {
				$extData->setData($key, $dataObject->getData($key));
			}
		}
		// handle values that need to be booleans
		foreach ($this->_extKeysBool as $key) {
			if ($dataObject->hasData($key)) {
				$extData->setData($key, $helper->parseBool($dataObject->getData($key)));
			}
		}
		$this->_preparePricingEventData($dataObject, $extData);
		// @todo clean up circular assignments
		$outData->setData('long_description', $this->_getLocalizations($extData, 'long_description'));
		$outData->setData('short_description', $this->_getLocalizations($extData, 'short_description'));
		$outData->setData('extended_attributes', $extData);
		$extendedAttributes = $outData->getExtendedAttributes()->getData();
		$giftWrapData = array();
		if (!empty($extendedAttributes) && isset($extendedAttributes['gift_wrap'])) {
			$giftWrapData['gift_wrap'] = Mage::helper('eb2cproduct')->parseBool($extendedAttributes['gift_wrap']);
		}
		$extData->addData($giftWrapData);
		///////
		$customAttributes = $dataObject->getCustomAttributes();
		if( $customAttributes ) {
			$this->_prepareCustomAttributes($customAttributes, $outData);
		}
		if ($dataObject->hasData('product_links')) {
			$outData->setData('product_links', $dataObject->getData('product_links'));
		}
		// let's check if there's category link
		if ($dataObject->hasData('category_links')) {
			$outData->setData('category_links', $dataObject->getData('category_links'));
		}
		return $outData;
	}
	/**
	 * delete product.
	 * @param Varien_Object $dataObject, the object with data needed to delete the product
	 * @return self
	 */
	protected function _deleteItem(Varien_Object $dataObject)
	{
		$sku = $dataObject->getClientItemId();
		if ($sku) {
			// we have a valid item, let's check if this product already exists in Magento
			$product = Mage::helper('eb2cproduct')->loadProductBySku($sku);
			if ($product->getId()) {
				try {
					// deleting the product from magento
					$product->delete();
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
	 * Gets the localizations for a field
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the extended attribute product data
	 * @param fieldName field to extract localizations from
	 * @return array of langCode=>Translations for the given fieldName
	 */
	protected function _getLocalizations(Varien_Object $dataObject, $fieldName)
	{
		$data = array();
		$localizationSet = $dataObject->getData($fieldName);
		if (!empty($localizationSet)) {
			foreach( $localizationSet as $localization ) {
				$data[strtolower($localization['lang'])] = $localization[$fieldName];
			}
		}
		return $data;
	}
	/**
	 * Transform valid CustomAttribute data into a readily saveable form.
	 * @param customAttributes the array of custom attributes
	 * @param outData where to place the prepared attributes
	 */
	protected function _prepareCustomAttributes($customAttributes, Varien_Object $outData)
	{
		$helper = Mage::helper('eb2cproduct');
		$attributeSetId = $helper->getDefaultProductAttributeSetId();
		foreach($customAttributes as $attribute) {
			if ($attribute['name'] === 'AttributeSet') {
				$attributeSetId = $attribute['value'];
				break;
			}
		}
		$customAttributeSet = $helper->getCustomAttributeCodeSet($attributeSetId);
		$cookedAttributes = array();
		foreach ($customAttributes as $customAttribute) {
			if( $customAttribute['name'] === 'ProductType' ) {
				$outData->setData('product_type', strtolower($customAttribute['value']));
				continue; // ProductType is specially handled, nothing more to do.
			}
			if( $customAttribute['name'] === 'ConfigurableAttributes' ) {
				$this->_processConfigurableAttributes($customAttribute, $outData);
				continue; // ConfigurableAttributes are specially handled, nothing more to do.
			}
			$lang = isset($customAttribute['lang']) ? strtolower($customAttribute['lang']) : $this->_defaultLanguageCode;
			if (!isset($this->_storeLanguageCodeMap[$lang])) {
				$this->_customAttributeErrors[self::CA_ERROR_INVALID_LANGUAGE]++;
				continue;
			}
			if (in_array($customAttribute['name'], $customAttributeSet)) {
				if (isset($customAttribute['operation_type'])) {
					$op = $customAttribute['operation_type'];
					if( $op === 'Add') {
						$cookedAttributes[$customAttribute['name']][$lang] = $customAttribute['value'];
					} elseif( $op === 'Delete') {
						$cookedAttributes[$customAttribute['name']][$lang] = null;
					}
					else {
						$this->_customAttributeErrors[self::CA_ERROR_INVALID_OP_TYPE]++;
					}
				} else {
					$this->_customAttributeErrors[self::CA_ERROR_MISSING_OP_TYPE]++;
				}
			} else {
				$this->_customAttributeErrors[self::CA_ERROR_MISSING_ATTRIBUTE]++;
			}
		}
		if (!empty($cookedAttributes)) {
			$outData->setData('custom_attributes', $cookedAttributes);
		}
		return $this;
	}
	/**
	 * Special data processor for the product configurable_attributes custom attribute.
	 * Assigns the CONFIGURABLEATTRIBUTES custom attribute to the product data as configurable_attributes.
	 *
	 * @param  array         $attrData   Map of custom attribute data: name, operation type, lang and value
	 * @param  Varien_Object $outData    Varien_Object containing all transformed product feed data
	 */
	protected function _processConfigurableAttributes($attrData, Varien_Object $outData)
	{
		$configurableAttributeData = array();
		$configurableAttributes = explode(',', $attrData['value']);
		foreach ($configurableAttributes as $attrCode) {
			$superAttribute  = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attrCode);
			$configurableAtt = Mage::getModel('catalog/product_type_configurable_attribute')->setProductAttribute($superAttribute);
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
		$outData->setData('configurable_attributes_data', $configurableAttributeData);
	}
	/**
	 * Manages the pricing for events
	 */
	protected function _preparePricingEventData(Varien_Object $dataObject, Varien_Object $outData)
	{
		if ($dataObject->hasEbcPricingEventNumber()) {
			$priceIsVatInclusive = Mage::helper('eb2cproduct')->parseBool($dataObject->getPriceVatInclusive());
			$data = array(
				'price' => $dataObject->getPrice(),
				'msrp' => $dataObject->getMsrp(),
				'price_is_vat_inclusive' => $priceIsVatInclusive,
			);
			if ($dataObject->getEbcPricingEventNumber()) {
				$startDate = new DateTime($dataObject->getStartDate());
				$startDate->setTimezone(new DateTimeZone('UTC'));
				$data['special_from_date'] = $startDate->format('Y-m-d H:i:s');
				if ($dataObject->getEndDate()) {
					$endDate = new DateTime($dataObject->getEndDate());
					$endDate->setTimezone(new DateTimeZone('UTC'));
					$data['special_to_date'] = $endDate->format('Y-m-d H:i:s');
				}
			}
			$outData->addData($data);
		}
		return $this;
	}
	/**
	 * getting category attribute set id.
	 *
	 * @return int, the category attribute set id
	 */
	protected function _getCategoryAttributeSetId()
	{
		return (int) Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'attribute_set_id')
			->getEntityType()
			->getDefaultAttributeSetId();
	}
	/**
	 * Gets the option id for the option within the given attribute
	 *
	 * @param string $code, The attribute code
	 * @param string $option, The option within the attribute
	 * @return int
	 * @throws TrueAction_Eb2cProduct_Model_Feed_Exception if attributeCode is not found.
	 */
	protected function _getAttributeOptionId($code, $option)
	{
		if (!isset($this->_attributeOptions[$code])) {
			throw new TrueAction_Eb2cProduct_Model_Feed_Exception("Cannot get attribute option id for undefined attribute code '$code'.");
		}

		$collection = $this->_attributeOptions[$code];
		$targetOption = $collection->getItemByColumnValue('value', strtolower($option));
		if ($targetOption) {
			return (int) $targetOption->getId();
		}
		return 0;
	}
	/**
	 * Add new attribute aption and return the newly inserted option id
	 *
	 * @param string $attribute, the attribute to which the new option is added
	 * @param array $newOption, the new option itself
	 * 	The format of the 'newOption' is an array:
	 * 		'code'          => 'admin_value',
	 *		'localizations' => array(
	 *				'en-US' => 'English',
	 *				'he-IL' => 'עברית',
	 * 				'ja-JP' => '日本人',
	 *			)
	 * @return int, the newly inserted option id
	 */
	protected function _addOptionToAttribute($attribute, $newOption)
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
		if (!$attributeId) {
			throw new TrueAction_Eb2cProduct_Model_Feed_Exception("Cannot add option to undefined attribute code '$attribute'.");
		}
		// This entire set of options belongs to this attribute:
		$newAttributeOption['attribute_id'] = $attributeId;
		// Default Store (i.e., 'Admin') takes the value of 'code'.
		$values[Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID] = $newOption['code'];
		foreach($this->_storeLanguageCodeMap as $lang => $storeId) {
			if (!empty($newOption['localization'][$lang]) ) {
				// Each store view now gets its own localization, if one has been provided.
				$values[$storeId] = $newOption['localization'][$lang];
			}
		}
		$newAttributeOption['value'] = array('replace_with_primary_key' => $values);
		$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
		try {
			$setup->addAttributeOption($newAttributeOption);
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] Error creating Admin option "%s" for attribute "%s": %s',
					__CLASS__, $newOption['code'], $attribute, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $this->_getAttributeOptionId($attribute, $newOption['code']); // Get the newly created id
	}
	/**
	 * add/update magento product with eb2c data
	 * @param Varien_Object $item, the object with data needed to add/update a magento product
	 * @return self
	 */
	protected function _synchProduct(Varien_Object $item)
	{
		$sku = $item->getItemId()->getClientItemId();
		if ($sku === '' || is_null($sku)) {
			Mage::log(sprintf('[ %s ] Cowardly refusing to import item with no client_item_id.', __CLASS__), Zend_Log::WARN);
			return;
		}
		$product = Mage::helper('eb2cproduct')->prepareProductModel($sku, $item->getBaseAttributes()->getItemDescription());
		$productData = new Varien_Object();
		if ($item->hasProductType()) {
			$productData->setData('type_id', $item->getProductType());
		}
		if ($item->getExtendedAttributes()->getItemDimensionShipping()->hasWeight()) {
			$productData->setData('weight', $item->getExtendedAttributes()->getItemDimensionShipping()->getWeight());
		}
		if ($item->getExtendedAttributes()->getItemDimensionShipping()->hasData('mass')) {
			$productData->setData('mass', $item->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure());
		}
		if( $item->getBaseAttributes()->getCatalogClass()) {
			$productData->setData('visibility', $this->_getVisibilityData($item));
		}
		if ($item->getBaseAttributes()->getItemStatus()) {
			$productData->setData('status', $this->_getItemStatusData($item->getBaseAttributes()->getItemStatus()));
		}
		if ($item->getExtendedAttributes()->getMsrp()) {
			$productData->setData('msrp', $item->getExtendedAttributes()->getMsrp());
		}
		if ($item->getExtendedAttributes()->getPrice()) {
			$productData->setData('price', $item->getExtendedAttributes()->getPrice());
		}
		if ($item->getItemId()->getClientItemId()) {
			$productData->setData('url_key', $item->getItemId()->getClientItemId());
		}
		if ($item->getProductLinks()) {
			$productData->setData('unresolved_product_links', serialize($item->getProductLinks()));
		}
		// setting category data
		if( $item->getCategoryLinks() ) {
			$productData->setData('category_ids', $this->_preparedCategoryLinkData($item));
		}
		// setting the product's color to a Magento Attribute Id
		if ($item->getExtendedAttributes()->hasData('color')) {
			$productData->setData('color', $this->_getProductColorOptionId($item->getExtendedAttributes()->getData('color')));
		}
		$configurableAttributesData = Mage::helper('eb2cproduct')
			->getConfigurableAttributesData($productData->getTypeId(), $item, $product);
		if ($configurableAttributesData) {
			$productData->setData('configurable_attributes_data', $configurableAttributesData);
		}
		// Gathers up all translatable fields, applies the defaults;  whatever's left are
		// alternate language codes that have to be applied /after/ the default product is saved
		$translations = $this->_applyDefaultTranslations(
			$productData, $this->_mergeTranslations($item)
		);
		// mark all products that have just been imported as not being clean
		// Find out that setting the 'is_clean' attribute to false wasn't add the attribute relationship
		// to the catalog_product_entity_int table, that's why the cleaner wasn't running.
		$productData->setData('is_clean', 0);
		$product->addData($productData->getData())
			->addData($this->_getEb2cSpecificAttributeData($item));
		try {
			$product->save(); // saving the product
		} catch(PDOException $e) {
			Mage::logException($e);
		}
		$this->_addStockItemDataToProduct($item, $product); // @todo: only do if !configurable product type
		// Alternate languages /must/ happen after default product has been saved:
		if( $translations ) {
			$this->_applyAlternateTranslations($product->getId(), $translations);
		}
		return $this;
	}
	/**
	 * Merges translation-enabled fields into one array.
	 * @todo Also does some mapping too, which really should be abstracted down to a lower level I think.
	 * @return array of attribute_codes => array(languages)
	 */
	protected function _mergeTranslations($item)
	{
		$translations = array();
		foreach( array('short_description', 'brand_description') as $field ) {
			if ($item->hasData($field)) {
				$translations = array_merge($translations, array($field => $item->getData($field)));
			}
		}
		if ($item->hasData('long_description')) {
			$translations = array_merge($translations, array('description' => $item->getData('long_description')));
		}
		if ($item->hasData('custom_attributes')) {
			$translations = array_merge($translations, $item->getData('custom_attributes'));
		}
		$productTitleSet = $this->_getProductTitleSet($item);
		if (!empty($productTitleSet)) {
			$translations = array_merge($translations, array('name' => $productTitleSet));
		}
		return $translations;
	}
	/**
	 * Applies default translations, returns an array of what still needs processing
	 * @return array of attribute_codes => array(languages)
	 */
	protected function _applyDefaultTranslations($productData, $translations)
	{
		// For our translation-enabled fields, let's assign the default. Once assigned, remove it from
		// the translations array - so if we have no other languages but the default, we'll be done.
		foreach (array_keys($translations) as $code) {
			$productData->setData($code, $translations[$code][$this->_defaultLanguageCode]);
			unset($translations[$code][$this->_defaultLanguageCode]);
			if (empty($translations[$code])) {
				unset($translations[$code]);
			}
		}
		return $translations;
	}
	/**
	 * Apply all other translations to our product.
	 * @param int $productId
	 * @param array $translations
	 */
	protected function _applyAlternateTranslations($productId, array $translations)
	{
		foreach($this->_storeLanguageCodeMap as $lang => $storeId) {
			if ($lang === $this->_defaultLanguageCode) {
				continue; // Skip default language - it's already been done
			}
			/**
			 * @see http://www.fabrizio-branca.de/whats-wrong-with-the-new-url-keys-in-magento.html for
			 * details about why url_key has to be set specially. It's a Magento 1.13 'feature.'
			 */
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$altProduct = Mage::getModel('catalog/product')->load($productId)
				->setStoreId($storeId)
				->setUrlKey(false);
			foreach (array_keys($translations) as $code) {
				$value = false; // false means "Use Default View"
				if (isset($translations[$code][$lang])) {
					$value = $translations[$code][$lang];
				}
				$altProduct->setData($code, $value);
			}
			$altProduct->save();
		}
		return $this;
	}
	/**
	 * Get the id of the Color-Attribute Option for this specific color. Create it if it doesn't exist.
	 * @todo This is probably more specific than we really need it to be.
	 *       All attributes should be processed in a similar manner - special handling of 'color' should be revisited.0
	 * @param Varien_Object $dataObject, the object with data needed to create dummy product
	 * @return int, the option id
	 */
	protected function _getProductColorOptionId($colorData)
	{
		$colorOptionId = 0;
		if (!empty($colorData)) {
			$colorOptionId = $this->_getAttributeOptionId('color', $colorData['code']);
			if (!$colorOptionId) {
				$colorOptionId = $this->_addOptionToAttribute('color', $colorData);
			}
		}
		return $colorOptionId;
	}
	/**
	 * Add stock item data to a product.
	 * @param Varien_Object $dataObject, the object with data needed to add the stock data to the product
	 * @param Mage_Catalog_Model_Product $parentProductObject, the product object to set stock item data to
	 * @return self
	 */
	protected function _addStockItemDataToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productObject);
		$stockData = array(
			'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
			'product_id' => $productObject->getId(),
		);
		if ($productObject->getTypeId() !== Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
			$stockData = $stockData + array(
				'use_config_backorders' => false,
				'backorders' => $dataObject->getExtendedAttributes()->getBackOrderable(),
			);
		} else {
			// Always consider config products in stock: when the config product is out of stock, it will
			// never show up. When it is in stock, it will show up when its used products are in stock but not
			// when they are all out of stock, which I believe is the expected behavior.
			$stockData = $stockData + array('is_in_stock' => 1);
		}
		$stockItem->addData($stockData)->save();
		return $this;
	}
	/**
	 * Return array of titles, keyed by lang code
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the default product title
	 * @return array
	 */
	protected function _getProductTitleSet(Varien_Object $dataObject)
	{
		$titleData = array();
		$titles = $dataObject->getBaseAttributes()->getTitle();
		if(isset($titles) && !empty($titles)) {
			foreach ($titles as $title) {
				// It's possible $title['title'] doesn't exist, eg we receive <Title xml:lang='en-US' />
				// As per spec, it's required
				if (array_key_exists('title', $title) && array_key_exists('lang', $title)) {
					$titleData[strtolower($title['lang'])] = $title['title'];
				}
			}
		}
		return $titleData;
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
		$catalogClass = strtolower(trim($dataObject->getBaseAttributes()->getCatalogClass()));
		if ($catalogClass === 'regular' || $catalogClass === 'always') {
			$visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
		}
		return $visibility;
	}
	/**
	 * Translate the feed's idea of status to Magento's
	 *
	 * @param type $originalStatus as from XML feed E.g., "Active"
	 * @return Magento-ized version of status
	 */
	protected function _getItemStatusData($originalStatus)
	{
		$mageStatus = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
		if(strtolower($originalStatus) === 'active') {
			$mageStatus = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
		}
		return $mageStatus;
	}
	/**
	 * add color description per locale to a child product of using parent configurable store color attribute data.
	 * @param Mage_Catalog_Model_Product $childProductObject, the child product object
	 * @param array $parentColorDescriptionData, collection of configurable color description data
	 * @return self
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
				sprintf(
					'[ %s ] The following error has occurred while adding configurable color data to child product for Item Master Feed (%d)',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $this;
	}
	/**
	 * extract eb2c specific attribute data to be set to a product, if those attribute exists in magento
	 * @param Varien_Object $dataObject, the object with data needed to retrieve eb2c specific attribute product data
	 * @return array, composite array containing eb2c specific attribute to be set to a product
	 */
	protected function _getEb2cSpecificAttributeData(Varien_Object $dataObject)
	{
		$data = array();
		$helper = Mage::helper('eb2cproduct');
		if ($helper->hasEavAttr('is_drop_shipped')) {
			// setting is_drop_shipped attribute
			$data['is_drop_shipped'] = $dataObject->getBaseAttributes()->getDropShipped();
		}
		if ($helper->hasEavAttr('tax_code')) {
			// setting tax_code attribute
			$data['tax_code'] = $dataObject->getBaseAttributes()->getTaxCode();
		}
		if ($helper->hasEavAttr('drop_ship_supplier_name')) {
			// setting drop_ship_supplier_name attribute
			$data['drop_ship_supplier_name'] = $dataObject->getDropShipSupplierInformation()->getSupplierName();
		}
		if ($helper->hasEavAttr('drop_ship_supplier_number')) {
			// setting drop_ship_supplier_number attribute
			$data['drop_ship_supplier_number'] = $dataObject->getDropShipSupplierInformation()->getSupplierNumber();
		}
		if ($helper->hasEavAttr('drop_ship_supplier_part')) {
			// setting drop_ship_supplier_part attribute
			$data['drop_ship_supplier_part'] = $dataObject->getDropShipSupplierInformation()->getSupplierPartNumber();
		}
		if ($helper->hasEavAttr('gift_message_available')) {
			// setting gift_message_available attribute
			$data['gift_message_available'] = $dataObject->getExtendedAttributes()->getAllowGiftMessage();
			$data['use_config_gift_message_available'] = false;
		}
		if ($helper->hasEavAttr('country_of_manufacture')) {
			// setting country_of_manufacture attribute
			$data['country_of_manufacture'] = $dataObject->getExtendedAttributes()->getCountryOfOrigin();
		}
		if ($helper->hasEavAttr('gift_card_tender_code')) {
			// setting gift_card_tender_code attribute
			$data['gift_card_tender_code'] = $dataObject->getExtendedAttributes()->getGiftCardTenderCode();
		}
		if ($helper->hasEavAttr('item_type')) {
			// setting item_type attribute
			$data['item_type'] = $dataObject->getBaseAttributes()->getItemType();
		}
		if ($helper->hasEavAttr('client_alt_item_id')) {
			// setting client_alt_item_id attribute
			$data['client_alt_item_id'] = $dataObject->getItemId()->getClientAltItemId();
		}
		if ($helper->hasEavAttr('manufacturer_item_id')) {
			// setting manufacturer_item_id attribute
			$data['manufacturer_item_id'] = $dataObject->getItemId()->getManufacturerItemId();
		}
		if ($helper->hasEavAttr('brand_name')) {
			// setting brand_name attribute
			$data['brand_name'] = $dataObject->getExtendedAttributes()->getBrandName();
		}
		if ($helper->hasEavAttr('hts_codes')) {
			$data['hts_codes'] = $dataObject->getHtsCode();
		}
		// Get default lang and translations for brand_description
		if ($helper->hasEavAttr('brand_description')) {
			$data['brand_description'] = $helper->parseTranslations(
				$dataObject->getExtendedAttributes()->getBrandDescription()
			);
		}
		if ($helper->hasEavAttr('buyer_name')) {
			// setting buyer_name attribute
			$data['buyer_name'] = $dataObject->getExtendedAttributes()->getBuyerName();
		}
		if ($helper->hasEavAttr('buyer_id')) {
			// setting buyer_id attribute
			$data['buyer_id'] = $dataObject->getExtendedAttributes()->getBuyerId();
		}
		if ($helper->hasEavAttr('companion_flag')) {
			// setting companion_flag attribute
			$data['companion_flag'] = $dataObject->getExtendedAttributes()->getCompanionFlag();
		}
		if ($helper->hasEavAttr('hazardous_material_code')) {
			// setting hazardous_material_code attribute
			$data['hazardous_material_code'] = $dataObject->getExtendedAttributes()->getHazardousMaterialCode();
		}
		if ($helper->hasEavAttr('is_hidden_product')) {
			// setting is_hidden_product attribute
			$data['is_hidden_product'] = $dataObject->getExtendedAttributes()->getIsHiddenProduct();
		}
		if ($helper->hasEavAttr('item_dimension_shipping_mass_unit_of_measure')) {
			// setting item_dimension_shipping_mass_unit_of_measure attribute
			$data['item_dimension_shipping_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure();
		}
		if ($helper->hasEavAttr('item_dimension_shipping_mass_weight')) {
			// setting item_dimension_shipping_mass_weight attribute
			$data['item_dimension_shipping_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getWeight();
		}
		if ($helper->hasEavAttr('item_dimension_display_mass_unit_of_measure')) {
			// setting item_dimension_display_mass_unit_of_measure attribute
			$data['item_dimension_display_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getMassUnitOfMeasure();
		}
		if ($helper->hasEavAttr('item_dimension_display_mass_weight')) {
			// setting item_dimension_display_mass_weight attribute
			$data['item_dimension_display_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getWeight();
		}
		if ($helper->hasEavAttr('item_dimension_display_packaging_unit_of_measure')) {
			// setting item_dimension_display_packaging_unit_of_measure attribute
			$data['item_dimension_display_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()
				->getPackaging()->getUnitOfMeasure();
		}
		if ($helper->hasEavAttr('item_dimension_display_packaging_width')) {
			// setting item_dimension_display_packaging_width attribute
			$data['item_dimension_display_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getWidth();
		}
		if ($helper->hasEavAttr('item_dimension_display_packaging_length')) {
			// setting item_dimension_display_packaging_length attribute
			$data['item_dimension_display_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getLength();
		}
		if ($helper->hasEavAttr('item_dimension_display_packaging_height')) {
			// setting item_dimension_display_packaging_height attribute
			$data['item_dimension_display_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionDisplay()->getPackaging()->getHeight();
		}
		if ($helper->hasEavAttr('item_dimension_shipping_packaging_unit_of_measure')) {
			// setting item_dimension_shipping_packaging_unit_of_measure attribute
			$data['item_dimension_shipping_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()
				->getPackaging()->getUnitOfMeasure();
		}
		if ($helper->hasEavAttr('item_dimension_shipping_packaging_width')) {
			// setting item_dimension_shipping_packaging_width attribute
			$data['item_dimension_shipping_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getWidth();
		}
		if ($helper->hasEavAttr('item_dimension_shipping_packaging_length')) {
			// setting item_dimension_shipping_packaging_length attribute
			$data['item_dimension_shipping_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getLength();
		}
		if ($helper->hasEavAttr('item_dimension_shipping_packaging_height')) {
			// setting item_dimension_shipping_packaging_height attribute
			$data['item_dimension_shipping_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionShipping()->getPackaging()->getHeight();
		}
		if ($helper->hasEavAttr('item_dimension_carton_mass_unit_of_measure')) {
			// setting item_dimension_carton_mass_unit_of_measure attribute
			$data['item_dimension_carton_mass_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getMassUnitOfMeasure();
		}
		if ($helper->hasEavAttr('item_dimension_carton_mass_weight')) {
			// setting item_dimension_carton_mass_weight attribute
			$data['item_dimension_carton_mass_weight'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getWeight();
		}
		if ($helper->hasEavAttr('item_dimension_carton_packaging_unit_of_measure')) {
			// setting item_dimension_carton_packaging_unit_of_measure attribute
			$data['item_dimension_carton_packaging_unit_of_measure'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()
				->getPackaging()->getUnitOfMeasure();
		}
		if ($helper->hasEavAttr('item_dimension_carton_packaging_width')) {
			// setting item_dimension_carton_packaging_width attribute
			$data['item_dimension_carton_packaging_width'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getWidth();
		}
		if ($helper->hasEavAttr('item_dimension_carton_packaging_length')) {
			// setting item_dimension_carton_packaging_length attribute
			$data['item_dimension_carton_packaging_length'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getLength();
		}
		if ($helper->hasEavAttr('item_dimension_carton_packaging_height')) {
			// setting item_dimension_carton_packaging_height attribute
			$data['item_dimension_carton_packaging_height'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getPackaging()->getHeight();
		}
		if ($helper->hasEavAttr('item_dimension_carton_type')) {
			// setting item_dimension_carton_type attribute
			$data['item_dimension_carton_type'] = $dataObject->getExtendedAttributes()->getItemDimensionCarton()->getType();
		}
		if ($helper->hasEavAttr('lot_tracking_indicator')) {
			// setting lot_tracking_indicator attribute
			$data['lot_tracking_indicator'] = $dataObject->getExtendedAttributes()->getLotTrackingIndicator();
		}
		if ($helper->hasEavAttr('ltl_freight_cost')) {
			// setting ltl_freight_cost attribute
			$data['ltl_freight_cost'] = $dataObject->getExtendedAttributes()->getLtlFreightCost();
		}
		if ($helper->hasEavAttr('manufacturing_date')) {
			// setting manufacturing_date attribute
			$data['manufacturing_date'] = $dataObject->getExtendedAttributes()->getManufacturer()->getDate();
		}
		if ($helper->hasEavAttr('manufacturer_name')) {
			// setting manufacturer_name attribute
			$data['manufacturer_name'] = $dataObject->getExtendedAttributes()->getManufacturer()->getName();
		}
		if ($helper->hasEavAttr('manufacturer_manufacturer_id')) {
			// setting manufacturer_manufacturer_id attribute
			$data['manufacturer_manufacturer_id'] = $dataObject->getExtendedAttributes()->getManufacturer()->getId();
		}
		if ($helper->hasEavAttr('may_ship_expedite')) {
			// setting may_ship_expedite attribute
			$data['may_ship_expedite'] = $dataObject->getExtendedAttributes()->getMayShipExpedite();
		}
		if ($helper->hasEavAttr('may_ship_international')) {
			// setting may_ship_international attribute
			$data['may_ship_international'] = $dataObject->getExtendedAttributes()->getMayShipInternational();
		}
		if ($helper->hasEavAttr('may_ship_usps')) {
			// setting may_ship_usps attribute
			$data['may_ship_usps'] = $dataObject->getExtendedAttributes()->getMayShipUsps();
		}
		if ($helper->hasEavAttr('safety_stock')) {
			// setting safety_stock attribute
			$data['safety_stock'] = $dataObject->getExtendedAttributes()->getSafetyStock();
		}
		if ($helper->hasEavAttr('sales_class')) {
			// setting sales_class attribute
			$data['sales_class'] = $dataObject->getExtendedAttributes()->getSalesClass();
		}
		if ($helper->hasEavAttr('serial_number_type')) {
			// setting serial_number_type attribute
			$data['serial_number_type'] = $dataObject->getExtendedAttributes()->getSerialNumberType();
		}
		if ($helper->hasEavAttr('service_indicator')) {
			// setting service_indicator attribute
			$data['service_indicator'] = $dataObject->getExtendedAttributes()->getServiceIndicator();
		}
		if ($helper->hasEavAttr('ship_group')) {
			// setting ship_group attribute
			$data['ship_group'] = $dataObject->getExtendedAttributes()->getShipGroup();
		}
		if ($helper->hasEavAttr('ship_window_min_hour')) {
			// setting ship_window_min_hour attribute
			$data['ship_window_min_hour'] = $dataObject->getExtendedAttributes()->getShipWindowMinHour();
		}
		if ($helper->hasEavAttr('ship_window_max_hour')) {
			// setting ship_window_max_hour attribute
			$data['ship_window_max_hour'] = $dataObject->getExtendedAttributes()->getShipWindowMaxHour();
		}
		if ($helper->hasEavAttr('street_date')) {
			// setting street_date attribute
			$data['street_date'] = $dataObject->getExtendedAttributes()->getStreetDate();
		}
		if ($helper->hasEavAttr('style_id')) {
			// setting style_id attribute
			$data['style_id'] = Mage::helper('eb2ccore')->normalizeSku(
				$dataObject->getExtendedAttributes()->getStyleId(),
				$dataObject->getCatalogId()
			);
		}
		if ($helper->hasEavAttr('style_description')) {
			// setting style_description attribute
			$data['style_description'] = $dataObject->getExtendedAttributes()->getStyleDescription();
		}
		if ($helper->hasEavAttr('supplier_name')) {
			// setting supplier_name attribute
			$data['supplier_name'] = $dataObject->getExtendedAttributes()->getSupplierName();
		}
		if ($helper->hasEavAttr('supplier_supplier_id')) {
			// setting supplier_supplier_id attribute
			$data['supplier_supplier_id'] = $dataObject->getExtendedAttributes()->getSupplierSupplierId();
		}
		if ($helper->hasEavAttr('size')) {
			// setting size attribute
			$sizeAttributes = $dataObject->getExtendedAttributes()->getSizeAttributes()->getSize();
			$size = null;
			if (!empty($sizeAttributes)){
				foreach ($sizeAttributes as $sizeData) {
					if (strtolower(trim($sizeData['lang'])) === strtolower($this->_defaultLanguageCode)) {
						$data['size'] = $sizeData['description'];
						break;
					}
				}
			}
		}
		return $data;
	}
	/**
	 * load category by name
	 * @param string $categoryName, the category name to filter the category table
	 * @return Mage_Catalog_Model_Category
	 */
	protected function _loadCategoryByName($categoryName)
	{
		return Mage::getModel('catalog/category')
			->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('name', array('eq' => $categoryName))
			->load()
			->getFirstItem();
	}
	/**
	 * get parent default category id
	 * @return int, default parent category id
	 */
	protected function _getDefaultParentCategoryId()
	{
		return Mage::getModel('catalog/category')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('parent_id', array('eq' => 0))
			->load()
			->getFirstItem()
			->getId();
	}
	/**
	 * get store root category id
	 * @return int, store root category id
	 */
	protected function _getStoreRootCategoryId()
	{
		return Mage::app()->getWebsite(true)->getDefaultStore()->getRootCategoryId();
	}
	/**
	 * prepared category data.
	 * @param Varien_Object $item, the object with data needed to update the product
	 * @return array, category data
	 */
	protected function _preparedCategoryLinkData(Varien_Object $item)
	{
		// Product Category Link
		$categoryLinks = $item->getCategoryLinks();
		$fullPath = 0;
		if (!empty($categoryLinks)) {
			foreach ($categoryLinks as $link) {
				$categories = explode('-', $link['name']);
				if (strtoupper(trim($link['import_mode'])) === 'DELETE') {
					foreach($categories as $category) {
						$categoryObject = Mage::getModel('catalog/category')->load(
							$this->_loadCategoryByName(ucwords($category))->getId()
						);
						if ($categoryObject->getId()) {
							// we have a valid category in the system let's delete it
							$categoryObject->delete();
						}
					}
				} else {
					// adding or changing category import mode
					$path = sprintf('%s/%s', $this->_getDefaultParentCategoryId(), $this->_getStoreRootCategoryId());
					foreach($categories as $category) {
						$categoryId = $this->_loadCategoryByName(ucwords($category))->getId();
						if ($categoryId) {
							$path .= sprintf('/%s', $categoryId);
						}
					}
					$fullPath .= sprintf('/%s', $path);
				}
			}
		}
		return explode('/', $fullPath);
	}
}
