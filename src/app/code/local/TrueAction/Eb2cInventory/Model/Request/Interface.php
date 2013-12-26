<?php

interface TrueAction_Eb2cInventory_Model_Request_Interface
{
	/**
	 * Make a request to the inventory service with details from the given quote.
	 * @param  Mage_Sales_Model_Quote $quote Quote to make the inventory request for.
	 * @return string The response from the service.
	 */
	public function makeRequestForQuote(Mage_Sales_Model_Quote $quote);
	/**
	 * Update the quote with the response from the inventory service.
	 * @param  Mage_Sale_Model_Quote $quote           Quote to update.
	 * @param  string                $responseMessage Response message from the inventory service.
	 * @return TrueAction_Eb2cInventory_Model_Request_Interface $this object
	 */
	public function updateQuoteWithResponse(Mage_Sales_Model_Quote $quote, $responseMessage);
}
