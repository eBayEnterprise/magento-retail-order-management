<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Model_Allocation extends TrueAction_Eb2cInventory_Model_Abstract
{
	const ALLOCATION_QTY_LIMITED_STOCK_MESSAGE = 'TrueAction_Eb2cInventory_Allocation_Qty_Limited_Stock_Message';
	const ALLOCATION_QTY_OUT_STOCK_MESSAGE = 'TrueAction_Eb2cInventory_Allocation_Qty_Out_Stock_Message';
	/**
	 * A quote only requires an allocation if it has items with managed stock, does not already have
	 * an allocation or has an allocation that has expired.
	 * @param  Mage_Sales_Model_Quote $quote The quote that may be allocation
	 * @return boolean                       True if the quote needs an allocation. False if it does not.
	 */
	public function requiresAllocation(Mage_Sales_Model_Quote $quote)
	{
		$managedStockItems = $this->getInventoriedItems($quote->getAllItems());
		return !empty($managedStockItems) && (!$this->hasAllocation($quote) || $this->isExpired($quote));
	}

	/**
	 * Allocating all items brand new quote from eb2c.
	 * @param Mage_Sales_Model_Quote $quote, the quote to allocate inventory items in eb2c for
	 * @return string the eb2c response to the request.
	 */
	public function allocateQuoteItems(Mage_Sales_Model_Quote $quote)
	{
		$responseMessage = '';
		// Shipping address required for the allocation request, if there's no address,
		// can't make the allocation request.
		if ($quote && $quote->getShippingAddress()) {
			// build request
			$doc = $this->_buildAllocationRequestMessage($quote);
			$helper = Mage::helper('eb2cinventory');
			$uri = $helper->getOperationUri('allocate_inventory');
			$xsd = $helper->getConfigModel()->xsdFileAllocation;
			$responseMessage = Mage::getModel('eb2ccore/api')->request($doc, $xsd, $uri);
		}
		return $responseMessage;
	}
	/**
	 * Build  Allocation request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	protected function _buildAllocationRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		$coreHelper = Mage::helper('eb2ccore');
		$domDocument = $coreHelper->getNewDomDocument();
		$allocationRequestMessage = $domDocument->addElement('AllocationRequestMessage', null, Mage::helper('eb2cinventory')->getXmlNs())->firstChild;
		$allocationRequestMessage->setAttribute('requestId', Mage::helper('eb2cinventory')->getRequestId($quote->getEntityId()));
		$allocationRequestMessage->setAttribute('reservationId', Mage::helper('eb2cinventory')->getReservationId($quote->getEntityId()));
		foreach ($quote->getAllAddresses() as $address) {
			foreach ($this->getInventoriedItems($address->getAllItems()) as $item) {
				// creating quoteItem element
				$quoteItem = $allocationRequestMessage->createChild('OrderItem', null, array('lineId' => $item->getId(), 'itemId' => $item->getSku()));
				// add quantity - FYI: integer value doesn't get added only string
				$quoteItem->createChild('Quantity', (string) $item->getQty());
				$shippingAddress = $quote->getShippingAddress();
				// creating shipping details
				$shipmentDetails = $quoteItem->createChild('ShipmentDetails', null);
				// add shipment method
				$shipmentDetails->createChild(
					'ShippingMethod',
					$coreHelper->lookupShipMethod($shippingAddress->getShippingMethod())
				);
				// add ship to address
				$shipToAddress = $shipmentDetails->createChild('ShipToAddress', null);
				// add ship to address Line 1
				$shipToAddress->createChild('Line1', $shippingAddress->getStreet(1), null);
				// add ship to address City
				$shipToAddress->createChild('City', $shippingAddress->getCity(), null);
				// add ship to address MainDivision
				$shipToAddress->createChild('MainDivision', $shippingAddress->getRegionCode(), null);
				// add ship to address CountryCode
				$shipToAddress->createChild('CountryCode', $shippingAddress->getCountryId(), null);
				// add ship to address PostalCode
				$shipToAddress->createChild('PostalCode', $shippingAddress->getPostcode(), null);
			}
		}

		return $domDocument;
	}

	/**
	 * Parse allocation response XML.
	 *
	 * @param string $allocationResponseMessage the XML response from eb2c
	 * @return array, an associative array of response data
	 */
	public function parseResponse($allocationResponseMessage)
	{
		$allocationData = array();
		if (trim($allocationResponseMessage) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();

			// load response string XML from eb2c
			$doc->loadXML($allocationResponseMessage);
			$i = 0;
			$allocationResponse = $doc->getElementsByTagName('AllocationResponse');
			$allocationMessage = $doc->getElementsByTagName('AllocationResponseMessage');
			foreach ($allocationResponse as $response) {
				$allocationData[] = array(
					'lineId' => $response->getAttribute('lineId'),
					'itemId' => $response->getAttribute('itemId'),
					'qty' => (int) $allocationResponse->item($i)->nodeValue,
					'reservation_id' => $allocationMessage->item(0)->getAttribute('reservationId'),
					'reserved_at' => Mage::getModel('core/date')->date('Y-m-d H:i:s'),
				);
				$i++;
			}
		}

		return $allocationData;
	}

	/**
	 * update quote with allocation response data.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote we use to get allocation response from eb2c
	 * @param string $allocationData, a parse associative array of eb2c response
	 * @return array, error results of item that cannot be allocated
	 */
	public function processAllocation(Mage_Sales_Model_Quote $quote, $allocationData)
	{
		$allocationResult = array();
		foreach ($allocationData as $data) {
			if ($item = $quote->getItemById($data['lineId'])) {
				$result = $this->_updateQuoteWithEb2cAllocation($item, $data);
				if ($result) {
					$allocationResult[] = $result;
				}
			}
		}
		return $allocationResult;
	}

	/**
	 * Removing all allocation data from quote item.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to empty any allocation data from its item
	 * @return void
	 */
	protected function _emptyQuoteAllocation(Mage_Sales_Model_Quote $quote)
	{
		foreach ($this->getInventoriedItems($quote->getAllItems()) as $item) {
			// emptying reservation data from quote item
			$item->unsEb2cReservationId()
				->unsEb2cReservedAt()
				->unsEb2cQtyReserved()
				->save();
		}
	}

	/**
	 * checking if any quote item has allocation data.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to check if its items have any allocation data
	 * @return boolean, true reserved allocation is found, false no allocation data found on any quote item
	 */
	public function hasAllocation(Mage_Sales_Model_Quote $quote)
	{
		foreach ($this->getInventoriedItems($quote->getAllItems()) as $item) {
			// find the reservation data in the quote item
			if (trim($item->getEb2cReservationId()) !== '') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the reserved allocation exceed the maximum expired setting.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to check if items have any allocation data
	 * @return boolean, true if any item is expired; false otherwise
	 */
	public function isExpired($quote)
	{
		$now = new DateTime(gmdate('c'));
		$expireMins = (int) Mage::helper('eb2cinventory')->getConfigModel()->allocationExpired;
		$expiredIfReservedBefore = $now->sub(DateInterval::createFromDateString(sprintf('%d minutes', $expireMins)));

		foreach ($this->getInventoriedItems($quote->getAllItems()) as $item) {
			// find the reservation data in the quote item
			if ($item->hasEb2cReservedAt()) {
				$reservedAt = new DateTime($item->getEb2cReservedAt());
				if ($reservedAt < $expiredIfReservedBefore) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Update quote with allocation response data.
	 *
	 * @param Mage_Sales_Model_Quote_Item $quoteItem the item to be updated with eb2c data
	 * @param array $quoteData the data from eb2c for the quote item
	 *
	 * @return string, the allocation error message for that particular inventory
	 */
	protected function _updateQuoteWithEb2cAllocation(Mage_Sales_Model_Quote_Item $quoteItem, $quoteData)
	{
		$results = '';

		// get quote from quote-item
		$quote = $quoteItem->getQuote();

		// Set the message allocation failure, adjust quote with quantity reserved.
		if ($quoteData['qty'] > 0 && $quoteItem->getQty() > $quoteData['qty']) {
			// save reservation data to inventory detail
			$quoteItem->setQty($quoteData['qty'])
				->setEb2cReservationId($quoteData['reservation_id'])
				->setEb2cReservedAt($quoteData['reserved_at'])
				->setEb2cQtyReserved($quoteData['qty'])
				->save();

			// save the quote
			$quote->save();

			$results = sprintf(Mage::helper('eb2cinventory')->__(self::ALLOCATION_QTY_LIMITED_STOCK_MESSAGE), $quoteData['qty'], $quoteItem->getSku());
		} elseif ($quoteData['qty'] <= 0) {
			// removed the out of stock allocated item
			$quote->deleteItem($quoteItem);
			$results = sprintf(Mage::helper('eb2cinventory')->__(self::ALLOCATION_QTY_OUT_STOCK_MESSAGE), $quoteItem->getSku());
		}

		return $results;
	}

	/**
	 * Roll back allocation request.
	 *
	 * @param Mage_Sales_Model_Quote $quote to generate request XML from
	 * @return string the xml response
	 */
	public function rollbackAllocation(Mage_Sales_Model_Quote $quote)
	{
		// remove last allocations data from quote item
		$this->_emptyQuoteAllocation($quote);
		$hlpr = Mage::helper('eb2cinventory');
		return Mage::getModel('eb2ccore/api')->request(
			$this->buildRollbackAllocationRequestMessage($quote),
			$hlpr->getConfigModel()->xsdFileRollback,
			$hlpr->getOperationUri('rollback_allocation')
		);
	}
	/**
	 * Build Rollback Allocation request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildRollbackAllocationRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$rollbackAllocationRequestMessage = $domDocument->addElement('RollbackAllocationRequestMessage', null, Mage::helper('eb2cinventory')->getXmlNs())->firstChild;
		$rollbackAllocationRequestMessage->setAttribute('requestId', Mage::helper('eb2cinventory')->getRequestId($quote->getEntityId()));
		$rollbackAllocationRequestMessage->setAttribute('reservationId', Mage::helper('eb2cinventory')->getReservationId($quote->getEntityId()));
		return $domDocument;
	}
}
