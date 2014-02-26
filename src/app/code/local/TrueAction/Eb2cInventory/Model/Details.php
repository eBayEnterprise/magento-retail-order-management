<?php
class TrueAction_Eb2cInventory_Model_Details
	extends TrueAction_Eb2cInventory_Model_Request_Abstract
{

	// Key used by the eb2cinventory/data helper to identify the URI for this request
	const OPERATION_KEY = 'get_inventory_details';
	// Config key used to identify the xsd file used to validate the request message
	const XSD_FILE_CONFIG = 'xsd_file_details';

	/*****************************************************************************
	 * Request methods                                                           *
	 ****************************************************************************/

	/**
	 * Determine if a valid request could be sent for the given quote. In this case, must
	 * have items (parent check), a shipping address, and shipping method.
	 * @param  Mage_Sales_Model_Quote $quote The quote the request would be for
	 * @return boolean True if possible to create valid request from the quote, false otherwise
	 */
	protected function _canMakeRequestWithQuote(Mage_Sales_Model_Quote $quote)
	{
		return parent::_canMakeRequestWithQuote($quote) &&
			$quote->getShippingAddress() && $quote->getShippingAddress()->getShippingMethod();
	}
	/**
	 * Take a quote address and interpolate it into a ShipmentDetails xml node string.
	 * @param Mage_Sales_Model_Quote_Address $address the address object to get data from
	 * @return string
	 */
	protected function _buildShipmentDetailsXml(Mage_Sales_Model_Quote_Address $address)
	{
		// Address line data
		$lines = '';
		for ($i = 1; $i <= 4; $i++) {
			$st = $address->getStreet($i);
			if ($st) {
				$lines .= sprintf('<Line%d>%s</Line%d>', $i, $st, $i);
			}
		}
		$xmlStr = sprintf(
			'<ShipmentDetails><ShippingMethod>%s</ShippingMethod><ShipToAddress>%s<City>%s</City><MainDivision>%s</MainDivision><CountryCode>%s</CountryCode><PostalCode>%s</PostalCode></ShipToAddress></ShipmentDetails>',
			Mage::helper('eb2ccore')->lookupShipMethod($address->getShippingMethod()),
			$lines,
			$address->getCity(),
			$address->getRegionCode(),
			$address->getCountryId(),
			$address->getPostcode()
		);
		return $xmlStr;
	}
	/**
	 * Take a single quote item and shipment details xml node string.
	 * Extract the data from the quote item and return an xml string.
	 * @param Mage_Sales_Model_Quote_Item $item the item to get data from
	 * @param string $shipDet the shipment details xml node string.
	 * @param int $idx Index of item in collection. Used in the place of item id as not all items will have one
	 * @return string
	 */
	protected function _buildOrderItemXml(Mage_Sales_Model_Quote_Item $item, $shipDet, $idx)
	{
		return sprintf(
			'<OrderItem lineId="item%s" itemId="%s"><Quantity>%d</Quantity>%s</OrderItem>',
			$idx,
			$item->getSku(),
			$item->getQty(),
			$shipDet
		);
	}
	/**
	 * Take an array of quote items and a quote address.
	 * Concatenate the results of applying buildOrderItemXml to each.
	 * @param array $items array of Mage_Sales_Model_Quote_Item
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param int $idx Index/counter for items added Used instead of id as items added to a new item collection will not have one
	 * @return string
	 */
	protected function _buildOrderItemsXml(array $items, $address, $idx=0)
	{
		if (empty($items)) {
			return '';
		}
		return $this->_buildOrderItemXml(array_shift($items), $this->_buildShipmentDetailsXml($address), $idx) . $this->_buildOrderItemsXml($items, $address, ++$idx);
	}
	/**
	 * Build Inventory Details request.
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	protected function _buildRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		$helper = Mage::helper('eb2cinventory');
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(sprintf(
			'<InventoryDetailsRequestMessage xmlns="%s">%s</InventoryDetailsRequestMessage>',
			$helper->getXmlNs(),
			$this->_buildOrderItemsXml($helper->getInventoriedItems($quote->getAllVisibleItems()), $quote->getShippingAddress())
		));
		return $doc;
	}

	/*****************************************************************************
	 * Response methods                                                          *
	 ****************************************************************************/

	/**
	 * Parse inventory details response xml.
	 * @param DOMXPath $responseMessage DOMXPath that can be used to search the response message
	 * @return array, an associative array of response data
	 */
	public function extractItemDetails(DOMXpath $responseXPath)
	{
		$inventoryData = array();
		$coreHelper = Mage::helper('eb2ccore');
		foreach ($responseXPath->query('//a:InventoryDetail') as $detail) {
			$delEst = $responseXPath->query('a:DeliveryEstimate[1]', $detail)->item(0);
			$shipFromAdd = $responseXPath->query('a:ShipFromAddress[1]', $detail)->item(0);
			$inventoryData[$detail->getAttribute('itemId')] = array_merge(
				array(
					'lineId' => $detail->getAttribute('lineId'),
					'itemId' => $detail->getAttribute('itemId'),
				),
				array_map(
					array($coreHelper, 'extractNodeVal'),
					array(
						'creationTime' => $responseXPath->query('a:CreationTime', $delEst),
						'display' => $responseXPath->query('a:Display', $delEst),
						'deliveryWindow_from' => $responseXPath->query('a:DeliveryWindow/a:From', $delEst),
						'deliveryWindow_to' => $responseXPath->query('a:DeliveryWindow/a:To', $delEst),
						'shippingWindow_from' => $responseXPath->query('a:ShippingWindow/a:From', $delEst),
						'shippingWindow_to' => $responseXPath->query('a:ShippingWindow/a:To', $delEst),
						'shipFromAddress_line1' => $responseXPath->query('a:Line1', $shipFromAdd),
						'shipFromAddress_city' => $responseXPath->query('a:City', $shipFromAdd),
						'shipFromAddress_mainDivision' => $responseXPath->query('a:MainDivision', $shipFromAdd),
						'shipFromAddress_countryCode' => $responseXPath->query('a:CountryCode', $shipFromAdd),
						'shipFromAddress_postalCode' => $responseXPath->query('a:PostalCode', $shipFromAdd),
					)
				)
			);
		}
		return $inventoryData;
	}
	/**
	 * Extract unavailable items from an inventory details response message.
	 * @param DOMXPath $responseMessage DOMXPath that can be used to search the response message
	 * @return array Map of sku => item details, item id and line id
	 */
	public function extractUnavailableItems(DOMXPath $responseXPath)
	{
		$items = array();
		foreach ($responseXPath->query('//a:UnavailableItem') as $item) {
			$itemId = $item->getAttribute('itemId');
			$items[$itemId] = array(
				'lineId' => $item->getAttribute('lineId'),
			);
		}
		return $items;
	}
	/**
	 * Update quote with inventory details response data.
	 * @param Mage_Sales_Model_Quote $quote           the quote we use to get inventory details from eb2c
	 * @param string                 $responseMessage xml text from the response
	 * @return void
	 */
	public function updateQuoteWithResponse(Mage_Sales_Model_Quote $quote, $responseMessage)
	{
		if ($responseMessage) {
			$helper = Mage::helper('eb2cinventory/quote');
			$responseXPath = $helper->getXPathForMessage($responseMessage);
			$itemDetails = $this->extractItemDetails($responseXPath);
			$itemsToDelete = $this->extractUnavailableItems($responseXPath);
			foreach ($quote->getAllItems() as $item) {
				if (isset($itemsToDelete[$item->getSku()])) {
					$helper->removeItemFromQuote($quote, $item);
				} elseif (isset($itemDetails[$item->getSku()])) {
					$this->_updateQuoteItemWithDetails($item, $itemDetails[$item->getSku()]);
				}
			}
			Mage::dispatchEvent('eb2cinventory_details_process_after', array('quote' => $quote));
		}
		return $this;
	}
	/**
	 * Update quote with inventory details response data.
	 * @param Mage_Sales_Model_Quote_Item $quoteItem the item to be updated with eb2c data
	 * @param array $inventoryData the data from eb2c for the quote-item
	 * @return void
	 */
	protected function _updateQuoteItemWithDetails(Mage_Sales_Model_Quote_Item $quoteItem, array $inventoryData)
	{
		// Add inventory details info to quote item
		$quoteItem->addData(array(
			'eb2c_creation_time' => $inventoryData['creationTime'],
			'eb2c_display' => $inventoryData['display'],
			'eb2c_delivery_window_from' => $inventoryData['deliveryWindow_from'],
			'eb2c_delivery_window_to' => $inventoryData['deliveryWindow_to'],
			'eb2c_shipping_window_from' => $inventoryData['shippingWindow_from'],
			'eb2c_shipping_window_to' => $inventoryData['shippingWindow_to'],
			'eb2c_ship_from_address_line1' => $inventoryData['shipFromAddress_line1'],
			'eb2c_ship_from_address_city' => $inventoryData['shipFromAddress_city'],
			'eb2c_ship_from_address_main_division' => $inventoryData['shipFromAddress_mainDivision'],
			'eb2c_ship_from_address_country_code' => $inventoryData['shipFromAddress_countryCode'],
			'eb2c_ship_from_address_postal_code' => $inventoryData['shipFromAddress_postalCode']
		));
		return $this;
	}
}
