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
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractItemId($feedXpath, $itemIndex, $catalogId)
	{
		$itemIdObject = new Varien_Object();
		$itemIdObject->setClientItemId(null);

		// SKU used to identify this item from the client system.
		$clientItemId = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/ItemId/ClientItemId'
		);

		if ($clientItemId->length) {
			$itemIdObject->setClientItemId(trim($clientItemId->item(0)->nodeValue));
		}

		return $itemIdObject;
	}

	/**
	 * extract BaseAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractBaseAttributes($feedXpath, $itemIndex, $catalogId)
	{
		$baseAttributesObject = new Varien_Object();

		// Allows for control of the web store display.
		$baseAttributesObject->setCatalogClass(null);
		$catalogClass = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/BaseAttributes/CatalogClass'
		);
		if ($catalogClass->length) {
			$baseAttributesObject->setCatalogClass(trim($catalogClass->item(0)->nodeValue));
		}

		// Indicates the item if fulfilled by a drop shipper.
		// New attribute.
		$baseAttributesObject->setDropShipped(null);
		$isDropShipped = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/BaseAttributes/IsDropShipped'
		);
		if ($isDropShipped->length) {
			$baseAttributesObject->setDropShipped((bool) $isDropShipped->item(0)->nodeValue);
		}

		// Short description in the catalog's base language.
		$baseAttributesObject->setItemDescription(null);
		$itemDescription = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/BaseAttributes/ItemDescription'
		);
		if ($itemDescription->length) {
			$baseAttributesObject->setItemDescription(trim($itemDescription->item(0)->nodeValue));
		}

		// Identifies the type of item.
		$baseAttributesObject->setItemType(null);
		$itemType = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/BaseAttributes/ItemType'
		);

		if ($itemType->length) {
			$baseAttributesObject->setItemType(strtolower(trim($itemType->item(0)->nodeValue)));
		}

		// Indicates whether an item is active, inactive or other various states.
		$baseAttributesObject->setItemStatus(0);
		$itemStatus = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/BaseAttributes/ItemStatus'
		);
		if ($itemStatus->length) {
			$baseAttributesObject->setItemStatus( (strtoupper(trim($itemStatus->item(0)->nodeValue)) === 'ACTIVE')? 1 : 0 );
		}

		// Tax group the item belongs to.
		$baseAttributesObject->setTaxCode(null);
		$taxCode = $feedXpath->query('//Item[' .
			$itemIndex . '][@catalog_id="' .
			$catalogId .
			'"]/BaseAttributes/TaxCode'
		);
		if ($taxCode->length) {
			$baseAttributesObject->setTaxCode(trim($taxCode->item(0)->nodeValue));
		}

		return $baseAttributesObject;
	}

	/**
	 * extract BundleContents data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractBundleContents($feedXpath, $itemIndex, $catalogId)
	{
		$bundleContentsObject = null;

		// Items included if this item is a bundle product.
		$bundleContents = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/BundleContents'
		);
		if ($bundleContents->length) {
			// Since we have bundle product let save these to a Varien_Object
			$bundleContentsObject = new Varien_Object();

			// Child item of this item
			$bundleItems = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/BundleContents/BundleItems'
			);
			if ($bundleItems->length) {
				$bundleItemCollection = array();
				$bundleItemIndex = 1;
				foreach ($bundleItems as $bundleItem) {
					$bundleItemObject = new Varien_Object();
					// All items in the bundle must ship together.
					$bundleItemObject->setShipTogether((bool) $bundleItem->getAttribute('ship_together'));

					$bundleItemObject->setOperationType((string) $bundleItem->getAttribute('operation_type'));
					$bundleCatalogId = (string) $bundleItem->getAttribute('catalog_id');
					$bundleItemObject->setCatalogId($bundleCatalogId);

					// Client or vendor id (SKU) for the item to be included in the bundle.
					$bundleItemObject->setItemID(null);
					$itemID = $feedXpath->query('//Item[' .
						$itemIndex . '][@catalog_id="' .
						$catalogId .
						'"]/BundleContents/BundleItems[' .
						$bundleItemIndex .
						']/ItemID'
					);
					if ($itemID->length) {
						$bundleItemObject->setItemID(trim($itemID->item(0)->nodeValue));
					}

					// How many of the child item come in the bundle.
					$bundleItemObject->setQuantity(null);
					$quantity = $feedXpath->query('//Item[' .
						$itemIndex . '][@catalog_id="' .
						$catalogId . '"]/BundleContents/BundleItems[' .
						$bundleItemIndex . ']/Quantity'
					);
					if ($quantity->length) {
						$bundleItemObject->setQuantity((int) $quantity->item(0)->nodeValue);
					}
					$bundleItemCollection[] = $bundleItemObject;
					$bundleItemIndex++;
				}

				$bundleContentsObject->setBundleItems($bundleItemCollection);
			}
		}

		return $bundleContentsObject;
	}

	/**
	 * extract DropShipSupplierInformation data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractDropShipSupplierInformation($feedXpath, $itemIndex, $catalogId)
	{
		$dropShipSupplierInformationObject = new Varien_Object();

		// let save drop Ship Supplier Information to a Varien_Object
		$dropShipSupplierInformationObject = new Varien_Object();
		$dropShipSupplierInformationObject->setSupplierName(null);
		$dropShipSupplierInformationObject->setSupplierNumber(null);
		$dropShipSupplierInformationObject->setSupplierPartNumber(null);

		// Encapsulates data for drop shipper fulfillment. If the item is fulfilled by a drop shipper, these values are required.
		$dropShipSupplierInformation = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/DropShipSupplierInformation'
		);
		if ($dropShipSupplierInformation->length) {
			// Name of the Drop Ship Supplier fulfilling the item
			$supplierName = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/DropShipSupplierInformation/SupplierName'
			);
			if ($supplierName->length) {
				$dropShipSupplierInformationObject->setSupplierName(trim($supplierName->item(0)->nodeValue));
			}

			// Unique code assigned to this supplier.
			$supplierNumber = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/DropShipSupplierInformation/SupplierNumber'
			);
			if ($supplierNumber->length) {
				$dropShipSupplierInformationObject->setSupplierNumber(trim($supplierNumber->item(0)->nodeValue));
			}

			// Id or SKU used by the drop shipper to identify this item.
			$supplierPartNumber = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/DropShipSupplierInformation/SupplierPartNumber'
			);
			if ($supplierPartNumber->length) {
				$dropShipSupplierInformationObject->setSupplierPartNumber(trim($supplierPartNumber->item(0)->nodeValue));
			}
		}

		return $dropShipSupplierInformationObject;
	}

	/**
	 * extract ExtendedAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractExtendedAttributes($feedXpath, $itemIndex, $catalogId)
	{
		$extendedAttributesObject = new Varien_Object();

		// let save drop Extended Attributes to a Varien_Object
		$extendedAttributesObject = new Varien_Object();
		$extendedAttributesObject->setAllowGiftMessage(false);
		$extendedAttributesObject->setBackOrderable(null);

		// let save drop color Attributes to a Varien_Object
		$colorAttributesObject = new Varien_Object();
		$colorAttributesObject->setColorCode(null);
		$colorAttributesObject->setColorDescription(null);

		$extendedAttributesObject->setColorAttributes($colorAttributesObject);
		$extendedAttributesObject->setCountryOfOrigin(null);
		$extendedAttributesObject->setGiftCartTenderCode(null);

		// let save ItemDimensions/Shipping to a Varien_Object
		$itemDimensionsShippingObject = new Varien_Object();
		$itemDimensionsShippingObject->setMassUnitOfMeasure(null);
		$itemDimensionsShippingObject->setWeight(0);

		$extendedAttributesObject->setItemDimensionsShipping($itemDimensionsShippingObject);
		$extendedAttributesObject->setMsrp(null);
		$extendedAttributesObject->setPrice(0);

		// let save ItemDimensions/Shipping to a Varien_Object
		$sizeAttributesObject = new Varien_Object();
		$sizeAttributesObject->setSize(array(array('lang' => null, 'code' => null, 'description' => null)));
		$extendedAttributesObject->setSizeAttributes($sizeAttributesObject);

		// Additional named attributes. None are required.
		$extendedAttributes = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/ExtendedAttributes'
		);
		if ($extendedAttributes->length) {
			// If false, customer cannot add a gift message to the item.
			$allowGiftMessage = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/AllowGiftMessage'
			);
			if ($allowGiftMessage->length) {
				$extendedAttributesObject->setAllowGiftMessage((bool) $allowGiftMessage->item(0)->nodeValue);
			}

			// Item is able to be back ordered.
			$backOrderable = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/BackOrderable'
			);
			if ($backOrderable->length) {
				$extendedAttributesObject->setBackOrderable(trim($backOrderable->item(0)->nodeValue));
			}

			// Item color
			$colorAttributes = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/ColorAttributes'
			);
			if ($colorAttributes->length) {
				// Color value/name with a locale specific description.
				// Name of the color used as the default and in the admin.
				$colorCode = $feedXpath->query('//Item[' .
					$itemIndex .
					'][@catalog_id="' .
					$catalogId .
					'"]/ExtendedAttributes/ColorAttributes/Color/Code'
				);
				if ($colorCode->length) {
					$colorAttributesObject->setColorCode((string) $colorCode->item(0)->nodeValue);
				}

				// Description of the color used for specific store views/languages.
				$colorDescription = $feedXpath->query('//Item[' .
					$itemIndex .
					'][@catalog_id="' .
					$catalogId .
					'"]/ExtendedAttributes/ColorAttributes/Color/Description'
				);
				if ($colorDescription->length) {
					$colorAttributesObject->setColorDescription(trim($colorDescription->item(0)->nodeValue));
				}
			}
			$extendedAttributesObject->setColorAttributes($colorAttributesObject);

			// Country in which goods were completely derived or manufactured.
			$countryOfOrigin = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/CountryOfOrigin'
			);
			if ($countryOfOrigin->length) {
				$extendedAttributesObject->setCountryOfOrigin(trim($countryOfOrigin->item(0)->nodeValue));
			}

			/*
			 *  Type of gift card to be used for activation.
			 * 		SD - TRU Digital Gift Card
			 *		SP - SVS Physical Gift Card
			 *		ST - SmartClixx Gift Card Canada
			 *		SV - SVS Virtual Gift Card
			 *		SX - SmartClixx Gift Card
			 */
			$giftCartTenderCode = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/GiftCartTenderCode'
			);
			if ($giftCartTenderCode->length) {
				$extendedAttributesObject->setGiftCartTenderCode(trim($giftCartTenderCode->item(0)->nodeValue));
			}

			// Dimensions used for shipping the item.
			$itemDimensionsShipping = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/ItemDimensions/Shipping'
			);
			if ($itemDimensionsShipping->length) {
				// Shipping weight of the item.
				$mass = $feedXpath->query('//Item[' .
					$itemIndex .
					'][@catalog_id="' .
					$catalogId .
					'"]/ExtendedAttributes/ItemDimensions/Shipping/Mass'
				);
				if ($mass->length) {
					$itemDimensionsShippingObject->setMassUnitOfMeasure((string) $mass->item(0)->getAttribute('unit_of_measure'));

					// Shipping weight of the item.
					$weight = $feedXpath->query('//Item[' .
						$itemIndex .
						'][@catalog_id="' .
						$catalogId .
						'"]/ExtendedAttributes/ItemDimensions/Shipping/Mass/Weight'
					);
					if ($weight->length) {
						$itemDimensionsShippingObject->setWeight((float) $weight->item(0)->nodeValue);
					}
				}
			}
			$extendedAttributesObject->setItemDimensionsShipping($itemDimensionsShippingObject);

			// Manufacturers suggested retail price. Not used for actual price calculations.
			$msrp = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/MSRP'
			);
			if ($msrp->length) {
				$extendedAttributesObject->setMsrp((string) $msrp->item(0)->nodeValue);
			}

			// Default price item is sold at. Required only if the item is new.
			$price = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/Price'
			);
			if ($price->length) {
				$extendedAttributesObject->setPrice((float) $price->item(0)->nodeValue);
			}

			// Dimensions used for shipping the item.
			$sizeAttributes = $feedXpath->query('//Item[' .
				$itemIndex .
				'][@catalog_id="' .
				$catalogId .
				'"]/ExtendedAttributes/SizeAttributes/Size'
			);
			if ($sizeAttributes->length) {
				$sizeData = array();
				foreach ($sizeAttributes as $sizeRecord) {
					// Language code for the natural language of the size data.
					$sizeLang = $sizeRecord->getAttribute('xml:lang');

					// Size code.
					$sizeCode = '';
					$sizeCodeElement = $feedXpath->query('//Item[' .
						$itemIndex .
						'][@catalog_id="' .
						$catalogId .
						'"]/ExtendedAttributes/SizeAttributes/Size/Code'
					);
					if ($sizeCodeElement->length) {
						$sizeCode = (string) $sizeCodeElement->item(0)->nodeValue;
					}

					// Size Description.
					$sizeDescription = '';
					$sizeDescriptionElement = $feedXpath->query('//Item[' .
						$itemIndex .
						'][@catalog_id="' .
						$catalogId .
						'"]/ExtendedAttributes/SizeAttributes/Size/Description'
					);
					if ($sizeDescriptionElement->length) {
						$sizeDescription = (string) $sizeDescriptionElement->item(0)->nodeValue;
					}

					$sizeData[] = array(
						'lang' => $sizeLang,
						'code' => $sizeCode,
						'description' => $sizeDescription,
					);
				}

				$sizeAttributesObject->setSize($sizeData);
			}
			$extendedAttributesObject->setSizeAttributes($sizeAttributesObject);
		}

		return $extendedAttributesObject;
	}

	/**
	 * extract CustomAttributes data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractCustomAttributes($feedXpath, $itemIndex, $catalogId)
	{
		$customAttributesObject = new Varien_Object();

		// let save CustomAttributes/Attribute to a Varien_Object
		$customAttributesObject = new Varien_Object();
		$customAttributesObject->setAttributes(array(array('name' => null, 'operationType' => null, 'lang' => null, 'value' => null)));

		// Name value paris of additional attributes for the product.
		$customAttributes = $feedXpath->query('//Item[' .
			$itemIndex .
			'][@catalog_id="' .
			$catalogId .
			'"]/CustomAttributes/Attribute'
		);
		if ($customAttributes->length) {
			$attributeData = array();
			foreach ($customAttributes as $attributeRecord) {
				// The name of the attribute.
				$attributeName = $attributeRecord->getAttribute('name');

				// Type of operation to take with this attribute. enum: ("Add", "Change", "Delete")
				$attributeOperationType = $attributeRecord->getAttribute('operation_type');

				// Language code for the natural language or the <Value /> element.
				$attributeLang = $attributeRecord->getAttribute('xml:lang');

				// Value of the attribute.
				$attributeValue = '';
				$attributeValueElement = $feedXpath->query('//Item[' .
					$itemIndex .
					'][@catalog_id="' .
					$catalogId .
					'"]/CustomAttributes/Attribute/Value'
				);
				if ($attributeValueElement->length) {
					$attributeValue = (string) $attributeValueElement->item(0)->nodeValue;
				}

				$attributeData[] = array(
					'name' => $attributeName,
					'operationType' => $attributeOperationType,
					'lang' => $attributeLang,
					'value' => $attributeValue,
				);
			}

			$customAttributesObject->setAttributes($attributeData);
		}

		return $customAttributesObject;
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extractItemItemMasterFeed($doc)
	{
		$collectionOfItems = array();
		$feedXpath = new DOMXPath($doc);

		$master = $feedXpath->query('//Item');
		$itemIndex = 1; // start index
		foreach ($master as $item) {
			// item object
			$itemObject = new Varien_Object();

			// setting catalog id
			$itemObject->setCatalogId((string) $item->getAttribute('catalog_id'));

			// setting gsi_client_id id
			$itemObject->setGsiClientId((string) $item->getAttribute('gsi_client_id'));

			// Defines the action requested for this item. enum:("Add", "Change", "Delete")
			$itemObject->setOperationType((string) $item->getAttribute('operation_type'));

			// get varien object of item id node
			$itemObject->setItemId($this->_extractItemId($feedXpath, $itemIndex, $itemObject->getCatalogId()));

			// get varien object of base attributes node
			$itemObject->setBaseAttributes($this->_extractBaseAttributes($feedXpath, $itemIndex, $itemObject->getCatalogId()));

			// get varien object of bundle attributes node
			$itemObject->setBundleContents($this->_extractBundleContents($feedXpath, $itemIndex, $itemObject->getCatalogId()));

			// get varien object of Drop Ship Supplier Information attribute node
			$itemObject->setDropShipSupplierInformation($this->_extractDropShipSupplierInformation($feedXpath, $itemIndex, $itemObject->getCatalogId()));

			// get varien object of Extended Attributes node
			$itemObject->setExtendedAttributes($this->_extractExtendedAttributes($feedXpath, $itemIndex, $itemObject->getCatalogId()));

			// get varien object of Custom Attributes node
			$itemObject->setCustomAttributes($this->_extractCustomAttributes($feedXpath, $itemIndex, $itemObject->getCatalogId()));

			// setting item object into the colelction of item objects.
			$collectionOfItems[] = $itemObject;

			// increment item index
			$itemIndex++;
		}

		return $collectionOfItems;
	}
}
