<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Extractor
	implements TrueAction_Eb2cProduct_Model_Feed_IExtractor
{
	const FEED_BASE_NODE = 'Item';

	/**
	 * extract item id data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractItemId(DOMXPath $xpath, DOMElement $item)
	{
		// SKU used to identify this item from the client system.
		$nodeClientItemId = $xpath->query("ItemId/ClientItemId/text()", $item);

		// Alternative identifier provided by the client.
		$nodeClientAltItemId = $xpath->query("ItemId/ClientAltItemId/text()", $item);

		// Code assigned to the item by the manufacturer to identify the item.
		$nodeManufacturerItemId = $xpath->query("ItemId/ManufacturerItemId/text()", $item);
		return new Varien_Object(array(
			'client_item_id' => ($nodeClientItemId->length)? (string) $nodeClientItemId->item(0)->nodeValue : null,
			'client_alt_item_id' => ($nodeClientAltItemId->length)? (string) $nodeClientAltItemId->item(0)->nodeValue : null,
			'manufacturer_item_id' => ($nodeManufacturerItemId->length)? (string) $nodeManufacturerItemId->item(0)->nodeValue : null,
		));
	}

	/**
	 * extract BaseAttributes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractBaseAttributes(DOMXPath $xpath, DOMElement $item)
	{
		// Allows for control of the web store display.
		$nodeCatalogClass = $xpath->query("BaseAttributes/CatalogClass/text()", $item);

		// Indicates the item if fulfilled by a drop shipper.
		// New attribute.
		$nodeIsDropShipped = $xpath->query("BaseAttributes/IsDropShipped/text()", $item);

		// Short description in the catalog's base language.
		$nodeItemDescription = $xpath->query("BaseAttributes/ItemDescription/text()", $item);

		// Identifies the type of item.
		$nodeItemType = $xpath->query("BaseAttributes/ItemType/text()", $item);

		// Indicates whether an item is active, inactive or other various states.
		$nodeItemStatus = $xpath->query("BaseAttributes/ItemStatus/text()", $item);

		// Tax group the item belongs to.
		$nodeTaxCode = $xpath->query("BaseAttributes/TaxCode/text()", $item);

		return new Varien_Object(
			array(
				'catalog_class' => ($nodeCatalogClass->length)? (string) $nodeCatalogClass->item(0)->nodeValue : null,
				'drop_shipped' => ($nodeIsDropShipped->length)? (bool) $nodeIsDropShipped->item(0)->nodeValue : false,
				'item_description' => ($nodeItemDescription->length)? (string) $nodeItemDescription->item(0)->nodeValue : null,
				'item_type' => ($nodeItemType->length)? strtolower(trim($nodeItemType->item(0)->nodeValue)) : null,
				'item_status' => ($nodeItemStatus->length > 0 && strtoupper(trim($nodeItemStatus->item(0)->nodeValue)) === 'ACTIVE')? 1 : 0,
				'tax_code' => ($nodeTaxCode->length)? (string) $nodeTaxCode->item(0)->nodeValue : null,
			)
		);
	}

	/**
	 * extract DropShipSupplierInformation data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return Varien_Object
	 */
	protected function _extractDropShipSupplierInformation(DOMXPath $xpath, DOMElement $item)
	{
		// Name of the Drop Ship Supplier fulfilling the item
		$nodeSupplierName = $xpath->query("DropShipSupplierInformation/SupplierName/text()", $item);

		// Unique code assigned to this supplier.
		$nodeSupplierNumber = $xpath->query("DropShipSupplierInformation/SupplierNumber/text()", $item);

		// Id or SKU used by the drop shipper to identify this item.
		$nodeSupplierPartNumber = $xpath->query("DropShipSupplierInformation/SupplierPartNumber/text()", $item);

		return new Varien_Object(
			array(
				'supplier_name' => ($nodeSupplierName->length)? (string) $nodeSupplierName->item(0)->nodeValue : null,
				'supplier_number' => ($nodeSupplierNumber->length)? (string) $nodeSupplierNumber->item(0)->nodeValue : null,
				'supplier_part_number' => ($nodeSupplierPartNumber->length)? (string) $nodeSupplierPartNumber->item(0)->nodeValue : null,
			)
		);
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
		// If false, customer cannot add a gift message to the item.
		$nodeAllowGiftMessage = $xpath->query("ExtendedAttributes/AllowGiftMessage/text()", $item);

		// Item is able to be back ordered.
		$nodeBackOrderable = $xpath->query("ExtendedAttributes/BackOrderable/text()", $item);

		$colorData = array();
		$nodeColorAttributes = $xpath->query("ExtendedAttributes/ColorAttributes/Color", $item);
		$colorIndex = 1;
		foreach ($nodeColorAttributes as $colorRecord) {
			// Color value/name with a locale specific description.
			// Name of the color used as the default and in the admin.
			$nodeColorCode = $xpath->query("Code/text()", $colorRecord);

			// Description of the color used for specific store views/languages.
			$nodeColorDescription = $xpath->query("Description", $colorRecord);
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
				'code' => ($nodeColorCode->length)? (string) $nodeColorCode->item(0)->nodeValue : null,
				'description' => $colorDescriptionData,
			);
			$colorIndex++;
		}

		// Selling name and description used to identify a product for advertising purposes
		// Selling/promotional name.
		$nodeBrandName = $xpath->query("ExtendedAttributes/Brand/Name/text()", $item);

		// Short description of the selling/promotional name.
		$brandDescriptionData = array();
		$nodeBrandDescription = $xpath->query("ExtendedAttributes/Brand/Description", $item);
		foreach ($nodeBrandDescription as $brandDescriptionRecord) {
			$brandDescriptionData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($brandDescriptionRecord->getAttribute('xml:lang')),
				'description' => $brandDescriptionRecord->nodeValue
			);
		}

		// Encapsulates information related to the individual/organization responsible for the procurement of this item.
		$nodeBuyerName = $xpath->query("ExtendedAttributes/Buyer/Name/text()", $item);
		$nodeBuyerId = $xpath->query("ExtendedAttributes/Buyer/BuyerId/text()", $item);

		/*
		 * Whether the item is a "companion" (must ship with another product) or can ship alone. ENUM: ("Yes", No", "Maybe")
		 *    Yes - may ship alone
		 *    No - cancelled if not shipped with companion
		 *    Maybe - other factors decide
		 */
		$nodeCompanionFlag = $xpath->query("ExtendedAttributes/CompanionFlag/text()", $item);

		// Country in which goods were completely derived or manufactured.
		$nodeCountryOfOrigin = $xpath->query("ExtendedAttributes/CountryOfOrigin/text()", $item);

		/*
		 *  Type of gift card to be used for activation.
		 * 		SD - TRU Digital Gift Card
		 *		SP - SVS Physical Gift Card
		 *		ST - SmartClixx Gift Card Canada
		 *		SV - SVS Virtual Gift Card
		 *		SX - SmartClixx Gift Card
		 */
		$nodeGiftCardTenderCode = $xpath->query("ExtendedAttributes/GiftCardTenderCode/text()", $item);

		// Indicates if the item is considered hazardous material.
		$nodeHazardousMaterialCode = $xpath->query("ExtendedAttributes/HazardousMaterialCode/text()", $item);

		// Not included in display or in emails. Default to false.
		$nodeIsHiddenProduct = $xpath->query("ExtendedAttributes/IsHiddenProduct/text()", $item);

		// Shipping weight of the item.
		$nodeShippingMass = $xpath->query("ExtendedAttributes/ItemDimension/Shipping/Mass/text()", $item);

		// Shipping weight of the item.
		$nodeShippingWeight = $xpath->query("ExtendedAttributes/ItemDimension/Shipping/Mass/Weight/text()", $item);

		// Unit of measure used for these dimensions.
		$nodeDisplayMass = $xpath->query("ExtendedAttributes/ItemDimension/Display/Mass/text()", $item);

		// Item's weight using the above unit of measure.
		$nodeDisplayWeight = $xpath->query("ExtendedAttributes/ItemDimension/Display/Mass/Weight/text()", $item);

		// Unit of measure used for these dimensions.
		$nodeDisplayPackaging = $xpath->query("ExtendedAttributes/ItemDimension/Display/Packaging/text()", $item);

		// Item's width.
		$nodeDisplayPackagingWidth = $xpath->query("ExtendedAttributes/ItemDimension/Display/Packaging/Width/text()", $item);

		// Item's length.
		$nodeDisplayPackagingLength = $xpath->query("ExtendedAttributes/ItemDimension/Display/Packaging/Length/text()", $item);

		// Item's height.
		$nodeDisplayPackagingHeight = $xpath->query("ExtendedAttributes/ItemDimension/Display/Packaging/Height/text()", $item);

		// Unit of measure used for these dimensions.
		$nodeShippingPackaging = $xpath->query("ExtendedAttributes/ItemDimension/Shipping/Packaging/text()", $item);

		// Item's width.
		$nodeShippingPackagingWidth = $xpath->query("ExtendedAttributes/ItemDimension/Shipping/Packaging/Width/text()", $item);

		// Item's length.
		$nodeShippingPackagingLength = $xpath->query("ExtendedAttributes/ItemDimension/Shipping/Packaging/Length/text()", $item);

		// Item's height.
		$nodeShippingPackagingHeight = $xpath->query("ExtendedAttributes/ItemDimension/Shipping/Packaging/Height/text()", $item);

		// Unit of measure used for these dimensions.
		$nodeCartonMass = $xpath->query("ExtendedAttributes/ItemDimension/Carton/Mass/text()", $item);

		// Weight of the carton.
		$nodeCartonWeight = $xpath->query("ExtendedAttributes/ItemDimension/Carton/Mass/Weight/text()", $item);

		// Unit of measure used for these dimensions.
		$nodeCartonPackaging = $xpath->query("ExtendedAttributes/ItemDimension/Carton/Packaging/text()", $item);

		// Item's width.
		$nodeCartonPackagingWidth = $xpath->query("ExtendedAttributes/ItemDimension/Carton/Packaging/Width/text()", $item);

		// Item's length.
		$nodeCartonPackagingLength = $xpath->query("ExtendedAttributes/ItemDimension/Carton/Packaging/Length/text()", $item);

		// Item's height.
		$nodeCartonPackagingHeight = $xpath->query("ExtendedAttributes/ItemDimension/Carton/Packaging/Height/text()", $item);

		// Used in combination with Ship Ground to determine how the order is released by the OMS. Determined on a per client basis.
		$nodeCartonType = $xpath->query("ExtendedAttributes/ItemDimension/CartonType/text()", $item);

		// Indicates if the item's lot assignment is required to be tracked.
		$nodeLotTrackingIndicator = $xpath->query("ExtendedAttributes/LotTrackingIndicator/text()", $item);

		// LTL freight cost for the item.
		$nodeLtlFreightCost = $xpath->query("ExtendedAttributes/LTLFreightCost/text()", $item);

		// Date the item was build by the manufacturer.
		$nodeManufacturingDate = $xpath->query("ExtendedAttributes/ManufacturingDate/text()", $item);

		// Company name of manufacturer.
		$nodeManufacturerName = $xpath->query("ExtendedAttributes/Manufacturer/Name/text()", $item);

		// Unique identifier to denote the item manufacturer.
		$nodeManufacturerId = $xpath->query("ExtendedAttributes/Manufacturer/ManufacturerId/text()", $item);

		// Vendor can ship expedited shipments. When false, should not offer expedited shipping on this item.
		$nodeMayShipExpedite = $xpath->query("ExtendedAttributes/MayShipExpedite/text()", $item);

		// Indicates if the item may be shipped internationally.
		$nodeMayShipInternational = $xpath->query("ExtendedAttributes/MayShipInternational/text()", $item);

		// Indicates if the item may be shipped via USPS.
		$nodeMayShipUsps = $xpath->query("ExtendedAttributes/MayShipUSPS/text()", $item);

		// Manufacturers suggested retail price. Not used for actual price calculations.
		$nodeMsrp = $xpath->query("ExtendedAttributes/MSRP/text()", $item);

		// Default price item is sold at. Required only if the item is new.
		$nodePrice = $xpath->query("ExtendedAttributes/Price/text()", $item);

		// Amount used for safety stock calculations.
		$nodeSafetyStock = $xpath->query("ExtendedAttributes/SafetyStock/text()", $item);

		// Determines behavior on the live system when the item is backordered.
		$nodeSalesClass = $xpath->query("ExtendedAttributes/SalesClass/text()", $item);

		// Type of serial number to be scanned.
		$nodeSerialNumberType = $xpath->query("ExtendedAttributes/SerialNumberType/text()", $item);

		// Identifies the item as a service, e.g. clothing monogramming or hemming.
		$nodeServiceIndicator = $xpath->query("ExtendedAttributes/ServiceIndicator/text()", $item);

		// Distinguishes items that can be shipped together with those in the same group.
		$nodeShipGroup = $xpath->query("ExtendedAttributes/ShipGroup/text()", $item);

		// Minimum number of hours before the item may ship.
		$nodeShipWindowMinHour = $xpath->query("ExtendedAttributes/ShipWindowMinHour/text()", $item);

		// Maximum number of hours before the item may ship.
		$nodeShipWindowMaxHour = $xpath->query("ExtendedAttributes/ShipWindowMaxHour/text()", $item);

		$sizeData = array();
		$nodeSizeAttributes = $xpath->query("ExtendedAttributes/SizeAttributes/Size", $item);
		foreach ($nodeSizeAttributes as $sizeRecord) {
			// Size code.
			$nodeSizeCode = $xpath->query("Code/text()", $sizeRecord);

			// Size Description.
			$nodeSizeDescription = $xpath->query("Description/text()", $sizeRecord);

			$sizeData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($sizeRecord->getAttribute('xml:lang')), // Language code for the natural language of the size data.
				'code' => ($nodeSizeCode->length)? (string) $nodeSizeCode->item(0)->nodeValue : null,
				'description' => ($nodeSizeDescription->length)? (string) $nodeSizeDescription->item(0)->nodeValue : null,
			);
		}

		// Earliest date the product can be shipped.
		$nodeStreetDate = $xpath->query("ExtendedAttributes/StreetDate/text()", $item);

		// Code that identifies the specific appearance type or variety in which the item is available.
		$nodeStyleId = $xpath->query("ExtendedAttributes/Style/StyleID/text()", $item);

		// Short description or title of the style for the item.
		$nodeStyleDescription = $xpath->query("ExtendedAttributes/Style/StyleDescription/text()", $item);

		// Name of the individual or organization providing the merchandise.
		$nodeSupplierName = $xpath->query("ExtendedAttributes/Supplier/Name/text()", $item);

		// Identifier for the supplier.
		$nodeSupplierId = $xpath->query("ExtendedAttributes/Supplier/SupplierId/text()", $item);

		return new Varien_Object(
			array(
				'allow_gift_message' => ($nodeAllowGiftMessage->length)? (bool) $nodeAllowGiftMessage->item(0)->nodeValue : false,
				'back_orderable' => ($nodeBackOrderable->length)? (string) $nodeBackOrderable->item(0)->nodeValue : null,
				'color_attributes' => new Varien_Object(
					array(
						'color' => $colorData,
					)
				),
				'country_of_origin' => ($nodeCountryOfOrigin->length)? (string) $nodeCountryOfOrigin->item(0)->nodeValue : null,
				'gift_card_tender_code' => ($nodeGiftCardTenderCode->length)? (string) $nodeGiftCardTenderCode->item(0)->nodeValue : null,
				'item_dimension_shipping' => new Varien_Object(
					array(
						'mass_unit_of_measure' => ($nodeShippingMass->length)? (string) $nodeShippingMass->item(0)->getAttribute('unit_of_measure') : null,
						'weight' => ($nodeShippingWeight->length)? (float) $nodeShippingWeight->item(0)->nodeValue : 0,
						'packaging' => new Varien_Object(
							array(
								'unit_of_measure' => ($nodeShippingPackaging->length)? (string) $nodeShippingPackaging->item(0)->getAttribute('unit_of_measure') : null,
								'width' => ($nodeShippingPackagingWidth->length)? (float) $nodeShippingPackagingWidth->item(0)->nodeValue : 0,
								'length' => ($nodeShippingPackagingLength->length)? (float) $nodeShippingPackagingLength->item(0)->nodeValue : 0,
								'height' => ($nodeShippingPackagingHeight->length)? (float) $nodeShippingPackagingHeight->item(0)->nodeValue : 0,
							)
						),
					)
				),
				'item_dimension_display' => new Varien_Object(
					array(
						'mass_unit_of_measure' => ($nodeDisplayMass->length)? (string) $nodeDisplayMass->item(0)->getAttribute('unit_of_measure') : null,
						'weight' => ($nodeDisplayWeight->length)? (float) $nodeDisplayWeight->item(0)->nodeValue : 0,
						'packaging' => new Varien_Object(
							array(
								'unit_of_measure' => ($nodeDisplayPackaging->length)? (string) $nodeDisplayPackaging->item(0)->getAttribute('unit_of_measure') : null,
								'width' => ($nodeDisplayPackagingWidth->length)? (float) $nodeDisplayPackagingWidth->item(0)->nodeValue : 0,
								'length' => ($nodeDisplayPackagingLength->length)? (float) $nodeDisplayPackagingLength->item(0)->nodeValue : 0,
								'height' => ($nodeDisplayPackagingHeight->length)? (float) $nodeDisplayPackagingHeight->item(0)->nodeValue : 0,
							)
						),
					)
				),
				'item_dimension_carton' => new Varien_Object(
					array(
						'mass_unit_of_measure' => ($nodeCartonMass->length)? (string) $nodeCartonMass->item(0)->getAttribute('unit_of_measure') : null,
						'weight' => ($nodeCartonWeight->length)? (float) $nodeCartonWeight->item(0)->nodeValue : 0,
						'packaging' => new Varien_Object(
							array(
								'unit_of_measure' => ($nodeCartonPackaging->length)? (string) $nodeCartonPackaging->item(0)->getAttribute('unit_of_measure') : null,
								'width' => ($nodeCartonPackagingWidth->length)? (float) $nodeCartonPackagingWidth->item(0)->nodeValue : 0,
								'length' => ($nodeCartonPackagingLength->length)? (float) $nodeCartonPackagingLength->item(0)->nodeValue : 0,
								'height' => ($nodeCartonPackagingHeight->length)? (float) $nodeCartonPackagingHeight->item(0)->nodeValue : 0,
							)
						),
						'type' => ($nodeCartonType->length)? (string) $nodeCartonType->item(0)->nodeValue : null,
					)
				),
				'lot_tracking_indicator' => ($nodeLotTrackingIndicator->length)? (string) $nodeLotTrackingIndicator->item(0)->nodeValue : null,
				'ltl_freight_cost' => ($nodeLtlFreightCost->length)? (string) $nodeLtlFreightCost->item(0)->nodeValue : null,
				'manufacturer' => new Varien_Object(
					array(
						'date' => ($nodeManufacturingDate->length)? (string) $nodeManufacturingDate->item(0)->nodeValue : null,
						'name' => ($nodeManufacturerName->length)? (string) $nodeManufacturerName->item(0)->nodeValue : null,
						'id' => ($nodeManufacturerId->length)? (string) $nodeManufacturerId->item(0)->nodeValue : null,
					)
				),
				'may_ship_expedite' => ($nodeMayShipExpedite->length)? (bool) $nodeMayShipExpedite->item(0)->nodeValue : false,
				'may_ship_international' => ($nodeMayShipInternational->length)? (bool) $nodeMayShipInternational->item(0)->nodeValue : false,
				'may_ship_usps' => ($nodeMayShipUsps->length)? (bool) $nodeMayShipUsps->item(0)->nodeValue : false,
				'msrp' => ($nodeMsrp->length)? (string) $nodeMsrp->item(0)->nodeValue : null,
				'price' => ($nodePrice->length)? (float) $nodePrice->item(0)->nodeValue : 0,
				'safety_stock' => ($nodeSafetyStock->length)? (int) $nodeSafetyStock->item(0)->nodeValue : 0,
				'sales_class' => ($nodeSalesClass->length)? (string) $nodeSalesClass->item(0)->nodeValue : null,
				'serial_number_type' => ($nodeSerialNumberType->length)? (string) $nodeSerialNumberType->item(0)->nodeValue : null,
				'service_indicator' => ($nodeServiceIndicator->length)? (bool) $nodeServiceIndicator->item(0)->nodeValue : false,
				'ship_group' => ($nodeShipGroup->length)? (string) $nodeShipGroup->item(0)->nodeValue : null,
				'ship_window_min_hour' => ($nodeShipWindowMinHour->length)? (int) $nodeShipWindowMinHour->item(0)->nodeValue : 0,
				'ship_window_max_hour' => ($nodeShipWindowMaxHour->length)? (int) $nodeShipWindowMaxHour->item(0)->nodeValue : 0,
				'size_attributes' => new Varien_Object(
					array(
						'size' => $sizeData
					)
				),
				'street_date' => ($nodeStreetDate->length)? (string) $nodeStreetDate->item(0)->nodeValue : null,
				'style_id' => ($nodeStyleId->length)? (string) $nodeStyleId->item(0)->nodeValue : null,
				'style_description' => ($nodeStyleDescription->length)? (string) $nodeStyleDescription->item(0)->nodeValue : null,
				'supplier_name' => ($nodeSupplierName->length)? (string) $nodeSupplierName->item(0)->nodeValue : null,
				'supplier_supplier_id' => ($nodeSupplierId->length)? (string) $nodeSupplierId->item(0)->nodeValue : null,
				'brand_name' => ($nodeBrandName->length)? (string) $nodeBrandName->item(0)->nodeValue : null,
				'brand_description' => $brandDescriptionData,
				'buyer_name' => ($nodeBuyerName->length)? (string) $nodeBuyerName->item(0)->nodeValue : null,
				'buyer_id' => ($nodeBuyerId->length)? (string) $nodeBuyerId->item(0)->nodeValue : null,
				'companion_flag' => ($nodeCompanionFlag->length)? (string) $nodeCompanionFlag->item(0)->nodeValue : null,
				'hazardous_material_code' => ($nodeHazardousMaterialCode->length)? (string) $nodeHazardousMaterialCode->item(0)->nodeValue : null,
				'is_hidden_product' => ($nodeIsHiddenProduct->length)? (bool) $nodeIsHiddenProduct->item(0)->nodeValue : false,
			)
		);
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

		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query("CustomAttributes/Attribute", $item);
		foreach ($nodeAttribute as $attributeRecord) {
			$nodeValue = $xpath->query("Value/text()", $attributeRecord);

			$attributeData[] = array(
				// The name of the attribute.
				'name' => (string) $attributeRecord->getAttribute('name'),
				// Type of operation to take with this attribute. enum: ("Add", "Change", "Delete")
				'operationType' => (string) $attributeRecord->getAttribute('operation_type'),
				// Language code for the natural language or the <Value /> element.
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($attributeRecord->getAttribute('xml:lang')),
				'value' => ($nodeValue->length)? (string) $nodeValue->item(0)->nodeValue : null,
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
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query("CustomAttributes/Attribute", $item);
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'PRODUCTTYPE') {
				$nodeValue = $xpath->query("Value/text()", $attributeRecord);
				return ($nodeValue->length)? strtolower(trim($nodeValue->item(0)->nodeValue)) : '';
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
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $xpath->query("CustomAttributes/Attribute", $item);
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'CONFIGURABLEATTRIBUTES') {
				$nodeValue = $xpath->query("Value/text()", $attributeRecord);
				return ($nodeValue->length)? explode(',', $nodeValue->item(0)->nodeValue) : array();
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
