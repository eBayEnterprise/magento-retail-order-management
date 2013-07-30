<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Tax totals calculation model
 */
class TrueAction_Eb2cTax_Overrides_Model_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
	/**
	 * Collect tax totals for quote address
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		// save the address and clear out the tax total fields
		Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);
		// clear out the hiddenTax related fields
		$this->_resetHiddenTaxes($address);

		$this->_roundingDeltas      = array();
		$this->_baseRoundingDeltas  = array();
		$this->_hiddenTaxes         = array();
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
		return $this;
	}

	/**
	 * Calculate address total tax based on row total
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @param   Varien_Object $taxRateRequest
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calcTaxForAddress(Mage_Sales_Model_Quote_Address $address) {
		$items          = $this->_getAddressItems($address);
		$itemTaxGroups  = array();
		$itemSelector   = new Varien_Object(array('address' => $address));
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
	 *
	 * @param   Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param   float $rate
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calcTaxForItem($itemSelector)
	{
		$item           = $itemSelector->getItem();
		$inclTax        = $item->getIsPriceInclTax();
		$subtotal       = $taxSubtotal     = $item->getTaxableAmount();
		$baseSubtotal   = $baseTaxSubtotal = $item->getBaseTaxableAmount();
		// default to 0 since there isn't any one rate
		$item->setTaxPercent(0);
		$hiddenTax      = null;
		$baseHiddenTax  = null;

		if ($this->_helper->getApplyTaxAfterDiscount($this->_store)) {
				// tax only what you pay
				$rowTax             = $this->_calculator->getTax($itemSelector);
				$discountAmount     = $item->getDiscountAmount();
				$rowTaxDiscount     = $this->_calculator->getDiscountTax($itemSelector);

				$baseRowTax         = $this->_calculator->getTaxForAmount($baseSubtotal, $itemSelector);
				$baseDiscountAmount = $item->getBaseDiscountAmount();
				$baseRowTaxDiscount = $this->_calculator->getDiscountTaxForAmount($baseDiscountAmount, $itemSelector);
				$this->_processHiddenTax($rowTaxDiscount, $baseRowTaxDiscount, $item);

				// adjust the subtotal due to the discount amounts
				$subtotal     = $subtotal - $discountAmount;
				$baseSubtotal = $baseSubtotal - $baseDiscountAmount;
				// adjust the tax amounts due to the discounts.
				$rowTax       = $rowTax - $rowTaxDiscount;
				$baseRowTax   = $baseRowTax - $baseRowTaxDiscount;
		} else {
				// tax the full itemprice
				$rowTax     = $this->_calculator->getTax($itemSelector);
				$baseRowTax = $this->_calculator->getTaxForAmount($baseSubtotal, $itemSelector);
		}

		$item->setTaxAmount(max(0, $rowTax));
		$item->setBaseTaxAmount(max(0, $baseRowTax));

		$rowTotalInclTax = $item->getRowTotalInclTax();
		if (!isset($rowTotalInclTax)) {
			$taxCompensation = $item->getDiscountTaxCompensation() ? $item->getDiscountTaxCompensation() : 0;
			$item->setRowTotalInclTax($subtotal + $rowTax + $taxCompensation);
			$item->setBaseRowTotalInclTax($baseSubtotal + $baseRowTax + $item->getBaseDiscountTaxCompensation());
		}

		return $this;
	}

	/**
	 * Collect applied tax rates information on address level
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @param   array $applied
	 * @param   float $amount
	 * @param   float $baseAmount
	 * @param   float $rate
	 */
	protected function _saveAppliedTaxes(Mage_Sales_Model_Quote_Address $address,
										 $applied, $amount, $baseAmount, $rate)
	{
		$previouslyAppliedTaxes = $address->getAppliedTaxes();
		$process = count($previouslyAppliedTaxes);

		foreach ($applied as $row) {
			if ($row['percent'] == 0) {
				continue;
			}
			if (!isset($previouslyAppliedTaxes[$row['id']])) {
				$row['process']     = $process++;
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
	 * @param  Mage_Sales_Model_Quote_Address $address
	 */
	protected function _resetHiddenTaxes(Mage_Sales_Model_Quote_Address $address)
	{
		$address->setTotalAmount('hidden_tax', 0);
		$address->setBaseTotalAmount('hidden_tax', 0);
		$address->setTotalAmount('shipping_hidden_tax', 0);
		$address->setBaseTotalAmount('shipping_hidden_tax', 0);
	}

	/**
	 * Process hidden taxes for items and shippings (in accordance with hidden tax type)
	 *
	 * @return void
	 */
	protected function _processHiddenTax($amount, $baseAmount, $item)
	{
		if ($amount || $baseAmount) {
			$hiddenTax      = $this->_calculator->round($amount);
			$baseHiddenTax  = $this->_calculator->round($baseAmount);
			$item->setHiddenTaxAmount(max(0, $hiddenTax));
			$item->setBaseHiddenTaxAmount(max(0, $baseHiddenTax));
			$this->_getAddress()->addTotalAmount('hidden_tax', $item->getHiddenTaxAmount());
			$this->_getAddress()->addBaseTotalAmount('hidden_tax', $item->getBaseHiddenTaxAmount());
		}
	}

	/**
	 * ensures the taxes are calculated chronologically after the discounts.
	 * see the tax and sales etc/config.xml
	 *
	 * @param   array $config
	 * @param   store $store
	 * @return  array
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
	 *
	 * @param $store
	 * @return bool
	 * @codeCoverageIgnore
	 */
	protected function _usePriceIncludeTax($store)
	{
		return false;
	}
}
