<?php
class TrueAction_Eb2cInventory_Model_Details extends TrueAction_Eb2cInventory_Model_Abstract
{
	/**
	 * Get the inventory details for all items in this quote from eb2c.
	 * @param Mage_Sales_Model_Quote $quote the quote to get eb2c inventory details on
	 * @return string the eb2c response to the request.
	 */
	public function getInventoryDetails(Mage_Sales_Model_Quote $quote)
	{
		$responseMessage = '';
		// only make api request if we have valid manage stock quote item in the cart and there is valid shipping add info for this quote
		// Shipping address required for the details request, if there's no address,
		// can't make the details request.
		if ($quote && $quote->getShippingAddress() && $this->getInventoriedItems($quote->getAllItems())) {
			// build request
			$requestDoc = $this->_buildInventoryDetailsRequestMessage($quote);
			Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);
			try {
				// make request to eb2c for inventory details
				$responseMessage = Mage::getModel('eb2ccore/api')
					->setUri(Mage::helper('eb2cinventory')->getOperationUri('get_inventory_details'))
					->setXsd(Mage::helper('eb2cinventory')->getConfigModel()->xsdFileDetails)
					->request($requestDoc);
			} catch(Zend_Http_Client_Exception $e) {
				Mage::log(
					sprintf(
						'[ %s ] The following error has occurred while sending InventoryDetails request to eb2c: (%s).',
						__CLASS__, $e->getMessage()
					),
					Zend_Log::ERR
				);
			}
		}
		return $responseMessage;
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
		return sprintf(
			'<ShipmentDetails><ShippingMethod>%s</ShippingMethod><ShipToAddress>%s<City>%s</City><MainDivision>%s</MainDivision><CountryCode>%s</CountryCode><PostalCode>%s</PostalCode></ShipToAddress></ShipmentDetails>',
			Mage::helper('eb2ccore')->lookupShipMethod($address->getShippingMethod()),
			$lines,
			$address->getCity(),
			$address->getRegionCode(),
			$address->getCountryId(),
			$address->getPostcode()
		);
	}
	/**
	 * Take a single quote item and shipment details xml node string.
	 * Extract the data from the quote item and return an xml string.
	 * @param Mage_Sales_Model_Quote_Item $item the item to get data from
	 * @param string $shipDet the shipment details xml node string.
	 * @return string
	 */
	protected function _buildOrderItemXml(Mage_Sales_Model_Quote_Item $item, $shipDet)
	{
		return sprintf(
			'<OrderItem lineId="%s" itemId="%s"><Quantity>%d</Quantity>%s</OrderItem>',
			$item->getId(),
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
	 * @return string
	 */
	protected function _buildOrderItemsXml(array $items, $address)
	{
		if (empty($items)) {
			return '';
		}
		return $this->_buildOrderItemXml(array_shift($items), $this->_buildShipmentDetailsXml($address)) . $this->_buildOrderItemsXml($items, $address);
	}
	/**
	 * Build Inventory Details request.
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	protected function _buildInventoryDetailsRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(sprintf(
			'<InventoryDetailsRequestMessage xmlns="%s">%s</InventoryDetailsRequestMessage>',
			Mage::helper('eb2cinventory')->getXmlNs(),
			$this->_buildOrderItemsXml($this->getInventoriedItems($quote->getAllItems()), $quote->getShippingAddress())
		));
		return $doc;
	}
	/**
	 * Parse inventory details response xml.
	 * @param string $responseMessage the xml response from eb2c
	 * @return array, an associative array of response data
	 */
	public function parseResponse($responseMessage)
	{
		$inventoryData = array();
		if (trim($responseMessage) !== '') {
			$coreHlpr = Mage::helper('eb2ccore');
			$doc = $coreHlpr ->getNewDomDocument();
			// load response string xml from eb2c
			$doc->loadXML($responseMessage);
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('a', Mage::helper('eb2cinventory')->getXmlNs());
			$inventoryDetails = $xpath->query('//a:InventoryDetail');
			foreach ($inventoryDetails as $detail) {
				$inventoryData[] = array(
					'lineId' => $detail->getAttribute('lineId'),
					'itemId' => $detail->getAttribute('itemId'),
					'creationTime' => $coreHlpr ->extractNodeVal($xpath->query('a:DeliveryEstimate/a:CreationTime', $detail)),
					'display' => $coreHlpr ->extractNodeVal($xpath->query('a:DeliveryEstimate/a:Display', $detail)),
					'deliveryWindow_from' => $coreHlpr->extractNodeVal($xpath->query('a:DeliveryEstimate/a:DeliveryWindow/a:From', $detail)),
					'deliveryWindow_to' => $coreHlpr->extractNodeVal($xpath->query('a:DeliveryEstimate/a:DeliveryWindow/a:To', $detail)),
					'shippingWindow_from' => $coreHlpr->extractNodeVal($xpath->query('a:DeliveryEstimate/a:ShippingWindow/a:From', $detail)),
					'shippingWindow_to' => $coreHlpr->extractNodeVal($xpath->query('a:DeliveryEstimate/a:ShippingWindow/a:To', $detail)),
					'shipFromAddress_line1' => $coreHlpr->extractNodeVal($xpath->query('a:ShipFromAddress/a:Line1', $detail)),
					'shipFromAddress_city' => $coreHlpr->extractNodeVal($xpath->query('a:ShipFromAddress/a:City', $detail)),
					'shipFromAddress_mainDivision' => $coreHlpr->extractNodeVal($xpath->query('a:ShipFromAddress/a:MainDivision', $detail)),
					'shipFromAddress_countryCode' => $coreHlpr->extractNodeVal($xpath->query('a:ShipFromAddress/a:CountryCode', $detail)),
					'shipFromAddress_postalCode' => $coreHlpr->extractNodeVal($xpath->query('a:ShipFromAddress/a:PostalCode', $detail)),
				);
			}
		}

		return $inventoryData;
	}

	/**
	 * Update quote with inventory details response data.
	 * @param Mage_Sales_Model_Quote $quote the quote we use to get inventory details from eb2c
	 * @param array $inventoryData, a parse associative array of eb2c response
	 * @return void
	 */
	public function processInventoryDetails(Mage_Sales_Model_Quote $quote, array $inventoryData)
	{
		foreach ($inventoryData as $data) {
			if ($item = $quote->getItemById($data['lineId'])) {
				$this->_updateQuoteWithEb2cInventoryDetails($item, $data);
			} else {
				Mage::log(
					sprintf('[ %s ]: No item matching lineId %s.', __CLASS__, $data['lineId']),
					Zend_Log::DEBUG
				);
			}
		}
		// Save the quote
		$quote->save();
		return $this;
	}

	/**
	 * Update quote with inventory details response data.
	 * @param Mage_Sales_Model_Quote_Item $quoteItem the item to be updated with eb2c data
	 * @param array $inventoryData the data from eb2c for the quote-item
	 * @return void
	 */
	protected function _updateQuoteWithEb2cInventoryDetails(Mage_Sales_Model_Quote_Item $quoteItem, array $inventoryData)
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
