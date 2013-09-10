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
	}

	/**
	 * return the total tax amount for any discounts.
	 * @param  Varien_Object $itemSelector
	 * @return float
	 */
	public function getDiscountTax(Varien_Object $itemSelector, $type='merchandise')
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
		$type='merchandise',
		$round=true
	)
	{
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
	 * get a tax request object.
	 * if $quote is null and a current request exists, return the existing request.
	 * if $quote is not null return a new request using the quote's data.
	 * otherwise return a new invalid request.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return TrueAction_Eb2cTax_Model_Request
	 */
	public function getTaxRequest(Mage_Sales_Model_Quote $quote=null)
	{
		if ($quote) {
			// delete old response/request
			$this->unsTaxResponse();
			Mage::getSingleton('checkout/session')->unsEb2cTaxResponse();
		}
		$response = $this->getTaxResponse();
		$request = is_null($quote) && $response && $response->getRequest() ?
			$response->getRequest() :
			Mage::getModel('eb2ctax/request', array('quote' => $quote));
		return $request;
	}

	/**
	 * store the tax response from eb2c
	 * @param TrueAction_Eb2cTax_Model_Response $response
	 */
	public function setTaxResponse(TrueAction_Eb2cTax_Model_Response $response=null)
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
	public function getTax(Varien_Object $itemSelector, $type='merchandise')
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

	/**
	 * calculate the tax for $amount using the effective rates in the response.
	 * @param  float        $amount
	 * @param  Varien_Object $itemSelector
	 * @param  string        $type
	 * @param  boolean       $round
	 * @return float
	 */
	public function getTaxForAmount($amount, Varien_Object $itemSelector, $type='merchandise', $round=true)
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
		Mage_Sales_Model_Quote_Item $item=null,
		Mage_Sales_Model_Quote_Address $address=null
	)
	{
		$response = $this->getTaxResponse();
		$itemResponse = $response && $response->isValid() ?
			$response->getResponseForItem($item, $address) :
			null;
		return $itemResponse;
	}

	/**
	 * Get the tax rates that have been applied for the item.
	 *
	 * @param  Varien_Object $itemSelector A wrapper object for an item and address
	 * @return array               The tax rates that have been applied to the item.
	 */
	public function getAppliedRates($itemSelector)
	{
		$helper       = Mage::helper('tax');
		$item         = $itemSelector->getItem();
		$address      = $itemSelector->getAddress();
		$store        = $address->getQuote()->getStore();
		$result       = array();
		$baseAmount   = $item->getBaseTaxAmount();
		$itemResponse = $this->_getItemResponse($item, $address);

		if ($itemResponse) {
			foreach ($itemResponse->getTaxQuotes() as $index => $taxQuote) {
				$taxRate = $taxQuote->getEffectiveRate();
				$code    = $taxQuote->getCode();
				$id      = $code . '-' . $taxRate;
				if (isset($result[$id])) {
					$group = $result[$id];
				} else {
					$group = array();
					$group['id']      = $id;
					$group['percent'] = $taxRate * 100.0;
					$group['amount']  = 0;
					$group['rates']   = array();
				}
				$rate = array();
				$rate['code']        = $code;
				$rate['title']       = $helper->__($code);
				$rate['percent']     = $taxRate * 100.0;
				$rate['base_amount'] = $taxQuote->getCalculatedTax();
				$rate['amount']      = $store->convertPrice($rate['base_amount']);
				$rate['position']    = 1;
				$rate['priority']    = 1;
				$group['rates'][]    = $rate;
				$group['amount']     += $rate['amount'];
				$result[$id]         = $group;

			}

			// add rate for any duty amounts
			if ($itemResponse->getDutyAmount()) {
				$id = $helper->taxDutyAmountRateCode($store);
				$group = array();
				$group['id']      = $id;
				$group['percent'] = null;
				$group['amount']  = 0;
				$rate = array();
				$rate['code']        = $id;
				$rate['title']       = $helper->__($id);
				$rate['percent']     = null;
				$rate['base_amount'] = $itemResponse->getDutyAmount();
				$rate['amount']      = $store->convertPrice($rate['base_amount']);
				$rate['position']    = 1;
				$rate['priority']    = 1;
				$group['rates'][]    = $rate;
				$group['amount']     += $rate['amount'];
				$result[$id]         = $group;
			}

			if ($helper->getApplyTaxAfterDiscount($store)) {
				foreach($itemResponse->getTaxQuoteDiscounts() as $index => $discountQuote) {
					$taxRate = $discountQuote->getEffectiveRate();
					$code    = $discountQuote->getCode();
					$id      = $code . '-' . $taxRate;
					if (isset($result[$id])) {
						$group = $result[$id];
					} else {
						$message = sprintf(
							'[ %s ] Eb2cTax: Discount with no matching tax quote encountered. code = %s, rate = %1.4f',
							__CLASS__,
							$code,
							$taxRate
						);
						Mage::log($message, Zend_Log::WARN);
						continue;
					}

					$rate                = $group['rates'][0];
					$rate['base_amount'] -= $discountQuote->getCalculatedTax();
					$rate['amount']      = $store->convertPrice($rate['base_amount']);
					$group['amount']     = $rate['amount'];
					$group['rates'][0]   = $rate;
					$result[$id]         = $group;
				}
			}
		}
		return $result;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getRateRequest(
		$shippingAddress=null,
		$billingAddress=null,
		$customerTaxClass='',
		$store=null
	)
	{
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
