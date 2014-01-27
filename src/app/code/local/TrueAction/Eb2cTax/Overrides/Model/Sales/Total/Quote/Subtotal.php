<?php
/**
 * Calculate items and address amounts including/excluding tax
 */
class TrueAction_Eb2cTax_Overrides_Model_Sales_Total_Quote_Subtotal extends Mage_Tax_Model_Sales_Total_Quote_Subtotal
{
	/**
	 * Calculate item price including/excluding tax, row total including/excluding tax
	 * and subtotal including/excluding tax.
	 * Determine discount price if needed
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @return  Mage_Tax_Model_Sales_Total_Quote_Subtotal
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		Mage::dispatchEvent('eb2ctax_subtotal_collect_before', array(
			'address' => $address,
			'quote' => $address->getQuote()
		));
		if ($this->_calculator->hasCalculationTrigger()) {
			Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);
			Mage::log('calculating tax subtotal', Zend_Log::DEBUG);
			$this->_store   = $address->getQuote()->getStore();
			$this->_address = $address;
			$this->_subtotalInclTax     = 0;
			$this->_baseSubtotalInclTax = 0;
			$this->_subtotal            = 0;
			$this->_baseSubtotal        = 0;

			$address->setSubtotalInclTax(0);
			$address->setBaseSubtotalInclTax(0);
			$address->setTotalAmount('subtotal', 0);
			$address->setBaseTotalAmount('subtotal', 0);

			$items = (array) $this->_getAddressItems($address);
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$this->_applyTaxes($child, $address);
					}
					$this->_recalculateParent($item);
				} else {
					$this->_applyTaxes($item, $address);
				}
				$this->_addSubtotalAmount($address, $item);
			}
		}
		return $this;
	}

	/**
	 * Calculate item price and row total including/excluding tax based on unit price rounding level
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param TrueAction_Eb2cTax_Model_Response_OrderItem $itemResponse
	 * @return Mage_Tax_Model_Sales_Total_Quote_Subtotal
	 */
	protected function _applyTaxes($item, $address)
	{
		$helper          = $this->_helper;
		$qty             = $item->getTotalQty();
		$basePrice       = $baseTaxPrice = $this->_calculator->round($item->getBaseCalculationPriceOriginal());

		$baseSubtotal = $baseTaxSubtotal = $item->getBaseRowTotal();
		$itemSelector = new Varien_Object(array('item' => $item, 'address' => $address));

		$baseTax         = $this->_calculator->getTaxForAmount($basePrice, $itemSelector);
		$baseTaxPrice    = $basePrice + $baseTax;
		$baseRowTax      = $this->_calculator->getTax($itemSelector);
		$baseTaxSubtotal = $baseSubtotal + $baseRowTax;
		$baseTaxable     = $baseSubtotal;
		if ($item->hasCustomPrice()) {
			/**
			 * Initialize item original price before declaring custom price
			 */
			$item->getOriginalPrice();
			$item->setCustomPrice($this->_convertAmount($basePrice));
			$item->setBaseCustomPrice($basePrice);
		}
		$item->setTaxPercent(0);
		$item->setPrice($this->_convertAmount($basePrice));
		$item->setBasePrice($basePrice);
		$item->setRowTotal($this->_convertAmount($baseSubtotal));
		$item->setBaseRowTotal($baseSubtotal);
		$item->setPriceInclTax($this->_convertAmount($baseTaxPrice));
		$item->setBasePriceInclTax($baseTaxPrice);
		$item->setRowTotalInclTax($this->_convertAmount($baseTaxSubtotal));
		$item->setBaseRowTotalInclTax($baseTaxSubtotal);
		$item->setTaxableAmount($this->_convertAmount($baseTaxable));
		$item->setBaseTaxableAmount($baseTaxable);
		$item->setIsPriceInclTax(false);
		if ($this->_config->discountTax($this->_store)) {
			$item->setDiscountCalculationPrice($this->_convertAmount($baseTaxPrice));
			$item->setBaseDiscountCalculationPrice($baseTaxPrice);
		}
		return $this;
	}

	/**
	 * convert an amount to the quote's store currency
	 * @param  float $amount
	 * @return float
	 */
	protected function _convertAmount($amount)
	{
		$amount = $this->_store->convertPrice($amount);
		$amount = $this->_calculator->round($amount);
		return $amount;
	}
}
