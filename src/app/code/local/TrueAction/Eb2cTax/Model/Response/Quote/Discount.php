<?php
/**
 * represents an OrderItem.
 * requires the following data to be passed to the constructor
 * @param  int                            type
 * @param  TrueAction_Dom_Element         node
 */
class TrueAction_Eb2cTax_Model_Response_Quote_Discount extends TrueAction_Eb2cTax_Model_Response_Quote
{
	protected function _construct()
	{
		$discount = $this->getNode();
		if ($discount) {
			$xpath = new DOMXPath($discount->ownerDocument);
			$xpath->registerNamespace('a', $discount->namespaceURI);
			// get discount id
			$this->setDiscountId($xpath->evaluate('string(./../../@id)', $discount));
			$this->setCalculateDutyFlag($xpath->evaluate('boolean(./../../@caclulateDuty)', $discount));
			$this->setAmount($xpath->evaluate('number(./../../a:Amount)', $discount));
		}
		// load the rest of the tax data for the quote
		parent::_construct();
	}
}
