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

use \eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkException;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use \eBayEnterprise\RetailOrderManagement\Payload\Checkout\IPersonName;
use \eBayEnterprise\RetailOrderManagement\Payload\Checkout\IPhysicalAddress;
use \eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use \eBayEnterprise\RetailOrderManagement\Payload\Exception\UnsupportedOperation;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IDestination;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderContext;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCustomer;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderDestinationIterable;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemIterable;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReferenceContainer;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroupIterable;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IEmailAddressDestination;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\IMailingAddressDestination;

/**
 * Fills out the Order Create Request payload and triggers
 * transmitting it to the service.
 */
class EbayEnterprise_Order_Model_Create
{
	const MAGE_CUSTOMER_GENDER_MALE = 1;
	const LEVEL_OF_SERVICE_REGULAR = 'REGULAR';
	const SHIPPING_CHARGE_TYPE_FLATRATE = 'FLATRATE';

	const ORDER_TYPE_SALES = 'SALES';
	const ORDER_TYPE_PURCHASE = 'PURCHASE';

	// Copy the constant over for interface consistency.
	const STATE_NEW = Mage_Sales_Model_Order::STATE_NEW;
	const STATUS_NEW = 'unsubmitted';

	const STATUS_SENT = 'pending';

	const ORDER_CREATE_FAIL_MESSAGE = 'EbayEnterprise_Order_Create_Fail_Message';

	/** @var string event dispatched before attaching the new payload to the order object */
	protected $_beforeAttachEvent = 'ebayenterprise_order_create_before_attach';
	/** @var string event dispatched before sending the request to ROM */
	protected $_beforeOrderSendEvent = 'ebayenterprise_order_create_before_send';
	/** @var string event dispatched to add payments to the request */
	protected $_paymentDataEvent = 'ebayenterprise_order_create_payment';
	/** @var string event dispatched to add context information to the request */
	protected $_contextDataEvent = 'ebayenterprise_order_create_context';
	/** @var string event dispatched to handle populating ship groups for addresses in the order */
	protected $_shipGroupEvent = 'ebayenterprise_order_create_ship_group';
	/** @var string event dispatched to handle populating order item payloads for items in the order */
	protected $_orderItemEvent = 'ebayenterprise_order_create_item';
	/** @var IBidirectionalApi */
	protected $_api;
	/** @var IOrderCreateRequest */
	protected $_payload;
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
	protected $_config;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;
	/** @var EbayEnterprise_Order_Model_Create_Payment */
	protected $_defaultPaymentHandler;
	/** @var EbayEnterprise_Order_Model_Create_Orderitem */
	protected $_defaultItemHandler;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var int counter to use for assigning line numbers */
	protected $_nextLineNumber = 0;
	/** @var string[] */
	protected $_validGenderStrings = ['M', 'F'];
	/** @var Mage_Core_Model_App */
	protected $_app;

