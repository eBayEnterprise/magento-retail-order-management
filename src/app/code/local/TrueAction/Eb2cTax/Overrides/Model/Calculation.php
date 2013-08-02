<?php
/**
 *
 */
class TrueAction_Eb2cTax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	protected static $_typeMap = array(0 => 'merchandise', 1 => 'shipping', 2 => 'duty');

	public function __construct()
	{
		parent::__construct();
		if (!$this->hasTaxResponse()) {
			$checkout = Mage::getSingleton('checkout/session');
			if ($checkout->hasEb2cTaxResponse()) {
				parent::setTaxResponse($checkout->getEb2cTaxResponse());
			}
		}
		$this->_eConfig = Mage::getModel('eb2ctax/config');
	}

	/**
	 * return the total tax amount for any discounts.
	 * @param  Varien_Object $itemSelector
	 * @return float
	 */
	public function getDiscountTax(Varien_Object $itemSelector, $type = 'merchandise')
	{
		$tax = 0.0;
		$itemResponse = $this->_getItemResponse($itemSelector->getItem(), $itemSelector->getAddress());
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuoteDiscounts();
			foreach ($taxQuotes as $taxQuote) {
				if ($type === self::$_typeMap[$taxQuote->getType()]) {
					$tax += $taxQuote->getCalculatedTax();
				}
			}
		}
		return $tax;
	}

	/**
	 * return the total tax amount for any discounts.
	 * @param  Varien_Object $itemSelector
	 * @return float
	 */
	public function getDiscountTaxForAmount(
		$amount,
		Varien_Object $itemSelector,
		$type = 'merchandise',
		$round = true
	) {
		$tax = 0.0;
		$itemResponse = $this->_getItemResponse($itemSelector->getItem(), $itemSelector->getAddress());
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuoteDiscounts();
			foreach ($taxQuotes as $taxQuote) {
				if ($type === self::$_typeMap[$taxQuote->getType()]) {
					$tax += ($amount * $taxQuote->getEffectiveRate());
				}
			}
		}
		$tax = $round ? $this->round($tax) : $tax;
		return $tax;
	}

	/**
	 * get a request object for the quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return TrueAction_Eb2cTax_Model_Request
	 */
	public function getTaxRequest(Mage_Sales_Model_Quote $quote = null)
	{
		$response = $this->getTaxResponse();
		$request = ($response && $response->getTaxRequest()) ?
			$response->getRequest() :
			Mage::getModel('eb2ctax/request', array('quote' => $quote));
		return $request;
	}

	/**
	 * store the tax response from eb2c
	 * @param TrueAction_Eb2cTax_Model_Response $response
	 */
	public function setTaxResponse(TrueAction_Eb2cTax_Model_Response $response = null)
	{
		if (isset($response)) {
			parent::setTaxResponse($response);
			Mage::getSingleton('checkout/session')->setEb2cTaxResponse($response);
		}
		return $this;
	}

	/**
	 * calculate tax amount for an item filtered by $type.
	 * @param  Varien_Object $itemSelector
	 * @param  string        $type
	 * @return float
	 */
	public function getTax(Varien_Object $itemSelector, $type = 'merchandise')
	{
		$itemResponse = $this->_getItemResponse($itemSelector->getItem(), $itemSelector->getAddress());
		$tax = 0.0;
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuotes();
			foreach ($taxQuotes as $taxQuote) {
				if ($type === self::$_typeMap[$taxQuote->getType()]) {
					$tax += $taxQuote->getCalculatedTax();
				}
			}
			if ($type === 'duty') {
				$tax += $itemResponse->getDutyAmount();
			}
		}
		return $tax;
	}

	public function getTaxForAmount($amount, Varien_Object $itemSelector, $type = 'merchandise', $round = true)
	{
		$itemResponse = $this->_getItemResponse($itemSelector->getItem(), $itemSelector->getAddress());
		$tax = 0.0;
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuotes();
			foreach ($taxQuotes as $taxQuote) {
				if ($type === self::$_typeMap[$taxQuote->getType()]) {
					$tax += $amount * $taxQuote->getEffectiveRate();
				}
			}
			if ($type === 'duty') {
				$tax += $itemResponse->getDutyAmount();
			}
		}
		$tax = $round ? $this->round($tax) : $tax;
		return $tax;
	}

	/**
	 * return the response data for the specified item.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @param  Mage_Salse_Model_Quote_Address $address
	 * @return
	 */
	protected function _getItemResponse(
		Mage_Sales_Model_Quote_Item $item = null,
		Mage_Sales_Model_Quote_Address $address = null
	) {
		$response = $this->getTaxResponse();
		$itemResponse = $response && $response->isValid() ?
			$response->getResponseForItem($item, $address) :
			null;
		return $itemResponse;
	}

	public function getAppliedRates($itemSelector)
	{
		$item       = $itemSelector->getItem();
		$address    = $itemSelector->getAddress();
		$result     = array();
		$baseAmount = $item->getBaseTaxAmount();
		$itemResponse = $this->_getItemResponse($item, $address);
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuotes();
			$nextId = 1;
			foreach ($taxQuotes as $index => $taxQuote) {
				$taxRate              = $taxQuote->getEffectiveRate();
				$code                 = $taxQuote->getCode();
				$id = $code . '-' . $taxRate;
				if (isset($result[$id])) {
					$group = $result[$id];
				} else {
					$group                = array();
					$group['id']          = $id;
					$group['percent']     = $taxRate * 100.0;
					$group['amount']      = 0;
				}
				$rate                = array();
				$rate['code']        = $code;
				$rate['title']       = Mage::helper('tax')->__($code);
				$rate['amount']      = $taxQuote->getCalculatedTax();
				$rate['percent']     = $taxRate * 100.0;
				$rate['base_amount'] = $baseAmount;
				$rate['position']    = 1;
				$rate['priority']    = 1;
				$group['rates'][]    = $rate;
				$group['amount']     += $rate['amount'];
				$result[$id]         = $group;
			}
		}
		return $result;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getRateRequest(
		$shippingAddress = null,
		$billingAddress = null,
		$customerTaxClass = '',
		$store = null
	) {
		$quote = $billingAddress ? $billingAddress->getQuote() : null;
		return $this->getTaxRequest($quote);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getRate($request)
	{
		return 0.0;
	}
}
