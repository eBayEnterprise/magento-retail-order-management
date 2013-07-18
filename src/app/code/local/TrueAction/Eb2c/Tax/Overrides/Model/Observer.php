<?php
class TrueAction_Eb2c_Tax_Overrides_Model_Observer
{
	protected $_tax;

	/**
	 * Get helper tax instantiated object.
	 *
	 * @return TrueAction_Eb2c_Tax_Overrides_Helper_Data
	 */
	protected function _getTaxHelper()
	{
		if (!$this->_tax) {
			$this->_tax =Mage::helper('tax');
		}
		return $this->_tax;
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
		foreach ($quote->getAllAddresses() as $address) {
			$address->setExtraTaxAmount(0);
			$address->setBaseExtraTaxAmount(0);
		}
		if (is_a($quote, 'Mage_Sales_Model_Quote')) {
			$this->_getTaxHelper()->getCalculator()
				->getTaxRequest()
				->checkAddresses($quote);
			$this->_fetchTaxDutyInfo($quote);
		} else {
			Mage::log(
				'EB2C Tax Error: quoteCollectTotalsBefore: did not receive a Mage_Sales_Model_Quote object',
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
				$calc->setTaxResponse($response);
			}
		} catch (Exception $e) {
			Mage::log(
				'Unable to send TaxDutyQuote request: ' . $e->getMessage(),
				Zend_Log::WARN
			);
		}
	}

	/**
	 * checking quote item discount
	 *
	 * @param Varien_Event_Observer $observer
	 * @return Mage_Tax_Model_Observer
	 */
	public function salesRuleEventItemProcessed(Varien_Event_Observer $observer)
	{
		Mage::log('salesrule_validator_process', Zend_Log::DEBUG);
		/* @var $quote Mage_Sales_Model_Quote_Item_Abstract */
		$item = $observer->getEvent()->getItem();
		if (is_a($item, 'Mage_Sales_Model_Quote_Item_Abstract')) {
			$this->_getTaxHelper()->getCalculator()
				->getTaxRequest()
				->checkDiscounts($item);
		} else {
			Mage::log(
				'EB2C Tax Error: salesRuleEventItemProcessed: did not receive a Mage_Sales_Model_Quote_Item_Abstract object',
				Zend_Log::WARN
			);
		}
		return $this;
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