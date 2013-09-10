<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Stored_Value_Balance extends Mage_Core_Model_Abstract
{
	protected $_helper;

	/**
	 * Initialize resource model
	 */
	protected function _construct()
	{
		$this->_helper = $this->_getHelper();
		return $this;
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
	 * Get gift card balance from eb2c.
	 *
	 * @param string $pan, Either a raw PAN or a token representing a PAN
	 * @param string $pin, The personal identification number or code associated with a gift card or gift certificate.
	 *
	 * @return string the eb2c response to the request.
	 */
	public function getBalance($pan, $pin)
	{
		$storeValueBalanceReply = '';
		try{
			// build request
			$storeValueBalanceRequest = $this->buildStoreValueBalanceRequest($pan, $pin);

			// make request to eb2c for Gift Card Balance
			$storeValueBalanceReply = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_gift_card_balance'))
				->request($storeValueBalanceRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		return $storeValueBalanceReply;
	}

	/**
	 * Build gift card balance request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildStoreValueBalanceRequest($pan, $pin)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$storeValueBalanceRequest = $domDocument->addElement('StoreValueBalanceRequest', null, $this->_getHelper()->getXmlNs())->firstChild;

		// creating PaymentAccountUniqueId element
		$storeValueBalanceRequest->createChild(
			'PaymentAccountUniqueId',
			$pan,
			array('isToken' => 'false')
		);

		// add Pin
		$storeValueBalanceRequest->createChild(
			'Pin',
			(string) $pin
		);

		// add Pin
		$storeValueBalanceRequest->createChild(
			'CurrencyCode',
			'USD'
		);

		return $domDocument;
	}

	/**
	 * Parse gift card balance response xml.
	 *
	 * @param string $storeValueBalanceReply the xml response from eb2c
	 *
	 * @return array, an associative array of response data
	 */
	public function parseResponse($storeValueBalanceReply)
	{
		$balanceData = array();
		if (trim($storeValueBalanceReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($storeValueBalanceReply);
			$balanceXpath = new DOMXPath($doc);
			$balanceXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$paymentAccountUniqueId = $balanceXpath->query('//a:PaymentAccountUniqueId');
			if ($paymentAccountUniqueId->length) {
				$balanceData['paymentAccountUniqueId'] = $paymentAccountUniqueId->item(0)->nodeValue;
			}

			$responseCode = $balanceXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$balanceData['responseCode'] = $responseCode->item(0)->nodeValue;
			}

			$balanceAmount = $balanceXpath->query('//a:BalanceAmount');
			if ($balanceAmount->length) {
				$balanceData['balanceAmount'] = $balanceAmount->item(0)->nodeValue;
			}
		}

		return $balanceData;
	}
}
