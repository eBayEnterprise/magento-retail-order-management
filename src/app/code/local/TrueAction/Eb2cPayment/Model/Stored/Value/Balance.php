<?php
class TrueAction_Eb2cPayment_Model_Stored_Value_Balance
{
	/**
	 * Get gift card balance from eb2c.
	 * @param string $pan, Either a raw PAN or a token representing a PAN
	 * @param string $pin, The personal identification number or code associated with a gift card or gift certificate.
	 * @return string the eb2c response to the request.
	 */
	public function getBalance($pan, $pin)
	{
		$responseMessage = '';
		// build request
		$requestDoc = $this->buildStoredValueBalanceRequest($pan, $pin);
		Mage::log(sprintf('[ %s ]: Making request with body: %s', __METHOD__, $requestDoc->saveXml()), Zend_Log::DEBUG);
		$hlpr = Mage::helper('eb2cpayment');
		// HACK: EBC-238
		// Replace the "GS" at the end of the url with the right tender type for the SVC.
		$uri = $hlpr->getSvcUri('get_gift_card_balance', $pan);
		if ($uri === '') {
			Mage::log(sprintf('[ %s ] pan "%s" is out of range of any configured tender type bin.', __CLASS__, $pan), Zend_Log::ERR);
			return '';
		}
		try {
			// make request to eb2c for Gift Card Balance
			$responseMessage = Mage::getModel('eb2ccore/api')
				->addData(array(
					'uri' => $uri,
					'xsd' => Mage::helper('eb2cpayment')->getConfigModel()->xsdFileStoredValueBalance
				))
				->request($requestDoc);
		} catch(Zend_Http_Client_Exception $e) {
			Mage::log(
				sprintf(
					'[ %s ] The following error has occurred while sending GetStoredValueBalance request to eb2c: (%s).',
					__CLASS__, $e->getMessage()
				),
				Zend_Log::ERR
			);
		}
		return $responseMessage;
	}

	/**
	 * Build gift card balance request.
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument to be sent as request to eb2c.
	 */
	public function buildStoredValueBalanceRequest($pan, $pin)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$storedValueBalanceRequest = $domDocument
			->addElement('StoredValueBalanceRequest', null, Mage::helper('eb2cpayment')->getXmlNs())
			->firstChild;
		// creating PaymentAccountUniqueId element
		$storedValueBalanceRequest->createChild(
			'PaymentAccountUniqueId',
			$pan,
			array('isToken' => 'false')
		);
		// add Pin
		$storedValueBalanceRequest->createChild(
			'Pin',
			(string) $pin
		);
		// add Pin
		$storedValueBalanceRequest->createChild(
			'CurrencyCode',
			'USD'
		);
		return $domDocument;
	}

	/**
	 * Parse gift card balance response xml.
	 * @param string $storeValueBalanceReply the xml response from eb2c
	 * @return array of response data
	 */
	public function parseResponse($storeValueBalanceReply)
	{
		$balanceData = array();
		if (trim($storeValueBalanceReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($storeValueBalanceReply);
			$balanceXpath = new DOMXPath($doc);
			$balanceXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodePaymentAccountUniqueId = $balanceXpath->query('//a:PaymentAccountUniqueId');
			$nodeResponseCode = $balanceXpath->query('//a:ResponseCode');
			$nodeBalanceAmount = $balanceXpath->query('//a:BalanceAmount');
			$balanceData = array(
				'paymentAccountUniqueId' => ($nodePaymentAccountUniqueId->length)? (string) $nodePaymentAccountUniqueId->item(0)->nodeValue : null,
				'responseCode' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'balanceAmount' => ($nodeBalanceAmount->length)? (float) $nodeBalanceAmount->item(0)->nodeValue : 0,
			);
		}
		return $balanceData;
	}
}
