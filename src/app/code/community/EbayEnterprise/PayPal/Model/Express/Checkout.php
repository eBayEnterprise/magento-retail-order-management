<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wrapper that performs Paypal Express and Checkout communication
 * Use current Paypal Express method instance
 * @SuppressWarnings(TooManyMethods)
 * ^-- @TODO If __construct, nullCoalesce and checkTypes are always needed, this value should be +3 IMO
 * @SuppressWarnings(ExcessiveClassComplexity)
 * ^-- This will ignore overall class complexity (which does take WMC into consideration), but will *not* ignore too much complexity in individual methods
 */
class EbayEnterprise_PayPal_Model_Express_Checkout
{
	const EBAYENTERPRISE_PAYPAL_ZERO_CHECKOUT_NOT_SUPPORTED = 'EBAYENTERPRISE_PAYPAL_ZERO_CHECKOUT_NOT_SUPPORTED';
	/**
	 * Cache ID prefix for "pal" lookup
	 *
	 * @var string
	 */
	const EBAYENTERPRISE_PAL_CACHE_ID = 'ebayenterprise_paypal_express_checkout_pal';

	/**
	 * Keys for passthrough variables in sales/quote_payment and sales/order_payment
	 * Stored in additional_information
	 *
	 * @var string
	 */
	const PAYMENT_INFO_TOKEN = 'paypal_express_checkout_token';
	const PAYMENT_INFO_SHIPPING_OVERRIDDEN = 'paypal_express_checkout_shipping_overriden';
	const PAYMENT_INFO_SHIPPING_METHOD = 'paypal_express_checkout_shipping_method';
	const PAYMENT_INFO_PAYER_ID = 'paypal_express_checkout_payer_id';
	const PAYMENT_INFO_REDIRECT = 'paypal_express_checkout_redirect_required';
	const PAYMENT_INFO_BILLING_AGREEMENT = 'paypal_ec_create_ba';
	const PAYMENT_INFO_IS_AUTHORIZED_FLAG = 'is_authorized';
	const PAYMENT_INFO_IS_VOIDED_FLAG = 'is_voided';
	const PAYMENT_INFO_ADDRESS_STATUS  = 'address_status';

	/** @var string Flag from the request that indicates checkout was initiated outside normal checkout flow */
	const PAYMENT_INFO_BUTTON = 'button';

	/** @var Mage_Sales_Model_Quote */
	protected $_quote;
	/** @var EbayEnterprise_PayPal_Model_Config */
	protected $_config;
	/** @var EbayEnterprise_PayPal_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Paypal_Model_Express_Api */
	protected $_api;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/** @var Mage_Customer_Model_Session */
	protected $_customerSession;
	/** @var int */
	protected $_customerId;
	/** @var Mage_Sales_Model_Order */
	protected $_order;


