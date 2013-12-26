<?php
/**
 * Tax totals calculation model
 */
class TrueAction_Eb2cTax_Overrides_Model_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
	/**
	 * running total of tax amount for the address.
	 */
	protected $_shippingTaxSubTotals = array();
	/**
	 * running total of tax amount for the address.
	 */
	protected $_shippingTaxTotals = array();
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->setCode('tax');
		$this->_calculator = Mage::getSingleton('tax/calculation');
		$this->_config = Mage::getSingleton('tax/config');
		$this->_weeeHelper = Mage::helper('weee');
	}
	/**
	 * Collect tax totals for quote address
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return Mage_Tax_Model_Sales_Total_Quote
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		Mage::log(__METHOD__, Zend_Log::DEBUG);
		if ($this->_calculator->hasCalculationTrigger()) {
			Mage::log('calculating', Zend_Log::DEBUG);
			$this->_initBeforeCollect($address);
			$this->_calculator->unsCalculationTrigger();
			$this->_roundingDeltas = array();
			$this->_baseRoundingDeltas = array();
			$this->_hiddenTaxes = array();
			$address->setShippingTaxAmount(0);
			$address->setBaseShippingTaxAmount(0);
			// zero amounts for the address.
			$this->_store = $address->getQuote()->getStore();
			$customer = $address->getQuote()->getCustomer();
			if (!$address->getAppliedTaxesReset()) {
				$address->setAppliedTaxes(array());
			}
			$items = $this->_getAddressItems($address);
			if (!count($items)) {
				return $this;
			}
			$this->_calcTaxForAddress($address);
			// adds extra tax amounts to the address
			$this->_addAmount($address->getExtraTaxAmount());
			$this->_addBaseAmount($address->getBaseExtraTaxAmount());
			//round total amounts in address
			$this->_roundTotals($address);
		}
		return $this;
	}
	/**
	 * Calculate address total tax based on row total
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param Varien_Object $taxRateRequest
	 * @return Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calcTaxForAddress(Mage_Sales_Model_Quote_Address $address)
	{
		$items = $this->_getAddressItems($address);
		$itemTaxGroups = array();
		$itemSelector = new Varien_Object(array('address' => $address));
		foreach ($items as $item) {
			if ($item->getParentItem()) {
				continue;
			}
			$dummyRate = 0;
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$itemSelector->setItem($child);
					$this->_calcTaxForItem($itemSelector);
					$this->_addAmount($child->getTaxAmount());
					$this->_addBaseAmount($child->getBaseTaxAmount());
					$applied = $this->_calculator->getAppliedRates($itemSelector);
					// need to come up with a similar concept (ie hasTaxes or some such)
					if (!empty($applied)) {
						$itemTaxGroups[$child->getId()] = $applied;
					}
					$this->_saveAppliedTaxes(
						$address,
						$applied,
						$child->getTaxAmount(),
						$child->getBaseTaxAmount(),
						$dummyRate
					);
					$child->setTaxRates($applied);
				}
				$this->_recalculateParent($item);
			} else {
				$itemSelector->setItem($item);
				$this->_calcTaxForItem($itemSelector);
				$this->_addAmount($item->getTaxAmount());
				$this->_addBaseAmount($item->getBaseTaxAmount());
				$applied = $this->_calculator->getAppliedRates($itemSelector);
				if (!empty($applied)) {
					$itemTaxGroups[$item->getId()] = $applied;
				}
				$this->_saveAppliedTaxes(
					$address,
					$applied,
					$item->getTaxAmount(),
					$item->getBaseTaxAmount(),
					$dummyRate
				);
				$item->setTaxRates($applied);
			}
		}
		if ($address->getQuote()->getTaxesForItems()) {
			$itemTaxGroups += $address->getQuote()->getTaxesForItems();
		}
		$address->getQuote()->setTaxesForItems($itemTaxGroups);
		return $this;
	}
	/**
	 * Calculate item tax amount based on row total
	 * @param Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param float $rate
	 * @return Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calcTaxForItem($itemSelector)
	{
		$item = $itemSelector->getItem();
		$inclTax = $item->getIsPriceInclTax();
		$baseSubtotal = $baseTaxSubtotal = $item->getBaseTaxableAmount();
		$subtotal = $taxSubtotal = $item->getTaxableAmount();
		// default to 0 since there isn't any one rate
		$item->setTaxPercent(0);
		$baseHiddenTax = null;
		$hiddenTax = null;
		if (Mage::helper('eb2ctax')->getApplyTaxAfterDiscount()) { // tax only what you pay
			$baseDiscountAmount = $item->getBaseDiscountAmount();
			// calculate the full tax amount
			$baseRowTax = $this->_calculator->getTax($itemSelector, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
			// amount to adjust tax due to discount.
			$baseRowTaxDiscount = $this->_calculator->getDiscountTax($itemSelector, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
			// record the tax adjustment amounts
			$this->_processItemHiddenTax($baseRowTaxDiscount, $item);
			// adjust the tax amounts due to the discounts.
			$baseRowTax = $baseRowTax - $baseRowTaxDiscount;
			// adjust the subtotal due to the discount amounts
			$baseSubtotal = $baseSubtotal - $baseDiscountAmount;
		} else { // tax the full itemprice
			$baseRowTax = $this->_calculator->getTax($itemSelector, TrueAction_Eb2cTax_Overrides_Model_Calculation::MERCHANDISE_TYPE);
		}
		$item->setBaseTaxAmount(max(0, $baseRowTax));
		$rowTax = $this->_convertAmount($baseRowTax);
		$item->setTaxAmount(max(0, $rowTax));
		$baseRowTotalInclTax = $item->getBaseRowTotalInclTax();
		if (!isset($baseRowTotalInclTax)) {
			$baseRowTotalInclTax = $baseSubtotal + $baseRowTax;
			$rowTotalinclTax = $this->_convertAmount($baseRowTotalInclTax);
			$item->setBaseRowTotalInclTax($baseRowTotalInclTax);
			$item->setRowTotalInclTax($rowTotalinclTax);
			$item->setBaseDiscountTaxCompensation(0);
			$item->setDiscountTaxCompensation(0);
		}
		return $this;
	}
	protected function _calcShippingTaxes($itemSelector)
	{
		$address = $itemSelector->getAddress();
		$addressId = $address->getId();
		$isPriceInclTax = $this->_isShippingPriceTaxInclusive();
		$address->setIsShippingInclTax($isPriceInclTax || $address->getIsShippingInclTax());
		$baseTaxable = $baseShipping = $baseTaxShipping = $address->getBaseShippingAmount();
		$rate = 0;
		$duty = $this->_calculator->getTax($itemSelector, TrueAction_Eb2cTax_Overrides_Model_Calculation::DUTY_TYPE);
		$baseTax = $this->_calculator->getTax($itemSelector, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE) + $duty;
		$this->_shippingTaxSubTotals[$addressId] += $baseTax;
		$baseRuninngShippingTax = $this->_shippingTaxSubTotals[$addressId];
		$baseTaxShipping = $baseShipping + $baseRuninngShippingTax;
		$address->setBaseTotalAmount('shipping', $baseShipping);
		$address->setBaseShippingInclTax($baseTaxShipping);
		$address->setBaseShippingTaxable($baseTaxable);
		$taxable = $shipping = $taxShipping = $address->getShippingAmount();
		$taxShipping = $this->_convertAmount($baseTaxShipping);
		$address->setShippingInclTax($taxShipping);
		$address->setTotalAmount('shipping', $shipping);
		$address->setShippingTaxable($taxable);
		// process final shipping tax data
		if (Mage::helper('eb2ctax')->getApplyTaxAfterDiscount()) {
			$baseTaxDiscount = $this->_calculator->getDiscountTax($itemSelector, TrueAction_Eb2cTax_Overrides_Model_Calculation::SHIPPING_TYPE);
			$this->_processShippingHiddenTax($baseTaxDiscount, $address);
			$baseTax -= $baseTaxDiscount;
			$tax = $this->_convertAmount($baseTax);
			$address->setBaseShippingAmountForDiscount($baseShipping + $baseTax);
			$address->setShippingAmountForDiscount($shipping + $tax);
		}
		$this->_shippingTaxTotals[$addressId] += $baseTax;
		$baseTaxes = $this->_shippingTaxTotals[$addressId];
		$taxes = $this->_convertAmount($baseTaxes);
		$address->setBaseShippingTaxAmount(max(0, $baseTaxes));
		$address->setShippingTaxAmount(max(0, $taxes));
		return $this;
	}
	/**
	 * convert an amount to the quote's store currency
	 * @param float $amount
	 * @return float
	 */
	protected function _convertAmount($amount)
	{
		$amount = $this->_store->convertPrice($amount);
		$amount = $this->_calculator->round($amount);
		return $amount;
	}
	/**
	 * initialize totals data before collecting the totals.
	 * @param Mage_Sales_Model_Quote_Address $address
	 */
	protected function _initBeforeCollect($address)
	{
		// save the address and clear out the tax total fields
		Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);
		$this->_shippingTaxTotals[$address->getId()] = 0.0;
		$this->_shippingTaxSubTotals[$address->getId()] = 0.0;
		// clear out the hiddenTax related fields
		$this->_resetHiddenTaxes($address);
	}
	/**
	 * return true if the shipping price includes VAT.
	 * return false otherwise.
	 * @return boolean
	 */
	protected function _isShippingPriceTaxInclusive()
	{
		return false;
	}
	/**
	 * Collect applied tax rates information on address level
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param array $applied
	 * @param float $amount
	 * @param float $baseAmount
	 * @param float $rate
	 */
	protected function _saveAppliedTaxes(
		Mage_Sales_Model_Quote_Address $address,
		$applied,
		$amount,
		$baseAmount,
		$rate
	)
	{
		$previouslyAppliedTaxes = $address->getAppliedTaxes();
		$process = count($previouslyAppliedTaxes);
		foreach ($applied as $row) {
			// key 'base_amount' is needed to calculate baseRealAmount in tax/observer class
			// also to be referenced as a key in tax/observer class salesEventOrderAfterSave method
			$row['base_amount'] = (float) $baseAmount;
			if ($row['percent'] == 0) {
				continue;
			}
			if (!isset($previouslyAppliedTaxes[$row['id']])) {
				$row['process'] = $process++;
				$previouslyAppliedTaxes[$row['id']] = $row;
			}
			if (!$previouslyAppliedTaxes[$row['id']]['amount']) {
				unset($previouslyAppliedTaxes[$row['id']]);
			}
		}
		$address->setAppliedTaxes($previouslyAppliedTaxes);
	}
	/**
	 * zero out the totalamout, basetotalamount fields for hidden taxes.
	 * @param Mage_Sales_Model_Quote_Address $address
	 */
	protected function _resetHiddenTaxes(Mage_Sales_Model_Quote_Address $address)
	{
		$address->setTotalAmount('hidden_tax', 0.0);
		$address->setBaseTotalAmount('hidden_tax', 0.0);
		$address->setTotalAmount('shipping_hidden_tax', 0.0);
		$address->setBaseTotalAmount('shipping_hidden_tax', 0.0);
	}
	/**
	 * Process hidden taxes for items (in accordance with hidden tax type)
	 * @return void
	 */
	protected function _processItemHiddenTax($baseAmount, $item)
	{
		if ($baseAmount) {
			$amount = $this->_convertAmount($baseAmount);
			$item->setHiddenTaxAmount(max(0.0, $amount));
			$item->setBaseHiddenTaxAmount(max(0.0, $baseAmount));
			$this->_getAddress()->addTotalAmount('hidden_tax', $item->getHiddenTaxAmount());
			$this->_getAddress()->addBaseTotalAmount('hidden_tax', $item->getBaseHiddenTaxAmount());
		}
	}
	/**
	 * Process hidden taxes for shipping (in accordance with hidden tax type)
	 * @return void
	 */
	protected function _processShippingHiddenTax($baseAmount, $address)
	{
		if ($baseAmount) {
			$amount = $this->_convertAmount($baseAmount);
			$address->addTotalAmount('shipping_hidden_tax', $amount);
			$address->addBaseTotalAmount('shipping_hidden_tax', $baseAmount);
		}
	}
	/**
	 * ensures the taxes are calculated chronologically after the discounts.
	 * see the tax and sales etc/config.xml
	 * @param array $config
	 * @param store $store
	 * @return array
	 */
	public function processConfigArray($config, $store)
	{
		$config['after'][] = 'discount';
		return $config;
	}
	/**
	 * Check if price include tax should be used for calculations.
	 * We are using price include tax just in case when catalog prices are including tax
	 * and customer tax request is same as store tax request
	 * @param $store
	 * @return bool
	 * @codeCoverageIgnore
	 */
	protected function _usePriceIncludeTax($store)
	{
		return false;
	}
}
