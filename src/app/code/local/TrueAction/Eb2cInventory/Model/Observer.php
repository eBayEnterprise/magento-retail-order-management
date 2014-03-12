<?php
class TrueAction_Eb2cInventory_Model_Observer
{
	const CANNOT_ADD_TO_CART_MESSAGE = 'TrueAction_Eb2cInventory_Cannot_Add_To_Cart_Message';
	const ALLOCATION_ERROR_MESSAGE = 'TrueAction_Eb2cInventory_Allocation_Error_Message';
	/**
	 * Validate the quote against inventory service calls. When items or item quantities in the quote
	 * have changed, this method will trigger a new inventory quantity service call and have the quote
	 * updated with the results. When shipping or item/item quantity changes are detected, a new
	 * inventory details request should be made.
	 *
	 * Failures in related methods, those actually making the inventory service calls
	 * and updating the quote, can signal for the process to be interrupted which will
	 * cause this method to throw a Mage_Core_Exception.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 * @throws TrueAction_Eb2cInventory_Exception_Cart_Interrupt If any of the service calls fail with a blocking
	 *         							                         status
	 * @throws TrueAction_Eb2cInventory_Exception_Cart If any of the service calls fail with a non-blocking status
	 */
	public function checkInventory($observer)
	{
		$coreSession = Mage::getSingleton('eb2ccore/session');
		$quote = $observer->getEvent()->getQuote();
		$qtyRequired = $coreSession->isQuantityUpdateRequired();
		$dtsRequired = $coreSession->isDetailsUpdateRequired();

		if ($qtyRequired || $dtsRequired) {
			Mage::helper('eb2cinventory/quote')->rollbackAllocation($quote);
			if ($qtyRequired) {
				$this->_updateQuantity($quote);
			}
			if ($dtsRequired) {
				$this->_updateDetails($quote);
			}
		}
		return $this;
	}
	/**
	 * Make an inventory quantity request and update the quote as needed.
	 * @param  Mage_Sales_Model_Quote $quote     Quote object to make the request for and updated
	 * @return self
	 */
	protected function _updateQuantity(Mage_Sales_Model_Quote $quote)
	{
		if ($this->_makeRequestAndUpdate(Mage::getModel('eb2cinventory/quantity'), $quote)) {
			Mage::getSingleton('eb2ccore/session')->updateQuoteInventory($quote)->resetQuantityUpdateRequired();
		}
		return $this;
	}
	/**
	 * Make an inventory details request and update the quote as needed.
	 * If the details request was successful (no unavailable items), also flag the
	 * inventory as being updated. If any items came back as unavailable, mark the session data as
	 * invalid as quantity will need to be run again at the next available opportunity.
	 * @param  Mage_Sales_Model_Quote $quote     Quote object the request is for
	 * @return self
	 */
	protected function _updateDetails(Mage_Sales_Model_Quote $quote)
	{
		if ($this->_makeRequestAndUpdate(Mage::getModel('eb2cinventory/details'), $quote)) {
			Mage::getSingleton('eb2ccore/session')->updateQuoteInventory($quote)->resetDetailsUpdateRequired();
		}
		return $this;
	}
	/**
	 * Use the given request model to make a request and update the quote accordingly.
	 * @param  TrueAction_Eb2cInventory_Model_Request_Abstract $requestModel Request object used to
	 *                                                                       make the request
	 * @param  Mage_Sales_Model_Quote $quote Quote object the request is for
	 * @return string the response text
	 */
	protected function _makeRequestAndUpdate(
		TrueAction_Eb2cInventory_Model_Request_Abstract $requestModel,
		Mage_Sales_Model_Quote $quote
	) {
		$response = $requestModel->makeRequestForQuote($quote);
		$requestModel->updateQuoteWithResponse($quote, $response);
		return $response;
	}
	/**
	 * Processing e2bc allocation, triggering eb2c_allocation_onepage_save_order_action_before event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function processEb2cAllocation($observer)
	{
		// get the quote from the event observer
		$quote = $observer->getEvent()->getQuote();
		$allocation = Mage::getModel('eb2cinventory/allocation');
		// only allow allocation only when, there's no previous allocation or the previous allocation expired
		if ($allocation->requiresAllocation($quote)) {
			// flag for failure or success allocation
			$isAllocated = true;

			// generate request and send request to eb2c allocation
			$allocationResponseMessage = $allocation->allocateQuoteItems($quote);
			if ($allocationResponseMessage) {
				// parse allocation response
				$allocationData = $allocation->parseResponse($allocationResponseMessage);

				// got a valid response from eb2c, then go ahead and update the quote with the eb2c information
				$allocatedErr = $allocation->processAllocation($quote, $allocationData);

				// Got an allocation failure
				if (!empty($allocatedErr)) {
					$isAllocated = false;
					foreach ($allocatedErr as $error) {
						Mage::getSingleton('checkout/session')->addError($error);
					}
				}
			}

			if (!$isAllocated) {
				throw new TrueAction_Eb2cInventory_Model_Allocation_Exception(
					Mage::helper('eb2cinventory')->__(self::ALLOCATION_ERROR_MESSAGE)
				);
				// @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd
		}

		// if we get to this point therefore, allocation was successful, then let dispatch an event for stored value gift card processing
		Mage::dispatchEvent('eb2c_event_dispatch_after_inventory_allocation', array('quote' => $quote));
	}
}
