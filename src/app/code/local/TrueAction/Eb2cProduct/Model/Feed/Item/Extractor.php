<?php
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
		$prdHlpr = Mage::helper('eb2cproduct');
		return new Varien_Object(array(
			// SKU used to identify this item from the client system.
			'client_item_id' => (string) $prdHlpr->extractNodeVal($xpath->query('ItemId/ClientItemId/text()', $item)),
			// Alternative identifier provided by the client.
			'client_alt_item_id' => (string) $prdHlpr->extractNodeVal($xpath->query('ItemId/ClientAltItemId/text()', $item)),
			// Code assigned to the item by the manufacturer to identify the item.
			'manufacturer_item_id' => (string) $prdHlpr->extractNodeVal($xpath->query('ItemId/ManufacturerItemId/text()', $item)),
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
		$prdHlpr = Mage::helper('eb2cproduct');
		return new Varien_Object(
			array(
				// Allows for control of the web store display.
				'catalog_class' => (string) $prdHlpr->extractNodeVal($xpath->query('BaseAttributes/CatalogClass/text()', $item)),
				// Indicates the item if fulfilled by a drop shipper. New attribute.
				'drop_shipped' => (bool) $prdHlpr->extractNodeVal($xpath->query('BaseAttributes/IsDropShipped/text()', $item)),
				// Short description in the catalog's base language.
				'item_description' => (string) $prdHlpr->extractNodeVal($xpath->query('BaseAttributes/ItemDescription/text()', $item)),
				// Identifies the type of item.
				'item_type' => (string) $prdHlpr->extractNodeVal($xpath->query('BaseAttributes/ItemType/text()', $item)),
				// Indicates whether an item is active, inactive or other various states.
				'item_status' => (strtoupper(trim($prdHlpr->extractNodeVal($xpath->query('BaseAttributes/ItemStatus/text()', $item)))) === 'ACTIVE')? 1 : 0,
				// Tax group the item belongs to.
				'tax_code' => (string) $prdHlpr->extractNodeVal($xpath->query('BaseAttributes/TaxCode/text()', $item)),
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
		$prdHlpr = Mage::helper('eb2cproduct');
		return new Varien_Object(
			array(
				// Name of the Drop Ship Supplier fulfilling the item
				'supplier_name' => (string) $prdHlpr->extractNodeVal($xpath->query('DropShipSupplierInformation/SupplierName/text()', $item)),
				// Unique code assigned to this supplier.
				'supplier_number' => (string) $prdHlpr->extractNodeVal($xpath->query('DropShipSupplierInformation/SupplierNumber/text()', $item)),
				// Id or SKU used by the drop shipper to identify this item.
				'supplier_part_number' => (string) $prdHlpr->extractNodeVal($xpath->query('DropShipSupplierInformation/SupplierPartNumber/text()', $item)),
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

		return new Varien_Object(
			array(
				// If false, customer cannot add a gift message to the item.
				'allow_gift_message' => (bool) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/AllowGiftMessage/text()', $item)),
				// Item is able to be back ordered.
				'back_orderable' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/BackOrderable/text()', $item)),
				'color_attributes' => new Varien_Object(
					array(
						'color' => $colorData,
					)
				),
				// Country in which goods were completely derived or manufactured.
				'country_of_origin' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/CountryOfOrigin/text()', $item)),
				/*
				 *  Type of gift card to be used for activation.
				 * 		SD - TRU Digital Gift Card
				 *		SP - SVS Physical Gift Card
				 *		ST - SmartClixx Gift Card Canada
				 *		SV - SVS Virtual Gift Card
				 *		SX - SmartClixx Gift Card
				 */
				'gift_card_tender_code' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/GiftCardTenderCode/text()', $item)),
				'item_dimension_shipping' => new Varien_Object(
					array(
						// Shipping weight of the item.
						'mass_unit_of_measure' => (string) $prdHlpr->extractNodeAttributeVal(
							$xpath->query('ExtendedAttributes/ItemDimension/Shipping/Mass', $item), 'unit_of_measure'
						),
						// Shipping weight of the item.
						'weight' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Shipping/Mass/Weight/text()', $item)),
						'packaging' => new Varien_Object(
							array(
								// Unit of measure used for these dimensions.
								'unit_of_measure' => (string) $prdHlpr->extractNodeAttributeVal(
									$xpath->query('ExtendedAttributes/ItemDimension/Shipping/Packaging', $item), 'unit_of_measure'
								),
								'width' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Shipping/Packaging/Width/text()', $item)),
								'length' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Shipping/Packaging/Length/text()', $item)),
								'height' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Shipping/Packaging/Height/text()', $item)),
							)
						),
					)
				),
				'item_dimension_display' => new Varien_Object(
					array(
						'mass_unit_of_measure' => (string) $prdHlpr->extractNodeAttributeVal(
							$xpath->query('ExtendedAttributes/ItemDimension/Display/Mass', $item), 'unit_of_measure'
						),
						'weight' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Display/Mass/Weight/text()', $item)),
						'packaging' => new Varien_Object(
							array(
								'unit_of_measure' => (string) $prdHlpr->extractNodeAttributeVal(
									$xpath->query('ExtendedAttributes/ItemDimension/Display/Packaging', $item), 'unit_of_measure'
								),
								'width' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Display/Packaging/Width/text()', $item)),
								'length' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Display/Packaging/Length/text()', $item)),
								'height' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Display/Packaging/Height/text()', $item)),
							)
						),
					)
				),
				'item_dimension_carton' => new Varien_Object(
					array(
						'mass_unit_of_measure' => (string) $prdHlpr->extractNodeAttributeVal(
							$xpath->query('ExtendedAttributes/ItemDimension/Carton/Mass', $item), 'unit_of_measure'
						),
						'weight' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Carton/Mass/Weight/text()', $item)),
						'packaging' => new Varien_Object(
							array(
								'unit_of_measure' => (string) $prdHlpr->extractNodeAttributeVal(
									$xpath->query('ExtendedAttributes/ItemDimension/Carton/Packaging', $item), 'unit_of_measure'
								),
								'width' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Carton/Packaging/Width/text()', $item)),
								'length' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Carton/Packaging/Length/text()', $item)),
								'height' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/Carton/Packaging/Height/text()', $item)),
							)
						),
						// Used in combination with Ship Ground to determine how the order is released by the OMS. Determined on a per client basis.
						'type' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ItemDimension/CartonType/text()', $item)),
					)
				),
				// Indicates if the item's lot assignment is required to be tracked.
				'lot_tracking_indicator' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/LotTrackingIndicator/text()', $item)),
				// LTL freight cost for the item.
				'ltl_freight_cost' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/LTLFreightCost/text()', $item)),
				'manufacturer' => new Varien_Object(
					array(
						// Date the item was build by the manufacturer.
						'date' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ManufacturingDate/text()', $item)),
						// Company name of manufacturer.
						'name' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Manufacturer/Name/text()', $item)),
						// Unique identifier to denote the item manufacturer.
						'id' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Manufacturer/ManufacturerId/text()', $item)),
					)
				),
				// Vendor can ship expedited shipments. When false, should not offer expedited shipping on this item.
				'may_ship_expedite' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/MayShipExpedite/text()', $item)),
				// Indicates if the item may be shipped internationally.
				'may_ship_international' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/MayShipInternational/text()', $item)),
				// Indicates if the item may be shipped via USPS.
				'may_ship_usps' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/MayShipUSPS/text()', $item)),
				// Manufacturers suggested retail price. Not used for actual price calculations.
				'msrp' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/MSRP/text()', $item)),
				// Default price item is sold at. Required only if the item is new.
				'price' => (float) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Price/text()', $item)),
				// Amount used for safety stock calculations.
				'safety_stock' => (int) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/SafetyStock/text()', $item)),
				// Determines behavior on the live system when the item is backordered.
				'sales_class' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/SalesClass/text()', $item)),
				// Type of serial number to be scanned.
				'serial_number_type' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/SerialNumberType/text()', $item)),
				// Identifies the item as a service, e.g. clothing monogramming or hemming.
				'service_indicator' => (bool) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ServiceIndicator/text()', $item)),
				// Distinguishes items that can be shipped together with those in the same group.
				'ship_group' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ShipGroup/text()', $item)),
				// Minimum number of hours before the item may ship.
				'ship_window_min_hour' => (int) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ShipWindowMinHour/text()', $item)),
				// Maximum number of hours before the item may ship.
				'ship_window_max_hour' => (int) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/ShipWindowMaxHour/text()', $item)),
				'size_attributes' => new Varien_Object(
					array(
						'size' => $sizeData
					)
				),
				// Earliest date the product can be shipped.
				'street_date' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/StreetDate/text()', $item)),
				// Code that identifies the specific appearance type or variety in which the item is available.
				'style_id' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Style/StyleID/text()', $item)),
				// Short description or title of the style for the item.
				'style_description' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Style/StyleDescription/text()', $item)),
				// Name of the individual or organization providing the merchandise.
				'supplier_name' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Supplier/Name/text()', $item)),
				// Identifier for the supplier.
				'supplier_supplier_id' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Supplier/SupplierId/text()', $item)),
				// Selling/promotional name.
				'brand_name' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Brand/Name/text()', $item)),
				'brand_description' => $brandDescriptionData,
				// Encapsulates information related to the individual/organization responsible for the procurement of this item.
				'buyer_name' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Buyer/Name/text()', $item)),
				'buyer_id' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/Buyer/BuyerId/text()', $item)),
				/*
				 * Whether the item is a 'companion' (must ship with another product) or can ship alone. ENUM: ('Yes', No', 'Maybe')
				 *    Yes - may ship alone
				 *    No - cancelled if not shipped with companion
				 *    Maybe - other factors decide
				 */
				'companion_flag' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/CompanionFlag/text()', $item)),
				// Indicates if the item is considered hazardous material.
				'hazardous_material_code' => (string) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/HazardousMaterialCode/text()', $item)),
				// Not included in display or in emails. Default to false.
				'is_hidden_product' => (bool) $prdHlpr->extractNodeVal($xpath->query('ExtendedAttributes/IsHiddenProduct/text()', $item)),
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
