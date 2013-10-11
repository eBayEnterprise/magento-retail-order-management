<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Extractor
	implements TrueAction_Eb2cProduct_Model_Feed_Extractor_Interface
{
	const FEED_BASE_NODE = 'Item';

	protected $_extractMap = array(
		// SKU used to identify this item from the client system.
		'client_item_id' => 'ItemId/ClientItemId/text()',
		// Alternative identifier provided by the client.
		'client_alt_item_id' => 'ItemId/ClientAltItemId/text()',
		// Code assigned to the item by the manufacturer to identify the item.
		'manufacturer_item_id' => 'ItemId/ManufacturerItemId/text()',
		// Name of the Drop Ship Supplier fulfilling the item
		'supplier_name' => 'DropShipSupplierInformation/SupplierName/text()',
		// Unique code assigned to this supplier.
		'supplier_number' => 'DropShipSupplierInformation/SupplierNumber/text()',
		// Id or SKU used by the drop shipper to identify this item.
		'supplier_part_number' => 'DropShipSupplierInformation/SupplierPartNumber/text()',
		// Allows for control of the web store display.
		'catalog_class' => 'BaseAttributes/CatalogClass/text()',
		// Short description in the catalog's base language.
		'item_description' => 'BaseAttributes/ItemDescription/text()',
		// Identifies the type of item.
		'item_type' => 'BaseAttributes/ItemType/text()',
		// Indicates whether an item is active, inactive or other various states.
		'item_status' => 'BaseAttributes/ItemStatus/text()',
		// Tax group the item belongs to.
		'tax_code' => 'BaseAttributes/TaxCode/text()',
	);

	protected $_extractBool = array(
		// Indicates the item if fulfilled by a drop shipper. New attribute.
		'drop_shipped' => 'BaseAttributes/IsDropShipped/text()',
	);

	protected $_extendedAttributesOptions = array(
//////// new kind of extractor for option data
		'color_attributes' => new Varien_Object(
			array(
				'color' => $colorData,
			)
		),
		'size_attributes' => new Varien_Object(
			array(
				'size' => $sizeData
			)
		),
	);

	protected $_extendedAttributesNested = array(
		'item_dimension_shipping' => array(
			// Shipping weight of the item.
			'mass_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Shipping/Mass@unit_of_measure',
			// Shipping weight of the item.
			'weight' => (float) 'ExtendedAttributes/ItemDimension/Shipping/Mass/Weight/text()',
			'packaging' => array(
				// Unit of measure used for these dimensions.
				'unit_of_measure' => 'ExtendedAttributes/ItemDimension/Shipping/Packaging@unit_of_measure',
				'width' => (float) 'ExtendedAttributes/ItemDimension/Shipping/Packaging/Width/text()',
				'length' => (float) 'ExtendedAttributes/ItemDimension/Shipping/Packaging/Length/text()',
				'height' => (float) 'ExtendedAttributes/ItemDimension/Shipping/Packaging/Height/text()',
			),
		),
		'item_dimension_display' => array(
				'mass_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Display/Mass@unit_of_measure',
				'weight' => (float) 'ExtendedAttributes/ItemDimension/Display/Mass/Weight/text()',
				'packaging' => array(
						'unit_of_measure' => 'ExtendedAttributes/ItemDimension/Display/Packaging@unit_of_measure',
						'width' => (float) 'ExtendedAttributes/ItemDimension/Display/Packaging/Width/text()',
						'length' => (float) 'ExtendedAttributes/ItemDimension/Display/Packaging/Length/text()',
						'height' => (float) 'ExtendedAttributes/ItemDimension/Display/Packaging/Height/text()',
					)
				),
			)
		),
		'item_dimension_carton' => array(
				'mass_unit_of_measure' => (string) 'ExtendedAttributes/ItemDimension/Carton/Mass@unit_of_measure',
				'weight' => (float) 'ExtendedAttributes/ItemDimension/Carton/Mass/Weight/text()',
				'packaging' => array(
						'unit_of_measure' => (string) 'ExtendedAttributes/ItemDimension/Carton/Packaging@unit_of_measure',
						'width' => (float) 'ExtendedAttributes/ItemDimension/Carton/Packaging/Width/text()',
						'length' => (float) 'ExtendedAttributes/ItemDimension/Carton/Packaging/Length/text()',
						'height' => (float) 'ExtendedAttributes/ItemDimension/Carton/Packaging/Height/text()',
					)
				),
				// Used in combination with Ship Ground to determine how the order is released by the OMS. Determined on a per client basis.
				'type' => (string) 'ExtendedAttributes/ItemDimension/CartonType/text()',
			)
		),
		'manufacturer' => array(
				// Date the item was build by the manufacturer.
				'date' => (string) 'ExtendedAttributes/ManufacturingDate/text()',
				// Company name of manufacturer.
				'name' => (string) 'ExtendedAttributes/Manufacturer/Name/text()',
				// Unique identifier to denote the item manufacturer.
				'id' => (string) 'ExtendedAttributes/Manufacturer/ManufacturerId/text()',
			)
		),
	);

	protected $_extenddedAttributes = array(
		// Item is able to be back ordered.
		'back_orderable' => 'ExtendedAttributes/BackOrderable/text()',
		// Country in which goods were completely derived or manufactured.
		'country_of_origin' => 'ExtendedAttributes/CountryOfOrigin/text()',
		/*
		 *  Type of gift card to be used for activation.
		 * 		SD - TRU Digital Gift Card
		 *		SP - SVS Physical Gift Card
		 *		ST - SmartClixx Gift Card Canada
		 *		SV - SVS Virtual Gift Card
		 *		SX - SmartClixx Gift Card
		 */
		'gift_card_tender_code' => 'ExtendedAttributes/GiftCardTenderCode/text()',
		// Vendor can ship expedited shipments. When false, should not offer expedited shipping on this item.
		'may_ship_expedite' => (float) 'ExtendedAttributes/MayShipExpedite/text()',
		// Indicates if the item may be shipped internationally.
		'may_ship_international' => (float) 'ExtendedAttributes/MayShipInternational/text()',
		// Indicates if the item may be shipped via USPS.
		'may_ship_usps' => (float) 'ExtendedAttributes/MayShipUSPS/text()',
		// Manufacturers suggested retail price. Not used for actual price calculations.
		'msrp' => (float) 'ExtendedAttributes/MSRP/text()',
		// Default price item is sold at. Required only if the item is new.
		'price' => (float) 'ExtendedAttributes/Price/text()',
		// Amount used for safety stock calculations.
		'safety_stock' => (int) 'ExtendedAttributes/SafetyStock/text()',
		// Determines behavior on the live system when the item is backordered.
		'sales_class' => 'ExtendedAttributes/SalesClass/text()',
		// Type of serial number to be scanned.
		'serial_number_type' => 'ExtendedAttributes/SerialNumberType/text()',
		// Identifies the item as a service, e.g. clothing monogramming or hemming.
		'service_indicator' => (bool) 'ExtendedAttributes/ServiceIndicator/text()',
		// Distinguishes items that can be shipped together with those in the same group.
		'ship_group' => 'ExtendedAttributes/ShipGroup/text()',
		// Minimum number of hours before the item may ship.
		'ship_window_min_hour' => (int) 'ExtendedAttributes/ShipWindowMinHour/text()',
		// Maximum number of hours before the item may ship.
		'ship_window_max_hour' => (int) 'ExtendedAttributes/ShipWindowMaxHour/text()',
		// Earliest date the product can be shipped.
		'street_date' => 'ExtendedAttributes/StreetDate/text()',
		// Code that identifies the specific appearance type or variety in which the item is available.
		'style_id' => 'ExtendedAttributes/Style/StyleID/text()',
		// Short description or title of the style for the item.
		'style_description' => 'ExtendedAttributes/Style/StyleDescription/text()',
		// Name of the individual or organization providing the merchandise.
		'supplier_name' => 'ExtendedAttributes/Supplier/Name/text()',
		// Identifier for the supplier.
		'supplier_supplier_id' => 'ExtendedAttributes/Supplier/SupplierId/text()',
		// Selling/promotional name.
		'brand_name' => 'ExtendedAttributes/Brand/Name/text()',
		'brand_description' => $brandDescriptionData,
		// Encapsulates information related to the individual/organization responsible for the procurement of this item.
		'buyer_name' => 'ExtendedAttributes/Buyer/Name/text()',
		'buyer_id' => 'ExtendedAttributes/Buyer/BuyerId/text()',
		/*
		 * Whether the item is a 'companion' (must ship with another product) or can ship alone. ENUM: ('Yes', No', 'Maybe')
		 *    Yes - may ship alone
		 *    No - cancelled if not shipped with companion
		 *    Maybe - other factors decide
		 */
		'companion_flag' => 'ExtendedAttributes/CompanionFlag/text()',
		// Indicates if the item is considered hazardous material.
		'hazardous_material_code' => 'ExtendedAttributes/HazardousMaterialCode/text()',
		// If false, customer cannot add a gift message to the item.
		'allow_gift_message' => (bool) 'ExtendedAttributes/AllowGiftMessage/text()',
		// Not included in display or in emails. Default to false.
		'is_hidden_product' => (bool) 'ExtendedAttributes/IsHiddenProduct/text()',
		// Indicates if the item's lot assignment is required to be tracked.
		'lot_tracking_indicator' => 'ExtendedAttributes/LotTrackingIndicator/text()',
		// LTL freight cost for the item.
		'ltl_freight_cost' => 'ExtendedAttributes/LTLFreightCost/text()',
	);

	public function transformData(Varien_Object $dataObject)
	{
		$dataObject->addData(array(
			$dataObject->getStatus() === 'ACTIVE' ? 1 : 0,
		));
	}

	/**
	 * extract ExtendedAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractExtendedAttributes(DOMXPath $xpath, DOMElement $item)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		$colorData = array();
		$nodeColorAttributes = $xpath->query('ExtendedAttributes/ColorAttributes/Color', $item);
		$colorIndex = 1;
		foreach ($nodeColorAttributes as $colorRecord) {
			// Description of the color used for specific store views/languages.
			$nodeColorDescription = $xpath->query('Description', $colorRecord);
			$colorDescriptionData = array();
			$colorDescriptionIndex = 1;
			foreach ($nodeColorDescription as $colorDescriptionRecord) {
				$colorDescriptionData[] = array(
					'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($colorDescriptionRecord->getAttribute('xml:lang')),
					'description' => $colorDescriptionRecord->nodeValue
				);
				$colorDescriptionIndex++;
			}

			$colorData[] = array(
				// Color value/name with a locale specific description.
				// Name of the color used as the default and in the admin.
				'code' => (string) $prdHlpr->extractNodeVal($xpath->query('Code/text()', $colorRecord)),
				'description' => $colorDescriptionData,
			);
			$colorIndex++;
		}

		// Selling name and description used to identify a product for advertising purposes
		// Short description of the selling/promotional name.
		$brandDescriptionData = array();
		$nodeBrandDescription = $xpath->query('ExtendedAttributes/Brand/Description', $item);
		foreach ($nodeBrandDescription as $brandDescriptionRecord) {
			$brandDescriptionData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($brandDescriptionRecord->getAttribute('xml:lang')),
				'description' => $brandDescriptionRecord->nodeValue
			);
		}

		$sizeData = array();
		$nodeSizeAttributes = $xpath->query('ExtendedAttributes/SizeAttributes/Size', $item);
		foreach ($nodeSizeAttributes as $sizeRecord) {
			$sizeData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($sizeRecord->getAttribute('xml:lang')), // Language code for the natural language of the size data.
				// Size code.
				'code' => (string) $prdHlpr->extractNodeVal($xpath->query('Code/text()', $sizeRecord)),
				// Size Description.
				'description' => (string) $prdHlpr->extractNodeVal($xpath->query('Description/text()', $sizeRecord)),
			);
		}
	}

	/**
	 * extract CustomAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractCustomAttributes(DOMXPath $xpath, DOMElement $item)
	{
		$attributeData = array();
		$prdHlpr = Mage::helper('eb2cproduct');

		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $item);
		foreach ($nodeAttribute as $attributeRecord) {
			$attributeData[] = array(
				// The name of the attribute.
				'name' => (string) $attributeRecord->getAttribute('name'),
				// Type of operation to take with this attribute. enum: ('Add', 'Change', 'Delete')
				'operationType' => (string) $attributeRecord->getAttribute('operation_type'),
				// Language code for the natural language or the <Value /> element.
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeRecord->getAttribute('xml:lang')),
				'value' => (string) $prdHlpr->extractNodeVal($xpath->query('Value/text()', $attributeRecord)),
			);
		}

		return new Varien_Object(
			array(
				'attributes' => $attributeData
			)
		);
	}

	/**
	 * extract productType from CustomAttributes data
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return string, the productType
	 */
	protected function _extractProductType(DOMXPath $xpath, DOMElement $item)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $item);
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'PRODUCTTYPE') {
				return strtolower(trim($prdHlpr->extractNodeVal($xpath->query('Value/text()', $attributeRecord))));
			}
		}

		return '';
	}

	/**
	 * extract ConfigurableAttributes from CustomAttributes data
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return array, the configurable attribute sets
	 */
	protected function _extractConfigurableAttributes(DOMXPath $xpath, DOMElement $item)
	{
		$prdHlpr = Mage::helper('eb2cproduct');
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query('CustomAttributes/Attribute', $item);
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'CONFIGURABLEATTRIBUTES') {
				return explode(',', (string) $prdHlpr->extractNodeVal($xpath->query('Value/text()', $attributeRecord)));
			}
		}

		return array();
	}

	/**
	 * extract feed data into a collection of varien objects
	 * @param DOMXPath $xpath, the DOMXPath with the loaded feed data
	 * @return array, an collection of varien objects
	 */
	public function extract(DOMXPath $xpath)
	{
		$collectionOfItems = array();
		$baseNode = self::FEED_BASE_NODE;

		$master = $xpath->query("//$baseNode");
		$idx = 1; // xpath uses 1-based indexing
		Mage::log(sprintf('[ %s ] Found %d items to extract', __CLASS__, $master->length), Zend_Log::DEBUG);
		foreach ($master as $item) {
			$catalogId = (string) $item->getAttribute('catalog_id');

			// setting item object into the collection of item objects.
			$collectionOfItems[] = new Varien_Object(
				array(
					// setting catalog id
					'catalog_id' => $catalogId,
					// setting gsi_client_id id
					'gsi_client_id' => (string) $item->getAttribute('gsi_client_id'),
					// Defines the action requested for this item. enum:("Add", "Change", "Delete")
					'operation_type' => (string) $item->getAttribute('operation_type'),
					// get varien object of item id node
					'item_id' => $this->_extractItemId($xpath, $item),
					// get varien object of base attributes node
					'base_attributes' => $this->_extractBaseAttributes($xpath, $item),
					// get varien object of Drop Ship Supplier Information attribute node
					'drop_ship_supplier_information' => $this->_extractDropShipSupplierInformation($xpath, $item),
					// get varien object of Extended Attributes node
					'extended_attributes' => $this->_extractExtendedAttributes($xpath, $item),
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($xpath, $item),
					// get product type from Custom Attributes node
					'product_type' => $this->_extractProductType($xpath, $item),
					// get configurable attributes from Custom Attributes node
					'configurable_attributes' => $this->_extractConfigurableAttributes($xpath, $item),
				)
			);

			// increment item index
			Mage::log(sprintf('[ %s ] Extracted %d of %d items', __CLASS__, $idx, $master->length), Zend_Log::DEBUG);
			$idx++;
		}
		return $collectionOfItems;
	}
}
