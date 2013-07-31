<?php
class TrueAction_Eb2cTax_Overrides_Model_Observer
	extends Mage_Tax_Model_Observer
{
	protected $_tax;

	/**
	 * Get helper tax instantiated object.
	 *
	 * @return TrueAction_Eb2cTax_Overrides_Helper_Data
	 */
	protected function _getTaxHelper()
	{
		if (!$this->_tax) {
			$this->_tax =Mage::helper('tax');
		}
		return $this->_tax;
	}

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
		if ($response = $this->_getTaxHelper()->getCalculator()->getTaxResponse()) {
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

	// TODO: ADD SHIPPING METHOD EVENT
	// TODO: EACH OF THESE EVENTS SHOULD BOIL DOWN TO 3 CASES: 1 ITEM CHANGED FORCE RESEND; 2 ITEM CHANGED CHECKED RESEND; ADDRESS CHECK
	public function salesEventItemAdded(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventItemAdded');
		$this->_getTaxHelper()->getCalculator()
			->getTaxRequest()
			->invalidate();
	}

	public function cartEventProductUpdated(Varien_Event_Observer $observer)
	{
		Mage::log('cartEventProductUpdated');
		$this->_getTaxHelper()->getCalculator()
			->getTaxRequest()
			->invalidate();
	}

	public function salesEventItemRemoved(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventItemRemoved');
		$this->_getTaxHelper()->getCalculator()
			->getTaxRequest()
			->invalidate();
	}

	public function salesEventItemQtyUpdated(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventItemQtyUpdated');
		$quoteItem = $observer->getEvent()->getItem();
		if (!is_a($quoteItem, 'Mage_Sales_Model_Quote_Item')) {
			Mage::log(
				'EB2C Tax Error: quoteCollectTotalsBefore: did not receive a Mage_Sales_Model_Quote_Item object',
				Zend_Log::WARN
			);
		} else {
			$this->_getTaxHelper()->getCalculator()
				->getTaxRequest()
				->checkItemQty($quoteItem);
		}
	}

	/**
	 * Reset extra tax amounts on quote addresses before recollecting totals
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Mage_Tax_Model_Observer
	 */
	public function quoteCollectTotalsBefore(Varien_Event_Observer $observer)
	{
		Mage::log('send tax request event');
		/* @var $quote Mage_Sales_Model_Quote */
		$quote = $observer->getEvent()->getQuote();
		if (is_a($quote, 'Mage_Sales_Model_Quote')) {
			foreach ($quote->getAllAddresses() as $address) {
				$address->setExtraTaxAmount(0);
				$address->setBaseExtraTaxAmount(0);
			}
		} else {
			Mage::log(
				'EB2C Tax Error: quoteCollectTotalsBefore: did not receive a Mage_Sales_Model_Quote object',
				Zend_Log::WARN
			);
		}
		return $this;
	}

	/**
	 * send a tax request for the quote and set the reponse in the calculator.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Mage_Tax_Model_Observer
	 */
	public function taxEventSendRequest(Varien_Event_Observer $observer)
	{
		Mage::log('send tax request event');
		/* @var $quote Mage_Sales_Model_Quote */
		$quote = $observer->getEvent()->getQuote();
		if (is_a($quote, 'Mage_Sales_Model_Quote')) {
			// checking address
			$this->_getTaxHelper()->getCalculator()
				->getTaxRequest()
				->checkAddresses($quote);
			// checking ShippingOrigin Address
			$this->_getTaxHelper()->getCalculator()
				->getTaxRequest()
				->checkShippingOriginAddresses($quote);
			// checking AdminOrigin Address
			$this->_getTaxHelper()->getCalculator()
				->getTaxRequest()
				->checkAdminOriginAddresses();
			$this->_fetchTaxDutyInfo($quote);
		} else {
			Mage::log(
				'EB2C Tax Error: taxEventSendRequest: did not receive a Mage_Sales_Model_Quote object',
				Zend_Log::WARN
			);
		}
		return $this;
	}

	/**
	 * attempt to send a request for taxes.
	 * @param  Mage_Sales_Model_Quote $quote
	 */
	protected function _fetchTaxDutyInfo(Mage_Sales_Model_Quote $quote)
	{
		try {
			$helper = $this->_getTaxHelper();
			$calc = $helper->getCalculator();
			$request = $calc->getTaxRequest($quote);
			if ($request && $request->isValid()) {
				Mage::log(
					'sending taxduty request for quote ' . $quote->getId(),
					Zend_Log::DEBUG
				);
				$response = $helper->sendRequest($request);
				if (!$response->isValid())
				{
					Mage::throwException('valid request recieved an invalid response');
				}
				$calc->setTaxResponse($response);
			}
		} catch (Exception $e) {
			Mage::log(
				'Unsuccessful TaxDutyQuote request: ' . $e->getMessage(),
				Zend_Log::WARN
			);
		}
	}

// place holder functions
//

	/**
	 * @codeCoverageIgnore
	 */
	public function addTaxPercentToProductCollection($observer)
	{
		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function prepareCatalogIndexPriceSelect($observer)
	{
		return $this;
	}
}