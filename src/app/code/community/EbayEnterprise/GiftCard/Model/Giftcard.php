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

class EbayEnterprise_GiftCard_Model_Giftcard implements EbayEnterprise_GiftCard_Model_IGiftcard
{
	const BALANCE_REQUST_ID_PREFIX = 'GCB-';
	const REDEEM_REQUST_ID_PREFIX = 'GCR-';
	const VOID_REQUST_ID_PREFIX = 'GCV-';
	const REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed';
	const BALANCE_REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed_Balance';
	const REDEEM_REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed_Redeem';
	const VOID_REQUEST_FAILED_MESSAGE = 'EbayEnterprise_GiftCard_Request_Failed_Void';
	/** @var bool **/
	protected $_panIsToken;
	/** @var string **/
	protected $_orderId;
	/** @var string **/
	protected $_cardNumber;
	/** @var string **/
	protected $_pin;
	/** @var string **/
	protected $_tokenizedCardNumber;
	/** @var string */
	protected $_balanceRequestId;
	/** @var string */
	protected $_redeemRequestId;
	/** @var string */
	protected $_redeemVoidRequestId;
	/** @var float **/
	protected $_amountToRedeem;
	/** @var float **/
	protected $_amountRedeemed;
	/** @var DateTime **/
	protected $_redeemedAt;
	/** @var string **/
	protected $_redeemCurrencyCode;
	/** @var float **/
	protected $_balanceAmount;
	/** @var string **/
	protected $_balanceCurrencyCode;
	/** @var bool **/
	protected $_isRedeemed;
	/** @var EbayEnterprise_GiftCard_Helper_Data **/
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data **/
	protected $_coreHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data **/
	protected $_logger;
	/** @var LoggerInterface **/
	protected $_apiLogger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;
	/**
	 * @param array $initParams May contain:
	 *                          - 'helper' => EbayEnterprise_GiftCard_Helper_Data
	 *                          - 'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
	 *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
	 *                          - 'api_logger' => LoggerInterface
	 */
	public function __construct(array $initParams = [])
	{
		list(
			$this->_helper,
			$this->_coreHelper,
			$this->_logger,
			$this->_context,
			$this->_apiLogger
		) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'helper', Mage::helper('ebayenterprise_giftcard')),
			$this->_nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context')),
			$this->_nullCoalesce($initParams, 'api_logger', new NullLogger)
		);
	}
	/**
	 * Type checks for self::__construct $initParams.
	 * @param  EbayEnterprise_Giftcard_Helper_Data $helper
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	 * @param  EbayEnterprise_MageLog_Helper_Data $logger
	 * @param  EbayEnterprise_MageLog_Helper_Context $context
	 * @param  LoggerInterface
	 * @return mixed[]
	 */
	protected function _checkTypes(
		EbayEnterprise_Giftcard_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $context,
		LoggerInterface $apiLogger
	) {
		return func_get_args();
	}
	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 * @param  array      $arr
	 * @param  string|int $field Valid array key
	 * @param  mixed      $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}
	public function setOrderId($orderId)
	{
		$this->_orderId = $orderId;
		return $this;
	}
	public function getOrderId()
	{
		return $this->_orderId;
	}
	/**
	 * Set the card number of the gift card. If the card number is changed after
	 * receiving a tokenized card number, the tokenized card number will be
	 * cleared out as it is not expected to be relevant to the new card number.
	 * @param string $cardNumber
	 * @return self
	 */
	public function setCardNumber($cardNumber)
	{
		// Tokenized number is no longer valid after changing the non-tokenized number.
		if ($this->_cardNumber !== $cardNumber) {
			$this->setTokenizedCardNumber(null);
		}
		$this->_cardNumber = $cardNumber;
		return $this;
	}
	public function getCardNumber()
	{
		return $this->_cardNumber;
	}
	public function getTenderType()
	{
		return $this->_helper->lookupTenderTypeForCard($this);
	}
	public function setTokenizedCardNumber($tokenizedCardNumber)
	{
		$this->_tokenizedCardNumber = $tokenizedCardNumber;
		return $this;
	}
	public function getTokenizedCardNumber()
	{
		return $this->_tokenizedCardNumber;
	}
	public function setPin($pin)
	{
		$this->_pin = $pin;
		return $this;
	}
	public function getPin()
	{
		return $this->_pin;
	}
	public function setPanIsToken($isToken)
	{
		$this->_panIsToken = $isToken;
		return $this;
	}
	public function getPanIsToken()
	{
		return $this->_panIsToken;
	}
	public function setBalanceRequestId($requestId)
	{
		$this->_balanceRequestId = $requestId;
		return $this;
	}
	public function getBalanceRequestId()
	{
		return $this->_balanceRequestId;
	}
	public function setRedeemRequestId($requestId)
	{
		$this->_redeemRequestId = $requestId;
		return $this;
	}
	public function getRedeemRequestId()
	{
		return $this->_redeemRequestId;
	}
	public function setRedeemVoidRequestId($requestId)
	{
		$this->_redeemVoidRequestId = $requestId;
		return $this;
	}
	public function getRedeemVoidRequestId()
	{
		return $this->_redeemVoidRequestId;
	}
	public function setAmountToRedeem($amount)
	{
		$this->_amountToRedeem = $amount;
		return $this;
	}
	public function getAmountToRedeem()
	{
		return (float) $this->_amountToRedeem;
	}
	public function setAmountRedeemed($amount)
	{
		$this->_amountRedeemed = $amount;
		return $this;
	}
	public function getAmountRedeemed()
	{
		return (float) $this->_amountRedeemed;
	}
	public function setRedeemCurrencyCode($currencyCode)
	{
		$this->_redeemCurrencyCode = $currencyCode;
		return $this;
	}
	public function getRedeemCurrencyCode()
	{
		// if no currency code has been set, default to the current store's currency code
		if (is_null($this->_redeemCurrencyCode)) {
			$this->setRedeemCurrencyCode(Mage::app()->getStore()->getCurrentCurrencyCode());
		}
		return $this->_redeemCurrencyCode;
	}
	public function setBalanceAmount($amount)
	{
		$this->_balanceAmount = $amount;
		return $this;
	}
	public function getBalanceAmount()
	{
		return (float) $this->_balanceAmount;
	}
	public function setBalanceCurrencyCode($currencyCode)
	{
		$this->_balanceCurrencyCode = $currencyCode;
		return $this;
	}
	public function getBalanceCurrencyCode()
	{
		// if no currency code has been set, default to the current store's currency code
		if (is_null($this->_balanceCurrencyCode)) {
			$this->setBalanceCurrencyCode(Mage::app()->getStore()->getCurrentCurrencyCode());
		}
		return $this->_balanceCurrencyCode;
	}
	public function setIsRedeemed($isRedeemed)
	{
		$this->_isRedeemed = $isRedeemed;
		return $this;
	}
	public function getIsRedeemed()
	{
		return $this->_isRedeemed;
	}
	public function setRedeemedAt(DateTime $redeemedAt)
	{
		$this->_redeemedAt = $redeemedAt;
		return $this;
	}
	public function getRedeemedAt()
	{
		return $this->_redeemedAt ?: new DateTime();
	}

	/**
	 * Log the different requests consistently.
	 *
	 * @param string $type 'balance', 'redeem', 'void'
	 * @param string $body the serialized xml body
	 * @param string $direction 'request' or 'response'
	 */
	protected function _logApiCall($type, $direction)
	{
		$logData = ['type' => $type, 'direction' => $direction];
		$logMessage = 'Processing gift card {type} {direction}.';
		$this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
	}

	public function checkBalance()
	{
		$api = $this->_getApi($this->_helper->getConfigModel()->apiOperationBalance);
		$this->_prepareApiForBalanceCheck($api);
		$this->_logApiCall('balance', 'request');
		$this->_sendRequest($api);
		$this->_logApiCall('balance', 'response');
		$this->_handleBalanceResponse($api);
		return $this;
	}
	public function redeem()
	{
		$api = $this->_getApi($this->_helper->getConfigModel()->apiOperationRedeem);
		$this->_prepareApiForRedeem($api);
		$this->_logApiCall('redeem', 'request');
		$this->_sendRequest($api);
		$this->_logApiCall('redeem', 'response');
		$this->_handleRedeemResponse($api);
		return $this;
	}
	public function void()
	{
		$api = $this->_getApi($this->_helper->getConfigModel()->apiOperationVoid);
		$this->_prepareApiForVoid($api);
		$this->_logApiCall('void', 'request');
		$this->_sendRequest($api);
		$this->_logApiCall('void', 'response');
		$this->_handleVoidResponse($api);
		return $this;
	}
	/**
	 * Get a new SDK Api instance for an API call.
	 * @param  string $operation
	 * @return Api\IBidirectionalApi
	 */
	protected function _getApi($operation)
	{
		return $this->_coreHelper->getSdkApi(
			$this->_helper->getConfigModel()->apiService,
			$operation,
			[$this->getTenderType()],
			// Use a special logger just for the SDK logging, prevents
			// the SDK from logging any PII.
			$this->_apiLogger
		);
	}
	/**
	 * Prepare an API instance for a balance request - fill out and set the
	 * request payload with gift card data.
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 */
	protected function _prepareApiForBalanceCheck(Api\IBidirectionalApi $api)
	{
		$this->setBalanceRequestId($this->_coreHelper->generateRequestId(self::BALANCE_REQUST_ID_PREFIX));
		$payload = $api->getRequestBody();
		$payload
			->setRequestId($this->getBalanceRequestId())
			->setPin($this->getPin())
			->setCurrencyCode($this->getBalanceCurrencyCode());
		$this->_setPayloadAccountUniqueId($payload);
		$api->setRequestBody($payload);
		return $this;
	}
	/**
	 * Prepare an API instance for a balance request - fill out and set the
	 * request payload with gift card data.
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 */
	protected function _prepareApiForRedeem(Api\IBidirectionalApi $api)
	{
		$this->setRedeemRequestId($this->_coreHelper->generateRequestId(self::REDEEM_REQUST_ID_PREFIX));
		$payload = $api->getRequestBody();
		$payload
			->setRequestId($this->getRedeemRequestId())
			->setPin($this->getPin())
			->setAmount($this->getAmountToRedeem())
			->setCurrencyCode($this->getRedeemCurrencyCode());
		$this->_setPayloadPaymentContext($payload);
		$api->setRequestBody($payload);
		return $this;
	}
	/**
	 * Prepare an API instance for a balance request - fill out and set the
	 * request payload with gift card data.
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 */
	protected function _prepareApiForVoid(Api\IBidirectionalApi $api)
	{
		$this->setRedeemVoidRequestId($this->_coreHelper->generateRequestId(self::VOID_REQUST_ID_PREFIX));
		$payload = $api->getRequestBody();
		$payload
			->setRequestId($this->getRedeemVoidRequestId())
			->setPin($this->getPin())
			->setAmount($this->getAmountRedeemed())
			->setCurrencyCode($this->getRedeemCurrencyCode());
		$this->_setPayloadPaymentContext($payload);
		$api->setRequestBody($payload);
		return $this;
	}
	/**
	 * Set the payment context on the payload - consists of an order id and
	 * a paymend account unique id.
	 * @param Payload\Payment\IPaymentContext $payload
	 * @return self
	 */
	protected function _setPayloadPaymentContext(Payload\Payment\IPaymentContext $payload)
	{
		$payload->setOrderId($this->getOrderId());
		return $this->_setPayloadAccountUniqueId($payload);
	}
	/**
	 * Set payment account unique id fields on the payload.
	 * @param Payload\Payment\IPaymentAccountUniqueId $payload
	 * @return self
	 */
	protected function _setPayloadAccountUniqueId(Payload\Payment\IPaymentAccountUniqueId $payload)
	{
		$tokenizedCardNumber = $this->getTokenizedCardNumber();
		$hasTokenizedNumber = !is_null($tokenizedCardNumber);
		$payload
			->setCardNumber($hasTokenizedNumber ? $tokenizedCardNumber : $this->getCardNumber())
			->setPanIsToken($hasTokenizedNumber);
		return $this;
	}
	/**
	 * Send the request via the SDK
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 * @throws EbayEnterprise_GiftCard_Exception If request cannot be made successfully
	 */
	protected function _sendRequest(Api\IBidirectionalApi $api)
	{
		try {
			$api->send();
		} catch (Api\Exception\NetworkError $e) {
			$logMessage = 'Stored value request failed. See exception log for details.';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__));
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
			throw Mage::exception('EbayEnterprise_GiftCard_Exception_Network', $this->_helper->__(self::REQUEST_FAILED_MESSAGE));
		} catch (Payload\Exception\InvalidPayload $e) {
			$logMessage = 'Invalid payload for stored value response. See exception log for details.';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__));
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
			throw Mage::exception('EbayEnterprise_GiftCard', $this->_helper->__(self::REQUEST_FAILED_MESSAGE));
		}
		return $this;
	}
	/**
	 * Check for the balance response to be successful. If it was, update the
	 * gift card with response data. If not, thrown an exception, indicating the
	 * request failed.
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 * @throws EbayEnterprise_GiftCard_Exception If the reply is not successful.
	 */
	protected function _handleBalanceResponse(Api\IBidirectionalApi $api)
	{
		$response = $api->getResponseBody();
		if (!$response->isSuccessful()) {
			throw Mage::exception('EbayEnterprise_GiftCard', self::BALANCE_REQUEST_FAILED_MESSAGE);
		}
		return $this->_extractPayloadAccountUniqueId($response)
			->setBalanceAmount($response->getBalanceAmount())
			->setBalanceCurrencyCode($response->getCurrencyCode());
	}
	/**
	 * Check for the gift card to have been redeemed. If it was, update the
	 * gift card with response data. If not, thrown an exception, indicating the
	 * request failed.
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 * @throws EbayEnterprise_GiftCard_Exception If the card was not redeemed
	 */
	protected function _handleRedeemResponse(Api\IBidirectionalApi $api)
	{
		$response = $api->getResponseBody();
		if (!$response->wasRedeemed()) {
			// redeem failed so empty out any redeem amount
			$this->setAmountRedeemed(0.00);
			throw Mage::exception('EbayEnterprise_GiftCard', self::REDEEM_REQUEST_FAILED_MESSAGE);
		}
		return $this->_extractPayloadAccountUniqueId($response)
			->setBalanceAmount($response->getBalanceAmount())
			->setBalanceCurrencyCode($response->getBalanceCurrencyCode())
			->setAmountRedeemed($response->getAmountRedeemed())
			->setRedeemCurrencyCode($response->getCurrencyCodeRedeemed())
			->setRedeemedAt(new DateTime)
			->setIsRedeemed(true);
	}
	/**
	 * Check for the gift card redeem to have been voided. If it was, update the
	 * gift card with response data. If not, thrown an exception, indicating the
	 * request failed.
	 * @param  Api\IBidirectionalApi $api
	 * @return self
	 * @throws EbayEnterprise_GiftCard_Exception If the redeem was not voided
	 */
	protected function _handleVoidResponse(Api\IBidirectionalApi $api)
	{
		$response = $api->getResponseBody();
		if (!$response->wasVoided()) {
			throw Mage::exception('EbayEnterprise_GiftCard', self::VOID_REQUEST_FAILED_MESSAGE);
		}
		return $this->_extractPayloadAccountUniqueId($response)
			// after voiding a redemption, new balance will be the current
			// balance plus the amount voided (which is the same as the
			// amount originally redeemed)
			->setBalanceAmount($this->getBalanceAmount() + $this->getAmountRedeemed())
			// redeem has been voided, so amount redeemed drops back to 0 and
			// card should no longer be considered to have been redeemed
			->setAmountRedeemed(0.00)
			->setIsRedeemed(false);
	}
	/**
	 * Extract payment context form the payload - ignoring order id in the reply
	 * as Magento is currently the master source for order ids when creating orders.
	 * @param  Payload\Payment\IPaymentContext $payload
	 * @return self
	 */
	protected function _extractPayloadPaymentContext(Payload\Payment\IPaymentContext $payload)
	{
		return $this->_extractPayloadAccountUniqueId($payload);
	}
	/**
	 * Update the gift card with account unique id data from the payload.
	 * @param  Payload\Payment\IPaymentAccountUniqueId $payload
	 * @return self
	 */
	protected function _extractPayloadAccountUniqueId(Payload\Payment\IPaymentAccountUniqueId $payload)
	{
		if ($payload->getPanIsToken()) {
			$this->setTokenizedCardNumber($payload->getCardNumber());
		} else {
			$this->setCardNumber($payload->getCardNumber());
		}
		return $this;
	}
}
