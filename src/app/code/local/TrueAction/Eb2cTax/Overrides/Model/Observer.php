<?php
class TrueAction_Eb2cTax_Overrides_Model_Observer extends Mage_Tax_Model_Observer
{
	protected $_tax;

	/**
	 * Put quote address tax information into order
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function salesEventConvertQuoteAddressToOrder(Varien_Event_Observer $observer)
	{
		$address = $observer->getEvent()->getAddress();
		$order = $observer->getEvent()->getOrder();

		$taxes = $address->getAppliedTaxes();
		if (is_array($taxes)) {
			if (is_array($order->getAppliedTaxes())) {
				$taxes = array_merge($order->getAppliedTaxes(), $taxes);
			}
			$order->setAppliedTaxes($taxes);
			$order->setConvertingFromQuote(true);
		}
	}

	/**
	 * Save order tax information
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function salesEventOrderAfterSave(Varien_Event_Observer $observer)
	{
		parent::salesEventOrderAfterSave($observer);
		$order = $observer->getEvent()->getOrder();
		// save all of the response quote and response quote discount objects
		if ($response = Mage::helper('tax')->getCalculator()->getTaxResponse()) {
			foreach ($order->getQuote()->getAllAddresses() as $address) {
				foreach ($address->getAllItems() as $item) {
					if ($responseItem = $response->getResponseForItem($item, $address)) {
						foreach ($responseItem->getTaxQuotes() as $taxQuote) {
							$taxQuote->setQuoteItemId($item->getId())
								->setQuoteAddressId($address->getId())
								->save();
						}
						foreach ($responseItem->getTaxQuoteDiscounts() as $taxDiscount) {
							$taxDiscount->setQuoteItemId($item->getId())
								->setQuoteAddressId($address->getId())
								->save();
						}
					}
				}
			}
		}
	}
	/**
	 * Clean up all of the tax flags at the end of collectTotals.
	 * @return TrueAction_Eb2cTax_Overrides_Model_Observer $this object
	 */
	public function cleanupTaxRequestFlags()
	{
		Mage::log(sprintf('[%s] Cleaning out tax session', __CLASS__));
		Mage::helper('eb2ctax')->cleanupSessionFlags();
		return $this;
	}
	/**
	 * Send a tax request for the address and update the calculator with the
	 * response and indicate taxes should be calculated using the response.
	 * @param Varien_Event_Observer $observer
	 * @return Mage_Tax_Model_Observer
	 */
	public function taxEventSubtotalCollectBefore(Varien_Event_Observer $observer)
	{
		$address = $observer->getEvent()->getAddress();
		if (Mage::helper('eb2ctax')->isRequestForAddressRequired($address)) {
			$calc = Mage::helper('tax')->getCalculator();
			$request = $calc->getTaxRequest($address);

			// If this doesn't get flipped to false, consider the request as having failed.
			$requestFailed = true;

			if ($request && $request->isValid()) {
				Mage::log('[' . __CLASS__ . '] Sending TaxDutyQuote request for quote address ' . $address->getId(), Zend_Log::DEBUG);

				try {
					$response = Mage::helper('eb2ctax')->sendRequest($request);
				} catch (Mage_Core_Exception $e) {
					$response = null;
					Mage::log(sprintf('[%s] Execption encountered making the TaxDutyQuote request: %s', __CLASS__, $e->getMessage()), Zend_Log::WARN);
				}

				if ($response && $response->isValid()) {
					$calc->setTaxResponse($response)
						->setCalculationTrigger(true);

					// safe to assume the request was made successfully - set the the flag so the session doesn't get flagged with a failure
					$requestFailed = false;
				} else {
					Mage::log('[' . __CLASS__ . '] Unsuccessful TaxDutyQuote request: valid request received an invalid response', Zend_Log::WARN);
				}

			}
			if ($requestFailed) {
				Mage::log(sprintf('[%s] TaxDutyRequest failed.', __CLASS__), Zend_Log::DEBUG);
				Mage::helper('eb2ctax')->failTaxRequest();
			}
		}
		return $this;
	}
}
