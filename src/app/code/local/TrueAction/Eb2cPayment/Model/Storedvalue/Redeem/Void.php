<?php
class TrueAction_Eb2cPayment_Model_Storedvalue_Redeem_Void
{
	/**
	 * Get gift card redeem void from eb2c.
	 * @param string $pan, Either a raw PAN or a token representing a PAN
	 * @param string $pin, The personal identification number or code associated with a gift card or gift certificate.
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to redeem void
	 * @return string the eb2c response to the request.
	 */
	public function getRedeemVoid($pan, $pin, $entityId, $amount)
	{
		$responseMessage = '';
		// build request
		$requestDoc = $this->buildStoredValueRedeemVoidRequest($pan, $pin, $entityId, $amount);
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);
		$hlpr = Mage::helper('eb2cpayment');
		// HACK: EBC-238
		// Replace the "GS" at the end of the url with the right tender type for the SVC.
		$uri = $hlpr->getSvcUri('get_gift_card_redeem_void', $pan);
		if ($uri === '') {
			Mage::log(sprintf('[ %s ] pan "%s" is out of range of any configured tender type bin.', __CLASS__, $pan), Zend_Log::ERR);
			return '';
		}
		try {
			// make request to eb2c for Gift Card redeem void
			$responseMessage = Mage::getModel('eb2ccore/api')
				->setUri($uri)
				->setXsd($hlpr->getConfigModel()->xsdFileStoredValueVoidRedeem)
				->request($requestDoc);

		} catch(Zend_Http_Client_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending StoredValueRedeemVoid request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}

		return $responseMessage;
	}

	/**
	 * Build gift card Redeem Void request.
	 * @param string $pan, the payment account number
	 * @param string $pin, the personal identification number
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to Redeem Void
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildStoredValueRedeemVoidRequest($pan, $pin, $entityId, $amount)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$storedValueRedeemVoidRequest = $domDocument->addElement('StoredValueRedeemVoidRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$storedValueRedeemVoidRequest->setAttribute('requestId', Mage::helper('eb2cpayment')->getRequestId($entityId));

		// creating PaymentContent element
		$paymentContext = $storedValueRedeemVoidRequest->createChild('PaymentContext', null);

		// creating OrderId element
		$paymentContext->createChild('OrderId', $entityId);

		// creating PaymentAccountUniqueId element
		$paymentContext->createChild('PaymentAccountUniqueId', $pan, array('isToken' => 'false'));

		// add Pin
		$storedValueRedeemVoidRequest->createChild('Pin', (string) $pin);

		// add amount
		$storedValueRedeemVoidRequest->createChild('Amount', $amount, array('currencyCode' => 'USD'));

		return $domDocument;
	}

	/**
	 * Parse gift card Redeem Void response xml.
	 * @param string $storeValueRedeemVoidReply the xml response from eb2c
	 * @return array, an associative array of response data
	 */
	public function parseResponse($storeValueRedeemVoidReply)
	{
		$redeemVoidData = array();
		if (trim($storeValueRedeemVoidReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($storeValueRedeemVoidReply);
			$redeemVoidXpath = new DOMXPath($doc);
			$redeemVoidXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $redeemVoidXpath->query('//a:PaymentContext/a:OrderId');
			$nodePaymentAccountUniqueId = $redeemVoidXpath->query('//a:PaymentContext/a:PaymentAccountUniqueId');
			$nodeResponseCode = $redeemVoidXpath->query('//a:ResponseCode');
			$redeemVoidData = array(
				'orderId' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'paymentAccountUniqueId' => ($nodePaymentAccountUniqueId->length)? (string) $nodePaymentAccountUniqueId->item(0)->nodeValue : null,
				'responseCode' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue: null,
			);
		}
		return $redeemVoidData;
	}
}
