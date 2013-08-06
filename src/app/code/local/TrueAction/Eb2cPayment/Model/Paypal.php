<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		$this->_init('eb2cpayment/paypal');
	}

	/**
	 * Load quote id
	 *
	 * @param   int $customerEmail
	 * @return  TrueAction_Eb2cPayment_Model_Paypal
	 */
	public function loadByQuoteId($quoteId)
	{
		$this->_getResource()->loadByQuoteId($this, $quoteId);
		return $this;
	}
}