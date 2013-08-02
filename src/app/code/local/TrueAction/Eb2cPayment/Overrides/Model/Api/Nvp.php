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
	 * Get paypal cart instantiated object.
	 *
	 * @return Mage_Paypal_Model_Cart
	 */
	protected function _getCart()
	{
		if (!$this->_cart) {
			$this->_cart = Mage::getModel('paypal/cart', array(Mage::getSingleton('checkout/session')->getQuote()));
		}

		return $this->_cart;
	}

	/**
	 * Override to make eb2c PayPalSetExpressCheckout call
	 *
	 * SetExpressCheckout call
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout
	 * TODO: put together style and giropay settings
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
					if ($payPalSetExpressCheckoutData = $this->_getPaypalSetExpressCheckout()->parseResponse($payPalSetExpressCheckoutReply)) {
						// making sure we have the right data
						if (isset($payPalSetExpressCheckoutData['responseCode']) && strtoupper(trim($payPalSetExpressCheckoutData['responseCode'])) === 'SUCCESS') {
							$response = array(
								'TOKEN' => 'EC-4TJ32438CM668953D', //$payPalSetExpressCheckoutData['token'],
								'ACK' => $payPalSetExpressCheckoutData['responseCode'],
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalSetExpressCheckout is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::SET_EXPRESS_CHECKOUT, $request);
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
					if ($payPalGetExpressCheckoutData = $this->_getPaypalGetExpressCheckout()->parseResponse($payPalGetExpressCheckoutReply)) {
						// making sure we have the right data
						$quoteShippingAddress = $quote->getShippingAddress();
						if (isset($payPalGetExpressCheckoutData['responseCode']) && strtoupper(trim($payPalGetExpressCheckoutData['responseCode'])) === 'SUCCESS') {
							$response = array(
								'ACK' => $payPalGetExpressCheckoutData['responseCode'],
								'EMAIL' => $payPalGetExpressCheckoutData['payerEmail'],
								'PAYERID' => $payPalGetExpressCheckoutData['payerId'],
								'PAYERSTATUS' => $payPalGetExpressCheckoutData['payerStatus'],
								'FIRSTNAME' => $payPalGetExpressCheckoutData['payerName']['firstName'],
								'LASTNAME' => $payPalGetExpressCheckoutData['payerName']['lastName'],
								'COUNTRYCODE' => $payPalGetExpressCheckoutData['payerCountry'],
								'SHIPTONAME' => $quoteShippingAddress->getName(),
								'SHIPTOSTREET' => $payPalGetExpressCheckoutData['shippingAddress']['line1'],
								'SHIPTOCITY' => $payPalGetExpressCheckoutData['shippingAddress']['city'],
								'SHIPTOSTATE' => $payPalGetExpressCheckoutData['shippingAddress']['mainDivision'],
								'SHIPTOZIP' => $payPalGetExpressCheckoutData['shippingAddress']['postalCode'],
								'SHIPTOCOUNTRYCODE' => $payPalGetExpressCheckoutData['shippingAddress']['countryCode'],
								'SHIPTOPHONENUM' => $payPalGetExpressCheckoutData['payerPhone'],
								'ADDRESSSTATUS' => $payPalGetExpressCheckoutData['shippingAddress']['addressStatus'],
							);
						}
					}
				}
			}
		} else {
			// Eb2c PaypalGetExpressCheckout is disabled, continue as normal with direct call to the paypal api
			$response = $this->call(self::GET_EXPRESS_CHECKOUT_DETAILS, $request);
		}

		$this->_importFromResponse($this->_paymentInformationResponse, $response);
		$this->_exportAddressses($response);
	}
}
