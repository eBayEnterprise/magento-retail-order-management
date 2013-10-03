<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Stored_Value_Redeem extends Mage_Core_Model_Abstract
{
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
			$storeValueRedeemReply = Mage::getModel('eb2ccore/api')
				->setUri(Mage::helper('eb2cpayment')->getOperationUri('get_gift_card_redeem'))
				->setXsd(Mage::helper('eb2cpayment')->getConfigModel()->xsdFileStoredValueRedeem)
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
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$storeValueRedeemRequest = $domDocument->addElement('StoreValueRedeemRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$storeValueRedeemRequest->setAttribute('requestId', Mage::helper('eb2cpayment')->getRequestId($entityId));

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
			array('isToken' => 'false')
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
			array('currencyCode' => 'USD')
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
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($storeValueRedeemReply);
			$redeemXpath = new DOMXPath($doc);
			$redeemXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $redeemXpath->query('//a:PaymentContext/a:OrderId');
			$nodePaymentAccountUniqueId = $redeemXpath->query('//a:PaymentContext/a:PaymentAccountUniqueId');
			$nodeResponseCode = $redeemXpath->query('//a:ResponseCode');
			$nodeAmountRedeemed = $redeemXpath->query('//a:AmountRedeemed');
			$nodeBalanceAmount = $redeemXpath->query('//a:BalanceAmount');
			$redeemData = array(
				'orderId' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue: 0,
				'paymentAccountUniqueId' => ($nodePaymentAccountUniqueId->length)? (string) $nodePaymentAccountUniqueId->item(0)->nodeValue : null,
				'responseCode' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'amountRedeemed' => ($nodeAmountRedeemed->length)? (float) $nodeAmountRedeemed->item(0)->nodeValue : 0,
				'balanceAmount' => ($nodeBalanceAmount->length)? (float) $nodeBalanceAmount->item(0)->nodeValue : 0,
			);
		}

		return $redeemData;
	}
}
