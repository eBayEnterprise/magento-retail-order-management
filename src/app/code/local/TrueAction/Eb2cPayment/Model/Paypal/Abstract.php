<?php
abstract class TrueAction_Eb2cPayment_Model_Paypal_Abstract
{
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
		Mage::helper('eb2cpayment/paypal')->savePaymentData($this->parseResponse($response), $quote);
		return $response;
	}
}