	public function __construct(array $initParams = array())
	{
		list($this->_helper, $this->_logger, $this->_config, $this->_quote)
			= $this->_checkTypes(
			$this->_nullCoalesce(
				$initParams, 'helper', Mage::helper('ebayenterprise_paypal')
			),
			$this->_nullCoalesce(
				$initParams, 'logger', Mage::helper('ebayenterprise_magelog')
			),
			$this->_nullCoalesce(
				$initParams, 'config',
				Mage::helper('ebayenterprise_paypal')->getConfigModel()
			),
			$this->_nullCoalesce($initParams, 'quote', null)
		);
		if (!$this->_quote) {
			throw new Exception('Quote instance is required.');
		}
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param EbayEnterprise_PayPal_Helper_Data             $helper
	 * @param EbayEnterprise_MageLog_Helper_Data            $logger ,
	 * @param EbayEnterprise_Eb2cCore_Model_Config_Registry $config ,
	 * @param Mage_Sales_Model_Quote                        $quote  ,
	 *
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_PayPal_Helper_Data $helper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
		Mage_Sales_Model_Quote $quote
	) {
		return array($helper, $logger, $config, $quote);
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 *
	 * @param  array      $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed      $default
	 *
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Reserve order ID for specified quote and start checkout on PayPal
	 *
	 * @param bool|null $button specifies if we came from Checkout Stream or from Product/Cart directly
	 *
	 * @return mixed
	 */
	public function start($returnUrl, $cancelUrl, $button = null)
	{
		$this->_quote->collectTotals();
		if (!$this->_quote->getGrandTotal()
			&& !$this->_quote->hasNominalItems()
		) {
			Mage::throwException(
				$this->_helper->__(
					self::EBAYENTERPRISE_PAYPAL_ZERO_CHECKOUT_NOT_SUPPORTED
				)
			);
		}
		$this->_quote->reserveOrderId()->save();
		$this->_getApi();
		$setExpressCheckoutReply = $this->_api->setExpressCheckout(
			$returnUrl, $cancelUrl, $this->_quote
		);
		$this->_quote->getPayment()->importData($setExpressCheckoutReply);
		$this->_quote->getPayment()->save();
		return $setExpressCheckoutReply;
	}

	/**
	 * Setter for customer
	 *
	 * @param Mage_Customer_Model_Customer $customer
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	public function setCustomer($customer)
	{
		$this->_quote->assignCustomer($customer);
		$this->_customerId = $customer->getId();
		return $this;
	}

	/**
	 * Setter for customer with billing and shipping address changing ability
	 *
	 * @param  Mage_Customer_Model_Customer   $customer
	 * @param  Mage_Sales_Model_Quote_Address $billingAddress
	 * @param  Mage_Sales_Model_Quote_Address $shippingAddress
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	public function setCustomerWithAddressChange(
		$customer, $billingAddress = null, $shippingAddress = null
	) {
		$this->_quote->assignCustomerWithAddressChange(
			$customer, $billingAddress, $shippingAddress
		);
		$this->_customerId = $customer->getId();
		return $this;
	}

	/**
	 * Update quote when returned from PayPal
	 * rewrite billing address by paypal
	 * save old billing address for new customer
	 * export shipping address in case address absence
	 *
	 * @param string $token
	 */
	public function returnFromPaypal($token)
	{
		$this->_getApi();
		$quote = $this->_quote;
		$getExpressCheckoutReply = $this->_api->getExpressCheckout(
			$quote->reserveOrderId()->getReservedOrderId(), $token,
			Mage::app()->getStore()->getCurrentCurrencyCode()
		);

		$this->_ignoreAddressValidation();
		/* Always import shipping address. We would have passed the shipping address in to begin with, so they
			can keep it that way at PayPal if they like - or choose their own registered shipping address at PayPal. */
		$paypalShippingAddress = $getExpressCheckoutReply['shipping_address'];
		if (!$quote->getIsVirtual()) {
			$shippingAddress = $quote->getShippingAddress();
			$shippingAddress->setStreet($paypalShippingAddress['street']);
			$shippingAddress->setCity($paypalShippingAddress['city']);
			$shippingAddress->setRegion($paypalShippingAddress['region_code']);
			$shippingAddress->setPostcode($paypalShippingAddress['postcode']);
			$shippingAddress->setCountryId(
				$paypalShippingAddress['country_id']
			);
			$shippingAddress->setPrefix(null);

			$shippingAddress->setMiddlename(
				$getExpressCheckoutReply['middlename']
			);
			$shippingAddress->setLastname($getExpressCheckoutReply['lastname']);
			$shippingAddress->setFirstname(
				$getExpressCheckoutReply['firstname']
			);
			$shippingAddress->setSuffix($getExpressCheckoutReply['suffix']);
			$shippingAddress->setCollectShippingRates(true);
			$shippingAddress->setSameAsBilling(0);
			$quote->setShippingAddress($shippingAddress);
		}

		// Import billing address if we are here via Button - which is to say we didn't have a billing address yet:
		$portBillingFromShipping
			= $quote->getPayment()->getAdditionalInformation(
				self::PAYMENT_INFO_BUTTON
			) == 1
			&& !$quote->isVirtual();
		if ($portBillingFromShipping) {
			$billingAddress = clone $shippingAddress;
			$billingAddress->unsAddressId()->unsAddressType();
			$data = $billingAddress->getData();
			$data['save_in_address_book'] = 0;
			$quote->getBillingAddress()->addData($data);
			$quote->getShippingAddress()->setSameAsBilling(1);
		} else {
			$billingAddress = $quote->getBillingAddress();
		}
		$paypalBillingAddress = $getExpressCheckoutReply['billing_address'];
		$billingAddress->setStreet($paypalBillingAddress['street']);
		$billingAddress->setCity($paypalBillingAddress['city']);
		$billingAddress->setRegion($paypalBillingAddress['region_code']);
		$billingAddress->setPostcode($paypalBillingAddress['postcode']);
		$billingAddress->setCountryId($paypalBillingAddress['country_id']);
		$quote->setBillingAddress($billingAddress);

		// import payment info
		$quote->getPayment()
			->setAdditionalInformation(
				self::PAYMENT_INFO_PAYER_ID,
				$getExpressCheckoutReply['payer_id']
			)
			->setAdditionalInformation(
				self::PAYMENT_INFO_TOKEN, $token
			);
		$quote->collectTotals()->save();
	}

	/**
	 * Check whether order review has enough data to initialize
	 *
	 * @param $token
	 *
	 * @throws Mage_Core_Exception
	 */
	public function prepareOrderReview()
	{
		$payment = $this->_quote->getPayment();
		if (!$payment
			|| !$payment->getAdditionalInformation(
				self::PAYMENT_INFO_PAYER_ID
			)
		) {
			Mage::throwException(
				Mage::helper('paypal')->__('Payer is not identified.')
			);
		}
		$this->_quote->setMayEditShippingAddress(
			1 != $this->_quote->getPayment()->getAdditionalInformation(
				self::PAYMENT_INFO_SHIPPING_OVERRIDDEN
			)
		);
		$this->_ignoreAddressValidation();
		$this->_quote->collectTotals()->save();
	}

	/**
	 * Set shipping method to quote, if needed
	 *
	 * @param string $methodCode
	 */
	public function updateShippingMethod($methodCode)
	{
		$shippingAddress = $this->_quote->getShippingAddress();
		if ($methodCode && !$this->_quote->getIsVirtual() && $shippingAddress) {
			if ($methodCode != $shippingAddress->getShippingMethod()) {
				$this->_ignoreAddressValidation();
				$shippingAddress->setShippingMethod($methodCode)
					->setCollectShippingRates(true);
				$this->_quote->collectTotals()->save();
			}
		}
	}

	/**
	 * Prepare the quote according to the method (guest, registering new customer, or existing customer)
	 *
	 * @return boolean Whether we are a new customer
	 */
	protected function _prepareQuote()
	{
		$isNewCustomer = false;
		switch ($this->getCheckoutMethod()) {
			case Mage_Checkout_Model_Type_Onepage::METHOD_GUEST:
				$this->_prepareGuestQuote();
				break;
			case Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER:
				$customerId = $this->_lookupCustomerId();
				if ($customerId) {
					$this->_getCustomerSession()->loginById($customerId);
					$this->_prepareCustomerQuote();
				} else {
					$this->_prepareNewCustomerQuote();
				}
				$isNewCustomer = true;
				break;
			default:
				$this->_prepareCustomerQuote();
				break;
		}
		return $isNewCustomer;
	}

	/**
	 * Place the order and recurring payment profiles when customer returned from paypal
	 * Until this moment all quote data must be valid
	 *
	 * @param string $token
	 * @param string $shippingMethodCode
	 */
	public function place($token, $shippingMethodCode = null)
	{
		$this->updateShippingMethod($shippingMethodCode);
		$isNewCustomer = $this->_prepareQuote();
		$this->_ignoreAddressValidation();
		$this->_quote->collectTotals();
		$this->_getApi();
		$payerId = $this->_quote->getPayment()->getAdditionalInformation(
			self::PAYMENT_INFO_PAYER_ID
		);
		$doExpressReply = $this->_api->doExpressCheckout(
			$this->_quote, $token, $payerId
		);
		$doAuthorizationReply = $this->_api->doAuthorization($this->_quote);
		$this->_quote->getPayment()
			->importData(array_merge($doExpressReply, $doAuthorizationReply));
		$service = Mage::getModel('sales/service_quote', $this->_quote);
		$service->submitAll();
		$this->_quote->save();

		if ($isNewCustomer) {
			try {
				$this->_involveNewCustomer();
			} catch (Exception $e) {
				$this->_logger->logException($e);
			}
		}

		$order = $service->getOrder();
		if (!$order) {
			return;
		}

		switch ($order->getState()) {
			// Even after placement, paypal can disallow authorize/capture
			case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
				break;
			// regular placement, when everything is ok
			case Mage_Sales_Model_Order::STATE_PROCESSING:
			case Mage_Sales_Model_Order::STATE_COMPLETE:
			case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
				$order->sendNewOrderEmail();
				break;
		}
		$this->_order = $order;

		Mage::dispatchEvent(
			'checkout_submit_all_after',
			array('order' => $order, 'quote' => $this->_quote)
		);
	}

	/**
	 * Make sure addresses will be saved without validation errors
	 */
	private function _ignoreAddressValidation()
	{
		$this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
		if (!$this->_quote->getIsVirtual()) {
			$this->_quote->getShippingAddress()->setShouldIgnoreValidation(
				true
			);
		}
	}

	/**
	 * Return order
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return $this->_order;
	}

	/**
	 * Get checkout method
	 *
	 * @return string
	 */
	public function getCheckoutMethod()
	{
		if ($this->_getCustomerSession()->isLoggedIn()) {
			return Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;
		}
		if (!$this->_quote->getCheckoutMethod()) {
			if (Mage::helper('checkout')->isAllowedGuestCheckout(
				$this->_quote
			)
			) {
				$this->_quote->setCheckoutMethod(
					Mage_Checkout_Model_Type_Onepage::METHOD_GUEST
				);
			} else {
				$this->_quote->setCheckoutMethod(
					Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER
				);
			}
		}
		return $this->_quote->getCheckoutMethod();
	}

	/**
	 * @return EbayEnterprise_Paypal_Model_Express_Api
	 */
	protected function _getApi()
	{
		if (!$this->_api) {
			$this->_api = Mage::getModel('ebayenterprise_paypal/express_api');
		}
		return $this->_api;
	}

	/**
	 * Prepare quote for guest checkout order submit
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	protected function _prepareGuestQuote()
	{
		$quote = $this->_quote;
		$quote->setCustomerId(null)
			->setCustomerEmail($quote->getBillingAddress()->getEmail())
			->setCustomerIsGuest(true)
			->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
		return $this;
	}

	/**
	 * Checks if customer with email coming from Express checkout exists
	 *
	 * @return int
	 */
	protected function _lookupCustomerId()
	{
		return Mage::getModel('customer/customer')
			->setWebsiteId(Mage::app()->getWebsite()->getId())
			->loadByEmail($this->_quote->getCustomerEmail())
			->getId();
	}

	/**
	 * Prepare quote for customer registration and customer order submit
	 * and restore magento customer data from quote
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	protected function _prepareNewCustomerQuote()
	{
		$quote = $this->_quote;
		$billing = $quote->getBillingAddress();
		$shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();

		$customer = $quote->getCustomer();
		/** @var $customer Mage_Customer_Model_Customer */
		$customerBilling = $billing->exportCustomerAddress();
		$customer->addAddress($customerBilling);
		$billing->setCustomerAddress($customerBilling);
		$customerBilling->setIsDefaultBilling(true);
		if ($shipping && !$shipping->getSameAsBilling()) {
			$customerShipping = $shipping->exportCustomerAddress();
			$customer->addAddress($customerShipping);
			$shipping->setCustomerAddress($customerShipping);
			$customerShipping->setIsDefaultShipping(true);
		} elseif ($shipping) {
			$customerBilling->setIsDefaultShipping(true);
		}
		$this->_setAdditionalCustomerBillingData();
		Mage::helper('core')->copyFieldset(
			'checkout_onepage_billing', 'to_customer', $billing, $customer
		);
		$customer->setEmail($quote->getCustomerEmail());
		$customer->setPrefix($quote->getCustomerPrefix());
		$customer->setFirstname($quote->getCustomerFirstname());
		$customer->setMiddlename($quote->getCustomerMiddlename());
		$customer->setLastname($quote->getCustomerLastname());
		$customer->setSuffix($quote->getCustomerSuffix());
		$customer->setPassword(
			$customer->decryptPassword($quote->getPasswordHash())
		);
		$customer->setPasswordHash(
			$customer->hashPassword($customer->getPassword())
		);
		$customer->save();
		$quote->setCustomer($customer);
		return $this;
	}

