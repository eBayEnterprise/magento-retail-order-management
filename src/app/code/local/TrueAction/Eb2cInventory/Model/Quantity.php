<?php
class TrueAction_Eb2cInventory_Model_Quantity
	extends TrueAction_Eb2cInventory_Model_Request_Abstract
	implements TrueAction_Eb2cInventory_Model_Request_Interface
{
	/**
	 * @see TrueAction_Eb2cInventory_Model_Request_Abstract
	 * @var string Key used by the eb2cinventory/data helper to identify the URI for this request
	 */
	const OPERATION_KEY = 'check_quantity';
	/**
	 * @see TrueAction_Eb2cInventory_Model_Request_Abstract
	 * @var string Config key used to identify the xsd file used to validate the request message
	 */
	const XSD_FILE_CONFIG = 'xsd_file_quantity';

	/**
	 * Build quantity request message DOM Document
	 * @param Mage_Sales_Model_Quote $quote Quote the request is for
	 * @return DOMDocument The XML document, to be sent as request to eb2c
	 */
	protected function _buildRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, Mage::helper('eb2cinventory')->getXmlNs())->firstChild;
		foreach (Mage::helper('eb2cinventory')->getInventoriedItems($quote->getAllVisibleItems()) as $idx => $item) {
			// just make sure the item has a sku, don't use getId as that field may not exist yet (new quote/item)
			if ($item->getSku()) {
				$quantityRequestMessage->createChild('QuantityRequest', null, array('lineId' => 'item' . $idx, 'itemId' => $item->getSku()));
			}
		}
		return $domDocument;
	}
	/**
	 * Parse through XML response to get eb2c available stock for an item.
	 * @param string $quantityResponseMessage the XML response from eb2c
	 * @return array The available stock from eb2c for each item, keyed by itemId
	 */
	public function getAvailableStockFromResponse($quantityResponseMessage)
	{
		$availableStock = array();
		if (trim($quantityResponseMessage) !== '') {
			$coreHlpr = Mage::helper('eb2ccore');
			$xpath = Mage::helper('eb2cinventory/quote')->getXpathForMessage($quantityResponseMessage);
			$quantities = $xpath->query('//a:QuantityResponse');
			foreach ($quantities as $quantity) {
				$availableStock[$quantity->getAttribute('itemId')] = (int) $coreHlpr->extractNodeVal($xpath->query('a:Quantity', $quantity));
			}
		}
		return $availableStock;
	}
	/**
	 * Update the quote with a response from the service
	 * @param  Mage_Sales_Model_Quote $quote           The quote object to update.
	 * @param  string                 $responseMessage Response from the Quantity service.
	 * @return TrueAction_Eb2cInventory_Model_Quantity $this object
	 */
	public function updateQuoteWithResponse(Mage_Sales_Model_Quote $quote, $responseMessage)
	{
		if ($responseMessage) {
			$availableStock = $this->getAvailableStockFromResponse($responseMessage);
			// loop through all items in the quote, not filtered to inventoried as it won't
			// be necessary as non-inventoried items won't be in the response and will not get updated
			foreach ($quote->getAllItems() as $item) {
				if (isset($availableStock[$item->getSku()])) {
					if ($availableStock[$item->getSku()] === 0) {
						Mage::helper('eb2cinventory/quote')->removeItemFromQuote($quote, $item);
					} elseif ($availableStock[$item->getSku()] < $item->getQty()) {
						Mage::helper('eb2cinventory/quote')->updateQuoteItemQuantity($quote, $item, $availableStock[$item->getSku()]);
					}
				}
			}
		}
		return $this;
	}
}
