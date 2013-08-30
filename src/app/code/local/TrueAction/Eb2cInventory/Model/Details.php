<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Details extends Mage_Core_Model_Abstract
{
	/**
	 * Initialize resource model
	 */
	protected function _construct()
	{
		$this->setHelper(Mage::helper('eb2cinventory'));
	}

	/**
	 * Get the inventory details for all items in this quote from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to get eb2c inventory details on
	 *
	 * @return string the eb2c response to the request.
	 */
	public function getInventoryDetails($quote)
	{
		$inventoryDetailsResponseMessage = '';
		try{
			// build request
			$inventoryDetailsRequestMessage = $this->buildInventoryDetailsRequestMessage($quote);

			// make request to eb2c for inventory details
			$inventoryDetailsResponseMessage = $this->getHelper()->getApiModel()
				->setUri($this->getHelper()->getOperationUri('get_inventory_details'))
				->request($inventoryDetailsRequestMessage);

		}catch(Exception $e){
			Mage::logException($e);
		}

		return $inventoryDetailsResponseMessage;
	}

	/**
	 * Build Inventory Details request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildInventoryDetailsRequestMessage($quote)
	{
		$domDocument = $this->getHelper()->getDomDocument();
		$inventoryDetailsRequestMessage = $domDocument->addElement('InventoryDetailsRequestMessage', null, $this->getHelper()->getXmlNs())->firstChild;
		if ($quote) {
			foreach($quote->getAllItems() as $item){
				try{
					// creating orderItem element
					$orderItem = $inventoryDetailsRequestMessage->createChild(
						'OrderItem',
						null,
						array('lineId' => $item->getId(), 'itemId' => $item->getSku())
					);

					// add quantity
					$orderItem->createChild(
						'Quantity',
						(string) $item->getQty() // integer value doesn't get added only string
					);

					$shippingAddress = $quote->getShippingAddress();
					// creating shipping details
					$shipmentDetails = $orderItem->createChild(
						'ShipmentDetails',
						null
					);

					// add shipment method
					$shipmentDetails->createChild(
						'ShippingMethod',
						$shippingAddress->getShippingMethod()
					);

					// add ship to address
					$shipToAddress = $shipmentDetails->createChild(
						'ShipToAddress',
						null
					);

					// add ship to address Line1
					$street = $shippingAddress->getStreet();
					$lineAddress = '';
					if(sizeof($street) > 0){
						$lineAddress = $street[0];
					}
					$shipToAddress->createChild(
						'Line1',
						$lineAddress
					);

					// add ship to address City
					$shipToAddress->createChild(
						'City',
						$shippingAddress->getCity()
					);

					// add ship to address MainDivision
					$shipToAddress->createChild(
						'MainDivision',
						$shippingAddress->getRegion()
					);

					// add ship to address CountryCode
					$shipToAddress->createChild(
						'CountryCode',
						$shippingAddress->getCountryId()
					);

					// add ship to address PostalCode
					$shipToAddress->createChild(
						'PostalCode',
						$shippingAddress->getPostcode()
					);
				}catch(Exception $e){
					Mage::logException($e);
				}
			}
		}
		return $domDocument;
	}

	/**
	 * Parse inventory details response xml.
	 *
	 * @param string $inventoryDetailsResponseMessage the xml response from eb2c
	 *
	 * @return array, an associative array of response data
	 */
	public function parseResponse($inventoryDetailsResponseMessage)
	{
		$inventoryData = array();
		if (trim($inventoryDetailsResponseMessage) !== '') {
			$doc = $this->getHelper()->getDomDocument();

			// load response string xml from eb2c
			$doc->loadXML($inventoryDetailsResponseMessage);
			$i = 0;
			$inventoryDetails = $doc->getElementsByTagName('InventoryDetails');
			foreach($inventoryDetails as $response) {
				foreach($response->childNodes as $inventoryDetail) {
					$detail = array();
					if ($inventoryDetail->nodeName === 'InventoryDetail') {
						$detail['lineId'] = $inventoryDetail->getAttribute('lineId');
						$detail['itemId'] = $inventoryDetail->getAttribute('itemId');

						$deliveryEstimate = $inventoryDetail->getElementsByTagName('DeliveryEstimate');

						if ($deliveryEstimate->length > 0) {
							$detail['creationTime'] = $deliveryEstimate->item(0)->getElementsByTagName('CreationTime')->item(0)->nodeValue;
							$detail['display'] = $deliveryEstimate->item(0)->getElementsByTagName('Display')->item(0)->nodeValue;

							$deliveryWindow = $deliveryEstimate->item(0)->getElementsByTagName('DeliveryWindow');
							$detail['deliveryWindow_from'] = $deliveryWindow->item(0)->getElementsByTagName('From')->item(0)->nodeValue;
							$detail['deliveryWindow_to'] = $deliveryWindow->item(0)->getElementsByTagName('To')->item(0)->nodeValue;

							$shippingWindow = $deliveryEstimate->item(0)->getElementsByTagName('ShippingWindow');
							$detail['shippingWindow_from'] = $shippingWindow->item(0)->getElementsByTagName('From')->item(0)->nodeValue;
							$detail['shippingWindow_to'] = $shippingWindow->item(0)->getElementsByTagName('To')->item(0)->nodeValue;
						}

						$shipFromAddress = $inventoryDetail->getElementsByTagName('ShipFromAddress');

						if ($shipFromAddress->length > 0) {
							$detail['shipFromAddress_line1'] = $shipFromAddress->item(0)->getElementsByTagName('Line1')->item(0)->nodeValue;
							$detail['shipFromAddress_city'] = $shipFromAddress->item(0)->getElementsByTagName('City')->item(0)->nodeValue;
							$detail['shipFromAddress_mainDivision'] = $shipFromAddress->item(0)->getElementsByTagName('MainDivision')->item(0)->nodeValue;
							$detail['shipFromAddress_countryCode'] = $shipFromAddress->item(0)->getElementsByTagName('CountryCode')->item(0)->nodeValue;
							$detail['shipFromAddress_postalCode'] = $shipFromAddress->item(0)->getElementsByTagName('PostalCode')->item(0)->nodeValue;
						}
					}

					if (!empty($detail)) {
						$inventoryData[] = $detail;
					}
				}
			}
		}

		return $inventoryData;
	}

	/**
	 * update quote with inventory details response data.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote we use to get inventory details from eb2c
	 * @param array $inventoryData, a parse associative array of eb2c response
	 *
	 * @return void
	 */
	public function processInventoryDetails($quote, $inventoryData)
	{

		foreach ($inventoryData as $data) {
			foreach ($quote->getAllItems() as $item) {
				// find the item in the quote
				if ((int) $item->getItemId() === (int) $data['lineId']) {
					// update quote with eb2c data.
					$this->_updateQuoteWithEb2cInventoryDetails($item, $data);
				}
			}
		}
	}

	/**
	 * update quote with inventory details response data.
	 *
	 * @param Mage_Sales_Model_Quote_Item $quoteItem the item to be updated with eb2c data
	 * @param array $inventoryData the data from eb2c for the quote-item
	 *
	 * @return void
	 */
	protected function _updateQuoteWithEb2cInventoryDetails($quoteItem, $inventoryData)
	{
		// get quote from quote item
		$quote = $quoteItem->getQuote();

		// Add inventory details info to quote item
		$quoteItem->setEb2cCreationTime($inventoryData['creationTime'])
			->setEb2cDisplay($inventoryData['display'])
			->setEb2cDeliveryWindowFrom($inventoryData['deliveryWindow_from'])
			->setEb2cDeliveryWindowTo($inventoryData['deliveryWindow_to'])
			->setEb2cShippingWindowFrom($inventoryData['shippingWindow_from'])
			->setEb2cShippingWindowTo($inventoryData['shippingWindow_to'])
			->setEb2cShipFromAddressLine1($inventoryData['shipFromAddress_line1'])
			->setEb2cShipFromAddressCity($inventoryData['shipFromAddress_city'])
			->setEb2cShipFromAddressMainDivision($inventoryData['shipFromAddress_mainDivision'])
			->setEb2cShipFromAddressCountryCode($inventoryData['shipFromAddress_countryCode'])
			->setEb2cShipFromAddressPostalCode($inventoryData['shipFromAddress_postalCode']);
		// Save the quote
		$quote->save();
	}
}