	public function __construct(array $args=[])
	{
		list(
			$this->_logger,
			$this->_helper,
			$this->_coreHelper,
			$this->_defaultPaymentHandler,
			$this->_defaultItemHandler,
			$this->_order,
			$this->_config,
			$this->_api,
			$this->_payload,
			$this->_logContext
		) = $this->_enforceTypes(
			$this->_nullCoalesce('logger', $args, Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce('helper', $args, Mage::helper('ebayenterprise_order')),
			$this->_nullCoalesce('core_helper', $args, Mage::helper('eb2ccore')),
			$this->_nullCoalesce('default_payment_handler', $args, Mage::getModel('ebayenterprise_order/create_payment')),
			$this->_nullCoalesce('default_item_handler', $args, Mage::getModel('ebayenterprise_order/create_orderitem')),
			$args['order'],
			$args['config'],
			$args['api'],
			$args['payload'],
			$this->_nullCoalesce('log_context', $args, Mage::helper('ebayenterprise_magelog/context'))
		);
		// Possibly one valid exception to the DI rule; we're so beholden to the Mage class anyway...
		$this->_app = Mage::app();
	}

	/**
	 * Enforce injected types.
	 *
	 * @param  EbayEnterprise_MageLog_Helper_Data
	 * @param  EbayEnterprise_Order_Helper_Data
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @param  EbayEnterprise_Order_Model_Create_Payment
	 * @param  EbayEnterprise_Order_Model_Create_Orderitem
	 * @param  Mage_Sales_Model_Order
	 * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
	 * @param  IBidirectionalApi
	 * @param  IOrderCreateRequest
	 * @return array
	 */
	protected function _enforceTypes(
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_Order_Helper_Data $helper,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
		EbayEnterprise_Order_Model_Create_Payment $defaultPaymentHandler,
		EbayEnterprise_Order_Model_Create_Orderitem $defaultItemHandler,
		Mage_Sales_Model_Order $order,
		EbayEnterprise_Eb2cCore_Model_Config_Registry $config,
		IBidirectionalApi $api,
		IOrderCreateRequest $payload,
		EbayEnterprise_MageLog_Helper_Context $logContext
	) {
		return [$logger, $helper, $coreHelper, $defaultPaymentHandler, $defaultItemHandler, $order, $config, $api, $payload, $logContext];
	}

	/**
	 * Fill in default values.
	 *
	 * @param  string
	 * @param  array
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce($key, array $arr, $default)
	{
		return isset($arr[$key]) ? $arr[$key] : $default;
	}

	/**
	 * Submit the order create request for the order.
	 *
	 * @return self
	 */
	public function send()
	{
		return $this
			->_prepareOrder()
			->_initPayload()
			->_send();
	}

	/**
	 * Set the order state and status to "new" and "unsubmitted" to start.
	 *
	 * @return self
	 */
	protected function _prepareOrder()
	{
		$state = self::STATE_NEW;
		$status = self::STATUS_NEW;
		$this->_order->setState($state, $status);
		return $this;
	}

	/**
	 * Fill out the order create request.
	 * (If the order already has one we can use, use it;
	 * otherwise create a new one.)
	 *
	 * @return self
	 */
	protected function _initPayload()
	{
		$raw = $this->_order->getEb2cOrderCreateRequest();
		if ($raw) {
			$this->_rebuildPayload($raw);
		} else {
			$this->_buildNewPayload()
				->_attachRequest();
		}
		return $this;
	}

	/**
	 * rebuild the payload by deserializing the previous
	 * request
	 * @param  string previously serialized request
	 * @return self
	 */
	protected function _rebuildPayload($raw)
	{
		try {
			$this->_payload->deserialize($raw);
		} catch (InvalidPayload $e) {
			$this->_logger->critical(
				'Failed to rebuild previous order request {order_id}',
				$this->_logContext->getMetaData(__CLASS__, ['order_id' => $this->_order->getIncrementId()])
			);
		}
		return $this;
	}

	/**
	 * save the request to the order
	 * @return self
	 */
	protected function _attachRequest()
	{
		Mage::dispatchEvent($this->_beforeAttachEvent, [
			'order' => $this->_order,
			'payload' => $this->_payload,
		]);
		try {
			$this->_order
				->setEb2cOrderCreateRequest($this->_payload->serialize());
		} catch (InvalidPayload $e) {
			$this->_logger->critical(
				'Unable to attach request for order {order_id}',
				$this->_logContext->getMetaData(__CLASS__, ['order_id' => $this->_order->getIncrementId()])
			);
		}
		return $this;
	}

	/**
	 * Send the order create request to the api.
	 *
	 * @return self
	 */
	protected function _send()
	{
		Mage::dispatchEvent($this->_beforeOrderSendEvent, [
			'order' => $this->_order,
			'payload' => $this->_payload,
		]);
		try {
			$reply = $this->_api
				->setRequestBody($this->_payload)
				->send()
				->getResponseBody();
		} catch (NetworkException $e) {
			$this->_logger
				->warning(
					'Caught a network error sending order create. Will retry later.',
					$this->_logContext->getMetaData(__CLASS__, [], $e)
				);
			return $this;
		} catch(UnsupportedOperation $e) {
			$this->_logger
				->critical(
					'[%s] Order create request saved, but not sent. See exception log.',
					$this->_logContext->getMetaData(__CLASS__, [], $e)
				);
			return $this;
		} catch (UnsupportedHttpAction $e) {
			$this->_logger
				->critical(
					'[%s] Order create request saved, but not sent. See exception log.',
					$this->_logContext->getMetaData(__CLASS__, [], $e)
				);
			return $this;
		} catch (Exception $e) {
			throw $this->_logUnhandledException($e);
		}
		if ($reply->isSuccessful()) {
			$this->_order->setStatus(self::STATUS_SENT);
		} else {
			throw $this->_logUnhandledException();
		}
		return $this;
	}

	/**
	 * Unhandled exceptions cause the entire order not to get saved.
	 * This is by design, so we don't report a false success or try
	 * to keep sending an order that has no hope for success.
	 *
	 * @param Exception|null The exception to log or null for the default.
	 * @return Exception The same (or default) exception after logging
	 */
	protected function _logUnhandledException(Exception $e=null)
	{
		if (!$e) {
			$errorMessage = $this->_helper->__(self::ORDER_CREATE_FAIL_MESSAGE);
			// Mage::exception adds '_Exception' to the end.
			$exceptionClassName = Mage::getConfig()->getModelClassName('ebayenterprise_order/create');
			$e = Mage::exception($exceptionClassName, $errorMessage);
		}
		$this->_logger
			->warning(
				'Encountered a fatal error attempting to send order create. See the exception log.',
				$this->_logContext->getMetaData(__CLASS__, [], $e)
			);
		return $e;
	}

	/**
	 * Convert the order's billing address into an IMailingAddress
	 * so the SDK can use it.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @return IMailingAddress
	 */
	protected function _getRomBillingAddress(Mage_Customer_Model_Address_Abstract $address)
	{
		$mailingAddress = $this->_payload->getDestinations()->getEmptyMailingAddress();
		return $this->_transferPhysicalAddressData(
			$address,
			$this->_transferPersonNameData($address, $mailingAddress)
		);
	}

	/**
	 * Fill in the values the order create request requires.
	 *
	 * @return self
	 */
	protected function _buildNewPayload()
	{
		$this->_payload
			->setBillingAddress($this->_getRomBillingAddress($this->_order->getBillingAddress()))
			->setCurrency($this->_order->getOrderCurrencyCode())
			->setLevelOfService($this->_config->levelOfService)
			->setLocale($this->_getLocale())
			->setOrderHistoryUrl($this->_helper->getOrderHistoryUrl($this->_order))
			->setOrderId($this->_order->getIncrementId())
			->setOrderTotal($this->_order->getBaseGrandTotal())
			->setOrderType($this->_config->orderType)
			->setRequestId($this->_coreHelper->generateRequestId('OCR-'));
		$createdAt = $this->_getAsDateTime($this->_order->getCreatedAt());
		if ($createdAt) {
			$this->_payload->setCreateTime($createdAt);
		}
		return $this
			->_setCustomerData($this->_order, $this->_payload)
			->_setOrderContext($this->_order, $this->_payload)
			->_setShipGroups($this->_order, $this->_payload)
			->_setPaymentData($this->_order, $this->_payload);
	}

	/**
	 * get the locale code for the order
	 *
	 * @return string
	 */
	protected function _getLocale()
	{
		$languageCode = $this->_coreHelper->getConfigModel()->setStore($this->_order->getStore())->languageCode;
		$splitCode = explode('-', $languageCode);
		if (!empty($splitCode[0]) && !empty($splitCode[1])) {
			$result = strtolower($splitCode[0]) . '_' . strtoupper($splitCode[1]);
		} else {
			$logData = ['order_id' => $this->_order->getIncrementId(), 'language_code' => $languageCode];
			$this->_logger->critical(
				"The store for order '{order_id}' is configured with an invalid language code: '{language_code}'",
				$this->_logContext->getMetaData(__CLASS__, $logData)
			);
			$result = '';
		}
		return $result;
	}

	/**
	 * Set Customer information on the payload
	 *
	 * @param Mage_Sales_Model_Order
	 * @param IOrderCustomer
	 * @return self
	 */
	protected function _setCustomerData(Mage_Sales_Model_Order $order, IOrderCustomer $payload)
	{
		$payload
			->setFirstName($order->getCustomerFirstname())
			->setLastName($order->getCustomerLastname())
			->setMiddleName($order->getCustomerMiddlename())
			->setHonorificName($order->getCustomerPrefix())
			->setGender($this->_getCustomerGender($order))
			->setCustomerId($this->_getCustomerId($order))
			->setEmailAddress($order->getCustomerEmail())
			->setTaxId($order->getCustomerTaxvat())
			->setIsTaxExempt($order->getCustomer()->getTaxExempt());
		$dob = $this->_getAsDateTime($order->getCustomerDob());
		if ($dob) {
			$payload->setDateOfBirth($dob);
		}
		return $this;
	}

	/**
	 * get an id for the customer
	 * @return string
	 */
	protected function _getCustomerId()
	{
		$prefix = $this->_coreHelper->getConfigModel()
			->setStore($this->_order->getStore())
			->clientCustomerIdPrefix;
		return $prefix . ($this->_order->getCustomerId() ?: $this->_getGuestCustomerId());
	}

	/**
	 * generate a customer id for a guest
	 * @return string
	 */
	protected function _getGuestCustomerId()
	{
		$sessionIdHash = hash('sha256', $this->_getCustomerSession()->getEncryptedSessionId());
		// when placing the order as a guest, there is no customer increment;
		// use a hash of the session id instead.
		return substr($sessionIdHash, 0, 35);
	}

	/**
	 * get the customer session
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Add payment payloads to the request
	 *
	 * @param  Mage_Sales_Model_Order
	 * @param  IPaymentContainer
	 * @return self
	 */
	protected function _setPaymentData(Mage_Sales_Model_Order $order, IPaymentContainer $paymentContainer)
	{
		// allow event handlers to communicate whether a payment
		// was handled or not
		$processedPayments = new SplObjectStorage();
		Mage::dispatchEvent($this->_paymentDataEvent, [
			'order' => $order,
			'payment_container' => $paymentContainer,
			// Any handler of this event should attach payments to
			// the processed payments object to signify that a payload
			// was created. Handlers should avoid creating a new
			// payload for any payment in the set of processed
			// payments to avoid adding duplicate payment information
			// to the request.
			'processed_payments' => $processedPayments,
		]);
		$this->_defaultPaymentHandler
			->addPaymentsToPayload($order, $paymentContainer, $processedPayments);
		return $this;
	}

	/**
	 * Add order context information to the request
	 *
	 * @param Mage_Sales_Model_Order
	 * @param IOrderContext
	 * @return self
	 */
	protected function _setOrderContext(Mage_Sales_Model_Order $order, IOrderContext $orderContext)
	{
		Mage::dispatchEvent($this->_contextDataEvent, [
			'order' => $order,
			'order_context' => $orderContext,
		]);
		return $this;
	}

	/**
	 * Add ship groups to the request. For each address in the order, dispatch
	 * an event for handling ship group destinations. For each ship group
	 * destination added, trigger additional events for each item in the ship
	 * group.
	 *
	 * @param Mage_Sales_Model_Order
	 * @param IOrderCreateRequest
	 * @return self
	 */
	protected function _setShipGroups(Mage_Sales_Model_Order $order, IOrderCreateRequest $request)
	{
		$shipGroups = $request->getShipGroups();
		$orderItems = $request->getOrderItems();
		$destinations = $request->getDestinations();
		$itemCollection = $order->getItemsCollection();
		foreach ($order->getAddressesCollection() as $address) {
			$items = $this->_getItemsForAddress($address, $itemCollection);
			if ($items) {
				$shipGroups->offsetSet($this->_buildShipGroupForAddress(
					$address, $items, $order, $shipGroups, $destinations, $orderItems
				));
			}
		}
		return $this;
	}

	/**
	 * Create a new ship group for the address and dispatch events to add
	 * destination, gifting and item data to the ship group.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param Mage_Sales_Model_Order_Item[]
	 * @param Mage_Sales_Model_Order
	 * @param IShipGroupIterable
	 * @param IOrderDestinationIterable
	 * @param IOrderItemIterable
	 * @return IShipGroup
	 */
	protected function _buildShipGroupForAddress(
		Mage_Customer_Model_Address_Abstract $address,
		array $items,
		Mage_Sales_Model_Order $order,
		IShipGroupIterable $shipGroups,
		IOrderDestinationIterable $destinations,
		IOrderItemIterable $orderItems
	) {
		$shipGroup = $shipGroups->getEmptyShipGroup();
		// default set this value to flatrate shipping since magento doesn't
		// currently allow us to figure out how much each item contributes to
		// shipping. The value can be changed by responding to the following
		// event.
		$shipGroup->setChargeType(self::SHIPPING_CHARGE_TYPE_FLATRATE);
		Mage::dispatchEvent($this->_shipGroupEvent, [
			'address' => $address,
			'order' => $order,
			'ship_group_payload' => $shipGroup,
			'order_destinations_payload' => $destinations,
		]);
		// If none of the event observers added a destination, include a default
		// mapping of the address to a destination.
		if (is_null($shipGroup->getDestination())) {
			$shipGroup->setDestination($this->_buildDefaultDestination($address, $destinations));
		}
		return $this->_addOrderItemReferences($shipGroup, $items, $orderItems, $address, $order);
	}

	/**
	 * @param IShipGroup
	 * @param Mage_Sales_Model_Order_Item[]
	 * @param IOrderItemIterable
	 * @param Mage_Sales_Model_Order_Address
	 * @param Mage_Sales_Model_Order
	 */
	protected function _addOrderItemReferences(
		IShipGroup $shipGroup,
		array $items,
		IOrderItemIterable $orderItems,
		Mage_Customer_Model_Address_Abstract $address,
		Mage_Sales_Model_Order $order
	) {
		$itemReferences = $shipGroup->getItemReferences();
		foreach ($items as $item) {
			$shippingChargeType = $shipGroup->getChargeType();
			$itemPayload = $orderItems->getEmptyOrderItem();
			$this->_defaultItemHandler->buildOrderItem(
				$itemPayload,
				$item,
				$order,
				$address,
				$this->_nextLineNumber += 1,
				$shippingChargeType
			);
			Mage::dispatchEvent($this->_orderItemEvent, [
				'item' => $item,
				'item_payload' => $itemPayload,
				'order' => $order,
				'address' => $address,
				'line_number' => $this->_nextLineNumber,
				'shipping_charge_type' => $shippingChargeType,
			]);
			$itemReferences->offsetSet(
				$itemReferences->getEmptyItemReference()->setReferencedItem($itemPayload)
			);
		}
		$shipGroup->setItemReferences($itemReferences);
		return $shipGroup;
	}

	/**
	 * Get all items shipping to a given address. For billing addresses, this
	 * will be all virtual items in the order. For shipping addresses, any
	 * non-virtual items.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param Mage_Sales_Model_Resource_Order_Item_Collection
	 * @return Mage_Sales_Model_Order_Item[]
	 */
	protected function _getItemsForAddress(
		Mage_Customer_Model_Address_Abstract $address,
		Mage_Sales_Model_Resource_Order_Item_Collection $orderItems
	) {
		return $orderItems->getItemsByColumnValue(
			'is_virtual',
			$this->_isAddressBilling($address)
		);
	}

	/**
	 * Build a default destination for an address. For billing addresses, this
	 * should result in an email address destination - destination for virtual
	 * items. For shipping addresses, a mailing address destination.
	 *
	 * @param Mage_Sales_Mdoel_Order_Address
	 * @param IOrderDestinationIterable
	 * @return IDestination
	 */
	protected function _buildDefaultDestination(Mage_Customer_Model_Address_Abstract $address, IOrderDestinationIterable $destinations)
	{
		return $this->_isAddressBilling($address)
			? $this->_buildVirtualDestination($address, $destinations)
			: $this->_buildPhysicalDestination($address, $destinations);
	}

	/**
	 * Create a new mailing address destination from a magento order address.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param IOrderDestinationIterable Used to create the new email address destination payload
	 * @return IMailingAddressDestination
	 */
	protected function _buildPhysicalDestination(Mage_Customer_Model_Address_Abstract $address, IOrderDestinationIterable $destinations)
	{
		$destination = $destinations->getEmptyMailingAddress();
		return $this->_transferPhysicalAddressData(
			$address,
			$this->_transferPersonNameData($address, $destination)
		);
	}

	/**
	 * Create a new mailing address destination from a magento order address.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param IOrderDestinationIterable Used to create the new email address destination payload
	 * @return IEmailAddressDestination
	 */
	protected function _buildVirtualDestination(Mage_Customer_Model_Address_Abstract $address, IOrderDestinationIterable $destinations)
	{
		$destination = $destinations->getEmptyEmailAddress();
		return $this->_transferPersonNameData($address, $destination)
			->setEmailAddress($address->getEmail());
	}

	/**
	 * Transfer person name data from the order address to the person name payload.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param IPersonName
	 * @return IPersonName
	 */
	protected function _transferPersonNameData(Mage_Customer_Model_Address_Abstract $address, IPersonName $personName)
	{
		return $personName
			->setFirstName($address->getFirstname())
			->setLastName($address->getLastname())
			->setMiddleName($address->getMiddlename())
			->setHonorificName($address->getPrefix());
	}

	/**
	 * Transfer physical address data from the order address to the physical
	 * address payload.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param IPhysicalAddress
	 * @return IPhysicalAddress
	 */
	protected function _transferPhysicalAddressData(Mage_Customer_Model_Address_Abstract $address, IPhysicalAddress $physicalAddress)
	{
		return $physicalAddress
			// get all address street lines as a single, newline-delimited string
			->setLines($address->getStreet(-1))
			->setCity($address->getCity())
			->setMainDivision($address->getRegionCode())
			->setCountryCode($address->getCountryId())
			->setPostalCode($address->getPostcode())
			->setPhone($address->getTelephone());
	}

	/**
	 * Check for an order address to be a billing address.
	 *
	 * @param Mage_Customer_Model_Address_Abstract
	 * @return bool
	 */
	protected function _isAddressBilling(Mage_Customer_Model_Address_Abstract $address) {
		return $address->getAddressType() === Mage_Customer_Model_Address_Abstract::TYPE_BILLING;
	}

	/**
	 * convert a mage date string to a datetime.
	 * if $dateString is invalid, return false.
	 * @param  string
	 * @return DateTime|false
	 */
	protected function _getAsDateTime($dateString)
	{
		return DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
	}

	/**
	 * Get the gender code for the customer
	 * @param  Mage_Sales_Model_Order
	 * @return string|null
	 */
	protected function _getCustomerGender(Mage_Sales_Model_Order $order)
	{
		return $this->_nullCoalesce(
			$this->_getCustomerGenderLabel($order),
			$this->_getValidGenderMappings(),
			null
		);
	}

	/**
	 * get the valid set of associations for mapping Magento gender labels
	 * to ROM gender strings.
	 * @return array
	 */
	protected function _getValidGenderMappings()
	{
		// get the mapping from the config and filter it down so that only
		// valid mappings exist
		return array_intersect(
			(array) $this->_config->genderMap,
			$this->_validGenderStrings
		);
	}

	/**
	 * get the label for the customer's gender
	 * @param  Mage_Sales_Model_Order
	 * @return string
	 */
	protected function _getCustomerGenderLabel(Mage_Sales_Model_Order $order)
	{
		$customerGenderLabel = null;
		// get the label for the customer's gender and use the filtered mapping to
		// get the ROM equivalent.
		$optionId = $order->getCustomerGender();
		foreach ($this->_getGenderOptions() as $option) {
			if ($option['value'] === $optionId) {
				$customerGenderLabel = $option['label'];
				break;
			}
		}
		return $customerGenderLabel;
	}

	/**
	 * get available gender options
	 * @return array
	 */
	protected function _getGenderOptions()
	{
		return (array) Mage::getResourceSingleton('customer/customer')
			->getAttribute('gender')
			->getSource()
			->getAllOptions();
	}
}
