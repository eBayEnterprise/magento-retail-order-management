<?php
class TrueAction_Eb2cProduct_Model_Feed_Item
	extends TrueAction_Eb2cProduct_Model_Feed_Abstract
{
	const UNIT_OPERATION_TYPE_XPATH = './@operation_type';

	public function __construct()
	{
		parent::__construct();
		$this->_operationExtractor = Mage::getModel(
			'eb2cproduct/feed_extractor_specialized_operationtype',
			self::UNIT_OPERATION_TYPE_XPATH
		);
		$this->_extractors = array(
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extractMap)),
			Mage::getModel('eb2cproduct/feed_extractor_xpath', array($this->_extendedAttributes)),
			Mage::getModel('eb2cproduct/feed_extractor_typecast', array($this->_extractBool, 'boolean')),
			Mage::getModel('eb2cproduct/feed_extractor_typecast', array($this->_extendedAttributesFloat, 'float')),
			Mage::getModel('eb2cproduct/feed_extractor_typecast', array($this->_extendedAttributesInt, 'int')),
			Mage::getModel('eb2cproduct/feed_extractor_color', array(
				array('color' => 'ExtendedAttributes/ColorAttributes/Color'),
				array('code' => 'Code/text()')
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('size' => 'ExtendedAttributes/SizeAttributes/Size'),
				array(
					'code' => 'Code/text()',
					'description' => 'Description/text()',
					'lang' => './@xml:lang'
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('brand_description' => 'ExtendedAttributes/Brand/Description'),
				array(
					'description' => '.',
					'lang' => './@xml:lang'
				)
			)),
			Mage::getModel('eb2cproduct/feed_extractor_mappinglist', array(
				array('custom_attributes' => 'CustomAttributes/Attribute'),
				array(
					// Custom attribute name.
					'name' => './@name',
					// Operation to take with the attribute. ("Add", "Change", "Delete")
					'operation_type' => './@operation_type',
					// Operation to take with the product link. ("Add", "Delete")
					'lang' => './@xml:lang',
					// Unique ID (SKU) for the linked product.
					'value' => 'Value',
				)
			)),
		);
		$this->_baseXpath = '/ItemMaster/Item';
		$this->_feedLocalPath = $this->_config->itemFeedLocalPath;
		$this->_feedRemotePath = $this->_config->itemFeedRemotePath;
		$this->_feedFilePattern = $this->_config->itemFeedFilePattern;
		$this->_feedEventType = $this->_config->itemFeedEventType;
	}

	protected $_extractMap = array(
		// Selling/promotional name.
		'brand_name' => 'ExtendedAttributes/Brand/Name/text()',
		// Allows for control of the web store display.
		'catalog_class' => 'BaseAttributes/CatalogClass/text()',
		// Alternative identifier provided by the client.
		'client_alt_item_id' => 'ItemId/ClientAltItemId/text()',
		// SKU used to identify this item from the client system.
		'client_item_id' => 'ItemId/ClientItemId/text()',
		// Name of the Drop Ship Supplier fulfilling the item
		'drop_ship_supplier_name' => 'DropShipSupplierInformation/SupplierName/text()',
		// Unique code assigned to this supplier.
		'drop_ship_supplier_number' => 'DropShipSupplierInformation/SupplierNumber/text()',
		// Id or SKU used by the drop shipper to identify this item.
		'drop_ship_supplier_part_number' => 'DropShipSupplierInformation/SupplierPartNumber/text()',
		// Indicates the item if fulfilled by a drop shipper. New attribute.
		'is_drop_shipped' => 'BaseAttributes/IsDropShipped/text()',
		// Identifies the type of item.
		'item_type' => 'BaseAttributes/ItemType/text()',
		// Indicates whether an item is active, inactive or other various states.
		'item_status' => 'BaseAttributes/ItemStatus/text()',
		// item dimensions structure
		'item_dimension_shipping_mass_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Shipping/Mass/@unit_of_measure',
		'item_dimension_shipping_packaging_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Shipping/Packaging/@unit_of_measure',
		'item_dimension_display_packaging_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Display/Packaging/@unit_of_measure',
		'item_dimension_display_mass_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Display/Mass/@unit_of_measure',
		'item_dimension_carton_mass_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Carton/Mass/@unit_of_measure',
		'item_dimension_carton_packaging_unit_of_measure' => 'ExtendedAttributes/ItemDimension/Carton/Packaging/@unit_of_measure',
		'item_dimension_carton_type' => 'ExtendedAttributes/ItemDimension/CartonType/text()',
		// Date the item was build by the manufacturer.
		'manufacturer_date' => 'ExtendedAttributes/ManufacturingDate/text()',
		// Code assigned to the item by the manufacturer to identify the item.
		'manufacturer_item_id' => 'ItemId/ManufacturerItemId/text()',
		// Company name of manufacturer.
		'manufacturer_name' => 'ExtendedAttributes/Manufacturer/Name/text()',
		// Unique identifier to denote the item manufacturer.
		'manufacturer_id' => 'ExtendedAttributes/Manufacturer/ManufacturerId/text()',
		// Tax group the item belongs to.
		'tax_code' => 'BaseAttributes/TaxCode/text()',
	);

	protected $_extractBool = array(
		// If false, customer cannot add a gift message to the item.
		'allow_gift_message' => 'ExtendedAttributes/AllowGiftMessage/text()',
		// Not included in display or in emails. Default to false.
		'is_hidden_product' => 'ExtendedAttributes/IsHiddenProduct/text()',
		// Identifies the item as a service, e.g. clothing monogramming or hemming.
		'service_indicator' => 'ExtendedAttributes/ServiceIndicator/text()',
	);

	protected $_extendedAttributesFloat = array(
		// item packaging measurements.
		'item_dimension_carton_mass_weight' => 'ExtendedAttributes/ItemDimension/Carton/Mass/Weight/text()',
		'item_dimension_carton_packaging_length' => 'ExtendedAttributes/ItemDimension/Carton/Packaging/Length/text()',
		'item_dimension_carton_packaging_width' => 'ExtendedAttributes/ItemDimension/Carton/Packaging/Width/text()',
		'item_dimension_carton_packaging_height' => 'ExtendedAttributes/ItemDimension/Carton/Packaging/Height/text()',
		// display measurments
		'item_dimension_display_mass_unit_of_measure_weight' => 'ExtendedAttributes/ItemDimension/Display/Mass/Weight/text()',
		'item_dimension_display_packaging_length' => 'ExtendedAttributes/ItemDimension/Display/Packaging/Length/text()',
		'item_dimension_display_packaging_width' => 'ExtendedAttributes/ItemDimension/Display/Packaging/Width/text()',
		'item_dimension_display_packaging_height' => 'ExtendedAttributes/ItemDimension/Display/Packaging/Height/text()',
		// Shipping weight of the item.
		'item_dimension_shipping_mass_weight' => 'ExtendedAttributes/ItemDimension/Shipping/Mass/Weight/text()',
		'item_dimension_shipping_packaging_length' => 'ExtendedAttributes/ItemDimension/Shipping/Packaging/Length/text()',
		'item_dimension_shipping_packaging_width' => 'ExtendedAttributes/ItemDimension/Shipping/Packaging/Width/text()',
		'item_dimension_shipping_packaging_height' => 'ExtendedAttributes/ItemDimension/Shipping/Packaging/Height/text()',
		// Vendor can ship expedited shipments. When false, should not offer expedited shipping on this item.
		'may_ship_expedite' => 'ExtendedAttributes/MayShipExpedite/text()',
		// Indicates if the item may be shipped internationally.
		'may_ship_international' => 'ExtendedAttributes/MayShipInternational/text()',
		// Indicates if the item may be shipped via USPS.
		'may_ship_usps' => 'ExtendedAttributes/MayShipUSPS/text()',
		// Manufacturers suggested retail price. Not used for actual price calculations.
		'msrp' => 'ExtendedAttributes/MSRP/text()',
		// Default price item is sold at. Required only if the item is new.
		'price' => 'ExtendedAttributes/Price/text()',
	);

	protected $_extendedAttributesInt = array(
		// Amount used for safety stock calculations.
		'safety_stock' => 'ExtendedAttributes/SafetyStock/text()',
		// Minimum number of hours before the item may ship.
		'ship_window_min_hour' => 'ExtendedAttributes/ShipWindowMinHour/text()',
		// Maximum number of hours before the item may ship.
		'ship_window_max_hour' => 'ExtendedAttributes/ShipWindowMaxHour/text()',
	);

	protected $_extendedAttributes = array(
		/*
		 * Whether the item is a 'companion' (must ship with another product) or can ship alone. ENUM: ('Yes', No', 'Maybe')
		 *    Yes - may ship alone
		 *    No - cancelled if not shipped with companion
		 *    Maybe - other factors decide
		 */
		'companion_flag' => 'ExtendedAttributes/CompanionFlag/text()',
		// Indicates if the item is considered hazardous material.
		'hazardous_material_code' => 'ExtendedAttributes/HazardousMaterialCode/text()',
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
		// Indicates if the item's lot assignment is required to be tracked.
		'lot_tracking_indicator' => 'ExtendedAttributes/LotTrackingIndicator/text()',
		// LTL freight cost for the item.
		'ltl_freight_cost' => 'ExtendedAttributes/LTLFreightCost/text()',
		// Determines behavior on the live system when the item is backordered.
		'sales_class' => 'ExtendedAttributes/SalesClass/text()',
		// Type of serial number to be scanned.
		'serial_number_type' => 'ExtendedAttributes/SerialNumberType/text()',
		// Distinguishes items that can be shipped together with those in the same group.
		'ship_group' => 'ExtendedAttributes/ShipGroup/text()',
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
		// Encapsulates information related to the individual/organization responsible for the procurement of this item.
		'buyer_name' => 'ExtendedAttributes/Buyer/Name/text()',
		'buyer_id' => 'ExtendedAttributes/Buyer/BuyerId/text()',
	);
}
