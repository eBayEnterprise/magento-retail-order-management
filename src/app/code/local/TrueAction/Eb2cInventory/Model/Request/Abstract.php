<?php
abstract class TrueAction_Eb2cInventory_Model_Request_Abstract
{
	// Key used by the eb2cinventory/data helper to identify the URI for this request
	const OPERATION_KEY = '';
	// Config key used to identify the xsd file used to validate the request message
	const XSD_FILE_CONFIG = '';
	/**
	 * Get the inventory details for all items in this quote from eb2c.
	 * @param Mage_Sales_Model_Quote $quote the quote to get eb2c inventory details on
	 * @return string the eb2c response to the request.
	 */
	public function makeRequestForQuote(Mage_Sales_Model_Quote $quote)
	{
		$responseMessage = '';
		// only make api request if we have valid manage stock quote item in the cart and there is valid shipping add info for this quote
		// Shipping address required for the details request, if there's no address,
		// can't make the details request.
		if ($this->_canMakeRequestWithQuote($quote)) {
			$requestDoc = $this->_buildRequestMessage($quote);
			// using separate call to addData instead of constructor for testability
			$api = Mage::getModel('eb2ccore/api')->addData(array(
				'uri' => Mage::helper('eb2cinventory')->getOperationUri(static::OPERATION_KEY),
				'xsd' => Mage::helper('eb2cinventory')->getConfigModel()->getConfig(static::XSD_FILE_CONFIG),
			));
			try {
				$responseMessage = $api->request($requestDoc);
			} catch (Zend_Http_Client_Exception $e) {
				// if the request errors out, log the error and allow the still empty response to be handled with other emtpy responses
				Mage::log(sprintf('[%s] The following error has occurred while sending the request to eb2c: %s', __CLASS__, $e->getMessage()), Zend_Log::ERR);
			}
			if ($responseMessage === '') {
				$this->_handleEmptyResponse($api);
				// @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd
		}
		return $responseMessage;
	}
	/**
	 * Handle cases where the API model returns an empty response. This could result in
	 * a blocking (TrueAction_Eb2cInventory_Exception_Cart_Interrupt) or
	 * non-blocking (TrueAction_Eb2cInventory_Exception_Cart) exception being thrown.
	 * @param  TrueAction_Eb2cCore_Model_Api $api The API model used to make the request
	 * @return TrueAction_Eb2cInventory_Model_Request_Abstract $this object
	 * @throws TrueAction_Eb2cInventory_Exception_Cart_Interrupt If blocking error encountered
	 * @throws TrueAction_Eb2cInventory_Exception_Cart If non-blocking error encountered
	 */
	protected function _handleEmptyResponse(TrueAction_Eb2cCore_Model_Api $api)
	{
		if (!$api->hasStatus()) {
			throw new TrueAction_Eb2cInventory_Exception_Cart('Inventory request received an empty response with no status code.');
		}
		if (Mage::helper('eb2cinventory')->isBlockingStatus($api->getStatus())) {
			throw new TrueAction_Eb2cInventory_Exception_Cart_Interrupt('Inventory service returned a disruptive status: ' . $api->getStatus());
		}
		throw new TrueAction_Eb2cInventory_Exception_Cart('Inventory request returned an empty response with status: ' . $api->getSatus());
	}
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
	protected function _buildRequestMessage(Mage_Sales_Model_Quote $quote)
	{
		return Mage::helper('eb2ccore')->getNewDomDocument();
	}
}
