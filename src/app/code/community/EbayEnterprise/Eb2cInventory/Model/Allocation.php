<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cInventory_Model_Allocation
	extends Varien_Object
{
	const ALLOCATION_QTY_LIMITED_STOCK_MESSAGE = 'EbayEnterprise_Eb2cInventory_Allocation_Qty_Limited_Stock_Message';
	const ALLOCATION_QTY_OUT_STOCK_MESSAGE = 'EbayEnterprise_Eb2cInventory_Allocation_Qty_Out_Stock_Message';
	const ALLOCATION_REQUEST_ID_PREFIX = 'IA-';
	const ROLLBACK_REQUEST_ID_PREFIX = 'IR-';

	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;

	public function __construct()
	{
		parent::__construct();
		$this->_logger = Mage::helper('ebayenterprise_magelog');
		$this->_context = Mage::helper('ebayenterprise_magelog/context');
	}

	/**
	 * A quote only requires an allocation if it has items with managed stock, does not already have
	 * an allocation or has an allocation that has expired.
	 * @param  Mage_Sales_Model_Quote $quote The quote that may be allocation
	 * @return bool                       True if the quote needs an allocation. False if it does not.
	 */
	public function requiresAllocation(Mage_Sales_Model_Quote $quote)
	{
		$managedStockItems = Mage::helper('eb2cinventory')->getInventoriedItems($quote->getAllVisibleItems());
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
		if ($quote->getShippingAddress()) {
			// build request
			$doc = $this->_buildAllocationRequestMessage($quote);
			$helper = Mage::helper('eb2cinventory');
			$uri = $helper->getOperationUri('allocate_inventory');
			$xsd = $helper->getConfigModel()->xsdFileAllocation;
			$responseMessage = Mage::getModel('eb2ccore/api')->request($doc, $xsd, $uri);
		} else {
			$logData = ['function_name' => __FUNCTION__];
			$logMessage = '{function_name} missing shipping address.';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
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
		$inventoryHelper = Mage::helper('eb2cinventory');
		$shippingHelper = Mage::helper('eb2ccore/shipping');
		$domDocument = $coreHelper->getNewDomDocument();
		$allocationRequestMessage = $domDocument->addElement('AllocationRequestMessage', null, $inventoryHelper->getXmlNs())->firstChild;
		$allocationRequestMessage->setAttribute('requestId', $coreHelper->generateRequestId(self::ALLOCATION_REQUEST_ID_PREFIX));
		$allocationRequestMessage->setAttribute('reservationId', $inventoryHelper->getReservationId());
		$shippingAddress = $quote->getShippingAddress();
		$shippingMethod = $shippingHelper->getMethodSdkId($shippingAddress->getShippingMethod());
		foreach ($inventoryHelper->getInventoriedItems($quote->getAllVisibleItems()) as $item) {
			// creating quoteItem element
			$quoteItem = $allocationRequestMessage->createChild('OrderItem', null, array('lineId' => $item->getId(), 'itemId' => $item->getSku()));
			// add quantity - FYI: integer value doesn't get added only string
			$quoteItem->createChild('Quantity', (string) $item->getQty());
			// creating shipping details
			$shipmentDetails = $quoteItem->createChild('ShipmentDetails', null);
			// add shipment method
			$shipmentDetails->createChild('ShippingMethod', $shippingMethod);
			// add ship to address
			$shipToAddress = $shipmentDetails->createChild('ShipToAddress', null);
			// add ship to address Line 1
			$shipToAddress->createChild('Line1', $shippingAddress->getStreet(1), null);
			// add ship to address City
			$shipToAddress->createChild('City', $shippingAddress->getCity(), null);
			$mainDivision = trim($shippingAddress->getRegionCode());
			if ($mainDivision !== '') {
				// add ship to address MainDivision
				$shipToAddress->createChild('MainDivision', $mainDivision, null);
			}
			// add ship to address CountryCode
			$shipToAddress->createChild('CountryCode', $shippingAddress->getCountryId(), null);
			// add ship to address PostalCode
			$shipToAddress->createChild('PostalCode', $shippingAddress->getPostcode(), null);
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
		if ($allocationResponseMessage) {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();

			// load response string XML from eb2c
			$doc->loadXML($allocationResponseMessage);
			$allocationResponse = $doc->getElementsByTagName('AllocationResponse');
			$reservationId = $doc->getElementsByTagName('AllocationResponseMessage')->item(0)->getAttribute('reservationId');
			foreach ($allocationResponse as $response) {
				$allocationData[] = array(
					'lineId' => $response->getAttribute('lineId'),
					'itemId' => $response->getAttribute('itemId'),
					'qty' => (int) $response->nodeValue,
					'reservation_id' => $reservationId,
					'reserved_at' => Mage::getModel('core/date')->date('Y-m-d H:i:s'),
				);
			}
		}
		return $allocationData;
	}

	/**
	 * update quote with allocation response data.
	 *
	 * @param  Mage_Sales_Model_Quote $quote the quote we use to get allocation response from eb2c
	 * @param  Mage_Sales_Model_Order $order the order object in memory we need to set the allocation data to for order create
	 * @param  array $allocationData parsed from eb2c allocation response
	 * @return array error results of item that cannot be allocated
	 */
	public function processAllocation(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order, array $allocationData)
	{
		$allocationResult = array();
		foreach ($allocationData as $data) {
			$quoteItem = $quote->getItemById($data['lineId']);
			$orderItem = $order->getItemByQuoteItemId($data['lineId']);
			if ($quoteItem && $orderItem) {
				$result = $this->_updateQuoteWithEb2cAllocation($quoteItem, $orderItem, $data);
				if ($result) {
					$allocationResult[] = $result;
				}
			}
		}
		return $allocationResult;
	}

	/**
	 * Removing all allocation data from quote item.
	 * @param  Mage_Sales_Model_Quote $quote the quote to empty any allocation data from its item
	 * @param  Mage_Sales_Model_Order $order
	 * @return void
	 */
	protected function _emptyQuoteAllocation(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order=null)
	{
		foreach (Mage::helper('eb2cinventory')->getInventoriedItems($quote->getAllVisibleItems()) as $item) {
			// emptying reservation data from quote item
			$item->unsEb2cReservationId()
				->unsEb2cReservedAt()
				->unsEb2cQtyReserved()
				->save();

			if ($order) {
				// attempting to clear allocation data from the order in memory
				$orderItem = $order->getItemByQuoteItemId($item->getItemId());
				if ($orderItem) {
					$orderItem->unsEb2cReservationId()
						->unsEb2cReservedAt()
						->unsEb2cQtyReserved();
				}
			}
		}
	}

	/**
	 * checking if any quote item has allocation data.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to check if its items have any allocation data
	 * @return bool, true reserved allocation is found, false no allocation data found on any quote item
	 */
	public function hasAllocation(Mage_Sales_Model_Quote $quote)
	{
		foreach (Mage::helper('eb2cinventory')->getInventoriedItems($quote->getAllVisibleItems()) as $item) {
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
	 * @return bool, true if any item is expired; false otherwise
	 */
	public function isExpired($quote)
	{
		$now = new DateTime(gmdate('c'));
		$expireMins = (int) Mage::helper('eb2cinventory')->getConfigModel()->allocationExpired;
		$expiredIfReservedBefore = $now->sub(DateInterval::createFromDateString(sprintf('%d minutes', $expireMins)));

		foreach (Mage::helper('eb2cinventory')->getInventoriedItems($quote->getAllVisibleItems()) as $item) {
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
	 * @param  Mage_Sales_Model_Quote_Item $quoteItem the quote item to be updated with eb2c data
	 * @param  Mage_Sales_Model_Order_Item $orderItem the order item to be updated with eb2c data
	 * @param  array $allocationData the data from eb2c for the quote item
	 * @return string, the allocation error message for that particular inventory
	 */
	protected function _updateQuoteWithEb2cAllocation(Mage_Sales_Model_Quote_Item $quoteItem, Mage_Sales_Model_Order_Item $orderItem, $allocationData)
	{
		$results = '';
		$quote = $quoteItem->getQuote();
		// If the item is OOS (0 qty allocated), the item cannot be sold. Remove
		// it from the quote and return an error message.
		if ($allocationData['qty'] === 0) {
			$quote->deleteItem($quoteItem);
			return Mage::helper('eb2cinventory')->__(self::ALLOCATION_QTY_OUT_STOCK_MESSAGE, $quoteItem->getSku());
		}
		$data = array(
			'eb2c_reservation_id' => $allocationData['reservation_id'],
			'eb2c_reserved_at' => $allocationData['reserved_at'],
			'eb2c_qty_reserved' => $allocationData['qty'],
		);

		$quoteItem->addData($data);
		$orderItem->addData($data);
		// If the requested quantity could not be allocated, it cannot be ordered.
		// Update the quote to the available quantity allocated and return a
		// user message.
		if ($quoteItem->getQty() > $allocationData['qty']) {
			$quoteItem->setQty($allocationData['qty']);
			$results = Mage::helper('eb2cinventory')->__(self::ALLOCATION_QTY_LIMITED_STOCK_MESSAGE, $allocationData['qty'], $quoteItem->getSku());
		}
		return $results;
	}
	/**
	 * Get the reservation id from the first order item. In the ware case when there's
	 * item simply return a random reservation id.
	 * @param  Mage_Sales_Model_Order $order The order object to retrieve the reservation id from
	 * @return string, the reservation id from the first order item
	 */
	protected function _getReservationIdFromOrder(Mage_Sales_Model_Order $order=null)
	{
		$newReservationId = Mage::helper('eb2cinventory')->getReservationId();
		if (!$order) {
			return $newReservationId;
		}
		$items = $order->getAllItems();
		return count($items) ? $items[0]->getEb2cReservationId() : $newReservationId;
	}
	/**
	 * Roll back allocation request.
	 * @param  Mage_Sales_Model_Quote $quote to generate request XML from
	 * @param  Mage_Sales_Model_Order $order Optional null may be passed in if the order is not currently in memory
	 * @return string the xml response
	 */
	public function rollbackAllocation(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order=null)
	{
		// remove last allocations data from quote item
		$this->_emptyQuoteAllocation($quote, $order);
		$hlpr = Mage::helper('eb2cinventory');
		return Mage::getModel('eb2ccore/api')->request(
			$this->buildRollbackAllocationRequestMessage($this->_getReservationIdFromOrder($order)),
			$hlpr->getConfigModel()->xsdFileRollback,
			$hlpr->getOperationUri('rollback_allocation')
		);
	}
	/**
	 * Build Rollback Allocation request.
	 * @param  string $reservationId the globally unique reservation id store in the order item
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildRollbackAllocationRequestMessage($reservationId)
	{
		$coreHelper = Mage::helper('eb2ccore');
		$domDocument = $coreHelper->getNewDomDocument();
		$rollbackAllocationRequestMessage = $domDocument->addElement('RollbackAllocationRequestMessage', null, Mage::helper('eb2cinventory')->getXmlNs())->firstChild;
		$rollbackAllocationRequestMessage->setAttribute('requestId', $coreHelper->generateRequestId(self::ROLLBACK_REQUEST_ID_PREFIX));
		$rollbackAllocationRequestMessage->setAttribute('reservationId', $reservationId);
		return $domDocument;
	}
}
