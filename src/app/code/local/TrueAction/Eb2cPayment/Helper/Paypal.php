<?php
class TrueAction_Eb2cPayment_Helper_Paypal
{
	/**
	 * Save payment data to quote_payment.
	 *
	 * @param Varien_Object $checkoutObject response data
	 * @param Mage_Sales_Model_Quote $quote sales quote instantiated object
	 * @return TrueAction_Eb2cPayment_Model_Paypal|null
	 */
	public function savePaymentData(Varien_Object $checkoutObject, Mage_Sales_Model_Quote $quote)
	{
		$transactionId = $checkoutObject->getTransactionId();
		if ($transactionId !== '') {
			$qId = $quote->getEntityId();
			return Mage::getModel('eb2cpayment/paypal')
				->loadByQuoteId($qId)
				->setQuoteId($qId)
				->setEb2cPaypalTransactionId($transactionId)
				->save();
		}
		return null;
	}
}
