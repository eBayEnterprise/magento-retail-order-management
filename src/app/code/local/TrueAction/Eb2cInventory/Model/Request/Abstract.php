<?php
abstract class TrueAction_Eb2cInventory_Model_Request_Abstract
{
	// Key used by the eb2cinventory/data helper to identify the URI for this request
	const OPERATION_KEY = '';
	// Config key used to identify the xsd file used to validate the request message
	const XSD_FILE_CONFIG = '';
	// path to definition of what to do for different statuses in the api response.
	const STATUS_HANDLER_CONFIG_PATH = '';
	/**
	 * Make a request to the inventory service with details from the given quote.
	 *
	 * @param Mage_Sales_Model_Quote $quote quote to make the inventory request for
	 * @return string xml response from the service
	 */
	public function makeRequestForQuote(Mage_Sales_Model_Quote $quote)
	{
		$responseMessage = '';
		// only make api request if we have valid manage stock quote item in the cart and there is valid shipping add info for this quote
		// Shipping address required for the details request, if there's no address,
		// can't make the details request.
		if ($this->_canMakeRequestWithQuote($quote)) {
			$doc = $this->_buildRequestMessage($quote);
			// using separate call to addData instead of constructor for testability
			$helper = Mage::helper('eb2cinventory');
			$api = Mage::getModel('eb2ccore/api');
			$responseMessage = $api
				->setStatusHandlerPath(static::STATUS_HANDLER_CONFIG_PATH)
				->request(
					$doc,
					$helper->getConfigModel()->getConfig(static::XSD_FILE_CONFIG),
					$helper->getOperationUri(static::OPERATION_KEY)
				);
		}
		return $responseMessage;
	}
	/**
	 * Update the quote with the response from the inventory service.
	 * @param  Mage_Sale_Model_Quote $quote           Quote to update.
	 * @param  string                $responseMessage Response message from the inventory service.
	 * @return self
	 */
	abstract public function updateQuoteWithResponse(Mage_Sales_Model_Quote $quote, $responseMessage);
	/**
	 * Determine if a valid message can be sent using the quote. All inventory services
	 * require at least a set of items to send in the request.
	 * @param  Mage_Sales_Model_Quote $quote Quote the request would be for
	 * @return boolean Can a valid request be made for the quote? true if yes, false if no.
	 */
	protected function _canMakeRequestWithQuote(Mage_Sales_Model_Quote $quote)
	{
		$items = Mage::helper('eb2cinventory')->getInventoriedItems($quote->getAllItems());
		return !empty($items);
	}
	/**
	 * Build the message to send to the service in the request.
	 * @param  Mage_Sales_Model_Quote $quote Quote object the request is for
	 * @return DOMDocument The request message
	 */
	abstract protected function _buildRequestMessage(Mage_Sales_Model_Quote $quote);
}
