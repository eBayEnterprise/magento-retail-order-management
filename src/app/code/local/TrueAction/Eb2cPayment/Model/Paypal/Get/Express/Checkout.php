<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Get_Express_Checkout extends Mage_Core_Model_Abstract
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
	 * getting paypal express checkout from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to Get express paypal checkout for in eb2c
	 *
	 * @return string the eb2c response to the request.
	 */
	public function getExpressCheckout($quote)
	{
		$paypalGetExpressCheckoutResponseMessage = '';
		try{
			// build request
			$payPalGetExpressCheckoutRequest = $this->buildPayPalGetExpressCheckoutRequest($quote);

			// make request to eb2c for quote items PaypalGetExpressCheckout
			$paypalGetExpressCheckoutResponseMessage = $this->_getHelper()->getApiModel()
				->setUri($this->_getHelper()->getOperationUri('get_paypal_get_express_checkout'))
				->request($payPalGetExpressCheckoutRequest);

		}catch(Exception $e){
			Mage::logException($e);
		}

		// Save payment data
		$this->_savePaymentData($this->parseResponse($paypalGetExpressCheckoutResponseMessage), $quote);

		return $paypalGetExpressCheckoutResponseMessage;
	}

	/**
	 * Build  PaypalGetExpressCheckout request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildPayPalGetExpressCheckoutRequest($quote)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$payPalGetExpressCheckoutRequest = $domDocument->addElement('PayPalGetExpressCheckoutRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$payPalGetExpressCheckoutRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);

		$paypal = $this->_getPaypal()->loadByQuoteId($quote->getEntityId());

		$payPalGetExpressCheckoutRequest->createChild(
			'Token',
			(string) $paypal->getEb2cPaypalToken()
		);
		$payPalGetExpressCheckoutRequest->createChild(
			'currencyCode',
			(string) $quote->getQuoteCurrencyCode()
		);

		return $domDocument;
	}

	/**
	 * Parse PayPal GetExpress reply xml.
	 *
	 * @param string $payPalGetExpressCheckoutReply the xml response from eb2c
	 *
	 * @return Varien_Object, an object of response data
	 */
	public function parseResponse($payPalGetExpressCheckoutReply)
	{
		$checkoutObject = new Varien_Object();
		if (trim($payPalGetExpressCheckoutReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($payPalGetExpressCheckoutReply);
			$checkoutXpath = new DOMXPath($doc);
			$checkoutXpath->registerNamespace('a', $this->_getHelper()->getXmlNs());

			$orderId = $checkoutXpath->query('//a:OrderId');
			if ($orderId->length) {
				$checkoutObject->setOrderId((int) $orderId->item(0)->nodeValue);
			}

			$responseCode = $checkoutXpath->query('//a:ResponseCode');
			if ($responseCode->length) {
				$checkoutObject->setResponseCode((string) $responseCode->item(0)->nodeValue);
			}

			$payerEmail = $checkoutXpath->query('//a:PayerEmail');
			if ($payerEmail->length) {
				$checkoutObject->setPayerEmail((string) $payerEmail->item(0)->nodeValue);
			}

			$payerId = $checkoutXpath->query('//a:PayerId');
			if ($payerId->length) {
				$checkoutObject->setPayerId((string) $payerId->item(0)->nodeValue);
			}

			$payerStatus = $checkoutXpath->query('//a:PayerStatus');
			if ($payerStatus->length) {
				$checkoutObject->setPayerStatus((string) $payerStatus->item(0)->nodeValue);
			}

			$payerName = $checkoutXpath->query('//a:PayerName');
			if ($payerName->length) {
				$honorific = $checkoutXpath->query('//a:PayerName/a:Honorific');
				if ($honorific->length) {
					$checkoutObject->setPayerNameHonorific((string) $honorific->item(0)->nodeValue);
				}

				$lastName = $checkoutXpath->query('//a:PayerName/a:LastName');
				if ($lastName->length) {
					$checkoutObject->setPayerNameLastName((string) $lastName->item(0)->nodeValue);
				}

				$middleName = $checkoutXpath->query('//a:PayerName/a:MiddleName');
				if ($middleName->length) {
					$checkoutObject->setPayerNameMiddleName((string) $middleName->item(0)->nodeValue);
				}

				$firstName = $checkoutXpath->query('//a:PayerName/a:FirstName');
				if ($firstName->length) {
					$checkoutObject->setPayerNameFirstName((string) $firstName->item(0)->nodeValue);
				}
			}

			$payerCountry = $checkoutXpath->query('//a:PayerCountry');
			if ($payerCountry->length) {
				$checkoutObject->setPayerCountry((string) $payerCountry->item(0)->nodeValue);
			}

			$billingAddress = $checkoutXpath->query('//a:BillingAddress');
			if ($billingAddress->length) {
				$lineA = $checkoutXpath->query('//a:BillingAddress/a:Line1');
				if ($lineA->length) {
					$checkoutObject->setBillingAddressLine1((string) $lineA->item(0)->nodeValue);
				}
				$lineB = $checkoutXpath->query('//a:BillingAddress/a:Line2');
				if ($lineB->length) {
					$checkoutObject->setBillingAddressLine2((string) $lineB->item(0)->nodeValue);
				}
				$lineC = $checkoutXpath->query('//a:BillingAddress/a:Line3');
				if ($lineC->length) {
					$checkoutObject->setBillingAddressLine3((string) $lineC->item(0)->nodeValue);
				}
				$lineD = $checkoutXpath->query('//a:BillingAddress/a:Line4');
				if ($lineD->length) {
					$checkoutObject->setBillingAddressLine4((string) $lineD->item(0)->nodeValue);
				}

				$city = $checkoutXpath->query('//a:BillingAddress/a:City');
				if ($city->length) {
					$checkoutObject->setBillingAddressCity((string) $city->item(0)->nodeValue);
				}

				$mainDivision = $checkoutXpath->query('//a:BillingAddress/a:MainDivision');
				if ($mainDivision->length) {
					$checkoutObject->setBillingAddressMainDivision((string) $mainDivision->item(0)->nodeValue);
				}

				$countryCode = $checkoutXpath->query('//a:BillingAddress/a:CountryCode');
				if ($countryCode->length) {
					$checkoutObject->setBillingAddressCountryCode((string) $countryCode->item(0)->nodeValue);
				}

				$postalCode = $checkoutXpath->query('//a:BillingAddress/a:PostalCode');
				if ($postalCode->length) {
					$checkoutObject->setBillingAddressPostalCode((string) $postalCode->item(0)->nodeValue);
				}

				$addressStatus = $checkoutXpath->query('//a:BillingAddress/a:AddressStatus');
				if ($addressStatus->length) {
					$checkoutObject->setBillingAddressStatus((string) $addressStatus->item(0)->nodeValue);
				}
			}

			$payerPhone = $checkoutXpath->query('//a:PayerPhone');
			if ($payerPhone->length) {
				$checkoutObject->setPayerPhone((string) $payerPhone->item(0)->nodeValue);
			}

			$shippingAddress = $checkoutXpath->query('//a:ShippingAddress');
			if ($shippingAddress->length) {
				$shpLineA = $checkoutXpath->query('//a:ShippingAddress/a:Line1');
				if ($shpLineA->length) {
					$checkoutObject->setShippingAddressLine1((string) $shpLineA->item(0)->nodeValue);
				}
				$shpLineB = $checkoutXpath->query('//a:ShippingAddress/a:Line2');
				if ($shpLineB->length) {
					$checkoutObject->setShippingAddressLine2((string) $shpLineB->item(0)->nodeValue);
				}
				$shpLineC = $checkoutXpath->query('//a:ShippingAddress/a:Line3');
				if ($shpLineC->length) {
					$checkoutObject->setShippingAddressLine3((string) $shpLineC->item(0)->nodeValue);
				}
				$shpLineD = $checkoutXpath->query('//a:ShippingAddress/a:Line4');
				if ($shpLineD->length) {
					$checkoutObject->setShippingAddressLine4((string) $shpLineD->item(0)->nodeValue);
				}

				$shpCity = $checkoutXpath->query('//a:ShippingAddress/a:City');
				if ($shpCity->length) {
					$checkoutObject->setShippingAddressCity((string) $shpCity->item(0)->nodeValue);
				}

				$shpMainDivision = $checkoutXpath->query('//a:ShippingAddress/a:MainDivision');
				if ($shpMainDivision->length) {
					$checkoutObject->setShippingAddressMainDivision((string) $shpMainDivision->item(0)->nodeValue);
				}

				$shpCountryCode = $checkoutXpath->query('//a:ShippingAddress/a:CountryCode');
				if ($shpCountryCode->length) {
					$checkoutObject->setShippingAddressCountryCode((string) $shpCountryCode->item(0)->nodeValue);
				}

				$shpPostalCode = $checkoutXpath->query('//a:ShippingAddress/a:PostalCode');
				if ($shpPostalCode->length) {
					$checkoutObject->setShippingAddressPostalCode((string) $shpPostalCode->item(0)->nodeValue);
				}

				$shpAddressStatus = $checkoutXpath->query('//a:ShippingAddress/a:AddressStatus');
				if ($shpAddressStatus->length) {
					$checkoutObject->setShippingAddressStatus((string) $shpAddressStatus->item(0)->nodeValue);
				}
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
		if (trim($checkoutObject->getPayerId()) !== '') {
			$this->_getPaypal()->loadByQuoteId($quote->getEntityId());
			$this->_getPaypal()->setQuoteId($quote->getEntityId())
				->setEb2cPaypalPayerId($checkoutObject->getPayerId())
				->save();
		}
		return ;
	}
}
