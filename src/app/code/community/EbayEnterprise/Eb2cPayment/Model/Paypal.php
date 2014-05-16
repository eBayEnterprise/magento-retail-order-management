<?php
class EbayEnterprise_Eb2cPayment_Model_Paypal extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		$this->_init('eb2cpayment/paypal');
	}

	/**
	 * Load quote id
	 *
	 * @param   int $customerEmail
	 * @return  EbayEnterprise_Eb2cPayment_Model_Paypal
	 */
	public function loadByQuoteId($quoteId)
	{
		$this->_getResource()->loadByQuoteId($this, $quoteId);
		return $this;
	}
}