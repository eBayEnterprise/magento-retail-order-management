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
	 * send a tax request for the quote and set the reponse in the calculator.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Mage_Tax_Model_Observer
	 */
	public function taxEventSendRequest(Varien_Event_Observer $observer)
	{
		$quote = $observer->getEvent()->getQuote();
		$helper = Mage::helper('tax');
		$calc = $helper->getCalculator();
		$request = $calc->getTaxRequest($quote);

		if ($request && $request->isValid()) {
			Mage::log('[' . __CLASS__ . '] sending taxduty request for quote ' . $quote->getId(), Zend_Log::DEBUG);

			$response = $helper->sendRequest($request);
			if ($response->isValid()) {
				$calc->setTaxResponse($response);
			} else {
				Mage::log('[' . __CLASS__ . '] Unsuccessful TaxDutyQuote request: valid request received an invalid response', Zend_Log::WARN);
			}

		}
		return $this;
	}

}
