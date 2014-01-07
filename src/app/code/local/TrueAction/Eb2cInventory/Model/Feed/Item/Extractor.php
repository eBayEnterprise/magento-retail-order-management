<?php
class TrueAction_Eb2cInventory_Model_Feed_Item_Extractor
{
	/**
	 * extract item id data into a varien object
	 * @param DOMXPath $feedXPath, the xpath object
	 * @return Varien_Object
	 */
	protected function _extractItemId($feedXPath, $itemIndex, $gsiClientId)
	{
		// SKU used to identify this item from the client system.
		$clientItemId = $feedXPath->query("//Inventory[$itemIndex][@gsi_client_id='$gsiClientId']/ItemId/ClientItemId");
		return new Varien_Object(array('client_item_id' => $clientItemId->length ? (string) $clientItemId->item(0)->nodeValue : null));
	}

	/**
	 * extract Measurements data into a varien object
	 * @param DOMXPath $feedXPath, the xpath object
	 * @return Varien_Object
	 */
	protected function _extractMeasurements($feedXPath, $itemIndex, $gsiClientId)
	{
		$qty = $feedXPath->query("//Inventory[$itemIndex][@gsi_client_id='$gsiClientId']/Measurements/AvailableQuantity");
		return new Varien_Object(array('available_quantity' => $qty->length ? (int) $qty->item(0)->nodeValue : 0));
	}

	/**
	 * extract feed data into a collection of varien objects
	 * @param DOMDocument $doc, the dom document with the loaded feed data
	 * @return array, an collection of varien objects
	 */
	public function extractInventoryFeed($doc)
	{
		$collectionOfItems = array();
		$feedXPath = new DOMXPath($doc);

		$inventory = $feedXPath->query('//Inventory');
		$itemIndex = 1; // start index
		foreach ($inventory as $item) {
			$gsiClientId = $item->getAttribute('gsi_client_id');
			$catalogId = $item->getAttribute('catalog_id');
			// setting item object into the collection of item objects.
			$collectionOfItems[] = new Varien_Object(array(
				'catalog_id'    => $catalogId,
				'gsi_client_id' => $gsiClientId, // setting gsi_client_id id
				'item_id'       => $this->_extractItemId($feedXPath, $itemIndex, $gsiClientId), // get varien object of item id node
				'measurements'  => $this->_extractMeasurements($feedXPath, $itemIndex, $gsiClientId), // get varien object of Measurements node
			));
			// increment item index
			$itemIndex++;
		}

		return $collectionOfItems;
	}
}
