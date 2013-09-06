<?php
class TrueAction_Eb2cTax_Overrides_Model_Total_Quote_Tax_Giftwrapping extends Enterprise_GiftWrapping_Model_Total_Quote_Tax_Giftwrapping
{
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		return $this;
	}
}
