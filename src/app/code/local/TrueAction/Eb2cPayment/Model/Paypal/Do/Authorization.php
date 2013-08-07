<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Do_Authorization extends Mage_Core_Model_Abstract
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
	 * Do paypal Authorization from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to do Authorization paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function doAuthorization($quote)
	{
		$paypalDoAuthorizationResponseMessage = '';
		try{
			// build request
			$payPalDoAuthorizationRequest = $this->buildPayPalDoAuthorizationRequest($quote);

			// make request to eb2c for quote items PaypalDoAuthorization
			$paypalDoAuthorizationResponseMessage = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_paypal_do_authorization'))
				->request($payPalDoAuthorizationRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		return $paypalDoAuthorizationResponseMessage;
	}

	/**
	 * Build  PaypalDoAuthorization request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalDoAuthorizationRequest($quote)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$payPalDoAuthorizationRequest = $domDocument->addElement('PayPalDoAuthorizationRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$payPalDoAuthorizationRequest->setAttribute('requestId', $this->_getHelper()->getRequestId($quote->getEntityId()));
		$payPalDoAuthorizationRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);

		$payPalDoAuthorizationRequest->createChild(
			'Amount',
			(string) $quote->getBaseGrandTotal(),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);

		return $domDocument;
	}

	/**
	 * Parse PayPal DoAuthorization reply xml.
	 *
	 * @param string $payPalDoAuthorizationReply the xml response from eb2c
	 *
	 * @return Varien_Object, an object of response data
	 */
	public function parseResponse($payPalDoAuthorizationReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalDoAuthorizationReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($payPalDoAuthorizationReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$orderId = $checkoutXpath->query('//a:OrderId');
			if ($orderId->length) {
				$checkoutObject->setOrderId((int) $orderId->item(0)->nodeValue);
			}

			$responseCode = $checkoutXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$checkoutObject->setResponseCode((string) $responseCode->item(0)->nodeValue);
			}

			$authorizationInfo = $checkoutXpath->query('//a:AuthorizationInfo');
			if ($authorizationInfo->length) {
				$paymentStatus = $checkoutXpath->query('//a:AuthorizationInfo/a:PaymentStatus');
				if ($paymentStatus->length) {
					$checkoutObject->setPaymentStatus((string) $paymentStatus->item(0)->nodeValue);
				}

				$pendingReason = $checkoutXpath->query('//a:AuthorizationInfo/a:PendingReason');
				if ($pendingReason->length) {
					$checkoutObject->setPendingReason((string) $pendingReason->item(0)->nodeValue);
				}

				$reasonCode = $checkoutXpath->query('//a:AuthorizationInfo/a:ReasonCode');
				if ($reasonCode->length) {
					$checkoutObject->setReasonCode((string) $reasonCode->item(0)->nodeValue);
				}
			}
		}

		return $checkoutObject;
	}
}
