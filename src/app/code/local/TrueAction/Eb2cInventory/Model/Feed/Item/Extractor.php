<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Feed_Item_Extractor extends Mage_Core_Model_Abstract
{
	/**
	 * extract item id data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractItemId($feedXpath, $itemIndex, $gsiClientId)
	{
		$itemIdObject = new Varien_Object();
		$itemIdObject->setClientItemId(null);

		// SKU used to identify this item from the client system.
		$clientItemId = $feedXpath->query('//Inventory[' .
			$itemIndex .
			'][@gsi_client_id="' .
			$gsiClientId .
			'"]/ItemId/ClientItemId'
		);

		if ($clientItemId->length) {
			$itemIdObject->setClientItemId((string) $clientItemId->item(0)->nodeValue);
		}

		return $itemIdObject;
	}

	/**
	 * extract Measurements data into a varien object
	 *
	 * @param DOMXPath $feedXpath, the xpath object
	 *
	 * @return Varien_Ojbect
	 */
	protected function _extractMeasurements($feedXpath, $itemIndex, $gsiClientId)
	{
		$measurementsObject = new Varien_Object();
		$measurementsObject->setAvailableQuantity(0);

		// available quantity.
		$availableQuantity = $feedXpath->query('//Inventory[' .
			$itemIndex .
			'][@gsi_client_id="' .
			$gsiClientId .
			'"]/Measurements/AvailableQuantity'
		);

		if ($availableQuantity->length) {
			$measurementsObject->setAvailableQuantity((int) $availableQuantity->item(0)->nodeValue);
		}

		return $measurementsObject;
	}

	/**
	 * extract feed data into a collection of varien objects
	 *
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 *
	 * @return array, an collection of varien objects
	 */
	public function extractInventoryFeed($doc)
	{
		$collectionOfItems = array();
		$feedXpath = new DOMXPath($doc);

		$inventory = $feedXpath->query('//Inventory');
		$itemIndex = 1; // start index
		foreach ($inventory as $item) {
			// item object
			$itemObject = new Varien_Object();

			// setting gsi_client_id id
			$itemObject->setGsiClientId((string) $item->getAttribute('gsi_client_id'));

			// get varien object of item id node
			$itemObject->setItemId($this->_extractItemId($feedXpath, $itemIndex, $itemObject->getGsiClientId()));

			// get varien object of Measurements node
			$itemObject->setMeasurements($this->_extractMeasurements($feedXpath, $itemIndex, $itemObject->getGsiClientId()));

			// setting item object into the colelction of item objects.
			$collectionOfItems[] = $itemObject;

			// increment item index
			$itemIndex++;
		}

		return $collectionOfItems;
	}
}
