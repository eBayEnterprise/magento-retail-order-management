<?php
class TrueAction_Eb2cProduct_Model_Feed_Processor extends Mage_Core_Model_Abstract
{
	/**
	 * list of all attribute codes within the set identified by $_attributeCodesSetId
	 * @var array
	 */
	private $_attributeCodes = null;

	/**
	 * attribute set id of the currently loaded attribute codes
	 * @var int
	 */
	private $_attributeCodesSetId = null;

	/**
	 * default language code for the store.
	 * @var string
	 */
	protected $_defaultStoreLanguageCode;

	/**
	 * A map of store Ids to LanguageCodes
	 * @var array
	 */
	protected $_storeLanguageCodeMap = array();

	/**
	 * list of attribute codes that are not setup on the system but were in the feed.
	 * @var array
	 */
	private $_missingAttributes = array();

	/**
	 * attributes that do not exist on the product.
	 * @var array
	 */
	protected $_unkownCustomAttributes = array();

	/**
	 * mapping of custom attributes and the function used to prepare them for
	 * use.
	 * @var array
	 */
	protected $_customAttributeProcessors = array(
		'PRODUCTTYPE' => '_processProductType',
		'CONFIGURABLEATTRIBUTES' => '_processConfigurableAttributes'
	);

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

	protected $_updateBatchSize = 100;
	protected $_deleteBatchSize = 100;
	protected $_maxTotalEntries = 100;

	/**
	 * hold an instance of eb2cproduct/data helper
	 * @var TrueAction_Eb2cProduct_Helper_Data
	 */
	protected $_helper = null;

	public function __construct()
	{
		$config = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_helper = Mage::helper('eb2cproduct');
		// @todo - I'm suspicious of this decision here for language
		$this->_defaultStoreLanguageCode = Mage::helper('eb2ccore')->mageToXmlLangFrmt(Mage::app()->getLocale()->getLocaleCode());
		$this->_initStoreLanguageCodeMap();
		$this->_updateBatchSize = $config->processorUpdateBatchSize;
		$this->_deleteBatchSize = $config->processorDeleteBatchSize;
		$this->_maxTotalEntries = $config->processorMaxTotalEntries;
	}

	/**
	 * Sets the default translation, returns an array of translations that have note yet been applied
	 * @param Varien_Object $source, the source object find the source to get the language data from
	 * @param Varien_Object $target, the target to set the source language data to
	 * @param string $fieldName, the field to set the translation find in from source to the target object
	 * @param string $translationsArrayName, the name of the source node to get the language translation data from
	 * @return array
	 */
	protected function _setDefaultTranslation($source, $target, $fieldName, $translationsArrayName)
	{
		Mage::log(sprintf("source = %s\ntarget = %s\nfieldName = %s\ntranslationsArrayName = %s", print_r($source, true), print_r($target, true), $fieldName, print_r($translationsArrayName, true)), Zend_log::DEBUG);
		// Set default product long description
		$translationsToApply = array();
		if ($source->getExtendedAttributes()->hasData($translationsArrayName)) {
			$translationsToApply = $source->getExtendedAttributes()->getData($translationsArrayName);
			// Assign the 'default' translation
			if (array_key_exists($this->_defaultStoreLanguageCode, $translationsToApply)) {
				$target->setData($fieldName, $translationsToApply[$this->_defaultStoreLanguageCode]);
				unset($translationsToApply[$this->_defaultStoreLanguageCode]);
			}
		}
		return $translationsToApply;
	}

	/**
	 * If key exists in array, return its value, otherwise return false
	 * @param string $languageCode, the language code key that must exists in the translation array
	 * @param array $arrayOfTranslations, collection of translation with language code composite keys
	 * @return boolean
	 */
	protected function _getTranslation($languageCode, $arrayOfTranslations)
	{
		return array_key_exists($languageCode, $arrayOfTranslations) ?
			$arrayOfTranslations[$languageCode] : false;
	}

	/**
	 * get an array of magento's stores
	 * @return array, an array of magento's store
	 */
	protected function _getAllStores()
	{
		return Mage::app()->getStores();
	}

	/**
	 * get store by store id
	 * @param int $storeId, the store id to get the store object from
	 * @return Mage_Core_Model_Store
	 */
	protected function _getStoreById($storeId)
	{
		return Mage::app()->getStore($storeId);
	}

