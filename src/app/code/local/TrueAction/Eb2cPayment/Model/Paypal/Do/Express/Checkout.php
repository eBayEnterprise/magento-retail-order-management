<?php
class TrueAction_Eb2cPayment_Model_Paypal_Do_Express_Checkout extends TrueAction_Eb2cPayment_Model_Paypal_Abstract
{
	// A mapping to something in the helper. Pretty contrived.
	const URI_KEY = 'get_paypal_do_express_checkout';
	const XSD_FILE = 'xsd_file_paypal_do_express';
	/**
	 * Build PaypalDoExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request XML from
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 */
	protected function _buildRequest(Mage_Sales_Model_Quote $quote)
	{
		$totals = $quote->getTotals();
		$currencyAttr = array('currencyCode' => $quote->getQuoteCurrencyCode());
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalDoExpressCheckoutRequest = $domDocument->addElement('PayPalDoExpressCheckoutRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalDoExpressCheckoutRequest->setAttribute('requestId', Mage::helper('eb2cpayment')->getRequestId($quote->getEntityId()));
		$payPalDoExpressCheckoutRequest->createChild('OrderId', (string) $quote->getEntityId());
		$paypal = Mage::getModel('eb2cpayment/paypal')->loadByQuoteId($quote->getEntityId());
		$payPalDoExpressCheckoutRequest->createChild('Token', (string) $paypal->getEb2cPaypalToken());
		$payPalDoExpressCheckoutRequest->createChild('PayerId', (string) $paypal->getEb2cPaypalPayerId());
		$payPalDoExpressCheckoutRequest->createChild('Amount', sprintf('%.02f', isset($totals['grand_total']) ? $totals['grand_total']->getValue() : 0), $currencyAttr);
		$quoteShippingAddress = $quote->getShippingAddress();
		$payPalDoExpressCheckoutRequest->createChild('ShipToName', (string) $quoteShippingAddress->getName());
		$lineItems = $payPalDoExpressCheckoutRequest->createChild('LineItems', null);
		$lineItems->createChild('LineItemsTotal', sprintf('%.02f', isset($totals['subtotal']) ? $totals['subtotal']->getValue() : 0), $currencyAttr);
		$lineItems->createChild('ShippingTotal', sprintf('%.02f', isset($totals['shipping']) ? $totals['shipping']->getValue() : 0), $currencyAttr);
		$lineItems->createChild('TaxTotal', sprintf('%.02f', isset($totals['tax']) ? $totals['tax']->getValue() : 0), $currencyAttr);
		if ($quote) {
			foreach($quote->getAllAddresses() as $addresses){
				if ($addresses){
					foreach ($addresses->getAllItems() as $item) {
						$lineItem = $lineItems->createChild('LineItem', null);
						$lineItem->createChild('Name', (string) $item->getName());
						$lineItem->createChild('Quantity', (string) $item->getQty());
						$lineItem->createChild('UnitAmount', sprintf('%.02f', $item->getPrice()), $currencyAttr);
					}
				}
			}
		}
		return $domDocument;
	}
	/**
	 * Parse PayPal DoExpress reply xml.
	 *
	 * @param string $payPalDoExpressCheckoutReply the xml response from eb2c
	 * @return Varien_Object an object of response data
	 */
	public function parseResponse($payPalDoExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalDoExpressCheckoutReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalDoExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $checkoutXpath->query('//a:OrderId');
			$nodeResponseCode = $checkoutXpath->query('//a:ResponseCode');
			$nodeTransactionID = $checkoutXpath->query('//a:TransactionID');
			$nodePaymentStatus = $checkoutXpath->query('//a:PaymentInfo/a:PaymentStatus');
			$nodePendingReason = $checkoutXpath->query('//a:PaymentInfo/a:PendingReason');
			$nodeReasonCode = $checkoutXpath->query('//a:PaymentInfo/a:ReasonCode');
			$checkoutObject->setData(array(
				'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
				'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'transaction_id' => ($nodeTransactionID->length)? (string) $nodeTransactionID->item(0)->nodeValue : null,
				'payment_status' => ($nodePaymentStatus->length)? (string) $nodePaymentStatus->item(0)->nodeValue : null,
				'pending_reason' => ($nodePendingReason->length)? (string) $nodePendingReason->item(0)->nodeValue : null,
				'reason_code' => ($nodeReasonCode->length)? (string) $nodeReasonCode->item(0)->nodeValue : null,
			));
		}
		return $checkoutObject;
	}
}
