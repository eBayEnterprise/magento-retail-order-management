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
<<<<<<< HEAD
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
=======
	 * @param DOMXPath $feedXPath, the xpath object
	 *
	 * @return Varien_Object
	 */
	protected function _extractItemId($feedXPath, $itemIndex, $gsiClientId)
	{
		// SKU used to identify this item from the client system.
		$clientItemId = $feedXPath->query("//Inventory[$itemIndex][@gsi_client_id='$gsiClientId']/ItemId/ClientItemId");
		return new Varien_Object(array('client_item_id' => $clientItemId->length ? (string) $clientItemId->item(0)->nodeValue : null));
>>>>>>> master
	}

	/**
	 * extract Measurements data into a varien object
	 *
<<<<<<< HEAD
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
=======
	 * @param DOMXPath $feedXPath, the xpath object
	 *
	 * @return Varien_Object
	 */
	protected function _extractMeasurements($feedXPath, $itemIndex, $gsiClientId)
	{
		$qty = $feedXPath->query("//Inventory[$itemIndex][@gsi_client_id='$gsiClientId']/Measurements/AvailableQuantity");
		return new Varien_Object(array('available_quantity' => $qty->length ? (int) $qty->item(0)->nodeValue : 0));
>>>>>>> master
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
<<<<<<< HEAD
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

=======
		$feedXPath = new DOMXPath($doc);

		$inventory = $feedXPath->query('//Inventory');
		$itemIndex = 1; // start index
		foreach ($inventory as $item) {
			$gsiClientId = $item->getAttribute('gsi_client_id');
			// setting item object into the colelction of item objects.
			$collectionOfItems[] = new Varien_Object(
				array(
					'gsi_client_id' => $gsiClientId, // setting gsi_client_id id
					'item_id' => $this->_extractItemId($feedXPath, $itemIndex, $gsiClientId), // get varien object of item id node
					'measurements' => $this->_extractMeasurements($feedXPath, $itemIndex, $gsiClientId), // get varien object of Measurements node
				)
			);
>>>>>>> master
			// increment item index
			$itemIndex++;
		}

		return $collectionOfItems;
	}
}
