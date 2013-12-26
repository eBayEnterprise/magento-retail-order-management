<?php

class TrueAction_Eb2cInventory_Helper_Quote
{
	const QUANTITY_REQUEST_GREATER_MESSAGE = 'TrueAction_Eb2cInventory_Quantity_Request_Greater_Message';
	const QUANTITY_OUT_OF_STOCK_MESSAGE = 'TrueAction_Eb2cInventory_Quantity_Out_Of_Stock_Message';
	const CODE_OOS_ITEM = 1;
	const CODE_LIMITED_STOCK_ITEM = 2;
	const ERROR_TYPE = 'qty';
	const ERROR_ORIGIN = 'eb2cinventory';
	/**
	 * If the quote has been allocated, roll it back.
	 * @param  Mage_Sales_Model_Quote $quote Quote to rollback allocation for.
	 * @return TrueAction_Eb2cInventory_Helper_Quote $this object
	 */
	public function rollbackAllocation(Mage_Sales_Model_Quote $quote)
	{
		$allocation = Mage::getModel('eb2cinventory/allocation');
		if ($allocation->hasAllocation($quote)) {
			$allocation->rollbackAllocation($quote);
		}
		return $this;
	}
	/**
	 * Instanticate a new DOMXPath object with the given DOM Document
	 * @param  TrueAction_Dom_Document $doc DOM Document the XPath is for
	 * @return DOMXPath
	 */
	protected function _getNewDomXPath(TrueAction_Dom_Document $doc)
	{
		return new DOMXPath($doc);
	}
	/**
	 * Create a DOMXPath object for a DOMDocument for the given xml string message.
	 * @param  string $xmlMessage string of xml
	 * @return DOMXPath object that can be used to search the given xml sting
	 */
	public function getXPathForMessage($xmlMessage)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xmlMessage);
		$xpath = $this->_getNewDomXPath($doc);
		$xpath->registerNamespace('a', Mage::helper('eb2cinventory')->getXmlNs());
		return $xpath;
	}
	/**
	 * Add a notice to the checkout session. Assume any messages already translated via _getCartMessage.
	 * @param Mage_Sales_Model_Quote $quote     Quote the message applied to
	 * @param string                 $message   Message to add as a notice to the checkout session
	 * @param string                 $errorCode Error code for the message
	 * @return TrueAction_Eb2cInventory_Model_Request_Abstract $this object
	 */
	public function addCartNotice(Mage_Sales_Model_Quote $quote, $message, $errorCode)
	{
		$quote->addErrorInfo(self::ERROR_TYPE, self::ERROR_ORIGIN, $errorCode, $message);
		Mage::getSingleton('checkout/session')->addNotice($message);
		return $this;
	}
	/**
	 * Remove the item from the quote. Also adds a session notice for the user indicating the
	 * item is no longer available.
	 * @param  Mage_Sales_Model_Quote      $quote Quote to update
	 * @param  Mage_Sales_Model_Quote_Item $item  Item to remove from the quote
	 * @return TrueAction_Eb2cInventory_Helper_Quote $this item
	 */
	public function removeItemFromQuote(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Quote_Item $item)
	{
		$message = Mage::helper('eb2cinventory')->__(
			self::QUANTITY_OUT_OF_STOCK_MESSAGE, $item->getName(), $item->getSku()
		);
		$this->addCartNotice($quote, $message, self::CODE_OOS_ITEM);
		$quote->deleteItem($item);
		return $this;
	}
	/**
	 * Update quantity of an item. Also adds a session notice for the user indicating the item
	 * was updated in the cart.
	 * @param  Mage_Sales_Model_Quote      $quote Quote to update
	 * @param  Mage_Sales_Model_Quote_Item $item  The item to update
	 * @param  float                       $qty   Quantity to set the item quantity to
	 * @return TrueAction_Eb2cInveotory_Helper_Quantity $this object
	 */
	public function updateQuoteItemQuantity(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Quote_Item $item, $qty)
	{
		if ($qty === 0) {
			return $this->removeItemFromQuote($quote, $item);
		}
		$message = Mage::helper('eb2cinventory')->__(
			self::QUANTITY_REQUEST_GREATER_MESSAGE, $item->getName(), $item->getSku(), $item->getQty(), $qty
		);
		$this->addCartNotice($quote, $message, self::CODE_LIMITED_STOCK_ITEM);
		$item->setQty($qty);
		return $this;
	}
}
