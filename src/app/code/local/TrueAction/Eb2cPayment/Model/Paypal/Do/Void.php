<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Do_Void extends Mage_Core_Model_Abstract
{
	protected $_helper;

	public function __construct()
	{
		$this->_helper = $this->_getHelper();
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2cpayment');
		}
		return $this->_helper;
	}

	/**
	 * Do paypal Void from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to do Void paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function doVoid($quote)
	{
		$paypalDoVoidResponseMessage = '';
		try{
			// build request
			$payPalDoVoidRequest = $this->buildPayPalDoVoidRequest($quote);

			// make request to eb2c for quote items PaypalDoVoid
			$paypalDoVoidResponseMessage = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_paypal_do_void'))
				->request($payPalDoVoidRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		return $paypalDoVoidResponseMessage;
	}

	/**
	 * Build  PaypalDoVoid request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalDoVoidRequest($quote)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$payPalDoVoidRequest = $domDocument->addElement('PayPalDoVoidRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$payPalDoVoidRequest->setAttribute('requestId', $this->_getHelper()->getRequestId($quote->getEntityId()));
		$payPalDoVoidRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);

		$payPalDoVoidRequest->createChild(
			'CurrencyCode',
			(string) $quote->getQuoteCurrencyCode()
		);

		return $domDocument;
	}

	/**
	 * Parse PayPal DoVoid reply xml.
	 *
	 * @param string $payPalDoVoidReply the xml response from eb2c
	 *
	 * @return array, an associative array of response data
	 */
	public function parseResponse($payPalDoVoidReply)
	{
		$checkoutData = array();
		if (trim($payPalDoVoidReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($payPalDoVoidReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$orderId = $checkoutXpath->query('//a:OrderId');
			if ($orderId->length) {
				$checkoutData['orderId'] = (int) $orderId->item(0)->nodeValue;
			}

			$responseCode = $checkoutXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$checkoutData['responseCode'] = (string) $responseCode->item(0)->nodeValue;
			}
		}

		return $checkoutData;
	}
}
