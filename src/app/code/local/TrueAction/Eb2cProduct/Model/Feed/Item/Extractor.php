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
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return Varien_Object
	 */
	protected function _extractItemId(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		// SKU used to identify this item from the client system.
		$nodeClientItemId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ItemId/ClientItemId");

		// Alternative identifier provided by the client.
		$nodeClientAltItemId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ItemId/ClientAltItemId");

		// Code assigned to the item by the manufacturer to identify the item.
		$nodeManufacturerItemId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ItemId/ManufacturerItemId");
		return new Varien_Object(array(
			'client_item_id' => ($nodeClientItemId->length)? (string) $nodeClientItemId->item(0)->nodeValue : null,
			'client_alt_item_id' => ($nodeClientAltItemId->length)? (string) $nodeClientAltItemId->item(0)->nodeValue : null,
			'manufacturer_item_id' => ($nodeManufacturerItemId->length)? (string) $nodeManufacturerItemId->item(0)->nodeValue : null,
		));
	}

	/**
	 * extract BaseAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return Varien_Object
	 */
	protected function _extractBaseAttributes(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		// Allows for control of the web store display.
		$nodeCatalogClass = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/BaseAttributes/CatalogClass");

		// Indicates the item if fulfilled by a drop shipper.
		// New attribute.
		$nodeIsDropShipped = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/BaseAttributes/IsDropShipped");

		// Short description in the catalog's base language.
		$nodeItemDescription = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/BaseAttributes/ItemDescription");

		// Identifies the type of item.
		$nodeItemType = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/BaseAttributes/ItemType");

		// Indicates whether an item is active, inactive or other various states.
		$nodeItemStatus = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/BaseAttributes/ItemStatus");

		// Tax group the item belongs to.
		$nodeTaxCode = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/BaseAttributes/TaxCode");

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
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return Varien_Object
	 */
	protected function _extractDropShipSupplierInformation(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		// Name of the Drop Ship Supplier fulfilling the item
		$nodeSupplierName = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/DropShipSupplierInformation/SupplierName");

		// Unique code assigned to this supplier.
		$nodeSupplierNumber = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/DropShipSupplierInformation/SupplierNumber");

		// Id or SKU used by the drop shipper to identify this item.
		$nodeSupplierPartNumber = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/DropShipSupplierInformation/SupplierPartNumber");

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
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return Varien_Object
	 */
	protected function _extractExtendedAttributes(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		// If false, customer cannot add a gift message to the item.
		$nodeAllowGiftMessage = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/AllowGiftMessage");

		// Item is able to be back ordered.
		$nodeBackOrderable = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/BackOrderable");

		$colorData = array();
		$nodeColorAttributes = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color");
		$colorIndex = 1;
		foreach ($nodeColorAttributes as $colorRecord) {
			// Color value/name with a locale specific description.
			// Name of the color used as the default and in the admin.
			$nodeColorCode = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color[$colorIndex]/Code");

			// Description of the color used for specific store views/languages.
			$nodeColorDescription = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color[$colorIndex]/Description");
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
		$nodeBrandName = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Brand/Name");

		// Short description of the selling/promotional name.
		$brandDescriptionData = array();
		$nodeBrandDescription = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Brand/Description");
		foreach ($nodeBrandDescription as $brandDescriptionRecord) {
			$brandDescriptionData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($brandDescriptionRecord->getAttribute('xml:lang')),
				'description' => $brandDescriptionRecord->nodeValue
			);
		}

		// Encapsulates information related to the individual/organization responsible for the procurement of this item.
		$nodeBuyerName = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Buyer/Name");
		$nodeBuyerId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Buyer/BuyerId");

		/*
		 * Whether the item is a "companion" (must ship with another product) or can ship alone. ENUM: ("Yes", No", "Maybe")
		 *    Yes - may ship alone
		 *    No - cancelled if not shipped with companion
		 *    Maybe - other factors decide
		 */
		$nodeCompanionFlag = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/CompanionFlag");

		// Country in which goods were completely derived or manufactured.
		$nodeCountryOfOrigin = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/CountryOfOrigin");

		/*
		 *  Type of gift card to be used for activation.
		 * 		SD - TRU Digital Gift Card
		 *		SP - SVS Physical Gift Card
		 *		ST - SmartClixx Gift Card Canada
		 *		SV - SVS Virtual Gift Card
		 *		SX - SmartClixx Gift Card
		 */
		$nodeGiftCardTenderCode = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/GiftCardTenderCode");

		// Indicates if the item is considered hazardous material.
		$nodeHazardousMaterialCode = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/HazardousMaterialCode");

		// Not included in display or in emails. Default to false.
		$nodeIsHiddenProduct = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/IsHiddenProduct");

		// Shipping weight of the item.
		$nodeShippingMass = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Shipping/Mass");

		// Shipping weight of the item.
		$nodeShippingWeight = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Shipping/Mass/Weight");

		// Unit of measure used for these dimensions.
		$nodeDisplayMass = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Display/Mass");

		// Item's weight using the above unit of measure.
		$nodeDisplayWeight = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Display/Mass/Weight");

		// Unit of measure used for these dimensions.
		$nodeDisplayPackaging = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Display/Packaging");

		// Item's width.
		$nodeDisplayPackagingWidth = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Display/Packaging/Width");

		// Item's length.
		$nodeDisplayPackagingLength = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Display/Packaging/Length");

		// Item's height.
		$nodeDisplayPackagingHeight = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Display/Packaging/Height");

		// Unit of measure used for these dimensions.
		$nodeShippingPackaging = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Shipping/Packaging");

		// Item's width.
		$nodeShippingPackagingWidth = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Shipping/Packaging/Width");

		// Item's length.
		$nodeShippingPackagingLength = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Shipping/Packaging/Length");

		// Item's height.
		$nodeShippingPackagingHeight = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Shipping/Packaging/Height");

		// Unit of measure used for these dimensions.
		$nodeCartonMass = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Carton/Mass");

		// Weight of the carton.
		$nodeCartonWeight = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Carton/Mass/Weight");

		// Unit of measure used for these dimensions.
		$nodeCartonPackaging = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Carton/Packaging");

		// Item's width.
		$nodeCartonPackagingWidth = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Carton/Packaging/Width");

		// Item's length.
		$nodeCartonPackagingLength = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Carton/Packaging/Length");

		// Item's height.
		$nodeCartonPackagingHeight = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/Carton/Packaging/Height");

		// Used in combination with Ship Ground to determine how the order is released by the OMS. Determined on a per client basis.
		$nodeCartonType = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimension/CartonType");

		// Indicates if the item's lot assignment is required to be tracked.
		$nodeLotTrackingIndicator = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/LotTrackingIndicator");

		// LTL freight cost for the item.
		$nodeLtlFreightCost = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/LTLFreightCost");

		// Date the item was build by the manufacturer.
		$nodeManufacturingDate = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ManufacturingDate");

		// Company name of manufacturer.
		$nodeManufacturerName = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Manufacturer/Name");

		// Unique identifier to denote the item manufacturer.
		$nodeManufacturerId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Manufacturer/ManufacturerId");

		// Vendor can ship expedited shipments. When false, should not offer expedited shipping on this item.
		$nodeMayShipExpedite = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/MayShipExpedite");

		// Indicates if the item may be shipped internationally.
		$nodeMayShipInternational = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/MayShipInternational");

		// Indicates if the item may be shipped via USPS.
		$nodeMayShipUsps = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/MayShipUSPS");

		// Manufacturers suggested retail price. Not used for actual price calculations.
		$nodeMsrp = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/MSRP");

		// Default price item is sold at. Required only if the item is new.
		$nodePrice = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Price");

		// Amount used for safety stock calculations.
		$nodeSafetyStock = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/SafetyStock");

		// Determines behavior on the live system when the item is backordered.
		$nodeSalesClass = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/SalesClass");

		// Type of serial number to be scanned.
		$nodeSerialNumberType = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/SerialNumberType");

		// Identifies the item as a service, e.g. clothing monogramming or hemming.
		$nodeServiceIndicator = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ServiceIndicator");

		// Distinguishes items that can be shipped together with those in the same group.
		$nodeShipGroup = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ShipGroup");

		// Minimum number of hours before the item may ship.
		$nodeShipWindowMinHour = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ShipWindowMinHour");

		// Maximum number of hours before the item may ship.
		$nodeShipWindowMaxHour = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/ShipWindowMaxHour");

		$sizeData = array();
		$nodeSizeAttributes = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/SizeAttributes/Size");
		foreach ($nodeSizeAttributes as $sizeRecord) {
			// Size code.
			$nodeSizeCode = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/SizeAttributes/Size/Code");

			// Size Description.
			$nodeSizeDescription = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/SizeAttributes/Size/Description");

			$sizeData[] = array(
				'lang' => Mage::helper('eb2ccore')->xmlToMageLangFrmt($sizeRecord->getAttribute('xml:lang')), // Language code for the natural language of the size data.
				'code' => ($nodeSizeCode->length)? (string) $nodeSizeCode->item(0)->nodeValue : null,
				'description' => ($nodeSizeDescription->length)? (string) $nodeSizeDescription->item(0)->nodeValue : null,
			);
		}

		// Earliest date the product can be shipped.
		$nodeStreetDate = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/StreetDate");

		// Code that identifies the specific appearance type or variety in which the item is available.
		$nodeStyleId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Style/StyleID");

		// Short description or title of the style for the item.
		$nodeStyleDescription = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Style/StyleDescription");

		// Name of the individual or organization providing the merchandise.
		$nodeSupplierName = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Supplier/Name");

		// Identifier for the supplier.
		$nodeSupplierId = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/ExtendedAttributes/Supplier/SupplierId");

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
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return Varien_Object
	 */
	protected function _extractCustomAttributes(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		$attributeData = array();

		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute");
		foreach ($nodeAttribute as $attributeRecord) {
			$nodeValue = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute/Value");

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
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return string, the productType
	 */
	protected function _extractProductType(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute");
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'PRODUCTTYPE') {
				$nodeValue = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute/Value");
				return ($nodeValue->length)? strtolower(trim($nodeValue->item(0)->nodeValue)) : '';
			}
		}

		return '';
	}

	/**
	 * extract ConfigurableAttributes from CustomAttributes data
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return array, the configurable attribute sets
	 */
	protected function _extractConfigurableAttributes(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		// Name value pairs of additional attributes for the product.
		$nodeAttribute = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute");
		foreach ($nodeAttribute as $attributeRecord) {
			if (trim(strtoupper($attributeRecord->getAttribute('name'))) === 'CONFIGURABLEATTRIBUTES') {
				$nodeValue = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute/Value");
				return ($nodeValue->length)? explode(',', $nodeValue->item(0)->nodeValue) : array();
			}
		}

		return array();
	}

	/**
	 * extract feed data into a collection of varien objects
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 * @return array, an collection of varien objects
	 */
	public function extract(DOMDocument $doc)
	{
		$collectionOfItems = array();
		$feedXPath = new DOMXPath($doc);
		$baseNode = self::FEED_BASE_NODE;

		$master = $feedXPath->query("//$baseNode");
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
					'item_id' => $this->_extractItemId($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of base attributes node
					'base_attributes' => $this->_extractBaseAttributes($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of Drop Ship Supplier Information attribute node
					'drop_ship_supplier_information' => $this->_extractDropShipSupplierInformation($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of Extended Attributes node
					'extended_attributes' => $this->_extractExtendedAttributes($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($feedXPath, $idx, $catalogId, $baseNode),
					// get product type from Custom Attributes node
					'product_type' => $this->_extractProductType($feedXPath, $idx, $catalogId, $baseNode),
					// get configurable attributes from Custom Attributes node
					'configurable_attributes' => $this->_extractConfigurableAttributes($feedXPath, $idx, $catalogId, $baseNode),
				)
			);

			// increment item index
			Mage::log(sprintf('[ %s ] Extracted %d of %d items', __CLASS__, $idx, $master->length), Zend_Log::DEBUG);
			$idx++;
		}
		return $collectionOfItems;
	}
}
