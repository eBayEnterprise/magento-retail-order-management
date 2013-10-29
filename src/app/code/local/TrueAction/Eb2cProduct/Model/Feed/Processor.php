<?php
/**
 */
// TODO: REMOVE DEPENDENCY ON MAGE_CORE_MODEL_ABSTRACT::_UNDERSCORE METHOD
class TrueAction_Eb2cProduct_Model_Feed_Processor
	extends Mage_Core_Model_Abstract
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
		'country_of_origin',
		'gift_card_tender_code',
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
		'ship_window_min_hour',
		'ship_window_max_hour',
		'street_date',
		'style_id',
		'style_description',
		'supplier_name',
		'supplier_supplier_id',
		'brand_name',
		'brand_description',
		'buyer_id',
		'companion_flag',
		'hazardous_material_code',
		'short_description',
		'long_description',
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

	public function __construct()
	{
		$config = Mage::helper('eb2cproduct')->getConfigModel();
		$this->_helper = Mage::helper('eb2cproduct');
		$this->_defaultStoreLanguageCode = Mage::helper('eb2ccore')->mageToXmlLangFrmt(Mage::app()->getLocale()->getLocaleCode());
		$this->_updateBatchSize = $config->processorUpdateBatchSize;
		$this->_deleteBatchSize = $config->processorDeleteBatchSize;
		$this->_maxTotalEntries = $config->processorMaxTotalEntries;
	}

	public function processUpdates($dataObjectList)
	{
		$message = 'processing ' . count($dataObjectList) . ' updates';
		Mage::log($message);
		foreach ($dataObjectList as $dataObject) {
			$dataObject = $this->_transformData($dataObject);
			$this->_synchProduct($dataObject);
		}
	}

	public function processDeletions($dataObjectList)
	{
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
		$baseAttributes->setData('drop_shipped', $this->_helper->convertToBoolean($dataObject->getData('is_drop_shipped')));
		foreach (array('catalog_class', 'item_type', 'item_status', 'tax_code', 'title') as $key) {
			$baseAttributes->setData($key, $dataObject->hasData($key) ? $dataObject->getData($key) : false);
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
				'weight' => $dataObject->hasData($section . '_weight') ? $dataObject->getData($section . '_weight') : false,
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
		$extData->setData('size_attributes', new Varien_Object(array(
			'size' => $dataObject->getData('size')
		)));
		$extData->setData('color_attributes', new Varien_Object(array(
			'color' => $dataObject->getData('color')
		)));

		foreach ($this->_extKeys as $key) {
			$extData->setData($key, $dataObject->hasData($key) ? $dataObject->getData($key) : false);
		}
		// handle values that need to be booleans
		foreach ($this->_extKeysBool as $key) {
			$extData->setData($key, $dataObject->hasData($key) ? $this->_helper->convertToBoolean($dataObject->getData($key)) : false);
		}

		$this->_preparePricingEventData($dataObject, $extData);
		// FIXME: CLEAN UP CIRCULAR ASSIGNMENTS
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
	 * extract extended attribute data such as (gift_wrap
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the extended attribute product data
	 *
	 * @return array, composite array containing description data, gift wrap, color... etc
	 */
	protected function _getContentExtendedAttributeData(Varien_Object $dataObject)
	{
		$data = array();
		$extendedAttributes = $dataObject->getExtendedAttributes()->getData();
		if (!empty($extendedAttributes)) {
			if (isset($extendedAttributes['gift_wrap'])) {
				// extracting gift_wrapping_available
				$data['gift_wrap'] = $this->_helper->convertToBoolean($extendedAttributes['gift_wrap']);
			}

			if (isset($extendedAttributes['long_description'])
					&& !empty($extendedAttributes['long_description']))
			{
				// get long description data
				$longDescriptions = $extendedAttributes['long_description'];
				foreach ($longDescriptions as $longDescription) {
					if (strtoupper($longDescription['lang']) === strtoupper($this->_defaultStoreLanguageCode)) {
						// extracting the product long description according to the store language setting
						$data['long_description'] = $longDescription['long_description'];
					}
				}
			}

			if (isset($extendedAttributes['short_description'])
					&& !empty($extendedAttributes['short_description']))
			{
				// get short description data
				$shortDescriptions = $extendedAttributes['short_description'];
				foreach ($shortDescriptions as $shortDescription) {
					if (strtoupper($shortDescription['lang']) === strtoupper($this->_defaultStoreLanguageCode)) {
						// setting the product short description according to the store language setting
						$data['short_description'] = $shortDescription['short_description'];
					}
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
		$coreHelper = Mage::helper('eb2ccore');
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
				if (strtoupper($attributeData['operation_type']) === 'DELETE') {
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
	 * @todo  needs to be reformatted to match the appropriate data format. I think this is a start but will need to be checked.
	 * @param  array         $attrData   Map of custom attribute data: name, operation type, lang and value
	 * @param  Varien_Object $customData Varien_Object containing all custome attributes data
	 * @param  Varien_Object $outData    Varien_Object containing all transformed product feed data
	 */
	protected function _processConfigurableAttributes($attrData, Varien_Object $customData, Varien_Object $outData)
	{
		$helper = Mage::helper('eb2cproduct');
		$outData->setData('configurable_attributes_data', array_map(function ($el) use ($helper) {
			return array('attribute_id' => $helper->getProductAttributeId($el));
		}, explode(',', $attrData['value'])));
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
			$priceIsVatInclusive = $this->_helper->convertToBoolean($dataObject->getPriceVatInclusive());
			$data = array(
				'price' => $dataObject->getPrice(),
				'msrp' => $dataObject->getMsrp(),
				'price_is_vat_inclusive' => $priceIsVatInclusive,
			);
			if ($dataObject->getEbcPricingEventNumber()) {
				$startDate = new DateTime($dataObject->getStartDate());
				$startDate->setTimezone(new DateTimeZone('UTC'));
				$endDate = new DateTime($dataObject->getEndDate());
				$endDate->setTimezone(new DateTimeZone('UTC'));
				$data['price'] = $dataObject->getAlternatePrice();
				$data['special_price'] = $dataObject->getPrice();
				$data['special_from_date'] = $startDate->format('Y-m-d H:i:s');
				$data['special_to_date'] = $endDate->format('Y-m-d H:i:s');
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
	 * getting the attribute selected option.
	 * @param string $attribute, the string attribute code to get the attribute config
	 * @param string $option, the string attribute option label to get the attribute
	 * @return int
	 */
	protected function _getAttributeOptionId($attribute, $option)
	{
		$optionId = 0;
		$attributes = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
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
	 * @param string $attribute, the attribute to used to add the new option
	 * @param string $newOption, the new option to be added for the attribute
	 * @return int, the newly inserted option id
	 */
	protected function _addAttributeOption($attribute, $newOption)
	{
		$newOptionId = 0;
		try{
			$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
			$attributeObject = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute);
			$setup->addAttributeOption(array('attribute_id' => $attributeObject->getAttributeId(),'value' => array('any_option_name' => array($newOption))));
			$newOptionId = $this->_getAttributeOptionId($attribute, $newOption);
		} catch (Mage_Core_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while creating new option "%d"  for attribute: %d in Item Master Feed (%d)',
					__CLASS__, $newOption, $attribute, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $newOptionId;
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
		$product = $this->_helper->prepareProductModel($sku);

		$productData = new Varien_Object();
		$productData->setData('visibility', $this->_getVisibilityData($item));
		// getting product name/title
		$productTitle = $this->_getDefaultLocaleTitle($item);

		if ($item->hasProductType()) {
			$productData->setData('type_id', $item->getProductType());
		}
		if ($item->getExtendedAttributes()->getItemDimensionShipping()->hasWeight()) {
			$productData->setData('weight', $item->getExtendedAttributes()->getItemDimensionShipping()->getWeight());
		}
		if ($item->getExtendedAttributes()->getItemDimensionShipping()->hasData('mass')) {
			$productData->setData('mass', $item->getExtendedAttributes()->getItemDimensionShipping()->getMassUnitOfMeasure());
		}
		if ($item->getBaseAttributes()->getItemStatus()) {
			$productData->setData('status', $item->getBaseAttributes()->getItemStatus());
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
		$productData->setData('category_ids', $this->_preparedCategoryLinkData($item));
		// Setting product name/title from base attributes
		$productData->setData('name', ($productTitle !== '')? $productTitle : $product->getName());
		// setting the product long description according to the store language setting
		if ($item->getExtendedAttributes()->hasData('long_description')) {
			$productData->setData('description', $item->getExtendedAttributes()->getData('long_description'));
		}
		// setting the product short description according to the store language setting
		if ($item->getExtendedAttributes()->hasData('short_description')) {
			$productData->setData('short_description', $item->getExtendedAttributes()->getData('short_description'));
		}

		// mark all products that have just been imported as not being clean
		$productData->setData('is_clean', false);

		$product->addData($productData->getData())
			->addData($this->_getEb2cSpecificAttributeData($item))
			->save(); // saving the product
		$this
			->_addColorToProduct($item, $product)
			->_addStockItemDataToProduct($item, $product);
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
			if(trim($colorCode) !== '') {
				$colorOptionId = (int) $this->_getAttributeOptionId('color', $colorCode);
				if (!$colorOptionId) {
					$colorOptionId = (int) $this->_addAttributeOption('color', $colorCode);
				}
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
		if (trim(strtoupper($dataObject->getProductType())) === 'CONFIGURABLE' && $prodHlpr->hasEavAttr('color')) {
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
	 * getting default locale title that match magento default locale
	 *
	 * @param Varien_Object $dataObject, the object with data needed to retrieve the default product title
	 *
	 * @return string
	 */
	protected function _getDefaultLocaleTitle(Varien_Object $dataObject)
	{
		$title = '';
		$titles = $dataObject->getBaseAttributes()->getTitle();
		if(isset($titles) && !empty($titles)) {
			foreach ($titles as $title) {
				if (strtoupper($title['lang']) === strtoupper($this->_defaultStoreLanguageCode)) {
					return $title['title'];
				}
			}
		}
		return $title;
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

		if ($prodHlpr->hasEavAttr('brand_description')) {
			// setting brand_description attribute
			$brandDescription = $dataObject->getExtendedAttributes()->getBrandDescription();
			foreach ($brandDescription as $bDesc) {
				if (trim(strtoupper($bDesc['lang'])) === strtoupper($this->_defaultStoreLanguageCode)) {
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
	 *
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
	 * prepared category data.
	 *
	 * @param Varien_Object $dataObject, the object with data needed to update the product
	 *
	 * @return array, category data
	 */
	protected function _preparedCategoryLinkData(Varien_Object $dataObject)
	{
		// Product Category Link
		$categoryLinks = $dataObject->getCategoryLinks();
		$fullPath = 0;

		if (!empty($categoryLinks)) {
			foreach ($categoryLinks as $link) {
				if ($link instanceof Varien_Object) {
					$categories = explode('-', $link->getName());
					if (strtoupper(trim($link->getImportMode())) === 'DELETE') {
						foreach($categories as $category) {
							$this->setCategory($this->_loadCategoryByName(ucwords($category)));
							if ($this->getCategory()->getId()) {
								// we have a valid category in the system let's delete it
								$this->getCategory()->delete();
							}
						}
					} else {
						// adding or changing category import mode
						$path = $this->getDefaultRootCategoryId();
						foreach($categories as $category) {
							$path .= '/' . $this->_addCategory(ucwords($category), $path);
						}
						$fullPath .= '/' . $path;
					}
				}
			}
		}
		return explode('/', $fullPath);
	}

	/**
	 * convert the linktype into a version usable by magento
	 * @param  string $lt link type extracted from the document
	 * @return string     the converted string or the empty string if conversion is not possible
	 */
	protected function _getProducLinkType($lt)
	{
		$lt = strtoupper($lt);
		if ($lt === 'ES_UPSELLING') {
			return 'upsell';
		} elseif ($lt === 'ES_CROSSSELLING') {
			return 'crosssell';
		}
		return '';
	}

	/**
	 * add category to magento, check if already exist and return the category id
	 *
	 * @param string $categoryName, the category to either add or get category id from magento
	 * @param string $path, delimited string of the category depth path
	 *
	 * @return int, the category id
	 */
	protected function _addCategory($categoryName, $path)
	{
		$categoryId = 0;
		if (trim($categoryName) !== '') {
			// let's check if category already exists
			$this->setCategory($this->_loadCategoryByName($categoryName));
			$categoryId = $this->getCategory()->getId();
			if (!$categoryId) {
				// category doesn't currently exists let's add it.
				try {
					$this->getCategory()->setAttributeSetId($this->getDefaultCategoryAttributeSetId())
						->setStoreId($this->getDefaultStoreId())
						->addData(
							array(
								'name' => $categoryName,
								'path' => $path, // parent relationship..
								'description' => $categoryName,
								'is_active' => 1,
								'is_anchor' => 0, //for layered navigation
								'page_layout' => 'default',
								'url_key' => Mage::helper('catalog/product_url')->format($categoryName), // URL to access this category
								'image' => null,
								'thumbnail' => null,
							)
						)
						->save();

					$categoryId = $this->getCategory()->getId();
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
					Mage::log(
						sprintf(
							'[ %s ] The following error has occurred while adding categories product for Content Master Feed (%d)',
							__CLASS__, $e->getMessage()
						),
						Zend_Log::ERR
					);
				}
			}
		}

		return $categoryId;
	}

}
