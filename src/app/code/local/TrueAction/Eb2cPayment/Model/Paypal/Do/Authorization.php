<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Do_Authorization extends Mage_Core_Model_Abstract
{
	/**
	 * Do paypal Authorization from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to do Authorization paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function doAuthorization($quote)
	{
		$responseMessage = '';
		try{
			// build request
			$requestDoc = $this->buildPayPalDoAuthorizationRequest($quote);
			Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);

			// make request to eb2c for quote items PaypalDoAuthorization
			$responseMessage = Mage::getModel('eb2ccore/api')
				->setUri(Mage::helper('eb2cpayment')->getOperationUri('get_paypal_do_authorization'))
				->setXsd(Mage::helper('eb2cpayment')->getConfigModel()->xsdFilePaypalDoAuth)
				->request($requestDoc);

		}catch(Exception $e){
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending Do paypal Authorization request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}

		return $responseMessage;
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
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalDoAuthorizationRequest = $domDocument->addElement('PayPalDoAuthorizationRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalDoAuthorizationRequest->setAttribute('requestId', Mage::helper('eb2cpayment')->getRequestId($quote->getEntityId()));
		$payPalDoAuthorizationRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);

		$payPalDoAuthorizationRequest->createChild(
			'Amount',
			sprintf('%.02f', $quote->getBaseGrandTotal()),
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
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalDoAuthorizationReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$nodePaymentStatus = $checkoutXpath->query('//a:AuthorizationInfo/a:PaymentStatus');
			$nodePendingReason = $checkoutXpath->query('//a:AuthorizationInfo/a:PendingReason');
			$nodeReasonCode = $checkoutXpath->query('//a:AuthorizationInfo/a:ReasonCode');
			$checkoutObject = new Varien_Object(
				array(
					'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue: 0,
					'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
					'payment_status' => ($nodePaymentStatus->length)? (string) $nodePaymentStatus->item(0)->nodeValue : null,
					'pending_reason' => ($nodePendingReason->length)? (string) $nodePendingReason->item(0)->nodeValue : null,
					'reason_code' => ($nodeReasonCode->length)? (string) $nodeReasonCode->item(0)->nodeValue : null,
				)
			);
		}

		return $checkoutObject;
	}
}
