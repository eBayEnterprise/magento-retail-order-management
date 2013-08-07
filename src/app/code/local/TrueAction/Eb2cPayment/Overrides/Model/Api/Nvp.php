<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Overrides_Model_Api_Nvp extends Mage_Paypal_Model_Api_Nvp
{
	/**
	 * instantiate payment helper object
	 *
	 * @var TrueAction_Eb2cPayment_Helper_Data
	 */
	protected $_helper;

	/**
	 * eb2c paypal set express checkout object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal_Set_Express_Checkout
	 */
	protected $_paypalSetExpressCheckout;

	/**
	 * eb2c paypal Get express checkout object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal_Get_Express_Checkout
	 */
	protected $_paypalGetExpressCheckout;

	/**
	 * eb2c paypal Do express checkout object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal_Do_Express_Checkout
	 */
	protected $_paypalDoExpressCheckout;

	/**
	 * eb2c paypal Do Authorization object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal_Do_Authorization
	 */
	protected $_paypalDoAuthorization;

	/**
	 * eb2c paypal Do Void object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal_Do_Void
	 */
	protected $_paypalDoVoid;

	/**
	 * instantiate paypal payment model object
	 *
	 * @var TrueAction_Eb2cPayment_Model_Paypal
	 */
	protected $_paypal;

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
	 * Get paypal Set Express Checkout instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Paypal_Set_Express_Checkout
	 */
	protected function _getPaypalSetExpressCheckout()
	{
		if (!$this->_paypalSetExpressCheckout) {
			$this->_paypalSetExpressCheckout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		}

		return $this->_paypalSetExpressCheckout;
	}

	/**
	 * Get paypal Get Express Checkout instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Paypal_Get_Express_Checkout
	 */
	protected function _getPaypalGetExpressCheckout()
	{
		if (!$this->_paypalGetExpressCheckout) {
			$this->_paypalGetExpressCheckout = Mage::getModel('eb2cpayment/paypal_get_express_checkout');
		}

		return $this->_paypalGetExpressCheckout;
	}

	/**
	 * Get paypal Do Express Checkout instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Paypal_Do_Express_Checkout
	 */
	protected function _getPaypalDoExpressCheckout()
	{
		if (!$this->_paypalDoExpressCheckout) {
			$this->_paypalDoExpressCheckout = Mage::getModel('eb2cpayment/paypal_do_express_checkout');
		}

		return $this->_paypalDoExpressCheckout;
	}

	/**
	 * Get paypal Do Authorization instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Paypal_Do_Authorization
	 */
	protected function _getPaypalDoAuthorization()
	{
		if (!$this->_paypalDoAuthorization) {
			$this->_paypalDoAuthorization = Mage::getModel('eb2cpayment/paypal_do_authorization');
		}

		return $this->_paypalDoAuthorization;
	}

	/**
	 * Get paypal Do Void instantiated object.
	 *
	 * @return TrueAction_Eb2cPayment_Model_Paypal_Do_Void
	 */
	protected function _getPaypalDoVoid()
	{
		if (!$this->_paypalDoVoid) {
			$this->_paypalDoVoid = Mage::getModel('eb2cpayment/paypal_do_void');
		}

		return $this->_paypalDoVoid;
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
	 * Get paypal cart instantiated object.
	 *
	 * @return Mage_Paypal_Model_Cart
	 */
	protected function _getCart()
	{
		if (!$this->_cart) {
			$sessionQuoteId = Mage::getSingleton('checkout/session')->getQuoteId();
			$this->_cart = Mage::getModel('paypal/cart', array(Mage::getModel('sales/quote')->load($sessionQuoteId)));
		}

		return $this->_cart;
	}

	/**
	 * Override to make eb2c PayPalSetExpressCheckout call
	 *
	 * SetExpressCheckout call
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout
	 */
	public function callSetExpressCheckout()
	{
		$this->_prepareExpressCheckoutCallRequest($this->_setExpressCheckoutRequest);
		$request = $this->_exportToRequest($this->_setExpressCheckoutRequest);
		$this->_exportLineItems($request);

		// import/suppress shipping address, if any
		$options = $this->getShippingOptions();
		if ($this->getAddress()) {
			$request = $this->_importAddresses($request);
			$request['ADDROVERRIDE'] = 1;
		} elseif ($options && (count($options) <= 10)) { // doesn't support more than 10 shipping options
			$request['CALLBACK'] = $this->getShippingOptionsCallbackUrl();
			$request['CALLBACKTIMEOUT'] = 6; // max value
			$request['MAXAMT'] = $request['AMT'] + 999.00; // it is impossible to calculate max amount
			$this->_exportShippingOptions($request);
		}

		// add recurring profiles information
		$i = 0;
		foreach ($this->_recurringPaymentProfiles as $profile) {
			$request["L_BILLINGTYPE{$i}"] = 'RecurringPayments';
			$request["L_BILLINGAGREEMENTDESCRIPTION{$i}"] = $profile->getScheduleDescription();
			$i++;
		}

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cPaypalSetExpressCheckout) {
			// Eb2c PaypalSetExpressCheckout is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalSetExpressCheckout call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's set PayPal Express checkout it through eb2c.
				if ($payPalSetExpressCheckoutReply = $this->_getPaypalSetExpressCheckout()->setExpressCheckout($quote)) {
					if ($payPalSetExpressCheckoutObject = $this->_getPaypalSetExpressCheckout()->parseResponse($payPalSetExpressCheckoutReply)) {
						// making sure we have the right data
						if (strtoupper(trim($payPalSetExpressCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$response = array(
								'TOKEN' => $payPalSetExpressCheckoutObject->getToken(),
								'ACK' => $payPalSetExpressCheckoutObject->getResponseCode(),
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalSetExpressCheckout is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::SET_EXPRESS_CHECKOUT, $request);
		}

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cDebug){
			Mage::log("\n\rDEDUG:\n\r________________________\n\rcallSetExpressCheckout:\n\r" . print_r($response, true) . "\n\r", Zend_Log::DEBUG);
		}

		$this->_importFromResponse($this->_setExpressCheckoutResponse, $response);
	}

	/**
	 * Override to make eb2c PayPalGetExpressCheckout call
	 *
	 * GetExpressCheckoutDetails call
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_GetExpressCheckoutDetails
	 */
	function callGetExpressCheckoutDetails()
	{
		$this->_prepareExpressCheckoutCallRequest($this->_getExpressCheckoutDetailsRequest);
		$request = $this->_exportToRequest($this->_getExpressCheckoutDetailsRequest);
		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cPaypalGetExpressCheckout) {
			// Eb2c PaypalGetExpressCheckout is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalGetExpressCheckout call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Get PayPal Express checkout it through eb2c.
				if ($payPalGetExpressCheckoutReply = $this->_getPaypalGetExpressCheckout()->getExpressCheckout($quote)) {
					if ($payPalGetExpressCheckoutObject = $this->_getPaypalGetExpressCheckout()->parseResponse($payPalGetExpressCheckoutReply)) {
						// making sure we have the right data
						$quoteShippingAddress = $quote->getShippingAddress();
						if (strtoupper(trim($payPalGetExpressCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$paypal = $this->_getPaypal()->loadByQuoteId($quote->getEntityId());
							$response = array(
								'TOKEN' => $paypal->getEb2cPaypalToken(),
								'ACK' => $payPalGetExpressCheckoutObject->getResponseCode(),
								'EMAIL' => $payPalGetExpressCheckoutObject->getPayerEmail(),
								'PAYERID' => $payPalGetExpressCheckoutObject->getPayerId(),
								'PAYERSTATUS' => $payPalGetExpressCheckoutObject->getPayerStatus(),
								'FIRSTNAME' => $payPalGetExpressCheckoutObject->getPayerNameFirstName(),
								'LASTNAME' => $payPalGetExpressCheckoutObject->getPayerNameLastName(),
								'COUNTRYCODE' => $payPalGetExpressCheckoutObject->getPayerCountry(),
								'SHIPTONAME' => $quoteShippingAddress->getName(),
								'SHIPTOSTREET' => $payPalGetExpressCheckoutObject->getShippingAddressLine1(),
								'SHIPTOCITY' => $payPalGetExpressCheckoutObject->getShippingAddressCity(),
								'SHIPTOSTATE' => $payPalGetExpressCheckoutObject->getShippingAddressMainDivision(),
								'SHIPTOZIP' => $payPalGetExpressCheckoutObject->getShippingAddressPostalCode(),
								'SHIPTOCOUNTRYCODE' => $payPalGetExpressCheckoutObject->getShippingAddressCountryCode(),
								'SHIPTOPHONENUM' => $payPalGetExpressCheckoutObject->getPayerPhone(),
								'ADDRESSSTATUS' => $payPalGetExpressCheckoutObject->getShippingAddressStatus(),
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalGetExpressCheckout is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::GET_EXPRESS_CHECKOUT_DETAILS, $request);
		}

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cDebug){
			Mage::log("\n\rDEDUG:\n\r________________________\n\rcallGetExpressCheckoutDetails:\n\r" . print_r($response, true) . "\n\r", Zend_Log::DEBUG);
		}

		$this->_importFromResponse($this->_paymentInformationResponse, $response);
		$this->_exportAddressses($response);
	}

	/**
	 * Override to make eb2c PayPalDoExpressCheckout call
	 *
	 * DoExpressCheckout call
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoExpressCheckoutPayment
	 */
	public function callDoExpressCheckoutPayment()
	{
		$this->_prepareExpressCheckoutCallRequest($this->_doExpressCheckoutPaymentRequest);
		$request = $this->_exportToRequest($this->_doExpressCheckoutPaymentRequest);
		$this->_exportLineItems($request);

		if ($this->getAddress()) {
			$request = $this->_importAddresses($request);
			$request['ADDROVERRIDE'] = 1;
		}
		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cPaypalDoExpressCheckout) {
			// Eb2c PaypalDoExpressCheckout is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalDoExpressCheckout call here.
			$sessionQuoteId = Mage::getSingleton('checkout/session')->getQuoteId();
			$quote = Mage::getModel('sales/quote')->load($sessionQuoteId);
			$this->_cart = Mage::getModel('paypal/cart', array($quote));

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Do PayPal Express checkout it through eb2c.
				if ($payPalDoExpressCheckoutReply = $this->_getPaypalDoExpressCheckout()->doExpressCheckout($quote)) {
					if ($payPalDoExpressCheckoutObject = $this->_getPaypalDoExpressCheckout()->parseResponse($payPalDoExpressCheckoutReply)) {
						// making sure we have the right data
						$quoteShippingAddress = $quote->getShippingAddress();
						if (strtoupper(trim($payPalDoExpressCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$paypal = $this->_getPaypal()->loadByQuoteId($quote->getEntityId());
							$response = array(
								'TOKEN' => $paypal->getEb2cPaypalToken(),
								'ACK' => $payPalDoExpressCheckoutObject->getResponseCode(),
								'TRANSACTIONID' => $payPalDoExpressCheckoutObject->getTransactionId(),
								'PAYMENTSTATUS' => $payPalDoExpressCheckoutObject->getPaymentStatus(),
								'PENDINGREASON' => $payPalDoExpressCheckoutObject->getPendingReason(),
								'REASONCODE' => $payPalDoExpressCheckoutObject->getReasonCode(),
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalDoExpressCheckout is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::DO_EXPRESS_CHECKOUT_PAYMENT, $request);
		}

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cDebug){
			Mage::log("\n\rDEDUG:\n\r________________________\n\rcallDoExpressCheckoutPayment:\n\r" . print_r($response, true) . "\n\r", Zend_Log::DEBUG);
		}

		$this->_importFromResponse($this->_paymentInformationResponse, $response);
		$this->_importFromResponse($this->_doExpressCheckoutPaymentResponse, $response);
		$this->_importFromResponse($this->_createBillingAgreementResponse, $response);
	}

	/**
	 * Override to make eb2c PayPalDoAuthorization call
	 *
	 * DoAuthorization call
	 *
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoAuthorization
	 * @return Mage_Paypal_Model_Api_Nvp
	 */
	public function callDoAuthorization()
	{
		$request = $this->_exportToRequest($this->_doAuthorizationRequest);

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cPaypalDoAuthorization) {
			// Eb2c PaypalDoAuthorization is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalDoAuthorization call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Do PayPal Authorization it through eb2c.
				if ($payPalDoAuthorizationReply = $this->_getPaypalDoAuthorization()->doAuthorization($quote)) {
					if ($payPalDoAuthorizationCheckoutObject = $this->_getPaypalDoAuthorization()->parseResponse($payPalDoAuthorizationReply)) {
						if (strtoupper(trim($payPalDoAuthorizationCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$response = array(
								'TRANSACTIONID' => uniqid(),
								'ACK' => $payPalDoAuthorizationCheckoutObject->getResponseCode(),
								'PAYMENTSTATUS' => $payPalDoAuthorizationCheckoutObject->getPaymentStatus(),
								'PENDINGREASON' => $payPalDoAuthorizationCheckoutObject->getPendingReason(),
								'REASONCODE' => $payPalDoAuthorizationCheckoutObject->getReasonCode(),
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalDoAuthorization is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::DO_AUTHORIZATION, $request);
		}

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cDebug){
			Mage::log("\n\rDEDUG:\n\r________________________\n\rcallDoAuthorization:\n\r" . print_r($response, true) . "\n\r", Zend_Log::DEBUG);
		}

		$this->_importFromResponse($this->_paymentInformationResponse, $response);
		$this->_importFromResponse($this->_doAuthorizationResponse, $response);

		return $this;
	}

	/**
	 * Override to make eb2c PayPalDoVoid call
	 *
	 * DoVoid call
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoVoid
	 */
	public function callDoVoid()
	{
		$request = $this->_exportToRequest($this->_doVoidRequest);

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cPaypalDoVoid) {
			// Eb2c PaypalDoVoid is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalDoVoid call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Do PayPal Void it through eb2c.
				if ($payPalDoVoidReply = $this->_getPaypalDoVoid()->doVoid($quote)) {
					if ($payPalDoVoidCheckoutObject = $this->_getPaypalDoVoid()->parseResponse($payPalDoVoidReply)) {
						if (strtoupper(trim($payPalDoVoidCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$response = array(
								'ACK' => $payPalDoVoidCheckoutObject->getResponseCode(),
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalDoVoid is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::DO_VOID, $request);
		}

		if ((bool) $this->_getHelper()->getConfigModel()->enabledEb2cDebug){
			Mage::log("\n\rDEDUG:\n\r________________________\n\rcallDoVoid:\n\r" . print_r($response, true) . "\n\r", Zend_Log::DEBUG);
		}
	}
}