	/**
	 * Creates a map of language codes (as dervied from the store view code) to store ids
	 * @todo The parse-language-from-store-view code might need to be closer to Eb2cCore.
	 * @return self
	 */
	protected function _initStoreLanguageCodeMap()
	{
		foreach ($this->_getAllStores() as $storeId) {
			$storeObject = $this->_getStoreById($storeId);
			$storeCodeParsed = explode('_', $storeObject->getCode(), 3);
			if (count($storeCodeParsed) > 2) {
				$this->_storeLanguageCodeMap[$storeCodeParsed[2]] = $storeObject->getId();
			} else {
				Mage::log(
					sprintf('Incompatible Store View Name ignored: "%s"', $storeObject->getName()),
					Zend_log::INFO
				);
			}
		}
		return $this;
	}

	/**
	 * processing list of product to be added or updated
	 * @param array $dataObjectList
	 * @return void
	 */
	public function processUpdates($dataObjectList)
	{
		Mage::log(sprintf('[ %s ] Processing %d updates.', __CLASS__, count($dataObjectList)));
		foreach ($dataObjectList as $dataObject) {
			$dataObject = $this->_transformData($dataObject);
			$this->_synchProduct($dataObject);
		}
	}

	/**
	 * processing list of product to be deleted
	 * @param array $dataObjectList
	 * @return void
	 */
	public function processDeletions($dataObjectList)
	{
		Mage::log(sprintf('[ %s ] Processing %d deletes.', __CLASS__, count($dataObjectList)));
		foreach ($dataObjectList as $dataObject) {
			$this->_deleteItem($dataObject);
		}
	}

