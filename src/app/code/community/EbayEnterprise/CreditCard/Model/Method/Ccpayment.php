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

use eBayEnterprise\RetailOrderManagement\Api;
use eBayEnterprise\RetailOrderManagement\Payload;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EbayEnterprise_CreditCard_Model_Method_Ccpayment extends Mage_Payment_Model_Method_Cc
{
	const CREDITCARD_AUTH_FAILED_MESSAGE = 'EbayEnterprise_CreditCard_Auth_Failed';
	const CREDITCARD_AVS_FAILED_MESSAGE = 'EbayEnterprise_CreditCard_AVS_Failed';
	const CREDITCARD_CVV_FAILED_MESSAGE = 'EbayEnterprise_CreditCard_CVV_Failed';
	const METHOD_NOT_ALLOWED_FOR_COUNTRY = 'EbayEnterprise_CreditCard_Method_Not_Allowed_For_Country';
	const INVALID_EXPIRATION_DATE = 'EbayEnterprise_CreditCard_Invalid_Expiration_Date';
	const INVALID_CARD_TYPE = 'EbayEnterprise_CreditCard_Invalid_Card_Type';
	/**
	 * Block type to use to render the payment method form.
	 * @var string
	 */
	protected $_formBlockType = 'ebayenterprise_creditcard/form_cc';
	/**
	 * Code unique to this payment method.
	 * @var string
	 */
	protected $_code          = 'ebayenterprise_creditcard';
	/**
	 * Is this payment method a gateway (online auth/charge) ?
	 */
	protected $_isGateway               = false;

	/**
	 * Can authorize online?
	 */
	protected $_canAuthorize            = true;

	/**
	 * Can capture funds online?
	 */
	protected $_canCapture              = false;

	/**
	 * Can capture partial amounts online?
	 */
	protected $_canCapturePartial       = false;

	/**
	 * Can refund online?
	 */
	protected $_canRefund               = false;

	/**
	 * Can void transactions online?
	 */
	protected $_canVoid                 = false;

	/**
	 * Can use this payment method in administration panel?
	 */
	protected $_canUseInternal          = true;

	/**
	 * Can show this payment method as an option on checkout payment page?
	 */
	protected $_canUseCheckout          = true;

	/**
	 * Is this payment method suitable for multi-shipping checkout?
	 */
	protected $_canUseForMultishipping  = true;

	/**
	 * Can save credit card information for future processing?
	 */
	protected $_canSaveCc = true;

	/** @var EbayEnterprise_CreditCard_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var Mage_Core_Helper_Http */
	protected $_httpHelper;

	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/** @var LoggerInterface */
	protected $_apiLogger;

	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;

	/** @var bool */
	protected $_isUsingClientSideEncryption;
	/**
	 * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
	 * Override __construct here as the usual protected `_construct` is not called.
	 * @param array $initParams May contain:
	 *                          -  'helper' => EbayEnterprise_CreditCard_Helper_Data
	 *                          -  'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          -  'http_helper' => Mage_Core_Helper_Http
	 *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
	 *                          -  'context' => EbayEnterprise_MageLog_Helper_Context
	 *                          -  'api_logger' => LoggerInterface
	 */
	public function __construct(array $initParams = [])
	{
		list(
			$this->_helper,
			$this->_coreHelper,
			$this->_httpHelper,
			$this->_logger,
			$this->_context,
			$this->_apiLogger
		) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_creditcard')),
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'http_helper', Mage::helper('core/http')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context')),
			$this->_nullCoalesce($initParams, 'api_logger', new NullLogger)
		);
		$this->_isUsingClientSideEncryption = $this->_helper->getConfigModel()->useClientSideEncryptionFlag;
	}
	/**
	 * Type hinting for self::__construct $initParams
	 * @param EbayEnterprise_CreditCard_Helper_Data
	 * @param EbayEnterprise_Eb2cCore_Helper_Data
	 * @param Mage_Core_Helper_Http
	 * @param Mage_Checkout_Model_Session
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Context
	 * @param LoggerInterface
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_CreditCard_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		Mage_Core_Helper_Http $httpHelper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $context,
		LoggerInterface $apiLogger
	) {
		return func_get_args();
	}
	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 * @param array      $arr
	 * @param string|int $field Valid array key
	 * @param mixed      $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	/**
	 * Get the session model to store checkout data in.
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckoutSession()
	{
		return Mage::getSingleton('checkout/session');
	}
	/**
	 * Override getting config data for the cctype configuration. Due to the
	 * special requirements for what types are actually available (must be
	 * mapped ROM tender type), when requesting configured cctypes, get the types
	 * that are actually available.
	 * @param string $field
	 * @param int|string|null|Mage_Core_Model_Store $storeId
	 * @return mixed
	 */
	public function getConfigData($field, $storeId = null)
	{
		if ($field === 'cctypes') {
			return implode(',', array_keys($this->_helper->getAvailableCardTypes()));
		}
		return parent::getConfigData($field, $storeId);
	}
	/**
	 * Assign post data to the payment info object.
	 * @param array|Varien_Object $data Contains payment data submitted in checkout - Varien_Object in OPC, array otherwise
	 * @return self
	 */
	public function assignData($data)
	{
		parent::assignData($data);
		if (is_array($data)) {
			$data = Mage::getModel('Varien_Object', $data);
		}
		if ($this->_isUsingClientSideEncryption) {
			$this->getInfoInstance()->setCcLast4($data->getCcLast4());
		}
		return $this;
	}
	/**
	 * Validate card data.
	 * @return self
	 */
	public function validate()
	{
		// card type can and should always be validated as data is not encrypted
		$this->_validateCardType();
		if ($this->_isUsingClientSideEncryption) {
			return $this->_validateWithEncryptedCardData();
		} else {
			return parent::validate();
		}
	}
	/**
	 * Validate what data can still be validated.
	 * @return self
	 */
	protected function _validateWithEncryptedCardData()
	{
		$info = $this->getInfoInstance();
		return $this->_validateCountry($info)->_validateExpirationDate($info);
	}
	/**
	 * Validate that the card type is one of the supported types.
	 * @return self
	 * @throws EbayEnterprise_CreditCard_Exception If card type is not supported
	 */
	protected function _validateCardType()
	{
		if (!in_array($this->getInfoInstance()->getCcType(), array_keys($this->_helper->getAvailableCardTypes()))) {
			throw Mage::exception('EbayEnterprise_CreditCard', self::INVALID_CARD_TYPE);
		}
		return $this;
	}
	/**
	 * Validate payment method is allowed for the customer's billing address country.
	 * @param Mage_Payment_Model_Info $info
	 * @return self
	 */
	protected function _validateCountry(Mage_Payment_Model_Info $info)
	{
		/**
		 * Get the order when dealing with an order payment, quote otherwise.
		 * @see Mage_Payment_Model_Method_Abstract
		 */
		if ($info instanceof Mage_Sales_Model_Order_Payment) {
			$billingCountry = $info->getOrder()->getBillingAddress()->getCountryId();
		} else {
			$billingCountry = $info->getQuote()->getBillingAddress()->getCountryId();
		}
		if (!$this->canUseForCountry($billingCountry)) {
			throw Mage::exception('EbayEnterprise_CreditCard', $this->_helper->__(self::METHOD_NOT_ALLOWED_FOR_COUNTRY));
		}
		return $this;
	}
	/**
	 * Validate the card expiration date.
	 * @param Mage_Payment_Model_Info $info
	 * @return self
	 */
	protected function _validateExpirationDate(Mage_Payment_Model_Info $info)
	{
		if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
			throw Mage::exception('EbayEnterprise_CreditCard', $this->_helper->__(self::INVALID_EXPIRATION_DATE));
		}
		return $this;
	}
	/**
	 * Authorize payment abstract method
	 *
	 * @param Varien_Object $payment
	 * @param float         $amount unused; only here to maintain signature
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function authorize(Varien_Object $payment, $amount)
	{
		$api = $this->_getApi($payment);
		$this->_prepareApiRequest($api, $payment);
		// Log the request instead of expecting the SDK to have logged it.
		// Allows the data to be properly scrubbed of any PII or other sensitive
		// data prior to writing the log files.
		$logMessage = 'Sending credit card auth request.';
		$cleanedRequestXml = $this->_helper->cleanAuthXml($api->getRequestBody()->serialize());
		$this->_logger->debug($logMessage, $this->_context->getMetaData(__CLASS__, ['rom_request_body' => $cleanedRequestXml]));
		$this->_sendAuthRequest($api);
		// Log the response instead of expecting the SDK to have logged it.
		// Allows the data to be properly scrubbed of any PII or other sensitive
		// data prior to writing the log files.
		$logMessage = 'Received credit card auth response.';
		$cleanedResponseXml = $this->_helper->cleanAuthXml($api->getResponseBody()->serialize());
		$this->_logger->debug($logMessage, $this->_context->getMetaData(__CLASS__, ['rom_request_body' => $cleanedResponseXml]));
		$this->_handleApiResponse($api, $payment);
		return $this;
	}
	/**
	 * Fill out the request payload with payment data and update the API request
	 * body with the complete request.
	 * @param Api\IBidirectionalApi $api
	 * @param Varien_Object         $payment Most likely a Mage_Sales_Model_Order_Payment
	 * @return self
	 */
	protected function _prepareApiRequest(Api\IBidirectionalApi $api, Varien_Object $payment)
	{
		$request = $api->getRequestBody();
		$order = $payment->getOrder();
		$billingAddress = $order->getBillingAddress();
		$shippingAddress = $order->getShippingAddress() ?: $billingAddress;
		$request
			->setIsEncrypted($this->_isUsingClientSideEncryption)
			->setRequestId($this->_coreHelper->generateRequestId('CCA-'))
			->setOrderId($order->getIncrementId())
			->setPanIsToken(false)
			->setCardNumber($payment->getCcNumber())
			->setExpirationDate($this->_coreHelper->getNewDateTime(sprintf('%s-%s', $payment->getCcExpYear(), $payment->getCcExpMonth())))
			->setCardSecurityCode($payment->getCcCid())
			->setAmount($payment->getBaseAmountAuthorized())
			->setCurrencyCode(Mage::app()->getStore()->getBaseCurrencyCode())
			->setEmail($order->getCustomerEmail())
			->setIp($this->_httpHelper->getRemoteAddr())
			->setBillingFirstName($billingAddress->getFirstname())
			->setBillingLastName($billingAddress->getLastname())
			->setBillingPhone($billingAddress->getTelephone())
			->setBillingLines($billingAddress->getStreet(-1)) // returns all lines, \n separated
			->setBillingCity($billingAddress->getCity())
			->setBillingMainDivision($billingAddress->getRegionCode())
			->setBillingCountryCode($billingAddress->getCountry())
			->setBillingPostalCode($billingAddress->getPostcode())
			->setShipToFirstName($shippingAddress->getFirstname())
			->setShipToLastName($shippingAddress->getLastname())
			->setShipToPhone($shippingAddress->getTelephone())
			->setShipToLines($shippingAddress->getStreet(-1)) // returns all lines, \n separated
			->setShipToCity($shippingAddress->getCity())
			->setShipToMainDivision($shippingAddress->getRegionCode())
			->setShipToCountryCode($shippingAddress->getCountry())
			->setShipToPostalCode($shippingAddress->getPostcode())
			->setIsRequestToCorrectCVVOrAVSError($this->_getIsCorrectionNeededForPayment($payment));
		return $this;
	}
	/**
	 * Check for the response to be valid.
	 * @param Payload\Payment\ICreditCardAuthReply $response
	 * @return self
	 */
	protected function _validateResponse(Payload\Payment\ICreditCardAuthReply $response)
	{
		// if auth was a complete success, accept the response and move on
		if ($response->getIsAuthSuccessful()) {
			return $this;
		}
		// if AVS correction is needed, redirect to billing address step
		if ($response->getIsAVSCorrectionRequired()) {
			$this->_failPaymentAuth(self::CREDITCARD_AVS_FAILED_MESSAGE, 'billing');
		}
		// if CVV correction is needed, redirect to payment method step
		if ($response->getIsCVV2CorrectionRequired()) {
			$this->_failPaymentAuth(self::CREDITCARD_CVV_FAILED_MESSAGE, 'payment');
		}
		// if AVS & CVV did not fail but was not a complete success, see if the
		// request is at least acceptable - timeout perhaps - and if so, take it
		// and allow order submit to continue
		if ($response->getIsAuthAcceptable()) {
			return $this;
		}
		// auth failed for some other reason, possibly declined, making it unacceptable
		// send user to payment step of checkout with an error message
		$this->_failPaymentAuth(self::CREDITCARD_AUTH_FAILED_MESSAGE, 'payment');
	}
	/**
	 * Update the order payment and quote payment with details from the CC auth
	 * request/response.
	 * @param Varien_Data $payment
	 * @param Payload\Payment\ICreditCardAuthRequest $request
	 * @param Payload\Payment\ICreditCardAuthReply   $response
	 * @return self
	 */
	protected function _updatePaymentsWithAuthData(
		Varien_Object $payment,
		Payload\Payment\ICreditCardAuthRequest $request,
		Payload\Payment\ICreditCardAuthReply $response
	) {
		return $this
			->_updatePayment($payment, $request, $response)
			->_updatePayment($payment->getOrder()->getQuote()->getPayment(), $request, $response);
	}
	/**
	 * Update the payment with details from the CC Auth Request and Reply
	 * @param Varien_Object                          $payment
	 * @param Payload\Payment\ICreditCardAuthRequest $request
	 * @param Payload\Payment\ICreditCardAuthReply   $response
	 * @return self
	 */
	public function _updatePayment(
		Varien_Object $payment,
		Payload\Payment\ICreditCardAuthRequest $request,
		Payload\Payment\ICreditCardAuthReply $response
	) {
		$correctionRequired = $response->getIsAVSCorrectionRequired() || $response->getIsCVV2CorrectionRequired();
		$payment->setAdditionalInformation([
			'request_id' => $request->getRequestId(),
			'response_code' => $response->getResponseCode(),
			'pan_is_token' => $response->getPanIsToken(),
			'bank_authorization_code' => $response->getBankAuthorizationCode(),
			'cvv2_response_code' => $response->getCVV2ResponseCode(),
			'avs_response_code' => $response->getAVSResponseCode(),
			'phone_response_code' => $response->getPhoneResponseCode(),
			'name_response_code' => $response->getNameResponseCode(),
			'email_response_code' => $response->getEmailResponseCode(),
			'currency_code' => $response->getCurrencyCode(),
			'tender_type' => $this->_helper->getTenderTypeForCcType($payment->getCcType()),
			'is_correction_required' => $correctionRequired,
			'last4_to_correct' => $correctionRequired ? $payment->getCcLast4() : null,
		])
			->setAmountAuthorized($response->getAmountAuthorized())
			->setBaseAmountAuthorized($response->getAmountAuthorized())
			->setCcNumberEnc($payment->encrypt($response->getCardNumber()));
		return $this;
	}
	/**
	 * Check if the payment needs to be corrected - payment additional information
	 * would have the is_correction_required flag set to true and the cc last 4
	 * for the current payment would match the last4_to_correct payment
	 * additional information.
	 * @param Varien_Object $payment
	 * @return bool
	 */
	protected function _getIsCorrectionNeededForPayment(Varien_Object $payment)
	{
		return $payment->getAdditionalInformation('is_correction_required')
			&& $payment->getCcLast4() === $payment->getAdditionalInformation('last4_to_correct');
	}
	/**
	 * Set the checkout session's goto section to the provided step.
	 * One of: 'login', 'billing', 'shipping', 'shipping_method', 'payment', 'review'
	 * @param string $step Step in checkout
	 * @return self
	 */
	public function _setCheckoutStep($step)
	{
		$this->_getCheckoutSession()->setGotoSection($step);
		return $this;
	}
	/**
	 * Fail the auth request by setting a checkout step to return to and throwing
	 * an exception.
	 * @see self::_setCheckoutStep for available checkout steps to return to
	 * @param string $errorMessage
	 * @param string $returnStep Step of checkout to send the user to
	 * @throws EbayEnterprise_CreditCard_Exception Always
	 */
	protected function _failPaymentAuth($errorMessage, $returnStep = 'payment')
	{
		$this->_setCheckoutStep($returnStep);
		throw Mage::exception('EbayEnterprise_CreditCard', $this->_helper->__($errorMessage));
	}
	/**
	 * Get the API SDK for the payment auth request.
	 * @param Varien_Object $payment
	 * @return Api\IBidirectionalApi
	 */
	protected function _getApi(Varien_Object $payment)
	{
		$config = $this->_helper->getConfigModel();
		return $this->_coreHelper->getSdkApi(
			$config->apiService,
			$config->apiOperation,
			[$this->_helper->getTenderTypeForCcType($payment->getCcType())],
			// Provide a logger specifically for logging data within the SDK.
			// Logger provided should prevent the logging of any PII within
			// the SDK.
			$this->_apiLogger
		);
	}
	/**
	 * Make the API request and handle any exceptions.
	 * @param ApiIBidirectionalApi $api
	 * @return self
	 */
	protected function _sendAuthRequest(Api\IBidirectionalApi $api)
	{
		try {
			$api->send();
		} catch (Payload\Exception\InvalidPayload $e) {
			// Invalid payloads cannot be valid - log the error and fail the auth
			$logData = ['error_message' => $e->getMessage()];
			$logMessage = 'Credit card auth payload invalid: {error_message}';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
			$this->_failPaymentAuth(self::CREDITCARD_AUTH_FAILED_MESSAGE);
		} catch (Api\Exception\NetworkError $e) {
			// Can't accept an auth request that could not be made successfully - log
			// the error and fail the auth.
			$logData = ['error_message' => $e->getMessage()];
			$logMessage = 'Credit card auth request failed: {error_message}';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
			$this->_failPaymentAuth(self::CREDITCARD_AUTH_FAILED_MESSAGE);
		}
		return $this;
	}
	/**
	 * Update payment objects with details of the auth request and response. Validate
	 * that a successful response was received.
	 * @param ApiIBidirectionalApi $api
	 * @param Varien_Object        $payment
	 * @return self
	 */
	protected function _handleApiResponse(Api\IBidirectionalApi $api, Varien_Object $payment)
	{
		$request = $api->getRequestBody();
		$response = $api->getResponseBody();
		return $this->_updatePaymentsWithAuthData($payment, $request, $response)->_validateResponse($response);
	}
}
