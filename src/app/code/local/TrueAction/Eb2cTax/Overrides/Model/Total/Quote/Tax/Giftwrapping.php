<?php
class TrueAction_Eb2cTax_Overrides_Model_Total_Quote_Tax_Giftwrapping
	extends Enterprise_GiftWrapping_Model_Total_Quote_Tax_Giftwrapping
{
	/**
	 * override the default collect function to do nothing.
	 * @param  Mage_Sales_Model_Quote_Address $address address to collect totals for
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		return $this;
	}
}
