<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Set_Express_Checkout extends Mage_Core_Model_Abstract
{
	/**
	 * instantiate payment helper object
	 *
	 * @var TrueAction_Eb2cPayment_Helper_Data
	 */
	protected $_helper;

	/**
	 * instantiate paypal payment model object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal
	 */
	protected $_paypal;

	public function __construct()
	{
		$this->_helper = $this->_getHelper();
		$this->_paypal = $this->_getPaypal();
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
	 * Get model paypal instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Paypal
	 */
	protected function _getPaypal()
	{
		if (!$this->_paypal) {
			$this->_paypal = Mage::getModel('eb2cpayment/paypal');
		}
		return $this->_paypal;
	}

	/**
	 * setting paypal express checking in eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to set paypal express checkout in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function setExpressCheckout($quote)
	{
		$paypalSetExpressCheckoutResponseMessage = '';
		try{
			// build request
			$payPalSetExpressCheckoutRequest = $this->buildPayPalSetExpressCheckoutRequest($quote);

			// make request to eb2c for quote items PaypalSetExpressCheckout
			$paypalSetExpressCheckoutResponseMessage = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_paypal_set_express_checkout'))
				->request($payPalSetExpressCheckoutRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		// Save payment data
		$this->_savePaymentData($this->parseResponse($paypalSetExpressCheckoutResponseMessage), $quote);

		return $paypalSetExpressCheckoutResponseMessage;
	}

	/**
	 * Build  PaypalSetExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalSetExpressCheckoutRequest($quote)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$payPalSetExpressCheckoutRequest = $domDocument->addElement('PayPalSetExpressCheckoutRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$payPalSetExpressCheckoutRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);
		$payPalSetExpressCheckoutRequest->createChild(
			'ReturnUrl',
			(string) Mage::getUrl('*/*/return')
		);
		$payPalSetExpressCheckoutRequest->createChild(
			'CancelUrl',
			(string) Mage::getUrl('*/*/cancel')
		);
		$payPalSetExpressCheckoutRequest->createChild(
			'LocaleCode',
			(string) Mage::app()->getLocale()->getDefaultLocale()
		);
		$payPalSetExpressCheckoutRequest->createChild(
			'Amount',
			(string) $quote->getBaseGrandTotal(),
			array('currencyCode' => $quote->getQuoteCurrencyCode())
		);
		// creating lineItems element
		$lineItems = $payPalSetExpressCheckoutRequest->createChild(
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
	 * Parse PayPal SetExpress reply xml.
	 *
	 * @param string $payPalSetExpressCheckoutReply the xml response from eb2c
	 *
	 * @return Varien_Object, an object of response data
	 */
	public function parseResponse($payPalSetExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalSetExpressCheckoutReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($payPalSetExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_getHelper()->getPaymentXmlNs());

			$orderId = $checkoutXpath->query('//a:OrderId');
			if ($orderId->length) {
				$checkoutObject->setOrderId((int) $orderId->item(0)->nodeValue);
			}

			$responseCode = $checkoutXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$checkoutObject->setResponseCode((string) $responseCode->item(0)->nodeValue);
			}

			$token = $checkoutXpath->query('//a:Token');
			if ($token->length) {
				$checkoutObject->setToken((string) $token->item(0)->nodeValue);
			}
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
		if (trim($checkoutObject->getToken()) !== '') {
			$this->_getPaypal()->loadByQuoteId($quote->getEntityId());
			$this->_getPaypal()->setQuoteId($quote->getEntityId())
				->setEb2cPaypalToken($checkoutObject->getToken())
				->save();
		}
		return ;
	}
}
