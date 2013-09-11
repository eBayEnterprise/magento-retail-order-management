<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Overrides_Model_Api_Nvp extends Mage_Paypal_Model_Api_Nvp
{
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cPaypalSetExpressCheckout) {
			// Eb2c PaypalSetExpressCheckout is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalSetExpressCheckout call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's set PayPal Express checkout it through eb2c.
				$payPalSetExpressCheckoutReply = Mage::getModel('eb2cpayment/paypal_set_express_checkout')->setExpressCheckout($quote);
				if ($payPalSetExpressCheckoutReply) {
					$payPalSetExpressCheckoutObject = Mage::getModel('eb2cpayment/paypal_set_express_checkout')->parseResponse($payPalSetExpressCheckoutReply);
					if ($payPalSetExpressCheckoutObject) {
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cDebug){
			Mage::log(
				'[' . __CLASS__ . '] ' .
				"\n\rDEDUG:\n\r________________________\n\rcallSetExpressCheckout:\n\r" .
				print_r($response, true) . "\n\r",
				Zend_Log::DEBUG
			);
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
		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cPaypalGetExpressCheckout) {
			// Eb2c PaypalGetExpressCheckout is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalGetExpressCheckout call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Get PayPal Express checkout it through eb2c.
				$payPalGetExpressCheckoutReply = Mage::getModel('eb2cpayment/paypal_get_express_checkout')->getExpressCheckout($quote);
				if ($payPalGetExpressCheckoutReply) {
					$payPalGetExpressCheckoutObject = Mage::getModel('eb2cpayment/paypal_get_express_checkout')->parseResponse($payPalGetExpressCheckoutReply);
					if ($payPalGetExpressCheckoutObject) {
						// making sure we have the right data
						$quoteShippingAddress = $quote->getShippingAddress();
						if (strtoupper(trim($payPalGetExpressCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$paypal = Mage::getModel('eb2cpayment/paypal')->loadByQuoteId($quote->getEntityId());
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cDebug){
			Mage::log(
				'[' . __CLASS__ . '] ' .
				"\n\rDEDUG:\n\r________________________\n\rcallGetExpressCheckoutDetails:\n\r" .
				print_r($response, true) . "\n\r",
				Zend_Log::DEBUG
			);
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
		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cPaypalDoExpressCheckout) {
			// Eb2c PaypalDoExpressCheckout is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalDoExpressCheckout call here.
			$sessionQuoteId = Mage::getSingleton('checkout/session')->getQuoteId();
			$quote = Mage::getModel('sales/quote')->load($sessionQuoteId);
			$this->_cart = Mage::getModel('paypal/cart', array($quote));

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Do PayPal Express checkout it through eb2c.
				$payPalDoExpressCheckoutReply = Mage::getModel('eb2cpayment/paypal_do_express_checkout')->doExpressCheckout($quote);
				if ($payPalDoExpressCheckoutReply) {
					$payPalDoExpressCheckoutObject = Mage::getModel('eb2cpayment/paypal_do_express_checkout')->parseResponse($payPalDoExpressCheckoutReply);
					if ($payPalDoExpressCheckoutObject) {
						// making sure we have the right data
						$quoteShippingAddress = $quote->getShippingAddress();
						if (strtoupper(trim($payPalDoExpressCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$paypal = Mage::getModel('eb2cpayment/paypal')->loadByQuoteId($quote->getEntityId());
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cDebug){
			Mage::log(
				'[' . __CLASS__ . '] ' .
				"\n\rDEDUG:\n\r________________________\n\rcallDoExpressCheckoutPayment:\n\r" .
				print_r($response, true) . "\n\r",
				Zend_Log::DEBUG
			);
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cPaypalDoAuthorization) {
			// Eb2c PaypalDoAuthorization is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalDoAuthorization call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Do PayPal Authorization it through eb2c.
				$payPalDoAuthorizationReply = Mage::getModel('eb2cpayment/paypal_do_authorization')->doAuthorization($quote);
				if ($payPalDoAuthorizationReply) {
					$payPalDoAuthorizationCheckoutObject = Mage::getModel('eb2cpayment/paypal_do_authorization')->parseResponse($payPalDoAuthorizationReply);
					if ($payPalDoAuthorizationCheckoutObject) {
						if (strtoupper(trim($payPalDoAuthorizationCheckoutObject->getResponseCode())) === 'SUCCESS') {
							$response = array(
								'TRANSACTIONID' => $quote->getId(),
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cDebug){
			Mage::log(
				'[' . __CLASS__ . '] ' .
				"\n\rDEDUG:\n\r________________________\n\rcallDoAuthorization:\n\r" .
				print_r($response, true) . "\n\r",
				Zend_Log::DEBUG
			);
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cPaypalDoVoid) {
			// Eb2c PaypalDoVoid is enabled
			// Removing direct call to PayPal, Make Eb2c PayPalDoVoid call here.
			$quote = $this->_getCart()->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's Do PayPal Void it through eb2c.
				$payPalDoVoidReply = Mage::getModel('eb2cpayment/paypal_do_void')->doVoid($quote);
				if ($payPalDoVoidReply) {
					$payPalDoVoidCheckoutObject = Mage::getModel('eb2cpayment/paypal_do_void')->parseResponse($payPalDoVoidReply);
					if ($payPalDoVoidCheckoutObject) {
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

		if ((bool) Mage::helper('eb2cpayment')->getConfigModel()->enabledEb2cDebug){
			Mage::log('[' . __CLASS__ . '] ' . "\n\rDEDUG:\n\r________________________\n\rcallDoVoid:\n\r" . print_r($response, true) . "\n\r", Zend_Log::DEBUG);
		}
	}
}
