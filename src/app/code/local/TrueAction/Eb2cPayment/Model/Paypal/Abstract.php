<?php
abstract class TrueAction_Eb2cPayment_Model_Paypal_Abstract
{
	const STORED_FIELD_PREFIX = 'eb2c_paypal';
	/**
	 * Do paypal express checkout from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to do express paypal checkout for in eb2c
	 * @return string the eb2c response to the request
	 */
	public function processExpressCheckout(Mage_Sales_Model_Quote $quote)
	{
		$helper = Mage::helper('eb2cpayment');
		$response = Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(TrueAction_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$this->_buildRequest($quote),
				$helper->getConfigModel()->getConfig(static::XSD_FILE),
				$helper->getOperationUri(static::URI_KEY)
			);
		$this->_savePaymentData($this->parseResponse($response), $quote);
		return $response;
	}

	/**
	 * Save payment data to quote_payment.
	 *
	 * @param Varien_Object $checkoutObject response data
	 * @param Mage_Sales_Model_Quote $quote sales quote instantiated object
	 * @return TrueAction_Eb2cPayment_Model_Paypal|null
	 */
	public function _savePaymentData(Varien_Object $checkoutObject, Mage_Sales_Model_Quote $quote)
	{
		$storedData = $checkoutObject->getData(static::STORED_FIELD);
		if ($storedData !== '') {
			$saveToField = sprintf('%s_%s', static::STORED_FIELD_PREFIX, static::STORED_FIELD);
			$qId = $quote->getEntityId();
			return Mage::getModel('eb2cpayment/paypal')
				->loadByQuoteId($qId)
				->setQuoteId($qId)
				->setData($saveToField, $storedData)
				->save();
		}
		return null;
	}
}
