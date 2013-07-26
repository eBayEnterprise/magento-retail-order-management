<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Stored_Value_Redeem_Void extends Mage_Core_Model_Abstract
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
	 * Get gift card redeem void from eb2c.
	 *
	 * @param string $pan, Either a raw PAN or a token representing a PAN
	 * @param string $pin, The personal identification number or code associated with a gift card or gift certificate.
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to redeem void
	 *
	 * @return string the eb2c response to the request.
	 */
	public function getRedeemVoid($pan, $pin, $entityId, $amount)
	{
		$storeValueRedeemVoidReply = '';
		try{
			// build request
			$storeValueRedeemVoidRequest = $this->buildStoreValueRedeemVoidRequest($pan, $pin, $entityId, $amount);

			// make request to eb2c for Gift Card redeem void
			$storeValueRedeemVoidReply = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_gift_card_redeem_void'))
				->request($storeValueRedeemVoidRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		return $storeValueRedeemVoidReply;
	}

	/**
	 * Build gift card Redeem Void request.
	 *
	 * @param string $pan, the payment account number
	 * @param string $pin, the personal identification number
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to Redeem Void
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildStoreValueRedeemVoidRequest($pan, $pin, $entityId, $amount)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$storeValueRedeemVoidRequest = $domDocument->addElement('StoreValueRedeemVoidRequest', null, $this->_getHelper()->getXmlNs())->firstChild;

		// creating PaymentContent element
		$paymentContext = $storeValueRedeemVoidRequest->createChild(
			'PaymentContext',
			null
		);

		// creating OrderId element
		$paymentContext->createChild(
			'OrderId',
			$entityId
		);

		// creating PaymentAccountUniqueId element
		$paymentContext->createChild(
			'PaymentAccountUniqueId',
			$pan,
			array('isToken' => "false")
		);

		// add Pin
		$storeValueRedeemVoidRequest->createChild(
			'Pin',
			(string) $pin
		);

		// add amount
		$storeValueRedeemVoidRequest->createChild(
			'Amount',
			$amount,
			array('currencyCode' => "USD")
		);

		return $domDocument;
	}

	/**
	 * Parse gift card Redeem Void response xml.
	 *
	 * @param string $storeValueRedeemVoidReply the xml response from eb2c
	 *
	 * @return array, an associative array of response data
	 */
	public function parseResponse($storeValueRedeemVoidReply)
	{
		$redeemVoidData = array();
		if (trim($storeValueRedeemVoidReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($storeValueRedeemVoidReply);
			$redeemVoidXpath = new DOMXPath($doc);
			$redeemVoidXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$orderId = $redeemVoidXpath->query('//a:PaymentContext/a:OrderId');
			if ($orderId->length) {
				$redeemVoidData['orderId'] = (int) $orderId->item(0)->nodeValue;
			}

			$paymentAccountUniqueId = $redeemVoidXpath->query('//a:PaymentContext/a:PaymentAccountUniqueId');
			if ($paymentAccountUniqueId->length) {
				$redeemVoidData['paymentAccountUniqueId'] = (string) $paymentAccountUniqueId->item(0)->nodeValue;
			}

			$responseCode = $redeemVoidXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$redeemVoidData['responseCode'] = (string) $responseCode->item(0)->nodeValue;
			}
		}

		return $redeemVoidData;
	}
}
