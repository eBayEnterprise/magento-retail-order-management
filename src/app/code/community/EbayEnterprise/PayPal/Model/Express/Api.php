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

use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\IShippingAddress;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemContainer;

/**
 * Payment Method for PayPal payments through Retail Order Management.
 * @SuppressWarnings(TooManyMethods)
 */
class EbayEnterprise_Paypal_Model_Express_Api
{
	const EBAYENTERPRISE_PAYPAL_API_FAILED = 'EBAYENTERPRISE_PAYPAL_API_FAILED';

	const PAYPAL_SETEXPRESS_REQUEST_ID_PREFIX = 'PSE-';
	const PAYPAL_GETEXPRESS_REQUEST_ID_PREFIX = 'PSG-';
	const PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX = 'PSD-';
	const PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX = 'PSA-';
	const PAYPAL_DOVOID_REQUEST_ID_PREFIX = 'PSV-';

	/** @var EbayEnterprise_PayPal_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;

	/**
	 * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
	 * Override __construct here as the usual protected `_construct` is not called.
	 *
	 * @param array $initParams May contain:
	 *                          -  'helper' => EbayEnterprise_PayPal_Helper_Data
	 *                          -  'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
	 */
	public function __construct(array $initParams = array())
	{
		$paypalHelper = Mage::helper('ebayenterprise_paypal');
		$coreHelper = Mage::helper('eb2ccore');
		$logHelper = Mage::helper('ebayenterprise_magelog');
		list($this->_helper, $this->_coreHelper, $this->_logger) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'helper', $paypalHelper),
			$this->_nullCoalesce($initParams, 'core_helper', $coreHelper),
			$this->_nullCoalesce($initParams, 'logger', $logHelper)
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param EbayEnterprise_PayPal_Helper_Data
	 * @param EbayEnterprise_Eb2cCore_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_PayPal_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_MageLog_Helper_Data $logger
	) {
		return [$helper, $coreHelper, $logger];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 *
	 * @param  array
	 * @param  string $field Valid array key
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Set Express Checkout Request/ Response
	 *
	 * @param  string
	 * @param  string
	 * @param  Mage_Sales_Model_Quote
	 * @return array
	 *
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function setExpressCheckout($returnUrl, $cancelUrl, Mage_Sales_Model_Quote $quote)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationSetExpressCheckout
		);

		$payload = $sdk->getRequestBody();
		$payload->setOrderId($quote->reserveOrderId()->getReservedOrderId())
			->setReturnUrl($returnUrl)
			->setCancelUrl($cancelUrl)
			->setLocaleCode(Mage::app()->getLocale()->getDefaultLocale())
			->setAmount($this->_getTotal('grand_total', $quote))
			->setCurrencyCode($quote->getQuoteCurrencyCode());
		if ($quote->getShippingAddress()->getId()) {
			$payload->setAddressOverride(true);
			$this->_addShippingAddress($quote->getShippingAddress(), $payload);
		}
		$this->_addLineItems($quote, $payload);
		$sdk->setRequestBody($payload);
		$this->_logApiCall('set express', $sdk->getRequestBody()->serialize(), 'request');
		$reply = $this->_sendRequest($sdk);
		if (!$reply->isSuccess() || is_null($reply->getToken())) {
			// Only set and do express have the error message in the reply.
			$this->_logger->logWarn('[%s] SetExpressCheckout request failed with message "%s". See exception log for details.', array(__CLASS__, $reply->getErrorMessage()));
			$e = Mage::exception('EbayEnterprise_PayPal', $this->_helper->__(self::EBAYENTERPRISE_PAYPAL_API_FAILED));
			$this->_logger->logException($e);
			throw $e;
		}
		$this->_logApiCall('set express', $reply->serialize(), 'response');
		return [
			'method' => EbayEnterprise_PayPal_Model_Method_Express::CODE,
			'token'  => $reply->getToken()
		];
	}

	/**
	 * Log the different requests consistently.
	 *
	 * @param string $type 'do authorization', 'do express', 'do void', 'get express', 'set express'
	 * @param string $body the serialized xml body
	 * @param string $direction 'request' or 'response'
	 */
	protected function _logApiCall($type, $body, $direction)
	{
		$this->_logger->logInfo('[%s] Processing PayPal %s %s', array(__CLASS__, $type, $direction));
		$this->_logger->logDebug('[%s] %s', array(__CLASS__, $body));
	}

	/**
	 * Get Express Checkout Request/ Response
	 *
	 * @param  Mage_Sales_Model_Quote
	 * @param  string                 $token as from setExpressCheckout
	 * @param  string
	 * @return array
	 *
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function getExpressCheckout($orderId, $token, $currencyCode)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationGetExpressCheckout
		);
		$payload = $sdk->getRequestBody();
		$payload->setOrderId($orderId)
			->setToken($token)
			->setCurrencyCode($currencyCode);
		$sdk->setRequestBody($payload);
		$this->_logApiCall('get express', $sdk->getRequestBody()->serialize(), 'request');
		$reply = $this->_sendRequest($sdk);
		if (!$reply->isSuccess()) {
			$this->_logger->logWarn('[%s] PayPal request failed. See exception log for details.', array(__CLASS__));
			$e = Mage::exception('EbayEnterprise_PayPal', $this->_helper->__(self::EBAYENTERPRISE_PAYPAL_API_FAILED));
			$this->_logger->logException($e);
			throw $e;
		}
		$this->_logApiCall('get express', $reply->serialize(), 'response');
		return [
			'method'           => EbayEnterprise_PayPal_Model_Method_Express::CODE,
			'order_id'         => $reply->getOrderId(),
			'country_id'       => $reply->getPayerCountry(),
			'email'            => $reply->getPayerEmail(),
			'firstname'        => $reply->getPayerFirstName(),
			'payer_id'         => $reply->getPayerId(),
			'lastname'         => $reply->getPayerLastName(),
			'middlename'       => $reply->getPayerMiddleName(),
			'suffix'           => $reply->getPayerNameHonorific(),
			'phone'            => $reply->getPayerPhone(),
			'status'           => $reply->getPayerStatus(),
			'response_code'    => $reply->getResponseCode(),
			'billing_address'  => [
				'street'      => $reply->getBillingLines(),
				'city'        => $reply->getBillingCity(),
				'region_code' => $reply->getBillingMainDivision(),
				'postcode'    => $reply->getBillingPostalCode(),
				'country_id'  => $reply->getBillingCountryCode(),
				'status'      => $reply->getBillingAddressStatus(),
			],
			'shipping_address' => [
				'street'      => $reply->getShipToLines(),
				'city'        => $reply->getShipToCity(),
				'region_code' => $reply->getShipToMainDivision(),
				'postcode'    => $reply->getShipToPostalCode(),
				'country_id'  => $reply->getShipToCountryCode(),
				'status'      => $reply->getShippingAddressStatus(),
			],
		];
	}

	/**
	 * Do Express Checkout Request/ Response
	 *
	 * @param  Mage_Sales_Model_Quote
	 * @param  string                 $token         as from setExpressCheckout
	 * @param  string                 $payerId       as from getExpressCheckout or from a PayPal redirected URL
	 * @param  string                 $pickUpStoreId as from getExpressCheckout or from a PayPal redirected URL (optional)
	 * @return array
	 *
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function doExpressCheckout(Mage_Sales_Model_Quote $quote, $token, $payerId, $pickUpStoreId=null)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationDoExpressCheckout
		);
		$payload = $sdk->getRequestBody();
		$payload->setRequestId(
			$this->_coreHelper->generateRequestId(
				self::PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX
			)
		)
			->setOrderId($quote->reserveOrderId()->getReservedOrderId())
			->setToken($token)
			->setPayerId($payerId)
			->setCurrencyCode($quote->getQuoteCurrencyCode())
			->setAmount($this->_getTotal('grand_total', $quote));
		$this->_addShippingAddress($quote->getShippingAddress(), $payload);
		if ($pickUpStoreId) {
			$payload->setPickUpStoreId($pickUpStoreId);
		}
		$this->_addLineItems($quote, $payload);
		$sdk->setRequestBody($payload);
		$this->_logApiCall('do express', $sdk->getRequestBody()->serialize(), 'request');
		$reply = $this->_sendRequest($sdk);
		if (!$reply->isSuccess()) {
			$this->_logger->logWarn('[%s] PayPal request failed with message "%s". See exception log for details.', array(__CLASS__, $reply->getErrorMessage()));
			$e = Mage::exception('EbayEnterprise_PayPal', $this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
			$this->_logger->logException($e);
			throw $e;
		}
		$this->_logApiCall('do express', $reply->serialize(), 'response');
		return [
			'method'         => EbayEnterprise_PayPal_Model_Method_Express::CODE,
			'order_id'       => $reply->getOrderId(),
			'transaction_id' => $reply->getTransactionId(),
			'response_code'  => $reply->getResponseCode(),
		];
	}

	/**
	 * Do Authorization Request/ Response
	 *
	 * @param  Mage_Sales_Model_Quote
	 * @return array
	 *
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function doAuthorization(Mage_Sales_Model_Quote $quote)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationDoAuthorization
		);
		$payload = $sdk->getRequestBody();
		$payload->setRequestId(
			$this->_coreHelper->generateRequestId(
				self::PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX
			)
		)
			->setOrderId($quote->reserveOrderId()->getReservedOrderId())
			->setCurrencyCode($quote->getQuoteCurrencyCode())
			->setAmount($this->_getTotal('grand_total', $quote));
		$sdk->setRequestBody($payload);
		$this->_logApiCall('do authorization', $sdk->getRequestBody()->serialize(), 'request');
		$reply = $this->_sendRequest($sdk);
		$isSuccess = $reply->isSuccess();
		if (!$isSuccess) {
			$this->_logger->logWarn('[%s] PayPal request failed.', array(__CLASS__));
			$e = Mage::exception('EbayEnterprise_PayPal', $this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
			$this->_logger->logException($e);
			throw $e;
		}
		$this->_logApiCall('do authorization', $reply->serialize(), 'response');
		return [
			'method'         => EbayEnterprise_PayPal_Model_Method_Express::CODE,
			'order_id'       => $reply->getOrderId(),
			'payment_status' => $reply->getPaymentStatus(),
			'pending_reason' => $reply->getPendingReason(),
			'reason_code'    => $reply->getReasonCode(),
			'is_authorized'  => $isSuccess,
		];
	}

	/**
	 * Do Void Request/Response
	 *
	 * @param  Mage_Sales_Model_Order
	 * @return array
	 *
	 * @throws EbayEnterprise_PayPal_Exception when the operation cannot be completed or fails.
	 */
	public function doVoid(Mage_Sales_Model_Order $order)
	{
		$sdk = $this->_getSdk(
			$this->_helper->getConfigModel()->apiOperationDoVoid
		);
		$payload = $sdk->getRequestBody();
		$payload->setOrderId($order->getIncrementId())
			->setRequestId($this->_coreHelper->generateRequestId(self::PAYPAL_DOVOID_REQUEST_ID_PREFIX))
			->setCurrencyCode($order->getOrderCurrencyCode());
		$sdk->setRequestBody($payload);
		$this->_logApiCall('do void', $sdk->getRequestBody()->serialize(), 'request');
		$reply = $this->_sendRequest($sdk);
		$isVoided = $reply->isSuccess();
		if (!$reply->isSuccess()) {
			$this->_logger->logWarn('[%s] PayPal DoVoid failed. See exception log for details.', array(__CLASS__));
			$e = Mage::exception('EbayEnterprise_PayPal', $this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
			$this->_logger->logException($e);
			throw $e;
		}
		$this->_logApiCall('do void', $reply->serialize(), 'response');
		return [
			'method'    => EbayEnterprise_PayPal_Model_Method_Express::CODE,
			'order_id'  => $reply->getOrderId(),
			'is_voided' => $isVoided
		];
	}

	/**
	 * Add an address to a payload
	 *
	 * @param  Mage_Sales_Model_Address_Abstract
	 * @param  IShippingAddress
	 * @return self
	 */
	protected function _addShippingAddress(Mage_Sales_Model_Quote_Address $shippingAddress, IShippingAddress $payload)
	{
		$payload->setShipToLines(implode('\n', $shippingAddress->getStreet()));
		$payload->setShipToCity($shippingAddress->getCity());
		$payload->setShipToMainDivision($shippingAddress->getRegionCode());
		$payload->setShipToCountryCode($shippingAddress->getCountryId());
		$payload->setShipToPostalCode($shippingAddress->getPostcode());
		return $this;
	}

	/**
	 * Send the request via the sdk
	 *
	 * @param  IBidirectionalApi
	 * @return Payload
	 *
	 * @throws EbayEnterprise_PayPal_Exception
	 * @throws EbayEnterprise_PayPal_Exception_Network
	 */
	protected function _sendRequest(IBidirectionalApi $sdk)
	{
		try {
			$sdk->send();
			$reply = $sdk->getResponseBody();
			return $reply;
		} catch (InvalidPayload $e) {
			$this->_logger->logWarn('[%s] PayPal payload invalid. See exception log for details.', array(__CLASS__));
			$this->_logger->logException($e);
		} catch (NetworkError $e) {
			$this->_logger->logWarn('[%s] PayPal request encountered a network error. See exception log for details.', array(__CLASS__));
			$this->_logger->logException($e);
		}
		$e = Mage::exception('EbayEnterprise_PayPal', $this->_helper->__(static::EBAYENTERPRISE_PAYPAL_API_FAILED));
		$this->_logger->logException($e);
		throw $e;
	}

	/**
	 * Get the API SDK for the operation.
	 *
	 * @param  Varien_Object
	 * @return IBidirectionalApi
	 */
	protected function _getSdk($operation)
	{
		$config = $this->_helper->getConfigModel();
		return $this->_coreHelper->getSdkApi($config->apiService, $operation);
	}

	/**
	 * Generate ILineItem objects for each item and add to the container payload.
	 *
	 * @param Mage_Sales_Model_Quote
	 * @param ILineItemContainer
	 */
	protected function _addLineItems(Mage_Sales_Model_Quote $quote, ILineItemContainer $container)
	{
		if ($this->_canIncludeLineItems($quote)) {
			$this->_processLineItems($quote, $container->getLineItems())
				->_processNegativeLineItems($quote, $container->getLineItems());
			$container->calculateLineItemsTotal();
			$container->setShippingTotal($this->_getTotal('shipping', $quote));
			$container->setTaxTotal($this->_getTotal('tax', $quote));
			$container->setCurrencyCode($quote->getQuoteCurrencyCode());
		}
	}

	/**
	 * recursively process line items into payloads
	 * @param  Mage_Sales_Model_Quote
	 * @param  ILineItemIterable
	 * @return self
	 */
	protected function _processLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems)
	{
		$items = $quote->getAllItems();
		$currencyCode = $quote->getQuoteCurrencyCode();
		foreach ($items as $item) {
			$this->_processItem($item, $lineItems, $currencyCode);
		}
		return $this;
	}

	/**
	 * process specific amount types into negative-value line item
	 * payloads
	 * @param  Mage_Sales_Model_Quote
	 * @param  ILineItemIterable
	 * @return self
	 */
	protected function _processNegativeLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems)
	{
		$negativeAmountTypes = array('discount', 'giftcardaccount', 'ebayenterprise_giftcard');
		$currencyCode = $quote->getQuoteCurrencyCode();
		foreach ($negativeAmountTypes as $totalType) {
			$totalAmount = $this->_getTotal($totalType, $quote);
			if ($totalAmount) {
				// ensure all amounts are negative
				$totalAmount = -abs($totalAmount);
				$lineItem = $lineItems->getEmptyLineItem();
				$lineItem->setName($this->_helper->__($totalType))
					->setSequenceNumber($totalType)
					->setQuantity(1)
					->setUnitAmount($totalAmount)
					->setCurrencyCode($currencyCode);
				$lineItems->offsetSet($lineItem, null);
			}
		}
		return $this;
	}

	/**
	 * return true if the line items can be included in the message
	 * @param  Mage_Sales_Model_Quote
	 * @return bool
	 */
	protected function _canIncludeLineItems($quote)
	{
		$reductions = -$this->_getTotal('discount', $quote);
		$reductions += $this->_getTotal('giftcardaccount', $quote);
		$reductions += $this->_getTotal('ebayenterprise_giftcard', $quote);
		// due to the way paypal verifies line items total and the need to send
		// discount/giftcard (admustment) amounts as negative line items, the LineItemsTotal
		// will not match what paypal is expecting when the adustment amounts add up to more
		// than the total amount for the line items.
		return $this->_helper->getConfigModel()->transferLines &&
			$this->_getTotal('subtotal', $quote) >= $reductions;
	}

	/**
	 * Process an item or, if the item has children that are calculated,
	 * the item's children. Processing an item consists of building an ILineItem
	 * payload to be added to the ILineItemIterable. The total for each line is
	 * recursively summed and returned.
	 *
	 * @param  Mage_Sales_Model_Quote_Item_Abstract
	 * @param  ILineItemIterable
	 * @param  string
	 * @return self
	 */
	protected function _processItem(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		ILineItemIterable $lineItems,
		$currencyCode
	) {
		// handle the possibility of getting a Mage_Sales_Model_Quote_Address_Item
		$item = $item->getQuoteItem() ?: $item;
		if ($item->getHasChildren() && $item->isChildrenCalculated()) {
			foreach ($item->getChildren() as $child) {
				$this->_processItem($child, $lineItems, $currencyCode);
			}
		} else {
			$lineItem = $lineItems->getEmptyLineItem();
			$lineItem->setName($this->_helper->__($item->getProduct()->getName()))
				->setSequenceNumber($item->getId())
				->setQuantity($item->getQty())
				->setUnitAmount($item->getPrice())
				->setCurrencyCode($currencyCode);
			$lineItems->offsetSet($lineItem, null);
		}
		return $this;
	}

	/**
	 * Get the specified total amount for the quote.
	 *
	 * @param  string
	 * @param  Mage_Sales_Model_Quote
	 * @return float
	 */
	protected function _getTotal($type, Mage_Sales_Model_Quote $quote)
	{
		$totals = $quote->getTotals();
		return isset($totals[$type]) ? $totals[$type]->getValue() : 0.0;
	}
}
