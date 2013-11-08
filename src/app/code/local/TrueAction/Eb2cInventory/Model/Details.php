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
			$requestDoc = $this->buildInventoryDetailsRequestMessage($quote);
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
	 * Build Inventory Details request.
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildInventoryDetailsRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		$coreHlpr = Mage::helper('eb2ccore');
		$domDocument = $coreHlpr->getNewDomDocument();
		$hlpr = Mage::helper('eb2cinventory');
		$requestMessage = $domDocument->addElement('InventoryDetailsRequestMessage', null, $hlpr->getXmlNs())->firstChild;
		foreach($this->getInventoriedItems($quote->getAllItems()) as $item) {
			// creating orderItem element
			$orderItem = $requestMessage->createChild('OrderItem', null, array('lineId' => $item->getId(), 'itemId' => $item->getSku()));
			// add quantity, FYI: integer value doesn't get added only string
			$orderItem->createChild('Quantity', (string) $item->getQty());
			$shippingAddress = $quote->getShippingAddress();
			// creating shipping details
			$shipmentDetails = $orderItem->createChild('ShipmentDetails', null);
			// add shipment method
			$shipMethod = $coreHlpr->lookupShipMethod($shippingAddress->getShippingMethod());
			if ($shipMethod) {
				$shipmentDetails->createChild('ShippingMethod', $shipMethod);
			} else {
				throw new Mage_Exception(
					'Please configure an eb2c shipping method for the Magento method ' .
					$shippingAddress->getShippingMethod()
				);
			}
			// add ship to address
			$shipToAddress = $shipmentDetails->createChild('ShipToAddress', null);
			// add ship to address Line1
			$shipToAddress->createChild('Line1', $shippingAddress->getStreet(1));
			// add ship to address City
			$shipToAddress->createChild('City', $shippingAddress->getCity());
			// add ship to address MainDivision
			$shipToAddress->createChild('MainDivision', $shippingAddress->getRegionCode());
			// add ship to address CountryCode
			$shipToAddress->createChild('CountryCode', $shippingAddress->getCountryId());
			// add ship to address PostalCode
			$shipToAddress->createChild('PostalCode', $shippingAddress->getPostcode());
		}
		return $domDocument;
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
