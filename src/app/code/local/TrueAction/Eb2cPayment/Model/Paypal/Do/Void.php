<?php
class TrueAction_Eb2cPayment_Model_Paypal_Do_Void extends Mage_Core_Model_Abstract
{
	/**
	 * Do paypal Void from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to do Void paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function doVoid($quote)
	{
		$responseMessage = '';
		// build request
		$requestDoc = $this->buildPayPalDoVoidRequest($quote);
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);

		try{
			// make request to eb2c for quote items PaypalDoVoid
			$responseMessage = Mage::getModel('eb2ccore/api')
				->setUri(Mage::helper('eb2cpayment')->getOperationUri('get_paypal_do_void'))
				->setXsd(Mage::helper('eb2cpayment')->getConfigModel()->xsdFilePaypalVoidAuth)
				->request($requestDoc);

		} catch(Zend_Http_Client_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending Do paypal Void request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}

		return $responseMessage;
	}

	/**
	 * Build PaypalDoVoid request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalDoVoidRequest($quote)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalDoVoidRequest = $domDocument->addElement('PayPalDoVoidRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalDoVoidRequest->setAttribute('requestId', Mage::helper('eb2cpayment')->getRequestId($quote->getEntityId()));
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
	 * @return Varien_Object, an object of response data
	 */
	public function parseResponse($payPalDoVoidReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalDoVoidReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalDoVoidReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$checkoutObject = new Varien_Object(
				array(
					'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
					'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				)
			);
		}

		return $checkoutObject;
	}
}
