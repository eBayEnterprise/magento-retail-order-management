<?php
/**
 * Override and disable the model that calculates shipping tax.
 */
class TrueAction_Eb2cTax_Overrides_Model_Sales_Total_Quote_Shipping extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->setCode('shipping');
		$this->_calculator = Mage::getSingleton('tax/calculation');
		$this->_config     = Mage::getSingleton('tax/config');
	}
}
