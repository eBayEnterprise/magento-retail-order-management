<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Model_Feed_I_Extractor extends Mage_Core_Model_Abstract
{
	/**
	 * Initialize model
	 */
	protected function _construct()
	{
		$this->setData(
			array(
				'feed_base_node' => 'Item', // Magically setting feed base node
			)
		);
	}

	/**
	 * convert feed lang data to match magento expected format (en-US => en_US)
	 *
	 * @param string $langCode, the language code
	 *
	 * @return string, the magento expected format
	 */
	protected function _languageFormat($langCode)
	{
		return str_replace('-', '_', $langCode);
	}

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
		return new Varien_Object(array('client_item_id' => ($nodeClientItemId->length)? (string) $nodeClientItemId->item(0)->nodeValue : null));
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
				'item_status' => (strtoupper(trim($nodeItemStatus->item(0)->nodeValue)) === 'ACTIVE')? 1 : 0,
				'tax_code' => ($nodeTaxCode->length)? (string) $nodeTaxCode->item(0)->nodeValue : null,
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

		// Name value paris of additional attributes for the product.
		$nodeAttribute = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute");
		if ($nodeAttribute->length) {
			foreach ($nodeAttribute as $attributeRecord) {
				$nodeValue = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/CustomAttributes/Attribute/Value");

				$attributeData[] = array(
					// The name of the attribute.
					'name' => (string) $attributeRecord->getAttribute('name'),
					// Type of operation to take with this attribute. enum: ("Add", "Change", "Delete")
					'operationType' => (string) $attributeRecord->getAttribute('operation_type'),
					// Language code for the natural language or the <Value /> element.
					'lang' => $this->_languageFormat($attributeRecord->getAttribute('xml:lang')),
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
	 * extract HTSCodes data into a varien object
	 *
	 * @param DOMXPath $feedXPath, the xpath object
	 * @param int $idx, the current item position
	 * @param string $catalogId, the catalog id for the current xml node
	 * @param string $baseNode, the feed base node
	 *
	 * @return string, json encoded content string
	 */
	protected function _extractHtsCodes(DOMXPath $feedXPath, $idx, $catalogId, $baseNode='Item')
	{
		$htsCodesData = array();

		// Name value paris of additional htsCodess for the product.
		$nodeHtsCode = $feedXPath->query("//${baseNode}[$idx][@catalog_id='$catalogId']/HTSCodes/HTSCode");
		if ($nodeHtsCode->length) {
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
		}

		return json_encode($htsCodesData);
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extractIShipFeed(DOMDocument $doc)
	{
		$collectionOfItems = array();
		$feedXPath = new DOMXPath($doc);
		$baseNode = $this->getFeedBaseNode();

		$master = $feedXPath->query("//$baseNode");
		$idx = 1; // start index
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
					'item_id' => $this->_extractItemId($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of base attributes node
					'base_attributes' => $this->_extractBaseAttributes($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of Custom Attributes node
					'custom_attributes' => $this->_extractCustomAttributes($feedXPath, $idx, $catalogId, $baseNode),
					// get varien object of HTSCode node
					'hts_codes' => $this->_extractHtsCodes($feedXPath, $idx, $catalogId, $baseNode),
				)
			);

			// increment item index
			$idx++;
		}

		return $collectionOfItems;
	}
}
