<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_I_Extractor
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
		return new Varien_Object(array('client_item_id' => ($nodeClientItemId->length)? (string) $nodeClientItemId->item(0)->nodeValue : null));
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
				'item_status' => (strtoupper(trim($nodeItemStatus->item(0)->nodeValue)) === 'ACTIVE')? 1 : 0,
				'tax_code' => ($nodeTaxCode->length)? (string) $nodeTaxCode->item(0)->nodeValue : null,
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

		// Name value paris of additional attributes for the product.
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
	 * extract HTSCodes data into a varien object
	 *
	 * @param DOMXPath $xpath, the xpath object
	 * @param DOMElement $item, the current element
	 *
	 * @return string, json encoded content string
	 */
	protected function _extractHtsCodes(DOMXPath $xpath, DOMElement $item)
	{
		$htsCodesData = array();

		// Name value paris of additional htsCodess for the product.
		$nodeHtsCode = $xpath->query("HTSCodes/HTSCode", $item);
		foreach ($nodeHtsCode as $htsCodeRecord) {
			$htsCodesData[] = array(
				// The mfn_duty_rate attributes.
				'mfn_duty_rate' => (string) $htsCodeRecord->getAttribute('mfn_duty_rate'),
				// The destination_country attributes
				'destination_country' => (string) $htsCodeRecord->getAttribute('operation_type'),
				// The restricted attributes
				'restricted' => (bool) $htsCodeRecord->getAttribute('restricted'),
				// The HTSCode node value
				'HTSCode' => (string) $htsCodeRecord->nodeValue,
			);
		}

		return json_encode($htsCodesData);
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMXPath $xpath, the DOMXPath with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extract(DOMXPath $xpath)
	{
		$collectionOfItems = array();
		$baseNode = self::FEED_BASE_NODE;

		$master = $xpath->query("//$baseNode");
		$idx = 1; // start index
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
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($xpath, $item),
					// get varien object of HTSCode node
					'hts_codes' => $this->_extractHtsCodes($xpath, $item),
				)
			);

			// increment item index
			$idx++;
		}

		return $collectionOfItems;
	}
}
