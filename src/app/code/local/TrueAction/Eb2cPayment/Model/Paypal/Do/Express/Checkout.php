<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Do_Express_Checkout extends Mage_Core_Model_Abstract
{
	public function __construct()
	{
		return $this;
	}

	/**
	 * Do paypal express checkout from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to do express paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function doExpressCheckout($quote)
	{
		$paypalDoExpressCheckoutResponseMessage = '';
		try{
			// build request
			$payPalDoExpressCheckoutRequest = $this->buildPayPalDoExpressCheckoutRequest($quote);

			// make request to eb2c for quote items PaypalDoExpressCheckout
			$paypalDoExpressCheckoutResponseMessage = Mage::getModel('eb2ccore/api')
				->setUri(Mage::helper('eb2cpayment')->getOperationUri('get_paypal_do_express_checkout'))
				->request($payPalDoExpressCheckoutRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		// Save payment data
		$this->_savePaymentData($this->parseResponse($paypalDoExpressCheckoutResponseMessage), $quote);

		return $paypalDoExpressCheckoutResponseMessage;
	}

	/**
	 * Build  PaypalDoExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalDoExpressCheckoutRequest($quote)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalDoExpressCheckoutRequest = $domDocument->addElement('PayPalDoExpressCheckoutRequest', null, Mage::helper('eb2cpayment')->getXmlNs())->firstChild;
		$payPalDoExpressCheckoutRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);

		$paypal = Mage::getModel('eb2cpayment/paypal')->loadByQuoteId($quote->getEntityId());

		$payPalDoExpressCheckoutRequest->createChild(
			'Token',
			(string) $paypal->getEb2cPaypalToken()
		);
		$payPalDoExpressCheckoutRequest->createChild(
			'PayerId',
			(string) $paypal->getEb2cPaypalPayerId()
		);

		$payPalDoExpressCheckoutRequest->createChild(
			'Amount',
			(string) $quote->getBaseGrandTotal(),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);

		$quoteShippingAddress = $quote->getShippingAddress();
		$payPalDoExpressCheckoutRequest->createChild(
			'ShipToName',
			(string) $quoteShippingAddress->getName()
		);

		// creating shippingAddress element
		$shippingAddress = $payPalDoExpressCheckoutRequest->createChild(
			'ShippingAddress',
			null
		);

		// add Line1
		$shippingAddress->createChild(
			'Line1',
			(string) $quoteShippingAddress->getStreet(1)
		);

		// add Line2
		$shippingAddress->createChild(
			'Line2',
			(string) $quoteShippingAddress->getStreet(2)
		);

		// add Line3
		$shippingAddress->createChild(
			'Line3',
			(string) $quoteShippingAddress->getStreet(3)
		);

		// add Line4
		$shippingAddress->createChild(
			'Line4',
			(string) $quoteShippingAddress->getStreet(4)
		);

		// add City
		$shippingAddress->createChild(
			'City',
			(string) $quoteShippingAddress->getCity()
		);

		// add MainDivision
		$shippingAddress->createChild(
			'MainDivision',
			(string) $quoteShippingAddress->getRegion()
		);

		// add CountryCode
		$shippingAddress->createChild(
			'CountryCode',
			(string) $quoteShippingAddress->getCountryId()
		);

		// add PostalCode
		$shippingAddress->createChild(
			'PostalCode',
			(string) $quoteShippingAddress->getPostcode()
		);

		// creating lineItems element
		$lineItems = $payPalDoExpressCheckoutRequest->createChild(
			'LineItems',
			null
		);

		// add LineItemsTotal
		$lineItems->createChild(
			'LineItemsTotal',
			(string) $quote->getSubTotal(), // integer value doesn't get added only string
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);

		// add ShippingTotal
		$lineItems->createChild(
			'ShippingTotal',
			(string) $quote->getShippingAmount(),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);

		// add TaxTotal
		$lineItems->createChild(
			'TaxTotal',
			(string) $quote->getTaxAmount(),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		if ($quote) {
			foreach($quote->getAllAddresses() as $addresses){
				if ($addresses){
					foreach ($addresses->getAllItems() as $item) {
						// creating lineItem element
						$lineItem = $lineItems->createChild(
							'LineItem',
							null
						);

						// add Name
						$lineItem->createChild(
							'Name',
							(string) $item->getName()
						);

						// add Quantity
						$lineItem->createChild(
							'Quantity',
							(string) $item->getQty()
						);

						// add UnitAmount
						$lineItem->createChild(
							'UnitAmount',
							(string) $item->getPrice(),
							array('currencyCode' => $quote->getQuoteCurrencyCode())
						);
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
	 *
	 * @return Varien_Object, an object of response data
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
			$checkoutObject = new Varien_Object(
				array(
					'order_id' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue : 0,
					'response_code' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
					'transaction_id' => ($nodeTransactionID->length)? (string) $nodeTransactionID->item(0)->nodeValue : null,
					'payment_status' => ($nodePaymentStatus->length)? (string) $nodePaymentStatus->item(0)->nodeValue : null,
					'pending_reason' => ($nodePendingReason->length)? (string) $nodePendingReason->item(0)->nodeValue : null,
					'reason_code' => ($nodeReasonCode->length)? (string) $nodeReasonCode->item(0)->nodeValue : null,
				)
			);
		}

		return $checkoutObject;
	}

	/**
	 * save payment data to quote_payment.
	 *
	 * @param array $checkoutObject, an associative array of response data
	 * @param Mage_Sales_Quote $quote, sales quote instantiated object
	 *
	 * @return void
	 */
	protected function _savePaymentData($checkoutObject, $quote)
	{
		if (trim($checkoutObject->getTransactionId()) !== '') {
			$paypalObj = Mage::getModel('eb2cpayment/paypal')->loadByQuoteId($quote->getEntityId());
			$paypalObj->setQuoteId($quote->getEntityId())
				->setEb2cPaypalTransactionId($checkoutObject->getTransactionId())
				->save();
		}
		return ;
	}
}
