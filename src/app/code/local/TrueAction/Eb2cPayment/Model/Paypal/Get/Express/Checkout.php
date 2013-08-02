<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Paypal_Get_Express_Checkout extends Mage_Core_Model_Abstract
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
		$domDocument = $this->_getHelper()->getDomDocument();
		$payPalGetExpressCheckoutRequest = $domDocument->addElement('PayPalGetExpressCheckoutRequest', null, $this->_getHelper()->getXmlNs())->firstChild;
		$payPalGetExpressCheckoutRequest->createChild(
			'OrderId',
			(string) $quote->getEntityId()
		);
		$payPalGetExpressCheckoutRequest->createChild(
			'Token',
			(string) $quote->getEb2cPaypalExpressCheckoutToken()
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
	 * @return array, an associative array of response data
	 */
	public function parseResponse($payPalGetExpressCheckoutReply)
	{
		$checkoutData = array();
		if (trim($payPalGetExpressCheckoutReply) !== '') {
			$doc = $this->_getHelper()->getDomDocument();
			$doc->loadXML($payPalGetExpressCheckoutReply);
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

			$payerEmail = $checkoutXpath->query('//a:PayerEmail');
			if ($payerEmail->length) {
				$checkoutData['payerEmail'] = (string) $payerEmail->item(0)->nodeValue;
			}

			$payerId = $checkoutXpath->query('//a:PayerId');
			if ($payerId->length) {
				$checkoutData['payerId'] = (string) $payerId->item(0)->nodeValue;
			}

			$payerStatus = $checkoutXpath->query('//a:PayerStatus');
			if ($payerStatus->length) {
				$checkoutData['payerStatus'] = (string) $payerStatus->item(0)->nodeValue;
			}

			$payerName = $checkoutXpath->query('//a:PayerName');
			if ($payerName->length) {
				$honorific = $checkoutXpath->query('//a:PayerName/a:Honorific');
				if ($honorific->length) {
					$checkoutData['payerName']['honorific'] = (string) $honorific->item(0)->nodeValue;
				}

				$lastName = $checkoutXpath->query('//a:PayerName/a:LastName');
				if ($lastName->length) {
					$checkoutData['payerName']['lastName'] = (string) $lastName->item(0)->nodeValue;
				}

				$middleName = $checkoutXpath->query('//a:PayerName/a:MiddleName');
				if ($middleName->length) {
					$checkoutData['payerName']['middleName'] = (string) $middleName->item(0)->nodeValue;
				}

				$firstName = $checkoutXpath->query('//a:PayerName/a:FirstName');
				if ($firstName->length) {
					$checkoutData['payerName']['firstName'] = (string) $firstName->item(0)->nodeValue;
				}
			}

			$payerCountry = $checkoutXpath->query('//a:PayerCountry');
			if ($payerCountry->length) {
				$checkoutData['payerCountry'] = (string) $payerCountry->item(0)->nodeValue;
			}

			$billingAddress = $checkoutXpath->query('//a:BillingAddress');
			if ($billingAddress->length) {
				$lineA = $checkoutXpath->query('//a:BillingAddress/a:Line1');
				if ($lineA->length) {
					$checkoutData['billingAddress']['line1'] = (string) $lineA->item(0)->nodeValue;
				}
				$lineB = $checkoutXpath->query('//a:BillingAddress/a:Line2');
				if ($lineB->length) {
					$checkoutData['billingAddress']['line2'] = (string) $lineB->item(0)->nodeValue;
				}
				$lineC = $checkoutXpath->query('//a:BillingAddress/a:Line3');
				if ($lineC->length) {
					$checkoutData['billingAddress']['line3'] = (string) $lineC->item(0)->nodeValue;
				}
				$lineD = $checkoutXpath->query('//a:BillingAddress/a:Line4');
				if ($lineD->length) {
					$checkoutData['billingAddress']['line4'] = (string) $lineD->item(0)->nodeValue;
				}

				$city = $checkoutXpath->query('//a:BillingAddress/a:City');
				if ($city->length) {
					$checkoutData['billingAddress']['city'] = (string) $city->item(0)->nodeValue;
				}

				$mainDivision = $checkoutXpath->query('//a:BillingAddress/a:MainDivision');
				if ($mainDivision->length) {
					$checkoutData['billingAddress']['mainDivision'] = (string) $mainDivision->item(0)->nodeValue;
				}

				$countryCode = $checkoutXpath->query('//a:BillingAddress/a:CountryCode');
				if ($countryCode->length) {
					$checkoutData['billingAddress']['countryCode'] = (string) $countryCode->item(0)->nodeValue;
				}

				$postalCode = $checkoutXpath->query('//a:BillingAddress/a:PostalCode');
				if ($postalCode->length) {
					$checkoutData['billingAddress']['postalCode'] = (string) $postalCode->item(0)->nodeValue;
				}

				$addressStatus = $checkoutXpath->query('//a:BillingAddress/a:AddressStatus');
				if ($addressStatus->length) {
					$checkoutData['billingAddress']['addressStatus'] = (string) $addressStatus->item(0)->nodeValue;
				}
			}

			$payerPhone = $checkoutXpath->query('//a:PayerPhone');
			if ($payerPhone->length) {
				$checkoutData['payerPhone'] = (string) $payerPhone->item(0)->nodeValue;
			}

			$shippingAddress = $checkoutXpath->query('//a:ShippingAddress');
			if ($shippingAddress->length) {
				$shpLineA = $checkoutXpath->query('//a:ShippingAddress/a:Line1');
				if ($shpLineA->length) {
					$checkoutData['shippingAddress']['line1'] = (string) $shpLineA->item(0)->nodeValue;
				}
				$shpLineB = $checkoutXpath->query('//a:ShippingAddress/a:Line2');
				if ($shpLineB->length) {
					$checkoutData['shippingAddress']['line2'] = (string) $shpLineB->item(0)->nodeValue;
				}
				$shpLineC = $checkoutXpath->query('//a:ShippingAddress/a:Line3');
				if ($shpLineC->length) {
					$checkoutData['shippingAddress']['line3'] = (string) $shpLineC->item(0)->nodeValue;
				}
				$shpLineD = $checkoutXpath->query('//a:ShippingAddress/a:Line4');
				if ($shpLineD->length) {
					$checkoutData['shippingAddress']['line4'] = (string) $shpLineD->item(0)->nodeValue;
				}

				$shpCity = $checkoutXpath->query('//a:ShippingAddress/a:City');
				if ($shpCity->length) {
					$checkoutData['shippingAddress']['city'] = (string) $shpCity->item(0)->nodeValue;
				}

				$shpMainDivision = $checkoutXpath->query('//a:ShippingAddress/a:MainDivision');
				if ($shpMainDivision->length) {
					$checkoutData['shippingAddress']['mainDivision'] = (string) $shpMainDivision->item(0)->nodeValue;
				}

				$shpCountryCode = $checkoutXpath->query('//a:ShippingAddress/a:CountryCode');
				if ($shpCountryCode->length) {
					$checkoutData['shippingAddress']['countryCode'] = (string) $shpCountryCode->item(0)->nodeValue;
				}

				$shpPostalCode = $checkoutXpath->query('//a:ShippingAddress/a:PostalCode');
				if ($shpPostalCode->length) {
					$checkoutData['shippingAddress']['postalCode'] = (string) $shpPostalCode->item(0)->nodeValue;
				}

				$shpAddressStatus = $checkoutXpath->query('//a:ShippingAddress/a:AddressStatus');
				if ($shpAddressStatus->length) {
					$checkoutData['shippingAddress']['addressStatus'] = (string) $shpAddressStatus->item(0)->nodeValue;
				}
			}
		}

		return $checkoutData;
	}
}