	/**
	 * Set additional customer billing data
	 *
	 * @return self
	 */
	protected function _setAdditionalCustomerBillingData()
	{
		$quote = $this->_quote;
		$billing = $quote->getBillingAddress();
		if ($quote->getCustomerDob() && !$billing->getCustomerDob()) {
			$billing->setCustomerDob($quote->getCustomerDob());
		}
		if ($quote->getCustomerTaxvat() && !$billing->getCustomerTaxvat()) {
			$billing->setCustomerTaxvat($quote->getCustomerTaxvat());
		}
		if ($quote->getCustomerGender() && !$billing->getCustomerGender()) {
			$billing->setCustomerGender($quote->getCustomerGender());
		}
		return $this;
	}

	/**
	 * Prepare quote customer address information and set the customer on the quote
	 *
	 * @return self
	 */
	protected function _prepareCustomerQuote()
	{
		$shipping = $this->_quote->isVirtual() ? null
			: $this->_quote->getShippingAddress();
		$customer = $this->_getCustomerSession()->getCustomer();
		$customerBilling = $this->_prepareCustomerBilling($customer);
		if ($shipping) {
			$customerShipping = $this->_prepareCustomerShipping(
				$customer, $shipping
			);
			if ($customerBilling && !$customer->getDefaultShipping()
				&& $shipping->getSameAsBilling()
			) {
				$customerBilling->setIsDefaultShipping(true);
			} elseif (isset($customerShipping)
				&& !$customer->getDefaultShipping()
			) {
				$customerShipping->setIsDefaultShipping(true);
			}
		}
		$this->_quote->setCustomer($customer);
		return $this;
	}

