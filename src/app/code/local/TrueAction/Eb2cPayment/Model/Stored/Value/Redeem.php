<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Stored_Value_Redeem extends Mage_Core_Model_Abstract
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
	 * Get gift card Redeem from eb2c.
	 *
	 * @param string $pan, Either a raw PAN or a token representing a PAN
	 * @param string $pin, The personal identification number or code associated with a gift card or gift certificate.
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to redeem
	 *
	 * @return string the eb2c response to the request.
	 */
	public function getRedeem($pan, $pin, $entityId, $amount)
	{
		$storeValueRedeemReply = '';
		try{
			// build request
			$storeValueRedeemRequest = $this->buildStoreValueRedeemRequest($pan, $pin, $entityId, $amount);

			// make request to eb2c for Gift Card Redeem
			$storeValueRedeemReply = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_gift_card_redeem'))
				->request($storeValueRedeemRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		return $storeValueRedeemReply;
	}

	/**
	 * Build gift card Redeem request.
	 *
	 * @param string $pan, the payment account number
	 * @param string $pin, the personal identification number
	 * @param string $entityId, the sales/quote entity_id value
	 * @param string $amount, the amount to redeem
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildStoreValueRedeemRequest($pan, $pin, $entityId, $amount)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$storeValueRedeemRequest = $domDocument->addElement('StoreValueRedeemRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$storeValueRedeemRequest->setAttribute('requestId', $this->_getHelper()->getRequestId($entityId));

		// creating PaymentContent element
		$paymentContext = $storeValueRedeemRequest->createChild(
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
		$storeValueRedeemRequest->createChild(
			'Pin',
			(string) $pin
		);

		// add amount
		$storeValueRedeemRequest->createChild(
			'Amount',
			$amount,
			array('currencyCode' => "USD")
		);

		return $domDocument;
	}

	/**
	 * Parse gift card Redeem response xml.
	 *
	 * @param string $storeValueRedeemReply the xml response from eb2c
	 *
	 * @return array, an associative array of response data
	 */
	public function parseResponse($storeValueRedeemReply)
	{
		$redeemData = array();
		if (trim($storeValueRedeemReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($storeValueRedeemReply);
			$redeemXpath = new DOMXPath($doc);
			$redeemXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$orderId = $redeemXpath->query('//a:PaymentContext/a:OrderId');
			if ($orderId->length) {
				$redeemData['orderId'] = (int) $orderId->item(0)->nodeValue;
			}

			$paymentAccountUniqueId = $redeemXpath->query('//a:PaymentContext/a:PaymentAccountUniqueId');
			if ($paymentAccountUniqueId->length) {
				$redeemData['paymentAccountUniqueId'] = (string) $paymentAccountUniqueId->item(0)->nodeValue;
			}

			$responseCode = $redeemXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$redeemData['responseCode'] = (string) $responseCode->item(0)->nodeValue;
			}

			$amountRedeemed = $redeemXpath->query('//a:AmountRedeemed');
			if ($amountRedeemed->length) {
				$redeemData['amountRedeemed'] = (float) $amountRedeemed->item(0)->nodeValue;
			}

			$balanceAmount = $redeemXpath->query('//a:BalanceAmount');
			if ($balanceAmount->length) {
				$redeemData['balanceAmount'] = (float) $balanceAmount->item(0)->nodeValue;
			}
		}

		return $redeemData;
	}
}
