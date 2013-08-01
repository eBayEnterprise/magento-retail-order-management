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

	protected function _getPaypalSetExpressCheckout()
	{
		if (!$this->_paypalSetExpressCheckout) {
			$this->_paypalSetExpressCheckout = Mage::getModel('eb2cpayment/paypal_set_express_checkout');
		}

		return $this->_paypalSetExpressCheckout;
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
	 * Override to make eb2c PayPalSetExpressCheckout call
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
			$quote = $this->_cart->getSalesEntity();

			$response = array();

			if ($quote) {
				// We have a valid quote, let's set PayPal Express checkout it through eb2c.
				if ($payPalSetExpressCheckoutReply = $this->_getPaypalSetExpressCheckout()->setExpressCheckout($quote)) {
					if ($payPalSetExpressCheckoutData = $this->_getPaypalSetExpressCheckout()->parseResponse($payPalSetExpressCheckoutReply)) {
						// making sure we have the right data
						if (isset($payPalSetExpressCheckoutData['responseCode']) && strtoupper(trim($payPalSetExpressCheckoutData['responseCode'])) === 'SUCCESS') {
							$response = array(
								'TOKEN' => $payPalSetExpressCheckoutData['token'],
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
}