	/**
	 * Transform the data extracted from the feed into a generic set of feed data
	 * including item_id, base_attributes, extended_attributes and custome_attributes
	 * @param  Varien_Object $dataObject Data extraced from the feed
	 * @return Varien_Object             Data that can be imported to a product
	 */
	protected function _transformData(Varien_Object $dataObject)
	{
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
		$baseAttributes->setData('drop_shipped', $this->_helper->parseBool($dataObject->getData('is_drop_shipped')));
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
				'mass_unit_of_measure' => $dataObject->hasData($section . '_mass_unit_of_measure') ? $dataObject->getData($section . '_mass_unit_of_measure') : false,
				// Shipping weight of the item.
				'weight' => $dataObject->hasData($section . '_mass_weight') ? $dataObject->getData($section . '_mass_weight') : false,
				'packaging' => new Varien_Object(array(
					// Unit of measure used for these dimensions.
					'unit_of_measure' => $dataObject->hasData($section . '_packaging_mass_unit_of_measure') ? $dataObject->getData($section . '_packaging_mass_unit_of_measure') : false,
					'width' => $dataObject->hasData($section . '_packaging_width') ? $dataObject->getData($section . '_packaging_width') : false,
					'length' => $dataObject->hasData($section . '_packaging_length') ? $dataObject->getData($section . '_packaging_length') : false,
					'height' => $dataObject->hasData($section . '_packaging_height') ? $dataObject->getData($section . '_packaging_height') : false,
				)),
			)));
		}
		$extData->getItemDimensionCarton()->setData('type', $dataObject->hasData('item_dimension_carton_type') ? $dataObject->getData('item_dimension_carton_type') : false);
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
				$extData->setData($key, $this->_helper->parseBool($dataObject->getData($key)));
			}
		}

		$this->_preparePricingEventData($dataObject, $extData);
		// @todo clean up circular assignments
		$outData->setData('extended_attributes', $extData);
		$extData->addData(
			// get extended attributes data containing (gift wrap, color, long/short descriptions)
			$this->_getContentExtendedAttributeData($outData)
		);
		///////
		$this->_prepareCustomAttributes($dataObject, $outData);

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
			$product = $this->_helper->loadProductBySku($sku);

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
					sprintf('[ %s ] Item Master Feed Delete Operation for SKU (%d), does not exists in Magento', __CLASS__, $sku),
					Zend_Log::WARN
				);
			}
		}

		return $this;
	}

	/**
	 * extract extended attribute data such as (gift_wrap
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the extended attribute product data
	 * @return array, composite array containing description data, gift wrap, color... etc
	 */
	protected function _getContentExtendedAttributeData(Varien_Object $dataObject)
	{
		$data = array();
		$extendedAttributes = $dataObject->getExtendedAttributes()->getData();
		if (!empty($extendedAttributes)) {
			if (isset($extendedAttributes['gift_wrap'])) {
				// extracting gift_wrapping_available
				$data['gift_wrap'] = $this->_helper->parseBool($extendedAttributes['gift_wrap']);
			}

			if (isset($extendedAttributes['long_description']) && !empty($extendedAttributes['long_description'])) {
				foreach( $extendedAttributes['long_description'] as $longDescription ) {
					$data['long_description_set'][$longDescription['lang']] = $longDescription['long_description'];
				}
			}

			if (isset($extendedAttributes['short_description']) && !empty($extendedAttributes['short_description'])) {
				foreach( $extendedAttributes['short_description'] as $shortDescription ) {
					$data['short_description_set'][$shortDescription['lang']] = $shortDescription['short_description'];
				}
			}
		}
		return $data;
	}

	protected function _isDefaultStoreLanguage($langStr)
	{
		$langStr = Mage::helper('eb2ccore')->xmlToMageLangFrmt($langStr);
		return strtoupper($langStr) !== strtoupper($this->_defaultStoreLanguageCode);
	}

	/**
	 * transform valid custom attribute data into a readily saveable form.
	 * @param  Varien_Object $dataObject
	 */
	protected function _prepareCustomAttributes(Varien_Object $dataObject, Varien_Object $outData)
	{
		$customAttrs = $dataObject->getCustomAttributes();
		if (!$customAttrs) {
			// do nothing if there is no custom attributes
			return;
		}
		$custData = new Varien_Object();
		$outData->setData('custom_attributes', $custData);
		foreach ($customAttrs as $attributeData) {
			if (!isset($attributeData['name'])) {
				// skip the attribute
				Mage::log('Custom attribute has no name: ' . json_encode($attributeData), Zend_Log::DEBUG);
			} else {
				// if (isset($attributeData['lang']) && !$this->_isDefaultStoreLanguage($attributeData['lang'])) {
				// 	// skip any attribute that is specifically not the default language
				// 	continue;
				// }
				$attributeCode = $this->_underscore($attributeData['name']);
				// setting custom attributes
				if (!isset($attributeData['operation_type'])) {
					Mage::log(sprintf('[ %s ]: Received custom attribute with no operation type: %s', __CLASS__, $attributeData['name']));
				} elseif (strtoupper($attributeData['operation_type']) === 'DELETE') {
					// setting custom attributes to null on operation type 'delete'
					$custData->setData($attributeCode, null);
				} else {
					$lookup = strtoupper($attributeData['name']);
					if (isset($this->_customAttributeProcessors[$lookup])) {
						$method = $this->_customAttributeProcessors[$lookup];
						$this->$method($attributeData, $custData, $outData);
					} else {
						$custData->setData($attributeCode, $attributeData['value']);
					}
				}
			}
		}
	}

	/**
	 * Special data processors for the product_type custome attribute.
	 * Assigns the PRODUCTTYPE custom attribute to the product data as 'product_type'
	 * @param  array         $attrData   Map of custom attribute data: name, operation type, lang and value
	 * @param  Varien_Object $customData Varien_Object containing all custome attributes data
	 * @param  Varien_Object $outData    Varien_Object containing all transformed product feed data
	 */
	protected function _processProductType($attrData, Varien_Object $customData, Varien_Object $outData)
	{
		$outData->setData('product_type', strtolower($attrData['value']));
	}

	/**
	 * Special data processor for the product configurable_attributes custom attribute.
	 * Assigns the CONFIGURABLEATTRIBUTES custom attribute to the product data as configurable_attributes.
	 *
	 * @param  array         $attrData   Map of custom attribute data: name, operation type, lang and value
	 * @param  Varien_Object $customData Varien_Object containing all custome attributes data
	 * @param  Varien_Object $outData    Varien_Object containing all transformed product feed data
	 */
	protected function _processConfigurableAttributes($attrData, Varien_Object $customData, Varien_Object $outData)
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
	 * stores the attribute data to be logged later.
	 * @param  string $code          the _unscored attribute code
	 * @param  array  $attributeData the extacted attribute data
	 */
	protected function _recordUnknownCustomAttribute($code, $attributeData)
	{
		if (!array_key_exists($name, $this->_unkownCustomAttributes)) {
			$this->_unkownCustomAttributes[$name] = $attributeData;
		}
	}

	/**
	 */
	protected function _preparePricingEventData(Varien_Object $dataObject, Varien_Object $outData)
	{
		if ($dataObject->hasEbcPricingEventNumber()) {
			$priceIsVatInclusive = $this->_helper->parseBool($dataObject->getPriceVatInclusive());
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
	 * @param string $attributeCode, The attribute code
	 * @param string $option, The option within the attribute
	 * @return int
	 * @throws TrueAction_Eb2cProduct_Model_Feed_Exception if attributeCode is not found.
	 */
	protected function _getAttributeOptionId($attributeCode, $option)
	{
		$attributeEntity = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attributeCode);
		if (!$attributeEntity->getId()) {
			throw new TrueAction_Eb2cProduct_Model_Feed_Exception("Cannot get attribute option id for undefined attribute code '$attributeCode'.");
		}
		$attributeOptions = Mage::getResourceModel('eav/entity_attribute_option_collection')
			->setAttributeFilter($attributeEntity->getId())
			// @todo false = 'don't use default', but I really don't know what that means.
			->setStoreFilter(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID, false);

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
		$product = $this->_helper->prepareProductModel($sku, $item->getBaseAttributes()->getItemDescription());

		$productData = new Varien_Object();

		// getting product name/title.
		// @todo: Title is awkwardly handled here. Should be handled more w/ _setDefaultTranslation(), but
		// even that needs to be refactored at some point.
		$productTitleSet = $this->_getProductTitleSet($item);
		$productTitle    = $this->_getTranslation($this->_defaultStoreLanguageCode, $productTitleSet);
		unset($productTitleSet[$this->_defaultStoreLanguageCode]);

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
			// @todo This should be visibilty none if it's a child product. Maybe.
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

		// Setting product name/title from base attributes
		$productData->setData('name', ($productTitle) ? $productTitle : $product->getName());

		// Set default product long description
		$longDescriptionSet = $this
			->_setDefaultTranslation($item, $productData, 'description', 'long_description_set');

		// Set default short description:
		$shortDescriptionSet = $this
			->_setDefaultTranslation($item, $productData, 'short_description', 'short_description_set');

		// Set default brand description:
		$brandDescriptionSet = $this
			->_setDefaultTranslation($item, $productData, 'brand_description', 'brand_description_set');

		// setting the product's color to a Magento Attribute Id
		if ($item->getExtendedAttributes()->hasData('color')) {
			$productData->setData('color', $this->_getProductColorOptionId($item->getExtendedAttributes()->getData('color')));
		}

		if( $item->hasData('configurable_attributes_data') ) {
			$productData->setData('configurable_attributes_data', $item->getData('configurable_attributes_data'));
		}

		// mark all products that have just been imported as not being clean
		// Find out that setting the 'is_clean' attribute to false wasn't add the attribute relationship
		// to the catalog_product_entity_int table, that's why the cleaner wasn't running.
		$productData->setData('is_clean', 0);

		$product->addData($productData->getData())
			->addData($this->_getEb2cSpecificAttributeData($item))
			->save(); // saving the product
		$defaultProductId = $product->getId();
		$this
			->_addStockItemDataToProduct($item, $product); // @todo: only do if !configurable product type

		// Process other-than-default language stuff:
		/**
		 * @todo brandSet and productTitleSet
		 * The Big Idea:
		 * - examine each of longDescriptionSet, shortDescriptionSet, brandSet, and producTitleSet
		 * - if set for this language, then use it.
		 * - if not specified, leave it alone
		 * - this code doesn't know how to UNSET a language'd field.
		 * - anyway, that's my 'first-pass idea' at doing this a single-save per language
		 */

		// @todo man this is goofy test. Or maybe isn't it. Unsure. Getting tired. If any of these
		// language-sensitive fields are filled in, process them.
		if (!empty($longDescriptionSet) || !empty($shortDescriptionSet)
			|| !empty($brandDescriptionSet) || !empty($productTitleSet)) {
			foreach($this->_storeLanguageCodeMap as $lang => $storeId) {
				if ($lang === $this->_defaultStoreLanguageCode) {
					continue; // Skip default store language - it's already been done
				}
				// @todo do I have to set Current Store every time?
				// @todo do I have to re-load the product every time?
				// See http://www.fabrizio-branca.de/whats-wrong-with-the-new-url-keys-in-magento.html for
				// details about why url_key has to be set specially. It's a Magento 1.13 'problem'.
				Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
				$altProduct =
					Mage::getModel('catalog/product')->load($defaultProductId)
					->setStoreId($storeId)
					->setUrlKey(false);

				// We could /always/ setFieldName(value), by using altProduct->getValue(). But that
				// would turn off the 'default flag' and make the store view hold more than it really
				// should be.
				$longDescription  = $this->_getTranslation($lang, $longDescriptionSet);
				if( $longDescription ) {
					$altProduct->setDescription($longDescription);
				}

				$shortDescription = $this->_getTranslation($lang, $shortDescriptionSet);
				if( $shortDescription ) {
					$altProduct->setShortDescription($shortDescription);
				}

				$brandDescription = $this->_getTranslation($lang, $brandDescriptionSet);
				if( $brandDescription ) {
					$altProduct->setBrandDescription($brandDescription);
				}

				$productTitle = $this->_getTranslation($lang, $productTitleSet);
				if( $productTitle ) {
					$altProduct->setName($brandDescription);
				}

				// Only set what we've been provided.
				$altProduct->save();
			}
		}

		return $this;
	}

	/**
	 * Get the id of the Color-Attribute Option for this specific color. Create it if it doesn't exist.
	 * @todo This is probably more specific than we really need it to be. All attributes should be processed in a similar manner - special handling of 'color' should be revisited.0
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
	 * adding stock item data to a product.
	 * @param Varien_Object $dataObject, the object with data needed to add the stock data to the product
	 * @param Mage_Catalog_Model_Product $parentProductObject, the product object to set stock item data to
	 * @return self
	 */
	protected function _addStockItemDataToProduct(Varien_Object $dataObject, Mage_Catalog_Model_Product $productObject)
	{
		if( $productObject->getTypeId() !== Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
			Mage::getModel('cataloginventory/stock_item')->loadByProduct($productObject)
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
					$titleData[$title['lang']] = $title['title'];
				}
			}
		}
		return $titleData;
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
	 * Translate the feed's idea of status to Magento's
	 *
	 * @param type $originalStatus as from XML feed E.g., "Active"
	 * @return Magento-ized version of status
	 */
	protected function _getItemStatusData($originalStatus)
	{
		$mageStatus = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
		if(strtoupper($originalStatus) === 'ACTIVE') {
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

		if ($prodHlpr->hasEavAttr('hts_codes')) {
			$data['hts_codes'] = $dataObject->getHtsCode();
		}

		// Get default lang and translations for brand_description
		if ($prodHlpr->hasEavAttr('brand_description')) {
			$data['brand_description_set'] = $this->_parseTranslations(
				$dataObject->getExtendedAttributes()->getBrandDescription()
			);
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
			$data['style_id'] = Mage::helper('eb2ccore')->normalizeSku(
				$dataObject->getExtendedAttributes()->getStyleId(),
				$dataObject->getCatalogId()
			);
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
					if (strtoupper(trim($sizeData['lang'])) === strtoupper($this->_defaultStoreLanguageCode)) {
						$data['size'] = $sizeData['description'];
						break;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Flattens translations into arrays keyed by language
	 * @return array in the form a['lang-code'] = 'localized value'
	 */
	protected function _parseTranslations($languageSet)
	{
		$parsedLanguages = array();
		if (!empty($languageSet)) {
			foreach ($languageSet as $language) {
				$parsedLanguages[$language['lang']] = $language['description'];
			}
		}
		return $parsedLanguages;
	}

	/**
	 * @param  array  $attributeList list of attributes we want to exist
	 * @return array                 subset of $attributeList that actually exist
	 */
	private function _getApplicableAttributes(array $attributeList)
	{
		$extraAttrs = array_diff($attributeList, self::$_attributeCodes);
		if ($extraAttrs) {
			self::$_missingAttributes = array_unique(array_merge(self::$_missingAttributes, $extraAttrs));
		}
		return array_intersect($attributeList, self::$_attributeCodes);
	}

	/**
	 * load all attribute codes
	 * @return self
	 */
	private function _loadAttributeCodes($product)
	{
		if (is_null(self::$_attributeCodes) || self::$_attribeteCodesSetId != $product->getAttributeSetId()) {
			self::$_attributeCodes = Mage::getSingleton('eav/config')
				->getEntityAttributeCodes($product->getResource()->getEntityType(), $product);
		}
		return $this;
	}

	/**
	 * load category by name
	 *
	 * @param string $categoryName, the category name to filter the category table
	 *
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
							$path .= '/' . $categoryId;
						}
					}
						$fullPath .= '/' . $path;
					}
				}
			}
		return explode('/', $fullPath);
	}

				}
