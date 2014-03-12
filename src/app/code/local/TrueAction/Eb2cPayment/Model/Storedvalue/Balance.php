<?php
class TrueAction_Eb2cPayment_Model_Storedvalue_Balance
{
	/**
	 * Get gift card balance from eb2c.
	 *
	 * @param string $pan either a raw PAN or a token representing a PAN
	 * @param string $pin personal identification number or code associated with a gift card or gift certificate
	 * @return string eb2c response to the request
	 */
	public function getBalance($pan, $pin)
	{
		$hlpr = Mage::helper('eb2cpayment');
		// HACK: EBC-238
		// Replace the "GS" at the end of the url with the right tender type for the SVC.
		$uri = $hlpr->getSvcUri('get_gift_card_balance', $pan);
		if ($uri === '') {
			Mage::log(sprintf('[ %s ] pan "%s" is out of range of any configured tender type bin.', __CLASS__, $pan), Zend_Log::ERR);
			return '';
		}
		return Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath('eb2cpayment/api_status_handler')
			->request(
				$this->buildStoredValueBalanceRequest($pan, $pin),
				$hlpr->getConfigModel()->xsdFileStoredValueBalance,
				$uri
			);
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
