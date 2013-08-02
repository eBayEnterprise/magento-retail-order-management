<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Do_Express_Checkout extends Mage_Core_Model_Abstract
{
	protected $_helper;

	public function __construct()
	{
		$this->_helper = $this->_getHelper();
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
			$paypalDoExpressCheckoutResponseMessage = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_paypal_do_express_checkout'))
				->request($payPalDoExpressCheckoutRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

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
		$domDocument = $this->_getHelper()->getDomDocument();
		$payPalDoExpressCheckoutRequest = $domDocument->addElement('PayPalDoExpressCheckoutRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$payPalDoExpressCheckoutRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);
		$payPalDoExpressCheckoutRequest->createChild(
			'Token',
			(string) $quote->getEb2cPaypalExpressCheckoutToken()
		);
		$payPalDoExpressCheckoutRequest->createChild(
			'PayerId',
			(string) $quote->getEb2cPaypalExpressCheckoutPayerId()
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
	 * @return array, an associative array of response data
	 */
	public function parseResponse($payPalDoExpressCheckoutReply)
	{
		$checkoutData = array();
		if (trim($payPalDoExpressCheckoutReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($payPalDoExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$orderId = $checkoutXpath->query('//a:OrderId');
			if ($orderId->length) {
				$checkoutData['orderId'] = (int) $orderId->item(0)->nodeValue;
			}

			$responseCode = $checkoutXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$checkoutData['responseCode'] = (string) $responseCode->item(0)->nodeValue;
			}

			$transactionID = $checkoutXpath->query('//a:TransactionID');
			if ($transactionID->length) {
				$checkoutData['transactionID'] = (string) $transactionID->item(0)->nodeValue;
			}

			$paymentInfo = $checkoutXpath->query('//a:PaymentInfo');
			if ($paymentInfo->length) {
				$paymentStatus = $checkoutXpath->query('//a:PaymentInfo/a:PaymentStatus');
				if ($paymentStatus->length) {
					$checkoutData['paymentInfo']['paymentStatus'] = (string) $paymentStatus->item(0)->nodeValue;
				}

				$pendingReason = $checkoutXpath->query('//a:PaymentInfo/a:PendingReason');
				if ($pendingReason->length) {
					$checkoutData['paymentInfo']['pendingReason'] = (string) $pendingReason->item(0)->nodeValue;
				}

				$reasonCode = $checkoutXpath->query('//a:PaymentInfo/a:ReasonCode');
				if ($reasonCode->length) {
					$checkoutData['paymentInfo']['reasonCode'] = (string) $reasonCode->item(0)->nodeValue;
				}
			}
		}

		return $checkoutData;
	}
}
