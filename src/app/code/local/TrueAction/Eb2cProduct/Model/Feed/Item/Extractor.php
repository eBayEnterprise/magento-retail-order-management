<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_Item_Extractor extends Mage_Core_Model_Abstract
{
	/**
	 * extract item id data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $itemIndex, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractItemId(DOMXPath $feedXPath, $itemIndex, $catalogId)
	{
		// SKU used to identify this item from the client system.
		$nodeClientItemId = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ItemId/ClientItemId");
		return new Varien_Object(array('client_item_id' => ($nodeClientItemId->length)? (string) $nodeClientItemId->item(0)->nodeValue : null));
	}

	/**
	 * extract BaseAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $itemIndex, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractBaseAttributes(DOMXPath $feedXPath, $itemIndex, $catalogId)
	{
		// Allows for control of the web store display.
		$nodeCatalogClass = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BaseAttributes/CatalogClass");

		// Indicates the item if fulfilled by a drop shipper.
		// New attribute.
		$nodeIsDropShipped = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BaseAttributes/IsDropShipped");

		// Short description in the catalog's base language.
		$nodeItemDescription = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BaseAttributes/ItemDescription");

		// Identifies the type of item.
		$nodeItemType = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BaseAttributes/ItemType");

		// Indicates whether an item is active, inactive or other various states.
		$nodeItemStatus = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BaseAttributes/ItemStatus");

		// Tax group the item belongs to.
		$nodeTaxCode = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BaseAttributes/TaxCode");

		return new Varien_Object(
			array(
				'catalog_class' => ($nodeCatalogClass->length)? (string) $nodeCatalogClass->item(0)->nodeValue : null,
				'drop_shipped' => ($nodeIsDropShipped->length)? (bool) $nodeIsDropShipped->item(0)->nodeValue : false,
				'item_description' => ($nodeItemDescription->length)? (string) $nodeItemDescription->item(0)->nodeValue : null,
				'item_type' => ($nodeItemType->length)? strtolower(trim($nodeItemType->item(0)->nodeValue)) : null,
				'item_status' => (strtoupper(trim($nodeItemStatus->item(0)->nodeValue)) === 'ACTIVE')? 1 : 0,
				'tax_code' => ($nodeTaxCode->length)? (string) $nodeTaxCode->item(0)->nodeValue : null,
			)
		);
	}

	/**
	 * extract BundleContents data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $itemIndex, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractBundleContents(DOMXPath $feedXPath, $itemIndex, $catalogId)
	{
		$bundleItemCollection = array();

		// get all bundle items
		$nodeBundleItems = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BundleContents/BundleItems");
		if ($nodeBundleItems->length) {
			$bundleItemIndex = 1;
			foreach ($nodeBundleItems as $bundleItem) {
				// Client or vendor id (SKU) for the item to be included in the bundle.
				$nodeItemID = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BundleContents/BundleItems[$bundleItemIndex]/ItemID");

				// How many of the child item come in the bundle.
				$nodeQuantity = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/BundleContents/BundleItems[$bundleItemIndex]/Quantity");

				$bundleItemCollection[] = new Varien_Object(
					array(
						'ship_together' => (bool) $bundleItem->getAttribute('ship_together'), // All items in the bundle must ship together.
						'operation_type' => (string) $bundleItem->getAttribute('operation_type'),
						'catalog_id' => (string) $bundleItem->getAttribute('catalog_id'),
						'item_id' => ($nodeItemID->length)? (string) $nodeItemID->item(0)->nodeValue : null,
						'quantity' => ($nodeQuantity->length)? (int) $nodeQuantity->item(0)->nodeValue : null,
					)
				);

				$bundleItemIndex++;
			}
		}

		return new Varien_Object(array('bundle_items' => ($nodeBundleItems->length)? $bundleItemCollection : null));
	}

	/**
	 * extract DropShipSupplierInformation data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $itemIndex, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractDropShipSupplierInformation(DOMXPath $feedXPath, $itemIndex, $catalogId)
	{
		// Name of the Drop Ship Supplier fulfilling the item
		$nodeSupplierName = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/DropShipSupplierInformation/SupplierName");

		// Unique code assigned to this supplier.
		$nodeSupplierNumber = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/DropShipSupplierInformation/SupplierNumber");

		// Id or SKU used by the drop shipper to identify this item.
		$nodeSupplierPartNumber = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/DropShipSupplierInformation/SupplierPartNumber");

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
	 * @param int $itemIndex, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractExtendedAttributes(DOMXPath $feedXPath, $itemIndex, $catalogId)
	{
		// If false, customer cannot add a gift message to the item.
		$nodeAllowGiftMessage = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/AllowGiftMessage");

		// Item is able to be back ordered.
		$nodeBackOrderable = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/BackOrderable");

		// Color value/name with a locale specific description.
		// Name of the color used as the default and in the admin.
		$nodeColorCode = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color/Code");

		// Description of the color used for specific store views/languages.
		$nodeColorDescription = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/ColorAttributes/Color/Description");

		// Country in which goods were completely derived or manufactured.
		$nodeCountryOfOrigin = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/CountryOfOrigin");

		/*
		 *  Type of gift card to be used for activation.
		 * 		SD - TRU Digital Gift Card
		 *		SP - SVS Physical Gift Card
		 *		ST - SmartClixx Gift Card Canada
		 *		SV - SVS Virtual Gift Card
		 *		SX - SmartClixx Gift Card
		 */
		$nodeGiftCardTenderCode = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/GiftCardTenderCode");

		// Shipping weight of the item.
		$nodeMass = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimensions/Shipping/Mass");

		// Shipping weight of the item.
		$nodeWeight = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/ItemDimensions/Shipping/Mass/Weight");

		// Manufacturers suggested retail price. Not used for actual price calculations.
		$nodeMsrp = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/MSRP");

		// Default price item is sold at. Required only if the item is new.
		$nodePrice = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/Price");

		$sizeData = array();
		$nodeSizeAttributes = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/SizeAttributes/Size");
		foreach ($nodeSizeAttributes as $sizeRecord) {
			// Size code.
			$nodeSizeCode = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/SizeAttributes/Size/Code");

			// Size Description.
			$nodeSizeDescription = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/ExtendedAttributes/SizeAttributes/Size/Description");

			$sizeData[] = array(
				'lang' => $sizeRecord->getAttribute('xml:lang'), // Language code for the natural language of the size data.
				'code' => ($nodeSizeCode->length)? (string) $nodeSizeCode->item(0)->nodeValue : null,
				'description' => ($nodeSizeDescription->length)? (string) $nodeSizeDescription->item(0)->nodeValue : null,
			);
		}

		return new Varien_Object(
			array(
				'allow_gift_message' => ($nodeAllowGiftMessage->length)? (bool) $nodeAllowGiftMessage->item(0)->nodeValue : false,
				'back_orderable' => ($nodeBackOrderable->length)? (string) $nodeBackOrderable->item(0)->nodeValue : null,
				'color_attributes' => new Varien_Object(
					array(
						'color_code' => ($nodeColorCode->length)? (string) $nodeColorCode->item(0)->nodeValue : null,
						'description' => ($nodeColorDescription->length)? (string) $nodeColorDescription->item(0)->nodeValue : null,
						'country_of_origin' => ($nodeCountryOfOrigin->length)? (string) $nodeCountryOfOrigin->item(0)->nodeValue : null,
						'gift_card_tender_code' => ($nodeGiftCardTenderCode->length)? (string) $nodeGiftCardTenderCode->item(0)->nodeValue : null,
					)
				),
				'item_dimensions_shipping' => new Varien_Object(
					array(
						'mass_unit_of_measure' => ($nodeMass->length)? (string) $nodeMass->item(0)->getAttribute('unit_of_measure') : null,
						'weight' => ($nodeWeight->length)? (float) $nodeWeight->item(0)->nodeValue : 0,
					)
				),
				'msrp' => ($nodeMsrp->length)? (string) $nodeMsrp->item(0)->nodeValue : null,
				'price' => ($nodePrice->length)? (float) $nodePrice->item(0)->nodeValue : 0,
				'size_attributes' => new Varien_Object(
					array(
						'size' => $sizeData
					)
				)
			)
		);
	}

	/**
	 * extract CustomAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $itemIndex, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 *
	 * @return Varien_Object
	 */
	protected function _extractCustomAttributes(DOMXPath $feedXPath, $itemIndex, $catalogId)
	{
		$attributeData = array();

		// Name value paris of additional attributes for the product.
		$nodeAttribute = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/CustomAttributes/Attribute");
		if ($nodeAttribute->length) {
			foreach ($nodeAttribute as $attributeRecord) {
				$nodeValue = $feedXPath->query("//Item[$itemIndex][@catalog_id='$catalogId']/CustomAttributes/Attribute/Value");

				$attributeData[] = array(
					// The name of the attribute.
					'name' => (string) $attributeRecord->getAttribute('name'),
					// Type of operation to take with this attribute. enum: ("Add", "Change", "Delete")
					'operationType' => (string) $attributeRecord->getAttribute('operation_type'),
					// Language code for the natural language or the <Value /> element.
					'lang' => (string) $attributeRecord->getAttribute('xml:lang'),
					'value' => ($nodeValue->length)? (string) $nodeValue->item(0)->nodeValue : null,
				);
			}
		}

		return new Varien_Object(
			array(
				'attributes' => $attributeData
			)
		);
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extractItemMasterFeed(DOMDocument $doc)
	{
		$collectionOfItems = array();
		$feedXPath = new DOMXPath($doc);

		$master = $feedXPath->query('//Item');
		$itemIndex = 1; // start index
		foreach ($master as $item) {
			$catalogId = (string) $item->getAttribute('catalog_id');

			// setting item object into the colelction of item objects.
			$collectionOfItems[] = new Varien_Object(
				array(
					// setting catalog id
					'catalog_id' => $catalogId,
					// setting gsi_client_id id
					'gsi_client_id' => (string) $item->getAttribute('gsi_client_id'),
					// Defines the action requested for this item. enum:("Add", "Change", "Delete")
					'operation_type' => (string) $item->getAttribute('operation_type'),
					// get varien object of item id node
					'item_id' => $this->_extractItemId($feedXPath, $itemIndex, $catalogId),
					// get varien object of base attributes node
					'base_attributes' => $this->_extractBaseAttributes($feedXPath, $itemIndex, $catalogId),
					// get varien object of bundle attributes node
					'bundle_contents' => $this->_extractBundleContents($feedXPath, $itemIndex, $catalogId),
					// get varien object of Drop Ship Supplier Information attribute node
					'drop_ship_supplier_information' => $this->_extractDropShipSupplierInformation($feedXPath, $itemIndex, $catalogId),
					// get varien object of Extended Attributes node
					'extended_attributes' => $this->_extractExtendedAttributes($feedXPath, $itemIndex, $catalogId),
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($feedXPath, $itemIndex, $catalogId),
				)
			);

			// increment item index
			$itemIndex++;
		}

		return $collectionOfItems;
	}
}