	/**
	 * Set up the billing address for the quote and on the customer, and set the customer's
	 * default billing address.
	 *
	 * @param $customer Mage_Customer_Model_Customer
	 *
	 * @return Mage_Sales_Model_Quote_Address $billingAddress | null
	 */
	protected function _prepareCustomerBilling(
		Mage_Customer_Model_Customer $customer
	) {
		$billing = $this->_quote->getBillingAddress();
		if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
			$customerBilling = $billing->exportCustomerAddress();
			$customer->addAddress($customerBilling);
			$billing->setCustomerAddress($customerBilling);
			if (!$customer->getDefaultBilling()) {
				$customerBilling->setIsDefaultBilling(true);
			}
			return $customerBilling;
		}
		return null;
	}

	/**
	 * Setup shipping address and set as customer default if indicated.
	 *
	 * @param                                $customer Mage_Customer_Model_Customer
	 * @param Mage_Sales_Model_Quote_Address $shipping
	 *
	 * @return Mage_Sales_Model_Quote_Address $shipping | null
	 */
	protected function _prepareCustomerShipping(
		Mage_Customer_Model_Customer $customer,
		Mage_Sales_Model_Quote_Address $shipping
	) {
		if ($shipping
			&& ((!$shipping->getCustomerId() && !$shipping->getSameAsBilling())
				|| (!$shipping->getSameAsBilling()
					&& $shipping->getSaveInAddressBook()))
		) {
			$customerShipping = $shipping->exportCustomerAddress();
			$customer->addAddress($customerShipping);
			$shipping->setCustomerAddress($customerShipping);
			return $customerShipping;
		}
		return null;
	}

	/**
	 * Involve new customer to system
	 *
	 * @return self
	 */
	protected function _involveNewCustomer()
	{
		$customer = $this->_quote->getCustomer();
		if ($customer->isConfirmationRequired()) {
			$customer->sendNewAccountEmail('confirmation');
			$url = Mage::helper('customer')->getEmailConfirmationUrl(
				$customer->getEmail()
			);
			$this->_getCustomerSession()->addSuccess(
				Mage::helper('customer')->__(
					'Account confirmation is required. Please, check your e-mail for confirmation link. To resend confirmation email please <a href="%s">click here</a>.',
					$url
				)
			);
		} else {
			$customer->sendNewAccountEmail();
			$this->_getCustomerSession()->loginById($customer->getId());
		}
		return $this;
	}

	/**
	 * Set customer session object
	 *
	 * @return self
	 */
	public function setCustomerSession(
		Mage_Customer_Model_Session $customerSession
	) {
		$this->_customerSession = $customerSession;
		return $this;
	}

	/**
	 * Get customer session object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getCustomerSession()
	{
		return $this->_customerSession;
	}
}
