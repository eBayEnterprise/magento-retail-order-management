<?php
class TrueAction_Eb2cTax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	const TAX_REGULAR = 'regular';
	const TAX_REGULAR_FOR_AMOUNT = 'regular-for-amount';
	const TAX_DISCOUNT = 'discount';
	const TAX_DISCOUNT_FOR_AMOUNT = 'discount-for-amount';
	const TAX_DUTY_TYPE = 'duty';
	const TAX_SHIPPING_TYPE = 'shipping';
	const TAX_MERCHANDISE_TYPE = 'merchandise';

	protected static $_typeMap = array();

	public function _construct()
	{
		parent::_construct();
		if (!$this->hasTaxResponse()) {
			$checkout = Mage::getSingleton('checkout/session');
			if ($checkout->hasEb2cTaxResponse()) {
				$this->setTaxResponse($checkout->getEb2cTaxResponse());
			}
		}
		self::$_typeMap = array(
			0 => self::TAX_MERCHANDISE_TYPE,
			1 => self::TAX_SHIPPING_TYPE,
			2 => self::TAX_DUTY_TYPE
		);
	}

	/**
	 * extract tax data regular or discount
	 * @param TrueAction_Eb2cTax_Model_Response_Orderitem $itemResponse
	 * @param string $mode, [regular | regular-for-amount | discount | discount-for-amount]
	 * @return array
	 */
	protected function _extractTax(TrueAction_Eb2cTax_Model_Response_Orderitem $itemResponse, $mode='regular')
	{
		if ($mode === self::TAX_DISCOUNT || $mode === self::TAX_DISCOUNT_FOR_AMOUNT) {
			return $itemResponse->getTaxQuoteDiscounts();
		} else {
			return $itemResponse->getTaxQuotes();
		}
	}

	/**
	 * calculate tax by mode
	 * @param float $amount The amount to calculate tax on
	 * @param Varien_Object $itemSelector
	 * @param string $type One of the values in self::_typeMap
	 * @param boolean $round Whether to round the result
	 * @param string $mode, [regular | discount | discount-for-amount, regular, regular-for-amount]
	 * @return float the total tax amount for any discounts
	 */
	protected function _calcTaxByMode($amount=0, Varien_Object $itemSelector, $type='merchandise', $round=true, $mode='regular')
	{
		$tax = 0.0;
		$itemResponse = $this->_getItemResponse($itemSelector->getItem(), $itemSelector->getAddress());
		$taxQuotes = $this->_extractTax($itemResponse, $mode);
		foreach ($taxQuotes as $taxQuote) {
			if ($type === self::$_typeMap[$taxQuote->getType()]) {
				if (in_array($mode, array(self::TAX_DISCOUNT_FOR_AMOUNT, self::TAX_REGULAR_FOR_AMOUNT))) {
					$tax += ($amount * $taxQuote->getEffectiveRate());
				} else {
					$tax += $taxQuote->getCalculatedTax();
				}
			}
		}
		if ($type === self::TAX_DUTY_TYPE) {
			$tax += $itemResponse->getDutyAmount();
		}
		return $round ? $this->round($tax) : $tax;
	}

	/**
	 * calculate tax amount for an item filtered by $type.
	 * @param  Varien_Object $itemSelector
	 * @param  string        $type
	 * @return float
	 */
	public function getTax(Varien_Object $itemSelector, $type='merchandise')
	{
		return $this->_calcTaxByMode(0, $itemSelector, $type, false, self::TAX_REGULAR);
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
		return $this->_calcTaxByMode($amount, $itemSelector, $type, $round, self::TAX_REGULAR_FOR_AMOUNT);
	}

	/**
	 * @param Varien_Object $itemSelector
	 * @param string $type One of the values in self::_typeMap
	 * @return float the total tax amount for any discounts
	 */
	public function getDiscountTax(Varien_Object $itemSelector, $type='merchandise')
	{
		return $this->_calcTaxByMode(0, $itemSelector, $type, false, self::TAX_DISCOUNT);
	}

	/**
	 * @param float $amount The amount to calculate tax on
	 * @param Varien_Object $itemSelector
	 * @param string $type One of the values in self::_typeMap
	 * @param boolean $round Whether to round the result
	 * @return float the total tax amount for any discounts
	 */
	public function getDiscountTaxForAmount($amount, Varien_Object $itemSelector, $type='merchandise', $round=true)
	{
		return $this->_calcTaxByMode($amount, $itemSelector, $type, $round, self::TAX_DISCOUNT_FOR_AMOUNT);
	}

	/**
	 * if $quote is null and a current request exists, return the existing request.
	 * if $quote is not null return a new request using the quote's data.
	 * otherwise return a new invalid request.
	 * @param Mage_Sales_Model_Quote $quote
	 * @return TrueAction_Eb2cTax_Model_Request a tax request object
	 */
	public function getTaxRequest(Mage_Sales_Model_Quote $quote=null)
	{
		if ($quote) {
			// delete old response/request
			$this->unsTaxResponse();
			return Mage::getModel('eb2ctax/request', array('quote_id' => $quote->getId()));
		}
		$response = $this->getTaxResponse();
		return $response ? $response->getRequest() : Mage::getModel('eb2ctax/request');
	}

	/**
	 * store the tax response from eb2c
	 * @param TrueAction_Eb2cTax_Model_Response $response
	 * @return self
	 */
	public function setTaxResponse(TrueAction_Eb2cTax_Model_Response $response=null)
	{
		Mage::getSingleton('checkout/session')->setEb2cTaxResponse($response);
		return $this->setData('tax_response', $response);
	}

	/**
	 * Unset the tax response from eb2c
	 * @return self
	 */
	public function unsTaxResponse()
	{
		Mage::getSingleton('checkout/session')->unsEb2cTaxResponse();
		return $this->unsetData('tax_response');
	}

	/**
	 * return the response data for the specified item.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @param  Mage_Salse_Model_Quote_Address $address
	 * @return TrueAction_Eb2cTax_Model_Response_OrderItem | null
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
					$id      = "{$code}-{$taxRate}";
					if (isset($result[$id])) {
						$group = $result[$id];
					} else {
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
	public function getRateRequest($shippingAddress=null, $billingAddress=null, $customerTaxClass='', $store=null)
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
